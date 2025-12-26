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
        Schema::create('mines_games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('bet', 15, 2);
            $table->integer('mine_count');
            $table->json('mines'); // массив ID мин
            $table->json('revealed')->default('[]'); // массив открытых ячеек
            $table->integer('step')->default(0);
            $table->string('status')->default('playing'); // playing, won, lost
            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mines_games');
    }
};
