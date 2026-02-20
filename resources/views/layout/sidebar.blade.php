<aside :class="open ? 'w-72' : 'w-20'"
    class="sidebar-transition fixed left-0 top-0 h-screen glass border-r border-white/10 z-50 flex flex-col justify-between p-4">
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
            <a href="{{ route('commuter.dashboard') }}"
                class="flex items-center gap-4 p-3 rounded-2xl bg-blue-600/10 text-blue-400 border border-blue-500/20 group">
                <div class="min-w-[24px] flex justify-center">
                    <i class="fa-solid fa-user-circle text-lg"></i>
                </div>
                <span x-show="open" x-transition.opacity
                    class="text-xs font-bold uppercase tracking-widest">Dashboard</span>
            </a>
        </nav>
        <nav class="space-y-2">
            <a href="{{ route('routes.index') }}"
                class="flex items-center gap-4 p-3 rounded-2xl bg-blue-600/10 text-blue-400 border border-blue-500/20 group">
                <div class="min-w-[24px] flex justify-center">
                    <i class="fa-solid fa-route text-lg"></i>
                </div>
                <span x-show="open" x-transition.opacity
                    class="text-xs font-bold uppercase tracking-widest">Routes</span>
            </a>
        </nav>
        <nav class="space-y-2">
            <a href="{{ route('rates.index') }}"
                class="flex items-center gap-4 p-3 rounded-2xl bg-blue-600/10 text-blue-400 border border-blue-500/20 group">
                <div class="min-w-[24px] flex justify-center">
                    <i class="fa-solid fa-money-bill text-lg"></i>
                </div>
                <span x-show="open" x-transition.opacity class="text-xs font-bold uppercase tracking-widest">Fare
                    rates</span>
            </a>
        </nav>
    </div>

    <form action="{{ route('home') }}" method="GET">
        @csrf
        <button type="submit"
            class="w-full flex items-center gap-4 p-3 rounded-2xl hover:bg-red-500/10 text-gray-500 hover:text-red-500 transition-all group">
            <div class="min-w-[24px] flex justify-center">
                <i class="fa-solid fa-right-from-bracket text-lg"></i>
            </div>
            <span x-show="open" x-transition.opacity class="text-xs font-bold uppercase tracking-widest">Logout</span>
        </button>
    </form>
</aside>