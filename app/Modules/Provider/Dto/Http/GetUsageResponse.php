<?php

namespace App\Modules\Provider\Dto\Http;

class GetUsageResponse
{
    public function __construct(
        public readonly ?array $services = null,
        public readonly ?Status $status = null,
    ) {
    }
}
