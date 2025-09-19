<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Hash;
use function App\Helpers\errorResponse;
use function App\Helpers\successResponse;
use Illuminate\Auth\Events\PasswordReset;

class AuthService
{
    /**
     * Register a new user with role assignment and token creation.
     *
     * @param  object  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function registerUser($request)
    {
        try {
            DB::beginTransaction();

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'is_active' => true,
            ]);

            // Assign default role
            $defaultRole = $request->role ?? 'cashier';
            if ($user->hasRole('super_admin') || auth()->user()?->hasRole(['super_admin', 'manager'])) {
                $user->assignRole($defaultRole);
            } else {
                $user->assignRole('cashier'); // Default role for self-registration
            }

            // Create API token
            $token = $user->createToken('pos_api_token', ['*'], Carbon::now()->addDays(30))->plainTextToken;
            $response = [
                'user' => new UserResource($user->load('roles')),
                'token' => $token,
                'token_type' => 'Bearer',
                'expires_at' => Carbon::now()->addDays(30)->toISOString(),
            ];

            DB::commit();

            return successResponse('User registered successfully', $response, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse('Registration failed', 500, $e->getMessage());
        }
    }

    /**
     * Handle user login with account status checks, token management, and failed attempt logging.
     * @param  object  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function loginUser($request)
    {
        // Check if user exists and is active
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return errorResponse('User not found', Response::HTTP_NOT_FOUND);
        }

        if (!$user->is_active) {
            return errorResponse('Account is deactivated. Please contact administrator.', Response::HTTP_FORBIDDEN);
        }

        // Attempt authentication
        if (!Hash::check($request->password, $user->password)) {
            // Log failed attempt
            $this->logFailedLogin($request->email, $request->ip());

            return errorResponse('Invalid credentials', Response::HTTP_UNAUTHORIZED);
        }

        // Check for too many failed attempts (optional rate limiting)
        if ($this->hasTooManyFailedAttempts($request->email)) {
            return errorResponse('Too many failed attempts. Please try again later.', Response::HTTP_TOO_MANY_REQUESTS);
        }

        // Revoke existing tokens if requested (single session)
        if ($request->revoke_existing_tokens) {
            $user->tokens()->delete();
        }

        // Create new token with abilities based on user roles
        $abilities = $this->getUserAbilities($user);
        $token = $user->createToken(
            'pos_api_token',
            $abilities,
            Carbon::now()->addDays(30)
        )->plainTextToken;

        // Update last login
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip()
        ]);

        // Clear failed attempts
        $this->clearFailedAttempts($request->email);

        $response = [
            'user' => new UserResource($user->load('roles.permissions')),
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_at' => Carbon::now()->addDays(30)->toISOString(),
            'abilities' => $abilities,
        ];

        return successResponse('Login successful', $response);
    }

    /**
     * Send password reset link to user's email.
     * @param  object  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function forgotPassword($request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return errorResponse('User not found', Response::HTTP_NOT_FOUND);
        }

        if (!$user->is_active) {
            // Account is deactivated
            return errorResponse('Account is deactivated', Response::HTTP_FORBIDDEN);
        }

        // Generate and store password reset token
        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => Hash::make($token),
                'created_at' => now()
            ]
        );

        // Send password reset email
        try {
            $user->notify(new \App\Notifications\PasswordResetNotification($token));

            return successResponse('Password reset link sent to your email');

        } catch (\Exception $e) {
            return errorResponse('Failed to send reset email', 500, $e->getMessage());
        }
    }

    /**
     * Reset password
     * @param  object  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetPassword($request)
    {
        // Verify the token
        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$resetRecord || !Hash::check($request->token, $resetRecord->token)) {
            return errorResponse('Invalid or expired reset token', Response::HTTP_BAD_REQUEST);
        }

        // Check token expiry (24 hours)
        if (Carbon::parse($resetRecord->created_at)->addHours(24)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();

            return errorResponse('Reset token has expired', Response::HTTP_BAD_REQUEST);
        }

        // Update password
        $user = User::where('email', $request->email)->first();
        $user->update([
            'password' => Hash::make($request->password),
            'password_changed_at' => now()
        ]);

        // Delete the reset token
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        // Revoke all existing tokens for security
        $user->tokens()->delete();

        // Fire password reset event
        event(new PasswordReset($user));

        return successResponse('Password reset successfully');
    }

    /**
     * Logout user by revoking current token
     * @param  object  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout($request)
    {

        try {
            // Get current token and revoke it
            $request->user()->currentAccessToken()->delete();

            return successResponse('Logged out successfully');

        } catch (\Exception $e) {
            return errorResponse('Logout failed', 500, $e->getMessage());
        }
    }

    /**
     * Logout user from all devices by revoking all tokens
     * @param  object  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logoutAll($request)
    {
        try {
            $request->user()->tokens()->delete();

            return successResponse('Logged out from all devices successfully');

        } catch (\Exception $e) {
            return errorResponse('Logout failed', 500, $e->getMessage());
        }
    }

    /**
     * Update user profile
     * @param  object  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfile($request)
    {
        try {
            $user = $request->user();

            $updateData = $request->only(['name', 'phone']);

            // Handle email change (may require verification)
            if ($request->email && $request->email !== $user->email) {
                $updateData['email'] = $request->email;
                $updateData['email_verified_at'] = null; // Reset verification
            }

            $user->update($updateData);

            return successResponse('Profile updated successfully', new UserResource($user->fresh()));

        } catch (\Exception $e) {
            return errorResponse('Profile update failed', 500, $e->getMessage());
        }
    }

    /**
     * Change user password with current password verification and optional token revocation.
     * @param  object  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changePassword($request)
    {
        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return errorResponse('Current password is incorrect', Response::HTTP_BAD_REQUEST);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
            'password_changed_at' => now()
        ]);

        // Optionally revoke all tokens except current
        if ($request->logout_other_devices) {
            $currentTokenId = $request->user()->currentAccessToken()->id;
            $user->tokens()->where('id', '!=', $currentTokenId)->delete();
        }

        return successResponse('Password changed successfully');
    }

    /**
     * Refresh the current token by revoking it and issuing a new one.
     * @param  object  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function refreshToken($request)
    {
        try {
            $user = $request->user();
            $currentToken = $request->user()->currentAccessToken();

            // Delete current token
            $currentToken->delete();

            // Create new token
            $abilities = $this->getUserAbilities($user);
            $newToken = $user->createToken(
                'pos_api_token',
                $abilities,
                Carbon::now()->addDays(30)
            )->plainTextToken;

            $response = [
                'token' => $newToken,
                'token_type' => 'Bearer',
                'expires_at' => Carbon::now()->addDays(30)->toISOString(),
            ];

            return successResponse('Token refreshed successfully', $response);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token refresh failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Log failed login attempt
     */
    private function logFailedLogin(string $email, string $ip): void
    {
        DB::table('failed_login_attempts')->insert([
            'email' => $email,
            'ip_address' => $ip,
            'attempted_at' => now()
        ]);
    }

    /**
     * Check if user has too many failed attempts
     */
    private function hasTooManyFailedAttempts(string $email): bool
    {
        $attempts = DB::table('failed_login_attempts')
            ->where('email', $email)
            // ->where('attempted_at', '>=', now()->subHours(1))
            ->where('attempted_at', '>=', now()->subMinutes(1))
            ->count();

        return $attempts >= 5; // 5 attempts per hour
    }

    /**
     * Get user abilities based on roles
     */
    private function getUserAbilities(User $user): array
    {
        $abilities = ['*']; // Default: all abilities

        // You can customize abilities based on roles
        if ($user->hasRole('cashier')) {
            $abilities = [
                'sales:create',
                'sales:view',
                'sale-returns:create'
            ];
        } elseif ($user->hasRole('manager')) {
            $abilities = [
                'sales:*',
                'purchases:*',
                'returns:approve',
                'transfers:*',
                'stock:adjust',
                'reports:view'
            ];
        } elseif ($user->hasRole('super_admin')) {
            $abilities = ['*'];
        }

        return $abilities;
    }

    /**
     * Clear failed login attempts
     */
    private function clearFailedAttempts(string $email): void
    {
        DB::table('failed_login_attempts')->where('email', $email)->delete();
    }
}
