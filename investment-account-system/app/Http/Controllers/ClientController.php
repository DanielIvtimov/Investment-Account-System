<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClientRequest;
use App\Models\Client;
use Illuminate\Http\JsonResponse;

class ClientController extends Controller
{
    public function store(StoreClientRequest $request): JsonResponse
    {
        $client = Client::create($request->validated());

        return response()->json([
            'id' => $client->id,
            'name' => $client->name,
        ], 201);
    }

    public function show(Client $client): JsonResponse
    {
        return response()->json([
            'id' => $client->id,
            'name' => $client->name,
            'cash_balance' => $client->cashBalance(),
            'holdings' => $client->holdings(),
        ]);
    }
}
