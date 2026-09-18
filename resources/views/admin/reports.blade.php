<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SmartCommute | System Reports</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    @include('partials.head-scripts')
    <style>
        .sidebar-transition {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }

        .dark input[type="date"]::-webkit-calendar-picker-indicator {
            filter: invert(0.4);
            cursor: pointer;
        }
    </style>
</head>

<body class="antialiased text-gray-900 dark:text-white" x-data="{
    currentTab: '{{ request('type') ?? 'financial' }}',
    generatedType: '{{ request('type') ?? 'financial' }}', // This tracks what data is actually loaded
    startDate: '{{ request('start_date') ?? \Carbon\Carbon::now()->subDays(7)->format('Y-m-d') }}',
    endDate: '{{ request('end_date') ?? \Carbon\Carbon::now()->format('Y-m-d') }}',
    isGenerating: false
}">

    <x-layout.sidebar />

    <main :class="$store.sidebar.open ? 'md:ml-72' : 'md:ml-20'"
        class="sidebar-transition pt-8 pr-4 sm:pr-8 pb-8 pl-4 sm:pl-8 min-h-screen mb-12">

        <!-- ── Header ── -->
        <div class="mb-8 flex flex-col sm:flex-row sm:items-end justify-between gap-4">
            <div>
                <span
                    class="text-[9px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Analytics</span>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-gray-900 dark:text-white mt-1">System
                    <span class="text-blue-500 dark:text-blue-400">Reports</span>
                </h1>
                <p class="text-[11px] text-gray-500 dark:text-[#555] mt-1">Generate and export analytical data for
                    operations.</p>
            </div>

            <!-- Active Export Button -->
            <a x-show="currentTab === generatedType" href="{{ route('reports.export', request()->query()) }}"
                x-transition.opacity.duration.200ms
                class="flex items-center gap-2 px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-[9px] font-bold uppercase tracking-widest text-white transition active:scale-[0.98] shadow-lg shadow-blue-500/20">
                <i class="fa-solid fa-download text-[8px]"></i>
                <span>Export CSV</span>
            </a>

            <!-- Disabled Button (Shows when tab is changed but not generated) -->
            <button x-show="currentTab !== generatedType" x-cloak x-transition.opacity.duration.200ms disabled
                class="flex items-center gap-2 px-4 py-2 rounded-xl bg-gray-200 dark:bg-[#222] text-gray-500 dark:text-gray-500 text-[9px] font-bold uppercase tracking-widest cursor-not-allowed opacity-60 transition border border-transparent dark:border-gray-800">
                <i class="fa-solid fa-download text-[8px]"></i>
                <!-- Dynamic Text: "Generate [Tab Name] Report First" -->
                <span
                    x-text="'Generate ' + (currentTab.charAt(0).toUpperCase() + currentTab.slice(1)) + ' Report First'"></span>
            </button>
        </div>

        <!-- ── Report Controls (Trigger & Selection) ── -->
        <div class="glass-card p-4 sm:p-6 rounded-[1.25rem] mb-6">
            <form action="{{ route('reports.generate') }}" method="GET"
                class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">

                <!-- Report Type -->
                <div class="flex flex-col gap-2">
                    <label class="text-[8px] font-bold uppercase tracking-widest text-gray-400 dark:text-[#444]">Report
                        Type</label>
                    <div class="relative">
                        <i
                            class="fa-solid fa-chart-pie absolute left-3.5 top-1/2 -translate-y-1/2 text-[10px] text-gray-400 dark:text-[#333]"></i>
                        <!-- REMOVED @change HERE -->
                        <select name="type" x-model="currentTab"
                            class="w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] rounded-xl text-[11px] text-gray-900 dark:text-white focus:outline-none focus:border-blue-500 dark:focus:border-blue-500 transition appearance-none cursor-pointer">
                            <option value="financial">Financial Overview</option>
                            <option value="driver">Driver Performance</option>
                            <option value="maintenance">Maintenance & Fleet</option>
                        </select>
                        <i
                            class="fa-solid fa-chevron-down absolute right-3.5 top-1/2 -translate-y-1/2 text-[8px] text-gray-400 dark:text-[#333] pointer-events-none"></i>
                    </div>
                </div>

                <!-- Date Start -->
                <div class="flex flex-col gap-2">
                    <label class="text-[8px] font-bold uppercase tracking-widest text-gray-400 dark:text-[#444]">Start
                        Date</label>
                    <div class="relative">
                        <i
                            class="fa-regular fa-calendar absolute left-3.5 top-1/2 -translate-y-1/2 text-[10px] text-gray-400 dark:text-[#333]"></i>
                        <!-- REMOVED @change HERE -->
                        <input type="date" name="start_date" x-model="startDate"
                            class="w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] rounded-xl text-[11px] text-gray-900 dark:text-white focus:outline-none focus:border-blue-500 transition">
                    </div>
                </div>

                <!-- Date End -->
                <div class="flex flex-col gap-2">
                    <label class="text-[8px] font-bold uppercase tracking-widest text-gray-400 dark:text-[#444]">End
                        Date</label>
                    <div class="relative">
                        <i
                            class="fa-regular fa-calendar absolute left-3.5 top-1/2 -translate-y-1/2 text-[10px] text-gray-400 dark:text-[#333]"></i>
                        <!-- REMOVED @change HERE -->
                        <input type="date" name="end_date" x-model="endDate"
                            class="w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] rounded-xl text-[11px] text-gray-900 dark:text-white focus:outline-none focus:border-blue-500 transition">
                    </div>
                </div>

                <!-- Generate Button -->
                <button type="submit" :disabled="isGenerating"
                    class="h-[42px] flex items-center justify-center gap-2 rounded-xl bg-gray-900 dark:bg-white hover:bg-gray-800 dark:hover:bg-gray-200 text-[9px] font-bold uppercase tracking-widest text-white dark:text-black transition active:scale-[0.98]">
                    <i class="fa-solid fa-bolt text-[8px]" :class="{ 'animate-pulse': isGenerating }"></i>
                    <span x-text="isGenerating ? 'Generating...' : 'Generate Report'"></span>
                </button>
            </form>
        </div>
        <!-- ══════════ REPORT CONTENT AREAS ══════════ -->

        <!-- ── 1. FINANCIAL REPORT ── -->
        <div x-show="currentTab === 'financial'" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">

            @isset($financialStats)
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                    <div class="glass-card p-5 rounded-[1.25rem] border-l-2 border-l-blue-500">
                        <span class="text-[8px] font-bold uppercase tracking-widest text-gray-400 dark:text-[#444]">Total
                            Revenue</span>
                        <h3 class="text-2xl font-black text-gray-900 dark:text-white mt-1">
                            ₱{{ number_format($financialStats['total_revenue'], 2) }}</h3>
                    </div>
                    <div class="glass-card p-5 rounded-[1.25rem] border-l-2 border-l-emerald-500">
                        <span class="text-[8px] font-bold uppercase tracking-widest text-gray-400 dark:text-[#444]">Wallet
                            Inflow</span>
                        <h3 class="text-2xl font-black text-gray-900 dark:text-white mt-1">
                            ₱{{ number_format($financialStats['total_topups'], 2) }}</h3>
                    </div>
                    <div class="glass-card p-5 rounded-[1.25rem] border-l-2 border-l-purple-500">
                        <span
                            class="text-[8px] font-bold uppercase tracking-widest text-gray-400 dark:text-[#444]">Transactions</span>
                        <h3 class="text-2xl font-black text-gray-900 dark:text-white mt-1">
                            {{ number_format($financialStats['tx_count']) }}</h3>
                    </div>
                </div>

                <div class="glass-card p-6 rounded-[1.25rem]">
                    <h3 class="text-xs font-bold text-gray-400 dark:text-[#555] uppercase tracking-widest mb-4">Revenue
                        Trend</h3>
                    <div class="relative h-64">
                        <canvas id="financialChart"></canvas>
                    </div>
                </div>
            @else
                <!-- EMPTY PLACEHOLDER -->
                <div
                    class="glass-card p-8 sm:p-12 rounded-[1.25rem] flex flex-col items-center justify-center text-center min-h-[400px]">
                    <div
                        class="w-16 h-16 rounded-2xl bg-gray-100 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] flex items-center justify-center mb-4">
                        <i class="fa-solid fa-newspaper text-xl text-gray-300 dark:text-[#333]"></i>
                    </div>
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-2">Financial Report Not Generated</h3>
                    <p class="text-[11px] text-gray-500 dark:text-[#555] max-w-xs">Please select your date range and click
                        "Generate Report" to view revenue data.</p>
                </div>
            @endisset
        </div>

        <!-- ── 2. DRIVER PERFORMANCE REPORT ── -->
        <div x-show="currentTab === 'driver'" x-cloak x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">

            @isset($driverStats)
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Stats -->
                    <div class="space-y-4">
                        <div class="glass-card p-5 rounded-[1.25rem] flex items-center justify-between">
                            <div>
                                <span
                                    class="text-[8px] font-bold uppercase tracking-widest text-gray-400 dark:text-[#444]">Total
                                    Drivers</span>
                                <h3 class="text-2xl font-black text-gray-900 dark:text-white mt-1">
                                    {{ number_format($driverStats['total']) }}</h3>
                            </div>
                            <div
                                class="w-10 h-10 rounded-full bg-blue-500/10 flex items-center justify-center text-blue-500">
                                <i class="fa-solid fa-users"></i>
                            </div>
                        </div>
                        <div class="glass-card p-5 rounded-[1.25rem] flex items-center justify-between">
                            <div>
                                <span
                                    class="text-[8px] font-bold uppercase tracking-widest text-gray-400 dark:text-[#444]">Active
                                    Duty</span>
                                <h3 class="text-2xl font-black text-gray-900 dark:text-white mt-1">
                                    {{ number_format($driverStats['active']) }}</h3>
                            </div>
                            <div
                                class="w-10 h-10 rounded-full bg-emerald-500/10 flex items-center justify-center text-emerald-500">
                                <i class="fa-solid fa-id-card"></i>
                            </div>
                        </div>

                        <div class="glass-card p-5 rounded-[1.25rem]">
                            <h3 class="text-xs font-bold text-gray-400 dark:text-[#555] uppercase tracking-widest mb-4">
                                Status Distribution</h3>
                            <div class="relative h-48">
                                <canvas id="driverStatusChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="glass-card rounded-[1.25rem] overflow-hidden">
                        <div class="p-5 border-b border-gray-200 dark:border-[#1e1e1e]">
                            <h3 class="text-xs font-bold text-gray-900 dark:text-white">Top Performers (Hours)</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead>
                                    <tr
                                        class="text-[8px] uppercase tracking-[0.15em] text-gray-400 dark:text-[#444] bg-gray-50 dark:bg-[#0a0a0a]">
                                        <th class="px-5 py-3 font-bold">Driver</th>
                                        <th class="px-5 py-3 font-bold">Status</th>
                                        <th class="px-5 py-3 font-bold text-right">Hours</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-[#1a1a1a]">
                                    @forelse($topPerformers ?? [] as $driver)
                                        <tr class="table-row hover:bg-gray-50 dark:hover:bg-[#111] transition">
                                            <td class="px-5 py-3">
                                                <div class="flex items-center gap-3">
                                                    <div
                                                        class="w-8 h-8 rounded-full bg-gray-200 dark:bg-[#333] flex items-center justify-center text-[10px] font-bold text-gray-600 dark:text-gray-300">
                                                        {{ strtoupper(substr($driver['name'], 0, 1)) }}
                                                    </div>
                                                    <span
                                                        class="text-[11px] font-bold text-gray-700 dark:text-[#ccc] truncate max-w-[150px]">{{ $driver['name'] }}</span>
                                                </div>
                                            </td>
                                            <td class="px-5 py-3">
                                                @if ($driver['status'] === 'Active')
                                                    <span
                                                        class="text-[8px] bg-emerald-500/10 text-emerald-600 border border-emerald-500/20 px-2 py-0.5 rounded-md font-bold uppercase">Active</span>
                                                @else
                                                    <span
                                                        class="text-[8px] bg-gray-500/10 text-gray-600 border border-gray-500/20 px-2 py-0.5 rounded-md font-bold uppercase">Inactive</span>
                                                @endif
                                            </td>
                                            <td class="px-5 py-3 text-right">
                                                <span
                                                    class="text-[11px] font-bold text-gray-900 dark:text-white">{{ number_format($driver['hours_worked'], 1) }}h</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3"
                                                class="px-5 py-8 text-center text-[10px] text-gray-400 dark:text-[#555]">No
                                                driver performance data available.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @else
                <!-- EMPTY PLACEHOLDER -->
                <div
                    class="glass-card p-8 sm:p-12 rounded-[1.25rem] flex flex-col items-center justify-center text-center min-h-[400px]">
                    <div
                        class="w-16 h-16 rounded-2xl bg-gray-100 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] flex items-center justify-center mb-4">
                        <i class="fa-solid fa-id-card text-xl text-gray-300 dark:text-[#333]"></i>
                    </div>
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-2">Driver Report Not Generated</h3>
                    <p class="text-[11px] text-gray-500 dark:text-[#555] max-w-xs">Please select "Driver Performance" and
                        click "Generate Report" to view driver stats.</p>
                </div>
            @endisset
        </div>

        <!-- ── 3. MAINTENANCE & FLEET REPORT ── -->
        <div x-show="currentTab === 'maintenance'" x-cloak x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">

            @isset($maintStats)
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                    <div class="glass-card p-5 rounded-[1.25rem] border-l-2 border-l-amber-500">
                        <span class="text-[8px] font-bold uppercase tracking-widest text-gray-400 dark:text-[#444]">Pending
                            Tasks</span>
                        <h3 class="text-2xl font-black text-amber-500 dark:text-amber-400 mt-1">
                            {{ number_format($maintStats['pending']) }}</h3>
                    </div>
                    <div class="glass-card p-5 rounded-[1.25rem] border-l-2 border-l-emerald-500">
                        <span
                            class="text-[8px] font-bold uppercase tracking-widest text-gray-400 dark:text-[#444]">Completed
                            (This Month)</span>
                        <h3 class="text-2xl font-black text-emerald-500 dark:text-emerald-400 mt-1">
                            {{ number_format($maintStats['completed']) }}</h3>
                    </div>
                    <div class="glass-card p-5 rounded-[1.25rem] border-l-2 border-l-red-500">
                        <span
                            class="text-[8px] font-bold uppercase tracking-widest text-gray-400 dark:text-[#444]">Overdue</span>
                        <h3 class="text-2xl font-black text-red-500 dark:text-red-400 mt-1">
                            {{ number_format($maintStats['overdue']) }}</h3>
                    </div>
                </div>

                <div class="glass-card rounded-[1.25rem] overflow-hidden">
                    <div class="p-5 border-b border-gray-200 dark:border-[#1e1e1e] flex justify-between items-center">
                        <h3 class="text-xs font-bold text-gray-900 dark:text-white">Upcoming Maintenance Schedule</h3>
                        <a href="#"
                            class="text-[8px] font-bold uppercase text-blue-500 dark:text-blue-400 cursor-pointer">View
                            Calendar</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr
                                    class="text-[8px] uppercase tracking-[0.15em] text-gray-400 dark:text-[#444] bg-gray-50 dark:bg-[#0a0a0a]">
                                    <th class="px-5 py-3 font-bold">Vehicle</th>
                                    <th class="px-5 py-3 font-bold">Task</th>
                                    <th class="px-5 py-3 font-bold">Due Date</th>
                                    <th class="px-5 py-3 font-bold">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-[#1a1a1a]">
                                @forelse($upcomingSchedule ?? [] as $task)
                                    <tr class="table-row hover:bg-gray-50 dark:hover:bg-[#111] transition">
                                        <td class="px-5 py-3 text-[11px] font-bold text-gray-700 dark:text-[#ccc]">
                                            {{ $task['vehicle_plate'] }}</td>
                                        <td class="px-5 py-3 text-[11px] text-gray-500 dark:text-[#888]">
                                            {{ $task['task_name'] }}</td>
                                        <td class="px-5 py-3 text-[11px] text-gray-500 dark:text-[#888]">
                                            {{ \Carbon\Carbon::parse($task['due_date'])->format('M d, Y') }}</td>
                                        <td class="px-5 py-3">
                                            @if ($task['is_overdue'])
                                                <span
                                                    class="text-[8px] bg-red-500/10 text-red-600 border border-red-500/20 px-2 py-0.5 rounded-md font-bold uppercase">Overdue</span>
                                            @else
                                                <span
                                                    class="text-[8px] bg-amber-500/10 text-amber-600 border border-amber-500/20 px-2 py-0.5 rounded-md font-bold uppercase">Scheduled</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4"
                                            class="px-5 py-8 text-center text-[10px] text-gray-400 dark:text-[#555]">No
                                            maintenance tasks scheduled.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <!-- EMPTY PLACEHOLDER -->
                <div
                    class="glass-card p-8 sm:p-12 rounded-[1.25rem] flex flex-col items-center justify-center text-center min-h-[400px]">
                    <div
                        class="w-16 h-16 rounded-2xl bg-gray-100 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] flex items-center justify-center mb-4">
                        <i class="fa-solid fa-wrench text-xl text-gray-300 dark:text-[#333]"></i>
                    </div>
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-2">Maintenance Report Not Generated</h3>
                    <p class="text-[11px] text-gray-500 dark:text-[#555] max-w-xs">Please select "Maintenance & Fleet" and
                        click "Generate Report" to view vehicle status.</p>
                </div>
            @endisset
        </div>

    </main>

    <!-- ══════════ CHARTS LOGIC ══════════ -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const isDark = document.documentElement.classList.contains('dark');

            Chart.defaults.font.family = "'Inter', sans-serif";
            Chart.defaults.color = isDark ? '#444' : '#64748b';

            function getGridColor() {
                return document.documentElement.classList.contains('dark') ? '#1a1a1a' : '#f1f5f9';
            }

            @isset($financialStats)
                // 1. Financial Chart (Bar)
                const finCtx = document.getElementById('financialChart');
                const chartLabels = @json($chartLabels ?? []);
                const chartRevenue = @json($chartRevenue ?? []);

                // Only render chart if there is data
                if (finCtx && chartLabels.length > 0) {
                    new Chart(finCtx, {
                        type: 'bar',
                        data: {
                            labels: chartLabels,
                            datasets: [{
                                label: 'Revenue',
                                data: chartRevenue,
                                backgroundColor: '#3b82f6',
                                borderRadius: 4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                }
                            },
                            scales: {
                                y: {
                                    grid: {
                                        color: getGridColor(),
                                        border: {
                                            display: false
                                        }
                                    },
                                    ticks: {
                                        callback: (val) => '₱' + val
                                    },
                                    beginAtZero: true
                                },
                                x: {
                                    grid: {
                                        display: false
                                    },
                                    border: {
                                        display: false
                                    }
                                }
                            }
                        }
                    });
                } else if (finCtx) {
                    // Optional: Show "No Data" text inside the canvas container via HTML/JS
                    finCtx.parentNode.innerHTML +=
                        '<div class="absolute inset-0 flex items-center justify-center text-gray-400 text-xs">No revenue data for selected range.</div>';
                }
            @endisset

            @isset($driverStats)
                // 2. Driver Status Chart (Doughnut)
                const drvCtx = document.getElementById('driverStatusChart');
                const driverStatusLabels = @json($driverStatusLabels ?? []);
                const driverStatusData = @json($driverStatusData ?? []);

                if (drvCtx && driverStatusData.length > 0) {
                    new Chart(drvCtx, {
                        type: 'doughnut',
                        data: {
                            labels: driverStatusLabels,
                            datasets: [{
                                data: driverStatusData,
                                backgroundColor: ['#10b981', '#f59e0b', '#64748b'],
                                borderWidth: 0
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '70%',
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        usePointStyle: true,
                                        boxWidth: 6
                                    }
                                }
                            }
                        }
                    });
                }
            @endisset

            // Theme Reactivity
            const observer = new MutationObserver(() => {
                Chart.defaults.color = document.documentElement.classList.contains('dark') ? '#444' :
                    '#64748b';
            });
            observer.observe(document.documentElement, {
                attributes: true
            });
        });
    </script>
</body>

</html>
