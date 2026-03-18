<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('orders')
            ->where('delivery_status', 'return_requested')
            ->update(['delivery_status' => 'returned']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse mapping. The obsolete state should not be restored.
    }
};
