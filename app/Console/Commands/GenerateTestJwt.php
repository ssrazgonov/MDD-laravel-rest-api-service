<?php

namespace App\Console\Commands;

use Firebase\JWT\JWT;
use Illuminate\Console\Command;
use Ramsey\Uuid\Uuid;
use RuntimeException;

class GenerateTestJwt extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jwt:generate
                            {--type=parent : Type of JWT: parent, child, adult}
                            {--children=1 : Number of children (for parent type)}
                            {--realm=residents : Keycloak realm: residents, kids}
                            {--user-id= : Custom user ID (UUID will be generated if not provided)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate test JWT tokens for different user scenarios';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->option('type');
        $childrenCount = (int) $this->option('children');
        $realm = $this->option('realm');
        $customUserId = $this->option('user-id');

        $userId = $customUserId ?: (string) Uuid::uuid4();

        $this->info("Generating JWT for: {$type}");
        $this->info("User ID: {$userId}");
        $this->info("Realm: {$realm}");

        $payload = $this->buildPayload($type, $userId, $childrenCount, $realm);

        $this->info("\nPayload:");
        $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $jwt = $this->generateJwt($payload);

        $this->info("\nGenerated JWT:");
        $this->line($jwt);

        $this->info("\nUse this JWT in Authorization header:");
        $this->line("Authorization: Bearer {$jwt}");

        return Command::SUCCESS;
    }

    private function buildPayload(string $type, string $userId, int $childrenCount, string $realm): array
    {
        $basePayload = [
            'iss' => "https://keycloak.example.com/realms/{$realm}",
            'aud' => 'resident-card-service',
            'sub' => $userId,
            'iat' => time(),
            'exp' => time() + 3600, // 1 hour
            'realm' => $realm,
            'preferred_username' => $this->generateUsername($type, $userId),
            'name' => $this->generateFullName($type),
            'given_name' => $this->generateFirstName($type),
            'family_name' => $this->generateLastName($type),
            'email' => $this->generateEmail($type, $userId),
            'email_verified' => true,
            'resource_access' => $this->generateResourceAccess(),
        ];

        switch ($type) {
            case 'parent':
                $basePayload['children'] = $this->generateChildren($childrenCount);
                $this->info("Generated {$childrenCount} children");
                break;

            case 'child':
                // Для ребенка добавляем external_id в payload
                $basePayload['external_id'] = $this->generateSnils();
                $basePayload['child_info'] = [
                    'name' => $this->generateFullName('child'),
                    'birth_date' => date('Y-m-d', strtotime('-' . rand(5, 15) . ' years')),
                ];
                break;

            case 'adult':
                // Взрослый без детей
                $basePayload['adult_info'] = [
                    'name' => $this->generateFullName('adult'),
                    'email' => $this->generateEmail('adult', $userId),
                ];
                break;
        }

        return $basePayload;
    }

    private function generateChildren(int $count): array
    {
        $children = [];

        for ($i = 0; $i < $count; $i++) {
            $children[] = [
                'external_id' => $this->generateSnils(),
                'name' => $this->generateFullName('child'),
                'birth_date' => date('Y-m-d', strtotime('-' . rand(3, 15) . ' years')),
                'relation' => ['child', 'stepchild', 'adopted'][rand(0, 2)],
            ];
        }

        return $children;
    }

    private function generateSnils(): string
    {
        // Генерируем СНИЛС в формате XXX-XXX-XXX XX
        $parts = [];
        for ($i = 0; $i < 3; $i++) {
            $parts[] = str_pad((string) rand(100, 999), 3, '0', STR_PAD_LEFT);
        }
        $parts[] = str_pad((string) rand(10, 99), 2, '0', STR_PAD_LEFT);

        return implode('-', $parts);
    }

    private function generateUsername(string $type, string $userId): string
    {
        switch ($type) {
            case 'parent':
                return 'parent_' . substr($userId, -8);
            case 'child':
                return 'child_' . substr($userId, -8);
            case 'adult':
                return 'user_' . substr($userId, -8);
            default:
                return 'user_' . substr($userId, -8);
        }
    }

    private function generateFullName(string $type): string
    {
        $firstName = $this->generateFirstName($type);
        $lastName = $this->generateLastName($type);

        return $firstName . ' ' . $lastName;
    }

    private function generateFirstName(string $type): string
    {
        $firstNames = [
            'parent' => ['Иван', 'Мария', 'Александр', 'Елена', 'Дмитрий', 'Ольга'],
            'child' => ['Маша', 'Саша', 'Коля', 'Катя', 'Ваня', 'Лена'],
            'adult' => ['Алексей', 'Татьяна', 'Сергей', 'Наталья', 'Андрей', 'Ирина'],
        ];

        $names = $firstNames[$type] ?? $firstNames['adult'];

        return $names[array_rand($names)];
    }

    private function generateLastName(string $type): string
    {
        $lastNames = [
            'Иванов', 'Петров', 'Сидоров', 'Кузнецов', 'Смирнов', 'Попов',
            'Васильев', 'Морозов', 'Новиков', 'Федоров',
        ];

        return $lastNames[array_rand($lastNames)];
    }

    private function generateEmail(string $type, string $userId): string
    {
        $username = $this->generateUsername($type, $userId);

        return $username . '@example.com';
    }

    private function generateResourceAccess(): array
    {
        return [
            'resident-card-service' => [
                'roles' => ['user'],
            ],
            'account' => [
                'roles' => ['manage-account', 'view-profile'],
            ],
        ];
    }

    private function generateJwt(array $payload): string
    {
        // Получаем приватный ключ для подписи
        $privateKey = $this->getPrivateKey();

        // Для encode используем строковый ключ (как в старой версии)
        // Создаем header с пустым kid (Firebase\JWT ожидает его наличия)
        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT',
            'kid' => '', // Пустой kid для простого случая
        ];

        return JWT::encode($payload, $privateKey, 'RS256', null, $header);
    }

    private function getPrivateKey(): string
    {
        // Используем файл private.pem для генерации JWT
        $privateKeyPath = base_path('tests/fixtures/auth/private.pem');

        if (! file_exists($privateKeyPath)) {
            throw new RuntimeException("Private key file not found at: {$privateKeyPath}");
        }

        return file_get_contents($privateKeyPath);
    }
}
