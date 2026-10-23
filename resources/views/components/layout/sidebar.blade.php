@props([
    'menuItems' => $sidebarMenu ?? [],
    'bottomItems' => $bottomBarMenu ?? [],
    'themeToggle' => $showThemeToggle ?? true,
    'consoleName' => $consoleName ?? 'SmartCommute',
    'dashboardRoute' => 'dashboard',
    'dashboardIcon' => 'fa-gauge-high',
    'settingsRoute' => 'settings.edit',
    'settingsIcon' => 'fa-gear',
])

@php
    // Colour code for menu areas (the 'color' key in config/menu.php). The same hue means the
    // same area in every role. On the dark navigation panel each hue is a light tint, used for the
    // icon tile; the page you are on switches to the solid brand pill, so "where am I" never
    // depends on telling two tints apart.
    $navHue = [
        'blue' => '129 140 248',
        'purple' => '192 132 252',
        'cyan' => '34 211 238',
        'emerald' => '52 211 153',
        'amber' => '251 191 36',
        'rose' => '251 113 133',
        'orange' => '251 146 60',
        'red' => '248 113 113',
        'teal' => '45 212 191',
        'slate' => '148 163 184',
    ];
    // Same hues, one step darker, for the light panel (kept readable on white).
    $navHueLight = [
        'blue' => '79 70 229',
        'purple' => '147 51 234',
        'cyan' => '14 116 144',
        'emerald' => '4 120 87',
        'amber' => '180 83 9',
        'rose' => '190 18 60',
        'orange' => '194 65 12',
        'red' => '185 28 28',
        'teal' => '15 118 110',
        'slate' => '71 85 105',
    ];
    $hueFor = fn(?string $c): string => '--tone: ' . ($navHue[$c ?? 'blue'] ?? $navHue['blue'])
        . '; --tone-l: ' . ($navHueLight[$c ?? 'blue'] ?? $navHueLight['blue']);

    $navUser = Auth::user();
    $navHandle = $navUser ? explode('@', $navUser->email)[0] : 'Guest';
    $navInitial = strtoupper(substr($navHandle, 0, 1));
    $navRole = $navUser && method_exists($navUser, 'getRoleNames') ? $navUser->getRoleNames()->first() : null;
    $navRoleLabel = $navRole ? ucwords(str_replace(['_', '-'], ' ', $navRole)) : 'Member';
@endphp

