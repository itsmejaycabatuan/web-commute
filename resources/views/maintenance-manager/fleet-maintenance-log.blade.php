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

        [x-cloak] { display: none !important; }

        .glass {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .sidebar-transition {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .tab-active {
            color: #fff;
            border-bottom: 2px solid #3b82f6;
        }

        .tab-inactive {
            color: rgba(255, 255, 255, 0.3);
            border-bottom: 2px solid transparent;
        }

        .table-scroll::-webkit-scrollbar { height: 4px; }
        .table-scroll::-webkit-scrollbar-track { background: transparent; }
        .table-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.08); border-radius: 999px; }

        .log-scroll::-webkit-scrollbar { width: 4px; }
        .log-scroll::-webkit-scrollbar-track { background: transparent; }
        .log-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.08); border-radius: 999px; }

        select {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='rgba(255,255,255,0.3)' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
        }

        input[type="text"],
        input[type="date"],
        input[type="number"],
        select,
        textarea {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            color: #fff;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        input:focus, select:focus, textarea:focus {
            border-color: rgba(59, 130, 246, 0.5);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        input[type="date"]::-webkit-calendar-picker-indicator { filter: invert(0.6); cursor: pointer; }

        .modal-backdrop {
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(8px);
        }

        .modal-enter {
            animation: modalIn 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes modalIn {
            from { opacity: 0; transform: scale(0.95) translateY(10px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        .backdrop-enter { animation: backdropIn 0.25s ease forwards; }
        .backdrop-leave { animation: backdropOut 0.2s ease forwards; }

        @keyframes backdropIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes backdropOut { from { opacity: 1; } to { opacity: 0; } }
    </style>
</head>

<script>
    document.getElementById('vehicle-selector')?.addEventListener('change', function () {
        const url = new URL(window.location.href);
        url.searchParams.set('vehicle_id', this.value);
        window.location.href = url.toString();
    });
</script>

<body x-data="summaryApp()" x-init="init()" :class="showEditModal ? 'overflow-hidden' : ''">

    @include('components.flash')
    @include('maintenance-manager.layout.sidebar')

    <main :class="open ? 'ml-72' : 'ml-20'"
        class="sidebar-transition p-8 md:p-12 min-h-screen">

        <div class="max-w-[1600px] mx-auto">

            @if(!$vehicle)
                <div class="flex flex-col items-center justify-center py-32">
                    <div class="w-16 h-16 rounded-2xl bg-white/[0.03] border border-white/5 flex items-center justify-center text-white/15 mb-4">
                        <i class="fa-solid fa-car text-2xl"></i>
                    </div>
                    <p class="text-white/30 text-sm mb-1">No vehicles registered yet.</p>
                    <p class="text-white/15 text-xs">Add a vehicle first to view maintenance summaries.</p>
                </div>
            @else

            <!-- Page Header -->
            <header class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
                <div>
                    <h2 class="text-3xl font-black tracking-tight mb-1">Fleet Maintenance <span class="text-blue-500">Summary</span></h2>
                    <p class="text-white/40 text-sm">Complete maintenance history, costs, and schedule for this vehicle.</p>
                </div>
                <div class="flex gap-3 self-start ml-8 md:ml-0">
                    <select id="vehicle-selector"
                        class="bg-[#0a0a0a] border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:ring-1 focus:ring-blue-500/50 outline-none appearance-none cursor-pointer pr-10">
                        @foreach($vehicles as $v)
                            <option value="{{ $v->id }}" {{ $vehicle->id === $v->id ? 'selected' : '' }}>
                                {{ $v->plate_number }} — {{ $v->brand }} {{ $v->model }}
                            </option>
                        @endforeach
                    </select>
                    <button class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-sm font-semibold shadow-lg shadow-blue-900/40 transition-all">
                        <i class="fa-solid fa-download mr-2"></i>Export
                    </button>
                </div>
            </header>

            <!-- ═══════════════════════════════════════════ -->
            <!-- VEHICLE INFORMATION CARD                   -->
            <!-- ═══════════════════════════════════════════ -->
            <div class="glass rounded-2xl p-6 md:p-8 border border-white/5 mb-6">

                <div class="flex flex-col md:flex-row md:items-start justify-between gap-4 mb-6">
                    <div class="flex items-center gap-4">
                        <div>
                            <h3 class="text-lg font-bold tracking-tight">{{ $vehicle->year }} {{ $vehicle->brand }} {{ $vehicle->model }}</h3>
                            <div class="flex items-center gap-3 mt-1">
                                <span class="font-mono text-xs text-white/50 bg-white/5 px-2 py-0.5 rounded">{{ $vehicle->plate_number }}</span>
                                @if($vehicle->status === 'active')
                                    <span class="inline-flex items-center gap-1.5 text-[10px] font-bold text-emerald-400 bg-emerald-500/10 px-2.5 py-0.5 rounded-full border border-emerald-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>Active
                                    </span>
                                @elseif($vehicle->status === 'maintenance')
                                    <span class="inline-flex items-center gap-1.5 text-[10px] font-bold text-amber-400 bg-amber-500/10 px-2.5 py-0.5 rounded-full border border-amber-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>Maintenance
                                    </span>
                                @elseif($vehicle->status === 'inactive')
                                    <span class="inline-flex items-center gap-1.5 text-[10px] font-bold text-rose-400/70 bg-rose-500/[0.06] px-2.5 py-0.5 rounded-full border border-rose-500/10">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-400/70"></span>Inactive
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 text-[10px] font-bold text-white/20 bg-white/[0.03] px-2.5 py-0.5 rounded-full border border-white/[0.06]">
                                        <span class="w-1.5 h-1.5 rounded-full bg-white/15"></span>{{ ucfirst($vehicle->status) }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="flex gap-2 shrink-0">
                        <button @click="openEditModal()" class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white/[0.03] border border-white/5 hover:bg-white/[0.06] transition-all text-sm text-white/60 font-semibold">
                            <i class="fa-solid fa-pen-to-square text-xs"></i> Edit
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-x-6 gap-y-4 text-sm border-t border-white/5 pt-5">
                    <div>
                        <span class="text-[10px] uppercase font-bold text-white/25 block mb-1">Fuel Type</span>
                        <span class="text-white/70 font-medium text-sm">{{ $vehicle->fuel_type || '—' }}</span>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase font-bold text-white/25 block mb-1">Tank Capacity</span>
                        <span class="text-white/70 font-medium text-sm">{{ $vehicle->tank_capacity ? $vehicle->tank_capacity . ' L' : '—' }}</span>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase font-bold text-white/25 block mb-1">Driver</span>
                        <span class="text-white/70 font-medium text-sm">{{ $vehicle->driver?->name ?? 'Unassigned' }}</span>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase font-bold text-white/25 block mb-1">Location</span>
                        <span class="text-white/70 font-medium text-sm">{{ $vehicle->location || '—' }}</span>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase font-bold text-white/25 block mb-1">Acquired</span>
                        <span class="text-white/50 text-xs">{{ $vehicle->acquistion_date ? $vehicle->acquistion_date->format('M d, Y') : '—' }}</span>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase font-bold text-white/25 block mb-1">Disposal</span>
                        <span class="text-white/50 text-xs">{{ $vehicle->exp_disposal_date ? $vehicle->exp_disposal_date->format('M d, Y') : '—' }}</span>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mt-4 pt-4 border-t border-white/5">
                    <div>
                        <span class="text-[10px] uppercase font-bold text-white/25 mr-2">VIN</span>
                        <span class="text-white/40 font-mono text-xs">{{ $vehicle->vin || '—' }}</span>
                    </div>
                    <span class="text-white/30 text-xs">Last updated: <span class="text-white/50">{{ $vehicle->updated_at->format('M d, Y') }}</span></span>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════ -->
            <!-- STAT CARDS                                 -->
            <!-- ═══════════════════════════════════════════ -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                <div class="glass rounded-2xl p-5 border border-white/5">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-[10px] uppercase font-black text-white/25 tracking-widest">Annual Mileage</span>
                        <div class="w-8 h-8 rounded-lg bg-white/5 flex items-center justify-center">
                            <i class="fa-solid fa-road text-white/30 text-xs"></i>
                        </div>
                    </div>
                    <p class="text-3xl font-black tracking-tight">{{ number_format($annualMileage) }}</p>
                    <p class="text-xs text-white/30 mt-1">miles driven in {{ $year }}</p>
                </div>
                <div class="glass rounded-2xl p-5 border border-white/5">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-[10px] uppercase font-black text-white/25 tracking-widest">Service Cost / Mile</span>
                        <div class="w-8 h-8 rounded-lg bg-emerald-500/10 flex items-center justify-center">
                            <i class="fa-solid fa-gauge text-emerald-400/60 text-xs"></i>
                        </div>
                    </div>
                    <p class="text-3xl font-black tracking-tight text-emerald-400">₱{{ number_format($costPerMile, 2) }}</p>
                    <p class="text-xs text-white/30 mt-1">per mile average (YTD)</p>
                </div>
                <div class="glass rounded-2xl p-5 border border-white/5">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-[10px] uppercase font-black text-white/25 tracking-widest">Total Service Cost</span>
                        <div class="w-8 h-8 rounded-lg bg-blue-500/10 flex items-center justify-center">
                            <i class="fa-solid fa-coins text-blue-400/60 text-xs"></i>
                        </div>
                    </div>
                    <p class="text-3xl font-black tracking-tight text-blue-400">₱{{ number_format($totalServiceCost, 2) }}</p>
                    <p class="text-xs text-white/30 mt-1">{{ $year }} year-to-date expenses</p>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════ -->
            <!-- TABS                                       -->
            <!-- ═══════════════════════════════════════════ -->
            <div>
                <div class="flex gap-6 border-b border-white/5 mb-6">
<button @click="activeTab = 'cpm'" :class="activeTab === 'cpm' ? 'tab-active' : 'tab-inactive'" class="pb-3 text-sm font-semibold transition-all hover:text-white/60">
    <i class="fa-solid fa-gauge-high mr-2 text-xs"></i>Cost Per Mile Summary
</button>
                    <button @click="activeTab = 'cost'" :class="activeTab === 'cost' ? 'tab-active' : 'tab-inactive'" class="pb-3 text-sm font-semibold transition-all hover:text-white/60">
                        <i class="fa-solid fa-table-columns mr-2 text-xs"></i>Maintenance Cost Summary
                    </button>
                    <button @click="activeTab = 'schedule'" :class="activeTab === 'schedule' ? 'tab-active' : 'tab-inactive'" class="pb-3 text-sm font-semibold transition-all hover:text-white/60">
                        <i class="fa-solid fa-calendar-days mr-2 text-xs"></i>Preventive Maintenance Schedule
                    </button>
                    <button @click="activeTab = 'log'" :class="activeTab === 'log' ? 'tab-active' : 'tab-inactive'" class="pb-3 text-sm font-semibold transition-all hover:text-white/60">
                        <i class="fa-solid fa-list-check mr-2 text-xs"></i>Vehicle Maintenance Log
                    </button>
                </div>

<!-- ─────────────────────────────────────── -->
<!-- TAB: COST PER MILE SUMMARY               -->
<!-- ─────────────────────────────────────── -->
<div x-show="activeTab === 'cpm'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
    <div class="glass rounded-2xl border border-white/5 overflow-hidden flex flex-col">
        <div class="px-6 py-3.5 border-b border-white/5 bg-white/[0.02] flex justify-between items-center">
            <h3 class="font-bold text-white text-sm">Cost Per Mile Summary — {{ $year }}</h3>
            <span class="text-[10px] text-white/20 uppercase tracking-wider font-bold">Scroll to view months <i class="fa-solid fa-arrow-right text-[8px] ml-1"></i></span>
        </div>
        <div class="overflow-x-auto table-scroll flex-1">
            <table class="w-full text-left whitespace-nowrap">
                <thead>
                    <tr class="border-b border-white/5 bg-[#0a0a0a]">
                        <th class="px-4 py-3 text-[10px] font-black uppercase tracking-widest text-blue-500 sticky left-0 bg-[#0a0a0a] z-10 text-left min-w-[180px]">Metric</th>
                        @for($m = 1; $m <= 12; $m++)
                            <th class="px-4 py-3 text-[10px] font-bold text-white/30 text-right w-20">{{ ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'][$m - 1] }}</th>
                        @endfor
                        <th class="px-4 py-3 text-[10px] font-bold text-blue-400 text-right w-24 border-l border-white/10">YTD</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5 text-sm">
                    <!-- Starting Mileage -->
                    <tr class="hover:bg-white/[0.02]">
                        <td class="px-4 py-2.5 text-white/40 sticky left-0 bg-[#050505]">
                            <i class="fa-solid fa-flag-checkered text-white/20 text-[10px] mr-2 w-4 text-center"></i>Starting Mileage
                        </td>
                        @for($m = 1; $m <= 12; $m++)
                            <td class="px-4 py-2.5 font-mono text-right @if($monthlyStartOdo[$m] !== null) text-white/40 @else text-white/15 @endif">
                                @if($monthlyStartOdo[$m] !== null)
                                    {{ number_format($monthlyStartOdo[$m]) }}
                                @else
                                    —
                                @endif
                            </td>
                        @endfor>
                        <td class="px-4 py-2.5 font-mono text-right text-white/50 border-l border-white/10">
                            {{ $yearStartOdo !== null ? number_format($yearStartOdo) : '—' }}
                        </td>
                    </tr>

                    <!-- Ending Mileage -->
                    <tr class="hover:bg-white/[0.02]">
                        <td class="px-4 py-2.5 text-white/40 sticky left-0 bg-[#050505]">
                            <i class="fa-solid fa-flag text-white/20 text-[10px] mr-2 w-4 text-center"></i>Ending Mileage
                        </td>
                        @for($m = 1; $m <= 12; $m++)
                            <td class="px-4 py-2.5 font-mono text-right @if($monthlyEndOdo[$m] !== null) text-white/50 @else text-white/15 @endif">
                                @if($monthlyEndOdo[$m] !== null)
                                    {{ number_format($monthlyEndOdo[$m]) }}
                                @else
                                    —
                                @endif
                            </td>
                        @endfor>
                        <td class="px-4 py-2.5 font-mono text-right text-white/50 border-l border-white/10">
                            {{ $yearEndOdo ? number_format($yearEndOdo) : '—' }}
                        </td>
                    </tr>

                    <!-- Monthly Mileage -->
                    <tr class="hover:bg-white/[0.02]">
                        <td class="px-4 py-2.5 text-white/60 sticky left-0 bg-[#050505]">
                            <i class="fa-solid fa-road text-white/20 text-[10px] mr-2 w-4 text-center"></i>Monthly Mileage
                        </td>
                        @for($m = 1; $m <= 12; $m++)
                            <td class="px-4 py-2.5 font-mono text-right @if($monthlyMileage[$m] > 0) text-white/60 @else text-white/15 @endif">
                                @if($monthlyMileage[$m] > 0)
                                    {{ number_format($monthlyMileage[$m]) }}
                                @else
                                    —
                                @endif
                            </td>
                        @endfor>
                        <td class="px-4 py-2.5 font-mono text-right font-bold text-white/70 border-l border-white/10">
                            {{ number_format($annualMileage) }}
                        </td>
                    </tr>

                    <!-- Service Cost / Mile -->
                    <tr class="hover:bg-white/[0.02]">
    <td class="px-4 py-2.5 text-white/60 sticky left-0 bg-[#050505]">
        <i class="fa-solid fa-gauge text-emerald-400/50 text-[10px] mr-2 w-4 text-center"></i>Service Cost / Mile
    </td>
    @for($m = 1; $m <= 12; $m++)
        <td class="px-4 py-2.5 font-mono text-right @if($monthlyCpm[$m] !== null) text-emerald-400/80 @else text-white/15 @endif">
            @if($monthlyCpm[$m] !== null)
                ₱{{ number_format($monthlyCpm[$m], 2) }}
            @else
                —
            @endif
        </td>
    @endfor>
    <td class="px-4 py-2.5 font-mono text-right font-bold text-emerald-400 border-l border-white/10">
        ₱{{ number_format($costPerMile, 2) }}
    </td>
</tr>

                    <!-- Total Service Cost -->
                    <tr class="bg-blue-500/[0.03] border-t-2 border-blue-500/20">
                        <td class="px-4 py-3.5 text-blue-400 font-bold sticky left-0 bg-blue-500/[0.03]">
                            <i class="fa-solid fa-coins text-xs mr-2"></i>Total Service Cost
                        </td>
                        @for($m = 1; $m <= 12; $m++)
                            <td class="px-4 py-3.5 font-mono text-right font-bold @if($monthlyTotals[$m] > 0) text-blue-400 @else text-white/20 @endif">
                                ₱{{ number_format($monthlyTotals[$m], 2) }}
                            </td>
                        @endfor
                        <td class="px-4 py-3.5 font-mono text-right font-bold text-blue-400 border-l border-white/10">
                            ₱{{ number_format($ytdTotal, 2) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="px-6 py-3 border-t border-white/5 bg-white/[0.01] flex flex-col sm:flex-row justify-between gap-2 text-xs">
            <div class="flex items-center gap-4">
                <span class="text-white/25 uppercase font-bold tracking-wider">Annual Mileage</span>
                <span class="text-white font-bold font-mono text-sm">{{ number_format($annualMileage) }} mi</span>
            </div>
            <div class="flex items-center gap-4">
                <span class="text-white/25 uppercase font-bold tracking-wider">Avg Cost / Mile</span>
                <span class="text-emerald-400 font-bold font-mono text-sm">₱{{ number_format($costPerMile, 2) }}</span>
            </div>
        </div>
    </div>
</div>

                <!-- ─────────────────────────────────────── -->
                <!-- TAB 1: COST SUMMARY                     -->
                <!-- ─────────────────────────────────────── -->
                <div x-show="activeTab === 'cost'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
                    <div class="glass rounded-2xl border border-white/5 overflow-hidden flex flex-col">
                        <div class="px-6 py-3.5 border-b border-white/5 bg-white/[0.02] flex justify-between items-center">
                            <h3 class="font-bold text-white text-sm">Maintenance Cost Summary — {{ $year }}</h3>
                            <span class="text-[10px] text-white/20 uppercase tracking-wider font-bold">Scroll to view months <i class="fa-solid fa-arrow-right text-[8px] ml-1"></i></span>
                        </div>
                        <div class="overflow-x-auto table-scroll flex-1">
                            <table class="w-full text-left whitespace-nowrap">
                                <thead>
                                    <tr class="border-b border-white/5 bg-[#0a0a0a]">
                                        <th class="px-4 py-3 text-[10px] font-black uppercase tracking-widest text-blue-500 sticky left-0 bg-[#0a0a0a] z-10 text-left min-w-[200px]">Category / Item</th>
                                        @for($m = 1; $m <= 12; $m++)
                                            <th class="px-4 py-3 text-[10px] font-bold text-white/30 text-right w-20">{{ ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'][$m - 1] }}</th>
                                        @endfor
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-white/5 text-sm">
                                    @forelse($costSummary as $taskName => $months)
                                        <tr class="hover:bg-white/[0.02]">
                                            <td class="px-4 py-2.5 text-white/60 sticky left-0 bg-[#050505]">{{ $taskName }}</td>
                                            @for($m = 1; $m <= 12; $m++)
                                                <td class="px-4 py-2.5 font-mono text-right @if($months[$m] > 0) text-white/50 @else text-white/15 @endif">
                                                    @if($months[$m] > 0)
                                                        ₱{{ number_format($months[$m], 2) }}
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                            @endfor
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="13" class="px-4 py-12 text-center text-white/25 text-sm">No maintenance records for {{ $year }}.</td>
                                        </tr>
                                    @endforelse

                                    @if($costSummary->isNotEmpty())
                                    <tr class="bg-blue-500/[0.03] border-t-2 border-blue-500/20">
                                        <td class="px-4 py-3.5 text-blue-400 font-bold sticky left-0 bg-blue-500/[0.03]">
                                            <i class="fa-solid fa-calculator mr-2 text-xs"></i>Total Monthly
                                        </td>
                                        @for($m = 1; $m <= 12; $m++)
                                            <td class="px-4 py-3.5 font-mono text-right font-bold @if($monthlyTotals[$m] > 0) text-blue-400 @else text-white/20 @endif">
                                                ₱{{ number_format($monthlyTotals[$m], 2) }}
                                            </td>
                                        @endfor
                                    </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                        <div class="px-6 py-3 border-t border-white/5 bg-white/[0.01] flex flex-col sm:flex-row justify-between gap-2 text-xs">
                            <span class="text-white/25 uppercase font-bold tracking-wider">YTD Total</span>
                            <span class="text-white font-bold font-mono text-sm">₱{{ number_format($ytdTotal, 2) }}</span>
                        </div>
                    </div>
                </div>

                <!-- ─────────────────────────────────────── -->
                <!-- TAB 2: PREVENTIVE MAINTENANCE SCHEDULE  -->
                <!-- ─────────────────────────────────────── -->
                <div x-show="activeTab === 'schedule'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
                    <div class="glass rounded-2xl border border-white/5 overflow-hidden">
                        <div class="px-6 py-3.5 border-b border-white/5 bg-white/[0.02] flex justify-between items-center">
                            <h3 class="font-bold text-white text-sm">Preventive Maintenance Schedule</h3>
                            <span class="text-[10px] text-white/20 uppercase tracking-wider font-bold">Based on mileage intervals</span>
                        </div>
                        <div class="p-6 md:p-8">
                            <div class="space-y-6">

                                <!-- 10,000 Miles -->
                                <div class="flex gap-5">
                                    <div class="shrink-0 flex flex-col items-center">
                                        <div class="w-6 h-6 rounded-full bg-blue-500/20 border-2 border-blue-500/40 flex items-center justify-center">
                                            <div class="w-2 h-2 rounded-full bg-blue-400"></div>
                                        </div>
                                    </div>
                                    <div class="flex-1 pb-2">
                                        <div class="flex items-center gap-3 mb-3">
                                            <span class="text-sm font-bold text-white">10,000 Miles</span>
                                            <span class="text-[10px] font-bold text-blue-400 bg-blue-500/10 px-2 py-0.5 rounded-full border border-blue-500/20">Every 10K</span>
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2">
                                            <div class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg bg-white/[0.02] border border-white/5"><i class="fa-solid fa-rotate text-white/25 text-[10px] w-4 text-center"></i><span class="text-xs text-white/60">Tire Rotation</span></div>
                                            <div class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg bg-white/[0.02] border border-white/5"><i class="fa-solid fa-oil-can text-white/25 text-[10px] w-4 text-center"></i><span class="text-xs text-white/60">Oil & Filter Change</span></div>
                                            <div class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg bg-white/[0.02] border border-white/5"><i class="fa-solid fa-wind text-white/25 text-[10px] w-4 text-center"></i><span class="text-xs text-white/60">Air Filter Inspection</span></div>
                                            <div class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg bg-white/[0.02] border border-white/5"><i class="fa-solid fa-droplet text-white/25 text-[10px] w-4 text-center"></i><span class="text-xs text-white/60">Fluid Level Check</span></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- 20,000 Miles -->
                                <div class="flex gap-5">
                                    <div class="shrink-0 flex flex-col items-center">
                                        <div class="w-6 h-6 rounded-full bg-amber-500/20 border-2 border-amber-500/40 flex items-center justify-center">
                                            <div class="w-2 h-2 rounded-full bg-amber-400"></div>
                                        </div>
                                    </div>
                                    <div class="flex-1 pb-2">
                                        <div class="flex items-center gap-3 mb-3">
                                            <span class="text-sm font-bold text-white">20,000 Miles</span>
                                            <span class="text-[10px] font-bold text-amber-400 bg-amber-500/10 px-2 py-0.5 rounded-full border border-amber-500/20">Every 20K</span>
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2">
                                            <div class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg bg-white/[0.02] border border-white/5"><i class="fa-solid fa-circle-dot text-white/25 text-[10px] w-4 text-center"></i><span class="text-xs text-white/60">Tire Replacement</span></div>
                                            <div class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg bg-white/[0.02] border border-white/5"><i class="fa-solid fa-oil-can text-white/25 text-[10px] w-4 text-center"></i><span class="text-xs text-white/60">Oil & Filter Change</span></div>
                                            <div class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg bg-white/[0.02] border border-white/5"><i class="fa-solid fa-wind text-white/25 text-[10px] w-4 text-center"></i><span class="text-xs text-white/60">Air Filter Replacement</span></div>
                                            <div class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg bg-white/[0.02] border border-white/5"><i class="fa-solid fa-droplet text-white/25 text-[10px] w-4 text-center"></i><span class="text-xs text-white/60">Coolant Flush</span></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- 30,000 Miles -->
                                <div class="flex gap-5">
                                    <div class="shrink-0 flex flex-col items-center">
                                        <div class="w-6 h-6 rounded-full bg-blue-500/20 border-2 border-blue-500/40 flex items-center justify-center">
                                            <div class="w-2 h-2 rounded-full bg-blue-400"></div>
                                        </div>
                                    </div>
                                    <div class="flex-1 pb-2">
                                        <div class="flex items-center gap-3 mb-3">
                                            <span class="text-sm font-bold text-white">30,000 Miles</span>
                                            <span class="text-[10px] font-bold text-blue-400 bg-blue-500/10 px-2 py-0.5 rounded-full border border-blue-500/20">Every 30K</span>
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2">
                                            <div class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg bg-white/[0.02] border border-white/5"><i class="fa-solid fa-rotate text-white/25 text-[10px] w-4 text-center"></i><span class="text-xs text-white/60">Tire Rotation</span></div>
                                            <div class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg bg-white/[0.02] border border-white/5"><i class="fa-solid fa-oil-can text-white/25 text-[10px] w-4 text-center"></i><span class="text-xs text-white/60">Oil & Filter Change</span></div>
                                            <div class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg bg-white/[0.02] border border-white/5"><i class="fa-solid fa-gears text-white/25 text-[10px] w-4 text-center"></i><span class="text-xs text-white/60">Transmission Fluid</span></div>
                                            <div class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg bg-white/[0.02] border border-white/5"><i class="fa-solid fa-car text-white/25 text-[10px] w-4 text-center"></i><span class="text-xs text-white/60">Brake Inspection</span></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- 50,000 Miles -->
                                <div class="flex gap-5">
                                    <div class="shrink-0 flex flex-col items-center">
                                        <div class="w-6 h-6 rounded-full bg-rose-500/20 border-2 border-rose-500/40 flex items-center justify-center">
                                            <div class="w-2 h-2 rounded-full bg-rose-400"></div>
                                        </div>
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3 mb-3">
                                            <span class="text-sm font-bold text-white">50,000 Miles</span>
                                            <span class="text-[10px] font-bold text-rose-400 bg-rose-500/10 px-2 py-0.5 rounded-full border border-rose-500/20">Major Service</span>
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2">
                                            <div class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg bg-white/[0.02] border border-white/5"><i class="fa-solid fa-circle-dot text-white/25 text-[10px] w-4 text-center"></i><span class="text-xs text-white/60">Tire Replacement</span></div>
                                            <div class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg bg-white/[0.02] border border-white/5"><i class="fa-solid fa-oil-can text-white/25 text-[10px] w-4 text-center"></i><span class="text-xs text-white/60">Oil & Filter Change</span></div>
                                            <div class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg bg-white/[0.02] border border-white/5"><i class="fa-solid fa-car text-white/25 text-[10px] w-4 text-center"></i><span class="text-xs text-white/60">Brake Pad Replacement</span></div>
                                            <div class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg bg-white/[0.02] border border-white/5"><i class="fa-solid fa-bolt text-white/25 text-[10px] w-4 text-center"></i><span class="text-xs text-white/60">Spark Plug Replacement</span></div>
                                            <div class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg bg-white/[0.02] border border-white/5"><i class="fa-solid fa-gears text-white/25 text-[10px] w-4 text-center"></i><span class="text-xs text-white/60">Transmission Service</span></div>
                                            <div class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg bg-white/[0.02] border border-white/5"><i class="fa-solid fa-droplet text-white/25 text-[10px] w-4 text-center"></i><span class="text-xs text-white/60">Coolant Flush</span></div>
                                            <div class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg bg-white/[0.02] border border-white/5"><i class="fa-solid fa-wind text-white/25 text-[10px] w-4 text-center"></i><span class="text-xs text-white/60">Air Filter Replacement</span></div>
                                            <div class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg bg-white/[0.02] border border-white/5"><i class="fa-solid fa-magnifying-glass text-white/25 text-[10px] w-4 text-center"></i><span class="text-xs text-white/60">Full Inspection</span></div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

                <!-- ─────────────────────────────────────── -->
                <!-- TAB 3: VEHICLE MAINTENANCE LOG           -->
                <!-- ─────────────────────────────────────── -->
                <div x-show="activeTab === 'log'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
                    <div class="glass rounded-2xl border border-white/5 overflow-hidden">
                        <div class="px-6 py-3.5 border-b border-white/5 bg-white/[0.02] flex justify-between items-center">
                            <h3 class="font-bold text-white text-sm">Vehicle Maintenance Log</h3>
                            <span class="text-[10px] text-white/20 uppercase tracking-wider font-bold">{{ $allLogs->count() }} {{ $allLogs->count() === 1 ? 'entry' : 'entries' }}</span>
                        </div>
                        <div class="max-h-[600px] overflow-y-auto log-scroll">

                            @forelse($allLogs as $log)
                            <div class="px-6 py-5 border-b border-white/5 hover:bg-white/[0.01] transition-colors">
                                <div class="flex gap-5">
                                    <div class="shrink-0 flex flex-col items-center pt-1">
                                        <div class="w-[30px] h-[30px] rounded-full bg-emerald-500/15 border border-emerald-500/30 flex items-center justify-center">
                                            <i class="fa-solid fa-check text-emerald-400 text-[10px]"></i>
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-2">
                                            <span class="text-sm font-bold text-white">{{ $log['task_name'] }}</span>
                                            <span class="text-[10px] text-white/30 font-mono shrink-0">{{ $log['service_date'] ?? '—' }}</span>
                                        </div>
                                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-x-6 gap-y-2 text-xs">
                                            <div>
                                                <span class="text-white/25 uppercase font-bold text-[10px] block">Mileage</span>
                                                <span class="text-white/60 font-mono">{{ number_format($log['mileage']) }} mi</span>
                                            </div>
                                            <div>
                                                <span class="text-white/25 uppercase font-bold text-[10px] block">Cost</span>
                                                <span class="text-white/60 font-mono">₱{{ number_format($log['cost'], 2) }}</span>
                                            </div>
                                            <div>
                                                <span class="text-white/25 uppercase font-bold text-[10px] block">Service Provider</span>
                                                <span class="text-white/60">{{ $log['performed_by'] ?? '—' }}</span>
                                            </div>
                                            <div>
                                                <span class="text-white/25 uppercase font-bold text-[10px] block">Remarks</span>
                                                <span class="text-white/40 truncate block max-w-[200px]" title="{{ $log['remarks'] ?? '' }}">{{ $log['remarks'] ?? '—' }}</span>
                                            </div>
                                        </div>
                                        @if($log['invoice_number'])
                                        <div class="mt-2">
                                            <span class="text-white/25 uppercase font-bold text-[10px]">Invoice:</span>
                                            <span class="text-white/40 font-mono text-[11px] ml-1.5">{{ $log['invoice_number'] }}</span>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @empty
                            <div class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="w-14 h-14 rounded-2xl bg-white/[0.03] border border-white/5 flex items-center justify-center text-white/15">
                                        <i class="fa-solid fa-clipboard-list text-xl"></i>
                                    </div>
                                    <p class="text-sm text-white/30">No maintenance logs recorded yet.</p>
                                </div>
                            </div>
                            @endforelse

                        </div>
                    </div>
                </div>

            </div>

            @endif
        </div>
    </main>

    <!-- ==================== EDIT VEHICLE MODAL ==================== -->
    <template x-teleport="body">
        <div x-show="showEditModal" x-cloak @keydown.escape.window="closeEditModal()"
            class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
            x-transition:enter="backdrop-enter" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="backdrop-leave" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

            <div class="modal-backdrop absolute inset-0" @click="closeEditModal()"></div>

            <form method="POST"
                  :action="`{{ route('vehicles.update', 0) }}`.replace('/0', '/' + editForm.id)"
                  x-show="showEditModal"
                  @click.stop
                  class="relative w-full max-w-xl rounded-2xl border border-white/[0.08] shadow-2xl shadow-black/60 modal-enter overflow-hidden"
                  style="background: linear-gradient(165deg, rgba(20,20,25,0.98) 0%, rgba(10,10,12,0.99) 100%);">

                @csrf
                @method('PATCH')

                <div class="absolute top-0 left-1/2 -translate-x-1/2 w-48 h-px bg-gradient-to-r from-transparent via-blue-500/60 to-transparent"></div>

                <div class="flex items-center justify-between px-7 pt-7 pb-2">
                    <div>
                        <h3 class="text-xl font-black tracking-tight">Edit Vehicle</h3>
                        <p class="text-white/30 text-xs mt-1">Update vehicle details.</p>
                    </div>
                    <button type="button" @click="closeEditModal()" class="w-9 h-9 rounded-lg bg-white/5 hover:bg-white/10 border border-white/5 flex items-center justify-center transition-all active:scale-90">
                        <i class="fa-solid fa-xmark text-white/40 text-sm"></i>
                    </button>
                </div>

                <div class="mx-7 my-4 h-px bg-white/[0.06]"></div>

                <div class="px-7 pb-2 space-y-5 max-h-[65vh] overflow-y-auto pr-2" style="scrollbar-width: thin; scrollbar-color: rgba(255,255,255,0.1) transparent;">

                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-white/20">Vehicle Information</p>
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-[0.15em] text-white/40 mb-2">Year</label>
                            <input type="number" name="year" x-model="editForm.year" min="1990" max="2030" class="w-full px-4 py-2.5 rounded-xl text-sm font-mono" required>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-[0.15em] text-white/40 mb-2">Brand</label>
                            <input type="text" name="brand" x-model="editForm.brand" class="w-full px-4 py-2.5 rounded-xl text-sm" required>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-[0.15em] text-white/40 mb-2">Model</label>
                            <input type="text" name="model" x-model="editForm.model" class="w-full px-4 py-2.5 rounded-xl text-sm" required>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-[0.15em] text-white/40 mb-2">Plate Number</label>
                            <input type="text" name="plate_number" x-model="editForm.plate_number" class="w-full px-4 py-2.5 rounded-xl text-sm font-mono" required>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-[0.15em] text-white/40 mb-2">VIN</label>
                            <input type="text" name="vin" x-model="editForm.vin" class="w-full px-4 py-2.5 rounded-xl text-sm font-mono">
                        </div>
                    </div>

                    <div class="mx-0 my-2 h-px bg-white/[0.04]"></div>
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-white/20">Specifications & Assignment</p>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-[0.15em] text-white/40 mb-2">Fuel Type</label>
                            <select name="fuel_type" x-model="editForm.fuel_type" class="w-full px-4 py-2.5 rounded-xl text-sm">
                                <option value="" class="bg-[#111]">Select fuel type</option>
                                <option value="Diesel" class="bg-[#111]">Diesel</option>
                                <option value="Gasoline" class="bg-[#111]">Gasoline</option>
                                <option value="Electric" class="bg-[#111]">Electric</option>
                                <option value="Hybrid" class="bg-[#111]">Hybrid</option>
                                <option value="LPG" class="bg-[#111]">LPG</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-[0.15em] text-white/40 mb-2">Tank Capacity (L)</label>
                            <input type="text" name="tank_capacity" x-model="editForm.tank_capacity" class="w-full px-4 py-2.5 rounded-xl text-sm font-mono">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-[0.15em] text-white/40 mb-2">Driver</label>
                            <select name="driver_id" x-model="editForm.driver_id" class="w-full px-4 py-2.5 rounded-xl text-sm">
                                <option value="" class="bg-[#111]">Unassigned</option>
                                @foreach($drivers as $driver)
                                    <option value="{{ $driver->id }}" class="bg-[#111]">{{ $driver->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-[0.15em] text-white/40 mb-2">Location</label>
                            <input type="text" name="location" x-model="editForm.location" class="w-full px-4 py-2.5 rounded-xl text-sm">
                        </div>
                    </div>

                    <div class="mx-0 my-2 h-px bg-white/[0.04]"></div>
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-white/20">Dates & Status</p>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-[0.15em] text-white/40 mb-2">Acquisition Date</label>
                            <input type="date" name="acquistion_date" x-model="editForm.acquistion_date" class="w-full px-4 py-2.5 rounded-xl text-sm font-mono" required>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-[0.15em] text-white/40 mb-2">Status</label>
                            <select name="status" x-model="editForm.status" class="w-full px-4 py-2.5 rounded-xl text-sm">
                                <option value="active" class="bg-[#111]">Active</option>
                                <option value="maintenance" class="bg-[#111]">Maintenance</option>
                                <option value="inactive" class="bg-[#111]">Inactive</option>
                                <option value="disposed" class="bg-[#111]">Disposed</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-[0.15em] text-white/40 mb-2">Expected Disposal</label>
                            <input type="date" name="exp_disposal_date" x-model="editForm.exp_disposal_date" class="w-full px-4 py-2.5 rounded-xl text-sm font-mono">
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 px-7 py-6 mt-2">
                    <button type="button" @click="closeEditModal()" class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white/50 hover:text-white/80 hover:bg-white/5 border border-white/[0.06] transition-all active:scale-95">Cancel</button>
                    <button type="submit" class="px-6 py-2.5 rounded-xl text-sm font-bold transition-all active:scale-95 bg-blue-600 hover:bg-blue-500 shadow-lg shadow-blue-900/40 text-white">
                        <i class="fa-solid fa-check mr-2"></i>Update Vehicle
                    </button>
                </div>
            </form>
        </div>
    </template>

    <script>
    function summaryApp() {
        return {
            open: true,
            activeTab: 'cpm',
            showEditModal: false,
            editForm: {},

            init() {
                @if($vehicle)
                this.editForm = {
                    id: {{ $vehicle->id }},
                    year: '{{ $vehicle->year }}',
                    brand: '{{ $vehicle->brand }}',
                    model: '{{ $vehicle->model }}',
                    plate_number: '{{ $vehicle->plate_number }}',
                    vin: '{{ $vehicle->vin }}',
                    fuel_type: '{{ $vehicle->fuel_type }}',
                    tank_capacity: '{{ $vehicle->tank_capacity }}',
                    driver_id: '{{ $vehicle->driver_id }}',
                    location: '{{ $vehicle->location }}',
                    acquistion_date: '{{ $vehicle->acquistion_date ? $vehicle->acquistion_date->format('Y-m-d') : '' }}',
                    status: '{{ $vehicle->status }}',
                    exp_disposal_date: '{{ $vehicle->exp_disposal_date ? $vehicle->exp_disposal_date->format('Y-m-d') : '' }}',
                };
                @endif
            },

            openEditModal() {
                this.showEditModal = true;
            },

            closeEditModal() {
                this.showEditModal = false;
            }
        }
    }
    </script>

</body>
</html>
