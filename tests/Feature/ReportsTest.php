<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\InventoryUnit;
use App\Models\InventoryUnitEvent;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_report_uses_available_inventory_units_and_fifo_value(): void
    {
        $user = User::factory()->create();
        [$product, $variant] = $this->makeProductWithVariant('Report Cream', 'RPT-CRM-100', '100 ml');
        [$purchase, $purchaseItem] = $this->makePurchaseItem($product, $variant, 4, 125);
        $this->makeInventoryUnit($variant, $purchase, $purchaseItem, InventoryUnit::STATUS_AVAILABLE, 'RPT-1');
        $this->makeInventoryUnit($variant, $purchase, $purchaseItem, InventoryUnit::STATUS_AVAILABLE, 'RPT-2');
        $this->makeInventoryUnit($variant, $purchase, $purchaseItem, InventoryUnit::STATUS_ALLOCATED, 'RPT-3');
        $variant->update(['quantity' => 2]);

        $response = $this->actingAs($user)->get(route('reports.stock', ['search' => 'RPT-CRM']));

        $response->assertOk();
        $response->assertSee('Report Cream');
        $response->assertSee('RPT-CRM-100');
        $response->assertSee('100 ml');
        $response->assertSee('2 PCS');
        $response->assertSee('250.00');
        $response->assertSee(route('reports.stock.show', $variant), false);
        $response->assertSee('export=pdf');
        $response->assertSee('export=excel');
    }

    public function test_stock_detail_report_shows_purchase_sale_cancel_and_return_movements(): void
    {
        $user = User::factory()->create();
        [$product, $variant] = $this->makeProductWithVariant('Movement Product', 'MOVE-001', '1 pcs');
        [$purchase, $purchaseItem] = $this->makePurchaseItem($product, $variant, 1, 75);
        $unit = $this->makeInventoryUnit($variant, $purchase, $purchaseItem, InventoryUnit::STATUS_AVAILABLE, 'MOVE-1');
        $saleOrder = $this->makeOrder('ORD-20260520-0001', $user, 'delivered');
        $cancelOrder = $this->makeOrder('ORD-20260520-0002', $user, 'cancel');
        $returnOrder = $this->makeOrder('ORD-20260520-0003', $user, 'returned');

        InventoryUnitEvent::create([
            'inventory_unit_id' => $unit->id,
            'purchase_id' => $purchase->id,
            'purchase_item_id' => $purchaseItem->id,
            'event_type' => 'received_to_stock',
            'created_at' => '2026-05-20 08:00:00',
            'updated_at' => '2026-05-20 08:00:00',
        ]);
        InventoryUnitEvent::create([
            'inventory_unit_id' => $unit->id,
            'purchase_id' => $purchase->id,
            'purchase_item_id' => $purchaseItem->id,
            'order_id' => $saleOrder->id,
            'event_type' => 'allocated',
            'created_at' => '2026-05-20 09:00:00',
            'updated_at' => '2026-05-20 09:00:00',
        ]);
        InventoryUnitEvent::create([
            'inventory_unit_id' => $unit->id,
            'purchase_id' => $purchase->id,
            'purchase_item_id' => $purchaseItem->id,
            'event_type' => 'released_on_cancel',
            'metadata' => ['order_id' => $cancelOrder->id],
            'created_at' => '2026-05-20 10:00:00',
            'updated_at' => '2026-05-20 10:00:00',
        ]);
        InventoryUnitEvent::create([
            'inventory_unit_id' => $unit->id,
            'purchase_id' => $purchase->id,
            'purchase_item_id' => $purchaseItem->id,
            'event_type' => 'released_on_return',
            'metadata' => ['order_id' => $returnOrder->id],
            'created_at' => '2026-05-20 11:00:00',
            'updated_at' => '2026-05-20 11:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('reports.stock.show', $variant));

        $response->assertOk();
        $response->assertSee('Purchasing');
        $response->assertSee('Sale');
        $response->assertSee('Cancel');
        $response->assertSee('Return');
        $response->assertSee('PUR-RPT-001');
        $response->assertSee('ORD-20260520-0001');
        $response->assertSee('ORD-20260520-0002');
        $response->assertSee('ORD-20260520-0003');
        $response->assertSee(route('purchases.show', $purchase), false);
        $response->assertSee(route('orders.show', $saleOrder), false);
        $response->assertSee(route('orders.show', $cancelOrder), false);
        $response->assertSee(route('orders.show', $returnOrder), false);
        $response->assertSee('Order or purchase no');
        $response->assertSee('View Purchase');
        $response->assertSee('View Order');
        $response->assertSee('+1');
        $response->assertSee('-1');
        $response->assertSee('75.00');
        $response->assertSee('-75.00');
    }

    public function test_stock_detail_filters_movements_and_exports_the_filtered_rows(): void
    {
        Excel::fake();

        $user = User::factory()->create();
        [$product, $variant] = $this->makeProductWithVariant('Filtered Movement Product', 'MOVE-FILTER-001', '1 pcs');
        [$purchase, $purchaseItem] = $this->makePurchaseItem($product, $variant, 1, 75);
        $unit = $this->makeInventoryUnit($variant, $purchase, $purchaseItem, InventoryUnit::STATUS_AVAILABLE, 'MOVE-FILTER-1');
        $saleOrder = $this->makeOrder('ORD-20260520-0091', $user, 'delivered');
        $returnOrder = $this->makeOrder('ORD-20260521-0092', $user, 'returned');

        InventoryUnitEvent::create([
            'inventory_unit_id' => $unit->id,
            'purchase_id' => $purchase->id,
            'purchase_item_id' => $purchaseItem->id,
            'event_type' => 'received_to_stock',
            'created_at' => '2026-05-20 08:00:00',
            'updated_at' => '2026-05-20 08:00:00',
        ]);
        InventoryUnitEvent::create([
            'inventory_unit_id' => $unit->id,
            'purchase_id' => $purchase->id,
            'purchase_item_id' => $purchaseItem->id,
            'order_id' => $saleOrder->id,
            'event_type' => 'allocated',
            'created_at' => '2026-05-20 09:00:00',
            'updated_at' => '2026-05-20 09:00:00',
        ]);
        InventoryUnitEvent::create([
            'inventory_unit_id' => $unit->id,
            'purchase_id' => $purchase->id,
            'purchase_item_id' => $purchaseItem->id,
            'event_type' => 'released_on_return',
            'metadata' => ['order_id' => $returnOrder->id],
            'created_at' => '2026-05-21 10:00:00',
            'updated_at' => '2026-05-21 10:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('reports.stock.show', [
            'variant' => $variant->id,
            'type' => 'Sale',
            'reference' => '0091',
            'start_date' => '2026-05-20',
            'end_date' => '2026-05-20',
        ]));

        $response->assertOk();
        $response->assertSee('Sale');
        $response->assertSee('ORD-20260520-0091');
        $response->assertDontSee('PUR-RPT-001');
        $response->assertDontSee('ORD-20260521-0092');
        $response->assertSee('-75.00');

        $this->actingAs($user)->get(route('reports.stock.show', [
            'variant' => $variant->id,
            'type' => 'Sale',
            'reference' => '0091',
            'start_date' => '2026-05-20',
            'end_date' => '2026-05-20',
            'export' => 'excel',
        ]));

        Excel::assertDownloaded('stock-movement-MOVE-FILTER-001.xlsx');
    }

    public function test_stock_detail_counts_legacy_created_active_units_as_opening_purchases(): void
    {
        $user = User::factory()->create();
        [$product, $variant] = $this->makeProductWithVariant('Legacy Movement Product', 'LEGACY-MOVE-001', '1 pcs');
        [$purchase, $purchaseItem] = $this->makePurchaseItem($product, $variant, 2, 60);
        $availableUnit = $this->makeInventoryUnit($variant, $purchase, $purchaseItem, InventoryUnit::STATUS_AVAILABLE, 'LEGACY-ACTIVE-1');
        $pendingUnit = $this->makeInventoryUnit($variant, $purchase, $purchaseItem, InventoryUnit::STATUS_PENDING_RECEIPT, 'LEGACY-PENDING-1');
        $saleOrder = $this->makeOrder('ORD-20260520-0191', $user, 'delivered');

        InventoryUnitEvent::create([
            'inventory_unit_id' => $availableUnit->id,
            'purchase_id' => $purchase->id,
            'purchase_item_id' => $purchaseItem->id,
            'event_type' => 'created',
            'created_at' => '2026-05-20 08:00:00',
            'updated_at' => '2026-05-20 08:00:00',
        ]);
        InventoryUnitEvent::create([
            'inventory_unit_id' => $pendingUnit->id,
            'purchase_id' => $purchase->id,
            'purchase_item_id' => $purchaseItem->id,
            'event_type' => 'created',
            'created_at' => '2026-05-20 08:05:00',
            'updated_at' => '2026-05-20 08:05:00',
        ]);
        InventoryUnitEvent::create([
            'inventory_unit_id' => $availableUnit->id,
            'purchase_id' => $purchase->id,
            'purchase_item_id' => $purchaseItem->id,
            'order_id' => $saleOrder->id,
            'event_type' => 'backfill_delivered',
            'created_at' => '2026-05-20 09:00:00',
            'updated_at' => '2026-05-20 09:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('reports.stock.show', $variant));

        $response->assertOk();
        $response->assertSee('Purchasing');
        $response->assertSee('Sale');
        $response->assertSee('>0</td>', false);
        $this->assertStringNotContainsString('text-white">-1</td>', $response->getContent());
    }

    public function test_packed_and_pick_from_rack_report_counts_user_activity_with_date_filter(): void
    {
        $viewer = User::factory()->create();
        $operator = User::factory()->create(['name' => 'Packing User', 'email' => 'packing@example.test']);
        $other = User::factory()->create(['name' => 'Other User']);
        $this->makeOrder('ORD-20260520-0101', $operator, 'packed', pickedBy: $operator, packedBy: $operator, pickedAt: '2026-05-20 09:00:00', packedAt: '2026-05-20 09:30:00');
        $this->makeOrder('ORD-20260520-0102', $operator, 'packed', packedBy: $operator, packedAt: '2026-05-20 10:00:00');
        $this->makeOrder('ORD-20260519-0103', $other, 'packed', pickedBy: $other, packedBy: $other, pickedAt: '2026-05-19 09:00:00', packedAt: '2026-05-19 09:30:00');

        $response = $this->actingAs($viewer)->get(route('reports.packet-count', [
            'user_id' => $operator->id,
            'start_date' => '2026-05-20',
            'end_date' => '2026-05-20',
        ]));

        $response->assertOk();
        $response->assertSee('Packing User');
        $response->assertSee('2');
        $response->assertSee('1');
        $response->assertDontSee('Other User</div>', false);
    }

    public function test_product_sales_report_excludes_cancelled_orders_and_applies_return_percentage_filter(): void
    {
        $user = User::factory()->create();
        [$product, $variant] = $this->makeProductWithVariant('Sales Product', 'SALE-001', '1 pcs');
        $this->makeOrderWithItem('ORD-20260520-0201', $user, $variant, 3, 'delivered');
        $this->makeOrderWithItem('ORD-20260520-0202', $user, $variant, 2, 'returned');
        $this->makeOrderWithItem('ORD-20260520-0203', $user, $variant, 5, 'cancel');

        $response = $this->actingAs($user)->get(route('reports.product-sales', [
            'search' => 'SALE-001',
            'start_date' => '2026-05-20',
            'end_date' => '2026-05-20',
            'min_return_percentage' => 30,
            'max_return_percentage' => 50,
        ]));

        $response->assertOk();
        $response->assertSee('Sales Product');
        $response->assertSee('SALE-001');
        $response->assertSee('5');
        $response->assertSee('3');
        $response->assertSee('2');
        $response->assertSee('40.00%');
        $response->assertDontSee('>10<', false);
    }

    public function test_user_sales_report_excludes_cancelled_orders_and_calculates_return_rates(): void
    {
        $viewer = User::factory()->create();
        $operator = User::factory()->create(['name' => 'Sales Operator', 'email' => 'operator@example.test']);
        [$product, $variant] = $this->makeProductWithVariant('User Sales Product', 'USR-SALE-001', '1 pcs');
        $this->makeOrderWithItem('ORD-20260520-0301', $operator, $variant, 3, 'delivered');
        $this->makeOrderWithItem('ORD-20260520-0302', $operator, $variant, 2, 'returned');
        $this->makeOrderWithItem('ORD-20260520-0303', $operator, $variant, 5, 'cancel');

        $response = $this->actingAs($viewer)->get(route('reports.user-sales', [
            'search' => 'operator@example.test',
            'start_date' => '2026-05-20',
            'end_date' => '2026-05-20',
            'min_return_percentage' => 30,
            'max_return_percentage' => 50,
        ]));

        $response->assertOk();
        $response->assertSee('Sales Operator');
        $response->assertSee('operator@example.test');
        $response->assertSee('2');
        $response->assertSee('1');
        $response->assertSee('5');
        $response->assertSee('3');
        $response->assertSee('40.00%');
    }

    public function test_report_excel_exports_use_the_current_filtered_dataset(): void
    {
        Excel::fake();

        $user = User::factory()->create();
        [$product, $variant] = $this->makeProductWithVariant('Export Product', 'EXP-001', '1 pcs');
        $this->makeOrderWithItem('ORD-20260520-0401', $user, $variant, 1, 'delivered');

        $this->actingAs($user)->get(route('reports.product-sales', [
            'search' => 'EXP-001',
            'export' => 'excel',
        ]));

        Excel::assertDownloaded('product-wise-sale-report.xlsx');
    }

    public function test_report_pdf_export_downloads_filtered_output(): void
    {
        $user = User::factory()->create();
        [$product, $variant] = $this->makeProductWithVariant('Pdf Product', 'PDF-001', '1 pcs');
        [$purchase, $purchaseItem] = $this->makePurchaseItem($product, $variant, 1, 50);
        $this->makeInventoryUnit($variant, $purchase, $purchaseItem, InventoryUnit::STATUS_AVAILABLE, 'PDF-1');

        $response = $this->actingAs($user)->get(route('reports.stock', [
            'search' => 'PDF-001',
            'export' => 'pdf',
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    private function makeProductWithVariant(string $productName, string $sku, string $unitValue): array
    {
        $category = Category::create(['name' => 'Reports', 'code' => 'RPT']);
        $unit = Unit::create(['name' => 'Pieces', 'short_name' => 'pcs']);
        $product = Product::create([
            'name' => $productName,
            'category_id' => $category->id,
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'unit_value' => $unitValue,
            'sku' => $sku,
            'selling_price' => 250,
            'limit_price' => 200,
            'quantity' => 0,
            'alert_quantity' => 0,
        ]);

        return [$product, $variant];
    }

    private function makePurchaseItem(Product $product, ProductVariant $variant, int $quantity, float $price): array
    {
        $supplier = Supplier::create([
            'business_name' => 'Report Supplier',
            'name' => 'Report Supplier',
            'mobile' => '0770000000',
        ]);
        $purchase = Purchase::create([
            'purchase_number' => 'PUR-RPT-001',
            'supplier_id' => $supplier->id,
            'purchase_date' => '2026-05-20',
            'status' => 'complete',
            'currency' => 'LKR',
            'sub_total' => $quantity * $price,
            'net_total' => $quantity * $price,
        ]);
        $purchaseItem = PurchaseItem::create([
            'purchase_id' => $purchase->id,
            'product_id' => $product->id,
            'stock_variant_id' => $variant->id,
            'product_name' => $product->name,
            'quantity' => $quantity,
            'purchase_price' => $price,
            'total' => $quantity * $price,
        ]);

        return [$purchase, $purchaseItem];
    }

    private function makeInventoryUnit(ProductVariant $variant, Purchase $purchase, PurchaseItem $purchaseItem, string $status, string $code): InventoryUnit
    {
        return InventoryUnit::create([
            'product_variant_id' => $variant->id,
            'purchase_id' => $purchase->id,
            'purchase_item_id' => $purchaseItem->id,
            'unit_code' => $code,
            'status' => $status,
            'sku_snapshot' => $variant->sku,
            'product_name_snapshot' => $variant->product->name,
            'variant_label_snapshot' => $variant->unit_value,
            'available_at' => $status === InventoryUnit::STATUS_AVAILABLE ? '2026-05-20 08:00:00' : null,
            'last_event_at' => '2026-05-20 08:00:00',
        ]);
    }

    private function makeOrder(
        string $orderNumber,
        User $user,
        string $deliveryStatus,
        ?User $pickedBy = null,
        ?User $packedBy = null,
        ?string $pickedAt = null,
        ?string $packedAt = null
    ): Order {
        $status = $deliveryStatus === 'cancel' ? 'cancel' : 'confirm';

        return Order::forceCreate([
            'order_number' => $orderNumber,
            'order_date' => '2026-05-20',
            'user_id' => $user->id,
            'status' => $status,
            'call_status' => $status === 'cancel' ? 'cancel' : 'confirm',
            'delivery_status' => $deliveryStatus,
            'payment_method' => 'COD',
            'payment_status' => 'pending',
            'total_amount' => 1000,
            'picked_by' => $pickedBy?->id,
            'picked_at' => $pickedAt,
            'packed_by' => $packedBy?->id,
            'packed_at' => $packedAt,
        ]);
    }

    private function makeOrderWithItem(string $orderNumber, User $user, ProductVariant $variant, int $quantity, string $deliveryStatus): Order
    {
        $order = $this->makeOrder($orderNumber, $user, $deliveryStatus);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,
            'product_name' => $variant->product->name,
            'sku' => $variant->sku,
            'quantity' => $quantity,
            'unit_price' => 250,
            'base_price' => 200,
            'cost_price' => 125,
            'total_price' => $quantity * 250,
            'subtotal' => $quantity * 250,
        ]);

        return $order;
    }
}
