<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartCommute | My Profile</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: { extend: { fontFamily: { sans: ['Plus Jakarta Sans', 'sans-serif'] } } }
        }
    </script>
    <style>
        body, html { margin: 0; padding: 0; height: 100%; font-family: 'Plus Jakarta Sans', sans-serif; overflow-x: hidden; background: #050505; color: #fff; }
        .glass-panel { background: #111111 !important; border: 1px solid #1e1e1e; box-shadow: 0 4px 24px rgba(0,0,0,0.6); }
        .glass-card { background: #161616; border: 1px solid #222222; box-shadow: 0 4px 24px rgba(0,0,0,0.5); }
        .sidebar-transition { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }

        @keyframes fade-up {
            0% { opacity: 0; transform: translateY(12px); }
            100% { opacity: 1; transform: translateY(0); }
        }
        .anim-up { animation: fade-up 0.5s ease-out forwards; opacity: 0; }
        .anim-d1 { animation-delay: 60ms; }
        .anim-d2 { animation-delay: 120ms; }
        .anim-d3 { animation-delay: 180ms; }
        .anim-d4 { animation-delay: 240ms; }
        .anim-d5 { animation-delay: 300ms; }

        .pw-input {
            width: 100%;
            background: #0e0e0e;
            border: 1px solid #1e1e1e;
            border-radius: 0.75rem;
            font-size: 0.75rem;
            font-weight: 500;
            color: #fff;
            padding-top: 0.75rem;
            padding-bottom: 0.75rem;
            padding-left: 2.5rem;
            padding-right: 1rem;
            min-height: 44px;
            transition: all 0.2s ease;
            outline: none;
            display: block;
        }
        @media (min-width: 640px) {
            .pw-input {
                border-radius: 1rem;
                font-size: 0.875rem;
                padding-top: 0.875rem;
                padding-bottom: 0.875rem;
                padding-right: 1.25rem;
                min-height: 48px;
            }
        }
        .pw-input::placeholder { color: #3a3a3a; }
        .pw-input:focus {
            border-color: rgba(59, 130, 246, 0.3);
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.15);
        }
        .pw-input:-webkit-autofill {
            -webkit-box-shadow: 0 0 0 1000px #0e0e0e inset !important;
            -webkit-text-fill-color: #fff !important;
        }

        ::-webkit-scrollbar { width: 3px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #333; border-radius: 10px; }
    </style>
</head>

<body class="antialiased" x-data="{ open: false, showLogoutModal: false }" @resize.window="if(window.innerWidth >= 768) open = true">

    @include('components.flash')
    @include('driver.layout.sidebar')

    <main :class="open ? 'md:ml-72' : 'md:ml-20'" class="sidebar-transition pt-4 sm:pt-8 pr-3 sm:pr-8 pb-8 pl-3 sm:pl-8 min-h-screen mb-20 md:mb-12">
        <div class="max-w-3xl mx-auto">

            <!-- Page Header -->
            <div class="mb-6 sm:mb-8 anim-up">
                <div class="flex items-center justify-between mb-1.5">
                    <div class="flex items-center gap-2">
                        <span class="text-[9px] font-bold uppercase tracking-[0.15em] text-[#444]">Driver Console</span>
                    </div>
                </div>
                <h1 class="text-xl sm:text-2xl md:text-3xl font-black tracking-tight">My <span class="text-blue-400">Profile</span></h1>
                <p class="text-[10px] sm:text-[11px] text-[#555] mt-1">Account details and security settings</p>
            </div>

            <!-- ══════════ PROFILE HERO CARD ══════════ -->
            <div class="glass-card rounded-2xl sm:rounded-[2rem] overflow-hidden mb-4 anim-up anim-d1">
                <!-- Gradient Top Bar -->
                <div class="h-24 sm:h-28 relative overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-br from-blue-600/20 via-blue-500/10 to-transparent"></div>
                    <div class="absolute -top-10 -right-10 w-40 h-40 bg-blue-500/10 rounded-full blur-3xl"></div>
                    <div class="absolute -bottom-10 -left-10 w-32 h-32 bg-indigo-500/5 rounded-full blur-2xl"></div>
                </div>

                <div class="px-5 sm:px-8 pb-6 sm:pb-8 -mt-10 sm:-mt-12 relative z-10">
                    <!-- Info Grid -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
                        <div class="bg-[#0e0e0e] rounded-xl sm:rounded-2xl p-3 sm:p-4 border border-[#1a1a1a]">
                            <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-[#111] border border-[#1e1e1e] flex items-center justify-center mb-2 sm:mb-2.5">
                                <i class="fa-solid fa-envelope text-[9px] sm:text-[10px] text-[#555]"></i>
                            </div>
                            <p class="text-[8px] sm:text-[9px] font-bold uppercase tracking-wider text-[#444] mb-1">Email</p>
                            <p class="text-[10px] sm:text-[11px] font-bold text-[#aaa] break-all leading-tight">{{ $user->email }}</p>
                        </div>
                        <div class="bg-[#0e0e0e] rounded-xl sm:rounded-2xl p-3 sm:p-4 border border-[#1a1a1a]">
                            <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-[#111] border border-[#1e1e1e] flex items-center justify-center mb-2 sm:mb-2.5">
                                <i class="fa-solid fa-id-badge text-[9px] sm:text-[10px] text-[#555]"></i>
                            </div>
                            <p class="text-[8px] sm:text-[9px] font-bold uppercase tracking-wider text-[#444] mb-1">ID</p>
                            <p class="text-[10px] sm:text-[11px] font-bold text-[#aaa]">#{{ str_pad($user->id, 4, '0', STR_PAD_LEFT) }}</p>
                        </div>
                        <div class="bg-[#0e0e0e] rounded-xl sm:rounded-2xl p-3 sm:p-4 border border-[#1a1a1a]">
                            <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-[#111] border border-[#1e1e1e] flex items-center justify-center mb-2 sm:mb-2.5">
                                <i class="fa-solid fa-calendar-check text-[9px] sm:text-[10px] text-[#555]"></i>
                            </div>
                            <p class="text-[8px] sm:text-[9px] font-bold uppercase tracking-wider text-[#444] mb-1">Joined</p>
                            <p class="text-[10px] sm:text-[11px] font-bold text-[#aaa]">{{ $user->created_at->timezone(config('app.timezone'))->format('M j, Y') }}</p>
                        </div>
                        <div class="bg-[#0e0e0e] rounded-xl sm:rounded-2xl p-3 sm:p-4 border border-[#1a1a1a]">
                            <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-[#111] border border-[#1e1e1e] flex items-center justify-center mb-2 sm:mb-2.5">
                                <i class="fa-solid fa-hourglass-half text-[9px] sm:text-[10px] text-[#555]"></i>
                            </div>
                            <p class="text-[8px] sm:text-[9px] font-bold uppercase tracking-wider text-[#444] mb-1">Tenure</p>
                            <p class="text-[10px] sm:text-[11px] font-bold text-[#aaa]">{{ $user->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ══════════ SECURITY SECTION ══════════ -->
            <div class="glass-card rounded-2xl sm:rounded-[2rem] overflow-hidden mb-4 anim-up anim-d3">
                <!-- Section Header -->
                <div class="px-5 sm:px-8 pt-5 sm:pt-6 pb-4 sm:pb-5 border-b border-[#1e1e1e] flex items-center justify-between">
                    <div class="flex items-center gap-2.5 sm:gap-3">
                        <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-amber-500/10 border border-amber-500/15 flex items-center justify-center">
                            <i class="fa-solid fa-shield text-[10px] sm:text-[11px] text-amber-400"></i>
                        </div>
                        <div>
                            <h3 class="text-xs sm:text-sm font-bold">Security</h3>
                            <p class="text-[8px] sm:text-[9px] text-[#444] font-medium">Change your account password</p>
                        </div>
                    </div>
                    <div class="w-2 h-2 rounded-full bg-amber-400/40"></div>
                </div>

                <!-- Form -->
                <div class="px-5 sm:px-8 py-5 sm:py-6">
                    <form action="{{ route('profile.update') }}" method="POST" class="space-y-4">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-4">
                            <!-- Current Password -->
                            <div class="sm:col-span-2">
                                <label class="text-[9px] sm:text-[10px] font-bold uppercase tracking-[0.15em] text-[#444] mb-2 block">Current Password</label>
                                <div class="relative">
                                    <i class="fa-solid fa-lock absolute left-3.5 top-1/2 -translate-y-1/2 text-[10px] text-[#333] pointer-events-none"></i>
                                    <input type="password" name="current_password" autocomplete="current-password"
                                        class="pw-input"
                                        placeholder="Enter current password">
                                </div>
                                @error('current_password')
                                    <p class="text-red-400 text-[10px] mt-1.5 flex items-center gap-1.5">
                                        <i class="fa-solid fa-circle-exclamation text-[8px]"></i> {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <!-- New Password -->
                            <div>
                                <label class="text-[9px] sm:text-[10px] font-bold uppercase tracking-[0.15em] text-[#444] mb-2 block">New Password</label>
                                <div class="relative">
                                    <i class="fa-solid fa-key absolute left-3.5 top-1/2 -translate-y-1/2 text-[10px] text-[#333] pointer-events-none"></i>
                                    <input type="password" name="password" autocomplete="new-password"
                                        class="pw-input"
                                        placeholder="New password">
                                </div>
                                @error('password')
                                    <p class="text-red-400 text-[10px] mt-1.5 flex items-center gap-1.5">
                                        <i class="fa-solid fa-circle-exclamation text-[8px]"></i> {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <!-- Confirm Password -->
                            <div>
                                <label class="text-[9px] sm:text-[10px] font-bold uppercase tracking-[0.15em] text-[#444] mb-2 block">Confirm Password</label>
                                <div class="relative">
                                    <i class="fa-solid fa-check-double absolute left-3.5 top-1/2 -translate-y-1/2 text-[10px] text-[#333] pointer-events-none"></i>
                                    <input type="password" name="password_confirmation" autocomplete="new-password"
                                        class="pw-input"
                                        placeholder="Confirm new password">
                                </div>
                                @error('password_confirmation')
                                    <p class="text-red-400 text-[10px] mt-1.5 flex items-center gap-1.5">
                                        <i class="fa-solid fa-circle-exclamation text-[8px]"></i> {{ $message }}
                                    </p>
                                @enderror
                            </div>
                        </div>

                        <!-- Submit -->
                        <div class="pt-2 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <p class="text-[8px] sm:text-[9px] text-[#333] flex items-center gap-1.5">
                                <i class="fa-solid fa-circle-info text-[7px]"></i>
                                Use a strong password with 8+ characters
                            </p>
                            <button type="submit"
                                class="w-full sm:w-auto bg-blue-500 hover:bg-blue-600 text-white px-8 py-3 rounded-xl sm:rounded-2xl text-[10px] font-black uppercase tracking-[0.15em] transition active:scale-[0.97] shadow-lg shadow-blue-500/10 flex items-center justify-center gap-2">
                                <i class="fa-solid fa-rotate text-[9px]"></i>
                                Update Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ══════════ DANGER ZONE ══════════ -->
            <div class="glass-card rounded-2xl sm:rounded-[2rem] overflow-hidden anim-up anim-d4">
                <div class="px-5 sm:px-8 pt-5 sm:pt-6 pb-5 sm:pb-6 flex items-center justify-between">
                    <div class="flex items-center gap-2.5 sm:gap-3">
                        <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-red-500/10 border border-red-500/15 flex items-center justify-center">
                            <i class="fa-solid fa-right-from-bracket text-[10px] sm:text-[11px] text-red-400"></i>
                        </div>
                        <div>
                            <h3 class="text-xs sm:text-sm font-bold">Session</h3>
                            <p class="text-[8px] sm:text-[9px] text-[#444] font-medium">End your current session</p>
                        </div>
                    </div>
                    <button @click="showLogoutModal = true"
                        class="px-4 sm:px-5 py-2 sm:py-2.5 rounded-xl bg-red-500/10 border border-red-500/15 text-[9px] sm:text-[10px] font-bold text-red-400 hover:bg-red-500/20 transition active:scale-95 flex items-center gap-2">
                        <i class="fa-solid fa-power-off text-[8px] sm:text-[9px]"></i>
                        <span class="hidden sm:inline">Logout</span>
                    </button>
                </div>
            </div>

            <!-- Footer -->
            <p class="text-center text-[7px] sm:text-[8px] text-[#222] uppercase tracking-[0.2em] pt-6 anim-up anim-d5">
                SmartCommute Driver Systems &bull; Profile Module
            </p>
        </div>
    </main>

    <!-- ══════════ LOGOUT MODAL ══════════ -->
    <div x-show="showLogoutModal" x-cloak
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
        x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        @click.self="showLogoutModal = false" style="display:none;">

        <div class="glass-panel p-6 sm:p-8 rounded-[1.5rem] sm:rounded-[2rem] max-w-sm w-full mx-4">
            <div class="text-center">
                <div class="w-14 h-14 bg-red-500/10 rounded-2xl flex items-center justify-center mx-auto mb-5 border border-red-500/20">
                    <i class="fa-solid fa-power-off text-red-400 text-lg"></i>
                </div>
                <h3 class="text-lg font-bold text-white mb-1.5">End Session?</h3>
                <p class="text-xs text-[#666] mb-7">Are you sure you want to exit the Driver Console?</p>

                <div class="flex gap-2.5">
                    <button @click="showLogoutModal = false"
                        class="flex-1 py-3 rounded-xl bg-[#1a1a1a] border border-[#2a2a2a] text-white text-[10px] font-bold uppercase tracking-widest hover:bg-[#222] transition active:scale-[0.98]">
                        Cancel
                    </button>
                    <form action="{{ route('users.logout') }}" method="POST" class="flex-1">
                        @csrf
                        <button type="submit"
                            class="w-full py-3 rounded-xl bg-red-600 text-white text-[10px] font-bold uppercase tracking-widest hover:bg-red-700 transition active:scale-[0.98]">
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
