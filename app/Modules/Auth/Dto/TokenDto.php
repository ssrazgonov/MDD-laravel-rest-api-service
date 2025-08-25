<?php

namespace App\Modules\Auth\Dto;

final readonly class TokenDto {
    public function __construct(
        readonly public string $sub
    ){}
}