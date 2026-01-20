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
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'payment_system_id')) {
                $table->foreignId('payment_system_id')->nullable()->after('user_id');
            }
            if (! Schema::hasColumn('payments', 'payment_url')) {
                $table->string('payment_url')->nullable()->after('status');
            }
            if (! Schema::hasColumn('payments', 'payment_id')) {
                $table->string('payment_id')->nullable()->after('payment_url');
            }
            if (! Schema::hasColumn('payments', 'payment_data')) {
                $table->json('payment_data')->nullable()->after('payment_id');
            }
        });

        // Меняем тип status на string если нужно
        if (Schema::hasColumn('payments', 'status')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->string('status')->default('pending')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['payment_system_id', 'payment_url', 'payment_id', 'payment_data']);
        });
    }
};
