<?php

use App\Modules\Card\Http\Controllers\Api\V1\CardController;
use Illuminate\Support\Facades\Route;

Route::middleware([])->prefix('api/v1')->group(function () {
    Route::apiResource('card', CardController::class);
});