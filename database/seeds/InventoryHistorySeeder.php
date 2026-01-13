<?php

use Illuminate\Database\Seeder;
use App\Product;
use App\User;
use App\InventoryMovement;
use Carbon\Carbon;

class InventoryHistorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Generate inventory movements for 2024-2026
     * - Initial stock at start of each year
     * - Monthly stock purchases (type: 'in')
     * - Regular stock out movements (type: 'out')
     * - Occasional adjustments (type: 'adjustment')
     *
     * @return void
     */
    public function run()
    {
        $products = Product::where('is_active', true)->get();
        
        if ($products->isEmpty()) {
            $this->command->warn('No active products found. Please seed products first.');
            return;
        }

        // Get admin user for inventory management
        $adminUser = User::where('role', 'admin')->first() ?? User::first();
        
        if (!$adminUser) {
            $this->command->warn('No users found. Please seed users first.');
            return;
        }

        $this->command->info('Generating inventory history for 2024-2026...');

        // Process each year
        $years = [2024, 2025, 2026];
        
        foreach ($years as $year) {
            $this->command->info("Processing year {$year}...");
            
            // Track stock levels for each product (in memory)
            $productStock = [];
            foreach ($products as $product) {
                // Get current stock or set initial
                $productStock[$product->id] = $product->quantity ?? 0;
            }

            // 1. Initial stock at the beginning of the year
            $initialStockDate = Carbon::create($year, 1, 1, 8, 0, 0);
            $this->command->info("  Adding initial stock for {$year}...");
            
            foreach ($products as $product) {
                // Add substantial initial stock (500-2000 units per product)
                $initialQty = rand(500, 2000);
                $productStock[$product->id] = ($productStock[$product->id] ?? 0) + $initialQty;
                $product->increment('quantity', $initialQty);
                
                InventoryMovement::create([
                    'product_id' => $product->id,
                    'user_id' => $adminUser->id,
                    'type' => 'in',
                    'quantity' => $initialQty,
                    'reason' => "Initial Stock - {$year}",
                    'reference' => "INIT-{$year}",
                    'created_at' => $initialStockDate,
                    'updated_at' => $initialStockDate,
                ]);
            }

            // 2. Monthly stock purchases (on 1st-5th of each month)
            for ($month = 1; $month <= 12; $month++) {
                $purchaseDate = Carbon::create($year, $month, rand(1, 5), rand(8, 12), rand(0, 59), rand(0, 59));
                
                // Purchase 60-80% of products each month
                $productsToPurchase = $products->random(rand((int)($products->count() * 0.6), (int)($products->count() * 0.8)));
                
                foreach ($productsToPurchase as $product) {
                    // Replenishment quantity based on product type
                    $baseQty = rand(150, 600);
                    $replenishQty = $baseQty;
                    
                    $productStock[$product->id] = ($productStock[$product->id] ?? 0) + $replenishQty;
                    $product->increment('quantity', $replenishQty);
                    
                    InventoryMovement::create([
                        'product_id' => $product->id,
                        'user_id' => $adminUser->id,
                        'type' => 'in',
                        'quantity' => $replenishQty,
                        'reason' => 'Monthly Supplier Order',
                        'reference' => "SUPPLIER-{$year}" . str_pad($month, 2, '0', STR_PAD_LEFT),
                        'created_at' => $purchaseDate,
                        'updated_at' => $purchaseDate,
                    ]);
                }
            }

            // 3. Regular stock out movements (throughout the year)
            // Generate 2-4 stock out movements per week
            $startDate = Carbon::create($year, 1, 1, 9, 0, 0);
            $endDate = Carbon::create($year, 12, 31, 20, 0, 0);
            $currentDate = $startDate->copy();
            
            $stockOutCount = 0;
            
            while ($currentDate->lte($endDate)) {
                // 2-4 stock out movements per week
                if (rand(1, 100) <= 40) { // 40% chance per day = ~2-3 per week
                    $stockOutDate = $currentDate->copy()->setTime(rand(9, 20), rand(0, 59), rand(0, 59));
                    
                    // Select 1-5 products for stock out
                    $productsToSell = $products->random(rand(1, min(5, $products->count())));
                    
                    foreach ($productsToSell as $product) {
                        // Check if we have stock
                        if (($productStock[$product->id] ?? 0) <= 0) {
                            continue;
                        }
                        
                        // Generate realistic quantity (1-50 units)
                        $rand = rand(1, 100);
                        if ($rand <= 60) {
                            $quantity = rand(1, 10); // 60% chance: 1-10 units
                        } elseif ($rand <= 85) {
                            $quantity = rand(11, 25); // 25% chance: 11-25 units
                        } elseif ($rand <= 95) {
                            $quantity = rand(26, 40); // 10% chance: 26-40 units
                        } else {
                            $quantity = rand(41, 50); // 5% chance: 41-50 units
                        }
                        
                        // Don't exceed available stock
                        $quantity = min($quantity, $productStock[$product->id]);
                        
                        if ($quantity > 0) {
                            $productStock[$product->id] -= $quantity;
                            $product->decrement('quantity', $quantity);
                            
                            InventoryMovement::create([
                                'product_id' => $product->id,
                                'user_id' => $adminUser->id,
                                'type' => 'out',
                                'quantity' => $quantity,
                                'reason' => 'Stock Out - Sale/Delivery',
                                'reference' => "OUT-{$year}" . str_pad($stockOutCount + 1, 5, '0', STR_PAD_LEFT),
                                'created_at' => $stockOutDate,
                                'updated_at' => $stockOutDate,
                            ]);
                            
                            $stockOutCount++;
                        }
                    }
                }
                
                // Move to next day
                $currentDate->addDay();
            }

            // 4. Occasional adjustments (1-2 per month)
            for ($month = 1; $month <= 12; $month++) {
                $adjustmentCount = rand(1, 2);
                
                for ($i = 0; $i < $adjustmentCount; $i++) {
                    $adjustmentDate = Carbon::create($year, $month, rand(10, 25), rand(10, 16), rand(0, 59), rand(0, 59));
                    
                    // Select 1-3 products for adjustment
                    $productsToAdjust = $products->random(rand(1, min(3, $products->count())));
                    
                    foreach ($productsToAdjust as $product) {
                        // Adjustment can be positive or negative
                        $adjustmentType = rand(1, 100);
                        
                        if ($adjustmentType <= 60) {
                            // 60% positive adjustment (found stock, correction)
                            $adjustmentQty = rand(1, 50);
                            $productStock[$product->id] = ($productStock[$product->id] ?? 0) + $adjustmentQty;
                            $product->increment('quantity', $adjustmentQty);
                            $reason = 'Stock Adjustment - Found/Correction';
                        } else {
                            // 40% negative adjustment (damaged, lost, correction)
                            $currentStock = $productStock[$product->id] ?? 0;
                            if ($currentStock > 0) {
                                $adjustmentQty = rand(1, min(30, $currentStock));
                                $productStock[$product->id] -= $adjustmentQty;
                                $product->decrement('quantity', $adjustmentQty);
                                $reason = 'Stock Adjustment - Damaged/Lost/Correction';
                            } else {
                                continue;
                            }
                        }
                        
                        InventoryMovement::create([
                            'product_id' => $product->id,
                            'user_id' => $adminUser->id,
                            'type' => 'adjustment',
                            'quantity' => $adjustmentQty,
                            'reason' => $reason,
                            'reference' => "ADJ-{$year}" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-" . ($i + 1),
                            'created_at' => $adjustmentDate,
                            'updated_at' => $adjustmentDate,
                        ]);
                    }
                }
            }

            // 5. End of year stock check (December 28-31)
            $yearEndDate = Carbon::create($year, 12, rand(28, 31), 14, 0, 0);
            $this->command->info("  Adding year-end stock check for {$year}...");
            
            // Select 30-50% of products for year-end adjustments
            $productsToCheck = $products->random(rand((int)($products->count() * 0.3), (int)($products->count() * 0.5)));
            
            foreach ($productsToCheck as $product) {
                // Small adjustments for year-end reconciliation
                $adjustmentQty = rand(1, 20);
                $adjustmentType = rand(1, 100);
                
                if ($adjustmentType <= 70) {
                    // Positive adjustment
                    $productStock[$product->id] = ($productStock[$product->id] ?? 0) + $adjustmentQty;
                    $product->increment('quantity', $adjustmentQty);
                    $reason = "Year-End Stock Reconciliation - {$year}";
                } else {
                    // Negative adjustment
                    $currentStock = $productStock[$product->id] ?? 0;
                    if ($currentStock > 0) {
                        $adjustmentQty = min($adjustmentQty, $currentStock);
                        $productStock[$product->id] -= $adjustmentQty;
                        $product->decrement('quantity', $adjustmentQty);
                        $reason = "Year-End Stock Reconciliation - {$year}";
                    } else {
                        continue;
                    }
                }
                
                InventoryMovement::create([
                    'product_id' => $product->id,
                    'user_id' => $adminUser->id,
                    'type' => 'adjustment',
                    'quantity' => $adjustmentQty,
                    'reason' => $reason,
                    'reference' => "YEAREND-{$year}",
                    'created_at' => $yearEndDate,
                    'updated_at' => $yearEndDate,
                ]);
            }

            $this->command->info("  Completed year {$year} - Generated stock out movements: {$stockOutCount}");
        }

        $this->command->info("Inventory history generation complete for 2024-2026!");
        $this->command->info("All inventory movements have been created and product quantities updated.");
    }
}

