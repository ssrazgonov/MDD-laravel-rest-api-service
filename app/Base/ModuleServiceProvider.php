<?php

namespace App\Base;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

abstract class ModuleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $migrationsPath = __DIR__ . '/../Database/Migrations';

        if (File::isDirectory($migrationsPath)) {
            $this->loadMigrationsFrom($migrationsPath);
        }
    }
}
