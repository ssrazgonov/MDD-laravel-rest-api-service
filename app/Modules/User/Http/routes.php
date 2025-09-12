<?php

use App\Modules\User\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api', 'keycloak'])->prefix('api/v1')->group(function () {
    Route::get('user/members', [UserController::class, 'members']);
});
