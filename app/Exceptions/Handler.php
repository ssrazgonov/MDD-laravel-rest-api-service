<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class Handler extends ExceptionHandler
{
    public function render($request, Throwable $exception)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            if ($exception instanceof ValidationException) {
                if ($this->shouldUseDefaultValidationResponse($exception)) {
                    return parent::render($request, $exception);
                }
            }
            
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

    private function shouldUseDefaultValidationResponse(ValidationException $exception): bool
    {
        $errors = $exception->errors();
        
        foreach ($errors as $field => $messages) {
            if (in_array($field, ['status', 'comment', 'pan'])) {
                return true;
            }
        }
        
        return false;
    }

    private function getStatusCode(Throwable $exception): int
    {
        return match (true) {
            $exception instanceof ModelNotFoundException => 404,
            $exception instanceof AuthenticationException => 401,
            $exception instanceof RuntimeException => 400,
            $exception instanceof ValidationException => $this->getValidationStatusCode($exception),
            default => 500
        };
    }
    
    private function getValidationStatusCode(ValidationException $exception): int
    {
        if ($this->shouldUseDefaultValidationResponse($exception)) {
            return 422;
        }
        
        return 400;
    }
}