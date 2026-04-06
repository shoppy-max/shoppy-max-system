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
        $this->addForeignKeyIfMissing(
            table: 'courier_payments',
            column: 'courier_id',
            referencedTable: 'couriers',
            onDelete: 'cascade',
        );

        $this->addForeignKeyIfMissing(
            table: 'purchase_items',
            column: 'purchase_id',
            referencedTable: 'purchases',
            onDelete: 'cascade',
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropForeignKeyIfExists('courier_payments', 'courier_id');
        $this->dropForeignKeyIfExists('purchase_items', 'purchase_id');
    }

    private function addForeignKeyIfMissing(
        string $table,
        string $column,
        string $referencedTable,
        string $referencedColumn = 'id',
        string $onDelete = 'cascade',
    ): void {
        if (! Schema::hasTable($table) || ! Schema::hasTable($referencedTable) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        if ($this->foreignKeyExists($table, $column, $referencedTable)) {
            return;
        }

        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column, $referencedTable, $referencedColumn, $onDelete) {
            $foreign = $blueprint->foreign($column)->references($referencedColumn)->on($referencedTable);

            if ($onDelete === 'cascade') {
                $foreign->cascadeOnDelete();
            } else {
                $foreign->onDelete($onDelete);
            }
        });
    }

    private function dropForeignKeyIfExists(string $table, string $column): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        if (! $this->foreignKeyExists($table, $column)) {
            return;
        }

        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column) {
            $blueprint->dropForeign([$column]);
        });
    }

    private function foreignKeyExists(string $table, string $column, ?string $referencedTable = null): bool
    {
        return match (DB::getDriverName()) {
            'mysql', 'mariadb' => DB::table('information_schema.KEY_COLUMN_USAGE')
                ->where('TABLE_SCHEMA', DB::getDatabaseName())
                ->where('TABLE_NAME', $table)
                ->where('COLUMN_NAME', $column)
                ->when($referencedTable !== null, fn ($query) => $query->where('REFERENCED_TABLE_NAME', $referencedTable))
                ->whereNotNull('REFERENCED_TABLE_NAME')
                ->exists(),
            'sqlite' => collect(DB::select("PRAGMA foreign_key_list('{$table}')"))
                ->contains(function ($foreignKey) use ($column, $referencedTable) {
                    return $foreignKey->from === $column
                        && ($referencedTable === null || $foreignKey->table === $referencedTable);
                }),
            default => false,
        };
    }
};
