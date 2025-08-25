<?php

namespace App\Modules\Auth\Http\Middleware;

use App\Modules\Auth\Dto\TokenDto;
use App\Modules\Auth\Services\KeycloakUserService;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

class KeycloakUserMiddleware
{
    protected $userService;

    public function __construct(KeycloakUserService $userService)
    {
        $this->userService = $userService;
    }

    public function handle(Request $request, Closure $next)
    {
        $token = auth()->user()?->token;

        if (!(auth()->guard()->validate()) || !($token?->sub)) {
            throw new AuthenticationException();
        }

        $user = $this->userService->findOrCreateUser(new TokenDto(sub: $token->sub));
        
        auth()->setUser($user);

        return $next($request);
    }
}