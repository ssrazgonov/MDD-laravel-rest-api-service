<?php

namespace App\Modules\Card\Helpers;

use App\Modules\User\Models\User;

class CardHelper
{
    public static function hashPan(User $user, string $pan): string
    {
        return hash('sha256', $user->id . $pan);
    }
}
