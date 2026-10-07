@php
    $totalVehicles = $vehicles->count();
    $activeCount = $vehicles->where('status', 'active')->count();
    $maintenanceCount = $vehicles->where('status', 'maintenance')->count();
    $inactiveCount = $vehicles->where('status', 'inactive')->count();
    $disposedCount = $vehicles->where('status', 'disposed')->count();
    $assignedCount = $vehicles->whereNotNull('driver_id')->where('driver_id', '!=', '')->count();
@endphp

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SmartCommute | Vehicle Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    @include('partials.head-scripts')
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif']
                    }
                }
            }
        }
    </script>
    <style>
        body,
        html {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: 'Inter', sans-serif;
            overflow-x: hidden;
        }

        [x-cloak] {
            display: none !important;
        }

        .glass-panel {
            background: rgba(255, 255, 255, 0.85);
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
        }

        .dark .glass-panel {
            background: #111111;
            border: 1px solid #1e1e1e;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.6);
        }

        .glass-card {
            background: #ffffff;
            border: 1px solid #f1f5f9;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        }

        .dark .glass-card {
            background: #161616;
            border: 1px solid #222222;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.5);
        }

        .sidebar-transition {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }

        .table-row {
            transition: all 0.2s ease !important;
        }

        .table-row:hover {
            background: #f8fafc;
        }

        .dark .table-row:hover {
            background: #1a1a1a;
        }

        ::-webkit-scrollbar {
            width: 3px;
            height: 3px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        .dark ::-webkit-scrollbar-thumb {
            background: #333;
        }

        .form-input {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #1e293b;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .dark .form-input {
            background: #111;
            border: 1px solid #1e1e1e;
            color: #fff;
        }

        .form-input:focus {
            border-color: rgba(59, 130, 246, 0.4);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.06);
        }

        .form-input::placeholder {
            color: #94a3b8;
        }

        .dark .form-input::placeholder {
            color: #2a2a2a;
        }

        select.form-select {
            appearance: none;
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 40px;
        }

        select.form-select option {
            background: #ffffff;
            color: #1e293b;
        }

        .dark select.form-select option {
            background: #111;
            color: #fff;
        }

        .dark input[type="date"]::-webkit-calendar-picker-indicator {
            filter: invert(0.4);
            cursor: pointer;
        }

        .filter-chip {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            border: 1px solid;
            transition: all 0.15s;
            cursor: default;
        }

        .filter-chip button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 12px;
            height: 12px;
            border-radius: 3px;
            transition: all 0.1s;
        }

        .status-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 10px;
            border-radius: 8px;
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border: 1px solid;
            transition: all 0.15s;
            cursor: pointer;
            background: transparent;
        }

        .status-btn:hover {
            transform: translateY(-1px);
        }

        .status-btn.active {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body class="antialiased text-gray-900 dark:text-white bg-white dark:bg-[#050505]" x-data>

    <div x-data="vehicleManagementApp()" @keydown.escape.window="if(showViewModal) showViewModal = false">

        <script type="text/json" id="vehicles-data">
            @json($vehicles)
        </script>

        <x-layout.sidebar />
        <main :class="$store.sidebar.open ? 'md:ml-72' : 'md:ml-20'"
            class="sidebar-transition pt-8 pr-4 sm:pr-8 pb-8 pl-4 sm:pl-8 min-h-screen mb-12">
            <div class="max-w-[1400px] mx-auto">

                <!-- ── Mobile: Identity Card ── -->
                <div class="lg:hidden mb-5">
                    <div class="glass-card p-4 rounded-[1.25rem]">
                        <div class="flex items-center gap-3.5">
                            <div class="w-11 h-11 bg-amber-600 rounded-xl flex items-center justify-center shrink-0"><i
                                    class="fa-solid fa-wrench text-sm text-white"></i></div>
                            <div class="min-w-0">
                                <h2 class="text-sm font-bold text-gray-900 dark:text-white truncate">Maintenance Manager
                                </h2>
                                <p class="text-[10px] text-gray-500 dark:text-[#555] truncate">{{ Auth::user()->email }}
                                </p>
                            </div>
                        </div>
                        <div
                            class="flex items-center gap-2 pt-3.5 mt-3.5 border-t border-gray-200 dark:border-[#1e1e1e]">
                            <i class="fa-solid fa-wrench text-[8px] text-amber-500 dark:text-amber-400"></i>
                            <span class="text-[10px] text-gray-600 dark:text-[#888] font-bold">Fleet Access</span>
                            <span class="text-gray-300 dark:text-[#333]">•</span>
                            <span class="font-mono text-[9px] text-gray-400 dark:text-[#444]">Vehicles</span>
                        </div>
                    </div>
                </div>

                <!-- ── Page Header (desktop) ── -->
                <div class="hidden lg:flex items-end justify-between mb-8">
                    <div>
                        <div class="flex items-center gap-2 mb-1.5">
                            <span
                                class="text-[9px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Fleet
                                Registry</span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-gray-900 dark:text-white">Vehicle
                            <span class="text-blue-600 dark:text-blue-500">Management</span>
                        </h1>
                        <p class="text-[11px] text-gray-500 dark:text-[#555] mt-1 flex items-center gap-2">
                            <i class="fa-solid fa-wrench text-[9px] text-amber-500 dark:text-amber-400"></i>
                            Track and manage all registered vehicles
                        </p>
                    </div>
                    <button @click="openAddModal()"
                        class="flex items-center gap-2 px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-[10px] font-bold uppercase tracking-widest shadow-lg shadow-blue-600/20 dark:shadow-blue-900/30 transition-all active:scale-[0.97]">
                        <i class="fa-solid fa-plus text-[9px]"></i><span>Add Vehicle</span>
                    </button>
                </div>

                <!-- ── Mobile Add Button ── -->
                <div class="lg:hidden mb-5">
                    <button @click="openAddModal()"
                        class="w-full flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-[10px] font-bold uppercase tracking-widest transition-all active:scale-[0.98]">
                        <i class="fa-solid fa-plus text-[9px]"></i><span>Add Vehicle</span>
                    </button>
                </div>

                <!-- ══════════ STAT CARDS ══════════ -->
                <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-5 gap-3 sm:gap-4 mb-5 sm:mb-6">
                    <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-blue-500 cursor-pointer hover:shadow-md transition-shadow"
                        @click="statusFilter = ''">
                        <div class="flex items-center gap-2 mb-2 sm:mb-3">
                            <div class="w-6 h-6 rounded-md bg-blue-500/10 flex items-center justify-center"><i
                                    class="fa-solid fa-car text-[8px] text-blue-500 dark:text-blue-400"></i></div><span
                                class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Total</span>
                        </div>
                        <div class="flex items-baseline gap-1.5"><span
                                class="text-xl sm:text-2xl font-black tracking-tight text-gray-900 dark:text-white">{{ $totalVehicles }}</span><span
                                class="text-xs font-bold text-gray-400 dark:text-[#555]">{{ $totalVehicles === 1 ? 'unit' : 'units' }}</span>
                        </div>
                    </div>
                    <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-emerald-500 cursor-pointer hover:shadow-md transition-shadow"
                        :class="statusFilter === 'active' ? 'ring-2 ring-emerald-500/30' : ''"
                        @click="statusFilter = statusFilter === 'active' ? '' : 'active'">
                        <div class="flex items-center gap-2 mb-2 sm:mb-3">
                            <div class="w-6 h-6 rounded-md bg-emerald-500/10 flex items-center justify-center"><i
                                    class="fa-solid fa-circle-check text-[8px] text-emerald-500 dark:text-emerald-400"></i>
                            </div><span
                                class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Active</span>
                        </div>
                        <div class="flex items-baseline gap-1.5"><span
                                class="text-xl sm:text-2xl font-black tracking-tight text-emerald-600 dark:text-emerald-400">{{ $activeCount }}</span>
                        </div>
                    </div>
                    <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-amber-500 cursor-pointer hover:shadow-md transition-shadow"
                        :class="statusFilter === 'maintenance' ? 'ring-2 ring-amber-500/30' : ''"
                        @click="statusFilter = statusFilter === 'maintenance' ? '' : 'maintenance'">
                        <div class="flex items-center gap-2 mb-2 sm:mb-3">
                            <div class="w-6 h-6 rounded-md bg-amber-500/10 flex items-center justify-center"><i
                                    class="fa-solid fa-wrench text-[8px] text-amber-500 dark:text-amber-400"></i></div>
                            <span
                                class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Maintenance</span>
                        </div>
                        <div class="flex items-baseline gap-1.5"><span
                                class="text-xl sm:text-2xl font-black tracking-tight {{ $maintenanceCount > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-400 dark:text-[#555]' }}">{{ $maintenanceCount }}</span>
                        </div>
                    </div>
                    <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-rose-500 cursor-pointer hover:shadow-md transition-shadow"
                        :class="statusFilter === 'inactive' ? 'ring-2 ring-rose-500/30' : ''"
                        @click="statusFilter = statusFilter === 'inactive' ? '' : 'inactive'">
                        <div class="flex items-center gap-2 mb-2 sm:mb-3">
                            <div class="w-6 h-6 rounded-md bg-rose-500/10 flex items-center justify-center"><i
                                    class="fa-solid fa-circle-xmark text-[8px] text-rose-500 dark:text-rose-400"></i>
                            </div>
                            <span
                                class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Inactive</span>
                        </div>
                        <div class="flex items-baseline gap-1.5"><span
                                class="text-xl sm:text-2xl font-black tracking-tight {{ $inactiveCount > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-gray-400 dark:text-[#555]' }}">{{ $inactiveCount }}</span>
                        </div>
                    </div>
                    <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-purple-500 cursor-pointer hover:shadow-md transition-shadow"
                        :class="assignmentFilter === 'assigned' ? 'ring-2 ring-purple-500/30' : ''"
                        @click="assignmentFilter = assignmentFilter === 'assigned' ? '' : 'assigned'">
                        <div class="flex items-center gap-2 mb-2 sm:mb-3">
                            <div class="w-6 h-6 rounded-md bg-purple-500/10 flex items-center justify-center"><i
                                    class="fa-solid fa-user-check text-[8px] text-purple-500 dark:text-purple-400"></i>
                            </div><span
                                class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Assigned</span>
                        </div>
                        <div class="flex items-baseline gap-1.5"><span
                                class="text-xl sm:text-2xl font-black tracking-tight text-purple-600 dark:text-purple-400">{{ $assignedCount }}</span><span
                                class="text-xs font-bold text-gray-400 dark:text-[#555]">drivers</span></div>
                    </div>
                </div>

                <!-- ══════════ SEARCH & FILTER BAR ══════════ -->
                <div class="glass-card p-4 rounded-[1.25rem] mb-5">
                    <!-- Main Filter Row -->
                    <div class="flex flex-col lg:flex-row gap-3 mb-3">
                        <!-- Search Input -->
                        <div class="relative flex-1">
                            <i
                                class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-300 dark:text-[#333] text-[10px]"></i>
                            <input type="text" x-model="search"
                                placeholder="Search brand, model, plate number, VIN, driver..."
                                class="form-input w-full pl-10 pr-10 py-2.5 rounded-xl text-[10px] font-bold">
                            <button x-show="search.length > 0" @click="search = ''"
                                class="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 rounded-md bg-gray-200 dark:bg-[#222] flex items-center justify-center text-gray-500 dark:text-[#555] hover:text-gray-700 dark:hover:text-white transition-colors">
                                <i class="fa-solid fa-xmark text-[8px]"></i>
                            </button>
                        </div>

                        <!-- Fuel Type Filter -->
                        <div class="relative">
                            <i
                                class="fa-solid fa-gas-pump absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-300 dark:text-[#333] text-[10px]"></i>
                            <select x-model="fuelFilter"
                                class="form-input form-select w-full lg:w-44 pl-10 pr-10 py-2.5 rounded-xl text-[10px] font-bold cursor-pointer">
                                <option value="">All Fuel Types</option>
                                <option value="Diesel">Diesel</option>
                                <option value="Gasoline">Gasoline</option>
                                <option value="Electric">Electric</option>
                                <option value="Hybrid">Hybrid</option>
                                <option value="LPG">LPG</option>
                            </select>
                        </div>

                        <!-- Assignment Filter -->
                        <div class="relative">
                            <i
                                class="fa-solid fa-user absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-300 dark:text-[#333] text-[10px]"></i>
                            <select x-model="assignmentFilter"
                                class="form-input form-select w-full lg:w-44 pl-10 pr-10 py-2.5 rounded-xl text-[10px] font-bold cursor-pointer">
                                <option value="">All Vehicles</option>
                                <option value="assigned">Assigned</option>
                                <option value="unassigned">Unassigned</option>
                            </select>
                        </div>

                        <!-- Sort -->
                        <div class="relative">
                            <i
                                class="fa-solid fa-arrow-up-wide-short absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-300 dark:text-[#333] text-[10px]"></i>
                            <select x-model="sortBy"
                                class="form-input form-select w-full lg:w-44 pl-10 pr-10 py-2.5 rounded-xl text-[10px] font-bold cursor-pointer">

                                <option value="newest">Newest First</option>
                                <option value="oldest">Oldest First</option>
                                <option value="brand_asc">Brand A-Z</option>
                                <option value="brand_desc">Brand Z-A</option>
                                <option value="year_desc">Newest Year</option>
                                <option value="year_asc">Oldest Year</option>
                            </select>
                        </div>
                    </div>

                    <!-- Active Filters Row -->
                    <div class="flex items-center justify-between flex-wrap gap-2">
                        <div class="flex items-center gap-2 flex-wrap">
                            <template x-if="hasActiveFilters">
                                <span
                                    class="text-[7px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#333]">Active:</span>
                            </template>

                            <template x-if="search.length > 0">
                                <span
                                    class="filter-chip bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20">
                                    <i class="fa-solid fa-magnifying-glass text-[6px]"></i>
                                    <span x-text="search.substring(0, 15) + (search.length > 15 ? '...' : '')"></span>
                                    <button @click="search = ''" class="hover:bg-blue-500/20 rounded">
                                        <i class="fa-solid fa-xmark text-[6px]"></i>
                                    </button>
                                </span>
                            </template>

                            <template x-if="statusFilter">
                                <span class="filter-chip"
                                    :class="statusFilter === 'active' ?
                                        'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20' :
                                        statusFilter === 'maintenance' ?
                                        'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20' :
                                        statusFilter === 'inactive' ?
                                        'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20' :
                                        'bg-gray-500/10 text-gray-600 dark:text-gray-400 border-gray-500/20'">
                                    <i class="fa-solid fa-circle text-[5px]"></i>
                                    <span x-text="statusFilter"></span>
                                    <button @click="statusFilter = ''" class="hover:opacity-70 rounded">
                                        <i class="fa-solid fa-xmark text-[6px]"></i>
                                    </button>
                                </span>
                            </template>

                            <template x-if="fuelFilter">
                                <span
                                    class="filter-chip bg-orange-500/10 text-orange-600 dark:text-orange-400 border-orange-500/20">
                                    <i class="fa-solid fa-gas-pump text-[6px]"></i>
                                    <span x-text="fuelFilter"></span>
                                    <button @click="fuelFilter = ''" class="hover:bg-orange-500/20 rounded">
                                        <i class="fa-solid fa-xmark text-[6px]"></i>
                                    </button>
                                </span>
                            </template>

                            <template x-if="assignmentFilter">
                                <span
                                    class="filter-chip bg-purple-500/10 text-purple-600 dark:text-purple-400 border-purple-500/20">
                                    <i class="fa-solid fa-user text-[6px]"></i>
                                    <span x-text="assignmentFilter === 'assigned' ? 'Assigned' : 'Unassigned'"></span>
                                    <button @click="assignmentFilter = ''" class="hover:bg-purple-500/20 rounded">
                                        <i class="fa-solid fa-xmark text-[6px]"></i>
                                    </button>
                                </span>
                            </template>

                            <template x-if="sortBy !== 'newest'">
                                <span
                                    class="filter-chip bg-gray-500/10 text-gray-600 dark:text-gray-400 border-gray-500/20">
                                    <i class="fa-solid fa-arrow-up-wide-short text-[6px]"></i>
                                    <span x-text="sortLabels[sortBy] || sortBy"></span>
                                    <button @click="sortBy = 'newest'" class="hover:bg-gray-500/20 rounded">
                                        <i class="fa-solid fa-xmark text-[6px]"></i>
                                    </button>
                                </span>
                            </template>

                            <button x-show="hasActiveFilters" @click="clearAllFilters()"
                                class="filter-chip bg-gray-100 dark:bg-[#111] text-gray-500 dark:text-[#555] border-gray-200 dark:border-[#222] hover:text-rose-500 dark:hover:text-rose-400 hover:border-rose-500/20 cursor-pointer">
                                <i class="fa-solid fa-filter-circle-xmark text-[6px]"></i>
                                Clear All
                            </button>
                        </div>

                        <span
                            class="text-[7px] sm:text-[8px] font-bold text-gray-400 dark:text-[#333] uppercase tracking-widest whitespace-nowrap"
                            x-text="filtered.length + ' of ' + vehicles.length + ' vehicles'"></span>
                    </div>
                </div>

                <!-- ══════════ TABLE ══════════ -->
                <div class="glass-card rounded-[1.25rem] sm:rounded-[1.5rem] overflow-hidden">
                    <div class="p-4 sm:p-6 pb-0">
                        <div class="flex items-center justify-between mb-4 sm:mb-5">
                            <div class="flex items-center gap-2.5">
                                <div
                                    class="w-7 h-7 rounded-lg bg-gray-100 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] flex items-center justify-center">
                                    <i class="fa-solid fa-car text-[9px] text-blue-500 dark:text-blue-400"></i>
                                </div>
                                <span
                                    class="text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-gray-500 dark:text-[#555]">Vehicle
                                    Registry</span>
                            </div>
                            <span
                                class="text-[7px] sm:text-[8px] font-bold text-gray-400 dark:text-[#333] uppercase tracking-widest"
                                x-text="filtered.length + ' of ' + vehicles.length"></span>
                        </div>
                    </div>
                    <div class="overflow-x-auto -mx-2 px-2 pb-2">
                        <table class="w-full text-left min-w-[700px]">
                            <thead>
                                <tr
                                    class="text-[7px] sm:text-[8px] uppercase tracking-[0.15em] text-gray-400 dark:text-[#444] border-b border-gray-200 dark:border-[#1e1e1e]">
                                    <th
                                        class="px-4 sm:px-6 py-2.5 font-bold sticky left-0 bg-white dark:bg-[#161616] z-10 min-w-[220px]">
                                        Vehicle</th>
                                    <th class="px-4 sm:px-6 py-2.5 font-bold">Plate Number</th>
                                    <th class="px-4 sm:px-6 py-2.5 font-bold">Driver</th>
                                    <th class="px-4 sm:px-6 py-2.5 font-bold">Location</th>
                                    <th class="px-4 sm:px-6 py-2.5 font-bold text-center">Status</th>
                                    <th class="px-4 sm:px-6 py-2.5 font-bold text-center w-24">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-[#1a1a1a]">
                                <template x-for="(v, index) in filtered" :key="v.id">
                                    <tr class="table-row group">
                                        <td
                                            class="px-4 sm:px-6 py-3.5 sticky left-0 z-10 bg-white dark:bg-[#161616] group-hover:bg-gray-50 dark:group-hover:bg-[#1a1a1a] transition-colors">
                                            <div class="flex items-center gap-3">
                                                <div
                                                    class="w-8 h-8 rounded-lg bg-blue-500/10 border border-blue-500/15 flex items-center justify-center shrink-0">
                                                    <i
                                                        class="fa-solid fa-van-shuttle text-[8px] text-blue-500 dark:text-blue-400"></i>
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="text-[10px] sm:text-[11px] font-bold text-gray-700 dark:text-[#ccc] truncate max-w-[160px]"
                                                        x-text="v.brand + ' ' + v.model"></p>
                                                    <div class="flex items-center gap-2">
                                                        <span
                                                            class="text-[8px] text-gray-400 dark:text-[#444] font-bold"
                                                            x-text="v.year"></span>
                                                        <template x-if="v.fuel_type">
                                                            <span
                                                                class="text-[7px] text-gray-300 dark:text-[#333] font-bold uppercase"
                                                                x-text="'• ' + v.fuel_type"></span>
                                                        </template>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 sm:px-6 py-3.5">
                                            <span
                                                class="text-[8px] sm:text-[9px] font-mono font-bold text-blue-600 dark:text-blue-400/60 bg-blue-500/10 px-2 py-0.5 rounded-md border border-blue-500/15"
                                                x-text="v.plate_number"></span>
                                        </td>
                                        <td class="px-4 sm:px-6 py-3.5">
                                            <div class="flex items-center gap-2">
                                                <div class="w-6 h-6 rounded-full bg-gray-100 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] flex items-center justify-center shrink-0"
                                                    :class="v.driver_name ? 'bg-purple-500/10 border-purple-500/15' : ''">
                                                    <i class="fa-solid fa-user text-[7px]"
                                                        :class="v.driver_name ? 'text-purple-500 dark:text-purple-400' :
                                                            'text-gray-300 dark:text-[#333]'"></i>
                                                </div>
                                                <span
                                                    class="text-[9px] sm:text-[10px] font-bold truncate max-w-[120px]"
                                                    :class="v.driver_name ? 'text-gray-700 dark:text-[#ccc]' :
                                                        'text-gray-400 dark:text-[#444]'"
                                                    x-text="v.driver_name || 'Unassigned'"></span>
                                            </div>
                                        </td>
                                        <td class="px-4 sm:px-6 py-3.5">
                                            <span
                                                class="text-[9px] sm:text-[10px] text-gray-500 dark:text-[#555] font-bold"
                                                x-text="v.location || '—'"></span>
                                        </td>
                                        <td class="px-4 sm:px-6 py-3.5 text-center">
                                            <span
                                                class="inline-flex items-center gap-1 text-[7px] sm:text-[8px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full border"
                                                :class="v.status === 'active' ?
                                                    'text-emerald-600 dark:text-emerald-400 bg-emerald-500/10 border-emerald-500/15' :
                                                    v.status === 'maintenance' ?
                                                    'text-amber-600 dark:text-amber-400 bg-amber-500/10 border-amber-500/15' :
                                                    v.status === 'inactive' ?
                                                    'text-rose-500 dark:text-rose-400/70 bg-rose-500/[0.06] border-rose-500/10' :
                                                    v.status === 'disposed' ?
                                                    'text-gray-400 dark:text-[#333] bg-gray-100 dark:bg-[#111] border-gray-200 dark:border-[#1e1e1e]' :
                                                    'text-gray-400 dark:text-[#333] bg-gray-100 dark:bg-[#111] border-gray-200 dark:border-[#1e1e1e]'">
                                                <span class="w-1.5 h-1.5 rounded-full shrink-0"
                                                    :class="v.status === 'active' ? 'bg-emerald-500 dark:bg-emerald-400' :
                                                        v.status === 'maintenance' ?
                                                        'bg-amber-500 dark:bg-amber-400 animate-pulse' :
                                                        v.status === 'inactive' ? 'bg-rose-500 dark:bg-rose-400/70' :
                                                        'bg-gray-400 dark:bg-[#333]'"></span>
                                                <span
                                                    x-text="v.status === 'active' ? 'Active' : v.status === 'maintenance' ? 'Maint.' : v.status === 'inactive' ? 'Inactive' : v.status === 'disposed' ? 'Disposed' : (v.status || '—')"></span>
                                            </span>
                                        </td>
                                        <td class="px-4 sm:px-6 py-3.5">
                                            <div
                                                class="opacity-30 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-1">
                                                <button @click="openViewModal(v)"
                                                    class="w-7 h-7 rounded-lg flex items-center justify-center text-gray-400 dark:text-[#555] hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-[#1a1a1a] transition-all"
                                                    title="View Details"><i
                                                        class="fa-solid fa-eye text-[9px]"></i></button>
                                                <button @click="openEditModal(v)"
                                                    class="w-7 h-7 rounded-lg flex items-center justify-center text-gray-400 dark:text-[#555] hover:text-blue-600 dark:hover:text-blue-400 hover:bg-blue-500/10 transition-all"
                                                    title="Edit"><i
                                                        class="fa-solid fa-pen-to-square text-[9px]"></i></button>
                                                <button @click="openDeleteModal(v.id, v.plate_number)"
                                                    class="w-7 h-7 rounded-lg flex items-center justify-center text-gray-400 dark:text-[#555] hover:text-rose-600 dark:hover:text-rose-400 hover:bg-rose-500/10 transition-all"
                                                    title="Delete">
                                                    <i class="fa-solid fa-trash-can text-[9px]"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <!-- Empty State -->
                    <div x-show="filtered.length === 0" x-cloak class="py-10 sm:py-12">
                        <div class="flex flex-col items-center justify-center">
                            <div
                                class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] flex items-center justify-center mb-2.5">
                                <i class="fa-solid fa-filter-circle-xmark text-sm text-gray-300 dark:text-[#333]"></i>
                            </div>
                            <p class="text-[10px] sm:text-[11px] text-gray-400 dark:text-[#444] font-medium">No
                                vehicles match your filters</p>
                            <p class="text-[8px] text-gray-300 dark:text-[#333] mt-0.5">Try adjusting your search or
                                filter criteria</p>
                            <button @click="clearAllFilters()"
                                class="mt-3 text-[9px] font-bold text-blue-500 dark:text-blue-400 hover:underline">
                                Clear all filters
                            </button>
                        </div>
                    </div>

                    <!-- Table Footer -->
                    <div
                        class="px-4 sm:px-6 py-3 border-t border-gray-200 dark:border-[#1e1e1e] bg-gray-50 dark:bg-[#111] flex flex-col sm:flex-row justify-between gap-2">
                        <div class="flex items-center gap-4">
                            <div class="flex items-center gap-1.5">
                                <div class="w-2 h-2 rounded-sm bg-emerald-500"></div><span
                                    class="text-[7px] font-bold text-gray-400 dark:text-[#444] uppercase">Active
                                    {{ $activeCount }}</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <div class="w-2 h-2 rounded-sm bg-amber-500"></div><span
                                    class="text-[7px] font-bold text-gray-400 dark:text-[#444] uppercase">Maint.
                                    {{ $maintenanceCount }}</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <div class="w-2 h-2 rounded-sm bg-rose-500"></div><span
                                    class="text-[7px] font-bold text-gray-400 dark:text-[#444] uppercase">Inactive
                                    {{ $inactiveCount }}</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <div class="w-2 h-2 rounded-sm bg-gray-400 dark:bg-[#333]"></div><span
                                    class="text-[7px] font-bold text-gray-400 dark:text-[#444] uppercase">Disposed
                                    {{ $disposedCount }}</span>
                            </div>
                        </div>
                        <span
                            class="text-[7px] text-gray-400 dark:text-[#333] font-bold uppercase tracking-widest">Total:
                            {{ $totalVehicles }} vehicles</span>
                    </div>
                </div>
                <div class="h-12"></div>
            </div>

            <!-- ══════════ VIEW VEHICLE MODAL ══════════ -->
            <template x-teleport="body">
                <div x-show="showViewModal" x-cloak @keydown.escape.window="showViewModal = false"
                    class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 dark:bg-black/80"
                    x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" style="display: none;">
                    <div class="absolute inset-0" @click="showViewModal = false"></div>
                    <div x-show="showViewModal" @click.stop x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                        x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                        class="relative w-full max-w-lg glass-panel rounded-[2rem] flex flex-col max-h-[90vh] overflow-hidden">
                        <!-- Header -->
                        <div
                            class="flex items-center justify-between px-6 sm:px-8 pt-6 sm:pt-7 pb-5 border-b border-gray-200 dark:border-[#1e1e1e] shrink-0">
                            <div class="flex items-center gap-3.5">
                                <div
                                    class="w-10 h-10 rounded-xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center">
                                    <i class="fa-solid fa-eye text-[11px] text-blue-500 dark:text-blue-400"></i>
                                </div>
                                <div>
                                    <h3
                                        class="text-sm sm:text-base font-black tracking-tight text-gray-900 dark:text-white">
                                        Vehicle Details</h3>
                                    <p class="text-[9px] sm:text-[10px] text-gray-500 dark:text-[#555] mt-0.5 truncate max-w-[200px]"
                                        x-text="viewData.brand + ' ' + viewData.model + ' (' + viewData.year + ')'">
                                    </p>
                                </div>
                            </div>
                            <button type="button" @click="showViewModal = false"
                                class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-[#1a1a1a] border border-gray-200 dark:border-[#2a2a2a] hover:bg-gray-200 dark:hover:bg-[#222] flex items-center justify-center text-gray-500 dark:text-[#555] hover:text-gray-900 dark:hover:text-white transition-colors">
                                <i class="fa-solid fa-xmark text-xs"></i>
                            </button>
                        </div>

                        <!-- Scrollable Body -->
                        <div class="flex-1 overflow-y-auto px-6 sm:px-8 py-5 sm:py-6 space-y-5">

                            <!-- Status + Location Badge Row -->
                            <div class="flex items-center gap-2.5 flex-wrap">
                                <span
                                    class="inline-flex items-center gap-1 text-[8px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full border"
                                    :class="viewData.status === 'active' ?
                                        'text-emerald-600 dark:text-emerald-400 bg-emerald-500/10 border-emerald-500/15' :
                                        viewData.status === 'maintenance' ?
                                        'text-amber-600 dark:text-amber-400 bg-amber-500/10 border-amber-500/15' :
                                        viewData.status === 'inactive' ?
                                        'text-rose-500 dark:text-rose-400/70 bg-rose-500/[0.06] border-rose-500/10' :
                                        'text-gray-400 dark:text-[#333] bg-gray-100 dark:bg-[#111] border-gray-200 dark:border-[#1e1e1e]'">
                                    <span class="w-1.5 h-1.5 rounded-full"
                                        :class="viewData.status === 'active' ? 'bg-emerald-500 dark:bg-emerald-400' :
                                            viewData.status === 'maintenance' ?
                                            'bg-amber-500 dark:bg-amber-400 animate-pulse' :
                                            viewData.status === 'inactive' ? 'bg-rose-500 dark:bg-rose-400/70' :
                                            'bg-gray-400 dark:bg-[#333]'"></span>
                                    <span x-text="viewData.status || '—'" class="capitalize"></span>
                                </span>
                                <span class="text-gray-300 dark:text-[#222]">•</span>
                                <span class="text-[9px] text-gray-500 dark:text-[#555] font-bold"
                                    x-text="viewData.location || 'No location'"></span>
                            </div>

                            <!-- Identification -->
                            <div>
                                <p
                                    class="text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#333] mb-3 flex items-center gap-2">
                                    <i class="fa-solid fa-fingerprint text-[7px]"></i> Identification
                                </p>
                                <div
                                    class="p-4 rounded-xl bg-gray-50 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e]">
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <span
                                                class="text-[7px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#333] block mb-1">Plate
                                                Number</span>
                                            <span
                                                class="text-[10px] font-bold font-mono text-blue-600 dark:text-blue-400/70"
                                                x-text="viewData.plate_number || '—'"></span>
                                        </div>
                                        <div>
                                            <span
                                                class="text-[7px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#333] block mb-1">VIN</span>
                                            <span
                                                class="text-[10px] font-bold font-mono text-gray-500 dark:text-[#666] truncate block"
                                                x-text="viewData.vin || '—'"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Vehicle Info -->
                            <div>
                                <p
                                    class="text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#333] mb-3 flex items-center gap-2">
                                    <i class="fa-solid fa-car text-[7px]"></i> Vehicle Information
                                </p>
                                <div
                                    class="p-4 rounded-xl bg-gray-50 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e]">
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <span
                                                class="text-[7px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#333] block mb-1">Brand</span>
                                            <span class="text-[10px] font-bold text-gray-700 dark:text-[#888]"
                                                x-text="viewData.brand || '—'"></span>
                                        </div>
                                        <div>
                                            <span
                                                class="text-[7px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#333] block mb-1">Model</span>
                                            <span class="text-[10px] font-bold text-gray-700 dark:text-[#888]"
                                                x-text="viewData.model || '—'"></span>
                                        </div>
                                        <div>
                                            <span
                                                class="text-[7px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#333] block mb-1">Year</span>
                                            <span
                                                class="text-[10px] font-bold text-gray-700 dark:text-[#888] font-mono"
                                                x-text="viewData.year || '—'"></span>
                                        </div>
                                        <div>
                                            <span
                                                class="text-[7px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#333] block mb-1">Fuel
                                                Type</span>
                                            <span class="text-[10px] font-bold text-gray-700 dark:text-[#888]"
                                                x-text="viewData.fuel_type || '—'"></span>
                                        </div>
                                        <div>
                                            <span
                                                class="text-[7px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#333] block mb-1">Tank
                                                Capacity</span>
                                            <span
                                                class="text-[10px] font-bold text-gray-700 dark:text-[#888] font-mono"
                                                x-text="viewData.tank_capacity ? viewData.tank_capacity + ' L' : '—'"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Driver Assignment -->
                            <div>
                                <p
                                    class="text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#333] mb-3 flex items-center gap-2">
                                    <i class="fa-solid fa-user text-[7px]"></i> Driver Assignment
                                </p>
                                <div
                                    class="p-3.5 rounded-xl bg-gray-50 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e]">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full flex items-center justify-center shrink-0"
                                            :class="viewData.driver_name ?
                                                'bg-purple-500/10 border border-purple-500/15' :
                                                'bg-gray-100 dark:bg-[#0a0a0a] border border-gray-200 dark:border-[#1e1e1e]'">
                                            <i class="fa-solid fa-user text-[9px]"
                                                :class="viewData.driver_name ?
                                                    'text-purple-500 dark:text-purple-400' :
                                                    'text-gray-300 dark:text-[#333]'"></i>
                                        </div>
                                        <div>
                                            <span class="text-[10px] font-bold block"
                                                :class="viewData.driver_name ? 'text-gray-700 dark:text-[#ccc]' :
                                                    'text-gray-400 dark:text-[#444]'"
                                                x-text="viewData.driver_name || 'Unassigned'"></span>
                                            <span class="text-[8px] text-gray-400 dark:text-[#444]"
                                                x-text="viewData.driver_email || ''"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Important Dates -->
                            <div>
                                <p
                                    class="text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#333] mb-3 flex items-center gap-2">
                                    <i class="fa-solid fa-calendar-days text-[7px]"></i> Important Dates
                                </p>
                                <div
                                    class="p-4 rounded-xl bg-gray-50 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e]">
                                    <div class="grid grid-cols-1 gap-3">
                                        <div>
                                            <span
                                                class="text-[7px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#333] block mb-1">Acquisition
                                                Date</span>
                                            <div class="flex items-center gap-1.5">
                                                <i
                                                    class="fa-solid fa-calendar-plus text-[7px] text-emerald-500/60 dark:text-emerald-400/40"></i>
                                                <span class="text-[10px] font-bold text-gray-700 dark:text-[#888]"
                                                    x-text="viewData.acquisition_date || '—'"></span>
                                            </div>
                                        </div>
                                        <div>
                                            <span
                                                class="text-[7px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#333] block mb-1">Expected
                                                Disposal Date</span>
                                            <div class="flex items-center gap-1.5">
                                                <i
                                                    class="fa-solid fa-calendar-minus text-[7px] text-rose-500/60 dark:text-rose-400/40"></i>
                                                <span class="text-[10px] font-bold"
                                                    :class="viewData.exp_disposal_date ? 'text-gray-700 dark:text-[#888]' :
                                                        'text-gray-400 dark:text-[#444]'"
                                                    x-text="viewData.exp_disposal_date || '—'"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Audit Trail -->
                            <div x-show="viewData.created_at || viewData.updated_at">
                                <p
                                    class="text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#333] mb-3 flex items-center gap-2">
                                    <i class="fa-solid fa-clock-rotate-left text-[7px]"></i> Audit Trail
                                </p>
                                <div
                                    class="p-4 rounded-xl bg-gray-50 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e]">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <span
                                                class="text-[7px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#333] block mb-1">Created</span>
                                            <span class="text-[10px] font-bold text-gray-700 dark:text-[#888]"
                                                x-text="viewData.created_at || '—'"></span>
                                        </div>
                                        <div>
                                            <span
                                                class="text-[7px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#333] block mb-1">Last
                                                Updated</span>
                                            <span class="text-[10px] font-bold text-gray-700 dark:text-[#888]"
                                                x-text="viewData.updated_at || '—'"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        <!-- Footer -->
                        <div
                            class="px-6 sm:px-8 py-4 border-t border-gray-200 dark:border-[#1e1e1e] bg-gray-50/50 dark:bg-[#0a0a0a]/50 shrink-0">
                            <div class="flex items-center justify-between gap-2.5">
                                <span
                                    class="text-[8px] text-gray-400 dark:text-[#333] font-bold uppercase tracking-widest"
                                    x-text="'ID: #' + (viewData.id || '—')"></span>
                                <div class="flex items-center gap-2">
                                    <button type="button" @click="showViewModal = false; openEditModal(viewData)"
                                        class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-[10px] font-bold uppercase tracking-widest shadow-lg shadow-blue-600/20 dark:shadow-blue-900/30 transition-all active:scale-[0.97]">
                                        <i class="fa-solid fa-pen-to-square text-[9px]"></i> Edit
                                    </button>
                                    <button type="button" @click="showViewModal = false"
                                        class="px-4 py-2.5 rounded-xl bg-gray-100 dark:bg-[#1a1a1a] border border-gray-200 dark:border-[#2a2a2a] text-[10px] font-bold uppercase tracking-widest text-gray-500 dark:text-[#555] hover:text-gray-900 dark:hover:text-white hover:bg-gray-200 dark:hover:bg-[#222] transition-all">
                                        Close
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <!-- ══════════ ADD / EDIT VEHICLE MODAL ══════════ -->
            <template x-teleport="body">
                <div x-show="showEditModal" x-cloak
                    class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 dark:bg-black/80"
                    x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                    @keydown.escape.window="showEditModal = false" style="display: none;">

                    <!-- Backdrop click to close -->
                    <div class="absolute inset-0" @click="showEditModal = false"></div>

                    <div x-show="showEditModal" @click.stop x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 scale-95 translate-y-3"
                        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                        x-transition:leave-end="opacity-0 scale-95 translate-y-3"
                        class="relative w-full max-w-xl glass-panel rounded-[2rem] flex flex-col max-h-[92vh] overflow-hidden">

                        <!-- ── Header ── -->
                        <div
                            class="flex items-center justify-between px-6 sm:px-8 pt-6 sm:pt-7 pb-5 border-b border-gray-200 dark:border-[#1e1e1e] shrink-0">
                            <div class="flex items-center gap-3.5">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                                    :class="editMode === 'add'
                                        ?
                                        'bg-emerald-500/10 border border-emerald-500/20' :
                                        'bg-blue-500/10 border border-blue-500/20'">
                                    <i class="text-[11px]"
                                        :class="editMode === 'add'
                                            ?
                                            'fa-solid fa-plus text-emerald-500 dark:text-emerald-400' :
                                            'fa-solid fa-pen-to-square text-blue-500 dark:text-blue-400'"></i>
                                </div>
                                <div>
                                    <h3 class="text-sm sm:text-base font-black tracking-tight text-gray-900 dark:text-white"
                                        x-text="editMode === 'add' ? 'Add Vehicle' : 'Edit Vehicle'"></h3>
                                    <p class="text-[9px] sm:text-[10px] text-gray-500 dark:text-[#555] mt-0.5"
                                        x-text="editMode === 'add'
                                            ? 'Register a new vehicle to the fleet'
                                            : (editForm.brand + ' ' + editForm.model + ' (' + editForm.year + ')')">
                                    </p>
                                </div>
                            </div>
                            <button type="button" @click="showEditModal = false"
                                class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-[#1a1a1a] border border-gray-200 dark:border-[#2a2a2a] hover:bg-gray-200 dark:hover:bg-[#222] flex items-center justify-center text-gray-500 dark:text-[#555] hover:text-gray-900 dark:hover:text-white transition-colors">
                                <i class="fa-solid fa-xmark text-xs"></i>
                            </button>
                        </div>

                        <!-- ── Scrollable Body ── -->
                        <div class="flex-1 overflow-y-auto px-6 sm:px-8 py-5 sm:py-6 space-y-5">

                            <!-- Identification Section -->
                            <div>
                                <p
                                    class="text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#333] mb-3 flex items-center gap-2">
                                    <i class="fa-solid fa-fingerprint text-[7px]"></i> Identification
                                </p>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label
                                            class="text-[7px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444] block mb-1.5">Plate
                                            Number <span class="text-rose-400">*</span></label>
                                        <input type="text" x-model="editForm.plate_number"
                                            placeholder="e.g. ABC-1234"
                                            class="form-input w-full px-3.5 py-2.5 rounded-xl text-[10px] font-bold font-mono uppercase"
                                            :class="editErrors.plate_number ? 'border-rose-400 dark:border-rose-500/50' : ''">
                                        <p x-show="editErrors.plate_number" x-text="editErrors.plate_number"
                                            class="text-[8px] text-rose-500 mt-1 font-medium"></p>
                                    </div>
                                    <div>
                                        <label
                                            class="text-[7px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444] block mb-1.5">VIN</label>
                                        <input type="text" x-model="editForm.vin"
                                            placeholder="Vehicle Identification Number"
                                            class="form-input w-full px-3.5 py-2.5 rounded-xl text-[10px] font-bold font-mono uppercase">
                                    </div>
                                </div>
                            </div>

                            <!-- Vehicle Info Section -->
                            <div>
                                <p
                                    class="text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#333] mb-3 flex items-center gap-2">
                                    <i class="fa-solid fa-car text-[7px]"></i> Vehicle Information
                                </p>
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                    <div>
                                        <label
                                            class="text-[7px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444] block mb-1.5">Brand
                                            <span class="text-rose-400">*</span></label>
                                        <input type="text" x-model="editForm.brand" placeholder="Toyota"
                                            class="form-input w-full px-3.5 py-2.5 rounded-xl text-[10px] font-bold"
                                            :class="editErrors.brand ? 'border-rose-400 dark:border-rose-500/50' : ''">
                                        <p x-show="editErrors.brand" x-text="editErrors.brand"
                                            class="text-[8px] text-rose-500 mt-1 font-medium"></p>
                                    </div>
                                    <div>
                                        <label
                                            class="text-[7px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444] block mb-1.5">Model
                                            <span class="text-rose-400">*</span></label>
                                        <input type="text" x-model="editForm.model" placeholder="Hilux"
                                            class="form-input w-full px-3.5 py-2.5 rounded-xl text-[10px] font-bold"
                                            :class="editErrors.model ? 'border-rose-400 dark:border-rose-500/50' : ''">
                                        <p x-show="editErrors.model" x-text="editErrors.model"
                                            class="text-[8px] text-rose-500 mt-1 font-medium"></p>
                                    </div>
                                    <div>
                                        <label
                                            class="text-[7px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444] block mb-1.5">Year</label>
                                        <input type="number" x-model="editForm.year" placeholder="2024"
                                            min="1900" max="2099"
                                            class="form-input w-full px-3.5 py-2.5 rounded-xl text-[10px] font-bold font-mono">
                                    </div>
                                    <div>
                                        <label
                                            class="text-[7px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444] block mb-1.5">Fuel
                                            Type</label>
                                        <select x-model="editForm.fuel_type"
                                            class="form-input form-select w-full px-3.5 py-2.5 rounded-xl text-[10px] font-bold cursor-pointer">
                                            <option value="">Select type</option>
                                            <option value="Diesel">Diesel</option>
                                            <option value="Gasoline">Gasoline</option>
                                            <option value="Electric">Electric</option>
                                            <option value="Hybrid">Hybrid</option>
                                            <option value="LPG">LPG</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-3 mt-3">
                                    <div>
                                        <label
                                            class="text-[7px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444] block mb-1.5">Tank
                                            Capacity (L)</label>
                                        <input type="number" x-model="editForm.tank_capacity" placeholder="60"
                                            min="0" step="0.1"
                                            class="form-input w-full px-3.5 py-2.5 rounded-xl text-[10px] font-bold font-mono">
                                    </div>
                                    <div>
                                        <label
                                            class="text-[7px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444] block mb-1.5">Location</label>
                                        <input type="text" x-model="editForm.location"
                                            placeholder="e.g. Main Garage"
                                            class="form-input w-full px-3.5 py-2.5 rounded-xl text-[10px] font-bold">
                                    </div>
                                    <div>
                                        <label
                                            class="text-[7px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444] block mb-1.5">Acquisition
                                            Date <span class="text-rose-400">*</span></label>
                                        <input type="date" x-model="editForm.acquisition_date"
                                            class="form-input w-full px-3.5 py-2.5 rounded-xl text-[10px] font-bold font-mono"
                                            :class="editErrors.acquisition_date ? 'border-rose-400 dark:border-rose-500/50' : ''">
                                        <p x-show="editErrors.acquisition_date" x-text="editErrors.acquisition_date"
                                            class="text-[8px] text-rose-500 mt-1 font-medium"></p>
                                    </div>
                                    <div>
                                        <label
                                            class="text-[7px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444] block mb-1.5">Expected
                                            Disposal Date</label>
                                        <input type="date" x-model="editForm.exp_disposal_date"
                                            class="form-input w-full px-3.5 py-2.5 rounded-xl text-[10px] font-bold font-mono"
                                            :class="editErrors.exp_disposal_date ? 'border-rose-400 dark:border-rose-500/50' : ''">
                                        <p x-show="editErrors.exp_disposal_date" x-text="editErrors.exp_disposal_date"
                                            class="text-[8px] text-rose-500 mt-1 font-medium"></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Driver Assignment -->
                            <div>
                                <p
                                    class="text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#333] mb-3 flex items-center gap-2">
                                    <i class="fa-solid fa-user text-[7px]"></i> Driver Assignment
                                </p>
                                <select x-model="editForm.driver_id"
                                    class="form-input form-select w-full px-3.5 py-2.5 rounded-xl text-[10px] font-bold cursor-pointer">
                                    <option value="">— No Driver Assigned —</option>
                                    <template x-for="d in drivers" :key="d.id">
                                        <option :value="String(d.id)" x-text="d.name + ' (' + d.email + ')'">
                                        </option>
                                    </template>
                                </select>
                            </div>

                            <!-- Status -->
                            <div>
                                <p
                                    class="text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#333] mb-3 flex items-center gap-2">
                                    <i class="fa-solid fa-circle-dot text-[7px]"></i> Status
                                </p>
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" @click="editForm.status = 'active'" class="status-btn"
                                        :class="editForm.status === 'active' ?
                                            'active text-emerald-600 dark:text-emerald-400 bg-emerald-500/10 border-emerald-500/25' :
                                            'text-gray-400 dark:text-[#444] border-gray-200 dark:border-[#222] hover:border-emerald-500/25 hover:text-emerald-500'">
                                        <span class="w-1.5 h-1.5 rounded-full"
                                            :class="editForm.status === 'active' ? 'bg-emerald-500' :
                                                'bg-gray-300 dark:bg-[#333]'"></span>
                                        Active
                                    </button>
                                    <button type="button" @click="editForm.status = 'maintenance'"
                                        class="status-btn"
                                        :class="editForm.status === 'maintenance' ?
                                            'active text-amber-600 dark:text-amber-400 bg-amber-500/10 border-amber-500/25' :
                                            'text-gray-400 dark:text-[#444] border-gray-200 dark:border-[#222] hover:border-amber-500/25 hover:text-amber-500'">
                                        <span class="w-1.5 h-1.5 rounded-full"
                                            :class="editForm.status === 'maintenance' ? 'bg-amber-500 animate-pulse' :
                                                'bg-gray-300 dark:bg-[#333]'"></span>
                                        Maintenance
                                    </button>
                                    <button type="button" @click="editForm.status = 'inactive'" class="status-btn"
                                        :class="editForm.status === 'inactive' ?
                                            'active text-rose-500 dark:text-rose-400 bg-rose-500/10 border-rose-500/25' :
                                            'text-gray-400 dark:text-[#444] border-gray-200 dark:border-[#222] hover:border-rose-500/25 hover:text-rose-500'">
                                        <span class="w-1.5 h-1.5 rounded-full"
                                            :class="editForm.status === 'inactive' ? 'bg-rose-500' :
                                                'bg-gray-300 dark:bg-[#333]'"></span>
                                        Inactive
                                    </button>
                                    <button type="button" @click="editForm.status = 'disposed'" class="status-btn"
                                        :class="editForm.status === 'disposed' ?
                                            'active text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-[#111] border-gray-300 dark:border-[#333]' :
                                            'text-gray-400 dark:text-[#444] border-gray-200 dark:border-[#222] hover:border-gray-400 dark:hover:border-[#333] hover:text-gray-500'">
                                        <span class="w-1.5 h-1.5 rounded-full"
                                            :class="editForm.status === 'disposed' ? 'bg-gray-400 dark:bg-[#555]' :
                                                'bg-gray-300 dark:bg-[#333]'"></span>
                                        Disposed
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- ── Footer ── -->
                        <div
                            class="px-6 sm:px-8 py-4 border-t border-gray-200 dark:border-[#1e1e1e] bg-gray-50/50 dark:bg-[#0a0a0a]/50 shrink-0">
                            <div class="flex items-center justify-end gap-2.5">
                                <button type="button" @click="showEditModal = false"
                                    class="px-5 py-2.5 rounded-xl bg-gray-100 dark:bg-[#1a1a1a] border border-gray-200 dark:border-[#2a2a2a] text-[10px] font-bold uppercase tracking-widest text-gray-500 dark:text-[#555] hover:text-gray-900 dark:hover:text-white hover:bg-gray-200 dark:hover:bg-[#222] transition-all">
                                    Cancel
                                </button>
                                <button type="button" @click="submitEditForm()" :disabled="editSubmitting"
                                    class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-[10px] font-bold uppercase tracking-widest text-white transition-all active:scale-[0.97] disabled:opacity-50 disabled:cursor-not-allowed"
                                    :class="editMode === 'add'
                                        ?
                                        'bg-emerald-600 hover:bg-emerald-500 shadow-lg shadow-emerald-600/20 dark:shadow-emerald-900/30' :
                                        'bg-blue-600 hover:bg-blue-500 shadow-lg shadow-blue-600/20 dark:shadow-blue-900/30'">
                                    <i x-show="!editSubmitting"
                                        :class="editMode === 'add' ? 'fa-solid fa-plus' : 'fa-solid fa-check'"
                                        class="text-[9px]"></i>
                                    <i x-show="editSubmitting" class="fa-solid fa-spinner fa-spin text-[9px]"></i>
                                    <span
                                        x-text="editSubmitting ? 'Saving...' : (editMode === 'add' ? 'Add Vehicle' : 'Save Changes')"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <!-- ══════════ DELETE VEHICLE MODAL ══════════ -->
            <template x-teleport="body">
                <div x-show="showDeleteModal" x-cloak
                    class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-black/50 dark:bg-black/80"
                    x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                    @keydown.escape.window="showDeleteModal = false" style="display: none;">
                    <div class="absolute inset-0" @click="showDeleteModal = false"></div>
                    <div x-show="showDeleteModal" @click.stop x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                        class="relative w-full max-w-sm glass-panel rounded-[2rem] overflow-hidden">

                        <div class="px-6 sm:px-8 pt-7 pb-6 text-center">
                            <div
                                class="w-14 h-14 rounded-2xl bg-rose-500/10 border border-rose-500/20 flex items-center justify-center mx-auto mb-4">
                                <i
                                    class="fa-solid fa-triangle-exclamation text-xl text-rose-500 dark:text-rose-400"></i>
                            </div>
                            <h3 class="text-base font-black tracking-tight text-gray-900 dark:text-white mb-1.5">Delete
                                Vehicle</h3>
                            <p class="text-[10px] text-gray-500 dark:text-[#555] leading-relaxed">
                                This will permanently remove
                                <span class="font-bold text-gray-700 dark:text-[#ccc] font-mono"
                                    x-text="deletePlateNumber"></span>
                                from the fleet. This action cannot be undone.
                            </p>
                        </div>

                        <div class="px-6 sm:px-8 pb-6 flex items-center gap-2.5">
                            <button type="button" @click="showDeleteModal = false"
                                class="flex-1 px-4 py-2.5 rounded-xl bg-gray-100 dark:bg-[#1a1a1a] border border-gray-200 dark:border-[#2a2a2a] text-[10px] font-bold uppercase tracking-widest text-gray-500 dark:text-[#555] hover:text-gray-900 dark:hover:text-white hover:bg-gray-200 dark:hover:bg-[#222] transition-all text-center">
                                Cancel
                            </button>
                            <button type="button" @click="confirmDelete()" :disabled="deleteSubmitting"
                                class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-[10px] font-bold uppercase tracking-widest shadow-lg shadow-rose-600/20 dark:shadow-rose-900/30 transition-all active:scale-[0.97] disabled:opacity-50 disabled:cursor-not-allowed">
                                <i x-show="!deleteSubmitting" class="fa-solid fa-trash-can text-[9px]"></i>
                                <i x-show="deleteSubmitting" class="fa-solid fa-spinner fa-spin text-[9px]"></i>
                                <span x-text="deleteSubmitting ? 'Deleting...' : 'Delete'"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </template>

            <!-- ══════════ TOAST NOTIFICATION ══════════ -->
            <template x-teleport="body">
                <div x-show="toast.show" x-cloak
                    class="fixed bottom-6 right-6 z-[200] flex items-center gap-3 px-5 py-3.5 rounded-xl shadow-2xl border"
                    :class="toast.type === 'success' ?
                        'bg-emerald-50 dark:bg-emerald-500/10 border-emerald-200 dark:border-emerald-500/20' :
                        toast.type === 'error' ?
                        'bg-rose-50 dark:bg-rose-500/10 border-rose-200 dark:border-rose-500/20' :
                        'bg-blue-50 dark:bg-blue-500/10 border-blue-200 dark:border-blue-500/20'"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-3 scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                    x-transition:leave-end="opacity-0 translate-y-3 scale-95" style="display: none;">
                    <div class="w-6 h-6 rounded-md flex items-center justify-center shrink-0"
                        :class="toast.type === 'success' ?
                            'bg-emerald-500/15' :
                            toast.type === 'error' ?
                            'bg-rose-500/15' :
                            'bg-blue-500/15'">
                        <i class="text-[10px]"
                            :class="toast.type === 'success' ?
                                'fa-solid fa-check text-emerald-600 dark:text-emerald-400' :
                                toast.type === 'error' ?
                                'fa-solid fa-xmark text-rose-600 dark:text-rose-400' :
                                'fa-solid fa-info text-blue-600 dark:text-blue-400'"></i>
                    </div>
                    <span class="text-[10px] font-bold"
                        :class="toast.type === 'success' ?
                            'text-emerald-700 dark:text-emerald-300' :
                            toast.type === 'error' ?
                            'text-rose-700 dark:text-rose-300' :
                            'text-blue-700 dark:text-blue-300'"
                        x-text="toast.message"></span>
                </div>
            </template>

    </div>
    </main>


    <!-- ══════════ ALPINE.JS APP ══════════ -->
    <script>
        function vehicleManagementApp() {
            return {
                // ── Data ──
                vehicles: JSON.parse(document.getElementById('vehicles-data').textContent),
                drivers: @json($drivers ?? []),

                // ── Filters ──
                search: '',
                statusFilter: '',
                fuelFilter: '',
                assignmentFilter: '',
                sortBy: 'newest',
                sortLabels: {
                    newest: 'Newest First',
                    oldest: 'Oldest First',
                    brand_asc: 'Brand A-Z',
                    brand_desc: 'Brand Z-A',
                    year_desc: 'Newest Year',
                    year_asc: 'Oldest Year',
                },

                // ── View Modal ──
                showViewModal: false,
                viewData: {},

                // ── Edit Modal ──
                showEditModal: false,
                editMode: 'add', // 'add' | 'edit'
                editId: null,
                editForm: {
                    plate_number: '',
                    vin: '',
                    brand: '',
                    model: '',
                    year: '',
                    fuel_type: '',
                    tank_capacity: '',
                    location: '',
                    driver_id: '',
                    status: 'active',
                },
                editErrors: {},
                editSubmitting: false,

                // ── Delete Modal ──
                showDeleteModal: false,
                deleteId: null,
                deletePlateNumber: '',
                deleteSubmitting: false,

                // ── Toast ──
                toast: {
                    show: false,
                    message: '',
                    type: 'success'
                },

                // ── Computed ──
                get hasActiveFilters() {
                    return this.search.length > 0 || this.statusFilter || this.fuelFilter || this.assignmentFilter ||
                        this.sortBy !== 'newest';
                },

                get filtered() {
                    let result = [...this.vehicles];

                    // Search
                    if (this.search.length > 0) {
                        const q = this.search.toLowerCase();
                        result = result.filter(v =>
                            (v.brand || '').toLowerCase().includes(q) ||
                            (v.model || '').toLowerCase().includes(q) ||
                            (v.plate_number || '').toLowerCase().includes(q) ||
                            (v.vin || '').toLowerCase().includes(q) ||
                            (v.driver_name || '').toLowerCase().includes(q) ||
                            (v.location || '').toLowerCase().includes(q)
                        );
                    }

                    // Status
                    if (this.statusFilter) {
                        result = result.filter(v => v.status === this.statusFilter);
                    }

                    // Fuel
                    if (this.fuelFilter) {
                        result = result.filter(v => v.fuel_type === this.fuelFilter);
                    }

                    // Assignment
                    if (this.assignmentFilter === 'assigned') {
                        result = result.filter(v => v.driver_id && v.driver_id !== '');
                    } else if (this.assignmentFilter === 'unassigned') {
                        result = result.filter(v => !v.driver_id || v.driver_id === '');
                    }

                    // Sort
                    switch (this.sortBy) {
                        case 'oldest':
                            result.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
                            break;
                        case 'brand_asc':
                            result.sort((a, b) => (a.brand || '').localeCompare(b.brand || ''));
                            break;
                        case 'brand_desc':
                            result.sort((a, b) => (b.brand || '').localeCompare(a.brand || ''));
                            break;
                        case 'year_desc':
                            result.sort((a, b) => (b.year || 0) - (a.year || 0));
                            break;
                        case 'year_asc':
                            result.sort((a, b) => (a.year || 0) - (b.year || 0));
                            break;
                        default: // newest
                            result.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
                    }

                    return result;
                },

                // ── Methods ──
                clearAllFilters() {
                    this.search = '';
                    this.statusFilter = '';
                    this.fuelFilter = '';
                    this.assignmentFilter = '';
                    this.sortBy = 'newest';
                },
                formatDate(dateStr) {
                    if (!dateStr) return '—';
                    if (/^\d{1,2}:\d{2}(:\d{2})?$/.test(String(dateStr))) return String(dateStr);
                    try {
                        const d = new Date(dateStr);
                        if (isNaN(d.getTime())) return '—';
                        return d.toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'short',
                            day: 'numeric'
                        });
                    } catch (e) {
                        return '—';
                    }
                },

                formatDateTime(dateStr) {
                    if (!dateStr) return '—';
                    if (/^\d{1,2}:\d{2}(:\d{2})?$/.test(String(dateStr))) return String(dateStr);
                    try {
                        const d = new Date(dateStr);
                        if (isNaN(d.getTime())) return '—';
                        const date = d.toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'short',
                            day: 'numeric'
                        });
                        const time = d.toLocaleTimeString('en-US', {
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                        return date + ' · ' + time;
                    } catch (e) {
                        return '—';
                    }
                },

                openViewModal(v) {
                    this.viewData = {
                        ...v
                    };
                    this.showViewModal = true;
                },

                openAddModal() {
                    this.editMode = 'add';
                    this.editId = null;
                    this.editForm = {
                        plate_number: '',
                        vin: '',
                        brand: '',
                        model: '',
                        year: new Date().getFullYear(),
                        fuel_type: '',
                        tank_capacity: '',
                        location: '',
                        driver_id: '',
                        status: 'active',
                        acquisition_date: '',
                        exp_disposal_date: '',
                    };
                    this.editErrors = {};
                    this.showEditModal = true;
                },

                openEditModal(v) {
                    this.editMode = 'edit';
                    this.editId = v.id;
                    this.editForm = {
                        plate_number: v.plate_number || '',
                        vin: v.vin || '',
                        brand: v.brand || '',
                        model: v.model || '',
                        year: v.year || '',
                        fuel_type: v.fuel_type || '',
                        tank_capacity: v.tank_capacity || '',
                        location: v.location || '',
                        driver_id: v.driver_id ? String(v.driver_id) : '',
                        status: v.status || 'active',
                        acquisition_date: v.acquisition_date_raw || '',
                        exp_disposal_date: v.exp_disposal_date_raw || '',
                    };
                    this.editErrors = {};
                    this.showEditModal = true;
                },

                openDeleteModal(id, plateNumber) {
                    this.deleteId = id;
                    this.deletePlateNumber = plateNumber;
                    this.showDeleteModal = true;
                },

                async submitEditForm() {
                    this.editErrors = {};
                    this.editSubmitting = true;

                    try {
                        const url = this.editMode === 'add' ?
                            '{{ route('vehicles.store') }}' :
                            '{{ route('vehicles.update', ':id') }}'.replace(':id', this.editId);

                        const method = this.editMode === 'add' ? 'POST' : 'PATCH';

                        const response = await fetch(url, {
                            method: method,
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                    'content'),
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify(this.editForm),
                        });

                        const data = await response.json();

                        if (!response.ok) {
                            if (data.errors) {
                                this.editErrors = data.errors;
                            }
                            this.showToast(data.message || 'Something went wrong.', 'error');
                            return;
                        }

                        // Refresh the vehicles list
                        if (data.vehicle) {
                            if (this.editMode === 'add') {
                                this.vehicles.unshift(data.vehicle);
                            } else {
                                const idx = this.vehicles.findIndex(v => v.id === this.editId);
                                if (idx !== -1) this.vehicles[idx] = data.vehicle;
                            }
                        }

                        this.showEditModal = false;
                        this.showToast(
                            this.editMode === 'add' ? 'Vehicle added successfully.' :
                            'Vehicle updated successfully.',
                            'success'
                        );
                    } catch (e) {
                        this.showToast('Network error. Please try again.', 'error');
                    } finally {
                        this.editSubmitting = false;
                    }
                },

                async confirmDelete() {
                    this.deleteSubmitting = true;

                    try {
                        const url = '{{ route('vehicles.destroy', ':id') }}'.replace(':id', this.deleteId);

                        const response = await fetch(url, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                    'content'),
                                'Accept': 'application/json',
                            },
                        });

                        const data = await response.json();

                        if (!response.ok) {
                            this.showToast(data.message || 'Failed to delete vehicle.', 'error');
                            return;
                        }

                        this.vehicles = this.vehicles.filter(v => v.id !== this.deleteId);
                        this.showDeleteModal = false;
                        this.showToast('Vehicle deleted successfully.', 'success');
                    } catch (e) {
                        this.showToast('Network error. Please try again.', 'error');
                    } finally {
                        this.deleteSubmitting = false;
                    }
                },

                showToast(message, type = 'success') {
                    this.toast = {
                        show: true,
                        message,
                        type
                    };
                    setTimeout(() => {
                        this.toast.show = false;
                    }, 3500);
                },
            };
        }
    </script>

</body>

</html>
