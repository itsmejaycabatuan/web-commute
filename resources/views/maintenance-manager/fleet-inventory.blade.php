<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SmartCommute | Fleet Inventory</title>
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
        [x-cloak] { display: none !important; }
        .glass-panel { background: #111111 !important; border: 1px solid #1e1e1e; box-shadow: 0 4px 24px rgba(0,0,0,0.6); }
        .glass-card { background: #161616; border: 1px solid #222222; box-shadow: 0 4px 24px rgba(0,0,0,0.5); }
        .sidebar-transition { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        .table-row { transition: all 0.2s ease; }
        .table-row:hover { background: #1a1a1a; }

        ::-webkit-scrollbar { width: 3px; height: 3px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #333; border-radius: 10px; }

        .form-input {
            background: #111;
            border: 1px solid #1e1e1e;
            color: #fff;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-input:focus {
            border-color: rgba(59, 130, 246, 0.4);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.06);
        }
        .form-input::placeholder { color: #2a2a2a; }

        select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='rgba(255,255,255,0.3)' viewBox='0 0 16 16'%3E%3Cpath d='M8 11L3 6h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
        }

        input[type="date"]::-webkit-calendar-picker-indicator { filter: invert(0.5); cursor: pointer; }
    </style>
</head>

@php
    $totalRecords = $inventories->count();
    $totalCost = $inventories->sum('maintenance_cost');
    $avgCost = $totalRecords > 0 ? $totalCost / $totalRecords : 0;
    $highCostCount = $inventories->filter(fn($i) => ($i['maintenance_cost'] ?? 0) > 1500000)->count();
@endphp

<body class="antialiased text-white" x-data="fleetInventoryApp()" x-init="init()">

    @include('components.flash')
    @include('maintenance-manager.layout.sidebar')

    <main :class="open ? 'md:ml-72' : 'md:ml-20'" class="sidebar-transition pt-8 pr-4 sm:pr-8 pb-8 pl-4 sm:pl-8 min-h-screen mb-12">
        <div class="max-w-[1400px] mx-auto">

            <!-- ── Mobile: Identity Card ── -->
            <div class="lg:hidden mb-5">
                <div class="glass-card p-4 rounded-[1.25rem]">
                    <div class="flex items-center gap-3.5">
                        <div class="w-11 h-11 bg-amber-600 rounded-xl flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-wrench text-sm text-white"></i>
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-sm font-bold text-white truncate">Maintenance Manager</h2>
                            <p class="text-[10px] text-[#555] truncate">{{ Auth::user()->email }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 pt-3.5 mt-3.5 border-t border-[#1e1e1e]">
                        <i class="fa-solid fa-wrench text-[8px] text-amber-400"></i>
                        <span class="text-[10px] text-[#888] font-bold">Fleet Access</span>
                        <span class="text-[#333]">•</span>
                        <span class="font-mono text-[9px] text-[#444]">Inventory</span>
                    </div>
                </div>
            </div>

            <!-- ── Page Header (desktop) ── -->
            <div class="hidden lg:flex items-end justify-between mb-8">
                <div>
                    <div class="flex items-center gap-2 mb-1.5">
                        <span class="text-[9px] font-bold uppercase tracking-[0.15em] text-[#444]">Financial Records</span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-black tracking-tight">Fleet <span class="text-blue-500">Inventory</span></h1>
                    <p class="text-[11px] text-[#555] mt-1 flex items-center gap-2">
                        <i class="fa-solid fa-wrench text-[9px] text-amber-400"></i>
                        Maintenance costs and financial records per vehicle
                    </p>
                </div>
                <button @click="openAddModal()"
                    class="flex items-center gap-2 px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-[10px] font-bold uppercase tracking-widest shadow-lg shadow-blue-900/30 transition-all active:scale-[0.97]">
                    <i class="fa-solid fa-plus text-[9px]"></i>
                    <span>Add Record</span>
                </button>
            </div>

            <!-- ── Mobile Add Button ── -->
            <div class="lg:hidden mb-5">
                <button @click="openAddModal()"
                    class="w-full flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-[10px] font-bold uppercase tracking-widest transition-all active:scale-[0.98]">
                    <i class="fa-solid fa-plus text-[9px]"></i>
                    <span>Add Record</span>
                </button>
            </div>

            <!-- ══════════ STAT CARDS ══════════ -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4 mb-5 sm:mb-6">

                <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-blue-500">
                    <div class="flex items-center gap-2 mb-2 sm:mb-3">
                        <div class="w-6 h-6 rounded-md bg-blue-500/10 flex items-center justify-center">
                            <i class="fa-solid fa-box-archive text-[8px] text-blue-400"></i>
                        </div>
                        <span class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Total Records</span>
                    </div>
                    <div class="flex items-baseline gap-1.5">
                        <span class="text-xl sm:text-2xl font-black tracking-tight">{{ $totalRecords }}</span>
                        <span class="text-xs font-bold text-[#555]">{{ $totalRecords === 1 ? 'entry' : 'entries' }}</span>
                    </div>
                </div>

                <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-amber-500">
                    <div class="flex items-center gap-2 mb-2 sm:mb-3">
                        <div class="w-6 h-6 rounded-md bg-amber-500/10 flex items-center justify-center">
                            <i class="fa-solid fa-coins text-[8px] text-amber-400"></i>
                        </div>
                        <span class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Total Cost</span>
                    </div>
                    <div class="flex items-baseline gap-1.5">
                        <span class="text-xl sm:text-2xl font-black tracking-tight text-amber-400">₱{{ number_format($totalCost, 2) }}</span>
                    </div>
                </div>

                <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-emerald-500">
                    <div class="flex items-center gap-2 mb-2 sm:mb-3">
                        <div class="w-6 h-6 rounded-md bg-emerald-500/10 flex items-center justify-center">
                            <i class="fa-solid fa-chart-line text-[8px] text-emerald-400"></i>
                        </div>
                        <span class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Avg Cost</span>
                    </div>
                    <div class="flex items-baseline gap-1.5">
                        <span class="text-xl sm:text-2xl font-black tracking-tight text-emerald-400">₱{{ number_format($avgCost, 2) }}</span>
                    </div>
                </div>

                <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-rose-500 col-span-2 sm:col-span-1">
                    <div class="flex items-center gap-2 mb-2 sm:mb-3">
                        <div class="w-6 h-6 rounded-md bg-rose-500/10 flex items-center justify-center">
                            <i class="fa-solid fa-triangle-exclamation text-[8px] text-rose-400"></i>
                        </div>
                        <span class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">High Cost</span>
                    </div>
                    <div class="flex items-baseline gap-1.5">
                        <span class="text-xl sm:text-2xl font-black tracking-tight {{ $highCostCount > 0 ? 'text-rose-400' : 'text-[#555]' }}">{{ $highCostCount }}</span>
                        <span class="text-xs font-bold text-[#555]">{{ $highCostCount === 1 ? 'item' : 'items' }}</span>
                    </div>
                    <p class="text-[7px] text-[#333] mt-1 font-bold uppercase">Over ₱1.5M</p>
                </div>

            </div>

            <!-- ══════════ SEARCH ══════════ -->
            <div class="mb-5">
                <div class="relative max-w-sm">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-[#333] text-[10px]"></i>
                    <input type="text" x-model="search"
                        placeholder="Search vehicle, plate, driver..."
                        class="form-input w-full pl-10 pr-4 py-2.5 rounded-xl text-[10px] font-bold placeholder:text-[#2a2a2a]">
                </div>
            </div>

            <!-- ══════════ TABLE ══════════ -->
            <div class="glass-card rounded-[1.25rem] sm:rounded-[1.5rem] overflow-hidden">
                <div class="p-4 sm:p-6 pb-0">
                    <div class="flex items-center justify-between mb-4 sm:mb-5">
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-lg bg-[#111] border border-[#1e1e1e] flex items-center justify-center">
                                <i class="fa-solid fa-box-archive text-[9px] text-blue-400"></i>
                            </div>
                            <span class="text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-[#555]">Inventory Records</span>
                        </div>
                        <span class="text-[7px] sm:text-[8px] font-bold text-[#333] uppercase tracking-widest" x-text="filtered.length + ' of ' + inventories.length"></span>
                    </div>
                </div>
                <div class="overflow-x-auto -mx-2 px-2 pb-2">
                    <table class="w-full text-left min-w-[650px]">
                        <thead>
                            <tr class="text-[7px] sm:text-[8px] uppercase tracking-[0.15em] text-[#444] border-b border-[#1e1e1e]">
                                <th class="px-4 sm:px-6 py-2.5 font-bold sticky left-0 bg-[#161616] z-10 min-w-[250px]">Vehicle</th>
                                <th class="px-4 sm:px-6 py-2.5 font-bold text-right">Maintenance Cost</th>
                                <th class="px-4 sm:px-6 py-2.5 font-bold">Notes</th>
                                <th class="px-4 sm:px-6 py-2.5 font-bold text-center w-24">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#1a1a1a]">
                            <template x-for="(inv, index) in filtered" :key="inv.id">
                                <tr class="table-row group">
                                    <td class="px-4 sm:px-6 py-3.5 sticky left-0 z-10 bg-[#161616] group-hover:bg-[#1a1a1a] transition-colors">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-lg bg-blue-500/10 border border-blue-500/15 flex items-center justify-center shrink-0">
                                                <i class="fa-solid fa-van-shuttle text-[8px] text-blue-400"></i>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-[10px] sm:text-[11px] font-bold text-[#ccc] truncate max-w-[200px]" x-text="inv.vehicle_name"></p>
                                                <div class="flex items-center gap-1.5 mt-0.5">
                                                    <span class="text-[8px] text-[#444] font-bold" x-text="inv.vehicle_year || ''"></span>
                                                    <span class="text-[#222]">•</span>
                                                    <span class="text-[8px] font-mono text-blue-400/50 font-bold" x-text="inv.plate_number || '—'"></span>
                                                    <template x-if="inv.driver_name">
                                                        <span class="text-[#222]">•</span>
                                                    </template>
                                                    <span class="text-[8px] text-[#333] font-bold truncate max-w-[70px]" x-show="inv.driver_name" x-text="inv.driver_name"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 sm:px-6 py-3.5 text-right">
                                        <span class="text-[10px] sm:text-[11px] font-bold font-mono"
                                              :class="Number(inv.maintenance_cost) > 1500000 ? 'text-rose-400' : Number(inv.maintenance_cost) > 500000 ? 'text-amber-400' : 'text-white/60'"
                                              x-text="fmt(inv.maintenance_cost)"></span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-3.5">
                                        <span class="text-[9px] sm:text-[10px] text-[#444] truncate block max-w-[180px]" :title="inv.notes || ''" x-text="inv.notes || '—'"></span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-3.5">
                                        <div class="opacity-30 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-1">
                                            <button @click="openViewModal(inv)"
                                                class="w-7 h-7 rounded-lg flex items-center justify-center text-[#555] hover:text-white hover:bg-[#1a1a1a] transition-all"
                                                title="View Details">
                                                <i class="fa-solid fa-eye text-[9px]"></i>
                                            </button>
                                            <button @click="openEditModal(inv)"
                                                class="w-7 h-7 rounded-lg flex items-center justify-center text-[#555] hover:text-blue-400 hover:bg-blue-500/10 transition-all"
                                                title="Edit">
                                                <i class="fa-solid fa-pen-to-square text-[9px]"></i>
                                            </button>
                                            <form method="POST" :action="`{{ route('maintenance-manager.fleet-inventory.destroy', '__ID__') }}`.replace('__ID__', inv.id)" class="inline-flex">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" onclick="return confirm('Remove this inventory record?')"
                                                    class="w-7 h-7 rounded-lg flex items-center justify-center text-[#555] hover:text-rose-400 hover:bg-rose-500/10 transition-all"
                                                    title="Delete">
                                                    <i class="fa-solid fa-trash-can text-[9px]"></i>
                                                </button>
                                            </form>
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
                        <div class="w-10 h-10 rounded-xl bg-[#111] border border-[#1e1e1e] flex items-center justify-center mb-2.5">
                            <i class="fa-solid fa-box-open text-sm text-[#333]"></i>
                        </div>
                        <p class="text-[10px] sm:text-[11px] text-[#444] font-medium">No inventory records found</p>
                        <p class="text-[8px] text-[#333] mt-0.5">Click "Add Record" to get started</p>
                    </div>
                </div>

                <!-- Table Footer -->
                <div class="px-4 sm:px-6 py-3 border-t border-[#1e1e1e] bg-[#111] flex flex-col sm:flex-row justify-between gap-2">
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-1.5">
                            <div class="w-2 h-2 rounded-sm bg-rose-500"></div>
                            <span class="text-[7px] font-bold text-[#444] uppercase">High Cost (₱1.5M+)</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <div class="w-2 h-2 rounded-sm bg-amber-500"></div>
                            <span class="text-[7px] font-bold text-[#444] uppercase">Medium (₱500K+)</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <div class="w-2 h-2 rounded-sm bg-[#555]"></div>
                            <span class="text-[7px] font-bold text-[#444] uppercase">Normal</span>
                        </div>
                    </div>
                    <span class="text-[7px] text-[#333] font-bold uppercase tracking-widest">Total: <span class="text-amber-400/70 font-mono" x-text="fmt(inventories.reduce((s,i) => s + Number(i.maintenance_cost), 0))"></span></span>
                </div>
            </div>

            <div class="h-12"></div>
        </div>
    </main>

    <!-- ══════════ LOGOUT MODAL ══════════ -->
    <div x-show="showLogoutModal"
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/80"
        x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" style="display: none;">

        <div @click.away="showLogoutModal = false"
            class="glass-panel p-8 rounded-[2rem] max-w-sm w-full">
            <div class="text-center">
                <div class="w-14 h-14 bg-red-500/10 rounded-2xl flex items-center justify-center mx-auto mb-5 border border-red-500/20">
                    <i class="fa-solid fa-power-off text-red-400 text-lg"></i>
                </div>
                <h3 class="text-lg font-bold text-white mb-1.5">End Session?</h3>
                <p class="text-xs text-[#666] mb-7">Are you sure you want to exit the Maintenance Console?</p>
                <div class="flex gap-2.5">
                    <button @click="showLogoutModal = false"
                        class="flex-1 py-3 rounded-xl bg-[#1a1a1a] border border-[#2a2a2a] text-white text-[10px] font-bold uppercase tracking-widest hover:bg-[#222] transition">
                        Cancel
                    </button>
                    <form action="{{ route('users.logout') }}" method="POST" class="flex-1">
                        @csrf
                        <button type="submit"
                            class="w-full py-3 rounded-xl bg-red-600 text-white text-[10px] font-bold uppercase tracking-widest hover:bg-red-700 transition active:scale-[0.98]">
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════ VIEW RECORD MODAL ══════════ -->
    <template x-teleport="body">
        <div x-show="showViewModal" x-cloak @keydown.escape.window="showViewModal = false"
            class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/80"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            style="display: none;">

            <div x-show="showViewModal" @click.stop
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                 class="relative w-full max-w-lg glass-panel rounded-[2rem] flex flex-col max-h-[90vh] overflow-hidden">

                <!-- Header -->
                <div class="flex items-center justify-between px-6 sm:px-8 pt-6 sm:pt-7 pb-5 border-b border-[#1e1e1e] shrink-0">
                    <div class="flex items-center gap-3.5">
                        <div class="w-10 h-10 rounded-xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center">
                            <i class="fa-solid fa-eye text-[11px] text-blue-400"></i>
                        </div>
                        <div>
                            <h3 class="text-sm sm:text-base font-black tracking-tight">Inventory Details</h3>
                            <p class="text-[9px] sm:text-[10px] text-[#555] mt-0.5 truncate max-w-[200px]" x-text="viewData.vehicle_name"></p>
                        </div>
                    </div>
                    <button type="button" @click="showViewModal = false"
                        class="w-8 h-8 rounded-lg bg-[#1a1a1a] border border-[#2a2a2a] hover:bg-[#222] flex items-center justify-center text-[#555] hover:text-white transition-colors">
                        <i class="fa-solid fa-xmark text-xs"></i>
                    </button>
                </div>

                <!-- Body -->
                <div class="flex-1 overflow-y-auto px-6 sm:px-8 py-5 sm:py-6">

                    <!-- Linked Vehicle -->
                    <p class="text-[8px] font-bold uppercase tracking-[0.15em] text-[#333] mb-3">Linked Vehicle</p>
                    <div class="p-4 rounded-xl bg-[#111] border border-[#1e1e1e] mb-5">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-9 h-9 rounded-lg bg-blue-500/10 border border-blue-500/15 flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-van-shuttle text-[10px] text-blue-400"></i>
                            </div>
                            <div>
                                <p class="text-[11px] font-bold text-[#ccc]" x-text="viewData.vehicle_name + ' (' + (viewData.vehicle_year || '—') + ')'"></p>
                                <p class="text-[9px] font-mono text-blue-400/50 font-bold" x-text="viewData.plate_number || 'No plate'"></p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3 pt-3 border-t border-[#1e1e1e]">
                            <div>
                                <span class="text-[7px] font-bold uppercase tracking-[0.15em] text-[#333] block mb-1">Driver</span>
                                <span class="text-[10px] font-bold text-[#888]" x-text="viewData.driver_name || 'Unassigned'"></span>
                            </div>
                            <div>
                                <span class="text-[7px] font-bold uppercase tracking-[0.15em] text-[#333] block mb-1">Status</span>
                                <div class="flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full"
                                          :class="viewData.vehicle_status === 'active' ? 'bg-emerald-400' : viewData.vehicle_status === 'maintenance' ? 'bg-amber-400 animate-pulse' : viewData.vehicle_status === 'inactive' ? 'bg-rose-400/70' : 'bg-[#333]'"></span>
                                    <span class="text-[10px] font-bold text-[#888] capitalize" x-text="viewData.vehicle_status || '—'"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Financials -->
                    <p class="text-[8px] font-bold uppercase tracking-[0.15em] text-[#333] mb-3">Financials</p>
                    <div class="p-4 rounded-xl bg-[#111] border border-[#1e1e1e] mb-5">
                        <span class="text-[7px] font-bold uppercase tracking-[0.15em] text-[#333] block mb-1">Maintenance Cost</span>
                        <span class="text-sm font-black font-mono text-amber-400" x-text="fmt(viewData.maintenance_cost)"></span>
                    </div>

                    <!-- Notes -->
                    <p class="text-[8px] font-bold uppercase tracking-[0.15em] text-[#333] mb-3">Notes</p>
                    <div class="p-3 rounded-xl bg-[#111] border border-[#1e1e1e] mb-4">
                        <p class="text-[10px] text-[#666] leading-relaxed whitespace-pre-wrap" x-text="viewData.notes || 'No notes provided.'"></p>
                    </div>

                    <!-- Timestamps -->
                    <div class="pt-4 border-t border-[#1e1e1e] flex items-center justify-between">
                        <span class="text-[7px] text-[#333] font-bold uppercase">Created <span class="font-mono text-[#444]" x-text="dateTimeStr(viewData.created_at)"></span></span>
                        <span class="text-[7px] text-[#333] font-bold uppercase">Updated <span class="font-mono text-[#444]" x-text="dateTimeStr(viewData.updated_at)"></span></span>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <!-- ══════════ ADD RECORD MODAL ══════════ -->
    <template x-teleport="body">
        <div x-show="showModal" x-cloak @keydown.escape.window="showModal = false"
            class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/80"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            style="display: none;">

            <div x-show="showModal" @click.stop
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                 class="relative w-full max-w-lg glass-panel rounded-[2rem] flex flex-col max-h-[90vh] overflow-hidden">

                <form method="POST" action="{{ route('maintenance-manager.fleet-inventory.store') }}" @submit="showModal = false" class="flex flex-col h-full">
                    @csrf

                    <!-- Header -->
                    <div class="flex items-center justify-between px-6 sm:px-8 pt-6 sm:pt-7 pb-5 border-b border-[#1e1e1e] shrink-0">
                        <div class="flex items-center gap-3.5">
                            <div class="w-10 h-10 rounded-xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center">
                                <i class="fa-solid fa-plus text-[11px] text-blue-400"></i>
                            </div>
                            <div>
                                <h3 class="text-sm sm:text-base font-black tracking-tight">Add Record</h3>
                                <p class="text-[9px] sm:text-[10px] text-[#555] mt-0.5">Add a maintenance cost record</p>
                            </div>
                        </div>
                        <button type="button" @click="showModal = false"
                            class="w-8 h-8 rounded-lg bg-[#1a1a1a] border border-[#2a2a2a] hover:bg-[#222] flex items-center justify-center text-[#555] hover:text-white transition-colors">
                            <i class="fa-solid fa-xmark text-xs"></i>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="flex-1 overflow-y-auto px-6 sm:px-8 py-5 sm:py-6 space-y-4 sm:space-y-5">
                        <div>
                            <label class="flex items-center gap-2 text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-[#555] mb-2">
                                <span class="w-[3px] h-3 rounded-sm bg-blue-500 shrink-0"></span>
                                Vehicle <span class="text-rose-400/60">*</span>
                            </label>
                            <select name="vehicle_id" class="form-input w-full rounded-xl px-4 py-2.5 text-[10px] sm:text-xs font-bold pr-10" required>
                                <option value="">Select a vehicle...</option>
                                @foreach($vehicles as $vehicle)
                                    <option value="{{ $vehicle->id }}">{{ $vehicle->brand }} {{ $vehicle->model }} ({{ $vehicle->year }}) — {{ $vehicle->plate_number }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="flex items-center gap-2 text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-[#555] mb-2">
                                <span class="w-[3px] h-3 rounded-sm bg-amber-500 shrink-0"></span>
                                Maintenance Cost <span class="text-rose-400/60">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-[#333] text-[10px] font-mono font-bold">₱</span>
                                <input type="number" name="maintenance_cost" placeholder="0.00" step="0.01" min="0"
                                    class="form-input w-full rounded-xl pl-8 pr-4 py-2.5 text-[10px] sm:text-xs font-mono placeholder:text-[#2a2a2a]" required>
                            </div>
                        </div>

                        <div>
                            <label class="flex items-center gap-2 text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-[#555] mb-2">
                                <span class="w-[3px] h-3 rounded-sm bg-[#333] shrink-0"></span>
                                Notes
                            </label>
                            <textarea name="notes" rows="4" placeholder="What was the maintenance for..."
                                class="form-input w-full rounded-xl px-4 py-2.5 text-[10px] sm:text-xs placeholder:text-[#2a2a2a] resize-none leading-relaxed"></textarea>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex items-center justify-end gap-2.5 px-6 sm:px-8 py-4 sm:py-5 border-t border-[#1e1e1e] shrink-0 bg-[#0a0a0a]/60">
                        <button type="button" @click="showModal = false"
                            class="px-5 py-2.5 rounded-xl bg-[#1a1a1a] border border-[#2a2a2a] text-[#888] text-[9px] sm:text-[10px] font-bold uppercase tracking-widest hover:bg-[#222] hover:text-white transition">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-6 py-2.5 rounded-xl bg-blue-600 text-white text-[9px] sm:text-[10px] font-bold uppercase tracking-widest hover:bg-blue-500 transition-all active:scale-[0.98] flex items-center gap-2">
                            <i class="fa-solid fa-check text-[9px]"></i>
                            <span>Add Record</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- ══════════ EDIT RECORD MODAL ══════════ -->
    <template x-teleport="body">
        <div x-show="showEditModal" x-cloak @keydown.escape.window="closeEditModal()"
            class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/80"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            style="display: none;">

            <form method="POST"
                  :action="`{{ route('maintenance-manager.fleet-inventory.update', 0) }}`.replace('/0', '/' + editForm.id)"
                  x-show="showEditModal"
                  @click.stop
                  x-transition:enter="transition ease-out duration-300"
                  x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                  x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                  x-transition:leave="transition ease-in duration-200"
                  x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                  x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                  class="relative w-full max-w-lg glass-panel rounded-[2rem] flex flex-col max-h-[90vh] overflow-hidden">

                @csrf
                @method('PATCH')

                <!-- Header -->
                <div class="flex items-center justify-between px-6 sm:px-8 pt-6 sm:pt-7 pb-5 border-b border-[#1e1e1e] shrink-0">
                    <div class="flex items-center gap-3.5">
                        <div class="w-10 h-10 rounded-xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center">
                            <i class="fa-solid fa-pen-to-square text-[11px] text-blue-400"></i>
                        </div>
                        <div>
                            <h3 class="text-sm sm:text-base font-black tracking-tight">Edit Record</h3>
                            <p class="text-[9px] sm:text-[10px] text-[#555] mt-0.5">Update inventory details</p>
                        </div>
                    </div>
                    <button type="button" @click="closeEditModal()"
                        class="w-8 h-8 rounded-lg bg-[#1a1a1a] border border-[#2a2a2a] hover:bg-[#222] flex items-center justify-center text-[#555] hover:text-white transition-colors">
                        <i class="fa-solid fa-xmark text-xs"></i>
                    </button>
                </div>

                <!-- Body -->
                <div class="flex-1 overflow-y-auto px-6 sm:px-8 py-5 sm:py-6 space-y-4 sm:space-y-5">
                    <div>
                        <label class="flex items-center gap-2 text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-[#555] mb-2">
                            <span class="w-[3px] h-3 rounded-sm bg-blue-500 shrink-0"></span>
                            Vehicle <span class="text-rose-400/60">*</span>
                        </label>
                        <select name="vehicle_id" x-model="editForm.vehicle_id"
                            class="form-input w-full rounded-xl px-4 py-2.5 text-[10px] sm:text-xs font-bold pr-10" required>
                            <option value="">Select a vehicle...</option>
                            @foreach($vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}">{{ $vehicle->brand }} {{ $vehicle->model }} ({{ $vehicle->year }}) — {{ $vehicle->plate_number }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="flex items-center gap-2 text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-[#555] mb-2">
                            <span class="w-[3px] h-3 rounded-sm bg-amber-500 shrink-0"></span>
                            Maintenance Cost <span class="text-rose-400/60">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-[#333] text-[10px] font-mono font-bold">₱</span>
                            <input type="number" name="maintenance_cost" x-model="editForm.maintenance_cost"
                                step="0.01" min="0"
                                class="form-input w-full rounded-xl pl-8 pr-4 py-2.5 text-[10px] sm:text-xs font-mono placeholder:text-[#2a2a2a]" required>
                        </div>
                    </div>

                    <div>
                        <label class="flex items-center gap-2 text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-[#555] mb-2">
                            <span class="w-[3px] h-3 rounded-sm bg-[#333] shrink-0"></span>
                            Notes
                        </label>
                        <textarea name="notes" x-model="editForm.notes" rows="4"
                            class="form-input w-full rounded-xl px-4 py-2.5 text-[10px] sm:text-xs placeholder:text-[#2a2a2a] resize-none leading-relaxed"></textarea>
                    </div>
                </div>

                <!-- Footer -->
                <div class="flex items-center justify-end gap-2.5 px-6 sm:px-8 py-4 sm:py-5 border-t border-[#1e1e1e] shrink-0 bg-[#0a0a0a]/60">
                    <button type="button" @click="closeEditModal()"
                        class="px-5 py-2.5 rounded-xl bg-[#1a1a1a] border border-[#2a2a2a] text-[#888] text-[9px] sm:text-[10px] font-bold uppercase tracking-widest hover:bg-[#222] hover:text-white transition">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-6 py-2.5 rounded-xl bg-blue-600 text-white text-[9px] sm:text-[10px] font-bold uppercase tracking-widest hover:bg-blue-500 transition-all active:scale-[0.98] flex items-center gap-2">
                        <i class="fa-solid fa-check text-[9px]"></i>
                        <span>Update Record</span>
                    </button>
                </div>
            </form>
        </div>
    </template>

    <script>
    function fleetInventoryApp() {
        return {
            open: true,
            showLogoutModal: false,
            showModal: false,
            showEditModal: false,
            showViewModal: false,
            search: '',
            inventories: [],
            editForm: {},
            viewData: {},

            init() {
                this.inventories = {{ Js::from($inventories) }};
            },

            openAddModal() {
                this.showModal = true;
            },

            openViewModal(inv) {
                this.viewData = { ...inv };
                this.showViewModal = true;
            },

            resetEditForm() {
                this.editForm = {
                    id: null,
                    vehicle_id: '',
                    maintenance_cost: '',
                    notes: ''
                };
            },
            openEditModal(inv) {
                this.editForm = { ...inv };
                this.showEditModal = true;
            },
            closeEditModal() {
                this.showEditModal = false;
                this.resetEditForm();
            },

            get filtered() {
                if (!this.search) return this.inventories;
                const q = this.search.toLowerCase();
                return this.inventories.filter(inv => {
                    return (inv.vehicle_name || '').toLowerCase().includes(q) ||
                        (inv.plate_number || '').toLowerCase().includes(q) ||
                        (inv.driver_name || '').toLowerCase().includes(q) ||
                        (inv.notes || '').toLowerCase().includes(q) ||
                        (inv.maintenance_cost || '').toString().includes(q);
                });
            },

            fmt(v) {
                return '₱' + Number(v || 0).toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            },

            dateTimeStr(d) {
                if (!d) return '—';
                return new Date(d).toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric'
                }) + ' ' + new Date(d).toLocaleTimeString('en-US', {
                    hour: '2-digit',
                    minute: '2-digit'
                });
            }
        }
    }
    </script>
</body>
</html>
