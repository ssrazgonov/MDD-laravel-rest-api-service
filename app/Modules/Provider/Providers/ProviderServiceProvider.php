<?php

namespace App\Modules\Provider\Providers;

use App\Base\ModuleServiceProvider;
use App\Modules\Provider\Actions\GetServicesUsage;
use App\Modules\Provider\Contracts\GetUsage as ContractGetUsage;

class ProviderServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        $this->app->bind(ContractGetUsage::class, GetServicesUsage::class);
    }
}
