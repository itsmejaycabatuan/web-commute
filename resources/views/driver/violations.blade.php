<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SmartCommute | My Violations</title>
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

        @keyframes slide-up {
            0% {
                opacity: 0;
                transform: translateY(8px);
            }

            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card-animate {
            animation: slide-up 0.3s ease-out forwards;
            opacity: 0;
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

        [x-cloak] {
            display: none !important;
        }

        /* ═══ BG CATCHES ═══ */
        .glass-card .bg-\[\#111\] {
            background: #f8fafc !important;
        }

        .dark .glass-card .bg-\[\#111\] {
            background: #111 !important;
        }

        .glass-card .bg-\[\#141414\] {
            background: #f1f5f9 !important;
        }

        .dark .glass-card .bg-\[\#141414\] {
            background: #141414 !important;
        }

        .glass-card .bg-\[\#0a0a0a\] {
            background: #f8fafc !important;
        }

        .dark .glass-card .bg-\[\#0a0a0a\] {
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

        .border-\[141414\] {
            border-color: #e2e8f0 !important;
        }

        .dark .border-\[141414\] {
            border-color: #141414 !important;
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

        .hover\:bg-\[\#222\]:hover {
            background: #e2e8f0 !important;
        }

        .dark .hover\:bg-\[\#222\]:hover {
            background: #222 !important;
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

        .text-rose-400 {
            color: #fb7185 !important;
        }
    </style>
</head>

<body class="antialiased text-gray-900 dark:text-white" x-data>

    <div x-data="{ showLogoutModal: false }" @keydown.escape.window="showLogoutModal = false">

        <x-layout.sidebar :menu-items="$sidebarMenu" />

        <main :class="$store.sidebar.open ? 'md:ml-72' : 'md:ml-20'"
            class="sidebar-transition pt-4 sm:pt-8 pr-3 sm:pr-8 pb-8 pl-3 sm:pl-8 min-h-screen mb-16 md:mb-12">

            <!-- Flash Messages -->
            @if (session('success'))
                <div
                    class="mb-4 sm:mb-6 px-3.5 sm:px-4 py-3 rounded-xl border border-emerald-500/20 bg-emerald-500/5 text-emerald-400 text-[10px] sm:text-[11px] font-medium flex items-center gap-2">
                    <i class="fa-solid fa-circle-check text-[9px] sm:text-[10px]"></i>
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div
                    class="mb-4 sm:mb-6 px-3.5 sm:px-4 py-3 rounded-xl border border-red-500/20 bg-red-500/5 text-red-400 text-[10px] sm:text-[11px] font-medium flex items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation text-[9px] sm:text-[10px]"></i>
                    {{ session('error') }}
                </div>
            @endif

            <!-- ── Mobile: Identity Card ── -->
            <div class="lg:hidden mb-5">
                <div class="glass-card p-4 rounded-[1.25rem]">
                    <div class="flex items-center gap-3.5">
                        <div class="w-11 h-11 bg-amber-600 rounded-xl flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-id-card text-white text-sm"></i>
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-sm font-bold truncate">{{ Auth::user()->driver->name }}</h2>
                            <p class="text-[10px] text-[#555] truncate font-mono">
                                {{ Auth::user()->driver->license_number }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 pt-3.5 mt-3.5 border-t border-[#1e1e1e]">
                        <i class="fa-solid fa-file-circle-exclamation text-[8px] text-amber-400"></i>
                        <span class="text-[10px] text-[#888] font-bold">Violation Records</span>
                        <span class="text-[#333]">•</span>
                        <span class="font-mono text-[9px] text-[#444]">{{ count($violations) }} entries</span>
                    </div>
                </div>
            </div>

            <!-- ── Page Header (desktop) ── -->
            <div class="hidden lg:block mb-8">
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="text-[9px] font-bold uppercase tracking-[0.15em] text-[#444]">My Records</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight">My <span
                        class="text-red-400">Violations</span></h1>
                <p class="text-[11px] text-[#555] mt-1 flex items-center gap-2">
                    <i class="fa-solid fa-list-check text-[9px] text-amber-400"></i>
                    <span class="text-[#888] font-bold">{{ count($violations) }}</span> violation records on file
                </p>
            </div>

            <!-- ══════════ SUMMARY CARDS ══════════ -->
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 sm:gap-4 mb-5 sm:mb-6">

                <!-- Total Violations -->
                <div class="glass-card rounded-[1.25rem] p-4 sm:p-5 border-l-2 border-l-amber-500">
                    <div class="flex items-center gap-2.5 mb-3">
                        <div
                            class="w-8 h-8 rounded-lg bg-amber-500/10 border border-amber-500/15 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-file text-[10px] text-amber-400"></i>
                        </div>
                        <span class="text-[8px] font-bold uppercase tracking-[0.15em] text-[#333]">Total</span>
                    </div>
                    <p class="text-2xl sm:text-3xl font-black">{{ count($violations) }}</p>
                    <p class="text-[8px] text-[#444] mt-0.5">violation records</p>
                </div>

                <!-- Total Fines -->
                <div class="glass-card rounded-[1.25rem] p-4 sm:p-5 border-l-2 border-l-red-500">
                    <div class="flex items-center gap-2.5 mb-3">
                        <div
                            class="w-8 h-8 rounded-lg bg-red-500/10 border border-red-500/15 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-peso-sign text-[10px] text-red-400"></i>
                        </div>
                        <span class="text-[8px] font-bold uppercase tracking-[0.15em] text-[#333]">Fines</span>
                    </div>
                    <p class="text-2xl sm:text-3xl font-black">₱{{ number_format($totalFines, 0) }}</p>
                    <p class="text-[8px] text-[#444] mt-0.5">total amount</p>
                </div>

                <!-- Latest Violation -->
                <div
                    class="glass-card rounded-[1.25rem] p-4 sm:p-5 border-l-2 border-l-blue-500 col-span-2 sm:col-span-1">
                    <div class="flex items-center gap-2.5 mb-3">
                        <div
                            class="w-8 h-8 rounded-lg bg-blue-500/10 border border-blue-500/15 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-clock-rotate-left text-[10px] text-blue-400"></i>
                        </div>
                        <span class="text-[8px] font-bold uppercase tracking-[0.15em] text-[#333]">Latest</span>
                    </div>
                    @if (count($violations) > 0)
                        <p class="text-[11px] sm:text-xs font-bold truncate">
                            {{ $violations->first()['violationType'] }}</p>
                        <p class="text-[8px] text-[#444] mt-0.5">{{ $violations->first()['date'] }}</p>
                    @else
                        <p class="text-[11px] font-bold text-[#333]">None</p>
                        <p class="text-[8px] text-[#333] mt-0.5">No records yet</p>
                    @endif
                </div>
            </div>

            <!-- ══════════ TABLE CARD ══════════ -->
            <div class="glass-card rounded-[1.25rem] sm:rounded-[1.5rem] overflow-hidden">

                <!-- ── Info Bar ── -->
                <div class="p-4 sm:p-5 border-b border-[#1e1e1e]">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <div
                                class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-[#111] border border-[#1e1e1e] flex items-center justify-center">
                                <i class="fa-solid fa-list-check text-[9px] sm:text-[10px] text-amber-400"></i>
                            </div>
                            <span
                                class="text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-[#555]">Violation
                                Records</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span
                                class="text-[8px] font-bold text-[#222] uppercase tracking-wider flex items-center gap-1.5">
                                <i class="fa-solid fa-lock text-[7px]"></i> Read-only
                            </span>
                        </div>
                    </div>
                </div>

                <!-- ══════════ MOBILE CARD VIEW ══════════ -->
                <div class="lg:hidden p-3 space-y-2">
                    @forelse ($violations as $index => $v)
                        @php
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

                            $iconMap = [
                                'red' => 'fa-gavel text-red-400',
                                'amber' => 'fa-triangle-exclamation text-amber-400',
                                'green' => 'fa-circle-info text-emerald-400',
                                'blue' => 'fa-circle-info text-blue-400',
                            ];
                            $iconClass = $iconMap[$v['codeColor']] ?? $iconMap['amber'];

                            $iconBgMap = [
                                'red' => 'bg-red-500/10 border border-red-500/20',
                                'amber' => 'bg-amber-500/10 border border-amber-500/20',
                                'green' => 'bg-emerald-500/10 border border-emerald-500/20',
                                'blue' => 'bg-blue-500/10 border border-blue-500/20',
                            ];
                            $iconBgClass = $iconBgMap[$v['codeColor']] ?? $iconBgMap['amber'];
                        @endphp

                        <div class="card-animate rounded-xl border border-[#1a1a1a] overflow-hidden"
                            style="animation-delay: {{ $index * 40 }}ms">

                            <!-- Card Top -->
                            <div class="flex items-start justify-between px-3.5 py-3 gap-2.5">
                                <div class="flex items-start gap-2.5 min-w-0">
                                    <div
                                        class="w-9 h-9 rounded-lg {{ $iconBgClass }} flex items-center justify-center shrink-0 mt-0.5">
                                        <i class="fa-solid {{ $iconClass }} text-[10px]"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-[11px] font-bold text-[#ccc] truncate">{{ $v['violationType'] }}
                                        </p>
                                        <div class="flex items-center gap-1.5 mt-1 flex-wrap">
                                            <span
                                                class="text-[7px] font-bold uppercase font-mono px-1.5 py-0.5 rounded-md {{ $badgeClass }}">{{ $v['violationCode'] }}</span>
                                            <span class="text-[7px] text-[#444] font-medium">{{ $offenseLabel }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="text-[12px] font-black text-red-400">₱{{ number_format($v['fine'], 0) }}
                                    </p>
                                </div>
                            </div>

                            <!-- Card Bottom -->
                            <div class="flex items-center border-t border-[#161616] px-3.5 py-2.5 gap-4">
                                <div class="flex items-center gap-1.5">
                                    <i class="fa-solid fa-location-dot text-[7px] text-blue-400/60"></i>
                                    <span
                                        class="text-[9px] font-medium text-[#555] truncate max-w-[120px]">{{ $v['location'] }}</span>
                                </div>
                                <div class="flex items-center gap-1.5 ml-auto shrink-0">
                                    <i class="fa-regular fa-calendar text-[7px] text-[#333]"></i>
                                    <span class="text-[9px] font-medium text-[#555]">{{ $v['date'] }}</span>
                                </div>
                            </div>

                            @if ($v['remarks'])
                                <div class="px-3.5 py-2 border-t border-[#161616]">
                                    <p class="text-[8px] text-[#333] italic truncate">"{{ $v['remarks'] }}"</p>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="flex flex-col items-center justify-center py-16 sm:py-20">
                            <div
                                class="w-14 h-14 rounded-2xl bg-[#111] border border-[#1e1e1e] flex items-center justify-center mb-4">
                                <i class="fa-solid fa-shield-halved text-xl text-[#1a1a1a]"></i>
                            </div>
                            <p class="text-[12px] text-[#444] font-bold mb-1.5">Clean Record</p>
                            <p class="text-[10px] text-[#333] max-w-[240px] text-center leading-relaxed">
                                No violations have been recorded against your license. Keep driving safely.
                            </p>
                            <div
                                class="flex items-center gap-1.5 mt-4 px-3 py-1.5 rounded-lg bg-emerald-500/5 border border-emerald-500/10">
                                <i class="fa-solid fa-circle-check text-[8px] text-emerald-500/40"></i>
                                <span class="text-[8px] font-bold text-emerald-500/40 uppercase tracking-wider">No
                                    offenses</span>
                            </div>
                        </div>
                    @endforelse
                </div>

                <!-- ══════════ DESKTOP TABLE VIEW ══════════ -->
                <div class="hidden lg:block overflow-x-auto">
                    <table class="w-full text-left min-w-[700px]">
                        <thead>
                            <tr
                                class="text-[8px] sm:text-[9px] uppercase tracking-[0.15em] text-[#444] border-b border-[#1e1e1e]">
                                <th class="px-4 sm:px-6 py-3 font-bold w-12">#</th>
                                <th class="px-4 sm:px-6 py-3 font-bold">Violation</th>
                                <th class="px-4 sm:px-6 py-3 font-bold">Date & Location</th>
                                <th class="px-4 sm:px-6 py-3 font-bold text-right">Fine</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-[#1a1a1a]">
                            @forelse ($violations as $v)
                                @php
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

                                    $iconMap = [
                                        'red' => 'fa-gavel text-red-400',
                                        'amber' => 'fa-triangle-exclamation text-amber-400',
                                        'green' => 'fa-circle-info text-emerald-400',
                                        'blue' => 'fa-circle-info text-blue-400',
                                    ];
                                    $iconClass = $iconMap[$v['codeColor']] ?? $iconMap['amber'];

                                    $iconBgMap = [
                                        'red' => 'bg-red-500/10 border border-red-500/20',
                                        'amber' => 'bg-amber-500/10 border border-amber-500/20',
                                        'green' => 'bg-emerald-500/10 border border-emerald-500/20',
                                        'blue' => 'bg-blue-500/10 border border-blue-500/20',
                                    ];
                                    $iconBgClass = $iconBgMap[$v['codeColor']] ?? $iconBgMap['amber'];
                                @endphp

                                <tr class="table-row">
                                    <td class="px-4 sm:px-6 py-3">
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="w-7 h-7 rounded-lg {{ $iconBgClass }} flex items-center justify-center shrink-0">
                                                <i class="fa-solid {{ $iconClass }} text-[8px]"></i>
                                            </div>
                                            <span
                                                class="text-[10px] font-bold text-[#333]">{{ str_pad($loop->index + 1, 2, '0', STR_PAD_LEFT) }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 sm:px-6 py-3">
                                        <div>
                                            <span
                                                class="text-[10px] sm:text-[11px] font-bold text-[#ccc] block">{{ $v['violationType'] }}</span>
                                            <div class="flex items-center gap-2 mt-1">
                                                <span
                                                    class="text-[7px] sm:text-[8px] font-bold uppercase font-mono px-1.5 py-0.5 rounded-md {{ $badgeClass }}">{{ $v['violationCode'] }}</span>
                                                <span
                                                    class="text-[8px] text-[#444] font-medium">{{ $offenseLabel }}</span>
                                            </div>
                                            @if ($v['remarks'])
                                                <p class="text-[8px] text-[#333] mt-1.5 italic truncate max-w-[240px]">
                                                    "{{ $v['remarks'] }}"</p>
                                            @endif
                                        </div>
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
                                        <div class="flex items-baseline justify-end gap-1">
                                            <span
                                                class="text-[11px] font-bold text-[#ccc]">₱{{ number_format($v['fine'], 2) }}</span>
                                        </div>
                                        <div class="text-[8px] text-red-400/60 font-medium mt-0.5">
                                            {{ $v['penalty'] ?: '' }}</div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-16 sm:py-20">
                                        <div class="flex flex-col items-center justify-center">
                                            <div
                                                class="w-14 h-14 rounded-2xl bg-[#111] border border-[#1e1e1e] flex items-center justify-center mb-4">
                                                <i class="fa-solid fa-shield-halved text-xl text-[#1a1a1a]"></i>
                                            </div>
                                            <p class="text-[12px] text-[#444] font-bold mb-1.5">Clean Record</p>
                                            <p
                                                class="text-[10px] text-[#333] max-w-[240px] text-center leading-relaxed">
                                                No violations have been recorded against your license. Keep driving
                                                safely.
                                            </p>
                                            <div
                                                class="flex items-center gap-1.5 mt-4 px-3 py-1.5 rounded-lg bg-emerald-500/5 border border-emerald-500/10">
                                                <i class="fa-solid fa-circle-check text-[8px] text-emerald-500/40"></i>
                                                <span
                                                    class="text-[8px] font-bold text-emerald-500/40 uppercase tracking-wider">No
                                                    offenses</span>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- ── Footer Summary ── -->
                @if (count($violations) > 0)
                    <div class="px-4 sm:px-6 py-4 border-t border-[#1e1e1e] flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="text-[8px] font-bold uppercase tracking-[0.15em] text-[#333]">Total
                                accumulated
                                fines</span>
                        </div>
                        <span class="text-sm font-black text-amber-400">₱{{ number_format($totalFines, 2) }}</span>
                    </div>
                @endif
            </div>

            <!-- ── Disclaimer ── -->
            <div class="mt-5 px-4 py-3 rounded-xl bg-[#0a0a0a] border border-[#141414] flex items-start gap-3">
                <i class="fa-solid fa-circle-info text-[9px] text-[#333] mt-0.5 shrink-0"></i>
                <p class="text-[9px] text-[#333] leading-relaxed">
                    This is a read-only record of violations filed against your license. If you believe any entry is
                    incorrect, please contact your assigned driver manager for review.
                </p>
            </div>

            <!-- Footer Note -->
            <p class="text-center text-[7px] sm:text-[8px] text-[#222] uppercase tracking-[0.2em] pt-5 sm:pt-6">
                SmartCommute Driver Systems &bull; Violations Module
            </p>

        </main>

        <!-- ══════════ LOGOUT MODAL ══════════ -->
        <div x-show="showLogoutModal" x-cloak
            class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 dark:bg-black/80"
            @click.self="showLogoutModal = false" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0">

            <div @click.stop class="glass-panel p-7 sm:p-8 rounded-[2rem] max-w-sm w-full" x-show="showLogoutModal"
                x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
                <div class="text-center">
                    <div
                        class="w-14 h-14 bg-red-500/10 rounded-2xl flex items-center justify-center mx-auto mb-5 border border-red-500/20">
                        <i class="fa-solid fa-power-off text-red-400 text-lg"></i>
                    </div>
                    <h3 class="text-lg font-bold mb-1.5">End Session?</h3>
                    <p class="text-xs text-[#666] mb-7">Are you sure you want to exit the Driver Console?</p>

                    <div class="flex gap-2.5">
                        <button @click="showLogoutModal = false"
                            class="flex-1 py-3 rounded-xl bg-[#1a1a1a] border border-[#2a2a2a] text-[10px] font-bold uppercase tracking-widest hover:bg-[#222] transition">
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

    </div>

</body>

</html>
