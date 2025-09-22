<?php

use App\Modules\S2P\Http\Controllers\S2PController;
use Illuminate\Support\Facades\Route;

Route::get('/api/v1/user/services/usage', [S2PController::class, 'getUsage']);
