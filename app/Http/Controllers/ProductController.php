<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\SubCategory; // Make sure SubCategory model is imported
use App\Models\Unit;
use App\Models\Attribute;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProductsExport;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Picqer\Barcode\BarcodeGeneratorHTML;
use App\Models\ProductVariant;
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
                units.short_name as unit_short_name
            ")
            ->orderBy('units.name')
            ->orderBy('unit_short_name')
            ->orderByRaw("CASE WHEN COALESCE(TRIM(product_variants.unit_value), '') = '' THEN 1 ELSE 0 END")
            ->orderByRaw("LOWER(COALESCE(TRIM(product_variants.unit_value), ''))")
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
        // Log file details for debugging
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            \Illuminate\Support\Facades\Log::info('Product Image Upload:', [
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'client_mime_type' => $file->getClientMimeType(),
                'extension' => $file->getClientOriginalExtension(),
            ]);
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

        // 1. Handle Product Image
        if ($request->hasFile('image')) {
            $validated['image'] = Cloudinary::uploadApi()->upload($request->file('image')->getRealPath(), ['verify' => false])['secure_url'];
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
            'barcode_data' => null, // Or derive from first variant SKU?
        ]);

        // 3. Create Variants
        foreach ($request->variants as $index => $variantData) {
            $variantImage = null;
            // Handle variant image upload check
            if ($request->hasFile("variants.{$index}.image")) {
                 $variantImage = Cloudinary::uploadApi()->upload($request->file("variants.{$index}.image")->getRealPath(), ['verify' => false])['secure_url'];
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
        // Log file details for debugging
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            \Illuminate\Support\Facades\Log::info('Product Update - Image Upload:', [
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'client_mime_type' => $file->getClientMimeType(),
            ]);
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
            // We can't easily use unique rule with ignore inside array validation in Laravel validation simple syntax
            // We'll rely on DB constraints or manual check if needed, but 'distinct' helps within the request.
            // For proper DB unique check ignoring self: we might need custom closure or loop check.
            
            'variants.*.selling_price' => 'required|numeric|min:0',
            'variants.*.limit_price' => 'nullable|numeric|min:0',
            'variants.*.alert_quantity' => 'nullable|integer|min:0',
            'variants.*.image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:2048',
        ]);

        $this->ensureUniqueProductNameIgnoreCase($validated['name'], $product->id);

        // Update Product Image
        if ($request->hasFile('image')) {
            $validated['image'] = Cloudinary::uploadApi()->upload($request->file('image')->getRealPath(), ['verify' => false])['secure_url'];
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
                 $variantImage = Cloudinary::uploadApi()->upload($request->file("variants.{$index}.image")->getRealPath(), ['verify' => false])['secure_url'];
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
        $product->load('variants.unit');
        return view('product_management.products.success', compact('product'));
    }

    public function printBarcode(ProductVariant $variant)
    {
        $generator = new BarcodeGeneratorHTML();
        $barcode = $generator->getBarcode($variant->sku, $generator::TYPE_CODE_128);
        
        return view('product_management.products.barcode_preview', compact('variant', 'barcode'));
    }

    public function bulkPrintBarcode(Request $request) 
    {
        $request->validate([
            'products' => 'required|string', // Comma separated IDs
        ]);

        $productIds = explode(',', $request->products);
        $variants = ProductVariant::whereIn('product_id', $productIds)
                        ->with(['product'])
                        ->orderBy('product_id')
                        ->get();

        if ($variants->isEmpty()) {
            return back()->with('error', 'No variants found for selected products.');
        }

        $pdf = app('dompdf.wrapper');
        $pdf->loadView('product_management.products.barcode-pdf', compact('variants'));
        
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
