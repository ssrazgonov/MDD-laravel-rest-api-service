<?php

use App\Modules\Card\Enums\CardAccessLevel;
use App\Modules\User\Models\User;
use Tests\Helpers\JWTTestHelper;

beforeEach(function () {
    $this->token = JWTTestHelper::generateToken(PRIVATE_KEY_PATH);
});

it('can create card with valid pan number', function (string $pan, string $digit) {
    $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson('/api/v1/cards', [
            'pan' => $pan,
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'status',
            'error',
            'data' => [
                'id',
                'group_member_id',
                'hash',
                'last_digits',
                'status',
                'comment',
                'created_at',
                'updated_at',
            ],
        ])
        ->assertJson([
            'data' => ['last_digits' => $digit],
        ]);
})->with([
    ['2200123456789000', '9000'],
    ['2200123456789001', '9001'],
    ['2200123456789002', '9002'],
    ['2200123456789003', '9003'],
]);

it('cannot create card with bad pan number', function (string $pan) {
    $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson('/api/v1/cards', [
            'pan' => $pan,
        ]);

    $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'error' => [
                'code',
                'message',
            ],
        ])
        ->assertJson([
            'error' => [
                'message' => 'The pan field format is invalid.',
            ],
        ]);
})->with([
    '2200',
    '123456789',
    '1234567890123456',
    '3200123456789003',
]);

it('cannot create card with empty pan number', function (string $pan) {
    $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson('/api/v1/cards', [
            'pan' => $pan,
        ]);

    $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'error' => [
                'code',
                'message',
            ],
        ])
        ->assertJson([
            'error' => [
                'message' => 'The pan field is required.',
            ],
        ]);
})->with([
    '',
    ' ',
]);

it('cannot create same card for user', function () {
    $firstResponse = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson('/api/v1/cards', [
            'pan' => '2234567891234567',
        ]);

    $firstResponse->assertStatus(201);

    $secondResponse = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson('/api/v1/cards', [
            'pan' => '2234567891234567',
        ]);

    $secondResponse->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'error' => [
                'code',
                'message',
            ],
            'data' => [],
        ]);

    $secondResponse->assertJson([
        'error' => [
            'message' => __('card::errors.already_exists'),
            'code' => 400,
        ],
    ]);
});

it('cannot create card for not authenticated', function () {
    $response = $this->postJson('/api/v1/cards', [
        'pan' => '2234567891234567',
    ]);

    $response->assertStatus(401)
        ->assertJsonStructure([
            'status',
            'error',
            'data' => [],
        ]);

    $response->assertJson([
        'error' => [
            'message' => 'Unauthenticated.',
        ],
    ]);
});

it('can create card with comment', function () {
    $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson('/api/v1/cards', [
            'pan' => '2234567891234568',
            'comment' => 'Test card with comment',
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'status',
            'error',
            'data' => [
                'id',
                'group_member_id',
                'hash',
                'last_digits',
                'status',
                'comment',
                'created_at',
                'updated_at',
            ],
        ]);

    $response->assertJson([
        'data' => [
            'comment' => 'Test card with comment',
        ],
    ]);
});

it('can get user cards list', function () {
    $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson('/api/v1/cards', [
            'pan' => '2234567891234569',
        ]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->getJson('/api/v1/cards');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'error',
            'data' => [
                '*' => [
                    'id',
                    'group_member_id',
                    'hash',
                    'last_digits',
                    'status',
                    'comment',
                    'created_at',
                    'updated_at',
                ],
            ],
        ]);
});

it('can get user cards with active status filter', function () {
    $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson('/api/v1/cards', [
            'pan' => '2234567891234570',
        ]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->getJson('/api/v1/cards?status=active');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'error',
            'data' => [
                '*' => [
                    'id',
                    'group_member_id',
                    'hash',
                    'last_digits',
                    'status',
                    'comment',
                    'created_at',
                    'updated_at',
                ],
            ],
        ]);
});

it('can get user cards with inactive status filter', function () {
    $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->getJson('/api/v1/cards?status=inactive');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'error',
            'data' => [],
        ]);
});

it('cannot get cards list for not authenticated', function () {
    $response = $this->getJson('/api/v1/cards');

    $response->assertStatus(401)
        ->assertJsonStructure([
            'status',
            'error',
            'data' => [],
        ]);

    $response->assertJson([
        'error' => [
            'message' => 'Unauthenticated.',
        ],
    ]);
});

it('can activate existing card', function () {
    $createResponse = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson('/api/v1/cards', [
            'pan' => '2234567891234571',
        ]);

    $cardId = $createResponse->json('data.id');

    $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson("/api/v1/cards/{$cardId}/deactivate");

    $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson("/api/v1/cards/{$cardId}/activate");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'error',
            'data' => [],
        ]);
});

