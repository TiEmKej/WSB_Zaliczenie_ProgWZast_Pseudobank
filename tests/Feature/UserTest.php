<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_haslo_jest_hashowane_w_bazie(): void
    {
        $user = User::create([
            'name' => 'Jan Kowalski',
            'email' => 'jan@example.com',
            'password' => 'Haslo123',
            'pesel' => '44051401359',
        ]);

        $this->assertNotSame('Haslo123', $user->password);
        $this->assertTrue(Hash::check('Haslo123', $user->password));
    }
}
