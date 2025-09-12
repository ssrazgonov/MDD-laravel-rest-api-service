<?php

namespace App\Base;

use App\Contracts\ResultDto;

abstract class ActionResultDto extends Dto implements ResultDto
{
    public function __construct(
        public readonly bool $status,
        public readonly ?ErrorDto $error = null,
        public readonly mixed $data = null,
    ) {
    }

    public function hasError(): bool
    {
        return $this->error instanceof ErrorDto;
    }

    public function getError(): ErrorDto
    {
        return $this->error;
    }
}
