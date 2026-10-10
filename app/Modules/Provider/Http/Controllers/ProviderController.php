<?php

namespace App\Modules\Provider\Http\Controllers;

use App\Modules\Provider\Actions\GetServicesUsage;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ProviderController extends Controller
{
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;

    public function getUsage(Request $request, GetServicesUsage $getUsageAction)
    {
        $usages = ($getUsageAction)(dateFrom: $request->date_from, dateTo: $request->date_to, clientId: $request->client_id);

        return response()->json()->api($usages);
    }
}
