<?php

use App\Services\MenuService;

if (! function_exists('menu')) {
    /**
     * Get menu items
     */
    function menu(string $type = 'sidebar'): array
    {
        $service = app(MenuService::class);

        return match ($type) {
            'sidebar' => $service->getSidebarMenu(),
            'bottom_bar' => $service->getBottomBarMenu(),
            default => $service->getSidebarMenu(),
        };
    }
}

if (! function_exists('can_access_route')) {
    /**
     * Check if current user can access a route
     */
    function can_access_route(string $route): bool
    {
        return app(MenuService::class)->canAccessRoute($route);
    }
}
