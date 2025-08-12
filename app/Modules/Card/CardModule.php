<?php

namespace App\Modules\Card;

use App\Core\Abstract\AbstractModule;
use App\Modules\Card\Providers\CardServiceProvider;

class CardModule extends AbstractModule
{
    protected string $name = 'card';
    
    protected array $providers = [
        CardServiceProvider::class
    ];
}