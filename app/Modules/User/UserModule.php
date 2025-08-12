<?php

namespace App\Modules\User;

use App\Core\Abstract\AbstractModule;
use App\Modules\User\Providers\UserServiceProvider;

class UserModule extends AbstractModule
{
    protected string $name = 'user';
    
    protected array $providers = [
        UserServiceProvider::class
    ];
}