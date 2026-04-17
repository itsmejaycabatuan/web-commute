<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartCommute | Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap');

        body {
            background: #050505;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #fff;
            overflow-x: hidden;
        }

        .glass {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
    </style>
</head>

<body x-data="{ open: true }">

    @include('layout.sidebar')

    <main :class="open ? 'ml-72' : 'ml-20'" class="sidebar-transition p-8 md:p-12 min-h-screen">
        <div class="max-w-6xl">
            <header class="mb-10 flex justify-between items-end">
                <div>
                    <h2 class="text-3xl font-black tracking-tight mb-2">System Overview</h2>
                    <p class="text-gray-500 text-sm">Real-time financial health and user activity monitoring.</p>
                </div>
               
            </header>

            @isset($adminStats)
                @include('admin.partials.stats-grid', ['stats' => $adminStats])
            @endisset

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                <div class="glass p-5 rounded-2xl border border-white/5">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1">Total Fare Revenue
                    </p>
                    <h4 class="text-xl font-bold text-blue-400">₱{{ number_format($totalRevenue, 2) }}</h4>
                </div>
                <div class="glass p-5 rounded-2xl border border-white/5">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1">Total Wallet Inflow
                    </p>
                    <h4 class="text-xl font-bold text-emerald-400">₱{{ number_format($totalFundsAdded, 2) }}</h4>
                </div>
                <div class="glass p-5 rounded-2xl border border-white/5">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-500 mb-1">Active Payers</p>
                    <h4 class="text-xl font-bold text-white">{{ $activeUsersCount }} <span
                            class="text-[10px] text-gray-500 font-medium">Commuters</span></h4>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <div class="glass rounded-[2rem] border border-white/10 overflow-hidden shadow-2xl">
                    <div class="p-6 border-b border-white/5 flex justify-between items-center bg-white/[0.01]">
                        <div class="flex items-center gap-3">
                            <i class="fa-solid fa-receipt text-blue-400"></i>
                            <h3 class="text-xs font-black uppercase tracking-widest text-white">Fare Activity</h3>
                        </div>
                        <a href="{{ route('faretransactions') }}"
                            class="text-[10px] text-blue-400 hover:text-white uppercase font-bold tracking-tighter transition">View
                            Ledger</a>
                    </div>
                    <div class="divide-y divide-white/5">
                        @forelse($recentFares ?? [] as $fare)
                            <div class="p-4 hover:bg-white/[0.02] transition flex justify-between items-center">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-8 h-8 rounded-full bg-white/5 flex items-center justify-center border border-white/10">
                                        <i class="fa-solid fa-user text-[10px] text-gray-500"></i>
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold text-white">{{ $fare->user->email }}</p>
                                        <p class="text-[9px] text-gray-500 uppercase">
                                            {{ $fare->created_at->format('h:i A') }}
                                        </p>
                                    </div>
                                </div>
                                <span class="text-xs font-bold text-white">-₱{{ number_format($fare->price, 2) }}</span>
                            </div>
                        @empty
                            <div class="p-10 text-center opacity-30">
                                <p class="text-xs">No recent fare activity</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="glass rounded-[2rem] border border-white/10 overflow-hidden shadow-2xl">
                    <div class="p-6 border-b border-white/5 flex justify-between items-center bg-white/[0.01]">
                        <div class="flex items-center gap-3">
                            <i class="fa-solid fa-wallet text-emerald-400"></i>
                            <h3 class="text-xs font-black uppercase tracking-widest text-white">Wallet Inflow</h3>
                        </div>
                        <a href="{{ route('admin.topups') }}"
                            class="text-[10px] text-emerald-400 hover:text-white uppercase font-bold tracking-tighter transition">View
                            Ledger</a>
                    </div>
                    <div class="divide-y divide-white/5">
                        @forelse($recentTopups ?? [] as $topup)
                            <div class="p-4 hover:bg-white/[0.02] transition flex justify-between items-center">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-8 h-8 rounded-full bg-white/5 flex items-center justify-center border border-white/10">
                                        <i class="fa-solid fa-arrow-up text-[10px] text-emerald-500"></i>
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold text-white">{{ $topup->user->email }}</p>
                                        <p class="text-[9px] text-gray-500 uppercase">{{ $topup->payment_method }} •
                                            {{ $topup->created_at->format('h:i A') }}
                                        </p>
                                    </div>
                                </div>
                                <span
                                    class="text-xs font-bold text-emerald-400">+₱{{ number_format($topup->amount_added, 2) }}</span>
                            </div>
                        @empty
                            <div class="p-10 text-center opacity-30">
                                <p class="text-xs">No recent top-up activity</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="glass rounded-[2.5rem] border border-white/10 p-8 relative overflow-hidden">
                <div class="absolute top-0 right-0 p-8 opacity-5">
                    <i class="fa-solid fa-gears text-6xl"></i>
                </div>
                <h3 class="text-xs font-black uppercase tracking-widest text-blue-400 mb-2">Management Controls</h3>
                <p class="text-sm text-gray-500 mb-6 max-w-md">Access specialized tools for driver vetting, route
                    optimization, and financial auditing.</p>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('admin.commuters.index') }}"
                        class="px-5 py-2.5 rounded-xl text-[10px] font-bold uppercase tracking-widest bg-white/5 hover:bg-white/10 text-white transition border border-white/10">Commuters</a>
                    <a href="{{ route('admin.drivers.index') }}"
                        class="px-5 py-2.5 rounded-xl text-[10px] font-bold uppercase tracking-widest bg-white/5 hover:bg-white/10 text-white transition border border-white/10">Driver
                        Verification</a>
                    <div class="w-px h-10 bg-white/10 mx-2"></div>
                    <a href="{{ route('faretransactions') }}"
                        class="px-5 py-2.5 rounded-xl text-[10px] font-bold uppercase tracking-widest bg-blue-600/10 hover:bg-blue-600/20 text-blue-400 transition border border-blue-500/20">Audit
                        Fares</a>
                    <a href="{{ route('admin.topups') }}"
                        class="px-5 py-2.5 rounded-xl text-[10px] font-bold uppercase tracking-widest bg-emerald-600/10 hover:bg-emerald-600/20 text-emerald-400 transition border border-emerald-500/20">Audit
                        Wallets</a>
                </div>
            </div>
        </div>
    </main>

</body>

</html>