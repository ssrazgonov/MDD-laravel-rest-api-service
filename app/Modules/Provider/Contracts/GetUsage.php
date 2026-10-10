<?php

namespace App\Modules\Provider\Contracts;

interface GetUsage
{
    public function __invoke(string $dateFrom, string $dateTo, ?string $clientId);
}
