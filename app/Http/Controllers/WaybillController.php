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
        $couriers = Courier::query()
            ->where('is_active', true)
            ->withCount([
                'orders as printable_orders_count' => function ($query) {
                    $query->where('status', 'confirm')
                        ->where(function ($waybillQuery) {
                            $waybillQuery->whereNull('waybill_number')
                                ->orWhere('waybill_number', '');
                        });
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
        $perPage = in_array((int) $request->input('per_page'), [25, 50, 100], true)
            ? (int) $request->input('per_page')
            : 25;

        $ordersQuery = Order::query()
            ->with('customer')
            ->where('courier_id', $courier->id)
            ->where('status', 'confirm')
            ->where(function ($waybillQuery) {
                $waybillQuery->whereNull('waybill_number')
                    ->orWhere('waybill_number', '');
            });

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
            ->where('status', 'confirm');

        $stats = [
            'eligible' => (clone $statsBaseQuery)
                ->where(function ($waybillQuery) {
                    $waybillQuery->whereNull('waybill_number')
                        ->orWhere('waybill_number', '');
                })
                ->count(),
            'confirm_total' => (clone $statsBaseQuery)->count(),
            'with_waybill' => (clone $statsBaseQuery)
                ->where(function ($waybillQuery) {
                    $waybillQuery->whereNotNull('waybill_number')
                        ->where('waybill_number', '!=', '');
                })
                ->count(),
        ];

        return view('orders.waybill.show', compact('courier', 'orders', 'stats'));
    }

    /**
     * Print selected waybills
     */
    public function print(Request $request)
    {
        $orderIds = collect($request->input('order_ids', []))
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($orderIds->isEmpty()) {
            return back()->with('error', 'No orders selected.');
        }

        $orders = Order::query()
            ->whereIn('id', $orderIds)
            ->where('status', 'confirm')
            ->where(function ($waybillQuery) {
                $waybillQuery->whereNull('waybill_number')
                    ->orWhere('waybill_number', '');
            })
            ->with('items', 'city', 'customer')
            ->get();

        if ($orders->isEmpty()) {
            return back()->with('error', 'Only confirmed orders without waybill numbers can be printed.');
        }

        // Generate waybill numbers before rendering print.
        foreach ($orders as $order) {
            if (!$order->waybill_number) {
                $order->waybill_number = 'WB-' . $order->order_number;
            }
            $order->delivery_status = 'waybill_printed';
            $order->save();
        }

        return view('orders.waybill.print', compact('orders'));
    }

    private function printableStatuses(): array
    {
        return ['confirm'];
    }
}
