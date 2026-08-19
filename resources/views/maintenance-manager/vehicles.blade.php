@use('Illuminate\Support\Js')

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

<body class="antialiased text-white" x-data="vehicleManagementApp()" x-init="init()">

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
                        <span class="font-mono text-[9px] text-[#444]">Vehicles</span>
                    </div>
                </div>
            </div>

            <!-- ── Page Header (desktop) ── -->
            <div class="hidden lg:flex items-end justify-between mb-8">
                <div>
                    <div class="flex items-center gap-2 mb-1.5">
                        <span class="text-[9px] font-bold uppercase tracking-[0.15em] text-[#444]">Fleet Registry</span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-black tracking-tight">Vehicle <span class="text-blue-500">Management</span></h1>
                    <p class="text-[11px] text-[#555] mt-1 flex items-center gap-2">
                        <i class="fa-solid fa-wrench text-[9px] text-amber-400"></i>
                        Track and manage all registered vehicles
                    </p>
                </div>
                <button @click="openAddModal()"
                    class="flex items-center gap-2 px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-[10px] font-bold uppercase tracking-widest shadow-lg shadow-blue-900/30 transition-all active:scale-[0.97]">
                    <i class="fa-solid fa-plus text-[9px]"></i>
                    <span>Add Vehicle</span>
                </button>
            </div>

            <!-- ── Mobile Add Button ── -->
            <div class="lg:hidden mb-5">
                <button @click="openAddModal()"
                    class="w-full flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-[10px] font-bold uppercase tracking-widest transition-all active:scale-[0.98]">
                    <i class="fa-solid fa-plus text-[9px]"></i>
                    <span>Add Vehicle</span>
                </button>
            </div>

            <!-- ══════════ STAT CARDS ══════════ -->
            <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-5 gap-3 sm:gap-4 mb-5 sm:mb-6">

                <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-blue-500">
                    <div class="flex items-center gap-2 mb-2 sm:mb-3">
                        <div class="w-6 h-6 rounded-md bg-blue-500/10 flex items-center justify-center">
                            <i class="fa-solid fa-car text-[8px] text-blue-400"></i>
                        </div>
                        <span class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Total</span>
                    </div>
                    <div class="flex items-baseline gap-1.5">
                        <span class="text-xl sm:text-2xl font-black tracking-tight">{{ $totalVehicles }}</span>
                        <span class="text-xs font-bold text-[#555]">{{ $totalVehicles === 1 ? 'unit' : 'units' }}</span>
                    </div>
                </div>

                <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-emerald-500">
                    <div class="flex items-center gap-2 mb-2 sm:mb-3">
                        <div class="w-6 h-6 rounded-md bg-emerald-500/10 flex items-center justify-center">
                            <i class="fa-solid fa-circle-check text-[8px] text-emerald-400"></i>
                        </div>
                        <span class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Active</span>
                    </div>
                    <div class="flex items-baseline gap-1.5">
                        <span class="text-xl sm:text-2xl font-black tracking-tight text-emerald-400">{{ $activeCount }}</span>
                    </div>
                </div>

                <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-amber-500">
                    <div class="flex items-center gap-2 mb-2 sm:mb-3">
                        <div class="w-6 h-6 rounded-md bg-amber-500/10 flex items-center justify-center">
                            <i class="fa-solid fa-wrench text-[8px] text-amber-400"></i>
                        </div>
                        <span class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Maintenance</span>
                    </div>
                    <div class="flex items-baseline gap-1.5">
                        <span class="text-xl sm:text-2xl font-black tracking-tight {{ $maintenanceCount > 0 ? 'text-amber-400' : 'text-[#555]' }}">{{ $maintenanceCount }}</span>
                    </div>
                </div>

                <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-rose-500">
                    <div class="flex items-center gap-2 mb-2 sm:mb-3">
                        <div class="w-6 h-6 rounded-md bg-rose-500/10 flex items-center justify-center">
                            <i class="fa-solid fa-circle-xmark text-[8px] text-rose-400"></i>
                        </div>
                        <span class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Inactive</span>
                    </div>
                    <div class="flex items-baseline gap-1.5">
                        <span class="text-xl sm:text-2xl font-black tracking-tight {{ $inactiveCount > 0 ? 'text-rose-400' : 'text-[#555]' }}">{{ $inactiveCount }}</span>
                    </div>
                </div>

                <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-purple-500">
                    <div class="flex items-center gap-2 mb-2 sm:mb-3">
                        <div class="w-6 h-6 rounded-md bg-purple-500/10 flex items-center justify-center">
                            <i class="fa-solid fa-user-check text-[8px] text-purple-400"></i>
                        </div>
                        <span class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Assigned</span>
                    </div>
                    <div class="flex items-baseline gap-1.5">
                        <span class="text-xl sm:text-2xl font-black tracking-tight text-purple-400">{{ $assignedCount }}</span>
                        <span class="text-xs font-bold text-[#555]">drivers</span>
                    </div>
                </div>

            </div>

            <!-- ══════════ SEARCH ══════════ -->
            <div class="mb-5">
                <div class="relative max-w-sm">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-[#333] text-[10px]"></i>
                    <input type="text" x-model="search"
                        placeholder="Search brand, model, plate number..."
                        class="form-input w-full pl-10 pr-4 py-2.5 rounded-xl text-[10px] font-bold placeholder:text-[#2a2a2a]">
                </div>
            </div>

            <!-- ══════════ TABLE ══════════ -->
            <div class="glass-card rounded-[1.25rem] sm:rounded-[1.5rem] overflow-hidden">
                <div class="p-4 sm:p-6 pb-0">
                    <div class="flex items-center justify-between mb-4 sm:mb-5">
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-lg bg-[#111] border border-[#1e1e1e] flex items-center justify-center">
                                <i class="fa-solid fa-car text-[9px] text-blue-400"></i>
                            </div>
                            <span class="text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-[#555]">Vehicle Registry</span>
                        </div>
                        <span class="text-[7px] sm:text-[8px] font-bold text-[#333] uppercase tracking-widest" x-text="filtered.length + ' of ' + vehicles.length"></span>
                    </div>
                </div>
                <div class="overflow-x-auto -mx-2 px-2 pb-2">
                    <table class="w-full text-left min-w-[700px]">
                        <thead>
                            <tr class="text-[7px] sm:text-[8px] uppercase tracking-[0.15em] text-[#444] border-b border-[#1e1e1e]">
                                <th class="px-4 sm:px-6 py-2.5 font-bold sticky left-0 bg-[#161616] z-10 min-w-[220px]">Vehicle</th>
                                <th class="px-4 sm:px-6 py-2.5 font-bold">Plate Number</th>
                                <th class="px-4 sm:px-6 py-2.5 font-bold">Driver</th>
                                <th class="px-4 sm:px-6 py-2.5 font-bold">Location</th>
                                <th class="px-4 sm:px-6 py-2.5 font-bold text-center">Status</th>
                                <th class="px-4 sm:px-6 py-2.5 font-bold text-center w-24">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#1a1a1a]">
                            <template x-for="(v, index) in filtered" :key="v.id">
                                <tr class="table-row group">
                                    <td class="px-4 sm:px-6 py-3.5 sticky left-0 z-10 bg-[#161616] group-hover:bg-[#1a1a1a] transition-colors">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-lg bg-blue-500/10 border border-blue-500/15 flex items-center justify-center shrink-0">
                                                <i class="fa-solid fa-van-shuttle text-[8px] text-blue-400"></i>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-[10px] sm:text-[11px] font-bold text-[#ccc] truncate max-w-[160px]" x-text="v.brand + ' ' + v.model"></p>
                                                <span class="text-[8px] text-[#444] font-bold" x-text="v.year"></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 sm:px-6 py-3.5">
                                        <span class="text-[8px] sm:text-[9px] font-mono font-bold text-blue-400/60 bg-blue-500/10 px-2 py-0.5 rounded-md border border-blue-500/15" x-text="v.plate_number"></span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-3.5">
                                        <div class="flex items-center gap-2">
                                            <div class="w-6 h-6 rounded-full bg-[#111] border border-[#1e1e1e] flex items-center justify-center shrink-0">
                                                <i class="fa-solid fa-user text-[#333] text-[7px]"></i>
                                            </div>
                                            <span class="text-[9px] sm:text-[10px] font-bold text-[#666] truncate max-w-[120px]" x-text="v.driver_name || 'Unassigned'"></span>
                                        </div>
                                    </td>
                                    <td class="px-4 sm:px-6 py-3.5">
                                        <span class="text-[9px] sm:text-[10px] text-[#555] font-bold" x-text="v.location || '—'"></span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-3.5 text-center">
                                        <span class="inline-flex items-center gap-1 text-[7px] sm:text-[8px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full border"
                                              :class="v.status === 'active' ? 'text-emerald-400 bg-emerald-500/10 border-emerald-500/15' :
                                                        v.status === 'maintenance' ? 'text-amber-400 bg-amber-500/10 border-amber-500/15' :
                                                        v.status === 'inactive' ? 'text-rose-400/70 bg-rose-500/[0.06] border-rose-500/10' :
                                                        v.status === 'disposed' ? 'text-[#333] bg-[#111] border-[#1e1e1e]' :
                                                        'text-[#333] bg-[#111] border-[#1e1e1e]'">
                                            <span class="w-1.5 h-1.5 rounded-full shrink-0"
                                                  :class="v.status === 'active' ? 'bg-emerald-400' :
                                                            v.status === 'maintenance' ? 'bg-amber-400 animate-pulse' :
                                                            v.status === 'inactive' ? 'bg-rose-400/70' :
                                                            'bg-[#333]'"></span>
                                            <span x-text="v.status === 'active' ? 'Active' :
                                                         v.status === 'maintenance' ? 'Maint.' :
                                                         v.status === 'inactive' ? 'Inactive' :
                                                         v.status === 'disposed' ? 'Disposed' :
                                                         (v.status || '—')"></span>
                                        </span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-3.5">
                                        <div class="opacity-30 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-1">
                                            <button @click="openViewModal(v)"
                                                class="w-7 h-7 rounded-lg flex items-center justify-center text-[#555] hover:text-white hover:bg-[#1a1a1a] transition-all"
                                                title="View Details">
                                                <i class="fa-solid fa-eye text-[9px]"></i>
                                            </button>
                                            <button @click="openEditModal(v)"
                                                class="w-7 h-7 rounded-lg flex items-center justify-center text-[#555] hover:text-blue-400 hover:bg-blue-500/10 transition-all"
                                                title="Edit">
                                                <i class="fa-solid fa-pen-to-square text-[9px]"></i>
                                            </button>
                                            <form method="POST" :action="`{{ route('vehicles.destroy', '__ID__') }}`.replace('__ID__', v.id)" class="inline-flex">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" onclick="return confirm('Remove this vehicle?')"
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
                            <i class="fa-solid fa-car text-sm text-[#333]"></i>
                        </div>
                        <p class="text-[10px] sm:text-[11px] text-[#444] font-medium">No vehicles found</p>
                        <p class="text-[8px] text-[#333] mt-0.5">Click "Add Vehicle" to get started</p>
                    </div>
                </div>

                <!-- Table Footer -->
                <div class="px-4 sm:px-6 py-3 border-t border-[#1e1e1e] bg-[#111] flex flex-col sm:flex-row justify-between gap-2">
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-1.5">
                            <div class="w-2 h-2 rounded-sm bg-emerald-500"></div>
                            <span class="text-[7px] font-bold text-[#444] uppercase">Active {{ $activeCount }}</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <div class="w-2 h-2 rounded-sm bg-amber-500"></div>
                            <span class="text-[7px] font-bold text-[#444] uppercase">Maint. {{ $maintenanceCount }}</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <div class="w-2 h-2 rounded-sm bg-rose-500"></div>
                            <span class="text-[7px] font-bold text-[#444] uppercase">Inactive {{ $inactiveCount }}</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <div class="w-2 h-2 rounded-sm bg-[#333]"></div>
                            <span class="text-[7px] font-bold text-[#444] uppercase">Disposed {{ $disposedCount }}</span>
                        </div>
                    </div>
                    <span class="text-[7px] text-[#333] font-bold uppercase tracking-widest">Total: {{ $totalVehicles }} vehicles</span>
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

    <!-- ══════════ VIEW VEHICLE MODAL ══════════ -->
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
                            <h3 class="text-sm sm:text-base font-black tracking-tight">Vehicle Details</h3>
                            <p class="text-[9px] sm:text-[10px] text-[#555] mt-0.5 truncate max-w-[200px]" x-text="viewData.brand + ' ' + viewData.model + ' (' + viewData.year + ')'"></p>
                        </div>
                    </div>
                    <button type="button" @click="showViewModal = false"
                        class="w-8 h-8 rounded-lg bg-[#1a1a1a] border border-[#2a2a2a] hover:bg-[#222] flex items-center justify-center text-[#555] hover:text-white transition-colors">
                        <i class="fa-solid fa-xmark text-xs"></i>
                    </button>
                </div>

                <!-- Body -->
                <div class="flex-1 overflow-y-auto px-6 sm:px-8 py-5 sm:py-6">

                    <!-- Status + Location -->
                    <div class="flex items-center gap-2.5 mb-5">
                        <span class="inline-flex items-center gap-1 text-[8px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full border"
                              :class="viewData.status === 'active' ? 'text-emerald-400 bg-emerald-500/10 border-emerald-500/15' :
                                        viewData.status === 'maintenance' ? 'text-amber-400 bg-amber-500/10 border-amber-500/15' :
                                        viewData.status === 'inactive' ? 'text-rose-400/70 bg-rose-500/[0.06] border-rose-500/10' :
                                        viewData.status === 'disposed' ? 'text-[#333] bg-[#111] border-[#1e1e1e]' :
                                        'text-[#333] bg-[#111] border-[#1e1e1e]'">
                            <span class="w-1.5 h-1.5 rounded-full"
                                  :class="viewData.status === 'active' ? 'bg-emerald-400' :
                                          viewData.status === 'maintenance' ? 'bg-amber-400 animate-pulse' :
                                          viewData.status === 'inactive' ? 'bg-rose-400/70' : 'bg-[#333]'"></span>
                            <span x-text="viewData.status || '—'" class="capitalize"></span>
                        </span>
                        <span class="text-[#222]">•</span>
                        <span class="text-[9px] text-[#555] font-bold" x-text="viewData.location || 'No location'"></span>
                    </div>

                    <!-- Identification -->
                    <p class="text-[8px] font-bold uppercase tracking-[0.15em] text-[#333] mb-3">Identification</p>
                    <div class="p-4 rounded-xl bg-[#111] border border-[#1e1e1e] mb-5">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <span class="text-[7px] font-bold uppercase tracking-[0.15em] text-[#333] block mb-1">Plate Number</span>
                                <span class="text-[10px] font-bold font-mono text-blue-400/70" x-text="viewData.plate_number || '—'"></span>
                            </div>
                            <div>
                                <span class="text-[7px] font-bold uppercase tracking-[0.15em] text-[#333] block mb-1">VIN</span>
                                <span class="text-[10px] font-bold font-mono text-[#666] truncate block" x-text="viewData.vin || '—'"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Vehicle Info -->
                    <p class="text-[8px] font-bold uppercase tracking-[0.15em] text-[#333] mb-3">Vehicle Info</p>
                    <div class="p-4 rounded-xl bg-[#111] border border-[#1e1e1e] mb-5">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <span class="text-[7px] font-bold uppercase tracking-[0.15em] text-[#333] block mb-1">Brand</span>
                                <span class="text-[10px] font-bold text-[#888]" x-text="viewData.brand || '—'"></span>
                            </div>
                            <div>
                                <span class="text-[7px] font-bold uppercase tracking-[0.15em] text-[#333] block mb-1">Model</span>
                                <span class="text-[10px] font-bold text-[#888]" x-text="viewData.model || '—'"></span>
                            </div>
                            <div>
                                <span class="text-[7px] font-bold uppercase tracking-[0.15em] text-[#333] block mb-1">Year</span>
                                <span class="text-[10px] font-bold text-[#888] font-mono" x-text="viewData.year || '—'"></span>
                            </div>
                            <div>
                                <span class="text-[7px] font-bold uppercase tracking-[0.15em] text-[#333] block mb-1">Fuel Type</span>
                                <span class="text-[10px] font-bold text-[#888]" x-text="viewData.fuel_type || '—'"></span>
                            </div>
                            <div>
                                <span class="text-[7px] font-bold uppercase tracking-[0.15em] text-[#333] block mb-1">Tank Capacity</span>
                                <span class="text-[10px] font-bold text-[#888] font-mono" x-text="viewData.tank_capacity ? viewData.tank_capacity + ' L' : '—'"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Assignment -->
                    <p class="text-[8px] font-bold uppercase tracking-[0.15em] text-[#333] mb-3">Assignment</p>
                    <div class="p-3.5 rounded-xl bg-[#111] border border-[#1e1e1e] mb-5">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-purple-500/10 border border-purple-500/15 flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-user text-[9px] text-purple-400"></i>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-[#ccc]" x-text="viewData.driver_name || 'Unassigned'"></p>
                                <p class="text-[8px] text-[#444] font-bold uppercase">Assigned Driver</p>
                            </div>
                        </div>
                    </div>

                    <!-- Important Dates -->
                    <p class="text-[8px] font-bold uppercase tracking-[0.15em] text-[#333] mb-3">Important Dates</p>
                    <div class="space-y-2 mb-4">
                        <div class="flex items-center justify-between p-3 rounded-xl bg-[#111] border border-[#1e1e1e]">
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-calendar-plus text-[#333] text-[9px]"></i>
                                <span class="text-[9px] font-bold text-[#555] uppercase">Acquisition</span>
                            </div>
                            <span class="text-[10px] font-bold font-mono text-[#888]" x-text="dateStr(viewData.acquistion_date)"></span>
                        </div>
                        <div class="flex items-center justify-between p-3 rounded-xl bg-[#111] border border-[#1e1e1e]">
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-calendar-xmark text-[#333] text-[9px]"></i>
                                <span class="text-[9px] font-bold text-[#555] uppercase">Disposal</span>
                            </div>
                            <span class="text-[10px] font-bold font-mono" :class="isOverdue(viewData.exp_disposal_date) ? 'text-rose-400' : 'text-[#888]'" x-text="dateStr(viewData.exp_disposal_date)"></span>
                        </div>
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

    <!-- ══════════ ADD VEHICLE MODAL ══════════ -->
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
                 class="relative w-full max-w-xl glass-panel rounded-[2rem] flex flex-col max-h-[90vh] overflow-hidden">

                <form method="POST" action="{{ route('vehicles.store') }}" @submit="showModal = false" class="flex flex-col h-full">
                    @csrf

                    <!-- Header -->
                    <div class="flex items-center justify-between px-6 sm:px-8 pt-6 sm:pt-7 pb-5 border-b border-[#1e1e1e] shrink-0">
                        <div class="flex items-center gap-3.5">
                            <div class="w-10 h-10 rounded-xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center">
                                <i class="fa-solid fa-plus text-[11px] text-blue-400"></i>
                            </div>
                            <div>
                                <h3 class="text-sm sm:text-base font-black tracking-tight">Add Vehicle</h3>
                                <p class="text-[9px] sm:text-[10px] text-[#555] mt-0.5">Register a new vehicle to the fleet</p>
                            </div>
                        </div>
                        <button type="button" @click="showModal = false"
                            class="w-8 h-8 rounded-lg bg-[#1a1a1a] border border-[#2a2a2a] hover:bg-[#222] flex items-center justify-center text-[#555] hover:text-white transition-colors">
                            <i class="fa-solid fa-xmark text-xs"></i>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="flex-1 overflow-y-auto px-6 sm:px-8 py-5 sm:py-6 space-y-4 sm:space-y-5">

                        <!-- Vehicle Information -->
                        <div>
                            <div class="flex items-center gap-2 mb-3">
                                <span class="w-[3px] h-3 rounded-sm bg-blue-500"></span>
                                <span class="text-[8px] font-bold uppercase tracking-[0.15em] text-[#555]">Vehicle Information</span>
                            </div>
                            <div class="grid grid-cols-3 gap-3">
                                <div>
                                    <label class="text-[7px] font-bold uppercase tracking-[0.15em] text-[#444] block mb-1.5">Year <span class="text-rose-400/60">*</span></label>
                                    <input type="number" name="year" placeholder="2024" min="1990" max="2030"
                                        class="form-input w-full rounded-xl px-3.5 py-2.5 text-[10px] font-mono font-bold placeholder:text-[#2a2a2a]" required>
                                </div>
                                <div>
                                    <label class="text-[7px] font-bold uppercase tracking-[0.15em] text-[#444] block mb-1.5">Brand <span class="text-rose-400/60">*</span></label>
                                    <input type="text" name="brand" placeholder="Toyota"
                                        class="form-input w-full rounded-xl px-3.5 py-2.5 text-[10px] font-bold placeholder:text-[#2a2a2a]" required>
                                </div>
                                <div>
                                    <label class="text-[7px] font-bold uppercase tracking-[0.15em] text-[#444] block mb-1.5">Model <span class="text-rose-400/60">*</span></label>
                                    <input type="text" name="model" placeholder="HiAce"
                                        class="form-input w-full rounded-xl px-3.5 py-2.5 text-[10px] font-bold placeholder:text-[#2a2a2a]" required>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3 mt-3">
                                <div>
                                    <label class="text-[7px] font-bold uppercase tracking-[0.15em] text-[#444] block mb-1.5">Plate Number <span class="text-rose-400/60">*</span></label>
                                    <input type="text" name="plate_number" placeholder="ABC-1234"
                                        class="form-input w-full rounded-xl px-3.5 py-2.5 text-[10px] font-mono font-bold placeholder:text-[#2a2a2a]" required>
                                </div>
                                <div>
                                    <label class="text-[7px] font-bold uppercase tracking-[0.15em] text-[#444] block mb-1.5">VIN</label>
                                    <input type="text" name="vin" placeholder="Vehicle Identification Number"
                                        class="form-input w-full rounded-xl px-3.5 py-2.5 text-[10px] font-mono font-bold placeholder:text-[#2a2a2a]">
                                </div>
                            </div>
                        </div>

                        <!-- Specifications & Assignment -->
                        <div>
                            <div class="flex items-center gap-2 mb-3">
                                <span class="w-[3px] h-3 rounded-sm bg-emerald-500"></span>
                                <span class="text-[8px] font-bold uppercase tracking-[0.15em] text-[#555]">Specifications & Assignment</span>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="text-[7px] font-bold uppercase tracking-[0.15em] text-[#444] block mb-1.5">Fuel Type</label>
                                    <select name="fuel_type" class="form-input w-full rounded-xl px-3.5 py-2.5 text-[10px] font-bold pr-10">
                                        <option value="">Select fuel type</option>
                                        <option value="Diesel">Diesel</option>
                                        <option value="Gasoline">Gasoline</option>
                                        <option value="Electric">Electric</option>
                                        <option value="Hybrid">Hybrid</option>
                                        <option value="LPG">LPG</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="text-[7px] font-bold uppercase tracking-[0.15em] text-[#444] block mb-1.5">Tank Capacity (L)</label>
                                    <input type="text" name="tank_capacity" placeholder="e.g. 60"
                                        class="form-input w-full rounded-xl px-3.5 py-2.5 text-[10px] font-mono font-bold placeholder:text-[#2a2a2a]">
                                </div>
                                <div>
                                    <label class="text-[7px] font-bold uppercase tracking-[0.15em] text-[#444] block mb-1.5">Driver</label>
                                    <select name="driver_id" class="form-input w-full rounded-xl px-3.5 py-2.5 text-[10px] font-bold pr-10">
                                        <option value="">Unassigned</option>
                                        @foreach($drivers as $driver)
                                            <option value="{{ $driver->id }}">{{ $driver->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="text-[7px] font-bold uppercase tracking-[0.15em] text-[#444] block mb-1.5">Location</label>
                                    <input type="text" name="location" placeholder="e.g. Main Garage"
                                        class="form-input w-full rounded-xl px-3.5 py-2.5 text-[10px] font-bold placeholder:text-[#2a2a2a]">
                                </div>
                            </div>
                        </div>

                        <!-- Dates & Status -->
                        <div>
                            <div class="flex items-center gap-2 mb-3">
                                <span class="w-[3px] h-3 rounded-sm bg-purple-500"></span>
                                <span class="text-[8px] font-bold uppercase tracking-[0.15em] text-[#555]">Dates & Status</span>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="text-[7px] font-bold uppercase tracking-[0.15em] text-[#444] block mb-1.5">Acquisition Date <span class="text-rose-400/60">*</span></label>
                                    <input type="date" name="acquistion_date"
                                        class="form-input w-full rounded-xl px-3.5 py-2.5 text-[10px] font-mono font-bold [color-scheme:dark]" required>
                                </div>
                                <div>
                                    <label class="text-[7px] font-bold uppercase tracking-[0.15em] text-[#444] block mb-1.5">Status</label>
                                    <select name="status" class="form-input w-full rounded-xl px-3.5 py-2.5 text-[10px] font-bold pr-10">
                                        <option value="active">Active</option>
                                        <option value="maintenance">Maintenance</option>
                                        <option value="inactive">Inactive</option>
                                        <option value="disposed">Disposed</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="text-[7px] font-bold uppercase tracking-[0.15em] text-[#444] block mb-1.5">Expected Disposal</label>
                                    <input type="date" name="exp_disposal_date"
                                        class="form-input w-full rounded-xl px-3.5 py-2.5 text-[10px] font-mono font-bold [color-scheme:dark]">
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Footer -->
                    <div class="px-6 sm:px-8 py-4 border-t border-[#1e1e1e] bg-[#0d0d0d] shrink-0">
                        <div class="flex gap-2.5">
                            <button type="button" @click="showModal = false"
                                class="flex-1 py-3 rounded-xl bg-[#1a1a1a] border border-[#2a2a2a] text-white text-[10px] font-bold uppercase tracking-widest hover:bg-[#222] transition">
                                Cancel
                            </button>
                            <button type="submit"
                                class="flex-1 py-3 rounded-xl bg-blue-600 text-white text-[10px] font-bold uppercase tracking-widest hover:bg-blue-500 transition active:scale-[0.98] shadow-lg shadow-blue-900/30">
                                Add Vehicle
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- ══════════ EDIT VEHICLE MODAL ══════════ -->
    <template x-teleport="body">
        <div x-show="showEditModal" x-cloak @keydown.escape.window="showEditModal = false"
            class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/80"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            style="display: none;">

            <div x-show="showEditModal" @click.stop
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                 class="relative w-full max-w-xl glass-panel rounded-[2rem] flex flex-col max-h-[90vh] overflow-hidden">

                <form method="POST" :action="`{{ route('vehicles.update', '__ID__') }}`.replace('__ID__', editData.id)" @submit="showEditModal = false" class="flex flex-col h-full">
                    @csrf
                    @method('PUT')

                    <!-- Header -->
                    <div class="flex items-center justify-between px-6 sm:px-8 pt-6 sm:pt-7 pb-5 border-b border-[#1e1e1e] shrink-0">
                        <div class="flex items-center gap-3.5">
                            <div class="w-10 h-10 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center">
                                <i class="fa-solid fa-pen-to-square text-[11px] text-amber-400"></i>
                            </div>
                            <div>
                                <h3 class="text-sm sm:text-base font-black tracking-tight">Edit Vehicle</h3>
                                <p class="text-[9px] sm:text-[10px] text-[#555] mt-0.5 truncate max-w-[200px]" x-text="editData.brand + ' ' + editData.model + ' (' + editData.year + ')'"></p>
                            </div>
                        </div>
                        <button type="button" @click="showEditModal = false"
                            class="w-8 h-8 rounded-lg bg-[#1a1a1a] border border-[#2a2a2a] hover:bg-[#222] flex items-center justify-center text-[#555] hover:text-white transition-colors">
                            <i class="fa-solid fa-xmark text-xs"></i>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="flex-1 overflow-y-auto px-6 sm:px-8 py-5 sm:py-6 space-y-4 sm:space-y-5">

                        <!-- Vehicle Information -->
                        <div>
                            <div class="flex items-center gap-2 mb-3">
                                <span class="w-[3px] h-3 rounded-sm bg-blue-500"></span>
                                <span class="text-[8px] font-bold uppercase tracking-[0.15em] text-[#555]">Vehicle Information</span>
                            </div>
                            <div class="grid grid-cols-3 gap-3">
                                <div>
                                    <label class="text-[7px] font-bold uppercase tracking-[0.15em] text-[#444] block mb-1.5">Year <span class="text-rose-400/60">*</span></label>
                                    <input type="number" name="year" x-model.number="editData.year" min="1990" max="2030"
                                        class="form-input w-full rounded-xl px-3.5 py-2.5 text-[10px] font-mono font-bold placeholder:text-[#2a2a2a]" required>
                                </div>
                                <div>
                                    <label class="text-[7px] font-bold uppercase tracking-[0.15em] text-[#444] block mb-1.5">Brand <span class="text-rose-400/60">*</span></label>
                                    <input type="text" name="brand" x-model="editData.brand"
                                        class="form-input w-full rounded-xl px-3.5 py-2.5 text-[10px] font-bold placeholder:text-[#2a2a2a]" required>
                                </div>
                                <div>
                                    <label class="text-[7px] font-bold uppercase tracking-[0.15em] text-[#444] block mb-1.5">Model <span class="text-rose-400/60">*</span></label>
                                    <input type="text" name="model" x-model="editData.model"
                                        class="form-input w-full rounded-xl px-3.5 py-2.5 text-[10px] font-bold placeholder:text-[#2a2a2a]" required>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3 mt-3">
                                <div>
                                    <label class="text-[7px] font-bold uppercase tracking-[0.15em] text-[#444] block mb-1.5">Plate Number <span class="text-rose-400/60">*</span></label>
                                    <input type="text" name="plate_number" x-model="editData.plate_number"
                                        class="form-input w-full rounded-xl px-3.5 py-2.5 text-[10px] font-mono font-bold placeholder:text-[#2a2a2a]" required>
                                </div>
                                <div>
                                    <label class="text-[7px] font-bold uppercase tracking-[0.15em] text-[#444] block mb-1.5">VIN</label>
                                    <input type="text" name="vin" x-model="editData.vin"
                                        class="form-input w-full rounded-xl px-3.5 py-2.5 text-[10px] font-mono font-bold placeholder:text-[#2a2a2a]">
                                </div>
                            </div>
                        </div>

                        <!-- Specifications & Assignment -->
                        <div>
                            <div class="flex items-center gap-2 mb-3">
                                <span class="w-[3px] h-3 rounded-sm bg-emerald-500"></span>
                                <span class="text-[8px] font-bold uppercase tracking-[0.15em] text-[#555]">Specifications & Assignment</span>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="text-[7px] font-bold uppercase tracking-[0.15em] text-[#444] block mb-1.5">Fuel Type</label>
                                    <select name="fuel_type" x-model="editData.fuel_type" class="form-input w-full rounded-xl px-3.5 py-2.5 text-[10px] font-bold pr-10">
                                        <option value="">Select fuel type</option>
                                        <option value="Diesel">Diesel</option>
                                        <option value="Gasoline">Gasoline</option>
                                        <option value="Electric">Electric</option>
                                        <option value="Hybrid">Hybrid</option>
                                        <option value="LPG">LPG</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="text-[7px] font-bold uppercase tracking-[0.15em] text-[#444] block mb-1.5">Tank Capacity (L)</label>
                                    <input type="text" name="tank_capacity" x-model="editData.tank_capacity"
                                        class="form-input w-full rounded-xl px-3.5 py-2.5 text-[10px] font-mono font-bold placeholder:text-[#2a2a2a]">
                                </div>
                                <div>
                                    <label class="text-[7px] font-bold uppercase tracking-[0.15em] text-[#444] block mb-1.5">Driver</label>
                                    <select name="driver_id" x-model="editData.driver_id" class="form-input w-full rounded-xl px-3.5 py-2.5 text-[10px] font-bold pr-10">
                                        <option value="">Unassigned</option>
                                        @foreach($drivers as $driver)
                                            <option value="{{ $driver->id }}">{{ $driver->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="text-[7px] font-bold uppercase tracking-[0.15em] text-[#444] block mb-1.5">Location</label>
                                    <input type="text" name="location" x-model="editData.location"
                                        class="form-input w-full rounded-xl px-3.5 py-2.5 text-[10px] font-bold placeholder:text-[#2a2a2a]">
                                </div>
                            </div>
                        </div>

                        <!-- Dates & Status -->
                        <div>
                            <div class="flex items-center gap-2 mb-3">
                                <span class="w-[3px] h-3 rounded-sm bg-purple-500"></span>
                                <span class="text-[8px] font-bold uppercase tracking-[0.15em] text-[#555]">Dates & Status</span>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="text-[7px] font-bold uppercase tracking-[0.15em] text-[#444] block mb-1.5">Acquisition Date <span class="text-rose-400/60">*</span></label>
                                    <input type="date" name="acquistion_date" x-model="editData.acquistion_date"
                                        class="form-input w-full rounded-xl px-3.5 py-2.5 text-[10px] font-mono font-bold [color-scheme:dark]" required>
                                </div>
                                <div>
                                    <label class="text-[7px] font-bold uppercase tracking-[0.15em] text-[#444] block mb-1.5">Status</label>
                                    <select name="status" x-model="editData.status" class="form-input w-full rounded-xl px-3.5 py-2.5 text-[10px] font-bold pr-10">
                                        <option value="active">Active</option>
                                        <option value="maintenance">Maintenance</option>
                                        <option value="inactive">Inactive</option>
                                        <option value="disposed">Disposed</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="text-[7px] font-bold uppercase tracking-[0.15em] text-[#444] block mb-1.5">Expected Disposal</label>
                                    <input type="date" name="exp_disposal_date" x-model="editData.exp_disposal_date"
                                        class="form-input w-full rounded-xl px-3.5 py-2.5 text-[10px] font-mono font-bold [color-scheme:dark]">
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Footer -->
                    <div class="px-6 sm:px-8 py-4 border-t border-[#1e1e1e] bg-[#0d0d0d] shrink-0">
                        <div class="flex gap-2.5">
                            <button type="button" @click="showEditModal = false"
                                class="flex-1 py-3 rounded-xl bg-[#1a1a1a] border border-[#2a2a2a] text-white text-[10px] font-bold uppercase tracking-widest hover:bg-[#222] transition">
                                Cancel
                            </button>
                            <button type="submit"
                                class="flex-1 py-3 rounded-xl bg-amber-600 text-white text-[10px] font-bold uppercase tracking-widest hover:bg-amber-500 transition active:scale-[0.98] shadow-lg shadow-amber-900/30">
                                Update Vehicle
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- ══════════ ALPINE.JS APP ══════════ -->
    <script>
        function vehicleManagementApp() {
            return {
                open: localStorage.getItem('sidebarOpen') !== 'false',
                search: '',
                showModal: false,
                showViewModal: false,
                showEditModal: false,
                showLogoutModal: false,
                vehicles: {{ Js::from($vehicles) }},
                viewData: {},
                editData: {},

                init() {
                    this.$watch('open', (val) => localStorage.setItem('sidebarOpen', val));
                },

                get filtered() {
                    if (!this.search.trim()) return this.vehicles;
                    const q = this.search.toLowerCase();
                    return this.vehicles.filter(v =>
                        (v.brand || '').toLowerCase().includes(q) ||
                        (v.model || '').toLowerCase().includes(q) ||
                        (v.plate_number || '').toLowerCase().includes(q) ||
                        (v.driver_name || '').toLowerCase().includes(q) ||
                        (v.location || '').toLowerCase().includes(q)
                    );
                },

                openAddModal() {
                    this.showModal = true;
                },

                openViewModal(v) {
                    this.viewData = { ...v };
                    this.showViewModal = true;
                },

                openEditModal(v) {
                    this.editData = { ...v };
                    this.showEditModal = true;
                },

                dateStr(val) {
                    if (!val) return '—';
                    const d = new Date(val);
                    return d.toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
                },

                dateTimeStr(val) {
                    if (!val) return '—';
                    const d = new Date(val);
                    return d.toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
                },

                isOverdue(val) {
                    if (!val) return false;
                    return new Date(val) < new Date();
                }
            };
        }
    </script>

</body>
</html>
