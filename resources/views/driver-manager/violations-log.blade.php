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

        <!-- Header -->
        <header class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-black tracking-tight text-white mb-1">Violations <span class="text-blue-500">Log</span></h1>
                <p class="text-white/40 text-sm">Overview of traffic violations and penalties.</p>
            </div>
            <div class="flex gap-3">
                <button class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-sm font-semibold shadow-lg shadow-blue-900/40 transition-all transform hover:-translate-y-0.5">
                    <i class="fa-solid fa-plus mr-2"></i> New Entry
                </button>
            </div>
        </header>

        <!-- Improved Table Container -->
        <div class="glass rounded-2xl overflow-hidden border border-white/5 shadow-2xl">

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-white/10 bg-white/[0.02]">
                            <!-- Reorganized Headers -->
                            <th class="px-6 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-blue-500 w-80 sticky left-0 bg-dark-900/95 backdrop-blur-sm z-10">
                                Driver Information
                            </th>
                            <th class="px-6 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-white/40">
                                Violation Details
                            </th>
                            <th class="px-6 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-white/40">
                                Location & Time
                            </th>
                            <th class="px-6 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-white/40 text-right">
                                Financials
                            </th>
                            <th class="px-6 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-white/40 text-center w-24">
                                Actions
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-white/5">
                        <!-- Row 1 -->
                        <tr class="group hover:bg-white/[0.03] transition-colors">
                            <!-- Zone 1: Driver (Merged Name, License, Validity) -->
                            <td class="px-6 py-5 sticky left-0 bg-dark-900 group-hover:bg-dark-900/80 backdrop-blur-sm transition-colors z-10 border-r border-white/5">
                                <div class="flex items-center gap-4">
                                    <div>
                                        <div class="font-bold text-white text-base">Jimboy Castillo</div>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="font-mono text-xs text-white/50 bg-white/5 px-1.5 py-0.5 rounded">S45-98-765432</span>
                                            <span class="text-[10px] font-bold uppercase text-emerald-400 flex items-center gap-1">
                                                <i class="fa-solid fa-shield-halved"></i> Valid
                                            </span>
                                        </div>
                                        <div class="text-[10px] text-white/30 mt-0.5">Exp: 21 Nov, 2030</div>
                                    </div>
                                </div>
                            </td>

                            <!-- Zone 2: Violation (Merged Type, Code, Instance) -->
                            <td class="px-6 py-5">
                                <div class="flex flex-col">
                                    <span class="text-sm font-semibold text-white mb-1">Disregarding Traffic Sign</span>
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20 font-mono">UV01</span>
                                        <span class="text-xs text-white/40">• 1st Offense</span>
                                    </div>
                                    <div class="text-xs text-white/30 mt-2 italic line-clamp-1">
                                        "Remarks 2"
                                    </div>
                                </div>
                            </td>

                            <!-- Zone 3: Context (Merged Place, Date, Time) -->
                            <td class="px-6 py-5">
                                <div class="flex flex-col gap-2">
                                    <div class="flex items-center gap-2 text-white/70 text-sm">
                                        <i class="fa-solid fa-location-dot w-4 text-center text-blue-500"></i>
                                        <span>Marikina City</span>
                                    </div>
                                    <div class="flex items-center gap-2 text-white/40 text-xs">
                                        <i class="fa-regular fa-calendar w-4 text-center"></i>
                                        <span>04 July, 2024</span>
                                        <span class="w-1 h-1 rounded-full bg-white/20"></span>
                                        <span>03:25 AM</span>
                                    </div>
                                </div>
                            </td>

                            <!-- Zone 4: Financials (Merged Fine, Penalties) -->
                            <td class="px-6 py-5 text-right">
                                <div class="text-lg font-bold text-white">₱ 1,000.00</div>
                                <div class="text-xs text-red-400/80">N/A</div>
                            </td>

                            <!-- Actions -->
                            <td class="px-6 py-5 text-center">
                                <button class="w-8 h-8 rounded-lg border border-white/10 flex items-center justify-center text-white/40 hover:bg-white/5 hover:text-blue-400 hover:border-blue-500/30 transition-all">
                                    <i class="fa-solid fa-pen-to-square text-xs"></i>
                                </button>
                            </td>
                        </tr>

                        <!-- Row 2 (Example with different data) -->
                        <tr class="group hover:bg-white/[0.03] transition-colors">
                            <td class="px-6 py-5 sticky left-0 bg-dark-900 group-hover:bg-dark-900/80 backdrop-blur-sm transition-colors z-10 border-r border-white/5">
                                <div class="flex items-center gap-4">
                                    <div>
                                        <div class="font-bold text-white text-base">Maria Santos</div>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="font-mono text-xs text-white/50 bg-white/5 px-1.5 py-0.5 rounded">N12-22-998877</span>
                                            <span class="text-[10px] font-bold uppercase text-red-400 flex items-center gap-1">
                                                <i class="fa-solid fa-triangle-exclamation"></i> Expired
                                            </span>
                                        </div>
                                        <div class="text-[10px] text-white/30 mt-0.5">Exp: 12 Jan, 2022</div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-5">
                                <div class="flex flex-col">
                                    <span class="text-sm font-semibold text-white mb-1">Over Speeding</span>
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-red-500/10 text-red-400 border border-red-500/20 font-mono">SPD-05</span>
                                        <span class="text-xs text-white/40">• 3rd Offense</span>
                                    </div>
                                    <div class="text-xs text-white/30 mt-2 italic line-clamp-1">
                                        "Driving 40kph over limit in school zone."
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-5">
                                <div class="flex flex-col gap-2">
                                    <div class="flex items-center gap-2 text-white/70 text-sm">
                                        <i class="fa-solid fa-location-dot w-4 text-center text-blue-500"></i>
                                        <span>Commonwealth Ave</span>
                                    </div>
                                    <div class="flex items-center gap-2 text-white/40 text-xs">
                                        <i class="fa-regular fa-calendar w-4 text-center"></i>
                                        <span>15 July, 2024</span>
                                        <span class="w-1 h-1 rounded-full bg-white/20"></span>
                                        <span>08:15 AM</span>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-5 text-right">
                                <div class="text-lg font-bold text-white">₱ 3,000.00</div>
                                <div class="text-xs text-red-400">Suspension (1 mo)</div>
                            </td>

                            <td class="px-6 py-5 text-center">
                                <button class="w-8 h-8 rounded-lg border border-white/10 flex items-center justify-center text-white/40 hover:bg-white/5 hover:text-blue-400 hover:border-blue-500/30 transition-all">
                                    <i class="fa-solid fa-pen-to-square text-xs"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Table Footer -->
            <div class="px-6 py-4 bg-white/[0.01] border-t border-white/5 flex justify-between items-center text-xs text-white/30">
                <span>Showing 2 of 124 entries</span>
                <div class="flex gap-2">
                    <button class="px-3 py-1 rounded border border-white/10 hover:bg-white/5 transition-colors">Prev</button>
                    <button class="px-3 py-1 rounded border border-blue-500/30 bg-blue-500/10 text-blue-400">1</button>
                    <button class="px-3 py-1 rounded border border-white/10 hover:bg-white/5 transition-colors">2</button>
                    <button class="px-3 py-1 rounded border border-white/10 hover:bg-white/5 transition-colors">3</button>
                    <button class="px-3 py-1 rounded border border-white/10 hover:bg-white/5 transition-colors">Next</button>
                </div>
            </div>
        </div>    </main>
</body>

</html>
