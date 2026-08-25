<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartCommute | User Profile</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">

    @include('partials.commuter-head-scripts')

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

        /* Light mode defaults */
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

        .inner-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            transition: background 0.2s ease;
        }

        .dark .inner-card {
            background: #111;
            border: 1px solid #1e1e1e;
        }

        .inner-card:hover {
            background: #f1f5f9;
        }

        .dark .inner-card:hover {
            background: #1a1a1a;
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

    <!-- ══════════ HEADER ══════════ -->
    <header
        class="fixed top-4 left-4 right-4 sm:top-5 sm:left-5 sm:right-5 z-50 flex items-center justify-between gap-3 pointer-events-none">
        <div class="glass-panel p-3 sm:p-3.5 rounded-2xl pointer-events-auto flex items-center gap-3 min-w-0">
            <a href="{{ route('map') }}"
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
                class="text-[9px] sm:text-[10px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#555] hidden sm:inline">Profile</span>
        </div>
        <div class="flex items-center gap-2 pointer-events-auto z-50 shrink-0">

            <a href="{{ route('payment.topup') }}"
                class="header-btn glass-panel px-3 sm:px-4 py-2 rounded-xl text-gray-700 dark:text-white text-[9px] sm:text-[10px] font-bold cursor-pointer uppercase tracking-wider flex items-center gap-2">
                <i class="fa-solid fa-wallet text-[9px] text-blue-500 dark:text-blue-400"></i>
                <span class="hidden sm:inline">Top Up</span>
            </a>
            <a href="{{ route('settings.edit') }}"
                class="header-btn bg-blue-600 px-3 sm:px-4 py-2 rounded-xl text-white text-[9px] sm:text-[10px] font-bold cursor-pointer uppercase tracking-wider flex items-center gap-2 shadow-lg shadow-blue-600/20 hover:bg-blue-500 transition active:scale-[0.98]">
                <i class="fa-solid fa-gear text-[8px] sm:text-[9px]"></i>
                <span class="hidden sm:inline">Settings</span>
            </a>
            @if (Auth::user())
                <button onclick="toggleLogoutModal()"
                    class="header-btn glass-panel w-9 h-9 rounded-xl flex items-center justify-center cursor-pointer hover:!border-red-500/30 hover:!bg-red-500/10">
                    <i class="fa-solid fa-right-from-bracket text-[9px] text-red-400"></i>
                </button>
            @endif
        </div>
    </header>

    <!-- ══════════ LOGOUT MODAL ══════════ -->
    <div id="logout-modal"
        class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 dark:bg-black/70 opacity-0 pointer-events-none transition-opacity duration-300">
        <div class="glass-panel p-7 sm:p-8 rounded-[2rem] w-full max-w-[360px] mx-4 text-center transform scale-95 opacity-0 transition-all duration-[350ms]"
            id="logout-modal-content">
            <div
                class="w-14 h-14 bg-red-500/10 rounded-2xl flex items-center justify-center mx-auto mb-5 border border-red-500/20">
                <i class="fa-solid fa-right-from-bracket text-red-400 text-lg"></i>
            </div>
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1.5">Sign Out?</h3>
            <p class="text-xs text-gray-500 dark:text-[#666] mb-7 leading-relaxed">Are you sure you want to log out of
                SmartCommute?</p>
            <div class="grid gap-2.5">
                <button onclick="toggleLogoutModal()"
                    class="px-5 py-3 rounded-xl bg-gray-100 dark:bg-[#1a1a1a] border border-gray-200 dark:border-[#2a2a2a] text-gray-700 dark:text-white text-[10px] font-bold uppercase tracking-widest hover:bg-gray-200 dark:hover:bg-[#222] transition">Cancel</button>
                <form action="{{ route('users.logout') }}" method="POST">
                    @csrf
                    <button type="submit"
                        class="w-full px-5 py-3 rounded-xl bg-red-600 text-white text-[10px] font-bold uppercase tracking-widest hover:bg-red-700 transition active:scale-[0.98]">Logout</button>
                </form>
            </div>
        </div>
    </div>

    <!-- ══════════ MAIN CONTENT ══════════ -->
    <div class="pt-20 sm:pt-24 pb-8 sm:pb-10 max-w-6xl mx-auto px-4 sm:px-6 lg:px-5">

        <!-- ── User Info Card (mobile) ── -->
        <div class="lg:hidden mb-6">
            <div class="glass-card p-5 rounded-[1.5rem]">
                <div class="flex items-center gap-2">
                    @if (Auth::user()->email_verified_at)
                        <span
                            class="flex items-center gap-1.5 text-[9px] font-bold text-emerald-500 dark:text-emerald-400">
                            <i class="fa-solid fa-circle-check text-[8px]"></i> Verified
                        </span>
                    @else
                        <span class="flex items-center gap-1.5 text-[9px] font-bold text-amber-500">
                            <i class="fa-solid fa-clock text-[8px]"></i> Pending
                        </span>
                    @endif
                    <div class="flex-1"></div>
                    <span class="text-[9px] text-gray-400 dark:text-[#444] font-medium">
                        <i class="fa-regular fa-calendar text-[8px] mr-1"></i>
                        Since {{ Auth::user()->created_at->format('M d, Y') }}
                    </span>
                </div>
            </div>
        </div>

        <!-- ── Mobile: Balance Card ── -->
        <div class="lg:hidden mb-6">
            <div class="glass-card p-5 rounded-[1.5rem]">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2.5">
                        <div
                            class="w-8 h-8 bg-blue-500/10 dark:bg-blue-500/15 rounded-lg flex items-center justify-center border border-blue-500/20">
                            <i class="fa-solid fa-wallet text-blue-500 dark:text-blue-400 text-[10px]"></i>
                        </div>
                        <span
                            class="text-[9px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#555]">Available
                            Balance</span>
                    </div>
                </div>
                <h3 class="text-3xl font-black tracking-tight text-gray-900 dark:text-white">
                    ₱{{ number_format($wallet->balance ?? 0, 2) }}</h3>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            <!-- ══════════ LEFT SIDEBAR (desktop only) ══════════ -->
            <div class="hidden lg:flex lg:col-span-4 flex-col gap-6">

                <!-- User Info -->
                <div class="glass-card p-6 rounded-[1.5rem]">
                    <div class="flex items-center gap-2.5 mb-5">
                        <div
                            class="w-8 h-8 bg-blue-500/10 dark:bg-blue-500/15 rounded-lg flex items-center justify-center">
                            <i class="fa-solid fa-circle-user text-blue-500 dark:text-blue-400 text-xs"></i>
                        </div>
                        <span
                            class="text-[9px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#555]">Commuter
                            Account</span>
                    </div>
                    <div class="space-y-3 pt-4 border-t border-gray-200 dark:border-[#1e1e1e]">
                        <div class="flex items-center justify-between">
                            <span
                                class="text-[9px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Status</span>
                            @if (Auth::user()->email_verified_at)
                                <span
                                    class="text-[9px] font-bold text-emerald-500 dark:text-emerald-400 flex items-center gap-1.5">
                                    <i class="fa-solid fa-circle-check text-[8px]"></i> Verified
                                </span>
                            @else
                                <span class="text-[9px] font-bold text-amber-500 flex items-center gap-1.5">
                                    <i class="fa-solid fa-clock text-[8px]"></i> Pending
                                </span>
                            @endif
                        </div>
                        <div class="flex items-center justify-between">
                            <span
                                class="text-[9px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Member
                                Since</span>
                            <span
                                class="text-[11px] font-bold text-gray-600 dark:text-[#888]">{{ Auth::user()->created_at->format('M d, Y') }}</span>
                        </div>
                    </div>
                </div>

                <!-- Balance -->
                <div class="glass-card p-6 rounded-[1.5rem]">
                    <div class="flex items-center gap-2.5 mb-4">
                        <div
                            class="w-8 h-8 bg-emerald-500/10 rounded-lg flex items-center justify-center border border-emerald-500/15">
                            <i class="fa-solid fa-wallet text-emerald-500 dark:text-emerald-400 text-[10px]"></i>
                        </div>
                        <span
                            class="text-[9px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#555]">Available
                            Balance</span>
                    </div>
                    <h3 class="text-3xl font-black tracking-tight text-gray-900 dark:text-white">
                        ₱{{ number_format($wallet->balance ?? 0, 2) }}</h3>
                </div>

            </div>

            <!-- ══════════ RIGHT CONTENT ══════════ -->
            <div class="lg:col-span-8 flex flex-col gap-6">

                <!-- ── Security Details ── -->
                <div class="glass-card p-5 sm:p-6 rounded-[1.5rem]">
                    <div class="flex items-center gap-2.5 mb-5">
                        <div class="w-8 h-8 rounded-lg inner-card flex items-center justify-center">
                            <i class="fa-solid fa-user-circle text-[10px] text-gray-400 dark:text-[#555]"></i>
                        </div>
                        <span
                            class="text-[9px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#555]">Security
                            Details</span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div class="inner-card flex items-center justify-between p-3.5 rounded-xl">
                            <div>
                                <p
                                    class="text-[9px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444] mb-0.5">
                                    Account Status</p>
                                @if (Auth::user()->email_verified_at)
                                    <span
                                        class="text-[10px] font-bold text-emerald-500 dark:text-emerald-400 flex items-center gap-1.5">
                                        <i class="fa-solid fa-circle-check text-[8px]"></i> Verified Commuter
                                    </span>
                                @else
                                    <span class="text-[10px] font-bold text-amber-500 flex items-center gap-1.5">
                                        <i class="fa-solid fa-clock text-[8px]"></i> Pending Verification
                                    </span>
                                @endif
                            </div>
                            <div
                                class="w-8 h-8 rounded-lg @if (Auth::user()->email_verified_at) bg-emerald-500/10 border border-emerald-500/20 @else bg-amber-500/10 border border-amber-500/20 @endif flex items-center justify-center shrink-0">
                                <i
                                    class="fa-solid @if (Auth::user()->email_verified_at) fa-check text-emerald-500 dark:text-emerald-400 @else fa-clock text-amber-400 @endif text-[9px]"></i>
                            </div>
                        </div>
                        <div class="inner-card flex items-center justify-between p-3.5 rounded-xl">
                            <div>
                                <p
                                    class="text-[9px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444] mb-0.5">
                                    Member Since</p>
                                <p class="text-[11px] font-bold text-gray-600 dark:text-[#888]">
                                    {{ Auth::user()->created_at->format('M d, Y') }}</p>
                            </div>
                            <div class="w-8 h-8 rounded-lg inner-card flex items-center justify-center shrink-0">
                                <i class="fa-regular fa-calendar text-[9px] text-gray-400 dark:text-[#555]"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── Recent Travels ── -->
                <div class="glass-card p-5 sm:p-6 rounded-[1.5rem]">
                    <div class="flex items-center justify-between mb-5">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg inner-card flex items-center justify-center">
                                <i class="fa-solid fa-route text-[10px] text-gray-400 dark:text-[#555]"></i>
                            </div>
                            <span
                                class="text-[9px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#555]">Recent
                                Travels</span>
                        </div>
                        <span class="text-[9px] font-bold text-gray-300 dark:text-[#555]">{{ count($payments) }}
                            trips</span>
                    </div>

                    <div class="space-y-2.5">
                        @forelse($payments as $payment)
                            <div
                                class="inner-card flex items-center justify-between p-3.5 rounded-xl border border-gray-200 dark:border-[#1e1e1e] group cursor-default">
                                <div class="flex items-center gap-3 min-w-0 flex-1">
                                    <div
                                        class="w-9 h-9 rounded-lg bg-blue-500/10 flex items-center justify-center text-blue-500 dark:text-blue-400 shrink-0 group-hover:scale-110 transition">
                                        <i class="fa-solid fa-location-arrow text-[10px]"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-[11px] font-bold text-gray-700 dark:text-[#ccc] truncate">
                                            {{ $payment->starting_point }} → {{ $payment->destination }}</p>
                                        <p
                                            class="text-[8px] text-gray-400 dark:text-[#444] font-bold uppercase tracking-tighter mt-0.5">
                                            {{ \Carbon\Carbon::parse($payment->paid_at)->format('M d • h:i A') }} •
                                            {{ $payment->total_distance }}km
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right shrink-0 ml-3 flex flex-col items-end gap-1">
                                    <p class="text-xs font-bold text-gray-900 dark:text-white">
                                        -₱{{ number_format($payment->price, 2) }}</p>
                                    @if ($payment->is_discounted)
                                        <span
                                            class="text-[7px] bg-blue-500/10 dark:bg-blue-500/15 text-blue-500 dark:text-blue-400 px-1.5 py-0.5 rounded font-bold uppercase">Discounted</span>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-10">
                                <div
                                    class="w-12 h-12 mx-auto mb-3 rounded-xl inner-card flex items-center justify-center">
                                    <i class="fa-solid fa-route text-lg text-gray-300 dark:text-[#333]"></i>
                                </div>
                                <p class="text-gray-400 dark:text-[#444] text-[11px] font-medium">No travel history
                                    found</p>
                                <a href="{{ route('map') }}"
                                    class="inline-flex items-center gap-2 mt-3 text-blue-500 dark:text-blue-400 text-[9px] font-bold uppercase tracking-wider hover:text-blue-600 dark:hover:text-blue-300 transition">
                                    <i class="fa-solid fa-arrow-right text-[8px]"></i> Start your first trip
                                </a>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- ── Wallet Loads ── -->
                <div class="glass-card p-5 sm:p-6 rounded-[1.5rem]">
                    <div class="flex items-center gap-2.5 mb-5">
                        <div class="w-8 h-8 rounded-lg inner-card flex items-center justify-center">
                            <i class="fa-solid fa-wallet text-[10px] text-gray-400 dark:text-[#555]"></i>
                        </div>
                        <span
                            class="text-[9px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#555]">Wallet
                            Loads</span>
                        <span class="text-[9px] font-bold text-gray-300 dark:text-[#555] ml-auto">{{ count($topups) }}
                            transactions</span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        @foreach ($topups as $topup)
                            <div class="inner-card flex items-center justify-between p-3.5 rounded-xl">
                                <div>
                                    <p
                                        class="text-[9px] font-bold text-emerald-500 dark:text-emerald-400 uppercase tracking-tighter">
                                        Reload Successful</p>
                                    <p class="text-[8px] text-gray-400 dark:text-[#444] mt-0.5">
                                        {{ $topup->created_at->diffForHumans() }} via {{ $topup->payment_method }}</p>
                                </div>
                                <p class="text-sm font-bold text-gray-900 dark:text-white shrink-0 ml-3">
                                    +₱{{ number_format($topup->amount_added, 2) }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- ══════════ LOGOUT MODAL LOGIC ══════════ -->
    <script>
        function toggleLogoutModal() {
            const modal = document.getElementById('logout-modal');
            const content = document.getElementById('logout-modal-content');
            const isOpen = modal.style.opacity === '1';
            modal.style.opacity = isOpen ? '0' : '1';
            modal.style.pointerEvents = isOpen ? 'none' : 'auto';
            content.style.transform = isOpen ? 'scale(0.95)' : 'scale(1)';
            content.style.opacity = isOpen ? '0' : '1';
        }
    </script>

</body>

</html>
