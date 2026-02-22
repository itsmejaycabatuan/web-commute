<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartCommute | Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap');
        body { background: #050505; font-family: 'Plus Jakarta Sans', sans-serif; color: #fff; }
        .glass { background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.05); }
        .sidebar-transition { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
    </style>
</head>

<body x-data="{ open: true, showLogoutModal: false }">

    <aside
        :class="open ? 'w-72' : 'w-20'"
        class="sidebar-transition fixed left-0 top-0 h-screen glass border-r border-white/10 z-50 flex flex-col justify-between p-4"
    >
        <div>
            <button @click="open = !open" class="w-full flex justify-end p-2 mb-8 hover:text-blue-400 transition">
                <i class="fa-solid" :class="open ? 'fa-chevron-left' : 'fa-chevron-right'"></i>
            </button>

            <div class="flex items-center gap-3 px-2 mb-10 overflow-hidden whitespace-nowrap">
                <div class="min-w-[40px] h-10 bg-blue-600 rounded-xl flex items-center justify-center shadow-lg shadow-blue-600/20">
                    <i class="fa-solid fa-bus text-white"></i>
                </div>
                <span x-show="open" x-transition.opacity class="font-bold text-lg tracking-tighter">Smart<span class="text-blue-500">Commute</span></span>
            </div>

            <nav class="space-y-2">
                <a href="#" class="flex items-center gap-4 p-3 rounded-2xl bg-blue-600/10 text-blue-400 border border-blue-500/20 group">
                    <div class="min-w-[24px] flex justify-center">
                        <i class="fa-solid fa-chart-pie text-lg"></i>
                    </div>
                    <span x-show="open" x-transition.opacity class="text-xs font-bold uppercase tracking-widest">Dashboard</span>
                </a>

                <a href="#" class="flex items-center gap-4 p-3 rounded-2xl text-gray-500 hover:bg-white/5 hover:text-white transition group">
                    <div class="min-w-[24px] flex justify-center">
                        <i class="fa-solid fa-bus text-lg"></i>
                    </div>
                    <span x-show="open" x-transition.opacity class="text-xs font-bold uppercase tracking-widest">Manage PUJ</span>
                </a>

                <a href="{{ route('adminprofile') }}" class="flex items-center gap-4 p-3 rounded-2xl text-gray-500 hover:bg-white/5 hover:text-white transition group">
                    <div class="min-w-[24px] flex justify-center"><i class="fa-solid fa-circle-user text-lg"></i></div>
                    <span x-show="open" x-transition.opacity class="text-xs font-bold uppercase tracking-widest">My Profile</span>
                </a>
            </nav>
        </div>

        <button @click="showLogoutModal = true" class="w-full flex items-center gap-4 p-3 rounded-2xl hover:bg-red-500/10 text-gray-500 hover:text-red-500 transition-all group">
            <div class="min-w-[24px] flex justify-center">
                <i class="fa-solid fa-right-from-bracket text-lg"></i>
            </div>
            <span x-show="open" x-transition.opacity class="text-xs font-bold uppercase tracking-widest">Exit System</span>
        </button>
    </aside>

    <main :class="open ? 'ml-72' : 'ml-20'" class="sidebar-transition p-8 md:p-12 min-h-screen">
        <header class="mb-12">
            <h2 class="text-3xl font-black tracking-tight">System <span class="text-blue-500">Overview</span></h2>
            <div class="h-1 w-12 bg-blue-600 mt-2 rounded-full"></div>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-10">
            <div class="glass p-8 rounded-[2.5rem] border-l-4 border-blue-500 shadow-xl shadow-blue-500/5">
                <div class="flex justify-between items-start mb-4">
                    <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Active Commuters</p>
                    <i class="fa-solid fa-users text-blue-500/30"></i>
                </div>
                <h3 class="text-5xl font-black tracking-tighter">1,204</h3>
                <p class="text-[10px] text-blue-400 font-bold mt-2 uppercase">Online Now</p>
            </div>

            <div class="glass p-8 rounded-[2.5rem] border-l-4 border-purple-500">
                <div class="flex justify-between items-start mb-4">
                    <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Total PUJ</p>
                    <i class="fa-solid fa-van-shuttle text-purple-500/30"></i>
                </div>
                <h3 class="text-5xl font-black tracking-tighter">86</h3>
                <p class="text-[10px] text-gray-500 font-bold mt-2 uppercase">Registered PUJ</p>
            </div>
        </div>

        <div class="glass p-8 rounded-[3rem]">
            <div class="flex justify-between items-center mb-10">
                <h4 class="text-xs font-black uppercase tracking-[0.2em] text-blue-400">System Logs</h4>
                <span class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Recent Activity</span>
            </div>

            <div class="space-y-6">
                <div class="flex items-center justify-between p-4 rounded-2xl hover:bg-white/5 transition border border-white/5">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-xl bg-blue-500/10 flex items-center justify-center text-blue-400">
                            <i class="fa-solid fa-user-plus text-sm"></i>
                        </div>
                        <div>
                            <p class="text-sm font-bold">New Registration</p>
                            <p class="text-[10px] text-gray-500">User #SC-99301 created an account.</p>
                        </div>
                    </div>
                    <span class="text-[10px] font-black text-gray-600">10:45 AM</span>
                </div>

                <div class="flex items-center justify-between p-4 rounded-2xl hover:bg-white/5 transition border border-white/5">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-xl bg-green-500/10 flex items-center justify-center text-green-400">
                            <i class="fa-solid fa-check-double text-sm"></i>
                        </div>
                        <div>
                            <p class="text-sm font-bold">PUJ Verified</p>
                            <p class="text-[10px] text-gray-500">Fleet Unit #PJ-442 confirmed for duty.</p>
                        </div>
                    </div>
                    <span class="text-[10px] font-black text-gray-600">09:30 AM</span>
                </div>

                <div class="flex items-center justify-between p-4 rounded-2xl hover:bg-white/5 transition border border-white/5">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-xl bg-purple-500/10 flex items-center justify-center text-purple-400">
                            <i class="fa-solid fa-route text-sm"></i>
                        </div>
                        <div>
                            <p class="text-sm font-bold">Route Updated</p>
                            <p class="text-[10px] text-gray-500">Schedule for Route 14B was modified.</p>
                        </div>
                    </div>
                    <span class="text-[10px] font-black text-gray-600">08:15 AM</span>
                </div>
            </div>
        </div>
    </main>

    <div x-show="showLogoutModal"
         class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        <div @click.away="showLogoutModal = false" class="glass p-8 rounded-[2.5rem] max-w-sm w-full border border-white/10 shadow-2xl">
            <div class="text-center">
                <div class="w-16 h-16 bg-red-500/10 text-red-500 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <i class="fa-solid fa-power-off text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold mb-2">End Session?</h3>
                <p class="text-gray-400 text-sm mb-8">Are you sure you want to exit the Admin Panel?</p>

                <div class="flex gap-3">
                    <button @click="showLogoutModal = false" class="flex-1 py-3 rounded-xl bg-white/5 hover:bg-white/10 transition font-bold text-xs uppercase tracking-widest">
                        Cancel
                    </button>
                    <form action="{{ route('home') }}" method="GET" class="flex-1">
                        @csrf
                        <button type="submit" class="w-full py-3 rounded-xl bg-red-600 hover:bg-red-500 transition font-bold text-xs uppercase tracking-widest">
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
</html>
