<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function createUser(string $role = 'user'): User
    {
        $user = new User([
            'name' => 'Test User',
            'email' => fake()->unique()->safeEmail(),
            'password' => 'Haslo123',
        ]);
        $user->forceFill(['role' => $role, 'email_verified_at' => now()])->save();

        return $user;
    }
}
