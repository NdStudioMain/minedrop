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
        Schema::table('users', function (Blueprint $table) {
            $table->bigInteger('tg_id')->nullable()->unique()->after('id');
            $table->string('username')->nullable()->after('name');
            $table->string('avatar')->nullable()->after('username');
            $table->string('ref_code')->nullable()->unique()->after('avatar');
            $table->foreignId('referrer_id')->nullable()->constrained('users')->nullOnDelete()->after('ref_code');
            $table->decimal('balance', 10, 2)->default(0)->after('referrer_id');
            $table->decimal('ref_balance', 10, 2)->default(0)->after('balance');
            $table->bigInteger('bonus_time')->nullable()->after('ref_balance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['referrer_id']);
            $table->dropColumn([
                'tg_id',
                'username',
                'avatar',
                'ref_code',
                'referrer_id',
                'balance',
                'ref_balance',
                'bonus_time',
            ]);
        });
    }
};
