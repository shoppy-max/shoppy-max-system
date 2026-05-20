<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Courier;
use App\Models\InventoryUnit;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderLog;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\StoreRack;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Services\InventoryUnitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
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
        $this->assertSame(3, substr_count($html, 'Barcode for '.$variant->sku));
        $this->assertSame(3, substr_count($html, '<div class="unit-code">'.$variant->sku.'</div>'));
        $this->assertStringNotContainsString('Barcode for '.$units->first()->unit_code, $html);
        $this->assertSame($variant->sku, $purchase->items()->with('inventoryUnits')->firstOrFail()->trackedUnitRangeLabel());
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
                'unit_code' => 'UNIT-PACK-SKU-'.$index,
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
        $this->assertSame($variant->sku, $orderItem->fresh('inventoryUnits')->trackedUnitRangeLabel());
    }

    public function test_order_allocation_prioritizes_retail_store_units_before_warehouse_units(): void
    {
        [, $variant] = $this->makePurchaseWithPendingUnits(0);
        $variant->update(['quantity' => 3]);
        $warehouseRack = StoreRack::create([
            'store_type' => StoreRack::STORE_WAREHOUSE,
            'rack_name' => 'Warehouse Rack',
            'rack_key' => StoreRack::normalizeRackKey('Warehouse Rack'),
            'row_name' => 'Row A',
            'row_key' => StoreRack::normalizeRowKey('Row A'),
        ]);
        $retailRack = StoreRack::create([
            'store_type' => StoreRack::STORE_RETAIL,
            'rack_name' => 'Retail Rack',
            'rack_key' => StoreRack::normalizeRackKey('Retail Rack'),
            'row_name' => 'Row A',
            'row_key' => StoreRack::normalizeRowKey('Row A'),
        ]);

        foreach (['WH-ALLOC-1', 'WH-ALLOC-2'] as $code) {
            InventoryUnit::create([
                'product_variant_id' => $variant->id,
                'unit_code' => $code,
                'status' => InventoryUnit::STATUS_AVAILABLE,
                'store_type' => StoreRack::STORE_WAREHOUSE,
                'store_rack_id' => $warehouseRack->id,
                'sku_snapshot' => $variant->sku,
                'available_at' => now(),
                'last_event_at' => now(),
            ]);
        }

        InventoryUnit::create([
            'product_variant_id' => $variant->id,
            'unit_code' => 'RET-ALLOC-1',
            'status' => InventoryUnit::STATUS_AVAILABLE,
            'store_type' => StoreRack::STORE_RETAIL,
            'store_rack_id' => $retailRack->id,
            'sku_snapshot' => $variant->sku,
            'available_at' => now(),
            'last_event_at' => now(),
        ]);

        $order = $this->makePackingOrder('ORD-20260518-9002');
        OrderItem::create([
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

        app(InventoryUnitService::class)->ensureOrderUnitsAllocated($order);

        $allocatedCodes = InventoryUnit::query()
            ->where('order_id', $order->id)
            ->orderBy('id')
            ->pluck('unit_code')
            ->all();

        $this->assertSame(['WH-ALLOC-1', 'RET-ALLOC-1'], $allocatedCodes);
        $this->assertNull(InventoryUnit::where('unit_code', 'WH-ALLOC-2')->value('order_id'));
    }

    public function test_packing_screen_shows_pick_locations_and_grn_sources_for_allocated_units(): void
    {
        $user = User::factory()->create();
        [$purchase, $variant] = $this->makePurchaseWithPendingUnits(0);
        $retailRack = StoreRack::create([
            'store_type' => StoreRack::STORE_RETAIL,
            'rack_name' => 'Retail Rack',
            'rack_key' => StoreRack::normalizeRackKey('Retail Rack'),
            'row_name' => 'Row 1',
            'row_key' => StoreRack::normalizeRowKey('Row 1'),
        ]);
        $order = $this->makePackingOrder('ORD-20260518-9003');
        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,
            'product_name' => 'SKU Barcode Product',
            'sku' => $variant->sku,
            'quantity' => 1,
            'unit_price' => 100,
            'base_price' => 100,
            'cost_price' => 50,
            'total_price' => 100,
        ]);
        InventoryUnit::create([
            'product_variant_id' => $variant->id,
            'purchase_id' => $purchase->id,
            'order_id' => $order->id,
            'order_item_id' => $orderItem->id,
            'unit_code' => 'RET-PICK-1',
            'status' => InventoryUnit::STATUS_ALLOCATED,
            'store_type' => StoreRack::STORE_RETAIL,
            'store_rack_id' => $retailRack->id,
            'sku_snapshot' => $variant->sku,
            'product_name_snapshot' => 'SKU Barcode Product',
            'variant_label_snapshot' => 'Piece',
            'allocated_at' => now(),
            'last_event_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('orders.packing.process', $order));

        $response->assertOk()
            ->assertSee('Retail Store')
            ->assertSee('Retail Rack / Row 1')
            ->assertSee($purchase->purchase_number)
            ->assertSee($variant->sku)
            ->assertDontSee('RET-PICK-1')
            ->assertSee('onScanInput', false);

        $this->actingAs($user)
            ->get(route('orders.packing.picking'))
            ->assertOk()
            ->assertSee('Retail Rack / Row 1')
            ->assertSee($purchase->purchase_number);
    }

    public function test_picking_and_packed_packing_queues_can_search_pick_location_or_grn(): void
    {
        $user = User::factory()->create();
        [$purchase, $variant] = $this->makePurchaseWithPendingUnits(0);
        $retailRack = StoreRack::create([
            'store_type' => StoreRack::STORE_RETAIL,
            'rack_name' => 'Search Rack',
            'rack_key' => StoreRack::normalizeRackKey('Search Rack'),
            'row_name' => 'Row 7',
            'row_key' => StoreRack::normalizeRowKey('Row 7'),
        ]);
        $matchingOrder = $this->makePackingOrder('ORD-20260518-9004');
        $matchingItem = OrderItem::create([
            'order_id' => $matchingOrder->id,
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,
            'product_name' => 'SKU Barcode Product',
            'sku' => $variant->sku,
            'quantity' => 1,
            'unit_price' => 100,
            'base_price' => 100,
            'cost_price' => 50,
            'total_price' => 100,
        ]);
        InventoryUnit::create([
            'product_variant_id' => $variant->id,
            'purchase_id' => $purchase->id,
            'order_id' => $matchingOrder->id,
            'order_item_id' => $matchingItem->id,
            'unit_code' => 'SEARCH-PICK-1',
            'status' => InventoryUnit::STATUS_ALLOCATED,
            'store_type' => StoreRack::STORE_RETAIL,
            'store_rack_id' => $retailRack->id,
            'sku_snapshot' => $variant->sku,
            'allocated_at' => now(),
            'last_event_at' => now(),
        ]);
        $otherOrder = $this->makePackingOrder('ORD-20260518-9005');
        $otherOrder->update(['delivery_status' => 'packed']);

        $this->actingAs($user)
            ->get(route('orders.packing.picking', ['search' => 'Search Rack']))
            ->assertOk()
            ->assertSee('ORD-20260518-9004')
            ->assertDontSee('ORD-20260518-9005');

        $this->actingAs($user)
            ->get(route('orders.packing.packed', ['search' => 'WB-9005']))
            ->assertOk()
            ->assertSee('ORD-20260518-9005')
            ->assertDontSee('ORD-20260518-9004');
    }

    public function test_packing_workflow_has_separate_ready_picking_packed_and_dispatched_pages(): void
    {
        $user = User::factory()->create();
        $readyOrder = $this->makePackingOrder('ORD-20260518-9006', 'waybill_printed');
        $pickingOrder = $this->makePackingOrder('ORD-20260518-9007', 'picked_from_rack');
        $packedOrder = $this->makePackingOrder('ORD-20260518-9008', 'packed');
        $dispatchedOrder = $this->makePackingOrder('ORD-20260518-9011', 'dispatched');

        $this->actingAs($user)
            ->get('/orders/packing/ready')
            ->assertOk()
            ->assertSee('Ready To Pick')
            ->assertSee($readyOrder->order_number)
            ->assertDontSee($pickingOrder->order_number)
            ->assertDontSee($packedOrder->order_number)
            ->assertDontSee($dispatchedOrder->order_number);

        $this->actingAs($user)
            ->get('/orders/packing/picking')
            ->assertOk()
            ->assertSee('Picking')
            ->assertSee($pickingOrder->order_number)
            ->assertDontSee($readyOrder->order_number)
            ->assertDontSee($packedOrder->order_number)
            ->assertDontSee($dispatchedOrder->order_number);

        $this->actingAs($user)
            ->get('/orders/packing/packed')
            ->assertOk()
            ->assertSee('Packed')
            ->assertSee($packedOrder->order_number)
            ->assertDontSee($readyOrder->order_number)
            ->assertDontSee($pickingOrder->order_number)
            ->assertDontSee($dispatchedOrder->order_number);

        $this->actingAs($user)
            ->get('/orders/packing/dispatched')
            ->assertOk()
            ->assertSee('Dispatched')
            ->assertSee($dispatchedOrder->order_number)
            ->assertDontSee($readyOrder->order_number)
            ->assertDontSee($pickingOrder->order_number)
            ->assertDontSee($packedOrder->order_number);
    }

    public function test_final_packing_scan_automatically_marks_order_packed(): void
    {
        $user = User::factory()->create();
        [, $variant] = $this->makePurchaseWithPendingUnits(0);
        $order = $this->makePackingOrder('ORD-20260518-9009', 'picked_from_rack');
        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,
            'product_name' => 'SKU Barcode Product',
            'sku' => $variant->sku,
            'quantity' => 1,
            'unit_price' => 100,
            'base_price' => 100,
            'cost_price' => 50,
            'total_price' => 100,
        ]);
        InventoryUnit::create([
            'product_variant_id' => $variant->id,
            'order_id' => $order->id,
            'order_item_id' => $orderItem->id,
            'unit_code' => 'AUTO-PACK-1',
            'status' => InventoryUnit::STATUS_ALLOCATED,
            'sku_snapshot' => $variant->sku,
            'allocated_at' => now(),
            'last_event_at' => now(),
        ]);

        $response = $this->actingAs($user)->postJson(route('orders.packing.scan', $order), [
            'unit_code' => $variant->sku,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('delivery_status', 'packed')
            ->assertJsonPath('auto_packed', true)
            ->assertJsonPath('summary.all_scanned', true)
            ->assertJsonPath('unit_code', $variant->sku)
            ->assertJsonPath('barcode_value', $variant->sku);

        $order->refresh();
        $this->assertSame('packed', $order->delivery_status);
        $this->assertNotNull($order->packed_at);
        $this->assertSame($user->id, (int) $order->packed_by);
    }

    public function test_ready_to_pick_creates_pick_grn_before_scanning_starts(): void
    {
        $user = User::factory()->create();
        $this->grantPermissions($user, [
            'view ready to pick orders',
            'create pick grns',
            'view picking orders',
            'scan packing',
            'view packed orders',
            'view pick grns',
        ]);
        [$purchase, $variant] = $this->makePurchaseWithPendingUnits(0);
        $retailRack = StoreRack::create([
            'store_type' => StoreRack::STORE_RETAIL,
            'rack_name' => 'Ready Rack',
            'rack_key' => StoreRack::normalizeRackKey('Ready Rack'),
            'row_name' => 'Row 1',
            'row_key' => StoreRack::normalizeRowKey('Row 1'),
        ]);
        $order = $this->makePackingOrder('ORD-20260518-9010', 'waybill_printed');
        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,
            'product_name' => 'SKU Barcode Product',
            'sku' => $variant->sku,
            'quantity' => 1,
            'unit_price' => 100,
            'base_price' => 100,
            'cost_price' => 50,
            'total_price' => 100,
        ]);
        InventoryUnit::create([
            'product_variant_id' => $variant->id,
            'purchase_id' => $purchase->id,
            'order_id' => $order->id,
            'order_item_id' => $orderItem->id,
            'unit_code' => 'READY-PICK-1',
            'status' => InventoryUnit::STATUS_ALLOCATED,
            'store_type' => StoreRack::STORE_RETAIL,
            'store_rack_id' => $retailRack->id,
            'sku_snapshot' => $variant->sku,
            'allocated_at' => now(),
            'last_event_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('orders.packing.ready'))
            ->assertOk()
            ->assertSee('Create Pick GRN')
            ->assertDontSee('Start Picking');

        $this->actingAs($user)
            ->get(route('orders.packing.process', $order))
            ->assertRedirect(route('orders.packing.ready'));

        $this->actingAs($user)
            ->postJson(route('orders.packing.scan', $order), ['unit_code' => 'ANY-SCAN'])
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $creationResponse = $this->actingAs($user)
            ->post(route('orders.packing.mark-picked', $order))
            ->assertRedirect(route('orders.packing.ready'))
            ->assertSessionHas('pick_grn_modal.number')
            ->assertSessionHas('pick_grn_modal.items');

        $modalPayload = $creationResponse->baseResponse->getSession()->get('pick_grn_modal');

        $this->assertSame('SKU Barcode Product', $modalPayload['items'][0]['product_name']);
        $this->assertSame($variant->sku, $modalPayload['items'][0]['sku']);
        $this->assertSame($variant->sku, $modalPayload['items'][0]['units'][0]['barcode_value']);
        $this->assertArrayNotHasKey('unit_code', $modalPayload['items'][0]['units'][0]);
        $this->assertSame('Retail Store', $modalPayload['items'][0]['units'][0]['store_label']);
        $this->assertSame('Ready Rack / Row 1', $modalPayload['items'][0]['units'][0]['rack_label']);
        $this->assertSame($purchase->purchase_number, $modalPayload['items'][0]['units'][0]['purchase_number']);

        $order->refresh();
        $this->assertSame('picked_from_rack', $order->delivery_status);
        $this->assertMatchesRegularExpression('/^PGRN-\d{8}-\d{4}$/', (string) $order->pick_grn_number);
        $this->assertNotNull($order->pick_grn_created_at);
        $this->assertSame($user->id, (int) $order->pick_grn_created_by);
        $this->assertNotNull($order->picked_at);
        $this->assertSame($user->id, (int) $order->picked_by);

        $this->actingAs($user)
            ->withSession([
                'pick_grn_modal' => $modalPayload,
            ])
            ->get(route('orders.packing.ready'))
            ->assertOk()
            ->assertSee('Pick GRN Created')
            ->assertSee($order->pick_grn_number)
            ->assertSee('SKU Barcode Product')
            ->assertSee($variant->sku)
            ->assertDontSee('READY-PICK-1')
            ->assertSee('Retail Store')
            ->assertSee('Ready Rack / Row 1')
            ->assertSee($purchase->purchase_number)
            ->assertSee('Print / Save PDF')
            ->assertSee('target="_blank"', false);

        $this->actingAs($user)
            ->get(route('orders.packing.picking'))
            ->assertOk()
            ->assertSee('activePickGrn', false)
            ->assertSee('pickGrnPayloads', false)
            ->assertSee('activePickGrn = pickGrnPayloads', false)
            ->assertDontSee("@click='activePickGrn =", false)
            ->assertSee('Pick GRN Details')
            ->assertSee('Print / Save PDF')
            ->assertSee('target="_blank"', false)
            ->assertSee($variant->sku)
            ->assertDontSee('READY-PICK-1')
            ->assertSee('Ready Rack / Row 1')
            ->assertSee($purchase->purchase_number);

        $order->update(['delivery_status' => 'packed']);

        $this->actingAs($user)
            ->get(route('orders.packing.packed'))
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertDontSee('Pick GRN</button>', false)
            ->assertDontSee('Pick GRN Details');

        $order->update(['delivery_status' => 'picked_from_rack']);

        $this->actingAs($user)
            ->get(route('orders.packing.process', $order))
            ->assertOk()
            ->assertSee('Scan Now')
            ->assertSee('@click="scanItem()"', false);

        $this->actingAs($user)
            ->get(route('orders.packing.pick-grn', $order))
            ->assertOk()
            ->assertSee($order->pick_grn_number)
            ->assertSee('Print / Save PDF')
            ->assertSee($variant->sku)
            ->assertSee('Ready Rack / Row 1')
            ->assertSee($purchase->purchase_number)
            ->assertDontSee('READY-PICK-1');
    }

    public function test_packed_queue_can_mark_order_dispatched(): void
    {
        $user = User::factory()->create();
        $this->grantPermissions($user, ['dispatch orders']);
        $order = $this->makePackingOrder('ORD-20260518-9012', 'packed');

        $this->actingAs($user)
            ->post(route('orders.packing.mark-dispatched', $order))
            ->assertRedirect(route('orders.packing.dispatched'))
            ->assertSessionHas('success');

        $order->refresh();
        $this->assertSame('dispatched', $order->delivery_status);
        $this->assertNotNull($order->dispatched_at);
        $this->assertSame($user->id, (int) $order->dispatched_by);
        $this->assertDatabaseHas('order_logs', [
            'order_id' => $order->id,
            'user_id' => $user->id,
            'action' => 'marked_dispatched',
        ]);
    }

    public function test_dispatched_queue_blocks_outstanding_cod_delivery_to_preserve_courier_receive(): void
    {
        $user = User::factory()->create();
        $this->grantPermissions($user, ['view dispatched orders', 'deliver orders', 'view courier receive']);
        $courier = Courier::create(['name' => 'Delivery Courier', 'is_active' => true]);
        [$order, $unit] = $this->makeDispatchedOrderWithAllocatedUnit(
            'ORD-20260518-9013',
            paymentMethod: 'COD',
            totalAmount: 200,
            paidAmount: 0,
            courierId: $courier->id
        );

        $this->actingAs($user)
            ->get(route('orders.packing.dispatched'))
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee('Receive Payment')
            ->assertDontSee('Mark Delivered');

        $this->actingAs($user)
            ->post(route('orders.packing.mark-delivered', $order))
            ->assertRedirect(route('orders.packing.dispatched'))
            ->assertSessionHas('error');

        $order->refresh();
        $unit->refresh();

        $this->assertSame('dispatched', $order->delivery_status);
        $this->assertNull($order->delivered_at);
        $this->assertNull($order->delivered_by);
        $this->assertSame(InventoryUnit::STATUS_ALLOCATED, $unit->status);
    }

    public function test_dispatched_queue_can_mark_paid_non_cod_order_delivered(): void
    {
        $user = User::factory()->create();
        $this->grantPermissions($user, ['deliver orders']);
        [$order, $unit] = $this->makeDispatchedOrderWithAllocatedUnit(
            'ORD-20260518-9014',
            paymentMethod: 'Online Payment',
            totalAmount: 200,
            paidAmount: 200
        );

        $this->actingAs($user)
            ->post(route('orders.packing.mark-delivered', $order))
            ->assertRedirect(route('orders.packing.dispatched'))
            ->assertSessionHas('success');

        $order->refresh();
        $unit->refresh();

        $this->assertSame('delivered', $order->delivery_status);
        $this->assertSame('confirm', $order->status);
        $this->assertSame('paid', $order->payment_status);
        $this->assertNotNull($order->delivered_at);
        $this->assertSame($user->id, (int) $order->delivered_by);
        $this->assertSame(InventoryUnit::STATUS_DELIVERED, $unit->status);
        $this->assertNotNull($unit->delivered_at);
        $this->assertSame(1, OrderLog::where('order_id', $order->id)->where('action', 'marked_delivered')->count());
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
            'purchase_number' => 'PUR-SKU-BARCODE-'.$quantity,
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

    private function makePackingOrder(string $orderNumber, string $deliveryStatus = 'picked_from_rack'): Order
    {
        return Order::forceCreate([
            'order_number' => $orderNumber,
            'order_date' => now()->toDateString(),
            'status' => 'confirm',
            'call_status' => 'confirm',
            'delivery_status' => $deliveryStatus,
            'waybill_number' => 'WB-'.substr($orderNumber, -4),
            'waybill_printed_at' => now(),
            'payment_method' => 'COD',
            'payment_status' => 'pending',
            'total_amount' => 200,
        ]);
    }

    private function makeDispatchedOrderWithAllocatedUnit(
        string $orderNumber,
        string $paymentMethod,
        float $totalAmount,
        float $paidAmount,
        ?int $courierId = null
    ): array {
        [, $variant] = $this->makePurchaseWithPendingUnits(0);
        $order = Order::forceCreate([
            'order_number' => $orderNumber,
            'order_date' => now()->toDateString(),
            'status' => 'confirm',
            'call_status' => 'confirm',
            'delivery_status' => 'dispatched',
            'waybill_number' => 'WB-'.substr($orderNumber, -4),
            'waybill_printed_at' => now()->subHours(3),
            'picked_at' => now()->subHours(2),
            'packed_at' => now()->subHour(),
            'dispatched_at' => now()->subMinutes(30),
            'courier_id' => $courierId,
            'payment_method' => $paymentMethod,
            'payment_status' => $paidAmount >= $totalAmount ? 'paid' : 'pending',
            'total_amount' => $totalAmount,
            'paid_amount' => $paidAmount,
        ]);
        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,
            'product_name' => 'SKU Barcode Product',
            'sku' => $variant->sku,
            'quantity' => 1,
            'unit_price' => $totalAmount,
            'base_price' => $totalAmount,
            'cost_price' => 50,
            'total_price' => $totalAmount,
        ]);
        $unit = InventoryUnit::create([
            'product_variant_id' => $variant->id,
            'order_id' => $order->id,
            'order_item_id' => $orderItem->id,
            'unit_code' => 'DELIVER-'.$orderNumber,
            'status' => InventoryUnit::STATUS_ALLOCATED,
            'sku_snapshot' => $variant->sku,
            'allocated_at' => now()->subHours(2),
            'packed_scan_at' => now()->subHour(),
            'last_event_at' => now()->subHour(),
        ]);

        return [$order, $unit];
    }

    private function grantPermissions(User $user, array $permissions): void
    {
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $user->givePermissionTo($permissions);
    }
}
