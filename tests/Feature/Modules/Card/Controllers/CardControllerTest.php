<?php

use Tests\Helpers\JWTTestHelper;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->token = JWTTestHelper::generateToken(PRIVATE_KEY_PATH);
});


it('can create card with valid pan number', function () {
    $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
    ->postJson('/api/v1/cards', [
        'pan' => '2234567891234567',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'status',
            'error',
            'data' => [
                'id',
                'user_id',
                'hash',
                'last_digits',
                'status',
                'comment',
                'created_at',
                'updated_at'
            ]
        ]);
});

it('cannot create card with bad pan number', function () {
    $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
    ->postJson('/api/v1/cards', [
        'pan' => '1234567891234567',
    ]);

    $response->assertStatus(422)
        ->assertJsonStructure([
            'message',
            'errors' => [
                'pan'
            ]
        ]);
});

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

    $secondResponse->assertStatus(422)
        ->assertJsonStructure([
            'status',
            'error',
            'data' => []
        ]);

    $secondResponse->assertJson([
            'error' => [
                'error' => 'Эта карта уже добавлена в ваш профиль.',
                'code' => 422
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
            'data' => []
        ]);

    $response->assertJson([
            'error' => [
                'message' => 'Unauthenticated.'
            ],
        ]);
});

it('can create card with comment', function () {
    $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
    ->postJson('/api/v1/cards', [
        'pan' => '2234567891234568',
        'comment' => 'Test card with comment'
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'status',
            'error',
            'data' => [
                'id',
                'user_id',
                'hash',
                'last_digits',
                'status',
                'comment',
                'created_at',
                'updated_at'
            ]
        ]);

    $response->assertJson([
        'data' => [
            'comment' => 'Test card with comment'
        ]
    ]);
});

