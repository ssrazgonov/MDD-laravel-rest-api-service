<?php

namespace App\Modules\Card\Enums;

enum CardAccessLevel: string
{
    case VIEW = 'view';
    case MANAGE = 'manage';
}
