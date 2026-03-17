<?php

use App\Services\InventoryUnitService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('inventory-units:backfill', function (InventoryUnitService $inventoryUnits) {
    DB::transaction(function () use ($inventoryUnits) {
        $summary = $inventoryUnits->backfillFromCurrentState(function (string $type, string $reference) {
            $this->line("Processed {$type}: {$reference}");
        });

        $this->newLine();
        $this->info('Inventory unit backfill completed.');
        $this->table(
            ['Metric', 'Count'],
            collect($summary)->map(fn ($count, $metric) => [$metric, $count])->values()->all()
        );
    });
})->purpose('Backfill inventory unit records from current purchases, orders, and stock');

Artisan::command('inventory-units:sync-stock', function (InventoryUnitService $inventoryUnits) {
    $updated = 0;

    App\Models\ProductVariant::query()->chunk(100, function ($variants) use ($inventoryUnits, &$updated) {
        foreach ($variants as $variant) {
            $before = (int) $variant->quantity;
            $inventoryUnits->syncVariantQuantityToAvailableUnits($variant);

            if ((int) $variant->quantity !== $before) {
                $updated++;
            }
        }
    });

    $this->info("Synced aggregate stock for {$updated} variants.");
})->purpose('Force product variant quantity to match available inventory units');
