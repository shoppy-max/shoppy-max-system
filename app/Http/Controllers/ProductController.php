<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\SubCategory; // Make sure SubCategory model is imported
use App\Models\Unit;
use App\Models\Attribute;
use App\Models\InventoryUnit;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProductsExport;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Picqer\Barcode\BarcodeGeneratorPNG;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        [$selectedUnitId, $selectedUnitValue, $isValueSpecificUnitFilter] = $this->resolveVariantUnitFilter($request);

        $applyVariantUnitFilter = function ($variantQuery) use ($selectedUnitId, $selectedUnitValue, $isValueSpecificUnitFilter) {
            if (!$selectedUnitId) {
                return;
            }

            $variantQuery->where('unit_id', $selectedUnitId)
                ->where('quantity', '>', 0);

            if (!$isValueSpecificUnitFilter) {
                return;
            }

            if ($selectedUnitValue === '') {
                $variantQuery->where(function ($query) {
                    $query->whereNull('unit_value')
                        ->orWhere('unit_value', '');
                });
            } else {
                $variantQuery->where('unit_value', $selectedUnitValue);
            }
        };

        $query = Product::with([
            'category',
            'subCategory',
            'variants' => function ($variantQuery) use ($applyVariantUnitFilter) {
                $applyVariantUnitFilter($variantQuery);
            },
        ])->latest();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('barcode_data', 'like', "%{$search}%")
                  ->orWhereHas('variants', function($subQ) use ($search) {
                      $subQ->where('sku', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('sub_category_id')) {
            $query->where('sub_category_id', $request->input('sub_category_id'));
        }

        if ($selectedUnitId) {
            $query->whereHas('variants', function ($variantQuery) use ($applyVariantUnitFilter) {
                $applyVariantUnitFilter($variantQuery);
            });
        }

        $products = $query->paginate(10);
        $products->appends($request->all());

        $categories = Category::orderBy('name')->get();
        $subCategories = SubCategory::query()
            ->orderBy('name')
            ->get();
        $variantUnitOptions = ProductVariant::query()
            ->join('units', 'product_variants.unit_id', '=', 'units.id')
            ->where('product_variants.quantity', '>', 0)
            ->selectRaw("
                DISTINCT product_variants.unit_id,
                COALESCE(TRIM(product_variants.unit_value), '') as unit_value,
                units.name as unit_name,
                units.short_name as unit_short_name,
                CASE WHEN COALESCE(TRIM(product_variants.unit_value), '') = '' THEN 1 ELSE 0 END as unit_value_blank_sort,
                LOWER(COALESCE(TRIM(product_variants.unit_value), '')) as unit_value_sort
            ")
            ->orderBy('units.name')
            ->orderBy('unit_short_name')
            ->orderBy('unit_value_blank_sort')
            ->orderBy('unit_value_sort')
            ->get()
            ->map(function ($option) {
                $unitValue = (string) $option->unit_value;
                $unitShortName = trim((string) ($option->unit_short_name ?? ''));
                $unitName = trim((string) ($option->unit_name ?? ''));
                $unitLabel = $unitShortName !== '' ? $unitShortName : $unitName;

                $text = $unitValue !== ''
                    ? trim($unitValue . ' ' . $unitLabel)
                    : ($unitName . ($unitShortName !== '' ? ' (' . $unitShortName . ')' : ''));

                return [
                    'id' => $option->unit_id . '::' . rawurlencode($unitValue),
                    'text' => $text,
                ];
            })
            ->values();

        return view('product_management.products.index', compact('products', 'categories', 'subCategories', 'variantUnitOptions'));
    }

    public function export(Request $request) 
    {
        return Excel::download(new ProductsExport($request->all()), 'products_' . date('Y-m-d_H-i') . '.xlsx');
    }

    public function create()
    {
        $categories = Category::all();
        $subCategories = SubCategory::all(); 
        $units = Unit::all();
        return view('product_management.products.create', compact('categories', 'subCategories', 'units')); 
    }

    public function store(Request $request)
    {
        // Guard: detect if PHP silently discarded the POST body (post_max_size exceeded)
        if ($request->method() === 'POST' && empty($request->all()) && empty($_FILES)) {
            return back()->with('error', 'Upload failed: The file(s) you selected may be too large. Please reduce image sizes and try again. (Server limit: ' . ini_get('post_max_size') . ')');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'sub_category_id' => 'nullable|exists:sub_categories,id',
            'description' => 'nullable|string',
            'warranty_period' => 'nullable|integer|min:0',
            'warranty_period_type' => 'nullable|in:years,months,days',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:2048',
            
            // Variants Validation
            'variants' => 'required|array|min:1',
            'variants.*.unit_id' => 'required|exists:units,id',
            'variants.*.unit_value' => 'nullable|string|max:50',
            'variants.*.sku' => 'required|string|distinct|unique:product_variants,sku',
            'variants.*.selling_price' => 'required|numeric|min:0',
            'variants.*.limit_price' => 'nullable|numeric|min:0|lte:variants.*.selling_price',
            'variants.*.alert_quantity' => 'nullable|integer|min:0',
            'variants.*.image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:2048',
        ]);

        $this->ensureUniqueProductNameIgnoreCase($validated['name']);

        try {
            // 1. Handle Product Image
            if ($request->hasFile('image')) {
                $validated['image'] = $this->safeCloudinaryUpload($request->file('image'));
            }

            // 2. Create Product
            $product = Product::create([
                'name' => trim($validated['name']),
                'category_id' => $validated['category_id'],
                'sub_category_id' => $validated['sub_category_id'] ?? null,
                'description' => $validated['description'] ?? null,
                'warranty_period' => $validated['warranty_period'] ?? null,
                'warranty_period_type' => $validated['warranty_period_type'] ?? null,
                'image' => $validated['image'] ?? null,
                'barcode_data' => null,
            ]);

            // 3. Create Variants
            foreach ($request->variants as $index => $variantData) {
                $variantImage = null;
                if ($request->hasFile("variants.{$index}.image")) {
                    $variantImage = $this->safeCloudinaryUpload($request->file("variants.{$index}.image"));
                }

                $product->variants()->create([
                    'unit_id' => $variantData['unit_id'],
                    'unit_value' => $variantData['unit_value'] ?? null,
                    'sku' => $variantData['sku'],
                    'selling_price' => $variantData['selling_price'],
                    'limit_price' => $variantData['limit_price'] ?? null,
                    'quantity' => 0,
                    'alert_quantity' => $variantData['alert_quantity'] ?? 0,
                    'image' => $variantImage,
                ]);
            }

            return redirect()->route('products.success', $product)->with('success', 'Product created successfully.');

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Product creation failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->withInput()->with('error', 'Failed to create product: ' . $e->getMessage());
        }
    }

    public function edit(Product $product)
    {
        $product->load('variants');
        $categories = Category::all();
        $subCategories = SubCategory::all();
        $units = Unit::all();
        return view('product_management.products.edit', compact('product', 'categories', 'subCategories', 'units'));
    }

    public function update(Request $request, Product $product)
    {
        // Guard: detect if PHP silently discarded the POST body (post_max_size exceeded)
        if (empty($request->all()) && empty($_FILES)) {
            return back()->with('error', 'Upload failed: The file(s) you selected may be too large. Please reduce image sizes and try again. (Server limit: ' . ini_get('post_max_size') . ')');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'sub_category_id' => 'nullable|exists:sub_categories,id',
            'description' => 'nullable|string',
            'warranty_period' => 'nullable|integer|min:0',
            'warranty_period_type' => 'nullable|in:years,months,days',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:2048',

            'variants' => 'required|array|min:1',
            'variants.*.id' => 'nullable|exists:product_variants,id',
            'variants.*.unit_id' => 'required|exists:units,id',
            'variants.*.unit_value' => 'nullable|string|max:50',
            'variants.*.sku' => 'required|string|distinct',
            'variants.*.selling_price' => 'required|numeric|min:0',
            'variants.*.limit_price' => 'nullable|numeric|min:0',
            'variants.*.alert_quantity' => 'nullable|integer|min:0',
            'variants.*.image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:2048',
        ]);

        $this->ensureUniqueProductNameIgnoreCase($validated['name'], $product->id);

        try {
            // Update Product Image
            if ($request->hasFile('image')) {
                $validated['image'] = $this->safeCloudinaryUpload($request->file('image'));
            }
            
            $product->update([
                'name' => trim($validated['name']),
                'category_id' => $validated['category_id'],
                'sub_category_id' => $validated['sub_category_id'] ?? null,
                'description' => $validated['description'] ?? null,
                'warranty_period' => $validated['warranty_period'] ?? null,
                'warranty_period_type' => $validated['warranty_period_type'] ?? null,
                'image' => $validated['image'] ?? $product->image,
            ]);

            // Sync Variants
            $existingVariantIds = [];

            foreach ($request->variants as $index => $variantData) {
                $variantImage = null;
                if ($request->hasFile("variants.{$index}.image")) {
                    $variantImage = $this->safeCloudinaryUpload($request->file("variants.{$index}.image"));
                }

                if (isset($variantData['id']) && $variantData['id']) {
                    $variant = $product->variants()->find($variantData['id']);
                    if ($variant) {
                        $variant->update([
                            'unit_id' => $variantData['unit_id'],
                            'unit_value' => $variantData['unit_value'] ?? null,
                            'sku' => $variantData['sku'],
                            'selling_price' => $variantData['selling_price'],
                            'limit_price' => $variantData['limit_price'] ?? null,
                            'alert_quantity' => $variantData['alert_quantity'] ?? 0,
                            'image' => $variantImage ?? $variant->image,
                        ]);
                        $existingVariantIds[] = $variant->id;
                    }
                } else {
                    $newVariant = $product->variants()->create([
                        'unit_id' => $variantData['unit_id'],
                        'unit_value' => $variantData['unit_value'] ?? null,
                        'sku' => $variantData['sku'],
                        'selling_price' => $variantData['selling_price'],
                        'limit_price' => $variantData['limit_price'] ?? null,
                        'quantity' => 0,
                        'alert_quantity' => $variantData['alert_quantity'] ?? 0,
                        'image' => $variantImage,
                    ]);
                    $existingVariantIds[] = $newVariant->id;
                }
            }

            // Delete removed variants
            $product->variants()->whereNotIn('id', $existingVariantIds)->delete();

            return redirect()->route('products.index')->with('success', 'Product updated successfully.');

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Product update failed: ' . $e->getMessage(), [
                'product_id' => $product->id,
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->withInput()->with('error', 'Failed to update product: ' . $e->getMessage());
        }
    }

    public function destroy(Product $product)
    {
        try {
            $product->delete(); // Cascades deletes variants
            return redirect()->route('products.index')->with('success', 'Product deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error deleting product: ' . $e->getMessage());
        }
    }
    public function success(Product $product)
    {
        $product->load(['category', 'variants.unit']);
        return view('product_management.products.success', compact('product'));
    }

    public function printBarcode(ProductVariant $variant)
    {
        $variant->loadMissing(['product', 'unit']);
        $labels = $this->buildBarcodeLabelsForVariant($variant);

        $pdf = app('dompdf.wrapper');
        if ($labels->count() === 1) {
            $label = $labels->first();
            $pdf->loadView('product_management.products.barcode-single-pdf', compact('label'));
            return $pdf->stream('barcode_' . $variant->sku . '.pdf');
        }

        $pdf->loadView('product_management.products.barcode-pdf', compact('labels'));

        return $pdf->stream('barcode_' . $variant->sku . '_labels.pdf');
    }

    public function bulkPrintBarcode(Request $request) 
    {
        $request->validate([
            'products' => 'required|string', // Comma separated IDs
            'mode' => 'nullable|in:quantity,variant',
        ]);

        $productIds = explode(',', $request->products);
        $variants = ProductVariant::whereIn('product_id', $productIds)
                        ->with(['product', 'unit'])
                        ->orderBy('product_id')
                        ->get();

        if ($variants->isEmpty()) {
            return back()->with('error', 'No variants found for selected products.');
        }

        $mode = strtolower((string) $request->input('mode', 'quantity'));
        $labels = $mode === 'variant'
            ? $this->buildGenericBarcodeLabelsForVariants($variants)
            : $this->buildBarcodeLabelsForVariants($variants);

        if ($labels->isEmpty()) {
            return back()->with('error', 'No barcode labels available for selected products.');
        }

        $pdf = app('dompdf.wrapper');
        $pdf->loadView('product_management.products.barcode-pdf', compact('labels'));
        
        return $pdf->stream('barcodes.pdf');
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'products' => 'required|string',
        ]);
        
        $ids = explode(',', $request->products);
        
        // Use logic that ensures observers are fired (if needed) or just bulk delete
        // For deleting images etc, we might need loop. But for now, simple delete.
        Product::whereIn('id', $ids)->delete();
        
        return back()->with('success', count($ids) . ' products deleted successfully.');
    }

    public function show(Product $product)
    {
        $product->load(['category', 'subCategory', 'variants.unit']);
        return response()->json($product);
    }

    /**
     * Safely upload a file to Cloudinary with error handling.
     */
    private function safeCloudinaryUpload($file): string
    {
        try {
            $result = Cloudinary::uploadApi()->upload($file->getRealPath(), [
                'verify' => false,
                'timeout' => 60,
            ]);
            return $result['secure_url'];
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Cloudinary upload failed: ' . $e->getMessage(), [
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
            ]);
            throw new \RuntimeException('Image upload failed: ' . $e->getMessage());
        }
    }

    private function ensureUniqueProductNameIgnoreCase(string $name, ?int $ignoreProductId = null): void
    {
        $normalized = mb_strtolower(trim($name));

        $exists = Product::query()
            ->when($ignoreProductId, fn ($query) => $query->where('id', '!=', $ignoreProductId))
            ->whereRaw('LOWER(name) = ?', [$normalized])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'name' => 'A product with this name already exists.',
            ]);
        }
    }

    private function buildBarcodeLabelsForVariants(Collection $variants): Collection
    {
        $variants = $variants->loadMissing(['product', 'unit']);
        $variantIds = $variants->pluck('id')->filter()->values();

        $availableUnits = InventoryUnit::query()
            ->whereIn('product_variant_id', $variantIds)
            ->where('status', InventoryUnit::STATUS_AVAILABLE)
            ->orderBy('product_variant_id')
            ->orderBy('id')
            ->get(['product_variant_id', 'unit_code'])
            ->groupBy('product_variant_id');

        return $variants->flatMap(function (ProductVariant $variant) use ($availableUnits) {
            return $this->buildBarcodeLabelsForVariant($variant, $availableUnits->get($variant->id, collect()));
        })->values();
    }

    private function buildGenericBarcodeLabelsForVariants(Collection $variants): Collection
    {
        $variants = $variants->loadMissing(['product', 'unit']);

        return $variants->map(function (ProductVariant $variant) {
            return $this->buildGenericBarcodeLabelForVariant($variant);
        })->values();
    }

    private function buildBarcodeLabelsForVariant(ProductVariant $variant, ?Collection $availableUnits = null): Collection
    {
        $variant->loadMissing(['product', 'unit']);

        $productName = (string) ($variant->product?->name ?? '');
        $variantText = trim(
            (string) ($variant->unit_value ? $variant->unit_value . ' ' : '') .
            (string) ($variant->unit?->name ?? '')
        );
        $variantText .= $variant->unit?->short_name ? ' (' . $variant->unit->short_name . ')' : '';

        $stockCount = max((int) ($variant->quantity ?? 0), 0);
        $availableUnits ??= InventoryUnit::query()
            ->where('product_variant_id', $variant->id)
            ->where('status', InventoryUnit::STATUS_AVAILABLE)
            ->orderBy('id')
            ->get(['unit_code']);

        $labels = collect();

        foreach ($availableUnits as $unit) {
            $labels->push([
                'product_name' => $productName,
                'variant_text' => $variantText,
                'barcode_value' => (string) $unit->unit_code,
                'display_code' => (string) $unit->unit_code,
            ]);
        }

        $targetCount = $stockCount > 0 ? $stockCount : 1;

        while ($labels->count() < $targetCount) {
            $labels->push([
                'product_name' => $productName,
                'variant_text' => $variantText,
                'barcode_value' => (string) $variant->sku,
                'display_code' => (string) $variant->sku,
            ]);
        }

        return $labels->values();
    }

    private function buildGenericBarcodeLabelForVariant(ProductVariant $variant): array
    {
        $variant->loadMissing(['product', 'unit']);

        $variantText = trim(
            (string) ($variant->unit_value ? $variant->unit_value . ' ' : '') .
            (string) ($variant->unit?->name ?? '')
        );
        $variantText .= $variant->unit?->short_name ? ' (' . $variant->unit->short_name . ')' : '';

        return [
            'product_name' => (string) ($variant->product?->name ?? ''),
            'variant_text' => $variantText,
            'barcode_value' => (string) $variant->sku,
            'display_code' => (string) $variant->sku,
        ];
    }

    private function resolveVariantUnitFilter(Request $request): array
    {
        if ($request->filled('variant_unit')) {
            [$rawUnitId, $encodedValue] = array_pad(explode('::', (string) $request->input('variant_unit'), 2), 2, '');

            if (ctype_digit($rawUnitId)) {
                return [(int) $rawUnitId, urldecode($encodedValue), true];
            }
        }

        if ($request->filled('unit_id')) {
            return [(int) $request->input('unit_id'), null, false];
        }

        return [null, null, false];
    }
}
