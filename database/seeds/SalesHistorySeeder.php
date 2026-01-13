<?php

use Illuminate\Database\Seeder;
use App\Product;
use App\Sale;
use App\SaleItem;
use App\User;
use App\InventoryMovement;
use Carbon\Carbon;

class SalesHistorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Generate sales history for 2024 (January to December)
     * - 20-30 products sold per day
     * - Connected to inventory "out" movements
     * - Monthly inventory "in" movements (supplier orders)
     * - All data connected to demand forecasting
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

        // Get only staff users (exclude admin)
        $staffUsers = User::where('role', '!=', 'admin')->get();
        if ($staffUsers->isEmpty()) {
            $this->command->warn('No staff users found. Please seed users first.');
            return;
        }

        $firstUser = $staffUsers->first() ?? User::first();

        $this->command->info('Generating sales history for 2024 (January to December)...');
        $this->command->info('Target: 20-30 products sold per day');

        // Add initial stock to all products at the start of 2024
        $this->command->info('Adding initial inventory for 2024...');
        $initialStockDate = Carbon::create(2024, 1, 1, 8, 0, 0);
        
        foreach ($products as $product) {
            // Add substantial initial stock (500-1500 units per product)
            $initialQty = rand(500, 1500);
            $product->increment('quantity', $initialQty);
            
            // Create inventory movement for initial stock
            InventoryMovement::create([
                'product_id' => $product->id,
                'user_id' => $firstUser->id,
                'type' => 'in',
                'quantity' => $initialQty,
                'reason' => 'Initial Stock - 2024',
                'reference' => 'INIT-2024',
                'created_at' => $initialStockDate,
                'updated_at' => $initialStockDate,
            ]);
        }

        // Track stock levels for each product (in memory)
        $productStock = [];
        foreach ($products as $product) {
            $productStock[$product->id] = $product->quantity;
        }

        // Generate sales for every day in 2024 (January to December)
        $startDate = Carbon::create(2024, 1, 1, 9, 0, 0);
        $endDate = Carbon::create(2024, 12, 31, 20, 0, 0);
        
        $currentDate = $startDate->copy();
        $saleCount = 0;
        $invoiceCounter = 1;

        while ($currentDate->lte($endDate)) {
            // Target: 20-30 unique products sold per day
            $targetProductsPerDay = rand(20, 30);
            $maxProducts = min($targetProductsPerDay, $products->count());
            
            // Select unique products for the day
            $productsForDay = $products->random($maxProducts);
            $productsSoldToday = [];
            
            // Determine number of sales per day (varies by day of week)
            $dayOfWeek = $currentDate->dayOfWeek; // 0 = Sunday, 6 = Saturday
            $isWeekend = ($dayOfWeek == 0 || $dayOfWeek == 6);
            
            // More sales on weekdays, fewer on weekends
            $salesPerDay = $isWeekend ? rand(8, 15) : rand(15, 25);
            
            // Generate sales for this day
            for ($saleNum = 0; $saleNum < $salesPerDay; $saleNum++) {
                // Random time during business hours (9 AM to 8 PM)
                $saleTime = $currentDate->copy()->setTime(rand(9, 20), rand(0, 59), rand(0, 59));
                
                // Random staff user
                $user = $staffUsers->random();

                // Random number of items per sale (1-5 items)
                $itemCount = rand(1, min(5, $productsForDay->count()));
                $selectedProducts = $productsForDay->random($itemCount);

                $subtotal = 0;
                $items = [];

                foreach ($selectedProducts as $product) {
                    // Generate realistic quantity (1-30 units)
                    $rand = rand(1, 100);
                    if ($rand <= 50) {
                        $quantity = rand(1, 5); // 50% chance: 1-5 units
                    } elseif ($rand <= 80) {
                        $quantity = rand(6, 15); // 30% chance: 6-15 units
                    } elseif ($rand <= 95) {
                        $quantity = rand(16, 25); // 15% chance: 16-25 units
                    } else {
                        $quantity = rand(26, 30); // 5% chance: 26-30 units
                    }
                    
                    $unitPrice = $product->price;
                    $itemSubtotal = $unitPrice * $quantity;

                    $items[] = [
                        'product' => $product,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'subtotal' => $itemSubtotal,
                    ];

                    $subtotal += $itemSubtotal;
                    
                    // Track products sold today
                    if (!isset($productsSoldToday[$product->id])) {
                        $productsSoldToday[$product->id] = 0;
                    }
                    $productsSoldToday[$product->id] += $quantity;
                }

                if (empty($items)) continue;

                // Random tax (0-12%)
                $taxRate = rand(0, 12);
                $tax = $subtotal * ($taxRate / 100);

                // Random discount (0-20%)
                $discountRate = rand(0, 20);
                $discount = $subtotal * ($discountRate / 100);

                $total = $subtotal + $tax - $discount;

                // Payment method - cash only
                $paymentMethod = 'cash';

                // Generate invoice number (INV-0000-0000-0000-0001 format)
                $invoiceNumber = 'INV-' . str_pad(0, 4, '0', STR_PAD_LEFT) . '-' . 
                                 str_pad(0, 4, '0', STR_PAD_LEFT) . '-' . 
                                 str_pad(0, 4, '0', STR_PAD_LEFT) . '-' . 
                                 str_pad($invoiceCounter, 4, '0', STR_PAD_LEFT);
                $invoiceCounter++;

                // Create sale
                $sale = Sale::create([
                    'sale_number' => $invoiceNumber,
                    'user_id' => $user->id,
                    'subtotal' => round($subtotal, 2),
                    'tax' => round($tax, 2),
                    'discount' => round($discount, 2),
                    'total' => round($total, 2),
                    'payment_method' => $paymentMethod,
                    'notes' => null,
                    'created_at' => $saleTime,
                    'updated_at' => $saleTime,
                ]);

                // Create sale items and update inventory
                $validItems = [];
                foreach ($items as $item) {
                    $productId = $item['product']->id;
                    
                    // Check if we have enough stock in memory
                    if (isset($productStock[$productId]) && $productStock[$productId] >= $item['quantity']) {
                        $validItems[] = $item;
                        $productStock[$productId] -= $item['quantity'];
                    }
                }
                
                // If no valid items, delete sale and continue
                if (empty($validItems)) {
                    $sale->delete();
                    continue;
                }
                
                // Update sale total if items changed
                if (count($validItems) != count($items)) {
                    $newSubtotal = array_sum(array_column($validItems, 'subtotal'));
                    $newTax = $newSubtotal * ($taxRate / 100);
                    $newTotal = $newSubtotal + $newTax - $discount;
                    $sale->update([
                        'subtotal' => round($newSubtotal, 2),
                        'tax' => round($newTax, 2),
                        'total' => round($newTotal, 2),
                    ]);
                }
                
                foreach ($validItems as $item) {
                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => $item['product']->id,
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'subtotal' => $item['subtotal'],
                        'created_at' => $saleTime,
                        'updated_at' => $saleTime,
                    ]);

                    // Update actual product quantity in database
                    $item['product']->decrement('quantity', $item['quantity']);

                    // Create inventory "out" movement (connected to sale)
                    InventoryMovement::create([
                        'product_id' => $item['product']->id,
                        'user_id' => $user->id,
                        'type' => 'out',
                        'quantity' => $item['quantity'],
                        'reason' => 'Sale',
                        'reference' => $invoiceNumber,
                        'created_at' => $saleTime,
                        'updated_at' => $saleTime,
                    ]);
                }
                
                $saleCount++;
            }
            
            // Monthly inventory "in" movements (supplier orders) - on 1st of each month
            if ($currentDate->day == 1) {
                $this->command->info("Adding monthly supplier order for " . $currentDate->format('F Y') . "...");
                
                foreach ($products as $product) {
                    // Calculate replenishment based on average monthly sales
                    // More realistic: replenish based on expected demand
                    $replenishQty = rand(200, 500);
                    $productStock[$product->id] = ($productStock[$product->id] ?? 0) + $replenishQty;
                    $product->increment('quantity', $replenishQty);
                    
                    // Create inventory "in" movement (supplier order)
                    InventoryMovement::create([
                        'product_id' => $product->id,
                        'user_id' => $firstUser->id,
                        'type' => 'in',
                        'quantity' => $replenishQty,
                        'reason' => 'Monthly Supplier Order',
                        'reference' => 'SUPPLIER-' . $currentDate->format('Ym'),
                        'created_at' => $currentDate->copy()->setTime(8, 0, 0),
                        'updated_at' => $currentDate->copy()->setTime(8, 0, 0),
                    ]);
                }
            }
            
            // Progress indicator
            if ($currentDate->day == 1) {
                $this->command->info("Generated sales for " . $currentDate->format('F Y') . " - Total sales so far: {$saleCount}");
            }
            
            // Move to next day
            $currentDate->addDay();
        }

        $this->command->info("Generated {$saleCount} sales transactions for 2024!");
        $this->command->info("All sales are connected to inventory 'out' movements.");
        $this->command->info("Monthly supplier orders are connected to inventory 'in' movements.");
        $this->command->info("Data is ready for demand forecasting analysis.");
    }
}
