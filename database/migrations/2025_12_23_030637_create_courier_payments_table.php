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
        Schema::create('courier_payments', function (Blueprint $table) {
            $table->id();
            // The FK is added in a later migration because the paired couriers
            // migration shares the same timestamp and may run after this file.
            $table->foreignId('courier_id');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            
            $table->decimal('amount', 10, 2);
            $table->date('payment_date');
            $table->string('reference_number')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courier_payments');
    }
};
