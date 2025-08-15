<?php

namespace App\Modules\Card;

use App\Base\Module;
use App\Modules\Card\Providers\CardServiceProvider;

class CardModule extends Module
{
    protected string $name = 'card';
    
    protected array $providers = [
        CardServiceProvider::class
    ];
}