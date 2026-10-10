<?php

namespace App\Modules\Provider\Actions;

use App\Modules\Provider\Contracts\GetUsage as ContractGetUsage;
use App\Modules\Provider\Dto\Http\GetUsage as HttpGetUsage;
use App\Modules\Provider\Http\Clients\ProviderClient;

class GetServicesUsage implements ContractGetUsage
{
    public function __construct(
        private readonly ProviderClient $client
    ) {
    }

    public function __invoke(string $dateFrom, string $dateTo, ?string $clientId)
    {
        $response = $this->client->getUsage(new HttpGetUsage(dateFrom: $dateFrom, dateTo: $dateTo, clientIid: $clientId));

        return $response;
    }
}
