<?php

namespace App\Http\Controllers;

use App\Models\Courier;
use App\Models\CourierPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CourierPaymentController extends Controller
{
    /**
     * Display a listing of courier payments.
     */
    public function index(Request $request)
    {
        $query = CourierPayment::with('courier');

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('courier', function($cq) use ($search) {
                    $cq->where('name', 'like', "%{$search}%");
                })
                ->orWhere('reference_number', 'like', "%{$search}%");
            });
        }

        // Filter by payment method
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->whereDate('payment_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('payment_date', '<=', $request->end_date);
        }

        $payments = $query->latest('payment_date')->paginate(20);
        
        return view('courier-payments.index', compact('payments'));
    }

    /**
     * Show the form for creating a new courier payment.
     */
    public function create()
    {
        $couriers = Courier::where('is_active', true)->orderBy('name')->get();
        return view('courier-payments.create', compact('couriers'));
    }

    /**
     * Store a newly created courier payment.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'courier_id' => 'required|exists:couriers,id',
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'payment_method' => 'nullable|string',
            'reference_number' => 'nullable|string|max:255',
            'payment_note' => 'nullable|string',
        ]);

        $validated['user_id'] = Auth::id();

        CourierPayment::create($validated);

        return redirect()->route('courier-payments.index')
            ->with('success', 'Courier payment recorded successfully.');
    }

    /**
     * Show the form for editing the specified courier payment.
     */
    public function edit(CourierPayment $courierPayment)
    {
        $couriers = Courier::where('is_active', true)->orderBy('name')->get();
        return view('courier-payments.edit', compact('courierPayment', 'couriers'));
    }

    /**
     * Update the specified courier payment.
     */
    public function update(Request $request, CourierPayment $courierPayment)
    {
        $validated = $request->validate([
            'courier_id' => 'required|exists:couriers,id',
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'payment_method' => 'nullable|string',
            'reference_number' => 'nullable|string|max:255',
            'payment_note' => 'nullable|string',
        ]);

        $courierPayment->update($validated);

        return redirect()->route('courier-payments.index')
            ->with('success', 'Courier payment updated successfully.');
    }

    /**
     * Remove the specified courier payment.
     */
    public function destroy(CourierPayment $courierPayment)
    {
        $courierPayment->delete();

        return redirect()->route('courier-payments.index')
            ->with('success', 'Courier payment deleted successfully.');
    }
}
