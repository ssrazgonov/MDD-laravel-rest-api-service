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

        // Здесь нужно декодировать JWT и получить данные
        // Предполагаем, что у нас есть декодированный payload
        $payload = $this->decodeJwt($token);

        if (! isset($payload['sub'])) {
            throw new AuthenticationException();
        }

        $user = $this->userService->findOrCreateUser(new TokenDto(sub: $payload['sub']));

        // Определяем тип пользователя по наличию children в JWT
        if (isset($payload['children']) && is_array($payload['children'])) {
            // Это родитель из residents realm - обрабатываем детей
            $this->memberService->processParentJwt($user, $payload['children'], $payload['name']);
        } else {
            // Это либо взрослый, либо ребенок из kids realm
            $this->memberService->processIndividualJwt($user, $payload['name']);
        }

        // Если Auth::user() имеет null id, устанавливаем его
        if (auth()->check() && ! auth()->user()->id) {
            auth()->user()->id = $payload['sub'];
        }

        return $next($request);
    }

    private function decodeJwt(string $token): array
    {
        // Получаем публичный ключ из конфига или файла fixtures
        $publicKey = $this->getPublicKey();

        try {
            // Создаем объект Key для публичного ключа
            $key = new Key($publicKey, 'RS256');

            // Декодируем и валидируем JWT с публичным ключом
            $decoded = JWT::decode($token, $key);

            return json_decode(json_encode($decoded), true);
        } catch (Exception $e) {
            throw new RuntimeException('Invalid JWT token: ' . $e->getMessage());
        }
    }

    private function getPublicKey(): string
    {
        // Обязательно берем из конфига (.env)
        $configKey = config('keycloak.realm_public_key');
        if (! $configKey) {
            throw new RuntimeException('KEYCLOAK_REALM_PUBLIC_KEY not found in .env file');
        }

        // Конвертируем PEM формат если нужно
        return $this->formatPublicKey($configKey);
    }

    private function formatPublicKey(string $key): string
    {
        // Если ключ уже в правильном формате PEM
        if (str_contains($key, '-----BEGIN PUBLIC KEY-----')) {
            return $key;
        }

        // Если ключ в формате JWK или другом формате, конвертируем
        // Для простоты предполагаем, что ключ уже в правильном формате
        return "-----BEGIN PUBLIC KEY-----\n" .
               wordwrap($key, 64, "\n", true) .
               "\n-----END PUBLIC KEY-----\n";
    }
}
