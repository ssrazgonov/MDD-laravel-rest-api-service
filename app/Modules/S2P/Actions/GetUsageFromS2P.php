<?php

namespace App\Modules\S2P\Actions;

use App\Modules\S2P\Contracts\GetUsage as ContractGetUsage;
use App\Modules\S2P\Dto\Http\GetUsage as HttpGetUsage;
use App\Modules\S2P\Http\Clients\S2P;

class GetUsageFromS2P implements ContractGetUsage
{
    public function __construct(
        private readonly S2P $client
    ) {
    }

    public function __invoke(string $dateFrom, string $dateTo, ?string $clientId)
    {
        $response = $this->client->getUsage(new HttpGetUsage(dateFrom: $dateFrom, dateTo: $dateTo, clientIid: $clientId));

        return $response;
    }
}
