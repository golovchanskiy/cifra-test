<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();

            $table->uuid('transaction_id');
            $table->bigInteger('user_id')
                ->unsigned();
            $table->decimal('amount');
            $table->integer('status')
                ->unsigned();
            $table->unique(['transaction_id', 'user_id'], 'uk_transaction_transaction_id_user_id');
            $table->index(['user_id', 'status'], 'ix_transaction_user_id_status_id');

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
