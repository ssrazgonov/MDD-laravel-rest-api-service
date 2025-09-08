<?php

namespace App\Base;

use App\Contracts\Actionable;
use App\Contracts\ResultDto;
use Throwable;
use RuntimeException;

abstract class Action implements Actionable
{
    /**
     * @throws Throwable
     */
    public function handleResult(ResultDto $dto): ResultDto
    {
        if ($dto->hasError()) {
            throw new RuntimeException($dto->getError()->error, $dto->getError()->code);
        }

        return $dto;
    }
}