<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBetsTable extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void {
        Schema::create('bets', function (Blueprint $table) {
            $table->id();
            $table->integer('player_id');
            $table->foreign('player_id')->references('player_id')->on('players');            $table->float('stake_amount');
            $table->unsignedInteger('created_at');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void {
        Schema::dropIfExists('bets');
    }
}
