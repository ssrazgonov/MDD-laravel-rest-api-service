<?php

namespace App\Core\Interfaces;

interface ModuleInterface
{
    public function getName(): string;

    public function getPath(): string;

    public function getProviders(): array;

    public function boot(): void;
}