<style>
    /* ───────────── Navigation panel ("Aurora" rail) ─────────────
       Follows the theme: a white panel in light mode, deep navy in dark mode. */
    .sc-nav,
    .sc-drawer {
        --n-bg: #ffffff;
        --n-border: var(--sc-line);
        --n-shadow: 0 24px 50px -26px rgba(40, 44, 120, .35);
        --n-text: #4a5273;
        --n-text-hi: var(--sc-ink);
        --n-hover: var(--sc-surface-2);
        --n-section: #636c94;
        --n-card: var(--sc-surface-2);
        --n-card-line: var(--sc-line);
        --n-name: var(--sc-ink);
        --n-role: var(--sc-mute);
        --n-chip: var(--sc-surface-3);
        --n-chip-line: var(--sc-line);
        --n-scroll: rgba(80, 90, 150, .25);
        --n-glow: rgba(99, 102, 241, .14);
        --n-wm-2: var(--sc-brand-text);
        --n-switch: #cdd2e6;
        --n-danger-bg: rgba(239, 68, 68, .1);
        --n-danger: #dc2626;
        --n-inset: rgba(255, 255, 255, .8);
    }

    :root.dark .sc-nav,
    :root.dark .sc-drawer {
        --n-bg: var(--sc-nav-bg);
        --n-border: rgba(255, 255, 255, .07);
        --n-shadow: 0 30px 60px -28px rgba(10, 12, 40, .75);
        --n-text: #b4bbe0;
        --n-text-hi: #ffffff;
        --n-hover: rgba(255, 255, 255, .07);
        --n-section: #6f78a6;
        --n-card: rgba(255, 255, 255, .05);
        --n-card-line: rgba(255, 255, 255, .08);
        --n-name: #ffffff;
        --n-role: #8d95bf;
        --n-chip: rgba(255, 255, 255, .06);
        --n-chip-line: rgba(255, 255, 255, .08);
        --n-scroll: rgba(255, 255, 255, .14);
        --n-glow: rgba(99, 102, 241, .35);
        --n-wm-2: #a5b4fc;
        --n-switch: rgba(255, 255, 255, .16);
        --n-danger-bg: rgba(248, 113, 113, .14);
        --n-danger: #fecaca;
        --n-inset: rgba(255, 255, 255, .06);
    }

    .sc-nav-link {
        --t: var(--tone-l, 79 70 229);
    }

    :root.dark .sc-nav-link {
        --t: var(--tone, 129 140 248);
    }

    .sc-nav {
        position: fixed;
        z-index: 50;
        top: 12px;
        bottom: 12px;
        left: 12px;
        display: none;
        flex-direction: column;
        width: 264px;
        padding: 14px 12px;
        border-radius: 28px;
        color: var(--n-text-hi);
        background: var(--n-bg);
        border: 1px solid var(--n-border);
        box-shadow: var(--n-shadow), inset 0 1px 0 var(--n-inset);
        transition: width .4s cubic-bezier(.16, 1, .3, 1), padding .4s cubic-bezier(.16, 1, .3, 1);
        overflow: hidden;
    }

    @media (min-width: 768px) {
        .sc-nav {
            display: flex;
        }
    }

    .sc-nav.is-collapsed {
        width: 76px;
        padding-left: 10px;
        padding-right: 10px;
    }

    /* soft brand glow behind the logo */
    .sc-nav::before {
        content: '';
        position: absolute;
        inset: -60px -40px auto -40px;
        height: 200px;
        background: radial-gradient(closest-side, var(--n-glow), transparent);
        pointer-events: none;
    }

    .sc-nav>* {
        position: relative;
    }

    .sc-brand {
        display: flex;
        align-items: center;
        gap: 12px;
        min-height: 44px;
        padding: 0 4px;
        margin-bottom: 18px;
        white-space: nowrap;
    }

    .sc-nav.is-collapsed .sc-brand {
        flex-direction: column;
        gap: 10px;
        margin-bottom: 16px;
    }

    .sc-logo {
        display: grid;
        place-items: center;
        flex: none;
        width: 42px;
        height: 42px;
        border-radius: 14px;
        color: #fff;
        font-size: 16px;
        background: var(--sc-grad-warm);
        box-shadow: 0 10px 24px -8px rgba(124, 58, 237, .8), inset 0 1px 0 rgba(255, 255, 255, .3);
    }

    .sc-wordmark {
        font-size: 17px;
        font-weight: 800;
        letter-spacing: -.02em;
        color: var(--n-text-hi);
    }

    .sc-wordmark span {
        color: var(--n-wm-2);
    }

    .sc-collapse {
        margin-left: auto;
        display: grid;
        place-items: center;
        flex: none;
        width: 32px;
        height: 32px;
        border-radius: 10px;
        color: var(--n-text);
        background: var(--n-chip);
        border: 1px solid var(--n-chip-line);
        font-size: 12px;
        cursor: pointer;
    }

    .sc-nav.is-collapsed .sc-collapse {
        margin-left: 0;
    }

    .sc-collapse:hover {
        color: var(--n-text-hi);
        background: var(--n-hover);
    }

    .sc-nav-scroll {
        flex: 1;
        min-height: 0;
        overflow-y: auto;
        overflow-x: hidden;
        margin: 0 -4px;
        padding: 0 4px;
        scrollbar-width: thin;
        scrollbar-color: var(--n-scroll) transparent;
    }

    .sc-nav-scroll::-webkit-scrollbar {
        width: 6px;
    }

    .sc-nav-scroll::-webkit-scrollbar-thumb {
        background: var(--n-scroll);
        border-radius: 99px;
    }

    .sc-section {
        margin: 18px 0 6px;
        padding: 0 12px;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: var(--n-section);
        white-space: nowrap;
    }

    .sc-nav.is-collapsed .sc-section {
        height: 1px;
        margin: 14px 10px;
        padding: 0;
        font-size: 0;
        background: var(--n-card-line);
    }

    .sc-nav-link {
        position: relative;
        display: flex;
        align-items: center;
        gap: 12px;
        min-height: 46px;
        padding: 5px 10px 5px 6px;
        margin-bottom: 2px;
        border-radius: 16px;
        color: var(--n-text);
        font-size: 14.5px;
        font-weight: 600;
        white-space: nowrap;
        transition: background-color .2s ease, color .2s ease, transform .15s ease;
    }

    .sc-nav.is-collapsed .sc-nav-link {
        justify-content: center;
        padding: 5px 0;
    }

    .sc-nav-link:hover {
        color: var(--n-text-hi);
        background: var(--n-hover);
    }

    .sc-nav-ico {
        display: grid;
        place-items: center;
        flex: none;
        width: 36px;
        height: 36px;
        border-radius: 12px;
        font-size: 14px;
        color: rgb(var(--t));
        background: rgb(var(--t) / .12);
        transition: background-color .2s ease, color .2s ease, transform .2s ease;
    }

    .sc-nav-link:hover .sc-nav-ico {
        background: rgb(var(--t) / .22);
        transform: scale(1.06);
    }

    .sc-nav-link.is-active,
    .sc-nav-link.is-active:hover {
        color: #fff;
        background: linear-gradient(135deg, #5b5bf5 0%, #7c3aed 100%);
        box-shadow: 0 14px 28px -12px rgba(99, 102, 241, .85), inset 0 1px 0 rgba(255, 255, 255, .22);
    }

    .sc-nav-link.is-active .sc-nav-ico {
        color: #fff;
        background: rgba(255, 255, 255, .2);
    }

    /* ───────────── Footer: user card + quick actions ───────────── */
    .sc-nav-foot {
        flex: none;
        margin-top: 10px;
        padding-top: 12px;
        border-top: 1px solid var(--n-card-line);
    }

    .sc-user {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 8px;
        margin-bottom: 6px;
        border-radius: 18px;
        background: var(--n-card);
        border: 1px solid var(--n-card-line);
        min-width: 0;
    }

    .sc-nav.is-collapsed .sc-user {
        justify-content: center;
        padding: 6px 0;
        background: transparent;
        border-color: transparent;
    }

    .sc-avatar {
        display: grid;
        place-items: center;
        flex: none;
        width: 38px;
        height: 38px;
        border-radius: 13px;
        font-size: 15px;
        font-weight: 800;
        color: #fff;
        background: linear-gradient(135deg, #06b6d4, #6366f1);
    }

    .sc-user-meta {
        min-width: 0;
        line-height: 1.25;
    }

    .sc-user-name {
        font-size: 14px;
        font-weight: 700;
        color: var(--n-name);
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .sc-user-role {
        font-size: 12px;
        font-weight: 500;
        color: var(--n-role);
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .sc-act {
        display: flex;
        align-items: center;
        gap: 12px;
        width: 100%;
        min-height: 42px;
        padding: 4px 10px 4px 6px;
        border-radius: 14px;
        color: var(--n-text);
        font-size: 14px;
        font-weight: 600;
        text-align: left;
        white-space: nowrap;
        cursor: pointer;
        transition: background-color .2s ease, color .2s ease;
    }

    .sc-nav.is-collapsed .sc-act {
        justify-content: center;
        padding: 4px 0;
    }

    .sc-act:hover {
        color: var(--n-text-hi);
        background: var(--n-hover);
    }

    .sc-act.is-danger:hover {
        color: var(--n-danger);
        background: var(--n-danger-bg);
    }

    .sc-act:disabled {
        opacity: .5;
    }

    .sc-act-ico {
        display: grid;
        place-items: center;
        flex: none;
        width: 34px;
        height: 34px;
        border-radius: 11px;
        font-size: 13px;
        background: var(--n-chip);
    }

    .sc-switch {
        margin-left: auto;
        position: relative;
        flex: none;
        width: 38px;
        height: 22px;
        border-radius: 99px;
        background: var(--n-switch);
        transition: background-color .25s ease;
    }

    .sc-switch::after {
        content: '';
        position: absolute;
        top: 3px;
        left: 3px;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        background: #fff;
        box-shadow: 0 2px 6px rgba(0, 0, 0, .35);
        transition: transform .25s cubic-bezier(.16, 1, .3, 1);
    }

    .sc-switch.is-on {
        background: #6366f1;
    }

    .sc-switch.is-on::after {
        transform: translateX(16px);
    }

    /* ───────────── Mobile: floating dock ───────────── */
    .sc-dock {
        position: fixed;
        z-index: 55;
        left: 12px;
        right: 12px;
        bottom: calc(12px + env(safe-area-inset-bottom));
    }

    @media (min-width: 768px) {
        .sc-dock {
            display: none;
        }
    }

    .sc-dock-inner {
        display: flex;
        align-items: center;
        justify-content: space-around;
        padding: 8px 10px;
        border-radius: 26px;
        background: color-mix(in srgb, var(--sc-surface) 88%, transparent);
        -webkit-backdrop-filter: blur(18px) saturate(1.4);
        backdrop-filter: blur(18px) saturate(1.4);
        border: 1px solid var(--sc-line);
        box-shadow: 0 20px 40px -16px rgba(20, 24, 70, .35);
    }

    .sc-dock .bar-link {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 3px;
        min-width: 64px;
        padding: 6px 10px;
        border-radius: 18px;
        color: var(--sc-mute);
        font-weight: 600;
    }

    .sc-dock .bar-link.is-active {
        color: var(--sc-brand-text);
    }

    .sc-dock .bar-link-label {
        font-size: 11px;
        font-weight: 700;
    }

    .sc-dock .bar-link-icon {
        font-size: 18px;
    }

    .sc-dock .bar-primary {
        margin-top: -26px;
    }

    .sc-dock .bar-primary-icon {
        display: grid;
        place-items: center;
        width: 54px;
        height: 54px;
        border-radius: 20px;
        color: #fff;
        background: var(--sc-grad);
        box-shadow: 0 16px 30px -10px rgba(99, 102, 241, .8), 0 0 0 5px var(--sc-bg);
    }

    .sc-dock .bar-primary.is-inactive .bar-primary-icon {
        color: var(--sc-ink-2);
        background: var(--sc-surface-3);
        box-shadow: 0 10px 20px -10px rgba(20, 24, 70, .3), 0 0 0 5px var(--sc-bg);
    }

    .sc-dock .bar-primary .bar-link-icon {
        font-size: 20px;
    }

    /* Mobile drawer (same dark panel, full height) */
    .sc-drawer {
        background: var(--n-bg);
        color: var(--n-text-hi);
        border-radius: 0 28px 28px 0;
        border-right: 1px solid var(--n-border);
    }

    .sc-drawer .sc-nav-scroll {
        padding-top: 4px;
    }

    .sc-drawer .sc-nav-link {
        min-height: 48px;
    }

    .sc-modal-card {
        background: var(--sc-surface);
        border: 1px solid var(--sc-line);
        border-radius: 28px;
        box-shadow: var(--sc-shadow-3);
    }
</style>

<div x-data x-cloak>

    <!-- ══════════ MOBILE DOCK ══════════ -->
    {{-- `bar-*` hooks are styled by partials/landscape-styles.blade.php so the
         dock collapses into a compact strip when the device is held sideways. --}}
    <div id="mobile-bottom-bar" class="sc-dock">
        <div class="sc-dock-inner bar-inner">

            {{-- LEFT: Settings --}}
            <a href="{{ route($settingsRoute) }}"
                class="bar-link {{ request()->routeIs($settingsRoute) ? 'is-active' : '' }}">
                <i class="bar-link-icon fa-solid {{ $settingsIcon }}"></i>
                <span class="bar-link-label">Settings</span>
            </a>

            {{-- CENTER: Dashboard (elevated primary) --}}
            <a href="{{ route($dashboardRoute) }}"
                class="bar-link bar-primary {{ request()->routeIs($dashboardRoute) ? 'is-active' : 'is-inactive' }}">
                <span class="bar-primary-icon">
                    <i class="bar-link-icon fa-solid {{ $dashboardIcon }}"></i>
                </span>
                <span class="bar-link-label">Dashboard</span>
            </a>

            {{-- RIGHT: More --}}
            <button type="button" @click="openMobileDrawer()" class="bar-link" aria-label="Open menu">
                <i class="bar-link-icon fa-solid fa-grip"></i>
                <span class="bar-link-label">More</span>
            </button>

        </div>
    </div>

    <!-- ══════════ MOBILE DRAWER BACKDROP ══════════ -->
    <div id="mobile-drawer-backdrop"
        class="fixed inset-0 z-[60] bg-black/50 dark:bg-black/70 backdrop-blur-sm opacity-0 pointer-events-none transition-opacity duration-300 md:hidden"
        @click="closeMobileDrawer()"></div>

    <!-- ══════════ MOBILE DRAWER ══════════ -->
    <div id="mobile-drawer"
        class="sc-drawer fixed top-0 left-0 h-full w-80 max-w-[86vw] z-[65] transform -translate-x-full transition-transform duration-300 ease-[cubic-bezier(0.16,1,0.3,1)] flex flex-col md:hidden p-3">

        <div class="sc-brand" style="margin-bottom: 10px;">
            <span class="sc-logo"><i class="fa-solid fa-bus"></i></span>
            <span class="sc-wordmark">Smart<span>Commute</span></span>
            <button type="button" @click="closeMobileDrawer()" class="sc-collapse" aria-label="Close menu">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <nav class="sc-nav-scroll">
            @foreach ($menuItems as $item)
                @if (!isset($item['route']) || (isset($item['section']) && $item['section'] !== null))
                    @if (($item['section'] ?? null) !== null && $item['section'] !== 'hidden')
                        <p class="sc-section">{{ $item['section'] }}</p>
                    @endif
                    @continue
                @endif
                <a href="{{ route($item['route']) }}" style="{{ $hueFor($item['color'] ?? null) }}"
                    class="drawer-nav-link sc-nav-link {{ request()->routeIs($item['route']) ? 'is-active' : '' }}"
                    @if (request()->routeIs($item['route'])) aria-current="page" @endif>
                    <span class="sc-nav-ico"><i class="fa-solid {{ $item['icon'] }}"></i></span>
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>

        <div class="sc-nav-foot">
            <div class="sc-user">
                <span class="sc-avatar">{{ $navInitial }}</span>
                <div class="sc-user-meta">
                    <div class="sc-user-name">{{ $navHandle }}</div>
                    <div class="sc-user-role">{{ $navRoleLabel }}</div>
                </div>
            </div>
            @if ($themeToggle)
                <button type="button" @click="$store.sidebar.toggleTheme($store.sidebar.isDark ? 'light' : 'dark')"
                    :disabled="$store.sidebar.syncing" class="sc-act">
                    <span class="sc-act-ico"><i class="fa-solid" :class="$store.sidebar.isDark ? 'fa-sun' : 'fa-moon'"></i></span>
                    <span>Dark mode</span>
                    <span class="sc-switch" :class="$store.sidebar.isDark ? 'is-on' : ''"></span>
                </button>
            @endif
            <button type="button" @click="$store.sidebar.toggleLogoutModal()" class="sc-act is-danger">
                <span class="sc-act-ico"><i class="fa-solid fa-right-from-bracket"></i></span>
                <span>Log out</span>
            </button>
        </div>
    </div>

    <!-- ══════════ DESKTOP SIDEBAR ══════════ -->
    <aside :class="$store.sidebar.open ? 'is-open' : 'is-collapsed'" class="sc-nav sidebar-transition-none">

        <div class="sc-brand">
            <span class="sc-logo"><i class="fa-solid fa-bus"></i></span>
            <span x-show="$store.sidebar.open" x-transition.opacity.duration.200ms
                class="sc-wordmark">Smart<span>Commute</span></span>
            <button type="button" @click="$store.sidebar.open = !$store.sidebar.open" class="sc-collapse"
                :aria-label="$store.sidebar.open ? 'Collapse navigation' : 'Expand navigation'">
                <i class="fa-solid" :class="$store.sidebar.open ? 'fa-chevron-left' : 'fa-chevron-right'"></i>
            </button>
        </div>

        <nav class="sc-nav-scroll">
            @foreach ($menuItems as $item)
                @if (!isset($item['route']) || (isset($item['section']) && $item['section'] !== null))
                    @if (($item['section'] ?? null) !== null && $item['section'] !== 'hidden')
                        <p class="sc-section">{{ $item['section'] }}</p>
                    @endif
                    @continue
                @endif
                <a href="{{ route($item['route']) }}" style="{{ $hueFor($item['color'] ?? null) }}"
                    title="{{ $item['label'] }}"
                    class="sc-nav-link {{ request()->routeIs($item['route']) ? 'is-active' : '' }}"
                    @if (request()->routeIs($item['route'])) aria-current="page" @endif>
                    <span class="sc-nav-ico"><i class="fa-solid {{ $item['icon'] }}"></i></span>
                    <span x-show="$store.sidebar.open" x-transition.opacity.duration.200ms>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>

        <div class="sc-nav-foot">
            <div class="sc-user" title="{{ $navUser?->email }}">
                <span class="sc-avatar">{{ $navInitial }}</span>
                <div class="sc-user-meta" x-show="$store.sidebar.open" x-transition.opacity.duration.200ms>
                    <div class="sc-user-name">{{ $navHandle }}</div>
                    <div class="sc-user-role">{{ $navRoleLabel }}</div>
                </div>
            </div>
            @if ($themeToggle)
                <button type="button" @click="$store.sidebar.toggleTheme($store.sidebar.isDark ? 'light' : 'dark')"
                    :disabled="$store.sidebar.syncing" class="sc-act"
                    :title="$store.sidebar.isDark ? 'Switch to light mode' : 'Switch to dark mode'">
                    <span class="sc-act-ico"><i class="fa-solid" :class="$store.sidebar.isDark ? 'fa-sun' : 'fa-moon'"></i></span>
                    <span x-show="$store.sidebar.open" x-transition.opacity.duration.200ms>Dark mode</span>
                    <span x-show="$store.sidebar.open" class="sc-switch"
                        :class="$store.sidebar.isDark ? 'is-on' : ''"></span>
                </button>
            @endif
            <button type="button" @click="$store.sidebar.toggleLogoutModal()" class="sc-act is-danger" title="Log out">
                <span class="sc-act-ico"><i class="fa-solid fa-right-from-bracket"></i></span>
                <span x-show="$store.sidebar.open" x-transition.opacity.duration.200ms>Log out</span>
            </button>
        </div>
    </aside>

    <!-- ══════════ LOGOUT MODAL ══════════ -->
    <div x-show="$store.sidebar.showLogoutModal" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0" @click.outside="$store.sidebar.showLogoutModal = false"
        @keydown.escape.window="$store.sidebar.showLogoutModal = false"
        class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 dark:bg-black/70 p-4"
        style="display: none;">
        <div x-show="$store.sidebar.showLogoutModal" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95 translate-y-2" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95" role="dialog" aria-modal="true" aria-labelledby="logout-title"
            class="sc-modal-card w-full max-w-sm p-8 text-center">
            <div class="w-16 h-16 rounded-3xl flex items-center justify-center mx-auto mb-5"
                style="background: rgb(248 113 113 / .14); color: #ef4444;">
                <i class="fa-solid fa-power-off text-xl"></i>
            </div>
            <h3 id="logout-title" class="text-xl font-extrabold mb-1.5" style="color: var(--sc-ink)">End session?</h3>
            <p class="text-sm mb-7" style="color: var(--sc-mute)">You'll be signed out of {{ $consoleName }} on this
                device.</p>
            <div class="flex gap-3">
                <button type="button" @click="$store.sidebar.showLogoutModal = false"
                    class="flex-1 h-12 rounded-2xl text-sm font-bold transition"
                    style="background: var(--sc-surface-3); color: var(--sc-ink);">Stay signed in</button>
                <form action="{{ route('users.logout') }}" method="POST" class="flex-1 m-0">
                    @csrf
                    <button type="submit"
                        class="w-full h-12 rounded-2xl bg-red-600 text-white text-sm font-bold hover:bg-red-700 transition shadow-lg shadow-red-600/25">Log
                        out</button>
                </form>
            </div>
        </div>
    </div>
</div>

@once
    <script>
        function openMobileDrawer() {
            const b = document.getElementById('mobile-drawer-backdrop');
            const d = document.getElementById('mobile-drawer');
            if (!b || !d) return;
            b.classList.remove('opacity-0', 'pointer-events-none');
            d.classList.remove('-translate-x-full');
            document.body.style.overflow = 'hidden';
        }

        function closeMobileDrawer() {
            const b = document.getElementById('mobile-drawer-backdrop');
            const d = document.getElementById('mobile-drawer');
            if (!b || !d) return;
            b.classList.add('opacity-0', 'pointer-events-none');
            d.classList.add('-translate-x-full');
            document.body.style.overflow = '';
        }

        document.querySelectorAll('#mobile-drawer a').forEach(l =>
            l.addEventListener('click', () => setTimeout(closeMobileDrawer, 50))
        );
    </script>
@endonce
