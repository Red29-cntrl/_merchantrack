<?php

namespace App\Http\Controllers;

use App\Sale;
use App\Product;
use App\Category;
use App\InventoryMovement;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SyncController extends Controller
{
    /**
     * Get latest data changes for sync
     * This is a fallback when WebSocket is not available
     */
    public function getLatestChanges(Request $request)
    {
        // Parse last_sync timestamp - handle both ISO format and datetime string
        $lastSyncInput = $request->input('last_sync');
        if ($lastSyncInput) {
            try {
                // Try to parse as Carbon date
                $lastSync = \Carbon\Carbon::parse($lastSyncInput);
            } catch (\Exception $e) {
                // If parsing fails, default to 5 minutes ago
                $lastSync = now()->subMinutes(5);
            }
        } else {
            $lastSync = now()->subMinutes(5);
        }
        
        // Get known IDs from client to detect deletions
        $knownProductIds = $request->input('known_product_ids', []);
        $knownCategoryIds = $request->input('known_category_ids', []);
        $knownMovementIds = $request->input('known_movement_ids', []);
        
        // Handle JSON-encoded arrays
        if (is_string($knownProductIds)) {
            $knownProductIds = json_decode($knownProductIds, true) ?: [];
        }
        if (is_string($knownCategoryIds)) {
            $knownCategoryIds = json_decode($knownCategoryIds, true) ?: [];
        }
        if (is_string($knownMovementIds)) {
            $knownMovementIds = json_decode($knownMovementIds, true) ?: [];
        }
        
        // Ensure arrays
        $knownProductIds = is_array($knownProductIds) ? $knownProductIds : [];
        $knownCategoryIds = is_array($knownCategoryIds) ? $knownCategoryIds : [];
        $knownMovementIds = is_array($knownMovementIds) ? $knownMovementIds : [];
        
        // Log for debugging (can be removed later)
        \Log::info('Sync request', [
            'last_sync_input' => $lastSyncInput,
            'parsed_last_sync' => $lastSync->toDateTimeString(),
            'now' => now()->toDateTimeString()
        ]);
        
        $changes = [
            'sales' => [],
            'products' => [],
            'products_deleted' => [],
            'categories' => [],
            'categories_deleted' => [],
            'inventory_movements' => [],
            'inventory_movements_deleted' => [],
            'timestamp' => now()->toDateTimeString(),
            'debug' => [
                'last_sync_received' => $lastSyncInput,
                'last_sync_parsed' => $lastSync->toDateTimeString(),
                'current_time' => now()->toDateTimeString(),
            ]
        ];
        
        // Get new sales since last sync (only sales processed by staff)
        // Use whereDate comparison to be more lenient with timestamp precision
        $sales = Sale::where('created_at', '>', $lastSync)
            ->with('user')
            ->whereHas('user', function($q) {
                $q->where('role', 'staff');
            })
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function($sale) {
                return [
                    'id' => $sale->id,
                    'sale_number' => $sale->sale_number,
                    'total' => (float) $sale->total,
                    'created_at' => $sale->created_at->toDateTimeString(),
                    'cashier_name' => $sale->user->name ?? 'Unknown',
                    'user_name' => $sale->user->name ?? 'Unknown', // Include user_name for consistency
                    'payment_method' => $sale->payment_method,
                    'user_role' => $sale->user->role ?? 'staff',
                ];
            });
        
        $changes['sales'] = $sales;
        
        // Get updated products since last sync
        // Include products that were updated OR have inventory movements since last sync
        $adminUser = User::where('role', 'admin')->first();
        $adminName = $adminUser ? $adminUser->name : 'Admin';
        
        // Get product IDs that have inventory movements since last sync
        $productIdsWithMovements = InventoryMovement::where('created_at', '>', $lastSync)
            ->distinct()
            ->pluck('product_id')
            ->toArray();
        
        // Get products updated directly OR products with recent inventory movements
        $productsQuery = Product::where(function($query) use ($lastSync, $productIdsWithMovements) {
                $query->where('updated_at', '>', $lastSync);
                if (!empty($productIdsWithMovements)) {
                    $query->orWhereIn('id', $productIdsWithMovements);
                }
            })
            ->where('is_active', true);
        
        $products = $productsQuery
            ->orderBy('updated_at', 'desc')
            ->limit(100)
            ->get()
            ->map(function($product) use ($adminName, $lastSync) {
                // Try to get user name from most recent inventory movement
                $recentMovement = InventoryMovement::where('product_id', $product->id)
                    ->where('created_at', '>', $lastSync)
                    ->with('user')
                    ->orderBy('created_at', 'desc')
                    ->first();
                
                $userName = $adminName; // Default to admin name
                $userRole = 'admin'; // Default to admin
                if ($recentMovement && $recentMovement->user) {
                    $userName = $recentMovement->user->name;
                    $userRole = $recentMovement->user->role;
                }
                
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => (float) $product->price,
                    'quantity' => (int) $product->quantity,
                    'reorder_level' => (int) $product->reorder_level,
                    'unit' => $product->unit ?? 'pcs',
                    'updated_at' => $product->updated_at->toDateTimeString(),
                    'action' => 'updated', // Default to updated for sync
                    'user_role' => $userRole,
                    'user_name' => $userName,
                ];
            });
        
        $changes['products'] = $products;
        
        // Detect deleted products by comparing known IDs with current IDs
        if (!empty($knownProductIds)) {
            $currentProductIds = Product::where('is_active', true)->pluck('id')->toArray();
            $deletedProductIds = array_diff($knownProductIds, $currentProductIds);
            
            if (!empty($deletedProductIds)) {
                $changes['products_deleted'] = array_values($deletedProductIds);
            }
        }
        
        // Get new inventory movements since last sync
        $movements = InventoryMovement::where('created_at', '>', $lastSync)
            ->with('product', 'user')
            ->whereHas('product') // Only get movements where product still exists
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function($movement) {
                // Calculate opening and running balance (simplified - would need full calculation for accuracy)
                // For now, we'll let the frontend handle this or reload the page
                return [
                    'id' => $movement->id,
                    'product_id' => $movement->product_id,
                    'product_name' => $movement->product->name ?? 'Unknown',
                    'type' => $movement->type,
                    'quantity' => (int) $movement->quantity,
                    'reason' => $movement->reason ?? 'N/A',
                    'reference' => $movement->reference ?? null,
                    'user_name' => $movement->user->name ?? 'Unknown',
                    'created_at' => $movement->created_at->toDateTimeString(),
                ];
            });
        
        $changes['inventory_movements'] = $movements;
        
        // Detect deleted inventory movements by comparing known IDs with current IDs
        if (!empty($knownMovementIds)) {
            $currentMovementIds = InventoryMovement::pluck('id')->toArray();
            $deletedMovementIds = array_diff($knownMovementIds, $currentMovementIds);
            
            if (!empty($deletedMovementIds)) {
                $changes['inventory_movements_deleted'] = array_values($deletedMovementIds);
            }
        }
        
        // Get updated categories since last sync
        $categories = Category::where('updated_at', '>', $lastSync)
            ->orderBy('updated_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'description' => $category->description ?? null,
                    'updated_at' => $category->updated_at->toDateTimeString(),
                    'created_at' => $category->created_at->toDateTimeString(),
                    'action' => 'updated', // Default to updated for sync
                ];
            });
        
        $changes['categories'] = $categories;
        
        // Detect deleted categories by comparing known IDs with current IDs
        if (!empty($knownCategoryIds)) {
            $currentCategoryIds = Category::pluck('id')->toArray();
            $deletedCategoryIds = array_diff($knownCategoryIds, $currentCategoryIds);
            
            if (!empty($deletedCategoryIds)) {
                $changes['categories_deleted'] = array_values($deletedCategoryIds);
            }
        }
        
        // Add debug info
        $changes['debug']['sales_count'] = $sales->count();
        $changes['debug']['products_count'] = $products->count();
        $changes['debug']['products_deleted_count'] = count($changes['products_deleted']);
        $changes['debug']['categories_count'] = $categories->count();
        $changes['debug']['categories_deleted_count'] = count($changes['categories_deleted']);
        $changes['debug']['inventory_movements_count'] = $movements->count();
        $changes['debug']['inventory_movements_deleted_count'] = count($changes['inventory_movements_deleted']);
        $changes['debug']['product_ids_with_movements'] = count($productIdsWithMovements);
        
        return response()->json($changes);
    }
}
