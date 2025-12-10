<?php

namespace App\Http\Controllers;

use App\Product;
use App\InventoryMovement;
use Illuminate\Http\Request;

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
                ->when($request->product_id, function ($query) use ($request) {
                    $query->where('id', $request->product_id);
                })
                ->orderBy('name')
                ->paginate(15);
        } else {
            $query = InventoryMovement::with('product', 'user')
                ->whereHas('product'); // Only get movements where product still exists

            if ($request->has('product_id') && $request->product_id) {
                $query->where('product_id', $request->product_id);
            }

            if ($request->has('type') && $request->type) {
                $query->where('type', $request->type);
            }

            // Ledger-style ordering: oldest first to compute running balances
            $movements = $query->orderBy('created_at', 'asc')->get();

            // Compute running balance per product (based on all recorded movements)
            $balances = [];
            foreach ($movements as $movement) {
                $productId = $movement->product_id;
                if (!isset($balances[$productId])) {
                    $balances[$productId] = 0;
                }

                // Stock before applying this movement
                $movement->opening_balance = $balances[$productId];

                if ($movement->type === 'out') {
                    $balances[$productId] -= $movement->quantity;
                } else {
                    // Treat "in" and "adjustment" as positive adjustments
                    $balances[$productId] += $movement->quantity;
                }

                $movement->running_balance = $balances[$productId];
            }
        }

        return view('inventory.index', compact('movements', 'products', 'showBalance', 'balanceProducts'));
    }

    public function adjust(Request $request, Product $product)
    {
        $this->checkModifyPermission();
        $request->validate([
            'type' => 'required|in:in,out,adjustment',
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string',
        ]);

        $quantity = $request->type === 'out' ? -$request->quantity : $request->quantity;

        // Prevent taking stock out when there is not enough on hand
        if ($request->type === 'out' && $product->quantity < $request->quantity) {
            return redirect()->back()->with('error', 'Cannot stock out more than available quantity.');
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
