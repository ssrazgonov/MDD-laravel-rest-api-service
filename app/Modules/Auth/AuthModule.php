<?php

namespace App\Modules\Auth;

use App\Base\Module;
use App\Modules\User\Providers\UserServiceProvider;

class AuthModule extends Module
{
    protected string $name = 'user';
    
    protected array $providers = [
        UserServiceProvider::class
    ];
}