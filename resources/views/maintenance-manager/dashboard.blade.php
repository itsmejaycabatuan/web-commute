<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SmartCommute | Maintenance Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
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

        .tab-active {
            color: #3b82f6;
            border-bottom: 2px solid #3b82f6;
        }

        .dark .tab-active {
            color: #fff;
            border-bottom: 2px solid #3b82f6;
        }

        .tab-inactive {
            color: #94a3b8;
            border-bottom: 2px solid transparent;
        }

        .dark .tab-inactive {
            color: #333;
            border-bottom: 2px solid transparent;
        }

        .tab-inactive:hover {
            color: #475569;
        }

        .dark .tab-inactive:hover {
            color: #666;
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

        .donut-center {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            pointer-events: none;
        }

        select.vehicle-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
        }

        .dark select.vehicle-select {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='rgba(255,255,255,0.3)' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
        }

        select.vehicle-select option {
            background: #ffffff;
            color: #1e293b;
        }

        .dark select.vehicle-select option {
            background: #111;
            color: #fff;
        }
    </style>
</head>

<script>
    function summaryApp() {
        return {
            activeTab: 'cpk',
            showLogoutModal: false,
        };
    }
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.vehicle-select').forEach(function(sel) {
            sel.addEventListener('change', function() {
                const url = new URL(window.location.href);
                url.searchParams.set('vehicle_id', this.value);
                window.location.href = url.toString();
            });
        });
    });
</script>

