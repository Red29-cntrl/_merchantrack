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
            transition: all 0.3s;
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
        }
        .btn-primary:hover {
            background-color: #4C1D3D;
            border-color: #4C1D3D;
        }
        .btn-secondary {
            background-color: #FFBB94;
            border-color: #DC586D;
            color: #4C1D3D;
        }
        .btn-secondary:hover {
            background-color: #FB9590;
            border-color: #DC586D;
        }
        .btn-warning {
            background-color: #852E4E;
            border-color: #852E4E;
            color: #ffffff;
        }
        .btn-warning:hover {
            background-color: #4C1D3D;
            border-color: #4C1D3D;
        }
        .btn-danger {
            background-color: #A33757;
            border-color: #A33757;
            color: #ffffff;
        }
        .btn-danger:hover {
            background-color: #852E4E;
            border-color: #852E4E;
        }
        .btn-info {
            background-color: #852E4E;
            border-color: #852E4E;
            color: #ffffff;
        }
        .btn-info:hover {
            background-color: #4C1D3D;
            border-color: #4C1D3D;
        }
        .btn-success {
            background-color: #A33757;
            border-color: #A33757;
            color: #ffffff;
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
            <div class="container-fluid">
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
    
    <!-- Disable Service Worker / Offline caching to prevent 419 issues -->
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.getRegistrations().then((registrations) => {
                registrations.forEach((registration) => registration.unregister());
            });
        }
    </script>
    
    @yield('scripts')
</body>
</html>

