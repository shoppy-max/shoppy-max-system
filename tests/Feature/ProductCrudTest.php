<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Unit;
use App\Models\User;
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
