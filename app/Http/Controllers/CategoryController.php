<?php

namespace App\Http\Controllers;

use App\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Check if user has permission to modify categories
     */
    private function checkModifyPermission()
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized action. Only admins can modify categories.');
        }
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

        Category::create($request->all());
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
        return redirect()->route('categories.index')->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category)
    {
        $this->checkModifyPermission();
        if ($category->products()->count() > 0) {
            return redirect()->route('categories.index')->with('error', 'Cannot delete category with existing products.');
        }
        $category->delete();
        return redirect()->route('categories.index')->with('success', 'Category deleted successfully.');
    }
}
