<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartCommute | Driver Console</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap');

        body {
            background: #050505;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #fff;
        }

        .glass {
            background: rgba(255, 255, 255, 0.02);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .sidebar-transition {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
    </style>
</head>

<body x-data="{ open: true, showLogoutModal: false }">

    <aside :class="open ? 'w-72' : 'w-20'"
        class="sidebar-transition fixed left-0 top-0 h-screen glass border-r border-white/10 z-50 flex flex-col justify-between p-4">
        <div>
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
                    class="flex items-center gap-4 p-3 rounded-2xl text-gray-500 hover:bg-white/5 hover:text-white transition group">
                    <div class="min-w-[24px] flex justify-center">
                        <i class="fa-solid fa-gauge-high text-lg"></i>
                    </div>
                    <span x-show="open" x-transition.opacity
                        class="text-xs font-bold uppercase tracking-widest">Map</span>
                </a>
                <a href="#"
                    class="flex items-center gap-4 p-3 rounded-2xl bg-blue-600/10 text-blue-400 border border-blue-500/20 group">
                    <div class="min-w-[24px] flex justify-center">
                        <i class="fa-solid fa-gauge-high text-lg"></i>
                    </div>
                    <span x-show="open" x-transition.opacity class="text-xs font-bold uppercase tracking-widest">Driver
                        Dashboard</span>
                </a>

                <a href="{{ route('driverprofile') }}"
                    class="flex items-center gap-4 p-3 rounded-2xl text-gray-500 hover:bg-white/5 hover:text-white transition group">
                    <div class="min-w-[24px] flex justify-center">
                        <i class="fa-solid fa-circle-user text-lg"></i>
                    </div>
                    <span x-show="open" x-transition.opacity
                        class="text-xs font-bold uppercase tracking-widest">Profile</span>
                </a>
            </nav>
        </div>

        <button @click="showLogoutModal = true"
            class="w-full flex items-center gap-4 p-3 rounded-2xl hover:bg-red-500/10 text-gray-500 hover:text-red-500 transition-all group">
            <div class="min-w-[24px] flex justify-center">
                <i class="fa-solid fa-right-from-bracket text-lg"></i>
            </div>
            <span x-show="open" x-transition.opacity class="text-xs font-bold uppercase tracking-widest">Exit
                System</span>
        </button>
    </aside>

    <main :class="open ? 'ml-72' : 'ml-20'" class="sidebar-transition p-8 md:p-12 min-h-screen">

        <header class="mb-12 flex justify-between items-end">
            <div>
                <h2 class="text-3xl font-black tracking-tight">Driver <span class="text-blue-500">Dashboard</span></h2>
                <p class="text-gray-500 text-sm mt-1">Route: <span class="text-white font-bold">Minglanilla - IT
                        Park</span></p>
            </div>
        </header>

        <div class="grid grid-cols-1 gap-6 mb-10">
            <div
                class="glass p-10 rounded-[3rem] border-l-4 border-blue-500 shadow-xl shadow-blue-500/5 flex flex-col md:flex-row justify-between items-center">
                <div>
                    <p class="text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] mb-2">Distance Traveled
                        Today</p>
                    <div class="flex items-baseline gap-2">
                        <h3 class="text-7xl font-black tracking-tighter text-white">{{ $total_distance }}</h3>
                        <span class="text-2xl font-bold text-blue-500">KM</span>
                    </div>
                </div>

                <div class="hidden md:block w-px h-16 bg-white/10 mx-8"></div>
            </div>
        </div>

    </main>

    <div x-show="showLogoutModal"
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
        x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" style="display: none;">

        <div @click.away="showLogoutModal = false"
            class="glass p-8 rounded-[2.5rem] max-w-sm w-full border border-white/10 shadow-2xl">
            <div class="text-center">
                <div
                    class="w-16 h-16 bg-red-500/10 text-red-500 rounded-2xl flex items-center justify-center mx-auto mb-4 border border-red-500/20">
                    <i class="fa-solid fa-power-off text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold mb-2">End Session?</h3>
                <p class="text-gray-400 text-sm mb-8">Are you sure you want to exit the Driver Console?</p>

                <div class="flex gap-3">
                    <button @click="showLogoutModal = false"
                        class="flex-1 py-3 rounded-xl bg-white/5 hover:bg-white/10 transition font-bold text-xs uppercase tracking-widest">
                        Cancel
                    </button>
                    <form action="{{ route('users.logout') }}" method="POST" class="flex-1">
                        @csrf
                        <button type="submit"
                            class="w-full py-3 rounded-xl bg-red-600 hover:bg-red-500 transition font-bold text-xs uppercase tracking-widest active:scale-95">
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</body>

</html>