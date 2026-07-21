<link rel="stylesheet" href="{{ asset('css/styles.css') }} ">

<aside :class="open ? 'w-72' : 'w-20'"
    class="overflow-y-auto sidebar-transition fixed left-0 top-0 h-screen glass border-r border-white/10 z-50 flex flex-col justify-between p-4">
    <div class="grid gap-2">
        <button @click="open = !open" class="w-full flex justify-end p-2 mb-8 hover:text-blue-400 transition">
            <i class="fa-solid" :class="open ? 'fa-chevron-left' : 'fa-chevron-right'"></i>
        </button>

        <div class="flex items-center gap-3 px-2 mb-10 overflow-hidden whitespace-nowrap">
            <div
                class="min-w-[40px] h-10 bg-blue-600 rounded-xl flex items-center justify-center shadow-lg shadow-blue-600/20">
                <i class="fa-solid fa-bus text-white"></i>
            </div>
            <span x-show="open" x-transition.opacity class="font-bold text-lg tracking-tighter">Smart<span
                    class="text-blue-500">Commute</span></span>
        </div>

        <nav class="space-y-2">
            <nav class="space-y-2">
                <a href="{{ route('commuter.dashboard') }}"
                    class="flex items-center gap-4 p-3 rounded-2xl transition-all group border {{ request()->routeIs('commuter.dashboard') ? 'bg-blue-600/10 text-blue-400 border-blue-500/20' : 'text-gray-400 border-transparent hover:bg-white/5' }}">
                    <div class="min-w-[24px] flex justify-center">
                        <i class="fa-solid fa-user-circle text-lg"></i>
                    </div>
                    <span x-show="open" x-transition.opacity
                        class="text-xs font-bold uppercase tracking-widest">Dashboard</span>
                </a>
            </nav>
        </nav>

        <nav class="space-y-2">
            <nav class="space-y-2">
                <a href="{{ route('maintenance-manager.preventive-maintenance') }}"
                    class="flex items-center gap-4 p-3 rounded-2xl transition-all group border {{ request()->routeIs('maintenance-manager.preventive-maintenance') ? 'bg-blue-600/10 text-blue-400 border-blue-500/20' : 'text-gray-400 border-transparent hover:bg-white/5' }}">
                    <div class="min-w-[24px] flex justify-center">
                        <i class="fa-solid fa-calendar text-lg"></i>
                    </div>
                    <span x-show="open" x-transition.opacity
                        class="text-xs font-bold uppercase tracking-widest">Preventive Maintenance Schedule</span>
                </a>
            </nav>
        </nav>

        <nav class="space-y-2">
            <nav class="space-y-2">
                <a href="{{ route('maintenance-manager.maintenance-calendar') }}"
                    class="flex items-center gap-4 p-3 rounded-2xl transition-all group border {{ request()->routeIs('maintenance-manager.maintenance-calendar') ? 'bg-blue-600/10 text-blue-400 border-blue-500/20' : 'text-gray-400 border-transparent hover:bg-white/5' }}">
                    <div class="min-w-[24px] flex justify-center">
                        <i class="fa-solid fa-calendar-check text-lg"></i>
                    </div>
                    <span x-show="open" x-transition.opacity
                        class="text-xs font-bold uppercase tracking-widest">Preventive Maintenance Calendar</span>
                </a>
            </nav>
        </nav>

        <nav class="space-y-2">
            <nav class="space-y-2">
                <a href="{{ route('maintenance-manager.maintenance-tasks') }}"
                    class="flex items-center gap-4 p-3 rounded-2xl transition-all group border {{ request()->routeIs('maintenance-manager.maintenance-tasks') ? 'bg-blue-600/10 text-blue-400 border-blue-500/20' : 'text-gray-400 border-transparent hover:bg-white/5' }}">
                    <div class="min-w-[24px] flex justify-center">
                        <i class="fa-solid fa-list-check text-lg"></i>
                    </div>
                    <span x-show="open" x-transition.opacity
                        class="text-xs font-bold uppercase tracking-widest">Maintenance Tasks</span>
                </a>
            </nav>
        </nav>

        <!--
        <nav class="space-y-2">
            <nav class="space-y-2">
                <a href="{{ route('maintenance-manager.vehicle-maintenance-log') }}"
                    class="flex items-center gap-4 p-3 rounded-2xl transition-all group border {{ request()->routeIs('maintenance-manager.vehicle-maintenance-log') ? 'bg-blue-600/10 text-blue-400 border-blue-500/20' : 'text-gray-400 border-transparent hover:bg-white/5' }}">
                    <div class="min-w-[24px] flex justify-center">
                        <i class="fa-solid fa-car text-lg"></i>
                    </div>
                    <span x-show="open" x-transition.opacity
                        class="text-xs font-bold uppercase tracking-widest">Vehicle Maintenance Log</span>
                </a>
            </nav>
        </nav>
        -->



        <nav class="space-y-2">
            <nav class="space-y-2">
                <a href="{{ route('maintenance-manager.fleet-inventory') }}"
                    class="flex items-center gap-4 p-3 rounded-2xl transition-all group border {{ request()->routeIs('maintenance-manager.fleet-inventory') ? 'bg-blue-600/10 text-blue-400 border-blue-500/20' : 'text-gray-400 border-transparent hover:bg-white/5' }}">
                    <div class="min-w-[24px] flex justify-center">
                        <i class="fa-solid fa-box text-lg"></i>
                    </div>
                    <span x-show="open" x-transition.opacity
                        class="text-xs font-bold uppercase tracking-widest">Fleet Inventory</span>
                </a>
            </nav>
        </nav>

        <nav class="space-y-2">
            <nav class="space-y-2">
                <a href="{{ route('vehicles.index') }}"
                    class="flex items-center gap-4 p-3 rounded-2xl transition-all group border {{ request()->routeIs('vehicles.index') ? 'bg-blue-600/10 text-blue-400 border-blue-500/20' : 'text-gray-400 border-transparent hover:bg-white/5' }}">
                    <div class="min-w-[24px] flex justify-center">
                        <i class="fa-solid fa-bus text-lg"></i>
                    </div>
                    <span x-show="open" x-transition.opacity
                        class="text-xs font-bold uppercase tracking-widest">Vehicles</span>
                </a>
            </nav>
        </nav>

        <nav class="space-y-2">
            <a href="{{ route('profile.admin') }}"
                class="flex items-center gap-4 p-3 rounded-2xl transition-all group border {{ request()->routeIs('profile.admin') ? 'bg-blue-600/10 text-blue-400 border-blue-500/20' : 'text-gray-400 border-transparent hover:bg-white/5' }}">
                <div class="min-w-[24px] flex justify-center"><i class="fa-solid fa-circle-user text-lg"></i></div>
                <span x-show="open" x-transition.opacity class="text-xs font-bold uppercase tracking-widest">My
                    Profile</span>
            </a>
        </nav>
    </div>

    <button type="button" onclick="toggleAdminLogoutModal()"
        class="w-full flex items-center gap-4 p-3 rounded-2xl hover:bg-red-500/10 text-gray-500 hover:text-red-500 transition-all group text-left">
        <div class="min-w-[24px] flex justify-center">
            <i class="fa-solid fa-right-from-bracket text-lg"></i>
        </div>
        <span x-show="open" x-transition.opacity class="text-xs font-bold uppercase tracking-widest">Logout</span>
    </button>
