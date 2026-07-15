@use('Illuminate\Support\Js')

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
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

        /* Modal Animations */
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
                    <p class="text-white/40 text-sm">Registered fleet vehicles and purchase records.</p>
                </div>
                <button @click="openAddModal()" class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-sm font-semibold shadow-lg shadow-blue-900/40 transition-all active:scale-[0.97] self-start">
                    <i class="fa-solid fa-plus mr-2"></i>Add Vehicle
                </button>
            </header>

            <!-- Search -->
            <div class="mb-4 fade-in" style="animation-delay:0.04s">
                <div class="relative max-w-sm">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-white/20 text-xs"></i>
                    <input type="text" x-model="search" placeholder="Search make, model, fleet ID..." class="w-full pl-10 pr-4 py-2.5 rounded-xl text-sm">
                </div>
            </div>

            <!-- Table -->
            <div class="glass rounded-2xl border border-white/5 overflow-hidden fade-in" style="animation-delay:0.08s">
                <div class="overflow-x-auto table-scroll">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-white/[0.06]">
                                <th class="px-5 py-3.5 text-[10px] font-bold uppercase tracking-[0.1em] text-white/40 sticky left-0 bg-[#0a0a0a] z-20 min-w-[220px]">Vehicle</th>
                                <th class="px-4 py-3.5 text-[10px] font-bold uppercase tracking-[0.1em] text-white/40">Fleet ID</th>
                                <th class="px-4 py-3.5 text-[10px] font-bold uppercase tracking-[0.1em] text-white/40 text-right">Purchased</th>
                                <th class="px-4 py-3.5 text-[10px] font-bold uppercase tracking-[0.1em] text-white/40 text-right">Cost</th>
                                <th class="px-4 py-3.5 text-[10px] font-bold uppercase tracking-[0.1em] text-white/40 text-right">Maintenance</th>
                                <th class="px-4 py-3.5 text-[10px] font-bold uppercase tracking-[0.1em] text-white/40 text-center">Status</th>
                                <th class="px-4 py-3.5 text-[10px] font-bold uppercase tracking-[0.1em] text-white/40">Notes</th>
                                <th class="px-4 py-3.5 w-20"></th>
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
                                                <span class="text-[13px] font-semibold text-white/80 block truncate" :class="inv.condition === 'disposed' ? 'line-through text-white/30' : ''" x-text="inv.make + ' ' + inv.model"></span>
                                                <div class="flex items-center gap-2 mt-0.5">
                                                    <span class="text-[11px] text-white/30" x-text="inv.year"></span>
                                                    <span class="w-0.5 h-0.5 rounded-full bg-white/10"></span>
                                                    <span class="text-[10px] font-mono text-white/20" x-text="inv.engine"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <span class="inline-flex items-center text-[11px] font-mono font-semibold text-blue-400/60 bg-blue-500/[0.05] px-2.5 py-1 rounded-lg border border-blue-500/[0.08]" x-text="inv.fleet_id"></span>
                                    </td>
                                    <td class="px-4 py-4 text-right">
                                        <span class="text-xs text-white/35 font-mono" x-text="dateStr(inv.purchase_date)"></span>
                                    </td>
                                    <td class="px-4 py-4 text-right">
                                        <span class="text-[13px] text-white/70 font-mono font-medium tabular-nums" x-text="fmt(inv.purchase_cost)"></span>
                                    </td>
                                    <td class="px-4 py-4 text-right">
                                        <span class="text-[13px] font-mono font-medium tabular-nums" :class="inv.maintenance_cost > 1500000 ? 'text-rose-400' : inv.maintenance_cost > 500000 ? 'text-amber-400' : 'text-white/40'" x-text="fmt(inv.maintenance_cost)"></span>
                                    </td>
                                    <td class="px-4 py-4 text-center">
                                        <template x-if="inv.condition === 'active'">
                                            <span class="inline-flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-wider text-emerald-400 bg-emerald-500/[0.08] px-3 py-1 rounded-full border border-emerald-500/[0.15]">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>Active
                                            </span>
                                        </template>
                                        <template x-if="inv.condition === 'maintenance'">
                                            <span class="inline-flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-wider text-amber-400 bg-amber-500/[0.08] px-3 py-1 rounded-full border border-amber-500/[0.15]">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>Maintenance
                                            </span>
                                        </template>
                                        <template x-if="inv.condition === 'disposed'">
                                            <span class="inline-flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-wider text-white/20 bg-white/[0.03] px-3 py-1 rounded-full border border-white/[0.06]">
                                                <span class="w-1.5 h-1.5 rounded-full bg-white/15"></span>Disposed
                                            </span>
                                        </template>
                                        <template x-if="!['active','maintenance','disposed'].includes(inv.condition)">
                                            <span class="text-[10px] text-white/25 uppercase tracking-wider" x-text="inv.condition || '—'"></span>
                                        </template>
                                    </td>
                                    <td class="px-4 py-4">
                                        <span class="text-[11px] text-white/20 block truncate max-w-[160px]" :title="inv.notes || ''" x-text="inv.notes || '—'"></span>
                                    </td>
                                    <td class="px-4 py-4 text-center">
                                        <div class="opacity-0 group-hover:opacity-100 transition-opacity duration-150 flex items-center justify-center gap-1">
                                            <button @click="openEditModal(inv)" class="w-8 h-8 rounded-lg hover:bg-white/[0.06] flex items-center justify-center text-white/20 hover:text-blue-400 transition-all duration-150">
                                                <i class="fa-solid fa-pen-to-square text-[11px]"></i>
                                            </button>
                                            <form method="POST" :action="`{{ route('maintenance-manager.fleet-inventory.destroy', '__ID__') }}`.replace('__ID__', inv.id)" class="inline-flex">
    @csrf
    @method('DELETE')
    <button type="submit" onclick="return confirm('Remove this inventory record?')" class="w-8 h-8 rounded-lg hover:bg-rose-500/[0.08] flex items-center justify-center text-white/20 hover:text-rose-400 transition-all duration-150">
        <i class="fa-solid fa-trash-can text-[11px]"></i>
    </button>
