<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SmartCommute | Wallet Top-ups</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    @include('partials.head-scripts')
    <style>
        .sidebar-transition {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }

        .table-row {
            transition: all 0.2s ease !important;
        }

        [x-cloak] {
            display: none !important;
        }

        .dark input[type="date"]::-webkit-calendar-picker-indicator {
            filter: invert(0.4);
            cursor: pointer;
        }

        .pagination {
            display: flex;
            gap: 4px;
            flex-wrap: wrap;
        }

        .pagination a,
        .pagination span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 32px;
            height: 32px;
            padding: 0 8px;
            border-radius: 8px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            transition: all 0.2s;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            color: #64748b;
        }

        .pagination a:hover {
            background: #f1f5f9;
            color: #1e293b;
            border-color: #cbd5e1;
        }

        .pagination .active {
            background-color: #10b981;
            color: #fff;
            border-color: #10b981;
        }

        .pagination .disabled {
            opacity: 0.3;
            pointer-events: none;
        }

        .dark .pagination a,
        .dark .pagination span {
            border-color: #1e1e1e;
            background: #111;
            color: #555;
        }

        .dark .pagination a:hover {
            background: #1a1a1a;
            color: #ccc;
            border-color: #333;
        }
    </style>
</head>

<body class="antialiased text-gray-900 dark:text-white" x-data>
    <div x-data="{ showDetailModal: false, selectedTx: null }">
        <x-layout.sidebar />

        <main :class="$store.sidebar.open ? 'md:ml-72' : 'md:ml-20'"
            class="sidebar-transition pt-8 pr-4 sm:pr-8 pb-8 pl-4 sm:pl-8 min-h-screen mb-12">

            <!-- ── Mobile: Admin Identity Card ── -->
            <div class="lg:hidden mb-5">
                <div class="glass-card p-4 rounded-[1.25rem]">
                    <div class="flex items-center gap-3.5">
                        <div class="w-11 h-11 bg-red-600 rounded-xl flex items-center justify-center shrink-0">
                            <span
                                class="text-sm font-black text-white">{{ strtoupper(substr(explode('@', Auth::user()->email)[0], 0, 1)) }}</span>
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-sm font-bold text-gray-900 dark:text-white truncate">System Administrator
                            </h2>
                            <p class="text-[10px] text-gray-500 dark:text-[#555] truncate">{{ Auth::user()->email }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 pt-3.5 mt-3.5 border-t border-gray-200 dark:border-[#1e1e1e]">
                        <i class="fa-solid fa-shield-halved text-[8px] text-red-500 dark:text-red-400"></i>
                        <span class="text-[10px] text-gray-600 dark:text-[#888] font-bold">Full Access</span>
                        <span class="text-gray-300 dark:text-[#333]">•</span>
                        <span class="font-mono text-[9px] text-gray-400 dark:text-[#444]">Admin</span>
                    </div>
                </div>
            </div>

            <!-- ── Page Header ── -->
            <div class="hidden lg:block mb-8">
                <div class="flex items-center gap-2 mb-1.5">
                    <span
                        class="text-[9px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Audit</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-gray-900 dark:text-white">Wallet <span
                        class="text-emerald-500 dark:text-emerald-400">Top-ups</span></h1>
                <p class="text-[11px] text-gray-500 dark:text-[#555] mt-1 flex items-center gap-2">
                    <i class="fa-solid fa-wallet text-[9px] text-emerald-500 dark:text-emerald-400"></i>
                    Audit trail for all commuter wallet fund additions
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

            @php
                $totalTopupCount =
                    $transactions instanceof \Illuminate\Pagination\LengthAwarePaginator
                        ? $transactions->total()
                        : count($transactions);
                $methodCounts = $transactions->groupBy('payment_method')->map->count()->sortDesc();
                $topMethod = $methodCounts->keys()->first();
                $topMethodCount = $methodCounts->first() ?? 0;
                $avgTopup = $totalTopupCount > 0 ? $totalFundsAdded / $totalTopupCount : 0;
            @endphp

            <!-- ══════════ STAT CARDS ══════════ -->
            <div class="grid grid-cols-2 xl:grid-cols-4 gap-3 sm:gap-4 mb-5 sm:mb-6">
                <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-emerald-500">
                    <div class="flex items-center gap-2 mb-2 sm:mb-3">
                        <div class="w-6 h-6 rounded-md bg-emerald-500/10 flex items-center justify-center">
                            <i class="fa-solid fa-peso-sign text-[8px] text-emerald-500 dark:text-emerald-400"></i>
                        </div>
                        <span
                            class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Total
                            Inflow</span>
                    </div>
                    <span
                        class="text-2xl sm:text-3xl font-black tracking-tight text-emerald-500 dark:text-emerald-400">₱{{ number_format($totalFundsAdded, 2) }}</span>
                </div>

                <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-blue-500">
                    <div class="flex items-center gap-2 mb-2 sm:mb-3">
                        <div class="w-6 h-6 rounded-md bg-blue-500/10 flex items-center justify-center">
                            <i class="fa-solid fa-hashtag text-[8px] text-blue-500 dark:text-blue-400"></i>
                        </div>
                        <span
                            class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Top-ups</span>
                    </div>
                    <span
                        class="text-2xl sm:text-3xl font-black tracking-tight text-gray-900 dark:text-white">{{ number_format($totalTopupCount) }}</span>
                </div>

                <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-purple-500">
                    <div class="flex items-center gap-2 mb-2 sm:mb-3">
                        <div class="w-6 h-6 rounded-md bg-purple-500/10 flex items-center justify-center">
                            <i class="fa-solid fa-chart-line text-[8px] text-purple-500 dark:text-purple-400"></i>
                        </div>
                        <span
                            class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Avg
                            Top-up</span>
                    </div>
                    <span
                        class="text-2xl sm:text-3xl font-black tracking-tight text-purple-500 dark:text-purple-400">₱{{ number_format($avgTopup, 2) }}</span>
                </div>

                <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-amber-500">
                    <div class="flex items-center gap-2 mb-2 sm:mb-3">
                        <div class="w-6 h-6 rounded-md bg-amber-500/10 flex items-center justify-center">
                            <i class="fa-solid fa-crown text-[8px] text-amber-500 dark:text-amber-400"></i>
                        </div>
                        <span
                            class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Top
                            Method</span>
                    </div>
                    <span
                        class="text-lg sm:text-xl font-black tracking-tight text-amber-500 dark:text-amber-400">{{ $topMethod ?? 'N/A' }}</span>
                    <p class="text-[7px] sm:text-[8px] text-gray-400 dark:text-[#333] mt-1 font-medium"
                        x-show="{{ $topMethodCount > 0 ? 'true' : 'false' }}">{{ $topMethodCount }} transactions</p>
                </div>
            </div>

            <!-- ══════════ FILTER BAR ══════════ -->
            <div class="glass-card rounded-[1.25rem] p-4 sm:p-5 mb-5">
                <form action="{{ route('admin.topups') }}" method="GET" class="flex flex-col sm:flex-row gap-3">
                    <div class="relative flex-1">
                        <i
                            class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-[10px] text-gray-400 dark:text-[#333]"></i>
                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="Email or user ID..."
                            class="w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] rounded-xl text-[11px] text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-[#333] focus:outline-none focus:border-gray-300 dark:focus:border-[#333] transition">
                    </div>
                    <div class="relative">
                        <i
                            class="fa-regular fa-calendar absolute left-3.5 top-1/2 -translate-y-1/2 text-[10px] text-gray-400 dark:text-[#333]"></i>
                        <input type="date" name="from_date" value="{{ request('from_date') }}"
                            class="w-full sm:w-auto pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] rounded-xl text-[11px] text-gray-900 dark:text-white focus:outline-none focus:border-gray-300 dark:focus:border-[#333] transition">
                    </div>
                    <button type="submit"
                        class="flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-[9px] font-bold uppercase tracking-widest text-white transition active:scale-[0.98] shrink-0">
                        <i class="fa-solid fa-filter text-[8px]"></i>
                        <span>Filter</span>
                    </button>
                    @if (request('search') || request('from_date'))
                        <a href="{{ route('admin.topups') }}"
                            class="flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-gray-50 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] hover:border-gray-300 dark:hover:border-[#333] text-[9px] font-bold uppercase tracking-widest text-gray-500 dark:text-[#555] hover:text-gray-700 dark:hover:text-[#888] transition shrink-0">
                            <i class="fa-solid fa-xmark text-[8px]"></i>
                            <span>Clear</span>
                        </a>
                    @endif
                </form>
            </div>

            <!-- ══════════ TABLE ══════════ -->
            <div class="glass-card rounded-[1.25rem] sm:rounded-[1.5rem] overflow-hidden">
                <div class="overflow-x-auto -mx-2 px-2 pb-2">
                    <table class="w-full text-left min-w-[650px]">
                        <thead>
                            <tr
                                class="text-[8px] sm:text-[9px] uppercase tracking-[0.15em] text-gray-400 dark:text-[#444] border-b border-gray-200 dark:border-[#1e1e1e]">
                                <th class="px-4 sm:px-6 py-3 font-bold">Commuter</th>
                                <th class="px-4 sm:px-6 py-3 font-bold">Transaction</th>
                                <th class="px-4 sm:px-6 py-3 font-bold">Method</th>
                                <th class="px-4 sm:px-6 py-3 font-bold">Timestamp</th>
                                <th class="px-4 sm:px-6 py-3 font-bold text-right">Amount</th>
                                <th class="px-4 sm:px-6 py-3 font-bold text-right w-16">Info</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-[#1a1a1a]">
                            @forelse ($transactions as $tx)
                                @php
                                    $methodColor = match (strtolower($tx->payment_method ?? '')) {
                                        'gcash' => 'blue',
                                        'maya' => 'emerald',
                                        'bank' => 'purple',
                                        'cash' => 'amber',
                                        default => 'gray',
                                    };
                                @endphp
                                <tr class="table-row">
                                    <td class="px-4 sm:px-6 py-3.5">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="w-8 h-8 rounded-lg bg-emerald-500/5 border border-emerald-500/10 flex items-center justify-center shrink-0">
                                                <i
                                                    class="fa-solid fa-arrow-up text-[9px] text-emerald-500/60 dark:text-emerald-400/60"></i>
                                            </div>
                                            <p
                                                class="text-[10px] sm:text-[11px] font-bold text-gray-700 dark:text-[#ccc] truncate max-w-[200px]">
                                                {{ $tx->user->email }}</p>
                                        </div>
                                    </td>
                                    <td class="px-4 sm:px-6 py-3.5">
                                        <span
                                            class="text-[10px] font-bold text-emerald-500 dark:text-emerald-400 font-mono">#{{ $tx->id }}</span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-3.5">
                                        <span
                                            class="text-[7px] sm:text-[8px] bg-{{ $methodColor }}-500/10 text-{{ $methodColor }}-600 dark:text-{{ $methodColor }}-400 border border-{{ $methodColor }}-500/15 px-2 py-0.5 rounded-md font-bold uppercase">{{ $tx->payment_method }}</span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-3.5">
                                        <span
                                            class="text-[10px] font-bold text-gray-500 dark:text-[#888]">{{ $tx->created_at->format('M j, Y') }}</span>
                                        <p class="text-[8px] text-gray-400 dark:text-[#444]">
                                            {{ $tx->created_at->format('g:i A') }}</p>
                                    </td>
                                    <td class="px-4 sm:px-6 py-3.5 text-right">
                                        <span
                                            class="text-[11px] font-bold text-emerald-500 dark:text-emerald-400">+₱{{ number_format($tx->amount_added, 2) }}</span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-3.5 text-right">
                                        <button
                                            @click="selectedTx = { email: '{{ $tx->user->email }}', txId: '#{{ $tx->id }}', method: '{{ $tx->payment_method }}', date: '{{ $tx->created_at->format('M j, Y g:i A') }}', amount: '{{ number_format($tx->amount_added, 2) }}' }; showDetailModal = true"
                                            class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] hover:bg-gray-200 dark:hover:bg-[#1a1a1a] hover:border-gray-300 dark:hover:border-[#333] flex items-center justify-center transition group ml-auto"
                                            title="View details">
                                            <i
                                                class="fa-solid fa-arrow-up-right-from-square text-[8px] text-gray-400 dark:text-[#444] group-hover:text-gray-900 dark:group-hover:text-white transition"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-12 sm:py-16">
                                        <div class="flex flex-col items-center justify-center">
                                            <div
                                                class="w-12 h-12 rounded-xl bg-gray-100 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] flex items-center justify-center mb-3">
                                                <i
                                                    class="fa-solid fa-wallet text-base text-gray-300 dark:text-[#222]"></i>
                                            </div>
                                            <p class="text-[11px] text-gray-400 dark:text-[#444] font-medium">No top-up
                                                records found</p>
                                            @if (request('search') || request('from_date'))
                                                <button onclick="window.location.href='{{ route('admin.topups') }}'"
                                                    class="mt-3 text-[9px] font-bold uppercase tracking-widest text-emerald-500 dark:text-emerald-400 hover:text-emerald-600 dark:hover:text-white transition">Clear
                                                    filters</button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($transactions->hasPages())
                    <div class="px-4 sm:px-6 py-4 border-t border-gray-200 dark:border-[#1e1e1e]">
                        {{ $transactions->links() }}
                    </div>
                @endif
            </div>
        </main>

        <!-- ══════════ TOP-UP DETAIL MODAL ══════════ -->
        <div x-show="showDetailModal" x-cloak
            class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 dark:bg-black/80"
            @click.self="showDetailModal = false" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" style="display:none;">

            <div class="glass-panel p-6 sm:p-8 rounded-[2rem] max-w-sm w-full">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-9 h-9 rounded-xl bg-emerald-500/10 border border-emerald-500/15 flex items-center justify-center">
                            <i class="fa-solid fa-wallet text-xs text-emerald-500 dark:text-emerald-400"></i>
                        </div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white">Top-up Detail</h3>
                    </div>
                    <button @click="showDetailModal = false"
                        class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-[#1a1a1a] border border-gray-200 dark:border-[#2a2a2a] flex items-center justify-center hover:bg-gray-200 dark:hover:bg-[#222] transition">
                        <i class="fa-solid fa-xmark text-[10px] text-gray-500 dark:text-[#555]"></i>
                    </button>
                </div>

                <div class="space-y-2.5">
                    <div
                        class="flex items-center justify-between p-3.5 rounded-xl bg-gray-50 dark:bg-[#0a0a0a] border border-gray-200 dark:border-[#1e1e1e]">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-7 h-7 rounded-lg bg-gray-100 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-user text-[8px] text-gray-400 dark:text-[#444]"></i>
                            </div>
                            <span
                                class="text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Commuter</span>
                        </div>
                        <span class="text-[10px] font-bold text-gray-700 dark:text-[#ccc] truncate max-w-[160px]"
                            x-text="selectedTx?.email"></span>
                    </div>
                    <div
                        class="flex items-center justify-between p-3.5 rounded-xl bg-gray-50 dark:bg-[#0a0a0a] border border-gray-200 dark:border-[#1e1e1e]">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-7 h-7 rounded-lg bg-emerald-500/5 border border-emerald-500/10 flex items-center justify-center shrink-0">
                                <i
                                    class="fa-solid fa-hashtag text-[8px] text-emerald-500/60 dark:text-emerald-400/60"></i>
                            </div>
                            <span
                                class="text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">TX
                                #</span>
                        </div>
                        <span class="text-[10px] font-bold text-emerald-500 dark:text-emerald-400 font-mono"
                            x-text="selectedTx?.txId"></span>
                    </div>
                    <div
                        class="flex items-center justify-between p-3.5 rounded-xl bg-gray-50 dark:bg-[#0a0a0a] border border-gray-200 dark:border-[#1e1e1e]">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-7 h-7 rounded-lg bg-gray-100 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-credit-card text-[8px] text-gray-400 dark:text-[#444]"></i>
                            </div>
                            <span
                                class="text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Method</span>
                        </div>
                        <span class="text-[9px] font-bold text-gray-600 dark:text-[#888] uppercase"
                            x-text="selectedTx?.method"></span>
                    </div>
                    <div
                        class="flex items-center justify-between p-3.5 rounded-xl bg-gray-50 dark:bg-[#0a0a0a] border border-gray-200 dark:border-[#1e1e1e]">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-7 h-7 rounded-lg bg-gray-100 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] flex items-center justify-center shrink-0">
                                <i class="fa-regular fa-clock text-[8px] text-gray-400 dark:text-[#444]"></i>
                            </div>
                            <span
                                class="text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Date</span>
                        </div>
                        <span class="text-[10px] font-bold text-gray-600 dark:text-[#888]"
                            x-text="selectedTx?.date"></span>
                    </div>
                    <div
                        class="flex items-center justify-between p-3.5 rounded-xl bg-emerald-500/5 border border-emerald-500/10">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-7 h-7 rounded-lg bg-emerald-500/10 border border-emerald-500/15 flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-peso-sign text-[8px] text-emerald-500 dark:text-emerald-400"></i>
                            </div>
                            <span
                                class="text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Added</span>
                        </div>
                        <span class="text-[12px] font-black text-emerald-500 dark:text-emerald-400"
                            x-text="'+₱' + selectedTx?.amount"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
