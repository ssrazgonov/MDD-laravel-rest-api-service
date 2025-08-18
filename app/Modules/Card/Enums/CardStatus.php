<?php

namespace App\Modules\Card\Enums;

enum CardStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'disabled';

    public function label(): string
    {
        return match($this) {
            self::ACTIVE => 'Подключена',
            self::INACTIVE => 'Не подключена',
        };
    }
}