<?php

namespace App\Modules\Auth\Services;

use App\Modules\Auth\Dto\TokenDto;
use App\Modules\User\Models\User;

class KeycloakUserService
{
    public function findOrCreateUser(TokenDto $tokenData): User
    {
        $user = User::where('id', $tokenData->sub)->first();

        if ($user) {
            return $user;
        }

        return User::create([
            'id' => $tokenData->sub,
        ]);
    }
}
