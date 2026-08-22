<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Role-Based Menu Definitions
    |--------------------------------------------------------------------------
    */

    'menus' => [
        // ── ADMIN MENU ──
        'admin' => [
            ['label' => 'Navigation', 'section' => 'Navigation'],
            ['label' => 'Map', 'icon' => 'fa-map-location-dot', 'route' => 'map', 'mobile_label' => 'Map'],
            ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'fa-gauge-high', 'section' => null, 'mobile_label' => 'Home'],
            ['label' => 'Management', 'section' => 'Management'],
            ['label' => 'PUJ Commuters', 'route' => 'admin.commuters.index', 'icon' => 'fa-users', 'section' => null],
            ['label' => 'PUJ Drivers', 'route' => 'admin.drivers.index', 'icon' => 'fa-id-card', 'section' => null],
            ['label' => 'Fare Rates', 'route' => 'fares.index', 'icon' => 'fa-money-bill', 'section' => null],
            ['label' => 'Fare Transactions', 'route' => 'faretransactions', 'icon' => 'fa-receipt', 'section' => null],
            ['label' => 'Top Ups', 'route' => 'admin.topups', 'icon' => 'fa-wallet', 'section' => null],
            ['label' => 'Account', 'section' => 'Account'],
            ['label' => 'Settings', 'route' => 'settings.edit', 'icon' => 'fa-gear', 'section' => null],
        ],

        // ── DRIVER MENU ──
        'driver' => [
            ['label' => 'Navigation', 'section' => 'Navigation'],
            ['label' => 'Map', 'icon' => 'fa-map-location-dot', 'route' => 'map', 'mobile_label' => 'Map'],
            ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'fa-gauge-high', 'section' => null, 'mobile_label' => 'Home'],
            ['label' => 'Timekeeping', 'route' => 'driver.timekeeping', 'icon' => 'fa-clock', 'section' => null, 'mobile_label' => 'Time'],
            ['label' => 'Violations', 'route' => 'driver.violations', 'icon' => 'fa-list', 'section' => null],
            ['label' => 'Account', 'section' => 'Account'],
            ['label' => 'Settings', 'route' => 'settings.edit', 'icon' => 'fa-gear', 'section' => null],

        ],

        // ── DRIVER MANAGER MENU ──
        'driver_manager' => [
            ['label' => 'Navigation', 'section' => 'Navigation', 'icon' => 'fa-map-location-dot', 'route' => 'map', 'mobile_label' => 'Map'],
            ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'fa-gauge-high', 'section' => null, 'mobile_label' => 'Home'],
            ['label' => 'Drivers', 'route' => 'admin.drivers.index', 'icon' => 'fa-id-card', 'section' => null],
            ['label' => 'Account', 'section' => 'Account'],
            ['label' => 'My Profile', 'route' => 'profile', 'icon' => 'fa-circle-user', 'section' => null],
        ],

        // ── MAINTENANCE MANAGER MENU ──
        'maintenance_manager' => [
            ['label' => 'Navigation', 'section' => 'Navigation', 'icon' => 'fa-map-location-dot', 'route' => 'map', 'mobile_label' => 'Map'],
            ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'fa-gauge-high', 'section' => null, 'mobile_label' => 'Home'],
            ['label' => 'Account', 'section' => 'Account'],
            ['label' => 'My Profile', 'route' => 'profile', 'icon' => 'fa-circle-user', 'section' => null],
        ],

        // ── DEFAULT/FALLBACK MENU (Commuter) ──
        'default' => [
            ['label' => 'Navigation', 'section' => 'Navigation', 'icon' => 'fa-map-location-dot', 'route' => 'map', 'mobile_label' => 'Map'],
            ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'fa-gauge-high', 'section' => null, 'mobile_label' => 'Home'],
            ['label' => 'Account', 'section' => 'Account'],
            ['label' => 'My Profile', 'route' => 'profile', 'icon' => 'fa-circle-user', 'section' => null],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Role-Specific Settings
    |--------------------------------------------------------------------------
    */

    'role_settings' => [
        'admin' => [
            'show_theme_toggle' => true,   // Light/dark mode toggle
            'theme' => 'light',             // auto, light, or dark
            'console_name' => 'Admin Console',
        ],
        'driver' => [
            'show_theme_toggle' => true,  // Driver is always dark
            'theme' => 'auto',
            'console_name' => 'Driver Console',
        ],
        'driver_manager' => [
            'show_theme_toggle' => true,
            'theme' => 'auto',
            'console_name' => 'Driver Manager Console',
        ],
        'maintenance_manager' => [
            'show_theme_toggle' => true,
            'theme' => 'auto',
            'console_name' => 'Maintenance Console',
        ],
        'commuter' => [
            'show_theme_toggle' => true,
            'theme' => 'auto',
            'console_name' => 'SmartCommute',
        ],
        'default' => [
            'show_theme_toggle' => true,
            'theme' => 'auto',
            'console_name' => 'SmartCommute',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Bottom Bar Override (per role)
    |--------------------------------------------------------------------------
    | Set to null to auto-generate from menu items with routes
    */

    'bottom_bar' => null,
];
