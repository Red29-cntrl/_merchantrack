<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Merchantrack - POS & Inventory System')</title>
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#852E4E">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Merchantrack">
    <meta name="description" content="Point of Sale and Inventory Management System with Consumer Demand Forecasting">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    
    <!-- Favicon and Icons -->
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('favicon.ico') }}">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #852E4E;
            --primary-dark: #4C1D3D;
            --primary-medium: #A33757;
            --primary-light: #DC586D;
            --accent: #FB9590;
            --accent-light: #FFBB94;
        }
        body {
            background-color: #ffffff;
        }
        /* Ensure all clickable elements have proper cursor */
        a, button, .btn {
            cursor: pointer;
        }
        .sidebar {
            min-height: 100vh;
            background: #4C1D3D;
            color: white;
            width: 250px;
            position: fixed;
            left: 0;
            top: 0;
            padding-top: 20px;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        .sidebar .logo {
            padding: 12px 20px;
            text-align: center; /* upper-center in the sidebar */
            border-bottom: 1px solid rgba(255,255,255,0.2);
            margin: 5px 10px 20px 10px;
        }
        .sidebar .logo img {
            max-width: 180px;
            height: auto;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            margin: 5px 10px;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s, color 0.3s;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        .navbar-custom {
            background: white;
            border-bottom: 2px solid #852E4E;
            margin-bottom: 20px;
        }
        .card {
            border: 1px solid #DC586D;
            border-radius: 8px;
            background: white;
            margin-bottom: 20px;
        }
        .card-header {
            background-color: #852E4E !important;
            color: white;
            border-bottom: 1px solid #DC586D;
        }
        .card-header.bg-primary {
            background-color: #852E4E !important;
        }
        .stat-card {
            background: #852E4E;
            color: white;
        }
        .stat-card.success {
            background: #852E4E;
            color: white;
        }
        .stat-card.warning {
            background: #852E4E;
            color: white;
        }
        .stat-card.info {
            background: #852E4E;
            color: white;
        }
        .btn-primary {
            background-color: #852E4E;
            border-color: #852E4E;
            color: #ffffff;
            cursor: pointer;
        }
        .btn-primary:hover {
            background-color: #4C1D3D;
            border-color: #4C1D3D;
        }
        .btn-secondary {
            background-color: #FFBB94;
            border-color: #DC586D;
            color: #4C1D3D;
            cursor: pointer;
        }
        .btn-secondary:hover {
            background-color: #FB9590;
            border-color: #DC586D;
        }
        .btn-warning {
            background-color: #852E4E;
            border-color: #852E4E;
            color: #ffffff;
            cursor: pointer;
        }
        .btn-warning:hover {
            background-color: #4C1D3D;
            border-color: #4C1D3D;
        }
        .btn-danger {
            background-color: #A33757;
            border-color: #A33757;
            color: #ffffff;
            cursor: pointer;
        }
        .btn-danger:hover {
            background-color: #852E4E;
            border-color: #852E4E;
        }
        .btn-info {
            background-color: #852E4E;
            border-color: #852E4E;
            color: #ffffff;
            cursor: pointer;
        }
        .btn-info:hover {
            background-color: #4C1D3D;
            border-color: #4C1D3D;
        }
        .btn-success {
            background-color: #A33757;
            border-color: #A33757;
            color: #ffffff;
            cursor: pointer;
        }
        .btn-success:hover {
            background-color: #852E4E;
            border-color: #852E4E;
        }
        .badge {
            border: 1px solid #DC586D;
        }
        .badge.bg-primary, .badge.bg-dark {
            background-color: #852E4E !important;
            color: #ffffff;
        }
        .badge.bg-success {
            background-color: #852E4E !important;
            color: #ffffff;
        }
        .badge.bg-warning {
            background-color: #852E4E !important;
            color: #ffffff;
            border: 1px solid #852E4E;
        }
        .badge.bg-info {
            background-color: #852E4E !important;
            color: #ffffff;
            border: 1px solid #852E4E;
        }
        .badge.bg-danger {
            background-color: #852E4E !important;
            color: #ffffff;
            border: 1px solid #852E4E;
        }
        .badge.bg-secondary {
            background-color: #FFBB94 !important;
            color: #4C1D3D;
            border: 1px solid #DC586D;
        }
        .table {
            border: 1px solid #DC586D;
        }
        .table thead {
            background-color: #852E4E;
            color: #ffffff;
        }
        .table tbody tr {
            border-bottom: 1px solid #FFBB94;
        }
        .form-control, .form-select {
            border: 1px solid #DC586D;
        }
        .form-control:focus, .form-select:focus {
            border-color: #852E4E;
            box-shadow: 0 0 0 0.2rem rgba(133, 46, 78, 0.25);
        }
        .alert {
            border: 1px solid #DC586D;
        }
        .alert-success {
            background-color: #FFBB94;
            color: #4C1D3D;
            border-color: #A33757;
        }
        .alert-danger {
            background-color: #FFBB94;
            color: #4C1D3D;
            border-color: #DC586D;
        }
        .pagination .page-link {
            color: #852E4E;
            border-color: #DC586D;
        }
        .pagination .page-link:hover {
            color: #ffffff;
            background-color: #852E4E;
            border-color: #852E4E;
        }
        .pagination .page-item.active .page-link {
            background-color: #852E4E;
            border-color: #852E4E;
            color: #ffffff;
        }
    </style>
    @yield('styles')
</head>
<body>
    @auth
    <div class="sidebar">
        <div class="logo">
            <h4 class="text-white mb-0">Merchantrack</h4>
        </div>
        <nav class="nav flex-column">
            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                <i class="fas fa-home me-2"></i> Dashboard
            </a>
            
            @if(auth()->user()->isStaff())
                {{-- Staff Menu --}}
                <a class="nav-link {{ request()->routeIs('pos.*') ? 'active' : '' }}" href="{{ route('pos.index') }}">
                    <i class="fas fa-cash-register me-2"></i> Point of Sale
                </a>
                <a class="nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}" href="{{ route('products.index') }}">
                    <i class="fas fa-box me-2"></i> Products
                </a>
                <a class="nav-link {{ request()->routeIs('categories.*') ? 'active' : '' }}" href="{{ route('categories.index') }}">
                    <i class="fas fa-tags me-2"></i> Categories
                </a>
                <a class="nav-link {{ request()->routeIs('inventory.*') ? 'active' : '' }}" href="{{ route('inventory.index') }}">
                    <i class="fas fa-warehouse me-2"></i> Inventory
                </a>
            @elseif(auth()->user()->isAdmin())
                {{-- Admin Menu --}}
                <a class="nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}" href="{{ route('products.index') }}">
                    <i class="fas fa-box me-2"></i> Products
                </a>
                <a class="nav-link {{ request()->routeIs('categories.*') ? 'active' : '' }}" href="{{ route('categories.index') }}">
                    <i class="fas fa-tags me-2"></i> Categories
                </a>
                <a class="nav-link {{ request()->routeIs('sales.*') ? 'active' : '' }}" href="{{ route('sales.index') }}">
                    <i class="fas fa-shopping-cart me-2"></i> Sales History
                </a>
                <a class="nav-link {{ request()->routeIs('inventory.*') ? 'active' : '' }}" href="{{ route('inventory.index') }}">
                    <i class="fas fa-warehouse me-2"></i> Inventory
                </a>
                <a class="nav-link {{ request()->routeIs('forecasts.*') ? 'active' : '' }}" href="{{ route('forecasts.index') }}">
                    <i class="fas fa-chart-line me-2"></i> Demand Forecast
                </a>
                <a class="nav-link {{ request()->routeIs('suppliers.*') ? 'active' : '' }}" href="{{ route('suppliers.index') }}">
                    <i class="fas fa-truck me-2"></i> Suppliers
                </a>
                <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">
                    <i class="fas fa-users me-2"></i> Users
                </a>
            @endif
            
            <a class="nav-link" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                <i class="fas fa-sign-out-alt me-2"></i> Logout
            </a>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                @csrf
            </form>
        </nav>
    </div>
    @endauth

    <div class="main-content">
        @auth
        <nav class="navbar navbar-expand-lg navbar-custom">
            <div class="container-fluid d-flex justify-content-between align-items-center">
                <span class="navbar-text">
                    Welcome, <strong>{{ auth()->user()->name }}</strong> 
                    <span class="badge bg-dark ms-2">{{ ucfirst(auth()->user()->role) }}</span>
                </span>
            </div>
        </nav>
        @endauth

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @yield('content')
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Laravel Echo & Pusher for Real-Time Updates -->
    <script src="{{ mix('js/app.js') }}" id="echo-script"></script>
    @auth
    <!-- Auto-sync when data changes on another device (no manual refresh needed) -->
    <script>
    (function() {
        // Store user role in JavaScript for reliable access
        const CURRENT_USER_ROLE = '{{ auth()->user()->role }}';
        console.log('Current user role:', CURRENT_USER_ROLE);
        
        // Helper function to check if admin (available before other scripts)
        function isAdmin() {
            return CURRENT_USER_ROLE === 'admin';
        }
        
        // Initialize lastSyncTime to 10 seconds before page load to catch recent sales
        // This ensures we don't miss sales created just before the page loaded
        // Using 10 seconds instead of 1 minute to avoid too much data on first load
        let lastSyncTime = new Date(Date.now() - 10000).toISOString(); // 10 seconds ago
        console.log('🔄 Initial lastSyncTime set to:', lastSyncTime, '(10 seconds ago)');
        let syncInterval = null;
        let websocketConnected = false;
        
        // Track known IDs for deletion detection
        function getKnownIds() {
            const stored = localStorage.getItem('sync_known_ids');
            if (stored) {
                try {
                    return JSON.parse(stored);
                } catch (e) {
                    return { products: [], categories: [], movements: [] };
                }
            }
            return { products: [], categories: [], movements: [] };
        }
        
        function updateKnownIds(type, ids) {
            const known = getKnownIds();
            known[type] = ids;
            localStorage.setItem('sync_known_ids', JSON.stringify(known));
        }
        
        // Fallback polling sync (works even without WebSocket)
        function startPollingSync() {
            if (syncInterval) {
                console.log('🔄 Polling sync already running');
                return; // Already started
            }
            
            console.log('🔄 Starting polling sync...');
            console.log('🔄 Initial lastSyncTime:', lastSyncTime);
            
            // Update sync status indicator
            const syncStatusEl = document.getElementById('sync-status');
            if (syncStatusEl) {
                syncStatusEl.innerHTML = '<i class="fas fa-sync-alt fa-spin"></i> Syncing...';
                syncStatusEl.className = 'text-muted';
            }
            
            // Do first check immediately
            const knownIds = getKnownIds();
            const params = new URLSearchParams();
            params.append('last_sync', lastSyncTime);
            params.append('known_product_ids', JSON.stringify(knownIds.products));
            params.append('known_category_ids', JSON.stringify(knownIds.categories));
            params.append('known_movement_ids', JSON.stringify(knownIds.movements));
            const syncUrl = '{{ route("sync.changes") }}?' + params.toString();
            
            fetch(syncUrl)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('HTTP error! status: ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('🔄 First sync check - Sales:', data.sales?.length || 0, 'Products:', data.products?.length || 0, 'Categories:', data.categories?.length || 0);
                    if (data.sales && data.sales.length > 0) {
                        console.log('📊 New sales detected on first check:', data.sales.length);
                        window.dispatchEvent(new CustomEvent('sync:newSales', { detail: data.sales }));
                    }
                    if (data.products && data.products.length > 0) {
                        console.log('📦 Product updates detected on first check:', data.products.length);
                        window.dispatchEvent(new CustomEvent('sync:productUpdates', { detail: data.products }));
                    }
                    if (data.products_deleted && data.products_deleted.length > 0) {
                        console.log('🗑️ Products deleted detected on first check:', data.products_deleted.length);
                        window.dispatchEvent(new CustomEvent('sync:productsDeleted', { detail: data.products_deleted }));
                    }
                    if (data.categories && data.categories.length > 0) {
                        console.log('🏷️ Category updates detected on first check:', data.categories.length);
                        window.dispatchEvent(new CustomEvent('sync:categoryUpdates', { detail: data.categories }));
                    }
                    if (data.categories_deleted && data.categories_deleted.length > 0) {
                        console.log('🗑️ Categories deleted detected on first check:', data.categories_deleted.length);
                        window.dispatchEvent(new CustomEvent('sync:categoriesDeleted', { detail: data.categories_deleted }));
                    }
                    if (data.inventory_movements && data.inventory_movements.length > 0) {
                        console.log('📋 Inventory movements detected on first check:', data.inventory_movements.length);
                        window.dispatchEvent(new CustomEvent('sync:inventoryMovements', { detail: data.inventory_movements }));
                    }
                    if (data.inventory_movements_deleted && data.inventory_movements_deleted.length > 0) {
                        console.log('🗑️ Inventory movements deleted detected on first check:', data.inventory_movements_deleted.length);
                        window.dispatchEvent(new CustomEvent('sync:inventoryMovementsDeleted', { detail: data.inventory_movements_deleted }));
                    }
                    if (data.timestamp) {
                        lastSyncTime = data.timestamp;
                        console.log('🔄 Updated lastSyncTime to:', lastSyncTime);
                    }
                })
                .catch(err => {
                    console.error('Sync polling error (first check):', err);
                    console.error('   → Check if route sync.changes is accessible');
                    console.error('   → Check browser console for CORS or 404 errors');
                    
                    // Update sync status indicator to show error
                    const syncStatusEl = document.getElementById('sync-status');
                    if (syncStatusEl) {
                        syncStatusEl.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Sync Error';
                        syncStatusEl.className = 'text-danger';
                    }
                });
            
            // Then check every 3 seconds
            syncInterval = setInterval(function() {
                console.log('🔄 Polling sync check - lastSyncTime:', lastSyncTime);
                const knownIds = getKnownIds();
                const params = new URLSearchParams();
                params.append('last_sync', lastSyncTime);
                params.append('known_product_ids', JSON.stringify(knownIds.products));
                params.append('known_category_ids', JSON.stringify(knownIds.categories));
                params.append('known_movement_ids', JSON.stringify(knownIds.movements));
                const syncUrl = '{{ route("sync.changes") }}?' + params.toString();
                
                fetch(syncUrl)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('HTTP error! status: ' + response.status);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.sales && data.sales.length > 0) {
                            console.log('📊 New sales detected:', data.sales.length, data.sales);
                            // Trigger custom event for pages to handle
                            window.dispatchEvent(new CustomEvent('sync:newSales', { detail: data.sales }));
                        }
                        if (data.products && data.products.length > 0) {
                            console.log('📦 Product updates detected:', data.products.length, data.products);
                            // Trigger custom event for pages to handle
                            window.dispatchEvent(new CustomEvent('sync:productUpdates', { detail: data.products }));
                        }
                        if (data.products_deleted && data.products_deleted.length > 0) {
                            console.log('🗑️ Products deleted detected:', data.products_deleted.length, data.products_deleted);
                            // Trigger custom event for pages to handle
                            window.dispatchEvent(new CustomEvent('sync:productsDeleted', { detail: data.products_deleted }));
                        }
                        if (data.categories && data.categories.length > 0) {
                            console.log('🏷️ Category updates detected:', data.categories.length, data.categories);
                            // Trigger custom event for pages to handle
                            window.dispatchEvent(new CustomEvent('sync:categoryUpdates', { detail: data.categories }));
                        }
                        if (data.categories_deleted && data.categories_deleted.length > 0) {
                            console.log('🗑️ Categories deleted detected:', data.categories_deleted.length, data.categories_deleted);
                            // Trigger custom event for pages to handle
                            window.dispatchEvent(new CustomEvent('sync:categoriesDeleted', { detail: data.categories_deleted }));
                        }
                        if (data.inventory_movements && data.inventory_movements.length > 0) {
                            console.log('📋 Inventory movements detected:', data.inventory_movements.length, data.inventory_movements);
                            // Trigger custom event for pages to handle
                            window.dispatchEvent(new CustomEvent('sync:inventoryMovements', { detail: data.inventory_movements }));
                        }
                        if (data.inventory_movements_deleted && data.inventory_movements_deleted.length > 0) {
                            console.log('🗑️ Inventory movements deleted detected:', data.inventory_movements_deleted.length, data.inventory_movements_deleted);
                            // Trigger custom event for pages to handle
                            window.dispatchEvent(new CustomEvent('sync:inventoryMovementsDeleted', { detail: data.inventory_movements_deleted }));
                        }
                        if (data.timestamp) {
                            lastSyncTime = data.timestamp;
                        }
                    })
                    .catch(err => {
                        console.error('Sync polling error:', err);
                        console.error('   → Route: {{ route("sync.changes") }}');
                        console.error('   → Check if server is running and route is accessible');
                    });
            }, 5000); // Check every 5 seconds (reduced frequency for better performance)
            
            console.log('✅ Polling sync started successfully');
        }
        
        function setupSync() {
            // ALWAYS start polling immediately - this is the primary sync method
            console.log('🔄 Setting up sync - starting polling sync immediately...');
            if (!syncInterval) {
                startPollingSync();
            } else {
                console.log('🔄 Polling already running, skipping start');
            }
            
            if (typeof window.Echo === 'undefined') {
                console.warn('⚠️ Laravel Echo is not available. Using polling only.');
                console.warn('   → For WebSocket support: npm run production');
                // Ensure polling is running
                if (!syncInterval) {
                    console.log('🔄 Starting polling as fallback...');
                    startPollingSync();
                }
                return false;
            }
            try {
                // Check connection status
                const pusher = window.Echo.connector.pusher;
                const connection = pusher.connection;
                
                console.log('✓ Laravel Echo is available');
                console.log('  Connection state:', connection.state);
                console.log('  WebSocket host:', pusher.config.wsHost);
                console.log('  WebSocket port:', pusher.config.wsPort);
                
                // Monitor connection
                connection.bind('state_change', function(states) {
                    console.log('WebSocket state changed:', states.previous, '→', states.current);
                    if (states.current === 'connected') {
                        console.log('✓ WebSocket connected successfully!');
                        websocketConnected = true;
                        // Keep polling running for admin users as backup
                        // Only disable polling if not admin
                        if (!isAdmin() && syncInterval) {
                            clearInterval(syncInterval);
                            syncInterval = null;
                            console.log('✓ Switched to WebSocket mode (polling disabled)');
                        } else {
                            console.log('✓ WebSocket connected, but keeping polling active for admin users');
                        }
                    } else if (states.current === 'failed' || states.current === 'disconnected') {
                        console.warn('⚠️ WebSocket disconnected. Falling back to polling...');
                        websocketConnected = false;
                        if (!syncInterval) {
                            startPollingSync();
                        }
                    }
                });
                
                connection.bind('error', function(err) {
                    console.error('✗ WebSocket error:', err);
                    if (!syncInterval) {
                        startPollingSync();
                    }
                });
                
                // If not connected after 2 seconds, ensure polling is running
                setTimeout(function() {
                    if (!websocketConnected && !syncInterval) {
                        console.warn('⚠️ WebSocket not connected after 2s. Starting polling fallback...');
                        startPollingSync();
                    }
                }, 2000);
                
                return true;
            } catch (e) {
                console.error('✗ Real-time sync setup failed:', e);
                if (!syncInterval) {
                    startPollingSync();
                }
                return false;
            }
        }
        
        function trySetup(attempt) {
            if (setupSync()) return;
            if (attempt < 25) setTimeout(function() { trySetup(attempt + 1); }, 200);
        }
        
        // Start sync immediately - don't wait for DOMContentLoaded
        console.log('🔄 Initializing sync system...');
        console.log('🔄 Document ready state:', document.readyState);
        
        // Start immediately if document is already loaded
        if (document.readyState === 'complete' || document.readyState === 'interactive') {
            console.log('🔄 Document already loaded, starting sync now...');
            trySetup(0);
        } else {
            // Otherwise wait for DOMContentLoaded
            console.log('🔄 Waiting for DOMContentLoaded...');
            document.addEventListener('DOMContentLoaded', function() {
                console.log('🔄 DOMContentLoaded fired, starting sync...');
                trySetup(0);
            });
        }
        
        // Also try immediately (in case DOMContentLoaded already fired)
        setTimeout(function() {
            if (!syncInterval) {
                console.log('🔄 Fallback: Starting sync after 500ms delay...');
                trySetup(0);
            }
        }, 500);
    })();
    </script>
    
    <!-- Global auto-reload for list pages so data stays in sync without manual refresh -->
    <script>
    (function() {
        'use strict';

        // Expose current route name from Laravel for per-page decisions
        window.CURRENT_ROUTE_NAME = '{{ \Illuminate\Support\Facades\Route::currentRouteName() }}';
        console.log('📍 Current route:', window.CURRENT_ROUTE_NAME);

        function isAdmin() {
            return (typeof CURRENT_USER_ROLE !== 'undefined') && CURRENT_USER_ROLE === 'admin';
        }

        function isStaff() {
            return (typeof CURRENT_USER_ROLE !== 'undefined') && CURRENT_USER_ROLE === 'staff';
        }

        function isProtectedRoute() {
            // Routes where users typically type or edit data and must not lose input
            var protectedRoutes = [
                'pos.index',           // cart & search
                'products.create',
                'products.edit',
                'categories.create',
                'categories.edit',
                'suppliers.create',
                'suppliers.edit'
            ];
            return protectedRoutes.indexOf(window.CURRENT_ROUTE_NAME) !== -1;
        }

        function isUserActivelyTyping() {
            var el = document.activeElement;
            if (!el) return false;
            var tag = (el.tagName || '').toUpperCase();
            if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') {
                return true;
            }
            if (el.isContentEditable) {
                return true;
            }
            return false;
        }

        function scheduleReload() {
            // Never auto-reload when user is on a protected route or actively typing,
            // to avoid losing form input or search text.
            // Exception: sales.index should reload even if user is not actively typing
            const isSalesIndex = window.CURRENT_ROUTE_NAME === 'sales.index';
            const isDashboard = window.CURRENT_ROUTE_NAME === 'dashboard';
            
            if (isProtectedRoute()) {
                console.log('🔄 Global Sync: Change detected but on protected route; skipping auto-reload.');
                return;
            }
            
            // For sales.index and dashboard, only skip if user is actively typing in an input
            if (!isSalesIndex && !isDashboard && isUserActivelyTyping()) {
                console.log('🔄 Global Sync: Change detected but user is editing/typing; skipping auto-reload.');
                return;
            }
            
            // For sales.index, allow reload even if user clicked on a filter (but not typing)
            if (isSalesIndex && isUserActivelyTyping()) {
                console.log('🔄 Global Sync: User typing on sales page; will reload after delay...');
                // Still reload but with longer delay
                setTimeout(function() {
                    if (!isUserActivelyTyping()) {
                        window.location.reload();
                    }
                }, 2000);
                return;
            }

            if (window.__globalAutoReloadScheduled) {
                console.log('🔄 Global Sync: Reload already scheduled, skipping duplicate.');
                return;
            }
            window.__globalAutoReloadScheduled = true;
            console.log('🔄 Global Sync: Data change detected, reloading page in 700ms...');
            setTimeout(function() {
                console.log('🔄 Global Sync: Executing page reload now...');
                window.location.reload();
            }, 700);
        }

        // When new sales are detected (polling fallback)
        window.addEventListener('sync:newSales', function(event) {
            var sales = event.detail || [];
            if (!sales.length) return;

            console.log('📊 Global Sync: New sales detected via polling:', sales.length, 'Current route:', window.CURRENT_ROUTE_NAME);
            
            // Dashboard uses page reload, sales.index uses DOM updates (handled in sales/index.blade.php)
            if (isAdmin() && window.CURRENT_ROUTE_NAME === 'dashboard') {
                console.log('📊 Global Sync: Reloading dashboard for new sales...');
                scheduleReload();
            }
            // sales.index handles updates via DOM manipulation (no reload needed)
        });

        // When product updates are detected (polling fallback)
        window.addEventListener('sync:productUpdates', function(event) {
            var products = event.detail || [];
            if (!products.length) return;

            console.log('📦 Global Sync: Product updates detected via polling:', products.length, 'Current route:', window.CURRENT_ROUTE_NAME);
            
            // Pages handle their own DOM updates (no reload needed)
            // inventory.index, dashboard, and products.index will handle updates via DOM manipulation
        });

        // Also hook into WebSocket events if Echo is available
        if (typeof Echo !== 'undefined') {
            try {
                // New sales
                Echo.channel('sales')
                    .listen('.sale.created', function(e) {
                        console.log('📊 Global Sync: New sale via WebSocket:', e.sale, 'Current route:', window.CURRENT_ROUTE_NAME);
                        // Dashboard uses page reload, sales.index uses DOM updates (handled in sales/index.blade.php)
                        if (isAdmin() && window.CURRENT_ROUTE_NAME === 'dashboard') {
                            console.log('📊 Global Sync: Reloading dashboard for new sale...');
                            scheduleReload();
                        }
                        // sales.index handles updates via DOM manipulation (no reload needed)
                    });

                // Product & inventory updates
                Echo.channel('products')
                    .listen('.product.updated', function(e) {
                        console.log('📦 Global Sync: Product updated via WebSocket:', e.product, 'Current route:', window.CURRENT_ROUTE_NAME);
                        // Pages handle their own DOM updates (no reload needed)
                    });

                Echo.channel('inventory')
                    .listen('.inventory.updated', function(e) {
                        console.log('📦 Global Sync: Inventory updated via WebSocket:', e.product, 'Current route:', window.CURRENT_ROUTE_NAME);
                        // Pages handle their own DOM updates (no reload needed)
                    });
            } catch (e) {
                console.error('Global Sync: Failed to attach Echo listeners', e);
            }
        }
    })();
    </script>
    
    @endauth
    <!-- Disable Service Worker / Offline caching to prevent 419 issues -->
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.getRegistrations().then((registrations) => {
                registrations.forEach((registration) => registration.unregister());
            });
        }
    </script>
    
    @auth
    <!-- Prevent browser back/forward button navigation (ONLY browser <- and -> buttons, not keyboard shortcuts) -->
    <script>
    (function() {
        'use strict';
        
        // Push initial state to history stack
        history.pushState(null, null, location.href);
        history.pushState(null, null, location.href);
        
        // Function to maintain current position in history
        function maintainCurrentPosition() {
            try {
                // Replace current state and push new one to lock position
                history.replaceState(null, null, location.href);
                history.pushState(null, null, location.href);
            } catch(e) {
                console.error('History maintenance error:', e);
            }
        }
        
        // CRITICAL: This is the ONLY way to detect browser back/forward button clicks
        // The popstate event fires when user clicks browser <- or -> buttons
        window.addEventListener('popstate', function(event) {
            // Immediately push state back to prevent navigation
            history.pushState(null, null, location.href);
            console.log('Browser back/forward button navigation prevented');
        }, true); // Use capture phase for immediate interception
        
        // Backup handler
        window.onpopstate = function(event) {
            history.pushState(null, null, location.href);
        };
        
        // Continuously maintain history state to prevent navigation
        // This ensures the history stack always has the current page
        setInterval(function() {
            maintainCurrentPosition();
        }, 100); // Check every 100ms
        
        // Maintain state on page load
        if (document.readyState === 'complete') {
            maintainCurrentPosition();
        } else {
            window.addEventListener('load', function() {
                maintainCurrentPosition();
            });
        }
        
        // Initial state setup
        setTimeout(maintainCurrentPosition, 50);
        
    })();
    </script>
    @endauth
    
    @yield('scripts')
</body>
</html>

