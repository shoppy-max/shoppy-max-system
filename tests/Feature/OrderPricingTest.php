<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\City;
use App\Models\InventoryUnit;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Reseller;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderPricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_direct_order_uses_variant_selling_price_even_when_request_is_tampered(): void
    {
        $user = User::factory()->create();
        [$city, $variant] = $this->orderDependencies();

        $response = $this->actingAs($user)->postJson(route('orders.store'), $this->orderPayload($city, $variant, [
            'order_type' => 'direct',
            'items' => [
                [
                    'id' => $variant->id,
                    'quantity' => 1,
                    'selling_price' => 1,
                ],
            ],
        ]));

        $response->assertOk()->assertJson(['success' => true]);

        $order = Order::with('items')->latest('id')->firstOrFail();
        $item = $order->items->first();

        $this->assertSame('direct', $order->order_type);
        $this->assertSame('250.00', $item->unit_price);
        $this->assertSame('250.00', $item->total_price);
        $this->assertSame('250.00', $order->total_amount);
        $this->assertSame('0.00', $order->total_commission);
    }

    public function test_reseller_order_allows_price_at_or_above_limit_price(): void
    {
        $user = User::factory()->create();
        [$city, $variant] = $this->orderDependencies();
        $reseller = $this->makeReseller();

        $response = $this->actingAs($user)->postJson(route('orders.store'), $this->orderPayload($city, $variant, [
            'order_type' => 'reseller',
            'reseller_id' => $reseller->id,
            'items' => [
                [
                    'id' => $variant->id,
                    'quantity' => 1,
                    'selling_price' => 180,
                ],
            ],
        ]));

        $response->assertOk()->assertJson(['success' => true]);

        $order = Order::with('items')->latest('id')->firstOrFail();
        $item = $order->items->first();

        $this->assertSame('reseller', $order->order_type);
        $this->assertSame('180.00', $item->unit_price);
        $this->assertSame('100.00', $item->base_price);
        $this->assertSame('80.00', $order->total_commission);
    }

    public function test_reseller_order_rejects_price_below_limit_price(): void
    {
        $user = User::factory()->create();
        [$city, $variant] = $this->orderDependencies();
        $reseller = $this->makeReseller();

        $response = $this->actingAs($user)->postJson(route('orders.store'), $this->orderPayload($city, $variant, [
            'order_type' => 'reseller',
            'reseller_id' => $reseller->id,
            'items' => [
                [
                    'id' => $variant->id,
                    'quantity' => 1,
                    'selling_price' => 99,
                ],
            ],
        ]));

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $this->assertDatabaseCount('orders', 0);
    }

    private function orderPayload(City $city, ProductVariant $variant, array $overrides = []): array
    {
        return array_replace_recursive([
            'order_type' => 'direct',
            'customer' => [
                'name' => 'Order Pricing Customer',
                'mobile' => '0771234567',
                'landline' => '',
                'address' => '123 Test Road',
                'city_id' => $city->id,
                'district' => $city->district,
                'province' => $city->province,
            ],
            'items' => [
                [
                    'id' => $variant->id,
                    'quantity' => 1,
                    'selling_price' => $variant->selling_price,
                ],
            ],
            'courier_id' => null,
            'courier_charge' => 0,
            'discount_type' => 'fixed',
            'discount_value' => 0,
            'payment_method' => 'COD',
            'payment_status' => 'pending',
            'sales_note' => '',
        ], $overrides);
    }

    private function orderDependencies(): array
    {
        $city = City::create([
            'city_name' => 'Colombo 01',
            'postal_code' => '00100',
            'district' => 'Colombo',
            'province' => 'Western',
        ]);
        $category = Category::create(['name' => 'Pricing Category', 'code' => 'PRICE']);
        $unit = Unit::create(['name' => 'Piece', 'short_name' => 'pcs']);
        $product = Product::create([
            'name' => 'Pricing Product',
            'category_id' => $category->id,
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'sku' => 'SKU-ORDER-PRICE',
            'selling_price' => 250,
            'limit_price' => 100,
            'quantity' => 3,
            'alert_quantity' => 0,
        ]);

        foreach (range(1, 3) as $index) {
            InventoryUnit::create([
                'product_variant_id' => $variant->id,
                'unit_code' => 'UNIT-ORDER-PRICE-' . $index,
                'status' => InventoryUnit::STATUS_AVAILABLE,
                'sku_snapshot' => $variant->sku,
                'product_name_snapshot' => $product->name,
                'variant_label_snapshot' => 'Piece',
                'available_at' => now(),
                'last_event_at' => now(),
            ]);
        }

        return [$city, $variant];
    }

    private function makeReseller(): Reseller
    {
        return Reseller::create([
            'business_name' => 'Pricing Reseller',
            'name' => 'Pricing Reseller Contact',
            'mobile' => '0777654321',
            'due_amount' => 0,
            'reseller_type' => Reseller::TYPE_RESELLER,
        ]);
    }
}
