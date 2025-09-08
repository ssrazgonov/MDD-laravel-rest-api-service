<?php

namespace App\Modules\Auth\Dto;

final class TokenDto {
    public function __construct(
        readonly public string $sub
    ){}
}