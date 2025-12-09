<?php

namespace App\Http\Controllers;

use App\Product;
use App\Sale;
use App\SaleItem;
use App\Category;
use App\InventoryMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class POSController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $categories = Category::with('products')->get();
        $products = Product::where('is_active', true)->where('quantity', '>', 0)->get();
        return view('pos.index', compact('categories', 'products'));
    }

    public function getProduct($id)
    {
        $product = Product::findOrFail($id);
        return response()->json($product);
    }

    public function processSale(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'subtotal' => 'required|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash',
        ]);

        DB::beginTransaction();
        try {
            $sale = Sale::create([
                'sale_number' => 'SALE-' . strtoupper(Str::random(8)),
                'user_id' => auth()->id(),
                'subtotal' => $request->subtotal,
                'tax' => $request->tax ?? 0,
                'discount' => $request->discount ?? 0,
                'total' => $request->total,
                'payment_method' => $request->payment_method,
                'notes' => $request->notes,
            ]);

            foreach ($request->items as $item) {
                $product = Product::findOrFail($item['product_id']);
                
                if ($product->quantity < $item['quantity']) {
                    throw new \Exception("Insufficient stock for {$product->name}");
                }

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['subtotal'],
                ]);

                $product->decrement('quantity', $item['quantity']);

                InventoryMovement::create([
                    'product_id' => $product->id,
                    'user_id' => auth()->id(),
                    'type' => 'out',
                    'quantity' => $item['quantity'],
                    'reason' => 'Sale',
                    'reference' => $sale->sale_number,
                ]);
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'sale_id' => $sale->id,
                'sale_number' => $sale->sale_number,
                'message' => 'Sale processed successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
