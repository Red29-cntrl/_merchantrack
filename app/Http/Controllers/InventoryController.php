<?php

namespace App\Http\Controllers;

use App\Product;
use App\InventoryMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Carbon\Carbon;

class InventoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Check if user has permission to modify inventory (admin only).
     */
    private function checkModifyPermission()
    {
        Gate::authorize('manage_inventory');
    }

    public function index(Request $request)
    {
        $products = Product::with('category')->get();

        if ($request->type === 'balance') {
            Gate::authorize('manage_inventory');
        }

        $showBalance = $request->type === 'balance';
        $balanceProducts = null;
        $movements = null;

        if ($showBalance) {
            // Show current stock balances instead of movement history
            $balanceProducts = Product::with('category')
                ->when($request->filled('product_id'), function ($query) use ($request) {
                    $query->where('id', $request->product_id);
                })
                ->orderBy('name')
                ->paginate(15);
            
            // Calculate Current Stock and Balance for each product
            foreach ($balanceProducts as $product) {
                // Get all transactions for this product
                $allMovements = InventoryMovement::where('product_id', $product->id)
                    ->orderBy('created_at', 'asc')
                    ->get();
                
                $transactionSum = 0;
                foreach ($allMovements as $movement) {
                    if ($movement->type === 'out') {
                        $transactionSum -= $movement->quantity;
                    } else {
                        $transactionSum += $movement->quantity;
                    }
                }
                
                // Current Stock = product.quantity - transaction sum (initial stock before transactions)
                $product->current_stock = max(0, $product->quantity - $transactionSum);
                // Balance = Current Stock + transaction sum = product.quantity (current balance)
                $product->balance = $product->quantity;
            }
        } else {
            $query = InventoryMovement::with('product', 'user')
                ->whereHas('product'); // Only get movements where product still exists

            if ($request->filled('product_id')) {
                $query->where('product_id', $request->product_id);
            }

            if ($request->has('type') && $request->type) {
                $query->where('type', $request->type);
            }

            // Month-by-month filtering (default to "all" so recent changes are visible without extra clicks)
            $selectedYear = $request->get('year', date('Y'));
            $selectedMonth = $request->has('month')
                ? $request->get('month')
                : 'all';

            if ($selectedMonth !== 'all') {
                $query->whereMonth('created_at', $selectedMonth);
            }

            if ($selectedYear) {
                $query->whereYear('created_at', $selectedYear);
            }

            // Get movements ordered by latest first (newest to oldest) within the selected year
            $movements = $query->orderBy('created_at', 'desc')
                              ->orderBy('id', 'desc')
                              ->get();

            // Compute running balance per product
            // Current Stock = quantity from product model (initial/baseline before transactions)
            // Balance = Current Stock + sum of all transactions
            
            // First, get all movements in chronological order for balance calculation
            $balanceQuery = InventoryMovement::with('product', 'user')
                ->whereHas('product');
            
            if ($request->has('product_id') && $request->product_id) {
                $balanceQuery->where('product_id', $request->product_id);
            }
            
            $allMovementsForBalance = $balanceQuery->orderBy('created_at', 'asc')->get();
            
            // Calculate initial stock (Current Stock) for each product
            // Current Stock = product.quantity - (sum of all transactions)
            $productCurrentStocks = [];
            $transactionSums = [];
            
            foreach ($allMovementsForBalance as $movement) {
                $productId = $movement->product_id;
                if (!isset($transactionSums[$productId])) {
                    $transactionSums[$productId] = 0;
                }
                
                if ($movement->type === 'out') {
                    $transactionSums[$productId] -= $movement->quantity;
                } else {
                    // Treat "in" and "adjustment" as positive adjustments
                    $transactionSums[$productId] += $movement->quantity;
                }
            }
            
            // Get current stock for each product (reverse calculate from current quantity)
            $productIds = array_unique($allMovementsForBalance->pluck('product_id')->toArray());
            foreach ($productIds as $productId) {
                $product = Product::find($productId);
                if ($product) {
                    // Current Stock = product.quantity - transaction sum
                    // This gives us the initial stock before any transactions
                    $productCurrentStocks[$productId] = max(0, $product->quantity - $transactionSums[$productId]);
                } else {
                    $productCurrentStocks[$productId] = 0;
                }
            }
            
            // Calculate balances for all movements in chronological order
            // Then attach them to the movements displayed in reverse order
            $movementBalances = [];
            
            // Process all movements chronologically to calculate balances
            $chronologicalMovements = InventoryMovement::with('product', 'user')
                ->whereHas('product');
            
            if ($request->has('product_id') && $request->product_id) {
                $chronologicalMovements->where('product_id', $request->product_id);
            }
            
            $chronologicalMovements = $chronologicalMovements->orderBy('created_at', 'asc')
                ->orderBy('id', 'asc')
                ->get();
            
            $runningBalances = [];
            foreach ($chronologicalMovements as $movement) {
                $productId = $movement->product_id;
                
                // Initialize balance with Current Stock if first movement for this product
                if (!isset($runningBalances[$productId])) {
                    $currentStock = $productCurrentStocks[$productId] ?? 0;
                    $runningBalances[$productId] = $currentStock;
                }
                
                // Store opening balance (before this transaction)
                $movementBalances[$movement->id] = [
                    'opening_balance' => max(0, $runningBalances[$productId]),
                ];
                
                // Apply transaction
                if ($movement->type === 'out') {
                    $runningBalances[$productId] -= $movement->quantity;
                } else {
                    $runningBalances[$productId] += $movement->quantity;
                }
                
                // Store running balance (after this transaction)
                $movementBalances[$movement->id]['running_balance'] = max(0, $runningBalances[$productId]);
            }
            
            // Attach balances to displayed movements (which are in reverse chronological order)
            foreach ($movements as $movement) {
                if (isset($movementBalances[$movement->id])) {
                    $movement->opening_balance = $movementBalances[$movement->id]['opening_balance'];
                    $movement->running_balance = $movementBalances[$movement->id]['running_balance'];
                } else {
                    // Fallback if movement not found in chronological list
                    $movement->opening_balance = 0;
                    $movement->running_balance = 0;
                }
            }
        }

        // Get available years from inventory movements
        $availableYears = InventoryMovement::selectRaw('DISTINCT YEAR(created_at) as year')
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        // Default to current year if no years available
        if (empty($availableYears)) {
            $availableYears = [date('Y')];
        }

        $selectedMonth = $request->has('month') ? $request->get('month') : 'all';
        $selectedYear = $request->get('year', date('Y'));

        return view('inventory.index', compact(
            'movements',
            'products',
            'showBalance',
            'balanceProducts',
            'availableYears',
            'selectedMonth',
            'selectedYear'
        ));
    }

    public function monthlySummary(Request $request)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized action. Only admins can view monthly summary.');
        }

        $selectedYear = $request->get('year', date('Y'));

        // Get available years from inventory movements
        $availableYears = InventoryMovement::selectRaw('DISTINCT YEAR(created_at) as year')
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        // Default to current year if no years available
        if (empty($availableYears)) {
            $availableYears = [date('Y')];
        }

        // Monthly summary for dashboard cards
        $monthlySummary = InventoryMovement::selectRaw('
                YEAR(created_at) as year,
                MONTH(created_at) as month,
                SUM(CASE WHEN type = "in" THEN quantity ELSE 0 END) as total_in,
                SUM(CASE WHEN type = "out" THEN quantity ELSE 0 END) as total_out,
                SUM(CASE WHEN type = "adjustment" THEN quantity ELSE 0 END) as total_adjustment,
                SUM(
                    CASE 
                        WHEN type = "out" THEN -quantity 
                        ELSE quantity 
                    END
                ) as net_change
            ')
            ->whereYear('created_at', $selectedYear)
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

        return view('inventory.monthly-summary', compact(
            'monthlySummary',
            'availableYears',
            'selectedYear'
        ));
    }

    public function adjust(Request $request, Product $product)
    {
        $this->checkModifyPermission();
        $request->validate([
            'type' => 'required|in:in,out,adjustment',
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string',
        ]);

        // Refresh product from database to get the latest quantity (avoid stale data)
        $product->refresh();

        $quantity = $request->type === 'out' ? -$request->quantity : $request->quantity;

        // Prevent taking stock out when there is not enough on hand
        if ($request->type === 'out' && $product->quantity < $request->quantity) {
            return redirect()->back()->with('error', "Cannot stock out more than available quantity. Available stock: {$product->quantity}, Requested: {$request->quantity}.");
        }

        // Enforce 20-unit reorder buffer: do not allow stock-out that would drop below 20 remaining
        if ($request->type === 'out') {
            $remaining = $product->quantity - (int) $request->quantity;
            if ($remaining < 20) {
                return redirect()->back()->with(
                    'error',
                    "Cannot stock out {$request->quantity}. Must keep at least 20 in stock for reorder. Available stock: {$product->quantity}, Would remain: {$remaining}."
                );
            }
        }

        InventoryMovement::create([
            'product_id' => $product->id,
            'user_id' => auth()->id(),
            'type' => $request->type,
            'quantity' => $request->quantity,
            'reason' => $request->reason,
        ]);

        $product->increment('quantity', $quantity);

        // Refresh product to get updated quantity
        $product->refresh();
        
        // Auto-refresh syncing uses the shared DB; broadcasting is optional and must never break core flows.
        if (config('broadcasting.default') !== 'null') {
            try {
                event(new \App\Events\InventoryUpdated($product, $request->type, auth()->user()->role, auth()->user()->name));
            } catch (\Throwable $e) {
                \Log::warning('Failed to broadcast InventoryUpdated event: ' . $e->getMessage());
            }
        }

        return redirect()->back()->with('success', 'Inventory adjusted successfully.');
    }
}
