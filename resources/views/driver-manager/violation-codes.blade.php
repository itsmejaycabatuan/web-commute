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
                    <h2 class="text-3xl font-black tracking-tight mb-2">Violation <span class="text-blue-500">Codes</span></h2>
                    <p class="text-white/40 text-sm">Reference guide for traffic violation fines and penalties.</p>
                </div>
                <div class="flex gap-3">
                    <button class="px-4 py-2 rounded-lg bg-white/5 hover:bg-white/10 text-white text-sm font-medium transition-all border border-white/10">
                        <i class="fa-solid fa-download mr-2"></i> Export PDF
                    </button>
                    <button class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-500 text-white text-sm font-medium shadow-lg shadow-blue-900/40 transition-all">
                        <i class="fa-solid fa-plus mr-2"></i> Add Code
                    </button>
                </div>
            </header>

            <div class="glass rounded-[2rem] overflow-hidden border border-white/5">
                <div class="overflow-x-auto">
                    <table class="w-full text-left whitespace-nowrap">
                        <thead>
                            <tr class="border-b border-white/5 bg-white/5">
                                <th class="px-6 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-blue-500 sticky left-0 bg-dark-900/95 backdrop-blur-sm z-10">Code</th>
                                <th class="px-6 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-blue-500">Violation Name</th>
                                <th class="px-6 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-blue-500 text-right w-32">1st Offense</th>
                                <th class="px-6 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-blue-500 text-right w-32">2nd Offense</th>
                                <th class="px-6 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-blue-500 text-right w-32">3rd Offense</th>
                                <th class="px-6 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-red-500 text-right w-32">4th +</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <!-- Row 1 -->
                            <tr class="hover:bg-white/[0.02] transition-colors group">
                                <td class="px-6 py-4 sticky left-0 bg-dark-900 group-hover:bg-dark-900/80 backdrop-blur-sm transition-colors z-10">
                                    <span class="inline-block px-2 py-1 rounded bg-blue-500/10 text-blue-400 border border-blue-500/20 font-mono text-sm font-bold">
                                        UV01
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-semibold text-white">Disregarding Traffic Sign</td>
                                <td class="px-6 py-4 text-right font-mono text-white/70">₱ 1,000</td>
                                <td class="px-6 py-4 text-right font-mono text-white/70">₱ 2,000</td>
                                <td class="px-6 py-4 text-right font-mono text-white/70">₱ 3,000</td>
                                <td class="px-6 py-4 text-right font-mono text-white/90 font-bold">₱ 10,000</td>
                            </tr>

                            <!-- Row 2 -->
                            <tr class="hover:bg-white/[0.02] transition-colors group">
                                <td class="px-6 py-4 sticky left-0 bg-dark-900 group-hover:bg-dark-900/80 backdrop-blur-sm transition-colors z-10">
                                    <span class="inline-block px-2 py-1 rounded bg-blue-500/10 text-blue-400 border border-blue-500/20 font-mono text-sm font-bold">
                                        UV02
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-semibold text-white">Illegal Parking</td>
                                <td class="px-6 py-4 text-right font-mono text-white/70">₱ 500</td>
                                <td class="px-6 py-4 text-right font-mono text-white/70">₱ 1,000</td>
                                <td class="px-6 py-4 text-right font-mono text-white/70">₱ 1,500</td>
                                <td class="px-6 py-4 text-right font-mono text-white/90 font-bold">₱ 2,000</td>
                            </tr>

                            <!-- Row 3 -->
                            <tr class="hover:bg-white/[0.02] transition-colors group">
                                <td class="px-6 py-4 sticky left-0 bg-dark-900 group-hover:bg-dark-900/80 backdrop-blur-sm transition-colors z-10">
                                    <span class="inline-block px-2 py-1 rounded bg-blue-500/10 text-blue-400 border border-blue-500/20 font-mono text-sm font-bold">
                                        UV03
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-semibold text-white">Reckless Driving</td>
                                <td class="px-6 py-4 text-right font-mono text-white/70">₱ 2,000</td>
                                <td class="px-6 py-4 text-right font-mono text-white/70">₱ 5,000</td>
                                <td class="px-6 py-4 text-right font-mono text-white/70">₱ 10,000</td>
                                <td class="px-6 py-4 text-right font-mono text-red-400 font-bold border border-red-500/20 rounded bg-red-500/5">
                                    Revocation
                                </td>
                            </tr>

                            <!-- Row 4 -->
                            <tr class="hover:bg-white/[0.02] transition-colors group">
                                <td class="px-6 py-4 sticky left-0 bg-dark-900 group-hover:bg-dark-900/80 backdrop-blur-sm transition-colors z-10">
                                    <span class="inline-block px-2 py-1 rounded bg-blue-500/10 text-blue-400 border border-blue-500/20 font-mono text-sm font-bold">
                                        UV04
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-semibold text-white">Driving without License</td>
                                <td class="px-6 py-4 text-right font-mono text-white/70">₱ 3,000</td>
                                <td class="px-6 py-4 text-right font-mono text-white/70">₱ 5,000</td>
                                <td class="px-6 py-4 text-right font-mono text-white/70">₱ 10,000</td>
                                <td class="px-6 py-4 text-right font-mono text-white/90 font-bold">₱ 15,000</td>
                            </tr>

                             <!-- Row 5 -->
                             <tr class="hover:bg-white/[0.02] transition-colors group">
                                <td class="px-6 py-4 sticky left-0 bg-dark-900 group-hover:bg-dark-900/80 backdrop-blur-sm transition-colors z-10">
                                    <span class="inline-block px-2 py-1 rounded bg-blue-500/10 text-blue-400 border border-blue-500/20 font-mono text-sm font-bold">
                                        UV05
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-semibold text-white">Over Speeding</td>
                                <td class="px-6 py-4 text-right font-mono text-white/70">₱ 1,500</td>
                                <td class="px-6 py-4 text-right font-mono text-white/70">₱ 3,000</td>
                                <td class="px-6 py-4 text-right font-mono text-white/70">₱ 5,000</td>
                                <td class="px-6 py-4 text-right font-mono text-white/90 font-bold">₱ 10,000</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</body>

</html>
