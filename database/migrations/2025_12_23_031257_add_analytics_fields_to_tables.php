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
        Schema::table('cities', function (Blueprint $table) {
            $table->string('province')->nullable()->after('district');
        });
        
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->integer('remaining_quantity')->default(0)->after('quantity');
        });
        
        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('cost_price', 10, 2)->default(0)->after('unit_price'); // FIFO Cost Snapshot
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->dropColumn('province');
        });
        
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropColumn('remaining_quantity');
        });
        
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('cost_price');
        });
    }
};
