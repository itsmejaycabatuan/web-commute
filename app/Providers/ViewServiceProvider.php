<?php

namespace App\Providers;

use App\Services\MenuService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Share sidebar menu with ALL views
        View::composer('*', function ($view) {
            $menuService = app(MenuService::class);

            $view->with([
                'sidebarMenu' => $menuService->getSidebarMenu(),
                'bottomBarMenu' => $menuService->getBottomBarMenu(),
            ]);
        });
    }
}
