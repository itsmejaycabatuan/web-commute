<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartCommute | My Profile</title>
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

    <aside :class="open ? 'w-72' : 'w-20'" class="sidebar-transition fixed left-0 top-0 h-screen glass border-r border-white/10 z-50 flex flex-col justify-between p-4">
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
                <a href="{{ route('driverdashboard') }}" class="flex items-center gap-4 p-3 rounded-2xl text-gray-500 hover:bg-white/5 hover:text-white transition group">
                    <div class="min-w-[24px] flex justify-center"><i class="fa-solid fa-gauge-high text-lg"></i></div>
                    <span x-show="open" x-transition.opacity class="text-xs font-bold uppercase tracking-widest">Driver Console</span>
                </a>
                <a href="#" class="flex items-center gap-4 p-3 rounded-2xl text-gray-500 hover:bg-white/5 hover:text-white transition group">
                    <div class="min-w-[24px] flex justify-center"><i class="fa-solid fa-route text-lg"></i></div>
                    <span x-show="open" x-transition.opacity class="text-xs font-bold uppercase tracking-widest">My Routes</span>
                </a>
                <a href="#" class="flex items-center gap-4 p-3 rounded-2xl bg-blue-600/10 text-blue-400 border border-blue-500/20 group">
                    <div class="min-w-[24px] flex justify-center"><i class="fa-solid fa-circle-user text-lg"></i></div>
                    <span x-show="open" x-transition.opacity class="text-xs font-bold uppercase tracking-widest">Profile</span>
                </a>
            </nav>
        </div>

        <button @click="showLogoutModal = true" class="w-full flex items-center gap-4 p-3 rounded-2xl hover:bg-red-500/10 text-gray-500 hover:text-red-500 transition-all group">
            <div class="min-w-[24px] flex justify-center"><i class="fa-solid fa-right-from-bracket text-lg"></i></div>
            <span x-show="open" x-transition.opacity class="text-xs font-bold uppercase tracking-widest">Exit System</span>
        </button>
    </aside>

    <main :class="open ? 'ml-72' : 'ml-20'" class="sidebar-transition p-8 md:p-12 min-h-screen">
        <div class="max-w-4xl mx-auto">
            <header class="mb-12">
                <h2 class="text-3xl font-black tracking-tight mb-2">My <span class="text-blue-500">Profile</span></h2>
                <p class="text-gray-500 text-sm">Manage your driver account information and credentials.</p>
            </header>

            <div class="glass rounded-[2.5rem] p-8 md:p-12 relative overflow-hidden">
                <div class="absolute -top-24 -right-24 w-64 h-64 bg-blue-600/10 rounded-full blur-3xl"></div>

                <div class="relative z-10 flex flex-col md:flex-row items-center gap-10">
                    <div class="relative">
                        <div class="w-32 h-32 rounded-[2.5rem] glass border-2 border-white/10 flex items-center justify-center bg-white/5 overflow-hidden">
                            <i class="fa-solid fa-user text-5xl text-blue-500/50"></i>
                        </div>
                        <label for="upload-photo" class="absolute -bottom-2 -right-2 w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center border-4 border-[#050505] cursor-pointer hover:bg-blue-500 transition">
                            <i class="fa-solid fa-camera text-[10px]"></i>
                            <input type="file" id="upload-photo" class="hidden">
                        </label>
                    </div>

                    <div class="flex-1 space-y-6 w-full">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="space-y-1">
                                <label class="text-[10px] uppercase font-black text-blue-500 tracking-[0.2em]">Full Name</label>
                                <div class="glass p-4 rounded-2xl text-sm font-semibold border-white/5 bg-white/[0.02]">
                                    Allen Jay Cabatuan
                                </div>
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] uppercase font-black text-blue-500 tracking-[0.2em]">Email Address</label>
                                <div class="glass p-4 rounded-2xl text-sm font-semibold border-white/5 bg-white/[0.02]">
                                    allenjaycabatuan@gmail.com
                                </div>
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] uppercase font-black text-blue-500 tracking-[0.2em]">License Number</label>
                                <div class="glass p-4 rounded-2xl text-sm font-semibold border-white/5 bg-white/[0.02]">
                                    D01-23-456789
                                </div>
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] uppercase font-black text-blue-500 tracking-[0.2em]">Assigned Vehicle</label>
                                <div class="glass p-4 rounded-2xl text-sm font-semibold border-white/5 bg-white/[0.02]">
                                    PUJ-14B (PJ-442)
                                </div>
                            </div>
                        </div>

                        <div class="pt-6">
                            <button class="bg-white text-black px-8 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-blue-50 transition active:scale-95 shadow-lg shadow-white/5">
                                Update Information
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
                <div class="glass p-6 rounded-3xl border-white/5">
                    <p class="text-[10px] uppercase font-bold text-gray-500 mb-1">Status</p>
                    <p class="text-sm font-bold text-green-400 flex items-center">
                        <i class="fa-solid fa-circle-check mr-2 text-[10px]"></i> Verified Driver
                    </p>
                </div>
                <div class="glass p-6 rounded-3xl border-white/5">
                    <p class="text-[10px] uppercase font-bold text-gray-500 mb-1">Joined</p>
                    <p class="text-sm font-bold">February 2026</p>
                </div>
                <div class="glass p-6 rounded-3xl border-white/5">
                    <p class="text-[10px] uppercase font-bold text-gray-500 mb-1">Rating</p>
                    <p class="text-sm font-bold flex items-center">
                        <i class="fa-solid fa-star text-yellow-500 mr-2 text-[10px]"></i> 4.9 <span class="text-gray-500 font-normal ml-1">(142 trips)</span>
                    </p>
                </div>
            </div>
        </div>
    </main>

    <div x-show="showLogoutModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm" style="display: none;">
        <div class="glass p-8 rounded-[2.5rem] max-w-sm w-full border border-white/10 shadow-2xl">
            <div class="text-center text-white">
                <h3 class="text-xl font-bold mb-2">End Session?</h3>
                <p class="text-gray-400 text-sm mb-8">Confirm to exit the system.</p>
               <div class="flex gap-3">
    <button @click="showLogoutModal = false" class="flex-1 py-3 rounded-xl bg-white/5 hover:bg-white/10 transition font-bold text-xs">
        Cancel
    </button>

    <form action="{{ route('home') }}" method="POST" class="flex-1">
        @csrf
        <button type="submit" class="w-full py-3 rounded-xl bg-red-600 hover:bg-red-500 transition font-bold text-xs uppercase tracking-widest text-white">
            Logout
        </button>
    </form>
</div>
            </div>
        </div>
    </div>

</body>
</html>
