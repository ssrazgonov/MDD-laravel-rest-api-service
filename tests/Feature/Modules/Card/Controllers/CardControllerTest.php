<?php

use Tests\Helpers\JWTTestHelper;

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

it('can create card with bad pan number', function () {
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
});