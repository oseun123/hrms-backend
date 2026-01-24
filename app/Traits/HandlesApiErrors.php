<?php

namespace App\Traits;

use App\Helpers\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

trait HandlesApiErrors
{
    /**
     * Handle common controller exceptions
     */
    protected function handleException(\Exception $e, string $context = 'Operation')
    {
        if ($e instanceof ValidationException) {
            return ApiResponse::validationError('Validation failed', $e->errors());
        }

        if ($e instanceof ModelNotFoundException) {
            return ApiResponse::notFound('Resource not found');
        }

        // Log the error
        Log::error("{$context} failed: ".$e->getMessage(), [
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);

        return ApiResponse::serverError("{$context} failed. Please try again.");
    }
}
