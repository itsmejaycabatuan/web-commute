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
    </style>
</head>

<body x-data="{ open: true, activeTab: 'cost' }">

    @include('maintenance-manager.layout.sidebar');

    <main :class="open ? 'ml-72' : 'ml-20'" x-data="{ editing: false }"
        class="sidebar-transition p-8 md:p-12 min-h-screen">


  <div class="max-w-[1600px] mx-auto">

            <!-- Page Header -->
            <header class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8 fade-in">
                <div>
                    <div class="flex items-center gap-3 mb-1">
                        <h2 class=" text-3xl font-black tracking-tight">Fleet Maintenance <span
                                class="text-blue-500">Log</span></h2>
                    </div>
                    <p class="text-white/40 text-sm">Complete maintenance history, costs, and schedule for this
                        vehicle.</p>
                </div>
                <div class="flex gap-3 self-start ml-8 md:ml-0">
                    <select
                        class="bg-[#0a0a0a] border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:ring-1 focus:ring-blue-500/50 outline-none appearance-none cursor-pointer pr-10">
                        <option>ABC123 — Ford Transit</option>
                        <option>FLT012 — Ford Transit</option>
                    </select>
                    <button
                        class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-sm font-semibold shadow-lg shadow-blue-900/40 transition-all">
                        <i class="fa-solid fa-download mr-2"></i>Export
                    </button>
                </div>
            </header>

            <!-- ═══════════════════════════════════════════ -->
            <!-- VEHICLE INFORMATION CARD                   -->
            <!-- ═══════════════════════════════════════════ -->
            <div class="glass rounded-2xl p-6 md:p-8 border border-white/5 mb-6 fade-in" style="animation-delay: 0.05s">

                <!-- Vehicle Title Row -->
                <div class="flex flex-col md:flex-row md:items-start justify-between gap-4 mb-6">
                    <div class="flex items-center gap-4">

                        <div>
                            <h3 class="text-lg font-bold tracking-tight">2021 Ford Transit-350 Cargo</h3>
                            <div class="flex items-center gap-3 mt-1">
                                <span class="font-mono text-xs text-white/50 bg-white/5 px-2 py-0.5 rounded">ABC123
                                </span>
                                <span
                                    class="inline-flex items-center gap-1.5 text-[10px] font-bold text-emerald-400 bg-emerald-500/10 px-2.5 py-0.5 rounded-full border border-emerald-500/20">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                    Active
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="flex gap-2 shrink-0">

                        <a href="#"
                            class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white/[0.03] border border-white/5 hover:bg-white/[0.06] transition-all text-sm text-white/60 font-semibold">
                            <i class="fa-solid fa-pen-to-square text-xs"></i> Edit
                        </a>
                    </div>
                </div>

                <!-- Info Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-x-6 gap-y-4 text-sm border-t border-white/5 pt-5">
                    <div>
                        <span class="text-[10px] uppercase font-bold text-white/25 block mb-1">Fuel Type</span>
                        <span class="text-white/70 font-medium text-sm">Regular</span>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase font-bold text-white/25 block mb-1">Tank Capacity</span>
                        <span class="text-white/70 font-medium text-sm">25.1 gal</span>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase font-bold text-white/25 block mb-1">Operator</span>
                        <span class="text-white/70 font-medium text-sm">John Smith</span>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase font-bold text-white/25 block mb-1">Location</span>
                        <span class="text-white/70 font-medium text-sm">Atlanta</span>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase font-bold text-white/25 block mb-1">Acquired</span>
                        <span class="text-white/50 text-xs">Jan 1, 2022</span>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase font-bold text-white/25 block mb-1">Disposal</span>
                        <span class="text-white/50 text-xs">Jan 1, 2027</span>
                    </div>
                </div>

                <!-- VIN + Last Updated -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mt-4 pt-4 border-t border-white/5">
                    <div>
                        <span class="text-[10px] uppercase font-bold text-white/25 mr-2">VIN</span>
                        <span class="text-white/40 font-mono text-xs">2T1BR18E8XC165041</span>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="text-white/30 text-xs">Last updated: <span class="text-white/50">Dec 21,
                                2023</span></span>
                        <span class="text-xs">Next service: <span class="text-amber-400 font-medium">March 1,
                                2024</span></span>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════ -->
            <!-- COST PER MILE SUMMARY — STAT CARDS          -->
            <!-- ═══════════════════════════════════════════ -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6 fade-in" style="animation-delay: 0.1s">
                <div class="stat-card glass rounded-2xl p-5 border border-white/5">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-[10px] uppercase font-black text-white/25 tracking-widest">Annual
                            Mileage</span>
                        <div class="w-8 h-8 rounded-lg bg-white/5 flex items-center justify-center">
                            <i class="fa-solid fa-road text-white/30 text-xs"></i>
                        </div>
                    </div>
                    <p class="text-3xl font-black tracking-tight">85,000</p>
                    <p class="text-xs text-white/30 mt-1">miles driven this year</p>
                </div>
                <div class="stat-card glass rounded-2xl p-5 border border-white/5">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-[10px] uppercase font-black text-white/25 tracking-widest">Service Cost /
                            Mile</span>
                        <div class="w-8 h-8 rounded-lg bg-emerald-500/10 flex items-center justify-center">
                            <i class="fa-solid fa-gauge text-emerald-400/60 text-xs"></i>
                        </div>
                    </div>
                    <p class="text-3xl font-black tracking-tight text-emerald-400">₱0.02</p>
                    <p class="text-xs text-white/30 mt-1">per mile average</p>
                </div>
                <div class="stat-card glass rounded-2xl p-5 border border-white/5">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-[10px] uppercase font-black text-white/25 tracking-widest">Total Service
                            Cost</span>
                        <div class="w-8 h-8 rounded-lg bg-blue-500/10 flex items-center justify-center">
                            <i class="fa-solid fa-coins text-blue-400/60 text-xs"></i>
                        </div>
                    </div>
                    <p class="text-3xl font-black tracking-tight text-blue-400">₱1,600</p>
                    <p class="text-xs text-white/30 mt-1">year-to-date expenses</p>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════ -->
            <!-- TABS: COST TABLE / PM SCHEDULE / LOG       -->
            <!-- ═══════════════════════════════════════════ -->
            <div class="fade-in" style="animation-delay: 0.15s">
                <!-- Tab Bar -->
                <div class="flex gap-6 border-b border-white/5 mb-6">
                    <button @click="activeTab = 'cost'"
                        :class="activeTab === 'cost' ? 'tab-active' : 'tab-inactive'"
                        class="pb-3 text-sm font-semibold transition-all hover:text-white/60">
                        <i class="fa-solid fa-table-columns mr-2 text-xs"></i>Maintenance Cost Summary
                    </button>
                    <button @click="activeTab = 'schedule'"
                        :class="activeTab === 'schedule' ? 'tab-active' : 'tab-inactive'"
                        class="pb-3 text-sm font-semibold transition-all hover:text-white/60">
                        <i class="fa-solid fa-calendar-days mr-2 text-xs"></i>Preventive Maintenance Schedule
                    </button>
                    <button @click="activeTab = 'log'"
                        :class="activeTab === 'log' ? 'tab-active' : 'tab-inactive'"
                        class="pb-3 text-sm font-semibold transition-all hover:text-white/60">
                        <i class="fa-solid fa-list-check mr-2 text-xs"></i>Vehicle Maintenance Log
                    </button>
                </div>

                <!-- ─────────────────────────────────────── -->
                <!-- TAB 1: MAINTENANCE COST SUMMARY TABLE   -->
                <!-- ─────────────────────────────────────── -->
                <div x-show="activeTab === 'cost'" x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 -translate-y-2"
                    x-transition:enter-end="opacity-100 translate-y-0">
                    <div class="glass rounded-2xl border border-white/5 overflow-hidden flex flex-col">
                        <div
                            class="px-6 py-3.5 border-b border-white/5 bg-white/[0.02] flex justify-between items-center">
                            <h3 class="font-bold text-white text-sm">Maintenance Cost Summary — 2024</h3>
                            <span
                                class="text-[10px] text-white/20 uppercase tracking-wider font-bold">Scroll to view
                                months <i class="fa-solid fa-arrow-right text-[8px] ml-1"></i></span>
                        </div>
                        <div class="overflow-x-auto table-scroll flex-1">
                            <table class="w-full text-left whitespace-nowrap">
                                <thead>
                                    <tr class="border-b border-white/5 bg-[#0a0a0a]">
                                        <th
                                            class="px-4 py-3 text-[10px] font-black uppercase tracking-widest text-blue-500 sticky left-0 bg-[#0a0a0a] z-10 text-left min-w-[160px]">
                                            Category / Item</th>
                                        <th class="px-4 py-3 text-[10px] font-bold text-white/30 text-right w-20">Jan
                                        </th>
                                        <th class="px-4 py-3 text-[10px] font-bold text-white/30 text-right w-20">Feb
                                        </th>
                                        <th class="px-4 py-3 text-[10px] font-bold text-white/30 text-right w-20">Mar
                                        </th>
                                        <th class="px-4 py-3 text-[10px] font-bold text-white/20 text-right w-20">Apr
                                        </th>
                                        <th class="px-4 py-3 text-[10px] font-bold text-white/20 text-right w-20">May
                                        </th>
                                        <th class="px-4 py-3 text-[10px] font-bold text-white/20 text-right w-20">Jun
                                        </th>
                                        <th class="px-4 py-3 text-[10px] font-bold text-white/20 text-right w-20">Jul
                                        </th>
                                        <th class="px-4 py-3 text-[10px] font-bold text-white/20 text-right w-20">Aug
                                        </th>
                                        <th class="px-4 py-3 text-[10px] font-bold text-white/20 text-right w-20">Sep
                                        </th>
                                        <th class="px-4 py-3 text-[10px] font-bold text-white/20 text-right w-20">Oct
                                        </th>
                                        <th class="px-4 py-3 text-[10px] font-bold text-white/20 text-right w-20">Nov
                                        </th>
                                        <th class="px-4 py-3 text-[10px] font-bold text-white/20 text-right w-20">Dec
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-white/5 text-sm">
                                    <!-- ENGINE Group -->
                                    <tr class="bg-white/[0.015]">
                                        <td colspan="13"
                                            class="px-4 py-2 text-[10px] font-black text-white/30 uppercase tracking-widest sticky left-0 bg-white/[0.015]">
                                            <i class="fa-solid fa-gear mr-2 text-[8px]"></i>Engine</td>
                                    </tr>
                                    <tr class="hover:bg-white/[0.02]">
                                        <td class="px-4 py-2.5 text-white/60 sticky left-0 bg-[#050505]">Oil & Filter
                                            Change</td>
                                        <td class="px-4 py-2.5 text-white/50 font-mono text-right">₱35.00</td>
                                        <td class="px-4 py-2.5 text-white/15 font-mono text-right">—</td>
                                        <td class="px-4 py-2.5 text-white/15 font-mono text-right">—</td>
                                        <td class="px-4 py-2.5 text-white/15 font-mono text-right">—</td>
                                        <td class="px-4 py-2.5 text-white/15 font-mono text-right">—</td>
                                        <td class="px-4 py-2.5 text-white/50 font-mono text-right">₱35.00</td>
                                        <td class="px-4 py-2.5 text-white/15 font-mono text-right">—</td>
                                        <td class="px-4 py-2.5 text-white/15 font-mono text-right">—</td>
                                        <td class="px-4 py-2.5 text-white/50 font-mono text-right">₱35.00</td>
                                        <td class="px-4 py-2.5 text-white/15 font-mono text-right">—</td>
                                        <td class="px-4 py-2.5 text-white/15 font-mono text-right">—</td>
                                        <td class="px-4 py-2.5 text-white/15 font-mono text-right">—</td>
                                    </tr>
                                    <tr class="hover:bg-white/[0.02]">
                                        <td class="px-4 py-2.5 text-white/60 sticky left-0 bg-[#050505]">Air Filter
                                            Change</td>
                                        <td class="px-4 py-2.5 text-white/50 font-mono text-right">₱15.00</td>
                                        <td class="px-4 py-2.5 text-white/15 font-mono text-right">—</td>
                                        <td class="px-4 py-2.5 text-white/15 font-mono text-right">—</td>
                                        <td class="px-4 py-2.5 text-white/15 font-mono text-right">—</td>
                                        <td class="px-4 py-2.5 text-white/15 font-mono text-right">—</td>
                                        <td class="px-4 py-2.5 text-white/15 font-mono text-right">—</td>
                                        <td class="px-4 py-2.5 text-white/15 font-mono text-right">—</td>
                                        <td class="px-4 py-2.5 text-white/15 font-mono text-right">—</td>
                                        <td class="px-4 py-2.5 text-white/50 font-mono text-right">₱15.00</td>
                                        <td class="px-4 py-2.5 text-white/15 font-mono text-right">—</td>
                                        <td class="px-4 py-2.5 text-white/15 font-mono text-right">—</td>
                                        <td class="px-4 py-2.5 text-white/15 font-mono text-right">—</td>
                                    </tr>

                                    <!-- CHASSIS Group -->
                                    <tr class="bg-white/[0.015]">
                                        <td colspan="13"
                                            class="px-4 py-2 text-[10px] font-black text-white/30 uppercase tracking-widest sticky left-0 bg-white/[0.015]">
                                            <i class="fa-solid fa-car mr-2 text-[8px]"></i>Chassis</td>
                                    </tr>
                                    <tr class="hover:bg-white/[0.02]">
                                        <td class="px-4 py-2.5 text-white/60 sticky left-0 bg-[#050505]">Tire Repair /
                                            Replacement</td>
                                        <td class="px-4 py-2.5 text-white/15 font-mono text-right">—</td>
                                        <td class="px-4 py-2.5 text-white/50 font-mono text-right">₱500.00</td>
                                        <td class="px-4 py-2.5 text-white/15 font-mono text-right">—</td>
                                        <td class="px-4 py-2.5 text-white/15 font-mono text-right">—</td>
                                        <td class="px-4 py-2.5 text-white/15 font-mono text-right">—</td>
                                        <td class="px-4 py-2.5 text-white/15 font-mono text-right">—</td>
                                        <td class="px-4 py-2.5 text-white/15 font-mono text-right">—</td>
                                        <td class="px-4 py-2.5 text-white/15 font-mono text-right">—</td>
                                        <td class="px-4 py-2.5 text-white/15 font-mono text-right">—</td>
                                        <td class="px-4 py-2.5 text-white/50 font-mono text-right">₱500.00</td>
                                        <td class="px-4 py-2.5 text-white/15 font-mono text-right">—</td>
                                        <td class="px-4 py-2.5 text-white/15 font-mono text-right">—</td>
                                    </tr>
                                    <tr class="hover:bg-white/[0.02]">
                                        <td class="px-4 py-2.5 text-white/60 sticky left-0 bg-[#050505]">Brake Pad
                                            Replacement</td>
                                        <td class="px-4 py-2.5 text-white/15 font-mono text-right">—</td>
                                        <td class="px-4 py-2.5 text-white/50 font-mono text-right">₱50.00</td>
                                        <td class="px-4 py-2.5 text-white/15 font-mono text-right">—</td>
                                        <td class="px-4 py-2.5 text-white/15 font-mono text-right">—</td>
                                        <td class="px-4 py-2.5 text-white/15 font-mono text-right">—</td>
                                        <td class="px-4 py-2.5 text-white/15 font-mono text-right">—</td>
                                        <td class="px-4 py-2.5 text-white/15 font-mono text-right">—</td>
                                        <td class="px-4 py-2.5 text-white/15 font-mono text-right">—</td>
                                        <td class="px-4 py-2.5 text-white/15 font-mono text-right">—</td>
                                        <td class="px-4 py-2.5 text-white/15 font-mono text-right">—</td>
                                        <td class="px-4 py-2.5 text-white/15 font-mono text-right">—</td>
                                        <td class="px-4 py-2.5 text-white/15 font-mono text-right">—</td>
                                    </tr>

                                    <!-- MISC Group -->
                                    <tr class="bg-white/[0.015]">
                                        <td colspan="13"
                                            class="px-4 py-2 text-[10px] font-black text-white/30 uppercase tracking-widest sticky left-0 bg-white/[0.015]">
                                            <i class="fa-solid fa-ellipsis mr-2 text-[8px]"></i>Other</td>
                                    </tr>
                                    <tr class="hover:bg-white/[0.02]">
                                        <td class="px-4 py-2.5 text-white/60 sticky left-0 bg-[#050505]">Windshield
                                            Wiper</td>
                                        <td class="px-4 py-2.5 text-white/15 font-mono text-right">—</td>
                                        <td class="px-4 py-2.5 text-white/15 font-mono text-right">—</td>
                                        <td class="px-4 py-2.5 text-white/15 font-mono text-right">—</td>
                                        <td class="px-4 py-2.5 text-white/15 font-mono text-right">—</td>
                                        <td class="px-4 py-2.5 text-white/15 font-mono text-right">—</td>
                                        <td class="px-4 py-2.5 text-white/50 font-mono text-right">₱1,000.00</td>
                                        <td class="px-4 py-2.5 text-white/15 font-mono text-right">—</td>
                                        <td class="px-4 py-2.5 text-white/15 font-mono text-right">—</td>
                                        <td class="px-4 py-2.5 text-white/15 font-mono text-right">—</td>
                                        <td class="px-4 py-2.5 text-white/15 font-mono text-right">—</td>
                                        <td class="px-4 py-2.5 text-white/15 font-mono text-right">—</td>
                                        <td class="px-4 py-2.5 text-white/15 font-mono text-right">—</td>
                                    </tr>
                                    <tr class="hover:bg-white/[0.02]">
                                        <td class="px-4 py-2.5 text-white/60 sticky left-0 bg-[#050505]">Battery
                                            Replacement</td>
                                        <td class="px-4 py-2.5 text-white/15 font-mono text-right">—</td>
                                        <td class="px-4 py-2.5 text-white/15 font-mono text-right">—</td>
                                        <td class="px-4 py-2.5 text-white/15 font-mono text-right">—</td>
                                        <td class="px-4 py-2.5 text-white/15 font-mono text-right">—</td>
                                        <td class="px-4 py-2.5 text-white/15 font-mono text-right">—</td>
                                        <td class="px-4 py-2.5 text-white/15 font-mono text-right">—</td>
                                        <td class="px-4 py-2.5 text-white/15 font-mono text-right">—</td>
                                        <td class="px-4 py-2.5 text-white/50 font-mono text-right">₱450.00</td>
                                        <td class="px-4 py-2.5 text-white/15 font-mono text-right">—</td>
                                        <td class="px-4 py-2.5 text-white/15 font-mono text-right">—</td>
                                        <td class="px-4 py-2.5 text-white/15 font-mono text-right">—</td>
                                        <td class="px-4 py-2.5 text-white/15 font-mono text-right">—</td>
                                    </tr>

                                    <!-- TOTALS ROW -->
                                    <tr class="bg-blue-500/[0.03] border-t-2 border-blue-500/20">
                                        <td
                                            class="px-4 py-3.5 text-blue-400 font-bold sticky left-0 bg-blue-500/[0.03]">
                                            <i class="fa-solid fa-calculator mr-2 text-xs"></i>Total Monthly</td>
                                        <td class="px-4 py-3.5 text-blue-400 font-mono text-right font-bold">₱50.00</td>
                                        <td class="px-4 py-3.5 text-blue-400 font-mono text-right font-bold">₱550.00
                                        </td>
                                        <td class="px-4 py-3.5 text-white/20 font-mono text-right">₱0.00</td>
                                        <td class="px-4 py-3.5 text-white/20 font-mono text-right">₱0.00</td>
                                        <td class="px-4 py-3.5 text-white/20 font-mono text-right">₱0.00</td>
                                        <td class="px-4 py-3.5 text-blue-400 font-mono text-right font-bold">₱1,035.00
                                        </td>
                                        <td class="px-4 py-3.5 text-white/20 font-mono text-right">₱0.00</td>
                                        <td class="px-4 py-3.5 text-blue-400 font-mono text-right font-bold">₱450.00
                                        </td>
                                        <td class="px-4 py-3.5 text-blue-400 font-mono text-right font-bold">₱50.00</td>
                                        <td class="px-4 py-3.5 text-blue-400 font-mono text-right font-bold">₱500.00
                                        </td>
                                        <td class="px-4 py-3.5 text-white/20 font-mono text-right">₱0.00</td>
                                        <td class="px-4 py-3.5 text-white/20 font-mono text-right">₱0.00</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <!-- Footer -->
                        <div
                            class="px-6 py-3 border-t border-white/5 bg-white/[0.01] flex flex-col sm:flex-row justify-between gap-2 text-xs">
                            <span class="text-white/25 uppercase font-bold tracking-wider">YTD Total</span>
                            <span class="text-white font-bold font-mono text-sm">₱2,635.00</span>
                        </div>
                    </div>
                </div>

                <!-- ─────────────────────────────────────── -->
                <!-- TAB 2: PREVENTIVE MAINTENANCE SCHEDULE  -->
                <!-- ─────────────────────────────────────── -->
                <div x-show="activeTab === 'schedule'" x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 -translate-y-2"
                    x-transition:enter-end="opacity-100 translate-y-0">
                    <div class="glass rounded-2xl border border-white/5 overflow-hidden">
                        <div class="px-6 py-3.5 border-b border-white/5 bg-white/[0.02] flex justify-between items-center">
                            <h3 class="font-bold text-white text-sm">Preventive Maintenance Schedule</h3>
                            <span class="text-[10px] text-white/20 uppercase tracking-wider font-bold">Based on mileage
                                intervals</span>
                        </div>
                        <div class="p-6 md:p-8">
                            <!-- Milestone Timeline -->
                            <div class="space-y-6">

                                <!-- 10,000 Miles -->
                                <div class="milestone-dot flex gap-5">
                                    <div class="shrink-0 flex flex-col items-center">
                                        <div
                                            class="w-6 h-6 rounded-full bg-blue-500/20 border-2 border-blue-500/40 flex items-center justify-center">
                                            <div class="w-2 h-2 rounded-full bg-blue-400"></div>
                                        </div>
                                    </div>
                                    <div class="flex-1 pb-2">
                                        <div class="flex items-center gap-3 mb-3">
                                            <span class="text-sm font-bold text-white">10,000 Miles</span>
                                            <span
                                                class="text-[10px] font-bold text-blue-400 bg-blue-500/10 px-2 py-0.5 rounded-full border border-blue-500/20">Every
                                                10K</span>
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2">
                                            <div
                                                class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg bg-white/[0.02] border border-white/5">
                                                <i class="fa-solid fa-rotate text-white/25 text-[10px] w-4 text-center">
                                                </i>
                                                <span class="text-xs text-white/60">Tire Rotation</span>
                                            </div>
                                            <div
                                                class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg bg-white/[0.02] border border-white/5">
                                                <i class="fa-solid fa-oil-can text-white/25 text-[10px] w-4 text-center">
                                                </i>
                                                <span class="text-xs text-white/60">Oil & Filter Change</span>
                                            </div>
                                            <div
                                                class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg bg-white/[0.02] border border-white/5">
                                                <i class="fa-solid fa-wind text-white/25 text-[10px] w-4 text-center">
                                                </i>
                                                <span class="text-xs text-white/60">Air Filter Inspection</span>
                                            </div>
                                            <div
                                                class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg bg-white/[0.02] border border-white/5">
                                                <i class="fa-solid fa-droplet text-white/25 text-[10px] w-4 text-center">
                                                </i>
                                                <span class="text-xs text-white/60">Fluid Level Check</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- 20,000 Miles -->
                                <div class="milestone-dot flex gap-5">
                                    <div class="shrink-0 flex flex-col items-center">
                                        <div
                                            class="w-6 h-6 rounded-full bg-amber-500/20 border-2 border-amber-500/40 flex items-center justify-center">
                                            <div class="w-2 h-2 rounded-full bg-amber-400"></div>
                                        </div>
                                    </div>
                                    <div class="flex-1 pb-2">
                                        <div class="flex items-center gap-3 mb-3">
                                            <span class="text-sm font-bold text-white">20,000 Miles</span>
                                            <span
                                                class="text-[10px] font-bold text-amber-400 bg-amber-500/10 px-2 py-0.5 rounded-full border border-amber-500/20">Every
                                                20K</span>
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2">
                                            <div
                                                class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg bg-white/[0.02] border border-white/5">
                                                <i class="fa-solid fa-circle-dot text-white/25 text-[10px] w-4 text-center">
                                                </i>
                                                <span class="text-xs text-white/60">Tire Replacement</span>
                                            </div>
                                            <div
                                                class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg bg-white/[0.02] border border-white/5">
                                                <i class="fa-solid fa-oil-can text-white/25 text-[10px] w-4 text-center">
                                                </i>
                                                <span class="text-xs text-white/60">Oil & Filter Change</span>
                                            </div>
                                            <div
                                                class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg bg-white/[0.02] border border-white/5">
                                                <i class="fa-solid fa-wind text-white/25 text-[10px] w-4 text-center">
                                                </i>
                                                <span class="text-xs text-white/60">Air Filter Replacement</span>
                                            </div>
                                            <div
                                                class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg bg-white/[0.02] border border-white/5">
                                                <i class="fa-solid fa-droplet text-white/25 text-[10px] w-4 text-center">
                                                </i>
                                                <span class="text-xs text-white/60">Coolant Flush</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- 30,000 Miles -->
                                <div class="milestone-dot flex gap-5">
                                    <div class="shrink-0 flex flex-col items-center">
                                        <div
                                            class="w-6 h-6 rounded-full bg-blue-500/20 border-2 border-blue-500/40 flex items-center justify-center">
                                            <div class="w-2 h-2 rounded-full bg-blue-400"></div>
                                        </div>
                                    </div>
                                    <div class="flex-1 pb-2">
                                        <div class="flex items-center gap-3 mb-3">
                                            <span class="text-sm font-bold text-white">30,000 Miles</span>
                                            <span
                                                class="text-[10px] font-bold text-blue-400 bg-blue-500/10 px-2 py-0.5 rounded-full border border-blue-500/20">Every
                                                30K</span>
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2">
                                            <div
                                                class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg bg-white/[0.02] border border-white/5">
                                                <i class="fa-solid fa-rotate text-white/25 text-[10px] w-4 text-center">
                                                </i>
                                                <span class="text-xs text-white/60">Tire Rotation</span>
                                            </div>
                                            <div
                                                class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg bg-white/[0.02] border border-white/5">
                                                <i class="fa-solid fa-oil-can text-white/25 text-[10px] w-4 text-center">
                                                </i>
                                                <span class="text-xs text-white/60">Oil & Filter Change</span>
                                            </div>
                                            <div
                                                class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg bg-white/[0.02] border border-white/5">
                                                <i class="fa-solid fa-gears text-white/25 text-[10px] w-4 text-center">
                                                </i>
                                                <span class="text-xs text-white/60">Transmission Fluid</span>
                                            </div>
                                            <div
                                                class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg bg-white/[0.02] border border-white/5">
                                                <i class="fa-solid fa-car text-white/25 text-[10px] w-4 text-center">
                                                </i>
                                                <span class="text-xs text-white/60">Brake Inspection</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- 50,000 Miles -->
                                <div class="milestone-dot flex gap-5">
                                    <div class="shrink-0 flex flex-col items-center">
                                        <div
                                            class="w-6 h-6 rounded-full bg-rose-500/20 border-2 border-rose-500/40 flex items-center justify-center">
                                            <div class="w-2 h-2 rounded-full bg-rose-400"></div>
                                        </div>
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3 mb-3">
                                            <span class="text-sm font-bold text-white">50,000 Miles</span>
                                            <span
                                                class="text-[10px] font-bold text-rose-400 bg-rose-500/10 px-2 py-0.5 rounded-full border border-rose-500/20">Major
                                                Service</span>
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2">
                                            <div
                                                class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg bg-white/[0.02] border border-white/5">
                                                <i class="fa-solid fa-circle-dot text-white/25 text-[10px] w-4 text-center">
                                                </i>
                                                <span class="text-xs text-white/60">Tire Replacement</span>
                                            </div>
                                            <div
                                                class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg bg-white/[0.02] border border-white/5">
                                                <i class="fa-solid fa-oil-can text-white/25 text-[10px] w-4 text-center">
                                                </i>
                                                <span class="text-xs text-white/60">Oil & Filter Change</span>
                                            </div>
                                            <div
                                                class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg bg-white/[0.02] border border-white/5">
                                                <i class="fa-solid fa-car text-white/25 text-[10px] w-4 text-center">
                                                </i>
                                                <span class="text-xs text-white/60">Brake Pad Replacement</span>
                                            </div>
                                            <div
                                                class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg bg-white/[0.02] border border-white/5">
                                                <i class="fa-solid fa-bolt text-white/25 text-[10px] w-4 text-center">
                                                </i>
                                                <span class="text-xs text-white/60">Spark Plug Replacement</span>
                                            </div>
                                            <div
                                                class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg bg-white/[0.02] border border-white/5">
                                                <i class="fa-solid fa-gears text-white/25 text-[10px] w-4 text-center">
                                                </i>
                                                <span class="text-xs text-white/60">Transmission Service</span>
                                            </div>
                                            <div
                                                class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg bg-white/[0.02] border border-white/5">
                                                <i class="fa-solid fa-droplet text-white/25 text-[10px] w-4 text-center">
                                                </i>
                                                <span class="text-xs text-white/60">Coolant Flush</span>
                                            </div>
                                            <div
                                                class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg bg-white/[0.02] border border-white/5">
                                                <i class="fa-solid fa-wind text-white/25 text-[10px] w-4 text-center">
                                                </i>
                                                <span class="text-xs text-white/60">Air Filter Replacement</span>
                                            </div>
                                            <div
                                                class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg bg-white/[0.02] border border-white/5">
                                                <i class="fa-solid fa-magnifying-glass text-white/25 text-[10px] w-4 text-center">
                                                </i>
                                                <span class="text-xs text-white/60">Full Inspection</span>
                                            </div>
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
                <div x-show="activeTab === 'log'" x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 -translate-y-2"
                    x-transition:enter-end="opacity-100 translate-y-0">
                    <div class="glass rounded-2xl border border-white/5 overflow-hidden">
                        <div
                            class="px-6 py-3.5 border-b border-white/5 bg-white/[0.02] flex justify-between items-center">
                            <h3 class="font-bold text-white text-sm">Vehicle Maintenance Log</h3>
                            <button
                                class="px-3.5 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold transition-all">
                                <i class="fa-solid fa-plus mr-1.5"></i>Add Entry
                            </button>
                        </div>
                        <div class="max-h-[600px] overflow-y-auto log-scroll">

                            <!-- Log Entry 1 -->
                            <div class="timeline-entry px-6 py-5 border-b border-white/5 hover:bg-white/[0.01] transition-colors">
                                <div class="flex gap-5">
                                    <div class="shrink-0 flex flex-col items-center pt-1">
                                        <div
                                            class="w-[30px] h-[30px] rounded-full bg-emerald-500/15 border border-emerald-500/30 flex items-center justify-center">
                                            <i class="fa-solid fa-check text-emerald-400 text-[10px]"></i>
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-2">
                                            <div>
                                                <span class="text-sm font-bold text-white">Oil & Filter Change</span>
                                                <span class="text-xs text-white/30 mx-2">·</span>
                                                <span class="text-xs text-white/40">Air Filter Change</span>
                                            </div>
                                            <span class="text-[10px] text-white/30 font-mono shrink-0">Nov 1, 2023</span>
                                        </div>
                                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-x-6 gap-y-2 text-xs">
                                            <div>
                                                <span class="text-white/25 uppercase font-bold text-[10px] block">Mileage
                                                </span>
                                                <span class="text-white/60 font-mono">85,230 mi</span>
                                            </div>
                                            <div>
                                                <span class="text-white/25 uppercase font-bold text-[10px] block">Cost
                                                </span>
                                                <span class="text-white/60 font-mono">₱50.00</span>
                                            </div>
                                            <div>
                                                <span class="text-white/25 uppercase font-bold text-[10px] block">Service
                                                    Provider</span>
                                                <span class="text-white/60">QuickLube Atlanta</span>
                                            </div>
                                            <div>
                                                <span class="text-white/25 uppercase font-bold text-[10px] block">Category
                                                </span>
                                                <span
                                                    class="text-blue-400/80 bg-blue-500/10 px-1.5 py-0.5 rounded text-[10px] font-bold">Engine</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Log Entry 2 -->
                            <div class="timeline-entry px-6 py-5 border-b border-white/5 hover:bg-white/[0.01] transition-colors">
                                <div class="flex gap-5">
                                    <div class="shrink-0 flex flex-col items-center pt-1">
                                        <div
                                            class="w-[30px] h-[30px] rounded-full bg-emerald-500/15 border border-emerald-500/30 flex items-center justify-center">
                                            <i class="fa-solid fa-check text-emerald-400 text-[10px]"></i>
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-2">
                                            <div>
                                                <span class="text-sm font-bold text-white">Tire Repair /
                                                    Replacement</span>
                                            </div>
                                            <span class="text-[10px] text-white/30 font-mono shrink-0">Oct 15, 2023</span>
                                        </div>
                                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-x-6 gap-y-2 text-xs">
                                            <div>
                                                <span class="text-white/25 uppercase font-bold text-[10px] block">Mileage
                                                </span>
                                                <span class="text-white/60 font-mono">82,100 mi</span>
                                            </div>
                                            <div>
                                                <span class="text-white/25 uppercase font-bold text-[10px] block">Cost
                                                </span>
                                                <span class="text-white/60 font-mono">₱500.00</span>
                                            </div>
                                            <div>
                                                <span class="text-white/25 uppercase font-bold text-[10px] block">Service
                                                    Provider</span>
                                                <span class="text-white/60">TireWorld GA</span>
                                            </div>
                                            <div>
                                                <span class="text-white/25 uppercase font-bold text-[10px] block">Category
                                                </span>
                                                <span
                                                    class="text-amber-400/80 bg-amber-500/10 px-1.5 py-0.5 rounded text-[10px] font-bold">Chassis</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Log Entry 3 -->
                            <div class="timeline-entry px-6 py-5 border-b border-white/5 hover:bg-white/[0.01] transition-colors">
                                <div class="flex gap-5">
                                    <div class="shrink-0 flex flex-col items-center pt-1">
                                        <div
                                            class="w-[30px] h-[30px] rounded-full bg-emerald-500/15 border border-emerald-500/30 flex items-center justify-center">
                                            <i class="fa-solid fa-check text-emerald-400 text-[10px]"></i>
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-2">
                                            <div>
                                                <span class="text-sm font-bold text-white">Brake Pad
                                                    Replacement</span>
                                            </div>
                                            <span class="text-[10px] text-white/30 font-mono shrink-0">Sep 28, 2023</span>
                                        </div>
                                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-x-6 gap-y-2 text-xs">
                                            <div>
                                                <span class="text-white/25 uppercase font-bold text-[10px] block">Mileage
                                                </span>
                                                <span class="text-white/60 font-mono">78,500 mi</span>
                                            </div>
                                            <div>
                                                <span class="text-white/25 uppercase font-bold text-[10px] block">Cost
                                                </span>
                                                <span class="text-white/60 font-mono">₱50.00</span>
                                            </div>
                                            <div>
                                                <span class="text-white/25 uppercase font-bold text-[10px] block">Service
                                                    Provider</span>
                                                <span class="text-white/60">BrakeMaster Inc.</span>
                                            </div>
                                            <div>
                                                <span class="text-white/25 uppercase font-bold text-[10px] block">Category
                                                </span>
                                                <span
                                                    class="text-amber-400/80 bg-amber-500/10 px-1.5 py-0.5 rounded text-[10px] font-bold">Chassis</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Log Entry 4 -->
                            <div class="timeline-entry px-6 py-5 border-b border-white/5 hover:bg-white/[0.01] transition-colors">
                                <div class="flex gap-5">
                                    <div class="shrink-0 flex flex-col items-center pt-1">
                                        <div
                                            class="w-[30px] h-[30px] rounded-full bg-emerald-500/15 border border-emerald-500/30 flex items-center justify-center">
                                            <i class="fa-solid fa-check text-emerald-400 text-[10px]"></i>
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-2">
                                            <div>
                                                <span class="text-sm font-bold text-white">Oil & Filter Change</span>
                                                <span class="text-xs text-white/30 mx-2">·</span>
                                                <span class="text-xs text-white/40">Tire Rotation</span>
                                            </div>
                                            <span class="text-[10px] text-white/30 font-mono shrink-0">Aug 12, 2023</span>
                                        </div>
                                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-x-6 gap-y-2 text-xs">
                                            <div>
                                                <span class="text-white/25 uppercase font-bold text-[10px] block">Mileage
                                                </span>
                                                <span class="text-white/60 font-mono">72,800 mi</span>
                                            </div>
                                            <div>
                                                <span class="text-white/25 uppercase font-bold text-[10px] block">Cost
                                                </span>
                                                <span class="text-white/60 font-mono">₱35.00</span>
                                            </div>
                                            <div>
                                                <span class="text-white/25 uppercase font-bold text-[10px] block">Service
                                                    Provider</span>
                                                <span class="text-white/60">QuickLube Atlanta</span>
                                            </div>
                                            <div>
                                                <span class="text-white/25 uppercase font-bold text-[10px] block">Category
                                                </span>
                                                <span
                                                    class="text-blue-400/80 bg-blue-500/10 px-1.5 py-0.5 rounded text-[10px] font-bold">Engine</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Log Entry 5 -->
                            <div class="timeline-entry px-6 py-5 border-b border-white/5 hover:bg-white/[0.01] transition-colors">
                                <div class="flex gap-5">
                                    <div class="shrink-0 flex flex-col items-center pt-1">
                                        <div
                                            class="w-[30px] h-[30px] rounded-full bg-emerald-500/15 border border-emerald-500/30 flex items-center justify-center">
                                            <i class="fa-solid fa-check text-emerald-400 text-[10px]"></i>
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-2">
                                            <div>
                                                <span class="text-sm font-bold text-white">Battery Replacement</span>
                                            </div>
                                            <span class="text-[10px] text-white/30 font-mono shrink-0">Jul 5, 2023</span>
                                        </div>
                                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-x-6 gap-y-2 text-xs">
                                            <div>
                                                <span class="text-white/25 uppercase font-bold text-[10px] block">Mileage
                                                </span>
                                                <span class="text-white/60 font-mono">68,200 mi</span>
                                            </div>
                                            <div>
                                                <span class="text-white/25 uppercase font-bold text-[10px] block">Cost
                                                </span>
                                                <span class="text-white/60 font-mono">₱450.00</span>
                                            </div>
                                            <div>
                                                <span class="text-white/25 uppercase font-bold text-[10px] block">Service
                                                    Provider</span>
                                                <span class="text-white/60">AutoZone Atlanta</span>
                                            </div>
                                            <div>
                                                <span class="text-white/25 uppercase font-bold text-[10px] block">Category
                                                </span>
                                                <span
                                                    class="text-white/50 bg-white/5 px-1.5 py-0.5 rounded text-[10px] font-bold">Other</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Log Entry 6 -->
                            <div class="timeline-entry px-6 py-5 hover:bg-white/[0.01] transition-colors">
                                <div class="flex gap-5">
                                    <div class="shrink-0 flex flex-col items-center pt-1">
                                        <div
                                            class="w-[30px] h-[30px] rounded-full bg-emerald-500/15 border border-emerald-500/30 flex items-center justify-center">
                                            <i class="fa-solid fa-check text-emerald-400 text-[10px]"></i>
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-2">
                                            <div>
                                                <span class="text-sm font-bold text-white">Windshield Wiper
                                                    Replacement</span>
                                            </div>
                                            <span class="text-[10px] text-white/30 font-mono shrink-0">Jun 18, 2023</span>
                                        </div>
                                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-x-6 gap-y-2 text-xs">
                                            <div>
                                                <span class="text-white/25 uppercase font-bold text-[10px] block">Mileage
                                                </span>
                                                <span class="text-white/60 font-mono">64,900 mi</span>
                                            </div>
                                            <div>
                                                <span class="text-white/25 uppercase font-bold text-[10px] block">Cost
                                                </span>
                                                <span class="text-white/60 font-mono">₱1,000.00</span>
                                            </div>
                                            <div>
                                                <span class="text-white/25 uppercase font-bold text-[10px] block">Service
                                                    Provider</span>
                                                <span class="text-white/60">Self-service</span>
                                            </div>
                                            <div>
                                                <span class="text-white/25 uppercase font-bold text-[10px] block">Category
                                                </span>
                                                <span
                                                    class="text-white/50 bg-white/5 px-1.5 py-0.5 rounded text-[10px] font-bold">Other</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                        <!-- Log Footer -->
                        <div class="px-6 py-3 border-t border-white/5 bg-white/[0.01] flex justify-between text-xs">
                            <span class="text-white/25 uppercase font-bold tracking-wider">Total Entries</span>
                            <span class="text-white font-bold font-mono">6 records</span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Bottom Spacer -->
            <div class="h-12"></div>
        </div>
    </main>
</body>

</html>
