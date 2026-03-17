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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
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
            ->where('status', InventoryUnit::STATUS_PENDING_RECEIPT)
            ->lockForUpdate()
            ->get();

        if ($units->isEmpty()) {
            return 0;
        }

        $now = now();

        foreach ($units as $unit) {
            $unit->status = InventoryUnit::STATUS_AVAILABLE;
            $unit->available_at = $now;
            $unit->last_event_at = $now;
            $unit->save();

            $this->recordEvent($unit, 'received_to_stock', $userId);
        }

        $units->groupBy('product_variant_id')->each(function (Collection $group, $variantId) {
            ProductVariant::query()->whereKey((int) $variantId)->lockForUpdate()->increment('quantity', $group->count());
        });

        return $units->count();
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
        if (!Schema::hasTable('inventory_units') || !Schema::hasTable('inventory_unit_events')) {
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
        if ($quantity < 1 || !$item->variant) {
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

    private function ensureOrderItemUnitAllocation(OrderItem $item, ?int $userId, bool $adjustVariantStock): void
    {
        $item->loadMissing(['variant.product', 'variant.unit', 'order']);

        $variant = $item->variant;
        if (!$variant) {
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
        if ($qty < 1 || !$item->product_variant_id) {
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
            return $unitValue . ' ' . $unitName;
        }

        return $unitValue !== '' ? $unitValue : $unitName;
    }

    private function generateUnitCode(InventoryUnit $unit, string $prefix = 'IU'): string
    {
        return strtoupper($prefix . '-' . str_pad((string) $unit->id, 10, '0', STR_PAD_LEFT));
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
