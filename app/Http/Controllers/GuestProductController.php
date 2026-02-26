<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class GuestProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['category', 'subCategory', 'variants.unit'])
                        ->whereHas('variants', function ($q) {
                            $q->where('quantity', '>', 0);
                        });

        // Search by name or SKU
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhereHas('category', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        // Filter by price range
        if ($request->filled('min_price')) {
            $query->whereHas('variants', function ($q) use ($request) {
                $q->where('selling_price', '>=', $request->min_price);
            });
        }
        if ($request->filled('max_price')) {
            $query->whereHas('variants', function ($q) use ($request) {
                $q->where('selling_price', '<=', $request->max_price);
            });
        }

        // Sort options
        $sort = $request->get('sort', 'latest');
        switch ($sort) {
            case 'price_low':
                $query->withMin(['variants as min_variant_price' => function ($q) {
                    $q->where('quantity', '>', 0);
                }], 'selling_price')
                    ->orderBy('min_variant_price', 'asc');
                break;
            case 'price_high':
                $query->withMin(['variants as min_variant_price' => function ($q) {
                    $q->where('quantity', '>', 0);
                }], 'selling_price')
                    ->orderBy('min_variant_price', 'desc');
                break;
            case 'name_asc':
                $query->orderBy('name', 'ASC');
                break;
            case 'name_desc':
                $query->orderBy('name', 'DESC');
                break;
            default:
                $query->latest();
                break;
        }

        $products = $query->paginate(12)->withQueryString();
        $categories = Category::orderBy('name')->get();

        return view('guest.products.index', compact('products', 'categories'));
    }
}
