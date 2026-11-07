<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
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

    public function transfers(): Builder
    {
        return Transfer::where(function (Builder $query) {
            $query->where('sender_id', $this->id)->orWhere('recipient_id', $this->id);
        })->with(['sender', 'recipient'])->latest()->latest('id');
    }

    public function roleLabel(): string
    {
        return $this->role === 'admin' ? 'administrator' : 'klient';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

}
