<?php

namespace App\Modules\S2P\Dto\Http;

class GetUsage
{
    public function __construct(
        public readonly string $dateFrom,
        public readonly string $dateTo,
        public readonly ?string $clientIid,
    ) {
    }
}
