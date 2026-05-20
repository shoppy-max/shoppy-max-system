<?php

namespace App\Http\Controllers;

use App\Models\InventoryUnit;
use App\Models\Order;
use App\Models\OrderLog;
use App\Models\StoreRack;
use App\Services\InventoryUnitService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PackingController extends Controller
{
    private const STAGES = [
        'ready' => [
            'status' => 'waybill_printed',
            'title' => 'Ready To Pick',
            'description' => 'Create the pick GRN with rack locations before scanner picking starts.',
            'empty' => 'No waybill printed orders are waiting to be picked.',
            'sort' => 'waybill_printed_at',
        ],
        'picking' => [
            'status' => 'picked_from_rack',
            'title' => 'Picking',
            'description' => 'Orders currently being scanned from rack labels.',
            'empty' => 'No orders are currently in picking.',
            'sort' => 'picked_at',
        ],
        'packed' => [
            'status' => 'packed',
            'title' => 'Packed',
            'description' => 'Orders fully scanned and ready to dispatch.',
            'empty' => 'No packed orders are waiting for dispatch.',
            'sort' => 'packed_at',
        ],
        'dispatched' => [
            'status' => 'dispatched',
            'title' => 'Dispatched',
            'description' => 'Orders handed to the courier and waiting to be marked delivered.',
            'empty' => 'No dispatched orders are waiting for delivery confirmation.',
            'sort' => 'dispatched_at',
        ],
    ];

    public function __construct(
        private readonly InventoryUnitService $inventoryUnits
    ) {}

    /**
     * List orders for packing.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $route = match (true) {
            $user?->can('view ready to pick orders') => 'orders.packing.ready',
            $user?->can('view picking orders') => 'orders.packing.picking',
            $user?->can('view packed orders') => 'orders.packing.packed',
            $user?->can('view dispatched orders') => 'orders.packing.dispatched',
            default => null,
        };

        abort_unless($route, 403);

        return redirect()->route($route, $request->query());
    }

    public function ready(Request $request)
    {
        return $this->queue($request, 'ready');
    }

    public function picking(Request $request)
    {
        return $this->queue($request, 'picking');
    }

    public function packed(Request $request)
    {
        return $this->queue($request, 'packed');
    }

    public function dispatched(Request $request)
    {
        return $this->queue($request, 'dispatched');
    }

    private function queue(Request $request, string $stage)
    {
        $perPage = in_array((int) $request->input('per_page'), [15, 25, 50, 100], true)
            ? (int) $request->input('per_page')
            : 25;
        $stageConfig = self::STAGES[$stage] ?? self::STAGES['ready'];

        $orders = $this->buildPackingQueueQuery($request, $stageConfig['status'])
            ->with(['customer', 'courier', 'items.inventoryUnits.purchase', 'items.inventoryUnits.storeRack'])
            ->orderBy($stageConfig['sort'] ?? 'waybill_printed_at')
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();

        $orders->setCollection(
            $orders->getCollection()->map(function (Order $order) {
                $order->packing_summary = $this->buildPackingSummary($order);
                $order->pick_grn_modal_payload = $order->pick_grn_number
                    ? $this->buildPickGrnModalPayload($order, $order->packing_summary)
                    : null;

                return $order;
            })
        );

        $statsBaseQuery = Order::query()
            ->where('call_status', 'confirm')
            ->whereIn('delivery_status', ['waybill_printed', 'picked_from_rack', 'packed', 'dispatched']);

        $stats = [
            'total' => (clone $statsBaseQuery)->count(),
            'waybill_printed' => (clone $statsBaseQuery)->where('delivery_status', 'waybill_printed')->count(),
            'picked_from_rack' => (clone $statsBaseQuery)->where('delivery_status', 'picked_from_rack')->count(),
            'packed' => (clone $statsBaseQuery)->where('delivery_status', 'packed')->count(),
            'dispatched' => (clone $statsBaseQuery)->where('delivery_status', 'dispatched')->count(),
        ];

        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'per_page' => $perPage,
        ];
        $stageRoutes = [
            'ready' => route('orders.packing.ready', request()->except(['page'])),
            'picking' => route('orders.packing.picking', request()->except(['page'])),
            'packed' => route('orders.packing.packed', request()->except(['page'])),
            'dispatched' => route('orders.packing.dispatched', request()->except(['page'])),
        ];

        return view('orders.packing.index', compact('orders', 'stats', 'filters', 'stage', 'stageConfig', 'stageRoutes'));
    }

    /**
     * Packing Interface for a specific order (Scanner UI).
     */
    public function pickGrn($id)
    {
        $order = Order::with([
            'customer',
            'courier',
            'items.inventoryUnits.purchase',
            'items.inventoryUnits.storeRack',
            'pickGrnCreator',
        ])->findOrFail($id);

        if ((string) ($order->call_status ?? '') !== 'confirm' || ! in_array((string) ($order->delivery_status ?? ''), ['picked_from_rack', 'packed', 'dispatched', 'delivered'], true)) {
            return redirect()->route('orders.packing.ready')->with('error', 'Create the pick GRN before viewing it.');
        }

        if (blank($order->pick_grn_number)) {
            return redirect()->route('orders.packing.ready')->with('error', 'Pick GRN number is missing. Create the pick GRN again.');
        }

        $packingSummary = $this->buildPackingSummary($order);

        return view('orders.packing.pick-grn', compact('order', 'packingSummary'));
    }

    public function process($id)
    {
        $order = Order::with(['items.inventoryUnits' => function ($query) {
            $query->where(function ($unitQuery) {
                $unitQuery->where('status', InventoryUnit::STATUS_ALLOCATED)
                    ->orWhereNotNull('packed_scan_at');
            })
                ->with(['purchase', 'storeRack'])
                ->orderBy('id');
        }])->findOrFail($id);

        if ((string) ($order->call_status ?? '') !== 'confirm' || (string) ($order->delivery_status ?? '') !== 'picked_from_rack') {
            return redirect()->route('orders.packing.ready')->with('error', 'Only call-confirmed orders in the picking queue can be packed.');
        }

        $packingSummary = $this->buildPackingSummary($order);

        return view('orders.packing.process', compact('order', 'packingSummary'));
    }

    public function scan(Request $request, $id)
    {
        $validated = $request->validate([
            'unit_code' => 'required|string|max:255',
        ]);

        $order = Order::with(['items.inventoryUnits'])->findOrFail($id);

        if ((string) ($order->call_status ?? '') !== 'confirm' || (string) ($order->delivery_status ?? '') !== 'picked_from_rack') {
            return response()->json([
                'success' => false,
                'message' => 'Only call-confirmed orders in the picking queue can be scanned.',
            ], 422);
        }

        DB::beginTransaction();

        try {
            $result = $this->inventoryUnits->scanOrderUnitForPacking($order, $validated['unit_code'], Auth::id());
            $summary = $this->buildPackingSummary($order->fresh(['items.inventoryUnits.purchase', 'items.inventoryUnits.storeRack']));
            $autoPacked = false;

            if (($summary['all_scanned'] ?? false) && (string) ($order->delivery_status ?? '') === 'picked_from_rack') {
                $this->markOrderPacked($order, Auth::id());
                $order->refresh();
                $autoPacked = true;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $autoPacked ? 'All labels scanned. Order packed.' : 'Item scanned successfully.',
                'delivery_status' => $order->delivery_status,
                'auto_packed' => $autoPacked,
                'order_item_id' => $result['order_item_id'],
                'unit_code' => $result['unit']->barcode_value,
                'barcode_value' => $result['unit']->barcode_value,
                'scanned_count' => $result['scanned_count'],
                'required_count' => $result['required_count'],
                'summary' => $summary,
            ]);
        } catch (ValidationException $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?: 'Unable to scan item.',
            ], 422);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while scanning.',
            ], 422);
        }
    }

    public function markPicked(Request $request, $id)
    {
        $order = Order::with(['items.inventoryUnits.purchase', 'items.inventoryUnits.storeRack'])->findOrFail($id);

        if ((string) ($order->call_status ?? '') !== 'confirm' || (string) ($order->delivery_status ?? '') !== 'waybill_printed') {
            $payload = [
                'success' => false,
                'message' => 'Only call-confirmed waybill-printed orders can move to Picked From Rack.',
            ];

            return $request->expectsJson()
                ? response()->json($payload, 422)
                : redirect()->route('orders.packing.ready')->with('error', $payload['message']);
        }

        $summary = $this->buildPackingSummary($order);
        $summaryItems = collect($summary['items'] ?? []);
        $requiredCount = $summaryItems->sum('required_count');
        $allocatedCount = $summaryItems->sum(fn ($item) => count($item['units'] ?? []));
        $missingRackLocation = $summaryItems
            ->flatMap(fn ($item) => collect($item['units'] ?? []))
            ->contains(fn ($unit) => empty($unit['rack_id']));

        if ($requiredCount < 1 || $allocatedCount < $requiredCount || $missingRackLocation) {
            $message = 'Pick GRN cannot be created until all required units have rack locations.';

            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $message], 422)
                : redirect()->route('orders.packing.ready')->with('error', $message);
        }

        $order->delivery_status = 'picked_from_rack';
        $order->status = $order->call_status === 'hold' ? 'hold' : 'confirm';
        if (! $order->pick_grn_number) {
            $order->pick_grn_number = $this->nextPickGrnNumber();
        }
        if (! $order->pick_grn_created_at) {
            $order->pick_grn_created_at = now();
        }
        if (! $order->pick_grn_created_by) {
            $order->pick_grn_created_by = Auth::id();
        }
        if (! $order->picked_at) {
            $order->picked_at = now();
        }
        if (! $order->picked_by) {
            $order->picked_by = Auth::id();
        }
        $order->save();

        OrderLog::create([
            'order_id' => $order->id,
            'user_id' => Auth::id(),
            'action' => 'pick_grn_created',
            'description' => 'Pick GRN '.$order->pick_grn_number.' created and order moved to picking.',
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'delivery_status' => $order->delivery_status,
                'pick_grn_number' => $order->pick_grn_number,
                'print_url' => route('orders.packing.pick-grn', ['id' => $order->id, 'print' => 1]),
            ]);
        }

        return redirect()
            ->route('orders.packing.ready')
            ->with('success', 'Pick GRN '.$order->pick_grn_number.' created. Print or save the pick sheet, then scan from the Picking tab.')
            ->with('pick_grn_modal', $this->buildPickGrnModalPayload($order, $summary));
    }

    /**
     * Mark as packed.
     */
    public function markPacked(Request $request, $id)
    {
        $order = Order::with(['items.inventoryUnits'])->findOrFail($id);

        if ((string) ($order->call_status ?? '') !== 'confirm' || (string) ($order->delivery_status ?? '') !== 'picked_from_rack') {
            return redirect()->route('orders.packing.picking')->with('error', 'Only picked orders can be marked as packed.');
        }

        foreach ($this->buildPackingSummary($order)['items'] as $itemSummary) {
            if (($itemSummary['scanned_count'] ?? 0) < ($itemSummary['required_count'] ?? 0)) {
                return redirect()->route('orders.packing.process', $order->id)
                    ->with('error', 'All allocated unit labels must be scanned before completing packing.');
            }
        }

        $this->markOrderPacked($order, Auth::id());

        return redirect()->route('orders.packing.packed')->with('success', 'Order packed successfully.');
    }

    public function markDispatched(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        if ((string) ($order->call_status ?? '') !== 'confirm' || (string) ($order->delivery_status ?? '') !== 'packed') {
            return redirect()->route('orders.packing.packed')->with('error', 'Only packed orders can be marked as dispatched.');
        }

        $order->delivery_status = 'dispatched';
        $order->status = $order->call_status === 'hold' ? 'hold' : 'confirm';
        if (! $order->dispatched_at) {
            $order->dispatched_at = now();
        }
        if (! $order->dispatched_by) {
            $order->dispatched_by = Auth::id();
        }
        $order->save();

        OrderLog::create([
            'order_id' => $order->id,
            'user_id' => Auth::id(),
            'action' => 'marked_dispatched',
            'description' => 'Order marked as dispatched after packing.',
        ]);

        return redirect()->route('orders.packing.dispatched')->with('success', 'Order marked as dispatched.');
    }

    public function markDelivered(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $order = Order::query()
                ->with(['items.inventoryUnits'])
                ->whereKey($id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((string) ($order->call_status ?? '') !== 'confirm' || (string) ($order->delivery_status ?? '') !== 'dispatched') {
                DB::rollBack();

                return redirect()->route('orders.packing.dispatched')->with('error', 'Only dispatched call-confirmed orders can be marked delivered.');
            }

            if ($this->requiresCourierReceiveBeforeDelivery($order)) {
                DB::rollBack();

                return redirect()
                    ->route('orders.packing.dispatched')
                    ->with('error', 'Outstanding COD orders must be completed through Receive Courier Payment so courier costs and settlement are recorded.');
            }

            $summaryItems = collect($this->buildPackingSummary($order)['items'] ?? []);
            if ($summaryItems->isEmpty() || $summaryItems->sum('required_count') < 1) {
                DB::rollBack();

                return redirect()
                    ->route('orders.packing.dispatched')
                    ->with('error', 'Only dispatched orders with order items can be marked delivered.');
            }

            foreach ($summaryItems as $itemSummary) {
                $requiredCount = (int) ($itemSummary['required_count'] ?? 0);
                $allocatedCount = count($itemSummary['units'] ?? []);
                $scannedCount = (int) ($itemSummary['scanned_count'] ?? 0);

                if ($requiredCount < 1 || $allocatedCount < $requiredCount || $scannedCount < $requiredCount) {
                    DB::rollBack();

                    return redirect()
                        ->route('orders.packing.dispatched')
                        ->with('error', 'Only dispatched orders with all allocated labels scanned can be marked delivered.');
                }
            }

            $order->delivery_status = 'delivered';
            $order->status = 'confirm';
            $order->payment_status = $this->resolveDeliveredPaymentStatus($order);
            if (! $order->delivered_at) {
                $order->delivered_at = now();
            }
            if (! $order->delivered_by) {
                $order->delivered_by = Auth::id();
            }
            $order->save();

            $this->inventoryUnits->markOrderUnitsDelivered($order, Auth::id());

            OrderLog::create([
                'order_id' => $order->id,
                'user_id' => Auth::id(),
                'action' => 'marked_delivered',
                'description' => 'Order marked as delivered from the dispatched packing queue.',
            ]);

            DB::commit();

            return redirect()->route('orders.packing.dispatched')->with('success', 'Order marked as delivered.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->route('orders.packing.dispatched')->with('error', 'Unable to mark this order delivered.');
        }
    }

    /**
     * Create Packing Batch (Placeholder).
     */
    public function createBatch(Request $request)
    {
        // Batch logic to be implemented
        return back()->with('info', 'Batch creation feature coming soon.');
    }

    private function buildPackingSummary(Order $order): array
    {
        $items = $order->items->map(function ($item) {
            $units = $item->inventoryUnits
                ->filter(fn ($unit) => in_array((string) $unit->status, [
                    InventoryUnit::STATUS_ALLOCATED,
                    InventoryUnit::STATUS_DELIVERED,
                ], true) || ! empty($unit->packed_scan_at))
                ->sortBy(fn ($unit) => sprintf(
                    '%02d|%s|%010d',
                    $this->storePriority((string) ($unit->store_type ?? '')),
                    $unit->storeRack?->display_label ?? '',
                    (int) $unit->id
                ))
                ->values();

            $scannedUnits = $units->filter(fn ($unit) => ! empty($unit->packed_scan_at))->values();

            return [
                'order_item_id' => $item->id,
                'sku' => $item->sku,
                'product_name' => $item->product_name,
                'required_count' => max((int) ($item->quantity ?? 0), 0),
                'scanned_count' => $scannedUnits->count(),
                'scanned_codes' => $scannedUnits->pluck('barcode_value')->filter()->values()->all(),
                'units' => $units->map(fn ($unit) => [
                    'id' => $unit->id,
                    'barcode_value' => $unit->barcode_value,
                    'store_type' => $unit->store_type,
                    'rack_id' => $unit->store_rack_id,
                    'store_label' => $unit->store_type ? StoreRack::storeLabel((string) $unit->store_type) : 'Unassigned Store',
                    'rack_label' => $unit->storeRack?->display_label ?? 'Unassigned Rack',
                    'purchase_number' => $unit->purchase?->purchase_number ?? 'Legacy stock',
                    'scanned' => ! empty($unit->packed_scan_at),
                ])->values()->all(),
            ];
        })->values();

        return [
            'items' => $items->all(),
            'all_scanned' => $items->every(fn ($item) => ($item['required_count'] ?? 0) > 0 && ($item['scanned_count'] ?? 0) >= ($item['required_count'] ?? 0)),
        ];
    }

    private function buildPickGrnModalPayload(Order $order, array $summary): array
    {
        return [
            'number' => $order->pick_grn_number,
            'order_number' => $order->order_number,
            'waybill_number' => $order->waybill_number,
            'print_url' => route('orders.packing.pick-grn', ['id' => $order->id, 'print' => 1]),
            'picking_url' => route('orders.packing.picking'),
            'items' => collect($summary['items'] ?? [])
                ->map(fn ($item) => [
                    'product_name' => $item['product_name'] ?? '-',
                    'sku' => $item['sku'] ?? '-',
                    'required_count' => (int) ($item['required_count'] ?? 0),
                    'units' => collect($item['units'] ?? [])
                        ->map(fn ($unit) => [
                            'id' => $unit['id'] ?? null,
                            'barcode_value' => $unit['barcode_value'] ?? '-',
                            'store_label' => $unit['store_label'] ?? 'Unassigned Store',
                            'rack_label' => $unit['rack_label'] ?? 'Unassigned Rack',
                            'purchase_number' => $unit['purchase_number'] ?? 'Legacy stock',
                        ])
                        ->values()
                        ->all(),
                ])
                ->values()
                ->all(),
        ];
    }

    private function buildPackingQueueQuery(Request $request, string $deliveryStatus): Builder
    {
        $query = Order::query()
            ->where('call_status', 'confirm')
            ->where('delivery_status', $deliveryStatus);

        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $query->where(function (Builder $searchQuery) use ($search) {
                $searchQuery
                    ->where('order_number', 'like', "%{$search}%")
                    ->orWhere('waybill_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%")
                    ->orWhereHas('customer', function (Builder $customerQuery) use ($search) {
                        $customerQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('mobile', 'like', "%{$search}%");
                    })
                    ->orWhereHas('items', function (Builder $itemQuery) use ($search) {
                        $itemQuery->where('product_name', 'like', "%{$search}%")
                            ->orWhere('sku', 'like', "%{$search}%")
                            ->orWhereHas('inventoryUnits', function (Builder $unitQuery) use ($search) {
                                $unitQuery->where('unit_code', 'like', "%{$search}%")
                                    ->orWhereHas('purchase', fn (Builder $purchaseQuery) => $purchaseQuery->where('purchase_number', 'like', "%{$search}%"))
                                    ->orWhereHas('storeRack', function (Builder $rackQuery) use ($search) {
                                        $rackQuery->where('rack_name', 'like', "%{$search}%")
                                            ->orWhere('row_name', 'like', "%{$search}%");
                                    });
                            });
                    });
            });
        }

        return $query;
    }

    private function markOrderPacked(Order $order, ?int $userId): void
    {
        if ((string) ($order->delivery_status ?? '') === 'packed') {
            return;
        }

        $order->packed_by = $userId;
        $order->delivery_status = 'packed';
        $order->status = $order->call_status === 'hold' ? 'hold' : 'confirm';
        if (! $order->packed_at) {
            $order->packed_at = now();
        }
        $order->save();

        OrderLog::create([
            'order_id' => $order->id,
            'user_id' => $userId,
            'action' => 'packed_confirm',
            'description' => 'Order packed after all allocated labels were scanned.',
        ]);
    }

    private function storePriority(string $storeType): int
    {
        return match (StoreRack::normalizeStoreType($storeType)) {
            StoreRack::STORE_RETAIL => 0,
            StoreRack::STORE_WAREHOUSE => 1,
            default => 2,
        };
    }

    private function nextPickGrnNumber(): string
    {
        $date = now()->format('Ymd');
        $prefix = "PGRN-{$date}-";
        $latest = Order::query()
            ->where('pick_grn_number', 'like', $prefix.'%')
            ->orderByDesc('pick_grn_number')
            ->value('pick_grn_number');

        $next = $latest && Str::startsWith($latest, $prefix)
            ? ((int) substr($latest, strlen($prefix))) + 1
            : 1;

        do {
            $number = $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
            $next++;
        } while (Order::query()->where('pick_grn_number', $number)->exists());

        return $number;
    }

    private function resolveDeliveredPaymentStatus(Order $order): string
    {
        $totalAmount = max(round((float) ($order->total_amount ?? 0), 2), 0);
        $paidAmount = max(round((float) ($order->paid_amount ?? 0), 2), 0);

        if (trim((string) ($order->payment_method ?? 'COD')) === 'COD') {
            return 'paid';
        }

        return $totalAmount > 0 && $paidAmount >= $totalAmount ? 'paid' : 'pending';
    }

    private function requiresCourierReceiveBeforeDelivery(Order $order): bool
    {
        $totalAmount = max(round((float) ($order->total_amount ?? 0), 2), 0);
        $paidAmount = max(round((float) ($order->paid_amount ?? 0), 2), 0);

        return trim((string) ($order->payment_method ?? 'COD')) === 'COD'
            && empty($order->courier_payment_id)
            && $totalAmount > $paidAmount;
    }
}
