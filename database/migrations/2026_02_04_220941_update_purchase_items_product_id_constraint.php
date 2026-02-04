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
        Schema::table('purchase_items', function (Blueprint $table) {
            // Drop the foreign key constraint on product_id
            // This allows us to store product_id even if the product doesn't exist
            // We keep product_name as the source of truth
            $table->dropForeign(['product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            // Re-add the foreign key constraint
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
        });
    }
};
