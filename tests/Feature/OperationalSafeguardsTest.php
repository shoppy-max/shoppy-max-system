<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\City;
use App\Models\Courier;
use App\Models\CourierWaybill;
use App\Models\InventoryUnit;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Reseller;
use App\Models\ResellerPayment;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DemoSystemSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;
use Tests\TestCase;

class OperationalSafeguardsTest extends TestCase
{
    use RefreshDatabase;

    public function test_cancelled_orders_cannot_be_reopened_through_status_endpoint(): void
    {
        $user = User::factory()->create();
        $order = Order::forceCreate([
            'order_number' => 'ORD-20260518-0001',
            'order_date' => now()->toDateString(),
            'status' => 'cancel',
            'call_status' => 'cancel',
            'delivery_status' => 'cancel',
            'payment_method' => 'COD',
            'payment_status' => 'pending',
            'total_amount' => 1000,
        ]);

        $response = $this->actingAs($user)->postJson(route('orders.status.update', $order), [
            'call_status' => 'pending',
        ]);

        $response->assertStatus(422);
        $order->refresh();

        $this->assertSame('cancel', $order->status);
        $this->assertSame('cancel', $order->call_status);
        $this->assertSame('cancel', $order->delivery_status);
    }

    public function test_hold_call_status_can_move_back_to_pending_before_waybill_printing(): void
    {
        $user = User::factory()->create();
        $order = Order::forceCreate([
            'order_number' => 'ORD-20260518-0003',
            'order_date' => now()->toDateString(),
            'status' => 'hold',
            'call_status' => 'hold',
            'delivery_status' => 'pending',
            'payment_method' => 'COD',
            'payment_status' => 'pending',
            'total_amount' => 1000,
        ]);

        $response = $this->actingAs($user)->postJson(route('orders.status.update', $order), [
            'call_status' => 'pending',
        ]);

        $response->assertOk()->assertJson([
            'success' => true,
            'call_status' => 'pending',
            'status' => 'pending',
            'delivery_status' => 'pending',
        ]);

        $order->refresh();
        $this->assertSame('pending', $order->status);
        $this->assertSame('pending', $order->call_status);
        $this->assertSame('pending', $order->delivery_status);
    }

    public function test_call_list_shows_edit_action_for_pending_and_hold_orders(): void
    {
        $user = User::factory()->create();
        $pendingOrder = Order::forceCreate([
            'order_number' => 'ORD-20260518-0004',
            'order_date' => now()->toDateString(),
            'status' => 'pending',
            'call_status' => 'pending',
            'delivery_status' => 'pending',
            'payment_method' => 'COD',
            'payment_status' => 'pending',
            'total_amount' => 1000,
        ]);
        $holdOrder = Order::forceCreate([
            'order_number' => 'ORD-20260518-0005',
            'order_date' => now()->toDateString(),
            'status' => 'hold',
            'call_status' => 'hold',
            'delivery_status' => 'pending',
            'payment_method' => 'COD',
            'payment_status' => 'pending',
            'total_amount' => 1000,
        ]);

        $response = $this->actingAs($user)->get(route('orders.call-list'));

        $response->assertOk();
        $response->assertSee(route('orders.edit', $pendingOrder), false);
        $response->assertSee(route('orders.edit', $holdOrder), false);
        $response->assertSee('Edit', false);
    }

    public function test_orders_index_shows_reseller_name_or_direct_source(): void
    {
        $user = User::factory()->create();
        $reseller = $this->makeReseller('Index Source Reseller', '0718000001');

        Order::forceCreate([
            'order_number' => 'ORD-20260518-0007',
            'order_date' => now()->toDateString(),
            'order_type' => 'reseller',
            'reseller_id' => $reseller->id,
            'status' => 'pending',
            'call_status' => 'pending',
            'delivery_status' => 'pending',
            'payment_method' => 'COD',
            'payment_status' => 'pending',
            'total_amount' => 1000,
        ]);
        Order::forceCreate([
            'order_number' => 'ORD-20260518-0008',
            'order_date' => now()->toDateString(),
            'order_type' => 'direct',
            'status' => 'pending',
            'call_status' => 'pending',
            'delivery_status' => 'pending',
            'payment_method' => 'COD',
            'payment_status' => 'pending',
            'total_amount' => 1000,
        ]);

        $response = $this->actingAs($user)->get(route('orders.index', ['view' => 'active']));

        $response->assertOk();
        $response->assertSee('Reseller');
        $response->assertSee('Index Source Reseller');
        $response->assertSee('0718000001');
        $response->assertSee('Direct');
    }

