<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SmartCommute | Settings</title>

    @include('partials.commuter-head-scripts')

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">

    <style>
        body,
        html {
            margin: 0;
            padding: 0;
            height: 100%;
            width: 100%;
            font-family: 'Inter', sans-serif;
            overflow-x: hidden;
        }

        body {
            background: #f8fafc;
        }

        .dark body {
            background: #050505;
        }

        .glass-panel {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.06);
        }

        .dark .glass-panel {
            background: #111111;
            border: 1px solid #1e1e1e;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.6);
        }

        .glass-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        }

        .dark .glass-card {
            background: #161616;
            border: 1px solid #222222;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.5);
        }

        .inner-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
        }

        .dark .inner-card {
            background: #111;
            border: 1px solid #1e1e1e;
        }

        .header-btn {
            transition: all 0.2s ease;
        }

        .header-btn:hover {
            background: #f1f5f9 !important;
            border-color: #cbd5e1 !important;
        }

        .dark .header-btn:hover {
            background: #1a1a1a !important;
            border-color: #333 !important;
        }

        .form-input {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #0f172a;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-input::placeholder {
            color: #94a3b8;
        }

        .form-input:focus {
            border-color: rgba(59, 130, 246, 0.4);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.08);
        }

        .dark .form-input {
            background: #111;
            border: 1px solid #1e1e1e;
            color: #fff;
        }

        .dark .form-input::placeholder {
            color: #555;
        }

        .form-input-locked {
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            color: #94a3b8;
            cursor: not-allowed;
            user-select: none;
        }

        .dark .form-input-locked {
            background: #0a0a0a;
            border: 1px solid #1e1e1e;
            color: #444;
        }

        .setting-row {
            transition: background 0.15s ease;
        }

        .setting-row:hover {
            background: #f8fafc;
        }

        .dark .setting-row:hover {
            background: #1a1a1a;
        }

        .toggle-track {
            width: 44px;
            height: 24px;
            border-radius: 12px;
            background: #cbd5e1;
            position: relative;
            transition: background 0.3s ease;
            cursor: pointer;
        }

        .toggle-track.active {
            background: #3b82f6;
        }

        .dark .toggle-track {
            background: #333;
        }

        .dark .toggle-track.active {
            background: #2563eb;
        }

        .toggle-thumb {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: white;
            position: absolute;
            top: 3px;
            left: 3px;
            transition: transform 0.3s ease;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
        }

        .toggle-track.active .toggle-thumb {
            transform: translateX(20px);
        }

        .nav-item {
            padding: 10px 14px;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.15s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-item:hover {
            background: #f1f5f9;
        }

        .dark .nav-item:hover {
            background: #1a1a1a;
        }

        .nav-item.active {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
        }

        .dark .nav-item.active {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.2);
        }

        ::-webkit-scrollbar {
            width: 3px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        .dark ::-webkit-scrollbar-thumb {
            background: #333;
            border-radius: 10px;
        }
    </style>
</head>

<body class="antialiased text-gray-900 dark:text-white">

    @include('components.flash')

    <!-- ══════════ HEADER ══════════ -->
    <header
        class="fixed top-4 left-4 right-4 sm:top-5 sm:left-5 sm:right-5 z-50 flex items-center justify-between gap-3 pointer-events-none">
        <div class="glass-panel p-3 sm:p-3.5 rounded-2xl pointer-events-auto flex items-center gap-3 min-w-0">
            <a href="{{ route('profile') }}"
                class="header-btn w-9 h-9 rounded-xl flex items-center justify-center shrink-0 border border-gray-200 dark:border-[#1e1e1e] bg-white dark:bg-[#111] transition cursor-pointer">
                <i class="fa-solid fa-arrow-left text-[10px] text-gray-500 dark:text-[#666]"></i>
            </a>
            <div class="w-px h-6 bg-gray-200 dark:bg-[#222] mx-0.5 hidden sm:block"></div>
            <div
                class="w-8 h-8 sm:w-9 sm:h-9 bg-blue-600 rounded-lg sm:rounded-xl flex items-center justify-center shrink-0">
                <i class="fa-solid fa-bus text-white text-xs sm:text-sm"></i>
            </div>
            <span
                class="text-[13px] sm:text-sm font-bold tracking-tight text-gray-900 dark:text-white whitespace-nowrap">Smart<span
                    class="text-blue-500 dark:text-blue-400">Commute</span></span>
            <div class="w-px h-6 bg-gray-200 dark:bg-[#222] mx-0.5 hidden sm:block"></div>
            <span
                class="text-[9px] sm:text-[10px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#555] hidden sm:inline">Settings</span>
        </div>
    </header>

    <!-- ══════════ MAIN CONTENT ══════════ -->
    <div class="pt-20 sm:pt-24 pb-8 sm:pb-10 max-w-5xl mx-auto px-4 sm:px-6">

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            <!-- ══════════ LEFT NAV (desktop) ══════════ -->
            <div class="hidden lg:block lg:col-span-4">
                <div class="glass-card p-4 rounded-[1.5rem] sticky top-28">
                    <div class="space-y-1">
                        <div class="nav-item active" data-section="profile" onclick="switchSection('profile', this)">
                            <div
                                class="w-8 h-8 rounded-lg bg-blue-500/10 dark:bg-blue-500/15 flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-user text-[10px] text-blue-500 dark:text-blue-400"></i>
                            </div>
                            <div>
                                <p class="text-[11px] font-bold text-gray-900 dark:text-white">Profile</p>
                                <p class="text-[8px] text-gray-400 dark:text-[#444]">Email & account info</p>
                            </div>
                        </div>
                        <div class="nav-item" data-section="security" onclick="switchSection('security', this)">
                            <div class="w-8 h-8 rounded-lg inner-card flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-shield text-[10px] text-gray-400 dark:text-[#555]"></i>
                            </div>
                            <div>
                                <p class="text-[11px] font-bold text-gray-700 dark:text-[#ccc]">Security</p>
                                <p class="text-[8px] text-gray-400 dark:text-[#444]">Password & authentication</p>
                            </div>
                        </div>
                        <div class="nav-item" data-section="preferences" onclick="switchSection('preferences', this)">
                            <div class="w-8 h-8 rounded-lg inner-card flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-sliders text-[10px] text-gray-400 dark:text-[#555]"></i>
                            </div>
                            <div>
                                <p class="text-[11px] font-bold text-gray-700 dark:text-[#ccc]">Preferences</p>
                                <p class="text-[8px] text-gray-400 dark:text-[#444]">Appearance & notifications</p>
                            </div>
                        </div>
                        <div class="nav-item" data-section="danger" onclick="switchSection('danger', this)">
                            <div class="w-8 h-8 rounded-lg bg-red-500/10 flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-triangle-exclamation text-[10px] text-red-400"></i>
                            </div>
                            <div>
                                <p class="text-[11px] font-bold text-red-500 dark:text-red-400">Danger Zone</p>
                                <p class="text-[8px] text-gray-400 dark:text-[#444]">Delete account</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ══════════ RIGHT CONTENT ══════════ -->
            <div class="lg:col-span-8 flex flex-col gap-6">

                <!-- ── MOBILE SECTION TABS ── -->
                <div class="lg:hidden flex gap-2 overflow-x-auto pb-1 -mx-1 px-1">
                    <button onclick="switchSection('profile', this)"
                        class="mobile-tab active shrink-0 px-3.5 py-2 rounded-xl text-[9px] font-bold uppercase tracking-wider border transition flex items-center gap-2 bg-blue-500/10 dark:bg-blue-500/15 text-blue-500 dark:text-blue-400 border-blue-500/20">
                        <i class="fa-solid fa-user text-[8px]"></i> Profile
                    </button>
                    <button onclick="switchSection('security', this)"
                        class="mobile-tab shrink-0 px-3.5 py-2 rounded-xl text-[9px] font-bold uppercase tracking-wider border transition flex items-center gap-2 bg-white dark:bg-[#161616] text-gray-400 dark:text-[#555] border-gray-200 dark:border-[#222]">
                        <i class="fa-solid fa-shield text-[8px]"></i> Security
                    </button>
                    <button onclick="switchSection('preferences', this)"
                        class="mobile-tab shrink-0 px-3.5 py-2 rounded-xl text-[9px] font-bold uppercase tracking-wider border transition flex items-center gap-2 bg-white dark:bg-[#161616] text-gray-400 dark:text-[#555] border-gray-200 dark:border-[#222]">
                        <i class="fa-solid fa-sliders text-[8px]"></i> Prefs
                    </button>
                    <button onclick="switchSection('danger', this)"
                        class="mobile-tab shrink-0 px-3.5 py-2 rounded-xl text-[9px] font-bold uppercase tracking-wider border transition flex items-center gap-2 bg-white dark:bg-[#161616] text-gray-400 dark:text-[#555] border-gray-200 dark:border-[#222]">
                        <i class="fa-solid fa-triangle-exclamation text-[8px]"></i> Danger
                    </button>
                </div>

                <!-- ══════════ SECTION: PROFILE ══════════ -->
                <div id="section-profile" class="settings-section">
                    <div class="glass-card p-5 sm:p-6 rounded-[1.5rem]">
                        <div class="flex items-center gap-2.5 mb-5">
                            <div
                                class="w-8 h-8 rounded-lg bg-blue-500/10 dark:bg-blue-500/15 flex items-center justify-center">
                                <i class="fa-solid fa-user text-[10px] text-blue-500 dark:text-blue-400"></i>
                            </div>
                            <div>
                                <span
                                    class="text-[9px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#555]">Account
                                    Information</span>
                                <p class="text-[8px] text-gray-300 dark:text-[#444]">View your account details</p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="space-y-1.5">
                                <label
                                    class="text-[9px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444] ml-1">Email
                                    Address</label>
                                <div class="relative">
                                    <i
                                        class="fa-solid fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-[11px] text-gray-300 dark:text-[#444]"></i>
                                    <input type="email" value="{{ Auth::user()->email }}" readonly
                                        class="form-input-locked w-full rounded-xl pl-11 pr-24 py-3 text-[13px]">
                                    <div class="absolute right-4 top-1/2 -translate-y-1/2 flex items-center gap-1.5">
                                        <i class="fa-solid fa-lock text-[8px] text-gray-300 dark:text-[#333]"></i>
                                        <span
                                            class="text-[8px] font-bold uppercase tracking-wider text-gray-300 dark:text-[#333]">Locked</span>
                                    </div>
                                </div>
                                <p class="text-[8px] text-gray-300 dark:text-[#333] ml-1">Email cannot be changed.
                                    Contact support if you need to update it.</p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="space-y-1.5">
                                    <label
                                        class="text-[9px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444] ml-1">Role</label>
                                    <div class="form-input-locked w-full rounded-xl px-4 py-3 text-[13px] capitalize">
                                        {{ str_replace('_', ' ', Auth::user()->roles[0]->name ?? 'user') }}
                                    </div>
                                </div>
                                <div class="space-y-1.5">
                                    <label
                                        class="text-[9px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444] ml-1">Member
                                        Since</label>
                                    <div class="form-input-locked w-full rounded-xl px-4 py-3 text-[13px]">
                                        {{ Auth::user()->created_at->format('M d, Y') }}
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-1.5">
                                <label
                                    class="text-[9px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444] ml-1">Email
                                    Verification</label>
                                <div
                                    class="setting-row flex items-center justify-between p-4 rounded-xl inner-card border">
                                    <div class="flex items-center gap-3">
                                        @if (Auth::user()->email_verified_at)
                                            <div
                                                class="w-8 h-8 rounded-lg bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center">
                                                <i
                                                    class="fa-solid fa-circle-check text-[10px] text-emerald-500 dark:text-emerald-400"></i>
                                            </div>
                                            <div>
                                                <p
                                                    class="text-[11px] font-bold text-emerald-500 dark:text-emerald-400">
                                                    Verified</p>
                                                <p class="text-[8px] text-gray-400 dark:text-[#444]">Your email has
                                                    been verified</p>
                                            </div>
                                        @else
                                            <div
                                                class="w-8 h-8 rounded-lg bg-amber-500/10 border border-amber-500/20 flex items-center justify-center">
                                                <i class="fa-solid fa-clock text-[10px] text-amber-500"></i>
                                            </div>
                                            <div>
                                                <p class="text-[11px] font-bold text-amber-500">Pending</p>
                                                <p class="text-[8px] text-gray-400 dark:text-[#444]">Check your inbox
                                                    for verification link</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ══════════ SECTION: SECURITY ══════════ -->
                <div id="section-security" class="settings-section hidden">
                    <form action="{{ route('profile.update') }}" method="POST">
                        @csrf @method('PUT')

                        <div class="glass-card p-5 sm:p-6 rounded-[1.5rem]">
                            <div class="flex items-center gap-2.5 mb-5">
                                <div
                                    class="w-8 h-8 rounded-lg bg-red-500/10 border border-red-500/15 flex items-center justify-center">
                                    <i class="fa-solid fa-key text-[10px] text-red-400"></i>
                                </div>
                                <div>
                                    <span
                                        class="text-[9px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#555]">Change
                                        Password</span>
                                    <p class="text-[8px] text-gray-300 dark:text-[#444]">Update your account password
                                    </p>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div class="space-y-1.5">
                                    <label
                                        class="text-[9px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444] ml-1">Current
                                        Password</label>
                                    <div class="relative">
                                        <i
                                            class="fa-solid fa-shield absolute left-4 top-1/2 -translate-y-1/2 text-[11px] text-gray-300 dark:text-[#444]"></i>
                                        <input type="password" name="current_password"
                                            placeholder="Enter current password"
                                            class="form-input w-full rounded-xl pl-11 pr-11 py-3 text-[13px]">
                                        <button type="button" onclick="togglePassword(this)"
                                            class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-300 dark:text-[#555] hover:text-gray-500 dark:hover:text-[#888] transition cursor-pointer bg-transparent border-none">
                                            <i class="fa-solid fa-eye text-[11px]"></i>
                                        </button>
                                    </div>
                                    @error('current_password')
                                        <p class="text-[9px] text-red-400 mt-1 ml-1 flex items-center gap-1.5">
                                            <i class="fa-solid fa-circle-exclamation text-[8px]"></i> {{ $message }}
                                        </p>
                                    @enderror
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div class="space-y-1.5">
                                        <label
                                            class="text-[9px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444] ml-1">New
                                            Password</label>
                                        <div class="relative">
                                            <i
                                                class="fa-solid fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-[11px] text-gray-300 dark:text-[#444]"></i>
                                            <input type="password" name="password" placeholder="Min. 8 characters"
                                                class="form-input w-full rounded-xl pl-11 pr-11 py-3 text-[13px]">
                                            <button type="button" onclick="togglePassword(this)"
                                                class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-300 dark:text-[#555] hover:text-gray-500 dark:hover:text-[#888] transition cursor-pointer bg-transparent border-none">
                                                <i class="fa-solid fa-eye text-[11px]"></i>
                                            </button>
                                        </div>
                                        @error('password')
                                            <p class="text-[9px] text-red-400 mt-1 ml-1 flex items-center gap-1.5">
                                                <i class="fa-solid fa-circle-exclamation text-[8px]"></i>
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </div>
                                    <div class="space-y-1.5">
                                        <label
                                            class="text-[9px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444] ml-1">Confirm
                                            Password</label>
                                        <div class="relative">
                                            <i
                                                class="fa-solid fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-[11px] text-gray-300 dark:text-[#444]"></i>
                                            <input type="password" name="password_confirmation"
                                                placeholder="Repeat password"
                                                class="form-input w-full rounded-xl pl-11 pr-11 py-3 text-[13px]">
                                            <button type="button" onclick="togglePassword(this)"
                                                class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-300 dark:text-[#555] hover:text-gray-500 dark:hover:text-[#888] transition cursor-pointer bg-transparent border-none">
                                                <i class="fa-solid fa-eye text-[11px]"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Password Requirements -->
                        <div class="glass-card p-5 rounded-[1.5rem] mt-4">
                            <div class="flex items-center gap-2.5 mb-4">
                                <div class="w-8 h-8 rounded-lg inner-card flex items-center justify-center">
                                    <i class="fa-solid fa-list-check text-[10px] text-gray-400 dark:text-[#555]"></i>
                                </div>
                                <span
                                    class="text-[9px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#555]">Password
                                    Requirements</span>
                            </div>
                            <div class="grid grid-cols-2 gap-2.5">
                                <div class="flex items-center gap-2.5">
                                    <div
                                        class="w-5 h-5 rounded-md bg-emerald-500/10 border border-emerald-500/15 flex items-center justify-center shrink-0">
                                        <i
                                            class="fa-solid fa-check text-[7px] text-emerald-500 dark:text-emerald-400"></i>
                                    </div>
                                    <span class="text-[10px] text-gray-500 dark:text-[#666]">8+ characters</span>
                                </div>
                                <div class="flex items-center gap-2.5">
                                    <div
                                        class="w-5 h-5 rounded-md bg-emerald-500/10 border border-emerald-500/15 flex items-center justify-center shrink-0">
                                        <i
                                            class="fa-solid fa-check text-[7px] text-emerald-500 dark:text-emerald-400"></i>
                                    </div>
                                    <span class="text-[10px] text-gray-500 dark:text-[#666]">One uppercase</span>
                                </div>
                                <div class="flex items-center gap-2.5">
                                    <div
                                        class="w-5 h-5 rounded-md bg-emerald-500/10 border border-emerald-500/15 flex items-center justify-center shrink-0">
                                        <i
                                            class="fa-solid fa-check text-[7px] text-emerald-500 dark:text-emerald-400"></i>
                                    </div>
                                    <span class="text-[10px] text-gray-500 dark:text-[#666]">One number</span>
                                </div>
                                <div class="flex items-center gap-2.5">
                                    <div
                                        class="w-5 h-5 rounded-md inner-card flex items-center justify-center shrink-0">
                                        <i class="fa-solid fa-circle text-[4px] text-gray-300 dark:text-[#444]"></i>
                                    </div>
                                    <span class="text-[10px] text-gray-400 dark:text-[#444]">Must match</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 mt-4">
                            <a href="{{ route('profile') }}"
                                class="text-[9px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444] hover:text-gray-900 dark:hover:text-white transition flex items-center gap-2">
                                <i class="fa-solid fa-xmark text-[10px]"></i> Cancel
                            </a>
                            <button type="submit"
                                class="bg-blue-600 text-white px-6 py-3 rounded-xl text-[10px] font-bold uppercase tracking-wider hover:bg-blue-500 transition shadow-lg shadow-blue-600/20 active:scale-[0.98] flex items-center gap-2">
                                <i class="fa-solid fa-check text-[9px]"></i> Update Password
                            </button>
                        </div>
                    </form>
                </div>

                <!-- ══════════ SECTION: PREFERENCES ══════════ -->
                <div id="section-preferences" class="settings-section hidden">
                    <div class="glass-card p-5 sm:p-6 rounded-[1.5rem]">
                        <div class="flex items-center gap-2.5 mb-5">
                            <div
                                class="w-8 h-8 rounded-lg bg-purple-500/10 border border-purple-500/15 flex items-center justify-center">
                                <i class="fa-solid fa-palette text-[10px] text-purple-500 dark:text-purple-400"></i>
                            </div>
                            <div>
                                <span
                                    class="text-[9px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#555]">Appearance</span>
                                <p class="text-[8px] text-gray-300 dark:text-[#444]">Customize how SmartCommute looks
                                </p>
                            </div>
                        </div>

                        <div class="space-y-1">
                            <div class="setting-row flex items-center justify-between p-4 rounded-xl">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-lg bg-amber-500/10 flex items-center justify-center"
                                        id="theme-icon-wrap">
                                        <i class="fa-solid fa-sun text-[11px] text-amber-500" id="theme-icon"></i>
                                    </div>
                                    <div>
                                        <p class="text-[11px] font-bold text-gray-900 dark:text-white">Dark Mode</p>
                                        <p class="text-[8px] text-gray-400 dark:text-[#444]">Switch between light and
                                            dark theme</p>
                                    </div>
                                </div>
                                <div class="toggle-track" id="theme-toggle" onclick="toggleTheme()">
                                    <div class="toggle-thumb"></div>
                                </div>
                            </div>

                            <div class="setting-row flex items-center justify-between p-4 rounded-xl">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-lg inner-card flex items-center justify-center">
                                        <i class="fa-solid fa-mobile text-[11px] text-gray-400 dark:text-[#555]"></i>
                                    </div>
                                    <div>
                                        <p class="text-[11px] font-bold text-gray-900 dark:text-white">Compact Mode</p>
                                        <p class="text-[8px] text-gray-400 dark:text-[#444]">Reduce spacing and padding
                                        </p>
                                    </div>
                                </div>
                                <div class="toggle-track" onclick="this.classList.toggle('active')">
                                    <div class="toggle-thumb"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="glass-card p-5 sm:p-6 rounded-[1.5rem] mt-4">
                        <div class="flex items-center gap-2.5 mb-5">
                            <div
                                class="w-8 h-8 rounded-lg bg-blue-500/10 border border-blue-500/15 flex items-center justify-center">
                                <i class="fa-solid fa-bell text-[10px] text-blue-500 dark:text-blue-400"></i>
                            </div>
                            <div>
                                <span
                                    class="text-[9px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#555]">Notifications</span>
                                <p class="text-[8px] text-gray-300 dark:text-[#444]">Manage your notification
                                    preferences</p>
                            </div>
                        </div>

                        <div class="space-y-1">
                            <div class="setting-row flex items-center justify-between p-4 rounded-xl">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-lg bg-emerald-500/10 flex items-center justify-center">
                                        <i
                                            class="fa-solid fa-envelope text-[11px] text-emerald-500 dark:text-emerald-400"></i>
                                    </div>
                                    <div>
                                        <p class="text-[11px] font-bold text-gray-900 dark:text-white">Email
                                            Notifications</p>
                                        <p class="text-[8px] text-gray-400 dark:text-[#444]">Receipts and account
                                            updates</p>
                                    </div>
                                </div>
                                <div class="toggle-track active" onclick="this.classList.toggle('active')">
                                    <div class="toggle-thumb"></div>
                                </div>
                            </div>

                            <div class="setting-row flex items-center justify-between p-4 rounded-xl">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-lg bg-blue-500/10 flex items-center justify-center">
                                        <i class="fa-solid fa-route text-[11px] text-blue-500 dark:text-blue-400"></i>
                                    </div>
                                    <div>
                                        <p class="text-[11px] font-bold text-gray-900 dark:text-white">Trip Reminders
                                        </p>
                                        <p class="text-[8px] text-gray-400 dark:text-[#444]">Upcoming scheduled rides
                                        </p>
                                    </div>
                                </div>
                                <div class="toggle-track active" onclick="this.classList.toggle('active')">
                                    <div class="toggle-thumb"></div>
                                </div>
                            </div>

                            <div class="setting-row flex items-center justify-between p-4 rounded-xl">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-lg bg-amber-500/10 flex items-center justify-center">
                                        <i class="fa-solid fa-wallet text-[11px] text-amber-500"></i>
                                    </div>
                                    <div>
                                        <p class="text-[11px] font-bold text-gray-900 dark:text-white">Low Balance
                                            Alert</p>
                                        <p class="text-[8px] text-gray-400 dark:text-[#444]">Notify when balance is low
                                        </p>
                                    </div>
                                </div>
                                <div class="toggle-track active" onclick="this.classList.toggle('active')">
                                    <div class="toggle-thumb"></div>
                                </div>
                            </div>

                            <div class="setting-row flex items-center justify-between p-4 rounded-xl">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-lg inner-card flex items-center justify-center">
                                        <i class="fa-solid fa-bullhorn text-[11px] text-gray-400 dark:text-[#555]"></i>
                                    </div>
                                    <div>
                                        <p class="text-[11px] font-bold text-gray-900 dark:text-white">Promotions</p>
                                        <p class="text-[8px] text-gray-400 dark:text-[#444]">Discounts and special
                                            offers</p>
                                    </div>
                                </div>
                                <div class="toggle-track" onclick="this.classList.toggle('active')">
                                    <div class="toggle-thumb"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- ══════════ SECTION: DANGER ZONE ══════════ -->
                <div id="section-danger" class="settings-section hidden">
                    <div class="glass-card p-5 sm:p-6 rounded-[1.5rem] border-red-500/20">
                        <div class="flex items-center gap-2.5 mb-5">
                            <div
                                class="w-8 h-8 rounded-lg bg-red-500/10 border border-red-500/15 flex items-center justify-center">
                                <i class="fa-solid fa-triangle-exclamation text-[10px] text-red-400"></i>
                            </div>
                            <div>
                                <span
                                    class="text-[9px] font-bold uppercase tracking-[0.15em] text-red-500 dark:text-red-400">Danger
                                    Zone</span>
                                <p class="text-[8px] text-gray-400 dark:text-[#444]">Irreversible actions</p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="p-4 rounded-xl border border-red-500/20 bg-red-500/5">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-[11px] font-bold text-gray-900 dark:text-white mb-1">Delete
                                            Account</p>
                                        <p class="text-[9px] text-gray-400 dark:text-[#444] leading-relaxed">
                                            Permanently delete your account and all associated data including travel
                                            history, wallet balance, and personal information. This action cannot be
                                            undone.</p>
                                    </div>
                                </div>
                                <button onclick="document.getElementById('delete-modal').classList.add('active')"
                                    class="mt-4 px-4 py-2.5 rounded-xl border border-red-500/30 text-red-500 dark:text-red-400 text-[9px] font-bold uppercase tracking-wider hover:bg-red-500/10 transition flex items-center gap-2">
                                    <i class="fa-solid fa-trash-can text-[8px]"></i> Delete My Account
                                </button>
                            </div>

                            <div class="p-4 rounded-xl inner-card border">
                                <div class="flex items-start gap-4">
                                    <div
                                        class="w-9 h-9 rounded-lg bg-amber-500/10 flex items-center justify-center shrink-0">
                                        <i class="fa-solid fa-clock-rotate-left text-[11px] text-amber-500"></i>
                                    </div>
                                    <div>
                                        <p class="text-[11px] font-bold text-gray-900 dark:text-white mb-1">Request
                                            Data Export</p>
                                        <p class="text-[9px] text-gray-400 dark:text-[#444] leading-relaxed">Download a
                                            copy of all your data including trips, payments, and account information.
                                        </p>
                                    </div>
                                </div>
                                <button
                                    class="mt-4 px-4 py-2.5 rounded-xl border border-gray-200 dark:border-[#222] text-gray-500 dark:text-[#666] text-[9px] font-bold uppercase tracking-wider hover:bg-gray-50 dark:hover:bg-[#1a1a1a] transition flex items-center gap-2">
                                    <i class="fa-solid fa-download text-[8px]"></i> Request Export
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- ══════════ DELETE ACCOUNT MODAL ══════════ -->
    <div id="delete-modal"
        class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 dark:bg-black/70 opacity-0 pointer-events-none transition-opacity duration-300">
        <div class="glass-panel p-7 sm:p-8 rounded-[2rem] w-full max-w-[400px] mx-4 text-center transform scale-95 opacity-0 transition-all duration-[350ms]"
            id="delete-modal-content">
            <div
                class="w-14 h-14 bg-red-500/10 rounded-2xl flex items-center justify-center mx-auto mb-5 border border-red-500/20">
                <i class="fa-solid fa-triangle-exclamation text-red-400 text-lg"></i>
            </div>
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1.5">Delete Account?</h3>
            <p class="text-xs text-gray-500 dark:text-[#666] mb-3 leading-relaxed">This will permanently delete your
                account and all data.</p>
            <div class="bg-red-500/5 border border-red-500/15 rounded-xl p-3 mb-6">
                <p class="text-[9px] text-red-500 dark:text-red-400 font-bold">• All travel history will be lost</p>
                <p class="text-[9px] text-red-500 dark:text-red-400 font-bold mt-1">• Wallet balance cannot be
                    recovered</p>
                <p class="text-[9px] text-red-500 dark:text-red-400 font-bold mt-1">• This action is irreversible</p>
            </div>
            <div class="space-y-2">
                <div class="relative">
                    <input type="text" id="delete-confirm-input" placeholder='Type "DELETE" to confirm'
                        class="form-input w-full rounded-xl px-4 py-3 text-[12px] text-center font-mono uppercase tracking-widest">
                </div>
                <button id="delete-confirm-btn" onclick="handleDelete()" disabled
                    class="w-full px-5 py-3 rounded-xl bg-red-600/50 text-white/50 text-[10px] font-bold uppercase tracking-widest cursor-not-allowed transition flex items-center justify-center gap-2">
                    <i class="fa-solid fa-trash-can text-[9px]"></i> Delete My Account
                </button>
            </div>
            <button onclick="closeDeleteModal()"
                class="mt-3 text-[10px] font-bold text-gray-400 dark:text-[#555] hover:text-gray-900 dark:hover:text-white transition">
                Cancel
            </button>
        </div>
    </div>

    <!-- ══════════ SCRIPTS ══════════ -->
    <script>
        // Section switching
        function switchSection(sectionId, clickedBtn) {
            // Hide all sections
            document.querySelectorAll('.settings-section').forEach(s => s.classList.add('hidden'));
            // Show target
            document.getElementById('section-' + sectionId).classList.remove('hidden');

            // Update desktop nav
            document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
            const desktopNav = document.querySelector(`.nav-item[data-section="${sectionId}"]`);
            if (desktopNav) desktopNav.classList.add('active');

            // Update mobile tabs
            document.querySelectorAll('.mobile-tab').forEach(t => {
                t.classList.remove('active', 'bg-blue-500/10', 'dark:bg-blue-500/15', 'text-blue-500',
                    'dark:text-blue-400', 'border-blue-500/20');
                t.classList.add('bg-white', 'dark:bg-[#161616]', 'text-gray-400', 'dark:text-[#555]',
                    'border-gray-200', 'dark:border-[#222]');
            });
            if (clickedBtn && clickedBtn.classList.contains('mobile-tab')) {
                clickedBtn.classList.add('active', 'bg-blue-500/10', 'dark:bg-blue-500/15', 'text-blue-500',
                    'dark:text-blue-400', 'border-blue-500/20');
                clickedBtn.classList.remove('bg-white', 'dark:bg-[#161616]', 'text-gray-400', 'dark:text-[#555]',
                    'border-gray-200', 'dark:border-[#222]');
            }
        }

        // Password toggle
        function togglePassword(btn) {
            const input = btn.parentElement.querySelector('input');
            const icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        // Theme label
        function updateThemeLabel() {
            const label = document.getElementById('theme-label');
            if (label) {
                label.textContent = document.documentElement.classList.contains('dark') ? 'Dark' : 'Light';
            }
        }
        updateThemeLabel();
        window.addEventListener('storage', updateThemeLabel);

        // Delete modal
        function closeDeleteModal() {
            const modal = document.getElementById('delete-modal');
            const content = document.getElementById('delete-modal-content');
            modal.style.opacity = '0';
            modal.style.pointerEvents = 'none';
            content.style.transform = 'scale(0.95)';
            content.style.opacity = '0';
            document.getElementById('delete-confirm-input').value = '';
            updateDeleteBtn();
        }

        document.getElementById('delete-confirm-input').addEventListener('input', updateDeleteBtn);

        function updateDeleteBtn() {
            const input = document.getElementById('delete-confirm-input');
            const btn = document.getElementById('delete-confirm-btn');
            if (input.value.toUpperCase() === 'DELETE') {
                btn.disabled = false;
                btn.classList.remove('bg-red-600/50', 'text-white/50', 'cursor-not-allowed');
                btn.classList.add('bg-red-600', 'text-white', 'cursor-pointer', 'hover:bg-red-700', 'active:scale-[0.98]');
            } else {
                btn.disabled = true;
                btn.classList.add('bg-red-600/50', 'text-white/50', 'cursor-not-allowed');
                btn.classList.remove('bg-red-600', 'text-white', 'cursor-pointer', 'hover:bg-red-700',
                    'active:scale-[0.98]');
            }
        }

        function handleDelete() {
            // Submit delete request here
            alert('Account deletion would be processed here.');
            closeDeleteModal();
        }

        function toggleTheme() {
            var isDark = document.documentElement.classList.toggle('dark');
            var theme = isDark ? 'dark' : 'light';
            localStorage.setItem('color-theme', theme);
            updateThemeUI(isDark);

            fetch('{{ route('settings.update.theme') }}', {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                },
                body: JSON.stringify({
                    theme: theme
                })
            }).catch(function() {
                document.documentElement.classList.toggle('dark');
                localStorage.setItem('color-theme', isDark ? 'light' : 'dark');
                updateThemeUI(!isDark);
            });

            return isDark;
        }

        function updateThemeUI(isDark) {
            var toggle = document.getElementById('theme-toggle');
            var icon = document.getElementById('theme-icon');
            var iconWrap = document.getElementById('theme-icon-wrap');

            if (isDark) {
                toggle.classList.add('active');
                icon.classList.remove('fa-sun', 'text-amber-500');
                icon.classList.add('fa-moon', 'text-blue-400');
                iconWrap.classList.remove('bg-amber-500/10');
                iconWrap.classList.add('bg-blue-500/10');
            } else {
                toggle.classList.remove('active');
                icon.classList.remove('fa-moon', 'text-blue-400');
                icon.classList.add('fa-sun', 'text-amber-500');
                iconWrap.classList.remove('bg-blue-500/10');
                iconWrap.classList.add('bg-amber-500/10');
            }
        }

        // Init theme toggle state on load
        (function() {
            var isDark = document.documentElement.classList.contains('dark');
            updateThemeUI(isDark);
        })();

        // Listen for changes from other tabs (e.g., map page)
        window.addEventListener('storage', function(e) {
            if (e.key === 'color-theme') {
                var isDark = e.newValue === 'dark';
                if (isDark) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
                updateThemeUI(isDark);
            }
        });
    </script>

</body>

</html>
