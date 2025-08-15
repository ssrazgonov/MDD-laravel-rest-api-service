<?php

namespace App\Enums;

enum CardStatus: string
{
    case ACTIVE = 'active';
    case DISABLED = 'disabled';
}