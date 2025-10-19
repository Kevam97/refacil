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
            $table->uuid('transaction_id')->unique();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 20, 6);
            $table->enum('type', ['deposit','withdraw']);
            $table->timestamp('occurred_at');
            $table->json('metadata')->nullable();
            $table->enum('status', ['pending','processed','failed'])->default('pending');
            $table->string('reason')->nullable();
            $table->timestamps();
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
