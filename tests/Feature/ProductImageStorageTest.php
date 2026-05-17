<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Unit;
use App\Models\User;
use App\Services\ProductImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImageStorageTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_create_stores_images_as_private_b2_object_keys(): void
    {
        Storage::fake('b2');
        config(['product-images.disk' => 'b2']);

        $user = User::factory()->create();
        $category = Category::create(['name' => 'Beauty', 'code' => 'BEAUTY']);
        $unit = Unit::create(['name' => 'Gram', 'short_name' => 'g']);

        $response = $this->actingAs($user)->post(route('products.store'), [
            'name' => 'B2 Image Product',
            'category_id' => $category->id,
            'image' => UploadedFile::fake()->image('product.jpg', 800, 800),
            'variants' => [
                [
                    'unit_id' => $unit->id,
                    'unit_value' => '1',
                    'sku' => 'SKU-B2-IMAGE',
                    'selling_price' => 100,
                    'limit_price' => 80,
                    'alert_quantity' => 0,
                    'image' => UploadedFile::fake()->image('variant.jpg', 800, 800),
                ],
            ],
        ]);

        $product = Product::where('name', 'B2 Image Product')->firstOrFail();
        $variant = $product->variants()->firstOrFail();

        $response->assertRedirect(route('products.success', $product));

        $this->assertStringStartsWith('products/', $product->image);
        $this->assertStringStartsWith('product-variants/', $variant->image);
        $this->assertFalse(str_starts_with($product->image, '/storage/'));
        $this->assertFalse(str_starts_with($product->image, 'http'));
        Storage::disk('b2')->assertExists($product->image);
        Storage::disk('b2')->assertExists($variant->image);
    }

    public function test_product_json_includes_signed_image_urls_separately_from_stored_keys(): void
    {
        config(['product-images.disk' => 'b2']);
        Storage::fake('b2')->buildTemporaryUrlsUsing(
            fn (string $path) => "https://signed.example.test/{$path}?expires=1"
        );

        $user = User::factory()->create();
        $category = Category::create(['name' => 'Beauty', 'code' => 'BEAUTY']);
        $unit = Unit::create(['name' => 'Piece', 'short_name' => 'pcs']);
        $product = Product::create([
            'name' => 'Signed Image Product',
            'category_id' => $category->id,
            'image' => 'products/signed.jpg',
        ]);
        ProductVariant::create([
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'unit_value' => null,
            'sku' => 'SKU-SIGNED-IMAGE',
            'selling_price' => 100,
            'limit_price' => 80,
            'quantity' => 0,
            'alert_quantity' => 0,
            'image' => 'product-variants/signed-variant.jpg',
        ]);

        $response = $this->actingAs($user)->getJson(route('products.show', $product));

        $response->assertOk();
        $response->assertJsonPath('image', 'products/signed.jpg');
        $response->assertJsonPath('image_url', 'https://signed.example.test/products/signed.jpg?expires=1');
        $response->assertJsonPath('variants.0.image', 'product-variants/signed-variant.jpg');
        $response->assertJsonPath('variants.0.image_url', 'https://signed.example.test/product-variants/signed-variant.jpg?expires=1');
    }

    public function test_remote_import_image_urls_are_copied_to_b2_when_valid(): void
    {
        Storage::fake('b2');
        Http::fake([
            'https://example.test/product.jpg' => Http::response('fake-image-bytes', 200, [
                'Content-Type' => 'image/jpeg',
            ]),
        ]);

        $path = app(ProductImageService::class)->uploadFromUrl('https://example.test/product.jpg', 'products');

        $this->assertIsString($path);
        $this->assertStringStartsWith('products/', $path);
        $this->assertStringEndsWith('.jpg', $path);
        Storage::disk('b2')->assertExists($path);
    }

    public function test_remote_import_image_urls_reject_non_images(): void
    {
        Storage::fake('b2');
        Http::fake([
            'https://example.test/not-image.txt' => Http::response('not-image', 200, [
                'Content-Type' => 'text/plain',
            ]),
        ]);

        $path = app(ProductImageService::class)->uploadFromUrl('https://example.test/not-image.txt', 'products');

        $this->assertNull($path);
    }

    public function test_public_products_page_uses_signed_b2_image_urls_and_searches_variant_skus(): void
    {
        config(['product-images.disk' => 'b2']);
        Storage::fake('b2')->buildTemporaryUrlsUsing(
            fn (string $path) => "https://signed.example.test/{$path}?expires=1"
        );

        $category = Category::create(['name' => 'Beauty', 'code' => 'BEAUTY']);
        $unit = Unit::create(['name' => 'Piece', 'short_name' => 'pcs']);
        $product = Product::create([
            'name' => 'Public Signed Product',
            'category_id' => $category->id,
            'image' => 'products/public-signed.jpg',
        ]);
        ProductVariant::create([
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'unit_value' => null,
            'sku' => 'SKU-PUBLIC-SIGNED',
            'selling_price' => 100,
            'limit_price' => 80,
            'quantity' => 3,
            'alert_quantity' => 0,
        ]);

        $response = $this->get(route('guest.products', ['search' => 'SKU-PUBLIC-SIGNED']));

        $response->assertOk();
        $response->assertSee('Public Signed Product');
        $response->assertSee('https://signed.example.test/products/public-signed.jpg?expires=1', false);
    }
}
