<?php

namespace App\Http\Controllers;

use App\Models\Courier;
use Illuminate\Http\Request;

class CourierController extends Controller
{
    /**
     * Display a listing of couriers.
     */
    public function index(Request $request)
    {
        $query = Courier::query();

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $couriers = $query->latest()->paginate(10);
        return view('couriers.index', compact('couriers'));
    }

    /**
     * Show the form for creating a new courier.
     */
    public function create()
    {
        return view('couriers.create');
    }

    /**
     * Store a newly created courier in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        Courier::create($validated);

        return redirect()->route('couriers.index')->with('success', 'Courier added successfully.');
    }

    /**
     * Show the form for editing the specified courier.
     */
    public function edit(Courier $courier)
    {
        return view('couriers.edit', compact('courier'));
    }

    /**
     * Update the specified courier in storage.
     */
    public function update(Request $request, Courier $courier)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $courier->update($validated);

        return redirect()->route('couriers.index')->with('success', 'Courier updated successfully.');
    }

    /**
     * Remove the specified courier from storage.
     */
    public function destroy(Courier $courier)
    {
        // Check for orders
        if ($courier->orders()->exists()) {
             return back()->with('error', 'Cannot delete courier with associated orders. Deactivate instead.');
        }
        
        $courier->delete();

        return redirect()->route('couriers.index')->with('success', 'Courier deleted successfully.');
    }
}
