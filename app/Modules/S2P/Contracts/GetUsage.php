<?php

namespace App\Modules\S2P\Contracts;

interface GetUsage
{
    public function __invoke(string $dateFrom, string $dateTo, ?string $clientId);
}
