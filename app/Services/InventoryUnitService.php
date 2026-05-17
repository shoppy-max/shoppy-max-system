<?php

namespace App\Services;

use App\Models\InventoryUnit;
use App\Models\InventoryUnitEvent;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class InventoryUnitService
{
    public function createPendingUnitsForPurchaseItem(PurchaseItem $item, ?int $userId = null): Collection
    {
        return $this->createUnitsForPurchaseItem($item, InventoryUnit::STATUS_PENDING_RECEIPT, false, $userId);
    }

    public function createAvailableUnitsForPurchaseItem(PurchaseItem $item, ?int $userId = null): Collection
    {
        return $this->createUnitsForPurchaseItem($item, InventoryUnit::STATUS_AVAILABLE, true, $userId);
    }

    public function archivePendingUnitsForPurchase(Purchase $purchase, string $reason, ?int $userId = null): int
    {
        $units = InventoryUnit::query()
            ->where('purchase_id', $purchase->id)
            ->where('status', InventoryUnit::STATUS_PENDING_RECEIPT)
            ->lockForUpdate()
            ->get();

        return $this->archiveUnits($units, 'archived', $reason, $userId);
    }

    public function activatePendingUnitsForPurchase(Purchase $purchase, ?int $userId = null): int
    {
        $units = InventoryUnit::query()
            ->where('purchase_id', $purchase->id)
            ->whereIn('status', [
                InventoryUnit::STATUS_PENDING_RECEIPT,
                InventoryUnit::STATUS_GRN_SCANNED,
            ])
            ->lockForUpdate()
            ->get();

        if ($units->isEmpty()) {
            return 0;
        }

        foreach ($units as $unit) {
            $this->activatePendingUnit($unit, $userId, null, 'bulk_activation');
        }

        return $units->count();
    }

    public function scanPendingUnitForPurchase(Purchase $purchase, string $rawUnitCode, ?int $userId = null): array
    {
        $purchase = Purchase::query()
            ->whereKey($purchase->id)
            ->lockForUpdate()
            ->firstOrFail();

        $unitCode = strtoupper(preg_replace('/\s+/', '', trim($rawUnitCode)));

        if ($unitCode === '') {
            throw ValidationException::withMessages([
                'unit_code' => 'Scan a valid GRN barcode.',
            ]);
        }

        $unit = InventoryUnit::query()
            ->with(['purchase', 'purchaseItem.variant.unit'])
            ->where('unit_code', $unitCode)
            ->lockForUpdate()
            ->first();

        if (! $unit) {
            $unit = $this->nextPendingPurchaseUnitForSku($purchase, $unitCode);
        }

        if (! $unit) {
            if ($this->purchaseHasSku($purchase, $unitCode)) {
                throw ValidationException::withMessages([
                    'unit_code' => 'All labels for this SKU are already scanned for this GRN.',
                ]);
            }

            throw ValidationException::withMessages([
                'unit_code' => 'Scanned barcode was not found. Use a valid GRN label.',
            ]);
        }

        if ((int) $unit->purchase_id !== (int) $purchase->id) {
            $sourcePurchase = $unit->purchase?->purchase_number;

            throw ValidationException::withMessages([
                'unit_code' => $sourcePurchase
                    ? "This label belongs to {$sourcePurchase}, not {$purchase->purchase_number}."
                    : 'This label belongs to another purchase.',
            ]);
        }

        if ($unit->status === InventoryUnit::STATUS_ARCHIVED) {
            throw ValidationException::withMessages([
                'unit_code' => 'This label is archived and cannot be scanned into GRN.',
            ]);
        }

        if ($unit->status === InventoryUnit::STATUS_GRN_SCANNED) {
            throw ValidationException::withMessages([
                'unit_code' => 'This label is already scanned for this GRN.',
            ]);
        }

        if ($unit->status !== InventoryUnit::STATUS_PENDING_RECEIPT) {
            $statusLabel = ucfirst(str_replace('_', ' ', (string) $unit->status));

            throw ValidationException::withMessages([
                'unit_code' => "This label is already {$statusLabel}.",
            ]);
        }

        $this->markUnitAsGrnScanned($unit, $userId);

        $remainingPendingUnits = InventoryUnit::query()
            ->where('purchase_id', $purchase->id)
            ->where('status', InventoryUnit::STATUS_PENDING_RECEIPT)
            ->lockForUpdate()
            ->count();

        $completed = false;

        if ($remainingPendingUnits === 0 && ($purchase->status ?? 'pending') !== 'complete') {
            $this->activateScannedUnitsForPurchase($purchase, $userId);
            $completed = true;
            $purchase->status = 'complete';
            $purchase->completed_by = $userId;
            $purchase->completed_at = now();
            $purchase->stock_applied_at = now();
            $purchase->save();
        }

        $purchaseItem = $unit->purchaseItem;
        if (! $purchaseItem) {
            throw ValidationException::withMessages([
                'unit_code' => 'This label is not linked to a purchase item.',
            ]);
        }

        if ($purchaseItem) {
            $purchaseItem->load(['variant.unit', 'inventoryUnits']);
        }

        return [
            'unit' => $unit->fresh(['purchase']),
            'item' => $purchaseItem,
            'completed' => $completed,
        ];
    }

    public function ensureOrderUnitsAllocated(Order $order, ?int $userId = null, bool $adjustVariantStock = true): void
    {
        $order->loadMissing(['items.variant.product', 'items.variant.unit']);

        foreach ($order->items as $item) {
            $this->ensureOrderItemUnitAllocation($item, $userId, $adjustVariantStock);
        }
    }

    public function releaseOrderUnits(Order $order, string $eventType, ?int $userId = null, bool $adjustVariantStock = true): int
    {
        $units = InventoryUnit::query()
            ->where('order_id', $order->id)
            ->whereIn('status', [
                InventoryUnit::STATUS_ALLOCATED,
                InventoryUnit::STATUS_DELIVERED,
            ])
            ->lockForUpdate()
            ->get();

        if ($units->isEmpty()) {
            return 0;
        }

        return $this->releaseUnits($units, $eventType, $userId, $adjustVariantStock);
    }

    public function markOrderUnitsDelivered(Order $order, ?int $userId = null): int
    {
        $units = InventoryUnit::query()
            ->where('order_id', $order->id)
            ->where('status', InventoryUnit::STATUS_ALLOCATED)
            ->lockForUpdate()
            ->get();

        if ($units->isEmpty()) {
            return 0;
        }

        $now = now();
        foreach ($units as $unit) {
            $unit->status = InventoryUnit::STATUS_DELIVERED;
            $unit->delivered_at = $now;
            $unit->last_event_at = $now;
            $unit->save();

            $this->recordEvent($unit, 'delivered', $userId);
        }

        return $units->count();
    }

    public function markOrderUnitsAllocated(Order $order, ?int $userId = null): int
    {
        $units = InventoryUnit::query()
            ->where('order_id', $order->id)
            ->where('status', InventoryUnit::STATUS_DELIVERED)
            ->lockForUpdate()
            ->get();

        if ($units->isEmpty()) {
            return 0;
        }

        $now = now();
        foreach ($units as $unit) {
            $unit->status = InventoryUnit::STATUS_ALLOCATED;
            $unit->last_event_at = $now;
            $unit->save();

            $this->recordEvent($unit, 'reopened', $userId);
        }

        return $units->count();
    }

    public function scanOrderUnitForPacking(Order $order, string $rawUnitCode, ?int $userId = null): array
    {
        $unitCode = strtoupper(preg_replace('/\s+/', '', trim($rawUnitCode)));

        if ($unitCode === '') {
            throw ValidationException::withMessages([
                'unit_code' => 'Scan a valid item barcode.',
            ]);
        }

        $unit = InventoryUnit::query()
            ->with(['order', 'orderItem'])
            ->where('unit_code', $unitCode)
            ->lockForUpdate()
            ->first();

        if (! $unit) {
            $unit = $this->nextUnscannedOrderUnitForSku($order, $unitCode);
        }

        if (! $unit) {
            if ($this->orderHasSku($order, $unitCode)) {
                throw ValidationException::withMessages([
                    'unit_code' => 'All labels for this SKU are already scanned for packing.',
                ]);
            }

            throw ValidationException::withMessages([
                'unit_code' => 'Scanned barcode was not found.',
            ]);
        }

        if ((int) ($unit->order_id ?? 0) !== (int) $order->id) {
            $sourceOrder = $unit->order?->order_number;

            throw ValidationException::withMessages([
                'unit_code' => $sourceOrder
                    ? "This label belongs to {$sourceOrder}, not {$order->order_number}."
                    : 'This label does not belong to this order.',
            ]);
        }

        if ($unit->status !== InventoryUnit::STATUS_ALLOCATED) {
            $statusLabel = ucfirst(str_replace('_', ' ', (string) $unit->status));

            throw ValidationException::withMessages([
                'unit_code' => "This label is already {$statusLabel}.",
            ]);
        }

        if ($unit->packed_scan_at) {
            throw ValidationException::withMessages([
                'unit_code' => 'This label is already scanned for packing.',
            ]);
        }

        if (! $unit->orderItem) {
            throw ValidationException::withMessages([
                'unit_code' => 'This label is not linked to an order item.',
            ]);
        }

        $now = now();

        $unit->packed_scan_at = $now;
        $unit->packed_scan_user_id = $userId;
        $unit->last_event_at = $now;
        $unit->save();

        $this->recordEvent(
            $unit,
            'packing_scanned',
            $userId,
            ['source' => 'packing_scan'],
            'Scanned during packing.'
        );

        $scannedCount = InventoryUnit::query()
            ->where('order_item_id', $unit->order_item_id)
            ->whereNotNull('packed_scan_at')
            ->count();

        return [
            'unit' => $unit->fresh(),
            'order_item_id' => (int) $unit->order_item_id,
            'scanned_count' => $scannedCount,
            'required_count' => max((int) ($unit->orderItem->quantity ?? 0), 0),
        ];
    }

    public function purchaseUnits(Purchase $purchase): Collection
    {
        return InventoryUnit::query()
            ->with(['productVariant.product', 'productVariant.unit', 'purchaseItem'])
            ->where('purchase_id', $purchase->id)
            ->where('status', '!=', InventoryUnit::STATUS_ARCHIVED)
            ->orderBy('purchase_item_id')
            ->orderBy('id')
            ->get();
    }

    private function nextPendingPurchaseUnitForSku(Purchase $purchase, string $sku): ?InventoryUnit
    {
        return InventoryUnit::query()
            ->with(['purchase', 'purchaseItem.variant.unit'])
            ->where('purchase_id', $purchase->id)
            ->where('status', InventoryUnit::STATUS_PENDING_RECEIPT)
            ->where($this->skuMatchCallback($sku))
            ->orderBy('purchase_item_id')
            ->orderBy('id')
            ->lockForUpdate()
            ->first();
    }

    private function purchaseHasSku(Purchase $purchase, string $sku): bool
    {
        return InventoryUnit::query()
            ->where('purchase_id', $purchase->id)
            ->where('status', '!=', InventoryUnit::STATUS_ARCHIVED)
            ->where($this->skuMatchCallback($sku))
            ->exists();
    }

    private function nextUnscannedOrderUnitForSku(Order $order, string $sku): ?InventoryUnit
    {
        return InventoryUnit::query()
            ->with(['order', 'orderItem'])
            ->where('order_id', $order->id)
            ->where('status', InventoryUnit::STATUS_ALLOCATED)
            ->whereNull('packed_scan_at')
            ->where($this->skuMatchCallback($sku))
            ->orderBy('order_item_id')
            ->orderBy('id')
            ->lockForUpdate()
            ->first();
    }

    private function orderHasSku(Order $order, string $sku): bool
    {
        return InventoryUnit::query()
            ->where('order_id', $order->id)
            ->where('status', '!=', InventoryUnit::STATUS_ARCHIVED)
            ->where($this->skuMatchCallback($sku))
            ->exists();
    }

    private function skuMatchCallback(string $sku): callable
    {
        return function ($query) use ($sku) {
            $query
                ->whereRaw('UPPER(sku_snapshot) = ?', [$sku])
                ->orWhereHas('productVariant', function ($variantQuery) use ($sku) {
                    $variantQuery->whereRaw('UPPER(sku) = ?', [$sku]);
                });
        };
    }

    public function purchaseItemUnits(PurchaseItem $item): Collection
    {
        return InventoryUnit::query()
            ->with(['productVariant.product', 'productVariant.unit'])
            ->where('purchase_item_id', $item->id)
            ->where('status', '!=', InventoryUnit::STATUS_ARCHIVED)
            ->orderBy('id')
            ->get();
    }

    public function backfillFromCurrentState(?callable $progress = null): array
    {
        if (! Schema::hasTable('inventory_units') || ! Schema::hasTable('inventory_unit_events')) {
            throw ValidationException::withMessages([
                'inventory' => 'Inventory unit tables are not available. Run migrations first.',
            ]);
        }

        if (InventoryUnit::query()->exists()) {
            throw ValidationException::withMessages([
                'inventory' => 'Inventory units already exist. Backfill only runs on an empty inventory unit store.',
            ]);
        }

        $created = 0;
        $allocated = 0;
        $delivered = 0;
        $legacyAvailable = 0;
        $legacyAllocated = 0;
        $archived = 0;

        Purchase::query()
            ->with(['items.variant.product', 'items.variant.unit'])
            ->orderBy('id')
            ->chunk(50, function ($purchases) use (&$created, $progress) {
                foreach ($purchases as $purchase) {
                    $status = ($purchase->status ?? 'pending') === 'complete'
                        ? InventoryUnit::STATUS_AVAILABLE
                        : InventoryUnit::STATUS_PENDING_RECEIPT;

                    foreach ($purchase->items as $item) {
                        $created += $this->createUnitsForPurchaseItem($item, $status, false, null)->count();
                    }

                    $progress && $progress('purchase', $purchase->purchase_number ?? (string) $purchase->id);
                }
            });

        Order::query()
            ->with(['items.variant.product', 'items.variant.unit'])
            ->orderBy('id')
            ->chunk(50, function ($orders) use (&$allocated, &$delivered, &$legacyAllocated, $progress) {
                foreach ($orders as $order) {
                    if ((string) ($order->status ?? '') === 'cancel') {
                        continue;
                    }

                    if (strtolower((string) ($order->delivery_status ?? '')) === 'returned') {
                        continue;
                    }

                    foreach ($order->items as $item) {
                        $state = strtolower((string) ($order->delivery_status ?? '')) === 'delivered'
                            ? InventoryUnit::STATUS_DELIVERED
                            : InventoryUnit::STATUS_ALLOCATED;

                        $result = $this->backfillAllocateOrderItemUnits($order, $item, $state);
                        $allocated += $result['allocated'];
                        $delivered += $result['delivered'];
                        $legacyAllocated += $result['legacy_allocated'];
                    }

                    $progress && $progress('order', $order->order_number ?? (string) $order->id);
                }
            });

        ProductVariant::query()
            ->with(['product', 'unit'])
            ->orderBy('id')
            ->chunk(100, function ($variants) use (&$legacyAvailable, &$archived) {
                foreach ($variants as $variant) {
                    $currentAvailableCount = InventoryUnit::query()
                        ->where('product_variant_id', $variant->id)
                        ->where('status', InventoryUnit::STATUS_AVAILABLE)
                        ->count();

                    $targetAvailableCount = max((int) ($variant->quantity ?? 0), 0);

                    if ($currentAvailableCount < $targetAvailableCount) {
                        $legacyAvailable += $this->createLegacyAvailableUnits(
                            $variant,
                            $targetAvailableCount - $currentAvailableCount
                        );
                    } elseif ($currentAvailableCount > $targetAvailableCount) {
                        $archived += $this->archiveExcessAvailableUnits(
                            $variant,
                            $currentAvailableCount - $targetAvailableCount
                        );
                    }

                    $this->syncVariantQuantityToAvailableUnits($variant);
                }
            });

        return [
            'created' => $created,
            'allocated' => $allocated,
            'delivered' => $delivered,
            'legacy_available' => $legacyAvailable,
            'legacy_allocated' => $legacyAllocated,
            'archived' => $archived,
        ];
    }

    private function createUnitsForPurchaseItem(
        PurchaseItem $item,
        string $status,
        bool $logCreatedAsStocked,
        ?int $userId = null
    ): Collection {
        $item->loadMissing(['purchase', 'variant.product', 'variant.unit']);

        $quantity = max((int) ($item->quantity ?? 0), 0);
        if ($quantity < 1 || ! $item->variant) {
            return collect();
        }

        $now = now();
        $units = collect();
        $eventType = $logCreatedAsStocked ? 'received_to_stock' : 'created';

        for ($index = 0; $index < $quantity; $index++) {
            $unit = InventoryUnit::create([
                'product_variant_id' => $item->variant->id,
                'purchase_id' => $item->purchase_id,
                'purchase_item_id' => $item->id,
                'status' => $status,
                'sku_snapshot' => $item->variant->sku,
                'product_name_snapshot' => $item->variant->product->name ?? $item->product_name,
                'variant_label_snapshot' => $this->buildVariantLabel($item->variant),
                'available_at' => $status === InventoryUnit::STATUS_AVAILABLE ? $now : null,
                'last_event_at' => $now,
            ]);

            $unit->unit_code = $this->generateUnitCode($unit);
            $unit->save();

            $this->recordEvent($unit, $eventType, $userId);
            $units->push($unit);
        }

        return $units;
    }

    private function activatePendingUnit(
        InventoryUnit $unit,
        ?int $userId = null,
        ?string $note = null,
        string $source = 'grn_scan'
    ): void {
        $now = now();

        $unit->status = InventoryUnit::STATUS_AVAILABLE;
        $unit->available_at = $now;
        $unit->last_event_at = $now;
        $unit->save();

        ProductVariant::query()
            ->whereKey((int) $unit->product_variant_id)
            ->lockForUpdate()
            ->increment('quantity', 1);

        $this->recordEvent($unit, 'received_to_stock', $userId, ['source' => $source], $note);
    }

    private function markUnitAsGrnScanned(InventoryUnit $unit, ?int $userId = null): void
    {
        $now = now();

        $unit->status = InventoryUnit::STATUS_GRN_SCANNED;
        $unit->last_event_at = $now;
        $unit->save();

        $this->recordEvent($unit, 'grn_scanned', $userId, ['source' => 'grn_scan'], 'Scanned during GRN checking.');
    }

    private function activateScannedUnitsForPurchase(Purchase $purchase, ?int $userId = null): int
    {
        $units = InventoryUnit::query()
            ->where('purchase_id', $purchase->id)
            ->where('status', InventoryUnit::STATUS_GRN_SCANNED)
            ->lockForUpdate()
            ->get();

        if ($units->isEmpty()) {
            return 0;
        }

        foreach ($units as $unit) {
            $this->activatePendingUnit($unit, $userId, 'GRN fully completed.', 'grn_completion');
        }

        return $units->count();
    }

    private function ensureOrderItemUnitAllocation(OrderItem $item, ?int $userId, bool $adjustVariantStock): void
    {
        $item->loadMissing(['variant.product', 'variant.unit', 'order']);

        $variant = $item->variant;
        if (! $variant) {
            throw ValidationException::withMessages([
                'items' => 'A selected order item variant is invalid.',
            ]);
        }

        $targetQty = max((int) ($item->quantity ?? 0), 0);
        if ($targetQty < 1) {
            return;
        }

        $existing = InventoryUnit::query()
            ->where('order_item_id', $item->id)
            ->whereIn('status', [
                InventoryUnit::STATUS_ALLOCATED,
                InventoryUnit::STATUS_DELIVERED,
            ])
            ->lockForUpdate()
            ->get();

        if ($existing->count() > $targetQty) {
            $this->releaseUnits($existing->slice($targetQty)->values(), 'released_for_rebalance', $userId, $adjustVariantStock);
            $existing = $existing->take($targetQty)->values();
        }

        $missing = $targetQty - $existing->count();
        if ($missing <= 0) {
            return;
        }

        $availableUnits = InventoryUnit::query()
            ->where('product_variant_id', $variant->id)
            ->where('status', InventoryUnit::STATUS_AVAILABLE)
            ->orderBy('id')
            ->lockForUpdate()
            ->limit($missing)
            ->get();

        if ($availableUnits->count() < $missing) {
            $displayName = $variant->product->name ?? $item->product_name ?? $variant->sku ?? 'selected product';

            throw ValidationException::withMessages([
                'items' => "Insufficient unit stock for {$displayName}.",
            ]);
        }

        $now = now();
        foreach ($availableUnits as $unit) {
            $unit->status = InventoryUnit::STATUS_ALLOCATED;
            $unit->order_id = $item->order_id;
            $unit->order_item_id = $item->id;
            $unit->allocated_at = $now;
            $unit->packed_scan_at = null;
            $unit->packed_scan_user_id = null;
            $unit->last_event_at = $now;
            $unit->save();

            $this->recordEvent($unit, 'allocated', $userId);
        }

        if ($adjustVariantStock) {
            ProductVariant::query()->whereKey($variant->id)->lockForUpdate()->decrement('quantity', $availableUnits->count());
        }
    }

    private function releaseUnits(Collection $units, string $eventType, ?int $userId, bool $adjustVariantStock = true): int
    {
        if ($units->isEmpty()) {
            return 0;
        }

        $now = now();

        foreach ($units as $unit) {
            $unit->status = InventoryUnit::STATUS_AVAILABLE;
            $unit->order_id = null;
            $unit->order_item_id = null;
            $unit->packed_scan_at = null;
            $unit->packed_scan_user_id = null;
            $unit->available_at = $now;
            $unit->last_event_at = $now;
            $unit->save();

            $this->recordEvent($unit, $eventType, $userId);
        }

        if ($adjustVariantStock) {
            $units->groupBy('product_variant_id')->each(function (Collection $group, $variantId) {
                ProductVariant::query()->whereKey((int) $variantId)->lockForUpdate()->increment('quantity', $group->count());
            });
        }

        return $units->count();
    }

    private function archiveUnits(Collection $units, string $eventType, string $note, ?int $userId = null): int
    {
        if ($units->isEmpty()) {
            return 0;
        }

        $now = now();
        foreach ($units as $unit) {
            $unit->status = InventoryUnit::STATUS_ARCHIVED;
            $unit->archived_at = $now;
            $unit->last_event_at = $now;
            $unit->save();

            $this->recordEvent($unit, $eventType, $userId, ['note' => $note], $note);
        }

        return $units->count();
    }

    private function backfillAllocateOrderItemUnits(Order $order, OrderItem $item, string $targetStatus): array
    {
        $qty = max((int) ($item->quantity ?? 0), 0);
        if ($qty < 1 || ! $item->product_variant_id) {
            return ['allocated' => 0, 'delivered' => 0, 'legacy_allocated' => 0];
        }

        $availableUnits = InventoryUnit::query()
            ->where('product_variant_id', $item->product_variant_id)
            ->where('status', InventoryUnit::STATUS_AVAILABLE)
            ->orderBy('id')
            ->limit($qty)
            ->get();

        $allocatedCount = 0;
        foreach ($availableUnits as $unit) {
            $unit->status = $targetStatus;
            $unit->order_id = $order->id;
            $unit->order_item_id = $item->id;
            $unit->allocated_at = $order->order_date ? \Carbon\Carbon::parse($order->order_date) : now();
            $unit->delivered_at = $targetStatus === InventoryUnit::STATUS_DELIVERED
                ? ($order->delivered_at ?? now())
                : null;
            $unit->last_event_at = now();
            $unit->save();

            $this->recordEvent($unit, $targetStatus === InventoryUnit::STATUS_DELIVERED ? 'backfill_delivered' : 'backfill_allocated');
            $allocatedCount++;
        }

        $legacyCount = 0;
        $missing = $qty - $allocatedCount;
        if ($missing > 0) {
            $variant = ProductVariant::query()->with(['product', 'unit'])->find($item->product_variant_id);

            for ($index = 0; $index < $missing; $index++) {
                $unit = InventoryUnit::create([
                    'product_variant_id' => $item->product_variant_id,
                    'order_id' => $order->id,
                    'order_item_id' => $item->id,
                    'status' => $targetStatus,
                    'sku_snapshot' => $variant?->sku ?? $item->sku,
                    'product_name_snapshot' => $variant?->product?->name ?? $item->product_name,
                    'variant_label_snapshot' => $variant ? $this->buildVariantLabel($variant) : null,
                    'allocated_at' => $order->order_date ? \Carbon\Carbon::parse($order->order_date) : now(),
                    'delivered_at' => $targetStatus === InventoryUnit::STATUS_DELIVERED
                        ? ($order->delivered_at ?? now())
                        : null,
                    'last_event_at' => now(),
                ]);
                $unit->unit_code = $this->generateUnitCode($unit, 'LEG');
                $unit->save();

                $this->recordEvent(
                    $unit,
                    $targetStatus === InventoryUnit::STATUS_DELIVERED ? 'backfill_legacy_delivered' : 'backfill_legacy_allocated'
                );
                $legacyCount++;
            }
        }

        return [
            'allocated' => $targetStatus === InventoryUnit::STATUS_ALLOCATED ? $qty : 0,
            'delivered' => $targetStatus === InventoryUnit::STATUS_DELIVERED ? $qty : 0,
            'legacy_allocated' => $legacyCount,
        ];
    }

    private function createLegacyAvailableUnits(ProductVariant $variant, int $count): int
    {
        if ($count < 1) {
            return 0;
        }

        $created = 0;
        for ($index = 0; $index < $count; $index++) {
            $unit = InventoryUnit::create([
                'product_variant_id' => $variant->id,
                'status' => InventoryUnit::STATUS_AVAILABLE,
                'sku_snapshot' => $variant->sku,
                'product_name_snapshot' => $variant->product->name ?? null,
                'variant_label_snapshot' => $this->buildVariantLabel($variant),
                'available_at' => now(),
                'last_event_at' => now(),
            ]);
            $unit->unit_code = $this->generateUnitCode($unit, 'LEG');
            $unit->save();
            $this->recordEvent($unit, 'backfill_legacy_available');
            $created++;
        }

        return $created;
    }

    private function archiveExcessAvailableUnits(ProductVariant $variant, int $count): int
    {
        if ($count < 1) {
            return 0;
        }

        $units = InventoryUnit::query()
            ->where('product_variant_id', $variant->id)
            ->where('status', InventoryUnit::STATUS_AVAILABLE)
            ->orderByDesc('purchase_id')
            ->orderByDesc('id')
            ->limit($count)
            ->get();

        return $this->archiveUnits($units, 'backfill_archived', 'Archived during inventory unit reconciliation.');
    }

    private function recordEvent(
        InventoryUnit $unit,
        string $eventType,
        ?int $userId = null,
        array $metadata = [],
        ?string $note = null
    ): void {
        InventoryUnitEvent::create([
            'inventory_unit_id' => $unit->id,
            'user_id' => $userId,
            'purchase_id' => $unit->purchase_id,
            'purchase_item_id' => $unit->purchase_item_id,
            'order_id' => $unit->order_id,
            'order_item_id' => $unit->order_item_id,
            'event_type' => $eventType,
            'note' => $note,
            'metadata' => empty($metadata) ? null : $metadata,
        ]);
    }

    private function buildVariantLabel(ProductVariant $variant): string
    {
        $unitName = trim((string) ($variant->unit?->name ?? $variant->unit?->short_name ?? ''));
        $unitValue = trim((string) ($variant->unit_value ?? ''));

        if ($unitValue !== '' && $unitName !== '') {
            return $unitValue.' '.$unitName;
        }

        return $unitValue !== '' ? $unitValue : $unitName;
    }

    private function generateUnitCode(InventoryUnit $unit, string $prefix = 'IU'): string
    {
        return strtoupper($prefix.'-'.str_pad((string) $unit->id, 10, '0', STR_PAD_LEFT));
    }

    public function syncVariantQuantityToAvailableUnits(ProductVariant $variant): void
    {
        $availableCount = InventoryUnit::query()
            ->where('product_variant_id', $variant->id)
            ->where('status', InventoryUnit::STATUS_AVAILABLE)
            ->count();

        if ((int) $variant->quantity !== $availableCount) {
            $variant->quantity = $availableCount;
            $variant->save();
        }
    }
}
