<?php

namespace App\Http\Controllers;

use App\Models\Courier;
use App\Models\Order;
use App\Models\CourierPayment;
use App\Models\BankAccount;
use App\Services\CourierPaymentOrderService;
use App\Support\CourierSettlement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CourierReceiveController extends Controller
{
    public function __construct(
        private readonly CourierPaymentOrderService $courierPaymentOrders
    ) {
    }

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
    public function show(Request $request, Courier $courier)
    {
        $orders = $this->eligibleOrdersQuery($courier)
            ->latest('dispatched_at')
            ->take(50)
            ->get();

        $bankAccounts = BankAccount::where('is_active', true)
            ->orderBy('name')
            ->get();

        $initialRows = collect();
        $oldOrderIds = $this->normalizeRequestedOrderIds((array) $request->old('order_ids', []));
        $oldCourierCosts = (array) $request->old('courier_costs', []);

        if (!empty($oldOrderIds)) {
            $initialRows = Order::query()
                ->whereIn('id', $oldOrderIds)
                ->get()
                ->map(function (Order $order) use ($oldCourierCosts) {
                    $override = array_key_exists($order->id, $oldCourierCosts)
                        ? (float) $oldCourierCosts[$order->id]
                        : null;

                    return CourierSettlement::serializeOrder($order, $override);
                })
                ->values();
        }

        return view('couriers.receive.show', compact('courier', 'orders', 'bankAccounts', 'initialRows'));
    }

    /**
     * Search for an order by Waybill/Order No.
     */
    public function searchOrder(Request $request) 
    {
        $query = trim((string) $request->get('query'));
        $courierId = $request->integer('courier_id');

        if ($query === '' || !$courierId) {
            return response()->json(['success' => false, 'message' => 'Enter a valid waybill or order number.']);
        }

        $order = $this->eligibleOrdersQuery(Courier::findOrFail($courierId))
            ->where(function (Builder $builder) use ($query) {
                $builder->where('waybill_number', $query)
                    ->orWhere('order_number', $query);

                if (ctype_digit($query)) {
                    $builder->orWhere('id', (int) $query);
                }
            })
            ->first();

        if ($order) {
            return response()->json([
                'success' => true,
                'data' => CourierSettlement::serializeOrder($order),
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
                    $order = $this->eligibleOrdersQuery($courier)
                        ->where(function (Builder $builder) use ($waybill) {
                            $builder->where('waybill_number', $waybill)
                                ->orWhere('order_number', $waybill);

                            if (ctype_digit((string) $waybill)) {
                                $builder->orWhere('id', (int) $waybill);
                            }
                        })
                        ->first();
                    
                    if ($order) {
                        $foundOrders[] = CourierSettlement::serializeOrder($order);
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
            'payment_account_id' => 'required|exists:bank_accounts,id',
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'integer|distinct|exists:orders,id',
            'courier_costs' => 'required|array',
            'courier_costs.*' => 'nullable|numeric|min:0',
            'expected_amount' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::transaction(function() use ($request, $courier) {
                $selectedAccount = BankAccount::where('is_active', true)
                    ->findOrFail($request->payment_account_id);
                $paymentDate = now()->toDateString();
                $orderIds = $this->normalizeRequestedOrderIds($request->input('order_ids', []));

                $eligibleOrders = $this->eligibleOrdersQuery($courier)
                    ->whereIn('id', $orderIds)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                if ($eligibleOrders->count() !== count($orderIds)) {
                    throw ValidationException::withMessages([
                        'order_ids' => 'One or more selected orders are invalid for this courier or already reconciled.',
                    ]);
                }

                $resolvedCosts = [];
                $totalAmount = 0.0;

                foreach ($orderIds as $orderId) {
                    /** @var Order $order */
                    $order = $eligibleOrders->get($orderId);
                    $realCharge = $this->resolveRealChargeFromRequest($request, $order);
                    $this->assertRealChargeIsValid($order, $realCharge);

                    if (strtolower((string) $order->delivery_status) !== 'dispatched') {
                        throw ValidationException::withMessages([
                            'order_ids' => 'Only dispatched orders can be marked as delivered through courier payments.',
                        ]);
                    }

                    $resolvedCosts[$orderId] = $realCharge;
                    $totalAmount += CourierSettlement::receivedAmount($order, $realCharge);
                }

                $totalAmount = round($totalAmount, 2);
                $this->assertExpectedAmountMatches($request, $totalAmount);

                $payment = CourierPayment::create([
                    'courier_id' => $courier->id,
                    'user_id' => auth()->id(),
                    'amount' => $totalAmount,
                    'payment_date' => $paymentDate,
                    'payment_method' => $selectedAccount->display_label,
                    'bank_account_id' => $selectedAccount->id,
                    'reference_number' => null, // Or generated
                    'payment_note' => 'Received via Receive Courier Payment module',
                ]);

                foreach ($orderIds as $orderId) {
                    /** @var Order $order */
                    $order = $eligibleOrders->get($orderId);
                    $this->courierPaymentOrders->attachOrderToPayment(
                        $order,
                        $payment,
                        $resolvedCosts[$orderId],
                        auth()->id()
                    );
                }
            });

            return redirect()->route('courier-receive.index')->with('success', 'Payment received and orders updated successfully.');

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return back()->with('error', 'Error processing payment: ' . $e->getMessage());
        }
    }

    private function eligibleOrdersQuery(Courier $courier)
    {
        return Order::query()
            ->where('courier_id', $courier->id)
            ->where('call_status', 'confirm')
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

    private function assertExpectedAmountMatches(Request $request, float $computedAmount): void
    {
        if (!$request->filled('expected_amount')) {
            return;
        }

        if (abs(((float) $request->input('expected_amount')) - $computedAmount) > 0.01) {
            throw ValidationException::withMessages([
                'expected_amount' => 'Received amount total does not match the selected orders.',
            ]);
        }
    }
}
