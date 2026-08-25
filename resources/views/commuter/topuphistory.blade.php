<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartCommute | Top-up History</title>

    @include('partials.commuter-head-scripts')

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">

    <style>
        html,
        body {
            margin: 0;
            padding: 0;
            height: 100%;
            width: 100%;
            font-family: 'Inter', sans-serif;
            overflow-x: hidden;
            background-color: #f8fafc;
        }

        .dark html,
        .dark body {
            background-color: #050505;
        }

        *,
        *::before,
        *::after {
            transition-property: background-color, border-color, color, box-shadow, opacity, fill, stroke;
            transition-duration: 0.3s;
            transition-timing-function: ease;
        }

        .glass-panel {
            background: #ffffff !important;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
        }

        .dark .glass-panel {
            background: #111111 !important;
            border: 1px solid #1e1e1e;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.6);
        }

        .glass-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .dark .glass-card {
            background: #161616;
            border: 1px solid #222222;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.5);
        }

        .header-btn {
            transition: all 0.3s ease !important;
        }

        input::placeholder {
            color: #94a3b8;
        }

        .dark input::placeholder {
            color: #333;
        }

        input:focus,
        select:focus {
            border-color: rgba(59, 130, 246, 0.4) !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.08);
        }

        .section-toggle-icon {
            transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1) !important;
        }

        .section-toggle-icon.rotated {
            transform: rotate(180deg);
        }

        .section-body {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.4s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.3s ease !important;
            opacity: 0;
        }

        .section-body.open {
            max-height: 600px;
            opacity: 1;
        }

        .table-row {
            transition: all 0.2s ease !important;
        }

        .table-row:hover {
            background: #f8fafc;
        }

        .dark .table-row:hover {
            background: #1a1a1a;
        }

        .card-row {
            transition: all 0.2s ease !important;
        }

        .card-row:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
        }

        .dark .card-row:hover {
            background: #1a1a1a;
            border-color: #333;
        }

        .pagination {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .pagination a,
        .pagination span {
            display: flex;
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
            transition: all 0.2s ease !important;
            cursor: pointer;
            border: 1px solid transparent;
        }

        .pagination a {
            color: #64748b;
            background: #ffffff;
            border-color: #e2e8f0;
        }

        .pagination a:hover {
            color: #0f172a;
            background: #f8fafc;
            border-color: #cbd5e1;
        }

        .dark .pagination a {
            color: #666;
            background: #111;
            border-color: #1e1e1e;
        }

        .dark .pagination a:hover {
            color: #fff;
            background: #1a1a1a;
            border-color: #333;
        }

        .pagination .active {
            color: #fff;
            background: #2563eb;
            border-color: #2563eb;
            box-shadow: 0 2px 12px rgba(37, 99, 235, 0.25);
        }

        .pagination .disabled {
            color: #cbd5e1;
            background: #f8fafc;
            border-color: #e2e8f0;
            cursor: default;
            pointer-events: none;
        }

        .dark .pagination .disabled {
            color: #333;
            background: #0a0a0a;
            border-color: #151515;
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
        }
    </style>
</head>

<body class="antialiased text-slate-900 dark:text-white">

    <!-- ══════════ HEADER ══════════ -->
    <header
        class="fixed top-4 left-4 right-4 sm:top-5 sm:left-5 sm:right-5 z-50 flex items-center justify-between gap-3 pointer-events-none">
        <div class="glass-panel p-3 sm:p-3.5 rounded-2xl pointer-events-auto flex items-center gap-3 min-w-0">
            <a href="{{ route('map') }}"
                class="header-btn w-9 h-9 rounded-xl flex items-center justify-center shrink-0 border border-slate-200 dark:border-[#1e1e1e] bg-white dark:bg-[#111] hover:bg-slate-50 dark:hover:bg-[#1a1a1a] transition cursor-pointer">
                <i class="fa-solid fa-arrow-left text-[10px] text-slate-400 dark:text-[#666]"></i>
            </a>
            <div class="w-px h-6 bg-slate-200 dark:bg-[#222] mx-0.5 hidden sm:block"></div>
            <div
                class="w-8 h-8 sm:w-9 sm:h-9 bg-blue-600 rounded-lg sm:rounded-xl flex items-center justify-center shrink-0">
                <i class="fa-solid fa-bus text-white text-xs sm:text-sm"></i>
            </div>
            <span
                class="text-[13px] sm:text-sm font-bold tracking-tight text-slate-900 dark:text-white whitespace-nowrap">Smart<span
                    class="text-blue-500 dark:text-blue-400">Commute</span></span>
            <div class="w-px h-6 bg-slate-200 dark:bg-[#222] mx-0.5 hidden sm:block"></div>
            <span
                class="text-[9px] sm:text-[10px] font-bold uppercase tracking-[0.15em] text-slate-400 dark:text-[#555] hidden sm:inline">Top-up
                History</span>
        </div>
        <div class="flex items-center gap-2 pointer-events-auto z-50 shrink-0">
            <a href="{{ route('payment.topup') }}"
                class="header-btn bg-blue-600 px-3 sm:px-4 py-2 rounded-xl text-white text-[9px] sm:text-[10px] font-bold cursor-pointer uppercase tracking-wider flex items-center gap-2 shadow-lg shadow-blue-600/20 hover:bg-blue-500 transition active:scale-[0.98]">
                <i class="fa-solid fa-plus text-[8px] sm:text-[9px]"></i>
                <span class="hidden sm:inline">New Top-up</span>
            </a>
        </div>
    </header>

    <!-- ══════════ MAIN CONTENT ══════════ -->
    <div class="pt-20 sm:pt-24 pb-8 sm:pb-10 max-w-6xl mx-auto px-4 sm:px-6 lg:px-5">

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            <!-- ══════════ LEFT SIDEBAR (desktop filters) ══════════ -->
            <div class="hidden lg:block lg:col-span-4">
                <div class="glass-card p-6 rounded-[1.5rem] sticky top-24">
                    <div class="flex items-center gap-2.5 mb-5">
                        <div
                            class="w-8 h-8 rounded-lg bg-slate-50 dark:bg-[#111] border border-slate-200 dark:border-[#1e1e1e] flex items-center justify-center">
                            <i class="fa-solid fa-filter text-[10px] text-slate-400 dark:text-[#555]"></i>
                        </div>
                        <span
                            class="text-[9px] font-bold uppercase tracking-[0.15em] text-slate-400 dark:text-[#555]">Filters</span>
                    </div>

                    <form action="{{ route('payment.topup.history') }}" method="GET" class="space-y-4">
                        <!-- Search -->
                        <div class="space-y-1.5">
                            <label
                                class="text-[9px] font-bold uppercase tracking-[0.15em] text-slate-500 dark:text-[#444] ml-1">Search</label>
                            <div class="relative">
                                <i
                                    class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-[10px] text-slate-400 dark:text-[#444]"></i>
                                <input type="text" name="search" value="{{ request('search') }}"
                                    placeholder="Transaction ID"
                                    class="w-full bg-slate-50 dark:bg-[#111] border border-slate-200 dark:border-[#1e1e1e] rounded-xl pl-10 pr-4 py-2.5 text-[12px] text-slate-900 dark:text-white outline-none transition">
                            </div>
                        </div>

                        <!-- Payment Method -->
                        <div class="space-y-1.5">
                            <label
                                class="text-[9px] font-bold uppercase tracking-[0.15em] text-slate-500 dark:text-[#444] ml-1">Payment
                                Method</label>
                            <div class="relative">
                                <i
                                    class="fa-solid fa-credit-card absolute left-3.5 top-1/2 -translate-y-1/2 text-[10px] text-slate-400 dark:text-[#444]"></i>
                                <select name="method"
                                    class="w-full bg-slate-50 dark:bg-[#111] border border-slate-200 dark:border-[#1e1e1e] rounded-xl pl-10 pr-4 py-2.5 text-[12px] text-slate-900 dark:text-white outline-none transition appearance-none cursor-pointer">
                                    <option value="" {{ !request('method') ? 'selected' : '' }}>All Methods
                                    </option>
                                    <option value="gcash" {{ request('method') === 'gcash' ? 'selected' : '' }}>GCash
                                    </option>
                                    <option value="maya" {{ request('method') === 'maya' ? 'selected' : '' }}>Maya
                                    </option>
                                    <option value="admin" {{ request('method') === 'admin' ? 'selected' : '' }}>Admin
                                        Settlement</option>
                                </select>
                                <i
                                    class="fa-solid fa-chevron-down absolute right-3.5 top-1/2 -translate-y-1/2 text-[8px] text-slate-400 dark:text-[#444] pointer-events-none"></i>
                            </div>
                        </div>

                        <!-- Date Range -->
                        <div class="space-y-1.5">
                            <label
                                class="text-[9px] font-bold uppercase tracking-[0.15em] text-slate-500 dark:text-[#444] ml-1">Date
                                Range</label>
                            <div class="grid grid-cols-2 gap-2">
                                <div class="relative">
                                    <i
                                        class="fa-regular fa-calendar absolute left-3 top-1/2 -translate-y-1/2 text-[9px] text-slate-400 dark:text-[#444]"></i>
                                    <input type="date" name="from_date" value="{{ request('from_date') }}"
                                        class="w-full bg-slate-50 dark:bg-[#111] border border-slate-200 dark:border-[#1e1e1e] rounded-xl pl-9 pr-2 py-2.5 text-[11px] text-slate-900 dark:text-white outline-none transition">
                                </div>
                                <div class="relative">
                                    <i
                                        class="fa-regular fa-calendar absolute left-3 top-1/2 -translate-y-1/2 text-[9px] text-slate-400 dark:text-[#444]"></i>
                                    <input type="date" name="to_date" value="{{ request('to_date') }}"
                                        class="w-full bg-slate-50 dark:bg-[#111] border border-slate-200 dark:border-[#1e1e1e] rounded-xl pl-9 pr-2 py-2.5 text-[11px] text-slate-900 dark:text-white outline-none transition">
                                </div>
                            </div>
                        </div>

                        <!-- Quick Range -->
                        <div class="space-y-1.5">
                            <label
                                class="text-[9px] font-bold uppercase tracking-[0.15em] text-slate-500 dark:text-[#444] ml-1">Quick
                                Range</label>
                            <div class="grid grid-cols-3 gap-1.5">
                                <button type="button" onclick="setQuickRange(7)"
                                    class="py-2 rounded-lg bg-slate-50 dark:bg-[#111] border border-slate-200 dark:border-[#1e1e1e] text-[9px] font-bold text-slate-400 dark:text-[#555] hover:text-slate-900 dark:hover:text-white hover:border-slate-300 dark:hover:border-[#333] transition">7
                                    Days</button>
                                <button type="button" onclick="setQuickRange(30)"
                                    class="py-2 rounded-lg bg-slate-50 dark:bg-[#111] border border-slate-200 dark:border-[#1e1e1e] text-[9px] font-bold text-slate-400 dark:text-[#555] hover:text-slate-900 dark:hover:text-white hover:border-slate-300 dark:hover:border-[#333] transition">30
                                    Days</button>
                                <button type="button" onclick="setQuickRange(90)"
                                    class="py-2 rounded-lg bg-slate-50 dark:bg-[#111] border border-slate-200 dark:border-[#1e1e1e] text-[9px] font-bold text-slate-400 dark:text-[#555] hover:text-slate-900 dark:hover:text-white hover:border-slate-300 dark:hover:border-[#333] transition">90
                                    Days</button>
                            </div>
                        </div>

                        <!-- Actions -->
                        <button type="submit"
                            class="w-full bg-blue-600 hover:bg-blue-500 py-3 rounded-xl text-[10px] font-bold uppercase tracking-wider text-white transition shadow-lg shadow-blue-600/20 active:scale-[0.98] flex items-center justify-center gap-2">
                            <i class="fa-solid fa-magnifying-glass text-[9px]"></i>
                            Apply Filters
                        </button>

                        @if (request()->hasAny(['from_date', 'to_date', 'search', 'method']))
                            <a href="{{ route('payment.topup.history') }}"
                                class="block text-center text-[9px] text-slate-400 dark:text-[#444] hover:text-slate-900 dark:hover:text-white font-bold uppercase tracking-wider transition py-1">
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
                    <button onclick="toggleSection('filters')"
                        class="w-full glass-card p-4 rounded-[1.25rem] flex items-center justify-between cursor-pointer hover:border-slate-300 dark:hover:border-[#333] transition">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-8 h-8 rounded-lg bg-slate-50 dark:bg-[#111] border border-slate-200 dark:border-[#1e1e1e] flex items-center justify-center">
                                <i class="fa-solid fa-filter text-[10px] text-slate-400 dark:text-[#555]"></i>
                            </div>
                            <div class="text-left">
                                <span
                                    class="text-[10px] font-bold uppercase tracking-[0.15em] text-slate-400 dark:text-[#555]">Filters</span>
                                @if (request()->hasAny(['from_date', 'to_date', 'search', 'method']))
                                    <span
                                        class="ml-2 text-[8px] bg-blue-500/15 text-blue-600 dark:text-blue-400 px-1.5 py-0.5 rounded font-bold">Active</span>
                                @endif
                            </div>
                        </div>
                        <i id="filters-icon"
                            class="fa-solid fa-chevron-down text-[10px] text-slate-400 dark:text-[#555] section-toggle-icon"></i>
                    </button>
                    <div id="filters-body" class="section-body">
                        <div class="glass-card border-t-0 rounded-t-none p-4 pt-3">
                            <form action="{{ route('payment.topup.history') }}" method="GET" class="space-y-3">
                                <!-- Search -->
                                <div class="relative">
                                    <i
                                        class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-[10px] text-slate-400 dark:text-[#444]"></i>
                                    <input type="text" name="search" value="{{ request('search') }}"
                                        placeholder="Transaction ID"
                                        class="w-full bg-slate-50 dark:bg-[#111] border border-slate-200 dark:border-[#1e1e1e] rounded-xl pl-10 pr-4 py-2.5 text-[12px] text-slate-900 dark:text-white outline-none transition">
                                </div>
                                <!-- Method -->
                                <div class="relative">
                                    <i
                                        class="fa-solid fa-credit-card absolute left-3.5 top-1/2 -translate-y-1/2 text-[10px] text-slate-400 dark:text-[#444]"></i>
                                    <select name="method"
                                        class="w-full bg-slate-50 dark:bg-[#111] border border-slate-200 dark:border-[#1e1e1e] rounded-xl pl-10 pr-4 py-2.5 text-[12px] text-slate-900 dark:text-white outline-none transition appearance-none cursor-pointer">
                                        <option value="" {{ !request('method') ? 'selected' : '' }}>All Methods
                                        </option>
                                        <option value="gcash" {{ request('method') === 'gcash' ? 'selected' : '' }}>
                                            GCash</option>
                                        <option value="maya" {{ request('method') === 'maya' ? 'selected' : '' }}>
                                            Maya</option>
                                        <option value="admin" {{ request('method') === 'admin' ? 'selected' : '' }}>
                                            Admin Settlement</option>
                                    </select>
                                    <i
                                        class="fa-solid fa-chevron-down absolute right-3.5 top-1/2 -translate-y-1/2 text-[8px] text-slate-400 dark:text-[#444] pointer-events-none"></i>
                                </div>
                                <!-- Date Range -->
                                <div class="grid grid-cols-2 gap-2">
                                    <div class="relative">
                                        <i
                                            class="fa-regular fa-calendar absolute left-3 top-1/2 -translate-y-1/2 text-[9px] text-slate-400 dark:text-[#444]"></i>
                                        <input type="date" name="from_date" value="{{ request('from_date') }}"
                                            class="w-full bg-slate-50 dark:bg-[#111] border border-slate-200 dark:border-[#1e1e1e] rounded-xl pl-9 pr-2 py-2.5 text-[11px] text-slate-900 dark:text-white outline-none transition">
                                    </div>
                                    <div class="relative">
                                        <i
                                            class="fa-regular fa-calendar absolute left-3 top-1/2 -translate-y-1/2 text-[9px] text-slate-400 dark:text-[#444]"></i>
                                        <input type="date" name="to_date" value="{{ request('to_date') }}"
                                            class="w-full bg-slate-50 dark:bg-[#111] border border-slate-200 dark:border-[#1e1e1e] rounded-xl pl-9 pr-2 py-2.5 text-[11px] text-slate-900 dark:text-white outline-none transition">
                                    </div>
                                </div>
                                <!-- Quick Range -->
                                <div class="grid grid-cols-3 gap-1.5">
                                    <button type="button" onclick="setQuickRange(7)"
                                        class="py-2 rounded-lg bg-slate-50 dark:bg-[#111] border border-slate-200 dark:border-[#1e1e1e] text-[9px] font-bold text-slate-400 dark:text-[#555] hover:text-slate-900 dark:hover:text-white hover:border-slate-300 dark:hover:border-[#333] transition">7
                                        Days</button>
                                    <button type="button" onclick="setQuickRange(30)"
                                        class="py-2 rounded-lg bg-slate-50 dark:bg-[#111] border border-slate-200 dark:border-[#1e1e1e] text-[9px] font-bold text-slate-400 dark:text-[#555] hover:text-slate-900 dark:hover:text-white hover:border-slate-300 dark:hover:border-[#333] transition">30
                                        Days</button>
                                    <button type="button" onclick="setQuickRange(90)"
                                        class="py-2 rounded-lg bg-slate-50 dark:bg-[#111] border border-slate-200 dark:border-[#1e1e1e] text-[9px] font-bold text-slate-400 dark:text-[#555] hover:text-slate-900 dark:hover:text-white hover:border-slate-300 dark:hover:border-[#333] transition">90
                                        Days</button>
                                </div>
                                <div class="flex gap-2">
                                    <button type="submit"
                                        class="flex-1 bg-blue-600 hover:bg-blue-500 py-2.5 rounded-xl text-[10px] font-bold uppercase tracking-wider text-white transition active:scale-[0.98]">Apply</button>
                                    @if (request()->hasAny(['from_date', 'to_date', 'search', 'method']))
                                        <a href="{{ route('payment.topup.history') }}"
                                            class="px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-[#111] border border-slate-200 dark:border-[#1e1e1e] text-[10px] font-bold text-slate-400 dark:text-[#555] hover:text-slate-900 dark:hover:text-white hover:border-slate-300 dark:hover:border-[#333] transition flex items-center">
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
                        <div
                            class="w-8 h-8 rounded-lg bg-slate-50 dark:bg-[#111] border border-slate-200 dark:border-[#1e1e1e] flex items-center justify-center">
                            <i class="fa-solid fa-clock-rotate-left text-[10px] text-slate-400 dark:text-[#555]"></i>
                        </div>
                        <span
                            class="text-[9px] font-bold uppercase tracking-[0.15em] text-slate-400 dark:text-[#555]">All
                            Top-ups</span>
                    </div>
                    <span class="text-[9px] font-bold text-slate-300 dark:text-[#333]">{{ $transactions->total() }}
                        results</span>
                </div>

                <!-- ── Desktop: Table View ── -->
                <div class="hidden lg:block glass-card rounded-[1.5rem] overflow-hidden">
                    <table class="w-full text-left">
                        <thead>
                            <tr
                                class="text-[9px] uppercase tracking-[0.15em] text-slate-400 dark:text-[#444] border-b border-slate-100 dark:border-[#1e1e1e]">
                                <th class="px-6 py-3.5 font-bold">Date & Time</th>
                                <th class="px-6 py-3.5 font-bold">Transaction</th>
                                <th class="px-6 py-3.5 font-bold">Method</th>
                                <th class="px-6 py-3.5 font-bold text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-[#1a1a1a]">
                            @forelse($transactions as $tx)
                                <tr class="table-row">
                                    <td class="px-6 py-4">
                                        <p class="text-[11px] font-bold text-slate-700 dark:text-[#ccc]">
                                            {{ $tx->created_at->format('M d, Y') }}</p>
                                        <p class="text-[9px] text-slate-400 dark:text-[#444] mt-0.5">
                                            {{ $tx->created_at->format('h:i A') }}</p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span
                                            class="text-[10px] font-bold text-blue-600 dark:text-blue-400/80 bg-blue-500/10 border border-blue-500/15 px-2 py-1 rounded-md font-mono">#{{ $tx->id }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2.5">
                                            <div
                                                class="w-7 h-7 rounded-lg bg-slate-50 dark:bg-[#111] border border-slate-200 dark:border-[#1e1e1e] flex items-center justify-center shrink-0">
                                                @if ($tx->payment_method == 'gcash')
                                                    <i
                                                        class="fa-solid fa-mobile-screen-button text-[9px] text-blue-500 dark:text-blue-400"></i>
                                                @elseif($tx->payment_method == 'maya')
                                                    <i
                                                        class="fa-solid fa-bolt text-[9px] text-emerald-500 dark:text-emerald-400"></i>
                                                @else
                                                    <i
                                                        class="fa-solid fa-user-tie text-[9px] text-amber-500 dark:text-amber-400"></i>
                                                @endif
                                            </div>
                                            <span
                                                class="text-[11px] font-bold text-slate-500 dark:text-[#888] capitalize">{{ $tx->payment_method }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <span
                                            class="text-[12px] font-bold text-emerald-600 dark:text-emerald-400">+₱{{ number_format($tx->amount_added, 2) }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-16">
                                        <div class="flex flex-col items-center justify-center">
                                            <div
                                                class="w-12 h-12 rounded-xl bg-slate-50 dark:bg-[#111] border border-slate-200 dark:border-[#1e1e1e] flex items-center justify-center mb-3">
                                                <i
                                                    class="fa-solid fa-receipt text-lg text-slate-300 dark:text-[#333]"></i>
                                            </div>
                                            <p class="text-[11px] text-slate-400 dark:text-[#444] font-medium">No
                                                top-up history found</p>
                                            <a href="{{ route('payment.topup') }}"
                                                class="text-[9px] text-blue-600 dark:text-blue-400 font-bold uppercase tracking-wider mt-2 hover:text-blue-500 dark:hover:text-blue-300 transition">
                                                <i class="fa-solid fa-plus text-[8px] mr-1"></i> Make your first top-up
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    @if ($transactions->hasPages())
                        <div class="px-6 py-4 border-t border-slate-100 dark:border-[#1e1e1e]">
                            {{ $transactions->links('vendor.pagination.custom') }}
                        </div>
                    @endif
                </div>

                <!-- ── Mobile: Card View ── -->
                <div class="lg:hidden space-y-2.5">
                    @forelse($transactions as $tx)
                        <div class="glass-card p-4 rounded-[1.25rem] card-row">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-start gap-3 min-w-0 flex-1">
                                    <div
                                        class="w-9 h-9 rounded-lg bg-slate-50 dark:bg-[#111] border border-slate-200 dark:border-[#1e1e1e] flex items-center justify-center shrink-0 mt-0.5">
                                        @if ($tx->payment_method == 'gcash')
                                            <i
                                                class="fa-solid fa-mobile-screen-button text-[10px] text-blue-500 dark:text-blue-400"></i>
                                        @elseif($tx->payment_method == 'maya')
                                            <i
                                                class="fa-solid fa-bolt text-[10px] text-emerald-500 dark:text-emerald-400"></i>
                                        @else
                                            <i
                                                class="fa-solid fa-user-tie text-[10px] text-amber-500 dark:text-amber-400"></i>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <span
                                            class="text-[10px] font-bold text-blue-600 dark:text-blue-400/80 bg-blue-500/10 border border-blue-500/15 px-1.5 py-0.5 rounded font-mono">#{{ $tx->id }}</span>
                                        <p
                                            class="text-[10px] font-bold text-slate-500 dark:text-[#888] capitalize mt-1.5">
                                            {{ $tx->payment_method }}</p>
                                        <p
                                            class="text-[8px] text-slate-400 dark:text-[#444] font-bold uppercase tracking-wider mt-0.5">
                                            {{ $tx->created_at->format('M d, Y') }} •
                                            {{ $tx->created_at->format('h:i A') }}
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="text-[12px] font-bold text-emerald-600 dark:text-emerald-400">
                                        +₱{{ number_format($tx->amount_added, 2) }}</p>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="glass-card p-10 rounded-[1.25rem] text-center">
                            <div
                                class="w-12 h-12 rounded-xl bg-slate-50 dark:bg-[#111] border border-slate-200 dark:border-[#1e1e1e] flex items-center justify-center mx-auto mb-3">
                                <i class="fa-solid fa-receipt text-lg text-slate-300 dark:text-[#333]"></i>
                            </div>
                            <p class="text-[11px] text-slate-400 dark:text-[#444] font-medium">No top-up history found
                            </p>
                            <a href="{{ route('payment.topup') }}"
                                class="inline-flex items-center gap-1.5 text-[9px] text-blue-600 dark:text-blue-400 font-bold uppercase tracking-wider mt-2 hover:text-blue-500 dark:hover:text-blue-300 transition">
                                <i class="fa-solid fa-plus text-[8px]"></i> Make your first top-up
                            </a>
                        </div>
                    @endforelse

                    @if ($transactions->hasPages())
                        <div class="pt-2">
                            {{ $transactions->links('vendor.pagination.custom') }}
                        </div>
                    @endif
                </div>

                <!-- Footer -->
                <p class="text-center text-[8px] text-slate-300 dark:text-[#222] uppercase tracking-[0.2em] pt-4">
                    SmartCommute Payment Systems &bull; End-to-End Encrypted
                </p>

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

            const form = document.querySelector('form[action="{{ route('payment.topup.history') }}"]');
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
