<?php

namespace App\Modules\Card\Http\Controllers\Api\V1;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller;

class CardController extends Controller
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
}