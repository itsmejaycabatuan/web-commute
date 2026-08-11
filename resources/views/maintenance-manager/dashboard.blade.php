<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartCommute | Maintenance Dashboard</title>
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
        [x-cloak] { display: none !important; }
        .glass-panel { background: #111111 !important; border: 1px solid #1e1e1e; box-shadow: 0 4px 24px rgba(0,0,0,0.6); }
        .glass-card { background: #161616; border: 1px solid #222222; box-shadow: 0 4px 24px rgba(0,0,0,0.5); }
        .sidebar-transition { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        .table-row { transition: all 0.2s ease; }
        .table-row:hover { background: #1a1a1a; }
        .tab-active { color: #fff; border-bottom: 2px solid #3b82f6; }
        .tab-inactive { color: #333; border-bottom: 2px solid transparent; }
        .tab-inactive:hover { color: #666; }

        ::-webkit-scrollbar { width: 3px; height: 3px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #333; border-radius: 10px; }

        .donut-center {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            pointer-events: none;
        }

        select {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='rgba(255,255,255,0.3)' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
        }
    </style>
</head>

<script>
function summaryApp() {
    return {
        open: false,
        activeTab: 'cpk',
        showLogoutModal: false,
    };
}
</script>

<script>
    document.getElementById('fleet-selector')?.addEventListener('change', function () {
        const url = new URL(window.location.href);
        url.searchParams.set('fleet_id', this.value);
        window.location.href = url.toString();
    });
</script>

<body class="antialiased text-white" x-data="summaryApp()" x-cloak>

    @include('components.flash')
    @include('maintenance-manager.layout.sidebar')

    <main :class="open ? 'md:ml-72' : 'md:ml-20'" class="sidebar-transition pt-8 pr-4 sm:pr-8 pb-8 pl-4 sm:pl-8 min-h-screen mb-12">

        @php
            // Chart data: monthly totals for bar chart
            $chartMonthLabels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            $chartMonthlyData = [];
            foreach (range(1, 12) as $m) {
                $chartMonthlyData[] = round($monthlyTotals[$m] ?? 0, 2);
            }

            // Donut data: cost by category
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

        @if(!$fleet)

        <!-- ── Empty State ── -->
        <div class="flex flex-col items-center justify-center py-32">
            <div class="w-14 h-14 rounded-2xl bg-[#111] border border-[#1e1e1e] flex items-center justify-center mb-4">
                <i class="fa-solid fa-car text-[#333] text-xl"></i>
            </div>
            <p class="text-[#444] text-[11px] font-bold mb-1">No fleet entries yet.</p>
            <p class="text-[#333] text-[10px]">Add a fleet entry first to view maintenance summaries.</p>
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
                        <h2 class="text-sm font-bold text-white truncate">Maintenance Manager</h2>
                        <p class="text-[10px] text-[#555] truncate">{{ Auth::user()->email }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 pt-3.5 mt-3.5 border-t border-[#1e1e1e]">
                    <i class="fa-solid fa-wrench text-[8px] text-amber-400"></i>
                    <span class="text-[10px] text-[#888] font-bold">Fleet Access</span>
                    <span class="text-[#333]">•</span>
                    <span class="font-mono text-[9px] text-[#444]">Maintenance</span>
                </div>
            </div>
        </div>

        <!-- ── Page Header (desktop) ── -->
        <div class="hidden lg:flex items-end justify-between mb-8">
            <div>
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="text-[9px] font-bold uppercase tracking-[0.15em] text-[#444]">Fleet Overview</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight">Maintenance <span class="text-blue-500">Dashboard</span></h1>
                <p class="text-[11px] text-[#555] mt-1 flex items-center gap-2">
                    <i class="fa-solid fa-wrench text-[9px] text-amber-400"></i>
                    Role: <span class="text-[#888] font-bold">Maintenance Manager</span>
                    <span class="text-[#333]">•</span>
                    <span class="font-mono text-[10px] text-[#444]">{{ Auth::user()->email }}</span>
                </p>
            </div>
            <select id="fleet-selector"
                class="bg-[#0a0a0a] border border-[#1e1e1e] rounded-xl px-4 py-2.5 text-[10px] font-bold text-[#888] focus:ring-1 focus:ring-blue-500/50 outline-none appearance-none cursor-pointer pr-10">
                @foreach($fleets as $f)
                    <option value="{{ $f->id }}" {{ $fleet->id === $f->id ? 'selected' : '' }}>
                        {{ $f->vehicle?->plate_number }} — {{ $f->vehicle?->brand }} {{ $f->vehicle?->model }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- ── Mobile Fleet Selector ── -->
        <div class="lg:hidden mb-5">
            <select id="fleet-selector"
                class="w-full bg-[#161616] border border-[#222] rounded-xl px-4 py-2.5 text-[10px] font-bold text-[#888] focus:ring-1 focus:ring-blue-500/50 outline-none appearance-none cursor-pointer pr-10">
                @foreach($fleets as $f)
                    <option value="{{ $f->id }}" {{ $fleet->id === $f->id ? 'selected' : '' }}>
                        {{ $f->vehicle?->plate_number }} — {{ $f->vehicle?->brand }} {{ $f->vehicle?->model }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- ══════════ VEHICLE INFORMATION CARD ══════════ -->
        <div class="glass-card rounded-[1.25rem] sm:rounded-[1.5rem] p-4 sm:p-6 mb-5 sm:mb-6">
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 mb-5">
                <div class="flex items-center gap-3.5">
                    <div class="w-11 h-11 rounded-xl bg-[#111] border border-[#1e1e1e] flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-car text-[#555] text-sm"></i>
                    </div>
                    <div>
                        <h3 class="text-sm sm:text-base font-black tracking-tight">{{ $fleet->vehicle?->year }} {{ $fleet->vehicle?->brand }} {{ $fleet->vehicle?->model }}</h3>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="font-mono text-[9px] text-[#555] bg-[#111] border border-[#1e1e1e] px-1.5 py-0.5 rounded-md">{{ $fleet->vehicle?->plate_number }}</span>
                            @if($fleet->vehicle?->status === 'active')
                                <span class="inline-flex items-center gap-1 text-[8px] font-bold text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded-full border border-emerald-500/20">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>Active
                                </span>
                            @elseif($fleet->vehicle?->status === 'maintenance')
                                <span class="inline-flex items-center gap-1 text-[8px] font-bold text-amber-400 bg-amber-500/10 px-2 py-0.5 rounded-full border border-amber-500/20">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>Maintenance
                                </span>
                            @elseif($fleet->vehicle?->status === 'inactive')
                                <span class="inline-flex items-center gap-1 text-[8px] font-bold text-rose-400/70 bg-rose-500/[0.06] px-2 py-0.5 rounded-full border border-rose-500/10">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-400/70"></span>Inactive
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 text-[8px] font-bold text-[#333] bg-[#111] px-2 py-0.5 rounded-full border border-[#1e1e1e]">
                                    <span class="w-1.5 h-1.5 rounded-full bg-[#333]"></span>{{ ucfirst($fleet->vehicle?->status ?? '') }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="sm:text-right">
                    <span class="text-[8px] font-bold uppercase tracking-[0.15em] text-[#333] block">Last updated</span>
                    <span class="text-[10px] font-bold text-[#555]">{{ $fleet->vehicle?->updated_at?->format('M d, Y') }}</span>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-x-4 gap-y-3 pt-4 border-t border-[#1e1e1e]">
                <div>
                    <span class="text-[7px] font-bold uppercase tracking-[0.15em] text-[#333] block mb-1">Fuel Type</span>
                    <span class="text-[10px] font-bold text-[#888]">{{ $fleet->vehicle?->fuel_type ?? '—' }}</span>
                </div>
                <div>
                    <span class="text-[7px] font-bold uppercase tracking-[0.15em] text-[#333] block mb-1">Tank Capacity</span>
                    <span class="text-[10px] font-bold text-[#888]">{{ $fleet->vehicle?->tank_capacity ? $fleet->vehicle->tank_capacity . ' L' : '—' }}</span>
                </div>
                <div>
                    <span class="text-[7px] font-bold uppercase tracking-[0.15em] text-[#333] block mb-1">Driver</span>
                    <span class="text-[10px] font-bold text-[#888]">{{ $fleet->vehicle?->driver?->name ?? 'Unassigned' }}</span>
                </div>
                <div>
                    <span class="text-[7px] font-bold uppercase tracking-[0.15em] text-[#333] block mb-1">Location</span>
                    <span class="text-[10px] font-bold text-[#888]">{{ $fleet->vehicle?->location ?? '—' }}</span>
                </div>
                <div>
                    <span class="text-[7px] font-bold uppercase tracking-[0.15em] text-[#333] block mb-1">Acquired</span>
                    <span class="text-[10px] font-bold text-[#555]">{{ $fleet->vehicle?->acquistion_date ? $fleet->vehicle->acquistion_date->format('M d, Y') : '—' }}</span>
                </div>
                <div>
                    <span class="text-[7px] font-bold uppercase tracking-[0.15em] text-[#333] block mb-1">Disposal</span>
                    <span class="text-[10px] font-bold text-[#555]">{{ $fleet->vehicle?->exp_disposal_date ? $fleet->vehicle->exp_disposal_date->format('M d, Y') : '—' }}</span>
                </div>
            </div>

            <div class="flex items-center gap-2 mt-3 pt-3 border-t border-[#1e1e1e]">
                <span class="text-[7px] font-bold uppercase tracking-[0.15em] text-[#333]">VIN</span>
                <span class="font-mono text-[9px] text-[#444]">{{ $fleet->vehicle?->vin ?? '—' }}</span>
            </div>
        </div>

        <!-- ══════════ STAT CARDS ══════════ -->
        <div class="grid grid-cols-2 sm:grid-cols-2 xl:grid-cols-3 gap-3 sm:gap-4 mb-5 sm:mb-6">

            <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-amber-500 col-span-2 xl:col-span-1">
                <div class="flex items-center gap-2 mb-2 sm:mb-3">
                    <div class="w-6 h-6 rounded-md bg-amber-500/10 flex items-center justify-center">
                        <i class="fa-solid fa-road text-[8px] text-amber-400"></i>
                    </div>
                    <span class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Annual Kilometers</span>
                </div>
                <div class="flex items-baseline gap-1 sm:gap-1.5">
                    <span class="text-xl sm:text-2xl font-black tracking-tight">{{ number_format($annualKm) }}</span>
                    <span class="text-xs sm:text-sm font-bold text-[#555]">km</span>
                </div>
                <p class="text-[8px] text-[#333] mt-1 font-bold uppercase">Driven in {{ $year }}</p>
            </div>

            <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-emerald-500">
                <div class="flex items-center gap-2 mb-2 sm:mb-3">
                    <div class="w-6 h-6 rounded-md bg-emerald-500/10 flex items-center justify-center">
                        <i class="fa-solid fa-gauge text-[8px] text-emerald-400"></i>
                    </div>
                    <span class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Cost Per Km</span>
                </div>
                <div class="flex items-baseline gap-1 sm:gap-1.5">
                    <span class="text-xl sm:text-2xl font-black tracking-tight text-emerald-400">₱{{ number_format($costPerKm, 2) }}</span>
                </div>
                <p class="text-[8px] text-[#333] mt-1 font-bold uppercase">Per km average (YTD)</p>
            </div>

            <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-blue-500">
                <div class="flex items-center gap-2 mb-2 sm:mb-3">
                    <div class="w-6 h-6 rounded-md bg-blue-500/10 flex items-center justify-center">
                        <i class="fa-solid fa-coins text-[8px] text-blue-400"></i>
                    </div>
                    <span class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Total Service Cost</span>
                </div>
                <div class="flex items-baseline gap-1 sm:gap-1.5">
                    <span class="text-xl sm:text-2xl font-black tracking-tight text-blue-400">₱{{ number_format($totalServiceCost, 2) }}</span>
                </div>
                <p class="text-[8px] text-[#333] mt-1 font-bold uppercase">{{ $year }} year-to-date</p>
            </div>

        </div>

        <div class="grid grid-cols-1 xl:grid-cols-12 gap-5 sm:gap-6">

            <!-- ══════════ LEFT COLUMN ══════════ -->
            <div class="xl:col-span-8 flex flex-col gap-5 sm:gap-6">

                <!-- ── Monthly Cost Chart ── -->
                <div class="glass-card p-4 sm:p-6 rounded-[1.25rem] sm:rounded-[1.5rem]">
                    <div class="flex items-center justify-between mb-5">
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-lg bg-[#111] border border-[#1e1e1e] flex items-center justify-center">
                                <i class="fa-solid fa-chart-column text-[9px] text-[#555]"></i>
                            </div>
                            <span class="text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-[#555]">{{ $year }} Monthly Maintenance Cost</span>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="flex items-center gap-1.5">
                                <div class="w-2 h-2 rounded-sm bg-blue-500"></div>
                                <span class="text-[7px] sm:text-[8px] font-bold text-[#444] uppercase">Cost</span>
                            </div>
                        </div>
                    </div>
                    <div class="relative" style="height: 220px;">
                        <canvas id="monthlyCostChart"></canvas>
                    </div>
                </div>

                <!-- ══════════ TABS ══════════ -->
                <div>
                    <div class="flex gap-1 border-b border-[#1e1e1e] mb-5 overflow-x-auto">
                        <button @click="activeTab = 'cpk'" :class="activeTab === 'cpk' ? 'tab-active' : 'tab-inactive'" class="pb-3 px-3 text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.12em] transition-all whitespace-nowrap">
                            <i class="fa-solid fa-gauge-high mr-1.5 text-[7px]"></i>Cost / Km
                        </button>
                        <button @click="activeTab = 'cost'" :class="activeTab === 'cost' ? 'tab-active' : 'tab-inactive'" class="pb-3 px-3 text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.12em] transition-all whitespace-nowrap">
                            <i class="fa-solid fa-table-columns mr-1.5 text-[7px]"></i>Cost Summary
                        </button>
                        <button @click="activeTab = 'schedule'" :class="activeTab === 'schedule' ? 'tab-active' : 'tab-inactive'" class="pb-3 px-3 text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.12em] transition-all whitespace-nowrap">
                            <i class="fa-solid fa-calendar-days mr-1.5 text-[7px]"></i>P.M. Schedule
                        </button>
                        <button @click="activeTab = 'log'" :class="activeTab === 'log' ? 'tab-active' : 'tab-inactive'" class="pb-3 px-3 text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.12em] transition-all whitespace-nowrap">
                            <i class="fa-solid fa-list-check mr-1.5 text-[7px]"></i>Maint. Log
                        </button>
                    </div>

                    <!-- ─────────────────────────────────── -->
                    <!-- TAB: COST PER KM SUMMARY            -->
                    <!-- ─────────────────────────────────── -->
                    <div x-show="activeTab === 'cpk'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" style="display:none;">
                        <div class="glass-card rounded-[1.25rem] sm:rounded-[1.5rem] overflow-hidden">
                            <div class="px-4 sm:px-6 py-3 border-b border-[#1e1e1e] bg-[#111] flex justify-between items-center">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-6 h-6 rounded-md bg-blue-500/10 flex items-center justify-center">
                                        <i class="fa-solid fa-gauge-high text-[8px] text-blue-400"></i>
                                    </div>
                                    <span class="text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-[#555]">Cost Per Km — {{ $year }}</span>
                                </div>
                                <span class="text-[7px] text-[#333] uppercase tracking-widest font-bold hidden sm:inline">Scroll <i class="fa-solid fa-arrow-right text-[6px] ml-0.5"></i></span>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left whitespace-nowrap">
                                    <thead>
                                        <tr class="border-b border-[#1e1e1e] bg-[#0a0a0a]">
                                            <th class="px-4 sm:px-6 py-2.5 text-[7px] sm:text-[8px] font-black uppercase tracking-[0.15em] text-blue-500 sticky left-0 bg-[#0a0a0a] z-10 text-left min-w-[160px]">Metric</th>
                                            @foreach(range(1, 12) as $m)<th class="px-3 sm:px-4 py-2.5 text-[7px] sm:text-[8px] font-bold text-[#333] text-right w-16 sm:w-20">{{ ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'][$m - 1] }}</th>@endforeach
                                            <th class="px-4 sm:px-6 py-2.5 text-[7px] sm:text-[8px] font-bold text-blue-400 text-right w-20 sm:w-24 border-l border-[#1e1e1e]">YTD</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-[#1a1a1a]">
                                        <tr class="table-row">
                                            <td class="px-4 sm:px-6 py-2.5 text-[9px] sm:text-[10px] text-[#555] font-bold sticky left-0 bg-[#161616]">
                                                <i class="fa-solid fa-flag-checkered text-[#333] text-[8px] mr-2 w-3.5 text-center"></i>Start Odometer
                                            </td>
                                            @foreach(range(1, 12) as $m)<td class="px-3 sm:px-4 py-2.5 font-mono text-[9px] sm:text-[10px] text-right @if($monthlyStartOdo[$m] !== null) text-[#555] @else text-[#222] @endif">{{ $monthlyStartOdo[$m] !== null ? number_format($monthlyStartOdo[$m]) : '—' }}</td>@endforeach
                                            <td class="px-4 sm:px-6 py-2.5 font-mono text-[9px] sm:text-[10px] text-right text-[#666] border-l border-[#1e1e1e]">{{ $yearStartOdo !== null ? number_format($yearStartOdo) : '—' }}</td>
                                        </tr>
                                        <tr class="table-row">
                                            <td class="px-4 sm:px-6 py-2.5 text-[9px] sm:text-[10px] text-[#555] font-bold sticky left-0 bg-[#161616]">
                                                <i class="fa-solid fa-flag text-[#333] text-[8px] mr-2 w-3.5 text-center"></i>End Odometer
                                            </td>
                                            @foreach(range(1, 12) as $m)<td class="px-3 sm:px-4 py-2.5 font-mono text-[9px] sm:text-[10px] text-right @if($monthlyEndOdo[$m] !== null) text-[#666] @else text-[#222] @endif">{{ $monthlyEndOdo[$m] !== null ? number_format($monthlyEndOdo[$m]) : '—' }}</td>@endforeach
                                            <td class="px-4 sm:px-6 py-2.5 font-mono text-[9px] sm:text-[10px] text-right text-[#666] border-l border-[#1e1e1e]">{{ $yearEndOdo ? number_format($yearEndOdo) : '—' }}</td>
                                        </tr>
                                        <tr class="table-row">
                                            <td class="px-4 sm:px-6 py-2.5 text-[9px] sm:text-[10px] text-[#888] font-bold sticky left-0 bg-[#161616]">
                                                <i class="fa-solid fa-road text-[#444] text-[8px] mr-2 w-3.5 text-center"></i>Monthly Km
                                            </td>
                                            @foreach(range(1, 12) as $m)<td class="px-3 sm:px-4 py-2.5 font-mono text-[9px] sm:text-[10px] text-right @if($monthlyKm[$m] > 0) text-[#888] @else text-[#222] @endif">{{ $monthlyKm[$m] > 0 ? number_format($monthlyKm[$m]) : '—' }}</td>@endforeach
                                            <td class="px-4 sm:px-6 py-2.5 font-mono text-[9px] sm:text-[10px] text-right font-bold text-[#ccc] border-l border-[#1e1e1e]">{{ number_format($annualKm) }}</td>
                                        </tr>
                                        <tr class="table-row">
                                            <td class="px-4 sm:px-6 py-2.5 text-[9px] sm:text-[10px] text-[#888] font-bold sticky left-0 bg-[#161616]">
                                                <i class="fa-solid fa-gauge text-emerald-500/50 text-[8px] mr-2 w-3.5 text-center"></i>Cost / Km
                                            </td>
                                            @foreach(range(1, 12) as $m)<td class="px-3 sm:px-4 py-2.5 font-mono text-[9px] sm:text-[10px] text-right @if($monthlyCpk[$m] !== null) text-emerald-400/80 @else text-[#222] @endif">{{ $monthlyCpk[$m] !== null ? '₱' . number_format($monthlyCpk[$m], 2) : '—' }}</td>@endforeach
                                            <td class="px-4 sm:px-6 py-2.5 font-mono text-[9px] sm:text-[10px] text-right font-bold text-emerald-400 border-l border-[#1e1e1e]">₱{{ number_format($costPerKm, 2) }}</td>
                                        </tr>
                                        <tr class="bg-blue-500/[0.03] border-t-2 border-blue-500/20">
                                            <td class="px-4 sm:px-6 py-3 text-[9px] sm:text-[10px] text-blue-400 font-bold sticky left-0 bg-blue-500/[0.03]">
                                                <i class="fa-solid fa-coins text-[9px] mr-2"></i>Total Service Cost
                                            </td>
                                            @foreach(range(1, 12) as $m)<td class="px-3 sm:px-4 py-3 font-mono text-[9px] sm:text-[10px] text-right font-bold @if($monthlyTotals[$m] > 0) text-blue-400 @else text-[#1a1a1a] @endif">₱{{ number_format($monthlyTotals[$m], 2) }}</td>@endforeach
                                            <td class="px-4 sm:px-6 py-3 font-mono text-[9px] sm:text-[10px] text-right font-black text-blue-400 border-l border-[#1e1e1e]">₱{{ number_format($ytdTotal, 2) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="px-4 sm:px-6 py-2.5 border-t border-[#1e1e1e] bg-[#111] flex flex-col sm:flex-row justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <span class="text-[7px] text-[#333] uppercase font-bold tracking-widest">Annual Km</span>
                                    <span class="text-[10px] text-white font-black font-mono">{{ number_format($annualKm) }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-[7px] text-[#333] uppercase font-bold tracking-widest">Avg Cost/Km</span>
                                    <span class="text-[10px] text-emerald-400 font-black font-mono">₱{{ number_format($costPerKm, 2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ─────────────────────────────────── -->
                    <!-- TAB: COST SUMMARY                   -->
                    <!-- ─────────────────────────────────── -->
                    <div x-show="activeTab === 'cost'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" style="display:none;">
                        <div class="glass-card rounded-[1.25rem] sm:rounded-[1.5rem] overflow-hidden">
                            <div class="px-4 sm:px-6 py-3 border-b border-[#1e1e1e] bg-[#111] flex justify-between items-center">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-6 h-6 rounded-md bg-blue-500/10 flex items-center justify-center">
                                        <i class="fa-solid fa-table-columns text-[8px] text-blue-400"></i>
                                    </div>
                                    <span class="text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-[#555]">Maintenance Cost — {{ $year }}</span>
                                </div>
                                <span class="text-[7px] text-[#333] uppercase tracking-widest font-bold hidden sm:inline">Scroll <i class="fa-solid fa-arrow-right text-[6px] ml-0.5"></i></span>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left whitespace-nowrap">
                                    <thead>
                                        <tr class="border-b border-[#1e1e1e] bg-[#0a0a0a]">
                                            <th class="px-4 sm:px-6 py-2.5 text-[7px] sm:text-[8px] font-black uppercase tracking-[0.15em] text-blue-500 sticky left-0 bg-[#0a0a0a] z-10 text-left min-w-[180px]">Category / Item</th>
                                            @foreach(range(1, 12) as $m)<th class="px-3 sm:px-4 py-2.5 text-[7px] sm:text-[8px] font-bold text-[#333] text-right w-16 sm:w-20">{{ ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'][$m - 1] }}</th>@endforeach
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-[#1a1a1a]">
                                        @forelse($costSummary as $taskName => $months)
                                            <tr class="table-row">
                                                <td class="px-4 sm:px-6 py-2.5 text-[9px] sm:text-[10px] text-[#888] font-bold sticky left-0 bg-[#161616] truncate max-w-[220px]">{{ $taskName }}</td>
                                                @foreach(range(1, 12) as $m)<td class="px-3 sm:px-4 py-2.5 font-mono text-[9px] sm:text-[10px] text-right @if($months[$m] > 0) text-[#666] @else text-[#222] @endif">{{ $months[$m] > 0 ? '₱' . number_format($months[$m], 2) : '—' }}</td>@endforeach
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="13" class="px-6 py-12 text-center">
                                                    <div class="flex flex-col items-center">
                                                        <div class="w-10 h-10 rounded-xl bg-[#111] border border-[#1e1e1e] flex items-center justify-center mb-2.5">
                                                            <i class="fa-solid fa-coins text-sm text-[#333]"></i>
                                                        </div>
                                                        <p class="text-[10px] text-[#444] font-medium">No maintenance records for {{ $year }}</p>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                        @if($costSummary->isNotEmpty())
                                        <tr class="bg-blue-500/[0.03] border-t-2 border-blue-500/20">
                                            <td class="px-4 sm:px-6 py-3 text-[9px] sm:text-[10px] text-blue-400 font-bold sticky left-0 bg-blue-500/[0.03]">
                                                <i class="fa-solid fa-calculator text-[9px] mr-2"></i>Total Monthly
                                            </td>
                                            @foreach(range(1, 12) as $m)<td class="px-3 sm:px-4 py-3 font-mono text-[9px] sm:text-[10px] text-right font-bold @if($monthlyTotals[$m] > 0) text-blue-400 @else text-[#1a1a1a] @endif">₱{{ number_format($monthlyTotals[$m], 2) }}</td>@endforeach
                                        </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                            <div class="px-4 sm:px-6 py-2.5 border-t border-[#1e1e1e] bg-[#111] flex justify-between items-center">
                                <span class="text-[7px] text-[#333] uppercase font-bold tracking-widest">YTD Total</span>
                                <span class="text-[10px] text-white font-black font-mono">₱{{ number_format($ytdTotal, 2) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- ─────────────────────────────────── -->
                    <!-- TAB: PREVENTIVE MAINTENANCE SCHEDULE-->
                    <!-- ─────────────────────────────────── -->
                    <div x-show="activeTab === 'schedule'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" style="display:none;">
                        <div class="glass-card rounded-[1.25rem] sm:rounded-[1.5rem] overflow-hidden">
                            <div class="px-4 sm:px-6 py-3 border-b border-[#1e1e1e] bg-[#111]">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-6 h-6 rounded-md bg-amber-500/10 flex items-center justify-center">
                                        <i class="fa-solid fa-calendar-days text-[8px] text-amber-400"></i>
                                    </div>
                                    <span class="text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-[#555]">Preventive Maintenance Schedule</span>
                                </div>
                            </div>
                            <div class="p-4 sm:p-6 space-y-4">

                                <!-- 10,000 Km -->
                                <div class="p-4 rounded-xl bg-[#111] border border-[#1e1e1e]">
                                    <div class="flex items-center gap-2.5 mb-3">
                                        <div class="w-5 h-5 rounded-md bg-blue-500/15 border border-blue-500/25 flex items-center justify-center">
                                            <div class="w-1.5 h-1.5 rounded-full bg-blue-400"></div>
                                        </div>
                                        <span class="text-[10px] font-black text-white">10,000 Km</span>
                                        <span class="text-[7px] font-bold text-blue-400 bg-blue-500/10 px-1.5 py-0.5 rounded-md border border-blue-500/15 uppercase">Every 10K</span>
                                    </div>
                                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-1.5">
                                        <div class="flex items-center gap-2 px-2.5 py-2 rounded-lg bg-[#161616] border border-[#1e1e1e]"><i class="fa-solid fa-rotate text-[#333] text-[8px] w-3.5 text-center"></i><span class="text-[9px] text-[#666] font-bold">Tire Rotation</span></div>
                                        <div class="flex items-center gap-2 px-2.5 py-2 rounded-lg bg-[#161616] border border-[#1e1e1e]"><i class="fa-solid fa-oil-can text-[#333] text-[8px] w-3.5 text-center"></i><span class="text-[9px] text-[#666] font-bold">Oil & Filter</span></div>
                                        <div class="flex items-center gap-2 px-2.5 py-2 rounded-lg bg-[#161616] border border-[#1e1e1e]"><i class="fa-solid fa-wind text-[#333] text-[8px] w-3.5 text-center"></i><span class="text-[9px] text-[#666] font-bold">Air Filter Check</span></div>
                                        <div class="flex items-center gap-2 px-2.5 py-2 rounded-lg bg-[#161616] border border-[#1e1e1e]"><i class="fa-solid fa-droplet text-[#333] text-[8px] w-3.5 text-center"></i><span class="text-[9px] text-[#666] font-bold">Fluid Levels</span></div>
                                    </div>
                                </div>

                                <!-- 20,000 Km -->
                                <div class="p-4 rounded-xl bg-[#111] border border-[#1e1e1e]">
                                    <div class="flex items-center gap-2.5 mb-3">
                                        <div class="w-5 h-5 rounded-md bg-amber-500/15 border border-amber-500/25 flex items-center justify-center">
                                            <div class="w-1.5 h-1.5 rounded-full bg-amber-400"></div>
                                        </div>
                                        <span class="text-[10px] font-black text-white">20,000 Km</span>
                                        <span class="text-[7px] font-bold text-amber-400 bg-amber-500/10 px-1.5 py-0.5 rounded-md border border-amber-500/15 uppercase">Every 20K</span>
                                    </div>
                                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-1.5">
                                        <div class="flex items-center gap-2 px-2.5 py-2 rounded-lg bg-[#161616] border border-[#1e1e1e]"><i class="fa-solid fa-circle-dot text-[#333] text-[8px] w-3.5 text-center"></i><span class="text-[9px] text-[#666] font-bold">Tire Replacement</span></div>
                                        <div class="flex items-center gap-2 px-2.5 py-2 rounded-lg bg-[#161616] border border-[#1e1e1e]"><i class="fa-solid fa-oil-can text-[#333] text-[8px] w-3.5 text-center"></i><span class="text-[9px] text-[#666] font-bold">Oil & Filter</span></div>
                                        <div class="flex items-center gap-2 px-2.5 py-2 rounded-lg bg-[#161616] border border-[#1e1e1e]"><i class="fa-solid fa-wind text-[#333] text-[8px] w-3.5 text-center"></i><span class="text-[9px] text-[#666] font-bold">Air Filter Replace</span></div>
                                        <div class="flex items-center gap-2 px-2.5 py-2 rounded-lg bg-[#161616] border border-[#1e1e1e]"><i class="fa-solid fa-droplet text-[#333] text-[8px] w-3.5 text-center"></i><span class="text-[9px] text-[#666] font-bold">Coolant Flush</span></div>
                                    </div>
                                </div>

                                <!-- 30,000 Km -->
                                <div class="p-4 rounded-xl bg-[#111] border border-[#1e1e1e]">
                                    <div class="flex items-center gap-2.5 mb-3">
                                        <div class="w-5 h-5 rounded-md bg-blue-500/15 border border-blue-500/25 flex items-center justify-center">
                                            <div class="w-1.5 h-1.5 rounded-full bg-blue-400"></div>
                                        </div>
                                        <span class="text-[10px] font-black text-white">30,000 Km</span>
                                        <span class="text-[7px] font-bold text-blue-400 bg-blue-500/10 px-1.5 py-0.5 rounded-md border border-blue-500/15 uppercase">Every 30K</span>
                                    </div>
                                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-1.5">
                                        <div class="flex items-center gap-2 px-2.5 py-2 rounded-lg bg-[#161616] border border-[#1e1e1e]"><i class="fa-solid fa-rotate text-[#333] text-[8px] w-3.5 text-center"></i><span class="text-[9px] text-[#666] font-bold">Tire Rotation</span></div>
                                        <div class="flex items-center gap-2 px-2.5 py-2 rounded-lg bg-[#161616] border border-[#1e1e1e]"><i class="fa-solid fa-oil-can text-[#333] text-[8px] w-3.5 text-center"></i><span class="text-[9px] text-[#666] font-bold">Oil & Filter</span></div>
                                        <div class="flex items-center gap-2 px-2.5 py-2 rounded-lg bg-[#161616] border border-[#1e1e1e]"><i class="fa-solid fa-gears text-[#333] text-[8px] w-3.5 text-center"></i><span class="text-[9px] text-[#666] font-bold">Transmission Fluid</span></div>
                                        <div class="flex items-center gap-2 px-2.5 py-2 rounded-lg bg-[#161616] border border-[#1e1e1e]"><i class="fa-solid fa-car text-[#333] text-[8px] w-3.5 text-center"></i><span class="text-[9px] text-[#666] font-bold">Brake Inspection</span></div>
                                    </div>
                                </div>

                                <!-- 50,000 Km -->
                                <div class="p-4 rounded-xl bg-[#111] border border-rose-500/10">
                                    <div class="flex items-center gap-2.5 mb-3">
                                        <div class="w-5 h-5 rounded-md bg-rose-500/15 border border-rose-500/25 flex items-center justify-center">
                                            <div class="w-1.5 h-1.5 rounded-full bg-rose-400"></div>
                                        </div>
                                        <span class="text-[10px] font-black text-white">50,000 Km</span>
                                        <span class="text-[7px] font-bold text-rose-400 bg-rose-500/10 px-1.5 py-0.5 rounded-md border border-rose-500/15 uppercase">Major Service</span>
                                    </div>
                                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-1.5">
                                        <div class="flex items-center gap-2 px-2.5 py-2 rounded-lg bg-[#161616] border border-[#1e1e1e]"><i class="fa-solid fa-circle-dot text-[#333] text-[8px] w-3.5 text-center"></i><span class="text-[9px] text-[#666] font-bold">Tire Replacement</span></div>
                                        <div class="flex items-center gap-2 px-2.5 py-2 rounded-lg bg-[#161616] border border-[#1e1e1e]"><i class="fa-solid fa-oil-can text-[#333] text-[8px] w-3.5 text-center"></i><span class="text-[9px] text-[#666] font-bold">Oil & Filter</span></div>
                                        <div class="flex items-center gap-2 px-2.5 py-2 rounded-lg bg-[#161616] border border-[#1e1e1e]"><i class="fa-solid fa-car text-[#333] text-[8px] w-3.5 text-center"></i><span class="text-[9px] text-[#666] font-bold">Brake Pads</span></div>
                                        <div class="flex items-center gap-2 px-2.5 py-2 rounded-lg bg-[#161616] border border-[#1e1e1e]"><i class="fa-solid fa-bolt text-[#333] text-[8px] w-3.5 text-center"></i><span class="text-[9px] text-[#666] font-bold">Spark Plugs</span></div>
                                        <div class="flex items-center gap-2 px-2.5 py-2 rounded-lg bg-[#161616] border border-[#1e1e1e]"><i class="fa-solid fa-gears text-[#333] text-[8px] w-3.5 text-center"></i><span class="text-[9px] text-[#666] font-bold">Transmission Svc</span></div>
                                        <div class="flex items-center gap-2 px-2.5 py-2 rounded-lg bg-[#161616] border border-[#1e1e1e]"><i class="fa-solid fa-droplet text-[#333] text-[8px] w-3.5 text-center"></i><span class="text-[9px] text-[#666] font-bold">Coolant Flush</span></div>
                                        <div class="flex items-center gap-2 px-2.5 py-2 rounded-lg bg-[#161616] border border-[#1e1e1e]"><i class="fa-solid fa-wind text-[#333] text-[8px] w-3.5 text-center"></i><span class="text-[9px] text-[#666] font-bold">Air Filter</span></div>
                                        <div class="flex items-center gap-2 px-2.5 py-2 rounded-lg bg-[#161616] border border-[#1e1e1e]"><i class="fa-solid fa-magnifying-glass text-[#333] text-[8px] w-3.5 text-center"></i><span class="text-[9px] text-[#666] font-bold">Full Inspection</span></div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- ─────────────────────────────────── -->
                    <!-- TAB: VEHICLE MAINTENANCE LOG        -->
                    <!-- ─────────────────────────────────── -->
                    <div x-show="activeTab === 'log'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" style="display:none;">
                        <div class="glass-card rounded-[1.25rem] sm:rounded-[1.5rem] overflow-hidden">
                            <div class="px-4 sm:px-6 py-3 border-b border-[#1e1e1e] bg-[#111] flex justify-between items-center">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-6 h-6 rounded-md bg-emerald-500/10 flex items-center justify-center">
                                        <i class="fa-solid fa-list-check text-[8px] text-emerald-400"></i>
                                    </div>
                                    <span class="text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-[#555]">Maintenance Log</span>
                                </div>
                                <span class="text-[7px] text-[#333] uppercase tracking-widest font-bold">{{ $allLogs->count() }} {{ $allLogs->count() === 1 ? 'entry' : 'entries' }}</span>
                            </div>
                            <div class="overflow-x-auto -mx-2 px-2 pb-2">
                                <table class="w-full text-left min-w-[600px]">
                                    <thead>
                                        <tr class="text-[7px] sm:text-[8px] uppercase tracking-[0.15em] text-[#444] border-b border-[#1e1e1e]">
                                            <th class="px-4 sm:px-6 py-2.5 font-bold">Task</th>
                                            <th class="px-4 sm:px-6 py-2.5 font-bold">Date</th>
                                            <th class="px-4 sm:px-6 py-2.5 font-bold">Odometer</th>
                                            <th class="px-4 sm:px-6 py-2.5 font-bold text-right">Cost</th>
                                            <th class="px-4 sm:px-6 py-2.5 font-bold">Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-[#1a1a1a]">
                                        @forelse($allLogs as $log)
                                            <tr class="table-row">
                                                <td class="px-4 sm:px-6 py-3">
                                                    <div class="flex items-center gap-3">
                                                        <div class="w-8 h-8 rounded-lg bg-emerald-500/10 border border-emerald-500/15 flex items-center justify-center shrink-0">
                                                            <i class="fa-solid fa-check text-[8px] text-emerald-400"></i>
                                                        </div>
                                                        <p class="text-[10px] sm:text-[11px] font-bold text-[#ccc] truncate max-w-[200px]">{{ $log['task_name'] }}</p>
                                                    </div>
                                                </td>
                                                <td class="px-4 sm:px-6 py-3">
                                                    <span class="text-[10px] sm:text-[11px] font-bold text-[#888]">{{ $log['service_date'] ? \Carbon\Carbon::parse($log['service_date'])->format('M d, Y') : '—' }}</span>
                                                </td>
                                                <td class="px-4 sm:px-6 py-3">
                                                    <span class="font-mono text-[10px] sm:text-[11px] font-bold text-[#666]">{{ number_format($log['mileage']) }} km</span>
                                                </td>
                                                <td class="px-4 sm:px-6 py-3 text-right">
                                                    <span class="text-[10px] sm:text-[11px] font-bold text-blue-400">₱{{ number_format($log['cost'], 2) }}</span>
                                                </td>
                                                <td class="px-4 sm:px-6 py-3">
                                                    <span class="text-[9px] sm:text-[10px] text-[#444] truncate block max-w-[180px]" title="{{ $log['remarks'] ?? '' }}">{{ $log['remarks'] ?? '—' }}</span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="py-10 sm:py-12">
                                                    <div class="flex flex-col items-center justify-center">
                                                        <div class="w-10 h-10 rounded-xl bg-[#111] border border-[#1e1e1e] flex items-center justify-center mb-2.5">
                                                            <i class="fa-solid fa-clipboard-list text-sm text-[#333]"></i>
                                                        </div>
                                                        <p class="text-[10px] sm:text-[11px] text-[#444] font-medium">No maintenance logs recorded yet</p>
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

            <!-- ══════════ RIGHT COLUMN ══════════ -->
            <div class="xl:col-span-4 flex flex-col gap-5 sm:gap-6">

                <!-- ── Cost Breakdown Donut ── -->
                <div class="glass-card p-4 sm:p-6 rounded-[1.25rem] sm:rounded-[1.5rem]">
                    <div class="flex items-center gap-2.5 mb-5">
                        <div class="w-7 h-7 rounded-lg bg-[#111] border border-[#1e1e1e] flex items-center justify-center">
                            <i class="fa-solid fa-chart-pie text-[9px] text-[#555]"></i>
                        </div>
                        <span class="text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-[#555]">Cost Breakdown</span>
                    </div>
                    @if($donutTotal > 0)
                    <div class="flex items-center justify-center mb-5">
                        <div class="relative" style="width: 160px; height: 160px;">
                            <canvas id="donutChart"></canvas>
                            <div class="donut-center text-center">
                                <p class="text-[10px] font-black text-white">₱{{ number_format($donutTotal, 0) }}</p>
                                <p class="text-[7px] font-bold uppercase tracking-[0.15em] text-[#444]">Total</p>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-1.5 max-h-[200px] overflow-y-auto">
                        @php
                            $catColors = ['#3b82f6', '#10b981', '#a855f7', '#f59e0b', '#ef4444', '#06b6d4', '#ec4899', '#84cc16', '#f97316', '#6366f1'];
                        @endphp
                        @foreach($donutLabels as $idx => $label)
                            <div class="flex items-center justify-between p-2.5 rounded-xl bg-[#111] border border-[#1e1e1e]">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <div class="w-2.5 h-2.5 rounded-sm shrink-0" style="background: {{ $catColors[$idx % count($catColors)] }}"></div>
                                    <span class="text-[8px] sm:text-[9px] font-bold text-[#666] uppercase truncate">{{ $label }}</span>
                                </div>
                                <div class="flex items-center gap-2 shrink-0 ml-2">
                                    <span class="text-[9px] sm:text-[10px] font-bold text-[#ccc]">₱{{ number_format($donutData[$idx], 0) }}</span>
                                    <span class="text-[7px] font-bold text-[#333] w-10 text-right">{{ $donutTotal > 0 ? round(($donutData[$idx] / $donutTotal) * 100, 0) : 0 }}%</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @else
                    <div class="flex flex-col items-center justify-center py-8">
                        <div class="w-10 h-10 rounded-xl bg-[#111] border border-[#1e1e1e] flex items-center justify-center mb-2.5">
                            <i class="fa-solid fa-chart-pie text-sm text-[#333]"></i>
                        </div>
                        <p class="text-[10px] text-[#444] font-medium">No cost data for {{ $year }}</p>
                    </div>
                    @endif
                </div>

                <!-- ── Cost Category Horizontal Bar ── -->
                @if($donutTotal > 0)
                <div class="glass-card p-4 sm:p-6 rounded-[1.25rem] sm:rounded-[1.5rem]">
                    <div class="flex items-center gap-2.5 mb-5">
                        <div class="w-7 h-7 rounded-lg bg-[#111] border border-[#1e1e1e] flex items-center justify-center">
                            <i class="fa-solid fa-bars-staggered text-[9px] text-[#555]"></i>
                        </div>
                        <span class="text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-[#555]">Category Ranking</span>
                    </div>
                    <div class="relative" style="height: {{ max(120, count($donutLabels) * 36) }}px;">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
                @endif

                <!-- ── Quick Actions ── -->
                <div class="glass-card p-4 sm:p-6 rounded-[1.25rem] sm:rounded-[1.5rem]">
                    <div class="flex items-center gap-2.5 mb-4 sm:mb-5">
                        <div class="w-7 h-7 rounded-lg bg-[#111] border border-[#1e1e1e] flex items-center justify-center">
                            <i class="fa-solid fa-gears text-[9px] text-[#555]"></i>
                        </div>
                        <span class="text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-[#555]">Quick Actions</span>
                    </div>
                    <div class="space-y-2">
                        <a href="{{ route('maintenance-manager.fleet-inventory') }}" class="flex items-center justify-between p-3 sm:p-3.5 rounded-xl bg-[#111] border border-[#1e1e1e] hover:bg-[#1a1a1a] hover:border-[#333] transition group cursor-pointer">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-blue-500/10 border border-blue-500/15 flex items-center justify-center">
                                    <i class="fa-solid fa-car text-[9px] text-blue-400"></i>
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold text-[#888] group-hover:text-white transition block">Fleet Inventory</span>
                                    <span class="text-[7px] text-[#333] font-bold uppercase">View all vehicles</span>
                                </div>
                            </div>
                            <i class="fa-solid fa-chevron-right text-[8px] text-[#333] group-hover:text-[#555] transition"></i>
                        </a>
                    </div>
                </div>

                <!-- ── Vehicle Specs Summary ── -->
                <div class="glass-card p-4 sm:p-6 rounded-[1.25rem] sm:rounded-[1.5rem]">
                    <div class="flex items-center gap-2.5 mb-4 sm:mb-5">
                        <div class="w-7 h-7 rounded-lg bg-[#111] border border-[#1e1e1e] flex items-center justify-center">
                            <i class="fa-solid fa-info-circle text-[9px] text-[#555]"></i>
                        </div>
                        <span class="text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-[#555]">Vehicle Specs</span>
                    </div>
                    <div class="space-y-2">
                        <div class="flex items-center justify-between p-3 rounded-xl bg-[#111] border border-[#1e1e1e]">
                            <span class="text-[9px] font-bold text-[#555] uppercase">Plate Number</span>
                            <span class="font-mono text-[10px] font-bold text-[#888]">{{ $fleet->vehicle?->plate_number ?? '—' }}</span>
                        </div>
                        <div class="flex items-center justify-between p-3 rounded-xl bg-[#111] border border-[#1e1e1e]">
                            <span class="text-[9px] font-bold text-[#555] uppercase">VIN</span>
                            <span class="font-mono text-[10px] font-bold text-[#888] truncate max-w-[160px]">{{ $fleet->vehicle?->vin ?? '—' }}</span>
                        </div>
                        <div class="flex items-center justify-between p-3 rounded-xl bg-[#111] border border-[#1e1e1e]">
                            <span class="text-[9px] font-bold text-[#555] uppercase">Driver</span>
                            <span class="text-[10px] font-bold text-[#888] truncate max-w-[160px]">{{ $fleet->vehicle?->driver?->name ?? 'Unassigned' }}</span>
                        </div>
                        <div class="flex items-center justify-between p-3 rounded-xl bg-[#111] border border-[#1e1e1e]">
                            <span class="text-[9px] font-bold text-[#555] uppercase">Location</span>
                            <span class="text-[10px] font-bold text-[#888] truncate max-w-[160px]">{{ $fleet->vehicle?->location ?? '—' }}</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        @endif

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
                <p class="text-xs text-[#666] mb-7">Are you sure you want to exit the Maintenance Console?</p>

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

            // ══════════ MONTHLY COST BAR CHART ══════════
            const monthlyCtx = document.getElementById('monthlyCostChart');
            if (monthlyCtx) {
                new Chart(monthlyCtx, {
                    type: 'bar',
                    data: {
                        labels: @json($chartMonthLabels),
                        datasets: [
                            {
                                label: 'Maintenance Cost',
                                data: @json($chartMonthlyData),
                                backgroundColor: 'rgba(59, 130, 246, 0.7)',
                                hoverBackgroundColor: 'rgba(59, 130, 246, 0.9)',
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
                                        return ' Cost:  ₱' + ctx.parsed.y.toLocaleString('en-PH', { minimumFractionDigits: 2 });
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

            // ══════════ COST BREAKDOWN DONUT ══════════
            const donutCtx = document.getElementById('donutChart');
            if (donutCtx && @json($donutTotal) > 0) {
                const catColors = ['#3b82f6', '#10b981', '#a855f7', '#f59e0b', '#ef4444', '#06b6d4', '#ec4899', '#84cc16', '#f97316', '#6366f1'];

                new Chart(donutCtx, {
                    type: 'doughnut',
                    data: {
                        labels: @json($donutLabels),
                        datasets: [{
                            data: @json($donutData),
                            backgroundColor: catColors.slice(0, @json(count($donutLabels))),
                            hoverBackgroundColor: catColors.slice(0, @json(count($donutLabels))).map(c => c + 'dd'),
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
                                        return ' ' + ctx.label + ':  ₱' + ctx.parsed.toLocaleString('en-PH', { minimumFractionDigits: 2 }) + ' (' + pct + '%)';
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // ══════════ CATEGORY RANKING HORIZONTAL BAR ══════════
            const catCtx = document.getElementById('categoryChart');
            if (catCtx && @json($donutTotal) > 0) {
                const catColors = ['#3b82f6', '#10b981', '#a855f7', '#f59e0b', '#ef4444', '#06b6d4', '#ec4899', '#84cc16', '#f97316', '#6366f1'];
                const labels = @json($donutLabels);
                const data = @json($donutData);

                // Sort by value descending for ranking
                const paired = labels.map((l, i) => ({ label: l, value: data[i], color: catColors[i % catColors.length] }));
                paired.sort((a, b) => b.value - a.value);

                new Chart(catCtx, {
                    type: 'bar',
                    data: {
                        labels: paired.map(p => p.label.length > 18 ? p.label.substring(0, 18) + '…' : p.label),
                        datasets: [{
                            data: paired.map(p => p.value),
                            backgroundColor: paired.map(p => p.color + 'b3'),
                            hoverBackgroundColor: paired.map(p => p.color),
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
                                    title: function (items) {
                                        return paired[items[0].dataIndex].label;
                                    },
                                    label: function (ctx) {
                                        return ' ₱' + ctx.parsed.x.toLocaleString('en-PH', { minimumFractionDigits: 2 });
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                beginAtZero: true,
                                grid: { color: '#1a1a1a', drawBorder: false },
                                ticks: { font: { size: 9, weight: '600' }, color: '#333', callback: function(v) { return '₱' + v; } },
                                border: { display: false }
                            },
                            y: {
                                grid: { display: false },
                                ticks: { font: { size: 8, weight: '700' }, color: '#555' },
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
