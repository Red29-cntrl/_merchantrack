<?php

namespace App\Http\Controllers;

use App\Product;
use App\Category;
use App\InventoryMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Check if user has permission to modify products (admin only).
     */
    private function checkModifyPermission()
    {
        Gate::authorize('manage_products');
    }

    public function index(Request $request)
    {
        $query = Product::with('category');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('sku', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        if ($request->filled('low_stock')) {
            $query->whereColumn('quantity', '<=', 'reorder_level');
        }

        $products = $query->latest()->paginate(15)->appends($request->query());
        $categories = Category::all();
        return view('products.index', compact('products', 'categories'));
    }

    public function create()
    {
        $this->checkModifyPermission();
        $categories = Category::all();
        return view('products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $this->checkModifyPermission();
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:255|unique:products',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
            'price' => 'required|numeric|min:0',
            'cost' => 'nullable|numeric|min:0',
            'quantity' => 'required|integer|min:0',
            'reorder_level' => 'required|integer|min:0',
            'unit' => 'required|string|max:50',
        ]);

        $product = Product::create($data);
        // Auto-refresh syncing uses the shared DB; broadcasting is optional and must never break core flows.
        if (config('broadcasting.default') !== 'null') {
            try {
                event(new \App\Events\ProductUpdated($product, 'created', auth()->user()->role, auth()->user()->name));
            } catch (\Throwable $e) {
                \Log::warning('Failed to broadcast ProductUpdated event: ' . $e->getMessage());
            }
        }
        
        return redirect()->route('products.index')->with('success', 'Product created successfully.');
    }

    public function show(Product $product)
    {
        $product->load('category', 'inventoryMovements.user', 'demandForecasts');
        return view('products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        $this->checkModifyPermission();
        $categories = Category::all();
        return view('products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        $this->checkModifyPermission();
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:255|unique:products,sku,' . $product->id,
            'description' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
            'price' => 'required|numeric|min:0',
            'cost' => 'nullable|numeric|min:0',
            'quantity' => 'required|integer|min:0',
            'reorder_level' => 'required|integer|min:0',
            'unit' => 'required|string|max:50',
        ]);

        // Capture old values to detect changes
        $oldQuantity = $product->quantity;
        $oldName = $product->name;
        $oldPrice = $product->price;
        $oldDescription = $product->description;
        $oldSku = $product->sku;

        $product->update($data);
        $product->refresh();

        // Check if name, price, description, or SKU changed
        $productChanged = ($oldName !== $product->name) || 
                         ($oldPrice != $product->price) || 
                         ($oldDescription !== $product->description) || 
                         ($oldSku !== $product->sku);

        // Log inventory movement if quantity changed via product edit
        $newQuantity = $product->quantity;
        $diff = $newQuantity - $oldQuantity;

        if ($diff !== 0) {
            InventoryMovement::create([
                'product_id' => $product->id,
                'user_id' => auth()->id(),
                'type' => $diff > 0 ? 'in' : 'out',
                'quantity' => abs($diff),
                'reason' => 'Product quantity edited',
            ]);
            
            // Broadcasting is optional; must never break core flows.
            if (config('broadcasting.default') !== 'null') {
                try {
                    event(new \App\Events\InventoryUpdated($product, 'edit', auth()->user()->role, auth()->user()->name));
                } catch (\Throwable $e) {
                    \Log::warning('Failed to broadcast InventoryUpdated event: ' . $e->getMessage());
                }
            }
        }
        
        // Broadcast product update if product details changed (name, price, description, SKU, or quantity)
        // This ensures staff are notified of any product changes
        if ($productChanged && config('broadcasting.default') !== 'null') {
            try {
                event(new \App\Events\ProductUpdated($product, 'updated', auth()->user()->role, auth()->user()->name));
            } catch (\Throwable $e) {
                \Log::warning('Failed to broadcast ProductUpdated event: ' . $e->getMessage());
            }
        }

        return redirect()->route('products.index')->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        $this->checkModifyPermission();
        
        // Broadcast before deletion (optional)
        if (config('broadcasting.default') !== 'null') {
            try {
                event(new \App\Events\ProductUpdated($product, 'deleted', auth()->user()->role, auth()->user()->name));
            } catch (\Throwable $e) {
                \Log::warning('Failed to broadcast ProductUpdated event: ' . $e->getMessage());
            }
        }
        
        $product->delete();
        return redirect()->route('products.index')->with('success', 'Product deleted successfully.');
    }

}
