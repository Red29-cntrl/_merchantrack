@extends('layouts.app')

@section('title', 'Create New User')

@section('styles')
<style>
    .form-label {
        font-weight: 600;
        color: #4C1D3D;
        margin-bottom: 8px;
    }
    .required-field::after {
        content: " *";
        color: #852E4E;
        font-weight: bold;
    }
    .form-hint {
        font-size: 0.875rem;
        color: #666666;
        margin-top: 4px;
    }
    .form-control:focus, .form-select:focus {
        border-color: #852E4E;
        box-shadow: 0 0 0 0.2rem rgba(133, 46, 78, 0.25);
    }
    .form-section {
        border-bottom: 1px solid #e0e0e0;
        padding-bottom: 20px;
        margin-bottom: 20px;
    }
    .form-section:last-child {
        border-bottom: none;
        margin-bottom: 0;
    }
    .form-section-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #4C1D3D;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 2px solid #852E4E;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-user-plus me-2"></i>Create New User</h2>
        <a href="{{ route('users.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Users
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-user-cog me-2"></i>User Information</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('users.store') }}" method="POST" id="createUserForm">
                @csrf
                
                <!-- Personal Information Section -->
                <div class="form-section">
                    <div class="form-section-title">Personal Information</div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label required-field">Full Name</label>
                            <input type="text" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name') }}" 
                                   required
                                   placeholder="Enter full name">
                            <div class="form-hint">Enter the user's complete full name</div>
                            @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label required-field">Email Address</label>
                            <input type="email" 
                                   class="form-control @error('email') is-invalid @enderror" 
                                   id="email" 
                                   name="email" 
                                   value="{{ old('email') }}" 
                                   required
                                   placeholder="user@example.com">
                            <div class="form-hint">Enter a valid email address (must be unique)</div>
                            @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Account Security Section -->
                <div class="form-section">
                    <div class="form-section-title">Account Security</div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label required-field">Password</label>
                            <input type="password" 
                                   class="form-control @error('password') is-invalid @enderror" 
                                   id="password" 
                                   name="password" 
                                   required
                                   minlength="8"
                                   placeholder="Enter password">
                            <div class="form-hint">
                                <i class="fas fa-info-circle me-1"></i>
                                Password must be at least 8 characters long
                            </div>
                            @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="password_confirmation" class="form-label required-field">Confirm Password</label>
                            <input type="password" 
                                   class="form-control @error('password_confirmation') is-invalid @enderror" 
                                   id="password_confirmation" 
                                   name="password_confirmation" 
                                   required
                                   minlength="8"
                                   placeholder="Re-enter password">
                            <div class="form-hint">Re-enter the password to confirm</div>
                            @error('password_confirmation')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Role Assignment Section -->
                <div class="form-section">
                    <div class="form-section-title">Role Assignment</div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="role" class="form-label required-field">User Role</label>
                            <select class="form-select @error('role') is-invalid @enderror" 
                                    id="role" 
                                    name="role" 
                                    required>
                                <option value="">-- Select Role --</option>
                                <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>
                                    Admin - Full system access and user management
                                </option>
                                <option value="staff" {{ old('role') === 'staff' ? 'selected' : '' }}>
                                    Staff - Standard user access
                                </option>
                            </select>
                            <div class="form-hint">
                                <i class="fas fa-info-circle me-1"></i>
                                <strong>Admin:</strong> Can manage users and all system features<br>
                                <strong>Staff:</strong> Can access POS, products, and sales features
                            </div>
                            @error('role')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="d-flex gap-2 justify-content-end mt-4 pt-3 border-top">
                    <a href="{{ route('users.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times me-2"></i>Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Create User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Password confirmation validation
    document.getElementById('password_confirmation').addEventListener('input', function() {
        const password = document.getElementById('password').value;
        const confirmPassword = this.value;
        
        if (password !== confirmPassword) {
            this.setCustomValidity('Passwords do not match');
        } else {
            this.setCustomValidity('');
        }
    });

    // Password strength indicator
    document.getElementById('password').addEventListener('input', function() {
        const password = this.value;
        const hint = this.nextElementSibling;
        
        if (password.length > 0 && password.length < 8) {
            hint.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i>Password must be at least 8 characters long';
        } else if (password.length >= 8) {
            hint.innerHTML = '<i class="fas fa-check-circle me-1"></i>Password length is valid';
        }
    });
</script>
@endsection
