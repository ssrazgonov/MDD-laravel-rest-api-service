<?php

namespace App\Core\Interfaces;

interface DtoInterface
{
    public function toArray(): array;
    
    public function validate(): bool;
    
    public static function fromArray(array $data): static;
}