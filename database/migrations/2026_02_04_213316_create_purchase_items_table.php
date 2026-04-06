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
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            // The FK is added in a later migration because the paired purchases
            // migration shares the same timestamp and may run after this file.
            $table->foreignId('purchase_id');
            $table->foreignId('product_id')->nullable()->constrained()->onDelete('set null');
            $table->string('product_name'); // Backup incase product is deleted
            $table->integer('quantity')->default(1);
            $table->decimal('purchase_price', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
    }
};
