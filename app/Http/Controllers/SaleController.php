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
        $query = Sale::with('user', 'items.product');

        if ($request->has('search')) {
            $query->where('sale_number', 'like', '%' . $request->search . '%');
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $sales = $query->latest()->paginate(15);
        return view('sales.index', compact('sales'));
    }

    public function show(Sale $sale)
    {
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
