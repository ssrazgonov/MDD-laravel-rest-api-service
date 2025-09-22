<?php

namespace App\Modules\S2P\Providers;

use App\Base\ModuleServiceProvider;
use App\Modules\S2P\Actions\GetUsageFromS2P;
use App\Modules\S2P\Contracts\GetUsage as ContractGetUsage;

class S2PProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        $this->app->bind(ContractGetUsage::class, GetUsageFromS2P::class);
    }
}
