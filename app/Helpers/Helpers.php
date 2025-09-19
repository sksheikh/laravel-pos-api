<?php
namespace App\Helpers;

use Illuminate\Support\Facades\Log;

/**
 * Generate standardized pagination metadata.
 *
 * @param  \Illuminate\Pagination\LengthAwarePaginator  $data
 * @return array
 */
if (!function_exists('metaPagination')) {
    function metaPagination($data)
    {
        return [
            'current_page' => $data->currentPage(),
            'last_page' => $data->lastPage(),
            'per_page' => $data->perPage(),
            'total' => $data->total(),
            'from' => $data->firstItem(),
            'to' => $data->lastItem(),
        ];
    }
}

/**
 * Standardized success response.
 *
 * @param  string  $message
 * @param  mixed  $data
 * @param  int  $status
 * @return \Illuminate\Http\JsonResponse
 */
if(!function_exists('successResponse')){
    function successResponse($message, $data = null, $status = 200){
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $status);
    }
}


/**
 * Standardized error response with logging.
 *
 * @param  string  $message
 * @param  int  $status
 * @param  mixed  $errors
 * @return \Illuminate\Http\JsonResponse
 */
if(!function_exists('errorResponse')){
    function errorResponse($message = 'Something went wrong', $status = 500, $errors = null){
        Log::error($errors);

        return response()->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }
}