it('can get user cards list', function () {
    $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson('/api/v1/cards', [
            'pan' => '2234567891234569',
        ]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->getJson('/api/v1/cards');

    $response->assertStatus(201)
        ->assertJsonStructure([
            'status',
            'error',
            'data' => [
                '*' => [
                    'id',
                    'user_id',
                    'hash',
                    'last_digits',
                    'status',
                    'comment',
                    'created_at',
                    'updated_at',
                ]
            ]
        ]);
});

it('can get user cards with active status filter', function () {
    $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson('/api/v1/cards', [
            'pan' => '2234567891234570',
        ]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->getJson('/api/v1/cards?status=active');

    $response->assertStatus(201)
        ->assertJsonStructure([
            'status',
            'error',
            'data' => [
                '*' => [
                    'id',
                    'user_id',
                    'hash',
                    'last_digits',
                    'status',
                    'comment',
                    'created_at',
                    'updated_at',
                ]
            ]
        ]);
});

it('can get user cards with inactive status filter', function () {
    $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->getJson('/api/v1/cards?status=inactive');

    $response->assertStatus(201)
        ->assertJsonStructure([
            'status',
            'error',
            'data' => []
        ]);
});

it('cannot get cards list for not authenticated', function () {
    $response = $this->getJson('/api/v1/cards');

    $response->assertStatus(401)
        ->assertJsonStructure([
            'status',
            'error',
            'data' => []
        ]);

    $response->assertJson([
        'error' => [
            'message' => 'Unauthenticated.'
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

    $response->assertStatus(201)
        ->assertJsonStructure([
            'status',
            'error',
            'data' => []
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

    $response->assertStatus(201)
        ->assertJsonStructure([
            'status',
            'error',
            'data' => []
        ]);
});

it('cannot activate non-existent card', function () {
    $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson('/api/v1/cards/99999/activate');

    $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'error',
            'data' => []
        ]);
});

it('cannot deactivate non-existent card', function () {
    $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson('/api/v1/cards/99999/deactivate');

    $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'error',
            'data' => []
        ]);
});

it('cannot activate card for not authenticated', function () {
    $response = $this->postJson('/api/v1/cards/1/activate');

    $response->assertStatus(401)
        ->assertJsonStructure([
            'status',
            'error',
            'data' => []
        ]);

    $response->assertJson([
        'error' => [
            'message' => 'Unauthenticated.'
        ],
    ]);
});

it('cannot deactivate card for not authenticated', function () {
    $response = $this->postJson('/api/v1/cards/1/deactivate');

    $response->assertStatus(401)
        ->assertJsonStructure([
            'status',
            'error',
            'data' => []
        ]);

    $response->assertJson([
        'error' => [
            'message' => 'Unauthenticated.'
        ],
    ]);
});

it('rejects invalid status filter', function () {
    $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->getJson('/api/v1/cards?status=invalid');

    $response->assertStatus(422)
        ->assertJsonStructure([
            'message',
            'errors' => [
                'status'
            ]
        ]);
});

it('validates comment field length', function () {
    $longComment = str_repeat('a', 257);

    $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson('/api/v1/cards', [
            'pan' => '2234567891234573',
            'comment' => $longComment
        ]);

    $response->assertStatus(422)
        ->assertJsonStructure([
            'message',
            'errors' => [
                'comment'
            ]
        ]);
});

it('rejects pan with invalid starting digit', function () {
    $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson('/api/v1/cards', [
            'pan' => '1234567891234567',
        ]);

    $response->assertStatus(422)
        ->assertJsonStructure([
            'message',
            'errors' => [
                'pan'
            ]
        ]);
});

it('rejects pan with non-numeric characters', function () {
    $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson('/api/v1/cards', [
            'pan' => '223456789123456a',
        ]);

    $response->assertStatus(422)
        ->assertJsonStructure([
            'message',
            'errors' => [
                'pan'
            ]
        ]);
});

it('rejects invalid pan format with special characters', function () {
    $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson('/api/v1/cards', [
            'pan' => '223456789123456A',
        ]);

    $response->assertStatus(422)
        ->assertJsonStructure([
            'message',
            'errors' => [
                'pan'
            ]
        ]);
});

it('rejects pan that is too short', function () {
    $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson('/api/v1/cards', [
            'pan' => '223456789123456',
        ]);

    $response->assertStatus(422)
        ->assertJsonStructure([
            'message',
            'errors' => [
                'pan'
            ]
        ]);
});

it('rejects pan that is too long', function () {
    $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson('/api/v1/cards', [
            'pan' => '22345678912345678901',
        ]);

    $response->assertStatus(422)
        ->assertJsonStructure([
            'message',
            'errors' => [
                'pan'
            ]
        ]);
});

it('rejects missing pan field', function () {
    $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson('/api/v1/cards', [
            'comment' => 'Test comment'
        ]);

    $response->assertStatus(422)
        ->assertJsonStructure([
            'message',
            'errors' => [
                'pan'
            ]
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
                'user_id',
                'hash',
                'last_digits',
                'status',
                'comment',
                'created_at',
                'updated_at'
            ]
        ])
        ->assertJson([
            'data' => [
                'last_digits' => '4575',
                'comment' => null
            ]
        ]);
});

it('rejects empty pan field', function () {
    $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson('/api/v1/cards', [
            'pan' => '',
        ]);

    $response->assertStatus(422)
        ->assertJsonStructure([
            'message',
            'errors' => [
                'pan'
            ]
        ]);
});

it('can get user cards with null status filter', function () {
    $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson('/api/v1/cards', [
            'pan' => '2234567891234576',
        ]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->getJson('/api/v1/cards?status=');

    $response->assertStatus(201)
        ->assertJsonStructure([
            'status',
            'error',
            'data' => [
                '*' => [
                    'id',
                    'user_id',
                    'hash',
                    'last_digits',
                    'status',
                    'comment',
                    'created_at',
                    'updated_at',
                ]
            ]
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
            'data' => []
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
            'data' => []
        ]);
});

it('rejects comment with special characters', function () {
    $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson('/api/v1/cards', [
            'pan' => '2234567891234581',
            'comment' => 'Test comment with <script>alert("xss")</script>'
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'status',
            'error',
            'data' => [
                'id',
                'user_id',
                'hash',
                'last_digits',
                'status',
                'comment',
                'created_at',
                'updated_at',
            ]
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

    $response1->assertStatus(201);
    $response2->assertStatus(201);
});

it('rejects pan with leading zeros', function () {
    $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson('/api/v1/cards', [
            'pan' => '0234567891234567',
        ]);

    $response->assertStatus(422)
        ->assertJsonStructure([
            'message',
            'errors' => [
                'pan'
            ]
        ]);
});

it('rejects pan with spaces', function () {
    $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson('/api/v1/cards', [
            'pan' => '223456789123456 7',
        ]);

    $response->assertStatus(422)
        ->assertJsonStructure([
            'message',
            'errors' => [
                'pan'
            ]
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

    $listResponse->assertStatus(201)
        ->assertJsonCount(2, 'data');
});

it('validates comment field with exact max length', function () {
    $maxComment = str_repeat('a', 256);

    $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson('/api/v1/cards', [
            'pan' => '2234567891234585',
            'comment' => $maxComment
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'status',
            'error',
            'data' => [
                'id',
                'user_id',
                'hash',
                'last_digits',
                'status',
                'comment',
                'created_at',
                'updated_at',
            ]
        ]);
});
it('cannot activate card without manage access', function () {
    $createResponse = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson('/api/v1/cards', [
            'pan' => '2234567891234586',
        ]);

    $cardId = $createResponse->json('data.id');

    $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson("/api/v1/cards/{$cardId}/deactivate");

    $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson("/api/v1/cards/{$cardId}/activate");

    $response->assertStatus(201);
});

it('cannot deactivate card without manage access', function () {
    $createResponse = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson('/api/v1/cards', [
            'pan' => '2234567891234587',
        ]);

    $cardId = $createResponse->json('data.id');

    $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->postJson("/api/v1/cards/{$cardId}/deactivate");

    $response->assertStatus(201);
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

    $response->assertStatus(201)
        ->assertJsonStructure([
            'status',
            'error',
            'data' => []
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

    $response->assertStatus(201)
        ->assertJsonStructure([
            'status',
            'error',
            'data' => []
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
                    'user_id',
                    'hash',
                    'last_digits',
                    'status',
                    'comment',
                    'created_at',
                    'updated_at'
                ],
                'history' => [
                    '*' => [
                        'id',
                        'card_id',
                        'status',
                        'comment',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]
        ]);
});

it('cannot get non-existent card', function () {
    $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
        ->getJson('/api/v1/cards/99999');

    $response->assertStatus(404)
        ->assertJsonStructure([
            'status',
            'error',
            'data'
        ])
        ->assertJson([
            'data' => [
                'card' => null,
                'history' => null
            ]
        ]);
});

it('cannot get card without authentication', function () {
    $response = $this->getJson('/api/v1/cards/1');
    
    $response->assertStatus(401)
        ->assertJsonStructure([
            'status',
            'error',
            'data' => []
        ]);
});