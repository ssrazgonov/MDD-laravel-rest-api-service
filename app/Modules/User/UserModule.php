<?php

namespace App\Modules\User;

use App\Base\Module;
use App\Modules\User\Providers\UserServiceProvider;

class UserModule extends Module
{
    protected string $name = 'user';
    
    protected array $providers = [
        UserServiceProvider::class
    ];
}