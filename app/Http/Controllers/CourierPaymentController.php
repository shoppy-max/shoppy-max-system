<?php

namespace App\Http\Controllers;

use App\Models\Courier;
use App\Models\CourierPayment;
use App\Models\Order;
use App\Services\CourierPaymentOrderService;
use App\Support\CourierSettlement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CourierPaymentController extends Controller
{
    public function __construct(
        private readonly CourierPaymentOrderService $courierPaymentOrders
    ) {
    }

    /**
     * Display a listing of courier payments.
     */
    public function index(Request $request)
    {
        $query = CourierPayment::with(['courier', 'bankAccount'])->withCount('orders');

        if ($request->filled('search')) {
            $search = trim((string) $request->search);

            $query->where(function (Builder $builder) use ($search) {
                $builder->whereHas('courier', function (Builder $courierQuery) use ($search) {
                    $courierQuery->where('name', 'like', "%{$search}%");
                })->orWhereHas('orders', function (Builder $orderQuery) use ($search) {
                    $orderQuery->where('order_number', 'like', "{$search}%")
                        ->orWhere('waybill_number', 'like', "{$search}%");

                    if (ctype_digit($search)) {
                        $orderQuery->orWhere('id', (int) $search);
                    }
                })->orWhere('reference_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('date')) {
            $query->whereDate('payment_date', $request->date);
        } else {
            if ($request->filled('start_date')) {
                $query->whereDate('payment_date', '>=', $request->start_date);
            }
            if ($request->filled('end_date')) {
                $query->whereDate('payment_date', '<=', $request->end_date);
            }
        }

        $payments = $query->latest('payment_date')->latest('id')->paginate(20)->withQueryString();
        
        return view('courier-payments.index', compact('payments'));
    }

    /**
     * Show the form for editing the specified courier payment.
     */
    public function edit(Request $request, CourierPayment $courierPayment)
    {
        $courierPayment->load(['courier', 'orders' => function ($query) {
            $query->latest('id');
        }]);

        $couriers = Courier::where('is_active', true)->orderBy('name')->get();
        $oldOrderIds = $this->normalizeRequestedOrderIds((array) $request->old('order_ids', []));
        $oldCourierCosts = (array) $request->old('courier_costs', []);

        if (!empty($oldOrderIds)) {
            $linkedOrders = Order::query()
                ->whereIn('id', $oldOrderIds)
                ->get()
                ->map(function (Order $order) use ($oldCourierCosts) {
                    $override = array_key_exists($order->id, $oldCourierCosts)
                        ? (float) $oldCourierCosts[$order->id]
                        : null;

                    return CourierSettlement::serializeOrder($order, $override);
                })
                ->values();
        } else {
            $linkedOrders = $courierPayment->orders
                ->map(fn (Order $order) => CourierSettlement::serializeOrder($order))
                ->values();
        }

        return view('courier-payments.edit', compact('courierPayment', 'couriers', 'linkedOrders'));
    }

    /**
     * Update the specified courier payment.
     */
    public function update(Request $request, CourierPayment $courierPayment)
    {
        $validated = $request->validate([
            'courier_id' => 'required|exists:couriers,id',
            'amount' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string',
            'reference_number' => 'nullable|string|max:255',
            'payment_note' => 'nullable|string',
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'integer|distinct|exists:orders,id',
            'courier_costs' => 'required|array',
            'courier_costs.*' => 'nullable|numeric|min:0',
        ]);

        if (
            (int) $validated['courier_id'] !== (int) $courierPayment->courier_id
            && $courierPayment->orders()->exists()
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'courier_id' => 'Courier cannot be changed while this payment is linked to orders.',
                ]);
        }

        DB::transaction(function () use ($request, $validated, $courierPayment) {
            $orderIds = $this->normalizeRequestedOrderIds($validated['order_ids']);

            $currentOrders = $courierPayment->orders()
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $requestedOrders = Order::query()
                ->whereIn('id', $orderIds)
                ->where(function (Builder $builder) use ($courierPayment, $validated) {
                    $builder->where('courier_payment_id', $courierPayment->id)
                        ->orWhere(function (Builder $eligibleBuilder) use ($validated) {
                            $this->applyEligibleCourierOrderConstraints($eligibleBuilder, (int) $validated['courier_id']);
                        });
                })
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($requestedOrders->count() !== count($orderIds)) {
                throw ValidationException::withMessages([
                    'order_ids' => 'One or more selected orders are invalid, already reconciled, or not eligible for this courier.',
                ]);
            }

            $resolvedCosts = [];
            $computedAmount = 0.0;

            foreach ($orderIds as $orderId) {
                /** @var Order $order */
                $order = $requestedOrders->get($orderId);
                $realCharge = $this->resolveRealChargeFromRequest($request, $order);
                $this->assertRealChargeIsValid($order, $realCharge);

                if (
                    (int) ($order->courier_payment_id ?? 0) !== (int) $courierPayment->id
                    && strtolower((string) $order->delivery_status) !== 'dispatched'
                ) {
                    throw ValidationException::withMessages([
                        'order_ids' => 'Only dispatched orders can be marked as delivered through courier payments.',
                    ]);
                }

                $resolvedCosts[$orderId] = $realCharge;
                $computedAmount += CourierSettlement::receivedAmount($order, $realCharge);
            }

            $computedAmount = round($computedAmount, 2);

            if ($request->filled('amount') && abs(((float) $request->input('amount')) - $computedAmount) > 0.01) {
                throw ValidationException::withMessages([
                    'amount' => 'Received amount total does not match the selected orders.',
                ]);
            }

            $courierPayment->update([
                'courier_id' => $validated['courier_id'],
                'amount' => $computedAmount,
                'payment_method' => $validated['payment_method'] ?? $courierPayment->payment_method,
                'reference_number' => $validated['reference_number'] ?? null,
                'payment_note' => $validated['payment_note'] ?? null,
            ]);

            $removedOrders = $currentOrders->except($orderIds);
            foreach ($removedOrders as $order) {
                $this->courierPaymentOrders->detachOrderFromPayment(
                    $order,
                    auth()->id(),
                    $courierPayment->id
                );
            }

            foreach ($orderIds as $orderId) {
                /** @var Order $order */
                $order = $requestedOrders->get($orderId);
                $this->courierPaymentOrders->attachOrderToPayment(
                    $order,
                    $courierPayment,
                    $resolvedCosts[$orderId],
                    auth()->id()
                );
            }
        });

        return redirect()->route('courier-payments.index')
            ->with('success', 'Courier payment updated successfully.');
    }

    /**
     * Remove the specified courier payment.
     */
    public function destroy(CourierPayment $courierPayment)
    {
        DB::transaction(function () use ($courierPayment) {
            $orders = $courierPayment->orders()
                ->lockForUpdate()
                ->get();

            foreach ($orders as $order) {
                $this->courierPaymentOrders->detachOrderFromPayment(
                    $order,
                    auth()->id(),
                    $courierPayment->id
                );
            }

            $courierPayment->delete();
        });

        return redirect()->route('courier-payments.index')
            ->with('success', 'Courier payment deleted successfully.');
    }

    /**
     * Display the specified courier payment.
     */
    public function show(CourierPayment $courierPayment)
    {
        $courierPayment->load(['courier', 'orders' => function ($query) {
            $query->latest('id');
        }]);

        return view('courier-payments.show', compact('courierPayment'));
    }

    private function applyEligibleCourierOrderConstraints(Builder $query, int $courierId): void
    {
        $query->where('courier_id', $courierId)
            ->where('status', 'confirm')
            ->where('payment_method', 'COD')
            ->where('delivery_status', 'dispatched')
            ->whereNotNull('waybill_number')
            ->where('waybill_number', '!=', '')
            ->whereNull('courier_payment_id');
    }

    private function normalizeRequestedOrderIds(array $orderIds): array
    {
        return collect($orderIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function resolveRealChargeFromRequest(Request $request, Order $order): float
    {
        $rawValue = $request->input('courier_costs.' . $order->id);
        if ($rawValue === null || $rawValue === '') {
            return CourierSettlement::defaultRealDeliveryCharge($order);
        }

        return round((float) $rawValue, 2);
    }

    private function assertRealChargeIsValid(Order $order, float $realCharge): void
    {
        if ($realCharge > (float) ($order->total_amount ?? 0)) {
            throw ValidationException::withMessages([
                'courier_costs.' . $order->id => 'Real delivery charge cannot exceed the order amount.',
            ]);
        }
    }

}
