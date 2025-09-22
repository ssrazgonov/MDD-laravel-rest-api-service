<?php

namespace App\Modules\S2P\Dto;

use App\Base\Dto;

class Usage extends Dto
{
    public function __construct(
        public readonly ?bool $isRefund = null,
        public readonly ?string $documentId = null,
        public readonly ?string $serviceTypeId = null,
        public readonly ?string $dateTimeFrom = null,
        public readonly ?string $dateTimeTo = null,
        public readonly ?string $providerId = null,
        public readonly ?string $employeeId = null,
        public readonly ?string $addressInfo = null,
        public readonly ?string $client_id = null,
    ) {
    }
}
