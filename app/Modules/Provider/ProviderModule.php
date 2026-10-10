<?php

namespace App\Modules\Provider;

use App\Base\Module;
use App\Modules\Provider\Providers\ProviderServiceProvider;

class ProviderModule extends Module
{
    protected string $name = 'provider';

    protected array $providers = [
        ProviderServiceProvider::class,
    ];
}