</form>                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div x-show="filtered.length === 0" x-cloak class="py-14 text-center">
                    <i class="fa-solid fa-box-open text-white/10 text-2xl mb-3 block"></i>
                    <p class="text-white/30 text-sm">No vehicles yet</p>
                    <p class="text-white/15 text-xs mt-1">Click "Add Vehicle" to get started</p>
                </div>

                <div class="px-5 py-3 border-t border-white/5 bg-white/[0.01] flex justify-between text-xs">
                    <span class="text-white/20" x-text="filtered.length + ' of ' + inventories.length + ' vehicles'"></span>
                    <div class="flex items-center gap-5">
                        <span class="text-white/20">Purchase: <span class="text-white/60 font-mono font-bold" x-text="fmt(inventories.reduce((s,i) => s + Number(i.purchase_cost), 0))"></span></span>
                        <span class="text-white/20">Maintenance: <span class="text-amber-400/70 font-mono font-bold" x-text="fmt(inventories.reduce((s,i) => s + Number(i.maintenance_cost), 0))"></span></span>
                    </div>
                </div>
            </div>

            <div class="h-12"></div>
        </div>
    </main>

    <!-- ==================== ADD VEHICLE MODAL ==================== -->
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
                            <h3 class="text-xl font-black tracking-tight">Add Vehicle</h3>
                            <p class="text-white/30 text-xs mt-1">Register a new fleet vehicle.</p>
                        </div>
                        <button type="button" @click="showModal = false" class="w-9 h-9 rounded-lg bg-white/5 hover:bg-white/10 border border-white/5 flex items-center justify-center transition-all active:scale-90">
                            <i class="fa-solid fa-xmark text-white/40 text-sm"></i>
                        </button>
                    </div>

                    <div class="mx-7 my-4 h-px bg-white/[0.06]"></div>

                    <div class="px-7 pb-2 space-y-5 max-h-[65vh] overflow-y-auto pr-2" style="scrollbar-width: thin; scrollbar-color: rgba(255,255,255,0.1) transparent;">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-black uppercase tracking-[0.15em] text-white/40 mb-2">Fleet ID</label>
                                <input type="text" name="fleet_id" placeholder="PUV2" class="w-full px-4 py-2.5 rounded-xl text-sm font-mono" required>
                            </div>
                            <div>
                                <label class="block text-[10px] font-black uppercase tracking-[0.15em] text-white/40 mb-2">Year</label>
                                <input type="number" name="year" placeholder="2024" min="1990" max="2030" class="w-full px-4 py-2.5 rounded-xl text-sm font-mono" required>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-black uppercase tracking-[0.15em] text-white/40 mb-2">Make</label>
                                <input type="text" name="make" placeholder="Toyota" class="w-full px-4 py-2.5 rounded-xl text-sm" required>
                            </div>
                            <div>
                                <label class="block text-[10px] font-black uppercase tracking-[0.15em] text-white/40 mb-2">Model</label>
                                <input type="text" name="model" placeholder="HiAce Commuter" class="w-full px-4 py-2.5 rounded-xl text-sm" required>
                            </div>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-[0.15em] text-white/40 mb-2">Engine</label>
                            <input type="text" name="engine" placeholder="2.8L Diesel" class="w-full px-4 py-2.5 rounded-xl text-sm font-mono" required>
                        </div>

                        <div class="mx-0 my-2 h-px bg-white/[0.04]"></div>
                        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-white/20">Financials & Status</p>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-black uppercase tracking-[0.15em] text-white/40 mb-2">Purchase Date</label>
                                <input type="date" name="purchase_date" class="w-full px-4 py-2.5 rounded-xl text-sm font-mono" required>
                            </div>
                            <div>
                                <label class="block text-[10px] font-black uppercase tracking-[0.15em] text-white/40 mb-2">Status</label>
                                <select name="condition" class="w-full px-4 py-2.5 rounded-xl text-sm">
                                    <option value="active" class="bg-[#111]">Active</option>
                                    <option value="maintenance" class="bg-[#111]">Maintenance</option>
                                    <option value="disposed" class="bg-[#111]">Disposed</option>
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-black uppercase tracking-[0.15em] text-white/40 mb-2">Purchase Cost (₱)</label>
                                <input type="number" name="purchase_cost" placeholder="0.00" step="0.01" min="0" class="w-full px-4 py-2.5 rounded-xl text-sm font-mono" required>
                            </div>
                            <div>
                                <label class="block text-[10px] font-black uppercase tracking-[0.15em] text-white/40 mb-2">Maintenance Cost (₱)</label>
                                <input type="number" name="maintenance_cost" placeholder="0.00" step="0.01" min="0" class="w-full px-4 py-2.5 rounded-xl text-sm font-mono">
                            </div>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-[0.15em] text-white/40 mb-2">Notes</label>
                            <textarea name="notes" rows="3" placeholder="Optional notes..." class="w-full px-4 py-2.5 rounded-xl text-sm resize-none"></textarea>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 px-7 py-6 mt-2">
                        <button type="button" @click="showModal = false" class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white/50 hover:text-white/80 hover:bg-white/5 border border-white/[0.06] transition-all active:scale-95">Cancel</button>
                        <button type="submit" class="px-6 py-2.5 rounded-xl text-sm font-bold transition-all active:scale-95 bg-blue-600 hover:bg-blue-500 shadow-lg shadow-blue-900/40 text-white">
                            <i class="fa-solid fa-check mr-2"></i>Add Vehicle
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

