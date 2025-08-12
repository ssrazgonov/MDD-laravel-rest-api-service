<?php

namespace App\Core\Interfaces;

use App\Core\Abstract\AbstractDto;

interface ActionableInterface
{
    public function __invoke(DtoInterface $dtoInterface);
}