<?php

namespace App\Contracts;

interface Dto
{
    public function toArray(): array;
    
    public function validate(): bool;
    
    public static function fromArray(array $data): static;
}