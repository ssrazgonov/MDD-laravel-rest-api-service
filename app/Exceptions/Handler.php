<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class Handler extends ExceptionHandler
{
    public function render($request, Throwable $exception)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return $this->renderApiException($exception);
        }

        return parent::render($request, $exception);
    }

    private function renderApiException(Throwable $exception): JsonResponse
    {        
        return response()->json()->apiError(
            message: $exception->getMessage() ?: 'Internal Server Error',
            code: $this->getStatusCode($exception)
        );
    }

    private function getStatusCode(Throwable $exception): int
    {
        return match (true) {
            $exception instanceof RuntimeException => 400,
            default => 500
        };
    }
}