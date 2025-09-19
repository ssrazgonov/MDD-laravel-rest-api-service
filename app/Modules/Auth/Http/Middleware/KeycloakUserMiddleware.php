<?php

namespace App\Modules\Auth\Http\Middleware;

use App\Modules\Auth\Dto\TokenDto;
use App\Modules\Auth\Services\KeycloakUserService;
use App\Modules\User\Services\KeycloakMemberService;
use Closure;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class KeycloakUserMiddleware
{
    protected $userService;
    protected $memberService;

    public function __construct(
        KeycloakUserService $userService,
        KeycloakMemberService $memberService
    ) {
        $this->userService = $userService;
        $this->memberService = $memberService;
    }

    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (! $token) {
            throw new AuthenticationException();
        }

        $payload = $this->decodeJwt($token);

        if (! isset($payload['sub'])) {
            throw new AuthenticationException();
        }

        $user = $this->userService->findOrCreateUser(new TokenDto(sub: $payload['sub']));

        $kids = [];
        if (isset($payload['kids'])) {
            $kids = is_array($payload['kids']) ? $payload['kids'] : json_decode($payload['kids'], true);
        }

        if (! empty($kids)) {
            $this->memberService->processParentJwt($user, $kids, $payload['name'] ?? 'Unknown');
        } else {
            $this->memberService->processIndividualJwt(
                $user,
                $payload['name'] ?? 'Unknown',
                $payload['snils'] ?? null
            );
        }

        Auth::setUser($user->refresh());

        return $next($request);
    }

    private function decodeJwt(string $token): array
    {
        $publicKey = $this->getPublicKey();

        try {
            $key = new Key($publicKey, 'RS256');

            $decoded = JWT::decode($token, $key);

            return json_decode(json_encode($decoded), true);
        } catch (Exception $e) {
            throw new RuntimeException('Invalid JWT token: ' . $e->getMessage());
        }
    }

    private function getPublicKey(): string
    {
        $configKey = config('keycloak.realm_public_key');

        if (! $configKey) {
            throw new RuntimeException('KEYCLOAK_REALM_PUBLIC_KEY not found in .env file');
        }

        return $this->formatPublicKey($configKey);
    }

    private function formatPublicKey(string $key): string
    {
        if (str_contains($key, '-----BEGIN PUBLIC KEY-----')) {
            return $key;
        }

        return "-----BEGIN PUBLIC KEY-----\n" .
               wordwrap($key, 64, "\n", true) .
               "\n-----END PUBLIC KEY-----\n";
    }
}
