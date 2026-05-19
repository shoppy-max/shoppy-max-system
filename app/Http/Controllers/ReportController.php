<?php

namespace App\Http\Controllers;

use App\Exports\ArrayReportExport;
use App\Models\InventoryUnit;
use App\Models\InventoryUnitEvent;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    private const PER_PAGE = 20;

    public function index()
    {
        $stockSummary = $this->stockSummary($this->stockRows(new Request()));
        $salesOrders = $this->nonCancelledOrders(Order::query());

        $metrics = [
            'stock_pcs' => $stockSummary['total_pcs'],
            'stock_value' => $stockSummary['total_value'],
            'active_orders' => (clone $salesOrders)->count(),
            'delivered_orders' => (clone $salesOrders)->where('delivery_status', 'delivered')->count(),
            'returned_orders' => (clone $salesOrders)->where('delivery_status', 'returned')->count(),
            'packed_orders' => Order::query()->whereNotNull('packed_at')->count(),
        ];

        $cards = [
            [
                'title' => 'Stock Report',
                'description' => 'Current available PCS, FIFO stock value, and per-SKU stock movement history.',
                'route' => route('reports.stock'),
                'accent' => 'blue',
            ],
            [
                'title' => 'Packed & Pick From Rack',
                'description' => 'Operator counts for packed orders and pick-from-rack work with date filters.',
                'route' => route('reports.packet-count'),
                'accent' => 'purple',
            ],
            [
                'title' => 'Product Wise Sale',
                'description' => 'Per-product PCS totals, delivered PCS, returned PCS, and return percentage.',
                'route' => route('reports.product-sales'),
                'accent' => 'emerald',
            ],
            [
                'title' => 'User Wise Sale',
                'description' => 'Creator-level order and PCS performance with cancelled orders excluded.',
                'route' => route('reports.user-sales'),
                'accent' => 'amber',
            ],
        ];

        return view('reports.index', compact('metrics', 'cards'));
    }

    public function stockReport(Request $request)
    {
        $rows = $this->stockRows($request);
        $summary = $this->stockSummary($rows);

        if ($export = $this->exportType($request)) {
            return $this->downloadReport(
                'Stock Report',
                'stock-report',
                ['Product name', 'SKU', 'Variant', 'Total Stock (Qty)', 'Stock Value (FIFO)'],
                $rows->map(fn (array $row) => [
                    $row['product_name'],
                    $row['sku'],
                    $row['variant_label'],
                    $row['stock_qty'],
                    $row['stock_value'],
                ]),
                $export
            );
        }

        $paginatedRows = $this->paginateCollection($rows, $request);

        return view('reports.stock', compact('paginatedRows', 'summary'));
    }

    public function stockDetail(Request $request, ProductVariant $variant)
    {
        $variant->load(['product', 'unit']);
        $rows = $this->stockMovementRows($variant, $request);
        $summary = [
            'total_pcs' => InventoryUnit::query()
                ->where('product_variant_id', $variant->id)
                ->where('status', InventoryUnit::STATUS_AVAILABLE)
                ->count(),
            'total_value' => InventoryUnit::query()
                ->with('purchaseItem')
                ->where('product_variant_id', $variant->id)
                ->where('status', InventoryUnit::STATUS_AVAILABLE)
                ->get()
                ->sum(fn (InventoryUnit $unit) => (float) ($unit->purchaseItem?->purchase_price ?? 0)),
            'movement_count' => $rows->count(),
            'in_qty' => $rows->where('quantity_change', '>', 0)->sum('quantity_change'),
            'out_qty' => abs($rows->where('quantity_change', '<', 0)->sum('quantity_change')),
            'net_value_change' => round($rows->sum('value_change'), 2),
        ];

        if ($export = $this->exportType($request)) {
            return $this->downloadReport(
                'Stock Movement - '.$this->variantDisplayName($variant),
                'stock-movement-'.$variant->sku,
                ['Type', 'Quantity Change', 'Available Quantity', 'Date & Time', 'Reference No', 'Reference Type', 'Value Change'],
                $rows->map(fn (array $row) => [
                    $row['type'],
                    $row['quantity_change'],
                    $row['available_quantity'],
                    $row['date_time'],
                    $row['reference_no'],
                    $row['reference_type'],
                    $row['value_change'],
                ]),
                $export
            );
        }

        $paginatedRows = $this->paginateCollection($rows, $request);

        return view('reports.stock_detail', compact('variant', 'paginatedRows', 'summary'));
    }

    public function packetCount(Request $request)
    {
        $users = User::query()->orderBy('name')->get();
        $rows = $this->packetRows($request, $users);
        $summary = [
            'packed_count' => $rows->sum('packed_count'),
            'picked_count' => $rows->sum('picked_count'),
        ];

        if ($export = $this->exportType($request)) {
            return $this->downloadReport(
                'Packed & Pick From Rack Report',
                'packed-pick-from-rack-report',
                ['User', 'Email', 'Packed Count', 'Pick From Rack Count'],
                $rows->map(fn (array $row) => [
                    $row['user_name'],
                    $row['email'],
                    $row['packed_count'],
                    $row['picked_count'],
                ]),
                $export
            );
        }

        $paginatedRows = $this->paginateCollection($rows, $request);

        return view('reports.packet_count', compact('users', 'paginatedRows', 'summary'));
    }

    public function productSales(Request $request)
    {
        $rows = $this->productSalesRows($request);
        $summary = [
            'total_pcs' => $rows->sum('total_pcs'),
            'delivered_pcs' => $rows->sum('delivered_pcs'),
            'returned_pcs' => $rows->sum('returned_pcs'),
        ];

        if ($export = $this->exportType($request)) {
            return $this->downloadReport(
                'Product Wise Sale Report',
                'product-wise-sale-report',
                ['Product', 'SKU', 'Variant', 'Total PCS', 'Delivered PCS', 'Returned PCS', 'Return % (PCS)'],
                $rows->map(fn (array $row) => [
                    $row['product_name'],
                    $row['sku'],
                    $row['variant_label'],
                    $row['total_pcs'],
                    $row['delivered_pcs'],
                    $row['returned_pcs'],
                    $row['return_percentage'],
                ]),
                $export
            );
        }

        $paginatedRows = $this->paginateCollection($rows, $request);

        return view('reports.product_sales', compact('paginatedRows', 'summary'));
    }

    public function userSales(Request $request)
    {
        $rows = $this->userSalesRows($request);
        $summary = [
            'total_orders' => $rows->sum('total_orders'),
            'total_pcs' => $rows->sum('total_pcs'),
            'returned_orders' => $rows->sum('returned_orders'),
            'returned_pcs' => $rows->sum('returned_pcs'),
        ];

        if ($export = $this->exportType($request)) {
            return $this->downloadReport(
                'User Wise Sale Report',
                'user-wise-sale-report',
                ['Name', 'Email', 'Total Orders', 'Delivered Orders', 'Returned Orders', 'Return % (Order)', 'Total PCS', 'Delivered PCS', 'Returned PCS', 'Return % (PCS)'],
                $rows->map(fn (array $row) => [
                    $row['user_name'],
                    $row['email'],
                    $row['total_orders'],
                    $row['delivered_orders'],
                    $row['returned_orders'],
                    $row['order_return_percentage'],
                    $row['total_pcs'],
                    $row['delivered_pcs'],
                    $row['returned_pcs'],
                    $row['pcs_return_percentage'],
                ]),
                $export
            );
        }

        $paginatedRows = $this->paginateCollection($rows, $request);

        return view('reports.user_sales', compact('paginatedRows', 'summary'));
    }

    public function provinceSale()
    {
        $provinceSales = DB::table('orders')
            ->join('cities', 'orders.city_id', '=', 'cities.id')
            ->select('cities.province', DB::raw('sum(orders.total_amount) as total_sales'), DB::raw('count(orders.id) as order_count'))
            ->where('orders.status', 'confirm')
            ->groupBy('cities.province')
            ->orderBy('total_sales', 'desc')
            ->get();

        return view('reports.province', compact('provinceSales'));
    }

    public function profitLoss(Request $request)
    {
        $query = Order::with(['items', 'courierPayment'])->where('status', 'confirm');

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                Carbon::parse($request->input('start_date'))->startOfDay(),
                Carbon::parse($request->input('end_date'))->endOfDay(),
            ]);
        }

        $orders = $query->get();
        $data = [
            'total_sales' => $orders->sum('total_amount'),
            'cogs' => $orders->sum(fn (Order $order) => $order->items->sum(fn (OrderItem $item) => (float) ($item->cost_price ?? 0) * (int) $item->quantity)),
            'courier_cost' => $orders->sum('courier_cost'),
            'delivery_income' => $orders->sum('delivery_fee'),
            'gross_profit' => 0,
            'net_profit' => 0,
        ];
        $data['gross_profit'] = $data['total_sales'] - $data['cogs'];
        $data['net_profit'] = $data['gross_profit'] - $data['courier_cost'];

        return view('reports.profit_loss', compact('data'));
    }

    private function stockRows(Request $request): Collection
    {
        $search = trim((string) $request->input('search', ''));

        return ProductVariant::query()
            ->with(['product', 'unit', 'inventoryUnits.purchaseItem'])
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $inner) use ($search) {
                    $inner->where('sku', 'like', "%{$search}%")
                        ->orWhereHas('product', fn (Builder $productQuery) => $productQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->get()
            ->map(function (ProductVariant $variant) {
                $availableUnits = $variant->inventoryUnits->where('status', InventoryUnit::STATUS_AVAILABLE);

                return [
                    'variant_id' => $variant->id,
                    'product_name' => $variant->product?->name ?? 'Deleted product',
                    'sku' => $variant->sku,
                    'variant_label' => $this->variantUnitLabel($variant),
                    'stock_qty' => $availableUnits->count(),
                    'stock_value' => round($availableUnits->sum(fn (InventoryUnit $unit) => (float) ($unit->purchaseItem?->purchase_price ?? 0)), 2),
                ];
            })
            ->sortBy([['product_name', 'asc'], ['sku', 'asc']])
            ->values();
    }

    private function stockSummary(Collection $rows): array
    {
        return [
            'total_pcs' => $rows->sum('stock_qty'),
            'total_value' => round($rows->sum('stock_value'), 2),
        ];
    }

    private function stockMovementRows(ProductVariant $variant, ?Request $request = null): Collection
    {
        $events = InventoryUnitEvent::query()
            ->with(['unit.purchaseItem'])
            ->whereHas('unit', fn (Builder $query) => $query->where('product_variant_id', $variant->id))
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $purchaseIds = $events->pluck('purchase_id')
            ->merge($events->pluck('unit.purchase_id'))
            ->filter()
            ->unique()
            ->values();
        $orderIds = $events->map(fn (InventoryUnitEvent $event) => $this->eventOrderId($event))
            ->filter()
            ->unique()
            ->values();

        $purchases = Purchase::query()->whereIn('id', $purchaseIds)->get()->keyBy('id');
        $orders = Order::query()->whereIn('id', $orderIds)->get()->keyBy('id');
        $stockInUnitIds = $events
            ->whereIn('event_type', ['received_to_stock', 'bulk_activation', 'backfill_legacy_available'])
            ->pluck('inventory_unit_id')
            ->filter()
            ->unique();
        $available = 0;

        $rows = $events
            ->map(function (InventoryUnitEvent $event) use (&$available, $purchases, $orders, $stockInUnitIds) {
                $movement = $this->movementForEvent($event, $stockInUnitIds);

                if (! $movement) {
                    return null;
                }

                $quantityChange = $movement['quantity'];
                $available += $quantityChange;
                $purchaseId = $event->purchase_id ?? $event->unit?->purchase_id;
                $purchase = $purchaseId ? $purchases->get($purchaseId) : null;
                $orderId = $this->eventOrderId($event);
                $order = $orderId ? $orders->get($orderId) : null;
                $unitCost = (float) ($event->unit?->purchaseItem?->purchase_price ?? 0);
                $isPurchaseReference = $movement['type'] === 'Purchasing';
                $referenceNo = $isPurchaseReference
                    ? ($purchase?->purchase_number ?? ($purchaseId ? 'Purchase #'.$purchaseId : '-'))
                    : ($order?->order_number ?? ($orderId ? 'Order #'.$orderId : '-'));

                return [
                    'type' => $movement['type'],
                    'event_type' => (string) $event->event_type,
                    'quantity_change' => $quantityChange,
                    'available_quantity' => $available,
                    'date_time' => optional($event->created_at)->timezone(config('app.timezone'))->format('Y-m-d H:i'),
                    'occurred_at' => $event->created_at,
                    'reference_no' => $referenceNo,
                    'reference_type' => $isPurchaseReference ? 'Purchase' : 'Order',
                    'reference_url' => $isPurchaseReference && $purchase
                        ? route('purchases.show', $purchase)
                        : ($order ? route('orders.show', $order) : null),
                    'value_change' => round($quantityChange * $unitCost, 2),
                ];
            })
            ->filter()
            ->values();

        if ($request) {
            $rows = $this->filterStockMovementRows($rows, $request);
        }

        return $rows
            ->sortByDesc('occurred_at')
            ->values();
    }

    private function filterStockMovementRows(Collection $rows, Request $request): Collection
    {
        if ($type = trim((string) $request->input('type', ''))) {
            $rows = $rows->filter(fn (array $row) => $row['type'] === $type);
        }

        if ($reference = trim((string) $request->input('reference', ''))) {
            $needle = mb_strtolower($reference);
            $rows = $rows->filter(function (array $row) use ($needle) {
                return str_contains(mb_strtolower((string) $row['reference_no']), $needle)
                    || str_contains(mb_strtolower((string) $row['event_type']), $needle)
                    || str_contains(mb_strtolower((string) $row['reference_type']), $needle);
            });
        }

        if ($request->filled('start_date')) {
            $start = Carbon::parse($request->input('start_date'), config('app.timezone'))->startOfDay();
            $rows = $rows->filter(fn (array $row) => $row['occurred_at'] && $row['occurred_at']->copy()->timezone(config('app.timezone'))->greaterThanOrEqualTo($start));
        }

        if ($request->filled('end_date')) {
            $end = Carbon::parse($request->input('end_date'), config('app.timezone'))->endOfDay();
            $rows = $rows->filter(fn (array $row) => $row['occurred_at'] && $row['occurred_at']->copy()->timezone(config('app.timezone'))->lessThanOrEqualTo($end));
        }

        return $rows->values();
    }

    private function movementForEvent(InventoryUnitEvent $event, Collection $stockInUnitIds): ?array
    {
        $eventType = (string) $event->event_type;

        if (in_array($eventType, ['received_to_stock', 'bulk_activation', 'backfill_legacy_available'], true)) {
            return ['type' => 'Purchasing', 'quantity' => 1];
        }

        if (
            $eventType === 'created'
            && $event->unit
            && in_array($event->unit->status, InventoryUnit::ACTIVE_STOCK_STATUSES, true)
            && ! $stockInUnitIds->contains($event->inventory_unit_id)
        ) {
            return ['type' => 'Purchasing', 'quantity' => 1];
        }

        if (str_contains($eventType, 'return')) {
            return ['type' => 'Return', 'quantity' => 1];
        }

        if (str_contains($eventType, 'cancel') || str_contains($eventType, 'delete') || str_contains($eventType, 'rebalance') || str_contains($eventType, 'update')) {
            return ['type' => 'Cancel', 'quantity' => 1];
        }

        if (in_array($eventType, ['allocated', 'backfill_allocated', 'backfill_delivered', 'backfill_legacy_allocated', 'backfill_legacy_delivered'], true)) {
            return ['type' => 'Sale', 'quantity' => -1];
        }

        return null;
    }

    private function eventOrderId(InventoryUnitEvent $event): ?int
    {
        $metadata = $event->metadata ?? [];

        return $event->order_id
            ? (int) $event->order_id
            : (isset($metadata['order_id']) ? (int) $metadata['order_id'] : (isset($metadata['released_order_id']) ? (int) $metadata['released_order_id'] : null));
    }

    private function packetRows(Request $request, Collection $users): Collection
    {
        $selectedUserId = $request->integer('user_id') ?: null;

        return $users
            ->when($selectedUserId, fn (Collection $collection) => $collection->where('id', $selectedUserId))
            ->map(function (User $user) use ($request) {
                $packedQuery = Order::query()->where('packed_by', $user->id)->whereNotNull('packed_at');
                $pickedQuery = Order::query()->where('picked_by', $user->id)->whereNotNull('picked_at');
                $this->applyDateRange($packedQuery, $request, 'packed_at');
                $this->applyDateRange($pickedQuery, $request, 'picked_at');

                return [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'email' => $user->email,
                    'packed_count' => $packedQuery->count(),
                    'picked_count' => $pickedQuery->count(),
                ];
            })
            ->filter(fn (array $row) => $selectedUserId || $row['packed_count'] > 0 || $row['picked_count'] > 0)
            ->sortByDesc(fn (array $row) => $row['packed_count'] + $row['picked_count'])
            ->values();
    }

    private function productSalesRows(Request $request): Collection
    {
        $query = OrderItem::query()
            ->with(['order', 'variant.product', 'variant.unit'])
            ->whereHas('order', function (Builder $orderQuery) use ($request) {
                $this->nonCancelledOrders($orderQuery);
                $this->applyDateRange($orderQuery, $request, 'order_date');
            });

        if ($search = trim((string) $request->input('search', ''))) {
            $query->where(function (Builder $inner) use ($search) {
                $inner->where('product_name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhereHas('variant.product', fn (Builder $productQuery) => $productQuery->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('variant', fn (Builder $variantQuery) => $variantQuery->where('sku', 'like', "%{$search}%"));
            });
        }

        return $this->applyReturnPercentageFilter(
            $query->get()
                ->groupBy(fn (OrderItem $item) => $item->product_variant_id ? 'variant-'.$item->product_variant_id : 'snapshot-'.$item->sku.'-'.$item->product_name)
                ->map(function (Collection $items) {
                    $first = $items->first();
                    $totalPcs = $items->sum(fn (OrderItem $item) => (int) $item->quantity);
                    $deliveredPcs = $items->filter(fn (OrderItem $item) => $item->order?->delivery_status === 'delivered')->sum(fn (OrderItem $item) => (int) $item->quantity);
                    $returnedPcs = $items->filter(fn (OrderItem $item) => $item->order?->delivery_status === 'returned')->sum(fn (OrderItem $item) => (int) $item->quantity);
                    $returnPercentage = $totalPcs > 0 ? round(($returnedPcs / $totalPcs) * 100, 2) : 0.0;

                    return [
                        'product_name' => $first->variant?->product?->name ?? $first->product_name,
                        'sku' => $first->variant?->sku ?? $first->sku,
                        'variant_label' => $first->variant ? $this->variantUnitLabel($first->variant) : '-',
                        'total_pcs' => $totalPcs,
                        'delivered_pcs' => $deliveredPcs,
                        'returned_pcs' => $returnedPcs,
                        'return_percentage' => $returnPercentage,
                    ];
                })
                ->values(),
            $request,
            'return_percentage'
        )->sortByDesc('total_pcs')->values();
    }

    private function userSalesRows(Request $request): Collection
    {
        $query = $this->nonCancelledOrders(Order::query()->with(['user', 'items']));
        $this->applyDateRange($query, $request, 'order_date');

        if ($search = trim((string) $request->input('search', ''))) {
            $query->whereHas('user', function (Builder $userQuery) use ($search) {
                $userQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $this->applyReturnPercentageFilter(
            $query->get()
                ->groupBy(fn (Order $order) => $order->user_id ?: 'unassigned')
                ->map(function (Collection $orders) {
                    $first = $orders->first();
                    $totalOrders = $orders->count();
                    $deliveredOrders = $orders->where('delivery_status', 'delivered')->count();
                    $returnedOrders = $orders->where('delivery_status', 'returned')->count();
                    $totalPcs = $orders->sum(fn (Order $order) => $order->items->sum(fn (OrderItem $item) => (int) $item->quantity));
                    $deliveredPcs = $orders->where('delivery_status', 'delivered')->sum(fn (Order $order) => $order->items->sum(fn (OrderItem $item) => (int) $item->quantity));
                    $returnedPcs = $orders->where('delivery_status', 'returned')->sum(fn (Order $order) => $order->items->sum(fn (OrderItem $item) => (int) $item->quantity));

                    return [
                        'user_name' => $first->user?->name ?? 'Unassigned',
                        'email' => $first->user?->email ?? '-',
                        'total_orders' => $totalOrders,
                        'delivered_orders' => $deliveredOrders,
                        'returned_orders' => $returnedOrders,
                        'order_return_percentage' => $totalOrders > 0 ? round(($returnedOrders / $totalOrders) * 100, 2) : 0.0,
                        'total_pcs' => $totalPcs,
                        'delivered_pcs' => $deliveredPcs,
                        'returned_pcs' => $returnedPcs,
                        'pcs_return_percentage' => $totalPcs > 0 ? round(($returnedPcs / $totalPcs) * 100, 2) : 0.0,
                    ];
                })
                ->values(),
            $request,
            'pcs_return_percentage'
        )->sortByDesc('total_pcs')->values();
    }

    private function nonCancelledOrders(Builder $query): Builder
    {
        return $query
            ->where('status', '!=', 'cancel')
            ->where('call_status', '!=', 'cancel')
            ->where('delivery_status', '!=', 'cancel');
    }

    private function applyDateRange(Builder $query, Request $request, string $column): void
    {
        if ($request->filled('start_date')) {
            $start = Carbon::parse($request->input('start_date'));
            if ($column === 'order_date') {
                $query->whereDate($column, '>=', $start->toDateString());
            } else {
                $query->where($column, '>=', $start->startOfDay());
            }
        }

        if ($request->filled('end_date')) {
            $end = Carbon::parse($request->input('end_date'));
            if ($column === 'order_date') {
                $query->whereDate($column, '<=', $end->toDateString());
            } else {
                $query->where($column, '<=', $end->endOfDay());
            }
        }
    }

    private function applyReturnPercentageFilter(Collection $rows, Request $request, string $column): Collection
    {
        if ($request->filled('min_return_percentage')) {
            $rows = $rows->filter(fn (array $row) => (float) $row[$column] >= (float) $request->input('min_return_percentage'));
        }

        if ($request->filled('max_return_percentage')) {
            $rows = $rows->filter(fn (array $row) => (float) $row[$column] <= (float) $request->input('max_return_percentage'));
        }

        return $rows->values();
    }

    private function exportType(Request $request): ?string
    {
        $export = strtolower((string) $request->input('export', ''));

        return in_array($export, ['pdf', 'excel'], true) ? $export : null;
    }

    private function downloadReport(string $title, string $filename, array $headings, Collection $rows, string $export)
    {
        $preparedRows = $rows->map(fn (array $row) => array_map(fn ($value) => is_float($value) ? number_format($value, 2, '.', '') : $value, $row))->values();

        if ($export === 'excel') {
            return Excel::download(new ArrayReportExport($headings, $preparedRows->all(), $title), $filename.'.xlsx');
        }

        return Pdf::loadView('reports.export_pdf', [
            'title' => $title,
            'headings' => $headings,
            'rows' => $preparedRows,
            'generatedAt' => now()->timezone(config('app.timezone'))->format('Y-m-d H:i'),
        ])->setPaper('a4', 'landscape')->download($filename.'.pdf');
    }

    private function paginateCollection(Collection $rows, Request $request, int $perPage = self::PER_PAGE): LengthAwarePaginator
    {
        $page = LengthAwarePaginator::resolveCurrentPage();
        $items = $rows->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $items,
            $rows->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
    }

    private function variantDisplayName(ProductVariant $variant): string
    {
        return trim(($variant->product?->name ?? 'Product').' '.$variant->sku);
    }

    private function variantUnitLabel(ProductVariant $variant): string
    {
        $unitValue = trim((string) ($variant->unit_value ?? ''));
        $unitName = trim((string) ($variant->unit?->short_name ?? $variant->unit?->name ?? ''));
        $label = trim($unitValue.' '.$unitName);

        return $label !== '' ? $label : '-';
    }
}
