<?php

namespace App\Providers;

use App\Models\Driver;
use App\Models\User;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        View::composer('admin.dashboard', function ($view) {
            if (! auth()->check() || ! auth()->user()->hasRole('admin')) {
                return;
            }

            $view->with('adminStats', [
                'total_users' => User::count(),
                'total_commuters' => User::role('commuter')->count(),
                'total_drivers' => User::role('driver')->count(),
                'approved_drivers' => Driver::where('is_approved', true)->count(),
                'rejected_drivers' => Driver::where('is_approved', false)->count(),
                'total_applications' => User::role('driver')->count(),
            ]);
        });
    }
}
