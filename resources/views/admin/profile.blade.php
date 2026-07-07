<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartCommute | Admin Profile</title>
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

        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body x-data="{ open: true, showLogoutModal: false }">

    @include('layout.sidebar')

    <main :class="open ? 'ml-72' : 'ml-20'" class="sidebar-transition p-6 md:p-12 min-h-screen">
        <div class="max-w-3xl mx-auto">
            <header class="mb-8">
                <h2 class="text-2xl font-bold tracking-tight">Account <span class="text-blue-500">Settings</span></h2>
                <p class="text-gray-500 text-xs">Manage your credentials and view account timestamps.</p>
            </header>

            <div class="bg-white/5 border border-white/10 rounded-[2rem] p-8 overflow-hidden relative">
                <div class="flex flex-col md:flex-row items-start md:items-center gap-8">

                    <div
                        class="w-20 h-20 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shadow-lg shadow-blue-500/20">
                        <span class="text-2xl font-black text-white">
                            {{ strtoupper(substr(Auth::user()->email, 0, 1)) }}
                        </span>
                    </div>

                    <div class="flex-1 space-y-4 w-full">
                        <div>
                            <label
                                class="text-[10px] uppercase font-bold text-gray-500 tracking-widest block mb-1">Email
                                Address</label>
                            <p class="text-lg font-medium">{{ Auth::user()->email }}</p>
                        </div>

                        <div class="flex flex-wrap gap-6">
                            <div>
                                <label
                                    class="text-[10px] uppercase font-bold text-gray-500 tracking-widest block mb-1">Status</label>
                                @if(Auth::user()->email_verified_at)
                                    <span
                                        class="text-[10px] bg-emerald-500/10 text-emerald-500 px-2 py-1 rounded-md font-bold flex items-center">
                                        <i class="fa-solid fa-check-circle mr-1 text-[8px]"></i> VERIFIED
                                    </span>
                                @else
                                    <span
                                        class="text-[10px] bg-amber-500/10 text-amber-500 px-2 py-1 rounded-md font-bold">UNVERIFIED</span>
                                @endif
                            </div>

                            <div>
                                <label
                                    class="text-[10px] uppercase font-bold text-gray-500 tracking-widest block mb-1">Member
                                    Since</label>
                                <p class="text-xs font-semibold">{{ Auth::user()->created_at->format('M Y') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="w-full md:w-auto">
                        <button
                            class="w-full md:w-auto bg-white text-black px-6 py-2.5 rounded-xl text-[10px] font-bold uppercase tracking-widest hover:bg-gray-200 transition">
                            Update Password
                        </button>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <div class="bg-white/5 border border-white/5 p-5 rounded-2xl">
                    <p class="text-[10px] uppercase font-bold text-gray-500 mb-1">Database Reference</p>
                    <p class="text-xs font-mono text-blue-400">UID:
                        #{{ str_pad(Auth::user()->id, 4, '0', STR_PAD_LEFT) }}</p>
                </div>
                <div class="bg-white/5 border border-white/5 p-5 rounded-2xl">
                    <p class="text-[10px] uppercase font-bold text-gray-500 mb-1">Last Update</p>
                    <p class="text-xs font-semibold">{{ Auth::user()->updated_at->diffForHumans() }}</p>
                </div>
            </div>
        </div>
    </main>

    <div x-show="showLogoutModal" x-cloak
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm" x-transition>
        <div @click.away="showLogoutModal = false"
            class="glass p-8 rounded-[2.5rem] max-w-sm w-full border border-white/10 shadow-2xl">
            <div class="text-center">
                <div
                    class="w-16 h-16 bg-red-500/10 text-red-500 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <i class="fa-solid fa-power-off text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold mb-2">End Admin Session?</h3>
                <p class="text-gray-400 text-sm mb-8">Are you sure you want to end your admin session?</p>
                <div class="flex gap-3">
                    <button type="button" @click="showLogoutModal = false"
                        class="flex-1 py-3 rounded-xl bg-white/5 hover:bg-white/10 transition font-bold text-xs uppercase tracking-widest">
                        Cancel
                    </button>
                    <form action="{{ route('users.logout') }}" method="POST" class="flex-1">
                        @csrf
                        <button type="submit"
                            class="w-full py-3 rounded-xl bg-red-600 hover:bg-red-500 transition font-bold text-xs uppercase tracking-widest text-white">
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</body>

</html>