<body class="antialiased text-gray-900 dark:text-white bg-white dark:bg-[#050505]" x-data="summaryApp()" x-cloak>

    @include('components.flash')
    <x-layout.sidebar />

    <main :class="$store.sidebar.open ? 'md:ml-72' : 'md:ml-20'"
        class="sidebar-transition pt-8 pr-4 sm:pr-8 pb-8 pl-4 sm:pl-8 min-h-screen mb-12">

        @php
            $chartMonthLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            $chartMonthlyData = [];
            foreach (range(1, 12) as $m) {
                $chartMonthlyData[] = round($monthlyTotals[$m] ?? 0, 2);
            }

            $donutLabels = [];
            $donutData = [];
            foreach ($costSummary as $taskName => $months) {
                $total = array_sum(array_filter($months));
                if ($total > 0) {
                    $donutLabels[] = $taskName;
                    $donutData[] = round($total, 2);
                }
            }
            $donutTotal = array_sum($donutData);
        @endphp

        @if (!$vehicle)

            <!-- ── Empty State ── -->
            <div class="flex flex-col items-center justify-center py-32">
                <div
                    class="w-14 h-14 rounded-2xl bg-gray-100 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] flex items-center justify-center mb-4">
                    <i class="fa-solid fa-car text-gray-300 dark:text-[#333] text-xl"></i>
                </div>
                <p class="text-gray-400 dark:text-[#444] text-[11px] font-bold mb-1">No vehicles yet.</p>
                <p class="text-gray-300 dark:text-[#333] text-[10px]">Add a vehicle first to view maintenance summaries.
                </p>
            </div>
        @else
            <!-- ── Mobile: Identity Card ── -->
            <div class="lg:hidden mb-5">
                <div class="glass-card p-4 rounded-[1.25rem]">
                    <div class="flex items-center gap-3.5">
                        <div class="w-11 h-11 bg-amber-600 rounded-xl flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-wrench text-sm text-white"></i>
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-sm font-bold text-gray-900 dark:text-white truncate">Maintenance Manager
                            </h2>
                            <p class="text-[10px] text-gray-500 dark:text-[#555] truncate">{{ Auth::user()->email }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 pt-3.5 mt-3.5 border-t border-gray-200 dark:border-[#1e1e1e]">
                        <i class="fa-solid fa-wrench text-[8px] text-amber-500 dark:text-amber-400"></i>
                        <span class="text-[10px] text-gray-600 dark:text-[#888] font-bold">Fleet Access</span>
                        <span class="text-gray-300 dark:text-[#333]">•</span>
                        <span class="font-mono text-[9px] text-gray-400 dark:text-[#444]">Maintenance</span>
                    </div>
                </div>
            </div>

            <!-- ── Page Header (desktop) ── -->
            <div class="hidden lg:flex items-end justify-between mb-8">
                <div>
                    <div class="flex items-center gap-2 mb-1.5">
                        <span
                            class="text-[9px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Fleet
                            Overview</span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-gray-900 dark:text-white">Maintenance
                        <span class="text-blue-600 dark:text-blue-500">Dashboard</span>
                    </h1>
                    <p class="text-[11px] text-gray-500 dark:text-[#555] mt-1 flex items-center gap-2">
                        <i class="fa-solid fa-wrench text-[9px] text-amber-500 dark:text-amber-400"></i>
                        Role: <span class="text-gray-700 dark:text-[#888] font-bold">Maintenance Manager</span>
                        <span class="text-gray-300 dark:text-[#333]">•</span>
                        <span
                            class="font-mono text-[10px] text-gray-400 dark:text-[#444]">{{ Auth::user()->email }}</span>
                    </p>
                </div>
                <select
                    class="vehicle-select bg-gray-50 dark:bg-[#0a0a0a] border border-gray-200 dark:border-[#1e1e1e] rounded-xl px-4 py-2.5 text-[10px] font-bold text-gray-600 dark:text-[#888] focus:ring-1 focus:ring-blue-500/50 outline-none cursor-pointer pr-10">
                    @foreach ($vehicles as $v)
                        <option value="{{ $v->id }}" {{ $vehicle->id === $v->id ? 'selected' : '' }}>
                            {{ $v->plate_number }} — {{ $v->brand }} {{ $v->model }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- ── Mobile Vehicle Selector ── -->
            <div class="lg:hidden mb-5">
                <select
                    class="vehicle-select w-full bg-gray-50 dark:bg-[#161616] border border-gray-200 dark:border-[#222] rounded-xl px-4 py-2.5 text-[10px] font-bold text-gray-600 dark:text-[#888] focus:ring-1 focus:ring-blue-500/50 outline-none cursor-pointer pr-10">
                    @foreach ($vehicles as $v)
                        <option value="{{ $v->id }}" {{ $vehicle->id === $v->id ? 'selected' : '' }}>
                            {{ $v->plate_number }} — {{ $v->brand }} {{ $v->model }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- ══════════ VEHICLE INFORMATION CARD ══════════ -->
            <div class="glass-card rounded-[1.25rem] sm:rounded-[1.5rem] p-4 sm:p-6 mb-5 sm:mb-6">
                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 mb-5">
                    <div class="flex items-center gap-3.5">
                        <div
                            class="w-11 h-11 rounded-xl bg-gray-100 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-car text-gray-400 dark:text-[#555] text-sm"></i>
                        </div>
                        <div>
                            <h3 class="text-sm sm:text-base font-black tracking-tight text-gray-900 dark:text-white">
                                {{ $vehicle->year }} {{ $vehicle->brand }} {{ $vehicle->model }}</h3>
                            <div class="flex items-center gap-2 mt-1">
                                <span
                                    class="font-mono text-[9px] text-gray-500 dark:text-[#555] bg-gray-100 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] px-1.5 py-0.5 rounded-md">{{ $vehicle->plate_number }}</span>
                                @if ($vehicle->status === 'active')
                                    <span
                                        class="inline-flex items-center gap-1 text-[8px] font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded-full border border-emerald-500/20">
                                        <span
                                            class="w-1.5 h-1.5 rounded-full bg-emerald-500 dark:bg-emerald-400 animate-pulse"></span>Active
                                    </span>
                                @elseif($vehicle->status === 'maintenance')
                                    <span
                                        class="inline-flex items-center gap-1 text-[8px] font-bold text-amber-600 dark:text-amber-400 bg-amber-500/10 px-2 py-0.5 rounded-full border border-amber-500/20">
                                        <span
                                            class="w-1.5 h-1.5 rounded-full bg-amber-500 dark:bg-amber-400 animate-pulse"></span>Maintenance
                                    </span>
                                @elseif($vehicle->status === 'inactive')
                                    <span
                                        class="inline-flex items-center gap-1 text-[8px] font-bold text-rose-500 dark:text-rose-400/70 bg-rose-500/[0.06] px-2 py-0.5 rounded-full border border-rose-500/10">
                                        <span
                                            class="w-1.5 h-1.5 rounded-full bg-rose-500 dark:bg-rose-400/70"></span>Inactive
                                    </span>
                                @else
                                    <span
                                        class="inline-flex items-center gap-1 text-[8px] font-bold text-gray-400 dark:text-[#333] bg-gray-100 dark:bg-[#111] px-2 py-0.5 rounded-full border border-gray-200 dark:border-[#1e1e1e]">
                                        <span
                                            class="w-1.5 h-1.5 rounded-full bg-gray-400 dark:bg-[#333]"></span>{{ ucfirst($vehicle->status ?? '') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="sm:text-right">
                        <span
                            class="text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#333] block">Last
                            updated</span>
                        <span
                            class="text-[10px] font-bold text-gray-500 dark:text-[#555]">{{ $vehicle->updated_at?->format('M d, Y') }}</span>
                    </div>
                </div>

                <div
                    class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-x-4 gap-y-3 pt-4 border-t border-gray-200 dark:border-[#1e1e1e]">
                    <div>
                        <span
                            class="text-[7px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#333] block mb-1">Fuel
                            Type</span>
                        <span
                            class="text-[10px] font-bold text-gray-700 dark:text-[#888]">{{ $vehicle->fuel_type ?? '—' }}</span>
                    </div>
                    <div>
                        <span
                            class="text-[7px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#333] block mb-1">Tank
                            Capacity</span>
                        <span
                            class="text-[10px] font-bold text-gray-700 dark:text-[#888]">{{ $vehicle->tank_capacity ? $vehicle->tank_capacity . ' L' : '—' }}</span>
                    </div>
                    <div>
                        <span
                            class="text-[7px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#333] block mb-1">Driver</span>
                        <span
                            class="text-[10px] font-bold text-gray-700 dark:text-[#888]">{{ $vehicle->driver?->name ?? 'Unassigned' }}</span>
                    </div>
                    <div>
                        <span
                            class="text-[7px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#333] block mb-1">Location</span>
                        <span
                            class="text-[10px] font-bold text-gray-700 dark:text-[#888]">{{ $vehicle->location ?? '—' }}</span>
                    </div>
                    <div>
                        <span
                            class="text-[7px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#333] block mb-1">Acquired</span>
                        <span
                            class="text-[10px] font-bold text-gray-500 dark:text-[#555]">{{ $vehicle->acquisition_date ? $vehicle->acquisition_date->format('M d, Y') : '—' }}</span>
                    </div>
                    <div>
                        <span
                            class="text-[7px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#333] block mb-1">Disposal</span>
                        <span
                            class="text-[10px] font-bold text-gray-500 dark:text-[#555]">{{ $vehicle->exp_disposal_date ? $vehicle->exp_disposal_date->format('M d, Y') : '—' }}</span>
                    </div>
                </div>

                <div class="flex items-center gap-2 mt-3 pt-3 border-t border-gray-200 dark:border-[#1e1e1e]">
                    <span
                        class="text-[7px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#333]">VIN</span>
                    <span
                        class="font-mono text-[9px] text-gray-500 dark:text-[#444]">{{ $vehicle->vin ?? '—' }}</span>
                </div>
            </div>

            <!-- ══════════ STAT CARDS ══════════ -->
            <div class="grid grid-cols-2 sm:grid-cols-2 xl:grid-cols-3 gap-3 sm:gap-4 mb-5 sm:mb-6">

                <div
                    class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-amber-500 col-span-2 xl:col-span-1">
                    <div class="flex items-center gap-2 mb-2 sm:mb-3">
                        <div class="w-6 h-6 rounded-md bg-amber-500/10 flex items-center justify-center">
                            <i class="fa-solid fa-road text-[8px] text-amber-500 dark:text-amber-400"></i>
                        </div>
                        <span
                            class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Annual
                            Kilometers</span>
                    </div>
                    <div class="flex items-baseline gap-1 sm:gap-1.5">
                        <span
                            class="text-xl sm:text-2xl font-black tracking-tight text-gray-900 dark:text-white">{{ number_format($annualKm) }}</span>
                        <span class="text-xs sm:text-sm font-bold text-gray-400 dark:text-[#555]">km</span>
                    </div>
                    <p class="text-[8px] text-gray-400 dark:text-[#333] mt-1 font-bold uppercase">Driven in
                        {{ $year }}</p>
                </div>

                <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-emerald-500">
                    <div class="flex items-center gap-2 mb-2 sm:mb-3">
                        <div class="w-6 h-6 rounded-md bg-emerald-500/10 flex items-center justify-center">
                            <i class="fa-solid fa-gauge text-[8px] text-emerald-500 dark:text-emerald-400"></i>
                        </div>
                        <span
                            class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Cost
                            Per Km</span>
                    </div>
                    <div class="flex items-baseline gap-1 sm:gap-1.5">
                        <span
                            class="text-xl sm:text-2xl font-black tracking-tight text-emerald-600 dark:text-emerald-400">₱{{ number_format($costPerKm, 2) }}</span>
                    </div>
                    <p class="text-[8px] text-gray-400 dark:text-[#333] mt-1 font-bold uppercase">Per km average (YTD)
                    </p>
                </div>

                <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-blue-500">
                    <div class="flex items-center gap-2 mb-2 sm:mb-3">
                        <div class="w-6 h-6 rounded-md bg-blue-500/10 flex items-center justify-center">
                            <i class="fa-solid fa-coins text-[8px] text-blue-500 dark:text-blue-400"></i>
                        </div>
                        <span
                            class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Total
                            Service Cost</span>
                    </div>
                    <div class="flex items-baseline gap-1 sm:gap-1.5">
                        <span
                            class="text-xl sm:text-2xl font-black tracking-tight text-blue-600 dark:text-blue-400">₱{{ number_format($totalServiceCost, 2) }}</span>
                    </div>
                    <p class="text-[8px] text-gray-400 dark:text-[#333] mt-1 font-bold uppercase">{{ $year }}
                        year-to-date</p>
                </div>

            </div>

            <div class="grid grid-cols-1 xl:grid-cols-12 gap-5 sm:gap-6">

                <!-- ══════════ LEFT COLUMN ══════════ -->
                <div class="xl:col-span-8 flex flex-col gap-5 sm:gap-6">

                    <!-- ── Monthly Cost Chart ── -->
                    <div class="glass-card p-4 sm:p-6 rounded-[1.25rem] sm:rounded-[1.5rem]">
                        <div class="flex items-center justify-between mb-5">
                            <div class="flex items-center gap-2.5">
                                <div
                                    class="w-7 h-7 rounded-lg bg-gray-100 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] flex items-center justify-center">
                                    <i class="fa-solid fa-chart-column text-[9px] text-gray-400 dark:text-[#555]"></i>
                                </div>
                                <span
                                    class="text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-gray-500 dark:text-[#555]">{{ $year }}
                                    Monthly Maintenance Cost</span>
                            </div>
                            <div class="flex items-center gap-4">
                                <div class="flex items-center gap-1.5">
                                    <div class="w-2 h-2 rounded-sm bg-blue-500"></div>
                                    <span
                                        class="text-[7px] sm:text-[8px] font-bold text-gray-400 dark:text-[#444] uppercase">Cost</span>
                                </div>
                            </div>
                        </div>
                        <div class="relative" style="height: 220px;">
                            <canvas id="monthlyCostChart"></canvas>
                        </div>
                    </div>

                    <!-- ══════════ TABS ══════════ -->
                    <div>
                        <div class="flex gap-1 border-b border-gray-200 dark:border-[#1e1e1e] mb-5 overflow-x-auto">
                            <button @click="activeTab = 'cpk'"
                                :class="activeTab === 'cpk' ? 'tab-active' : 'tab-inactive'"
                                class="pb-3 px-3 text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.12em] transition-all whitespace-nowrap">
                                <i class="fa-solid fa-gauge-high mr-1.5 text-[7px]"></i>Cost / Km
                            </button>
                            <button @click="activeTab = 'cost'"
                                :class="activeTab === 'cost' ? 'tab-active' : 'tab-inactive'"
                                class="pb-3 px-3 text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.12em] transition-all whitespace-nowrap">
                                <i class="fa-solid fa-table-columns mr-1.5 text-[7px]"></i>Cost Summary
                            </button>
                            <button @click="activeTab = 'schedule'"
                                :class="activeTab === 'schedule' ? 'tab-active' : 'tab-inactive'"
                                class="pb-3 px-3 text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.12em] transition-all whitespace-nowrap">
                                <i class="fa-solid fa-calendar-days mr-1.5 text-[7px]"></i>P.M. Schedule
                            </button>
                            <button @click="activeTab = 'log'"
                                :class="activeTab === 'log' ? 'tab-active' : 'tab-inactive'"
                                class="pb-3 px-3 text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.12em] transition-all whitespace-nowrap">
                                <i class="fa-solid fa-list-check mr-1.5 text-[7px]"></i>Maint. Log
                            </button>
                        </div>

                        <!-- ─────────────────────────────────── -->
                        <!-- TAB: COST PER KM SUMMARY            -->
                        <!-- ─────────────────────────────────── -->
                        <div x-show="activeTab === 'cpk'" x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 -translate-y-2"
                            x-transition:enter-end="opacity-100 translate-y-0" style="display:none;">
                            <div class="glass-card rounded-[1.25rem] sm:rounded-[1.5rem] overflow-hidden">
                                <div
                                    class="px-4 sm:px-6 py-3 border-b border-gray-200 dark:border-[#1e1e1e] bg-gray-50 dark:bg-[#111] flex justify-between items-center">
                                    <div class="flex items-center gap-2.5">
                                        <div
                                            class="w-6 h-6 rounded-md bg-blue-500/10 flex items-center justify-center">
                                            <i
                                                class="fa-solid fa-gauge-high text-[8px] text-blue-500 dark:text-blue-400"></i>
                                        </div>
                                        <span
                                            class="text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-gray-500 dark:text-[#555]">Cost
                                            Per Km — {{ $year }}</span>
                                    </div>
                                    <span
                                        class="text-[7px] text-gray-400 dark:text-[#333] uppercase tracking-widest font-bold hidden sm:inline">Scroll
                                        <i class="fa-solid fa-arrow-right text-[6px] ml-0.5"></i></span>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left whitespace-nowrap">
                                        <thead>
                                            <tr
                                                class="border-b border-gray-200 dark:border-[#1e1e1e] bg-gray-50 dark:bg-[#0a0a0a]">
                                                <th
                                                    class="px-4 sm:px-6 py-2.5 text-[7px] sm:text-[8px] font-black uppercase tracking-[0.15em] text-blue-600 dark:text-blue-500 sticky left-0 bg-gray-50 dark:bg-[#0a0a0a] z-10 text-left min-w-[160px]">
                                                    Metric</th>
                                                @foreach (range(1, 12) as $m)
                                                    <th
                                                        class="px-3 sm:px-4 py-2.5 text-[7px] sm:text-[8px] font-bold text-gray-400 dark:text-[#333] text-right w-16 sm:w-20">
                                                        {{ ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'][$m - 1] }}
                                                    </th>
                                                @endforeach
                                                <th
                                                    class="px-4 sm:px-6 py-2.5 text-[7px] sm:text-[8px] font-bold text-blue-600 dark:text-blue-400 text-right w-20 sm:w-24 border-l border-gray-200 dark:border-[#1e1e1e]">
                                                    YTD</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 dark:divide-[#1a1a1a]">
                                            <tr class="table-row">
                                                <td
                                                    class="px-4 sm:px-6 py-2.5 text-[9px] sm:text-[10px] text-gray-500 dark:text-[#555] font-bold sticky left-0 bg-white dark:bg-[#161616]">
                                                    <i
                                                        class="fa-solid fa-flag-checkered text-gray-300 dark:text-[#333] text-[8px] mr-2 w-3.5 text-center"></i>Start
                                                    Odometer
                                                </td>
                                                @foreach (range(1, 12) as $m)
                                                    <td
                                                        class="px-3 sm:px-4 py-2.5 font-mono text-[9px] sm:text-[10px] text-right @if ($monthlyStartOdo[$m] !== null) text-gray-500 dark:text-[#555] @else text-gray-200 dark:text-[#222] @endif">
                                                        {{ $monthlyStartOdo[$m] !== null ? number_format($monthlyStartOdo[$m]) : '—' }}
                                                    </td>
                                                @endforeach
                                                <td
                                                    class="px-4 sm:px-6 py-2.5 font-mono text-[9px] sm:text-[10px] text-right text-gray-600 dark:text-[#666] border-l border-gray-200 dark:border-[#1e1e1e]">
                                                    {{ $yearStartOdo !== null ? number_format($yearStartOdo) : '—' }}
                                                </td>
                                            </tr>
                                            <tr class="table-row">
                                                <td
                                                    class="px-4 sm:px-6 py-2.5 text-[9px] sm:text-[10px] text-gray-500 dark:text-[#555] font-bold sticky left-0 bg-white dark:bg-[#161616]">
                                                    <i
                                                        class="fa-solid fa-flag text-gray-300 dark:text-[#333] text-[8px] mr-2 w-3.5 text-center"></i>End
                                                    Odometer
                                                </td>
                                                @foreach (range(1, 12) as $m)
                                                    <td
                                                        class="px-3 sm:px-4 py-2.5 font-mono text-[9px] sm:text-[10px] text-right @if ($monthlyEndOdo[$m] !== null) text-gray-600 dark:text-[#666] @else text-gray-200 dark:text-[#222] @endif">
                                                        {{ $monthlyEndOdo[$m] !== null ? number_format($monthlyEndOdo[$m]) : '—' }}
                                                    </td>
                                                @endforeach
                                                <td
                                                    class="px-4 sm:px-6 py-2.5 font-mono text-[9px] sm:text-[10px] text-right text-gray-600 dark:text-[#666] border-l border-gray-200 dark:border-[#1e1e1e]">
                                                    {{ $yearEndOdo ? number_format($yearEndOdo) : '—' }}</td>
                                            </tr>
                                            <tr class="table-row">
                                                <td
                                                    class="px-4 sm:px-6 py-2.5 text-[9px] sm:text-[10px] text-gray-700 dark:text-[#888] font-bold sticky left-0 bg-white dark:bg-[#161616]">
                                                    <i
                                                        class="fa-solid fa-road text-gray-400 dark:text-[#444] text-[8px] mr-2 w-3.5 text-center"></i>Monthly
                                                    Km
                                                </td>
                                                @foreach (range(1, 12) as $m)
                                                    <td
                                                        class="px-3 sm:px-4 py-2.5 font-mono text-[9px] sm:text-[10px] text-right @if ($monthlyKm[$m] > 0) text-gray-700 dark:text-[#888] @else text-gray-200 dark:text-[#222] @endif">
                                                        {{ $monthlyKm[$m] > 0 ? number_format($monthlyKm[$m]) : '—' }}
                                                    </td>
                                                @endforeach
                                                <td
                                                    class="px-4 sm:px-6 py-2.5 font-mono text-[9px] sm:text-[10px] text-right font-bold text-gray-900 dark:text-[#ccc] border-l border-gray-200 dark:border-[#1e1e1e]">
                                                    {{ number_format($annualKm) }}</td>
                                            </tr>
                                            <tr class="table-row">
                                                <td
                                                    class="px-4 sm:px-6 py-2.5 text-[9px] sm:text-[10px] text-gray-700 dark:text-[#888] font-bold sticky left-0 bg-white dark:bg-[#161616]">
                                                    <i
                                                        class="fa-solid fa-gauge text-emerald-500/50 text-[8px] mr-2 w-3.5 text-center"></i>Cost
                                                    / Km
                                                </td>
                                                @foreach (range(1, 12) as $m)
                                                    <td
                                                        class="px-3 sm:px-4 py-2.5 font-mono text-[9px] sm:text-[10px] text-right @if ($monthlyCpk[$m] !== null) text-emerald-600 dark:text-emerald-400/80 @else text-gray-200 dark:text-[#222] @endif">
                                                        {{ $monthlyCpk[$m] !== null ? '₱' . number_format($monthlyCpk[$m], 2) : '—' }}
                                                    </td>
                                                @endforeach
                                                <td
                                                    class="px-4 sm:px-6 py-2.5 font-mono text-[9px] sm:text-[10px] text-right font-bold text-emerald-600 dark:text-emerald-400 border-l border-gray-200 dark:border-[#1e1e1e]">
                                                    ₱{{ number_format($costPerKm, 2) }}</td>
                                            </tr>
                                            <tr
                                                class="bg-blue-50 dark:bg-blue-500/[0.03] border-t-2 border-blue-500/20">
                                                <td
                                                    class="px-4 sm:px-6 py-3 text-[9px] sm:text-[10px] text-blue-600 dark:text-blue-400 font-bold sticky left-0 bg-blue-50 dark:bg-blue-500/[0.03]">
                                                    <i class="fa-solid fa-coins text-[9px] mr-2"></i>Total Service Cost
                                                </td>
                                                @foreach (range(1, 12) as $m)
                                                    <td
                                                        class="px-3 sm:px-4 py-3 font-mono text-[9px] sm:text-[10px] text-right font-bold @if ($monthlyTotals[$m] > 0) text-blue-600 dark:text-blue-400 @else text-blue-100 dark:text-[#1a1a1a] @endif">
                                                        ₱{{ number_format($monthlyTotals[$m], 2) }}</td>
                                                @endforeach
                                                <td
                                                    class="px-4 sm:px-6 py-3 font-mono text-[9px] sm:text-[10px] text-right font-black text-blue-600 dark:text-blue-400 border-l border-gray-200 dark:border-[#1e1e1e]">
                                                    ₱{{ number_format($ytdTotal, 2) }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div
                                    class="px-4 sm:px-6 py-2.5 border-t border-gray-200 dark:border-[#1e1e1e] bg-gray-50 dark:bg-[#111] flex flex-col sm:flex-row justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="text-[7px] text-gray-400 dark:text-[#333] uppercase font-bold tracking-widest">Annual
                                            Km</span>
                                        <span
                                            class="text-[10px] text-gray-900 dark:text-white font-black font-mono">{{ number_format($annualKm) }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="text-[7px] text-gray-400 dark:text-[#333] uppercase font-bold tracking-widest">Avg
                                            Cost/Km</span>
                                        <span
                                            class="text-[10px] text-emerald-600 dark:text-emerald-400 font-black font-mono">₱{{ number_format($costPerKm, 2) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ─────────────────────────────────── -->
                        <!-- TAB: COST SUMMARY                   -->
                        <!-- ─────────────────────────────────── -->
                        <div x-show="activeTab === 'cost'" x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 -translate-y-2"
                            x-transition:enter-end="opacity-100 translate-y-0" style="display:none;">
                            <div class="glass-card rounded-[1.25rem] sm:rounded-[1.5rem] overflow-hidden">
                                <div
                                    class="px-4 sm:px-6 py-3 border-b border-gray-200 dark:border-[#1e1e1e] bg-gray-50 dark:bg-[#111] flex justify-between items-center">
                                    <div class="flex items-center gap-2.5">
                                        <div
                                            class="w-6 h-6 rounded-md bg-blue-500/10 flex items-center justify-center">
                                            <i
                                                class="fa-solid fa-table-columns text-[8px] text-blue-500 dark:text-blue-400"></i>
                                        </div>
                                        <span
                                            class="text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-gray-500 dark:text-[#555]">Maintenance
                                            Cost — {{ $year }}</span>
                                    </div>
                                    <span
                                        class="text-[7px] text-gray-400 dark:text-[#333] uppercase tracking-widest font-bold hidden sm:inline">Scroll
                                        <i class="fa-solid fa-arrow-right text-[6px] ml-0.5"></i></span>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left whitespace-nowrap">
                                        <thead>
                                            <tr
                                                class="border-b border-gray-200 dark:border-[#1e1e1e] bg-gray-50 dark:bg-[#0a0a0a]">
                                                <th
                                                    class="px-4 sm:px-6 py-2.5 text-[7px] sm:text-[8px] font-black uppercase tracking-[0.15em] text-blue-600 dark:text-blue-500 sticky left-0 bg-gray-50 dark:bg-[#0a0a0a] z-10 text-left min-w-[180px]">
                                                    Category / Item</th>
                                                @foreach (range(1, 12) as $m)
                                                    <th
                                                        class="px-3 sm:px-4 py-2.5 text-[7px] sm:text-[8px] font-bold text-gray-400 dark:text-[#333] text-right w-16 sm:w-20">
                                                        {{ ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'][$m - 1] }}
                                                    </th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 dark:divide-[#1a1a1a]">
                                            @forelse($costSummary as $taskName => $months)
                                                <tr class="table-row">
                                                    <td
                                                        class="px-4 sm:px-6 py-2.5 text-[9px] sm:text-[10px] text-gray-700 dark:text-[#888] font-bold sticky left-0 bg-white dark:bg-[#161616] truncate max-w-[220px]">
                                                        {{ $taskName }}</td>
                                                    @foreach (range(1, 12) as $m)
                                                        <td
                                                            class="px-3 sm:px-4 py-2.5 font-mono text-[9px] sm:text-[10px] text-right @if ($months[$m] > 0) text-gray-600 dark:text-[#666] @else text-gray-200 dark:text-[#222] @endif">
                                                            {{ $months[$m] > 0 ? '₱' . number_format($months[$m], 2) : '—' }}
                                                        </td>
                                                    @endforeach
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="13" class="px-6 py-12 text-center">
                                                        <div class="flex flex-col items-center">
                                                            <div
                                                                class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] flex items-center justify-center mb-2.5">
                                                                <i
                                                                    class="fa-solid fa-coins text-sm text-gray-300 dark:text-[#333]"></i>
                                                            </div>
                                                            <p
                                                                class="text-[10px] text-gray-400 dark:text-[#444] font-medium">
                                                                No maintenance records for {{ $year }}</p>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforelse
                                            @if ($costSummary->isNotEmpty())
                                                <tr
                                                    class="bg-blue-50 dark:bg-blue-500/[0.03] border-t-2 border-blue-500/20">
                                                    <td
                                                        class="px-4 sm:px-6 py-3 text-[9px] sm:text-[10px] text-blue-600 dark:text-blue-400 font-bold sticky left-0 bg-blue-50 dark:bg-blue-500/[0.03]">
                                                        <i class="fa-solid fa-calculator text-[9px] mr-2"></i>Total
                                                        Monthly
                                                    </td>
                                                    @foreach (range(1, 12) as $m)
                                                        <td
                                                            class="px-3 sm:px-4 py-3 font-mono text-[9px] sm:text-[10px] text-right font-bold @if ($monthlyTotals[$m] > 0) text-blue-600 dark:text-blue-400 @else text-blue-100 dark:text-[#1a1a1a] @endif">
                                                            ₱{{ number_format($monthlyTotals[$m], 2) }}</td>
                                                    @endforeach
                                                </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                                <div
                                    class="px-4 sm:px-6 py-2.5 border-t border-gray-200 dark:border-[#1e1e1e] bg-gray-50 dark:bg-[#111] flex justify-between items-center">
                                    <span
                                        class="text-[7px] text-gray-400 dark:text-[#333] uppercase font-bold tracking-widest">YTD
                                        Total</span>
                                    <span
                                        class="text-[10px] text-gray-900 dark:text-white font-black font-mono">₱{{ number_format($ytdTotal, 2) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- ─────────────────────────────────── -->
                        <!-- TAB: PREVENTIVE MAINTENANCE SCHEDULE-->
                        <!-- ─────────────────────────────────── -->
                        <div x-show="activeTab === 'schedule'" x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 -translate-y-2"
                            x-transition:enter-end="opacity-100 translate-y-0" style="display:none;">
                            <div class="glass-card rounded-[1.25rem] sm:rounded-[1.5rem] overflow-hidden">
                                <div
                                    class="px-4 sm:px-6 py-3 border-b border-gray-200 dark:border-[#1e1e1e] bg-gray-50 dark:bg-[#111]">
                                    <div class="flex items-center gap-2.5">
                                        <div
                                            class="w-6 h-6 rounded-md bg-amber-500/10 flex items-center justify-center">
                                            <i
                                                class="fa-solid fa-calendar-days text-[8px] text-amber-500 dark:text-amber-400"></i>
                                        </div>
                                        <span
                                            class="text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-gray-500 dark:text-[#555]">Preventive
                                            Maintenance Schedule</span>
                                    </div>
                                </div>
                                <div class="p-4 sm:p-6 space-y-4">

                                    <div
                                        class="p-4 rounded-xl bg-gray-50 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e]">
                                        <div class="flex items-center gap-2.5 mb-3">
                                            <div
                                                class="w-5 h-5 rounded-md bg-blue-500/15 border border-blue-500/25 flex items-center justify-center">
                                                <div class="w-1.5 h-1.5 rounded-full bg-blue-500 dark:bg-blue-400">
                                                </div>
                                            </div>
                                            <span class="text-[10px] font-black text-gray-900 dark:text-white">10,000
                                                Km</span>
                                            <span
                                                class="text-[7px] font-bold text-blue-600 dark:text-blue-400 bg-blue-500/10 px-1.5 py-0.5 rounded-md border border-blue-500/15 uppercase">Every
                                                10K</span>
                                        </div>
                                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-1.5">
                                            <div
                                                class="flex items-center gap-2 px-2.5 py-2 rounded-lg bg-white dark:bg-[#161616] border border-gray-200 dark:border-[#1e1e1e]">
                                                <i
                                                    class="fa-solid fa-rotate text-gray-300 dark:text-[#333] text-[8px] w-3.5 text-center"></i><span
                                                    class="text-[9px] text-gray-600 dark:text-[#666] font-bold">Tire
                                                    Rotation</span>
                                            </div>
                                            <div
                                                class="flex items-center gap-2 px-2.5 py-2 rounded-lg bg-white dark:bg-[#161616] border border-gray-200 dark:border-[#1e1e1e]">
                                                <i
                                                    class="fa-solid fa-oil-can text-gray-300 dark:text-[#333] text-[8px] w-3.5 text-center"></i><span
                                                    class="text-[9px] text-gray-600 dark:text-[#666] font-bold">Oil &
                                                    Filter</span>
                                            </div>
                                            <div
                                                class="flex items-center gap-2 px-2.5 py-2 rounded-lg bg-white dark:bg-[#161616] border border-gray-200 dark:border-[#1e1e1e]">
                                                <i
                                                    class="fa-solid fa-wind text-gray-300 dark:text-[#333] text-[8px] w-3.5 text-center"></i><span
                                                    class="text-[9px] text-gray-600 dark:text-[#666] font-bold">Air
                                                    Filter Check</span>
                                            </div>
                                            <div
                                                class="flex items-center gap-2 px-2.5 py-2 rounded-lg bg-white dark:bg-[#161616] border border-gray-200 dark:border-[#1e1e1e]">
                                                <i
                                                    class="fa-solid fa-droplet text-gray-300 dark:text-[#333] text-[8px] w-3.5 text-center"></i><span
                                                    class="text-[9px] text-gray-600 dark:text-[#666] font-bold">Fluid
                                                    Levels</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div
                                        class="p-4 rounded-xl bg-gray-50 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e]">
                                        <div class="flex items-center gap-2.5 mb-3">
                                            <div
                                                class="w-5 h-5 rounded-md bg-amber-500/15 border border-amber-500/25 flex items-center justify-center">
                                                <div class="w-1.5 h-1.5 rounded-full bg-amber-500 dark:bg-amber-400">
                                                </div>
                                            </div>
                                            <span class="text-[10px] font-black text-gray-900 dark:text-white">20,000
                                                Km</span>
                                            <span
                                                class="text-[7px] font-bold text-amber-600 dark:text-amber-400 bg-amber-500/10 px-1.5 py-0.5 rounded-md border border-amber-500/15 uppercase">Every
                                                20K</span>
                                        </div>
                                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-1.5">
                                            <div
                                                class="flex items-center gap-2 px-2.5 py-2 rounded-lg bg-white dark:bg-[#161616] border border-gray-200 dark:border-[#1e1e1e]">
                                                <i
                                                    class="fa-solid fa-circle-dot text-gray-300 dark:text-[#333] text-[8px] w-3.5 text-center"></i><span
                                                    class="text-[9px] text-gray-600 dark:text-[#666] font-bold">Tire
                                                    Replacement</span>
                                            </div>
                                            <div
                                                class="flex items-center gap-2 px-2.5 py-2 rounded-lg bg-white dark:bg-[#161616] border border-gray-200 dark:border-[#1e1e1e]">
                                                <i
                                                    class="fa-solid fa-oil-can text-gray-300 dark:text-[#333] text-[8px] w-3.5 text-center"></i><span
                                                    class="text-[9px] text-gray-600 dark:text-[#666] font-bold">Oil &
                                                    Filter</span>
                                            </div>
                                            <div
                                                class="flex items-center gap-2 px-2.5 py-2 rounded-lg bg-white dark:bg-[#161616] border border-gray-200 dark:border-[#1e1e1e]">
                                                <i
                                                    class="fa-solid fa-wind text-gray-300 dark:text-[#333] text-[8px] w-3.5 text-center"></i><span
                                                    class="text-[9px] text-gray-600 dark:text-[#666] font-bold">Air
                                                    Filter Replace</span>
                                            </div>
                                            <div
                                                class="flex items-center gap-2 px-2.5 py-2 rounded-lg bg-white dark:bg-[#161616] border border-gray-200 dark:border-[#1e1e1e]">
                                                <i
                                                    class="fa-solid fa-droplet text-gray-300 dark:text-[#333] text-[8px] w-3.5 text-center"></i><span
                                                    class="text-[9px] text-gray-600 dark:text-[#666] font-bold">Coolant
                                                    Flush</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div
                                        class="p-4 rounded-xl bg-gray-50 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e]">
                                        <div class="flex items-center gap-2.5 mb-3">
                                            <div
                                                class="w-5 h-5 rounded-md bg-blue-500/15 border border-blue-500/25 flex items-center justify-center">
                                                <div class="w-1.5 h-1.5 rounded-full bg-blue-500 dark:bg-blue-400">
                                                </div>
                                            </div>
                                            <span class="text-[10px] font-black text-gray-900 dark:text-white">30,000
                                                Km</span>
                                            <span
                                                class="text-[7px] font-bold text-blue-600 dark:text-blue-400 bg-blue-500/10 px-1.5 py-0.5 rounded-md border border-blue-500/15 uppercase">Every
                                                30K</span>
                                        </div>
                                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-1.5">
                                            <div
                                                class="flex items-center gap-2 px-2.5 py-2 rounded-lg bg-white dark:bg-[#161616] border border-gray-200 dark:border-[#1e1e1e]">
                                                <i
                                                    class="fa-solid fa-rotate text-gray-300 dark:text-[#333] text-[8px] w-3.5 text-center"></i><span
                                                    class="text-[9px] text-gray-600 dark:text-[#666] font-bold">Tire
                                                    Rotation</span>
                                            </div>
                                            <div
                                                class="flex items-center gap-2 px-2.5 py-2 rounded-lg bg-white dark:bg-[#161616] border border-gray-200 dark:border-[#1e1e1e]">
                                                <i
                                                    class="fa-solid fa-oil-can text-gray-300 dark:text-[#333] text-[8px] w-3.5 text-center"></i><span
                                                    class="text-[9px] text-gray-600 dark:text-[#666] font-bold">Oil &
                                                    Filter</span>
                                            </div>
                                            <div
                                                class="flex items-center gap-2 px-2.5 py-2 rounded-lg bg-white dark:bg-[#161616] border border-gray-200 dark:border-[#1e1e1e]">
                                                <i
                                                    class="fa-solid fa-gears text-gray-300 dark:text-[#333] text-[8px] w-3.5 text-center"></i><span
                                                    class="text-[9px] text-gray-600 dark:text-[#666] font-bold">Transmission
                                                    Fluid</span>
                                            </div>
                                            <div
                                                class="flex items-center gap-2 px-2.5 py-2 rounded-lg bg-white dark:bg-[#161616] border border-gray-200 dark:border-[#1e1e1e]">
                                                <i
                                                    class="fa-solid fa-car text-gray-300 dark:text-[#333] text-[8px] w-3.5 text-center"></i><span
                                                    class="text-[9px] text-gray-600 dark:text-[#666] font-bold">Brake
                                                    Inspection</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="p-4 rounded-xl bg-gray-50 dark:bg-[#111] border border-rose-500/10">
                                        <div class="flex items-center gap-2.5 mb-3">
                                            <div
                                                class="w-5 h-5 rounded-md bg-rose-500/15 border border-rose-500/25 flex items-center justify-center">
                                                <div class="w-1.5 h-1.5 rounded-full bg-rose-500 dark:bg-rose-400">
                                                </div>
                                            </div>
                                            <span class="text-[10px] font-black text-gray-900 dark:text-white">50,000
                                                Km</span>
                                            <span
                                                class="text-[7px] font-bold text-rose-500 dark:text-rose-400 bg-rose-500/10 px-1.5 py-0.5 rounded-md border border-rose-500/15 uppercase">Major
                                                Service</span>
                                        </div>
                                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-1.5">
                                            <div
                                                class="flex items-center gap-2 px-2.5 py-2 rounded-lg bg-white dark:bg-[#161616] border border-gray-200 dark:border-[#1e1e1e]">
                                                <i
                                                    class="fa-solid fa-circle-dot text-gray-300 dark:text-[#333] text-[8px] w-3.5 text-center"></i><span
                                                    class="text-[9px] text-gray-600 dark:text-[#666] font-bold">Tire
                                                    Replacement</span>
                                            </div>
                                            <div
                                                class="flex items-center gap-2 px-2.5 py-2 rounded-lg bg-white dark:bg-[#161616] border border-gray-200 dark:border-[#1e1e1e]">
                                                <i
                                                    class="fa-solid fa-oil-can text-gray-300 dark:text-[#333] text-[8px] w-3.5 text-center"></i><span
                                                    class="text-[9px] text-gray-600 dark:text-[#666] font-bold">Oil &
                                                    Filter</span>
                                            </div>
                                            <div
                                                class="flex items-center gap-2 px-2.5 py-2 rounded-lg bg-white dark:bg-[#161616] border border-gray-200 dark:border-[#1e1e1e]">
                                                <i
                                                    class="fa-solid fa-car text-gray-300 dark:text-[#333] text-[8px] w-3.5 text-center"></i><span
                                                    class="text-[9px] text-gray-600 dark:text-[#666] font-bold">Brake
                                                    Pads</span>
                                            </div>
                                            <div
                                                class="flex items-center gap-2 px-2.5 py-2 rounded-lg bg-white dark:bg-[#161616] border border-gray-200 dark:border-[#1e1e1e]">
                                                <i
                                                    class="fa-solid fa-bolt text-gray-300 dark:text-[#333] text-[8px] w-3.5 text-center"></i><span
                                                    class="text-[9px] text-gray-600 dark:text-[#666] font-bold">Spark
                                                    Plugs</span>
                                            </div>
                                            <div
                                                class="flex items-center gap-2 px-2.5 py-2 rounded-lg bg-white dark:bg-[#161616] border border-gray-200 dark:border-[#1e1e1e]">
                                                <i
                                                    class="fa-solid fa-gears text-gray-300 dark:text-[#333] text-[8px] w-3.5 text-center"></i><span
                                                    class="text-[9px] text-gray-600 dark:text-[#666] font-bold">Transmission
                                                    Svc</span>
                                            </div>
                                            <div
                                                class="flex items-center gap-2 px-2.5 py-2 rounded-lg bg-white dark:bg-[#161616] border border-gray-200 dark:border-[#1e1e1e]">
                                                <i
                                                    class="fa-solid fa-droplet text-gray-300 dark:text-[#333] text-[8px] w-3.5 text-center"></i><span
                                                    class="text-[9px] text-gray-600 dark:text-[#666] font-bold">Coolant
                                                    Flush</span>
                                            </div>
                                            <div
                                                class="flex items-center gap-2 px-2.5 py-2 rounded-lg bg-white dark:bg-[#161616] border border-gray-200 dark:border-[#1e1e1e]">
                                                <i
                                                    class="fa-solid fa-wind text-gray-300 dark:text-[#333] text-[8px] w-3.5 text-center"></i><span
                                                    class="text-[9px] text-gray-600 dark:text-[#666] font-bold">Air
                                                    Filter</span>
                                            </div>
                                            <div
                                                class="flex items-center gap-2 px-2.5 py-2 rounded-lg bg-white dark:bg-[#161616] border border-gray-200 dark:border-[#1e1e1e]">
                                                <i
                                                    class="fa-solid fa-magnifying-glass text-gray-300 dark:text-[#333] text-[8px] w-3.5 text-center"></i><span
                                                    class="text-[9px] text-gray-600 dark:text-[#666] font-bold">Full
                                                    Inspection</span>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <!-- ─────────────────────────────────── -->
                        <!-- TAB: VEHICLE MAINTENANCE LOG        -->
                        <!-- ─────────────────────────────────── -->
                        <div x-show="activeTab === 'log'" x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 -translate-y-2"
                            x-transition:enter-end="opacity-100 translate-y-0" style="display:none;">
                            <div class="glass-card rounded-[1.25rem] sm:rounded-[1.5rem] overflow-hidden">
                                <div
                                    class="px-4 sm:px-6 py-3 border-b border-gray-200 dark:border-[#1e1e1e] bg-gray-50 dark:bg-[#111] flex justify-between items-center">
                                    <div class="flex items-center gap-2.5">
                                        <div
                                            class="w-6 h-6 rounded-md bg-emerald-500/10 flex items-center justify-center">
                                            <i
                                                class="fa-solid fa-list-check text-[8px] text-emerald-500 dark:text-emerald-400"></i>
                                        </div>
                                        <span
                                            class="text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-gray-500 dark:text-[#555]">Maintenance
                                            Log</span>
                                    </div>
                                    <span
                                        class="text-[7px] text-gray-400 dark:text-[#333] uppercase tracking-widest font-bold">{{ $allLogs->count() }}
                                        {{ $allLogs->count() === 1 ? 'entry' : 'entries' }}</span>
                                </div>
                                <div class="overflow-x-auto -mx-2 px-2 pb-2">
                                    <table class="w-full text-left min-w-[600px]">
                                        <thead>
                                            <tr
                                                class="text-[7px] sm:text-[8px] uppercase tracking-[0.15em] text-gray-400 dark:text-[#444] border-b border-gray-200 dark:border-[#1e1e1e]">
                                                <th class="px-4 sm:px-6 py-2.5 font-bold">Task</th>
                                                <th class="px-4 sm:px-6 py-2.5 font-bold">Date</th>
                                                <th class="px-4 sm:px-6 py-2.5 font-bold">Odometer</th>
                                                <th class="px-4 sm:px-6 py-2.5 font-bold text-right">Cost</th>
                                                <th class="px-4 sm:px-6 py-2.5 font-bold">Remarks</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 dark:divide-[#1a1a1a]">
                                            @forelse($allLogs as $log)
                                                <tr class="table-row">
                                                    <td class="px-4 sm:px-6 py-3">
                                                        <div class="flex items-center gap-3">
                                                            <div
                                                                class="w-8 h-8 rounded-lg bg-emerald-500/10 border border-emerald-500/15 flex items-center justify-center shrink-0">
                                                                <i
                                                                    class="fa-solid fa-check text-[8px] text-emerald-500 dark:text-emerald-400"></i>
                                                            </div>
                                                            <p
                                                                class="text-[10px] sm:text-[11px] font-bold text-gray-700 dark:text-[#ccc] truncate max-w-[200px]">
                                                                {{ $log['task_name'] }}</p>
                                                        </div>
                                                    </td>
                                                    <td class="px-4 sm:px-6 py-3">
                                                        <span
                                                            class="text-[10px] sm:text-[11px] font-bold text-gray-500 dark:text-[#888]">{{ $log['service_date'] ? \Carbon\Carbon::parse($log['service_date'])->format('M d, Y') : '—' }}</span>
                                                    </td>
                                                    <td class="px-4 sm:px-6 py-3">
                                                        <span
                                                            class="font-mono text-[10px] sm:text-[11px] font-bold text-gray-500 dark:text-[#666]">{{ number_format($log['mileage']) }}
                                                            km</span>
                                                    </td>
                                                    <td class="px-4 sm:px-6 py-3 text-right">
                                                        <span
                                                            class="text-[10px] sm:text-[11px] font-bold text-blue-600 dark:text-blue-400">₱{{ number_format($log['cost'], 2) }}</span>
                                                    </td>
                                                    <td class="px-4 sm:px-6 py-3">
                                                        <span
                                                            class="text-[9px] sm:text-[10px] text-gray-400 dark:text-[#444] truncate block max-w-[180px]"
                                                            title="{{ $log['remarks'] ?? '' }}">{{ $log['remarks'] ?? '—' }}</span>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="py-10 sm:py-12">
                                                        <div class="flex flex-col items-center justify-center">
                                                            <div
                                                                class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] flex items-center justify-center mb-2.5">
                                                                <i
                                                                    class="fa-solid fa-clipboard-list text-sm text-gray-300 dark:text-[#333]"></i>
                                                            </div>
                                                            <p
                                                                class="text-[10px] sm:text-[11px] text-gray-400 dark:text-[#444] font-medium">
                                                                No maintenance logs recorded yet</p>
                                                            <p
                                                                class="text-[8px] text-gray-300 dark:text-[#333] mt-0.5">
                                                                Logs will appear here once preventive maintenance is
                                                                logged</p>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- ══════════ RIGHT COLUMN (DONUT) ══════════ -->
                <div class="xl:col-span-4">
                    <div class="glass-card p-4 sm:p-6 rounded-[1.25rem] sm:rounded-[1.5rem] sticky top-8">
                        <div class="flex items-center gap-2.5 mb-5">
                            <div
                                class="w-7 h-7 rounded-lg bg-gray-100 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] flex items-center justify-center">
                                <i class="fa-solid fa-chart-pie text-[9px] text-gray-400 dark:text-[#555]"></i>
                            </div>
                            <span
                                class="text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-gray-500 dark:text-[#555]">Cost
                                Breakdown</span>
                        </div>

                        @if ($donutTotal > 0)
                            <div class="relative mx-auto" style="max-width: 200px;">
                                <canvas id="donutChart"></canvas>
                                <div class="donut-center text-center">
                                    <p
                                        class="text-[7px] font-bold uppercase tracking-widest text-gray-400 dark:text-[#444]">
                                        Total</p>
                                    <p class="text-sm font-black text-gray-900 dark:text-white">
                                        ₱{{ number_format($donutTotal, 2) }}</p>
                                </div>
                            </div>

                            <div class="mt-5 space-y-2">
                                @foreach ($donutLabels as $i => $label)
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <div class="w-2 h-2 rounded-sm"
                                                style="background: {{ ['rgba(59,130,246,0.7)', 'rgba(16,185,129,0.7)', 'rgba(245,158,11,0.7)', 'rgba(239,68,68,0.7)', 'rgba(168,85,247,0.7)', 'rgba(236,72,153,0.7)', 'rgba(20,184,166,0.7)', 'rgba(249,115,22,0.7)'][$i % 8] }}">
                                            </div>
                                            <span
                                                class="text-[9px] font-bold text-gray-600 dark:text-[#666] truncate max-w-[140px]">{{ $label }}</span>
                                        </div>
                                        <span
                                            class="text-[9px] font-bold text-gray-500 dark:text-[#555] font-mono">₱{{ number_format($donutData[$i], 2) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="flex flex-col items-center justify-center py-10">
                                <div
                                    class="w-12 h-12 rounded-xl bg-gray-100 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] flex items-center justify-center mb-3">
                                    <i class="fa-solid fa-chart-pie text-lg text-gray-300 dark:text-[#222]"></i>
                                </div>
                                <p class="text-[10px] text-gray-400 dark:text-[#444] font-medium">No cost data for
                                    {{ $year }}</p>
                            </div>
                        @endif
                    </div>
                </div>

            </div>

        @endif

    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const isDark = document.documentElement.classList.contains('dark');
            const gridColor = isDark ? 'rgba(255,255,255,0.04)' : 'rgba(0,0,0,0.06)';
            const tickColor = isDark ? '#444' : '#94a3b8';
            const tickFont = {
                family: 'Inter',
                size: 9,
                weight: '600'
            };

            // ── Bar Chart ──
            const barCtx = document.getElementById('monthlyCostChart');
            if (barCtx) {
                new Chart(barCtx, {
                    type: 'bar',
                    data: {
                        labels: @json($chartMonthLabels),
                        datasets: [{
                            data: @json($chartMonthlyData),
                            backgroundColor: isDark ? 'rgba(59,130,246,0.5)' :
                                'rgba(59,130,246,0.65)',
                            hoverBackgroundColor: 'rgba(59,130,246,0.8)',
                            borderRadius: 4,
                            borderSkipped: false,
                            maxBarThickness: 32,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: isDark ? '#1a1a1a' : '#ffffff',
                                titleColor: isDark ? '#fff' : '#1e293b',
                                bodyColor: isDark ? '#aaa' : '#64748b',
                                borderColor: isDark ? '#2a2a2a' : '#e2e8f0',
                                borderWidth: 1,
                                padding: 10,
                                cornerRadius: 8,
                                titleFont: {
                                    family: 'Inter',
                                    size: 10,
                                    weight: '700'
                                },
                                bodyFont: {
                                    family: 'Inter',
                                    size: 10,
                                    weight: '500'
                                },
                                callbacks: {
                                    label: function(ctx) {
                                        return '  ₱' + ctx.parsed.y.toLocaleString(undefined, {
                                            minimumFractionDigits: 2
                                        });
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                ticks: {
                                    color: tickColor,
                                    font: tickFont
                                },
                                grid: {
                                    display: false
                                },
                                border: {
                                    display: false
                                }
                            },
                            y: {
                                ticks: {
                                    color: tickColor,
                                    font: tickFont,
                                    callback: v => '₱' + (v >= 1000 ? (v / 1000).toFixed(0) + 'k' : v)
                                },
                                grid: {
                                    color: gridColor
                                },
                                border: {
                                    display: false
                                },
                                beginAtZero: true
                            }
                        }
                    }
                });
            }

            // ── Donut Chart ──
            const donutCtx = document.getElementById('donutChart');
            if (donutCtx) {
                new Chart(donutCtx, {
                    type: 'doughnut',
                    data: {
                        labels: @json($donutLabels),
                        datasets: [{
                            data: @json($donutData),
                            backgroundColor: [
                                'rgba(59,130,246,0.7)', 'rgba(16,185,129,0.7)',
                                'rgba(245,158,11,0.7)',
                                'rgba(239,68,68,0.7)', 'rgba(168,85,247,0.7)',
                                'rgba(236,72,153,0.7)',
                                'rgba(20,184,166,0.7)', 'rgba(249,115,22,0.7)'
                            ],
                            borderWidth: 0,
                            hoverOffset: 4,
                        }]
                    },
                    options: {
                        responsive: true,
                        cutout: '72%',
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: isDark ? '#1a1a1a' : '#ffffff',
                                titleColor: isDark ? '#fff' : '#1e293b',
                                bodyColor: isDark ? '#aaa' : '#64748b',
                                borderColor: isDark ? '#2a2a2a' : '#e2e8f0',
                                borderWidth: 1,
                                padding: 10,
                                cornerRadius: 8,
                                titleFont: {
                                    family: 'Inter',
                                    size: 10,
                                    weight: '700'
                                },
                                bodyFont: {
                                    family: 'Inter',
                                    size: 10,
                                    weight: '500'
                                },
                                callbacks: {
                                    label: function(ctx) {
                                        const pct = ((ctx.parsed / @json($donutTotal)) * 100)
                                            .toFixed(1);
                                        return '  ₱' + ctx.parsed.toLocaleString(undefined, {
                                            minimumFractionDigits: 2
                                        }) + '  (' + pct + '%)';
                                    }
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
