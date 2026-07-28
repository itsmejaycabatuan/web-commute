<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartCommute | Payment History</title>
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
        input:focus, select:focus { border-color: rgba(59, 130, 246, 0.4) !important; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.08); }

        .section-toggle-icon { transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
        .section-toggle-icon.rotated { transform: rotate(180deg); }
        .section-body { max-height: 0; overflow: hidden; transition: max-height 0.4s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.3s ease; opacity: 0; }
        .section-body.open { max-height: 600px; opacity: 1; }

        .table-row { transition: all 0.2s ease; }
        .table-row:hover { background: #1a1a1a; }

        .card-row { transition: all 0.2s ease; }
        .card-row:hover { background: #1a1a1a; border-color: #333; }

        ::-webkit-scrollbar { width: 3px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #333; border-radius: 10px; }

        /* Pagination base */
        .pagination { display: flex; align-items: center; gap: 4px; }
        .pagination a, .pagination span {
            display: flex; align-items: center; justify-content: center;
            min-width: 32px; height: 32px; padding: 0 8px;
            border-radius: 8px; font-size: 10px; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.05em;
            transition: all 0.2s ease; cursor: pointer; border: 1px solid transparent;
        }
        .pagination a {
            color: #666; background: #111; border-color: #1e1e1e;
        }
        .pagination a:hover { color: #fff; background: #1a1a1a; border-color: #333; }
        .pagination .active {
            color: #fff; background: #2563eb; border-color: #2563eb;
            box-shadow: 0 2px 12px rgba(37, 99, 235, 0.25);
        }
        .pagination .disabled { color: #333; background: #0a0a0a; border-color: #151515; cursor: default; pointer-events: none; }
    </style>
</head>

<body class="antialiased text-white">

    <!-- ══════════ HEADER ══════════ -->
    <header class="fixed top-4 left-4 right-4 sm:top-5 sm:left-5 sm:right-5 z-50 flex items-center justify-between gap-3 pointer-events-none">
        <div class="glass-panel p-3 sm:p-3.5 rounded-2xl pointer-events-auto flex items-center gap-3 min-w-0">
            <a href="{{ route('map') }}" class="header-btn w-9 h-9 rounded-xl flex items-center justify-center shrink-0 border border-[#1e1e1e] bg-[#111] hover:bg-[#1a1a1a] transition cursor-pointer">
                <i class="fa-solid fa-arrow-left text-[10px] text-[#666]"></i>
            </a>
            <div class="w-px h-6 bg-[#222] mx-0.5 hidden sm:block"></div>
            <div class="w-8 h-8 sm:w-9 sm:h-9 bg-blue-600 rounded-lg sm:rounded-xl flex items-center justify-center shrink-0">
                <i class="fa-solid fa-bus text-white text-xs sm:text-sm"></i>
            </div>
            <span class="text-[13px] sm:text-sm font-bold tracking-tight text-white whitespace-nowrap">Smart<span class="text-blue-400">Commute</span></span>
            <div class="w-px h-6 bg-[#222] mx-0.5 hidden sm:block"></div>
            <span class="text-[9px] sm:text-[10px] font-bold uppercase tracking-[0.15em] text-[#555] hidden sm:inline">Payments</span>
        </div>
        <div class="flex items-center gap-2 pointer-events-auto z-50 shrink-0">
            <div class="hidden sm:flex items-center gap-3 glass-panel px-4 py-2 rounded-xl">
                <div class="text-right">
                    <p class="text-[7px] font-bold uppercase tracking-[0.15em] text-[#444]">Total Spent</p>
                    <p class="text-[11px] font-bold text-blue-400 leading-tight">₱{{ number_format($totalSpent, 2) }}</p>
                </div>
                <div class="w-px h-6 bg-[#222]"></div>
                <div class="text-right">
                    <p class="text-[7px] font-bold uppercase tracking-[0.15em] text-[#444]">Balance</p>
                    <p class="text-[11px] font-bold text-emerald-400 leading-tight">₱{{ number_format($balance, 2) }}</p>
                </div>
            </div>
            <a href="{{ route('profile') }}" class="header-btn glass-panel w-9 h-9 rounded-xl flex items-center justify-center text-white cursor-pointer">
                <i class="fa-solid fa-circle-user text-[10px] text-[#555]"></i>
            </a>
        </div>
    </header>

    <!-- ══════════ MAIN CONTENT ══════════ -->
    <div class="pt-20 sm:pt-24 pb-8 sm:pb-10 max-w-6xl mx-auto px-4 sm:px-6 lg:px-5">

        <!-- Page Title -->
        <div class="mb-8">
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">Payment <span class="text-blue-400">History</span></h1>
            <p class="text-[11px] text-[#555] mt-1.5">View and search your complete transaction ledger.</p>
        </div>

        <!-- ── Mobile: Stats Cards ── -->
        <div class="sm:hidden grid grid-cols-2 gap-3 mb-6">
            <div class="glass-card p-4 rounded-[1.25rem]">
                <p class="text-[8px] font-bold uppercase tracking-[0.15em] text-[#444] mb-1">Total Spent</p>
                <p class="text-lg font-black text-blue-400">₱{{ number_format($totalSpent, 2) }}</p>
            </div>
            <div class="glass-card p-4 rounded-[1.25rem]">
                <p class="text-[8px] font-bold uppercase tracking-[0.15em] text-[#444] mb-1">Balance</p>
                <p class="text-lg font-black text-emerald-400">₱{{ number_format($balance, 2) }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            <!-- ══════════ LEFT SIDEBAR (desktop filters) ══════════ -->
            <div class="hidden lg:block lg:col-span-4">
                <div class="glass-card p-6 rounded-[1.5rem] sticky top-24">
                    <div class="flex items-center gap-2.5 mb-5">
                        <div class="w-8 h-8 rounded-lg bg-[#111] border border-[#1e1e1e] flex items-center justify-center">
                            <i class="fa-solid fa-filter text-[10px] text-[#555]"></i>
                        </div>
                        <span class="text-[9px] font-bold uppercase tracking-[0.15em] text-[#555]">Filters</span>
                    </div>

                    <form action="{{ route('payment.history') }}" method="GET" class="space-y-4">
                        <!-- Search -->
                        <div class="space-y-1.5">
                            <label class="text-[9px] font-bold uppercase tracking-[0.15em] text-[#444] ml-1">Search</label>
                            <div class="relative">
                                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-[10px] text-[#444]"></i>
                                <input type="text" name="search" value="{{ request('search') }}" placeholder="Transaction ID or destination"
                                    class="w-full bg-[#111] border border-[#1e1e1e] rounded-xl pl-10 pr-4 py-2.5 text-[12px] text-white outline-none transition">
                            </div>
                        </div>

                        <!-- Date Range -->
                        <div class="space-y-1.5">
                            <label class="text-[9px] font-bold uppercase tracking-[0.15em] text-[#444] ml-1">Date Range</label>
                            <div class="grid grid-cols-2 gap-2">
                                <div class="relative">
                                    <i class="fa-regular fa-calendar absolute left-3 top-1/2 -translate-y-1/2 text-[9px] text-[#444]"></i>
                                    <input type="date" name="from_date" value="{{ request('from_date') }}"
                                        class="w-full bg-[#111] border border-[#1e1e1e] rounded-xl pl-9 pr-2 py-2.5 text-[11px] text-white outline-none transition">
                                </div>
                                <div class="relative">
                                    <i class="fa-regular fa-calendar absolute left-3 top-1/2 -translate-y-1/2 text-[9px] text-[#444]"></i>
                                    <input type="date" name="to_date" value="{{ request('to_date') }}"
                                        class="w-full bg-[#111] border border-[#1e1e1e] rounded-xl pl-9 pr-2 py-2.5 text-[11px] text-white outline-none transition">
                                </div>
                            </div>
                        </div>

                        <!-- Quick Range Buttons -->
                        <div class="space-y-1.5">
                            <label class="text-[9px] font-bold uppercase tracking-[0.15em] text-[#444] ml-1">Quick Range</label>
                            <div class="grid grid-cols-3 gap-1.5">
                                <button type="button" onclick="setQuickRange(7)" class="py-2 rounded-lg bg-[#111] border border-[#1e1e1e] text-[9px] font-bold text-[#555] hover:text-white hover:border-[#333] transition">7 Days</button>
                                <button type="button" onclick="setQuickRange(30)" class="py-2 rounded-lg bg-[#111] border border-[#1e1e1e] text-[9px] font-bold text-[#555] hover:text-white hover:border-[#333] transition">30 Days</button>
                                <button type="button" onclick="setQuickRange(90)" class="py-2 rounded-lg bg-[#111] border border-[#1e1e1e] text-[9px] font-bold text-[#555] hover:text-white hover:border-[#333] transition">90 Days</button>
                            </div>
                        </div>

                        <!-- Actions -->
                        <button type="submit"
                            class="w-full bg-blue-600 hover:bg-blue-500 py-3 rounded-xl text-[10px] font-bold uppercase tracking-wider transition shadow-lg shadow-blue-600/20 active:scale-[0.98] flex items-center justify-center gap-2">
                            <i class="fa-solid fa-magnifying-glass text-[9px]"></i>
                            Apply Filters
                        </button>

                        @if(request()->hasAny(['from_date', 'to_date', 'search']))
                            <a href="{{ route('payment.history') }}"
                                class="block text-center text-[9px] text-[#444] hover:text-white font-bold uppercase tracking-wider transition py-1">
                                <i class="fa-solid fa-xmark text-[8px] mr-1"></i> Clear Filters
                            </a>
                        @endif
                    </form>
                </div>
            </div>

            <!-- ══════════ RIGHT CONTENT ══════════ -->
            <div class="lg:col-span-8 flex flex-col gap-6">

                <!-- ── Mobile: Filter Panel (collapsible) ── -->
                <div class="lg:hidden">
                    <button onclick="toggleSection('filters')" class="w-full glass-card p-4 rounded-[1.25rem] flex items-center justify-between cursor-pointer hover:border-[#333] transition">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-[#111] border border-[#1e1e1e] flex items-center justify-center">
                                <i class="fa-solid fa-filter text-[10px] text-[#555]"></i>
                            </div>
                            <div class="text-left">
                                <span class="text-[10px] font-bold uppercase tracking-[0.15em] text-[#555]">Filters</span>
                                @if(request()->hasAny(['from_date', 'to_date', 'search']))
                                    <span class="ml-2 text-[8px] bg-blue-500/15 text-blue-400 px-1.5 py-0.5 rounded font-bold">Active</span>
                                @endif
                            </div>
                        </div>
                        <i id="filters-icon" class="fa-solid fa-chevron-down text-[10px] text-[#555] section-toggle-icon"></i>
                    </button>
                    <div id="filters-body" class="section-body">
                        <div class="glass-card border-t-0 rounded-t-none p-4 pt-3">
                            <form action="{{ route('payment.history') }}" method="GET" class="space-y-3">
                                <!-- Search -->
                                <div class="relative">
                                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-[10px] text-[#444]"></i>
                                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Transaction ID or destination"
                                        class="w-full bg-[#111] border border-[#1e1e1e] rounded-xl pl-10 pr-4 py-2.5 text-[12px] text-white outline-none transition">
                                </div>
                                <!-- Date Range -->
                                <div class="grid grid-cols-2 gap-2">
                                    <div class="relative">
                                        <i class="fa-regular fa-calendar absolute left-3 top-1/2 -translate-y-1/2 text-[9px] text-[#444]"></i>
                                        <input type="date" name="from_date" value="{{ request('from_date') }}"
                                            class="w-full bg-[#111] border border-[#1e1e1e] rounded-xl pl-9 pr-2 py-2.5 text-[11px] text-white outline-none transition">
                                    </div>
                                    <div class="relative">
                                        <i class="fa-regular fa-calendar absolute left-3 top-1/2 -translate-y-1/2 text-[9px] text-[#444]"></i>
                                        <input type="date" name="to_date" value="{{ request('to_date') }}"
                                            class="w-full bg-[#111] border border-[#1e1e1e] rounded-xl pl-9 pr-2 py-2.5 text-[11px] text-white outline-none transition">
                                    </div>
                                </div>
                                <!-- Quick Range -->
                                <div class="grid grid-cols-3 gap-1.5">
                                    <button type="button" onclick="setQuickRange(7)" class="py-2 rounded-lg bg-[#111] border border-[#1e1e1e] text-[9px] font-bold text-[#555] hover:text-white hover:border-[#333] transition">7 Days</button>
                                    <button type="button" onclick="setQuickRange(30)" class="py-2 rounded-lg bg-[#111] border border-[#1e1e1e] text-[9px] font-bold text-[#555] hover:text-white hover:border-[#333] transition">30 Days</button>
                                    <button type="button" onclick="setQuickRange(90)" class="py-2 rounded-lg bg-[#111] border border-[#1e1e1e] text-[9px] font-bold text-[#555] hover:text-white hover:border-[#333] transition">90 Days</button>
                                </div>
                                <div class="flex gap-2">
                                    <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-500 py-2.5 rounded-xl text-[10px] font-bold uppercase tracking-wider transition active:scale-[0.98]">Apply</button>
                                    @if(request()->hasAny(['from_date', 'to_date', 'search']))
                                        <a href="{{ route('payment.history') }}" class="px-4 py-2.5 rounded-xl bg-[#111] border border-[#1e1e1e] text-[10px] font-bold text-[#555] hover:text-white hover:border-[#333] transition flex items-center">
                                            <i class="fa-solid fa-xmark text-[9px]"></i>
                                        </a>
                                    @endif
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- ── Results Count ── -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-[#111] border border-[#1e1e1e] flex items-center justify-center">
                            <i class="fa-solid fa-receipt text-[10px] text-[#555]"></i>
                        </div>
                        <span class="text-[9px] font-bold uppercase tracking-[0.15em] text-[#555]">Recent Receipts</span>
                    </div>
                    <span class="text-[9px] font-bold text-[#333]">{{ $recentReceipts->total() }} results</span>
                </div>

                <!-- ── Desktop: Table View ── -->
                <div class="hidden lg:block glass-card rounded-[1.5rem] overflow-hidden">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-[9px] uppercase tracking-[0.15em] text-[#444] border-b border-[#1e1e1e]">
                                <th class="px-6 py-3.5 font-bold">Transaction</th>
                                <th class="px-6 py-3.5 font-bold">Details</th>
                                <th class="px-6 py-3.5 font-bold">Date</th>
                                <th class="px-6 py-3.5 font-bold text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#1a1a1a]">
                            @forelse($recentReceipts as $receipt)
                                <tr class="table-row cursor-pointer"
                                    onclick="window.location.href='{{ route('payment.showReceipt', $receipt->id) }}'">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-lg bg-[#111] border border-[#1e1e1e] flex items-center justify-center shrink-0 group-hover:border-blue-500/30 transition">
                                                <i class="fa-solid fa-receipt text-[10px] text-[#444]"></i>
                                            </div>
                                            <span class="text-[11px] font-bold text-[#ccc] font-mono">{{ $receipt->transaction_id }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="text-[11px] font-bold text-[#888]">Ride to {{ $receipt->destination ?? 'Downtown' }}</p>
                                        <p class="text-[8px] text-[#444] font-bold uppercase tracking-wider mt-0.5">Standard Regular Fare</p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="text-[11px] text-[#666]">{{ $receipt->paid_at }}</p>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <span class="text-[12px] font-bold text-white">-₱{{ number_format($receipt->price, 2) }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-16">
                                        <div class="flex flex-col items-center justify-center">
                                            <div class="w-12 h-12 rounded-xl bg-[#111] border border-[#1e1e1e] flex items-center justify-center mb-3">
                                                <i class="fa-solid fa-box-open text-lg text-[#333]"></i>
                                            </div>
                                            <p class="text-[11px] text-[#444] font-medium">No transactions found</p>
                                            <p class="text-[9px] text-[#333] mt-1">Try adjusting your filters</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    @if($recentReceipts->hasPages())
                        <div class="px-6 py-4 border-t border-[#1e1e1e]">
                            {{ $recentReceipts->appends(request()->query())->links('vendor.pagination.custom') }}
                        </div>
                    @endif
                </div>

                <!-- ── Mobile: Card View ── -->
                <div class="lg:hidden space-y-2.5">
                    @forelse($recentReceipts as $receipt)
                        <div class="glass-card p-4 rounded-[1.25rem] card-row cursor-pointer"
                            onclick="window.location.href='{{ route('payment.showReceipt', $receipt->id) }}'">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-start gap-3 min-w-0 flex-1">
                                    <div class="w-9 h-9 rounded-lg bg-[#111] border border-[#1e1e1e] flex items-center justify-center shrink-0 mt-0.5">
                                        <i class="fa-solid fa-receipt text-[10px] text-[#444]"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-[10px] font-bold text-[#ccc] font-mono truncate">{{ $receipt->transaction_id }}</p>
                                        <p class="text-[10px] font-bold text-[#888] mt-0.5 truncate">Ride to {{ $receipt->destination_name ?? 'Downtown' }}</p>
                                        <p class="text-[8px] text-[#444] font-bold uppercase tracking-wider mt-0.5">Standard Regular Fare</p>
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="text-[12px] font-bold text-white">-₱{{ number_format($receipt->price, 2) }}</p>
                                    <p class="text-[8px] text-[#444] mt-0.5 whitespace-nowrap">{{ $receipt->paid_at }}</p>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="glass-card p-10 rounded-[1.25rem] text-center">
                            <div class="w-12 h-12 rounded-xl bg-[#111] border border-[#1e1e1e] flex items-center justify-center mx-auto mb-3">
                                <i class="fa-solid fa-box-open text-lg text-[#333]"></i>
                            </div>
                            <p class="text-[11px] text-[#444] font-medium">No transactions found</p>
                            <p class="text-[9px] text-[#333] mt-1">Try adjusting your filters</p>
                        </div>
                    @endforelse

                    @if($recentReceipts->hasPages())
                        <div class="pt-2">
                            {{ $recentReceipts->appends(request()->query())->links('vendor.pagination.custom') }}
                        </div>
                    @endif
                </div>

            </div>
        </div>
    </div>

    <!-- ══════════ SCRIPTS ══════════ -->
    <script>
        function toggleSection(id) {
            const body = document.getElementById(id + '-body');
            const icon = document.getElementById(id + '-icon');
            if (!body || !icon) return;
            body.classList.toggle('open');
            icon.classList.toggle('rotated');
        }

        function setQuickRange(days) {
            const to = new Date();
            const from = new Date();
            from.setDate(from.getDate() - days);

            const formatDate = (d) => d.toISOString().split('T')[0];

            const form = document.querySelector('form[action="{{ route("payment.history") }}"]');
            if (!form) return;

            const fromInput = form.querySelector('input[name="from_date"]');
            const toInput = form.querySelector('input[name="to_date"]');

            if (fromInput) fromInput.value = formatDate(from);
            if (toInput) toInput.value = formatDate(to);

            form.submit();
        }
    </script>

</body>
</html>
