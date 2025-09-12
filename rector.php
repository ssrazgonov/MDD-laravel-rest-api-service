<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use RectorLaravel\Set\LaravelSetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/app',
        __DIR__ . '/database',
        __DIR__ . '/tests',
    ]);

    // Указываем версию PHP 8.4
    $rectorConfig->phpVersion(80400);

    // Импорт имён
    $rectorConfig->importNames();
    $rectorConfig->removeUnusedImports();

    // Laravel-правила (опционально)
    $rectorConfig->sets([
        LaravelSetList::LARAVEL_100,
    ]);
};