<!-- ==================== EDIT VEHICLE MODAL ==================== -->
<template x-teleport="body">
    <div x-show="showEditModal" x-cloak @keydown.escape.window="closeEditModal()"
        class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
        x-transition:enter="backdrop-enter" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="backdrop-leave" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

        <div class="modal-backdrop absolute inset-0" @click="closeEditModal()"></div>

        <!-- ✅ Added standard form with dynamic action -->
        <form method="POST"
              :action="`{{ route('maintenance-manager.fleet-inventory.update', 0) }}`.replace('/0', '/' + editForm.id)"
              x-show="showEditModal"
              @click.stop
              class="relative w-full max-w-xl rounded-2xl border border-white/[0.08] shadow-2xl shadow-black/60 modal-enter overflow-hidden"
              style="background: linear-gradient(165deg, rgba(20,20,25,0.98) 0%, rgba(10,10,12,0.99) 100%);">

            @csrf
            @method('PATCH') <!-- ✅ Tells Laravel this is a PATCH request -->

            <div class="absolute top-0 left-1/2 -translate-x-1/2 w-48 h-px bg-gradient-to-r from-transparent via-blue-500/60 to-transparent"></div>

            <div class="flex items-center justify-between px-7 pt-7 pb-2">
                <div>
                    <h3 class="text-xl font-black tracking-tight">Edit Vehicle</h3>
                    <p class="text-white/30 text-xs mt-1">Update inventory details.</p>
                </div>
                <button type="button" @click="closeEditModal()" class="w-9 h-9 rounded-lg bg-white/5 hover:bg-white/10 border border-white/5 flex items-center justify-center transition-all active:scale-90">
                    <i class="fa-solid fa-xmark text-white/40 text-sm"></i>
                </button>
            </div>

            <div class="mx-7 my-4 h-px bg-white/[0.06]"></div>

            <div class="px-7 pb-2 space-y-5 max-h-[65vh] overflow-y-auto pr-2" style="scrollbar-width: thin; scrollbar-color: rgba(255,255,255,0.1) transparent;">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-[0.15em] text-white/40 mb-2">Fleet ID</label>
                        <!-- ✅ Added name="fleet_id" -->
                        <input type="text" name="fleet_id" x-model="editForm.fleet_id" class="w-full px-4 py-2.5 rounded-xl text-sm font-mono">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-[0.15em] text-white/40 mb-2">Year</label>
                        <!-- ✅ Added name="year" -->
                        <input type="number" name="year" x-model="editForm.year" class="w-full px-4 py-2.5 rounded-xl text-sm font-mono">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-[0.15em] text-white/40 mb-2">Make</label>
                        <input type="text" name="make" x-model="editForm.make" class="w-full px-4 py-2.5 rounded-xl text-sm" required>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-[0.15em] text-white/40 mb-2">Model</label>
                        <input type="text" name="model" x-model="editForm.model" class="w-full px-4 py-2.5 rounded-xl text-sm" required>
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-[0.15em] text-white/40 mb-2">Engine</label>
                    <input type="text" name="engine" x-model="editForm.engine" class="w-full px-4 py-2.5 rounded-xl text-sm font-mono">
                </div>

                <div class="mx-0 my-2 h-px bg-white/[0.04]"></div>
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-white/20">Financials & Status</p>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-[0.15em] text-white/40 mb-2">Purchase Date</label>
                        <input type="date" name="purchase_date" x-model="editForm.purchase_date" class="w-full px-4 py-2.5 rounded-xl text-sm font-mono">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-[0.15em] text-white/40 mb-2">Condition</label>
                        <select name="condition" x-model="editForm.condition" class="w-full px-4 py-2.5 rounded-xl text-sm">
                            <option value="active" class="bg-[#111]">Active</option>
                            <option value="maintenance" class="bg-[#111]">Maintenance</option>
                            <option value="disposed" class="bg-[#111]">Disposed</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-[0.15em] text-white/40 mb-2">Purchase Cost (₱)</label>
                        <input type="number" name="purchase_cost" x-model="editForm.purchase_cost" step="0.01" min="0" class="w-full px-4 py-2.5 rounded-xl text-sm font-mono">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-[0.15em] text-white/40 mb-2">Maintenance Cost (₱)</label>
                        <input type="number" name="maintenance_cost" x-model="editForm.maintenance_cost" step="0.01" min="0" class="w-full px-4 py-2.5 rounded-xl text-sm font-mono">
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-[0.15em] text-white/40 mb-2">Notes</label>
                    <textarea name="notes" x-model="editForm.notes" rows="3" class="w-full px-4 py-2.5 rounded-xl text-sm resize-none"></textarea>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 px-7 py-6 mt-2">
                <button type="button" @click="closeEditModal()" class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white/50 hover:text-white/80 hover:bg-white/5 border border-white/[0.06] transition-all active:scale-95">Cancel</button>
                <!-- ✅ Changed to type="submit" and removed JS click logic -->
                <button type="submit" class="px-6 py-2.5 rounded-xl text-sm font-bold transition-all active:scale-95 bg-blue-600 hover:bg-blue-500 shadow-lg shadow-blue-900/40 text-white">
                    <i class="fa-solid fa-check mr-2"></i>Update Vehicle
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
            search: '',
            inventories: [],
            condition: '',
            editForm: {},

            init() {
                this.inventories = {{ Js::from($inventories) }};
            },

            openAddModal() {
                this.showModal = true;
            },

            // ✅ Keep these to manage the UI and populate inputs
            resetEditForm() {
                this.editForm = {
                    id: null, fleet_id: '', make: '', model: '',
                    year: '', engine: '', purchase_date: '',
                    purchase_cost: '', maintenance_cost: '',
                    condition: '', notes: ''
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

            // ❌ Removed updateRecord() entirely

            get filtered() {
                if (!this.search) return this.inventories;
                const q = this.search.toLowerCase();
                return this.inventories.filter(inv => {
                    return (inv.fleet_id || '').toLowerCase().includes(q) ||
                        (inv.make || '').toLowerCase().includes(q) ||
                        (inv.model || '').toLowerCase().includes(q) ||
                        (inv.engine || '').toLowerCase().includes(q) ||
                        (inv.year || '').toString().includes(q) ||
                        (inv.notes || '').toLowerCase().includes(q);
                });
            },

            fmt(v) {
                return '₱' + Number(v || 0).toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            },

            dateStr(d) {
                if (!d) return '—';
                return new Date(d + 'T00:00:00').toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric'
                });
            }
        }
    }
</script></body>
</html>
