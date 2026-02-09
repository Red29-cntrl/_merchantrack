<?php

namespace App\Http\Controllers;

use App\Product;
use App\Sale;
use App\SaleItem;
use App\Category;
use App\InventoryMovement;
use App\BusinessSetting;
use App\Events\SaleCreated;
use App\Events\InventoryUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class POSController extends Controller
{
    /**
     * Minimum stock that must remain after any stock-out (sales, etc.)
     * This keeps 20 units on-hand as the reorder buffer.
     */
    private const MIN_REMAINING_STOCK = 20;

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            Gate::authorize('use_pos');
            return $next($request);
        });
    }

    public function index()
    {
        $categories = Category::with('products')->get();
        // Only show items that can be sold without dropping below the reorder buffer
        $products = Product::where('is_active', true)->where('quantity', '>', self::MIN_REMAINING_STOCK)->get();
        $businessSettings = BusinessSetting::getSettings();
        return view('pos.index', compact('categories', 'products', 'businessSettings'));
    }

    public function getProduct($id)
    {
        $product = Product::findOrFail($id);
        return response()->json($product);
    }

    public function getProductByBarcode(Request $request)
    {
        $barcode = $request->input('barcode');
        
        if (!$barcode) {
            return response()->json([
                'success' => false,
                'message' => 'Barcode is required'
            ], 400);
        }

        $product = Product::where('barcode', $barcode)
            ->where('is_active', true)
            ->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found with this barcode'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'product' => $product
        ]);
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
                'sale_number' => Sale::generateSaleNumber(),
                'user_id' => auth()->id(),
                'subtotal' => $request->subtotal,
                'tax' => $request->tax ?? 0,
                'discount' => $request->discount ?? 0,
                'total' => $request->total,
                'payment_method' => $request->payment_method,
                'notes' => $request->notes,
            ]);

            foreach ($request->items as $item) {
                // Lock the product row to prevent race conditions in concurrent sales
                $product = Product::where('id', $item['product_id'])->lockForUpdate()->firstOrFail();
                
                // Validate stock availability
                if ($product->quantity < $item['quantity']) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Insufficient stock for {$product->name}. Available: {$product->quantity}, Requested: {$item['quantity']}"
                    ], 400);
                }

                // Enforce reorder buffer: do not allow sales that would drop stock below 20
                $remainingAfterSale = $product->quantity - (int) $item['quantity'];
                if ($remainingAfterSale < self::MIN_REMAINING_STOCK) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Cannot complete sale for {$product->name}.\n\n"
                            . "Must keep at least " . self::MIN_REMAINING_STOCK . " in stock for reorder.\n\n"
                            . "Available: {$product->quantity}\n"
                            . "Requested: {$item['quantity']}\n"
                            . "Would remain: {$remainingAfterSale}"
                    ], 400);
                }

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'] ?? $product->unit ?? 'pcs',
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['subtotal'],
                ]);

                // Decrement quantity and ensure updated_at is refreshed
                $product->decrement('quantity', $item['quantity']);
                // Explicitly touch the product to ensure updated_at is current
                $product->touch();

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
            
            // Auto-refresh syncing uses the shared DB; broadcasting is optional and must never break core flows.
            if (config('broadcasting.default') !== 'null') {
                try {
                    event(new SaleCreated($sale, auth()->user()->role, auth()->user()->name));
                } catch (\Throwable $e) {
                    \Log::warning('Failed to broadcast SaleCreated event: ' . $e->getMessage());
                }
                
                foreach ($request->items as $item) {
                    $product = Product::find($item['product_id']);
                    if ($product) {
                        try {
                            event(new InventoryUpdated($product, 'sale', auth()->user()->role, auth()->user()->name));
                        } catch (\Throwable $e) {
                            \Log::warning('Failed to broadcast InventoryUpdated event: ' . $e->getMessage());
                        }
                    }
                }
            }
            
            $businessSettings = BusinessSetting::getSettings();
            
            // Format date - use sale_date if available, otherwise created_at
            $saleDate = $sale->sale_date 
                ? \Carbon\Carbon::parse($sale->sale_date)->setTimezone('Asia/Manila')
                : $sale->created_at->setTimezone('Asia/Manila');
            
            // Convert items collection to array with null safety
            $itemsArray = [];
            foreach ($sale->items as $item) {
                // Handle case where product might be deleted
                if (!$item->product) {
                    $itemsArray[] = [
                        'product_name' => 'Deleted Product',
                        'quantity' => (int) $item->quantity,
                        'unit' => $item->unit ?? 'pcs',
                        'unit_price' => (float) $item->unit_price,
                        'subtotal' => (float) $item->subtotal,
                    ];
                } else {
                    $itemsArray[] = [
                        'product_name' => $item->product->name ?? 'Unknown Product',
                        'quantity' => (int) $item->quantity,
                        'unit' => $item->unit ?? ($item->product->unit ?? 'pcs'),
                        'unit_price' => (float) $item->unit_price,
                        'subtotal' => (float) $item->subtotal,
                    ];
                }
            }
            
            // Build response data as plain arrays (no objects)
            $responseData = [
                'success' => true,
                'sale_id' => (int) $sale->id,
                'sale_number' => (string) $sale->sale_number,
                'message' => 'Sale processed successfully',
                'sale' => [
                    'sale_number' => (string) $sale->sale_number,
                    'date' => (string) $saleDate->format('M d, Y h:i:s A'),
                    'date_short' => (string) $saleDate->format('M d, Y'),
                    'cashier' => (string) ($sale->user->name ?? 'Unknown'),
                    'payment_method' => (string) $sale->payment_method,
                    'items' => $itemsArray,
                    'subtotal' => (float) $sale->subtotal,
                    'tax' => (float) $sale->tax,
                    'discount' => (float) $sale->discount,
                    'total' => (float) $sale->total,
                ],
                'business' => [
                    'business_name' => (string) ($businessSettings->business_name ?? ''),
                    'receipt_type' => (string) ($businessSettings->receipt_type ?? 'SALES INVOICE'),
                    'business_type' => (string) ($businessSettings->business_type ?? ''),
                    'address' => (string) ($businessSettings->address ?? ''),
                    'proprietor' => (string) ($businessSettings->proprietor ?? ''),
                    'vat_reg_tin' => (string) ($businessSettings->vat_reg_tin ?? ''),
                    'phone' => (string) ($businessSettings->phone ?? ''),
                    'receipt_footer_note' => (string) ($businessSettings->receipt_footer_note ?? ''),
                ]
            ];
            
            return response()->json($responseData);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            
            // Log the full error for debugging
            \Log::error('Sale processing error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() . ' (Line: ' . $e->getLine() . ')'
            ], 400);
        }
    }
}
