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

    @include('driver-manager.layout.sidebar');

    <main :class="open ? 'ml-72' : 'ml-20'" x-data="{ editing: false }"
        class="sidebar-transition p-8 md:p-12 min-h-screen">

         <div class="max-w-[1400px] mx-auto">
            <header class="mb-8 flex flex-col md:flex-row md:items-end justify-between gap-6">
                <div>
                    <h2 class="text-3xl font-black tracking-tight mb-2">Time <span class="text-blue-500">Keeping</span></h2>
                    <p class="text-white/40 text-sm">Track driver shifts, hours, and leaves.</p>
                </div>
                <div class="flex gap-3">
                    <button class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-500 text-white text-sm font-medium shadow-lg shadow-blue-900/40 transition-all">
                        <i class="fa-solid fa-plus mr-2"></i> New Entry
                    </button>
                </div>
            </header>

            <div class="glass rounded-[2rem] overflow-hidden border border-white/5">
                <div class="overflow-x-auto">
                    <table class="w-full text-left whitespace-nowrap">
                        <thead>
                            <tr class="border-b border-white/5 bg-white/5">
                                <th class="px-6 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-blue-500 w-64">Driver & Date</th>
                                <th class="px-6 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-blue-500">Shift (In / Out)</th>
                                <th class="px-6 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-blue-500">Total Hours</th>
                                <th class="px-6 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-blue-500">Overtime</th>
                                <th class="px-6 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-blue-500">Leaves</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <!-- Row 1: Standard Shift -->
                            <tr class="hover:bg-white/[0.02] transition-colors group">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-white">Jimboy Castillo</div>
                                    <div class="text-sm text-white/40">June 15, 2026</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex flex-col">
                                            <span class="text-[10px] uppercase text-white/30 font-bold tracking-wider">In</span>
                                            <span class="text-sm font-mono text-emerald-400 font-semibold">9:00 AM</span>
                                        </div>
                                        <div class="h-8 w-[1px] bg-white/10"></div>
                                        <div class="flex flex-col">
                                            <span class="text-[10px] uppercase text-white/30 font-bold tracking-wider">Out</span>
                                            <span class="text-sm font-mono text-amber-400 font-semibold">6:00 PM</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-lg font-bold text-white">8.00</span>
                                    <span class="text-xs text-white/30 ml-1">hrs</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm text-white/20">0</span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex gap-2">
                                        <span class="px-2 py-1 rounded bg-white/5 text-white/20 text-xs font-medium">Sick: 0</span>
                                        <span class="px-2 py-1 rounded bg-white/5 text-white/20 text-xs font-medium">Vac: 0</span>
                                    </div>
                                </td>
                            </tr>

                            <!-- Row 2: With Overtime -->
                            <tr class="hover:bg-white/[0.02] transition-colors group">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-white">Maria Santos</div>
                                    <div class="text-sm text-white/40">June 15, 2026</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex flex-col">
                                            <span class="text-[10px] uppercase text-white/30 font-bold tracking-wider">In</span>
                                            <span class="text-sm font-mono text-emerald-400 font-semibold">8:00 AM</span>
                                        </div>
                                        <div class="h-8 w-[1px] bg-white/10"></div>
                                        <div class="flex flex-col">
                                            <span class="text-[10px] uppercase text-white/30 font-bold tracking-wider">Out</span>
                                            <span class="text-sm font-mono text-amber-400 font-semibold">8:00 PM</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-lg font-bold text-white">12.00</span>
                                    <span class="text-xs text-white/30 ml-1">hrs</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-blue-500/20 text-blue-400 border border-blue-500/20">
                                        +4.00 hrs
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex gap-2">
                                        <span class="px-2 py-1 rounded bg-white/5 text-white/20 text-xs font-medium">Sick: 0</span>
                                        <span class="px-2 py-1 rounded bg-white/5 text-white/20 text-xs font-medium">Vac: 0</span>
                                    </div>
                                </td>
                            </tr>

                            <!-- Row 3: Sick Leave -->
                            <tr class="hover:bg-white/[0.02] transition-colors group">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-white">Juan Dela Cruz</div>
                                    <div class="text-sm text-white/40">June 15, 2026</div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm text-white/30 italic">— No Shift —</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-lg font-bold text-white/30">0</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm text-white/20">0</span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex gap-2">
                                        <span class="px-2 py-1 rounded bg-red-500/10 text-red-400 text-xs font-bold border border-red-500/20">Sick: 1</span>
                                        <span class="px-2 py-1 rounded bg-white/5 text-white/20 text-xs font-medium">Vac: 0</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>    </main>
</body>

</html>
