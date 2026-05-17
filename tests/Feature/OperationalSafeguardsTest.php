<?php

namespace Tests\Feature;

use App\Models\Category;
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
use Illuminate\Support\Facades\Hash;
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

    public function test_demo_seeder_does_not_reset_existing_admin_password(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::where('email', 'admin@shoppy-max.com')->firstOrFail();
        $admin->forceFill(['password' => Hash::make('changed-password')])->save();

        $this->seed(DemoSystemSeeder::class);

        $this->assertTrue(Hash::check('changed-password', $admin->fresh()->password));
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
}
