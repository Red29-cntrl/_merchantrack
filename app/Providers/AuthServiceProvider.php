<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // Access rights per role: admin has full manage rights; staff has view-only where shared + POS only
        Gate::define('manage_users', function ($user) {
            return $user->role === 'admin';
        });
        Gate::define('manage_products', function ($user) {
            return $user->role === 'admin';
        });
        Gate::define('manage_categories', function ($user) {
            return $user->role === 'admin';
        });
        Gate::define('manage_inventory', function ($user) {
            return $user->role === 'admin';
        });
        Gate::define('view_sales', function ($user) {
            return $user->role === 'admin';
        });
        Gate::define('manage_forecasts', function ($user) {
            return $user->role === 'admin';
        });
        Gate::define('manage_suppliers', function ($user) {
            return $user->role === 'admin';
        });
        Gate::define('use_pos', function ($user) {
            return $user->role === 'staff';
        });
    }
}
