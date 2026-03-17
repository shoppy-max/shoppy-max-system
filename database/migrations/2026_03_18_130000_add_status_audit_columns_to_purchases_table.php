<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            if (!Schema::hasColumn('purchases', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('purchases', 'checked_by')) {
                $table->foreignId('checked_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('purchases', 'checked_at')) {
                $table->timestamp('checked_at')->nullable()->after('checked_by');
            }

            if (!Schema::hasColumn('purchases', 'verified_by')) {
                $table->foreignId('verified_by')->nullable()->after('checked_at')->constrained('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('purchases', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('verified_by');
            }

            if (!Schema::hasColumn('purchases', 'completed_by')) {
                $table->foreignId('completed_by')->nullable()->after('verified_at')->constrained('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('purchases', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('completed_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            foreach (['created_by', 'checked_by', 'verified_by', 'completed_by'] as $foreignKeyColumn) {
                if (Schema::hasColumn('purchases', $foreignKeyColumn)) {
                    $table->dropConstrainedForeignId($foreignKeyColumn);
                }
            }

            foreach (['checked_at', 'verified_at', 'completed_at'] as $timestampColumn) {
                if (Schema::hasColumn('purchases', $timestampColumn)) {
                    $table->dropColumn($timestampColumn);
                }
            }
        });
    }
};
