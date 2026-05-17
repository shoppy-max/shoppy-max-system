<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\InventoryUnit;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\Unit;
use App\Services\InventoryUnitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseSkuBarcodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_barcode_labels_repeat_sku_for_each_quantity_unit(): void
    {
        [$purchase, $variant] = $this->makePurchaseWithPendingUnits(3);

        $units = app(InventoryUnitService::class)->purchaseUnits($purchase);
        $html = view('purchases.barcode-pdf', ['units' => $units])->render();

        $this->assertSame(3, $units->count());
        $this->assertSame(3, substr_count($html, 'Barcode for ' . $variant->sku));
        $this->assertSame(3, substr_count($html, '<div class="unit-code">' . $variant->sku . '</div>'));
        $this->assertStringNotContainsString('Barcode for ' . $units->first()->unit_code, $html);
    }

    public function test_grn_accepts_repeated_sku_barcode_scans_for_pending_units(): void
    {
        [$purchase, $variant] = $this->makePurchaseWithPendingUnits(2);

        $firstScan = app(InventoryUnitService::class)->scanPendingUnitForPurchase($purchase, $variant->sku);
        $secondScan = app(InventoryUnitService::class)->scanPendingUnitForPurchase($purchase->fresh(), $variant->sku);

        $this->assertFalse($firstScan['completed']);
        $this->assertTrue($secondScan['completed']);
        $this->assertSame('complete', $purchase->fresh()->status);
        $this->assertSame(2, InventoryUnit::where('purchase_id', $purchase->id)->where('status', InventoryUnit::STATUS_AVAILABLE)->count());
    }

    public function test_packing_accepts_repeated_sku_barcode_scans_for_allocated_units(): void
    {
        [, $variant] = $this->makePurchaseWithPendingUnits(0);
        $order = Order::forceCreate([
            'order_number' => 'ORD-20260518-9001',
            'order_date' => now()->toDateString(),
            'status' => 'confirm',
            'call_status' => 'confirm',
            'delivery_status' => 'picked_from_rack',
            'payment_method' => 'COD',
            'payment_status' => 'pending',
            'total_amount' => 200,
        ]);
        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,
            'product_name' => 'SKU Barcode Product',
            'sku' => $variant->sku,
            'quantity' => 2,
            'unit_price' => 100,
            'base_price' => 100,
            'cost_price' => 50,
            'total_price' => 200,
        ]);

        foreach (range(1, 2) as $index) {
            InventoryUnit::create([
                'product_variant_id' => $variant->id,
                'order_id' => $order->id,
                'order_item_id' => $orderItem->id,
                'unit_code' => 'UNIT-PACK-SKU-' . $index,
                'status' => InventoryUnit::STATUS_ALLOCATED,
                'sku_snapshot' => $variant->sku,
                'product_name_snapshot' => 'SKU Barcode Product',
                'variant_label_snapshot' => 'Piece',
                'allocated_at' => now(),
                'last_event_at' => now(),
            ]);
        }

        $firstScan = app(InventoryUnitService::class)->scanOrderUnitForPacking($order, $variant->sku);
        $secondScan = app(InventoryUnitService::class)->scanOrderUnitForPacking($order, $variant->sku);

        $this->assertSame(1, $firstScan['scanned_count']);
        $this->assertSame(2, $secondScan['scanned_count']);
        $this->assertSame(2, InventoryUnit::where('order_id', $order->id)->whereNotNull('packed_scan_at')->count());
    }

    private function makePurchaseWithPendingUnits(int $quantity): array
    {
        $supplier = Supplier::create([
            'name' => 'Barcode Supplier',
            'business_name' => 'Barcode Supplier Business',
            'mobile' => '0770000000',
        ]);
        $category = Category::create(['name' => 'Barcode Category', 'code' => 'BAR']);
        $unit = Unit::create(['name' => 'Piece', 'short_name' => 'pcs']);
        $product = Product::create([
            'name' => 'SKU Barcode Product',
            'category_id' => $category->id,
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'sku' => 'SKU-BARCODE-001',
            'selling_price' => 100,
            'limit_price' => 50,
            'quantity' => 0,
            'alert_quantity' => 0,
        ]);
        $purchase = Purchase::create([
            'purchase_number' => 'PUR-SKU-BARCODE-' . $quantity,
            'supplier_id' => $supplier->id,
            'purchase_date' => now()->toDateString(),
            'status' => 'verified',
            'currency' => 'LKR',
            'sub_total' => 50 * $quantity,
            'discount_type' => 'fixed',
            'discount_value' => 0,
            'discount_amount' => 0,
            'net_total' => 50 * $quantity,
            'paid_amount' => 0,
        ]);
        $item = PurchaseItem::create([
            'purchase_id' => $purchase->id,
            'product_id' => $product->id,
            'stock_variant_id' => $variant->id,
            'product_name' => $product->name,
            'quantity' => $quantity,
            'purchase_price' => 50,
            'total' => 50 * $quantity,
        ]);

        app(InventoryUnitService::class)->createPendingUnitsForPurchaseItem($item);

        return [$purchase, $variant];
    }
}
