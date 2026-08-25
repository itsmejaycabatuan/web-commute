<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SmartCommute | Maintenance Logs</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    @include('partials.head-scripts')
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
    <style>
        body,
        html {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: 'Inter', sans-serif;
            overflow-x: hidden;
        }

        [x-cloak] {
            display: none !important;
        }

        .glass-panel {
            background: rgba(255, 255, 255, 0.85);
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
        }

        .dark .glass-panel {
            background: #111111;
            border: 1px solid #1e1e1e;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.6);
        }

        .glass-card {
            background: #ffffff;
            border: 1px solid #f1f5f9;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        }

        .dark .glass-card {
            background: #161616;
            border: 1px solid #222222;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.5);
        }

        .sidebar-transition {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
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

        ::-webkit-scrollbar {
            width: 3px;
            height: 3px;
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

        .form-input {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #1e293b;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .dark .form-input {
            background: #111;
            border: 1px solid #1e1e1e;
            color: #fff;
        }

        .form-input:focus {
            border-color: rgba(59, 130, 246, 0.4);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.06);
        }

        .form-input::placeholder {
            color: #94a3b8;
        }

        .dark .form-input::placeholder {
            color: #2a2a2a;
        }

        select.vehicle-filter {
            appearance: none;
            background-repeat: no-repeat;
            background-position: right 12px center;
        }

        select.vehicle-filter option {
            background: #ffffff;
            color: #1e293b;
        }

        .dark select.vehicle-filter option {
            background: #111;
            color: #fff;
        }

        .dark input[type="date"]::-webkit-calendar-picker-indicator {
            filter: invert(0.4);
            cursor: pointer;
        }

        .filter-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border: 1px solid;
            transition: all 0.15s;
        }

        .pagination a,
        .pagination span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 28px;
            height: 28px;
            padding: 0 8px;
            border-radius: 6px;
            font-size: 9px;
            font-weight: 700;
            transition: all 0.15s;
            border: 1px solid #e2e8f0;
            color: #64748b;
            background: transparent;
        }

        .dark .pagination a,
        .dark .pagination span {
            border: 1px solid #1e1e1e;
            color: #555;
            background: transparent;
        }

        .pagination a:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
            color: #334155;
        }

        .dark .pagination a:hover {
            background: #1a1a1a;
            border-color: #333;
            color: #888;
        }

        .pagination .active {
            background: rgba(59, 130, 246, 0.08);
            border-color: rgba(59, 130, 246, 0.25);
            color: #3b82f6;
        }

        .dark .pagination .active {
            background: rgba(59, 130, 246, 0.08);
            border-color: rgba(59, 130, 246, 0.25);
            color: #60a5fa;
        }

        .pagination .disabled {
            opacity: 0.2;
            pointer-events: none;
        }
    </style>
</head>

