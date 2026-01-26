<?php

namespace App\Console\Commands;

use App\Product;
use App\InventoryMovement;
use App\User;
use Illuminate\Console\Command;

class CreateOpeningInventoryBalances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:create-opening-balances';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create opening inventory movements for products that do not yet have any inventory history';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Find a user to attribute the movements to (prefer an admin)
        $user = User::where('role', 'admin')->first() ?? User::first();

        if (!$user) {
            $this->error('No users found. Cannot create inventory movements without a user_id.');
            return 1;
        }

        $this->info("Using user '{$user->name}' (ID: {$user->id}) for opening balance records.");

        $created = 0;

        Product::chunk(100, function ($products) use (&$created, $user) {
            foreach ($products as $product) {
                // Skip products that already have any inventory movement
                if ($product->inventoryMovements()->exists()) {
                    continue;
                }

                // If quantity is zero, no need to create an opening balance
                if ((int) $product->quantity === 0) {
                    continue;
                }

                InventoryMovement::create([
                    'product_id' => $product->id,
                    'user_id' => $user->id,
                    'type' => 'in',
                    'quantity' => (int) $product->quantity,
                    'reason' => 'Opening balance created from current product quantity',
                    'reference' => 'opening-balance',
                ]);

                $created++;
                $this->line("Created opening balance for product ID {$product->id} ({$product->name}) with quantity {$product->quantity}.");
            }
        });

        $this->info("Done. Created {$created} opening inventory movement(s).");

        return 0;
    }
}


