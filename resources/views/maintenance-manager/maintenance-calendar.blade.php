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

             <div class="max-w-[1400px] mx-auto">

            <header class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
                <div>
                    <h2 class="text-3xl font-black tracking-tight mb-1">Maintenance <span class="text-blue-500">Calendar</span></h2>
                    <p class="text-white/40 text-sm">Historical record of all fleet services.</p>
                </div>
                <div class="flex gap-3 self-start">
                    <input type="text" placeholder="Search logs..." class="bg-[#0a0a0a] border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:ring-1 focus:ring-blue-500/50 outline-none w-64">
                    <button class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-sm font-semibold shadow-lg shadow-blue-900/40 transition-all">
                        <i class="fa-solid fa-plus mr-2"></i> Add Log
                    </button>
                </div>
            </header>

            <div class="glass rounded-[2rem] border border-white/5 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left whitespace-nowrap">
                        <thead>
                            <tr class="border-b border-white/5 bg-white/[0.02]">
                                <th class="px-6 py-5 text-[10px] font-black uppercase tracking-[0.15em] text-blue-500">Date</th>
                                <th class="px-6 py-5 text-[10px] font-black uppercase tracking-[0.15em] text-blue-500">Vehicle</th>
                                <th class="px-6 py-5 text-[10px] font-black uppercase tracking-[0.15em] text-blue-500">Service Performed</th>
                                <th class="px-6 py-5 text-[10px] font-black uppercase tracking-[0.15em] text-blue-500">Mileage</th>
                                <th class="px-6 py-5 text-[10px] font-black uppercase tracking-[0.15em] text-blue-500 text-right">Cost</th>
                                <th class="px-6 py-5 text-[10px] font-black uppercase tracking-[0.15em] text-blue-500 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <tr class="hover:bg-white/[0.02] transition-colors">
                                <td class="px-6 py-4 text-sm text-white/50">Jun 20, 2026</td>
                                <td class="px-6 py-4">
                                    <span class="text-xs font-mono bg-blue-500/10 text-blue-400 px-2 py-0.5 rounded border border-blue-500/20">VEH001</span>
                                </td>
                                <td class="px-6 py-4 text-sm text-white font-medium">Oil & Filter Change</td>
                                <td class="px-6 py-4 text-sm text-white/60 font-mono">12,500 mi</td>
                                <td class="px-6 py-4 text-sm text-white font-mono text-right font-semibold">₱ 3,500</td>
                                <td class="px-6 py-4 text-center">
                                    <button class="text-white/30 hover:text-blue-400 transition-colors"><i class="fa-solid fa-pen text-xs"></i></button>
                                </td>
                            </tr>
                            <tr class="hover:bg-white/[0.02] transition-colors">
                                <td class="px-6 py-4 text-sm text-white/50">Jun 18, 2026</td>
                                <td class="px-6 py-4">
                                    <span class="text-xs font-mono bg-purple-500/10 text-purple-400 px-2 py-0.5 rounded border border-purple-500/20">VEH003</span>
                                </td>
                                <td class="px-6 py-4 text-sm text-white font-medium">Battery Replacement</td>
                                <td class="px-6 py-4 text-sm text-white/60 font-mono">45,200 mi</td>
                                <td class="px-6 py-4 text-sm text-white font-mono text-right font-semibold">₱ 8,500</td>
                                <td class="px-6 py-4 text-center">
                                    <button class="text-white/30 hover:text-blue-400 transition-colors"><i class="fa-solid fa-pen text-xs"></i></button>
                                </td>
                            </tr>
                            <tr class="hover:bg-white/[0.02] transition-colors">
                                <td class="px-6 py-4 text-sm text-white/50">Jun 15, 2026</td>
                                <td class="px-6 py-4">
                                    <span class="text-xs font-mono bg-emerald-500/10 text-emerald-400 px-2 py-0.5 rounded border border-emerald-500/20">VEH002</span>
                                </td>
                                <td class="px-6 py-4 text-sm text-white font-medium">Fuel Filter Change</td>
                                <td class="px-6 py-4 text-sm text-white/60 font-mono">22,100 mi</td>
                                <td class="px-6 py-4 text-sm text-white font-mono text-right font-semibold">₱ 1,200</td>
                                <td class="px-6 py-4 text-center">
                                    <button class="text-white/30 hover:text-blue-400 transition-colors"><i class="fa-solid fa-pen text-xs"></i></button>
                                </td>
                            </tr>
                            <tr class="hover:bg-white/[0.02] transition-colors">
                                <td class="px-6 py-4 text-sm text-white/50">May 10, 2026</td>
                                <td class="px-6 py-4">
                                    <span class="text-xs font-mono bg-blue-500/10 text-blue-400 px-2 py-0.5 rounded border border-blue-500/20">VEH001</span>
                                </td>
                                <td class="px-6 py-4 text-sm text-white font-medium">Tire Rotation</td>
                                <td class="px-6 py-4 text-sm text-white/60 font-mono">12,000 mi</td>
                                <td class="px-6 py-4 text-sm text-white font-mono text-right font-semibold">₱ 1,500</td>
                                <td class="px-6 py-4 text-center">
                                    <button class="text-white/30 hover:text-blue-400 transition-colors"><i class="fa-solid fa-pen text-xs"></i></button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-6 py-4 bg-white/[0.01] border-t border-white/5 flex justify-between items-center text-xs text-white/30">
                    <span>Showing 1-4 of 45 entries</span>
                    <div class="flex gap-2">
                        <button class="px-3 py-1 rounded border border-white/10 hover:bg-white/5 transition">Prev</button>
                        <button class="px-3 py-1 rounded border border-blue-500/30 bg-blue-500/10 text-blue-400">1</button>
                        <button class="px-3 py-1 rounded border border-white/10 hover:bg-white/5 transition">Next</button>
                    </div>
                </div>
            </div>
        </div>    </main>
</body>

</html>
