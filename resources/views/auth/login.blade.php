<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - Merchantrack</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #ffffff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-card {
            background: white;
            border: 2px solid #852E4E;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            max-width: 450px;
            width: 100%;
        }
        .login-header {
            text-align: center;
            padding: 30px 20px 20px;
            border-bottom: 2px solid #852E4E;
        }
        .login-header h2 {
            color: #4C1D3D;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .login-header p {
            color: #4C1D3D;
            margin: 0;
        }
        .login-body {
            padding: 20px 40px 40px;
        }
        .form-label {
            color: #4C1D3D;
            font-weight: 500;
        }
        .form-control {
            border: 1px solid #DC586D;
            color: #4C1D3D;
        }
        .form-control:focus {
            border-color: #852E4E;
            box-shadow: 0 0 0 0.2rem rgba(133, 46, 78, 0.25);
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
        .alert-danger {
            background-color: #ffffff;
            border: 1px solid #DC586D;
            color: #4C1D3D;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <h2>Merchantrack</h2>
        </div>
        <div class="login-body">
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

            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror" 
                           id="email" name="email" value="{{ old('email') }}" required autofocus
                           placeholder="Enter your email">
                    @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control @error('password') is-invalid @enderror" 
                           id="password" name="password" required placeholder="Enter your password">
                    @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-4 form-check">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                    <label class="form-check-label" for="remember">Remember me</label>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2">
                    Login
                </button>
            </form>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

