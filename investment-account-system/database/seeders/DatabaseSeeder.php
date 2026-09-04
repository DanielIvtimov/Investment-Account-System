<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Services\TransactionService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $transactionService = app(TransactionService::class);

        $ana = Client::create([
            'name' => 'Ana',
        ]);

        $transactionService->deposit($ana, '1000.00');

        $transactionService->buy(
            $ana,
            'AAPL',
            5,
            '100.00'
        );

        $transactionService->sell(
            $ana,
            'AAPL',
            3,
            '120.00'
        );

        $bob = Client::create([
            'name' => 'Bob',
        ]);

        $transactionService->deposit($bob, '500.00');

        $transactionService->buy(
            $bob,
            'MSFT',
            2,
            '100.00'
        );
    }
}
