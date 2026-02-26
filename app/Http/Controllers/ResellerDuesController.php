<?php

namespace App\Http\Controllers;

use App\Models\Reseller;
use App\Models\Order;
use App\Models\ResellerPayment;
use Illuminate\Http\Request;

class ResellerDuesController extends Controller
{
    /**
     * Display a listing of resellers with their due amounts.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $query = Reseller::regular();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('business_name', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%");
            });
        }
        
        // Default sort by name, but allow sorting by due_amount
        $sort = $request->input('sort', 'name');
        $direction = $request->input('direction', 'asc');
        
        if ($sort === 'due_amount') {
            $query->orderBy('due_amount', $direction);
        } else {
             $query->orderBy('name', 'asc');
        }

        $resellers = $query->paginate(20);

        return view('resellers.dues.index', compact('resellers'));
    }

    /**
     * Display the statement (ledger) for a specific reseller.
     */
    public function show(Request $request, $id)
    {
        $reseller = Reseller::regular()->findOrFail($id);
        
        // --- 1. Filter Parameters ---
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // --- 2. Build Query (UNION) ---
        // We use DB queries for performance and UNION compatibility
        
        // Orders Query
        $ordersQuery = \DB::table('orders')
            ->select(
                'created_at as date',
                'order_number as reference',
                \DB::raw('total_amount - COALESCE(reseller_return_fee_applied, 0) as amount'),
                'status', // Add status to match union
                \DB::raw("'Order' as type"),
                \DB::raw('CONCAT("Order placed (", status, ")") as description'),
                'id as original_id' // for linking
            )
            ->where('reseller_id', $id)
            ->where('status', '!=', 'cancel'); // Matches previous logic

        // Payments Query
        $paymentsQuery = \DB::table('reseller_payments')
            ->select(
                'payment_date as date',
                \DB::raw("COALESCE(reference_id, 'N/A') as reference"),
                'amount', // We will handle sign later or separate columns? Let's use amount and distinguish by type
                'status', // Include status
                \DB::raw("'Payment' as type"),
                \DB::raw('CONCAT("Payment received (", payment_method, ")") as description'),
                'id as original_id'
            )
            ->where('reseller_id', $id);

        // Apply Date Filters to sub-queries if possible, or usually better to apply to the UNION
        // But for "Opening Balance" calculation we need strict separation.
        
        // --- 3. Calculate Global Math ---
        
        // A. Global Opening Adjustment (The "Manual" part)
        // This is what makes the final balance match the DB due_amount
        // Formula: Adj = API_Due - (All_Orders - All_Payments)
        // If the system is perfect, Adj is 0. If imported data exists, Adj is the initial balance.
        
        $allOrdersSum = (float) $reseller->orders()
            ->where('status', '!=', 'cancel')
            ->sum(\DB::raw('total_amount - COALESCE(reseller_return_fee_applied, 0)'));
        $allPaymentsSum = $reseller->payments()->where('status', '!=', 'cancelled')->sum('amount');
        $globalAdjustment = $reseller->due_amount - ($allOrdersSum - $allPaymentsSum);
        
        // B. Calculate "Balance Forward" (Balance BEFORE the start_date)
        $balanceForward = $globalAdjustment;
        
        if ($startDate) {
            $ordersBefore = $reseller->orders()
                ->where('status', '!=', 'cancel')
                ->where('created_at', '<', $startDate)
                ->sum(\DB::raw('total_amount - COALESCE(reseller_return_fee_applied, 0)'));
                
            $paymentsBefore = $reseller->payments()
                ->where('status', '!=', 'cancelled') // Exclude cancelled
                ->where('payment_date', '<', $startDate)
                ->sum('amount');
                
            $balanceForward += ($ordersBefore - $paymentsBefore);
        }

        // --- 4. Fetch Paginated Records for View ---
        
        // Bind queries for Union
        // We need to filter the UNION result by date
        $combinedQuery = $ordersQuery->union($paymentsQuery);
        
        // Improve: Apply date filters to the combined query wrapper
        // Laravel's union builder is tricky with where clauses on the union itself without a subquery wrapper.
        // Easiest is to apply where to both parts if simple.
        if ($startDate) {
            $ordersQuery->where('created_at', '>=', $startDate);
            $paymentsQuery->where('payment_date', '>=', $startDate);
        }
        if ($endDate) {
            // Include the whole end day
            $ordersQuery->where('created_at', '<=', $endDate . ' 23:59:59');
            $paymentsQuery->where('payment_date', '<=', $endDate . ' 23:59:59');
        }
        
        // Now paginate the UNION
        // Note: Union and orderBy/paginate requires careful syntax
        $transactions = $ordersQuery->union($paymentsQuery)
            ->orderBy('date', 'desc')
            ->paginate(20)
            ->withQueryString();

        // --- 5. Calculate Running Balance for Display ---
        // We are displaying Descending (Newest First).
        // Row 1 Balance = Balance at End of Period (or End of Page)
        
        // We need the "Closing Balance" of this specific PAGE to start subtracting down.
        // Closing Balance of Page = BalanceForward + NetChange(All Items in range up to this page's start??)
        // Actually: 
        // Total Balance at End of Filtered Selection = BalanceForward + Sum(All Visible Items in Range)
        
        // Let's get the sum of all items in the filtered range (Orders - Payments)
        $ordersInRange = \DB::table('orders')->where('reseller_id', $id)->where('status', '!=', 'cancel');
        $paymentsInRange = \DB::table('reseller_payments')->where('reseller_id', $id);
        
        if ($startDate) {
            $ordersInRange->where('created_at', '>=', $startDate);
            $paymentsInRange->where('payment_date', '>=', $startDate);
        }
        if ($endDate) {
            $ordersInRange->where('created_at', '<=', $endDate . ' 23:59:59');
            $paymentsInRange->where('payment_date', '<=', $endDate . ' 23:59:59');
        }
        
        $netChangeInRange = $ordersInRange->sum(\DB::raw('total_amount - COALESCE(reseller_return_fee_applied, 0)')) - $paymentsInRange->sum('amount');
        $closingBalance = $balanceForward + $netChangeInRange;

        // But we are PAGINATING. If we are on Page 2, the top item is NOT the closing balance.
        // The top item of Page 2 is (ClosingBalance - NetChangeOfPage1).
        
        // Calculate Net Change of items *newer* than current page's items?
        // Offset approach:
        // Skip = ($page - 1) * $perPage
        // We need the sum of the first (Skip) items from the sorted QUERY to subtract from ClosingBalance.
        
        $currentPage = $transactions->currentPage();
        $perPage = $transactions->perPage();
        $offset = ($currentPage - 1) * $perPage;
        
        // Calculate Sum of items skipped (Newer items not on this page)
        // We reuse the union query logic but limit/offset? 
        // Actually, just fetching the top N items and summing them is safest.
        $newerItemsAdjustment = 0;
        if ($offset > 0) {
            // Re-run the union query with limit=$offset to sum their effect
            $newOrders = clone $ordersQuery; // These already have filters applied
            $newPayments = clone $paymentsQuery;
            
            $newerItems = $newOrders->union($newPayments)
                ->orderBy('date', 'desc')
                ->limit($offset)
                ->get();
                
            foreach ($newerItems as $item) {
                if ($item->type === 'Order') {
                    $newerItemsAdjustment += $item->amount;
                } else {
                    $newerItemsAdjustment -= $item->amount;
                }
            }
        }
        
        $startingBalanceForPage = $closingBalance - $newerItemsAdjustment;
        
        // --- 6. Transform for View ---
        // Iterate current page items and assign running balance
        // Since we go DESC, 
        // Item 1 Balance = $startingBalanceForPage
        // Item 1 PrevBalance (for Item 2) = $startingBalanceForPage - (Item1 Effect)
        
        $running = $startingBalanceForPage;
        
        $statement = $transactions->map(function ($t) use (&$running) {
            $t = (object) $t;
            $t->balance = $running;
            
            // Prepare for next item (which is older)
            // If current is DEBIT (Order), it INCREASED the balance to get here. So older balance was LOWER.
            // OldBalance = CurrentBalance - OrderAmount
            // If current is CREDIT (Payment), it DECREASED the balance. So older balance was HIGHER.
            // OldBalance = CurrentBalance + PaymentAmount
            
            if ($t->type === 'Order') {
                $t->debit = $t->amount;
                $t->credit = 0;
                $t->url = route('orders.index', ['search' => $t->reference]);
                $running -= $t->amount;
            } else { // Payment
                $t->debit = 0;
                
                // If cancelled, credit is effectively 0 for balance calculation, but we might want to show the amount visually with strikethrough?
                // For Balance correctness:
                if (isset($t->status) && $t->status === 'cancelled') {
                    $t->credit = 0;
                    $effectiveAmount = 0;
                    $t->description = $t->description . ' (Cancelled)';
                } else {
                    $t->credit = $t->amount;
                    $effectiveAmount = $t->amount;
                }
                
                $t->url = route('reseller-payments.edit', $t->original_id);
                $running += $effectiveAmount;
            }
            
            return $t;
        });

        return view('resellers.dues.show', compact('reseller', 'statement', 'transactions', 'balanceForward', 'startDate', 'endDate'));
    }
}
