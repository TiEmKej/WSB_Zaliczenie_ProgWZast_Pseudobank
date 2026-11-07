<?php

namespace App\Models;

use App\Support\AccountNumber;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['name', 'account_number'])]
class Recipient extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function formattedAccountNumber(): string
    {
        return AccountNumber::format($this->account_number);
    }
}
