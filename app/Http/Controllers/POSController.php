<?php

namespace App\Http\Controllers;

use App\Product;
use App\Sale;
use App\SaleItem;
use App\Category;
use App\InventoryMovement;
use App\BusinessSetting;
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
        $businessSettings = BusinessSetting::getSettings();
        return view('pos.index', compact('categories', 'products', 'businessSettings'));
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
                    'unit' => $item['unit'] ?? $product->unit ?? 'pcs',
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
            
            // Load sale with items and user for receipt
            $sale->load('items.product', 'user');
            $businessSettings = BusinessSetting::getSettings();
            
            // Format date - use sale_date if available, otherwise created_at
            $saleDate = $sale->sale_date 
                ? \Carbon\Carbon::parse($sale->sale_date)->setTimezone('Asia/Manila')
                : $sale->created_at->setTimezone('Asia/Manila');
            
            return response()->json([
                'success' => true,
                'sale_id' => $sale->id,
                'sale_number' => $sale->sale_number,
                'message' => 'Sale processed successfully',
                'sale' => [
                    'sale_number' => $sale->sale_number,
                    'date' => $saleDate->format('M d, Y h:i:s A'),
                    'date_short' => $saleDate->format('M d, Y'),
                    'cashier' => $sale->user->name,
                    'payment_method' => $sale->payment_method,
                    'items' => $sale->items->map(function($item) {
                        return [
                            'product_name' => $item->product->name,
                            'quantity' => $item->quantity,
                            'unit' => $item->unit ?? $item->product->unit ?? 'pcs',
                            'unit_price' => $item->unit_price,
                            'subtotal' => $item->subtotal,
                        ];
                    }),
                    'subtotal' => $sale->subtotal,
                    'tax' => $sale->tax,
                    'discount' => $sale->discount,
                    'total' => $sale->total,
                ],
                'business' => [
                    'business_name' => $businessSettings->business_name ?? '',
                    'receipt_type' => $businessSettings->receipt_type ?? 'SALES INVOICE',
                    'business_type' => $businessSettings->business_type ?? '',
                    'address' => $businessSettings->address ?? '',
                    'proprietor' => $businessSettings->proprietor ?? '',
                    'vat_reg_tin' => $businessSettings->vat_reg_tin ?? '',
                    'phone' => $businessSettings->phone ?? '',
                    'receipt_footer_note' => $businessSettings->receipt_footer_note ?? '',
                ]
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
