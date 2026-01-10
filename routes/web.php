<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\POSController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\DemandForecastController;
use App\Http\Controllers\SupplierController;

// Authentication Routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Redirect root to login
Route::get('/', function () {
    return redirect()->route('login');
});

// Protected Routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Shared routes (both staff and admin can view)
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    // Note: create/edit routes must be defined before {product} route to avoid route conflicts
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    // Note: create/edit routes must be defined before {category} route to avoid route conflicts
    
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    
    // Staff-only Routes
    Route::middleware('staff')->group(function () {
        // POS Routes (Staff only)
        Route::get('/pos', [POSController::class, 'index'])->name('pos.index');
        Route::get('/pos/product/{id}', [POSController::class, 'getProduct'])->name('pos.getProduct');
        Route::post('/pos/process-sale', [POSController::class, 'processSale'])->name('pos.processSale');
    });
    
    // Admin-only Routes
    Route::middleware('admin')->group(function () {
        // Users Management
        Route::resource('users', UserController::class);
        
        // Products Management (Modify operations only - view is shared above)
        // IMPORTANT: create/edit routes must be defined BEFORE {product} route
        Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
        Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::patch('/products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
        
        // Categories Management (Modify operations only - view is shared above)
        // IMPORTANT: create/edit routes must be defined BEFORE {category} route
        Route::get('/categories/create', [CategoryController::class, 'create'])->name('categories.create');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::get('/categories/{category}/edit', [CategoryController::class, 'edit'])->name('categories.edit');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::patch('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
        
        // Sales History
        Route::resource('sales', SaleController::class)->only(['index', 'show', 'destroy']);
        
        // Inventory Management (with in/out)
        Route::post('/inventory/adjust/{product}', [InventoryController::class, 'adjust'])->name('inventory.adjust');
        Route::get('/inventory/monthly-summary', [InventoryController::class, 'monthlySummary'])->name('inventory.monthly-summary');
        
        // Demand Forecast
        Route::get('/forecasts', [DemandForecastController::class, 'index'])->name('forecasts.index');
        Route::post('/forecasts/generate', [DemandForecastController::class, 'generate'])->name('forecasts.generate');
        Route::get('/forecasts/{forecast}', [DemandForecastController::class, 'show'])->name('forecasts.show');
        
        // Supplier Management
        Route::post('/suppliers/{supplier}/toggle-status', [SupplierController::class, 'toggleStatus'])->name('suppliers.toggle-status');
        Route::resource('suppliers', SupplierController::class);
    });
    
    // Shared view routes (must be after create/edit to avoid conflicts)
    Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
    Route::get('/categories/{category}', [CategoryController::class, 'show'])->name('categories.show');
});
