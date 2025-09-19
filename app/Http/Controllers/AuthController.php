<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\ForgotPasswordRequest;

class AuthController extends Controller
{
     /**
     * Register a new user
     */
    public function register(RegisterRequest $request): JsonResponse
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

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'User registered successfully',
                'data' => [
                    'user' => new UserResource($user->load('roles')),
                    'token' => $token,
                    'token_type' => 'Bearer',
                    'expires_at' => Carbon::now()->addDays(30)->toISOString(),
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * User login
     */
    public function login(LoginRequest $request): JsonResponse
    {
        // Check if user exists and is active
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Account is deactivated. Please contact administrator.'
            ], 403);
        }

        // Attempt authentication
        if (!Hash::check($request->password, $user->password)) {
            // Log failed attempt
            $this->logFailedLogin($request->email, $request->ip());

            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        // Check for too many failed attempts (optional rate limiting)
        if ($this->hasTooManyFailedAttempts($request->email)) {
            return response()->json([
                'success' => false,
                'message' => 'Too many failed attempts. Please try again later.'
            ], 429);
        }
        // dd($request->revoke_existing_tokens);
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

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => new UserResource($user->load('roles.permissions')),
                'token' => $token,
                'token_type' => 'Bearer',
                'expires_at' => Carbon::now()->addDays(30)->toISOString(),
                'abilities' => $abilities,
            ]
        ]);
    }

    /**
     * Forgot password
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Account is deactivated'
            ], 403);
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

            return response()->json([
                'success' => true,
                'message' => 'Password reset link sent to your email'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send reset email',
                'error' => config('app.debug') ? $e->getMessage() : 'Email service error'
            ], 500);
        }
    }

    /**
     * Reset password
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        // Verify the token
        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$resetRecord || !Hash::check($request->token, $resetRecord->token)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired reset token'
            ], 400);
        }

        // Check token expiry (24 hours)
        if (Carbon::parse($resetRecord->created_at)->addHours(24)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();

            return response()->json([
                'success' => false,
                'message' => 'Reset token has expired'
            ], 400);
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

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully'
        ]);
    }

    /**
     * Logout user (revoke current token)
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            // Get current token and revoke it
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Logout failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Logout from all devices (revoke all tokens)
     */
    public function logoutAll(Request $request): JsonResponse
    {
        try {
            $request->user()->tokens()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Logged out from all devices successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Logout failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get current user profile
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user()->load('roles.permissions');

        return response()->json([
            'success' => true,
            'data' => new UserResource($user)
        ]);
    }

    /**
     * Update user profile
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
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

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => new UserResource($user->fresh())
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Profile update failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Change password
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect'
            ], 400);
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

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully'
        ]);
    }

    /**
     * Refresh token
     */
    public function refreshToken(Request $request): JsonResponse
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

            return response()->json([
                'success' => true,
                'message' => 'Token refreshed successfully',
                'data' => [
                    'token' => $newToken,
                    'token_type' => 'Bearer',
                    'expires_at' => Carbon::now()->addDays(30)->toISOString(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token refresh failed',
                'error' => $e->getMessage()
            ], 500);
        }
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
                'sales:create', 'sales:view', 'sale-returns:create'
            ];
        } elseif ($user->hasRole('manager')) {
            $abilities = [
                'sales:*', 'purchases:*', 'returns:approve',
                'transfers:*', 'stock:adjust', 'reports:view'
            ];
        } elseif ($user->hasRole('super_admin')) {
            $abilities = ['*'];
        }

        return $abilities;
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
     * Clear failed login attempts
     */
    private function clearFailedAttempts(string $email): void
    {
        DB::table('failed_login_attempts')->where('email', $email)->delete();
    }
}
