<?php

use App\Modules\Card\Http\Controllers\Api\V1\CardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api', 'keycloak'])->prefix('api/v1')->group(function () {
    Route::apiResource('cards', CardController::class);
    Route::post('cards/{id}/activate', [CardController::class, 'activate']);
    Route::post('cards/{id}/deactivate', [CardController::class, 'deactivate']);

    Route::get('members/{memberId}/cards', [CardController::class, 'getMemberCards']);
    Route::get('members/{memberId}/cards/{cardId}', [CardController::class, 'getMemberCard']);
});
