<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SmartCommute | Settings</title>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    @include('partials.head-scripts')
    <style>
        .sidebar-transition {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }

        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="antialiased text-gray-900 dark:text-white" x-data>

    <div x-data="settingsPage()" @keydown.escape.window="activeTab = 'account'">

        <x-layout.sidebar />

        <main :class="$store.sidebar.open ? 'md:ml-72' : 'md:ml-20'"
            class="sidebar-transition pt-8 pr-4 sm:pr-8 pb-8 pl-4 sm:pl-8 min-h-screen mb-12">

            <!-- ── Mobile: Identity Card ── -->
            <div class="lg:hidden mb-5">
                <div class="glass-card p-4 rounded-[1.25rem]">
                    <div class="flex items-center gap-3.5">
                        <div class="w-11 h-11 bg-red-600 rounded-xl flex items-center justify-center shrink-0">
                            <span
                                class="text-sm font-black text-white">{{ strtoupper(substr(explode('@', Auth::user()->email)[0], 0, 1)) }}</span>
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-sm font-bold text-gray-900 dark:text-white truncate">Settings</h2>
                            <p class="text-[10px] text-gray-500 dark:text-[#555] truncate">{{ Auth::user()->email }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Page Header ── -->
            <div class="hidden lg:block mb-8">
                <div class="flex items-center gap-2 mb-1.5">
                    <span
                        class="text-[9px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Account</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-gray-900 dark:text-white">Settings</h1>
                <p class="text-[11px] text-gray-500 dark:text-[#555] mt-1 flex items-center gap-2">
                    <i class="fa-solid fa-gear text-[9px] text-gray-400 dark:text-[#333]"></i>
                    Manage your account information and security
                </p>
            </div>

            @if (session('success'))
                <div
                    class="mb-5 px-4 py-3 rounded-xl border border-emerald-500/15 bg-emerald-500/5 flex items-center gap-3">
                    <div class="w-6 h-6 rounded-md bg-emerald-500/10 flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-check text-[8px] text-emerald-500 dark:text-emerald-400"></i>
                    </div>
                    <span
                        class="text-[11px] text-emerald-600 dark:text-emerald-400 font-medium">{{ session('success') }}</span>
                </div>
            @endif

            <!-- ══════════ TABS ══════════ -->
            <div class="flex items-center gap-1 mb-6 p-1 bg-gray-100 dark:bg-[#111] rounded-xl w-fit">
                <button @click="activeTab = 'account'"
                    :class="activeTab === 'account'
                        ?
                        'bg-white dark:bg-[#1a1a1a] text-gray-900 dark:text-white shadow-sm' :
                        'text-gray-500 dark:text-[#555] hover:text-gray-700 dark:hover:text-[#888]'"
                    class="flex items-center gap-2 px-4 py-2 rounded-lg text-[9px] font-bold uppercase tracking-widest transition">
                    <i class="fa-solid fa-user text-[8px]"></i>
                    <span>Account</span>
                </button>
                <button @click="activeTab = 'security'"
                    :class="activeTab === 'security'
                        ?
                        'bg-white dark:bg-[#1a1a1a] text-gray-900 dark:text-white shadow-sm' :
                        'text-gray-500 dark:text-[#555] hover:text-gray-700 dark:hover:text-[#888]'"
                    class="flex items-center gap-2 px-4 py-2 rounded-lg text-[9px] font-bold uppercase tracking-widest transition">
                    <i class="fa-solid fa-shield-halved text-[8px]"></i>
                    <span>Security</span>
                </button>
            </div>

            <!-- ══════════ ACCOUNT TAB ══════════ -->
            <div x-show="activeTab === 'account'" x-cloak>
                <div class="glass-card rounded-[1.25rem] sm:rounded-[1.5rem] overflow-hidden">

                    <!-- Section header -->
                    <div class="px-6 sm:px-8 py-5 border-b border-gray-200 dark:border-[#1e1e1e]">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-9 h-9 rounded-xl bg-blue-500/10 border border-blue-500/15 flex items-center justify-center">
                                <i class="fa-solid fa-user-pen text-xs text-blue-500 dark:text-blue-400"></i>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Account Information</h3>
                                <p class="text-[9px] text-gray-500 dark:text-[#555]">Update your email address</p>
                            </div>
                        </div>
                    </div>

                    <!-- Form -->
                    <div class="px-6 sm:px-8 py-6 sm:py-8">
                        <form action="{{ route('settings.update') }}" method="POST" class="space-y-5">
                            @csrf
                            @method('PUT')

                            <div class="max-w-md">
                                <label
                                    class="block mb-1.5 text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">
                                    Email <span class="text-red-500 dark:text-red-400">*</span>
                                </label>
                                <input type="email" name="email" value="{{ old('email', Auth::user()->email) }}"
                                    required
                                    class="w-full px-4 py-2.5 rounded-xl bg-gray-50 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] text-[11px] text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-[#333] focus:outline-none focus:border-gray-300 dark:focus:border-[#333] transition"
                                    placeholder="Email address">
                                @error('email')
                                    <p class="mt-1.5 text-[9px] text-red-500 dark:text-red-400 flex items-center gap-1.5">
                                        <i class="fa-solid fa-circle-exclamation text-[7px]"></i> {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <!-- Read-only info -->
                            <div class="flex items-center gap-3 pt-2">
                                <div class="flex-1 h-px bg-gray-200 dark:bg-[#1e1e1e]"></div>
                                <span
                                    class="text-[7px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#333]">System
                                    info</span>
                                <div class="flex-1 h-px bg-gray-200 dark:bg-[#1e1e1e]"></div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-w-lg">
                                <div
                                    class="flex items-center justify-between p-3.5 rounded-xl bg-gray-50 dark:bg-[#0a0a0a] border border-gray-200 dark:border-[#1e1e1e]">
                                    <span
                                        class="text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Role</span>
                                    <span class="text-[10px] font-bold text-gray-700 dark:text-[#ccc]">
                                        {{ ucfirst(Auth::user()->getRoleNames()->first() ?? 'User') }} </span>
                                </div>
                                <div
                                    class="flex items-center justify-between p-3.5 rounded-xl bg-gray-50 dark:bg-[#0a0a0a] border border-gray-200 dark:border-[#1e1e1e]">
                                    <span
                                        class="text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Joined</span>
                                    <span class="text-[10px] font-bold text-gray-700 dark:text-[#ccc]">
                                        {{ Auth::user()->created_at->format('M d, Y') }}
                                    </span>
                                </div>
                            </div>

                            <div class="pt-2">
                                <button type="submit"
                                    class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-[10px] font-bold uppercase tracking-widest text-white transition active:scale-[0.98]">
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- ══════════ SECURITY TAB ══════════ -->
            <div x-show="activeTab === 'security'" x-cloak>
                <div class="glass-card rounded-[1.25rem] sm:rounded-[1.5rem] overflow-hidden">

                    <!-- Section header -->
                    <div class="px-6 sm:px-8 py-5 border-b border-gray-200 dark:border-[#1e1e1e]">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-9 h-9 rounded-xl bg-red-500/10 border border-red-500/15 flex items-center justify-center">
                                <i class="fa-solid fa-key text-xs text-red-500 dark:text-red-400"></i>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Change Password</h3>
                                <p class="text-[9px] text-gray-500 dark:text-[#555]">Update your account password</p>
                            </div>
                        </div>
                    </div>

                    <!-- Form -->
                    <div class="px-6 sm:px-8 py-6 sm:py-8">
                        <form action="{{ route('settings.password') }}" method="POST" class="space-y-5 max-w-md">
                            @csrf
                            @method('PUT')

                            <div>
                                <label
                                    class="block mb-1.5 text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">
                                    Current Password <span class="text-red-500 dark:text-red-400">*</span>
                                </label>
                                <div class="relative">
                                    <input :type="showCurrent ? 'text' : 'password'" name="current_password" required
                                        class="w-full px-4 pr-10 py-2.5 rounded-xl bg-gray-50 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] text-[11px] text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-[#333] focus:outline-none focus:border-gray-300 dark:focus:border-[#333] transition"
                                        placeholder="Enter current password">
                                    <button type="button" @click="showCurrent = !showCurrent"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 dark:text-[#444] hover:text-gray-700 dark:hover:text-[#888] transition">
                                        <i :class="showCurrent ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"
                                            class="text-[10px]"></i>
                                    </button>
                                </div>
                                @error('current_password')
                                    <p class="mt-1.5 text-[9px] text-red-500 dark:text-red-400 flex items-center gap-1.5">
                                        <i class="fa-solid fa-circle-exclamation text-[7px]"></i> {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <div>
                                <label
                                    class="block mb-1.5 text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">
                                    New Password <span class="text-red-500 dark:text-red-400">*</span>
                                </label>
                                <div class="relative">
                                    <input :type="showNew ? 'text' : 'password'" name="password" required
                                        minlength="8"
                                        class="w-full px-4 pr-10 py-2.5 rounded-xl bg-gray-50 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] text-[11px] text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-[#333] focus:outline-none focus:border-gray-300 dark:focus:border-[#333] transition"
                                        placeholder="Minimum 8 characters">
                                    <button type="button" @click="showNew = !showNew"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 dark:text-[#444] hover:text-gray-700 dark:hover:text-[#888] transition">
                                        <i :class="showNew ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"
                                            class="text-[10px]"></i>
                                    </button>
                                </div>
                                @error('password')
                                    <p class="mt-1.5 text-[9px] text-red-500 dark:text-red-400 flex items-center gap-1.5">
                                        <i class="fa-solid fa-circle-exclamation text-[7px]"></i> {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <div>
                                <label
                                    class="block mb-1.5 text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">
                                    Confirm New Password <span class="text-red-500 dark:text-red-400">*</span>
                                </label>
                                <div class="relative">
                                    <input :type="showConfirm ? 'text' : 'password'" name="password_confirmation"
                                        required
                                        class="w-full px-4 pr-10 py-2.5 rounded-xl bg-gray-50 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] text-[11px] text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-[#333] focus:outline-none focus:border-gray-300 dark:focus:border-[#333] transition"
                                        placeholder="Re-enter new password">
                                    <button type="button" @click="showConfirm = !showConfirm"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 dark:text-[#444] hover:text-gray-700 dark:hover:text-[#888] transition">
                                        <i :class="showConfirm ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"
                                            class="text-[10px]"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="pt-2">
                                <button type="submit"
                                    class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-[10px] font-bold uppercase tracking-widest text-white transition active:scale-[0.98]">
                                    Update Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Danger zone -->
                <div class="glass-card rounded-[1.25rem] sm:rounded-[1.5rem] overflow-hidden mt-5">
                    <div class="px-6 sm:px-8 py-5 border-b border-red-500/10">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-9 h-9 rounded-xl bg-red-500/10 border border-red-500/15 flex items-center justify-center">
                                <i class="fa-solid fa-triangle-exclamation text-xs text-red-500 dark:text-red-400"></i>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-red-600 dark:text-red-400">Danger Zone</h3>
                                <p class="text-[9px] text-gray-500 dark:text-[#555]">Irreversible actions</p>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 sm:px-8 py-5">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div>
                                <p class="text-[11px] font-bold text-gray-700 dark:text-[#ccc]">Sign out of all devices
                                </p>
                                <p class="text-[9px] text-gray-400 dark:text-[#444] mt-0.5">This will invalidate all
                                    active sessions except your current one.</p>
                            </div>
                            <form action="{{ route('settings.logout-others') }}" method="POST" class="shrink-0">
                                @csrf
                                <button type="submit"
                                    class="px-4 py-2 rounded-xl bg-red-500/5 border border-red-500/15 hover:bg-red-500/10 hover:border-red-500/25 text-[9px] font-bold uppercase tracking-widest text-red-600 dark:text-red-400 transition">
                                    Sign Out Others
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('settingsPage', () => ({
                activeTab: 'account',
                showCurrent: false,
                showNew: false,
                showConfirm: false,
            }));
        });
    </script>
</body>

</html>