<body class="antialiased text-gray-900 dark:text-white bg-white dark:bg-[#050505]" x-data x-init="$nextTick(() => {
    document.querySelectorAll('.vehicle-filter-server').forEach(function(sel) {
        sel.addEventListener('change', function() {
            const url = new URL(window.location.href);
            if (this.value) { url.searchParams.set('vehicle', this.value); } else { url.searchParams.delete('vehicle'); }
            window.location.href = url.toString();
        });
    });
})">

    <x-layout.sidebar />

    <main :class="$store.sidebar.open ? 'md:ml-72' : 'md:ml-20'"
        class="sidebar-transition pt-8 pr-4 sm:pr-8 pb-8 pl-4 sm:pl-8 min-h-screen mb-12">

        @php
            $totalCost = $logs->sum(fn($log) => (float) ($log->preventiveMaintenance?->last_service_cost ?? 0));
            $uniqueVehicles = $logs->pluck('preventiveMaintenance.vehicle_id')->filter()->unique()->count();

            // Prepare logs data for Alpine
            $logsData = $logs
                ->map(function ($log) {
                    $pm = $log->preventiveMaintenance;
                    $plate = $pm?->vehicle?->plate_number ?? 'N/A';

                    $badgeColors = [
                        ['bg' => 'rgba(59,130,246,0.08)', 'text' => '#3b82f6', 'border' => 'rgba(59,130,246,0.2)'],
                        ['bg' => 'rgba(168,85,247,0.08)', 'text' => '#a855f7', 'border' => 'rgba(168,85,247,0.2)'],
                        ['bg' => 'rgba(16,185,129,0.08)', 'text' => '#10b981', 'border' => 'rgba(16,185,129,0.2)'],
                        ['bg' => 'rgba(251,146,60,0.08)', 'text' => '#f5923c', 'border' => 'rgba(251,146,60,0.2)'],
                        ['bg' => 'rgba(244,63,94,0.08)', 'text' => '#f43f5e', 'border' => 'rgba(244,63,94,0.2)'],
                        ['bg' => 'rgba(45,212,191,0.08)', 'text' => '#2dd4bf', 'border' => 'rgba(45,212,191,0.2)'],
                    ];
                    $colorIdx = crc32($plate) % count($badgeColors);
                    $color = $badgeColors[$colorIdx];

                    return [
                        'id' => $log->id,
                        'date' => $pm?->last_service_date?->format('M d, Y') ?? '—',
                        'day' => $pm?->last_service_date?->format('l') ?? '',
                        'date_raw' => $pm?->last_service_date?->format('Y-m-d') ?? null,
                        'plate' => $plate,
                        'vehicle_name' => ($pm?->vehicle?->brand ?? '') . ' ' . ($pm?->vehicle?->model ?? ''),
                        'service' => $pm?->maintenanceTask?->tasks_performed ?? '—',
                        'mileage' => $pm?->last_service_odo ? number_format($pm->last_service_odo) . ' km' : '—',
                        'mileage_raw' => $pm?->last_service_odo ?? null,
                        'cost' => $pm?->last_service_cost ? number_format($pm->last_service_cost, 2) : '0.00',
                        'cost_raw' => (float) ($pm?->last_service_cost ?? 0),
                        'comments' => $pm?->comments ?? '—',
                        'color' => $color,
                    ];
                })
                ->values();
        @endphp

        <script type="text/json" id="logs-data">
            @json($logsData)
        </script>

        <!-- ── Mobile: Identity Card ── -->
        <div class="lg:hidden mb-5">
            <div class="glass-card p-4 rounded-[1.25rem]">
                <div class="flex items-center gap-3.5">
                    <div class="w-11 h-11 bg-amber-600 rounded-xl flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-wrench text-sm text-white"></i>
                    </div>
                    <div class="min-w-0">
                        <h2 class="text-sm font-bold text-gray-900 dark:text-white truncate">Maintenance Manager</h2>
                        <p class="text-[10px] text-gray-500 dark:text-[#555] truncate">{{ Auth::user()->email }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 pt-3.5 mt-3.5 border-t border-gray-200 dark:border-[#1e1e1e]">
                    <i class="fa-solid fa-wrench text-[8px] text-amber-500 dark:text-amber-400"></i>
                    <span class="text-[10px] text-gray-600 dark:text-[#888] font-bold">Fleet Access</span>
                    <span class="text-gray-300 dark:text-[#333]">•</span>
                    <span class="font-mono text-[9px] text-gray-400 dark:text-[#444]">Calendar</span>
                </div>
            </div>
        </div>

        <!-- ── Page Header (desktop) ── -->
        <div class="hidden lg:flex items-end justify-between mb-8">
            <div>
                <div class="flex items-center gap-2 mb-1.5">
                    <span
                        class="text-[9px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Service
                        History</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-gray-900 dark:text-white">Maintenance
                    <span class="text-blue-600 dark:text-blue-500">Logs</span>
                </h1>
                <p class="text-[11px] text-gray-500 dark:text-[#555] mt-1 flex items-center gap-2">
                    <i class="fa-solid fa-wrench text-[9px] text-amber-500 dark:text-amber-400"></i>
                    Historical record of all fleet preventive services
                </p>
            </div>
            <div>
                <label
                    class="text-[7px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#333] block mb-1.5 text-right">Filter
                    Vehicle</label>
                <select
                    class="vehicle-filter-server bg-gray-50 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] rounded-xl px-4 py-2.5 text-[10px] font-bold text-gray-600 dark:text-[#888] focus:ring-1 focus:ring-blue-500/50 outline-none cursor-pointer pr-10 w-56">
                    <option value="">All Vehicles</option>
                    @foreach ($vehicleOptions ?? [] as $id => $label)
                        <option value="{{ $id }}" {{ request('vehicle') == (string) $id ? 'selected' : '' }}>
                            {{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- ── Mobile Vehicle Filter ── -->
        <div class="lg:hidden mb-5">
            <label
                class="text-[7px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#333] block mb-1.5">Filter
                Vehicle</label>
            <select
                class="vehicle-filter-server w-full bg-gray-50 dark:bg-[#161616] border border-gray-200 dark:border-[#222] rounded-xl px-4 py-2.5 text-[10px] font-bold text-gray-600 dark:text-[#888] focus:ring-1 focus:ring-blue-500/50 outline-none cursor-pointer pr-10">
                <option value="">All Vehicles</option>
                @foreach ($vehicleOptions ?? [] as $id => $label)
                    <option value="{{ $id }}" {{ request('vehicle') == (string) $id ? 'selected' : '' }}>
                        {{ $label }}</option>
                @endforeach
            </select>
        </div>

        <!-- ══════════ STAT CARDS ══════════ -->
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 sm:gap-4 mb-5 sm:mb-6" x-data="maintenanceLogsApp()">
            <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-blue-500">
                <div class="flex items-center gap-2 mb-2 sm:mb-3">
                    <div class="w-6 h-6 rounded-md bg-blue-500/10 flex items-center justify-center"><i
                            class="fa-solid fa-list-check text-[8px] text-blue-500 dark:text-blue-400"></i></div>
                    <span
                        class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Total
                        Entries</span>
                </div>
                <div class="flex items-baseline gap-1.5">
                    <span
                        class="text-xl sm:text-2xl font-black tracking-tight text-gray-900 dark:text-white">{{ $logs->total() }}</span>
                    <span class="text-xs font-bold text-gray-400 dark:text-[#555]">logs</span>
                </div>
            </div>
            <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-purple-500">
                <div class="flex items-center gap-2 mb-2 sm:mb-3">
                    <div class="w-6 h-6 rounded-md bg-purple-500/10 flex items-center justify-center"><i
                            class="fa-solid fa-car text-[8px] text-purple-500 dark:text-purple-400"></i></div>
                    <span
                        class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Vehicles</span>
                </div>
                <div class="flex items-baseline gap-1.5">
                    <span
                        class="text-xl sm:text-2xl font-black tracking-tight text-purple-600 dark:text-purple-400">{{ $uniqueVehicles }}</span>
                    <span
                        class="text-xs font-bold text-gray-400 dark:text-[#555]">{{ $uniqueVehicles === 1 ? 'unit' : 'units' }}</span>
                </div>
            </div>
            <div
                class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-emerald-500 col-span-2 sm:col-span-1">
                <div class="flex items-center gap-2 mb-2 sm:mb-3">
                    <div class="w-6 h-6 rounded-md bg-emerald-500/10 flex items-center justify-center"><i
                            class="fa-solid fa-coins text-[8px] text-emerald-500 dark:text-emerald-400"></i></div>
                    <span
                        class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Total
                        Cost</span>
                </div>
                <div class="flex items-baseline gap-1.5">
                    <span
                        class="text-xl sm:text-2xl font-black tracking-tight text-emerald-600 dark:text-emerald-400">₱{{ number_format($totalCost, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- ══════════ SEARCH & FILTER BAR ══════════ -->
        <div x-data="maintenanceLogsApp()" class="mb-5">
            <div class="glass-card p-4 rounded-[1.25rem]">
                <!-- Main Search Row -->
                <div class="flex flex-col sm:flex-row gap-3 mb-3">
                    <!-- Search Input -->
                    <div class="relative flex-1">
                        <i
                            class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-300 dark:text-[#333] text-[10px]"></i>
                        <input type="text" x-model="search" placeholder="Search service, plate number, comments..."
                            class="form-input w-full pl-10 pr-10 py-2.5 rounded-xl text-[10px] font-bold">
                        <button x-show="search.length > 0" @click="search = ''"
                            class="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 rounded-md bg-gray-200 dark:bg-[#222] flex items-center justify-center text-gray-500 dark:text-[#555] hover:text-gray-700 dark:hover:text-white transition-colors">
                            <i class="fa-solid fa-xmark text-[8px]"></i>
                        </button>
                    </div>

                    <!-- Date From -->
                    <div class="relative">
                        <i
                            class="fa-regular fa-calendar absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-300 dark:text-[#333] text-[10px]"></i>
                        <input type="date" x-model="dateFrom"
                            class="form-input w-full sm:w-44 pl-10 pr-3 py-2.5 rounded-xl text-[10px] font-mono font-bold">
                    </div>

                    <!-- Date To -->
                    <div class="relative">
                        <i
                            class="fa-regular fa-calendar-check absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-300 dark:text-[#333] text-[10px]"></i>
                        <input type="date" x-model="dateTo"
                            class="form-input w-full sm:w-44 pl-10 pr-3 py-2.5 rounded-xl text-[10px] font-mono font-bold">
                    </div>

                    <!-- Sort Dropdown -->
                    <div class="relative">
                        <i
                            class="fa-solid fa-arrow-up-wide-short absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-300 dark:text-[#333] text-[10px]"></i>
                        <select x-model="sortBy"
                            class="form-input form-select w-full lg:w-44 pl-10 pr-10 py-2.5 rounded-xl text-[10px] font-bold cursor-pointer">
                            <option value="date_desc">Newest First</option>
                            <option value="date_asc">Oldest First</option>
                            <option value="cost_desc">Highest Cost</option>
                            <option value="cost_asc">Lowest Cost</option>
                            <option value="mileage_desc">Highest Mileage</option>
                            <option value="mileage_asc">Lowest Mileage</option>
                        </select>
                    </div>
                </div>

                <!-- Active Filters & Results Count -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2 flex-wrap">
                        <template x-if="hasActiveFilters">
                            <span
                                class="text-[7px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#333]">Active:</span>
                        </template>

                        <template x-if="search.length > 0">
                            <span
                                class="filter-chip bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20">
                                <i class="fa-solid fa-magnifying-glass text-[7px]"></i>
                                <span x-text="search.substring(0, 20) + (search.length > 20 ? '...' : '')"></span>
                                <button @click="search = ''"
                                    class="ml-0.5 hover:text-blue-800 dark:hover:text-blue-300">
                                    <i class="fa-solid fa-xmark text-[7px]"></i>
                                </button>
                            </span>
                        </template>

                        <template x-if="dateFrom">
                            <span
                                class="filter-chip bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20">
                                <i class="fa-regular fa-calendar text-[7px]"></i>
                                From: <span x-text="dateFrom"></span>
                                <button @click="dateFrom = ''"
                                    class="ml-0.5 hover:text-emerald-800 dark:hover:text-emerald-300">
                                    <i class="fa-solid fa-xmark text-[7px]"></i>
                                </button>
                            </span>
                        </template>

                        <template x-if="dateTo">
                            <span
                                class="filter-chip bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20">
                                <i class="fa-regular fa-calendar-check text-[7px]"></i>
                                To: <span x-text="dateTo"></span>
                                <button @click="dateTo = ''"
                                    class="ml-0.5 hover:text-emerald-800 dark:hover:text-emerald-300">
                                    <i class="fa-solid fa-xmark text-[7px]"></i>
                                </button>
                            </span>
                        </template>

                        <template x-if="sortBy !== 'date_desc'">
                            <span
                                class="filter-chip bg-purple-500/10 text-purple-600 dark:text-purple-400 border-purple-500/20">
                                <i class="fa-solid fa-arrow-up-wide-short text-[7px]"></i>
                                <span x-text="sortLabels[sortBy] || sortBy"></span>
                                <button @click="sortBy = 'date_desc'"
                                    class="ml-0.5 hover:text-purple-800 dark:hover:text-purple-300">
                                    <i class="fa-solid fa-xmark text-[7px]"></i>
                                </button>
                            </span>
                        </template>

                        <button x-show="hasActiveFilters" @click="clearAllFilters()"
                            class="filter-chip bg-gray-100 dark:bg-[#111] text-gray-500 dark:text-[#555] border-gray-200 dark:border-[#222] hover:text-rose-500 dark:hover:text-rose-400 hover:border-rose-500/20 cursor-pointer">
                            <i class="fa-solid fa-filter-circle-xmark text-[7px]"></i>
                            Clear All
                        </button>
                    </div>

                    <span
                        class="text-[7px] sm:text-[8px] font-bold text-gray-400 dark:text-[#333] uppercase tracking-widest whitespace-nowrap"
                        x-text="filtered.length + ' of ' + logs.length + ' shown'"></span>
                </div>
            </div>
        </div>

        <!-- ══════════ SERVICE LOG TABLE ══════════ -->
        <div x-data="maintenanceLogsApp()" class="glass-card rounded-[1.25rem] sm:rounded-[1.5rem] overflow-hidden">
            <div class="p-4 sm:p-6 pb-0">
                <div class="flex items-center justify-between mb-4 sm:mb-5">
                    <div class="flex items-center gap-2.5">
                        <div
                            class="w-7 h-7 rounded-lg bg-gray-100 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] flex items-center justify-center">
                            <i class="fa-solid fa-calendar-days text-[9px] text-amber-500 dark:text-amber-400"></i>
                        </div>
                        <span
                            class="text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-gray-500 dark:text-[#555]">Service
                            Log</span>
                    </div>
                    <span
                        class="text-[7px] sm:text-[8px] font-bold text-gray-400 dark:text-[#333] uppercase tracking-widest"
                        x-text="filtered.length + ' entries'"></span>
                </div>
            </div>
            <div class="overflow-x-auto -mx-2 px-2 pb-2">
                <table class="w-full text-left min-w-[750px]">
                    <thead>
                        <tr
                            class="text-[7px] sm:text-[8px] uppercase tracking-[0.15em] text-gray-400 dark:text-[#444] border-b border-gray-200 dark:border-[#1e1e1e]">
                            <th class="px-4 sm:px-6 py-2.5 font-bold">Date</th>
                            <th class="px-4 sm:px-6 py-2.5 font-bold">Vehicle</th>
                            <th class="px-4 sm:px-6 py-2.5 font-bold">Service Performed</th>
                            <th class="px-4 sm:px-6 py-2.5 font-bold">Mileage</th>
                            <th class="px-4 sm:px-6 py-2.5 font-bold text-right">Cost</th>
                            <th class="px-4 sm:px-6 py-2.5 font-bold">Comments</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-[#1a1a1a]">
                        <template x-for="log in filtered" :key="log.id">
                            <tr class="table-row">
                                <td class="px-4 sm:px-6 py-3.5">
                                    <span
                                        class="text-[10px] sm:text-[11px] font-bold text-gray-500 dark:text-[#888] block"
                                        x-text="log.date"></span>
                                    <span
                                        class="text-[7px] sm:text-[8px] text-gray-400 dark:text-[#333] font-bold uppercase"
                                        x-text="log.day"></span>
                                </td>
                                <td class="px-4 sm:px-6 py-3.5">
                                    <span
                                        class="inline-flex items-center gap-1.5 text-[8px] sm:text-[9px] font-mono font-bold px-2 py-1 rounded-md"
                                        :style="'background: ' + log.color.bg + '; color: ' + log.color.text +
                                            '; border: 1px solid ' + log.color.border">
                                        <i class="fa-solid fa-car text-[7px]"></i>
                                        <span x-text="log.plate"></span>
                                    </span>
                                </td>
                                <td class="px-4 sm:px-6 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-8 h-8 rounded-lg bg-emerald-500/10 border border-emerald-500/15 flex items-center justify-center shrink-0">
                                            <i
                                                class="fa-solid fa-check text-[8px] text-emerald-500 dark:text-emerald-400"></i>
                                        </div>
                                        <p class="text-[10px] sm:text-[11px] font-bold text-gray-700 dark:text-[#ccc] truncate max-w-[220px]"
                                            x-text="log.service"></p>
                                    </div>
                                </td>
                                <td class="px-4 sm:px-6 py-3.5">
                                    <span
                                        class="text-[10px] sm:text-[11px] font-bold text-gray-500 dark:text-[#666] font-mono"
                                        x-text="log.mileage"></span>
                                </td>
                                <td class="px-4 sm:px-6 py-3.5 text-right">
                                    <span
                                        class="text-[10px] sm:text-[11px] font-bold text-gray-900 dark:text-white font-mono"
                                        x-text="'₱' + log.cost"></span>
                                </td>
                                <td class="px-4 sm:px-6 py-3.5">
                                    <span
                                        class="text-[9px] sm:text-[10px] text-gray-400 dark:text-[#444] truncate block max-w-[180px]"
                                        :title="log.comments" x-text="log.comments"></span>
                                </td>
                            </tr>
                        </template>

                        <!-- Empty State -->
                        <tr x-show="filtered.length === 0" x-cloak>
                            <td colspan="6" class="py-10 sm:py-12">
                                <div class="flex flex-col items-center justify-center">
                                    <div
                                        class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] flex items-center justify-center mb-2.5">
                                        <i
                                            class="fa-solid fa-filter-circle-xmark text-sm text-gray-300 dark:text-[#333]"></i>
                                    </div>
                                    <p class="text-[10px] sm:text-[11px] text-gray-400 dark:text-[#444] font-medium">No
                                        logs match your filters</p>
                                    <p class="text-[8px] text-gray-300 dark:text-[#333] mt-0.5">Try adjusting your
                                        search or date range</p>
                                    <button @click="clearAllFilters()"
                                        class="mt-3 text-[9px] font-bold text-blue-500 dark:text-blue-400 hover:underline">
                                        Clear all filters
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Table Footer -->
            <div
                class="px-4 sm:px-6 py-3 border-t border-gray-200 dark:border-[#1e1e1e] bg-gray-50 dark:bg-[#111] flex flex-col sm:flex-row justify-between items-center gap-2">
                <div class="flex items-center gap-4">
                    <span class="text-[7px] text-gray-400 dark:text-[#333] font-bold uppercase tracking-widest">
                        Showing <span x-text="filtered.length"></span> entries
                    </span>
                    <span class="text-[7px] text-gray-400 dark:text-[#333] font-bold uppercase tracking-widest">
                        Filtered Cost: <span class="text-emerald-600 dark:text-emerald-400"
                            x-text="'₱' + filteredCostFormatted"></span>
                    </span>
                </div>
                @if ($logs->hasPages())
                    <div class="flex gap-1">{{ $logs->links('maintenance-manager.partials.pagination') }}</div>
                @endif
            </div>
        </div>
    </main>

    <!-- ══════════ ALPINE.JS APP ══════════ -->
    <script>
        function maintenanceLogsApp() {
            return {
                logs: [],
                search: '',
                dateFrom: '',
                dateTo: '',
                sortBy: 'date_desc',

                sortLabels: {
                    'date_desc': 'Newest First',
                    'date_asc': 'Oldest First',
                    'cost_desc': 'Highest Cost',
                    'cost_asc': 'Lowest Cost',
                    'mileage_desc': 'Highest Mileage',
                    'mileage_asc': 'Lowest Mileage'
                },

                init() {
                    const dataElement = document.getElementById('logs-data');
                    if (dataElement) {
                        try {
                            this.logs = JSON.parse(dataElement.textContent);
                        } catch (e) {
                            console.error('Failed to parse logs data:', e);
                            this.logs = [];
                        }
                    }
                },

                get hasActiveFilters() {
                    return this.search.length > 0 || this.dateFrom || this.dateTo || this.sortBy !== 'date_desc';
                },

                get filtered() {
                    let result = [...this.logs];

                    // Search filter
                    if (this.search && this.search.trim() !== '') {
                        const search = this.search.toLowerCase().trim();
                        result = result.filter(log =>
                            (log.plate && log.plate.toLowerCase().includes(search)) ||
                            (log.vehicle_name && log.vehicle_name.toLowerCase().includes(search)) ||
                            (log.service && log.service.toLowerCase().includes(search)) ||
                            (log.comments && log.comments.toLowerCase().includes(search)) ||
                            (log.date && log.date.toLowerCase().includes(search)) ||
                            (log.mileage && log.mileage.toLowerCase().includes(search))
                        );
                    }

                    // Date range filter
                    if (this.dateFrom) {
                        result = result.filter(log => log.date_raw && log.date_raw >= this.dateFrom);
                    }
                    if (this.dateTo) {
                        result = result.filter(log => log.date_raw && log.date_raw <= this.dateTo);
                    }

                    // Sorting
                    switch (this.sortBy) {
                        case 'date_desc':
                            result.sort((a, b) => (b.date_raw || '').localeCompare(a.date_raw || ''));
                            break;
                        case 'date_asc':
                            result.sort((a, b) => (a.date_raw || '').localeCompare(b.date_raw || ''));
                            break;
                        case 'cost_desc':
                            result.sort((a, b) => (b.cost_raw || 0) - (a.cost_raw || 0));
                            break;
                        case 'cost_asc':
                            result.sort((a, b) => (a.cost_raw || 0) - (b.cost_raw || 0));
                            break;
                        case 'mileage_desc':
                            result.sort((a, b) => (b.mileage_raw || 0) - (a.mileage_raw || 0));
                            break;
                        case 'mileage_asc':
                            result.sort((a, b) => (a.mileage_raw || 0) - (b.mileage_raw || 0));
                            break;
                    }

                    return result;
                },

                get filteredCostFormatted() {
                    const total = this.filtered.reduce((sum, log) => sum + (log.cost_raw || 0), 0);
                    return total.toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                },

                clearAllFilters() {
                    this.search = '';
                    this.dateFrom = '';
                    this.dateTo = '';
                    this.sortBy = 'date_desc';
                }
            }
        }
    </script>
</body>

</html>
