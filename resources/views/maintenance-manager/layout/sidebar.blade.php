<link rel="stylesheet" href="{{ asset('css/styles.css') }}">

<!-- ══════════ MOBILE BOTTOM BAR ══════════ -->
<div class="fixed bottom-0 left-0 right-0 z-[55] md:hidden">
    <div class="bg-[#0a0a0a]/95 backdrop-blur-xl border-t border-[#1a1a1a] px-2 pb-[env(safe-area-inset-bottom)]">
        <div class="flex items-center justify-around py-1.5">
            <a href="{{ route('dashboard') }}"
                class="flex flex-col items-center gap-0.5 py-1.5 px-3 rounded-xl transition-all active:scale-90 {{ request()->routeIs('dashboard') ? 'text-blue-400' : 'text-[#444]' }}">
                <i class="fa-solid fa-gauge-high text-[15px]"></i>
                <span class="text-[7px] font-bold uppercase tracking-wider">Home</span>
            </a>
            <a href="{{ route('maintenance-manager.preventive-maintenance') }}"
                class="flex flex-col items-center gap-0.5 py-1.5 px-3 rounded-xl transition-all active:scale-90 {{ request()->routeIs('maintenance-manager.preventive-maintenance') ? 'text-blue-400' : 'text-[#444]' }}">
                <i class="fa-solid fa-calendar text-[15px]"></i>
                <span class="text-[7px] font-bold uppercase tracking-wider">Schedule</span>
            </a>
            <a href="{{ route('maintenance-manager.maintenance-tasks') }}"
                class="flex flex-col items-center gap-0.5 py-1.5 px-3 rounded-xl transition-all active:scale-90 {{ request()->routeIs('maintenance-manager.maintenance-tasks') ? 'text-blue-400' : 'text-[#444]' }}">
                <i class="fa-solid fa-list-check text-[15px]"></i>
                <span class="text-[7px] font-bold uppercase tracking-wider">Tasks</span>
            </a>
            <button type="button" onclick="openMobileDrawer()"
                class="flex flex-col items-center gap-0.5 py-1.5 px-3 rounded-xl text-[#444] transition-all active:scale-90">
                <i class="fa-solid fa-bars text-[15px]"></i>
                <span class="text-[7px] font-bold uppercase tracking-wider">More</span>
            </button>
        </div>
    </div>
</div>

<!-- ══════════ MOBILE DRAWER BACKDROP ══════════ -->
<div id="mobile-drawer-backdrop"
    class="fixed inset-0 z-[60] bg-black/70 backdrop-blur-sm opacity-0 pointer-events-none transition-opacity duration-300 md:hidden"
    onclick="closeMobileDrawer()">
</div>

