<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPerformanceIndexesToTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sales', function (Blueprint $table) {
            // Index for filtering by user_id and created_at (used in dashboard queries)
            $table->index(['user_id', 'created_at'], 'sales_user_created_idx');
            // Index for date filtering
            $table->index('created_at', 'sales_created_at_idx');
        });

        Schema::table('products', function (Blueprint $table) {
            // Index for low stock queries
            $table->index(['is_active', 'quantity', 'reorder_level'], 'products_low_stock_idx');
            // Index for category filtering
            $table->index('category_id', 'products_category_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            // Index for role filtering (used in whereHas queries)
            $table->index('role', 'users_role_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('sales_user_created_idx');
            $table->dropIndex('sales_created_at_idx');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_low_stock_idx');
            $table->dropIndex('products_category_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_role_idx');
        });
    }
}
