<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartCommute | Driver Manager</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: { extend: { fontFamily: { sans: ['Inter', 'sans-serif'] } } }
        }
    </script>
    <style>
        body, html { margin: 0; padding: 0; height: 100%; font-family: 'Inter', sans-serif; overflow-x: hidden; background: #050505; }
        .glass-panel { background: #111111 !important; border: 1px solid #1e1e1e; box-shadow: 0 4px 24px rgba(0,0,0,0.6); }
        .glass-card { background: #161616; border: 1px solid #222222; box-shadow: 0 4px 24px rgba(0,0,0,0.5); }
        .sidebar-transition { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        .table-row { transition: all 0.2s ease; }
        .table-row:hover { background: #1a1a1a; }

        ::-webkit-scrollbar { width: 3px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #333; border-radius: 10px; }
    </style>
</head>

<body class="antialiased text-white" x-data="dashboardData()">

    <x-layout.sidebar :menu-items="$sidebarMenu" />

    <main :class="open ? 'md:ml-[270px]' : 'md:ml-[76px]'" class="sidebar-transition pt-8 pr-4 sm:pr-8 pb-8 pl-4 sm:pl-8 min-h-screen mb-12">
        <div class="max-w-7xl mx-auto">

            <!-- ── Mobile: Identity Card ── -->
            <div class="lg:hidden mb-5">
                <div class="glass-card p-4 rounded-[1.25rem]">
                    <div class="flex items-center gap-3.5">
                        <div class="w-11 h-11 bg-blue-600 rounded-xl flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-gauge-high text-white text-sm"></i>
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-sm font-bold text-white truncate">Driver Manager</h2>
                            <p class="text-[10px] text-[#555] truncate">{{ Auth::user()->email }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 pt-3.5 mt-3.5 border-t border-[#1e1e1e]">
                        <i class="fa-solid fa-id-badge text-[8px] text-blue-400"></i>
                        <span class="text-[10px] text-[#888] font-bold">Driver Oversight</span>
                        <span class="text-[#333]">•</span>
                        <span class="font-mono text-[9px] text-[#444]">Manager</span>
                    </div>
                </div>
            </div>

            <!-- ── Page Header (desktop) ── -->
            <div class="hidden lg:block mb-8">
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="text-[9px] font-bold uppercase tracking-[0.15em] text-[#444]">Welcome back,</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight">Driver Manager</h1>
                <p class="text-[11px] text-[#555] mt-1 flex items-center gap-2">
                    <i class="fa-solid fa-id-badge text-[9px] text-blue-400"></i>
                    Role: <span class="text-[#888] font-bold">Driver Manager</span>
                    <span class="text-[#333]">•</span>
                    <span class="font-mono text-[10px] text-[#444]">{{ Auth::user()->email }}</span>
                </p>
            </div>

            <!-- ── Driver Search Selector ── -->
            <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] mb-5 sm:mb-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-lg bg-[#111] border border-[#1e1e1e] flex items-center justify-center">
                            <i class="fa-solid fa-magnifying-glass text-[9px] text-[#555]"></i>
                        </div>
                        <span class="text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-[#555]">Driver Data Summary</span>
                    </div>

                    <div @click.away="closeDropdown()" class="relative w-full sm:w-auto">
                        <div class="flex items-center gap-2 bg-[#111] border border-[#222] rounded-xl px-4 py-2.5 min-w-[280px] cursor-text transition-all"
                             :class="dropdownOpen ? 'border-blue-500/50 ring-1 ring-blue-500/20' : 'hover:border-[#333]'"
                             @click="openDropdown()">
                            <i class="fa-solid fa-magnifying-glass text-[#555] text-[10px] flex-shrink-0"></i>
                            <input type="text"
                                   x-model="searchQuery"
                                   @input="dropdownOpen = true"
                                   @focus="openDropdown()"
                                   @keydown.escape="closeDropdown()"
                                   @keydown.enter.prevent="selectFirstMatch()"
                                   placeholder="Search drivers..."
                                   class="bg-transparent text-white text-[11px] font-medium focus:outline-none w-full placeholder-[#444]">
                            <button x-show="selectedDriver"
                                    @click.stop="selectAll()"
                                    x-transition
                                    class="text-[#555] hover:text-white transition-colors flex-shrink-0">
                                <i class="fa-solid fa-xmark text-[10px]"></i>
                            </button>
                        </div>

                        <!-- Dropdown -->
                        <div x-show="dropdownOpen"
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 -translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100 translate-y-0"
                             x-transition:leave-end="opacity-0 -translate-y-1"
                             class="absolute top-full left-0 mt-2 w-full bg-[#111] border border-[#1e1e1e] rounded-xl shadow-2xl shadow-black/80 overflow-hidden z-50">

                            <button @click="selectAll()"
                                    :class="!selectedDriver ? 'bg-blue-500/10 text-blue-400' : 'text-[#888] hover:bg-[#1a1a1a]'"
                                    class="w-full px-4 py-3 text-left text-[11px] font-medium flex items-center gap-3 transition-colors">
                                <div class="w-7 h-7 rounded-lg bg-[#1a1a1a] border border-[#222] flex items-center justify-center">
                                    <i class="fa-solid fa-users text-[9px]"></i>
                                </div>
                                <span class="font-bold">All Drivers</span>
                                <span class="ml-auto text-[9px] font-bold px-1.5 py-0.5 rounded-md bg-[#1a1a1a] border border-[#222] text-[#555]" x-text="drivers.length"></span>
                            </button>

                            <div class="h-px bg-[#1e1e1e]"></div>

                            <div class="max-h-[280px] overflow-y-auto">
                                <template x-for="driver in filteredDrivers" :key="driver.id">
                                    <button @click="selectDriver(driver)"
                                            :class="selectedDriver && selectedDriver.id === driver.id ? 'bg-blue-500/10 text-blue-400' : 'text-[#888] hover:bg-[#1a1a1a]'"
                                            class="w-full px-4 py-2.5 text-left text-[11px] font-medium flex items-center gap-3 transition-colors">
                                        <div class="w-7 h-7 rounded-lg bg-[#1a1a1a] border border-[#222] flex items-center justify-center text-[9px] font-bold flex-shrink-0"
                                             x-text="getInitials(driver.name)"></div>
                                        <span x-text="driver.name"></span>
                                    </button>
                                </template>

                                <div x-show="filteredDrivers.length === 0 && searchQuery.trim()"
                                     class="px-4 py-8 text-center text-[#333] text-[11px]">
                                    <i class="fa-solid fa-magnifying-glass text-sm mb-2 block"></i>
                                    No drivers match "<span x-text="searchQuery"></span>"
                                </div>
                            </div>
                        </div>
                    </div>

                    <div x-show="selectedDriver" x-transition class="flex items-center gap-2 shrink-0">
                        <div class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></div>
                        <span class="text-[9px] font-bold uppercase tracking-[0.15em] text-[#444]">Filtered</span>
                    </div>
                </div>
            </div>

            <!-- ── Driver Info Cards (only when a specific driver is selected) ── -->
            <div x-show="selectedDriver"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 -translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 -translate-y-2"
                 class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3 sm:gap-4 mb-5 sm:mb-6">

                <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-blue-500">
                    <div class="flex items-center gap-2 mb-2 sm:mb-3">
                        <div class="w-6 h-6 rounded-md bg-blue-500/10 flex items-center justify-center">
                            <i class="fa-solid fa-id-badge text-[8px] text-blue-400"></i>
                        </div>
                        <span class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Driver Code</span>
                    </div>
                    <span class="text-xl sm:text-2xl font-black tracking-tight" x-text="selectedDriver.driver_code"></span>
                </div>

                <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-purple-500">
                    <div class="flex items-center gap-2 mb-2 sm:mb-3">
                        <div class="w-6 h-6 rounded-md bg-purple-500/10 flex items-center justify-center">
                            <i class="fa-solid fa-id-card text-[8px] text-purple-400"></i>
                        </div>
                        <span class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">License No.</span>
                    </div>
                    <span class="text-xl sm:text-2xl font-black tracking-tight" x-text="selectedDriver.license_number"></span>
                </div>

                <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-emerald-500 col-span-1 sm:col-span-2 xl:col-span-1">
                    <div class="flex items-center gap-2 mb-2 sm:mb-3">
                        <div class="w-6 h-6 rounded-md bg-emerald-500/10 flex items-center justify-center">
                            <i class="fa-solid fa-calendar-check text-[8px] text-emerald-400"></i>
                        </div>
                        <span class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">License Validity</span>
                    </div>
                    <span class="text-xl sm:text-2xl font-black tracking-tight" x-text="selectedDriver.expiration_date"></span>
                </div>
            </div>

            <!-- ══════════ 6 STAT CARDS ══════════ -->
            <div class="grid grid-cols-2 sm:grid-cols-2 xl:grid-cols-3 gap-3 sm:gap-4 mb-5 sm:mb-6">

                <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-blue-500">
                    <div class="flex items-center gap-2 mb-2 sm:mb-3">
                        <div class="w-6 h-6 rounded-md bg-blue-500/10 flex items-center justify-center">
                            <i class="fa-solid fa-calendar-check text-[8px] text-blue-400"></i>
                        </div>
                        <span class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Total Days Driving</span>
                    </div>
                    <span class="text-xl sm:text-2xl font-black tracking-tight" x-text="formatNum(stats.drivingDays)"></span>
                </div>

                <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-orange-500">
                    <div class="flex items-center gap-2 mb-2 sm:mb-3">
                        <div class="w-6 h-6 rounded-md bg-orange-500/10 flex items-center justify-center">
                            <i class="fa-solid fa-notes-medical text-[8px] text-orange-400"></i>
                        </div>
                        <span class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Sick Days</span>
                    </div>
                    <span class="text-xl sm:text-2xl font-black tracking-tight" x-text="formatNum(stats.sickDays)"></span>
                </div>

                <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-teal-500 col-span-2 xl:col-span-1">
                    <div class="flex items-center gap-2 mb-2 sm:mb-3">
                        <div class="w-6 h-6 rounded-md bg-teal-500/10 flex items-center justify-center">
                            <i class="fa-solid fa-umbrella-beach text-[8px] text-teal-400"></i>
                        </div>
                        <span class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Vacation Days</span>
                    </div>
                    <span class="text-xl sm:text-2xl font-black tracking-tight" x-text="formatNum(stats.vacationDays)"></span>
                </div>

                <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-purple-500">
                    <div class="flex items-center gap-2 mb-2 sm:mb-3">
                        <div class="w-6 h-6 rounded-md bg-purple-500/10 flex items-center justify-center">
                            <i class="fa-solid fa-clock text-[8px] text-purple-400"></i>
                        </div>
                        <span class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Total Hours</span>
                    </div>
                    <div class="flex items-baseline gap-1.5">
                        <span class="text-xl sm:text-2xl font-black tracking-tight" x-text="formatNum(stats.totalHours)"></span>
                        <span class="text-xs sm:text-sm font-bold text-purple-400">hrs</span>
                    </div>
                </div>

                <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-red-500">
                    <div class="flex items-center gap-2 mb-2 sm:mb-3">
                        <div class="w-6 h-6 rounded-md bg-red-500/10 flex items-center justify-center">
                            <i class="fa-solid fa-circle-exclamation text-[8px] text-red-400"></i>
                        </div>
                        <span class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Total Violations</span>
                    </div>
                    <span class="text-xl sm:text-2xl font-black tracking-tight" x-text="formatNum(stats.totalViolations)"></span>
                </div>

                <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-emerald-500 col-span-2 xl:col-span-1">
                    <div class="flex items-center gap-2 mb-2 sm:mb-3">
                        <div class="w-6 h-6 rounded-md bg-emerald-500/10 flex items-center justify-center">
                            <i class="fa-solid fa-money-bill text-[8px] text-emerald-400"></i>
                        </div>
                        <span class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Total Violation Fines</span>
                    </div>
                    <span class="text-xl sm:text-2xl font-black tracking-tight" x-text="formatCurrency(stats.totalFines)"></span>
                </div>

            </div>

            <!-- ══════════ SPLIT VIEW: TIME SHEET & VIOLATIONS ══════════ -->
            <div class="grid grid-cols-1 xl:grid-cols-12 gap-5 sm:gap-6">

                <!-- ── Time Sheet ── -->
                <div class="xl:col-span-7 glass-card rounded-[1.25rem] sm:rounded-[1.5rem] overflow-hidden">
                    <div class="p-4 sm:p-6 pb-0">
                        <div class="flex items-center justify-between mb-4 sm:mb-5">
                            <div class="flex items-center gap-2.5">
                                <div class="w-7 h-7 rounded-lg bg-[#111] border border-[#1e1e1e] flex items-center justify-center">
                                    <i class="fa-regular fa-calendar text-[9px] text-blue-400"></i>
                                </div>
                                <span class="text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-[#555]">Time Sheet</span>
                            </div>
                            <a href="{{ route('driver-manager.time-keeping') }}"
                               class="text-[8px] sm:text-[9px] font-bold uppercase tracking-widest text-blue-400 hover:text-white transition flex items-center gap-1.5">
                                <span>View All</span>
                                <i class="fa-solid fa-arrow-right text-[7px]"></i>
                            </a>
                        </div>
                    </div>
                    <div class="overflow-x-auto -mx-2 px-2 pb-2">
                        <table class="w-full text-left min-w-[480px]">
                            <thead>
                                <tr class="text-[8px] sm:text-[9px] uppercase tracking-[0.15em] text-[#444] border-b border-[#1e1e1e]">
                                    <th class="px-4 sm:px-6 py-2.5 font-bold">Driver Name</th>
                                    <th class="px-4 sm:px-6 py-2.5 font-bold">Date</th>
                                    <th class="px-4 sm:px-6 py-2.5 font-bold">Hours</th>
                                    <th class="px-4 sm:px-6 py-2.5 font-bold text-right">Type</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#1a1a1a]">
                                <template x-for="tk in recentTimeKeepings" :key="tk.driver_id + '-' + tk.date">
                                    <tr class="table-row">
                                        <td class="px-4 sm:px-6 py-3">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-lg bg-[#111] border border-[#1e1e1e] flex items-center justify-center shrink-0">
                                                    <span class="text-[9px] font-bold text-[#555]" x-text="getInitials(tk.driver_name)"></span>
                                                </div>
                                                <span class="text-[10px] sm:text-[11px] font-bold text-[#ccc] truncate max-w-[160px]" x-text="tk.driver_name"></span>
                                            </div>
                                        </td>
                                        <td class="px-4 sm:px-6 py-3">
                                            <span class="text-[10px] sm:text-[11px] font-bold text-[#888]" x-text="formatDate(tk.date)"></span>
                                        </td>
                                        <td class="px-4 sm:px-6 py-3">
                                            <span x-show="!tk.is_leave" class="text-[10px] sm:text-[11px] font-bold text-[#888]" x-text="formatTime(tk.time_in) + ' - ' + formatTime(tk.time_out)"></span>
                                            <span x-show="tk.is_leave" class="text-[10px] sm:text-[11px] text-[#333]">--</span>
                                        </td>
                                        <td class="px-4 sm:px-6 py-3 text-right">
                                            <span class="text-[8px] sm:text-[9px] font-bold uppercase px-2 py-0.5 rounded-md"
                                                  :class="timeSheetType(tk).classes"
                                                  x-text="timeSheetType(tk).label"></span>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="recentTimeKeepings.length === 0">
                                    <td colspan="4" class="py-10 sm:py-12">
                                        <div class="flex flex-col items-center justify-center">
                                            <div class="w-10 h-10 rounded-xl bg-[#111] border border-[#1e1e1e] flex items-center justify-center mb-2.5">
                                                <i class="fa-regular fa-calendar text-sm text-[#333]"></i>
                                            </div>
                                            <p class="text-[10px] sm:text-[11px] text-[#444] font-medium">No time sheet entries found</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ── Violations ── -->
                <div class="xl:col-span-5 flex flex-col gap-5 sm:gap-6">

                    <div class="glass-card rounded-[1.25rem] sm:rounded-[1.5rem] overflow-hidden flex-1">
                        <div class="p-4 sm:p-6 pb-0">
                            <div class="flex items-center justify-between mb-4 sm:mb-5">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-7 h-7 rounded-lg bg-[#111] border border-[#1e1e1e] flex items-center justify-center">
                                        <i class="fa-solid fa-list-check text-[9px] text-red-400"></i>
                                    </div>
                                    <span class="text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-[#555]">Violations</span>
                                </div>
                                <a href="{{ route('driver-manager.violations-log') }}"
                                   class="text-[8px] sm:text-[9px] font-bold uppercase tracking-widest text-red-400 hover:text-white transition flex items-center gap-1.5">
                                    <span>View All</span>
                                    <i class="fa-solid fa-arrow-right text-[7px]"></i>
                                </a>
                            </div>
                        </div>
                        <div class="overflow-y-auto px-4 sm:px-6 pb-4 sm:pb-6 space-y-2.5" style="max-height: 420px;">
                            <template x-for="v in recentViolations" :key="v.id">
                                <div class="p-3.5 rounded-xl bg-[#111] border border-[#1e1e1e] flex gap-3.5 items-center group hover:border-red-500/20 hover:bg-[#141414] transition-all cursor-pointer">
                                    <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0 transition-all"
                                         :class="violationIconClasses(v.violation_instance)">
                                        <i class="fa-solid text-[10px]" :class="violationIcon(v.violation_instance)"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-[10px] sm:text-[11px] font-bold text-[#ccc] truncate" x-text="v.violation_instance"></h4>
                                        <p class="text-[8px] sm:text-[9px] text-[#555] font-medium" x-text="v.user_name"></p>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <p class="text-[10px] sm:text-[11px] font-bold text-red-400" x-text="formatCurrency(v.violation_fine)"></p>
                                        <p class="text-[7px] sm:text-[8px] text-[#444] font-bold uppercase" x-text="v.created_at + ', ' + v.time"></p>
                                    </div>
                                </div>
                            </template>
                            <div x-show="recentViolations.length === 0"
                                 class="flex flex-col items-center justify-center py-14 text-[#333]">
                                <div class="w-10 h-10 rounded-xl bg-[#111] border border-[#1e1e1e] flex items-center justify-center mb-2.5">
                                    <i class="fa-solid fa-shield-halved text-sm text-[#222]"></i>
                                </div>
                                <span class="text-[10px] sm:text-[11px] font-medium">No violations found</span>
                            </div>
                        </div>
                    </div>

                    <!-- ── Quick Actions ── -->
                    <div class="glass-card p-4 sm:p-6 rounded-[1.25rem] sm:rounded-[1.5rem]">
                        <div class="flex items-center gap-2.5 mb-4 sm:mb-5">
                            <div class="w-7 h-7 rounded-lg bg-[#111] border border-[#1e1e1e] flex items-center justify-center">
                                <i class="fa-solid fa-bolt text-[9px] text-[#555]"></i>
                            </div>
                            <span class="text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-[#555]">Quick Actions</span>
                        </div>
                        <div class="space-y-2">
                            <a href="{{ route('driver-manager.time-keeping') }}" class="flex items-center justify-between p-3 sm:p-3.5 rounded-xl bg-[#111] border border-[#1e1e1e] hover:bg-[#1a1a1a] hover:border-[#333] transition group cursor-pointer">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-blue-500/10 border border-blue-500/15 flex items-center justify-center">
                                        <i class="fa-solid fa-clock text-[9px] text-blue-400"></i>
                                    </div>
                                    <span class="text-[10px] font-bold text-[#888] group-hover:text-white transition">Manage Time Keeping</span>
                                </div>
                                <i class="fa-solid fa-chevron-right text-[8px] text-[#333] group-hover:text-[#555] transition"></i>
                            </a>
                            <a href="{{ route('driver-manager.violation-codes') }}" class="flex items-center justify-between p-3 sm:p-3.5 rounded-xl bg-[#111] border border-[#1e1e1e] hover:bg-[#1a1a1a] hover:border-[#333] transition group cursor-pointer">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-amber-500/10 border border-amber-500/15 flex items-center justify-center">
                                        <i class="fa-solid fa-road text-[9px] text-amber-400"></i>
                                    </div>
                                    <span class="text-[10px] font-bold text-[#888] group-hover:text-white transition">Violation Codes</span>
                                </div>
                                <i class="fa-solid fa-chevron-right text-[8px] text-[#333] group-hover:text-[#555] transition"></i>
                            </a>
                        </div>
                    </div>
                </div>

            </div>

            <div class="h-8"></div>
        </div>
    </main>

    <script>
        function dashboardData() {
            return {
                open: false,

                searchQuery: '',
                dropdownOpen: false,
                selectedDriver: null,

                drivers: @json($drivers),
                timeKeepings: @json($timeKeepings),
                violationLogs: @json($violationLogs),

                // ─── COMPUTED ───

                get filteredDrivers() {
                    if (!this.searchQuery.trim()) return this.drivers;
                    const q = this.searchQuery.toLowerCase().trim();
                    return this.drivers.filter(d => d.name.toLowerCase().includes(q));
                },

                get selectedUserId() {
                    return this.selectedDriver ? this.selectedDriver.user_id : null;
                },

                get recentTimeKeepings() {
                    let data = this.selectedDriver
                        ? this.timeKeepings.filter(t => t.driver_id === this.selectedDriver.id)
                        : this.timeKeepings;
                    return data.sort((a, b) => b.date.localeCompare(a.date)).slice(0, 5);
                },

                get recentViolations() {
                    let data = this.selectedUserId
                        ? this.violationLogs.filter(v => v.user_id === this.selectedUserId)
                        : this.violationLogs;
                    return data.sort((a, b) => b.id - a.id).slice(0, 5);
                },

                get stats() {
                    let tk = this.selectedDriver
                        ? this.timeKeepings.filter(t => t.driver_id === this.selectedDriver.id)
                        : this.timeKeepings;

                    let viol = this.selectedUserId
                        ? this.violationLogs.filter(v => v.user_id === this.selectedUserId)
                        : this.violationLogs;

                    return {
                        drivingDays:     tk.filter(t => !t.is_leave).length,
                        sickDays:        tk.reduce((s, t) => s + t.sick, 0),
                        vacationDays:    tk.reduce((s, t) => s + t.vacation, 0),
                        totalHours:      tk.reduce((s, t) => s + t.hours_worked, 0),
                        totalViolations: viol.length,
                        totalFines:      viol.reduce((s, v) => s + v.violation_fine, 0),
                    };
                },

                // ─── DROPDOWN ───

                openDropdown() {
                    this.dropdownOpen = true;
                    if (this.selectedDriver) {
                        this.$nextTick(() => { this.searchQuery = ''; });
                    }
                },

                closeDropdown() {
                    this.searchQuery = this.selectedDriver ? this.selectedDriver.name : '';
                    this.dropdownOpen = false;
                },

                selectDriver(driver) {
                    this.selectedDriver = driver;
                    this.searchQuery = driver.name;
                    this.dropdownOpen = false;
                },

                selectAll() {
                    this.selectedDriver = null;
                    this.searchQuery = '';
                    this.dropdownOpen = false;
                },

                selectFirstMatch() {
                    if (this.filteredDrivers.length > 0) {
                        this.selectDriver(this.filteredDrivers[0]);
                    }
                },

                // ─── FORMATTING ───

                formatTime(str) {
                    if (!str) return '--';
                    const [h, m] = str.split(':').map(Number);
                    const period = h >= 12 ? 'PM' : 'AM';
                    const hour = h % 12 || 12;
                    return hour + ':' + String(m).padStart(2, '0') + ' ' + period;
                },

                formatDate(dateStr) {
                    if (!dateStr) return '';
                    const d = new Date(dateStr + 'T00:00:00');
                    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                },

                formatNum(n) {
                    return Math.round(n).toLocaleString();
                },

                formatCurrency(n) {
                    return '₱' + n.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                },

                getInitials(name) {
                    return name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2);
                },

                // ─── TIME SHEET TYPE ───

                timeSheetType(tk) {
                    if (tk.sick)              return { label: 'Sick',     classes: 'bg-red-500/15 text-red-400 border border-red-500/20' };
                    if (tk.vacation)          return { label: 'Vacation', classes: 'bg-teal-500/15 text-teal-400 border border-teal-500/20' };
                    if (tk.overtime_hours > 0) return { label: 'Overtime', classes: 'bg-orange-500/15 text-orange-400 border border-orange-500/20' };
                    return { label: 'Regular', classes: 'bg-blue-500/15 text-blue-400 border border-blue-500/20' };
                },

                // ─── VIOLATION ICONS ───

                violationIcon(instance) {
                    const t = (instance || '').toLowerCase();
                    if (t.includes('speed') || t.includes('over'))                          return 'fa-gauge-high';
                    if (t.includes('phone') || t.includes('mobile') || t.includes('device')) return 'fa-mobile-screen-button';
                    if (t.includes('traffic') || t.includes('light') || t.includes('signal')) return 'fa-traffic-light';
                    if (t.includes('parking') || t.includes('park'))                        return 'fa-square-parking';
                    if (t.includes('lane') || t.includes('swerv') || t.includes('weaving')) return 'fa-road';
                    return 'fa-triangle-exclamation';
                },

                violationIconClasses(instance) {
                    const t = (instance || '').toLowerCase();
                    if (t.includes('phone') || t.includes('mobile'))  return 'bg-orange-500/10 border border-orange-500/15 text-orange-400 group-hover:bg-orange-500 group-hover:text-white group-hover:border-orange-500';
                    if (t.includes('traffic') || t.includes('park'))   return 'bg-yellow-500/10 border border-yellow-500/15 text-yellow-400 group-hover:bg-yellow-500 group-hover:text-white group-hover:border-yellow-500';
                    return 'bg-red-500/10 border border-red-500/15 text-red-400 group-hover:bg-red-500 group-hover:text-white group-hover:border-red-500';
                },
            };
        }
    </script>
</body>

</html>
