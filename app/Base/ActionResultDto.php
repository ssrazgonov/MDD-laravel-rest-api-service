<?php

namespace App\Base;

use App\Contracts\ResultDto;

abstract class ActionResultDto extends Dto implements ResultDto
{
    public function __construct(
        readonly public bool $status,
        readonly public ?ErrorDto $error = null,
        readonly public mixed $data = null,
    ) {}

    public function hasError(): bool
    {
        return $this->error !== null;
    }

    public function getError(): ErrorDto
    {
        return $this->error;
    }
}