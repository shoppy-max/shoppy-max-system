<?php

namespace App\Http\Controllers;

use App\Models\Courier;
use App\Models\Order;
use App\Models\CourierPayment;
use App\Models\BankAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CourierReceiveController extends Controller
{
    /**
     * Display the courier selection popup/page.
     */
    public function index()
    {
        $couriers = Courier::where('is_active', true)->orderBy('name')->get();
        return view('couriers.receive.index', compact('couriers'));
    }

    /**
     * Show the Receive Courier Payment form for a specific courier.
     */
    public function show(Courier $courier)
    {
        // Get orders that are "Dispatched" via this courier and NOT yet paid/reconciled
        // This logic might need adjustment based on specific business rules for "Receive"
        $orders = Order::where('courier_id', $courier->id)
            ->where('status', 'Dispatched') // Assuming Dispatched orders are the ones to be received
            ->whereNull('courier_payment_id')
            ->latest()
            ->take(50) // Limit for initial load
            ->get();

        $bankAccounts = BankAccount::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('couriers.receive.show', compact('courier', 'orders', 'bankAccounts'));
    }

    /**
     * Search for an order by Waybill/Order No.
     */
    public function searchOrder(Request $request) 
    {
        $query = $request->get('query');
        $order = Order::where(function($q) use ($query) {
                $q->where('waybill_number', $query)
                  ->orWhere('id', $query); // Assuming Order No is ID or another field
            })
            ->with(['customer', 'city', 'courier']) // Eager load relationships
            ->first();

        if ($order) {
            return response()->json([
                'success' => true,
                'data' => [
                    'waybill_number' => $order->waybill_number,
                    'order_no' => $order->id, // or order_number field
                    'customer_name' => $order->customer_name ?? $order->customer->name ?? 'N/A', // Fallback
                    'destination' => $order->city ? $order->city->name : ($order->customer_city ?? 'N/A'),
                    'description' => 'Order #' . $order->id, // Placeholder
                    'phone1' => $order->customer_phone,
                    'phone2' => $order->customer_phone_2 ?? '', // Assuming logic
                    'delivery_fee' => $order->delivery_fee,
                    'amount' => $order->total_amount,
                    'city' => $order->city ? $order->city->name : '',
                    'remarks' => $order->sales_note,
                    'id' => $order->id
                ]
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Order not found']);
    }

    /**
     * Handle Excel import preview.
     */
    public function import(Request $request, Courier $courier)
    {
        $request->validate([
            'excel_file' => 'required|mimes:xlsx,xls,csv',
        ]);

        try {
            // Quick implementation using basic Excel import or just parsing directly
            // For now, let's assume we use Maatwebsite Excel
            $data = \Maatwebsite\Excel\Facades\Excel::toArray([], $request->file('excel_file'));
            
            if (empty($data) || empty($data[0])) {
                return response()->json(['success' => false, 'message' => 'Empty or invalid file']);
            }

            $rows = $data[0];
            $foundOrders = [];

            // Skip header row if exists (usually row 0)
            // Logic depends on Excel structure. Assuming Column A is Waybill.
            foreach ($rows as $index => $row) {
                if ($index === 0) continue; // Skip header

                $waybill = $row[0] ?? null; // Adjusted based on actual template
                if ($waybill) {
                    $order = Order::where('waybill_number', $waybill)
                        ->where('courier_id', $courier->id)
                        ->first();
                    
                    if ($order) {
                        $foundOrders[] = [
                            'waybill_number' => $order->waybill_number,
                            'order_no' => $order->id,
                            'customer_name' => $order->customer_name,
                            'destination' => $order->city->name ?? $order->customer_city,
                            'delivery_fee' => $order->delivery_fee,
                            'amount' => $order->total_amount,
                            'id' => $order->id
                        ];
                    }
                }
            }
            
            return response()->json(['success' => true, 'data' => $foundOrders]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Store the payment and update orders.
     */
    public function store(Request $request, Courier $courier)
    {
        $request->validate([
            'payment_date' => 'required|date',
            'payment_account_id' => 'required|exists:bank_accounts,id',
            'order_ids' => 'required|array',
            'order_ids.*' => 'exists:orders,id',
        ]);

        try {
            DB::transaction(function() use ($request, $courier) {
                $selectedAccount = BankAccount::findOrFail($request->payment_account_id);

                // 1. Calculate Total Amount from Orders
                // Note: In a real scenario, we might want to sum up specific fields or use a user-provided total.
                // Here we sum 'total_amount' of the selected orders.
                $totalAmount = Order::whereIn('id', $request->order_ids)->sum('total_amount');

                // 2. Create Payment Record
                $payment = CourierPayment::create([
                    'courier_id' => $courier->id,
                    'user_id' => auth()->id(),
                    'amount' => $totalAmount,
                    'payment_date' => $request->payment_date,
                    'payment_method' => $selectedAccount->display_label,
                    'bank_account_id' => $selectedAccount->id,
                    'reference_number' => null, // Or generated
                    'payment_note' => 'Received via Receive Courier Payment module',
                ]);

                // 3. Update Orders
                Order::whereIn('id', $request->order_ids)->update([
                    'courier_payment_id' => $payment->id,
                    'payment_status' => 'Paid', // Assuming receiving payment means order is Paid/Reconciled
                ]);
            });

            return redirect()->route('courier-receive.index')->with('success', 'Payment received and orders updated successfully.');

        } catch (\Exception $e) {
            return back()->with('error', 'Error processing payment: ' . $e->getMessage());
        }
    }
}
