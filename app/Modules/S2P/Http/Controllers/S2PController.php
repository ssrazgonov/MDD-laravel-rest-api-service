<?php

namespace App\Modules\S2P\Http\Controllers;

use App\Modules\S2P\Actions\GetUsageFromS2P;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class S2PController extends Controller
{
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;

    public function getUsage(Request $request, GetUsageFromS2P $getUsageAction)
    {
        $usages = ($getUsageAction)(dateFrom: $request->date_from, dateTo: $request->date_to, clientId: $request->client_id);

        return response()->json()->api($usages);
    }
}
