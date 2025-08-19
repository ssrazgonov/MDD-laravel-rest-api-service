<?php

namespace App\Base;

use App\Contracts\ResultDto;

abstract class ActionResultDto extends Dto implements ResultDto
{
    public function __construct(
        public bool $status,
        public ?ErrorDto $error = null,
    ){}

    public function hasError(): bool
    {
        return $this->error !== null;
    }

    public function getError(): ErrorDto
    {
        return $this->error;
    }
}