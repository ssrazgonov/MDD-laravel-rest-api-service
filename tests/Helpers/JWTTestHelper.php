<?php

namespace Tests\Helpers;

use Firebase\JWT\JWT;

class JWTTestHelper
{
    public static function generateToken(string $privateKeyPath, array $payload = []): string
    {
        $defaultPayload = [
            'sub' => '00000000-0000-0000-0000-000000000000',
            'iss' => 'http://keycloak.local/realms/test',
            'aud' => 'account',
            'iat' => time(),
            'exp' => time() + 3600,
            'email' => 'test@example.com',
            'name' => 'test user',
            'preferred_username' => 'testuser',
            'resource_access' => [
                'account' => [
                    'roles' => ['user', 'offline_access'],
                ],
            ],
        ];

        $payload = array_merge($defaultPayload, $payload);

        $privateKey = file_get_contents($privateKeyPath);

        return JWT::encode($payload, $privateKey, 'RS256');
    }

    public static function generateTokenForUser(string $userId, string $privateKeyPath, array $userData = []): string
    {
        $payload = array_merge([
            'sub' => $userId,
        ], $userData);

        return static::generateToken($privateKeyPath, $payload);
    }
}
