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
        $printableStatuses = $this->printableStatuses();

        $couriers = Courier::query()
            ->where('is_active', true)
            ->withCount([
                'orders as printable_orders_count' => function ($query) use ($printableStatuses) {
                    $query->whereIn('status', $printableStatuses);
                },
            ])
            ->orderBy('name')
            ->get();

        return view('orders.waybill.index', compact('couriers'));
    }
    
    /**
     * Show orders for a specific courier
     */
    public function show(Request $request, Courier $courier)
    {
        $printableStatuses = $this->printableStatuses();
        $perPage = in_array((int) $request->input('per_page'), [25, 50, 100], true)
            ? (int) $request->input('per_page')
            : 25;

        $ordersQuery = Order::query()
            ->with('customer')
            ->where('courier_id', $courier->id)
            ->whereIn('status', $printableStatuses);

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));

            $ordersQuery->where(function ($query) use ($search) {
                $query->where('order_number', 'like', "%{$search}%")
                    ->orWhere('waybill_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($customerQuery) use ($search) {
                        $customerQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('mobile', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status') && in_array($request->status, $printableStatuses, true)) {
            $ordersQuery->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $ordersQuery->whereDate('order_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $ordersQuery->whereDate('order_date', '<=', $request->date_to);
        }

        $orders = $ordersQuery
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $statsBaseQuery = Order::query()
            ->where('courier_id', $courier->id)
            ->whereIn('status', $printableStatuses);

        $stats = [
            'total' => (clone $statsBaseQuery)->count(),
            'pending' => (clone $statsBaseQuery)->where('status', 'pending')->count(),
            'hold' => (clone $statsBaseQuery)->where('status', 'hold')->count(),
            'confirm' => (clone $statsBaseQuery)->where('status', 'confirm')->count(),
            'with_waybill' => (clone $statsBaseQuery)->whereNotNull('waybill_number')->count(),
        ];

        return view('orders.waybill.show', compact('courier', 'orders', 'stats'));
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

    private function printableStatuses(): array
    {
        return ['pending', 'hold', 'confirm'];
    }
}
