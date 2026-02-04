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
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'order_date')) {
                $table->date('order_date')->after('order_number')->useCurrent();
            }
            if (!Schema::hasColumn('orders', 'order_type')) {
                $table->string('order_type')->default('direct')->after('order_date'); // 'direct', 'reseller'
            }
            if (!Schema::hasColumn('orders', 'customer_id')) {
                $table->foreignId('customer_id')->nullable()->after('reseller_id')->constrained()->nullOnDelete();
            }
            if (!Schema::hasColumn('orders', 'total_cost')) {
                $table->decimal('total_cost', 15, 2)->default(0)->after('total_amount');
            }
            if (!Schema::hasColumn('orders', 'total_commission')) {
                $table->decimal('total_commission', 15, 2)->default(0)->after('total_cost');
            }
        });

        Schema::table('order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('order_items', 'product_variant_id')) {
                 $table->foreignId('product_variant_id')->nullable()->after('product_id')->constrained()->nullOnDelete();
            }
            if (!Schema::hasColumn('order_items', 'base_price')) {
                 $table->decimal('base_price', 15, 2)->default(0)->after('unit_price'); // Snapshot of limit_price/cost
            }
            if (!Schema::hasColumn('order_items', 'subtotal')) {
                 $table->decimal('subtotal', 15, 2)->default(0)->after('total_price');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $columns = ['order_date', 'order_type', 'customer_id', 'total_cost', 'total_commission'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    if ($column === 'customer_id') {
                        $table->dropForeign(['customer_id']);
                    }
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('order_items', function (Blueprint $table) {
            $columns = ['product_variant_id', 'base_price', 'subtotal'];
             foreach ($columns as $column) {
                if (Schema::hasColumn('order_items', $column)) {
                    if ($column === 'product_variant_id') {
                         $table->dropForeign(['product_variant_id']);
                    }
                    $table->dropColumn($column);
                }
            }
        });
    }
};
