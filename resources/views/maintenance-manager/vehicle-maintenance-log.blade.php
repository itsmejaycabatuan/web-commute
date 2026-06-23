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


  <div class="max-w-[1400px] mx-auto flex-1 flex flex-col min-h-0">

            <!-- Page Header -->
            <header class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6 shrink-0">
                <div>
                    <h2 class="text-3xl font-black tracking-tight mb-1">Vehicle Maintenance <span class="text-blue-500">Log</span></h2>
                    <p class="text-white/40 text-sm">Detailed history, costs, and service records.</p>
                </div>
                <div class="flex gap-3 self-start">
                    <select class="bg-[#0a0a0a] border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:ring-1 focus:ring-blue-500/50 outline-none appearance-none cursor-pointer pr-10">
                        <option>PUV #1 (ABC-123)</option>
                        <option>VEH002 (XYZ-789)</option>
                    </select>
                    <input type="text" placeholder="Search logs..." class="bg-[#0a0a0a] border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:ring-1 focus:ring-blue-500/50 outline-none w-48">
                </div>
            </header>

            <!-- TOP SECTION: Info & Guide -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6 shrink-0">

                <!-- LEFT: Vehicle Info & Cost Summary -->
                <div class="lg:col-span-2 glass rounded-[2rem] p-6 md:p-8 border border-white/5">
                    <div class="flex items-start justify-between mb-6">
                        <div>
                            <h3 class="text-lg font-bold text-white mb-3">Vehicle Information</h3>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-x-8 gap-y-3 text-sm">
                                <div>
                                    <span class="text-[10px] uppercase font-bold text-white/30 block">Fleet ID</span>
                                    <span class="text-white/80 font-medium">PUV #1</span>
                                </div>
                                <div>
                                    <span class="text-[10px] uppercase font-bold text-white/30 block">Year</span>
                                    <span class="text-white/80 font-medium">2021</span>
                                </div>
                                <div>
                                    <span class="text-[10px] uppercase font-bold text-white/30 block">Brand / Model</span>
                                    <span class="text-white/80 font-medium">Ford Transit 350</span>
                                </div>
                                <div>
                                    <span class="text-[10px] uppercase font-bold text-white/30 block">Plate / Reg</span>
                                    <span class="text-white/80 font-medium font-mono">ABC-123</span>
                                </div>
                            </div>
                        </div>
                        <div class="text-right bg-blue-500/5 border border-blue-500/20 rounded-xl px-5 py-3">
                            <span class="text-[10px] uppercase font-bold text-blue-400 block">Total Costs</span>
                            <span class="text-2xl font-black text-white">₱ 155.12</span>
                        </div>
                    </div>

                    <!-- Cost Per Mile Summary -->
                    <div class="border-t border-white/5 pt-5">
                        <h4 class="text-[10px] uppercase font-black text-white/30 tracking-widest mb-3">Cost Per Mile Summary</h4>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                            <div class="bg-white/[0.02] rounded-lg p-3 border border-white/5">
                                <span class="text-[10px] text-white/30 block mb-1">Annual Mileage</span>
                                <span class="text-white font-bold font-mono">85,000 mi</span>
                            </div>
                            <div class="bg-white/[0.02] rounded-lg p-3 border border-white/5">
                                <span class="text-[10px] text-white/30 block mb-1">Current Odometer</span>
                                <span class="text-white font-bold font-mono">20,611 mi</span>
                            </div>
                            <div class="bg-white/[0.02] rounded-lg p-3 border border-white/5">
                                <span class="text-[10px] text-white/30 block mb-1">Total Services</span>
                                <span class="text-white font-bold">3 Logs</span>
                            </div>
                            <div class="bg-white/[0.02] rounded-lg p-3 border border-white/5">
                                <span class="text-[10px] text-white/30 block mb-1">Cost / Mile</span>
                                <span class="text-emerald-400 font-bold font-mono">₱ 0.02</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT: Maintenance Guide Hyperlink & Log Button -->
                <div class="glass rounded-[2rem] p-6 border border-white/5 flex flex-col gap-6">
                    <a rel="noopener noreferrer" target="_blank" href="https://www.edmunds.com/car-maintenance/guide-page.html" class="flex items-center gap-4 p-4 rounded-xl bg-white/[0.02] border border-white/5 hover:bg-white/[0.04] hover:border-white/10 transition-all group">
                        <div class="w-12 h-12 rounded-xl bg-blue-500/10 flex items-center justify-center text-blue-500 group-hover:bg-blue-500/20 transition-colors shrink-0">
                            <i class="fa-solid fa-book-open text-lg"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <span class="text-sm font-bold text-white/90 block group-hover:text-white transition-colors">Maintenance Guide</span>
                            <span class="text-xs text-white/40 flex items-center gap-1">View scheduled services <i class="fa-solid fa-arrow-right text-[10px] group-hover:translate-x-1 transition-transform"></i></span>
                        </div>
                    </a>

                    <button class="w-full py-3.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-sm font-bold shadow-lg shadow-blue-900/40 transition-all flex items-center justify-center gap-2">
                        <i class="fa-solid fa-plus"></i> Log New Service
                    </button>
                </div>
            </div>

            <!-- BOTTOM SECTION: Compacted Log Table -->
            <div class="glass rounded-[2rem] border border-white/5 flex-1 flex flex-col overflow-hidden">

                <!-- Removed whitespace-nowrap to allow wrapping -->
                <div class="overflow-y-auto flex-1 table-scroll">
                    <table class="w-full text-left">
                        <thead class="sticky top-0 z-10">
                            <tr class="border-b border-white/5 bg-[#0a0a0a]">
                                <th class="px-5 py-4 text-[10px] font-black uppercase tracking-[0.15em] text-blue-500 w-[15%]">Date & Odo</th>
                                <th class="px-5 py-4 text-[10px] font-black uppercase tracking-[0.15em] text-blue-500 w-[30%]">Service Details</th>
                                <th class="px-5 py-4 text-[10px] font-black uppercase tracking-[0.15em] text-blue-500 w-[25%]">Cost Breakdown</th>
                                <th class="px-5 py-4 text-[10px] font-black uppercase tracking-[0.15em] text-blue-500 w-[20%]">Notes</th>
                                <th class="px-5 py-4 text-[10px] font-black uppercase tracking-[0.15em] text-blue-500 w-[10%] text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <!-- Row 1 -->
                            <tr class="hover:bg-white/[0.02] transition-colors">
                                <td class="px-5 py-4">
                                    <div class="text-sm text-white/70 font-medium">Jun 07, 2021</div>
                                    <div class="text-xs text-white/30 font-mono mt-0.5">20,611 mi</div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="text-sm text-white font-semibold">Minor Parts Repair</div>
                                    <div class="text-xs text-white/40 mt-0.5">In-House</div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="text-sm text-white font-bold font-mono">₱ 0.00</div>
                                    <div class="text-[10px] text-white/30 font-mono mt-0.5">Mat: 0.00 | Lab: 0.00</div>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-block text-xs text-emerald-400 bg-emerald-500/5 px-2 py-1 rounded border border-emerald-500/10">Under Warranty</span>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <button class="text-white/30 hover:text-blue-400 transition-colors"><i class="fa-solid fa-pen text-xs"></i></button>
                                </td>
                            </tr>
                            <!-- Row 2 -->
                            <tr class="hover:bg-white/[0.02] transition-colors">
                                <td class="px-5 py-4">
                                    <div class="text-sm text-white/70 font-medium">Mar 26, 2021</div>
                                    <div class="text-xs text-white/30 font-mono mt-0.5">17,339 mi</div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="text-sm text-white font-semibold">Tire Maintenance</div>
                                    <div class="text-xs text-white/40 mt-0.5">Goodyear Service Center</div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="text-sm text-white font-bold font-mono">₱ 80.23</div>
                                    <div class="text-[10px] text-white/30 font-mono mt-0.5">Mat: 50.23 | Lab: 30.00</div>
                                </td>
                                <td class="px-5 py-4 text-sm text-white/30">—</td>
                                <td class="px-5 py-4 text-center">
                                    <button class="text-white/30 hover:text-blue-400 transition-colors"><i class="fa-solid fa-pen text-xs"></i></button>
                                </td>
                            </tr>
                            <!-- Row 3 -->
                            <tr class="hover:bg-white/[0.02] transition-colors">
                                <td class="px-5 py-4">
                                    <div class="text-sm text-white/70 font-medium">Jun 18, 2020</div>
                                    <div class="text-xs text-white/30 font-mono mt-0.5">875 mi</div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="text-sm text-white font-semibold">Oil & Filter Change</div>
                                    <div class="text-xs text-white/40 mt-0.5">Castrol Shop</div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="text-sm text-white font-bold font-mono">₱ 74.89</div>
                                    <div class="text-[10px] text-white/30 font-mono mt-0.5">Mat: 44.89 | Lab: 30.00</div>
                                </td>
                                <td class="px-5 py-4 text-sm text-white/30">—</td>
                                <td class="px-5 py-4 text-center">
                                    <button class="text-white/30 hover:text-blue-400 transition-colors"><i class="fa-solid fa-pen text-xs"></i></button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Pagination Pinned to Bottom -->
        <div class="-mx-8 md:-mx-12 -mb-8 md:-mb-12 mt-4 px-8 md:px-12 py-4 bg-[#0a0a0a] border-t border-white/5 flex justify-between items-center text-xs text-white/30 shrink-0">
            <span>Showing 1-3 of 3 entries</span>
            <div class="flex gap-2">
                <button class="px-3 py-1 rounded border border-white/10 hover:bg-white/5 transition opacity-50 cursor-not-allowed" disabled>Prev</button>
                <button class="px-3 py-1 rounded border border-blue-500/30 bg-blue-500/10 text-blue-400">1</button>
                <button class="px-3 py-1 rounded border border-white/10 hover:bg-white/5 transition opacity-50 cursor-not-allowed" disabled>Next</button>
            </div>
        </div>

    </main>
</body>

</html>