    public function test_hold_order_can_use_full_update_before_waybill_printing(): void
    {
        $user = User::factory()->create();
        $city = City::create(['city_name' => 'Negombo', 'district' => 'Gampaha', 'province' => 'Western', 'postal_code' => '11500']);
        $courier = $this->makeCourier('Hold Edit Courier');
        [, $variant] = $this->makeProductWithVariant('SKU-HOLD-EDIT');
        $variant->update(['quantity' => 1]);
        $this->makeAvailableInventoryUnits($variant, 1, 'HOLD-EDIT');
        $order = Order::forceCreate([
            'order_number' => 'ORD-20260518-0006',
            'order_date' => now()->toDateString(),
            'order_type' => 'direct',
            'status' => 'hold',
            'call_status' => 'hold',
            'delivery_status' => 'pending',
            'payment_method' => 'COD',
            'payment_status' => 'pending',
            'total_amount' => 1000,
        ]);

        $response = $this->actingAs($user)->putJson(route('orders.update', $order), [
            'order_type' => 'direct',
            'customer' => [
                'name' => 'Updated Hold Customer',
                'mobile' => '0778889990',
                'address' => 'Hold edit address',
                'city_id' => $city->id,
            ],
            'items' => [
                [
                    'id' => $variant->id,
                    'quantity' => 1,
                    'selling_price' => 150,
                ],
            ],
            'courier_id' => $courier->id,
            'courier_charge' => 0,
            'payment_method' => 'COD',
            'discount_type' => 'fixed',
            'discount_value' => 0,
            'delivery_status' => 'pending',
        ]);

        $response->assertOk()->assertJson(['success' => true]);
        $order->refresh();
        $this->assertSame('Updated Hold Customer', $order->customer_name);
        $this->assertSame($courier->id, $order->courier_id);
        $this->assertSame('confirm', $order->call_status);
    }

    public function test_product_with_live_stock_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        [$product, $variant] = $this->makeProductWithVariant();
        $variant->update(['quantity' => 2]);

