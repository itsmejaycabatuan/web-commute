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
    </style>
</head>

<body x-data="{ open: true, selectedDriver: 'all' }">

    @include('driver-manager.layout.sidebar')

    <main :class="open ? 'ml-72' : 'ml-20'" class="sidebar-transition p-8 md:p-12 min-h-screen">
        <div class="max-w-7xl mx-auto">

            <!-- Header -->
            <header class="mb-8 flex justify-between items-end">
                <div>
                    <h1 class="text-3xl md:text-4xl font-black tracking-tight text-white mb-2">Dashboard</h1>
                    <p class="text-gray-500 text-sm">Driver Management Tool Overview</p>
                </div>

                <div class="flex gap-3">
                    <button class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-500 text-white text-sm font-medium shadow-lg shadow-blue-900/40 transition-all">
                        <i class="fa-solid fa-plus mr-2"></i> New Entry
                    </button>
                </div>
            </header>

            <!-- Driver Data Summary (Dropdown) -->
            <div class="mb-8 flex items-center justify-between border-b border-white/5 pb-6">
                <div class="flex items-center gap-4">
                    <label class="text-sm font-medium text-gray-400">Driver Data Summary:</label>
                    <div class="relative">
                        <select x-model="selectedDriver" class="appearance-none bg-black/40 border border-white/10 rounded-lg py-2 pl-4 pr-10 text-white text-sm focus:outline-none focus:border-blue-500 transition-all cursor-pointer min-w-[250px]">
                            <option value="all">All Drivers</option>
                            <option value="1">John Doe</option>
                            <option value="2">Jane Smith</option>
                        </select>
                        <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-gray-400 flex align-center">
                            <i class="fa-solid fa-chevron-down text-xs"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div x-show="selectedDriver !== 'all'" x-transition class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                <div class="glass-panel p-6 rounded-2xl hover:bg-white/5 transition-colors group relative overflow-hidden">
                    <div class="absolute right-[-20px] top-[-20px] text-9xl text-white/5 -z-0 rotate-12 group-hover:text-blue-500/5 transition-colors">
                        <i class="fa-solid fa-id-badge"></i>
                    </div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-2 mb-2 text-blue-400">
                            <i class="fa-solid fa-id-badge"></i>
                            <h3 class="text-sm font-semibold uppercase tracking-wider">Driver ID</h3>
                        </div>
                        <p class="text-3xl font-bold mt-1">#4022</p>
                    </div>
                </div>

                <div class="glass-panel p-6 rounded-2xl hover:bg-white/5 transition-colors group relative overflow-hidden">
                    <div class="absolute right-[-20px] top-[-20px] text-9xl text-white/5 -z-0 rotate-12 group-hover:text-purple-500/5 transition-colors">
                        <i class="fa-solid fa-id-card"></i>
                    </div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-2 mb-2 text-purple-400">
                            <i class="fa-solid fa-id-card"></i>
                            <h3 class="text-sm font-semibold uppercase tracking-wider">License No.</h3>
                        </div>
                        <p class="text-3xl font-bold mt-1">M01-12-123456</p>
                    </div>
                </div>

                <div class="glass-panel p-6 rounded-2xl hover:bg-white/5 transition-colors group relative overflow-hidden">
                    <div class="absolute right-[-20px] top-[-20px] text-9xl text-white/5 -z-0 rotate-12 group-hover:text-green-500/5 transition-colors">
                        <i class="fa-solid fa-calendar-check"></i>
                    </div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-2 mb-2 text-green-400">
                            <i class="fa-solid fa-calendar-check"></i>
                            <h3 class="text-sm font-semibold uppercase tracking-wider">License Validity</h3>
                        </div>
                        <p class="text-3xl font-bold mt-1">September 6, 2028</p>

                    </div>
                </div>
            </div>

            <!-- 6 Stats Modules (Updated fields based on image) -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">

                <!-- 1. Total Days Driving -->
                <div class="glass-panel p-6 rounded-2xl hover:bg-white/5 transition-colors group relative overflow-hidden">
                    <div class="absolute right-[-20px] top-[-20px] text-9xl text-white/5 -z-0 rotate-12 group-hover:text-blue-500/5 transition-colors">
                        <i class="fa-solid fa-calendar-days"></i>
                    </div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-2 mb-2 text-blue-400">
                            <i class="fa-solid fa-calendar-check"></i>
                            <h3 class="text-sm font-semibold uppercase tracking-wider">Total Days Driving</h3>
                        </div>
                        <p class="text-3xl font-bold mt-1">1,284</p>

                    </div>
                </div>

                <!-- 2. Sick Days (Specific Request) -->
                <div class="glass-panel p-6 rounded-2xl hover:bg-white/5 transition-colors group relative overflow-hidden">
                    <div class="absolute right-[-20px] top-[-20px] text-9xl text-white/5 -z-0 rotate-12 group-hover:text-orange-500/5 transition-colors">
                        <i class="fa-solid fa-bed-pulse"></i>
                    </div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-2 mb-2 text-orange-400">
                            <i class="fa-solid fa-notes-medical"></i>
                            <h3 class="text-sm font-semibold uppercase tracking-wider">Sick Days</h3>
                        </div>
                        <p class="text-3xl font-bold mt-1">12</p>

                    </div>
                </div>

                <!-- 3. Vacation Days (Logical Pair) -->
                <div class="glass-panel p-6 rounded-2xl hover:bg-white/5 transition-colors group relative overflow-hidden">
                    <div class="absolute right-[-20px] top-[-20px] text-9xl text-white/5 -z-0 rotate-12 group-hover:text-teal-500/5 transition-colors">
                        <i class="fa-solid fa-plane-departure"></i>
                    </div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-2 mb-2 text-teal-400">
                            <i class="fa-solid fa-umbrella-beach"></i>
                            <h3 class="text-sm font-semibold uppercase tracking-wider">Vacation Days</h3>
                        </div>
                        <p class="text-3xl font-bold mt-1">28</p>
                    </div>
                </div>

                <!-- 4. Total Hours -->
                <div class="glass-panel p-6 rounded-2xl hover:bg-white/5 transition-colors group relative overflow-hidden">
                    <div class="absolute right-[-20px] top-[-20px] text-9xl text-white/5 -z-0 rotate-12 group-hover:text-purple-500/5 transition-colors">
                        <i class="fa-solid fa-stopwatch"></i>
                    </div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-2 mb-2 text-purple-400">
                            <i class="fa-solid fa-clock"></i>
                            <h3 class="text-sm font-semibold uppercase tracking-wider">Total Hours</h3>
                        </div>
                        <p class="text-3xl font-bold mt-1">3,402 <span class="text-lg font-normal text-gray-500">hrs</span></p>
                    </div>
                </div>

                <!-- 5. Violations -->
                <div class="glass-panel p-6 rounded-2xl hover:bg-white/5 transition-colors group relative overflow-hidden">
                    <div class="absolute right-[-20px] top-[-20px] text-9xl text-white/5 -z-0 rotate-12 group-hover:text-red-500/5 transition-colors">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-2 mb-2 text-red-400">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            <h3 class="text-sm font-semibold uppercase tracking-wider">Total Violations</h3>
                        </div>
                        <p class="text-3xl font-bold mt-1">8</p>
                    </div>
                </div>

                <!-- 6. Active Drivers -->
                <div class="glass-panel p-6 rounded-2xl hover:bg-white/5 transition-colors group relative overflow-hidden">
                    <div class="absolute right-[-20px] top-[-20px] text-9xl text-white/5 -z-0 rotate-12 group-hover:text-green-500/5 transition-colors">
                        <i class="fa-solid fa-money-bill"></i>
                    </div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-2 mb-2 text-green-400">
                            <i class="fa-solid fa-money-bill"></i>
                            <h3 class="text-sm font-semibold uppercase tracking-wider">Total Violation Fines</h3>
                        </div>
                        <p class="text-3xl font-bold mt-1">₱3,000.00</p>
                    </div>
                </div>
            </div>

            <!-- Split View: Time Sheet & Violations -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

                <!-- Time Sheet Section -->
                <section class="glass-panel rounded-2xl overflow-hidden flex flex-col h-[450px]">
                    <div class="p-5 border-b border-white/5 flex justify-between items-center bg-white/[0.02]">
                        <h3 class="font-bold text-lg flex items-center gap-2">
                            <i class="fa-regular fa-calendar text-blue-400"></i> Time Sheet
                        </h3>
                        <div class="flex gap-2">
                            <button class="text-xs text-gray-400 hover:text-white px-2 py-1 transition-colors">See All</button>
                        </div>
                    </div>

                    <div class="overflow-y-auto flex-1 p-2">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="text-gray-500 text-xs uppercase border-b border-white/5 bg-white/5 sticky top-0 backdrop-blur-md z-10">
                                    <th class="p-3 font-medium">Driver Name</th>
                                    <th class="p-3 font-medium">Date</th>
                                    <th class="p-3 font-medium">Hours</th>
                                    <th class="p-3 font-medium text-right">Type</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm divide-y divide-white/5">
                                <tr class="hover:bg-white/5 transition-colors group cursor-pointer">
                                    <td class="p-3 font-medium flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-full bg-gray-700 flex items-center justify-center text-[10px]">JD</div>
                                        J. Doe
                                    </td>
                                    <td class="p-3 text-gray-400">Oct 24, 2023</td>
                                    <td class="p-3 text-gray-400">08:30 - 17:00</td>
                                    <td class="p-3 text-right"><span class="px-2 py-0.5 rounded text-[10px] bg-blue-500/20 text-blue-400 font-semibold">Regular</span></td>
                                </tr>
                                <tr class="hover:bg-white/5 transition-colors group cursor-pointer">
                                    <td class="p-3 font-medium flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-full bg-gray-700 flex items-center justify-center text-[10px]">JS</div>
                                        J. Smith
                                    </td>
                                    <td class="p-3 text-gray-400">Oct 24, 2023</td>
                                    <td class="p-3 text-gray-400">08:00 - 18:30</td>
                                    <td class="p-3 text-right"><span class="px-2 py-0.5 rounded text-[10px] bg-orange-500/20 text-orange-400 font-semibold">Overtime</span></td>
                                </tr>
                                <tr class="hover:bg-white/5 transition-colors group cursor-pointer">
                                    <td class="p-3 font-medium flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-full bg-gray-700 flex items-center justify-center text-[10px]">MR</div>
                                        M. Ross
                                    </td>
                                    <td class="p-3 text-gray-400">Oct 23, 2023</td>
                                    <td class="p-3 text-gray-400">--</td>
                                    <td class="p-3 text-right"><span class="px-2 py-0.5 rounded text-[10px] bg-red-500/20 text-red-400 font-semibold">Sick</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Violations Section -->
                <section class="glass-panel rounded-2xl overflow-hidden flex flex-col h-[450px]">
                    <div class="p-5 border-b border-white/5 flex justify-between items-center bg-white/[0.02]">
                        <h3 class="font-bold text-lg flex items-center gap-2">
                            <i class="fa-solid fa-list-check text-red-400"></i> Violations
                        </h3>
                        <div class="flex gap-2">
                            <button class="text-xs text-gray-400 hover:text-white px-2 py-1 transition-colors">See All</button>
                        </div>
                    </div>

                    <div class="overflow-y-auto flex-1 p-4 space-y-3">
                        <!-- Violation Item -->
                        <div class="p-4 rounded-xl bg-white/5 border border-white/5 flex gap-4 items-center group hover:border-red-500/30 transition-all cursor-pointer">
                            <div class="w-10 h-10 rounded-lg bg-red-500/10 flex items-center justify-center text-red-500 shrink-0 group-hover:bg-red-500 group-hover:text-white transition-all">
                                <i class="fa-solid fa-gauge-high"></i>
                            </div>
                            <div class="flex-1">
                                <h4 class="text-sm font-semibold">Speeding Violation</h4>
                                <p class="text-xs text-gray-400">15mph over limit • Highway I-95</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-bold text-red-400">$150</p>
                                <p class="text-[10px] text-gray-500">Oct 24, 2:30 PM</p>
                            </div>
                        </div>

                        <!-- Violation Item -->
                        <div class="p-4 rounded-xl bg-white/5 border border-white/5 flex gap-4 items-center group hover:border-orange-500/30 transition-all cursor-pointer">
                            <div class="w-10 h-10 rounded-lg bg-orange-500/10 flex items-center justify-center text-orange-500 shrink-0 group-hover:bg-orange-500 group-hover:text-white transition-all">
                                <i class="fa-solid fa-mobile-screen-button"></i>
                            </div>
                            <div class="flex-1">
                                <h4 class="text-sm font-semibold">Mobile Device Usage</h4>
                                <p class="text-xs text-gray-400">Distracted driving detected</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-bold text-orange-400">$75</p>
                                <p class="text-[10px] text-gray-500">Oct 23, 9:15 AM</p>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <!-- Footer spacing -->
            <div class="h-20"></div>
        </div>
    </main>
</body>

</html>
