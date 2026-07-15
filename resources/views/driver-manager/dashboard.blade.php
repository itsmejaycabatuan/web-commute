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

        .glass-panel {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .sidebar-transition {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .custom-scroll::-webkit-scrollbar { width: 4px; }
        .custom-scroll::-webkit-scrollbar-track { background: transparent; }
        .custom-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.08); border-radius: 99px; }
        .custom-scroll::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.15); }
    </style>
</head>

<body x-data="dashboardData()">

    @include('driver-manager.layout.sidebar')

    <main :class="open ? 'ml-72' : 'ml-20'" class="sidebar-transition p-8 md:p-12 min-h-screen">
        <div class="max-w-7xl mx-auto">

            <!-- Header -->
            <header class="mb-8 flex justify-between items-end">
                <div>
                    <h1 class="text-3xl md:text-4xl font-black tracking-tight text-white mb-2">Dashboard</h1>
                    <p class="text-gray-500 text-sm">Driver Management Tool Overview</p>
                </div>

            </header>

            <!-- Searchable Driver Selection -->
            <div class="mb-8 flex items-center justify-between border-b border-white/5 pb-6">
                <div class="flex items-center gap-4">
                    <label class="text-sm font-medium text-gray-400">Driver Data Summary:</label>

                    <div @click.away="closeDropdown()" class="relative">
                        <div class="flex items-center gap-2 bg-black/40 border border-white/10 rounded-lg px-4 py-2 min-w-[280px] cursor-text transition-all"
                             :class="dropdownOpen ? 'border-blue-500/50 ring-1 ring-blue-500/20' : 'hover:border-white/20'"
                             @click="openDropdown()">
                            <i class="fa-solid fa-magnifying-glass text-gray-500 text-xs flex-shrink-0"></i>
                            <input type="text"
                                   x-model="searchQuery"
                                   @input="dropdownOpen = true"
                                   @focus="openDropdown()"
                                   @keydown.escape="closeDropdown()"
                                   @keydown.enter.prevent="selectFirstMatch()"
                                   placeholder="Search drivers..."
                                   class="bg-transparent text-white text-sm focus:outline-none w-full placeholder-gray-600">
                            <button x-show="selectedDriver"
                                    @click.stop="selectAll()"
                                    x-transition
                                    class="text-gray-500 hover:text-white transition-colors flex-shrink-0">
                                <i class="fa-solid fa-xmark text-xs"></i>
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
                             class="absolute top-full left-0 mt-2 w-full bg-[#0e0e10] border border-white/10 rounded-xl shadow-2xl shadow-black/80 overflow-hidden z-50">

                            <button @click="selectAll()"
                                    :class="!selectedDriver ? 'bg-blue-600/10 text-blue-400' : 'text-white/60 hover:bg-white/[0.04]'"
                                    class="w-full px-4 py-3 text-left text-sm flex items-center gap-3 transition-colors">
                                <div class="w-7 h-7 rounded-lg bg-white/5 flex items-center justify-center">
                                    <i class="fa-solid fa-users text-[10px]"></i>
                                </div>
                                <span class="font-medium">All Drivers</span>
                                <span class="ml-auto text-[10px] font-bold px-1.5 py-0.5 rounded bg-white/5" x-text="drivers.length"></span>
                            </button>

                            <div class="h-px bg-white/5"></div>

                            <div class="max-h-[280px] overflow-y-auto custom-scroll">
                                <template x-for="driver in filteredDrivers" :key="driver.id">
                                    <button @click="selectDriver(driver)"
                                            :class="selectedDriver && selectedDriver.id === driver.id ? 'bg-blue-600/10 text-blue-400' : 'text-white/60 hover:bg-white/[0.04]'"
                                            class="w-full px-4 py-2.5 text-left text-sm flex items-center gap-3 transition-colors">
                                        <div class="w-7 h-7 rounded-full bg-white/10 flex items-center justify-center text-[10px] font-bold flex-shrink-0"
                                             x-text="getInitials(driver.name)"></div>
                                        <span x-text="driver.name"></span>
                                    </button>
                                </template>

                                <div x-show="filteredDrivers.length === 0 && searchQuery.trim()"
                                     class="px-4 py-8 text-center text-white/15 text-sm">
                                    <i class="fa-solid fa-magnifying-glass text-lg mb-2 block"></i>
                                    No drivers match "@{{ searchQuery }}"
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div x-show="selectedDriver" x-transition class="flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></div>
                    <span class="text-xs text-white/30">Filtered</span>
                </div>
            </div>

            <!-- Driver Info Cards (only when a specific driver is selected) -->
            <div x-show="selectedDriver" x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 -translate-y-3"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 -translate-y-3"
                 class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">

                <div class="glass-panel p-6 rounded-2xl hover:bg-white/5 transition-colors group relative overflow-hidden">
                    <div class="absolute right-[-20px] top-[-20px] text-9xl text-white/5 -z-0 rotate-12 group-hover:text-blue-500/5 transition-colors">
                        <i class="fa-solid fa-id-badge"></i>
                    </div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-2 mb-2 text-blue-400">
                            <i class="fa-solid fa-id-badge"></i>
                            <h3 class="text-sm font-semibold uppercase tracking-wider">Driver Code</h3>
                        </div>
                        <p class="text-3xl font-bold mt-1" x-text="selectedDriver.driver_code"></p>
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
                        <p class="text-3xl font-bold mt-1" x-text="selectedDriver.license_number"></p>
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
                        <p class="text-3xl font-bold mt-1" x-text="selectedDriver.expiration_date"></p>
                    </div>
                </div>
            </div>

            <!-- 6 Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">

                <div class="glass-panel p-6 rounded-2xl hover:bg-white/5 transition-colors group relative overflow-hidden">
                    <div class="absolute right-[-20px] top-[-20px] text-9xl text-white/5 -z-0 rotate-12 group-hover:text-blue-500/5 transition-colors">
                        <i class="fa-solid fa-calendar-days"></i>
                    </div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-2 mb-2 text-blue-400">
                            <i class="fa-solid fa-calendar-check"></i>
                            <h3 class="text-sm font-semibold uppercase tracking-wider">Total Days Driving</h3>
                        </div>
                        <p class="text-3xl font-bold mt-1" x-text="formatNum(stats.drivingDays)"></p>
                    </div>
                </div>

                <div class="glass-panel p-6 rounded-2xl hover:bg-white/5 transition-colors group relative overflow-hidden">
                    <div class="absolute right-[-20px] top-[-20px] text-9xl text-white/5 -z-0 rotate-12 group-hover:text-orange-500/5 transition-colors">
                        <i class="fa-solid fa-bed-pulse"></i>
                    </div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-2 mb-2 text-orange-400">
                            <i class="fa-solid fa-notes-medical"></i>
                            <h3 class="text-sm font-semibold uppercase tracking-wider">Sick Days</h3>
                        </div>
                        <p class="text-3xl font-bold mt-1" x-text="formatNum(stats.sickDays)"></p>
                    </div>
                </div>

                <div class="glass-panel p-6 rounded-2xl hover:bg-white/5 transition-colors group relative overflow-hidden">
                    <div class="absolute right-[-20px] top-[-20px] text-9xl text-white/5 -z-0 rotate-12 group-hover:text-teal-500/5 transition-colors">
                        <i class="fa-solid fa-plane-departure"></i>
                    </div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-2 mb-2 text-teal-400">
                            <i class="fa-solid fa-umbrella-beach"></i>
                            <h3 class="text-sm font-semibold uppercase tracking-wider">Vacation Days</h3>
                        </div>
                        <p class="text-3xl font-bold mt-1" x-text="formatNum(stats.vacationDays)"></p>
                    </div>
                </div>

                <div class="glass-panel p-6 rounded-2xl hover:bg-white/5 transition-colors group relative overflow-hidden">
                    <div class="absolute right-[-20px] top-[-20px] text-9xl text-white/5 -z-0 rotate-12 group-hover:text-purple-500/5 transition-colors">
                        <i class="fa-solid fa-stopwatch"></i>
                    </div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-2 mb-2 text-purple-400">
                            <i class="fa-solid fa-clock"></i>
                            <h3 class="text-sm font-semibold uppercase tracking-wider">Total Hours</h3>
                        </div>
                        <p class="text-3xl font-bold mt-1">
                            <span x-text="formatNum(stats.totalHours)"></span>
                            <span class="text-lg font-normal text-gray-500">hrs</span>
                        </p>
                    </div>
                </div>

                <div class="glass-panel p-6 rounded-2xl hover:bg-white/5 transition-colors group relative overflow-hidden">
                    <div class="absolute right-[-20px] top-[-20px] text-9xl text-white/5 -z-0 rotate-12 group-hover:text-red-500/5 transition-colors">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-2 mb-2 text-red-400">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            <h3 class="text-sm font-semibold uppercase tracking-wider">Total Violations</h3>
                        </div>
                        <p class="text-3xl font-bold mt-1" x-text="formatNum(stats.totalViolations)"></p>
                    </div>
                </div>

                <div class="glass-panel p-6 rounded-2xl hover:bg-white/5 transition-colors group relative overflow-hidden">
                    <div class="absolute right-[-20px] top-[-20px] text-9xl text-white/5 -z-0 rotate-12 group-hover:text-green-500/5 transition-colors">
                        <i class="fa-solid fa-money-bill"></i>
                    </div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-2 mb-2 text-green-400">
                            <i class="fa-solid fa-money-bill"></i>
                            <h3 class="text-sm font-semibold uppercase tracking-wider">Total Violation Fines</h3>
                        </div>
                        <p class="text-3xl font-bold mt-1" x-text="formatCurrency(stats.totalFines)"></p>
                    </div>
                </div>
            </div>

            <!-- Split View: Time Sheet & Violations -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

                <!-- Time Sheet -->
                <section class="glass-panel rounded-2xl overflow-hidden flex flex-col h-[450px]">
                    <div class="p-5 border-b border-white/5 flex justify-between items-center bg-white/[0.02]">
                        <h3 class="font-bold text-lg flex items-center gap-2">
                            <i class="fa-regular fa-calendar text-blue-400"></i> Time Sheet
                        </h3>
                        <a href="{{ route('driver-manager.time-keeping') }}"
                           class="text-xs text-gray-400 hover:text-white px-2 py-1 transition-colors">See All</a>
                    </div>

                    <div class="overflow-y-auto flex-1 p-2 custom-scroll">
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
                                <template x-for="tk in recentTimeKeepings" :key="tk.driver_id + '-' + tk.date">
                                    <tr class="hover:bg-white/5 transition-colors cursor-pointer">
                                        <td class="p-3 font-medium flex items-center gap-2">
                                            <div class="w-6 h-6 rounded-full bg-gray-700 flex items-center justify-center text-[10px] font-bold flex-shrink-0"
                                                 x-text="getInitials(tk.driver_name)"></div>
                                            <span x-text="tk.driver_name"></span>
                                        </td>
                                        <td class="p-3 text-gray-400" x-text="formatDate(tk.date)"></td>
                                        <td class="p-3 text-gray-400">
                                            <span x-show="!tk.is_leave" x-text="formatTime(tk.time_in) + ' - ' + formatTime(tk.time_out)"></span>
                                            <span x-show="tk.is_leave" class="text-white/20">--</span>
                                        </td>
                                        <td class="p-3 text-right">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-semibold"
                                                  :class="timeSheetType(tk).classes"
                                                  x-text="timeSheetType(tk).label"></span>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="recentTimeKeepings.length === 0">
                                    <td colspan="4" class="p-12 text-center text-white/15 text-sm">No entries found</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Violations -->
                <section class="glass-panel rounded-2xl overflow-hidden flex flex-col h-[450px]">
                    <div class="p-5 border-b border-white/5 flex justify-between items-center bg-white/[0.02]">
                        <h3 class="font-bold text-lg flex items-center gap-2">
                            <i class="fa-solid fa-list-check text-red-400"></i> Violations
                        </h3>
                        <a href="{{ route('driver-manager.violations-log') }}"
                           class="text-xs text-gray-400 hover:text-white px-2 py-1 transition-colors">See All</a>
                    </div>

                    <div class="overflow-y-auto flex-1 p-4 space-y-3 custom-scroll">
                        <template x-for="v in recentViolations" :key="v.id">
                            <div class="p-4 rounded-xl bg-white/5 border border-white/5 flex gap-4 items-center group hover:border-red-500/30 transition-all cursor-pointer">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0 transition-all"
                                     :class="violationIconClasses(v.violation_instance)">
                                    <i class="fa-solid" :class="violationIcon(v.violation_instance)"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-sm font-semibold truncate" x-text="v.violation_instance"></h4>
                                    <p class="text-xs text-gray-400" x-text="v.user_name"></p>
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="text-sm font-bold text-red-400" x-text="formatCurrency(v.violation_fine)"></p>
                                    <p class="text-[10px] text-gray-500" x-text="v.created_at + ', ' + v.time"></p>
                                </div>
                            </div>
                        </template>
                        <div x-show="recentViolations.length === 0"
                             class="flex flex-col items-center justify-center py-16 text-white/15">
                            <i class="fa-solid fa-shield-check text-3xl mb-3"></i>
                            <span class="text-sm">No violations found</span>
                        </div>
                    </div>
                </section>
            </div>

            <div class="h-20"></div>
        </div>
    </main>

    <script>
        function dashboardData() {
            return {
                open: true,

                searchQuery: '',
                dropdownOpen: false,
                selectedDriver: null,

                // From controller — column-matched to your 3 tables
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
                    // time_keepings table — filtered by driver_id
                    let tk = this.selectedDriver
                        ? this.timeKeepings.filter(t => t.driver_id === this.selectedDriver.id)
                        : this.timeKeepings;

                    // violation_logs table — filtered by user_id
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

                // ─── TIME SHEET TYPE from time_keepings columns ───

                timeSheetType(tk) {
                    if (tk.sick)              return { label: 'Sick',     classes: 'bg-red-500/20 text-red-400' };
                    if (tk.vacation)          return { label: 'Vacation', classes: 'bg-teal-500/20 text-teal-400' };
                    if (tk.overtime_hours > 0) return { label: 'Overtime', classes: 'bg-orange-500/20 text-orange-400' };
                    return { label: 'Regular', classes: 'bg-blue-500/20 text-blue-400' };
                },

                // ─── VIOLATION ICONS from violation_logs.violation_instance ───

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
                    if (t.includes('phone') || t.includes('mobile'))  return 'bg-orange-500/10 text-orange-500 group-hover:bg-orange-500 group-hover:text-white';
                    if (t.includes('traffic') || t.includes('park'))   return 'bg-yellow-500/10 text-yellow-500 group-hover:bg-yellow-500 group-hover:text-white';
                    return 'bg-red-500/10 text-red-500 group-hover:bg-red-500 group-hover:text-white';
                },
            };
        }
    </script>
</body>
</html>