        $response = $this->actingAs($user)->delete(route('products.destroy', $product));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertFalse($product->fresh()->trashed());
    }

    public function test_variant_with_order_history_cannot_be_removed_from_product_edit(): void
    {
        $user = User::factory()->create();
        [$product, $variantToKeep] = $this->makeProductWithVariant('SKU-KEEP');
        $variantWithHistory = ProductVariant::create([
            'product_id' => $product->id,
            'unit_id' => $variantToKeep->unit_id,
            'sku' => 'SKU-HISTORY',
            'selling_price' => 150,
            'limit_price' => 100,
            'quantity' => 0,
            'alert_quantity' => 0,
        ]);
        $order = Order::forceCreate([
            'order_number' => 'ORD-20260518-0002',
            'order_date' => now()->toDateString(),
            'status' => 'pending',
            'call_status' => 'pending',
            'delivery_status' => 'pending',
            'payment_method' => 'COD',
            'payment_status' => 'pending',
            'total_amount' => 150,
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => $variantWithHistory->id,
            'product_name' => $product->name,
            'sku' => $variantWithHistory->sku,
            'quantity' => 1,
            'unit_price' => 150,
            'base_price' => 100,
            'total_price' => 150,
            'subtotal' => 150,
        ]);

        $response = $this->actingAs($user)->put(route('products.update', $product), [
            'name' => $product->name,
            'category_id' => $product->category_id,
            'sub_category_id' => null,
            'description' => null,
            'variants' => [
                [
                    'id' => $variantToKeep->id,
                    'unit_id' => $variantToKeep->unit_id,
                    'unit_value' => $variantToKeep->unit_value,
                    'sku' => $variantToKeep->sku,
                    'selling_price' => $variantToKeep->selling_price,
                    'limit_price' => $variantToKeep->limit_price,
                    'alert_quantity' => $variantToKeep->alert_quantity,
                ],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertNotNull(ProductVariant::find($variantWithHistory->id));
    }

    public function test_reseller_payment_create_ignores_forged_cancelled_status(): void
    {
        $user = User::factory()->create();
        $reseller = Reseller::create([
            'business_name' => 'Regular Reseller',
            'name' => 'Regular Reseller',
            'mobile' => '0711234567',
            'due_amount' => 500,
            'return_fee' => 0,
            'reseller_type' => Reseller::TYPE_RESELLER,
        ]);

        $this->actingAs($user)->post(route('reseller-payments.store'), [
            'reseller_id' => $reseller->id,
            'amount' => 100,
            'payment_method' => 'cash',
            'reference_id' => 'PAY-001',
            'payment_date' => now()->toDateString(),
            'status' => 'cancelled',
        ])->assertRedirect(route('reseller-payments.index'));

        $payment = ResellerPayment::firstOrFail();
        $this->assertSame('paid', $payment->status);
        $this->assertEquals(400.0, (float) $reseller->fresh()->due_amount);
    }

    public function test_reseller_payment_move_reverses_old_reseller_and_applies_new_reseller(): void
    {
        $user = User::factory()->create();
        $oldReseller = Reseller::create([
            'business_name' => 'Old Reseller',
            'name' => 'Old Reseller',
            'mobile' => '0711234567',
            'due_amount' => 400,
            'return_fee' => 0,
            'reseller_type' => Reseller::TYPE_RESELLER,
        ]);
        $newReseller = Reseller::create([
            'business_name' => 'New Reseller',
            'name' => 'New Reseller',
            'mobile' => '0711234568',
            'due_amount' => 500,
            'return_fee' => 0,
            'reseller_type' => Reseller::TYPE_RESELLER,
        ]);
        $payment = ResellerPayment::create([
            'reseller_id' => $oldReseller->id,
            'amount' => 100,
            'payment_method' => 'cash',
            'reference_id' => 'PAY-002',
            'payment_date' => now()->toDateString(),
            'status' => 'paid',
        ]);

        $this->actingAs($user)->put(route('reseller-payments.update', $payment), [
            'reseller_id' => $newReseller->id,
            'amount' => 100,
            'payment_method' => 'cash',
            'reference_id' => 'PAY-002',
            'payment_date' => now()->toDateString(),
        ])->assertRedirect(route('reseller-payments.index'));

        $this->assertSame($newReseller->id, $payment->fresh()->reseller_id);
        $this->assertEquals(500.0, (float) $oldReseller->fresh()->due_amount);
        $this->assertEquals(400.0, (float) $newReseller->fresh()->due_amount);
    }

    public function test_reseller_due_is_synced_when_reseller_order_is_created(): void
    {
        $user = User::factory()->create();
        $city = City::create(['city_name' => 'Colombo', 'district' => 'Colombo', 'province' => 'Western', 'postal_code' => '00100']);
        $courier = $this->makeCourier('Order Create Courier');
        $reseller = $this->makeReseller('Regular Order Reseller');
        [, $variant] = $this->makeProductWithVariant('SKU-ORDER-CREATE');
        $variant->update(['quantity' => 2]);
        $this->makeAvailableInventoryUnits($variant, 2, 'CREATE');

        $this->actingAs($user)->postJson(route('orders.store'), [
            'order_type' => 'reseller',
            'reseller_id' => $reseller->id,
            'customer' => [
                'name' => 'Order Customer',
                'mobile' => '0771234567',
                'address' => '123 Main Street',
                'city_id' => $city->id,
            ],
            'items' => [
                [
                    'id' => $variant->id,
                    'quantity' => 2,
                    'selling_price' => 150,
                ],
            ],
            'courier_id' => $courier->id,
            'courier_charge' => 0,
            'payment_method' => 'COD',
            'discount_type' => 'fixed',
            'discount_value' => 0,
        ])->assertOk();

        $this->assertEquals(300.0, (float) $reseller->fresh()->due_amount);
    }

    public function test_reseller_due_is_synced_when_order_moves_between_resellers_on_update(): void
    {
        $user = User::factory()->create();
        $city = City::create(['city_name' => 'Kandy', 'district' => 'Kandy', 'province' => 'Central', 'postal_code' => '20000']);
        $courier = $this->makeCourier('Order Update Courier');
        $oldReseller = $this->makeReseller('Old Order Reseller', '0712000001', 100);
        $newReseller = $this->makeReseller('New Order Reseller', '0712000002');
        [, $variant] = $this->makeProductWithVariant('SKU-ORDER-UPDATE');
        $variant->update(['quantity' => 2]);
        $this->makeAvailableInventoryUnits($variant, 2, 'UPDATE');

        $order = $this->makeResellerOrder($oldReseller, 100, 'ORD-20260518-0200');
        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_variant_id' => $variant->id,
            'product_name' => 'Old Item',
            'sku' => $variant->sku,
            'quantity' => 1,
            'unit_price' => 100,
            'base_price' => 80,
            'total_price' => 100,
            'subtotal' => 100,
        ]);
        InventoryUnit::where('product_variant_id', $variant->id)->first()->update([
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'status' => InventoryUnit::STATUS_ALLOCATED,
            'allocated_at' => now(),
        ]);

        $this->actingAs($user)->putJson(route('orders.update', $order), [
            'order_type' => 'reseller',
            'reseller_id' => $newReseller->id,
            'customer' => [
                'name' => 'Updated Customer',
                'mobile' => '0777654321',
                'address' => '456 Main Street',
                'city_id' => $city->id,
            ],
            'items' => [
                [
                    'id' => $variant->id,
                    'quantity' => 2,
                    'selling_price' => 150,
                ],
            ],
            'courier_id' => $courier->id,
            'courier_charge' => 0,
            'payment_method' => 'COD',
            'discount_type' => 'fixed',
            'discount_value' => 0,
            'delivery_status' => 'pending',
        ])->assertOk();

        $this->assertEquals(0.0, (float) $oldReseller->fresh()->due_amount);
        $this->assertEquals(300.0, (float) $newReseller->fresh()->due_amount);
    }

    public function test_reseller_due_is_synced_when_order_is_cancelled_or_deleted(): void
    {
        $user = User::factory()->create();
        $reseller = $this->makeReseller('Lifecycle Reseller', '0713000001', 450);
        $cancelledOrder = $this->makeResellerOrder($reseller, 200, 'ORD-20260518-0300');
        $deletedOrder = $this->makeResellerOrder($reseller, 250, 'ORD-20260518-0301');

        $this->actingAs($user)->postJson(route('orders.status.update', $cancelledOrder), [
            'status' => 'cancel',
        ])->assertOk();

        $this->assertEquals(250.0, (float) $reseller->fresh()->due_amount);

        $this->actingAs($user)->delete(route('orders.destroy', $deletedOrder))
            ->assertRedirect(route('orders.index'));

        $this->assertEquals(0.0, (float) $reseller->fresh()->due_amount);
    }

    public function test_direct_reseller_order_due_does_not_use_regular_return_fee_penalty(): void
    {
        $user = User::factory()->create();
        $city = City::create(['city_name' => 'Galle', 'district' => 'Galle', 'province' => 'Southern', 'postal_code' => '80000']);
        $courier = $this->makeCourier('Direct Reseller Order Courier');
        $directReseller = $this->makeReseller(
            'Direct Order Reseller',
            '0714000001',
            0,
            25,
            Reseller::TYPE_DIRECT_RESELLER
        );
        [, $variant] = $this->makeProductWithVariant('SKU-DIRECT-CREATE');
        $variant->update(['quantity' => 1]);
        $this->makeAvailableInventoryUnits($variant, 1, 'DIRECT');

        $this->actingAs($user)->postJson(route('orders.store'), [
            'order_type' => 'reseller',
            'reseller_id' => $directReseller->id,
            'customer' => [
                'name' => 'Direct Customer',
                'mobile' => '0771112223',
                'address' => '789 Main Street',
                'city_id' => $city->id,
            ],
            'items' => [
                [
                    'id' => $variant->id,
                    'quantity' => 1,
                    'selling_price' => 150,
                ],
            ],
            'courier_id' => $courier->id,
            'courier_charge' => 0,
            'payment_method' => 'COD',
            'discount_type' => 'fixed',
            'discount_value' => 0,
        ])->assertOk();

        $directReseller->refresh();
        $order = Order::where('reseller_id', $directReseller->id)->firstOrFail();
        $this->assertEquals(150.0, (float) $directReseller->due_amount);
        $this->assertEquals(0.0, (float) $order->reseller_return_fee_applied);
    }

    public function test_demo_seeder_does_not_reset_existing_admin_password(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::where('email', 'admin@shoppy-max.com')->firstOrFail();
        $admin->forceFill(['password' => Hash::make('changed-password')])->save();

        $this->seed(DemoSystemSeeder::class);

        $this->assertTrue(Hash::check('changed-password', $admin->fresh()->password));
    }

    public function test_waybill_print_failure_does_not_mark_order_printed_or_consume_waybill(): void
    {
        $user = User::factory()->create();
        $courier = Courier::create(['name' => 'Test Courier', 'is_active' => true]);
        $order = Order::forceCreate([
            'order_number' => 'ORD-20260518-0101',
            'order_date' => now()->toDateString(),
            'courier_id' => $courier->id,
            'status' => 'confirm',
            'call_status' => 'confirm',
            'delivery_status' => 'pending',
            'payment_method' => 'COD',
            'payment_status' => 'pending',
            'total_amount' => 1000,
        ]);
        $waybill = CourierWaybill::create([
            'courier_id' => $courier->id,
            'code' => 'WB-FAIL-001',
            'sequence_number' => 1,
            'range_start' => 1,
            'range_end' => 1,
        ]);

        $this->app->bind(\App\Services\WaybillPdfService::class, fn () => new class extends \App\Services\WaybillPdfService
        {
            public function download(Collection $orders, string $paperSize, string $filePrefix, $generatedAt): \Illuminate\Http\Response
            {
                throw new RuntimeException('PDF generation failed');
            }
        });

        try {
            $this->withoutExceptionHandling();
            $this->actingAs($user)->post(route('orders.waybill.print'), [
                'courier_id' => $courier->id,
                'paper_size' => 'a4',
                'order_ids' => [$order->id],
            ]);
            $this->fail('Expected PDF generation to fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('PDF generation failed', $exception->getMessage());
        }

        $order->refresh();
        $waybill->refresh();

        $this->assertNull($order->waybill_number);
        $this->assertSame('pending', $order->delivery_status);
        $this->assertNull($order->waybill_printed_at);
        $this->assertNull($order->waybill_printed_by);
        $this->assertNull($waybill->order_id);
        $this->assertNull($waybill->allocated_at);
    }

    public function test_orders_index_exposes_cancel_action_only_before_waybill_printing(): void
    {
        $user = User::factory()->create();

        Order::forceCreate([
            'order_number' => 'ORD-20260518-0400',
            'order_date' => now()->toDateString(),
            'status' => 'pending',
            'call_status' => 'pending',
            'delivery_status' => 'pending',
            'payment_method' => 'COD',
            'payment_status' => 'pending',
            'total_amount' => 1000,
        ]);
        Order::forceCreate([
            'order_number' => 'ORD-20260518-0401',
            'order_date' => now()->toDateString(),
            'status' => 'confirm',
            'call_status' => 'confirm',
            'delivery_status' => 'waybill_printed',
            'waybill_number' => 'WB-CANCEL-BLOCKED',
            'waybill_printed_at' => now(),
            'payment_method' => 'COD',
            'payment_status' => 'pending',
            'total_amount' => 1000,
        ]);

        $response = $this->actingAs($user)->get(route('orders.index', ['view' => 'active']));

        $response->assertOk();
        $response->assertSee('title="Cancel Order"', false);
        $response->assertSee('title="Cancel is only available before waybill printing"', false);
    }

    public function test_waybill_printed_order_cannot_be_cancelled_from_status_endpoint(): void
    {
        $user = User::factory()->create();
        $order = Order::forceCreate([
            'order_number' => 'ORD-20260518-0402',
            'order_date' => now()->toDateString(),
            'status' => 'confirm',
            'call_status' => 'confirm',
            'delivery_status' => 'waybill_printed',
            'waybill_number' => 'WB-CANCEL-LOCK',
            'waybill_printed_at' => now(),
            'payment_method' => 'COD',
            'payment_status' => 'pending',
            'total_amount' => 1000,
        ]);

        $response = $this->actingAs($user)->postJson(route('orders.status.update', $order), [
            'status' => 'cancel',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);

        $order->refresh();
        $this->assertSame('confirm', $order->status);
        $this->assertSame('confirm', $order->call_status);
        $this->assertSame('waybill_printed', $order->delivery_status);
        $this->assertNull($order->cancelled_at);
    }

    public function test_delivered_orders_only_show_download_and_view_actions_on_index(): void
    {
        $user = User::factory()->create();

        Order::forceCreate([
            'order_number' => 'ORD-20260518-0403',
            'order_date' => now()->toDateString(),
            'status' => 'confirm',
            'call_status' => 'confirm',
            'delivery_status' => 'delivered',
            'waybill_number' => 'WB-DELIVERED-ACTION',
            'waybill_printed_at' => now()->subHour(),
            'delivered_at' => now(),
            'payment_method' => 'COD',
            'payment_status' => 'paid',
            'total_amount' => 1000,
        ]);

        $response = $this->actingAs($user)->get(route('orders.index', ['view' => 'active']));

        $response->assertOk();
        $response->assertSee('ORD-20260518-0403');
        $response->assertSee('title="Download PDF"', false);
        $response->assertSee('title="View Details"', false);
        $response->assertDontSee('title="Reprint Waybill"', false);
        $response->assertDontSee('title="Update Payment"', false);
        $response->assertDontSee('title="Cancel is only available before waybill printing"', false);
        $response->assertDontSee('title="Manual edit, payment update, and delete are locked for this order"', false);
        $response->assertSee('waybillEligibleOrderIds: []', false);
    }

    public function test_waybill_excel_failure_does_not_mark_orders_exported(): void
    {
        $user = User::factory()->create();
        $courier = Courier::create(['name' => 'Export Courier', 'is_active' => true]);
        $order = Order::forceCreate([
            'order_number' => 'ORD-20260518-0102',
            'order_date' => now()->toDateString(),
            'courier_id' => $courier->id,
            'status' => 'confirm',
            'call_status' => 'confirm',
            'delivery_status' => 'waybill_printed',
            'waybill_number' => 'WB-XLSX-001',
            'waybill_printed_at' => now(),
            'payment_method' => 'COD',
            'payment_status' => 'pending',
            'total_amount' => 1000,
        ]);

        Excel::shouldReceive('download')
            ->once()
            ->andThrow(new RuntimeException('Excel generation failed'));

        try {
            $this->withoutExceptionHandling();
            $this->actingAs($user)->post(route('orders.waybill-excel.export', $courier));
            $this->fail('Expected Excel generation to fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Excel generation failed', $exception->getMessage());
        }

        $order->refresh();

        $this->assertNull($order->waybill_excel_exported_at);
        $this->assertNull($order->waybill_excel_exported_by);
    }

    private function makeProductWithVariant(string $sku = 'SKU-001'): array
    {
        $category = Category::create(['name' => 'Beauty', 'code' => 'BEAUTY']);
        $unit = Unit::create(['name' => 'Piece', 'short_name' => 'pcs']);
        $product = Product::create([
            'name' => 'Traceable Product '.$sku,
            'category_id' => $category->id,
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'sku' => $sku,
            'selling_price' => 100,
            'limit_price' => 80,
            'quantity' => 0,
            'alert_quantity' => 0,
        ]);

        return [$product, $variant];
    }

    private function makeReseller(
        string $name,
        string $mobile = '0711234567',
        float $dueAmount = 0,
        float $returnFee = 0,
        string $type = Reseller::TYPE_RESELLER
    ): Reseller {
        return Reseller::create([
            'business_name' => $name,
            'name' => $name,
            'mobile' => $mobile,
            'due_amount' => $dueAmount,
            'return_fee' => $returnFee,
            'reseller_type' => $type,
        ]);
    }

    private function makeResellerOrder(Reseller $reseller, float $totalAmount, string $orderNumber): Order
    {
        return Order::forceCreate([
            'order_number' => $orderNumber,
            'order_date' => now()->toDateString(),
            'order_type' => 'reseller',
            'reseller_id' => $reseller->id,
            'status' => 'pending',
            'call_status' => 'pending',
            'delivery_status' => 'pending',
            'payment_method' => 'COD',
            'payment_status' => 'pending',
            'total_amount' => $totalAmount,
        ]);
    }

    private function makeCourier(string $name): Courier
    {
        return Courier::create([
            'name' => $name,
            'rates' => [0],
            'is_active' => true,
        ]);
    }

    private function makeAvailableInventoryUnits(ProductVariant $variant, int $quantity, string $prefix): void
    {
        foreach (range(1, $quantity) as $number) {
            InventoryUnit::create([
                'product_variant_id' => $variant->id,
                'unit_code' => "UNIT-{$prefix}-{$number}",
                'status' => InventoryUnit::STATUS_AVAILABLE,
                'sku_snapshot' => $variant->sku,
                'available_at' => now(),
                'last_event_at' => now(),
            ]);
        }
    }
}
