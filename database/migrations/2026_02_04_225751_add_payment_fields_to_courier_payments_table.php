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
        Schema::table('courier_payments', function (Blueprint $table) {
            $table->string('payment_method')->nullable()->after('payment_date');
            $table->text('payment_note')->nullable()->after('payment_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courier_payments', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'payment_note']);
        });
    }
};
