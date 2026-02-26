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
        $orders = Order::where('status', 'confirm')
                       ->orderBy('created_at', 'asc')
                       ->get();
        return view('orders.packing.index', compact('orders'));
    }
    
    /**
     * Packing Interface for a specific order (Scanner UI).
     */
    public function process($id)
    {
        $order = Order::with('items')->findOrFail($id);
        return view('orders.packing.process', compact('order'));
    }
    
    /**
     * Mark as packed.
     */
    public function markPacked(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        $order->status = 'confirm';
        $order->packed_by = Auth::id();
        $order->dispatched_at = now();
        $order->save();
        
        OrderLog::create([
            'order_id' => $order->id,
            'user_id' => Auth::id(),
            'action' => 'packed_confirm',
            'description' => 'Order packed and marked confirm.',
        ]);
        
        return redirect()->route('orders.packing.index')->with('success', 'Order packed successfully.');
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
