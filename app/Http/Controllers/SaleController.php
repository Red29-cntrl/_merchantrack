<?php

namespace App\Http\Controllers;

use App\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => 'nullable|string',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'sort_by' => 'nullable|in:sale_number,date,cashier,total,items',
            'sort_order' => 'nullable|in:asc,desc',
        ]);

        $query = Sale::with('user', 'items.product')
            ->whereHas('user', function($q) {
                $q->where('role', '!=', 'admin');
            });

        if ($request->filled('search')) {
            $query->where('sale_number', 'like', '%' . $validated['search'] . '%');
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $validated['date_from']);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $validated['date_to']);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'sale_number');
        $sortOrder = $request->get('sort_order', 'desc');
        
        switch ($sortBy) {
            case 'sale_number':
                // Sort by sale number (natural sort for INV-YYYYMMDD-XXXX format)
                $query->orderBy('sale_number', $sortOrder);
                break;
            case 'total':
                $query->orderBy('total', $sortOrder);
                break;
            case 'cashier':
                $query->join('users', 'sales.user_id', '=', 'users.id')
                      ->orderBy('users.name', $sortOrder)
                      ->select('sales.*');
                break;
            case 'items':
                $query->withCount('items')->orderBy('items_count', $sortOrder);
                break;
            case 'date':
            default:
                $query->orderBy('created_at', $sortOrder);
                break;
        }

        $sales = $query->paginate(15)->appends($request->query());
        return view('sales.index', compact('sales', 'sortBy', 'sortOrder'));
    }

    public function show(Sale $sale)
    {
        // Prevent viewing sales with admin cashiers
        if ($sale->user->role === 'admin') {
            return redirect()->route('sales.index')->with('error', 'Sale not found.');
        }
        
        $sale->load('user', 'items.product');
        return view('sales.show', compact('sale'));
    }

    public function destroy(Sale $sale)
    {
        DB::beginTransaction();
        try {
            foreach ($sale->items as $item) {
                $item->product->increment('quantity', $item->quantity);
            }
            $sale->delete();
            DB::commit();
            return redirect()->route('sales.index')->with('success', 'Sale deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('sales.index')->with('error', 'Error deleting sale.');
        }
    }
}
