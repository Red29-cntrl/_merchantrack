<?php

namespace App\Http\Controllers;

use App\Product;
use App\InventoryMovement;
use Illuminate\Http\Request;
use Carbon\Carbon;

class InventoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Check if user has permission to modify inventory
     */
    private function checkModifyPermission()
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized action. Only admins can modify inventory.');
        }
    }

    public function index(Request $request)
    {
        $products = Product::with('category')->get();

        if ($request->type === 'balance' && !auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized action. Only admins can view balance.');
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
        } else {
            $query = InventoryMovement::with('product', 'user')
                ->whereHas('product'); // Only get movements where product still exists

            if ($request->filled('product_id')) {
                $query->where('product_id', $request->product_id);
            }

            if ($request->has('type') && $request->type) {
                $query->where('type', $request->type);
            }

            // Month-by-month filtering (default to current month for clarity; "all" shows everything for the year)
            $selectedYear = $request->get('year', date('Y'));
            $selectedMonth = $request->has('month')
                ? $request->get('month')
                : date('n');

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

            // Compute running balance per product (based on all recorded movements in chronological order)
            // First, get all movements in chronological order for balance calculation
            $balanceQuery = InventoryMovement::with('product', 'user')
                ->whereHas('product');
            
            if ($request->has('product_id') && $request->product_id) {
                $balanceQuery->where('product_id', $request->product_id);
            }
            
            $allMovementsForBalance = $balanceQuery->orderBy('created_at', 'asc')->get();
            
            $balances = [];
            foreach ($allMovementsForBalance as $movement) {
                $productId = $movement->product_id;
                if (!isset($balances[$productId])) {
                    $balances[$productId] = 0;
                }

                if ($movement->type === 'out') {
                    $balances[$productId] -= $movement->quantity;
                } else {
                    // Treat "in" and "adjustment" as positive adjustments
                    $balances[$productId] += $movement->quantity;
                }
            }
            
            // Now add opening and running balances to displayed movements
            $displayBalances = [];
            foreach ($movements as $movement) {
                $productId = $movement->product_id;
                
                // Calculate opening balance by processing all movements before this one
                if (!isset($displayBalances[$productId])) {
                    $displayBalances[$productId] = 0;
                    // Get all movements before this one for this product
                    $earlierMovements = InventoryMovement::where('product_id', $productId)
                        ->where('created_at', '<', $movement->created_at)
                        ->orderBy('created_at', 'asc')
                        ->get();
                    
                    foreach ($earlierMovements as $earlier) {
                        if ($earlier->type === 'out') {
                            $displayBalances[$productId] -= $earlier->quantity;
                        } else {
                            $displayBalances[$productId] += $earlier->quantity;
                        }
                    }
                }
                
                // Ensure balance doesn't go negative - set to 0 minimum
                $movement->opening_balance = max(0, $displayBalances[$productId]);
                
                if ($movement->type === 'out') {
                    $displayBalances[$productId] -= $movement->quantity;
                } else {
                    $displayBalances[$productId] += $movement->quantity;
                }
                
                // Ensure balance doesn't go negative - set to 0 minimum
                $movement->running_balance = max(0, $displayBalances[$productId]);
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

        $selectedMonth = $request->has('month') ? $request->get('month') : date('n');
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

        return redirect()->back()->with('success', 'Inventory adjusted successfully.');
    }
}
