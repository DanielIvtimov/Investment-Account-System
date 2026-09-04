<?php

namespace Tests\Feature;

use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_follow_the_assignment_example(): void
    {
        $client = Client::create([
            'name' => 'Ana',
        ]);

        $this->postJson("/api/clients/{$client->id}/deposit", [
            'amount' => '1000.00',
        ])->assertCreated();

        $this->postJson("/api/clients/{$client->id}/buy", [
            'ticker' => 'AAPL',
            'quantity' => 5,
            'price_per_unit' => '100.00',
        ])->assertCreated();

        $this->postJson("/api/clients/{$client->id}/sell", [
            'ticker' => 'AAPL',
            'quantity' => 3,
            'price_per_unit' => '120.00',
        ])->assertCreated();

        $this->getJson("/api/clients/{$client->id}")
            ->assertOk()
            ->assertJson([
                'id' => $client->id,
                'name' => 'Ana',
                'cash_balance' => '860.00',
                'holdings' => [
                    [
                        'instrument_ticker' => 'AAPL',
                        'quantity' => 2,
                    ],
                ],
            ]);
    }

    public function test_client_can_withdraw_available_cash(): void
    {
        $client = Client::create([
            'name' => 'Ana',
        ]);

        $this->postJson("/api/clients/{$client->id}/deposit", [
            'amount' => '1000.00',
        ])->assertCreated();

        $this->postJson("/api/clients/{$client->id}/withdraw", [
            'amount' => '1000.00',
        ])->assertCreated();

        $this->getJson("/api/clients/{$client->id}")
            ->assertOk()
            ->assertJson([
                'cash_balance' => '0.00',
                'holdings' => [],
            ]);
    }

    public function test_client_cannot_withdraw_more_than_available_cash(): void
    {
        $client = Client::create([
            'name' => 'Ana',
        ]);

        $this->postJson("/api/clients/{$client->id}/deposit", [
            'amount' => '1000.00',
        ])->assertCreated();

        $response = $this->postJson("/api/clients/{$client->id}/withdraw", [
            'amount' => '1000.01',
        ]);

        $response
            ->assertStatus(409)
            ->assertJson([
                'message' => 'Insufficient funds.',
                'error' => 'insufficient_funds',
            ]);

        $this->getJson("/api/clients/{$client->id}")
            ->assertOk()
            ->assertJson([
                'cash_balance' => '1000.00',
                'holdings' => [],
            ]);
    }

    public function test_client_cannot_buy_more_than_available_cash(): void
    {
        $client = Client::create([
            'name' => 'Ana',
        ]);

        $this->postJson("/api/clients/{$client->id}/deposit", [
            'amount' => '500.00',
        ])->assertCreated();

        $response = $this->postJson("/api/clients/{$client->id}/buy", [
            'ticker' => 'AAPL',
            'quantity' => 6,
            'price_per_unit' => '100.00',
        ]);

        $response
            ->assertStatus(409)
            ->assertJson([
                'message' => 'Insufficient funds.',
                'error' => 'insufficient_funds',
            ]);

        $this->getJson("/api/clients/{$client->id}")
            ->assertOk()
            ->assertJson([
                'cash_balance' => '500.00',
                'holdings' => [],
            ]);
    }

    public function test_client_can_buy_with_exact_available_cash(): void
    {
        $client = Client::create([
            'name' => 'Ana',
        ]);

        $this->postJson("/api/clients/{$client->id}/deposit", [
            'amount' => '500.00',
        ])->assertCreated();

        $this->postJson("/api/clients/{$client->id}/buy", [
            'ticker' => 'AAPL',
            'quantity' => 5,
            'price_per_unit' => '100.00',
        ])->assertCreated();

        $this->getJson("/api/clients/{$client->id}")
            ->assertOk()
            ->assertJson([
                'cash_balance' => '0.00',
                'holdings' => [
                    [
                        'instrument_ticker' => 'AAPL',
                        'quantity' => 5,
                    ],
                ],
            ]);
    }

    public function test_client_cannot_sell_more_than_available_holdings(): void
    {
        $client = Client::create([
            'name' => 'Ana',
        ]);

        $this->postJson("/api/clients/{$client->id}/deposit", [
            'amount' => '1000.00',
        ])->assertCreated();

        $this->postJson("/api/clients/{$client->id}/buy", [
            'ticker' => 'AAPL',
            'quantity' => 5,
            'price_per_unit' => '100.00',
        ])->assertCreated();

        $response = $this->postJson("/api/clients/{$client->id}/sell", [
            'ticker' => 'AAPL',
            'quantity' => 6,
            'price_per_unit' => '120.00',
        ]);

        $response
            ->assertStatus(409)
            ->assertJson([
                'message' => 'Insufficient holdings for AAPL.',
                'error' => 'insufficient_holdings',
            ]);

        $this->getJson("/api/clients/{$client->id}")
            ->assertOk()
            ->assertJson([
                'cash_balance' => '500.00',
                'holdings' => [
                    [
                        'instrument_ticker' => 'AAPL',
                        'quantity' => 5,
                    ],
                ],
            ]);
    }

    public function test_client_can_sell_exact_available_holdings(): void
    {
        $client = Client::create([
            'name' => 'Ana',
        ]);

        $this->postJson("/api/clients/{$client->id}/deposit", [
            'amount' => '1000.00',
        ])->assertCreated();

        $this->postJson("/api/clients/{$client->id}/buy", [
            'ticker' => 'AAPL',
            'quantity' => 5,
            'price_per_unit' => '100.00',
        ])->assertCreated();

        $this->postJson("/api/clients/{$client->id}/sell", [
            'ticker' => 'AAPL',
            'quantity' => 5,
            'price_per_unit' => '120.00',
        ])->assertCreated();

        $this->getJson("/api/clients/{$client->id}")
            ->assertOk()
            ->assertJson([
                'cash_balance' => '1100.00',
                'holdings' => [],
            ]);
    }

    public function test_transaction_requests_validate_required_fields(): void
    {
        $client = Client::create([
            'name' => 'Ana',
        ]);

        $this->postJson("/api/clients/{$client->id}/deposit", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['amount']);

        $this->postJson("/api/clients/{$client->id}/withdraw", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['amount']);

        $this->postJson("/api/clients/{$client->id}/buy", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'ticker',
                'quantity',
                'price_per_unit',
            ]);

        $this->postJson("/api/clients/{$client->id}/sell", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'ticker',
                'quantity',
                'price_per_unit',
            ]);
    }

    public function test_transaction_requests_reject_non_positive_values(): void
    {
        $client = Client::create([
            'name' => 'Ana',
        ]);

        $this->postJson("/api/clients/{$client->id}/deposit", [
            'amount' => '0.00',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['amount']);

        $this->postJson("/api/clients/{$client->id}/withdraw", [
            'amount' => '-10.00',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['amount']);

        $this->postJson("/api/clients/{$client->id}/buy", [
            'ticker' => 'AAPL',
            'quantity' => 0,
            'price_per_unit' => '100.00',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['quantity']);

        $this->postJson("/api/clients/{$client->id}/sell", [
            'ticker' => 'AAPL',
            'quantity' => 1,
            'price_per_unit' => '0.00',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['price_per_unit']);
    }

    public function test_buy_and_sell_amounts_are_rounded_to_two_decimal_places(): void
    {
        $client = Client::create([
            'name' => 'Ana',
        ]);

        $this->postJson("/api/clients/{$client->id}/deposit", [
            'amount' => '1000.00',
        ])->assertCreated();

        $this->postJson("/api/clients/{$client->id}/buy", [
            'ticker' => 'AAPL',
            'quantity' => 3,
            'price_per_unit' => '100.3333',
        ])
            ->assertCreated()
            ->assertJson([
                'cash_amount' => '301.00',
                'price_per_unit' => '100.3333',
            ]);

        $this->getJson("/api/clients/{$client->id}")
            ->assertOk()
            ->assertJson([
                'cash_balance' => '699.00',
                'holdings' => [
                    [
                        'instrument_ticker' => 'AAPL',
                        'quantity' => 3,
                    ],
                ],
            ]);

        $this->postJson("/api/clients/{$client->id}/sell", [
            'ticker' => 'AAPL',
            'quantity' => 3,
            'price_per_unit' => '100.3333',
        ])
            ->assertCreated()
            ->assertJson([
                'cash_amount' => '301.00',
                'price_per_unit' => '100.3333',
            ]);

        $this->getJson("/api/clients/{$client->id}")
            ->assertOk()
            ->assertJson([
                'cash_balance' => '1000.00',
                'holdings' => [],
            ]);
    }

    public function test_clients_have_isolated_account_state(): void
    {
        $ana = Client::create([
            'name' => 'Ana',
        ]);

        $bob = Client::create([
            'name' => 'Bob',
        ]);

        $this->postJson("/api/clients/{$ana->id}/deposit", [
            'amount' => '1000.00',
        ])->assertCreated();

        $this->postJson("/api/clients/{$ana->id}/buy", [
            'ticker' => 'AAPL',
            'quantity' => 5,
            'price_per_unit' => '100.00',
        ])->assertCreated();

        $this->getJson("/api/clients/{$ana->id}")
            ->assertOk()
            ->assertJson([
                'cash_balance' => '500.00',
                'holdings' => [
                    [
                        'instrument_ticker' => 'AAPL',
                        'quantity' => 5,
                    ],
                ],
            ]);

        $this->getJson("/api/clients/{$bob->id}")
            ->assertOk()
            ->assertJson([
                'cash_balance' => '0.00',
                'holdings' => [],
            ]);
    }

    public function test_client_can_view_own_transaction_history(): void
    {
        $client = Client::create([
            'name' => 'Ana',
        ]);

        $this->postJson("/api/clients/{$client->id}/deposit", [
            'amount' => '1000.00',
        ])->assertCreated();

        $this->postJson("/api/clients/{$client->id}/buy", [
            'ticker' => 'AAPL',
            'quantity' => 5,
            'price_per_unit' => '100.00',
        ])->assertCreated();

        $this->postJson("/api/clients/{$client->id}/sell", [
            'ticker' => 'AAPL',
            'quantity' => 2,
            'price_per_unit' => '120.00',
        ])->assertCreated();

        $this->getJson("/api/clients/{$client->id}/transactions")
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.type', 'deposit')
            ->assertJsonPath('data.0.cash_amount', '1000.00')
            ->assertJsonPath('data.1.type', 'buy')
            ->assertJsonPath('data.1.instrument_ticker', 'AAPL')
            ->assertJsonPath('data.1.quantity', 5)
            ->assertJsonPath('data.1.cash_amount', '500.00')
            ->assertJsonPath('data.2.type', 'sell')
            ->assertJsonPath('data.2.instrument_ticker', 'AAPL')
            ->assertJsonPath('data.2.quantity', 2)
            ->assertJsonPath('data.2.cash_amount', '240.00');
    }
    
    public function test_client_can_only_view_own_transaction_history(): void
    {
        $ana = Client::create([
            'name' => 'Ana',
        ]);

        $bob = Client::create([
            'name' => 'Bob',
        ]);

        $this->postJson("/api/clients/{$ana->id}/deposit", [
            'amount' => '1000.00',
        ])->assertCreated();

        $this->postJson("/api/clients/{$bob->id}/deposit", [
            'amount' => '500.00',
        ])->assertCreated();

        $this->postJson("/api/clients/{$bob->id}/buy", [
            'ticker' => 'MSFT',
            'quantity' => 2,
            'price_per_unit' => '100.00',
        ])->assertCreated();

        $this->getJson("/api/clients/{$ana->id}/transactions")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.client_id', $ana->id)
            ->assertJsonPath('data.0.type', 'deposit')
            ->assertJsonPath('data.0.cash_amount', '1000.00');
    }
}