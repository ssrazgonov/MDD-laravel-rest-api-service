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
                            {--child-snils= : Comma-separated list of SNILS for children (optional)}
                            {--user-snils= : SNILS for the user (optional)}
                            {--realm=residents : Keycloak realm: residents, kids}
                            {--user-id= : Custom user ID (UUID will be generated if not provided)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate test JWT tokens for different user scenarios. Use --child-snils to specify SNILS for children and --user-snils to specify SNILS for the user.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->option('type');
        $childrenCount = (int) $this->option('children');
        $childSnils = $this->option('child-snils');
        $userSnils = $this->option('user-snils');
        $realm = $this->option('realm');
        $customUserId = $this->option('user-id');

        $userId = $customUserId ?: (string) Uuid::uuid4();

        $childSnilsArray = [];
        if ($childSnils) {
            $childSnilsArray = array_map('trim', explode(',', $childSnils));
            $this->info('Using provided SNILS for children: ' . implode(', ', $childSnilsArray));
        }

        if ($userSnils) {
            $this->info("Using provided SNILS for user: {$userSnils}");
        }

        $this->info("Generating JWT for: {$type}");
        $this->info("User ID: {$userId}");
        $this->info("Realm: {$realm}");

        $payload = $this->buildPayload($type, $userId, $childrenCount, $childSnilsArray, $userSnils, $realm);

        $this->info("\nPayload:");
        $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $jwt = $this->generateJwt($payload);

        $this->info("\nGenerated JWT:");
        $this->line($jwt);

        $this->info("\nUse this JWT in Authorization header:");
        $this->line("Authorization: Bearer {$jwt}");

        return Command::SUCCESS;
    }

    private function buildPayload(string $type, string $userId, int $childrenCount, array $childSnilsArray, ?string $userSnils, string $realm): array
    {
        $iat = time();
        $exp = $iat + (365 * 24 * 3600);

        $kids = [];
        switch ($type) {
            case 'parent':
                $kids = $this->generateKids($childrenCount, $childSnilsArray);
                break;
            case 'child':
                $kids = [$this->generateSingleKid($childSnilsArray[0] ?? null)];
                break;
        }
        $kidsJson = json_encode($kids, JSON_UNESCAPED_UNICODE);

        return [
            'exp' => $exp,
            'iat' => $iat,
            'auth_time' => $iat,
            'jti' => (string) Uuid::uuid4(),
            'iss' => 'https://test5.admlr.lipetsk.ru/auth/realms/residents',
            'aud' => 'account',
            'sub' => $userId,
            'typ' => 'Bearer',
            'azp' => 'resident_test',
            'nonce' => (string) Uuid::uuid4(),
            'session_state' => (string) Uuid::uuid4(),
            'acr' => '1',
            'allowed-origins' => [
                'https://test3.admlr.lipetsk.ru',
            ],
            'realm_access' => [
                'roles' => [
                    'offline_access',
                    'uma_authorization',
                ],
            ],
            'resource_access' => [
                'resident_test' => [
                    'roles' => [
                        'resident',
                    ],
                ],
                'account' => [
                    'roles' => [
                        'manage-account',
                        'manage-account-links',
                        'view-profile',
                    ],
                ],
            ],
            'scope' => 'openid',
            'preferred_username' => '9066839057',
            'uuid' => $userId,
            'snils' => $userSnils ?? $this->generateSnils(),
            'kids' => $kidsJson,
        ];
    }

    private function generateKids(int $count, array $childSnilsArray = []): array
    {
        $kids = [];

        for ($i = 0; $i < $count; $i++) {
            $snils = $childSnilsArray[$i] ?? null;
            $kids[] = $this->generateSingleKid($snils);
        }

        return $kids;
    }

    private function generateSingleKid(?string $snils = null): array
    {
        return [
            'stateFacts' => ['Identifiable'],
            'firstName' => $this->generateFirstName('child'),
            'lastName' => $this->generateLastName('child'),
            'middleName' => $this->generateMiddleName(),
            'birthDate' => date('d.m.Y', strtotime('-' . rand(5, 15) . ' years')),
            'gender' => rand(0, 1) ? 'M' : 'F',
            'trusted' => false,
            'linkCode' => $this->generateLinkCode(),
            'updatedOn' => time(),
            'id' => rand(100000000, 999999999),
            'eTag' => $this->generateETag(),
            'snils' => $snils ?? $this->generateSnils(),
        ];
    }

    private function generateMiddleName(): string
    {
        $middleNames = ['Сергеевич', 'Александрович', 'Иванович', 'Петрович', 'Андреевич'];

        return $middleNames[array_rand($middleNames)];
    }

    private function generateLinkCode(): string
    {
        return str_pad((string) rand(1000000000000000, 9999999999999999), 16, '0', STR_PAD_LEFT);
    }

    private function generateETag(): string
    {
        return strtoupper(md5(uniqid()));
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
        $privateKey = $this->getPrivateKey();

        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT',
            'kid' => '',
        ];

        return JWT::encode($payload, $privateKey, 'RS256', null, $header);
    }

    private function getPrivateKey(): string
    {
        $privateKeyPath = base_path('tests/fixtures/auth/private.pem');

        if (! file_exists($privateKeyPath)) {
            throw new RuntimeException("Private key file not found at: {$privateKeyPath}");
        }

        return file_get_contents($privateKeyPath);
    }
}
