<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Order;
use App\Models\Courier;
use App\Models\CourierWaybill;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
                    $this->applyPrintableOrderConstraints($query);
                },
                'waybills as available_waybills_count' => function ($query) {
                    $query->whereNull('order_id');
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
        $availableWaybillsCount = $courier->waybills()->available()->count();
        if ($availableWaybillsCount < 1) {
            return redirect()
                ->route('orders.waybill.index')
                ->with('error', "Add waybill IDs for {$courier->name} before opening the print queue.");
        }

        $perPage = in_array((int) $request->input('per_page'), [25, 50, 100], true)
            ? (int) $request->input('per_page')
            : 25;

        $ordersQuery = Order::query()
            ->with('customer')
            ->where('courier_id', $courier->id);

        $this->applyPrintableOrderConstraints($ordersQuery);

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
            ->where('call_status', 'confirm');

        $stats = [
            'eligible' => (clone $statsBaseQuery)
                ->where('delivery_status', 'pending')
                ->where(function ($waybillQuery) {
                    $waybillQuery->whereNull('waybill_number')
                        ->orWhere('waybill_number', '');
                })
                ->count(),
            'confirm_total' => (clone $statsBaseQuery)->where('delivery_status', 'pending')->count(),
            'with_waybill' => (clone $statsBaseQuery)
                ->where(function ($waybillQuery) {
                    $waybillQuery->whereNotNull('waybill_number')
                        ->where('waybill_number', '!=', '');
                })
                ->count(),
            'available_waybills' => $availableWaybillsCount,
            'waybill_shortfall' => max(((clone $statsBaseQuery)->where('delivery_status', 'pending')->count()) - $availableWaybillsCount, 0),
            'next_available_waybill' => $courier->waybills()->available()->orderBy('id')->value('code'),
        ];

        return view('orders.waybill.show', compact('courier', 'orders', 'stats'));
    }

    /**
     * Print selected waybills
     */
    public function print(Request $request)
    {
        $validated = $request->validate([
            'courier_id' => 'required|exists:couriers,id',
            'paper_size' => 'required|in:a4,four_by_six',
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'integer|exists:orders,id',
        ]);

        $orderIds = collect($request->input('order_ids', []))
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
        $courierId = (int) $validated['courier_id'];
        $paperSize = (string) $validated['paper_size'];

        try {
            $orders = $this->allocateWaybills($courierId, $orderIds);
        } catch (ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first() ?: 'Unable to print waybills.');
        }

        return $this->streamWaybillPdf($orders, $paperSize);
    }

    /**
     * Reprint an existing waybill without allocating a new waybill ID
     */
    public function reprint(Request $request, Order $order)
    {
        $validated = $request->validate([
            'paper_size' => 'required|in:a4,four_by_six',
        ]);

        $order->loadMissing([
            'items',
            'city',
            'customer',
            'courier',
        ]);

        if (blank($order->waybill_number)) {
            abort(404, 'This order does not have a saved waybill to reprint.');
        }

        return $this->streamWaybillPdf(collect([$order]), (string) $validated['paper_size'], 'waybill_reprint');
    }

    /**
     * Reprint multiple existing waybills without allocating new waybill IDs
     */
    public function bulkReprint(Request $request)
    {
        $validated = $request->validate([
            'paper_size' => 'required|in:a4,four_by_six',
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'integer|exists:orders,id',
        ]);

        $orderIds = collect($request->input('order_ids', []))
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $orders = Order::query()
            ->whereIn('id', $orderIds)
            ->whereNotNull('waybill_number')
            ->where('waybill_number', '!=', '')
            ->with([
                'items',
                'city',
                'customer',
                'courier',
            ])
            ->get()
            ->sortBy(fn (Order $order) => $orderIds->search($order->id))
            ->values();

        if ($orders->count() !== $orderIds->count()) {
            throw ValidationException::withMessages([
                'order_ids' => 'Only orders with existing saved waybills can be reprinted.',
            ]);
        }

        return $this->streamWaybillPdf($orders, (string) $validated['paper_size'], 'waybill_reprint');
    }

    private function applyPrintableOrderConstraints($query): void
    {
        $query->where('call_status', 'confirm')
            ->where('delivery_status', 'pending')
            ->where(function ($waybillQuery) {
                $waybillQuery->whereNull('waybill_number')
                    ->orWhere('waybill_number', '');
            });
    }

    private function allocateWaybills(int $courierId, Collection $orderIds): Collection
    {
        return DB::transaction(function () use ($courierId, $orderIds) {
            $ordersQuery = Order::query()
                ->where('courier_id', $courierId)
                ->whereIn('id', $orderIds)
                ->with([
                    'items',
                    'city',
                    'customer',
                    'courier',
                ])
                ->lockForUpdate();

            $this->applyPrintableOrderConstraints($ordersQuery);

            $orders = $ordersQuery->get()
                ->sortBy(fn (Order $order) => $orderIds->search($order->id))
                ->values();

            if ($orders->count() !== $orderIds->count()) {
                throw ValidationException::withMessages([
                    'order_ids' => 'Only call-confirmed orders with pending delivery and no waybill numbers can be printed.',
                ]);
            }

            $availableWaybills = CourierWaybill::query()
                ->where('courier_id', $courierId)
                ->available()
                ->orderBy('id')
                ->lockForUpdate()
                ->limit($orderIds->count())
                ->get()
                ->values();

            if ($availableWaybills->count() < $orderIds->count()) {
                throw ValidationException::withMessages([
                    'order_ids' => 'Not enough available waybill IDs for this courier. Add more waybill IDs before printing.',
                ]);
            }

            $timestamp = now();

            foreach ($orders as $index => $order) {
                $waybill = $availableWaybills[$index];

                $order->waybill_number = $waybill->code;
                $order->delivery_status = 'waybill_printed';
                if (! $order->waybill_printed_at) {
                    $order->waybill_printed_at = $timestamp;
                }
                $order->save();

                $waybill->order_id = $order->id;
                $waybill->allocated_at = $timestamp;
                $waybill->save();
            }

            return $orders;
        });
    }

    private function streamWaybillPdf(Collection $orders, string $paperSize, string $filePrefix = 'waybills')
    {
        $view = $paperSize === 'four_by_six'
            ? 'orders.waybill.pdf-4x6'
            : 'orders.waybill.pdf-a4';

        $pdf = Pdf::loadView($view, [
            'orders' => $orders,
            'generatedAt' => now(),
        ]);

        if ($paperSize === 'four_by_six') {
            $pdf->setPaper([0, 0, 288, 432], 'portrait');
        } else {
            $pdf->setPaper('a4', 'portrait');
        }

        return $pdf->download($filePrefix . '_' . $paperSize . '_' . now()->format('Ymd_His') . '.pdf');
    }
}
