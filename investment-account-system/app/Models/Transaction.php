<?php

namespace App\Models;

use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'client_id',
        'type',
        'cash_amount',
        'instrument_ticker',
        'quantity',
        'price_per_unit',
    ];

    protected $casts = [
        'type' => TransactionType::class,
        'cash_amount' => 'decimal:2',
        'price_per_unit' => 'decimal:4',
        'quantity' => 'integer',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
