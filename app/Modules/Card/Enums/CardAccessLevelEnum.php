<?php

namespace App\Modules\Card\Enums;

enum CardAccessLevelEnum: string
{
    case VIEW = 'view';
    case MANAGE = 'manage';
}