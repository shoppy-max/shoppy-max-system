<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class GuestProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::where('quantity', '>', 0) // Only available products
                           ->latest()
                           ->paginate(12);
        return view('guest.products.index', compact('products'));
    }
}
