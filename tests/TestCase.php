<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function createUser(string $role = 'user'): User
    {
        $user = new User([
            'name' => 'Test User',
            'email' => uniqid().'@example.com',
            'password' => 'Haslo123',
        ]);
        $user->forceFill(['role' => $role, 'email_verified_at' => now()])->save();

        return $user;
    }
}
