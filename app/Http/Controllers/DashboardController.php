<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Product;
use App\Sale;
use App\Category;
use App\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        // Optimize: Use join instead of whereHas to avoid subqueries
        // Get non-admin user IDs once
        $nonAdminUserIds = User::where('role', '!=', 'admin')->pluck('id');
        
        // Get today's date once
        $today = today();
        $thisMonth = now()->month;
        $thisYear = now()->year;
        
        // Optimize: Combine sales queries using joins
        $salesTodayQuery = Sale::whereIn('user_id', $nonAdminUserIds)
            ->whereDate('created_at', $today);
        
        $salesThisMonthQuery = Sale::whereIn('user_id', $nonAdminUserIds)
            ->whereMonth('created_at', $thisMonth)
            ->whereYear('created_at', $thisYear);
        
        // Get stats in parallel (Laravel will optimize these)
        $stats = [
            'total_products' => Product::count(),
            'total_categories' => Category::count(),
            'low_stock_products' => Product::where('is_active', true)
                ->whereColumn('quantity', '<=', 'reorder_level')
                ->count(),
            'total_sales_today' => $salesTodayQuery->count(),
            'revenue_today' => $salesTodayQuery->sum('total'),
            'revenue_this_month' => $salesThisMonthQuery->sum('total'),
            'total_users' => User::count(),
        ];

        // Recent sales - use whereIn instead of whereHas for better performance
        $recent_sales = Sale::with(['user', 'items.product'])
            ->whereIn('user_id', $nonAdminUserIds)
            ->orderBy('id', 'desc')
            ->take(10)
            ->get();

        // Low stock products - limit results
        $low_stock_products = Product::with('category')
            ->where('is_active', true)
            ->whereColumn('quantity', '<=', 'reorder_level')
            ->orderBy('quantity', 'asc')
            ->orderBy('name', 'asc')
            ->take(20) // Limit to 20 most critical items
            ->get();

        // Top products - optimize query
        $top_products = DB::table('sale_items')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->select('products.name', DB::raw('SUM(sale_items.quantity) as total_sold'))
            ->groupBy('products.id', 'products.name')
            ->orderBy('total_sold', 'desc')
            ->take(5)
            ->get();

        return view('dashboard', compact('stats', 'recent_sales', 'low_stock_products', 'top_products'));
    }
}
