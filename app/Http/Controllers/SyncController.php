<?php

namespace App\Http\Controllers;

use App\Sale;
use App\Product;
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
        $lastSync = $request->input('last_sync', now()->subMinutes(5)->toDateTimeString());
        
        $changes = [
            'sales' => [],
            'products' => [],
            'timestamp' => now()->toDateTimeString(),
        ];
        
        // Get new sales since last sync (only sales processed by staff)
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
        
        // Get updated products since last sync (products are managed by admin)
        // Try to get admin user name for fallback
        $adminUser = User::where('role', 'admin')->first();
        $adminName = $adminUser ? $adminUser->name : 'Admin';
        
        $products = Product::where('updated_at', '>', $lastSync)
            ->where('is_active', true)
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
                if ($recentMovement && $recentMovement->user && $recentMovement->user->role === 'admin') {
                    $userName = $recentMovement->user->name;
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
                    'user_role' => 'admin', // Products are managed by admin
                    'user_name' => $userName, // Get from inventory movement or use admin name
                ];
            });
        
        $changes['products'] = $products;
        
        return response()->json($changes);
    }
}
