<?php

namespace App\Models;

use App\Support\AccountNumber;
use App\Support\Pesel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'pesel'])]
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

    public function recipients(): HasMany
    {
        return $this->hasMany(Recipient::class)->orderBy('name');
    }

    public function formattedAccountNumber(): string
    {
        return AccountNumber::format($this->account_number);
    }

    public function birthDate(): ?Carbon
    {
        return Pesel::birthDate((string) $this->pesel);
    }

    public function gender(): ?string
    {
        return Pesel::gender((string) $this->pesel);
    }

    public function genderLabel(): string
    {
        return match ($this->gender()) {
            'M' => 'mężczyzna',
            'K' => 'kobieta',
            default => 'brak danych',
        };
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
