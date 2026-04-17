<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ApiResponse
{
    /**
     * Build a standardized success response.
     */
    public static function success(mixed $data = null, int $status = Response::HTTP_OK): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $data,
        ], $status);
    }

    /**
     * Build a standardized error response.
     */
    public static function error(string $message, int $status, string $code, array $errors = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'code'    => $code,
            'errors'  => (object) $errors,
        ], $status);
    }
}
