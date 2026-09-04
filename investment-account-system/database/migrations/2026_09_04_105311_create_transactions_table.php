<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->enum('type', ['deposit', 'withdrawal', 'buy', 'sell']);
            $table->decimal('cash_amount', 15, 2)->unsigned();
            $table->string('instrument_ticker', 20)->nullable();
            $table->unsignedInteger('quantity')->nullable();
            $table->decimal('price_per_unit', 15, 4)->unsigned()->nullable();

            // created_at only, no updated_at. 
            $table->timestamp('created_at')->useCurrent();
            $table->index(['client_id', 'type']);
            $table->index(['client_id', 'instrument_ticker']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
