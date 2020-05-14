<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBalanceTransactionsTable extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void {
        Schema::create('balance_transactions', function (Blueprint $table) {
            $table->id();
            $table->integer('player_id');
            $table->foreign('player_id')->references('player_id')->on('players');
            $table->float('amount');
            $table->float('amount_before');
            $table->timestamp('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void {
        Schema::dropIfExists('balance_transactions');
    }
}
