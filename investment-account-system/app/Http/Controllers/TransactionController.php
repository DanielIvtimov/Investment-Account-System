<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBuyRequest;
use App\Http\Requests\StoreDepositRequest;
use App\Http\Requests\StoreSellRequest;
use App\Http\Requests\StoreWithdrawalRequest;
use App\Models\Client;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;

class TransactionController extends Controller
{
    public function __construct(private readonly TransactionService $transactionService) {}

    public function deposit(StoreDepositRequest $request, Client $client): JsonResponse
    {
        $transaction = $this->transactionService->deposit($client, $request->validated('amount'));

        return response()->json([
            'id' => $transaction->id,
            'client_id' => $transaction->client_id,
            'type' => $transaction->type->value,
            'cash_amount' => $transaction->cash_amount,
            'created_at' => $transaction->created_at,
        ], 201);
    }

    public function withdraw(StoreWithdrawalRequest $request, Client $client): JsonResponse
    {
        $transaction = $this->transactionService->withdraw($client, $request->validated('amount'));

        return response()->json([
            'id' => $transaction->id,
            'client_id' => $transaction->client_id,
            'type' => $transaction->type->value,
            'cash_amount' => $transaction->cash_amount,
            'created_at' => $transaction->created_at,
        ], 201);
    }

    public function buy(StoreBuyRequest $request, Client $client): JsonResponse
    {
        $transaction = $this->transactionService->buy(
            $client,
            $request->validated('ticker'),
            $request->validated('quantity'),
            $request->validated('price_per_unit')
        );

        return response()->json([
            'id' => $transaction->id,
            'client_id' => $transaction->client_id,
            'type' => $transaction->type->value,
            'cash_amount' => $transaction->cash_amount,
            'instrument_ticker' => $transaction->instrument_ticker,
            'quantity' => $transaction->quantity,
            'price_per_unit' => $transaction->price_per_unit,
            'created_at' => $transaction->created_at,
        ], 201);
    }

    public function sell(StoreSellRequest $request, Client $client): JsonResponse
    {
        $transaction = $this->transactionService->sell(
            $client,
            $request->validated('ticker'),
            $request->validated('quantity'),
            $request->validated('price_per_unit')
        );

        return response()->json([
            'id' => $transaction->id,
            'client_id' => $transaction->client_id,
            'type' => $transaction->type->value,
            'cash_amount' => $transaction->cash_amount,
            'instrument_ticker' => $transaction->instrument_ticker,
            'quantity' => $transaction->quantity,
            'price_per_unit' => $transaction->price_per_unit,
            'created_at' => $transaction->created_at,
        ], 201);
    }

    public function index(Client $client): JsonResponse
    {
        $transactions = $client->transactions()
            ->orderBy('id')
            ->get();

        return response()->json([
            'data' => $transactions,
        ]);
    }
}
