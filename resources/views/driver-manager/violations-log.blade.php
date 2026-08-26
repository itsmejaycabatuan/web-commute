<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SmartCommute | Violations Log</title>
    <script>
        if (localStorage.getItem('color-theme') === 'dark' ||
            (!localStorage.getItem('color-theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif']
                    }
                }
            }
        }
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    @include('partials.head-scripts')

    <style>
        body,
        html {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: 'Inter', sans-serif;
            overflow-x: hidden;
            background: #f8fafc;
        }

        .dark body,
        .dark html {
            background: #050505;
        }

        .glass-panel {
            background: #ffffff;
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

        .sidebar-transition {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .table-row {
            transition: all 0.2s ease;
        }

        .table-row:hover {
            background: #f1f5f9;
        }

        .dark .table-row:hover {
            background: #1a1a1a;
        }

        [x-cloak] {
            display: none !important;
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

        select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%2394a3b8' viewBox='0 0 16 16'%3E%3Cpath d='M8 11L3 6h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
        }

        .dark select {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='rgba(255,255,255,0.2)' viewBox='0 0 16 16'%3E%3Cpath d='M8 11L3 6h10z'/%3E%3C/svg%3E");
        }

        .dark select option {
            background: #111;
            color: #fff;
        }

        .dark input[type="date"]::-webkit-calendar-picker-indicator,
        .dark input[type="time"]::-webkit-calendar-picker-indicator {
            filter: invert(1) brightness(0.3);
            cursor: pointer;
        }

        /* ═══ BG CATCHES ═══ */
        .glass-card .bg-\[\#111\] {
            background: #f8fafc !important;
        }

        .dark .glass-card .bg-\[\#111\] {
            background: #111 !important;
        }

        .glass-card .bg-\[\#0a0a0a\] {
            background: #f8fafc !important;
        }

        .dark .glass-card .bg-\[\#0a0a0a\] {
            background: #0a0a0a !important;
        }

        .glass-panel .bg-\[\#0a0a0a\] {
            background: #f1f5f9 !important;
        }

        .dark .glass-panel .bg-\[\#0a0a0a\] {
            background: #0a0a0a !important;
        }

        /* ═══ BORDER CATCHES ═══ */
        .border-\[\#1e1e1e\] {
            border-color: #e2e8f0 !important;
        }

        .dark .border-\[\#1e1e1e\] {
            border-color: #1e1e1e !important;
        }

        .glass-card .border-\[\#1e1e1e\] {
            border-color: #e2e8f0 !important;
        }

        .dark .glass-card .border-\[\#1e1e1e\] {
            border-color: #1e1e1e !important;
        }

        .border-\[1a1a1a\] {
            border-color: #e2e8f0 !important;
        }

        .dark .border-\[1a1a1a\] {
            border-color: #1a1a1a !important;
        }

        .glass-card .border-\[1a1a1a\] {
            border-color: #e2e8f0 !important;
        }

        .dark .glass-card .border-\[1a1a1a\] {
            border-color: #1a1a1a !important;
        }

        .border-\[222\] {
            border-color: #e2e8f0 !important;
        }

        .dark .border-\[222\] {
            border-color: #222 !important;
        }

        .border-\[151515\] {
            border-color: #e2e8f0 !important;
        }

        .dark .border-\[151515\] {
            border-color: #151515 !important;
        }

        /* ═══ DIVIDE CATCHES ═══ */
        .divide-\[1a1a1a\]> :not([hidden])~ :not([hidden]) {
            border-color: #e2e8f0 !important;
        }

        .dark .divide-\[1a1a1a\]> :not([hidden])~ :not([hidden]) {
            border-color: #1a1a1a !important;
        }

        /* ═══ TEXT CATCHES ═══ */
        .text-\[\#ccc\] {
            color: #334155 !important;
        }

        .dark .text-\[\#ccc\] {
            color: #ccc !important;
        }

        .text-\[\#888\] {
            color: #64748b !important;
        }

        .dark .text-\[\#888\] {
            color: #888 !important;
        }

        .text-\[\#666\] {
            color: #64748b !important;
        }

        .dark .text-\[\#666\] {
            color: #666 !important;
        }

        .text-\[\#555\] {
            color: #94a3b8 !important;
        }

        .dark .text-\[\#555\] {
            color: #555 !important;
        }

        .text-\[\#444\] {
            color: #94a3b8 !important;
        }

        .dark .text-\[\#444\] {
            color: #444 !important;
        }

        .text-\[\#333\] {
            color: #cbd5e1 !important;
        }

        .dark .text-\[\#333\] {
            color: #333 !important;
        }

        .text-\[\#222\] {
            color: #e2e8f0 !important;
        }

        .dark .text-\[\#222\] {
            color: #222 !important;
        }

        /* ═══ HOVER STATES ═══ */
        .hover\:bg-\[\#1a1a1a\]:hover {
            background: #f1f5f9 !important;
        }

        .dark .hover\:bg-\[\#1a1a1a\]:hover {
            background: #1a1a1a !important;
        }

        .hover\:border-\[\#333\]:hover {
            border-color: #cbd5e1 !important;
        }

        .dark .hover\:border-\[\#333\]:hover {
            border-color: #333 !important;
        }

        .hover\:bg-\[\#222\]:hover {
            background: #e2e8f0 !important;
        }

        .dark .hover\:bg-\[\#222\]:hover {
            background: #222 !important;
        }

        /* ═══ LOGOUT MODAL CATCHES ═══ */
        .glass-panel .bg-\[\#1a1a1a\] {
            background: #f1f5f9 !important;
        }

        .dark .glass-panel .bg-\[\#1a1a1a\] {
            background: #1a1a1a !important;
        }

        .glass-panel .border-\[2a2a2a\] {
            border-color: #cbd5e1 !important;
        }

        .dark .glass-panel .border-\[2a2a2a\] {
            border-color: #2a2a2a !important;
        }

        /* ═══ ACCENT PRESERVATION ═══ */
        .text-blue-400 {
            color: #3b82f6 !important;
        }

        .text-emerald-400 {
            color: #10b981 !important;
        }

        .text-amber-400 {
            color: #f59e0b !important;
        }

        .text-red-400 {
            color: #f87171 !important;
        }

        .text-purple-400 {
            color: #a78bfa !important;
        }

        .text-teal-400 {
            color: #2dd4bf !important;
        }

        .text-orange-400 {
            color: #fb923c !important;
        }
    </style>
</head>

<body class="antialiased text-gray-900 dark:text-white" x-data>

    <div x-data="{ showLogoutModal: false }" @keydown.escape.window="showLogoutModal = false">

        <x-layout.sidebar :menu-items="$sidebarMenu" />

        <main :class="$store.sidebar.open ? 'md:ml-72' : 'md:ml-20'"
            class="sidebar-transition pt-4 sm:pt-8 pr-3 sm:pr-8 pb-8 pl-3 sm:pl-8 min-h-screen mb-16 md:mb-12"
            x-data="violationsLog()">

            <!-- ── Mobile: Identity Card ── -->
            <div class="lg:hidden mb-5">
                <div class="glass-card p-4 rounded-[1.25rem]">
                    <div class="flex items-center gap-3.5">
                        <div class="w-11 h-11 bg-blue-600 rounded-xl flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-gauge-high text-white text-sm"></i>
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-sm font-bold truncate">Driver Manager</h2>
                            <p class="text-[10px] text-[#555] truncate">{{ Auth::user()->email }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 pt-3.5 mt-3.5 border-t border-[#1e1e1e]">
                        <i class="fa-solid fa-id-badge text-[8px] text-blue-400"></i>
                        <span class="text-[10px] text-[#888] font-bold">Driver Oversight</span>
                        <span class="text-[#333]">•</span>
                        <span
                            class="font-mono text-[9px] text-[#444]">{{ Auth::user()->getRoleNames()->first() ?? 'Manager' }}</span>
                    </div>
                </div>
            </div>

            <!-- ── Page Header (desktop) ── -->
            <div class="hidden lg:block mb-8">
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="text-[9px] font-bold uppercase tracking-[0.15em] text-[#444]">Manage</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight">Violations <span
                        class="text-red-400">Log</span></h1>
                <p class="text-[11px] text-[#555] mt-1 flex items-center gap-2">
                    <i class="fa-solid fa-list-check text-[9px] text-red-400"></i>
                    <span class="text-[#888] font-bold">{{ count($violations) }}</span> violation records logged
                </p>
            </div>

            @if (session('success'))
                <div
                    class="mb-5 px-4 py-3 rounded-xl border border-emerald-500/15 bg-emerald-500/5 flex items-center gap-3">
                    <div class="w-6 h-6 rounded-md bg-emerald-500/10 flex items-center justify-center shrink-0"><i
                            class="fa-solid fa-check text-[8px] text-emerald-400"></i></div>
                    <span class="text-[11px] text-emerald-400 font-medium">{{ session('success') }}</span>
                </div>
            @endif
            @if (session('error'))
                <div class="mb-5 px-4 py-3 rounded-xl border border-red-500/15 bg-red-500/5 flex items-center gap-3">
                    <div class="w-6 h-6 rounded-md bg-red-500/10 flex items-center justify-center shrink-0"><i
                            class="fa-solid fa-xmark text-[8px] text-red-400"></i></div>
                    <span class="text-[11px] text-red-400 font-medium">{{ session('error') }}</span>
                </div>
            @endif

            <!-- ══════════ TABLE CARD ══════════ -->
            <div class="glass-card rounded-[1.25rem] sm:rounded-[1.5rem] overflow-hidden">
                <div class="p-4 sm:p-5 border-b border-[#1e1e1e]">
                    <div class="flex items-center justify-between">
                        <span
                            class="text-[8px] font-bold text-[#333] uppercase tracking-widest">{{ count($violations) }}
                            entries</span>
                        <div class="flex items-center gap-2">
                            <button @click="openBulkModal()"
                                class="flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-[#111] hover:bg-[#1a1a1a] border border-[#1e1e1e] hover:border-[#333] text-[#888] hover:text-[#ccc] text-[10px] font-bold uppercase tracking-widest transition active:scale-[0.98]">
                                <i class="fa-solid fa-layer-group text-[9px] text-blue-400"></i><span>Bulk Add</span>
                            </button>
                            <button @click="openModal()"
                                class="flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-[10px] font-bold uppercase tracking-widest text-white transition active:scale-[0.98]">
                                <i class="fa-solid fa-plus text-[9px]"></i><span>New Entry</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ═══ Mobile Card View ═══ -->
                <div class="lg:hidden p-3 space-y-2">
                    @forelse ($violations as $v)
                        @php
                            $isExpired = false;
                            if ($v['expirationDate'] !== 'N/A') {
                                try {
                                    $isExpired = \Carbon\Carbon::parse($v['expirationDate'])->isPast();
                                } catch (\Exception $e) {
                                    $isExpired = false;
                                }
                            }
                            $offenseLabel = match ($v['offenseCount']) {
                                1 => '1st Offense',
                                2 => '2nd Offense',
                                3 => '3rd Offense',
                                default => $v['offenseCount'] . 'th Offense',
                            };
                            $badgeMap = [
                                'red' => 'bg-red-500/10 text-red-400 border border-red-500/15',
                                'amber' => 'bg-amber-500/10 text-amber-400 border border-amber-500/15',
                                'green' => 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/15',
                                'blue' => 'bg-blue-500/10 text-blue-400 border border-blue-500/15',
                            ];
                            $badgeClass = $badgeMap[$v['codeColor']] ?? $badgeMap['amber'];
                        @endphp
                        <div class="rounded-xl border border-[#1a1a1a] overflow-hidden">
                            <div class="flex items-center justify-between px-3.5 py-2.5">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <div
                                        class="w-8 h-8 rounded-lg bg-[#111] border border-[#1e1e1e] flex items-center justify-center shrink-0">
                                        <span
                                            class="text-[9px] font-black text-[#555]">{{ strtoupper(substr($v['driverName'], 0, 1)) }}</span>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-[11px] font-bold text-[#ccc] truncate">{{ $v['driverName'] }}</p>
                                        <div class="flex items-center gap-2 mt-0.5">
                                            <span
                                                class="text-[8px] font-mono text-[#444] bg-[#0a0a0a] px-1.5 py-0.5 rounded-md border border-[#1a1a1a]">{{ $v['license'] }}</span>
                                            @if ($isExpired)
                                                <span
                                                    class="text-[7px] font-bold uppercase text-red-400 flex items-center gap-1"><i
                                                        class="fa-solid fa-triangle-exclamation text-[6px]"></i>
                                                    Exp</span>
                                            @else
                                                <span
                                                    class="text-[7px] font-bold uppercase text-emerald-400 flex items-center gap-1"><i
                                                        class="fa-solid fa-shield-halved text-[6px]"></i> Valid</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <span
                                    class="text-[11px] font-bold text-red-400 shrink-0">₱{{ number_format($v['fine'], 0) }}</span>
                            </div>
                            <div class="flex items-center justify-between px-3.5 py-2 border-t border-[#161616]">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span
                                        class="text-[7px] sm:text-[8px] font-bold uppercase font-mono px-1.5 py-0.5 rounded-md {{ $badgeClass }}">{{ $v['violationCode'] }}</span>
                                    <span class="text-[9px] text-[#888] truncate">{{ $v['violationType'] }}</span>
                                </div>
                                <div class="flex items-center gap-2 text-[8px] text-[#444] shrink-0">
                                    <i class="fa-solid fa-location-dot text-[7px] text-blue-400/60"></i>
                                    <span>{{ $v['date'] }} · {{ $v['time'] }}</span>
                                </div>
                            </div>
                            @if ($v['remarks'])
                                <div class="px-3.5 py-2 border-t border-[#161616]">
                                    <p class="text-[8px] text-[#333] italic truncate">"{{ $v['remarks'] }}"</p>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="flex flex-col items-center justify-center py-16">
                            <div
                                class="w-12 h-12 rounded-xl bg-[#111] border border-[#1e1e1e] flex items-center justify-center mb-3">
                                <i class="fa-solid fa-shield text-base text-[#222]"></i>
                            </div>
                            <p class="text-[11px] text-[#444] font-medium mb-4">No violations recorded yet</p>
                            <button @click="openModal()"
                                class="flex items-center gap-2 px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-[9px] font-bold uppercase tracking-widest text-white transition">
                                <i class="fa-solid fa-plus text-[8px]"></i><span>Log First Violation</span>
                            </button>
                        </div>
                    @endforelse
                </div>

                <!-- ═══ Desktop Table View ═══ -->
                <div class="hidden lg:block overflow-x-auto">
                    <table class="w-full text-left min-w-[850px]">
                        <thead>
                            <tr
                                class="text-[8px] sm:text-[9px] uppercase tracking-[0.15em] text-[#444] border-b border-[#1e1e1e]">
                                <th class="px-4 sm:px-6 py-3 font-bold w-12">#</th>
                                <th class="px-4 sm:px-6 py-3 font-bold">Driver</th>
                                <th class="px-4 sm:px-6 py-3 font-bold">Violation</th>
                                <th class="px-4 sm:px-6 py-3 font-bold">Date & Location</th>
                                <th class="px-4 sm:px-6 py-3 font-bold text-right">Fine</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#1a1a1a]">
                            @forelse ($violations as $v)
                                @php
                                    $isExpired = false;
                                    if ($v['expirationDate'] !== 'N/A') {
                                        try {
                                            $isExpired = \Carbon\Carbon::parse($v['expirationDate'])->isPast();
                                        } catch (\Exception $e) {
                                            $isExpired = false;
                                        }
                                    }
                                    $offenseLabel = match ($v['offenseCount']) {
                                        1 => '1st Offense',
                                        2 => '2nd Offense',
                                        3 => '3rd Offense',
                                        default => $v['offenseCount'] . 'th Offense',
                                    };
                                    $badgeMap = [
                                        'red' => 'bg-red-500/10 text-red-400 border border-red-500/15',
                                        'amber' => 'bg-amber-500/10 text-amber-400 border border-amber-500/15',
                                        'green' => 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/15',
                                        'blue' => 'bg-blue-500/10 text-blue-400 border border-blue-500/15',
                                    ];
                                    $badgeClass = $badgeMap[$v['codeColor']] ?? $badgeMap['amber'];
                                @endphp
                                <tr class="table-row">
                                    <td class="px-4 sm:px-6 py-3"><span
                                            class="text-[10px] font-bold text-[#333]">{{ str_pad($loop->index + 1, 2, '0', STR_PAD_LEFT) }}</span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-3">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="w-8 h-8 rounded-lg bg-[#111] border border-[#1e1e1e] flex items-center justify-center shrink-0">
                                                <span
                                                    class="text-[9px] font-black text-[#555]">{{ strtoupper(substr($v['driverName'], 0, 1)) }}</span>
                                            </div>
                                            <div class="min-w-0">
                                                <span
                                                    class="text-[10px] sm:text-[11px] font-bold text-[#ccc] block truncate max-w-[160px]">{{ $v['driverName'] }}</span>
                                                <div class="flex items-center gap-2 mt-0.5">
                                                    <span
                                                        class="text-[8px] font-mono text-[#444] bg-[#0a0a0a] px-1.5 py-0.5 rounded-md border border-[#1a1a1a]">{{ $v['license'] }}</span>
                                                    @if ($isExpired)
                                                        <span
                                                            class="text-[7px] font-bold uppercase text-red-400 flex items-center gap-1"><i
                                                                class="fa-solid fa-triangle-exclamation text-[6px]"></i>
                                                            Exp</span>
                                                    @else
                                                        <span
                                                            class="text-[7px] font-bold uppercase text-emerald-400 flex items-center gap-1"><i
                                                                class="fa-solid fa-shield-halved text-[6px]"></i>
                                                            Valid</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 sm:px-6 py-3">
                                        <span
                                            class="text-[10px] sm:text-[11px] font-bold text-[#ccc] block">{{ $v['violationType'] }}</span>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span
                                                class="text-[7px] sm:text-[8px] font-bold uppercase font-mono px-1.5 py-0.5 rounded-md {{ $badgeClass }}">{{ $v['violationCode'] }}</span>
                                            <span
                                                class="text-[8px] text-[#444] font-medium">{{ $offenseLabel }}</span>
                                        </div>
                                        @if ($v['remarks'])
                                            <p class="text-[8px] text-[#333] mt-1.5 italic truncate max-w-[220px]">
                                                "{{ $v['remarks'] }}"</p>
                                        @endif
                                    </td>
                                    <td class="px-4 sm:px-6 py-3">
                                        <div class="flex items-center gap-2 text-[10px] font-bold text-[#555] mb-1">
                                            <i class="fa-solid fa-location-dot text-[8px] text-blue-400/60"></i>
                                            <span class="truncate max-w-[140px]">{{ $v['location'] }}</span>
                                        </div>
                                        <div class="flex items-center gap-2 text-[8px] text-[#444] font-medium">
                                            <i class="fa-regular fa-calendar text-[7px]"></i>
                                            <span>{{ $v['date'] }}</span>
                                            <span class="text-[#222]">·</span>
                                            <span>{{ $v['time'] }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 sm:px-6 py-3 text-right">
                                        <span
                                            class="text-[11px] font-bold text-[#ccc]">₱{{ number_format($v['fine'], 2) }}</span>
                                        <div class="text-[8px] text-red-400/60 font-medium mt-0.5">
                                            {{ $v['penalty'] ?: '' }}</div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-12 sm:py-16">
                                        <div class="flex flex-col items-center justify-center">
                                            <div
                                                class="w-12 h-12 rounded-xl bg-[#111] border border-[#1e1e1e] flex items-center justify-center mb-3">
                                                <i class="fa-solid fa-shield text-base text-[#222]"></i>
                                            </div>
                                            <p class="text-[11px] text-[#444] font-medium mb-4">No violations recorded
                                                yet</p>
                                            <button @click="openModal()"
                                                class="flex items-center gap-2 px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-[9px] font-bold uppercase tracking-widest text-white transition"><i
                                                    class="fa-solid fa-plus text-[8px]"></i><span>Log First
                                                    Violation</span></button>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <p class="text-center text-[7px] sm:text-[8px] text-[#222] uppercase tracking-[0.2em] pt-5 sm:pt-6">
                SmartCommute Driver Systems &bull; Violations Log Module</p>

            <!-- ══════════ SINGLE ENTRY MODAL ══════════ -->
            <div x-show="showModal" x-cloak
                class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 dark:bg-black/80"
                @click.self="showModal = false" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0">
                <div @click.stop
                    class="glass-panel p-6 sm:p-8 rounded-[2rem] max-w-lg w-full max-h-[90vh] overflow-y-auto"
                    x-show="showModal" x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-10 h-10 rounded-xl bg-blue-500/10 border border-blue-500/15 flex items-center justify-center">
                                <i class="fa-solid fa-file text-[10px] text-blue-400"></i>
                            </div>
                            <div>
                                <h3 class="text-base sm:text-lg font-bold">New Violation</h3>
                                <p class="text-[10px] text-[#555] mt-0.5">Log a traffic violation entry</p>
                            </div>
                        </div>
                        <button @click="showModal = false"
                            class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-[#1a1a1a] border border-gray-200 dark:border-[#2a2a2a] flex items-center justify-center hover:bg-gray-200 dark:hover:bg-[#222] transition">
                            <i class="fa-solid fa-xmark text-[10px] text-gray-500 dark:text-[#555]"></i>
                        </button>
                    </div>
                    <form method="POST" action="{{ route('driver-manager.violations-log.store') }}"
                        class="space-y-4">
                        @csrf
                        <div>
                            <label
                                class="block mb-1.5 text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Driver
                                <span class="text-red-400">*</span></label>
                            <select name="user_id" x-model="form.driverId" @change="onDriverChange()"
                                class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] text-[11px] text-gray-900 dark:text-white focus:outline-none focus:border-gray-400 dark:focus:border-[#333] transition pr-10">
                                <option value="">Select a driver...</option>
                                <template x-for="d in drivers" :key="d.id">
                                    <option :value="d.id" x-text="d.name + ' — ' + d.license"></option>
                                </template>
                            </select>
                            @error('user_id')
                                <p class="mt-1.5 text-[9px] text-red-400 flex items-center gap-1.5"><i
                                        class="fa-solid fa-circle-exclamation text-[7px]"></i> {{ $message }}</p>
                            @enderror
                        </div>
                        <div x-show="selectedDriver" x-cloak
                            class="p-3 rounded-xl bg-gray-50 dark:bg-[#0a0a0a] border border-gray-200 dark:border-[#1e1e1e]">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-[10px] font-bold text-[#ccc]" x-text="selectedDriver.name"></p>
                                    <p class="text-[8px] text-[#444]"
                                        x-text="selectedDriver.license + ' · Exp: ' + selectedDriver.expirationDate">
                                    </p>
                                </div>
                                <span class="text-[7px] sm:text-[8px] font-bold uppercase px-1.5 py-0.5 rounded-md"
                                    :class="selectedDriver.isExpired ? 'bg-red-500/10 text-red-400 border border-red-500/15' :
                                        'bg-emerald-500/10 text-emerald-400 border border-emerald-500/15'"
                                    x-text="selectedDriver.isExpired ? 'Expired' : 'Valid'"></span>
                            </div>
                        </div>
                        <div>
                            <label
                                class="block mb-1.5 text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Violation
                                Type <span class="text-red-400">*</span></label>
                            <select name="vc_id" x-model="form.violationCodeId" @change="onViolationChange()"
                                class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] text-[11px] text-gray-900 dark:text-white focus:outline-none focus:border-gray-400 dark:focus:border-[#333] transition pr-10">
                                <option value="">Select violation...</option>
                                <template x-for="vc in violationCodes" :key="vc.id">
                                    <option :value="vc.id" x-text="vc.code + ' — ' + vc.violation_name">
                                    </option>
                                </template>
                            </select>
                            @error('vc_id')
                                <p class="mt-1.5 text-[9px] text-red-400 flex items-center gap-1.5"><i
                                        class="fa-solid fa-circle-exclamation text-[7px]"></i> {{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label
                                class="block mb-1.5 text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Offense
                                Instance <span class="text-red-400">*</span></label>
                            <select name="violation_instance" x-model="form.offenseCount" @change="calculateFine()"
                                class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] text-[11px] text-gray-900 dark:text-white focus:outline-none focus:border-gray-400 dark:focus:border-[#333] transition pr-10">
                                <option value="">Select...</option>
                                <option value="1">1st Offense</option>
                                <option value="2">2nd Offense</option>
                                <option value="3">3rd Offense</option>
                                <option value="4">4th+ Offense</option>
                            </select>
                            @error('violation_instance')
                                <p class="mt-1.5 text-[9px] text-red-400 flex items-center gap-1.5"><i
                                        class="fa-solid fa-circle-exclamation text-[7px]"></i> {{ $message }}</p>
                            @enderror
                        </div>
                        <input type="hidden" name="violation_fine" :value="form.violationFine">
                        <div x-show="form.violationFine > 0"
                            class="flex items-center justify-between p-3.5 rounded-xl bg-gray-50 dark:bg-[#0a0a0a] border border-gray-200 dark:border-[#1e1e1e]">
                            <div class="flex items-center gap-2">
                                <div class="w-1.5 h-1.5 rounded-full bg-blue-500"></div>
                                <span
                                    class="text-[8px] font-bold uppercase tracking-[0.15em] text-[#333]">Auto-calculated
                                    fine</span>
                            </div>
                            <span class="text-sm font-bold text-blue-400"
                                x-text="'₱' + Number(form.violationFine).toLocaleString('en-PH', {minimumFractionDigits: 2})"></span>
                        </div>
                        <div>
                            <label
                                class="block mb-1.5 text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Location
                                <span class="text-red-400">*</span></label>
                            <input type="text" name="place_of_violation" x-model="form.location"
                                value="{{ old('place_of_violation') }}" placeholder="e.g., Marikina City"
                                class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] text-[11px] text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-[#333] focus:outline-none focus:border-gray-400 dark:focus:border-[#333] transition">
                            @error('place_of_violation')
                                <p class="mt-1.5 text-[9px] text-red-400 flex items-center gap-1.5"><i
                                        class="fa-solid fa-circle-exclamation text-[7px]"></i> {{ $message }}</p>
                            @enderror
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label
                                    class="block mb-1.5 text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Date
                                    <span class="text-red-400">*</span></label>
                                <input type="date" name="date_of_violation" x-model="form.date"
                                    value="{{ old('date_of_violation') }}"
                                    class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] text-[11px] text-gray-900 dark:text-white focus:outline-none focus:border-gray-400 dark:focus:border-[#333] transition">
                                @error('date_of_violation')
                                    <p class="mt-1.5 text-[9px] text-red-400 flex items-center gap-1.5"><i
                                            class="fa-solid fa-circle-exclamation text-[7px]"></i> {{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label
                                    class="block mb-1.5 text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Time
                                    <span class="text-red-400">*</span></label>
                                <input type="time" name="time_of_violation" x-model="form.time"
                                    value="{{ old('time_of_violation') }}"
                                    class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] text-[11px] text-gray-900 dark:text-white focus:outline-none focus:border-gray-400 dark:focus:border-[#333] transition">
                                @error('time_of_violation')
                                    <p class="mt-1.5 text-[9px] text-red-400 flex items-center gap-1.5"><i
                                            class="fa-solid fa-circle-exclamation text-[7px]"></i> {{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div>
                            <label
                                class="block mb-1.5 text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Remarks</label>
                            <textarea name="remarks" x-model="form.remarks" rows="2" placeholder="Optional notes..."
                                class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] text-[11px] text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-[#333] focus:outline-none focus:border-gray-400 dark:focus:border-[#333] transition resize-none">{{ old('remarks') }}</textarea>
                        </div>
                        <div class="flex gap-2.5 pt-1">
                            <button type="button" @click="showModal = false"
                                class="flex-1 py-3 rounded-xl bg-gray-100 dark:bg-[#1a1a1a] border border-gray-200 dark:border-[#2a2a2a] text-[10px] font-bold uppercase tracking-widest hover:bg-gray-200 dark:hover:bg-[#222] transition">Cancel</button>
                            <button type="submit"
                                class="flex-1 py-3 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-[10px] font-bold uppercase tracking-widest transition active:scale-[0.98]"><i
                                    class="fa-solid fa-check mr-1.5 text-[8px]"></i> Save Entry</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ══════════ BULK ADD MODAL ══════════ -->
            <div x-show="showBulkModal" x-cloak
                class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 dark:bg-black/80"
                @click.self="showBulkModal = false" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0">
                <div @click.stop class="glass-panel rounded-[2rem] max-w-3xl w-full overflow-hidden flex flex-col"
                    style="max-height: 90vh;">
                    <div class="p-6 sm:p-8 pb-4 shrink-0">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-10 h-10 rounded-xl bg-purple-500/10 border border-purple-500/15 flex items-center justify-center">
                                    <i class="fa-solid fa-layer-group text-[10px] text-purple-400"></i>
                                </div>
                                <div>
                                    <h3 class="text-base sm:text-lg font-bold">Bulk Add Violations</h3>
                                    <p class="text-[10px] text-[#555] mt-0.5">Log multiple violations for a single
                                        driver</p>
                                </div>
                            </div>
                            <button @click="showBulkModal = false"
                                class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-[#1a1a1a] border border-gray-200 dark:border-[#2a2a2a] flex items-center justify-center hover:bg-gray-200 dark:hover:bg-[#222] transition">
                                <i class="fa-solid fa-xmark text-[10px] text-gray-500 dark:text-[#555]"></i>
                            </button>
                        </div>
                    </div>
                    <div class="px-6 sm:px-8 pb-4 overflow-y-auto flex-1">
                        <form id="bulkViolationForm" method="POST"
                            action="{{ route('driver-manager.violations-log.store-bulk') }}" class="space-y-5">
                            @csrf
                            @if ($errors->has('user_id') || $errors->has('violations'))
                                <div class="p-4 rounded-xl bg-red-500/5 border border-red-500/15">
                                    <div class="flex items-center gap-2 mb-2.5">
                                        <div class="w-5 h-5 rounded-md bg-red-500/10 flex items-center justify-center">
                                            <i class="fa-solid fa-circle-exclamation text-[7px] text-red-400"></i>
                                        </div>
                                        <span
                                            class="text-[8px] font-bold uppercase tracking-[0.15em] text-red-400">Please
                                            fix the following</span>
                                    </div>
                                    <ul class="space-y-1.5">
                                        @if ($errors->has('user_id'))
                                            <li class="text-[9px] text-red-400/80 flex items-start gap-2"><span
                                                    class="text-red-500/50 mt-0.5">·</span>{{ $errors->first('user_id') }}
                                            </li>
                                        @endif
                                        @foreach ($errors->get('violations') as $key => $messages)
                                            @php
                                                $rowNum = is_int($key) ? $key + 1 : $key;
                                                $fieldLabel = '';
                                                if (str_contains($key, 'vc_id')) {
                                                    $fieldLabel = 'Violation Type';
                                                } elseif (str_contains($key, 'violation_instance')) {
                                                    $fieldLabel = 'Offense';
                                                } elseif (str_contains($key, 'violation_fine')) {
                                                    $fieldLabel = 'Fine';
                                                } elseif (str_contains($key, 'place_of_violation')) {
                                                    $fieldLabel = 'Location';
                                                } elseif (str_contains($key, 'date_of_violation')) {
                                                    $fieldLabel = 'Date';
                                                } elseif (str_contains($key, 'time_of_violation')) {
                                                    $fieldLabel = 'Time';
                                                } elseif (str_contains($key, 'remarks')) {
                                                    $fieldLabel = 'Remarks';
                                                } else {
                                                    $fieldLabel = $key;
                                                }
                                            @endphp
                                            @foreach ($messages as $message)
                                                <li class="text-[9px] text-red-400/80 flex items-start gap-2"><span
                                                        class="text-red-500/50 mt-0.5">·</span><span><span
                                                            class="font-bold text-red-400">Entry #{{ $rowNum }}
                                                            ({{ $fieldLabel }})
                                                            :</span> {{ $message }}</span>
                                                </li>
                                            @endforeach
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div
                                class="p-4 rounded-xl bg-gray-50 dark:bg-[#0a0a0a] border border-gray-200 dark:border-[#1e1e1e]">
                                <label
                                    class="block mb-1.5 text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Driver
                                    <span class="text-red-400">*</span></label>
                                <select name="user_id" x-model="bulk.driverId" @change="onBulkDriverChange()"
                                    class="w-full px-4 py-3 rounded-xl bg-white dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] text-[11px] text-gray-900 dark:text-white focus:outline-none focus:border-gray-400 dark:focus:border-[#333] transition pr-10"
                                    :class="{ 'border-red-500/50': {{ $errors->has('user_id') ? 'true' : 'false' }} }">
                                    <option value="">Select a driver...</option>
                                    <template x-for="d in drivers" :key="d.id">
                                        <option :value="d.id" x-text="d.name + ' — ' + d.license"></option>
                                    </template>
                                </select>
                                <div x-show="bulk.selectedDriver" x-cloak
                                    class="mt-3 flex items-center justify-between">
                                    <div>
                                        <p class="text-[10px] font-bold text-[#ccc]"
                                            x-text="bulk.selectedDriver.name"></p>
                                        <p class="text-[8px] text-[#444]"
                                            x-text="bulk.selectedDriver.license + ' · Exp: ' + bulk.selectedDriver.expirationDate">
                                        </p>
                                    </div>
                                    <span class="text-[7px] sm:text-[8px] font-bold uppercase px-1.5 py-0.5 rounded-md"
                                        :class="bulk.selectedDriver.isExpired ?
                                            'bg-red-500/10 text-red-400 border border-red-500/15' :
                                            'bg-emerald-500/10 text-emerald-400 border border-emerald-500/15'"
                                        x-text="bulk.selectedDriver.isExpired ? 'Expired' : 'Valid'"></span>
                                </div>
                            </div>

                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <span
                                        class="text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Violation
                                        Entries <span class="text-purple-400 ml-1"
                                            x-text="'(' + bulk.rows.length + ')'"></span></span>
                                    <button type="button" @click="addBulkRow()"
                                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-purple-500/10 hover:bg-purple-500/20 border border-purple-500/15 text-purple-400 text-[9px] font-bold uppercase tracking-wider transition">
                                        <i class="fa-solid fa-plus text-[7px]"></i> Add Row
                                    </button>
                                </div>

                                <template x-for="(row, index) in bulk.rows" :key="row.id">
                                    <div
                                        class="p-4 rounded-xl border border-gray-200 dark:border-[#1e1e1e] bg-gray-50 dark:bg-[#111] relative group">
                                        <div class="flex items-center justify-between mb-3">
                                            <div class="flex items-center gap-2">
                                                <span
                                                    class="flex items-center justify-center w-6 h-6 rounded-lg bg-purple-500/10 text-purple-400 text-[9px] font-black"
                                                    x-text="index + 1"></span>
                                                <span class="text-[9px] font-bold text-[#555]">Entry #<span
                                                        x-text="index + 1"></span></span>
                                            </div>
                                            <button type="button" @click="removeBulkRow(index)"
                                                x-show="bulk.rows.length > 1"
                                                class="opacity-0 group-hover:opacity-100 flex items-center justify-center w-7 h-7 rounded-lg bg-red-500/5 border border-red-500/10 hover:bg-red-500/10 text-red-400/60 hover:text-red-400 text-[9px] transition-all">
                                                <i class="fa-solid fa-trash-can text-[8px]"></i>
                                            </button>
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
                                            <div class="sm:col-span-2">
                                                <label
                                                    class="block mb-1 text-[7px] font-bold uppercase tracking-[0.15em] text-[#333]">Violation
                                                    Type <span class="text-red-400/60">*</span></label>
                                                <select :name="'violations[' + index + '][vc_id]'" x-model="row.vcId"
                                                    @change="calculateBulkRowFine(index)"
                                                    class="w-full px-3 py-2.5 rounded-lg bg-white dark:bg-[#0a0a0a] border border-gray-200 dark:border-[#1e1e1e] text-[10px] text-gray-900 dark:text-white focus:outline-none focus:border-gray-400 dark:focus:border-[#333] transition pr-10">
                                                    <option value="">Select violation...</option>
                                                    <template x-for="vc in violationCodes" :key="vc.id">
                                                        <option :value="vc.id"
                                                            x-text="vc.code + ' — ' + vc.violation_name"></option>
                                                    </template>
                                                </select>
                                            </div>
                                            <div>
                                                <label
                                                    class="block mb-1 text-[7px] font-bold uppercase tracking-[0.15em] text-[#333]">Offense
                                                    <span class="text-red-400/60">*</span></label>
                                                <select :name="'violations[' + index + '][violation_instance]'"
                                                    x-model="row.offenseCount" @change="calculateBulkRowFine(index)"
                                                    class="w-full px-3 py-2.5 rounded-lg bg-white dark:bg-[#0a0a0a] border border-gray-200 dark:border-[#1e1e1e] text-[10px] text-gray-900 dark:text-white focus:outline-none focus:border-gray-400 dark:focus:border-[#333] transition pr-10">
                                                    <option value="">Select...</option>
                                                    <option value="1">1st Offense</option>
                                                    <option value="2">2nd Offense</option>
                                                    <option value="3">3rd Offense</option>
                                                    <option value="4">4th+ Offense</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label
                                                    class="block mb-1 text-[7px] font-bold uppercase tracking-[0.15em] text-[#333]">Location
                                                    <span class="text-red-400/60">*</span></label>
                                                <input type="text"
                                                    :name="'violations[' + index + '][place_of_violation]'"
                                                    x-model="row.location" placeholder="e.g., Marikina City"
                                                    class="w-full px-3 py-2.5 rounded-lg bg-white dark:bg-[#0a0a0a] border border-gray-200 dark:border-[#1e1e1e] text-[10px] text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-[#222] focus:outline-none focus:border-gray-400 dark:focus:border-[#333] transition">
                                            </div>
                                            <div>
                                                <label
                                                    class="block mb-1 text-[7px] font-bold uppercase tracking-[0.15em] text-[#333]">Date
                                                    <span class="text-red-400/60">*</span></label>
                                                <input type="date"
                                                    :name="'violations[' + index + '][date_of_violation]'"
                                                    x-model="row.date"
                                                    class="w-full px-3 py-2.5 rounded-lg bg-white dark:bg-[#0a0a0a] border border-gray-200 dark:border-[#1e1e1e] text-[10px] text-gray-900 dark:text-white focus:outline-none focus:border-gray-400 dark:focus:border-[#333] transition">
                                            </div>
                                            <div>
                                                <label
                                                    class="block mb-1 text-[7px] font-bold uppercase tracking-[0.15em] text-[#333]">Time
                                                    <span class="text-red-400/60">*</span></label>
                                                <input type="time"
                                                    :name="'violations[' + index + '][time_of_violation]'"
                                                    x-model="row.time"
                                                    class="w-full px-3 py-2.5 rounded-lg bg-white dark:bg-[#0a0a0a] border border-gray-200 dark:border-[#1e1e1e] text-[10px] text-gray-900 dark:text-white focus:outline-none focus:border-gray-400 dark:focus:border-[#333] transition">
                                            </div>
                                            <div class="sm:col-span-2">
                                                <label
                                                    class="block mb-1 text-[7px] font-bold uppercase tracking-[0.15em] text-[#333]">Remarks</label>
                                                <input type="text" :name="'violations[' + index + '][remarks]'"
                                                    x-model="row.remarks" placeholder="Optional notes..."
                                                    class="w-full px-3 py-2.5 rounded-lg bg-white dark:bg-[#0a0a0a] border border-gray-200 dark:border-[#1e1e1e] text-[10px] text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-[#222] focus:outline-none focus:border-gray-400 dark:focus:border-[#333] transition">
                                            </div>
                                        </div>
                                        <input type="hidden" :name="'violations[' + index + '][violation_fine]'"
                                            :value="row.fine">
                                        <div class="flex items-center justify-end">
                                            <div x-show="row.fine > 0"
                                                class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-blue-500/5 border border-blue-500/10">
                                                <span
                                                    class="text-[7px] font-bold uppercase tracking-[0.15em] text-blue-400/60">Fine</span>
                                                <span class="text-[10px] font-bold text-blue-400"
                                                    x-text="'₱' + Number(row.fine).toLocaleString('en-PH', {minimumFractionDigits: 2})"></span>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </form>
                    </div>

                    <div class="px-6 sm:px-8 py-5 border-t border-gray-200 dark:border-[#1e1e1e] shrink-0">
                        <div
                            class="flex items-center justify-between mb-4 p-3.5 rounded-xl bg-purple-500/5 border border-purple-500/15">
                            <div>
                                <span
                                    class="text-[8px] font-bold uppercase tracking-[0.15em] text-purple-400/60 block">Total
                                    Fines</span>
                                <span class="text-[8px] text-[#333]"
                                    x-text="bulk.rows.length + ' entr' + (bulk.rows.length === 1 ? 'y' : 'ies')"></span>
                            </div>
                            <span class="text-lg font-black text-purple-400"
                                x-text="'₱' + bulkTotalFine.toLocaleString('en-PH', {minimumFractionDigits: 2})"></span>
                        </div>
                        <div class="flex gap-2.5">
                            <button type="button" @click="showBulkModal = false"
                                class="flex-1 py-3 rounded-xl bg-gray-100 dark:bg-[#1a1a1a] border border-gray-200 dark:border-[#2a2a2a] text-[10px] font-bold uppercase tracking-widest hover:bg-gray-200 dark:hover:bg-[#222] transition">Cancel</button>
                            <button type="button" @click="addBulkRow()"
                                class="px-5 py-3 rounded-xl bg-gray-50 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] hover:bg-gray-100 dark:hover:bg-[#1a1a1a] hover:border-gray-300 dark:hover:border-[#333] text-gray-500 dark:text-[#888] text-[10px] font-bold uppercase tracking-widest hover:text-gray-700 dark:hover:text-[#ccc] transition">
                                <i class="fa-solid fa-plus mr-1.5 text-[8px]"></i> Row
                            </button>
                            <button type="submit" form="bulkViolationForm"
                                class="flex-1 py-3 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-[10px] font-bold uppercase tracking-widest transition active:scale-[0.98] shadow-lg shadow-purple-600/10">
                                <i class="fa-solid fa-check-double mr-1.5 text-[8px]"></i> Save All
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ══════════ LOGOUT MODAL ══════════ -->
            <div x-show="showLogoutModal" x-cloak
                class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 dark:bg-black/80"
                @click.self="showLogoutModal = false" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0">
                <div @click.stop class="glass-panel p-7 sm:p-8 rounded-[2rem] max-w-sm w-full"
                    x-show="showLogoutModal" x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
                    <div class="text-center">
                        <div
                            class="w-14 h-14 bg-red-500/10 rounded-2xl flex items-center justify-center mx-auto mb-5 border border-red-500/20">
                            <i class="fa-solid fa-power-off text-red-400 text-lg"></i>
                        </div>
                        <h3 class="text-lg font-bold mb-1.5">End Session?</h3>
                        <p class="text-xs text-[#666] mb-7">Are you sure you want to exit?</p>
                        <div class="flex gap-2.5">
                            <button @click="showLogoutModal = false"
                                class="flex-1 py-3 rounded-xl bg-gray-100 dark:bg-[#1a1a1a] border border-gray-200 dark:border-[#2a2a2a] text-[10px] font-bold uppercase tracking-widest hover:bg-gray-200 dark:hover:bg-[#222] transition">Cancel</button>
                            <form action="{{ route('users.logout') }}" method="POST" class="flex-1">
                                @csrf
                                <button type="submit"
                                    class="w-full py-3 rounded-xl bg-red-600 text-white text-[10px] font-bold uppercase tracking-widest hover:bg-red-700 transition active:scale-[0.98]">Logout</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    @if ($errors->any())
        <script>
            window.__openViolationModal = true;
        </script>
    @endif

    <script>
        function violationsLog() {
            let rowIdCounter = 0;

            function createEmptyRow() {
                return {
                    id: ++rowIdCounter,
                    vcId: '',
                    offenseCount: '',
                    fine: 0,
                    location: '',
                    date: new Date().toISOString().split('T')[0],
                    time: '',
                    remarks: ''
                };
            }
            return {
                showModal: false,
                showBulkModal: false,
                drivers: @json($drivers),
                violationCodes: @json($violationCodes),
                selectedDriver: null,
                form: {
                    driverId: '{{ old('user_id') }}',
                    violationCodeId: '{{ old('vc_id') }}',
                    offenseCount: '{{ old('violation_instance') }}',
                    violationFine: 0,
                    location: '{{ old('place_of_violation') }}',
                    date: '{{ old('date_of_violation') }}' || new Date().toISOString().split('T')[0],
                    time: '{{ old('time_of_violation') }}',
                    remarks: '{{ old('remarks') }}',
                },
                bulk: {
                    driverId: '',
                    selectedDriver: null,
                    rows: [createEmptyRow()]
                },

                get bulkTotalFine() {
                    return this.bulk.rows.reduce((sum, r) => sum + (parseFloat(r.fine) || 0), 0);
                },

                init() {
                    if (window.__openViolationModal) {
                        this.showModal = true;
                        if (this.form.violationCodeId && this.form.offenseCount) this.calculateFine();
                        if (this.form.driverId) this.onDriverChange();
                    }
                    @if (session('bulk_validation_failed'))
                        this.showBulkModal = true;
                        this.bulk.driverId = '{{ old('user_id') }}';
                        if (this.bulk.driverId) this.onBulkDriverChange();
                    @endif
                },

                openModal() {
                    this.form = {
                        driverId: '',
                        violationCodeId: '',
                        offenseCount: '',
                        violationFine: 0,
                        location: '',
                        date: new Date().toISOString().split('T')[0],
                        time: '',
                        remarks: ''
                    };
                    this.selectedDriver = null;
                    this.showModal = true;
                },

                onDriverChange() {
                    const driver = this.drivers.find(d => d.id == this.form.driverId);
                    if (driver) {
                        const expDate = new Date(driver.expirationDate);
                        driver.isExpired = expDate < new Date();
                        this.selectedDriver = driver;
                    } else {
                        this.selectedDriver = null;
                    }
                },

                onViolationChange() {
                    this.calculateFine();
                },

                calculateFine() {
                    const vc = this.violationCodes.find(v => v.id == this.form.violationCodeId);
                    const count = parseInt(this.form.offenseCount);
                    if (vc && count) {
                        const key = count >= 4 ? 'fourth_offense' : ['first_offense', 'second_offense', 'third_offense'][
                            count - 1
                        ];
                        this.form.violationFine = vc[key] || 0;
                    } else {
                        this.form.violationFine = 0;
                    }
                },

                openBulkModal() {
                    rowIdCounter = 0;
                    this.bulk = {
                        driverId: '',
                        selectedDriver: null,
                        rows: [createEmptyRow()]
                    };
                    this.showBulkModal = true;
                },

                onBulkDriverChange() {
                    const driver = this.drivers.find(d => d.id == this.bulk.driverId);
                    if (driver) {
                        const expDate = new Date(driver.expirationDate);
                        driver.isExpired = expDate < new Date();
                        this.bulk.selectedDriver = driver;
                    } else {
                        this.bulk.selectedDriver = null;
                    }
                },

                addBulkRow() {
                    this.bulk.rows.push(createEmptyRow());
                    this.$nextTick(() => {
                        const el = this.$el;
                        if (el) {
                            const scrollable = el.closest('.flex.flex-col')?.querySelector('.overflow-y-auto');
                            if (scrollable) scrollable.scrollTo({
                                top: scrollable.scrollHeight,
                                behavior: 'smooth'
                            });
                        }
                    });
                },

                removeBulkRow(index) {
                    if (this.bulk.rows.length > 1) this.bulk.rows.splice(index, 1);
                },

                calculateBulkRowFine(index) {
                    const row = this.bulk.rows[index];
                    const vc = this.violationCodes.find(v => v.id == row.vcId);
                    const count = parseInt(row.offenseCount);
                    if (vc && count) {
                        const key = count >= 4 ? 'fourth_offense' : ['first_offense', 'second_offense', 'third_offense'][
                            count - 1
                        ];
                        row.fine = vc[key] || 0;
                    } else {
                        row.fine = 0;
                    }
                }
            };
        }
    </script>
</body>

</html>
