<?php

use App\BusinessSetting;
use App\Category;
use App\Product;
use App\Sale;
use App\SaleItem;
use App\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AlNesReceiptSeeder extends Seeder
{
    /**
     * Convert handwritten AL-NES delivery receipt to digital format
     * Receipt #67282 - June 7, 2024
     */
    public function run()
    {
        DB::beginTransaction();
        try {
            // 1. Create/Update Business Settings - EXACTLY as shown on business header
            $businessSettings = BusinessSetting::firstOrNew(['id' => 1]);
            $businessSettings->business_name = 'AL-NES GENERAL MERCHANDISE';
            $businessSettings->receipt_type = 'SALES INVOICE';
            $businessSettings->business_type = 'HARDWARE CONSTRUCTION MATERIALS & ELECTRICAL SUPPLIES';
            $businessSettings->address = 'Market Site Sto. Domingo, Albay';
            $businessSettings->proprietor = 'ALVIN I. CUA ';
            $businessSettings->vat_reg_tin = '183-740-051-000';
            $businessSettings->phone = '0929-570-6220 / 0930-870-0068';
            $businessSettings->bir_authority_to_print = '1AU0002323456';
            $businessSettings->atp_date_issued = '2021-03-31';
            $businessSettings->atp_valid_until = '2026-03-30';
            $businessSettings->printer_name = 'CITIPRINT ENTERPRISE - Legazpi City';
            $businessSettings->printer_accreditation_no = '067MP20190000000013';
            $businessSettings->printer_accreditation_date = '2019-03-12';
            $businessSettings->receipt_footer_note = 'THIS SALES INVOICE SHALL BE VALID FOR FIVE (5) YEARS FROM THE DATE OF ATP. This document is not valid for claiming input tax.';
            $businessSettings->save();

            // 2. Get or Create Category for Kitchen/Hardware items
            $category = Category::firstOrCreate(
                ['name' => 'Kitchen Supplies'],
                ['description' => 'Kitchen and household items']
            );

            // 3. Create Standardized Product from readable receipt data
            // Original handwritten: "S/S STRAINNER" - Standardized to "Stainless Steel Strainer"
            // Quantity: 1 PC, Price: 150 (readable from receipt)
            $product = Product::firstOrCreate(
                ['sku' => 'SS-STRAINER-001'],
                [
                    'name' => 'Stainless Steel Strainer', // Standardized from "S/S STRAINNER"
                    'description' => 'Stainless Steel Strainer - Kitchen utensil for straining',
                    'category_id' => $category->id,
                    'price' => 150.00, // Readable from receipt: "150-"
                    'cost' => 0.00, // Not readable from receipt - left blank
                    'quantity' => 0, // Not readable from receipt - left blank
                    'unit' => 'pcs', // Standardized from "PC"
                    'reorder_level' => 0, // Not readable from receipt - left blank
                    'is_active' => true,
                ]
            );

            // 4. Get admin user for the sale
            $adminUser = User::where('email', 'admin@merchantrack.com')->first();
            if (!$adminUser) {
                $adminUser = User::first();
            }

            // 5. Create Sale Record (Receipt #67282)
            // Check if sale already exists
            $existingSale = Sale::where('sale_number', 'DR-67282')->first();
            
            if (!$existingSale) {
                // Date from receipt: "6-7-X" (handwritten, year unclear)
                // Only readable data: Month=6, Day=7, Year=unknown
                // Using current year as fallback, but noting uncertainty
                $saleDate = Carbon::now()->month(6)->day(7);
                if ($saleDate->isFuture()) {
                    $saleDate->subYear(); // If date is in future, use previous year
                }
                
                // Build sale data array
                $saleData = [
                    'sale_number' => 'DR-67282', // Delivery Receipt number (readable: "No.67282")
                    'user_id' => $adminUser->id,
                    'subtotal' => 150.00, // Readable from receipt
                    'tax' => 0.00, // Not readable from receipt - left blank
                    'discount' => 0.00, // Not readable from receipt - left blank
                    'total' => 150.00, // Readable from receipt: "150-"
                    'payment_method' => 'cash', // Not explicitly readable, but "PAID" stamp visible
                    'notes' => 'Converted from handwritten delivery receipt #67282. Date: 6-7-X (year unclear)',
                ];
                
                // Only add sale_date if the column exists in the database
                if (Schema::hasColumn('sales', 'sale_date')) {
                    $saleData['sale_date'] = $saleDate; // Month=6, Day=7, Year=estimated
                }
                
                $sale = Sale::create($saleData);

                // 6. Create Sale Item
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit' => 'pcs',
                    'unit_price' => 150.00,
                    'subtotal' => 150.00,
                ]);

                $this->command->info('✅ AL-NES Receipt #67282 imported successfully!');
                $this->command->info('   - Business Settings: AL-NES GENERAL MERCHANDISE');
                $this->command->info('   - Product: Stainless Steel Strainer (₱150.00)');
                $this->command->info('   - Sale: DR-67282 created');
            } else {
                $this->command->warn('⚠️  Sale DR-67282 already exists. Skipping...');
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('❌ Error importing receipt: ' . $e->getMessage());
            throw $e;
        }
    }
}
