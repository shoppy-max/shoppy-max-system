<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\Reseller;
use App\Models\Customer;
use App\Models\OrderLog;
use App\Models\Courier;
use App\Models\City;
use App\Services\InventoryUnitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;

class OrderController extends Controller
{
    private const DEFAULT_CUSTOMER_COUNTRY = 'Sri Lanka';

    private const DELIVERY_STATUSES = [
        'pending',
        'waybill_printed',
        'picked_from_rack',
        'packed',
        'dispatched',
        'delivered',
        'return_requested',
        'returned',
        'cancel',
    ];

    /**
     * Display a listing of orders.
     */
    public function index(Request $request)
    {
        $viewMode = $request->input('view', 'active');
        if (!in_array($viewMode, ['active', 'cancelled'], true)) {
            $viewMode = 'active';
        }

        $query = Order::with(['user', 'reseller', 'customer', 'items', 'courier']); 

        if ($viewMode === 'cancelled') {
            $query->where('status', 'cancel');
        } else {
            $query->where('status', '!=', 'cancel');
        }

        // 1. Search (Order Number, Customer Name, Mobile)
        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $numericSearch = ctype_digit($search) ? (int) $search : null;

            $query->where(function ($q) use ($search, $numericSearch) {
                if ($numericSearch !== null) {
                    $q->where('id', $numericSearch)
                        ->orWhere('user_id', $numericSearch)
                        ->orWhere('reseller_id', $numericSearch)
                        ->orWhere('customer_id', $numericSearch);
                }

                $q->orWhere('order_number', 'like', "%{$search}%")
                    ->orWhere('waybill_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($customerQuery) use ($search) {
                        $customerQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('mobile', 'like', "%{$search}%")
                            ->orWhere('landline', 'like', "%{$search}%")
                            ->orWhere('business_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('reseller', function ($resellerQuery) use ($search) {
                        $resellerQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('business_name', 'like', "%{$search}%")
                            ->orWhere('mobile', 'like', "%{$search}%")
                            ->orWhere('landline', 'like', "%{$search}%");
                    })
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        // 2. Filter by Order Status
        if ($request->filled('status')) {
            $status = (string) $request->status;

            if ($viewMode === 'cancelled') {
                $query->where('status', 'cancel');
            } elseif (in_array($status, ['pending', 'hold', 'confirm'], true)) {
                $query->where('status', $status);
            }
        }

        if ($request->filled('delivery_status') && in_array($request->delivery_status, self::DELIVERY_STATUSES, true)) {
            $query->where('delivery_status', $request->delivery_status);
        }

        // 3. Filter by Call Status
        if ($request->filled('call_status')) {
            $query->where('call_status', $request->call_status);
        }

        // 4. Filter by Courier
        if ($request->filled('courier_id')) {
            $query->where('courier_id', $request->courier_id);
        }

        // 5. Filter by Date Range
        if ($request->filled('date_from')) {
            $query->whereDate('order_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('order_date', '<=', $request->date_to);
        }

        // 6. Payment Method
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        $orders = $query->latest()->paginate(20)->withQueryString();
        $couriers = Courier::all();

        return view('orders.index', compact('orders', 'couriers', 'viewMode'));
    }

    /**
     * Show general order create form.
     */
    public function create()
    {
        $today = now();

        // Predict next order number for UI
        $dateStr = $today->format('Ymd');
        $sequence = $this->getNextOrderSequenceForDate($today);
        $nextOrderNumber = 'ORD-' . $dateStr . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
        
        $couriers = Courier::all();
        $courierRatesMap = $this->buildCourierRatesMap($couriers);
        $cities = City::orderBy('city_name')->get(['id', 'city_name', 'district', 'province', 'postal_code']);
        $currentOrderDate = $today->toDateString();

        return view('orders.create', compact('nextOrderNumber', 'couriers', 'courierRatesMap', 'cities', 'currentOrderDate'));
    }

    /**
     * API: Search Products for Order Form
     */
    public function searchProducts(Request $request)
    {
        $term = trim((string) $request->query('q', ''));
        if (mb_strlen($term) < 2) {
            return response()->json([]);
        }
        $normalizedTerm = $this->normalizeSearchText($term);
        $termTokens = array_values(array_filter(explode(' ', $normalizedTerm)));

        $compactTerm = mb_strtolower((string) preg_replace('/\s+/', '', $term));
        $parsedUnitSearch = null;
        if (preg_match('/^(\d+(?:\.\d+)?)([a-z]+)$/i', $compactTerm, $matches)) {
            $parsedUnitSearch = [
                'value' => $matches[1],
                'unit' => $matches[2],
            ];
        }

        $variants = ProductVariant::query()
            ->with([
                'product:id,name,description,image',
                'unit:id,name,short_name',
            ])
            ->whereHas('product')
            ->where(function ($query) use ($term, $parsedUnitSearch) {
                $query->where('sku', 'like', "%{$term}%")
                    ->orWhere('unit_value', 'like', "%{$term}%")
                    ->orWhereHas('unit', function ($unitQuery) use ($term) {
                        $unitQuery->where('short_name', 'like', "%{$term}%")
                            ->orWhere('name', 'like', "%{$term}%");
                    })
                    ->orWhereHas('product', function ($productQuery) use ($term) {
                        $productQuery->where('name', 'like', "%{$term}%")
                            ->orWhere('description', 'like', "%{$term}%");
                    });

                if ($parsedUnitSearch) {
                    $query->orWhere(function ($unitValueQuery) use ($parsedUnitSearch) {
                        $unitValueQuery
                            ->where('unit_value', 'like', $parsedUnitSearch['value'] . '%')
                            ->whereHas('unit', function ($unitQuery) use ($parsedUnitSearch) {
                                $unitQuery->where('short_name', 'like', $parsedUnitSearch['unit'] . '%')
                                    ->orWhere('name', 'like', $parsedUnitSearch['unit'] . '%');
                            });
                    });
                }
            })
            ->limit(120)
            ->get();

        $results = $variants->map(function (ProductVariant $variant) use ($normalizedTerm, $termTokens) {
            $product = $variant->product;
            if (!$product) {
                return null;
            }

            $unitLabel = $this->buildVariantUnitLabel($variant);
            $displayName = $this->buildVariantDisplayName($variant);
            $stock = (int) ($variant->quantity ?? 0);
            $score = $this->buildProductSearchScore($normalizedTerm, $termTokens, [
                'product_name' => (string) ($product->name ?? ''),
                'display_name' => (string) $displayName,
                'sku' => (string) ($variant->sku ?? ''),
                'unit_label' => (string) $unitLabel,
                'description' => (string) ($product->description ?? ''),
            ], $stock);

            return [
                'id' => $variant->id,
                'name' => $product->name,
                'display_name' => $displayName,
                'product_name' => $product->name,
                'unit_label' => $unitLabel,
                'image' => $variant->image ?: $product->image,
                'sku' => $variant->sku,
                'selling_price' => (float) ($variant->selling_price ?? 0),
                'limit_price' => (float) ($variant->limit_price ?? 0),
                'stock' => $stock,
                'unit' => $variant->unit->short_name ?? '',
                '_score' => $score,
            ];
        })
            ->filter(fn ($item) => $item && ($item['_score'] ?? 0) > 0)
            ->sort(function (array $a, array $b) {
                if ($a['_score'] !== $b['_score']) {
                    return $b['_score'] <=> $a['_score'];
                }
                if ($a['stock'] !== $b['stock']) {
                    return $b['stock'] <=> $a['stock'];
                }

                return strcasecmp((string) ($a['display_name'] ?? ''), (string) ($b['display_name'] ?? ''));
            })
            ->take(30)
            ->values()
            ->map(function (array $item) {
                unset($item['_score']);
                return $item;
            })
            ->values();

        return response()->json($results);
    }

    /**
     * API: Search Resellers for Order Form
     */
    public function searchResellers(Request $request)
    {
        $query = trim((string) $request->query('q', ''));
        if (mb_strlen($query) < 2) {
            return response()->json([]);
        }

        $resellers = Reseller::query()
            ->whereIn('reseller_type', [
                Reseller::TYPE_RESELLER,
                Reseller::TYPE_DIRECT_RESELLER,
            ])
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('business_name', 'like', "%{$query}%")
                    ->orWhere('mobile', 'like', "%{$query}%");
            })
            ->orderBy('business_name')
            ->orderBy('name')
            ->limit(25)
            ->get(['id', 'name', 'business_name', 'mobile', 'reseller_type'])
            ->map(function (Reseller $reseller) {
                return [
                    'id' => $reseller->id,
                    'name' => $reseller->name,
                    'business_name' => $reseller->business_name,
                    'display_name' => $reseller->business_name ?: $reseller->name,
                    'mobile' => $reseller->mobile,
                    'reseller_type' => $reseller->reseller_type,
                    'type_label' => $reseller->reseller_type === Reseller::TYPE_DIRECT_RESELLER ? 'Direct Reseller' : 'Reseller',
                ];
            });

        return response()->json($resellers);
    }

    /**
     * API: Search Customers for Order Form
     */
    public function searchCustomers(Request $request)
    {
        $query = trim((string) $request->query('q', ''));
        if (mb_strlen($query) < 2) {
            return response()->json([]);
        }

        $customers = Customer::query()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('mobile', 'like', "%{$query}%")
                    ->orWhere('address', 'like', "%{$query}%")
                    ->orWhere('city', 'like', "%{$query}%")
                    ->orWhere('district', 'like', "%{$query}%")
                    ->orWhere('province', 'like', "%{$query}%");
            })
            ->orderBy('name')
            ->orderBy('mobile')
            ->limit(25)
            ->get([
                'id',
                'name',
                'mobile',
                'landline',
                'address',
                'city',
                'district',
                'province',
                'country',
            ])
            ->map(function (Customer $customer) {
                $locationParts = array_filter([
                    $customer->city,
                    $customer->district,
                    $customer->province,
                ]);

                return [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'mobile' => $customer->mobile,
                    'landline' => $customer->landline,
                    'address' => $customer->address,
                    'city' => $customer->city,
                    'district' => $customer->district,
                    'province' => $customer->province,
                    'country' => $customer->country,
                    'location_label' => implode(' | ', $locationParts),
                    'display_label' => trim(($customer->name ?: 'Unknown') . ($customer->mobile ? " | {$customer->mobile}" : '')),
                ];
            });

