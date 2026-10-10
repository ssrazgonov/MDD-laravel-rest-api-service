<?php

use App\Modules\Provider\Http\Controllers\ProviderController;
use Illuminate\Support\Facades\Route;

Route::get('/api/v1/user/services/usage', [ProviderController::class, 'getUsage']);
