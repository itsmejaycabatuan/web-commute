<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartCommute | Admin Console</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: { extend: { fontFamily: { sans: ['Inter', 'sans-serif'] } } }
        }
    </script>
    <style>
        body, html { margin: 0; padding: 0; height: 100%; font-family: 'Inter', sans-serif; overflow-x: hidden; background: #050505; }
        .glass-panel { background: #111111 !important; border: 1px solid #1e1e1e; box-shadow: 0 4px 24px rgba(0,0,0,0.6); }
        .glass-card { background: #161616; border: 1px solid #222222; box-shadow: 0 4px 24px rgba(0,0,0,0.5); }
        .sidebar-transition { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        .table-row { transition: all 0.2s ease; }
        .table-row:hover { background: #1a1a1a; }

        ::-webkit-scrollbar { width: 3px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #333; border-radius: 10px; }

        .donut-center {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            pointer-events: none;
        }
    </style>
</head>

<body class="antialiased text-white" x-data="{ open: false, showLogoutModal: false }">

    @include('layout.sidebar')

    <main :class="open ? 'md:ml-72' : 'md:ml-20'" class="sidebar-transition pt-8 pr-4 sm:pr-8 pb-8 pl-4 sm:pl-8 min-h-screen mb-12">

        @php
            // Generate last 7 day labels
            $chartLabels = [];
            for ($i = 6; $i >= 0; $i--) {
                $chartLabels[] = \Carbon\Carbon::now()->subDays($i)->format('M d');
            }

            // Build revenue data indexed by date key
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

            // User breakdown for donut
            $totalUsers = $activeUsersCount + ($inactiveUsersCount ?? max(0, $activeUsersCount > 0 ? (int)($activeUsersCount * 0.3) : 0));
        @endphp

        <!-- ── Mobile: Admin Identity Card ── -->
        <div class="lg:hidden mb-5">
            <div class="glass-card p-4 rounded-[1.25rem]">
                <div class="flex items-center gap-3.5">
                    <div class="w-11 h-11 bg-red-600 rounded-xl flex items-center justify-center shrink-0">
                        <span class="text-sm font-black text-white">{{ strtoupper(substr(explode('@', Auth::user()->email)[0], 0, 1)) }}</span>
                    </div>
                    <div class="min-w-0">
                        <h2 class="text-sm font-bold text-white truncate">System Administrator</h2>
                        <p class="text-[10px] text-[#555] truncate">{{ Auth::user()->email }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 pt-3.5 mt-3.5 border-t border-[#1e1e1e]">
                    <i class="fa-solid fa-shield-halved text-[8px] text-red-400"></i>
                    <span class="text-[10px] text-[#888] font-bold">Full Access</span>
                    <span class="text-[#333]">•</span>
                    <span class="font-mono text-[9px] text-[#444]">Admin</span>
                </div>
            </div>
        </div>

        <!-- ── Page Header (desktop) ── -->
        <div class="hidden lg:block mb-8">
            <div class="flex items-center gap-2 mb-1.5">
                <span class="text-[9px] font-bold uppercase tracking-[0.15em] text-[#444]">Welcome back,</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">System Administrator</h1>
            <p class="text-[11px] text-[#555] mt-1 flex items-center gap-2">
                <i class="fa-solid fa-shield-halved text-[9px] text-red-400"></i>
                Role: <span class="text-[#888] font-bold">Super Admin</span>
                <span class="text-[#333]">•</span>
                <span class="font-mono text-[10px] text-[#444]">{{ Auth::user()->email }}</span>
            </p>
        </div>

        @isset($adminStats)
            @include('admin.partials.stats-grid', ['stats' => $adminStats])
        @endisset

        <!-- ══════════ FINANCIAL STAT CARDS ══════════ -->
        <div class="grid grid-cols-2 sm:grid-cols-2 xl:grid-cols-3 gap-3 sm:gap-4 mb-5 sm:mb-6">

            <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-blue-500">
                <div class="flex items-center gap-2 mb-2 sm:mb-3">
                    <div class="w-6 h-6 rounded-md bg-blue-500/10 flex items-center justify-center">
                        <i class="fa-solid fa-receipt text-[8px] text-blue-400"></i>
                    </div>
                    <span class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Fare Revenue</span>
                </div>
                <div class="flex items-baseline gap-1 sm:gap-1.5">
                    <span class="text-xl sm:text-2xl font-black tracking-tight">₱{{ number_format($totalRevenue, 2) }}</span>
                </div>
            </div>

            <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-emerald-500">
                <div class="flex items-center gap-2 mb-2 sm:mb-3">
                    <div class="w-6 h-6 rounded-md bg-emerald-500/10 flex items-center justify-center">
                        <i class="fa-solid fa-wallet text-[8px] text-emerald-400"></i>
                    </div>
                    <span class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Wallet Inflow</span>
                </div>
                <div class="flex items-baseline gap-1 sm:gap-1.5">
                    <span class="text-xl sm:text-2xl font-black tracking-tight">₱{{ number_format($totalFundsAdded, 2) }}</span>
                </div>
            </div>

            <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-purple-500 col-span-2 xl:col-span-1">
                <div class="flex items-center gap-2 mb-2 sm:mb-3">
                    <div class="w-6 h-6 rounded-md bg-purple-500/10 flex items-center justify-center">
                        <i class="fa-solid fa-users text-[8px] text-purple-400"></i>
                    </div>
                    <span class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Active Payers</span>
                </div>
                <div class="flex items-baseline gap-1 sm:gap-1.5">
                    <span class="text-2xl sm:text-3xl font-black tracking-tight">{{ $activeUsersCount }}</span>
                    <span class="text-xs sm:text-sm font-bold text-purple-400">commuters</span>
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
                            <div class="w-7 h-7 rounded-lg bg-[#111] border border-[#1e1e1e] flex items-center justify-center">
                                <i class="fa-solid fa-chart-column text-[9px] text-[#555]"></i>
                            </div>
                            <span class="text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-[#555]">7-Day Revenue vs Inflow</span>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="flex items-center gap-1.5">
                                <div class="w-2 h-2 rounded-sm bg-blue-500"></div>
                                <span class="text-[7px] sm:text-[8px] font-bold text-[#444] uppercase">Fares</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <div class="w-2 h-2 rounded-sm bg-emerald-500"></div>
                                <span class="text-[7px] sm:text-[8px] font-bold text-[#444] uppercase">Top-ups</span>
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
                                <div class="w-7 h-7 rounded-lg bg-[#111] border border-[#1e1e1e] flex items-center justify-center">
                                    <i class="fa-solid fa-receipt text-[9px] text-blue-400"></i>
                                </div>
                                <span class="text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-[#555]">Fare Activity</span>
                            </div>
                            <a href="{{ route('faretransactions') }}"
                                class="text-[8px] sm:text-[9px] font-bold uppercase tracking-widest text-blue-400 hover:text-white transition flex items-center gap-1.5">
                                <span>View Ledger</span>
                                <i class="fa-solid fa-arrow-right text-[7px]"></i>
                            </a>
                        </div>
                    </div>
                    <div class="overflow-x-auto -mx-2 px-2 pb-2">
                        <table class="w-full text-left min-w-[500px]">
                            <thead>
                                <tr class="text-[8px] sm:text-[9px] uppercase tracking-[0.15em] text-[#444] border-b border-[#1e1e1e]">
                                    <th class="px-4 sm:px-6 py-2.5 font-bold">Commuter</th>
                                    <th class="px-4 sm:px-6 py-2.5 font-bold">Time</th>
                                    <th class="px-4 sm:px-6 py-2.5 font-bold text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#1a1a1a]">
                                @forelse($recentFares ?? [] as $fare)
                                    <tr class="table-row">
                                        <td class="px-4 sm:px-6 py-3">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-lg bg-[#111] border border-[#1e1e1e] flex items-center justify-center shrink-0">
                                                    <i class="fa-solid fa-user text-[9px] text-[#555]"></i>
                                                </div>
                                                <p class="text-[10px] sm:text-[11px] font-bold text-[#ccc] truncate max-w-[200px]">{{ $fare->user->email }}</p>
                                            </div>
                                        </td>
                                        <td class="px-4 sm:px-6 py-3">
                                            <span class="text-[10px] sm:text-[11px] font-bold text-[#888]">{{ $fare->created_at->format('h:i A') }}</span>
                                            <p class="text-[7px] sm:text-[8px] text-[#444] font-bold uppercase">{{ $fare->created_at->format('M d') }}</p>
                                        </td>
                                        <td class="px-4 sm:px-6 py-3 text-right">
                                            <span class="text-[10px] sm:text-[11px] font-bold text-white">-₱{{ number_format($fare->price, 2) }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="py-10 sm:py-12">
                                            <div class="flex flex-col items-center justify-center">
                                                <div class="w-10 h-10 rounded-xl bg-[#111] border border-[#1e1e1e] flex items-center justify-center mb-2.5">
                                                    <i class="fa-solid fa-receipt text-sm text-[#333]"></i>
                                                </div>
                                                <p class="text-[10px] sm:text-[11px] text-[#444] font-medium">No recent fare activity</p>
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
                                <div class="w-7 h-7 rounded-lg bg-[#111] border border-[#1e1e1e] flex items-center justify-center">
                                    <i class="fa-solid fa-wallet text-[9px] text-emerald-400"></i>
                                </div>
                                <span class="text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-[#555]">Wallet Inflow</span>
                            </div>
                            <a href="{{ route('admin.topups') }}"
                                class="text-[8px] sm:text-[9px] font-bold uppercase tracking-widest text-emerald-400 hover:text-white transition flex items-center gap-1.5">
                                <span>View Ledger</span>
                                <i class="fa-solid fa-arrow-right text-[7px]"></i>
                            </a>
                        </div>
                    </div>
                    <div class="overflow-x-auto -mx-2 px-2 pb-2">
                        <table class="w-full text-left min-w-[500px]">
                            <thead>
                                <tr class="text-[8px] sm:text-[9px] uppercase tracking-[0.15em] text-[#444] border-b border-[#1e1e1e]">
                                    <th class="px-4 sm:px-6 py-2.5 font-bold">Commuter</th>
                                    <th class="px-4 sm:px-6 py-2.5 font-bold">Method</th>
                                    <th class="px-4 sm:px-6 py-2.5 font-bold">Time</th>
                                    <th class="px-4 sm:px-6 py-2.5 font-bold text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#1a1a1a]">
                                @forelse($recentTopups ?? [] as $topup)
                                    <tr class="table-row">
                                        <td class="px-4 sm:px-6 py-3">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-lg bg-emerald-500/10 border border-emerald-500/15 flex items-center justify-center shrink-0">
                                                    <i class="fa-solid fa-arrow-up text-[9px] text-emerald-400"></i>
                                                </div>
                                                <p class="text-[10px] sm:text-[11px] font-bold text-[#ccc] truncate max-w-[200px]">{{ $topup->user->email }}</p>
                                            </div>
                                        </td>
                                        <td class="px-4 sm:px-6 py-3">
                                            <span class="text-[8px] bg-[#111] text-[#888] border border-[#1e1e1e] px-1.5 py-0.5 rounded-md font-bold uppercase">{{ $topup->payment_method }}</span>
                                        </td>
                                        <td class="px-4 sm:px-6 py-3">
                                            <span class="text-[10px] sm:text-[11px] font-bold text-[#888]">{{ $topup->created_at->format('h:i A') }}</span>
                                            <p class="text-[7px] sm:text-[8px] text-[#444] font-bold uppercase">{{ $topup->created_at->format('M d') }}</p>
                                        </td>
                                        <td class="px-4 sm:px-6 py-3 text-right">
                                            <span class="text-[10px] sm:text-[11px] font-bold text-emerald-400">+₱{{ number_format($topup->amount_added, 2) }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-10 sm:py-12">
                                            <div class="flex flex-col items-center justify-center">
                                                <div class="w-10 h-10 rounded-xl bg-[#111] border border-[#1e1e1e] flex items-center justify-center mb-2.5">
                                                    <i class="fa-solid fa-wallet text-sm text-[#333]"></i>
                                                </div>
                                                <p class="text-[10px] sm:text-[11px] text-[#444] font-medium">No recent top-up activity</p>
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
                        <div class="w-7 h-7 rounded-lg bg-[#111] border border-[#1e1e1e] flex items-center justify-center">
                            <i class="fa-solid fa-chart-pie text-[9px] text-[#555]"></i>
                        </div>
                        <span class="text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-[#555]">User Distribution</span>
                    </div>
                    <div class="flex items-center justify-center mb-5">
                        <div class="relative" style="width: 160px; height: 160px;">
                            <canvas id="donutChart"></canvas>
                            <div class="donut-center text-center">
                                <p class="text-2xl font-black text-white">{{ $totalUsers }}</p>
                                <p class="text-[7px] font-bold uppercase tracking-[0.15em] text-[#444]">Total</p>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-2.5">
                        <div class="flex items-center justify-between p-3 rounded-xl bg-[#111] border border-[#1e1e1e]">
                            <div class="flex items-center gap-2.5">
                                <div class="w-2.5 h-2.5 rounded-sm bg-purple-500"></div>
                                <span class="text-[9px] font-bold text-[#888] uppercase">Active</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-[11px] font-bold text-white">{{ $activeUsersCount }}</span>
                                <span class="text-[8px] font-bold text-[#444]">{{ $totalUsers > 0 ? round(($activeUsersCount / $totalUsers) * 100, 1) : 0 }}%</span>
                            </div>
                        </div>
                        <div class="flex items-center justify-between p-3 rounded-xl bg-[#111] border border-[#1e1e1e]">
                            <div class="flex items-center gap-2.5">
                                <div class="w-2.5 h-2.5 rounded-sm bg-[#222]"></div>
                                <span class="text-[9px] font-bold text-[#888] uppercase">Inactive</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-[11px] font-bold text-[#555]">{{ $totalUsers - $activeUsersCount }}</span>
                                <span class="text-[8px] font-bold text-[#444]">{{ $totalUsers > 0 ? round((($totalUsers - $activeUsersCount) / $totalUsers) * 100, 1) : 0 }}%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── Fare Method Breakdown ── -->
                <div class="glass-card p-4 sm:p-6 rounded-[1.25rem] sm:rounded-[1.5rem]">
                    <div class="flex items-center gap-2.5 mb-5">
                        <div class="w-7 h-7 rounded-lg bg-[#111] border border-[#1e1e1e] flex items-center justify-center">
                            <i class="fa-solid fa-money-bill-transfer text-[9px] text-[#555]"></i>
                        </div>
                        <span class="text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-[#555]">Top-up Methods</span>
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
                                'other' => '#555',
                            ];
                        @endphp
                        @forelse($methodCounts as $method => $count)
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="w-2 h-2 rounded-sm" style="background: {{ $methodColors[$method] ?? '#555' }}"></div>
                                    <span class="text-[9px] font-bold text-[#888] uppercase">{{ $method }}</span>
                                </div>
                                <span class="text-[10px] font-bold text-[#ccc]">{{ $count }}</span>
                            </div>
                        @empty
                            <p class="text-[9px] text-[#333] text-center py-2">No data</p>
                        @endforelse
                    </div>
                </div>

                <!-- ── Management Controls ── -->
                <div class="glass-card p-4 sm:p-6 rounded-[1.25rem] sm:rounded-[1.5rem]">
                    <div class="flex items-center gap-2.5 mb-4 sm:mb-5">
                        <div class="w-7 h-7 rounded-lg bg-[#111] border border-[#1e1e1e] flex items-center justify-center">
                            <i class="fa-solid fa-gears text-[9px] text-[#555]"></i>
                        </div>
                        <span class="text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-[#555]">Management</span>
                    </div>
                    <div class="space-y-2">
                        <a href="{{ route('admin.commuters.index') }}" class="flex items-center justify-between p-3 sm:p-3.5 rounded-xl bg-[#111] border border-[#1e1e1e] hover:bg-[#1a1a1a] hover:border-[#333] transition group cursor-pointer">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-purple-500/10 border border-purple-500/15 flex items-center justify-center">
                                    <i class="fa-solid fa-users text-[9px] text-purple-400"></i>
                                </div>
                                <span class="text-[10px] font-bold text-[#888] group-hover:text-white transition">Commuters</span>
                            </div>
                            <i class="fa-solid fa-chevron-right text-[8px] text-[#333] group-hover:text-[#555] transition"></i>
                        </a>
                        <a href="{{ route('admin.drivers.index') }}" class="flex items-center justify-between p-3 sm:p-3.5 rounded-xl bg-[#111] border border-[#1e1e1e] hover:bg-[#1a1a1a] hover:border-[#333] transition group cursor-pointer">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-amber-500/10 border border-amber-500/15 flex items-center justify-center">
                                    <i class="fa-solid fa-id-badge text-[9px] text-amber-400"></i>
                                </div>
                                <span class="text-[10px] font-bold text-[#888] group-hover:text-white transition">Driver Verification</span>
                            </div>
                            <i class="fa-solid fa-chevron-right text-[8px] text-[#333] group-hover:text-[#555] transition"></i>
                        </a>
                    </div>
                </div>

                <!-- ── Financial Audit ── -->
                <div class="glass-card p-4 sm:p-6 rounded-[1.25rem] sm:rounded-[1.5rem]">
                    <div class="flex items-center gap-2.5 mb-4 sm:mb-5">
                        <div class="w-7 h-7 rounded-lg bg-[#111] border border-[#1e1e1e] flex items-center justify-center">
                            <i class="fa-solid fa-magnifying-glass text-[9px] text-[#555]"></i>
                        </div>
                        <span class="text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-[#555]">Financial Audit</span>
                    </div>
                    <div class="space-y-2">
                        <a href="{{ route('faretransactions') }}" class="flex items-center justify-between p-3 sm:p-3.5 rounded-xl bg-blue-500/5 border border-blue-500/10 hover:bg-blue-500/10 hover:border-blue-500/20 transition group cursor-pointer">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-blue-500/10 border border-blue-500/15 flex items-center justify-center">
                                    <i class="fa-solid fa-receipt text-[9px] text-blue-400"></i>
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold text-[#ccc] group-hover:text-white transition block">Audit Fares</span>
                                    <span class="text-[7px] text-[#555] font-bold uppercase">Fare transaction ledger</span>
                                </div>
                            </div>
                            <i class="fa-solid fa-chevron-right text-[8px] text-blue-500/30 group-hover:text-blue-400 transition"></i>
                        </a>
                        <a href="{{ route('admin.topups') }}" class="flex items-center justify-between p-3 sm:p-3.5 rounded-xl bg-emerald-500/5 border border-emerald-500/10 hover:bg-emerald-500/10 hover:border-emerald-500/20 transition group cursor-pointer">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-emerald-500/10 border border-emerald-500/15 flex items-center justify-center">
                                    <i class="fa-solid fa-wallet text-[9px] text-emerald-400"></i>
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold text-[#ccc] group-hover:text-white transition block">Audit Wallets</span>
                                    <span class="text-[7px] text-[#555] font-bold uppercase">Top-up & balance ledger</span>
                                </div>
                            </div>
                            <i class="fa-solid fa-chevron-right text-[8px] text-emerald-500/30 group-hover:text-emerald-400 transition"></i>
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <!-- ══════════ LOGOUT MODAL ══════════ -->
    <div x-show="showLogoutModal"
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/80"
        x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" style="display: none;">

        <div @click.away="showLogoutModal = false"
            class="glass-panel p-8 rounded-[2rem] max-w-sm w-full">
            <div class="text-center">
                <div class="w-14 h-14 bg-red-500/10 rounded-2xl flex items-center justify-center mx-auto mb-5 border border-red-500/20">
                    <i class="fa-solid fa-power-off text-red-400 text-lg"></i>
                </div>
                <h3 class="text-lg font-bold text-white mb-1.5">End Session?</h3>
                <p class="text-xs text-[#666] mb-7">Are you sure you want to exit the Admin Console?</p>

                <div class="flex gap-2.5">
                    <button @click="showLogoutModal = false"
                        class="flex-1 py-3 rounded-xl bg-[#1a1a1a] border border-[#2a2a2a] text-white text-[10px] font-bold uppercase tracking-widest hover:bg-[#222] transition">
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

    <!-- ══════════ CHART INITIALIZATION ══════════ -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            // ── Shared chart defaults ──
            Chart.defaults.font.family = "'Inter', sans-serif";
            Chart.defaults.color = '#444';

            const tooltipStyle = {
                backgroundColor: '#111',
                borderColor: '#1e1e1e',
                borderWidth: 1,
                titleColor: '#ccc',
                titleFont: { size: 10, weight: '700' },
                bodyColor: '#888',
                bodyFont: { size: 10, weight: '600' },
                padding: 10,
                cornerRadius: 10,
                displayColors: true,
                boxPadding: 4
            };

            // ══════════ 7-DAY REVENUE VS INFLOW BAR CHART ══════════
            const revenueCtx = document.getElementById('revenueChart');
            if (revenueCtx) {
                new Chart(revenueCtx, {
                    type: 'bar',
                    data: {
                        labels:  @json($chartLabels) ,
                        datasets: [
                            {
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
                                data:  @json($chartTopups),
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
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                ...tooltipStyle,
                                callbacks: {
                                    label: function (ctx) {
                                        return ' ' + ctx.dataset.label + ':  ₱' + ctx.parsed.y.toLocaleString('en-PH', { minimumFractionDigits: 2 });
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: { color: '#1a1a1a', drawBorder: false },
                                ticks: { font: { size: 9, weight: '600' }, color: '#444' },
                                border: { display: false }
                            },
                            y: {
                                beginAtZero: true,
                                grid: { color: '#1a1a1a', drawBorder: false },
                                ticks: {
                                    font: { size: 9, weight: '600' },
                                    color: '#333',
                                    callback: function (val) { return '₱' + val; },
                                    maxTicksLimit: 5
                                },
                                border: { display: false }
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
                            data: [{{ $activeUsersCount }}, {{ $totalUsers - $activeUsersCount }}],
                            backgroundColor: ['#a855f7', '#1e1e1e'],
                            hoverBackgroundColor: ['#a855f7', '#2a2a2a'],
                            borderColor: '#161616',
                            borderWidth: 3,
                            borderRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '72%',
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                ...tooltipStyle,
                                callbacks: {
                                    label: function (ctx) {
                                        const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                        const pct = total > 0 ? ((ctx.parsed / total) * 100).toFixed(1) : 0;
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
                const methods =  @json(array_keys($methodCounts)) ;
                const counts =  @json(array_values($methodCounts)) ;
                const colorMap = { gcash: '#3b82f6', maya: '#10b981', bank: '#a855f7', cash: '#f59e0b', other: '#555' };
                const barColors = methods.map(m => colorMap[m] || '#555');

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
                            legend: { display: false },
                            tooltip: {
                                ...tooltipStyle,
                                callbacks: {
                                    label: function (ctx) { return ' Count:  ' + ctx.parsed.x; }
                                }
                            }
                        },
                        scales: {
                            x: {
                                beginAtZero: true,
                                grid: { color: '#1a1a1a', drawBorder: false },
                                ticks: { font: { size: 9, weight: '600' }, color: '#333', stepSize: 1 },
                                border: { display: false }
                            },
                            y: {
                                grid: { display: false },
                                ticks: { font: { size: 9, weight: '700' }, color: '#555' },
                                border: { display: false }
                            }
                        }
                    }
                });
            }
        });
    </script>

</body>

</html>