        return response()->json($customers->values());
    }

    /**
     * Store a new order.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_type' => 'required|in:reseller,direct',
            'reseller_id' => [
                'required_if:order_type,reseller',
                'nullable',
                Rule::exists('resellers', 'id')->where(function ($query) {
                    $query->whereIn('reseller_type', [
                        Reseller::TYPE_RESELLER,
                        Reseller::TYPE_DIRECT_RESELLER,
                    ]);
                }),
            ],
            
            // Customer Details
            'customer.name' => 'required|string|max:255',
            'customer.mobile' => ['required', 'regex:/^\d{10}$/'],
            'customer.landline' => ['nullable', 'regex:/^\d{10}$/'],
            'customer.address' => 'required|string',
            'customer.city_id' => 'required|exists:cities,id',
            
            // Products
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.selling_price' => 'required|numeric|min:0',

            // Fulfillment & Address
            'courier_id' => 'nullable|exists:couriers,id',
            'courier_charge' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string|in:COD,Online Payment,Bank Transfer',
            'paid_amount' => 'nullable|numeric|min:0',
            'payments' => 'nullable|array',
            'payments.*.amount' => 'required_with:payments|numeric|min:0.01',
            'payments.*.date' => 'required_with:payments|date',
            'payments.*.note' => 'nullable|string|max:255',
            'payment_status' => 'nullable|in:pending,paid',
            'call_status' => 'nullable|in:pending,confirm,hold,cancel',
            'sales_note' => 'nullable|string',
            'order_status' => 'nullable|in:pending,hold,confirm,cancel',
            'delivery_status' => 'nullable|in:' . implode(',', self::DELIVERY_STATUSES),
            'customer.district' => 'nullable|string',
            'customer.province' => 'nullable|string',
        ]);

        $this->validateCourierChargeSelection(
            isset($validated['courier_id']) ? (int) $validated['courier_id'] : null,
            $validated['courier_charge'] ?? null
        );

        if (empty($validated['courier_id'])) {
            $validated['courier_charge'] = 0;
        } else {
            $validated['courier_charge'] = (float) $this->normalizeRateForComparison($validated['courier_charge']);
        }
        $validated['discount_amount'] = isset($validated['discount_amount'])
            ? round((float) $validated['discount_amount'], 2)
            : 0.0;

        DB::beginTransaction();
        try {
            $selectedCity = City::findOrFail($validated['customer']['city_id']);

            // 1. Create or Update Customer
            // Strategy: Search by mobile number, if exists update, else create
            // Note: User logic "customer we specify" implies we can create new on the fly.
            $customer = Customer::updateOrCreate(
                ['mobile' => $validated['customer']['mobile']],
                $this->buildCustomerDataFromOrderInput($validated['customer'], $selectedCity)
            );

            // 2. Create Order
            $orderNumber = $this->generateOrderNumber();
            
            $order = new Order();
            $order->order_number = $orderNumber;
            $order->order_date = now()->toDateString();
            $order->order_type = $validated['order_type'];
            $order->user_id = Auth::id(); // Admin creating the order
            $order->reseller_id = $validated['order_type'] === 'reseller' ? $validated['reseller_id'] : null;
            $order->customer_id = $customer->id;
            
            // Fallback legacy fields (optional, but good for redundancy if migrated)
            $order->customer_name = $customer->name;
            $order->customer_phone = $customer->mobile;
            $order->customer_address = $customer->address;

            $order->status = $this->resolveOrderStatus(
                $validated['order_status'] ?? 'pending',
                (float) ($validated['discount_amount'] ?? 0),
                $validated['payment_method'] ?? 'COD'
            );
            
            // New Fields
            $order->courier_id = $validated['courier_id'] ?? null;
            $order->courier_charge = $validated['courier_charge'] ?? 0;
            $order->discount_amount = $validated['discount_amount'] ?? 0;
            $order->payment_method = $validated['payment_method'] ?? 'COD';
            $requestedCallStatus = $validated['call_status'] ?? 'pending';
            $order->call_status = $order->status === 'cancel'
                ? 'cancel'
                : ($requestedCallStatus === 'cancel' ? 'pending' : $requestedCallStatus);
            $order->delivery_status = $this->normalizeDeliveryStatus('pending', (string) $order->status);
            $this->applyDeliveryTimelineTimestamps($order);
            $order->sales_note = $validated['sales_note'] ?? null;
            
            // Capture Address Snapshot
            $order->customer_city = $selectedCity->city_name;
            $order->customer_district = $selectedCity->district;
            $order->customer_province = $selectedCity->province;
            
            $order->save();

            $totalAmount = 0;
            $totalCost = 0;
            $totalCommission = 0;

            // 3. Process Items
            foreach ($validated['items'] as $itemData) {
                $variant = ProductVariant::with(['product', 'unit'])->find($itemData['id']);
                $variantDisplayName = $this->buildVariantDisplayName($variant);
                
                // Stock Validation (Optional: Validation rule could handle this, but explicit check is safer)
                if ($variant->quantity < $itemData['quantity']) {
                    throw new \Exception("Insufficient stock for {$variantDisplayName} (SKU: {$variant->sku})");
                }
                
                // Limit Price Validation
                if ($itemData['selling_price'] < $variant->limit_price) {
                     throw new \Exception("Selling price for {$variantDisplayName} (SKU: {$variant->sku}) cannot be lower than limit price ({$variant->limit_price})");
                }

                $qty = $itemData['quantity'];
                $unitPrice = $itemData['selling_price'];
                $basePrice = $variant->limit_price; // Assuming limit_price IS the base/cost price for commission calc
                $subtotal = $unitPrice * $qty;
                
                $itemCost = $basePrice * $qty;
                $itemCommission = ($unitPrice - $basePrice) * $qty;

                // Create Order Item
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_variant_id' => $variant->id,
                    'product_name' => $variantDisplayName, // Snapshot
                    'sku' => $variant->sku,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'base_price' => $basePrice,
                    'total_price' => $subtotal,
                    'subtotal' => $subtotal,
                ]);

                // Accumulate Totals
                $totalAmount += $subtotal;
                $totalCost += $itemCost;
                
                // Commission only applies for Reseller orders
                if ($order->order_type === 'reseller') {
                    $totalCommission += $itemCommission;
                }
            }

            // 4. Update Order Totals
            if (($validated['discount_amount'] ?? 0) > $totalAmount) {
                throw ValidationException::withMessages([
                    'discount_amount' => 'Discount cannot exceed item subtotal.',
                ]);
            }

            $order->discount_amount = min($validated['discount_amount'] ?? 0, $totalAmount);
            $order->total_amount = max($totalAmount - $order->discount_amount, 0) + $order->courier_charge;
            $order->total_cost = $totalCost;
            $order->total_commission = $totalCommission;
            $paymentDetails = $this->resolvePaymentDetails(
                (string) ($order->payment_method ?? 'COD'),
                $validated['payments'] ?? [],
                $validated['paid_amount'] ?? null,
                (float) $order->total_amount
            );
            $order->paid_amount = $paymentDetails['paid_amount'];
            $order->payments_data = $paymentDetails['payments_data'];
            $order->payment_status = $this->resolvePaymentStatus(
                $validated['payment_status'] ?? 'pending',
                (string) ($order->payment_method ?? 'COD'),
                (float) $paymentDetails['paid_amount'],
                (float) $order->total_amount,
                (string) ($order->delivery_status ?? 'pending')
            );
            $order->save();
            $this->syncOrderInventoryState($order, Auth::id());
            $this->syncOrderCommission($order);
            $this->syncResellerReturnFeePenalty($order);

            // 5. Log Action
            $this->logAction($order->id, 'created', 'Order created successfully.');

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Order created successfully!',
                'redirect' => route('orders.index'),
                'order_number' => $orderNumber
            ]);
            
        } catch (QueryException $e) {
            DB::rollBack();

            if ($this->isOrderNumberUniqueViolation($e)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order ID collision detected. Please try submitting again.',
                ], 422);
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }
    
    /**
     * Generate unique order number.
     */
    private function generateOrderNumber()
    {
        $today = now();
        $dateStr = $today->format('Ymd');
        $sequence = $this->getNextOrderSequenceForDate($today);

        do {
            $number = 'ORD-' . $dateStr . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
            $exists = Order::withTrashed()->where('order_number', $number)->exists();
            if ($exists) {
                $sequence++;
            }
        } while ($exists);
        
        return $number;
    }

    private function getNextOrderSequenceForDate($date): int
    {
        $dateStr = $date->format('Ymd');
        $prefix = 'ORD-' . $dateStr . '-';

        $latestNumber = Order::withTrashed()
            ->where('order_number', 'like', $prefix . '%')
            ->orderByDesc('order_number')
            ->value('order_number');

        if (!$latestNumber) {
            return 1;
        }

        $parts = explode('-', (string) $latestNumber);
        if (count($parts) === 3 && $parts[1] === $dateStr && ctype_digit((string) $parts[2])) {
            return ((int) $parts[2]) + 1;
        }

        return 1;
    }

    private function isOrderNumberUniqueViolation(QueryException $exception): bool
    {
        $message = strtolower($exception->getMessage());

        if (!str_contains($message, 'order_number')) {
            return false;
        }

        return str_contains($message, 'unique')
            || str_contains($message, 'duplicate')
            || str_contains($message, 'constraint failed');
    }
    
    /**
     * Log action helper.
     */
    private function logAction($orderId, $action, $description = null)
    {
        OrderLog::create([
            'order_id' => $orderId,
            'user_id' => Auth::id(),
            'action' => $action,
            'description' => $description,
        ]);
    }
    /**
     * Display the specified order (Invoice View).
     */
    public function show(Order $order)
    {
        $order->load(['items.variant.unit', 'items.variant.product', 'items.inventoryUnits.purchase', 'customer', 'reseller', 'user', 'courier']);
        return view('orders.show', compact('order'));
    }

    /**
     * Print-friendly order invoice view (standalone page, no app chrome).
     */
    public function printView(Order $order)
    {
        $order->load(['items.variant.unit', 'items.variant.product', 'items.inventoryUnits.purchase', 'customer', 'reseller', 'user', 'courier']);

        return view('orders.print', compact('order'));
    }

    /**
     * Download the order as PDF.
     */
    public function downloadPdf(Order $order)
    {
        $order->load(['items.variant.unit', 'items.variant.product', 'items.inventoryUnits.purchase', 'customer', 'reseller', 'user', 'courier']);
        $pdf = Pdf::loadView('orders.pdf', compact('order'))->setPaper('a4');
        return $pdf->download('invoice-' . $order->order_number . '.pdf');
    }

    /**
     * Download selected order invoices as a ZIP file.
     */
    public function downloadBulkPdf(Request $request)
    {
        $validated = $request->validate([
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'integer|exists:orders,id',
        ]);

        $requestedIds = collect($validated['order_ids'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $orders = Order::with(['items.variant.unit', 'items.inventoryUnits.purchase', 'customer', 'reseller', 'user', 'courier'])
            ->whereIn('id', $requestedIds)
            ->get()
            ->sortBy(fn (Order $order) => $requestedIds->search($order->id))
            ->values();

        if ($orders->isEmpty()) {
            return back()->with('error', 'No orders selected to download.');
        }

        $fileName = 'order_invoices_' . now()->format('Y-m-d_His') . '.zip';
        $tempDirectory = storage_path('app/temp');
        $zipPath = $tempDirectory . DIRECTORY_SEPARATOR . $fileName;

        if (!is_dir($tempDirectory) && !mkdir($tempDirectory, 0755, true) && !is_dir($tempDirectory)) {
            return back()->with('error', 'Could not prepare temporary download directory.');
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'Could not create ZIP file.');
        }

        foreach ($orders as $order) {
            $pdf = Pdf::loadView('orders.pdf', ['order' => $order])->setPaper('a4');
            $safeOrderNumber = preg_replace('/[^A-Za-z0-9_-]/', '_', (string) $order->order_number);
            $zip->addFromString('invoice-' . $safeOrderNumber . '.pdf', $pdf->output());
        }

        $zip->close();

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }

    /**
     * Show the form for editing the specified order.
     */
    public function edit(Order $order)
    {
        $order->load(['items.variant.unit', 'customer', 'reseller']);
        $couriers = Courier::all();
        $courierRatesMap = $this->buildCourierRatesMap($couriers);
        $cities = City::orderBy('city_name')->get(['id', 'city_name', 'district', 'province', 'postal_code']);
        $matchedCity = City::where('city_name', $order->customer_city ?: ($order->customer->city ?? ''))->first();
        $order->selected_city_id = $matchedCity?->id;
        
        return view('orders.edit', [
            'order' => $order,
            'orderFull' => $order,
            'couriers' => $couriers,
            'courierRatesMap' => $courierRatesMap,
            'cities' => $cities,
        ]);
    }

    /**
     * Update the specified order in storage.
     */
    public function update(Request $request, Order $order)
    {
        $isCoreLocked = strtolower((string) $order->status) !== 'pending';
        if ($isCoreLocked) {
            $validated = $request->validate([
                'paid_amount' => 'nullable|numeric|min:0',
                'payments' => 'nullable|array',
                'payments.*.amount' => 'required_with:payments|numeric|min:0.01',
                'payments.*.date' => 'required_with:payments|date',
                'payments.*.note' => 'nullable|string|max:255',
                'payment_status' => 'nullable|in:pending,paid',
                'sales_note' => 'nullable|string',
            ]);

            DB::beginTransaction();
            try {
                $order->sales_note = $validated['sales_note'] ?? $order->sales_note;

                $paymentDetails = $this->resolvePaymentDetails(
                    (string) ($order->payment_method ?? 'COD'),
                    $validated['payments'] ?? [],
                    $validated['paid_amount'] ?? null,
                    (float) $order->total_amount
                );
                $order->paid_amount = $paymentDetails['paid_amount'];
                $order->payments_data = $paymentDetails['payments_data'];
                $order->payment_status = $this->resolvePaymentStatus(
                    $validated['payment_status'] ?? $order->payment_status ?? 'pending',
                    (string) ($order->payment_method ?? 'COD'),
                    (float) $paymentDetails['paid_amount'],
                    (float) $order->total_amount,
                    (string) ($order->delivery_status ?? 'pending')
                );
                $order->save();
                $this->syncOrderCommission($order);
                $this->syncResellerReturnFeePenalty($order);

                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Order updated successfully!',
                    'redirect' => route('orders.index'),
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 422);
            }
        }

        $validated = $request->validate([
            'order_type' => 'required|in:reseller,direct',
            'reseller_id' => [
                'required_if:order_type,reseller',
                'nullable',
                Rule::exists('resellers', 'id')->where(function ($query) {
                    $query->whereIn('reseller_type', [
                        Reseller::TYPE_RESELLER,
                        Reseller::TYPE_DIRECT_RESELLER,
                    ]);
                }),
            ],
            
            // Customer Details
            'customer.name' => 'required|string|max:255',
            'customer.mobile' => ['required', 'regex:/^\d{10}$/'],
            'customer.landline' => ['nullable', 'regex:/^\d{10}$/'],
            'customer.address' => 'required|string',
            'customer.city_id' => 'required|exists:cities,id',
            
            // Products
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.selling_price' => 'required|numeric|min:0',

            // Fulfillment & Address
            'courier_id' => 'nullable|exists:couriers,id',
            'courier_charge' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string|in:COD,Online Payment,Bank Transfer',
            'paid_amount' => 'nullable|numeric|min:0',
            'payments' => 'nullable|array',
            'payments.*.amount' => 'required_with:payments|numeric|min:0.01',
            'payments.*.date' => 'required_with:payments|date',
            'payments.*.note' => 'nullable|string|max:255',
            'payment_status' => 'nullable|in:pending,paid',
            'call_status' => 'nullable|in:pending,confirm,hold,cancel',
            'sales_note' => 'nullable|string',
            'order_status' => 'nullable|in:pending,hold,confirm,cancel',
            'delivery_status' => 'nullable|in:' . implode(',', self::DELIVERY_STATUSES),
            'customer.district' => 'nullable|string',
            'customer.province' => 'nullable|string',
        ]);

        $this->validateCourierChargeSelection(
            isset($validated['courier_id']) ? (int) $validated['courier_id'] : null,
            $validated['courier_charge'] ?? null
        );

        if (empty($validated['courier_id'])) {
            $validated['courier_charge'] = 0;
        } else {
            $validated['courier_charge'] = (float) $this->normalizeRateForComparison($validated['courier_charge']);
        }
        $validated['discount_amount'] = isset($validated['discount_amount'])
            ? round((float) $validated['discount_amount'], 2)
            : 0.0;

        DB::beginTransaction();
        try {
            $selectedCity = City::findOrFail($validated['customer']['city_id']);

            $this->inventoryUnits()->releaseOrderUnits($order, 'released_for_order_update', Auth::id());
            
            // 2. Clear OLD items
            $order->items()->delete();

            // 3. Update Customer & Order Details
            $customer = Customer::updateOrCreate(
                ['mobile' => $validated['customer']['mobile']],
                $this->buildCustomerDataFromOrderInput($validated['customer'], $selectedCity)
            );

            // Order date is system-managed and not editable after creation.
            $order->order_type = $validated['order_type'];
            $order->reseller_id = $validated['order_type'] === 'reseller' ? $validated['reseller_id'] : null;
            $order->customer_id = $customer->id;
            // Legacy fields update
            $order->customer_name = $customer->name;
            $order->customer_phone = $customer->mobile;
            $order->customer_address = $customer->address;
            
             // Create/Update Logic for New Fields
            $previousOrderStatus = (string) $order->status;
            $previousDeliveryStatus = (string) ($order->delivery_status ?? 'pending');

            $order->status = $validated['order_status'] ?? $order->status;
            $order->courier_id = $validated['courier_id'] ?? null;
            $order->courier_charge = $validated['courier_charge'] ?? 0;
            $order->discount_amount = $validated['discount_amount'] ?? 0;
            $order->payment_method = $validated['payment_method'] ?? 'COD';
            $requestedCallStatus = $validated['call_status'] ?? $order->call_status;
            $order->call_status = $order->status === 'cancel'
                ? 'cancel'
                : (($requestedCallStatus === 'cancel' || empty($requestedCallStatus)) ? 'pending' : $requestedCallStatus);
            $requestedDeliveryStatus = $validated['delivery_status'] ?? $order->delivery_status;
            if (strtolower((string) $order->status) !== 'cancel') {
                $this->assertDeliveryStatusTransitionAllowed($requestedDeliveryStatus, $previousDeliveryStatus);
            }
            $order->delivery_status = $this->normalizeDeliveryStatus($requestedDeliveryStatus, (string) $order->status);
            $this->applyDeliveryTimelineTimestamps($order, $previousOrderStatus, $previousDeliveryStatus);
            $order->sales_note = $validated['sales_note'] ?? null;
             // Capture Address Snapshot
            $order->customer_city = $selectedCity->city_name;
            $order->customer_district = $selectedCity->district;
            $order->customer_province = $selectedCity->province;

            $order->save();

            $totalAmount = 0;
            $totalCost = 0;
            $totalCommission = 0;

            // 4. Process NEW Items
            foreach ($validated['items'] as $itemData) {
                $variant = ProductVariant::with(['product', 'unit'])->find($itemData['id']);
                $variantDisplayName = $this->buildVariantDisplayName($variant);
                
                // Stock Check
                if ($variant->quantity < $itemData['quantity']) {
                    throw new \Exception("Insufficient stock for {$variantDisplayName} (SKU: {$variant->sku})");
                }
                
                // Limit Price Check
                if ($itemData['selling_price'] < $variant->limit_price) {
                     throw new \Exception("Selling price for {$variantDisplayName} (SKU: {$variant->sku}) cannot be lower than limit price ({$variant->limit_price})");
                }

                $qty = $itemData['quantity'];
                $unitPrice = $itemData['selling_price'];
                $basePrice = $variant->limit_price;
                $subtotal = $unitPrice * $qty;
                
                $itemCost = $basePrice * $qty;
                $itemCommission = ($unitPrice - $basePrice) * $qty;

                // Create Item
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_variant_id' => $variant->id,
                    'product_name' => $variantDisplayName,
                    'sku' => $variant->sku,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'base_price' => $basePrice, 
                    'total_price' => $subtotal,
                    'subtotal' => $subtotal,
                ]);

                // Accumulate totals
                $totalAmount += $subtotal;
                $totalCost += $itemCost;
                
                if ($order->order_type === 'reseller') {
                    $totalCommission += $itemCommission;
                }
            }

            // 5. Update Order Totals
            if (($validated['discount_amount'] ?? 0) > $totalAmount) {
                throw ValidationException::withMessages([
                    'discount_amount' => 'Discount cannot exceed item subtotal.',
                ]);
            }

            $order->discount_amount = min($validated['discount_amount'] ?? 0, $totalAmount);
            $order->total_amount = max($totalAmount - $order->discount_amount, 0) + $order->courier_charge;
            $order->total_cost = $totalCost;
            $order->total_commission = $totalCommission;
            $paymentDetails = $this->resolvePaymentDetails(
                (string) ($order->payment_method ?? 'COD'),
                $validated['payments'] ?? [],
                $validated['paid_amount'] ?? null,
                (float) $order->total_amount
            );
            $order->paid_amount = $paymentDetails['paid_amount'];
            $order->payments_data = $paymentDetails['payments_data'];
            $order->payment_status = $this->resolvePaymentStatus(
                $validated['payment_status'] ?? $order->payment_status ?? 'pending',
                (string) ($order->payment_method ?? 'COD'),
                (float) $paymentDetails['paid_amount'],
                (float) $order->total_amount,
                (string) ($order->delivery_status ?? 'pending')
            );
            $order->save();
            $this->syncOrderInventoryState($order, Auth::id());
            $this->syncOrderCommission($order);
            $this->syncResellerReturnFeePenalty($order);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Order updated successfully!',
                'redirect' => route('orders.index'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Remove the specified order from storage.
     */
    public function destroy(Order $order)
    {
        DB::beginTransaction();
        try {
            $this->releaseResellerReturnFeePenalty($order);

            $this->inventoryUnits()->releaseOrderUnits($order, 'released_on_order_delete', Auth::id());

            // Delete Order (Cascades items)
            $order->delete();

            DB::commit();
            return redirect()->route('orders.index')->with('success', 'Order deleted successfully and allocated stock was released.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to delete order: ' . $e->getMessage());
        }
    }
    /**
     * Display the Call List for orders.
     */
    public function callList(Request $request)
    {
        $query = Order::with(['customer', 'reseller', 'items.inventoryUnits.purchase', 'user', 'courier'])
            ->where('status', '!=', 'cancel');
        
        // Default to 'pending' call status if not specified, 
        // OR user might want to see all. Let's start with all but maybe sort by pending.
        // Actually, user said "Focused and filtering on call status".
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($subQ) use ($search) {
                      $subQ->where('name', 'like', "%{$search}%")
                           ->orWhere('mobile', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('call_status')) {
            $query->where('call_status', $request->call_status);
        }
        
        if ($request->filled('date_from')) {
            $query->whereDate('order_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('order_date', '<=', $request->date_to);
        }

        $orders = $query->latest()->paginate(20)->withQueryString();

        return view('orders.call_list', compact('orders'));
    }

    /**
     * Update Order or Call Status via AJAX.
     */
    public function updateStatus(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        $validated = $request->validate([
            'status' => 'nullable|in:pending,hold,confirm,cancel',
            'call_status' => 'nullable|in:pending,confirm,hold',
            'delivery_status' => 'nullable|in:' . implode(',', self::DELIVERY_STATUSES),
            'sales_note' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $previousOrderStatus = (string) $order->status;
            $previousDeliveryStatus = (string) ($order->delivery_status ?? 'pending');

            $statusChanged = false;
            if (array_key_exists('status', $validated)) {
                $order->status = $validated['status'];
                $statusChanged = true;
            }

            if ($order->status === 'cancel') {
                $order->call_status = 'cancel';
                $order->delivery_status = 'cancel';
            } elseif (array_key_exists('call_status', $validated)) {
                $order->call_status = $validated['call_status'];
            } elseif ($statusChanged && $order->call_status === 'cancel') {
                $order->call_status = 'pending';
            }

            if ($order->status !== 'cancel' && array_key_exists('delivery_status', $validated)) {
                $this->assertDeliveryStatusTransitionAllowed($validated['delivery_status'], $previousDeliveryStatus);
                $order->delivery_status = $this->normalizeDeliveryStatus($validated['delivery_status'], (string) $order->status);
            } elseif ($statusChanged && $order->status !== 'cancel' && $order->delivery_status === 'cancel') {
                $order->delivery_status = 'pending';
            }

            if (array_key_exists('sales_note', $validated)) {
                $order->sales_note = $validated['sales_note'];
            }

            $this->applyDeliveryTimelineTimestamps($order, $previousOrderStatus, $previousDeliveryStatus);
            $order->save();
            $this->syncOrderInventoryState($order, Auth::id());
            $this->syncOrderCommission($order);
            $this->syncResellerReturnFeePenalty($order);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully!',
                'call_status' => $order->call_status,
                'status' => $order->status,
                'delivery_status' => $order->delivery_status,
            ]);
        } catch (ValidationException $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?: 'Unable to update order status.',
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    private function buildCourierRatesMap($couriers): array
    {
        return collect($couriers)
            ->mapWithKeys(function (Courier $courier) {
                $rates = collect($courier->rates ?? [])
                    ->map(fn ($rate) => number_format((float) $rate, 2, '.', ''))
                    ->values()
                    ->all();

                return [(string) $courier->id => $rates];
            })
            ->all();
    }

    private function mustKeepOrderPending(float $discountAmount, ?string $paymentMethod): bool
    {
        return $discountAmount > 0 || $paymentMethod === 'Online Payment';
    }

    private function resolveOrderStatus(?string $requestedStatus, float $discountAmount, ?string $paymentMethod): string
    {
        if ($this->mustKeepOrderPending($discountAmount, $paymentMethod)) {
            return 'pending';
        }

        return $requestedStatus ?: 'pending';
    }

    private function normalizeDeliveryStatus(?string $requestedStatus, string $orderStatus): string
    {
        if ($orderStatus === 'cancel') {
            return 'cancel';
        }

        if (!in_array($requestedStatus, self::DELIVERY_STATUSES, true)) {
            return 'pending';
        }

        if ($requestedStatus === 'cancel') {
            return 'pending';
        }

        return $requestedStatus;
    }

    private function assertDeliveryStatusTransitionAllowed(?string $requestedStatus, ?string $previousStatus): void
    {
        $requestedStatus = strtolower((string) ($requestedStatus ?? 'pending'));
        $previousStatus = strtolower((string) ($previousStatus ?? 'pending'));

        if ($requestedStatus === 'delivered' && !in_array($previousStatus, ['dispatched', 'delivered'], true)) {
            throw ValidationException::withMessages([
                'delivery_status' => 'Only dispatched orders can be marked as delivered.',
            ]);
        }
    }

    private function applyDeliveryTimelineTimestamps(
        Order $order,
        ?string $previousOrderStatus = null,
        ?string $previousDeliveryStatus = null
    ): void {
        $orderStatus = strtolower((string) ($order->status ?? ''));
        $deliveryStatus = strtolower((string) ($order->delivery_status ?? 'pending'));
        $previousOrderStatus = strtolower((string) ($previousOrderStatus ?? ''));
        $previousDeliveryStatus = strtolower((string) ($previousDeliveryStatus ?? ''));

        if (
            $orderStatus === 'cancel'
            && ($previousOrderStatus !== 'cancel' || !$order->cancelled_at)
        ) {
            $this->setOrderTimelineTimestamp($order, 'cancelled_at');
        }

        if ($deliveryStatus === 'waybill_printed' && ($previousDeliveryStatus !== 'waybill_printed' || !$order->waybill_printed_at)) {
            $this->setOrderTimelineTimestamp($order, 'waybill_printed_at');
        }

        if ($deliveryStatus === 'picked_from_rack' && ($previousDeliveryStatus !== 'picked_from_rack' || !$order->picked_at)) {
            $this->setOrderTimelineTimestamp($order, 'picked_at');
        }

        if ($deliveryStatus === 'packed' && ($previousDeliveryStatus !== 'packed' || !$order->packed_at)) {
            $this->setOrderTimelineTimestamp($order, 'packed_at');
        }

        if ($deliveryStatus === 'dispatched' && ($previousDeliveryStatus !== 'dispatched' || !$order->dispatched_at)) {
            $this->setOrderTimelineTimestamp($order, 'dispatched_at');
        }

        if ($deliveryStatus === 'delivered' && ($previousDeliveryStatus !== 'delivered' || !$order->delivered_at)) {
            $this->setOrderTimelineTimestamp($order, 'delivered_at');
        }

        if ($deliveryStatus === 'returned' && ($previousDeliveryStatus !== 'returned' || !$order->returned_at)) {
            $this->setOrderTimelineTimestamp($order, 'returned_at');
        }

        if ($deliveryStatus === 'cancel' && ($previousDeliveryStatus !== 'cancel' || !$order->cancelled_at)) {
            $this->setOrderTimelineTimestamp($order, 'cancelled_at');
        }
    }

    private function setOrderTimelineTimestamp(Order $order, string $field): void
    {
        if (!isset($order->{$field}) || !$order->{$field}) {
            $order->{$field} = now();
        }
    }

    private function normalizeSearchText(string $value): string
    {
        $normalized = mb_strtolower(trim($value));
        return preg_replace('/\s+/', ' ', $normalized) ?? '';
    }

    private function buildCustomerDataFromOrderInput(array $customerInput, City $selectedCity): array
    {
        $country = trim((string) ($customerInput['country'] ?? ''));
        if ($country === '') {
            $country = self::DEFAULT_CUSTOMER_COUNTRY;
        }

        $province = trim((string) ($selectedCity->province ?? ''));
        if ($province === '') {
            $province = trim((string) ($customerInput['province'] ?? ''));
        }

        $district = trim((string) ($selectedCity->district ?? ''));
        if ($district === '') {
            $district = trim((string) ($customerInput['district'] ?? ''));
        }

        $city = trim((string) ($selectedCity->city_name ?? ''));
        if ($city === '') {
            $city = trim((string) ($customerInput['city'] ?? ''));
        }

        return [
            'name' => trim((string) ($customerInput['name'] ?? '')),
            'landline' => (($customerInput['landline'] ?? null) === '' ? null : ($customerInput['landline'] ?? null)),
            'address' => trim((string) ($customerInput['address'] ?? '')),
            'country' => $country,
            'province' => $province,
            'district' => $district,
            'city' => $city,
        ];
    }

    private function buildProductSearchScore(string $normalizedTerm, array $termTokens, array $fields, int $stock): int
    {
        if ($normalizedTerm === '') {
            return 0;
        }

        $productName = $this->normalizeSearchText((string) ($fields['product_name'] ?? ''));
        $displayName = $this->normalizeSearchText((string) ($fields['display_name'] ?? ''));
        $sku = $this->normalizeSearchText((string) ($fields['sku'] ?? ''));
        $unitLabel = $this->normalizeSearchText((string) ($fields['unit_label'] ?? ''));
        $description = $this->normalizeSearchText((string) ($fields['description'] ?? ''));

        $score = 0;

        if ($productName === $normalizedTerm) {
            $score = max($score, 1000);
        } elseif (str_starts_with($productName, $normalizedTerm)) {
            $score = max($score, 920);
        } elseif (str_contains($productName, $normalizedTerm)) {
            $score = max($score, 860);
        }

        if ($displayName === $normalizedTerm) {
            $score = max($score, 910);
        } elseif (str_starts_with($displayName, $normalizedTerm)) {
            $score = max($score, 870);
        } elseif (str_contains($displayName, $normalizedTerm)) {
            $score = max($score, 820);
        }

        if ($sku === $normalizedTerm) {
            $score = max($score, 760);
        } elseif (str_starts_with($sku, $normalizedTerm)) {
            $score = max($score, 700);
        } elseif (str_contains($sku, $normalizedTerm)) {
            $score = max($score, 620);
        }

        if ($unitLabel === $normalizedTerm) {
            $score = max($score, 600);
        } elseif (str_contains($unitLabel, $normalizedTerm)) {
            $score = max($score, 520);
        }

        if (str_contains($description, $normalizedTerm)) {
            $score = max($score, 360);
        }

        if (!empty($termTokens)) {
            $productHits = 0;
            $displayHits = 0;

            foreach ($termTokens as $token) {
                if ($token === '') {
                    continue;
                }
                if (str_contains($productName, $token)) {
                    $productHits++;
                }
                if (str_contains($displayName, $token)) {
                    $displayHits++;
                }
            }

            if ($productHits > 0) {
                $score += $productHits === count($termTokens) ? 90 : ($productHits * 20);
            }
            if ($displayHits > 0) {
                $score += $displayHits === count($termTokens) ? 60 : ($displayHits * 12);
            }
        }

        $score += (int) floor(min(max($stock, 0), 200) / 25);

        return $score;
    }

    private function buildVariantUnitLabel(ProductVariant $variant): string
    {
        $parts = [];
        if ($variant->unit_value !== null && $variant->unit_value !== '') {
            $parts[] = trim((string) $variant->unit_value);
        }
        if ($variant->unit && $variant->unit->short_name) {
            $parts[] = trim((string) $variant->unit->short_name);
        }

        return trim(implode(' ', $parts));
    }

    private function buildVariantDisplayName(ProductVariant $variant): string
    {
        $baseName = $variant->product->name ?? 'Product';
        $unitLabel = $this->buildVariantUnitLabel($variant);

        if ($unitLabel === '') {
            return $baseName;
        }

        return "{$baseName} ({$unitLabel})";
    }

    private function validateCourierChargeSelection(?int $courierId, $courierCharge): void
    {
        if (!$courierId) {
            return;
        }

        $courier = Courier::find($courierId);
        if (!$courier) {
            throw ValidationException::withMessages([
                'courier_id' => 'Selected courier is invalid.',
            ]);
        }

        $rates = collect($courier->rates ?? [])
            ->map(fn ($rate) => $this->normalizeRateForComparison($rate))
            ->filter()
            ->unique()
            ->values();

        if ($rates->isEmpty()) {
            throw ValidationException::withMessages([
                'courier_charge' => 'Selected courier has no configured delivery charge values.',
            ]);
        }

        $normalizedCharge = $this->normalizeRateForComparison($courierCharge);
        if ($normalizedCharge === null || !$rates->contains($normalizedCharge)) {
            throw ValidationException::withMessages([
                'courier_charge' => 'Select a valid delivery charge from the selected courier list.',
            ]);
        }
    }

    private function normalizeRateForComparison($rate): ?string
    {
        if ($rate === null || $rate === '') {
            return null;
        }

        if (!is_numeric($rate)) {
            return null;
        }

        return number_format((float) $rate, 2, '.', '');
    }

    private function orderShouldHoldInventory(Order $order): bool
    {
        if ((string) ($order->status ?? '') === 'cancel') {
            return false;
        }

        return strtolower((string) ($order->delivery_status ?? 'pending')) !== 'returned';
    }

    private function syncOrderInventoryState(Order $order, ?int $userId = null): void
    {
        $order->loadMissing(['items.variant.product', 'items.variant.unit']);

        if (!$this->orderShouldHoldInventory($order)) {
            $eventType = (string) ($order->status ?? '') === 'cancel'
                ? 'released_on_cancel'
                : 'released_on_return';

            $this->inventoryUnits()->releaseOrderUnits($order, $eventType, $userId);

            return;
        }

        $this->inventoryUnits()->ensureOrderUnitsAllocated($order, $userId);

        if (strtolower((string) ($order->delivery_status ?? 'pending')) === 'delivered') {
            $this->inventoryUnits()->markOrderUnitsDelivered($order, $userId);
        } else {
            $this->inventoryUnits()->markOrderUnitsAllocated($order, $userId);
        }
    }

    private function shouldApplyResellerReturnFee(Order $order): bool
    {
        if ((string) ($order->order_type ?? '') !== 'reseller') {
            return false;
        }

        if ((string) ($order->status ?? '') === 'cancel') {
            return false;
        }

        return strtolower((string) ($order->delivery_status ?? '')) === 'returned';
    }

    private function shouldCalculateOrderCommission(Order $order): bool
    {
        if ((string) ($order->order_type ?? '') !== 'reseller') {
            return false;
        }

        if ((string) ($order->status ?? '') === 'cancel') {
            return false;
        }

        if (strtolower((string) ($order->delivery_status ?? '')) === 'returned') {
            return false;
        }

        if (!$order->reseller_id) {
            return false;
        }

        $reseller = $order->relationLoaded('reseller')
            ? $order->reseller
            : Reseller::find($order->reseller_id);

        return $reseller && $reseller->reseller_type === Reseller::TYPE_RESELLER;
    }

    private function syncOrderCommission(Order $order): void
    {
        $order->loadMissing(['items', 'reseller']);

        $commission = 0.0;
        if ($this->shouldCalculateOrderCommission($order)) {
            $grossCommission = (float) $order->items->sum(function (OrderItem $item) {
                $qty = max((int) ($item->quantity ?? 0), 0);
                $marginPerItem = (float) ($item->unit_price ?? 0) - (float) ($item->base_price ?? 0);

                return max($marginPerItem, 0) * $qty;
            });

            // Discounts reduce realizable margin/commission on the order.
            $commission = max($grossCommission - (float) ($order->discount_amount ?? 0), 0);
        }

        $normalizedCommission = round($commission, 2);
        if ((float) ($order->total_commission ?? 0) !== $normalizedCommission) {
            $order->total_commission = $normalizedCommission;
            $order->save();
        }
    }

    private function syncResellerReturnFeePenalty(Order $order): void
    {
        $previousAppliedAmount = round((float) ($order->reseller_return_fee_applied ?? 0), 2);
        $previousAppliedResellerId = $order->return_fee_reseller_id ? (int) $order->return_fee_reseller_id : null;

        $targetResellerId = null;
        $targetAppliedAmount = 0.0;

        if ($this->shouldApplyResellerReturnFee($order) && $order->reseller_id) {
            $targetReseller = $order->relationLoaded('reseller')
                ? $order->reseller
                : Reseller::find($order->reseller_id);

            if ($targetReseller && $targetReseller->reseller_type === Reseller::TYPE_RESELLER) {
                $targetResellerId = (int) $targetReseller->id;
                $targetAppliedAmount = round(max((float) ($targetReseller->return_fee ?? 0), 0), 2);
            }
        }

        if (
            $previousAppliedResellerId === $targetResellerId
            && abs($previousAppliedAmount - $targetAppliedAmount) < 0.0001
        ) {
            return;
        }

        if ($previousAppliedResellerId && $previousAppliedAmount > 0) {
            $previousReseller = Reseller::find($previousAppliedResellerId);
            if ($previousReseller) {
                $previousReseller->due_amount = round((float) $previousReseller->due_amount + $previousAppliedAmount, 2);
                $previousReseller->save();
            }
        }

        if ($targetResellerId && $targetAppliedAmount > 0) {
            $targetReseller = Reseller::find($targetResellerId);
            if ($targetReseller) {
                $targetReseller->due_amount = round((float) $targetReseller->due_amount - $targetAppliedAmount, 2);
                $targetReseller->save();
            }
        }

        $order->reseller_return_fee_applied = $targetAppliedAmount;
        $order->return_fee_reseller_id = $targetResellerId;
        $order->save();

        if ($targetAppliedAmount > 0) {
            $this->logAction(
                $order->id,
                'return_fee_penalty',
                'Reseller return fee applied: LKR ' . number_format($targetAppliedAmount, 2, '.', '')
            );
        } elseif ($previousAppliedAmount > 0) {
            $this->logAction($order->id, 'return_fee_penalty_reversed', 'Reseller return fee penalty reversed.');
        }
    }

    private function releaseResellerReturnFeePenalty(Order $order): void
    {
        $appliedAmount = round((float) ($order->reseller_return_fee_applied ?? 0), 2);
        $appliedResellerId = $order->return_fee_reseller_id ? (int) $order->return_fee_reseller_id : null;

        if (!$appliedResellerId || $appliedAmount <= 0) {
            return;
        }

        $reseller = Reseller::find($appliedResellerId);
        if ($reseller) {
            $reseller->due_amount = round((float) $reseller->due_amount + $appliedAmount, 2);
            $reseller->save();
        }

        $order->reseller_return_fee_applied = 0;
        $order->return_fee_reseller_id = null;
        $order->save();

        $this->logAction($order->id, 'return_fee_penalty_reversed', 'Reseller return fee penalty reversed on order removal.');
    }

    private function resolvePaymentDetails(string $paymentMethod, array $paymentsInput, $paidAmountInput, float $totalAmount): array
    {
        $normalizedTotal = max(round($totalAmount, 2), 0);

        $paymentsData = collect($paymentsInput)
            ->filter(fn ($payment) => is_array($payment))
            ->map(function (array $payment) {
                $amount = isset($payment['amount']) ? round((float) $payment['amount'], 2) : 0;
                $date = isset($payment['date']) && $payment['date']
                    ? date('Y-m-d', strtotime((string) $payment['date']))
                    : now()->toDateString();

                return [
                    'amount' => $amount,
                    'date' => $date,
                    'note' => trim((string) ($payment['note'] ?? '')),
                ];
            })
            ->filter(fn (array $payment) => $payment['amount'] > 0)
            ->values()
            ->all();

        $paidAmount = round((float) collect($paymentsData)->sum('amount'), 2);

        if ($paidAmount <= 0 && $paidAmountInput !== null && $paidAmountInput !== '') {
            if (!is_numeric($paidAmountInput)) {
                throw ValidationException::withMessages([
                    'paid_amount' => 'Paid amount must be a valid number.',
                ]);
            }

            $legacyPaidAmount = round((float) $paidAmountInput, 2);
            if ($legacyPaidAmount < 0) {
                throw ValidationException::withMessages([
                    'paid_amount' => 'Paid amount cannot be negative.',
                ]);
            }

            if ($legacyPaidAmount > 0) {
                $paymentsData = [[
                    'amount' => $legacyPaidAmount,
                    'date' => now()->toDateString(),
                    'note' => 'Recorded from paid amount field.',
                ]];
                $paidAmount = $legacyPaidAmount;
            }
        }

        if ($paymentMethod === 'Online Payment' && empty($paymentsData)) {
            throw ValidationException::withMessages([
                'payments' => 'Add at least one payment entry for Online Payment orders.',
            ]);
        }

        if ($paidAmount > $normalizedTotal) {
            throw ValidationException::withMessages([
                'payments' => 'Total paid amount cannot exceed the order total.',
            ]);
        }

        $remaining = max($normalizedTotal - $paidAmount, 0);

        return [
            'paid_amount' => $paidAmount,
            'payments_data' => $paymentsData,
            'payment_status' => $remaining <= 0 ? 'paid' : 'pending',
        ];
    }

    private function resolvePaymentStatus(
        ?string $requestedStatus,
        string $paymentMethod,
        float $paidAmount,
        float $totalAmount,
        string $deliveryStatus
    ): string {
        $normalizedRequested = in_array($requestedStatus, ['pending', 'paid'], true)
            ? $requestedStatus
            : 'pending';

        $normalizedDelivery = strtolower(trim($deliveryStatus));
        $isOnlinePayment = trim($paymentMethod) === 'Online Payment';
        $normalizedTotal = max(round($totalAmount, 2), 0);
        $normalizedPaid = max(round($paidAmount, 2), 0);
        $isOnlineFullyPaid = $isOnlinePayment && $normalizedPaid >= $normalizedTotal;

        if ($normalizedDelivery === 'delivered' || $isOnlineFullyPaid) {
            return 'paid';
        }

        return $normalizedRequested;
    }

    private function inventoryUnits(): InventoryUnitService
    {
        return app(InventoryUnitService::class);
    }
}
