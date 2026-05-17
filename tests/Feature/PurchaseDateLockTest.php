<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseDateLockTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_purchase_create_shows_current_date_as_disabled(): void
    {
        Carbon::setTestNow('2026-05-18 10:00:00');

        $response = $this->actingAs(User::factory()->create())
            ->get(route('purchases.create'));

        $response->assertOk();
        $response->assertSee('value="2026-05-18"', false);
        $response->assertSee('disabled', false);
        $response->assertSee("New purchases use today's date automatically.", false);
        $response->assertDontSee('name="purchase_date"', false);
    }

    public function test_purchase_store_uses_current_date_even_if_request_is_tampered(): void
    {
        Carbon::setTestNow('2026-05-18 10:00:00');

        $user = User::factory()->create();
        [$supplier, $variant] = $this->purchaseDependencies();

        $response = $this->actingAs($user)->post(route('purchases.store'), [
            'supplier_id' => $supplier->id,
            'purchase_date' => '2020-01-01',
            'purchase_number' => 'PUR-TEST-LOCKED-DATE',
            'items' => [
                [
                    'product_variant_id' => $variant->id,
                    'product_id' => $variant->product_id,
                    'product_name' => 'Locked Date Product',
                    'quantity' => 2,
                    'purchase_price' => 150,
                ],
            ],
            'discount_type' => 'fixed',
            'discount_value' => 0,
        ]);

        $purchase = Purchase::where('purchase_number', 'PUR-TEST-LOCKED-DATE')->firstOrFail();

        $response->assertRedirect(route('purchases.success', $purchase));
        $this->assertSame('2026-05-18', $purchase->purchase_date->format('Y-m-d'));
    }

    private function purchaseDependencies(): array
    {
        $supplier = Supplier::create([
            'name' => 'Test Supplier',
            'business_name' => 'Test Supplier Business',
            'mobile' => '0770000000',
        ]);
        $category = Category::create(['name' => 'Beauty', 'code' => 'BEAUTY']);
        $unit = Unit::create(['name' => 'Piece', 'short_name' => 'pcs']);
        $product = Product::create([
            'name' => 'Locked Date Product',
            'category_id' => $category->id,
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'sku' => 'SKU-LOCKED-DATE',
            'selling_price' => 200,
            'limit_price' => 150,
            'quantity' => 0,
            'alert_quantity' => 0,
        ]);

        return [$supplier, $variant];
    }
}
