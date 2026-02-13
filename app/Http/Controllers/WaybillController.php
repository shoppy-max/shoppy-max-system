<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Courier;
use Illuminate\Http\Request;

class WaybillController extends Controller
{
    /**
     * Show Waybill Print Selection - List of Couriers
     */
    public function index()
    {
        $couriers = Courier::where('is_active', true)->get();
        return view('orders.waybill.index', compact('couriers'));
    }
    
    /**
     * Show orders for a specific courier
     */
    public function show(Courier $courier)
    {
        $orders = Order::where('courier_id', $courier->id)
            ->whereIn('status', ['confirmed', 'processing', 'pending']) // Adjustable based on workflow
            ->latest()
            ->paginate(50);
            
        return view('orders.waybill.show', compact('courier', 'orders'));
    }

    /**
     * Print selected waybills
     */
    public function print(Request $request)
    {
        $orderIds = $request->input('order_ids', []);
        
        if (empty($orderIds)) {
            return back()->with('error', 'No orders selected.');
        }
        
        $orders = Order::whereIn('id', $orderIds)->with('items', 'city', 'customer')->get();
        
        // Generate unique waybill numbers if missing
        foreach ($orders as $order) {
            if (!$order->waybill_number) {
                 $order->waybill_number = 'WB-' . $order->order_number; // Logic to be refined if needed
                 $order->save();
            }
        }
        
        return view('orders.waybill.print', compact('orders'));
    }
}

