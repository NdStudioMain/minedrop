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
        Schema::table('banks', function (Blueprint $table) {
            $table->decimal('default_balance', 15, 2)->nullable()->change();
            $table->decimal('totalBets', 15, 2)->nullable()->change();
            $table->decimal('totalWins', 15, 2)->nullable()->change();
            $table->decimal('rtp', 10, 2)->nullable()->change();
            $table->decimal('houseEdge', 10, 2)->nullable()->change();
            $table->decimal('maxPayoutPercent', 10, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('banks', function (Blueprint $table) {
            $table->decimal('default_balance', 15, 2)->nullable(false)->change();
            $table->decimal('totalBets', 15, 2)->nullable(false)->change();
            $table->decimal('totalWins', 15, 2)->nullable(false)->change();
            $table->decimal('rtp', 10, 2)->nullable(false)->change();
            $table->decimal('houseEdge', 10, 2)->nullable(false)->change();
            $table->decimal('maxPayoutPercent', 10, 2)->nullable(false)->change();
        });
    }
};
