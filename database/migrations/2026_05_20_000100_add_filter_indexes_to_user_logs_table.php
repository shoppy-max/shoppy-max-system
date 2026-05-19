<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_logs', function (Blueprint $table) {
            $table->index(['user_id', 'occurred_at']);
            $table->index(['status_code', 'occurred_at']);
            $table->index(['method', 'occurred_at']);
            $table->index(['route_name', 'occurred_at']);
            $table->index(['ip_address', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::table('user_logs', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'occurred_at']);
            $table->dropIndex(['status_code', 'occurred_at']);
            $table->dropIndex(['method', 'occurred_at']);
            $table->dropIndex(['route_name', 'occurred_at']);
            $table->dropIndex(['ip_address', 'occurred_at']);
        });
    }
};
