<?php

namespace App\Http\Controllers;

use App\Models\InventoryUnit;
use App\Models\Order;
use App\Models\OrderLog;
use App\Services\InventoryUnitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PackingController extends Controller
{
    public function __construct(
        private readonly InventoryUnitService $inventoryUnits
    ) {
    }

    /**
     * List orders for packing.
     */
    public function index()
    {
        $orders = Order::where('call_status', 'confirm')
                       ->whereIn('delivery_status', ['waybill_printed', 'picked_from_rack', 'packed'])
                       ->orderBy('created_at', 'asc')
                       ->with(['items.inventoryUnits'])
                       ->get();
        return view('orders.packing.index', compact('orders'));
    }
    
    /**
     * Packing Interface for a specific order (Scanner UI).
     */
    public function process($id)
    {
        $order = Order::with(['items.inventoryUnits' => function ($query) {
            $query->where('status', InventoryUnit::STATUS_ALLOCATED)
                ->orWhereNotNull('packed_scan_at')
                ->orderBy('id');
        }])->findOrFail($id);

        if ((string) ($order->call_status ?? '') !== 'confirm' || !in_array((string) ($order->delivery_status ?? ''), ['waybill_printed', 'picked_from_rack'], true)) {
            return redirect()->route('orders.packing.index')->with('error', 'Only call-confirmed orders in the picking queue can be packed.');
        }

        return view('orders.packing.process', compact('order'));
    }

    public function scan(Request $request, $id)
    {
        $validated = $request->validate([
            'unit_code' => 'required|string|max:255',
        ]);

        $order = Order::with(['items.inventoryUnits'])->findOrFail($id);

        if ((string) ($order->call_status ?? '') !== 'confirm' || !in_array((string) ($order->delivery_status ?? ''), ['waybill_printed', 'picked_from_rack'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Only call-confirmed orders in the picking queue can be scanned.',
            ], 422);
        }

        DB::beginTransaction();

        try {
            if ((string) ($order->delivery_status ?? '') === 'waybill_printed') {
                $order->delivery_status = 'picked_from_rack';
                if (!$order->picked_at) {
                    $order->picked_at = now();
                }
                if (!$order->picked_by) {
                    $order->picked_by = Auth::id();
                }
                $order->save();

                OrderLog::create([
                    'order_id' => $order->id,
                    'user_id' => Auth::id(),
                    'action' => 'picked_from_rack',
                    'description' => 'Order moved to picked from rack.',
                ]);
            }

            $result = $this->inventoryUnits->scanOrderUnitForPacking($order, $validated['unit_code'], Auth::id());
            $summary = $this->buildPackingSummary($order->fresh(['items.inventoryUnits']));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Item scanned successfully.',
                'delivery_status' => $order->delivery_status,
                'order_item_id' => $result['order_item_id'],
                'unit_code' => $result['unit']->unit_code,
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
        if (!$order->picked_by) {
            $order->picked_by = Auth::id();
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
        $order = Order::with(['items.inventoryUnits'])->findOrFail($id);

        if ((string) ($order->call_status ?? '') !== 'confirm' || (string) ($order->delivery_status ?? '') !== 'picked_from_rack') {
            return redirect()->route('orders.packing.index')->with('error', 'Only picked orders can be marked as packed.');
        }

        foreach ($this->buildPackingSummary($order)['items'] as $itemSummary) {
            if (($itemSummary['scanned_count'] ?? 0) < ($itemSummary['required_count'] ?? 0)) {
                return redirect()->route('orders.packing.process', $order->id)
                    ->with('error', 'All allocated unit labels must be scanned before completing packing.');
            }
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
        if (!$order->dispatched_by) {
            $order->dispatched_by = Auth::id();
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

    private function buildPackingSummary(Order $order): array
    {
        $items = $order->items->map(function ($item) {
            $units = $item->inventoryUnits
                ->where('status', InventoryUnit::STATUS_ALLOCATED)
                ->sortBy('id')
                ->values();

            $scannedUnits = $units->filter(fn ($unit) => !empty($unit->packed_scan_at))->values();

            return [
                'order_item_id' => $item->id,
                'sku' => $item->sku,
                'required_count' => max((int) ($item->quantity ?? 0), 0),
                'scanned_count' => $scannedUnits->count(),
                'scanned_codes' => $scannedUnits->pluck('unit_code')->filter()->values()->all(),
            ];
        })->values();

        return [
            'items' => $items->all(),
            'all_scanned' => $items->every(fn ($item) => ($item['required_count'] ?? 0) > 0 && ($item['scanned_count'] ?? 0) >= ($item['required_count'] ?? 0)),
        ];
    }
}
