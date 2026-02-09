<?php

namespace App\Http\Controllers;

use App\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Check if user has permission to modify categories (admin only).
     */
    private function checkModifyPermission()
    {
        Gate::authorize('manage_categories');
    }

    public function index()
    {
        $categories = Category::withCount('products')->latest()->paginate(15);
        return view('categories.index', compact('categories'));
    }

    public function create()
    {
        $this->checkModifyPermission();
        return view('categories.create');
    }

    public function store(Request $request)
    {
        $this->checkModifyPermission();
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $category = Category::create($request->all());
        
        // Broadcast category creation for real-time sync
        if (config('broadcasting.default') !== 'null') {
            try {
                event(new \App\Events\CategoryUpdated($category, 'created', auth()->user()->role, auth()->user()->name));
            } catch (\Throwable $e) {
                \Log::warning('Failed to broadcast CategoryUpdated event: ' . $e->getMessage());
            }
        }
        
        return redirect()->route('categories.index')->with('success', 'Category created successfully.');
    }

    public function show(Category $category)
    {
        $category->load('products');
        return view('categories.show', compact('category'));
    }

    public function edit(Category $category)
    {
        $this->checkModifyPermission();
        return view('categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        $this->checkModifyPermission();
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $category->update($request->all());
        
        // Broadcast category update for real-time sync
        if (config('broadcasting.default') !== 'null') {
            try {
                event(new \App\Events\CategoryUpdated($category, 'updated', auth()->user()->role, auth()->user()->name));
            } catch (\Throwable $e) {
                \Log::warning('Failed to broadcast CategoryUpdated event: ' . $e->getMessage());
            }
        }
        
        return redirect()->route('categories.index')->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category)
    {
        $this->checkModifyPermission();
        if ($category->products()->count() > 0) {
            return redirect()->route('categories.index')->with('error', 'Cannot delete category with existing products.');
        }
        
        // Store category data before deletion for broadcast
        $categoryData = $category->toArray();
        
        // Broadcast category deletion before deleting
        if (config('broadcasting.default') !== 'null') {
            try {
                event(new \App\Events\CategoryUpdated($category, 'deleted', auth()->user()->role, auth()->user()->name));
            } catch (\Throwable $e) {
                \Log::warning('Failed to broadcast CategoryUpdated event: ' . $e->getMessage());
            }
        }
        
        $category->delete();
        return redirect()->route('categories.index')->with('success', 'Category deleted successfully.');
    }
}
