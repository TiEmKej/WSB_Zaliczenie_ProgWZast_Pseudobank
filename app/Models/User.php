<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'balance' => 'decimal:2'
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if ($user->account_number) {
                return;
            }

            do {
                $number = '';
                for ($i = 0; $i < 26; $i++) {
                    $number .= random_int(0, 9);
                }
            } while (static::where('account_number', $number)->exists());

            $user->account_number = $number;
        });
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

}
