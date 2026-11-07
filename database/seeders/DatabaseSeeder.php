<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\Pesel;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['Administrator', 'admin@example.com', 'admin', 'M'],
            ['Miłosz Piekarski', 'piekarskim@example.com', 'user', 'M'],
            ['Student Uczelni', 'uczelnis@example.com', 'user', 'K'],
        ];

        foreach ($users as [$name, $email, $role, $gender]) {
            $user = new User([
                'name' => $name,
                'email' => $email,
                'password' => 'Haslo123',
                'pesel' => Pesel::generate($gender),
            ]);
            $user->forceFill(['role' => $role, 'email_verified_at' => now()])->save();
        }
    }
}
