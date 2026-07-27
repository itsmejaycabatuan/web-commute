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

    @include('components.flash')

    @include('layout.sidebar');

    <main :class="open ? 'ml-72' : 'ml-20'" class="sidebar-transition p-8 md:p-12 min-h-screen">
        <div class="max-w-4xl mx-auto">
            <header class="mb-12">
                <h2 class="text-3xl font-black tracking-tight mb-2">My <span class="text-blue-500">Profile</span></h2>
                <p class="text-gray-500 text-sm">Your account details from registration.</p>
            </header>

            <div class="glass rounded-[2.5rem] p-8 md:p-12 relative overflow-hidden">
                <div class="absolute -top-24 -right-24 w-64 h-64 bg-blue-600/10 rounded-full blur-3xl"></div>

                <div class="relative z-10 flex flex-col md:flex-row items-center gap-10">
                    <div class="flex-1 space-y-6 w-full">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="space-y-1 md:col-span-2">
                                <label
                                    class="text-[10px] uppercase font-black text-blue-500 tracking-[0.2em]">Email</label>
                                <div
                                    class="glass p-4 rounded-2xl text-sm font-semibold border-white/5 bg-white/[0.02] break-all">
                                    {{ $user->email }}
                                </div>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-white/10 grid grid-cols-1 md:grid-cols-1 gap-8">
                            <form action="{{ route('profile.update') }}" method="POST" class="space-y-4 max-w-md">
                                @csrf
                                @method('PUT')
                                <div>
                                    <p class="text-[10px] uppercase font-black text-gray-500 tracking-[0.2em] mb-4">Change
                                        password</p>
                                    <input type="password" name="current_password" autocomplete="current-password"
                                        class="mt-1 w-full glass p-3 rounded-xl text-sm border border-white/10 bg-white/5 focus:ring-2 focus:ring-blue-500/40 focus:outline-none"
                                        placeholder="Current Password">
                                    @error('password')
                                        <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <input type="password" name="password" autocomplete="new-password"
                                        class="mt-1 w-full glass p-3 rounded-xl text-sm border border-white/10 bg-white/5 focus:ring-2 focus:ring-blue-500/40 focus:outline-none"
                                        placeholder="New Password">
                                    @error('password')
                                        <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <input type="password" name="password_confirmation" autocomplete="new-password"
                                        class="mt-1 w-full glass p-3 rounded-xl text-sm border border-white/10 bg-white/5 focus:ring-2 focus:ring-blue-500/40 focus:outline-none" placeholder="Confirm Password">
                                </div>
                                <button type="submit"
                                    class="bg-white text-black px-8 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-blue-50 transition active:scale-95 shadow-lg shadow-white/5">
                                    Update password
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8">

                <div class="glass p-6 rounded-3xl border-white/5">
                    <p class="text-[10px] uppercase font-bold text-gray-500 mb-1">Joined</p>
                    <p class="text-sm font-bold">
                        {{ $user->created_at->timezone(config('app.timezone'))->format('F j, Y') }}
                    </p>
                    <p class="text-[10px] text-gray-600 mt-1">Member since {{ $user->created_at->diffForHumans() }}</p>
                </div>
            </div>
        </div>
    </main>

    <div x-show="showLogoutModal" x-cloak
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
        x-on:click.self="showLogoutModal = false">
        <div class="glass p-8 rounded-[2.5rem] max-w-sm w-full border border-white/10 shadow-2xl">
            <div class="text-center text-white">
                <h3 class="text-xl font-bold mb-2">End Session?</h3>
                <p class="text-gray-400 text-sm mb-8">Confirm to exit the system.</p>
                <div class="flex gap-3">
                    <button type="button" x-on:click="showLogoutModal = false"
                        class="flex-1 py-3 rounded-xl bg-white/5 hover:bg-white/10 transition font-bold text-xs">
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
