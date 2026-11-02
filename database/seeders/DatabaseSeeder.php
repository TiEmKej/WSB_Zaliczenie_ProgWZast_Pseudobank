<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['Administrator', 'admin@example.com', 'admin'],
            ['Miłosz Piekarski', 'piekarskim@example.com', 'user'],
            ['Student Uczelni', 'uczelnis@example.com', 'user'],
        ];

        foreach ($users as [$name, $email, $role]) {
            $user = new User(['name' => $name, 'email' => $email, 'password' => 'Haslo123']);
            $user->forceFill(['role' => $role, 'email_verified_at' => now()])->save();
        }
    }
}
