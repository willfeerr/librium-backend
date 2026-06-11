<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_receive_token(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Will Ferreira',
            'email' => 'will@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.user.email', 'will@example.com')
            ->assertJsonStructure(['data' => ['access_token', 'token_type', 'user' => ['id', 'name', 'email']]]);
    }

    public function test_user_can_login(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'Will Ferreira',
            'email' => 'will@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertCreated();

        $this->postJson('/api/auth/login', [
            'email' => 'will@example.com',
            'password' => 'Password123!',
        ])
            ->assertOk()
            ->assertJsonStructure(['data' => ['access_token', 'user']]);
    }
}
