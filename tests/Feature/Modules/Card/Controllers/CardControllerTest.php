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

    $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'error',
            'data' => []
        ]);

    $response->assertJson([
            'error' => [
                'message' => 'The pan field format is invalid.'
            ],
        ]);
});

it('cannot create same card for user', function () {
    $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
    ->postJson('/api/v1/cards', [
        'pan' => '2234567891234567',
    ]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
    ->postJson('/api/v1/cards', [
        'pan' => '2234567891234567',
    ]);

    $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'error',
            'data' => []
        ]);

    $response->assertJson([
            'error' => [
                'message' => 'Эта карта уже добавлена в ваш профиль.'
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
                    'updated_at'
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
                    'updated_at'
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