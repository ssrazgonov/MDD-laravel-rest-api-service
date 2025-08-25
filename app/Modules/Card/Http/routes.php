<?php

use App\Modules\Card\Http\Controllers\Api\V1\CardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api', 'keycloak'])->prefix('api/v1')->group(function () {
    Route::apiResource('card', CardController::class);
    Route::post('card/activate', [CardController::class, 'activate']);
    Route::post('card/deactivate', [CardController::class, 'deactivate']);
});