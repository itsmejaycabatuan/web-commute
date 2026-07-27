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

    @include('driver.layout.sidebar')

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
