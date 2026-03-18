<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PackingController extends Controller
{
    /**
     * List orders for packing.
     */
    public function index()
    {
        $orders = Order::where('call_status', 'confirm')
                       ->whereIn('delivery_status', ['waybill_printed', 'picked_from_rack', 'packed'])
                       ->orderBy('created_at', 'asc')
                       ->with('items')
                       ->get();
        return view('orders.packing.index', compact('orders'));
    }
    
    /**
     * Packing Interface for a specific order (Scanner UI).
     */
    public function process($id)
    {
        $order = Order::with('items')->findOrFail($id);

        if ((string) ($order->call_status ?? '') !== 'confirm' || !in_array((string) ($order->delivery_status ?? ''), ['waybill_printed', 'picked_from_rack'], true)) {
            return redirect()->route('orders.packing.index')->with('error', 'Only call-confirmed orders in the picking queue can be packed.');
        }

        return view('orders.packing.process', compact('order'));
    }

    public function markPicked(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        if ((string) ($order->call_status ?? '') !== 'confirm' || (string) ($order->delivery_status ?? '') !== 'waybill_printed') {
            return response()->json([
                'success' => false,
                'message' => 'Only call-confirmed waybill-printed orders can move to Picked From Rack.',
            ], 422);
        }

        $order->delivery_status = 'picked_from_rack';
        $order->status = $order->call_status === 'hold' ? 'hold' : 'confirm';
        if (!$order->picked_at) {
            $order->picked_at = now();
        }
        $order->save();

        OrderLog::create([
            'order_id' => $order->id,
            'user_id' => Auth::id(),
            'action' => 'picked_from_rack',
            'description' => 'Order moved to picked from rack.',
        ]);

        return response()->json([
            'success' => true,
            'delivery_status' => $order->delivery_status,
        ]);
    }
    
    /**
     * Mark as packed.
     */
    public function markPacked(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        if ((string) ($order->call_status ?? '') !== 'confirm' || (string) ($order->delivery_status ?? '') !== 'picked_from_rack') {
            return redirect()->route('orders.packing.index')->with('error', 'Only picked orders can be marked as packed.');
        }

        $order->packed_by = Auth::id();
        $order->delivery_status = 'packed';
        $order->status = $order->call_status === 'hold' ? 'hold' : 'confirm';
        if (!$order->packed_at) {
            $order->packed_at = now();
        }
        $order->save();
        
        OrderLog::create([
            'order_id' => $order->id,
            'user_id' => Auth::id(),
            'action' => 'packed_confirm',
            'description' => 'Order packed and marked confirm.',
        ]);
        
        return redirect()->route('orders.packing.index')->with('success', 'Order packed successfully.');
    }

    public function markDispatched(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        if ((string) ($order->call_status ?? '') !== 'confirm' || (string) ($order->delivery_status ?? '') !== 'packed') {
            return redirect()->route('orders.packing.index')->with('error', 'Only packed orders can be marked as dispatched.');
        }

        $order->delivery_status = 'dispatched';
        $order->status = $order->call_status === 'hold' ? 'hold' : 'confirm';
        if (!$order->dispatched_at) {
            $order->dispatched_at = now();
        }
        $order->save();

        OrderLog::create([
            'order_id' => $order->id,
            'user_id' => Auth::id(),
            'action' => 'marked_dispatched',
            'description' => 'Order marked as dispatched after packing.',
        ]);

        return redirect()->route('orders.packing.index')->with('success', 'Order marked as dispatched.');
    }
    
    /**
     * Create Packing Batch (Placeholder).
     */
    public function createBatch(Request $request)
    {
        // Batch logic to be implemented
        return back()->with('info', 'Batch creation feature coming soon.');
    }
}
