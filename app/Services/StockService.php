<?php

namespace App\Services;

use App\Models\Product;
use App\Models\PurchaseItem;
use Illuminate\Support\Facades\DB;

class StockService
{
    /**
     * Deduct stock using FIFO method and calculate weighted cost price.
     * 
     * @param Product $product
     * @param int $quantity
     * @return float Weighted Cost Price per Item
     */
    public function deductStock(Product $product, int $quantity): float
    {
        $remainingToDeduct = $quantity;
        $totalCost = 0;
        
        // Product master stock deduction (simple count)
        $product->quantity -= $quantity;
        $product->save();

        // FIFO Batch Deduction
        // Get verified batches with stock, ordered by oldest first
        $batches = PurchaseItem::where('product_id', $product->id)
            ->where('remaining_quantity', '>', 0)
            ->whereHas('purchase', function($q) {
                $q->where('status', 'verified');
            })
            ->orderBy('created_at', 'asc') // FIFO
            ->get();

        foreach ($batches as $batch) {
            if ($remainingToDeduct <= 0) break;

            $available = $batch->remaining_quantity;
            $batchCost = $batch->purchasing_price;

            if ($available >= $remainingToDeduct) {
                // Take all needed from this batch
                $batch->remaining_quantity -= $remainingToDeduct;
                $batch->save();
                
                $totalCost += ($remainingToDeduct * $batchCost);
                $remainingToDeduct = 0;
            } else {
                // Take whatever is available
                $batch->remaining_quantity = 0;
                $batch->save();
                
                $totalCost += ($available * $batchCost);
                $remainingToDeduct -= $available;
            }
        }

        // If we still have deduction left (meaning we oversold stock not recorded in purchases),
        // we assume 0 cost or we could use current master purchasing price if available.
        // For accurate P&L, this implies negative stock or un-costed stock.
        // We will assume 0 for distinct tracking of "missing cost data".
        if ($remainingToDeduct > 0) {
            // Log warning or handle? For now, cost is 0 for these.
        }

        // Calculate weighted average cost per unit for this specific transaction
        // Avoid division by zero
        return $quantity > 0 ? ($totalCost / $quantity) : 0;
    }
}
