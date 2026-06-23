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
    </style>
</head>

<body x-data="{ open: true }">

    @include('maintenance-manager.layout.sidebar');

    <main :class="open ? 'ml-72' : 'ml-20'" x-data="{ editing: false }"
        class="sidebar-transition p-8 md:p-12 min-h-screen">

      <div class="max-w-[1600px] mx-auto space-y-6">

            <!-- Header -->
            <header class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-2">
                <div>
                    <h2 class="text-3xl font-black tracking-tight mb-1">Preventive <span class="text-blue-500">Maintenance</span></h2>
                    <p class="text-white/40 text-sm">Track service intervals and vehicle health.</p>
                </div>
                <button class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-sm font-semibold shadow-lg shadow-blue-900/40 transition-all self-start">
                    <i class="fa-solid fa-plus mr-2"></i> Add Service
                </button>
            </header>

            <!-- TOP SECTION: Vehicle Overview -->
            <div class="glass rounded-[2rem] border border-white/5 overflow-hidden">

                <!-- Controls Bar -->
                <div class="p-6 border-b border-white/5 flex flex-col md:flex-row gap-4 justify-between bg-white/[0.02]">
                    <div class="flex items-center gap-4">
                        <div>
                            <label class="text-[10px] uppercase font-bold text-white/30 tracking-wider block mb-1">Vehicle ID</label>
                            <select x-model="selectedVehicle" class="bg-[#0a0a0a] border border-white/10 text-white text-sm rounded-lg px-4 py-2.5 focus:ring-1 focus:ring-blue-500/50 outline-none appearance-none cursor-pointer pr-10">
                                <option value="VEH001">VEH001 - Toyota Hiace</option>
                                <option value="VEH002">VEH002 - Mitsubishi L300</option>
                                <option value="VEH003">VEH003 - Hyundai Starex</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-[10px] uppercase font-bold text-white/30 tracking-wider block mb-1">Today's Date</label>
                            <div class="bg-[#0a0a0a] border border-white/10 text-white/50 text-sm rounded-lg px-4 py-2.5 font-mono">
                                06 / 23 / 2026
                            </div>
                        </div>
                    </div>
                    <div class="flex items-end">
                        <span class="text-xs text-blue-400 font-semibold cursor-pointer hover:underline">See all logged & unlogged services <i class="fa-solid fa-arrow-up-right-from-square ml-1 text-[10px]"></i></span>
                    </div>
                </div>

                <!-- Overview Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left whitespace-nowrap">
                        <thead>
                            <tr class="border-b border-white/5 bg-white/[0.02]">
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.15em] text-blue-500">Service Item</th>
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.15em] text-white/40">Last Service</th>
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.15em] text-white/40">Frequency</th>
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.15em] text-white/40">Next Service Due</th>
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.15em] text-white/40">Remarks</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <!-- Row 1: Logged -->
                            <tr class="hover:bg-white/[0.02] transition-colors">
                                <td class="px-6 py-4 font-semibold text-white">Oil & Filter Change</td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-white/80 font-mono">12,500 mi</div>
                                    <div class="text-xs text-white/30">May 15, 2026</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex gap-2">
                                        <span class="px-2 py-0.5 rounded bg-white/5 text-white/50 text-xs font-medium">5,000 mi</span>
                                        <span class="px-2 py-0.5 rounded bg-white/5 text-white/50 text-xs font-medium">6 mo</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-emerald-400 font-mono font-semibold">17,500 mi</div>
                                    <div class="text-xs text-white/40">Nov 15, 2026</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-white/40">—</td>
                            </tr>
                            <!-- Row 2: Logged -->
                            <tr class="hover:bg-white/[0.02] transition-colors">
                                <td class="px-6 py-4 font-semibold text-white">Tire Rotation / Balance</td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-white/80 font-mono">12,000 mi</div>
                                    <div class="text-xs text-white/30">May 10, 2026</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex gap-2">
                                        <span class="px-2 py-0.5 rounded bg-white/5 text-white/50 text-xs font-medium">10,000 mi</span>
                                        <span class="px-2 py-0.5 rounded bg-white/5 text-white/50 text-xs font-medium">12 mo</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-emerald-400 font-mono font-semibold">22,000 mi</div>
                                    <div class="text-xs text-white/40">May 10, 2027</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-white/40">—</td>
                            </tr>
                            <!-- Row 3: Unlogged / Due Soon -->
                            <tr class="hover:bg-white/[0.02] transition-colors bg-amber-500/[0.02]">
                                <td class="px-6 py-4 font-semibold text-white">Air Filter Change</td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-white/30 italic">Not logged</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex gap-2">
                                        <span class="px-2 py-0.5 rounded bg-white/5 text-white/50 text-xs font-medium">10,000 mi</span>
                                        <span class="px-2 py-0.5 rounded bg-white/5 text-white/50 text-xs font-medium">6 mo</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-amber-400 font-mono font-semibold">Overdue</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-amber-400/70">Requires logging</td>
                            </tr>
                             <!-- Row 4: Unlogged -->
                             <tr class="hover:bg-white/[0.02] transition-colors">
                                <td class="px-6 py-4 font-semibold text-white">Battery Replacement</td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-white/30 italic">Not logged</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex gap-2">
                                        <span class="px-2 py-0.5 rounded bg-white/5 text-white/50 text-xs font-medium">N/A</span>
                                        <span class="px-2 py-0.5 rounded bg-white/5 text-white/50 text-xs font-medium">36 mo</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-white/30 italic">N/A</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-white/40">—</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- BOTTOM SECTION: Split View -->
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

                <!-- LEFT: Maintenance Tasks (Reference) -->
                <div class="glass rounded-[2rem] border border-white/5 overflow-hidden flex flex-col">
                    <div class="px-6 py-5 border-b border-white/5 bg-white/[0.02] flex justify-between items-center">
                        <h3 class="font-bold text-white text-sm">Maintenance Tasks</h3>
                        <span class="text-[10px] text-white/30 uppercase tracking-wider font-bold">Service Intervals</span>
                    </div>
                    <div class="overflow-y-auto flex-1">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="border-b border-white/5">
                                    <th class="px-6 py-3 text-[10px] font-black uppercase tracking-[0.15em] text-blue-500">Task Performed</th>
                                    <th class="px-6 py-3 text-[10px] font-black uppercase tracking-[0.15em] text-white/40 text-right">Miles</th>
                                    <th class="px-6 py-3 text-[10px] font-black uppercase tracking-[0.15em] text-white/40 text-right">Months</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <tr class="hover:bg-white/[0.02] transition-colors">
                                    <td class="px-6 py-3 text-sm text-white/90 font-medium">Oil & Filter Change</td>
                                    <td class="px-6 py-3 text-sm text-white/50 font-mono text-right">5,000</td>
                                    <td class="px-6 py-3 text-sm text-white/50 font-mono text-right">6</td>
                                </tr>
                                <tr class="hover:bg-white/[0.02] transition-colors">
                                    <td class="px-6 py-3 text-sm text-white/90 font-medium">Tire Rotation / Balance</td>
                                    <td class="px-6 py-3 text-sm text-white/50 font-mono text-right">10,000</td>
                                    <td class="px-6 py-3 text-sm text-white/50 font-mono text-right">12</td>
                                </tr>
                                <tr class="hover:bg-white/[0.02] transition-colors">
                                    <td class="px-6 py-3 text-sm text-white/90 font-medium">Air Filter Change</td>
                                    <td class="px-6 py-3 text-sm text-white/50 font-mono text-right">10,000</td>
                                    <td class="px-6 py-3 text-sm text-white/50 font-mono text-right">6</td>
                                </tr>
                                <tr class="hover:bg-white/[0.02] transition-colors">
                                    <td class="px-6 py-3 text-sm text-white/90 font-medium">Battery Replacement</td>
                                    <td class="px-6 py-3 text-sm text-white/20 font-mono text-right">—</td>
                                    <td class="px-6 py-3 text-sm text-white/50 font-mono text-right">36</td>
                                </tr>
                                <tr class="hover:bg-white/[0.02] transition-colors">
                                    <td class="px-6 py-3 text-sm text-white/90 font-medium">Belt Replacement</td>
                                    <td class="px-6 py-3 text-sm text-white/50 font-mono text-right">50,000</td>
                                    <td class="px-6 py-3 text-sm text-white/50 font-mono text-right">24</td>
                                </tr>
                                <tr class="hover:bg-white/[0.02] transition-colors">
                                    <td class="px-6 py-3 text-sm text-white/90 font-medium">Brake Inspection</td>
                                    <td class="px-6 py-3 text-sm text-white/50 font-mono text-right">20,000</td>
                                    <td class="px-6 py-3 text-sm text-white/50 font-mono text-right">12</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- RIGHT: Maintenance Log (History) -->
                <div class="glass rounded-[2rem] border border-white/5 overflow-hidden flex flex-col">
                    <div class="px-6 py-5 border-b border-white/5 bg-white/[0.02] flex justify-between items-center">
                        <h3 class="font-bold text-white text-sm">Maintenance Calendar</h3>
                        <button class="text-[10px] text-blue-400 font-bold uppercase tracking-wider hover:underline flex items-center gap-1">
                            Show all <i class="fa-solid fa-arrow-right text-[8px]"></i>
                        </button>
                    </div>
                    <div class="overflow-y-auto flex-1">
                        <table class="w-full text-left whitespace-nowrap">
                            <thead>
                                <tr class="border-b border-white/5">
                                    <th class="px-6 py-3 text-[10px] font-black uppercase tracking-[0.15em] text-blue-500">Date</th>
                                    <th class="px-6 py-3 text-[10px] font-black uppercase tracking-[0.15em] text-blue-500">Vehicle</th>
                                    <th class="px-6 py-3 text-[10px] font-black uppercase tracking-[0.15em] text-white/40">Service</th>
                                    <th class="px-6 py-3 text-[10px] font-black uppercase tracking-[0.15em] text-white/40 text-right">Cost</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <tr class="hover:bg-white/[0.02] transition-colors">
                                    <td class="px-6 py-3.5 text-sm text-white/50">Jun 20, 2026</td>
                                    <td class="px-6 py-3.5">
                                        <span class="text-xs font-mono bg-blue-500/10 text-blue-400 px-2 py-0.5 rounded border border-blue-500/20">VEH001</span>
                                    </td>
                                    <td class="px-6 py-3.5 text-sm text-white/90 font-medium">Oil & Filter Change</td>
                                    <td class="px-6 py-3.5 text-sm text-white font-mono text-right">₱ 3,500</td>
                                </tr>
                                <tr class="hover:bg-white/[0.02] transition-colors">
                                    <td class="px-6 py-3.5 text-sm text-white/50">Jun 18, 2026</td>
                                    <td class="px-6 py-3.5">
                                        <span class="text-xs font-mono bg-purple-500/10 text-purple-400 px-2 py-0.5 rounded border border-purple-500/20">VEH003</span>
                                    </td>
                                    <td class="px-6 py-3.5 text-sm text-white/90 font-medium">Battery Replacement</td>
                                    <td class="px-6 py-3.5 text-sm text-white font-mono text-right">₱ 8,500</td>
                                </tr>
                                <tr class="hover:bg-white/[0.02] transition-colors">
                                    <td class="px-6 py-3.5 text-sm text-white/50">Jun 15, 2026</td>
                                    <td class="px-6 py-3.5">
                                        <span class="text-xs font-mono bg-emerald-500/10 text-emerald-400 px-2 py-0.5 rounded border border-emerald-500/20">VEH002</span>
                                    </td>
                                    <td class="px-6 py-3.5 text-sm text-white/90 font-medium">Fuel Filter Change</td>
                                    <td class="px-6 py-3.5 text-sm text-white font-mono text-right">₱ 1,200</td>
                                </tr>
                                <tr class="hover:bg-white/[0.02] transition-colors">
                                    <td class="px-6 py-3.5 text-sm text-white/50">May 10, 2026</td>
                                    <td class="px-6 py-3.5">
                                        <span class="text-xs font-mono bg-blue-500/10 text-blue-400 px-2 py-0.5 rounded border border-blue-500/20">VEH001</span>
                                    </td>
                                    <td class="px-6 py-3.5 text-sm text-white/90 font-medium">Tire Rotation</td>
                                    <td class="px-6 py-3.5 text-sm text-white font-mono text-right">₱ 1,500</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </main>
</body>

</html>
