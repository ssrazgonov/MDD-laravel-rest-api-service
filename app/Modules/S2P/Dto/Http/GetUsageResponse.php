<?php

namespace App\Modules\S2P\Dto\Http;

class GetUsageResponse
{
    public function __construct(
        public readonly ?array $services = null,
        public readonly ?Status $status = null,
    ) {
    }
}
