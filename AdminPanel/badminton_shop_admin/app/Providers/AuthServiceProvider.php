<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate; // Thêm dòng này
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    // ...

    public function boot()
    {
        $this->registerPolicies();

        // 1. Gate kiểm tra quyền Admin tối cao
        Gate::define('admin', function ($employee) {
            return $employee->role === 'admin';
        });

        // 2. Gate kiểm tra quyền Staff (bao gồm Admin)
        Gate::define('staff', function ($employee) {
            return $employee->role === 'staff' || $employee->role === 'admin';
        });
        
        // 3. Gate kiểm tra quyền Marketing (bao gồm Admin)
        Gate::define('marketing', function ($employee) {
            return $employee->role === 'marketing' || $employee->role === 'admin';
        });
    }
}