<?php

namespace App\Models;

use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Client extends Model
{
    protected $fillable = [
        'name',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function cashBalance(): string
    {
        $deposits = $this->transactions()
            ->where('type', TransactionType::Deposit)
            ->sum('cash_amount');
        $withdrawals = $this->transactions()
            ->where('type', TransactionType::Withdrawal)
            ->sum('cash_amount');
        $buys = $this->transactions()
            ->where('type', TransactionType::Buy)
            ->sum('cash_amount');
        $sells = $this->transactions()
            ->where('type', TransactionType::Sell)
            ->sum('cash_amount');
        
        $balance = bcadd($deposits, $sells, 2);
        $balance = bcsub($balance, $withdrawals, 2);
        $balance = bcsub($balance, $buys, 2);

        return $balance;
    }

    public function holdings(): Collection
    {
        $bought = $this->transactions()
            ->where('type', TransactionType::Buy)
            ->groupBy('instrument_ticker')
            ->selectRaw('instrument_ticker, SUM(quantity) as quantity')
            ->pluck('quantity', 'instrument_ticker');
        $sold = $this->transactions()
            ->where('type', TransactionType::Sell)
            ->groupBy('instrument_ticker')
            ->selectRaw('instrument_ticker, SUM(quantity) as quantity')
            ->pluck('quantity', 'instrument_ticker');

        return $bought 
            ->map(fn ($quantity, $ticker) => [
                'instrument_ticker' => $ticker,
                'quantity' => $quantity - ($sold[$ticker] ?? 0),
            ])
            ->filter(fn ($holding) => $holding['quantity'] > 0)
            ->values();
    }
}