it('can deactivate existing card', function () {
    $createResponse = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson('/api/v1/cards', [
            'pan' => '2234567891234572',
        ]);

    $cardId = $createResponse->json('data.id');

    $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson("/api/v1/cards/{$cardId}/deactivate");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'error',
            'data' => [],
        ]);
});

it('cannot activate non-existent card', function () {
    $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson('/api/v1/cards/99999/activate');

    $response->assertStatus(404)
        ->assertJsonStructure([
            'status',
            'error',
            'data',
        ]);
});

it('cannot deactivate non-existent card', function () {
    $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson('/api/v1/cards/99999/deactivate');

    $response->assertStatus(404)
        ->assertJsonStructure([
            'status',
            'error',
            'data' => [],
        ]);
});

it('cannot activate card for not authenticated', function () {
    $response = $this->postJson('/api/v1/cards/1/activate');

    $response->assertStatus(401)
        ->assertJsonStructure([
            'status',
            'error',
            'data' => [],
        ]);

    $response->assertJson([
        'error' => [
            'message' => 'Unauthenticated.',
        ],
    ]);
});

it('cannot deactivate card for not authenticated', function () {
    $response = $this->postJson('/api/v1/cards/1/deactivate');

    $response->assertStatus(401)
        ->assertJsonStructure([
            'status',
            'error',
            'data' => [],
        ]);

    $response->assertJson([
        'error' => [
            'message' => 'Unauthenticated.',
        ],
    ]);
});

it('rejects invalid status filter', function () {
    $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->getJson('/api/v1/cards?status=invalid');

    $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'error' => [
                'code',
                'message',
            ],
        ]);
});

it('validates comment field length', function () {
    $longComment = str_repeat('a', 257);

    $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson('/api/v1/cards', [
            'pan' => '2234567891234573',
            'comment' => $longComment,
        ]);

    $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'error' => [
                'code',
                'message',
            ],
        ]);
});

it('rejects pan with invalid starting digit', function () {
    $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson('/api/v1/cards', [
            'pan' => '1234567891234567',
        ]);

    $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'error' => [
                'code',
                'message',
            ],
        ]);
});

it('rejects pan with non-numeric characters', function () {
    $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson('/api/v1/cards', [
            'pan' => '223456789123456a',
        ]);

    $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'error' => [
                'code',
                'message',
            ],
        ]);
});

it('rejects invalid pan format with special characters', function () {
    $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson('/api/v1/cards', [
            'pan' => '223456789123456A',
        ]);

    $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'error' => [
                'code',
                'message',
            ],
        ]);
});

it('rejects pan that is too short', function () {
    $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson('/api/v1/cards', [
            'pan' => '223456789123456',
        ]);

    $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'error' => [
                'code',
                'message',
            ],
        ]);
});

it('rejects pan that is too long', function () {
    $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson('/api/v1/cards', [
            'pan' => '22345678912345678901',
        ]);

    $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'error' => [
                'code',
                'message',
            ],
        ]);
});

it('rejects missing pan field', function () {
    $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson('/api/v1/cards', [
            'comment' => 'Test comment',
        ]);

    $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'error' => [
                'code',
                'message',
            ],
        ]);
});

it('can create card without comment', function () {
    $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson('/api/v1/cards', [
            'pan' => '2234567891234575',
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'status',
            'error',
            'data' => [
                'id',
                'group_member_id',
                'hash',
                'last_digits',
                'status',
                'comment',
                'created_at',
                'updated_at',
            ],
        ])
        ->assertJson([
            'data' => [
                'last_digits' => '4575',
                'comment' => null,
            ],
        ]);
});

it('rejects empty pan field', function () {
    $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson('/api/v1/cards', [
            'pan' => '',
        ]);

    $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'error' => [
                'code',
                'message',
            ],
        ]);
});

it('can get user cards with null status filter', function () {
    $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson('/api/v1/cards', [
            'pan' => '2234567891234576',
        ]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->getJson('/api/v1/cards?status=');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'error',
            'data' => [
                '*' => [
                    'id',
                    'group_member_id',
                    'hash',
                    'last_digits',
                    'status',
                    'comment',
                    'created_at',
                    'updated_at',
                ],
            ],
        ]);
});

it('cannot activate already active card', function () {
    $createResponse = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson('/api/v1/cards', [
            'pan' => '2234567891234577',
        ]);

    $cardId = $createResponse->json('data.id');

    $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson("/api/v1/cards/{$cardId}/activate");

    $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'error',
            'data' => [],
        ]);
});

it('cannot deactivate already inactive card', function () {
    $createResponse = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson('/api/v1/cards', [
            'pan' => '2234567891234578',
        ]);

    $cardId = $createResponse->json('data.id');

    $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson("/api/v1/cards/{$cardId}/deactivate");

    $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson("/api/v1/cards/{$cardId}/deactivate");

    $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'error',
            'data' => [],
        ]);
});

