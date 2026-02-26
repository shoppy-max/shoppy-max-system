<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Product;
use App\Models\City;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Dashboard / Summary Report.
     */
    public function index()
    {
        // General Metrics
        $totalSales = Order::where('status', 'confirm')->sum('total_amount');
        $totalOrders = Order::count();
        $pendingOrders = Order::where('status', 'pending')->count();
        $todaySales = Order::where('status', 'confirm')->whereDate('created_at', Carbon::today())->sum('total_amount');
        
        // Month-wise Sales for Chart
        $monthlySales = Order::select(
            DB::raw('sum(total_amount) as sums'), 
            DB::raw("strftime('%Y-%m', created_at) as month")
        )
        ->groupBy('month')
        ->orderBy('month', 'desc')
        ->limit(6)
        ->get();

        return view('reports.index', compact('totalSales', 'totalOrders', 'pendingOrders', 'todaySales', 'monthlySales'));
    }

    /**
     * Province Wise Sale Report (Table + Chart).
     */
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

    /**
     * Profit / Loss Report.
     */
    public function profitLoss(Request $request)
    {
        $query = Order::with(['items', 'courierPayment'])
            ->where('status', 'confirm');
            
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('created_at', [$request->input('start_date'), $request->input('end_date')]);
        }
        
        $orders = $query->get();
        
        $data = [
            'total_sales' => 0,
            'cogs' => 0, // Cost of Goods Sold from FIFO
            'courier_cost' => 0, // Real courier cost
            'delivery_income' => 0, // Delivery fees charged
            'gross_profit' => 0,
            'net_profit' => 0
        ];
        
        foreach ($orders as $order) {
            $data['total_sales'] += $order->total_amount; // Includes product price + delivery fee usually, assuming total_amount is final bill.
            
            // COGS
            $orderCogs = $order->items->sum('total_price') - $order->items->sum(function($item){
                 return ($item->unit_price - $item->cost_price) * $item->quantity; 
                 // Wait, cost_price is unit cost. 
                 // Margin = (Unit Price - Cost Price) * Qty
                 // COGS = Cost Price * Qty
            });
            // Simpler:
            $orderCogs = $order->items->sum(function($item) {
                return $item->cost_price * $item->quantity;
            });
            $data['cogs'] += $orderCogs;
            
            // Logistics
            $data['courier_cost'] += $order->courier_cost; // What we pay courier
            $data['delivery_income'] += $order->delivery_fee; // What we charge customer (if separated in total, or part of it)
            // Assuming total_amount includes delivery_fee.
        }
        
        $data['gross_profit'] = $data['total_sales'] - $data['cogs'];
        $data['net_profit'] = $data['gross_profit'] - $data['courier_cost'];
        // Note: Operational expenses (OpEx) aren't tracked here yet.
        
        return view('reports.profit_loss', compact('data'));
    }

    /**
     * Stock Report (Aging/Value).
     */
    public function stockReport()
    {
        $products = Product::with(['purchaseItems' => function($q) {
            $q->where('remaining_quantity', '>', 0);
        }])->get();
        
        // Calculate Valuation per product based on FIFO batches
        $products->map(function($product) {
            $product->stock_value = $product->purchaseItems->sum(function($item) {
                return $item->remaining_quantity * $item->purchasing_price;
            });
            return $product;
        });
        
        return view('reports.stock', compact('products'));
    }

    /**
     * User (Packer) Count Report.
     */
    public function packetCount()
    {
        $packers = User::withCount(['packedOrders' => function($q){
             $q->where('status', 'confirm');
        }])->get(); // Filter by role if needed
        
        return view('reports.packet_count', compact('packers'));
    }

    /**
     * Product Sales Report.
     */
    public function productSales()
    {
        $productSales = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->select(
                'order_items.product_name', 
                'order_items.sku',
                DB::raw('sum(order_items.quantity) as total_qty'),
                DB::raw('sum(order_items.total_price) as total_revenue'),
                'orders.status'
            )
            ->whereIn('orders.status', ['confirm', 'pending', 'hold', 'cancel'])
            ->groupBy('order_items.product_id', 'order_items.product_name', 'order_items.sku', 'orders.status')
            ->orderBy('total_qty', 'desc')
            ->get();
            
        return view('reports.product_sales', compact('productSales'));
    }
    
    /**
     * User Wise Sales Report (Sales Rep/Reseller Performance).
     */
    public function userSales()
    {
        // Assuming 'user_id' on order is the creator/sales rep
        $userSales = User::withSum(['orders' => function($q) {
            $q->where('status', 'confirm');
        }], 'total_amount')
        ->withCount(['orders' => function($q) {
            $q->where('status', 'confirm');
        }])
        ->get();
        
        return view('reports.user_sales', compact('userSales'));
    }
}
