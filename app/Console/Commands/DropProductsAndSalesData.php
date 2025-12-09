<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\SaleItem;
use App\Sale;
use App\Product;
use App\InventoryMovement;
use App\DemandForecast;
use Illuminate\Support\Facades\DB;

class DropProductsAndSalesData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:drop-products-sales {--force : Force the operation without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Drop all data from Products and Sales History tables';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (!$this->option('force') && !$this->confirm('Are you sure you want to delete ALL products and sales data? This action cannot be undone!')) {
            $this->info('Operation cancelled.');
            return 0;
        }

        $this->info('Starting data deletion...');

        try {
            DB::beginTransaction();

            // Disable foreign key checks temporarily
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            // Delete SaleItems first (they reference both Sales and Products)
            $saleItemsCount = SaleItem::count();
            $this->info("Deleting {$saleItemsCount} sale items...");
            SaleItem::truncate();
            $this->info('✓ Sale items deleted.');

            // Delete Inventory Movements (they reference Products)
            $movementsCount = InventoryMovement::count();
            $this->info("Deleting {$movementsCount} inventory movements...");
            InventoryMovement::truncate();
            $this->info('✓ Inventory movements deleted.');

            // Delete Demand Forecasts (they reference Products)
            $forecastsCount = DemandForecast::count();
            $this->info("Deleting {$forecastsCount} demand forecasts...");
            DemandForecast::truncate();
            $this->info('✓ Demand forecasts deleted.');

            // Delete Sales
            $salesCount = Sale::count();
            $this->info("Deleting {$salesCount} sales...");
            Sale::truncate();
            $this->info('✓ Sales deleted.');

            // Delete Products
            $productsCount = Product::count();
            $this->info("Deleting {$productsCount} products...");
            Product::truncate();
            $this->info('✓ Products deleted.');

            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            DB::commit();

            $this->info('');
            $this->info('✓ Successfully deleted all products and sales data!');
            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error occurred: ' . $e->getMessage());
            return 1;
        }
    }
}
