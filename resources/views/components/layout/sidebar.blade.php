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

<link rel="stylesheet" href="{{ asset('css/styles.css') }}">

<div x-data x-cloak>

    <!-- ══════════ MOBILE BOTTOM BAR ══════════ -->
    <div class="fixed bottom-0 left-0 right-0 z-[55] md:hidden">
        <div
            class="bg-white/95 dark:bg-[#0a0a0a]/95 backdrop-blur-xl border-t border-gray-200 dark:border-gray-800 pb-[env(safe-area-inset-bottom)]">
            <div class="flex items-center justify-between px-4 py-1.5">

                {{-- LEFT: Settings --}}
                <a href="{{ route($settingsRoute) }}"
                    class="flex flex-col items-center gap-0.5 py-2 px-4 rounded-2xl transition-all active:scale-90 {{ request()->routeIs($settingsRoute) ? 'text-blue-600 dark:text-blue-400' : 'text-gray-400 dark:text-gray-500' }}">
                    <i class="fa-solid {{ $settingsIcon }} text-[16px]"></i>
                    <span class="text-[7px] font-bold uppercase tracking-wider">Settings</span>
                </a>

                {{-- CENTER: Dashboard (elevated primary) --}}
                <a href="{{ route($dashboardRoute) }}"
                    class="flex flex-col items-center gap-0.5 py-1 px-5 rounded-2xl transition-all active:scale-90 -mt-4 {{ request()->routeIs($dashboardRoute) ? 'text-blue-600 dark:text-blue-400' : 'text-gray-400 dark:text-gray-500' }}">
                    <div
                        class="w-12 h-12 rounded-2xl {{ request()->routeIs($dashboardRoute) ? 'bg-blue-500 shadow-lg shadow-blue-500/30' : 'bg-gray-100 dark:bg-gray-800 shadow-md shadow-black/5 dark:shadow-black/30' }} flex items-center justify-center mb-0.5 transition-all">
                        <i
                            class="fa-solid {{ $dashboardIcon }} text-[17px] {{ request()->routeIs($dashboardRoute) ? 'text-white' : '' }}"></i>
                    </div>
                    <span class="text-[7px] font-bold uppercase tracking-wider">Dashboard</span>
                </a>

                {{-- RIGHT: More --}}
                <button type="button" @click="openMobileDrawer()"
                    class="flex flex-col items-center gap-0.5 py-2 px-4 rounded-2xl text-gray-400 dark:text-gray-500 transition-all active:scale-90">
                    <div class="w-[16px] h-[16px] grid grid-cols-3 grid-rows-3 gap-[2.5px]">
                        <span class="rounded-full bg-current"></span>
                        <span class="rounded-full bg-current"></span>
                        <span class="rounded-full bg-current"></span>
                        <span class="rounded-full bg-current"></span>
                        <span class="rounded-full bg-current"></span>
                        <span class="rounded-full bg-current"></span>
                        <span class="rounded-full bg-current"></span>
                        <span class="rounded-full bg-current"></span>
                        <span class="rounded-full bg-current"></span>
                    </div>
                    <span class="text-[7px] font-bold uppercase tracking-wider">More</span>
                </button>

            </div>
        </div>
    </div>

    <!-- ══════════ MOBILE DRAWER BACKDROP ══════════ -->
    <div id="mobile-drawer-backdrop"
        class="fixed inset-0 z-[60] bg-black/50 dark:bg-black/70 backdrop-blur-sm opacity-0 pointer-events-none transition-opacity duration-300 md:hidden"
        @click="closeMobileDrawer()"></div>

    <!-- ══════════ MOBILE DRAWER ══════════ -->
    <div id="mobile-drawer"
        class="fixed top-0 left-0 h-full w-72 z-[65] bg-white dark:bg-[#0a0a0a] border-r border-gray-200 dark:border-gray-800 transform -translate-x-full transition-transform duration-300 ease-[cubic-bezier(0.16,1,0.3,1)] flex flex-col md:hidden">

        {{-- Drawer Header --}}
        <div class="flex items-center justify-between px-4 py-4 border-b border-gray-100 dark:border-gray-800 shrink-0">
            <div class="flex items-center gap-3 overflow-hidden whitespace-nowrap">
                <div
                    class="w-9 h-9 bg-blue-500 rounded-lg flex items-center justify-center shadow-lg shadow-blue-500/15">
                    <i class="fa-solid fa-bus text-white text-[11px]"></i>
                </div>
                <span class="font-black text-base tracking-tight text-gray-900 dark:text-white">Smart<span
                        class="text-blue-500 dark:text-blue-400">Commute</span></span>
            </div>
            <button type="button" @click="closeMobileDrawer()"
                class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 flex items-center justify-center text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:border-gray-300 dark:hover:border-gray-600 transition active:scale-90">
                <i class="fa-solid fa-xmark text-[10px]"></i>
            </button>
        </div>

        {{-- Scrollable Menu --}}
        <nav class="flex-1 overflow-y-auto overscroll-contain p-3 space-y-1">
            @foreach ($menuItems as $item)
                @if (!isset($item['route']))
                    @if (($item['section'] ?? null) !== null && $item['section'] !== 'hidden')
                        <div class="my-2 mx-3 border-t border-gray-100 dark:border-gray-800"></div>
                        <p
                            class="text-[8px] font-bold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-600 px-3 pt-1 pb-1.5">
                            {{ $item['section'] }}
                        </p>
                    @endif
                    @continue
                @endif
                @if (isset($item['section']) && $item['section'] !== null)
                    <div class="my-2 mx-3 border-t border-gray-100 dark:border-gray-800"></div>
                    <p
                        class="text-[8px] font-bold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-600 px-3 pt-1 pb-1.5">
                        {{ $item['section'] }}
                    </p>
                    @continue
                @endif
                <a href="{{ route($item['route']) }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all border {{ request()->routeIs($item['route']) ? 'bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-200 dark:border-blue-500/20' : 'text-gray-600 dark:text-gray-400 border-transparent hover:bg-gray-100 dark:hover:bg-gray-800/50 hover:text-gray-900 dark:hover:text-gray-200' }}">
                    <div
                        class="w-7 h-7 rounded-lg {{ request()->routeIs($item['route']) ? 'bg-blue-100 dark:bg-blue-500/20' : 'bg-gray-100 dark:bg-gray-800' }} flex items-center justify-center shrink-0">
                        <i class="fa-solid {{ $item['icon'] }} text-[10px]"></i>
                    </div>
                    <span class="text-[10px] font-bold uppercase tracking-[0.12em]">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>

        {{-- Drawer Footer --}}
        <div class="p-3 border-t border-gray-100 dark:border-gray-800 space-y-1 shrink-0">
            @if ($themeToggle)
                <button type="button" @click="$store.sidebar.toggleTheme($store.sidebar.isDark ? 'light' : 'dark')"
                    :disabled="$store.sidebar.syncing"
                    class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800/50 transition-all text-left disabled:opacity-50">
                    <div
                        class="w-7 h-7 rounded-lg bg-gray-100 dark:bg-gray-800 flex items-center justify-center shrink-0">
                        <i x-show="!$store.sidebar.isDark" class="fa-solid fa-moon text-[10px] text-gray-600"></i>
                        <i x-show="$store.sidebar.isDark" class="fa-solid fa-sun text-[10px] text-amber-400"></i>
                    </div>
                    <span class="text-[10px] font-bold uppercase tracking-[0.12em]">
                        <span x-show="!$store.sidebar.isDark">Dark Mode</span>
                        <span x-show="$store.sidebar.isDark">Light Mode</span>
                    </span>
                </button>
            @endif
            <button type="button" @click="$store.sidebar.toggleLogoutModal()"
                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-600 dark:text-gray-400 hover:bg-red-50 dark:hover:bg-red-500/10 hover:text-red-600 dark:hover:text-red-400 transition-all text-left">
                <div class="w-7 h-7 rounded-lg bg-gray-100 dark:bg-gray-800 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-right-from-bracket text-[10px]"></i>
                </div>
                <span class="text-[10px] font-bold uppercase tracking-[0.12em]">Logout</span>
            </button>
        </div>
    </div>

    <!-- ══════════ DESKTOP SIDEBAR ══════════ -->
    <aside :class="$store.sidebar.open ? 'w-[270px] p-3' : 'w-[76px] p-2'"
        class="sidebar-transition fixed left-0 top-0 h-full bg-white dark:bg-[#0a0a0a] border-r border-gray-200 dark:border-gray-800 z-50 flex flex-col hidden md:flex">

        <div class="shrink-0 px-0.5">
            <div :class="$store.sidebar.open ? 'flex-row justify-between px-1 mb-6' : 'flex-col items-center gap-2.5 mb-6 pt-0.5'"
                class="flex items-center overflow-hidden whitespace-nowrap">
                <div class="flex items-center overflow-hidden whitespace-nowrap"
                    :class="$store.sidebar.open ? 'gap-2.5' : ''">
                    <div
                        class="w-9 h-9 bg-blue-500 rounded-lg flex items-center justify-center shadow-lg shadow-blue-500/15 shrink-0">
                        <i class="fa-solid fa-bus text-white text-[11px]"></i>
                    </div>
                    <span x-show="$store.sidebar.open" x-transition.opacity.duration.200ms
                        class="font-black text-sm tracking-tight whitespace-nowrap text-gray-900 dark:text-white">Smart<span
                            class="text-blue-500 dark:text-blue-400">Commute</span></span>
                </div>
                <button @click="$store.sidebar.open = !$store.sidebar.open"
                    class="w-7 h-7 rounded-lg bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 flex items-center justify-center text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300 dark:hover:border-gray-600 transition active:scale-90 shrink-0">
                    <i class="fa-solid text-[8px] transition-transform duration-300"
                        :class="$store.sidebar.open ? 'fa-chevron-left' : 'fa-chevron-right'"></i>
                </button>
            </div>
        </div>

        <nav class="flex-1 overflow-y-auto min-h-0 px-0.5 space-y-1">
            @foreach ($menuItems as $item)
                @if (!isset($item['route']))
                    @if (($item['section'] ?? null) !== null && $item['section'] !== 'hidden')
                        <div x-show="$store.sidebar.open" x-transition.opacity.duration.150ms
                            class="my-3 mx-3 border-t border-gray-100 dark:border-gray-800"></div>
                        <p x-show="$store.sidebar.open" x-transition.opacity.duration.150ms
                            class="text-[8px] font-bold uppercase tracking-[0.2em] text-gray-300 dark:text-gray-600 px-3 mb-2">
                            {{ $item['section'] }}</p>
                    @endif
                    @continue
                @endif
                @if (isset($item['section']) && $item['section'] !== null)
                    <div x-show="$store.sidebar.open" x-transition.opacity.duration.150ms
                        class="my-3 mx-3 border-t border-gray-100 dark:border-gray-800"></div>
                    <p x-show="$store.sidebar.open" x-transition.opacity.duration.150ms
                        class="text-[8px] font-bold uppercase tracking-[0.2em] text-gray-300 dark:text-gray-600 px-3 mb-2">
                        {{ $item['section'] }}</p>
                    @continue
                @endif
                <a href="{{ route($item['route']) }}"
                    :class="$store.sidebar.open ? 'gap-3 px-3' : 'justify-center px-0'"
                    class="flex items-center py-2.5 rounded-xl transition-all border group {{ request()->routeIs($item['route']) ? 'bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-200 dark:border-blue-500/20' : 'text-gray-600 dark:text-gray-400 border-transparent hover:bg-gray-100 dark:hover:bg-gray-800/50 hover:text-gray-900 dark:hover:text-gray-200 hover:border-gray-200 dark:hover:border-gray-700' }}">
                    <div
                        class="w-8 h-8 rounded-lg {{ request()->routeIs($item['route']) ? 'bg-blue-100 dark:bg-blue-500/20' : 'bg-gray-100 dark:bg-gray-800 group-hover:bg-gray-200 dark:group-hover:bg-gray-700' }} flex items-center justify-center shrink-0 transition">
                        <i class="fa-solid {{ $item['icon'] }} text-[11px]"></i>
                    </div>
                    <span x-show="$store.sidebar.open" x-transition.opacity.duration.200ms
                        class="text-[10px] font-bold uppercase tracking-[0.12em] whitespace-nowrap">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>

        <div class="shrink-0 mt-2 px-0.5 space-y-1">
            <div x-show="$store.sidebar.open" x-transition.opacity.duration.150ms
                class="mx-3 border-t border-gray-100 dark:border-gray-800 mb-3"></div>
            @if ($themeToggle)
                <button type="button" @click="$store.sidebar.toggleTheme($store.sidebar.isDark ? 'light' : 'dark')"
                    :disabled="$store.sidebar.syncing"
                    :class="$store.sidebar.open ? 'gap-3 px-3' : 'justify-center px-0'"
                    class="w-full flex items-center py-2.5 rounded-xl text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800/50 transition-all text-left group disabled:opacity-50">
                    <div
                        class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-800 group-hover:bg-gray-200 dark:group-hover:bg-gray-700 flex items-center justify-center shrink-0 transition relative">
                        <i x-show="!$store.sidebar.isDark" class="fa-solid fa-moon text-[11px] text-gray-600"></i>
                        <i x-show="$store.sidebar.isDark" class="fa-solid fa-sun text-[11px] text-amber-400"></i>
                    </div>
                    <span x-show="$store.sidebar.open" x-transition.opacity.duration.200ms
                        class="text-[10px] font-bold uppercase tracking-[0.12em] whitespace-nowrap">
                        <span x-show="!$store.sidebar.isDark">Dark Mode</span>
                        <span x-show="$store.sidebar.isDark">Light Mode</span>
                    </span>
                </button>
            @endif
            <button type="button" @click="$store.sidebar.toggleLogoutModal()"
                :class="$store.sidebar.open ? 'gap-3 px-3' : 'justify-center px-0'"
                class="w-full flex items-center py-2.5 rounded-xl text-gray-500 dark:text-gray-400 hover:bg-red-50 dark:hover:bg-red-500/10 hover:text-red-600 dark:hover:text-red-400 transition-all text-left group">
                <div
                    class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-800 group-hover:bg-red-100 dark:group-hover:bg-red-500/20 flex items-center justify-center shrink-0 transition">
                    <i class="fa-solid fa-right-from-bracket text-[11px]"></i>
                </div>
                <span x-show="$store.sidebar.open" x-transition.opacity.duration.200ms
                    class="text-[10px] font-bold uppercase tracking-[0.12em] whitespace-nowrap">Logout</span>
            </button>
        </div>
    </aside>

    <!-- ══════════ LOGOUT MODAL ══════════ -->
    <div x-show="$store.sidebar.showLogoutModal" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0" @click.outside="$store.sidebar.showLogoutModal = false"
        class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 dark:bg-black/80 p-4"
        style="display: none;">
        <div x-show="$store.sidebar.showLogoutModal" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="bg-white dark:bg-[#111] w-full max-w-sm rounded-[1.5rem] border border-gray-200 dark:border-gray-700 shadow-2xl shadow-black/10 dark:shadow-black/60 p-8 text-center">
            <div
                class="w-14 h-14 bg-red-50 dark:bg-red-500/10 rounded-2xl flex items-center justify-center mx-auto mb-5 border border-red-200 dark:border-red-500/20">
                <i class="fa-solid fa-power-off text-red-500 dark:text-red-400 text-lg"></i>
            </div>
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1.5">End Session?</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-7">Are you sure you want to log out of
                {{ $consoleName }}?</p>
            <div class="flex gap-2.5">
                <button type="button" @click="$store.sidebar.showLogoutModal = false"
                    class="flex-1 py-3 rounded-xl bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white text-[10px] font-bold uppercase tracking-widest hover:bg-gray-200 dark:hover:bg-gray-700 transition active:scale-[0.98]">Cancel</button>
                <form action="{{ route('users.logout') }}" method="POST" class="flex-1 m-0">
                    @csrf
                    <button type="submit"
                        class="w-full py-3 rounded-xl bg-red-600 text-white text-[10px] font-bold uppercase tracking-widest hover:bg-red-700 transition active:scale-[0.98] shadow-lg shadow-red-600/10">Logout</button>
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
