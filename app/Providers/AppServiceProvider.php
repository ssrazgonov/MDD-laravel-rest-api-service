<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Debug\ExceptionHandler;
use App\Exceptions\Handler;
use Illuminate\Http\JsonResponse;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ExceptionHandler::class, Handler::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        JsonResponse::macro('api', function (mixed $data = null, int $code = 200) {
            return response()->json([
                'status' => true,
                'error' => null,
                'data' => $data
            ], $code);
        });

        JsonResponse::macro('apiError', function (string $message, int $code = 500, mixed $data = null) {
            return response()->json([
                'status' => false,
                'error' => [
                    'code' => $code,
                    'message' => $message
                ],
                'data' => $data
            ], $code >= 400 && $code < 600 ? $code : 500);
        });
    }
}
