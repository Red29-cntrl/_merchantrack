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
        $stats = [
            'total_products' => Product::count(),
            'total_categories' => Category::count(),
            'low_stock_products' => Product::whereColumn('quantity', '<=', 'reorder_level')->count(),
            'total_sales_today' => Sale::whereDate('created_at', today())->count(),
            'revenue_today' => Sale::whereDate('created_at', today())->sum('total'),
            'revenue_this_month' => Sale::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('total'),
            'total_users' => User::count(),
        ];

        $recent_sales = Sale::with('user', 'items.product')
            ->latest()
            ->take(10)
            ->get();

        $low_stock_products = Product::with('category')
            ->whereColumn('quantity', '<=', 'reorder_level')
            ->take(10)
            ->get();

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
