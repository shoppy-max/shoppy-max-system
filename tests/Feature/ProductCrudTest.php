<?php

namespace Tests\Feature;

use App\Http\Controllers\ProductController;
use App\Models\Category;
use App\Models\InventoryUnit;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_create_rejects_duplicate_exact_variant_units(): void
    {
        $user = User::factory()->create();
        [$category, $unit] = $this->productDependencies();

        $response = $this->actingAs($user)->post(route('products.store'), [
            'name' => 'Duplicate Unit Product',
            'category_id' => $category->id,
            'variants' => [
                $this->variantPayload($unit, ' 500 ', 'SKU-DUP-001'),
                $this->variantPayload($unit, '500', 'SKU-DUP-002'),
            ],
        ]);

        $response->assertSessionHasErrors(['variants.1.unit_id']);
        $this->assertDatabaseMissing('products', ['name' => 'Duplicate Unit Product']);
    }

    public function test_product_create_allows_same_unit_with_different_values(): void
    {
        $user = User::factory()->create();
        [$category, $unit] = $this->productDependencies();

        $response = $this->actingAs($user)->post(route('products.store'), [
            'name' => 'Different Unit Values Product',
            'category_id' => $category->id,
            'variants' => [
                $this->variantPayload($unit, '500', 'SKU-UNIT-500'),
                $this->variantPayload($unit, '1000', 'SKU-UNIT-1000'),
            ],
        ]);

        $product = Product::where('name', 'Different Unit Values Product')->firstOrFail();

        $response->assertRedirect(route('products.success', $product));
        $this->assertSame(2, $product->variants()->count());
    }

    public function test_product_update_rejects_duplicate_exact_variant_units(): void
    {
        $user = User::factory()->create();
        [$category, $unit] = $this->productDependencies();
        $product = Product::create([
            'name' => 'Existing Product',
            'category_id' => $category->id,
        ]);
        $firstVariant = ProductVariant::create($this->storedVariantPayload($product, $unit, '500', 'SKU-EXISTING-500'));
        $secondVariant = ProductVariant::create($this->storedVariantPayload($product, $unit, '1000', 'SKU-EXISTING-1000'));

        $response = $this->actingAs($user)->put(route('products.update', $product), [
            'name' => $product->name,
            'category_id' => $category->id,
            'variants' => [
                ['id' => $firstVariant->id] + $this->variantPayload($unit, '500', $firstVariant->sku),
                ['id' => $secondVariant->id] + $this->variantPayload($unit, ' 500 ', $secondVariant->sku),
            ],
        ]);

        $response->assertSessionHasErrors(['variants.1.unit_id']);
        $this->assertSame('1000', $secondVariant->fresh()->unit_value);
    }

    public function test_product_create_form_shows_only_permitted_price_fields(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $directPriceUser = User::factory()->create();
        $directPriceUser->givePermissionTo(['create products', 'manage direct product prices']);
        $this->productDependencies();

        $this->actingAs($directPriceUser)
            ->get(route('products.create'))
            ->assertOk()
            ->assertSee('Direct Price')
            ->assertDontSee('Reseller Limit Price');

        $resellerPriceUser = User::factory()->create();
        $resellerPriceUser->givePermissionTo(['create products', 'manage reseller product prices']);

        $this->actingAs($resellerPriceUser)
            ->get(route('products.create'))
            ->assertOk()
            ->assertDontSee('Direct Price')
            ->assertSee('Reseller Limit Price');
    }

    public function test_product_create_requires_direct_price_permission(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $user->givePermissionTo('create products');
        [$category, $unit] = $this->productDependencies();

        $response = $this->actingAs($user)->post(route('products.store'), [
            'name' => 'Unauthorized Direct Price Product',
            'category_id' => $category->id,
            'variants' => [
                $this->variantPayload($unit, '500', 'SKU-NO-DIRECT-PERM'),
            ],
        ]);

        $response->assertSessionHasErrors(['variants.0.selling_price']);
        $this->assertDatabaseMissing('products', ['name' => 'Unauthorized Direct Price Product']);
    }

    public function test_product_create_rejects_reseller_limit_price_without_permission(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $user->givePermissionTo(['create products', 'manage direct product prices']);
        [$category, $unit] = $this->productDependencies();

        $response = $this->actingAs($user)->post(route('products.store'), [
            'name' => 'Unauthorized Reseller Limit Product',
            'category_id' => $category->id,
            'variants' => [
                $this->variantPayload($unit, '500', 'SKU-NO-LIMIT-PERM'),
            ],
        ]);

        $response->assertSessionHasErrors(['variants.0.limit_price']);
        $this->assertDatabaseMissing('products', ['name' => 'Unauthorized Reseller Limit Product']);
    }

    public function test_product_update_preserves_direct_price_when_field_is_not_permitted(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $user->givePermissionTo(['edit products', 'manage reseller product prices']);
        [$category, $unit] = $this->productDependencies();
        $product = Product::create([
            'name' => 'Existing Priced Product',
            'category_id' => $category->id,
        ]);
        $variant = ProductVariant::create($this->storedVariantPayload($product, $unit, '500', 'SKU-PRICE-PRESERVE'));

        $response = $this->actingAs($user)->put(route('products.update', $product), [
            'name' => $product->name,
            'category_id' => $category->id,
            'variants' => [
                [
                    'id' => $variant->id,
                    'unit_id' => $unit->id,
                    'unit_value' => '500',
                    'sku' => $variant->sku,
                    'limit_price' => 70,
                    'alert_quantity' => 0,
                ],
            ],
        ]);

        $response->assertRedirect(route('products.index'));
        $this->assertEquals(100.0, (float) $variant->fresh()->selling_price);
        $this->assertEquals(70.0, (float) $variant->fresh()->limit_price);
    }

    public function test_product_update_rejects_reseller_limit_price_tampering_without_permission(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $user->givePermissionTo(['edit products', 'manage direct product prices']);
        [$category, $unit] = $this->productDependencies();
        $product = Product::create([
            'name' => 'Existing Limit Product',
            'category_id' => $category->id,
        ]);
        $variant = ProductVariant::create($this->storedVariantPayload($product, $unit, '500', 'SKU-LIMIT-TAMPER'));

        $response = $this->actingAs($user)->put(route('products.update', $product), [
            'name' => $product->name,
            'category_id' => $category->id,
            'variants' => [
                [
                    'id' => $variant->id,
                    'unit_id' => $unit->id,
                    'unit_value' => '500',
                    'sku' => $variant->sku,
                    'selling_price' => 110,
                    'limit_price' => 60,
                    'alert_quantity' => 0,
                ],
            ],
        ]);

        $response->assertSessionHasErrors(['variants.0.limit_price']);
        $this->assertEquals(80.0, (float) $variant->fresh()->limit_price);
    }

    public function test_product_index_and_detail_hide_price_data_without_price_permissions(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $user->givePermissionTo('view products');
        [$category, $unit] = $this->productDependencies();
        $product = Product::create([
            'name' => 'Hidden Price Product',
            'category_id' => $category->id,
        ]);
        ProductVariant::create($this->storedVariantPayload($product, $unit, '500', 'SKU-HIDDEN-PRICE'));

        $this->actingAs($user)
            ->get(route('products.index'))
            ->assertOk()
            ->assertDontSee('Direct Price')
            ->assertDontSee('Reseller Limit')
            ->assertDontSee('100.00')
            ->assertDontSee('80.00');

        $payload = $this->actingAs($user)
            ->getJson(route('products.show', $product))
            ->assertOk()
            ->json();

        $this->assertArrayNotHasKey('price_display', $payload);
        $this->assertArrayNotHasKey('limit_price_display', $payload);
        $this->assertArrayNotHasKey('selling_price', $payload['variants'][0]);
        $this->assertArrayNotHasKey('limit_price', $payload['variants'][0]);
    }

    public function test_quantity_product_barcode_labels_repeat_variant_sku_not_internal_unit_codes(): void
    {
        [$category, $unit] = $this->productDependencies();
        $product = Product::create([
            'name' => 'Barcode Product',
            'category_id' => $category->id,
        ]);
        $variant = ProductVariant::create(array_merge(
            $this->storedVariantPayload($product, $unit, '500', 'SKU-PRODUCT-500'),
            ['quantity' => 2]
        ));

        InventoryUnit::create([
            'product_variant_id' => $variant->id,
            'unit_code' => 'IU-PRODUCT-0001',
            'status' => InventoryUnit::STATUS_AVAILABLE,
            'sku_snapshot' => $variant->sku,
            'available_at' => now(),
            'last_event_at' => now(),
        ]);
        InventoryUnit::create([
            'product_variant_id' => $variant->id,
            'unit_code' => 'IU-PRODUCT-0002',
            'status' => InventoryUnit::STATUS_AVAILABLE,
            'sku_snapshot' => $variant->sku,
            'available_at' => now(),
            'last_event_at' => now(),
        ]);

        $method = new \ReflectionMethod(ProductController::class, 'buildBarcodeLabelsForVariant');
        $method->setAccessible(true);

        $labels = $method->invoke(app(ProductController::class), $variant);

        $this->assertCount(2, $labels);
        $this->assertSame(['SKU-PRODUCT-500', 'SKU-PRODUCT-500'], $labels->pluck('barcode_value')->all());
        $this->assertSame(['SKU-PRODUCT-500', 'SKU-PRODUCT-500'], $labels->pluck('display_code')->all());
        $this->assertNotContains('IU-PRODUCT-0001', $labels->pluck('barcode_value')->all());
        $this->assertNotContains('IU-PRODUCT-0002', $labels->pluck('display_code')->all());
    }

    private function productDependencies(): array
    {
        return [
            Category::create(['name' => 'Beauty', 'code' => 'BEAUTY']),
            Unit::create(['name' => 'Gram', 'short_name' => 'g']),
        ];
    }

    private function variantPayload(Unit $unit, ?string $unitValue, string $sku): array
    {
        return [
            'unit_id' => $unit->id,
            'unit_value' => $unitValue,
            'sku' => $sku,
            'selling_price' => 100,
            'limit_price' => 80,
            'alert_quantity' => 0,
        ];
    }

    private function storedVariantPayload(Product $product, Unit $unit, ?string $unitValue, string $sku): array
    {
        return [
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'unit_value' => $unitValue,
            'sku' => $sku,
            'selling_price' => 100,
            'limit_price' => 80,
            'quantity' => 0,
            'alert_quantity' => 0,
        ];
    }
}
