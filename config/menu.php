<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Role-Based Menu Definitions
    |--------------------------------------------------------------------------
    |
    | 'color' is the sidebar colour code. It marks the area of the system an
    | item belongs to, so the same colour means the same thing in every role:
    |
    |   blue    = getting around (Map, Dashboard)
    |   purple  = reports
    |   cyan    = records of people and vehicles
    |   emerald = money (fares, transactions, top-ups)
    |   amber   = time and attendance
    |   rose    = violations
    |   orange  = maintenance work
    |   slate   = account
    |
    | The palette itself lives in components/layout/sidebar.blade.php.
    */

    'menus' => [
        // ── ADMIN MENU ──
        'admin' => [
            ['label' => 'Navigation', 'section' => 'Navigation'],
            ['label' => 'Map', 'icon' => 'fa-map-location-dot', 'color' => 'blue', 'route' => 'map', 'mobile_label' => 'Map'],
            ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'fa-gauge-high', 'color' => 'blue', 'section' => null, 'mobile_label' => 'Home'],
            ['label' => 'Management', 'section' => 'Management'],
            ['label' => 'Reports & Analytics', 'route' => 'reports.generate', 'icon' => 'fa-newspaper', 'color' => 'purple', 'section' => null],
            ['label' => 'PUJ Commuters', 'route' => 'commuters.index', 'icon' => 'fa-users', 'color' => 'cyan', 'section' => null],
            ['label' => 'PUJ Drivers', 'route' => 'drivers.index', 'icon' => 'fa-id-card', 'color' => 'cyan', 'section' => null],
            ['label' => 'Vehicles', 'route' => 'vehicles.index', 'icon' => 'fa-bus', 'color' => 'cyan', 'section' => null],
            ['label' => 'Staff', 'route' => 'staff.index', 'icon' => 'fa-user-gear', 'color' => 'cyan', 'section' => null],
            ['label' => 'Activity Log', 'route' => 'activity-log.index', 'icon' => 'fa-clipboard-list', 'color' => 'purple', 'section' => null],
            ['label' => 'Fare Rates', 'route' => 'fares.index', 'icon' => 'fa-money-bill', 'color' => 'emerald', 'section' => null],
            ['label' => 'Fare Transactions', 'route' => 'faretransactions', 'icon' => 'fa-receipt', 'color' => 'emerald', 'section' => null],
            ['label' => 'Top Ups', 'route' => 'admin.topups', 'icon' => 'fa-wallet', 'color' => 'emerald', 'section' => null],
            ['label' => 'Account', 'section' => 'Account'],
            ['label' => 'Settings', 'route' => 'settings.edit', 'icon' => 'fa-gear', 'color' => 'slate', 'section' => null],
        ],

        // ── DRIVER MENU ──
        'driver' => [
            ['label' => 'Navigation', 'section' => 'Navigation'],
            ['label' => 'Map', 'icon' => 'fa-map-location-dot', 'color' => 'blue', 'route' => 'map', 'mobile_label' => 'Map'],
            ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'fa-gauge-high', 'color' => 'blue', 'section' => null, 'mobile_label' => 'Home'],
            ['label' => 'Timekeeping', 'route' => 'driver.timekeeping', 'icon' => 'fa-clock', 'color' => 'amber', 'section' => null, 'mobile_label' => 'Time'],
            ['label' => 'Violations', 'route' => 'driver.violations', 'icon' => 'fa-list', 'color' => 'rose', 'section' => null],
            ['label' => 'Account', 'section' => 'Account'],
            ['label' => 'Settings', 'route' => 'settings.edit', 'icon' => 'fa-gear', 'color' => 'slate', 'section' => null],

        ],

        // ── DRIVER MANAGER MENU ──
        'driver_manager' => [
            ['label' => 'Navigation', 'section' => 'Navigation', 'icon' => 'fa-map-location-dot', 'route' => 'map', 'mobile_label' => 'Map'],
            ['label' => 'Map', 'icon' => 'fa-map-location-dot', 'color' => 'blue', 'route' => 'map', 'mobile_label' => 'Map'],
            ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'fa-gauge-high', 'color' => 'blue', 'section' => null, 'mobile_label' => 'Home'],
            ['label' => 'Drivers', 'route' => 'drivers.index', 'icon' => 'fa-id-card', 'color' => 'cyan', 'section' => null],
            ['label' => 'Time Keeping', 'route' => 'driver-manager.time-keeping', 'icon' => 'fa-clock', 'color' => 'amber', 'section' => null],
            ['label' => 'Violation Codes', 'route' => 'driver-manager.violation-codes', 'icon' => 'fa-shield', 'color' => 'rose', 'section' => null],
            ['label' => 'Violation Log', 'route' => 'driver-manager.violations-log', 'icon' => 'fa-list', 'color' => 'rose', 'section' => null],
            ['label' => 'Account', 'section' => 'Account'],
            ['label' => 'Settings', 'route' => 'settings.edit', 'icon' => 'fa-gear', 'color' => 'slate', 'section' => null],
        ],

        // ── MAINTENANCE MANAGER MENU ──
        'maintenance_manager' => [
            ['label' => 'Navigation', 'section' => 'Navigation', 'icon' => 'fa-map-location-dot', 'route' => 'map', 'mobile_label' => 'Map'],
            ['label' => 'Map', 'icon' => 'fa-map-location-dot', 'color' => 'blue', 'route' => 'map', 'mobile_label' => 'Map'],
            ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'fa-gauge-high', 'color' => 'blue', 'section' => null, 'mobile_label' => 'Home'],
            ['label' => 'Preventive Maintenance', 'route' => 'maintenance-manager.preventive-maintenance', 'icon' => 'fa-calendar-days', 'color' => 'orange', 'section' => null, 'mobile_label' => 'Home'],
            ['label' => 'Maintenance Logs', 'route' => 'maintenance-manager.maintenance-logs', 'icon' => 'fa-clock-rotate-left', 'color' => 'orange', 'section' => null, 'mobile_label' => 'Home'],
            ['label' => 'Maintenance Tasks', 'route' => 'maintenance-manager.maintenance-tasks', 'icon' => 'fa-list-check', 'color' => 'orange', 'section' => null, 'mobile_label' => 'Home'],
            ['label' => 'Vehicles', 'route' => 'vehicles.index', 'icon' => 'fa-bus', 'color' => 'cyan', 'section' => null, 'mobile_label' => 'Home'],
            ['label' => 'Account', 'section' => 'Account'],
            ['label' => 'Settings', 'route' => 'settings.edit', 'icon' => 'fa-gear', 'color' => 'slate', 'section' => null],
        ],

        // ── DEFAULT/FALLBACK MENU (Commuter) ──
        'default' => [
            ['label' => 'Navigation', 'section' => 'Navigation', 'icon' => 'fa-map-location-dot', 'route' => 'map', 'mobile_label' => 'Map'],
            ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'fa-gauge-high', 'color' => 'blue', 'section' => null, 'mobile_label' => 'Home'],
            ['label' => 'Account', 'section' => 'Account'],
            ['label' => 'Settings', 'route' => 'settings.edit', 'icon' => 'fa-gear', 'color' => 'slate', 'section' => null],
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
            'theme' => 'auto',             // auto, light, or dark
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
