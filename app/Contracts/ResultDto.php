<?php

namespace App\Contracts;

use App\Base\ErrorDto;

interface ResultDto
{
    public function hasError(): bool;

    public function getError(): ErrorDto;
}