<!-- ══════════ MOBILE DRAWER ══════════ -->
<div id="mobile-drawer"
    class="fixed top-0 left-0 h-full w-72 z-[65] bg-[#0a0a0a] border-r border-[#1a1a1a] transform -translate-x-full transition-transform duration-300 ease-[cubic-bezier(0.16,1,0.3,1)] flex flex-col md:hidden">

    <div class="flex items-center justify-between px-4 py-4 border-b border-[#151515]">
        <div class="flex items-center gap-3 overflow-hidden whitespace-nowrap">
            <div class="w-9 h-9 bg-blue-500 rounded-lg flex items-center justify-center shadow-lg shadow-blue-500/15">
                <i class="fa-solid fa-bus text-white text-[11px]"></i>
            </div>
            <span class="font-black text-base tracking-tight">Smart<span class="text-blue-400">Commute</span></span>
        </div>
        <button type="button" onclick="closeMobileDrawer()"
            class="w-8 h-8 rounded-lg bg-[#111] border border-[#1e1e1e] flex items-center justify-center text-[#555] hover:text-white hover:border-[#333] transition active:scale-90">
            <i class="fa-solid fa-xmark text-[10px]"></i>
        </button>
    </div>

    <nav class="flex-1 p-3 space-y-1 overflow-y-auto">
        <p class="text-[8px] font-bold uppercase tracking-[0.2em] text-[#333] px-3 pt-2 pb-1.5">Navigation</p>

        <a href="{{ route('dashboard') }}"
            class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all border {{ request()->routeIs('dashboard') ? 'bg-blue-500/[0.06] text-blue-400 border-blue-500/15' : 'text-[#555] border-transparent hover:bg-[#111] hover:text-[#888]' }}">
            <div class="w-7 h-7 rounded-lg {{ request()->routeIs('dashboard') ? 'bg-blue-500/10' : 'bg-[#111]' }} flex items-center justify-center shrink-0">
                <i class="fa-solid fa-gauge-high text-[10px]"></i>
            </div>
            <span class="text-[10px] font-bold uppercase tracking-[0.12em]">Dashboard</span>
        </a>

        <div class="my-2 mx-3 border-t border-[#151515]"></div>
        <p class="text-[8px] font-bold uppercase tracking-[0.2em] text-[#333] px-3 pt-1 pb-1.5">Maintenance</p>

        <a href="{{ route('maintenance-manager.preventive-maintenance') }}"
            class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all border {{ request()->routeIs('maintenance-manager.preventive-maintenance') ? 'bg-blue-500/[0.06] text-blue-400 border-blue-500/15' : 'text-[#555] border-transparent hover:bg-[#111] hover:text-[#888]' }}">
            <div class="w-7 h-7 rounded-lg {{ request()->routeIs('maintenance-manager.preventive-maintenance') ? 'bg-blue-500/10' : 'bg-[#111]' }} flex items-center justify-center shrink-0">
                <i class="fa-solid fa-calendar text-[10px]"></i>
            </div>
            <span class="text-[10px] font-bold uppercase tracking-[0.12em]">PM Schedule</span>
        </a>

        <a href="{{ route('maintenance-manager.maintenance-calendar') }}"
            class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all border {{ request()->routeIs('maintenance-manager.maintenance-calendar') ? 'bg-blue-500/[0.06] text-blue-400 border-blue-500/15' : 'text-[#555] border-transparent hover:bg-[#111] hover:text-[#888]' }}">
            <div class="w-7 h-7 rounded-lg {{ request()->routeIs('maintenance-manager.maintenance-calendar') ? 'bg-blue-500/10' : 'bg-[#111]' }} flex items-center justify-center shrink-0">
                <i class="fa-solid fa-calendar-check text-[10px]"></i>
            </div>
            <span class="text-[10px] font-bold uppercase tracking-[0.12em]">PM Calendar</span>
        </a>

        <a href="{{ route('maintenance-manager.maintenance-tasks') }}"
            class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all border {{ request()->routeIs('maintenance-manager.maintenance-tasks') ? 'bg-blue-500/[0.06] text-blue-400 border-blue-500/15' : 'text-[#555] border-transparent hover:bg-[#111] hover:text-[#888]' }}">
            <div class="w-7 h-7 rounded-lg {{ request()->routeIs('maintenance-manager.maintenance-tasks') ? 'bg-blue-500/10' : 'bg-[#111]' }} flex items-center justify-center shrink-0">
                <i class="fa-solid fa-list-check text-[10px]"></i>
            </div>
            <span class="text-[10px] font-bold uppercase tracking-[0.12em]">Maintenance Tasks</span>
        </a>

        <div class="my-2 mx-3 border-t border-[#151515]"></div>
        <p class="text-[8px] font-bold uppercase tracking-[0.2em] text-[#333] px-3 pt-1 pb-1.5">Fleet</p>

        <a href="{{ route('maintenance-manager.fleet-inventory') }}"
            class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all border {{ request()->routeIs('maintenance-manager.fleet-inventory') ? 'bg-blue-500/[0.06] text-blue-400 border-blue-500/15' : 'text-[#555] border-transparent hover:bg-[#111] hover:text-[#888]' }}">
            <div class="w-7 h-7 rounded-lg {{ request()->routeIs('maintenance-manager.fleet-inventory') ? 'bg-blue-500/10' : 'bg-[#111]' }} flex items-center justify-center shrink-0">
                <i class="fa-solid fa-box text-[10px]"></i>
            </div>
            <span class="text-[10px] font-bold uppercase tracking-[0.12em]">Fleet Inventory</span>
        </a>

        <a href="{{ route('vehicles.index') }}"
            class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all border {{ request()->routeIs('vehicles.index') ? 'bg-blue-500/[0.06] text-blue-400 border-blue-500/15' : 'text-[#555] border-transparent hover:bg-[#111] hover:text-[#888]' }}">
            <div class="w-7 h-7 rounded-lg {{ request()->routeIs('vehicles.index') ? 'bg-blue-500/10' : 'bg-[#111]' }} flex items-center justify-center shrink-0">
                <i class="fa-solid fa-bus text-[10px]"></i>
            </div>
            <span class="text-[10px] font-bold uppercase tracking-[0.12em]">Vehicles</span>
        </a>

        <div class="my-2 mx-3 border-t border-[#151515]"></div>
        <p class="text-[8px] font-bold uppercase tracking-[0.2em] text-[#333] px-3 pt-1 pb-1.5">Account</p>

        <a href="{{ route('profile') }}"
            class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all border {{ request()->routeIs('profile') ? 'bg-blue-500/[0.06] text-blue-400 border-blue-500/15' : 'text-[#555] border-transparent hover:bg-[#111] hover:text-[#888]' }}">
            <div class="w-7 h-7 rounded-lg {{ request()->routeIs('profile') ? 'bg-blue-500/10' : 'bg-[#111]' }} flex items-center justify-center shrink-0">
                <i class="fa-solid fa-circle-user text-[10px]"></i>
            </div>
            <span class="text-[10px] font-bold uppercase tracking-[0.12em]">My Profile</span>
        </a>
    </nav>

    <div class="p-3 border-t border-[#151515]">
        <button type="button" onclick="toggleAdminLogoutModal()"
            class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-[#555] hover:bg-red-500/[0.06] hover:text-red-400 transition-all text-left">
            <div class="w-7 h-7 rounded-lg bg-[#111] flex items-center justify-center shrink-0">
                <i class="fa-solid fa-right-from-bracket text-[10px]"></i>
            </div>
            <span class="text-[10px] font-bold uppercase tracking-[0.12em]">Logout</span>
        </button>
    </div>
</div>

<!-- ══════════ DESKTOP SIDEBAR ══════════ -->
<aside :class="open ? 'w-[270px] p-3' : 'w-[76px] p-2'"
    class="overflow-y-auto overflow-x-hidden sidebar-transition fixed left-0 top-0 h-screen bg-[#0a0a0a] border-r border-[#1a1a1a] z-50 flex-col justify-between hidden md:flex">

    <div>
        <!-- Logo + Toggle -->
        <div :class="open ? 'flex-row justify-between px-1 mb-6' : 'flex-col items-center gap-2.5 mb-6 pt-0.5'"
             class="flex items-center overflow-hidden whitespace-nowrap">
            <div class="flex items-center overflow-hidden whitespace-nowrap"
                 :class="open ? 'gap-2.5' : ''">
                <div class="w-9 h-9 bg-blue-500 rounded-lg flex items-center justify-center shadow-lg shadow-blue-500/15 shrink-0">
                    <i class="fa-solid fa-bus text-white text-[11px]"></i>
                </div>
                <span x-show="open" x-transition.opacity.duration.200ms
                      class="font-black text-sm tracking-tight whitespace-nowrap">Smart<span class="text-blue-400">Commute</span></span>
            </div>
            <button @click="open = !open"
                class="w-7 h-7 rounded-lg bg-[#111] border border-[#1e1e1e] flex items-center justify-center text-[#444] hover:text-[#888] hover:border-[#333] transition active:scale-90 shrink-0">
                <i class="fa-solid text-[8px] transition-transform duration-300" :class="open ? 'fa-chevron-left' : 'fa-chevron-right'"></i>
            </button>
        </div>

        <!-- Navigation Links -->
        <nav class="space-y-1">
            <p x-show="open" x-transition.opacity.duration.150ms class="text-[8px] font-bold uppercase tracking-[0.2em] text-[#2a2a2a] px-3 mb-2">Navigation</p>

            <a href="{{ route('dashboard') }}"
                :class="open ? 'gap-3 px-3' : 'justify-center px-0'"
                class="flex items-center py-2.5 rounded-xl transition-all border group {{ request()->routeIs('dashboard') ? 'bg-blue-500/[0.06] text-blue-400 border-blue-500/15' : 'text-[#555] border-transparent hover:bg-[#111] hover:text-[#888] hover:border-[#1a1a1a]' }}">
                <div class="w-8 h-8 rounded-lg {{ request()->routeIs('dashboard') ? 'bg-blue-500/10' : 'bg-[#111] group-hover:bg-[#161616]' }} flex items-center justify-center shrink-0 transition">
                    <i class="fa-solid fa-gauge-high text-[11px]"></i>
                </div>
                <span x-show="open" x-transition.opacity.duration.200ms class="text-[10px] font-bold uppercase tracking-[0.12em] whitespace-nowrap">Dashboard</span>
            </a>

            <!-- Divider -->
            <div x-show="open" x-transition.opacity.duration.150ms class="my-3 mx-3 border-t border-[#151515]"></div>
            <p x-show="open" x-transition.opacity.duration.150ms class="text-[8px] font-bold uppercase tracking-[0.2em] text-[#2a2a2a] px-3 mb-2">Maintenance</p>

            <a href="{{ route('maintenance-manager.preventive-maintenance') }}"
                :class="open ? 'gap-3 px-3' : 'justify-center px-0'"
                class="flex items-center py-2.5 rounded-xl transition-all border group {{ request()->routeIs('maintenance-manager.preventive-maintenance') ? 'bg-blue-500/[0.06] text-blue-400 border-blue-500/15' : 'text-[#555] border-transparent hover:bg-[#111] hover:text-[#888] hover:border-[#1a1a1a]' }}">
                <div class="w-8 h-8 rounded-lg {{ request()->routeIs('maintenance-manager.preventive-maintenance') ? 'bg-blue-500/10' : 'bg-[#111] group-hover:bg-[#161616]' }} flex items-center justify-center shrink-0 transition">
                    <i class="fa-solid fa-calendar text-[11px]"></i>
                </div>
                <span x-show="open" x-transition.opacity.duration.200ms class="text-[10px] font-bold uppercase tracking-[0.12em] whitespace-nowrap">PM Schedule</span>
            </a>

            <a href="{{ route('maintenance-manager.maintenance-calendar') }}"
                :class="open ? 'gap-3 px-3' : 'justify-center px-0'"
                class="flex items-center py-2.5 rounded-xl transition-all border group {{ request()->routeIs('maintenance-manager.maintenance-calendar') ? 'bg-blue-500/[0.06] text-blue-400 border-blue-500/15' : 'text-[#555] border-transparent hover:bg-[#111] hover:text-[#888] hover:border-[#1a1a1a]' }}">
                <div class="w-8 h-8 rounded-lg {{ request()->routeIs('maintenance-manager.maintenance-calendar') ? 'bg-blue-500/10' : 'bg-[#111] group-hover:bg-[#161616]' }} flex items-center justify-center shrink-0 transition">
                    <i class="fa-solid fa-calendar-check text-[11px]"></i>
                </div>
                <span x-show="open" x-transition.opacity.duration.200ms class="text-[10px] font-bold uppercase tracking-[0.12em] whitespace-nowrap">PM Calendar</span>
            </a>

            <a href="{{ route('maintenance-manager.maintenance-tasks') }}"
                :class="open ? 'gap-3 px-3' : 'justify-center px-0'"
                class="flex items-center py-2.5 rounded-xl transition-all border group {{ request()->routeIs('maintenance-manager.maintenance-tasks') ? 'bg-blue-500/[0.06] text-blue-400 border-blue-500/15' : 'text-[#555] border-transparent hover:bg-[#111] hover:text-[#888] hover:border-[#1a1a1a]' }}">
                <div class="w-8 h-8 rounded-lg {{ request()->routeIs('maintenance-manager.maintenance-tasks') ? 'bg-blue-500/10' : 'bg-[#111] group-hover:bg-[#161616]' }} flex items-center justify-center shrink-0 transition">
                    <i class="fa-solid fa-list-check text-[11px]"></i>
                </div>
                <span x-show="open" x-transition.opacity.duration.200ms class="text-[10px] font-bold uppercase tracking-[0.12em] whitespace-nowrap">Maintenance Tasks</span>
            </a>

            <!-- Divider -->
            <div x-show="open" x-transition.opacity.duration.150ms class="my-3 mx-3 border-t border-[#151515]"></div>
            <p x-show="open" x-transition.opacity.duration.150ms class="text-[8px] font-bold uppercase tracking-[0.2em] text-[#2a2a2a] px-3 mb-2">Fleet</p>

            <a href="{{ route('maintenance-manager.fleet-inventory') }}"
                :class="open ? 'gap-3 px-3' : 'justify-center px-0'"
                class="flex items-center py-2.5 rounded-xl transition-all border group {{ request()->routeIs('maintenance-manager.fleet-inventory') ? 'bg-blue-500/[0.06] text-blue-400 border-blue-500/15' : 'text-[#555] border-transparent hover:bg-[#111] hover:text-[#888] hover:border-[#1a1a1a]' }}">
                <div class="w-8 h-8 rounded-lg {{ request()->routeIs('maintenance-manager.fleet-inventory') ? 'bg-blue-500/10' : 'bg-[#111] group-hover:bg-[#161616]' }} flex items-center justify-center shrink-0 transition">
                    <i class="fa-solid fa-box text-[11px]"></i>
                </div>
                <span x-show="open" x-transition.opacity.duration.200ms class="text-[10px] font-bold uppercase tracking-[0.12em] whitespace-nowrap">Fleet Inventory</span>
            </a>

            <a href="{{ route('vehicles.index') }}"
                :class="open ? 'gap-3 px-3' : 'justify-center px-0'"
                class="flex items-center py-2.5 rounded-xl transition-all border group {{ request()->routeIs('vehicles.index') ? 'bg-blue-500/[0.06] text-blue-400 border-blue-500/15' : 'text-[#555] border-transparent hover:bg-[#111] hover:text-[#888] hover:border-[#1a1a1a]' }}">
                <div class="w-8 h-8 rounded-lg {{ request()->routeIs('vehicles.index') ? 'bg-blue-500/10' : 'bg-[#111] group-hover:bg-[#161616]' }} flex items-center justify-center shrink-0 transition">
                    <i class="fa-solid fa-bus text-[11px]"></i>
                </div>
                <span x-show="open" x-transition.opacity.duration.200ms class="text-[10px] font-bold uppercase tracking-[0.12em] whitespace-nowrap">Vehicles</span>
            </a>

            <!-- Divider -->
            <div x-show="open" x-transition.opacity.duration.150ms class="my-3 mx-3 border-t border-[#151515]"></div>
            <p x-show="open" x-transition.opacity.duration.150ms class="text-[8px] font-bold uppercase tracking-[0.2em] text-[#2a2a2a] px-3 mb-2">Account</p>

            <a href="{{ route('profile') }}"
                :class="open ? 'gap-3 px-3' : 'justify-center px-0'"
                class="flex items-center py-2.5 rounded-xl transition-all border group {{ request()->routeIs('profile') ? 'bg-blue-500/[0.06] text-blue-400 border-blue-500/15' : 'text-[#555] border-transparent hover:bg-[#111] hover:text-[#888] hover:border-[#1a1a1a]' }}">
                <div class="w-8 h-8 rounded-lg {{ request()->routeIs('profile') ? 'bg-blue-500/10' : 'bg-[#111] group-hover:bg-[#161616]' }} flex items-center justify-center shrink-0 transition">
                    <i class="fa-solid fa-circle-user text-[11px]"></i>
                </div>
                <span x-show="open" x-transition.opacity.duration.200ms class="text-[10px] font-bold uppercase tracking-[0.12em] whitespace-nowrap">My Profile</span>
            </a>
        </nav>
    </div>

    <!-- Sidebar Footer -->
    <div class="mt-4">
        <div x-show="open" x-transition.opacity.duration.150ms class="mx-3 border-t border-[#151515] mb-3"></div>
        <button type="button" onclick="toggleAdminLogoutModal()"
            :class="open ? 'gap-3 px-3' : 'justify-center px-0'"
            class="w-full flex items-center py-2.5 rounded-xl text-[#444] hover:bg-red-500/[0.06] hover:text-red-400 transition-all text-left group">
            <div class="w-8 h-8 rounded-lg bg-[#111] group-hover:bg-red-500/10 flex items-center justify-center shrink-0 transition">
                <i class="fa-solid fa-right-from-bracket text-[11px]"></i>
            </div>
            <span x-show="open" x-transition.opacity.duration.200ms class="text-[10px] font-bold uppercase tracking-[0.12em] whitespace-nowrap">Logout</span>
        </button>
    </div>
</aside>

<!-- ══════════ LOGOUT MODAL ══════════ -->
<div id="admin-logout-modal"
    class="fixed inset-0 z-[100] flex items-center justify-center bg-black/80 opacity-0 pointer-events-none transition-all duration-300 p-4">
    <div id="admin-logout-modal-content"
        class="bg-[#111] w-full max-w-sm rounded-[1.5rem] border border-[#1e1e1e] shadow-2xl shadow-black/60 p-8 text-center transform scale-95 transition-transform duration-300">
        <div class="w-14 h-14 bg-red-500/10 rounded-2xl flex items-center justify-center mx-auto mb-5 border border-red-500/20">
            <i class="fa-solid fa-power-off text-red-400 text-lg"></i>
        </div>
        <h3 class="text-lg font-bold text-white mb-1.5">End Session?</h3>
        <p class="text-xs text-[#666] mb-7">Are you sure you want to log out of SmartCommute?</p>
<div class="flex gap-2.5">
    <div class="flex-1">
        <button type="button" onclick="toggleAdminLogoutModal()"
            class="w-full py-3 rounded-xl bg-[#1a1a1a] border border-[#2a2a2a] text-white text-[10px] font-bold uppercase tracking-widest hover:bg-[#222] transition active:scale-[0.98]">
            Cancel
        </button>
    </div>
    <form action="{{ route('users.logout') }}" method="POST" class="flex-1 m-0">
        @csrf
        <button type="submit"
            class="w-full py-3 rounded-xl bg-red-600 text-white text-[10px] font-bold uppercase tracking-widest hover:bg-red-700 transition active:scale-[0.98] shadow-lg shadow-red-600/10">
            Logout
        </button>
    </form>
</div>
    </div>
</div>

<script>
    function openMobileDrawer() {
        const backdrop = document.getElementById('mobile-drawer-backdrop');
        const drawer = document.getElementById('mobile-drawer');
        if (!backdrop || !drawer) return;
        backdrop.classList.remove('opacity-0', 'pointer-events-none');
        drawer.classList.remove('-translate-x-full');
        document.body.style.overflow = 'hidden';
    }

    function closeMobileDrawer() {
        const backdrop = document.getElementById('mobile-drawer-backdrop');
        const drawer = document.getElementById('mobile-drawer');
        if (!backdrop || !drawer) return;
        backdrop.classList.add('opacity-0', 'pointer-events-none');
        drawer.classList.add('-translate-x-full');
        document.body.style.overflow = '';
    }

    document.querySelectorAll('#mobile-drawer a').forEach(link => {
        link.addEventListener('click', () => setTimeout(closeMobileDrawer, 50));
    });

    function toggleAdminLogoutModal() {
        const modal = document.getElementById('admin-logout-modal');
        const content = document.getElementById('admin-logout-modal-content');
        if (!modal || !content) return;
        if (modal.classList.contains('opacity-0')) {
            modal.classList.remove('opacity-0', 'pointer-events-none');
            content.classList.remove('scale-95');
            content.classList.add('scale-100');
        } else {
            modal.classList.add('opacity-0', 'pointer-events-none');
            content.classList.remove('scale-100');
            content.classList.add('scale-95');
        }
    }

    document.addEventListener('click', function (e) {
        const modal = document.getElementById('admin-logout-modal');
        if (!modal || modal.classList.contains('opacity-0')) return;
        if (e.target === modal) toggleAdminLogoutModal();
    });
</script>
