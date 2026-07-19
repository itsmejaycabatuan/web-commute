@use('Illuminate\Support\Js')

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
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap');

        body {
            background: #050505;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #fff;
            overflow-x: hidden;
        }

        [x-cloak] { display: none !important; }

        .glass {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .sidebar-transition {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        input[type="text"],
        input[type="date"],
        input[type="number"],
        select,
        textarea {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            color: #fff;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        input:focus, select:focus, textarea:focus {
            border-color: rgba(59, 130, 246, 0.5);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        input[type="date"]::-webkit-calendar-picker-indicator { filter: invert(0.6); cursor: pointer; }

        select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='rgba(255,255,255,0.3)' viewBox='0 0 16 16'%3E%3Cpath d='M8 11L3 6h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
        }

        .fade-in {
            animation: fadeIn 0.5s ease-out both;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .table-scroll::-webkit-scrollbar { height: 4px; }
        .table-scroll::-webkit-scrollbar-track { background: transparent; }
        .table-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.08); border-radius: 10px; }

        .modal-backdrop {
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(8px);
        }

        .modal-enter {
            animation: modalIn 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes modalIn {
            from { opacity: 0; transform: scale(0.95) translateY(10px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        .backdrop-enter { animation: backdropIn 0.25s ease forwards; }
        .backdrop-leave { animation: backdropOut 0.2s ease forwards; }

        @keyframes backdropIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes backdropOut { from { opacity: 1; } to { opacity: 0; } }

        .detail-label {
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: rgba(255,255,255,0.3);
        }
        .detail-value {
            font-size: 13px;
            color: rgba(255,255,255,0.75);
            margin-top: 4px;
        }
        .detail-value.mono {
            font-family: 'Courier New', monospace;
        }
    </style>
</head>

<body x-data="fleetInventoryApp()" x-init="init()">

    @include('components.flash')
    @include('maintenance-manager.layout.sidebar')

    <main :class="open ? 'ml-72' : 'ml-20'" class="sidebar-transition p-8 md:p-12 min-h-screen">
        <div class="max-w-[1400px] mx-auto">

            <!-- Header -->
            <header class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6 fade-in">
                <div>
                    <h2 class="text-3xl font-black tracking-tight mb-1">Fleet <span class="text-blue-500">Inventory</span></h2>
                    <p class="text-white/40 text-sm">Maintenance costs and financial records per vehicle.</p>
                </div>
                <button @click="openAddModal()" class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-sm font-semibold shadow-lg shadow-blue-900/40 transition-all active:scale-[0.97] self-start">
                    <i class="fa-solid fa-plus mr-2"></i>Add Record
                </button>
            </header>

            <!-- Search -->
            <div class="mb-4 fade-in" style="animation-delay:0.04s">
                <div class="relative max-w-sm">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-white/20 text-xs"></i>
                    <input type="text" x-model="search" placeholder="Search vehicle, plate, driver..." class="w-full pl-10 pr-4 py-2.5 rounded-xl text-sm">
                </div>
            </div>

            <!-- Table -->
            <div class="glass rounded-2xl border border-white/5 overflow-hidden fade-in" style="animation-delay:0.08s">
                <div class="overflow-x-auto table-scroll">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-white/[0.06]">
                                <th class="px-5 py-3.5 text-[10px] font-bold uppercase tracking-[0.1em] text-white/40 sticky left-0 bg-[#0a0a0a] z-20 min-w-[280px]">Vehicle</th>
                                <th class="px-4 py-3.5 text-[10px] font-bold uppercase tracking-[0.1em] text-white/40 text-right">Maintenance Cost</th>
                                <th class="px-4 py-3.5 text-[10px] font-bold uppercase tracking-[0.1em] text-white/40">Notes</th>
                                <th class="px-4 py-3.5 w-28"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(inv, index) in filtered" :key="inv.id">
                                <tr class="border-b border-white/[0.03] hover:bg-white/[0.02] transition-colors duration-150 group">
                                    <td class="px-5 py-4 sticky left-0 z-10 transition-colors duration-150" :class="index % 2 === 0 ? 'bg-[#080808] group-hover:bg-[#0b0b0b]' : 'bg-[#0a0a0a] group-hover:bg-[#0d0d0d]'">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-lg bg-blue-500/[0.06] border border-blue-500/10 flex items-center justify-center shrink-0">
                                                <i class="fa-solid fa-van-shuttle text-blue-400/50 text-xs"></i>
                                            </div>
                                            <div class="min-w-0">
                                                <span class="text-[13px] font-semibold text-white/80 block truncate" x-text="inv.vehicle_name"></span>
                                                <div class="flex items-center gap-2 mt-0.5">
                                                    <span class="text-[11px] text-white/30" x-text="inv.vehicle_year"></span>
                                                    <span class="w-0.5 h-0.5 rounded-full bg-white/10"></span>
                                                    <span class="text-[10px] font-mono text-blue-400/40" x-text="inv.plate_number || '—'"></span>
                                                    <span class="w-0.5 h-0.5 rounded-full bg-white/10" x-show="inv.driver_name"></span>
                                                    <span class="text-[10px] text-white/20 truncate max-w-[80px]" x-show="inv.driver_name" x-text="inv.driver_name"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 text-right">
                                        <span class="text-[13px] font-mono font-medium tabular-nums" :class="Number(inv.maintenance_cost) > 1500000 ? 'text-rose-400' : Number(inv.maintenance_cost) > 500000 ? 'text-amber-400' : 'text-white/40'" x-text="fmt(inv.maintenance_cost)"></span>
                                    </td>
                                    <td class="px-4 py-4">
                                        <span class="text-[11px] text-white/20 block truncate max-w-[200px]" :title="inv.notes || ''" x-text="inv.notes || '—'"></span>
                                    </td>
                                    <td class="px-4 py-4 text-center">
                                        <div class="opacity-0 group-hover:opacity-100 transition-opacity duration-150 flex items-center justify-center gap-1">
                                            <button @click="openViewModal(inv)" class="w-8 h-8 rounded-lg hover:bg-white/[0.06] flex items-center justify-center text-white/20 hover:text-white/70 transition-all duration-150" title="View Details">
                                                <i class="fa-solid fa-eye text-[11px]"></i>
                                            </button>
                                            <button @click="openEditModal(inv)" class="w-8 h-8 rounded-lg hover:bg-white/[0.06] flex items-center justify-center text-white/20 hover:text-blue-400 transition-all duration-150" title="Edit">
                                                <i class="fa-solid fa-pen-to-square text-[11px]"></i>
                                            </button>
                                            <form method="POST" :action="`{{ route('maintenance-manager.fleet-inventory.destroy', '__ID__') }}`.replace('__ID__', inv.id)" class="inline-flex">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" onclick="return confirm('Remove this inventory record?')" class="w-8 h-8 rounded-lg hover:bg-rose-500/[0.08] flex items-center justify-center text-white/20 hover:text-rose-400 transition-all duration-150" title="Delete">
                                                    <i class="fa-solid fa-trash-can text-[11px]"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div x-show="filtered.length === 0" x-cloak class="py-14 text-center">
                    <i class="fa-solid fa-box-open text-white/10 text-2xl mb-3 block"></i>
                    <p class="text-white/30 text-sm">No inventory records yet</p>
                    <p class="text-white/15 text-xs mt-1">Click "Add Record" to get started</p>
                </div>

                <div class="px-5 py-3 border-t border-white/5 bg-white/[0.01] flex justify-between text-xs">
                    <span class="text-white/20" x-text="filtered.length + ' of ' + inventories.length + ' records'"></span>
                    <div class="flex items-center gap-5">
                        <span class="text-white/20">Total Maintenance: <span class="text-amber-400/70 font-mono font-bold" x-text="fmt(inventories.reduce((s,i) => s + Number(i.maintenance_cost), 0))"></span></span>
                    </div>
                </div>
            </div>

            <div class="h-12"></div>
        </div>
    </main>

    <!-- ==================== VIEW RECORD MODAL ==================== -->
    <template x-teleport="body">
        <div x-show="showViewModal" x-cloak @keydown.escape.window="showViewModal = false"
            class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
            x-transition:enter="backdrop-enter" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="backdrop-leave" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

            <div class="modal-backdrop absolute inset-0" @click="showViewModal = false"></div>

            <div x-show="showViewModal" @click.stop class="relative w-full max-w-lg rounded-2xl border border-white/[0.08] shadow-2xl shadow-black/60 modal-enter overflow-hidden"
                 style="background: linear-gradient(165deg, rgba(20,20,25,0.98) 0%, rgba(10,10,12,0.99) 100%);">

                <div class="absolute top-0 left-1/2 -translate-x-1/2 w-48 h-px bg-gradient-to-r from-transparent via-blue-500/60 to-transparent"></div>

                <div class="flex items-center justify-between px-7 pt-7 pb-2">
                    <div>
                        <h3 class="text-xl font-black tracking-tight">Inventory Details</h3>
                        <p class="text-white/30 text-xs mt-1" x-text="viewData.vehicle_name"></p>
                    </div>
                    <button type="button" @click="showViewModal = false" class="w-9 h-9 rounded-lg bg-white/5 hover:bg-white/10 border border-white/5 flex items-center justify-center transition-all active:scale-90">
                        <i class="fa-solid fa-xmark text-white/40 text-sm"></i>
                    </button>
                </div>

                <div class="mx-7 my-4 h-px bg-white/[0.06]"></div>

                <div class="px-7 pb-7 max-h-[70vh] overflow-y-auto pr-2" style="scrollbar-width: thin; scrollbar-color: rgba(255,255,255,0.1) transparent;">

                    <!-- Linked Vehicle -->
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-white/20 mb-3">Linked Vehicle</p>
                    <div class="p-4 rounded-xl bg-white/[0.02] border border-white/[0.04] mb-6 space-y-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-blue-500/[0.06] border border-blue-500/10 flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-van-shuttle text-blue-400/50 text-sm"></i>
                            </div>
                            <div>
                                <div class="text-[14px] text-white/80 font-semibold" x-text="viewData.vehicle_name + ' (' + (viewData.vehicle_year || '—') + ')'"></div>
                                <div class="text-[11px] text-blue-400/50 font-mono" x-text="viewData.plate_number || 'No plate'"></div>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3 pt-2 border-t border-white/[0.04]">
                            <div>
                                <div class="detail-label">Driver</div>
                                <div class="detail-value" x-text="viewData.driver_name || 'Unassigned'"></div>
                            </div>
                            <div>
                                <div class="detail-label">Vehicle Status</div>
                                <div class="flex items-center gap-2 mt-1">
                                    <template x-if="viewData.vehicle_status === 'active'">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                    </template>
                                    <template x-if="viewData.vehicle_status === 'maintenance'">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                                    </template>
                                    <template x-if="viewData.vehicle_status === 'inactive'">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-400/70"></span>
                                    </template>
                                    <template x-if="viewData.vehicle_status === 'disposed'">
                                        <span class="w-1.5 h-1.5 rounded-full bg-white/15"></span>
                                    </template>
                                    <template x-if="!['active','maintenance','inactive','disposed'].includes(viewData.vehicle_status)">
                                        <span class="w-1.5 h-1.5 rounded-full bg-white/10"></span>
                                    </template>
                                    <span class="text-[12px] text-white/50 capitalize" x-text="viewData.vehicle_status || '—'"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Financials -->
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-white/20 mb-3">Financials</p>
                    <div class="grid grid-cols-2 gap-x-6 gap-y-4 mb-6">
                        <div>
                            <div class="detail-label">Maintenance Cost</div>
                            <div class="detail-value mono font-semibold text-amber-400/80" x-text="fmt(viewData.maintenance_cost)"></div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-white/20 mb-3">Notes</p>
                    <div class="p-3 rounded-xl bg-white/[0.02] border border-white/[0.04] mb-4">
                        <p class="text-[12px] text-white/50 leading-relaxed whitespace-pre-wrap" x-text="viewData.notes || 'No notes provided.'"></p>
                    </div>

                    <!-- Timestamps -->
                    <div class="mt-5 pt-4 border-t border-white/[0.04] flex items-center justify-between text-[10px] text-white/15">
                        <span>Created: <span class="mono" x-text="dateTimeStr(viewData.created_at)"></span></span>
                        <span>Updated: <span class="mono" x-text="dateTimeStr(viewData.updated_at)"></span></span>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <!-- ==================== ADD RECORD MODAL ==================== -->
    <template x-teleport="body">
        <div x-show="showModal" x-cloak @keydown.escape.window="showModal = false"
            class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
            x-transition:enter="backdrop-enter" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="backdrop-leave" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

            <div class="modal-backdrop absolute inset-0" @click="showModal = false"></div>

            <div x-show="showModal" @click.stop class="relative w-full max-w-xl rounded-2xl border border-white/[0.08] shadow-2xl shadow-black/60 modal-enter overflow-hidden"
                 style="background: linear-gradient(165deg, rgba(20,20,25,0.98) 0%, rgba(10,10,12,0.99) 100%);">

                <div class="absolute top-0 left-1/2 -translate-x-1/2 w-48 h-px bg-gradient-to-r from-transparent via-blue-500/60 to-transparent"></div>

                <form method="POST" action="{{ route('maintenance-manager.fleet-inventory.store') }}" @submit="showModal = false">
                    @csrf
                    <div class="flex items-center justify-between px-7 pt-7 pb-2">
                        <div>
                            <h3 class="text-xl font-black tracking-tight">Add Record</h3>
                            <p class="text-white/30 text-xs mt-1">Add a maintenance cost record for a vehicle.</p>
                        </div>
                        <button type="button" @click="showModal = false" class="w-9 h-9 rounded-lg bg-white/5 hover:bg-white/10 border border-white/5 flex items-center justify-center transition-all active:scale-90">
                            <i class="fa-solid fa-xmark text-white/40 text-sm"></i>
                        </button>
                    </div>

                    <div class="mx-7 my-4 h-px bg-white/[0.06]"></div>

                    <div class="px-7 pb-2 space-y-5 max-h-[65vh] overflow-y-auto pr-2" style="scrollbar-width: thin; scrollbar-color: rgba(255,255,255,0.1) transparent;">
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-[0.15em] text-white/40 mb-2">Vehicle</label>
                            <select name="vehicle_id" class="w-full px-4 py-2.5 rounded-xl text-sm" required>
                                <option value="" class="bg-[#111]">Select a vehicle...</option>
                                @foreach($vehicles as $vehicle)
                                    <option value="{{ $vehicle->id }}" class="bg-[#111]">{{ $vehicle->brand }} {{ $vehicle->model }} ({{ $vehicle->year }}) — {{ $vehicle->plate_number }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mx-0 my-2 h-px bg-white/[0.04]"></div>
                        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-white/20">Financials & Notes</p>

                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-[0.15em] text-white/40 mb-2">Maintenance Cost (₱)</label>
                            <input type="number" name="maintenance_cost" placeholder="0.00" step="0.01" min="0" class="w-full px-4 py-2.5 rounded-xl text-sm font-mono">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-[0.15em] text-white/40 mb-2">Notes</label>
                            <textarea name="notes" rows="4" placeholder="What was the maintenance for..." class="w-full px-4 py-2.5 rounded-xl text-sm resize-none"></textarea>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 px-7 py-6 mt-2">
                        <button type="button" @click="showModal = false" class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white/50 hover:text-white/80 hover:bg-white/5 border border-white/[0.06] transition-all active:scale-95">Cancel</button>
                        <button type="submit" class="px-6 py-2.5 rounded-xl text-sm font-bold transition-all active:scale-95 bg-blue-600 hover:bg-blue-500 shadow-lg shadow-blue-900/40 text-white">
                            <i class="fa-solid fa-check mr-2"></i>Add Record
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- ==================== EDIT RECORD MODAL ==================== -->
    <template x-teleport="body">
        <div x-show="showEditModal" x-cloak @keydown.escape.window="closeEditModal()"
            class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
            x-transition:enter="backdrop-enter" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="backdrop-leave" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

            <div class="modal-backdrop absolute inset-0" @click="closeEditModal()"></div>

            <form method="POST"
                  :action="`{{ route('maintenance-manager.fleet-inventory.update', 0) }}`.replace('/0', '/' + editForm.id)"
                  x-show="showEditModal"
                  @click.stop
                  class="relative w-full max-w-xl rounded-2xl border border-white/[0.08] shadow-2xl shadow-black/60 modal-enter overflow-hidden"
                  style="background: linear-gradient(165deg, rgba(20,20,25,0.98) 0%, rgba(10,10,12,0.99) 100%);">

                @csrf
                @method('PATCH')

                <div class="absolute top-0 left-1/2 -translate-x-1/2 w-48 h-px bg-gradient-to-r from-transparent via-blue-500/60 to-transparent"></div>

                <div class="flex items-center justify-between px-7 pt-7 pb-2">
                    <div>
                        <h3 class="text-xl font-black tracking-tight">Edit Record</h3>
                        <p class="text-white/30 text-xs mt-1">Update inventory details.</p>
                    </div>
                    <button type="button" @click="closeEditModal()" class="w-9 h-9 rounded-lg bg-white/5 hover:bg-white/10 border border-white/5 flex items-center justify-center transition-all active:scale-90">
                        <i class="fa-solid fa-xmark text-white/40 text-sm"></i>
                    </button>
                </div>

                <div class="mx-7 my-4 h-px bg-white/[0.06]"></div>

                <div class="px-7 pb-2 space-y-5 max-h-[65vh] overflow-y-auto pr-2" style="scrollbar-width: thin; scrollbar-color: rgba(255,255,255,0.1) transparent;">
                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-[0.15em] text-white/40 mb-2">Vehicle</label>
                        <select name="vehicle_id" x-model="editForm.vehicle_id" class="w-full px-4 py-2.5 rounded-xl text-sm" required>
                            <option value="" class="bg-[#111]">Select a vehicle...</option>
                            @foreach($vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}" class="bg-[#111]">{{ $vehicle->brand }} {{ $vehicle->model }} ({{ $vehicle->year }}) — {{ $vehicle->plate_number }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mx-0 my-2 h-px bg-white/[0.04]"></div>
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-white/20">Financials & Notes</p>

                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-[0.15em] text-white/40 mb-2">Maintenance Cost (₱)</label>
                        <input type="number" name="maintenance_cost" x-model="editForm.maintenance_cost" step="0.01" min="0" class="w-full px-4 py-2.5 rounded-xl text-sm font-mono">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-[0.15em] text-white/40 mb-2">Notes</label>
                        <textarea name="notes" x-model="editForm.notes" rows="4" class="w-full px-4 py-2.5 rounded-xl text-sm resize-none"></textarea>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 px-7 py-6 mt-2">
                    <button type="button" @click="closeEditModal()" class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white/50 hover:text-white/80 hover:bg-white/5 border border-white/[0.06] transition-all active:scale-95">Cancel</button>
                    <button type="submit" class="px-6 py-2.5 rounded-xl text-sm font-bold transition-all active:scale-95 bg-blue-600 hover:bg-blue-500 shadow-lg shadow-blue-900/40 text-white">
                        <i class="fa-solid fa-check mr-2"></i>Update Record
                    </button>
                </div>
            </form>
        </div>
    </template>

    <script>
    function fleetInventoryApp() {
        return {
            open: true,
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
