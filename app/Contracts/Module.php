<?php

namespace App\Contracts;

interface Module
{
    public function getName(): string;

    public function getPath(): string;

    public function getProviders(): array;

    public function boot(): void;
}
