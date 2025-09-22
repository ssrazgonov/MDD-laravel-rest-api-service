<?php

namespace App\Modules\S2P;

use App\Base\Module;
use App\Modules\S2P\Providers\S2PProvider;

class S2PModule extends Module
{
    protected string $name = 's2p';

    protected array $providers = [
        S2PProvider::class,
    ];
}
