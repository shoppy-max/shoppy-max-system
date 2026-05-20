<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Purchase;
use App\Models\StoreRack;
use Carbon\Carbon;
use Database\Seeders\DemoSystemSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationalTimestampTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_app_uses_business_timezone_by_default(): void
    {
        $this->assertSame('Asia/Colombo', config('app.timezone'));
    }

    public function test_demo_purchase_audit_times_match_purchase_dates(): void
    {
        Carbon::setTestNow('2026-05-18 02:00:00');
        $this->seed([RolesAndPermissionsSeeder::class, DemoSystemSeeder::class]);

        $purchases = Purchase::where('purchase_number', 'like', 'PUR-DEMO-%')->get();

        $this->assertNotEmpty($purchases);

        foreach ($purchases as $purchase) {
            $purchaseDate = $purchase->purchase_date->format('Y-m-d');

            $this->assertSame($purchaseDate, $purchase->created_at->format('Y-m-d'));
            $this->assertSame('Asia/Colombo', $purchase->created_at->timezoneName);
            $this->assertLessThanOrEqual(now(), $purchase->created_at);

            if ($purchase->checked_at) {
                $this->assertGreaterThanOrEqual($purchase->created_at, $purchase->checked_at);
                $this->assertLessThanOrEqual(now(), $purchase->checked_at);
            }

            if ($purchase->verified_at) {
                $this->assertGreaterThanOrEqual($purchase->checked_at ?? $purchase->created_at, $purchase->verified_at);
                $this->assertLessThanOrEqual(now(), $purchase->verified_at);
            }

            if ($purchase->completed_at) {
                $this->assertGreaterThanOrEqual($purchase->verified_at ?? $purchase->created_at, $purchase->completed_at);
                $this->assertLessThanOrEqual(now(), $purchase->completed_at);
            }
        }
    }

    public function test_demo_order_timeline_times_match_order_dates(): void
    {
        Carbon::setTestNow('2026-05-18 02:00:00');
        $this->seed([RolesAndPermissionsSeeder::class, DemoSystemSeeder::class]);

        $orders = Order::where('order_number', 'like', 'DEMO-ORD-%')->get();

        $this->assertNotEmpty($orders);

        foreach ($orders as $order) {
            $orderDate = $order->order_date->format('Y-m-d');

            $this->assertSame($orderDate, $order->created_at->format('Y-m-d'));
            $this->assertSame('Asia/Colombo', $order->created_at->timezoneName);
            $this->assertLessThanOrEqual(now(), $order->created_at);

            foreach ([
                'waybill_printed_at',
                'waybill_excel_exported_at',
                'picked_at',
                'packed_at',
                'dispatched_at',
                'cancelled_at',
                'delivered_at',
                'returned_at',
            ] as $column) {
                if (! $order->{$column}) {
                    continue;
                }

                $this->assertSame($orderDate, $order->{$column}->format('Y-m-d'), "{$column} should stay on order date.");
                $this->assertGreaterThanOrEqual($order->created_at, $order->{$column});
                $this->assertLessThanOrEqual(now(), $order->{$column});
            }
        }
    }

    public function test_demo_seed_includes_complete_packing_queues_with_located_units(): void
    {
        Carbon::setTestNow('2026-05-18 02:00:00');
        $this->seed([RolesAndPermissionsSeeder::class, DemoSystemSeeder::class]);

        $this->assertDatabaseHas('store_racks', [
            'store_type' => StoreRack::STORE_RETAIL,
            'rack_name' => 'Retail A',
            'row_name' => 'Row 1',
        ]);
        $this->assertDatabaseHas('store_racks', [
            'store_type' => StoreRack::STORE_WAREHOUSE,
            'rack_name' => 'Warehouse A',
            'row_name' => 'Row 1',
        ]);

        $this->assertGreaterThanOrEqual(7, Order::query()
            ->where('order_number', 'like', 'DEMO-ORD-%')
            ->where('call_status', 'confirm')
            ->where('delivery_status', 'waybill_printed')
            ->count(), 'Ready To Pick should have several seeded demo orders.');

        foreach (['waybill_printed', 'picked_from_rack', 'packed', 'dispatched'] as $deliveryStatus) {
            $order = Order::query()
                ->where('order_number', 'like', 'DEMO-ORD-%')
                ->where('call_status', 'confirm')
                ->where('delivery_status', $deliveryStatus)
                ->whereNotNull('waybill_number')
                ->with(['inventoryUnits.purchase', 'inventoryUnits.storeRack'])
                ->first();

            $this->assertNotNull($order, "Missing seeded {$deliveryStatus} packing order.");
            $this->assertTrue($order->inventoryUnits->isNotEmpty(), "Missing allocated units for {$order?->order_number}.");

            foreach ($order->inventoryUnits as $unit) {
                $this->assertNotNull($unit->purchase_id, "Missing GRN source for {$unit->unit_code}.");
                $this->assertNotNull($unit->store_rack_id, "Missing rack for {$unit->unit_code}.");
                $this->assertNotNull($unit->storeRack, "Missing rack relation for {$unit->unit_code}.");
            }
        }

        $packedOrder = Order::query()
            ->where('order_number', 'like', 'DEMO-ORD-%')
            ->where('delivery_status', 'packed')
            ->with('inventoryUnits')
            ->firstOrFail();

        $this->assertTrue($packedOrder->inventoryUnits->every(fn ($unit) => filled($unit->packed_scan_at)));

        $this->assertNotNull(Order::query()
            ->where('order_number', 'like', 'DEMO-ORD-%')
            ->where('delivery_status', 'picked_from_rack')
            ->whereNotNull('pick_grn_number')
            ->first());
    }

    public function test_demo_seed_restores_soft_deleted_demo_orders_instead_of_duplicating(): void
    {
        Carbon::setTestNow('2026-05-18 02:00:00');
        $this->seed([RolesAndPermissionsSeeder::class, DemoSystemSeeder::class]);

        $order = Order::where('order_number', 'DEMO-ORD-0014')->firstOrFail();
        $order->delete();

        $this->seed(DemoSystemSeeder::class);

        $this->assertSame(1, Order::withTrashed()->where('order_number', 'DEMO-ORD-0014')->count());
        $this->assertDatabaseHas('orders', [
            'order_number' => 'DEMO-ORD-0014',
            'deleted_at' => null,
        ]);
    }
}