it('rejects comment with special characters', function () {
    $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson('/api/v1/cards', [
            'pan' => '2234567891234581',
            'comment' => 'Test comment with <script>alert("xss")</script>',
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'status',
            'error',
            'data' => [
                'id',
                'group_member_id',
                'hash',
                'last_digits',
                'status',
                'comment',
                'created_at',
                'updated_at',
            ],
        ]);
});

it('can get user cards with multiple requests', function () {
    $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson('/api/v1/cards', [
            'pan' => '2234567891234582',
        ]);

    $response1 = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->getJson('/api/v1/cards?status=active');

    $response2 = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->getJson('/api/v1/cards?status=inactive');

    $response1->assertStatus(200);
    $response2->assertStatus(200);
});

it('rejects pan with leading zeros', function () {
    $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson('/api/v1/cards', [
            'pan' => '0234567891234567',
        ]);

    $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'error' => [
                'code',
                'message',
            ],
        ]);
});

it('rejects pan with spaces', function () {
    $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson('/api/v1/cards', [
            'pan' => '223456789123456 7',
        ]);

    $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'error' => [
                'code',
                'message',
            ],
        ]);
});

it('can create multiple cards for user', function () {
    $response1 = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson('/api/v1/cards', [
            'pan' => '2234567891234583',
        ]);

    $response2 = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson('/api/v1/cards', [
            'pan' => '2234567891234584',
        ]);

    $response1->assertStatus(201);
    $response2->assertStatus(201);

    $listResponse = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->getJson('/api/v1/cards');

    $listResponse->assertStatus(200)
        ->assertJsonCount(2, 'data');
});

it('validates comment field with exact max length', function () {
    $maxComment = str_repeat('a', 256);

    $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson('/api/v1/cards', [
            'pan' => '2234567891234585',
            'comment' => $maxComment,
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'status',
            'error',
            'data' => [
                'id',
                'group_member_id',
                'hash',
                'last_digits',
                'status',
                'comment',
                'created_at',
                'updated_at',
            ],
        ]);
});

it('cannot activate card without manage access', function () {
    $user = User::factory()->create();

    $token = JWTTestHelper::generateTokenForUser($user->id, PRIVATE_KEY_PATH);

    $card = $this->createCardForGroupMember($user->groupMember, CardAccessLevel::VIEW, status: false);

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson("/api/v1/cards/{$card->id}/activate");

    $response->assertStatus(403)
        ->assertJson([
            'error' => [
                'message' => __('card::errors.card_access_denied'),
            ],
        ]);
});

it('cannot deactivate card without manage access', function () {
    $user = User::factory()->create();

    $token = JWTTestHelper::generateTokenForUser($user->id, PRIVATE_KEY_PATH);

    $card = $this->createCardForGroupMember($user->groupMember, CardAccessLevel::VIEW, status: true);

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson("/api/v1/cards/{$card->id}/deactivate");

    $response->assertStatus(403);
});

it('can activate card with manage access', function () {
    $createResponse = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson('/api/v1/cards', [
            'pan' => '2234567891234588',
        ]);

    $cardId = $createResponse->json('data.id');

    $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson("/api/v1/cards/{$cardId}/deactivate");

    $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson("/api/v1/cards/{$cardId}/activate");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'error',
            'data' => [],
        ]);
});

it('can deactivate card with manage access', function () {
    $createResponse = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson('/api/v1/cards', [
            'pan' => '2234567891234589',
        ]);

    $cardId = $createResponse->json('data.id');

    $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson("/api/v1/cards/{$cardId}/deactivate");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'error',
            'data' => [],
        ]);
});

it('can get card with status history', function () {
    $createResponse = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson('/api/v1/cards', [
            'pan' => '2234567891234590',
        ]);

    $cardId = $createResponse->json('data.id');

    $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->getJson("/api/v1/cards/{$cardId}");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'error',
            'data' => [
                'card' => [
                    'id',
                    'group_member_id',
                    'hash',
                    'last_digits',
                    'status',
                    'comment',
                    'created_at',
                    'updated_at',
                ],
                'history' => [
                    '*' => [
                        'id',
                        'card_id',
                        'status',
                        'comment',
                        'changed_by',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ],
        ]);
});

it('cannot get non-existent card', function () {
    $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->getJson('/api/v1/cards/99999');

    $response->assertStatus(404)
        ->assertJsonStructure([
            'status',
            'error',
            'data',
        ])
        ->assertJson([
            'data' => null,
        ]);
});

it('cannot get card without authentication', function () {
    $response = $this->getJson('/api/v1/cards/1');

    $response->assertStatus(401)
        ->assertJsonStructure([
            'status',
            'error',
            'data' => [],
        ]);
});
