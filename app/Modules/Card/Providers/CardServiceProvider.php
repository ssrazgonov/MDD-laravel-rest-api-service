<?php

namespace App\Modules\Card\Providers;

use App\Base\ModuleServiceProvider;
use Illuminate\Support\Facades\Lang;

class CardServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        Lang::addNamespace('card', __DIR__ . '/../lang');
    }
}