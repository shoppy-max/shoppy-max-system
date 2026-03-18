<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'discount_type')) {
                $table->string('discount_type')->default('fixed')->after('discount_amount');
            }

            if (!Schema::hasColumn('orders', 'discount_value')) {
                $table->decimal('discount_value', 15, 2)->default(0)->after('discount_type');
            }
        });

        DB::table('orders')
            ->where(function ($query) {
                $query->whereNull('discount_type')
                    ->orWhere('discount_type', '');
            })
            ->update(['discount_type' => 'fixed']);

        DB::table('orders')->update([
            'discount_value' => DB::raw('COALESCE(discount_amount, 0)'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'discount_value')) {
                $table->dropColumn('discount_value');
            }

            if (Schema::hasColumn('orders', 'discount_type')) {
                $table->dropColumn('discount_type');
            }
        });
    }
};