</aside>

{{-- Logout confirmation (shared admin sidebar) --}}
<div id="admin-logout-modal"
    class="fixed inset-0 z-[100] flex items-center justify-center bg-black/70 backdrop-blur-sm opacity-0 pointer-events-none transition-all duration-300 p-4">
    <div id="admin-logout-modal-content"
        class="glass w-full max-w-sm rounded-2xl border border-white/10 p-8 text-center shadow-2xl transform scale-95 transition-transform duration-300">
        <div class="w-16 h-16 bg-red-500/20 rounded-full flex items-center justify-center mx-auto mb-6">
            <i class="fa-solid fa-right-from-bracket text-red-500 text-xl"></i>
        </div>
        <h3 class="text-xl font-bold text-white mb-2">Sign out?</h3>
        <p class="text-sm text-gray-400 mb-8">Are you sure you want to log out of SmartCommute?</p>
        <div class="flex gap-3">
            <button type="button" onclick="toggleAdminLogoutModal()"
                class="flex-1 px-6 py-3 rounded-xl bg-white/10 border border-white/20 text-white text-xs font-bold uppercase tracking-widest hover:bg-white/20 transition">
                Cancel
            </button>
            <form action="{{ route('users.logout') }}" method="POST" class="flex-1">
                @csrf
                <button type="submit"
                    class="w-full px-6 py-3 rounded-xl bg-red-600 text-white text-xs font-bold uppercase tracking-widest hover:bg-red-500 shadow-lg shadow-red-600/20 transition">
                    Logout
                </button>
            </form>
        </div>
    </div>
</div>

<script>
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
