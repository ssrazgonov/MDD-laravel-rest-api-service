<?php

use App\Modules\Card\CardModule;
use App\Modules\Provider\ProviderModule;
use App\Modules\User\UserModule;

return [

    /*
    |--------------------------------------------------------------------------
    | Список модулей
    |--------------------------------------------------------------------------
    |
    | Список классов модулей, которые нужно загрузить.
    | Пример: [\App\Modules\User\UserModule::class, ...]
    |
    */
    'modules' => [
        UserModule::class,
        CardModule::class,
        ProviderModule::class,
    ],
];
