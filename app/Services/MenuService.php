<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

class MenuService
{
    /**
     * Get the sidebar menu for the current user's role
     */
    public function getSidebarMenu(): array
    {
        $role = $this->getUserRole();
        $menus = Config::get('menu.menus', []);

        return $menus[$role] ?? $menus['default'] ?? [];
    }

    /**
     * Get mobile bottom bar items (only items with routes)
     */
    public function getBottomBarMenu(): array
    {
        $customBottomBar = Config::get('menu.bottom_bar');

        if ($customBottomBar !== null) {
            $sidebarMenu = $this->getSidebarMenu();

            return array_values(array_filter($sidebarMenu, function ($item) use ($customBottomBar) {
                return isset($item['route']) && in_array($item['route'], $customBottomBar);
            }));
        }

        // Auto-generate: items with routes only (exclude section headers)
        return array_values(array_filter($this->getSidebarMenu(), function ($item) {
            return isset($item['route']);
        }));
    }

    /**
     * Get settings for the current user's role
     */
    public function getRoleSettings(): array
    {
        $role = $this->getUserRole();
        $settings = Config::get('menu.role_settings', []);

        return $settings[$role] ?? $settings['default'] ?? [
            'show_theme_toggle' => true,
            'theme' => 'auto',
            'console_name' => 'SmartCommute',
        ];
    }

    /**
     * Check if theme toggle should be shown
     */
    public function showThemeToggle(): bool
    {
        return $this->getRoleSettings()['show_theme_toggle'] ?? true;
    }

    /**
     * Get the console name for current role
     */
    public function getConsoleName(): string
    {
        return $this->getRoleSettings()['console_name'] ?? 'SmartCommute';
    }

    /**
     * Get the forced theme (if any)
     */
    public function getForcedTheme(): ?string
    {
        $theme = $this->getRoleSettings()['theme'] ?? 'auto';

        return $theme === 'auto' ? null : $theme;
    }

    /**
     * Get the current user's role
     */
    protected function getUserRole(): ?string
    {
        if (! Auth::check()) {
            return 'default';
        }

        return Auth::user()->roles->first()->name ?? 'default';
    }
}
