<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Yêu cầu route /broadcasting/auth phải đi qua middleware 'web' và 'auth:admin'
        Broadcast::routes(['middleware' => ['web', 'auth:admin']]);

        require base_path('routes/channels.php');
    }
}