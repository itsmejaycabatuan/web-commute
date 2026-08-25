<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SmartCommute | Admin Console</title>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>

    @include('partials.head-scripts')

</head>

<body class="antialiased text-gray-900 dark:text-white" x-data>
    <x-layout.sidebar />

    <main :class="$store.sidebar.open ? 'md:ml-72' : 'md:ml-20'"
        class="sidebar-transition pt-8 pr-4 sm:pr-8 pb-8 pl-4 sm:pl-8 min-h-screen mb-12">

        @php
            $chartLabels = [];
            for ($i = 6; $i >= 0; $i--) {
                $chartLabels[] = \Carbon\Carbon::now()->subDays($i)->format('M d');
            }

            $revMap = [];
            foreach ($recentFares ?? [] as $f) {
                $key = \Carbon\Carbon::parse($f->created_at)->format('M d');
                $revMap[$key] = ($revMap[$key] ?? 0) + $f->price;
            }

            $topMap = [];
            foreach ($recentTopups ?? [] as $t) {
                $key = \Carbon\Carbon::parse($t->created_at)->format('M d');
                $topMap[$key] = ($topMap[$key] ?? 0) + $t->amount_added;
            }

            $chartRevenue = [];
            $chartTopups = [];
            foreach ($chartLabels as $label) {
                $chartRevenue[] = round($revMap[$label] ?? 0, 2);
                $chartTopups[] = round($topMap[$label] ?? 0, 2);
            }

            $totalUsers =
                $activeUsersCount +
                ($inactiveUsersCount ?? max(0, $activeUsersCount > 0 ? (int) ($activeUsersCount * 0.3) : 0));
        @endphp

        <!-- ── Mobile: Admin Identity Card ── -->
        <div class="lg:hidden mb-5">
            <div class="glass-card p-4 rounded-[1.25rem]">
                <div class="flex items-center gap-3.5">
                    <div class="w-11 h-11 bg-red-600 rounded-xl flex items-center justify-center shrink-0">
                        <span
                            class="text-sm font-black text-white">{{ strtoupper(substr(explode('@', Auth::user()->email)[0], 0, 1)) }}</span>
                    </div>
                    <div class="min-w-0">
                        <h2 class="text-sm font-bold text-gray-900 dark:text-white truncate">System Administrator</h2>
                        <p class="text-[10px] text-gray-500 dark:text-[#555] truncate">{{ Auth::user()->email }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 pt-3.5 mt-3.5 border-t border-gray-200 dark:border-[#1e1e1e]">
                    <i class="fa-solid fa-shield-halved text-[8px] text-red-500 dark:text-red-400"></i>
                    <span class="text-[10px] text-gray-600 dark:text-[#888] font-bold">Full Access</span>
                    <span class="text-gray-300 dark:text-[#555]">•</span>
                    <span class="font-mono text-[9px] text-gray-400 dark:text-[#444]">Admin</span>
                </div>
            </div>
        </div>

        <!-- ── Page Header (desktop) ── -->
        <div class="hidden lg:block mb-8">
            <div class="flex items-center gap-2 mb-1.5">
                <span class="text-[9px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Welcome
                    back,</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-gray-900 dark:text-white">System
                Administrator</h1>
            <p class="text-[11px] text-gray-500 dark:text-[#555] mt-1 flex items-center gap-2">
                <i class="fa-solid fa-shield-halved text-[9px] text-red-500 dark:text-red-400"></i>
                Role: <span class="text-gray-700 dark:text-[#888] font-bold">Super Admin</span>
                <span class="text-gray-300 dark:text-[#555]">•</span>
                <span class="font-mono text-[10px] text-gray-400 dark:text-[#444]">{{ Auth::user()->email }}</span>
            </p>
        </div>

        @isset($adminStats)
            <div class="grid grid-cols-2 sm:grid-cols-2 xl:grid-cols-3 gap-3 sm:gap-4 mb-5 sm:mb-6">

                <!-- Total Users -->
                <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-gray-300 dark:border-l-white/20">
                    <div class="flex items-center gap-2 mb-2 sm:mb-3">
                        <div class="w-6 h-6 rounded-md bg-gray-200 dark:bg-white/5 flex items-center justify-center">
                            <i class="fa-solid fa-users text-[8px] text-gray-500 dark:text-[#888]"></i>
                        </div>
                        <span
                            class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Total
                            Users</span>
                    </div>
                    <div class="flex items-baseline gap-1 sm:gap-1.5">
                        <span
                            class="text-2xl sm:text-3xl font-black tracking-tight text-gray-900 dark:text-white">{{ number_format($adminStats['total_users']) }}</span>
                    </div>
                    <p class="text-[7px] sm:text-[8px] text-gray-400 dark:text-[#555] mt-1.5 font-medium">All accounts in
                        the system
                    </p>
                </div>

                <!-- Total Commuters -->
                <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-cyan-500">
                    <div class="flex items-center gap-2 mb-2 sm:mb-3">
                        <div class="w-6 h-6 rounded-md bg-cyan-500/10 flex items-center justify-center">
                            <i class="fa-solid fa-user-group text-[8px] text-cyan-500"></i>
                        </div>
                        <span
                            class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Commuters</span>
                    </div>
                    <div class="flex items-baseline gap-1 sm:gap-1.5">
                        <span
                            class="text-2xl sm:text-3xl font-black tracking-tight text-cyan-600 dark:text-cyan-400">{{ number_format($adminStats['total_commuters']) }}</span>
                    </div>
                    <p class="text-[7px] sm:text-[8px] text-gray-400 dark:text-[#555] mt-1.5 font-medium">PUJ passenger
                        accounts</p>
                </div>

                <!-- Total Drivers -->
                <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-blue-500">
                    <div class="flex items-center gap-2 mb-2 sm:mb-3">
                        <div class="w-6 h-6 rounded-md bg-blue-500/10 flex items-center justify-center">
                            <i class="fa-solid fa-id-badge text-[8px] text-blue-500"></i>
                        </div>
                        <span
                            class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Drivers</span>
                    </div>
                    <div class="flex items-baseline gap-1 sm:gap-1.5">
                        <span
                            class="text-2xl sm:text-3xl font-black tracking-tight text-blue-600 dark:text-blue-400">{{ number_format($adminStats['total_drivers']) }}</span>
                    </div>
                    <p class="text-[7px] sm:text-[8px] text-gray-400 dark:text-[#555] mt-1.5 font-medium">PUJ operator
                        accounts</p>
                </div>

                <!-- Approved Drivers -->
                <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-emerald-500">
                    <div class="flex items-center gap-2 mb-2 sm:mb-3">
                        <div class="w-6 h-6 rounded-md bg-emerald-500/10 flex items-center justify-center">
                            <i class="fa-solid fa-circle-check text-[8px] text-emerald-500"></i>
                        </div>
                        <span
                            class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Approved</span>
                    </div>
                    <div class="flex items-baseline gap-1 sm:gap-1.5">
                        <span
                            class="text-2xl sm:text-3xl font-black tracking-tight text-emerald-600 dark:text-emerald-400">{{ number_format($adminStats['approved_drivers']) }}</span>
                        <span class="text-xs sm:text-sm font-bold text-gray-400 dark:text-[#555]">/
                            {{ number_format($adminStats['total_drivers']) }}</span>
                    </div>
                    <p class="text-[7px] sm:text-[8px] text-gray-400 dark:text-[#555] mt-1.5 font-medium">Can sign in</p>
                </div>

                <!-- Rejected Drivers -->
                <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-red-500">
                    <div class="flex items-center gap-2 mb-2 sm:mb-3">
                        <div class="w-6 h-6 rounded-md bg-red-500/10 flex items-center justify-center">
                            <i class="fa-solid fa-circle-xmark text-[8px] text-red-500"></i>
                        </div>
                        <span
                            class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Rejected</span>
                    </div>
                    <div class="flex items-baseline gap-1 sm:gap-1.5">
                        <span
                            class="text-2xl sm:text-3xl font-black tracking-tight text-red-500/80 dark:text-red-400/80">{{ number_format($adminStats['rejected_drivers']) }}</span>
                    </div>
                    <p class="text-[7px] sm:text-[8px] text-gray-400 dark:text-[#555] mt-1.5 font-medium">Denied
                        registrations</p>
                </div>

                <!-- Driver Applications -->
                <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-purple-500">
                    <div class="flex items-center gap-2 mb-2 sm:mb-3">
                        <div class="w-6 h-6 rounded-md bg-purple-500/10 flex items-center justify-center">
                            <i class="fa-solid fa-file-lines text-[8px] text-purple-500"></i>
                        </div>
                        <span
                            class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Applications</span>
                    </div>
                    <div class="flex items-baseline gap-1 sm:gap-1.5">
                        <span
                            class="text-2xl sm:text-3xl font-black tracking-tight text-gray-900 dark:text-white">{{ number_format($adminStats['total_applications']) }}</span>
                    </div>
                    <p class="text-[7px] sm:text-[8px] text-gray-400 dark:text-[#555] mt-1.5 font-medium">All-time signup
                        records
                    </p>
                </div>

            </div>
        @endisset

        <!-- ══════════ FINANCIAL STAT CARDS ══════════ -->
        <div class="grid grid-cols-2 sm:grid-cols-2 xl:grid-cols-3 gap-3 sm:gap-4 mb-5 sm:mb-6">

            <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-blue-500">
                <div class="flex items-center gap-2 mb-2 sm:mb-3">
                    <div class="w-6 h-6 rounded-md bg-blue-500/10 flex items-center justify-center">
                        <i class="fa-solid fa-receipt text-[8px] text-blue-500 dark:text-blue-400"></i>
                    </div>
                    <span
                        class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Fare
                        Revenue</span>
                </div>
                <div class="flex items-baseline gap-1 sm:gap-1.5">
                    <span
                        class="text-xl sm:text-2xl font-black tracking-tight text-gray-900 dark:text-white">₱{{ number_format($totalRevenue, 2) }}</span>
                </div>
            </div>

            <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-emerald-500">
                <div class="flex items-center gap-2 mb-2 sm:mb-3">
                    <div class="w-6 h-6 rounded-md bg-emerald-500/10 flex items-center justify-center">
                        <i class="fa-solid fa-wallet text-[8px] text-emerald-500 dark:text-emerald-400"></i>
                    </div>
                    <span
                        class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Wallet
                        Inflow</span>
                </div>
                <div class="flex items-baseline gap-1 sm:gap-1.5">
                    <span
                        class="text-xl sm:text-2xl font-black tracking-tight text-gray-900 dark:text-white">₱{{ number_format($totalFundsAdded, 2) }}</span>
                </div>
            </div>

            <div
                class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-purple-500 col-span-2 xl:col-span-1">
                <div class="flex items-center gap-2 mb-2 sm:mb-3">
                    <div class="w-6 h-6 rounded-md bg-purple-500/10 flex items-center justify-center">
                        <i class="fa-solid fa-users text-[8px] text-purple-500 dark:text-purple-400"></i>
                    </div>
                    <span
                        class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Active
                        Payers</span>
                </div>
                <div class="flex items-baseline gap-1 sm:gap-1.5">
                    <span
                        class="text-2xl sm:text-3xl font-black tracking-tight text-gray-900 dark:text-white">{{ $activeUsersCount }}</span>
                    <span class="text-xs sm:text-sm font-bold text-purple-500 dark:text-purple-400">commuters</span>
                </div>
            </div>

        </div>

        <div class="grid grid-cols-1 xl:grid-cols-12 gap-5 sm:gap-6">

            <!-- ══════════ LEFT COLUMN ══════════ -->
            <div class="xl:col-span-8 flex flex-col gap-5 sm:gap-6">

                <!-- ── Revenue & Inflow Chart ── -->
                <div class="glass-card p-4 sm:p-6 rounded-[1.25rem] sm:rounded-[1.5rem]">
                    <div class="flex items-center justify-between mb-5">
                        <div class="flex items-center gap-2.5">
                            <div
                                class="w-7 h-7 rounded-lg bg-gray-100 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] flex items-center justify-center">
                                <i class="fa-solid fa-chart-column text-[9px] text-gray-400 dark:text-[#555]"></i>
                            </div>
                            <span
                                class="text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#555]">7-Day
                                Revenue vs Inflow</span>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="flex items-center gap-1.5">
                                <div class="w-2 h-2 rounded-sm bg-blue-500"></div>
                                <span
                                    class="text-[7px] sm:text-[8px] font-bold text-gray-400 dark:text-[#444] uppercase">Fares</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <div class="w-2 h-2 rounded-sm bg-emerald-500"></div>
                                <span
                                    class="text-[7px] sm:text-[8px] font-bold text-gray-400 dark:text-[#444] uppercase">Top-ups</span>
                            </div>
                        </div>
                    </div>
                    <div class="relative" style="height: 220px;">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>

                <!-- ── Fare Activity ── -->
                <div class="glass-card rounded-[1.25rem] sm:rounded-[1.5rem] overflow-hidden">
                    <div class="p-4 sm:p-6 pb-0">
                        <div class="flex items-center justify-between mb-4 sm:mb-5">
                            <div class="flex items-center gap-2.5">
                                <div
                                    class="w-7 h-7 rounded-lg bg-gray-100 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] flex items-center justify-center">
                                    <i class="fa-solid fa-receipt text-[9px] text-blue-500 dark:text-blue-400"></i>
                                </div>
                                <span
                                    class="text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#555]">Fare
                                    Activity</span>
                            </div>
                            <a href="{{ route('faretransactions') }}"
                                class="text-[8px] sm:text-[9px] font-bold uppercase tracking-widest text-blue-500 dark:text-blue-400 hover:text-blue-600 dark:hover:text-white transition flex items-center gap-1.5">
                                <span>View Ledger</span>
                                <i class="fa-solid fa-arrow-right text-[7px]"></i>
                            </a>
                        </div>
                    </div>
                    <div class="overflow-x-auto -mx-2 px-2 pb-2">
                        <table class="w-full text-left min-w-[500px]">
                            <thead>
                                <tr
                                    class="text-[8px] sm:text-[9px] uppercase tracking-[0.15em] text-gray-400 dark:text-[#444] border-b border-gray-200 dark:border-[#1e1e1e]">
                                    <th class="px-4 sm:px-6 py-2.5 font-bold">Commuter</th>
                                    <th class="px-4 sm:px-6 py-2.5 font-bold">Time</th>
                                    <th class="px-4 sm:px-6 py-2.5 font-bold text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-[#1a1a1a]">
                                @forelse($recentFares ?? [] as $fare)
                                    <tr class="table-row">
                                        <td class="px-4 sm:px-6 py-3">
                                            <div class="flex items-center gap-3">
                                                <div
                                                    class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] flex items-center justify-center shrink-0">
                                                    <i
                                                        class="fa-solid fa-user text-[9px] text-gray-400 dark:text-[#555]"></i>
                                                </div>
                                                <p
                                                    class="text-[10px] sm:text-[11px] font-bold text-gray-700 dark:text-[#ccc] truncate max-w-[200px]">
                                                    {{ $fare->user->email }}</p>
                                            </div>
                                        </td>
                                        <td class="px-4 sm:px-6 py-3">
                                            <span
                                                class="text-[10px] sm:text-[11px] font-bold text-gray-500 dark:text-[#888]">{{ $fare->created_at->format('h:i A') }}</span>
                                            <p
                                                class="text-[7px] sm:text-[8px] text-gray-400 dark:text-[#444] font-bold uppercase">
                                                {{ $fare->created_at->format('M d') }}</p>
                                        </td>
                                        <td class="px-4 sm:px-6 py-3 text-right">
                                            <span
                                                class="text-[10px] sm:text-[11px] font-bold text-gray-900 dark:text-white">-₱{{ number_format($fare->price, 2) }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="py-10 sm:py-12">
                                            <div class="flex flex-col items-center justify-center">
                                                <div
                                                    class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-[#1c1c1c] border border-gray-200 dark:border-[#2a2a2a] flex items-center justify-center mb-2.5">
                                                    <i
                                                        class="fa-solid fa-receipt text-sm text-gray-300 dark:text-[#555]"></i>
                                                </div>
                                                <p
                                                    class="text-[10px] sm:text-[11px] text-gray-400 dark:text-[#555] font-medium">
                                                    No recent
                                                    fare activity</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ── Wallet Inflow ── -->
                <div class="glass-card rounded-[1.25rem] sm:rounded-[1.5rem] overflow-hidden">
                    <div class="p-4 sm:p-6 pb-0">
                        <div class="flex items-center justify-between mb-4 sm:mb-5">
                            <div class="flex items-center gap-2.5">
                                <div
                                    class="w-7 h-7 rounded-lg bg-gray-100 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] flex items-center justify-center">
                                    <i
                                        class="fa-solid fa-wallet text-[9px] text-emerald-500 dark:text-emerald-400"></i>
                                </div>
                                <span
                                    class="text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#555]">Wallet
                                    Inflow</span>
                            </div>
                            <a href="{{ route('admin.topups') }}"
                                class="text-[8px] sm:text-[9px] font-bold uppercase tracking-widest text-emerald-500 dark:text-emerald-400 hover:text-emerald-600 dark:hover:text-white transition flex items-center gap-1.5">
                                <span>View Ledger</span>
                                <i class="fa-solid fa-arrow-right text-[7px]"></i>
                            </a>
                        </div>
                    </div>
                    <div class="overflow-x-auto -mx-2 px-2 pb-2">
                        <table class="w-full text-left min-w-[500px]">
                            <thead>
                                <tr
                                    class="text-[8px] sm:text-[9px] uppercase tracking-[0.15em] text-gray-400 dark:text-[#444] border-b border-gray-200 dark:border-[#1e1e1e]">
                                    <th class="px-4 sm:px-6 py-2.5 font-bold">Commuter</th>
                                    <th class="px-4 sm:px-6 py-2.5 font-bold">Method</th>
                                    <th class="px-4 sm:px-6 py-2.5 font-bold">Time</th>
                                    <th class="px-4 sm:px-6 py-2.5 font-bold text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-[#1a1a1a]">
                                @forelse($recentTopups ?? [] as $topup)
                                    <tr class="table-row">
                                        <td class="px-4 sm:px-6 py-3">
                                            <div class="flex items-center gap-3">
                                                <div
                                                    class="w-8 h-8 rounded-lg bg-emerald-500/10 border border-emerald-500/15 flex items-center justify-center shrink-0">
                                                    <i
                                                        class="fa-solid fa-arrow-up text-[9px] text-emerald-500 dark:text-emerald-400"></i>
                                                </div>
                                                <p
                                                    class="text-[10px] sm:text-[11px] font-bold text-gray-700 dark:text-[#ccc] truncate max-w-[200px]">
                                                    {{ $topup->user->email }}</p>
                                            </div>
                                        </td>
                                        <td class="px-4 sm:px-6 py-3">
                                            <span
                                                class="text-[8px] bg-gray-100 dark:bg-[#111] text-gray-600 dark:text-[#888] border border-gray-200 dark:border-[#1e1e1e] px-1.5 py-0.5 rounded-md font-bold uppercase">{{ $topup->payment_method }}</span>
                                        </td>
                                        <td class="px-4 sm:px-6 py-3">
                                            <span
                                                class="text-[10px] sm:text-[11px] font-bold text-gray-500 dark:text-[#888]">{{ $topup->created_at->format('h:i A') }}</span>
                                            <p
                                                class="text-[7px] sm:text-[8px] text-gray-400 dark:text-[#444] font-bold uppercase">
                                                {{ $topup->created_at->format('M d') }}</p>
                                        </td>
                                        <td class="px-4 sm:px-6 py-3 text-right">
                                            <span
                                                class="text-[10px] sm:text-[11px] font-bold text-emerald-500 dark:text-emerald-400">+₱{{ number_format($topup->amount_added, 2) }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-10 sm:py-12">
                                            <div class="flex flex-col items-center justify-center">
                                                <div
                                                    class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-[#1c1c1c] border border-gray-200 dark:border-[#2a2a2a] flex items-center justify-center mb-2.5">
                                                    <i
                                                        class="fa-solid fa-wallet text-sm text-gray-300 dark:text-[#555]"></i>
                                                </div>
                                                <p
                                                    class="text-[10px] sm:text-[11px] text-gray-400 dark:text-[#555] font-medium">
                                                    No recent
                                                    top-up activity</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ══════════ RIGHT COLUMN ══════════ -->
            <div class="xl:col-span-4 flex flex-col gap-5 sm:gap-6">

                <!-- ── User Distribution Donut ── -->
                <div class="glass-card p-4 sm:p-6 rounded-[1.25rem] sm:rounded-[1.5rem]">
                    <div class="flex items-center gap-2.5 mb-5">
                        <div
                            class="w-7 h-7 rounded-lg bg-gray-100 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] flex items-center justify-center">
                            <i class="fa-solid fa-chart-pie text-[9px] text-gray-400 dark:text-[#555]"></i>
                        </div>
                        <span
                            class="text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#555]">User
                            Distribution</span>
                    </div>
                    <div class="flex items-center justify-center mb-5">
                        <div class="relative" style="width: 160px; height: 160px;">
                            <canvas id="donutChart"></canvas>
                            <div class="donut-center text-center">
                                <p class="text-2xl font-black text-gray-900 dark:text-white">{{ $totalUsers }}</p>
                                <p
                                    class="text-[7px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">
                                    Total</p>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-2.5">
                        <div
                            class="flex items-center justify-between p-3 rounded-xl bg-gray-50 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e]">
                            <div class="flex items-center gap-2.5">
                                <div class="w-2.5 h-2.5 rounded-sm bg-purple-500"></div>
                                <span
                                    class="text-[9px] font-bold text-gray-600 dark:text-[#888] uppercase">Active</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span
                                    class="text-[11px] font-bold text-gray-900 dark:text-white">{{ $activeUsersCount }}</span>
                                <span
                                    class="text-[8px] font-bold text-gray-400 dark:text-[#444]">{{ $totalUsers > 0 ? round(($activeUsersCount / $totalUsers) * 100, 1) : 0 }}%</span>
                            </div>
                        </div>
                        <div
                            class="flex items-center justify-between p-3 rounded-xl bg-gray-50 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e]">
                            <div class="flex items-center gap-2.5">
                                <div class="w-2.5 h-2.5 rounded-sm bg-gray-300 dark:bg-[#333]"></div>
                                <span
                                    class="text-[9px] font-bold text-gray-600 dark:text-[#888] uppercase">Inactive</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span
                                    class="text-[11px] font-bold text-gray-400 dark:text-[#666]">{{ $totalUsers - $activeUsersCount }}</span>
                                <span
                                    class="text-[8px] font-bold text-gray-400 dark:text-[#444]">{{ $totalUsers > 0 ? round((($totalUsers - $activeUsersCount) / $totalUsers) * 100, 1) : 0 }}%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── Fare Method Breakdown ── -->
                <div class="glass-card p-4 sm:p-6 rounded-[1.25rem] sm:rounded-[1.5rem]">
                    <div class="flex items-center gap-2.5 mb-5">
                        <div
                            class="w-7 h-7 rounded-lg bg-gray-100 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] flex items-center justify-center">
                            <i class="fa-solid fa-money-bill-transfer text-[9px] text-gray-400 dark:text-[#555]"></i>
                        </div>
                        <span
                            class="text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#555]">Top-up
                            Methods</span>
                    </div>
                    <div class="relative" style="height: 140px;">
                        <canvas id="methodChart"></canvas>
                    </div>
                    <div class="mt-4 space-y-2">
                        @php
                            $methodCounts = [];
                            foreach ($recentTopups ?? [] as $t) {
                                $m = strtolower($t->payment_method ?? 'other');
                                $methodCounts[$m] = ($methodCounts[$m] ?? 0) + 1;
                            }
                            $methodColors = [
                                'gcash' => '#3b82f6',
                                'maya' => '#10b981',
                                'bank' => '#a855f7',
                                'cash' => '#f59e0b',
                                'other' => '#6b7280',
                            ];
                        @endphp
                        @forelse($methodCounts as $method => $count)
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="w-2 h-2 rounded-sm"
                                        style="background: {{ $methodColors[$method] ?? '#6b7280' }}"></div>
                                    <span
                                        class="text-[9px] font-bold text-gray-600 dark:text-[#888] uppercase">{{ $method }}</span>
                                </div>
                                <span
                                    class="text-[10px] font-bold text-gray-700 dark:text-[#ccc]">{{ $count }}</span>
                            </div>
                        @empty
                            <p class="text-[9px] text-gray-400 dark:text-[#555] text-center py-2">No data</p>
                        @endforelse
                    </div>
                </div>

                <!-- ── Management Controls ── -->
                <div class="glass-card p-4 sm:p-6 rounded-[1.25rem] sm:rounded-[1.5rem]">
                    <div class="flex items-center gap-2.5 mb-4 sm:mb-5">
                        <div
                            class="w-7 h-7 rounded-lg bg-gray-100 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] flex items-center justify-center">
                            <i class="fa-solid fa-gears text-[9px] text-gray-400 dark:text-[#555]"></i>
                        </div>
                        <span
                            class="text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#555]">Management</span>
                    </div>
                    <div class="space-y-2">
                        <a href="{{ route('admin.commuters.index') }}"
                            class="flex items-center justify-between p-3 sm:p-3.5 rounded-xl bg-gray-50 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] hover:bg-gray-100 dark:hover:bg-[#1a1a1a] hover:border-gray-300 dark:hover:border-[#333] transition group cursor-pointer">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-8 h-8 rounded-lg bg-purple-500/10 border border-purple-500/15 flex items-center justify-center">
                                    <i class="fa-solid fa-users text-[9px] text-purple-500 dark:text-purple-400"></i>
                                </div>
                                <span
                                    class="text-[10px] font-bold text-gray-600 dark:text-[#888] group-hover:text-gray-900 dark:group-hover:text-white transition">Commuters</span>
                            </div>
                            <i
                                class="fa-solid fa-chevron-right text-[8px] text-gray-300 dark:text-[#555] group-hover:text-gray-500 dark:group-hover:text-[#777] transition"></i>
                        </a>
                        <a href="{{ route('drivers.index') }}"
                            class="flex items-center justify-between p-3 sm:p-3.5 rounded-xl bg-gray-50 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] hover:bg-gray-100 dark:hover:bg-[#1a1a1a] hover:border-gray-300 dark:hover:border-[#333] transition group cursor-pointer">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-8 h-8 rounded-lg bg-amber-500/10 border border-amber-500/15 flex items-center justify-center">
                                    <i class="fa-solid fa-id-badge text-[9px] text-amber-500 dark:text-amber-400"></i>
                                </div>
                                <span
                                    class="text-[10px] font-bold text-gray-600 dark:text-[#888] group-hover:text-gray-900 dark:group-hover:text-white transition">Driver
                                    Verification</span>
                            </div>
                            <i
                                class="fa-solid fa-chevron-right text-[8px] text-gray-300 dark:text-[#555] group-hover:text-gray-500 dark:group-hover:text-[#777] transition"></i>
                        </a>
                    </div>
                </div>

                <!-- ── Financial Audit ── -->
                <div class="glass-card p-4 sm:p-6 rounded-[1.25rem] sm:rounded-[1.5rem]">
                    <div class="flex items-center gap-2.5 mb-4 sm:mb-5">
                        <div
                            class="w-7 h-7 rounded-lg bg-gray-100 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] flex items-center justify-center">
                            <i class="fa-solid fa-magnifying-glass text-[9px] text-gray-400 dark:text-[#555]"></i>
                        </div>
                        <span
                            class="text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#555]">Financial
                            Audit</span>
                    </div>
                    <div class="space-y-2">
                        <a href="{{ route('faretransactions') }}"
                            class="flex items-center justify-between p-3 sm:p-3.5 rounded-xl bg-blue-500/5 dark:bg-blue-500/10 border border-blue-500/10 dark:border-blue-500/20 hover:bg-blue-500/10 dark:hover:bg-blue-500/15 hover:border-blue-500/20 dark:hover:border-blue-500/30 transition group cursor-pointer">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-8 h-8 rounded-lg bg-blue-500/10 border border-blue-500/15 flex items-center justify-center">
                                    <i class="fa-solid fa-receipt text-[9px] text-blue-500 dark:text-blue-400"></i>
                                </div>
                                <div>
                                    <span
                                        class="text-[10px] font-bold text-gray-700 dark:text-[#ccc] group-hover:text-gray-900 dark:group-hover:text-white transition block">Audit
                                        Fares</span>
                                    <span class="text-[7px] text-gray-400 dark:text-[#555] font-bold uppercase">Fare
                                        transaction
                                        ledger</span>
                                </div>
                            </div>
                            <i
                                class="fa-solid fa-chevron-right text-[8px] text-blue-500/30 dark:text-blue-500/40 group-hover:text-blue-500 transition"></i>
                        </a>
                        <a href="{{ route('admin.topups') }}"
                            class="flex items-center justify-between p-3 sm:p-3.5 rounded-xl bg-emerald-500/5 dark:bg-emerald-500/10 border border-emerald-500/10 dark:border-emerald-500/20 hover:bg-emerald-500/10 dark:hover:bg-emerald-500/15 hover:border-emerald-500/20 dark:hover:border-emerald-500/30 transition group cursor-pointer">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-8 h-8 rounded-lg bg-emerald-500/10 border border-emerald-500/15 flex items-center justify-center">
                                    <i
                                        class="fa-solid fa-wallet text-[9px] text-emerald-500 dark:text-emerald-400"></i>
                                </div>
                                <div>
                                    <span
                                        class="text-[10px] font-bold text-gray-700 dark:text-[#ccc] group-hover:text-gray-900 dark:group-hover:text-white transition block">Audit
                                        Wallets</span>
                                    <span class="text-[7px] text-gray-400 dark:text-[#555] font-bold uppercase">Top-up
                                        & balance
                                        ledger</span>
                                </div>
                            </div>
                            <i
                                class="fa-solid fa-chevron-right text-[8px] text-emerald-500/30 dark:text-emerald-500/40 group-hover:text-emerald-500 transition"></i>
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <!-- ══════════ CHART INITIALIZATION ══════════ -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const isDark = document.documentElement.classList.contains('dark');

            // ── Shared chart defaults ──
            Chart.defaults.font.family = "'Inter', sans-serif";
            Chart.defaults.color = isDark ? '#444' : '#64748b';

            const tooltipStyle = {
                backgroundColor: isDark ? '#111' : '#ffffff',
                borderColor: isDark ? '#1e1e1e' : '#e2e8f0',
                borderWidth: 1,
                titleColor: isDark ? '#ccc' : '#1e293b',
                titleFont: {
                    size: 10,
                    weight: '700'
                },
                bodyColor: isDark ? '#888' : '#64748b',
                bodyFont: {
                    size: 10,
                    weight: '600'
                },
                padding: 10,
                cornerRadius: 10,
                displayColors: true,
                boxPadding: 4
            };

            const gridColor = isDark ? '#1a1a1a' : '#f1f5f9';
            const tickColor = isDark ? '#444' : '#64748b';

            // ══════════ 7-DAY REVENUE VS INFLOW BAR CHART ══════════
            const revenueCtx = document.getElementById('revenueChart');
            if (revenueCtx) {
                new Chart(revenueCtx, {
                    type: 'bar',
                    data: {
                        labels: @json($chartLabels),
                        datasets: [{
                                label: 'Fare Revenue',
                                data: @json($chartRevenue),
                                backgroundColor: 'rgba(59, 130, 246, 0.7)',
                                hoverBackgroundColor: 'rgba(59, 130, 246, 0.9)',
                                borderRadius: 5,
                                borderSkipped: false,
                                barPercentage: 0.6,
                                categoryPercentage: 0.7
                            },
                            {
                                label: 'Wallet Inflow',
                                data: @json($chartTopups),
                                backgroundColor: 'rgba(16, 185, 129, 0.7)',
                                hoverBackgroundColor: 'rgba(16, 185, 129, 0.9)',
                                borderRadius: 5,
                                borderSkipped: false,
                                barPercentage: 0.6,
                                categoryPercentage: 0.7
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                ...tooltipStyle,
                                callbacks: {
                                    label: function(ctx) {
                                        return ' ' + ctx.dataset.label + ':  ₱' + ctx.parsed.y
                                            .toLocaleString('en-PH', {
                                                minimumFractionDigits: 2
                                            });
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: {
                                    color: gridColor,
                                    drawBorder: false
                                },
                                ticks: {
                                    font: {
                                        size: 9,
                                        weight: '600'
                                    },
                                    color: tickColor
                                },
                                border: {
                                    display: false
                                }
                            },
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: gridColor,
                                    drawBorder: false
                                },
                                ticks: {
                                    font: {
                                        size: 9,
                                        weight: '600'
                                    },
                                    color: tickColor,
                                    callback: function(val) {
                                        return '₱' + val;
                                    },
                                    maxTicksLimit: 5
                                },
                                border: {
                                    display: false
                                }
                            }
                        }
                    }
                });
            }

            // ══════════ USER DISTRIBUTION DONUT ══════════
            const donutCtx = document.getElementById('donutChart');
            if (donutCtx) {
                new Chart(donutCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Active', 'Inactive'],
                        datasets: [{
                            data: [{{ $activeUsersCount }},
                                {{ $totalUsers - $activeUsersCount }}
                            ],
                            backgroundColor: ['#a855f7', isDark ? '#1e1e1e' : '#e2e8f0'],
                            hoverBackgroundColor: ['#a855f7', isDark ? '#2a2a2a' : '#cbd5e1'],
                            borderColor: isDark ? '#161616' : '#ffffff',
                            borderWidth: 3,
                            borderRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '72%',
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                ...tooltipStyle,
                                callbacks: {
                                    label: function(ctx) {
                                        const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                        const pct = total > 0 ? ((ctx.parsed / total) * 100).toFixed(
                                            1) : 0;
                                        return ' ' + ctx.label + ':  ' + ctx.parsed + ' (' + pct + '%)';
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // ══════════ TOP-UP METHOD HORIZONTAL BAR ══════════
            const methodCtx = document.getElementById('methodChart');
            if (methodCtx) {
                const methods = @json(array_keys($methodCounts));
                const counts = @json(array_values($methodCounts));
                const colorMap = {
                    gcash: '#3b82f6',
                    maya: '#10b981',
                    bank: '#a855f7',
                    cash: '#f59e0b',
                    other: '#6b7280'
                };
                const barColors = methods.map(m => colorMap[m] || '#6b7280');

                new Chart(methodCtx, {
                    type: 'bar',
                    data: {
                        labels: methods.map(m => m.toUpperCase()),
                        datasets: [{
                            data: counts,
                            backgroundColor: barColors.map(c => c + 'b3'),
                            hoverBackgroundColor: barColors,
                            borderRadius: 4,
                            borderSkipped: false,
                            barPercentage: 0.55
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                ...tooltipStyle,
                                callbacks: {
                                    label: function(ctx) {
                                        return ' Count:  ' + ctx.parsed.x;
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                beginAtZero: true,
                                grid: {
                                    color: gridColor,
                                    drawBorder: false
                                },
                                ticks: {
                                    font: {
                                        size: 9,
                                        weight: '600'
                                    },
                                    color: tickColor,
                                    stepSize: 1
                                },
                                border: {
                                    display: false
                                }
                            },
                            y: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    font: {
                                        size: 9,
                                        weight: '700'
                                    },
                                    color: tickColor
                                },
                                border: {
                                    display: false
                                }
                            }
                        }
                    }
                });
            }
        });
    </script>

</body>

</html>
