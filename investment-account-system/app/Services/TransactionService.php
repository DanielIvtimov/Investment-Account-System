<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Exceptions\InsufficientFundsException;
use App\Exceptions\InsufficientHoldingsException;
use App\Models\Client;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    public function deposit(Client $client, string $amount): Transaction
    {
        return DB::transaction(function () use ($client, $amount) {
            return Transaction::create([
                'client_id' => $client->id,
                'type' => TransactionType::Deposit,
                'cash_amount' => $amount,
            ]);
        });
    }

    public function withdraw(Client $client, string $amount): Transaction
    {
        return DB::transaction(function () use ($client, $amount) {
            $locked = Client::whereKey($client->id)->lockForUpdate()->first();

            $available = $locked->cashBalance();

            if (bccomp($available, $amount, 2) < 0) {
                throw new InsufficientFundsException($available, $amount);
            }

            return Transaction::create([
                'client_id' => $locked->id,
                'type' => TransactionType::Withdrawal,
                'cash_amount' => $amount,
            ]);
        });
    }

    public function buy(Client $client, string $ticker, int $quantity, string $pricePerUnit): Transaction
    {
        return DB::transaction(function () use ($client, $ticker, $quantity, $pricePerUnit) {
            $locked = Client::whereKey($client->id)->lockForUpdate()->first();

            $cashAmount = $this->roundHalfUp(bcmul((string) $quantity, $pricePerUnit, 4));

            $available = $locked->cashBalance();

            if (bccomp($available, $cashAmount, 2) < 0) {
                throw new InsufficientFundsException($available, $cashAmount);
            }

            return Transaction::create([
                'client_id' => $locked->id,
                'type' => TransactionType::Buy,
                'cash_amount' => $cashAmount,
                'instrument_ticker' => $ticker,
                'quantity' => $quantity,
                'price_per_unit' => $pricePerUnit,
            ]);
        });
    }

    public function sell(Client $client, string $ticker, int $quantity, string $pricePerUnit): Transaction
    {
        return DB::transaction(function () use ($client, $ticker, $quantity, $pricePerUnit) {
            $locked = Client::whereKey($client->id)->lockForUpdate()->first();

            $holding = $locked->holdings()->firstWhere('instrument_ticker', $ticker);
            $available = $holding['quantity'] ?? 0;

            if ($available < $quantity) {
                throw new InsufficientHoldingsException($ticker, $available, $quantity);
            }

            $cashAmount = $this->roundHalfUp(bcmul((string) $quantity, $pricePerUnit, 4));

            return Transaction::create([
                'client_id' => $locked->id,
                'type' => TransactionType::Sell,
                'cash_amount' => $cashAmount,
                'instrument_ticker' => $ticker,
                'quantity' => $quantity,
                'price_per_unit' => $pricePerUnit,
            ]);
        });
    }

    private function roundHalfUp(string $value): string
    {
        return bcadd(bcadd($value, '0.005', 4), '0', 2);
    }
}

