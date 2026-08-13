<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartCommute | Edit Profile</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: { extend: { fontFamily: { sans: ['Inter', 'sans-serif'] } } }
        }
    </script>
    <style>
        body, html { margin: 0; padding: 0; height: 100%; width: 100%; font-family: 'Inter', sans-serif; overflow-x: hidden; background: #050505; }
        .glass-panel { background: #111111 !important; border: 1px solid #1e1e1e; box-shadow: 0 4px 24px rgba(0,0,0,0.6); }
        .glass-card { background: #161616; border: 1px solid #222222; box-shadow: 0 4px 24px rgba(0,0,0,0.5); }
        .header-btn { transition: all 0.3s ease; }
        .header-btn:hover { background: #1a1a1a !important; border-color: #333 !important; }

        input::placeholder { color: #333; }
        input:focus { border-color: rgba(59, 130, 246, 0.4) !important; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.08); }

        ::-webkit-scrollbar { width: 3px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #333; border-radius: 10px; }
    </style>
</head>

<body class="antialiased text-white">

    @include('components.flash')

    <!-- ══════════ HEADER ══════════ -->
    <header class="fixed top-4 left-4 right-4 sm:top-5 sm:left-5 sm:right-5 z-50 flex items-center justify-between gap-3 pointer-events-none">
        <div class="glass-panel p-3 sm:p-3.5 rounded-2xl pointer-events-auto flex items-center gap-3 min-w-0">
            <a href="{{ route('profile') }}" class="header-btn w-9 h-9 rounded-xl flex items-center justify-center shrink-0 border border-[#1e1e1e] bg-[#111] hover:bg-[#1a1a1a] transition cursor-pointer">
                <i class="fa-solid fa-arrow-left text-[10px] text-[#666]"></i>
            </a>
            <div class="w-px h-6 bg-[#222] mx-0.5 hidden sm:block"></div>
            <div class="w-8 h-8 sm:w-9 sm:h-9 bg-blue-600 rounded-lg sm:rounded-xl flex items-center justify-center shrink-0">
                <i class="fa-solid fa-bus text-white text-xs sm:text-sm"></i>
            </div>
            <span class="text-[13px] sm:text-sm font-bold tracking-tight text-white whitespace-nowrap">Smart<span class="text-blue-400">Commute</span></span>
            <div class="w-px h-6 bg-[#222] mx-0.5 hidden sm:block"></div>
            <span class="text-[9px] sm:text-[10px] font-bold uppercase tracking-[0.15em] text-[#555] hidden sm:inline">Edit Profile</span>
        </div>
    </header>

    <!-- ══════════ MAIN CONTENT ══════════ -->
    <div class="pt-20 sm:pt-24 pb-8 sm:pb-10 max-w-6xl mx-auto px-4 sm:px-6 lg:px-5">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            <!-- ══════════ LEFT SIDEBAR (desktop only) ══════════ -->
            <div class="hidden lg:flex lg:col-span-4 flex-col gap-6">

                <!-- Pro Tip -->
                <div class="glass-card p-6 rounded-[1.5rem]">
                    <div class="flex items-center gap-2.5 mb-4">
                        <div class="w-8 h-8 bg-blue-500/15 rounded-lg flex items-center justify-center border border-blue-500/20">
                            <i class="fa-solid fa-lightbulb text-blue-400 text-[10px]"></i>
                        </div>
                        <span class="text-[9px] font-bold uppercase tracking-[0.15em] text-[#555]">Pro Tip</span>
                    </div>
                    <p class="text-[11px] text-[#666] leading-relaxed">Use a unique password that you don't use for other online services. This keeps your commuter account secure.</p>
                </div>

                <!-- Password Requirements -->
                <div class="glass-card p-6 rounded-[1.5rem]">
                    <div class="flex items-center gap-2.5 mb-4">
                        <div class="w-8 h-8 rounded-lg bg-[#111] border border-[#1e1e1e] flex items-center justify-center">
                            <i class="fa-solid fa-list-check text-[10px] text-[#555]"></i>
                        </div>
                        <span class="text-[9px] font-bold uppercase tracking-[0.15em] text-[#555]">Password Rules</span>
                    </div>
                    <div class="space-y-2.5">
                        <div class="flex items-center gap-2.5">
                            <div class="w-5 h-5 rounded-md bg-emerald-500/10 border border-emerald-500/15 flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-check text-[7px] text-emerald-400"></i>
                            </div>
                            <span class="text-[10px] text-[#666]">Minimum 8 characters</span>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <div class="w-5 h-5 rounded-md bg-emerald-500/10 border border-emerald-500/15 flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-check text-[7px] text-emerald-400"></i>
                            </div>
                            <span class="text-[10px] text-[#666]">At least one uppercase letter</span>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <div class="w-5 h-5 rounded-md bg-emerald-500/10 border border-emerald-500/15 flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-check text-[7px] text-emerald-400"></i>
                            </div>
                            <span class="text-[10px] text-[#666]">At least one number</span>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <div class="w-5 h-5 rounded-md bg-[#111] border border-[#1e1e1e] flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-circle text-[4px] text-[#444]"></i>
                            </div>
                            <span class="text-[10px] text-[#444]">Must match confirmation</span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- ══════════ RIGHT CONTENT ══════════ -->
            <div class="lg:col-span-8 flex flex-col gap-6">

                <form id="edit-form" action="{{ route('profile.update') }}" method="POST" class="flex flex-col gap-6">
                    @csrf
                    @method('PUT')

                    <!-- ── Mobile: inline tip ── -->
                    <div class="lg:hidden flex items-start gap-2.5 p-3.5 rounded-xl bg-blue-500/5 border border-blue-500/10">
                        <i class="fa-solid fa-lightbulb text-blue-400 text-[10px] mt-0.5 shrink-0"></i>
                        <p class="text-[9px] text-[#555] leading-relaxed">Use a unique password that you don't use for other online services.</p>
                    </div>

                    <!-- ── General Information ── -->
                    <div class="glass-card p-5 sm:p-6 rounded-[1.5rem]">
                        <div class="flex items-center gap-2.5 mb-5">
                            <div class="w-8 h-8 rounded-lg bg-[#111] border border-[#1e1e1e] flex items-center justify-center">
                                <i class="fa-solid fa-user text-[10px] text-[#555]"></i>
                            </div>
                            <span class="text-[9px] font-bold uppercase tracking-[0.15em] text-[#555]">General Information</span>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-[9px] font-bold uppercase tracking-[0.15em] text-[#444] ml-1">Email Address</label>
                            <div class="relative">
                                <i class="fa-solid fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-[11px] text-[#444]"></i>
                                <input type="email" name="email" value="{{ Auth::user()->email }}" readonly
                                    class="w-full bg-[#111] border border-[#1e1e1e] rounded-xl pl-11 pr-4 py-3 text-[13px] text-[#888] outline-none transition cursor-not-allowed select-none">
                                <div class="absolute right-4 top-1/2 -translate-y-1/2 flex items-center gap-1.5">
                                    <i class="fa-solid fa-lock text-[8px] text-[#333]"></i>
                                    <span class="text-[8px] font-bold uppercase tracking-wider text-[#333]">Locked</span>
                                </div>
                            </div>
                            <p class="text-[8px] text-[#333] ml-1">Email cannot be changed. Contact support if you need to update it.</p>
                        </div>
                    </div>

                    <!-- ── Update Password ── -->
                    <div class="glass-card p-5 sm:p-6 rounded-[1.5rem]">
                        <div class="flex items-center gap-2.5 mb-5">
                            <div class="w-8 h-8 rounded-lg bg-red-500/10 border border-red-500/15 flex items-center justify-center">
                                <i class="fa-solid fa-key text-[10px] text-red-400"></i>
                            </div>
                            <span class="text-[9px] font-bold uppercase tracking-[0.15em] text-[#555]">Update Password</span>
                        </div>

                        <div class="space-y-4">

                            <!-- Current Password -->
                            <div class="space-y-1.5">
                                <label class="text-[9px] font-bold uppercase tracking-[0.15em] text-[#444] ml-1">Current Password</label>
                                <div class="relative">
                                    <i class="fa-solid fa-shield-halved absolute left-4 top-1/2 -translate-y-1/2 text-[11px] text-[#444]"></i>
                                    <input type="password" name="current_password" placeholder="Enter current password"
                                        class="w-full bg-[#111] border border-[#1e1e1e] rounded-xl pl-11 pr-11 py-3 text-[13px] text-white outline-none transition">
                                    <button type="button" onclick="togglePassword(this)" class="absolute right-4 top-1/2 -translate-y-1/2 text-[#555] hover:text-[#888] transition cursor-pointer">
                                        <i class="fa-solid fa-eye text-[11px]"></i>
                                    </button>
                                </div>
                                @error('current_password')
                                    <p class="text-[9px] text-red-400 mt-1 ml-1 flex items-center gap-1.5">
                                        <i class="fa-solid fa-circle-exclamation text-[8px]"></i>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <!-- New Password + Confirm -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="space-y-1.5">
                                    <label class="text-[9px] font-bold uppercase tracking-[0.15em] text-[#444] ml-1">New Password</label>
                                    <div class="relative">
                                        <i class="fa-solid fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-[11px] text-[#444]"></i>
                                        <input type="password" name="password" placeholder="Min. 8 characters"
                                            class="w-full bg-[#111] border border-[#1e1e1e] rounded-xl pl-11 pr-11 py-3 text-[13px] text-white outline-none transition">
                                        <button type="button" onclick="togglePassword(this)" class="absolute right-4 top-1/2 -translate-y-1/2 text-[#555] hover:text-[#888] transition cursor-pointer">
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
                                    <label class="text-[9px] font-bold uppercase tracking-[0.15em] text-[#444] ml-1">Confirm Password</label>
                                    <div class="relative">
                                        <i class="fa-solid fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-[11px] text-[#444]"></i>
                                        <input type="password" name="password_confirmation" placeholder="Repeat password"
                                            class="w-full bg-[#111] border border-[#1e1e1e] rounded-xl pl-11 pr-11 py-3 text-[13px] text-white outline-none transition">
                                        <button type="button" onclick="togglePassword(this)" class="absolute right-4 top-1/2 -translate-y-1/2 text-[#555] hover:text-[#888] transition cursor-pointer">
                                            <i class="fa-solid fa-eye text-[11px]"></i>
                                        </button>
                                    </div>
                                    @error('password_confirmation')
                                        <p class="text-[9px] text-red-400 mt-1 ml-1 flex items-center gap-1.5">
                                            <i class="fa-solid fa-circle-exclamation text-[8px]"></i>
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Mobile: password rules inline -->
                            <div class="lg:hidden pt-3 border-t border-[#1e1e1e]">
                                <p class="text-[8px] font-bold uppercase tracking-[0.15em] text-[#333] mb-2 ml-1">Requirements</p>
                                <div class="flex flex-wrap gap-x-4 gap-y-1.5">
                                    <span class="text-[9px] text-[#444] flex items-center gap-1.5">
                                        <i class="fa-solid fa-check text-[6px] text-emerald-500/60"></i> 8+ characters
                                    </span>
                                    <span class="text-[9px] text-[#444] flex items-center gap-1.5">
                                        <i class="fa-solid fa-check text-[6px] text-emerald-500/60"></i> Uppercase
                                    </span>
                                    <span class="text-[9px] text-[#444] flex items-center gap-1.5">
                                        <i class="fa-solid fa-check text-[6px] text-emerald-500/60"></i> Number
                                    </span>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- ── Actions ── -->
                    <div class="flex items-center justify-between pt-2">
                        <a href="{{ route('profile') }}"
                            class="text-[9px] font-bold uppercase tracking-[0.15em] text-[#444] hover:text-white transition flex items-center gap-2">
                            <i class="fa-solid fa-xmark text-[10px]"></i>
                            Discard
                        </a>
                        <button type="submit"
                            class="bg-blue-600 text-white px-6 py-3 rounded-xl text-[10px] font-bold uppercase tracking-wider hover:bg-blue-500 transition shadow-lg shadow-blue-600/20 active:scale-[0.98] flex items-center gap-2">
                            <i class="fa-solid fa-check text-[9px]"></i>
                            Save Changes
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>

    <!-- ══════════ SCRIPTS ══════════ -->
    <script>
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
    </script>

</body>
</html>
