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

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['category', 'variants'])->latest();

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

        $products = $query->paginate(10);
        $products->appends($request->all());

        $categories = Category::all();
        return view('product_management.products.index', compact('products', 'categories'));
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
            'variants.*.quantity' => 'required|integer|min:0',
            'variants.*.alert_quantity' => 'nullable|integer|min:0',
            'variants.*.image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:2048',
        ]);

        // 1. Handle Product Image
        if ($request->hasFile('image')) {
            $validated['image'] = Cloudinary::uploadApi()->upload($request->file('image')->getRealPath(), ['verify' => false])['secure_url'];
        }

        // 2. Create Product
        $product = Product::create([
            'name' => $validated['name'],
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
                'quantity' => $variantData['quantity'],
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
            'variants.*.quantity' => 'required|integer|min:0', // Allowing manual update here for now
            'variants.*.alert_quantity' => 'nullable|integer|min:0',
            'variants.*.image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:2048',
        ]);

        // Update Product Image
        if ($request->hasFile('image')) {
            $validated['image'] = Cloudinary::uploadApi()->upload($request->file('image')->getRealPath(), ['verify' => false])['secure_url'];
        }
        
        $product->update([
            'name' => $validated['name'],
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
                        'quantity' => $variantData['quantity'],
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
                    'quantity' => $variantData['quantity'],
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
}
