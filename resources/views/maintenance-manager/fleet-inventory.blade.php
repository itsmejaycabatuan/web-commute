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

        .tab-active {
            color: #fff;
            border-bottom: 2px solid #3b82f6;
        }

        .tab-inactive {
            color: rgba(255, 255, 255, 0.3);
            border-bottom: 2px solid transparent;
        }

        input[type="text"],
        input[type="date"],
        input[type="number"],
        select {
            background: #0a0a0a;
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff;
            outline: none;
            transition: border-color 0.2s;
        }
        input:focus, select:focus {
            border-color: rgba(59, 130, 246, 0.5);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        input[type="date"]::-webkit-calendar-picker-indicator { filter: invert(0.6); }
    </style>
</head>

<body x-data="{
    open: true,
    activeTab: 'cost',
    showModal: false,
    search: '',
    vehicles: [
        { id: 'PUV-001', name: 'PUV #1', year: 2020, make: 'Toyota', model: 'HiAce Commuter', plate: 'ABC123', purchaseDate: '2020-05-10', purchaseCost: 37300.00, maintenanceCost: 155.12, status: 'active' },
        { id: 'PUV-002', name: 'PUV #2', year: 2022, make: 'Toyota', model: 'HiAce Commuter', plate: 'DEF456', purchaseDate: '2022-06-12', purchaseCost: 35700.00, maintenanceCost: 20155.12, status: 'active' },
        { id: 'PUV-003', name: 'PUV #3', year: 2021, make: 'Suzuki', model: 'Super Carry', plate: 'GHI789', purchaseDate: '2021-03-22', purchaseCost: 28500.00, maintenanceCost: 8420.50, status: 'maintenance' },
        { id: 'PUV-004', name: 'PUV #4', year: 2019, make: 'Mitsubishi', model: 'L300 Exceed', plate: 'JKL012', purchaseDate: '2019-11-05', purchaseCost: 41200.00, maintenanceCost: 32800.00, status: 'disposed' }
    ],
    newVehicle: { name: '', year: '', make: '', model: '', plate: '', purchaseDate: '', purchaseCost: '' },
    get filtered() {
        if (!this.search) return this.vehicles;
        const q = this.search.toLowerCase();
        return this.vehicles.filter(v =>
            v.name.toLowerCase().includes(q) || v.plate.toLowerCase().includes(q) ||
            v.make.toLowerCase().includes(q) || v.model.toLowerCase().includes(q) || v.id.toLowerCase().includes(q)
        );
    },
    fmt(v) { return '₱' + v.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
    dateStr(d) { return d ? new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '—'; },
    addVehicle() {
        const n = this.vehicles.length + 1;
        this.vehicles.push({
            id: 'PUV-' + String(n).padStart(3, '0'),
            name: this.newVehicle.name || 'PUV #' + n,
            year: parseInt(this.newVehicle.year) || 2024,
            make: this.newVehicle.make || '—',
            model: this.newVehicle.model || '—',
            plate: this.newVehicle.plate || '—',
            purchaseDate: this.newVehicle.purchaseDate || null,
            purchaseCost: parseFloat(this.newVehicle.purchaseCost) || 0,
            maintenanceCost: 0,
            status: 'active'
        });
        this.newVehicle = { name: '', year: '', make: '', model: '', plate: '', purchaseDate: '', purchaseCost: '' };
        this.showModal = false;
    }
}">

    @include('maintenance-manager.layout.sidebar');

    <main :class="open ? 'ml-72' : 'ml-20'" x-data="{ editing: false }"
        class="sidebar-transition p-8 md:p-12 min-h-screen">
    <div class="max-w-[1400px] mx-auto">

            <!-- Header -->
            <header class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6 fade-in">
                <div>
                    <h2 class="text-3xl font-black tracking-tight mb-1">Fleet <span class="text-blue-500">Inventory</span></h2>
                    <p class="text-white/40 text-sm">Registered fleet vehicles and purchase records.</p>
                </div>
                <div class="flex gap-3 self-start">
                    <button class="px-4 py-2.5 rounded-xl bg-white/[0.04] border border-white/10 hover:bg-white/[0.07] text-white/70 text-sm font-semibold transition-all">
                        <i class="fa-solid fa-download mr-2"></i>Export
                    </button>
                    <button @click="showModal = true" class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-sm font-semibold shadow-lg shadow-blue-900/40 transition-all">
                        <i class="fa-solid fa-plus mr-2"></i>Add Vehicle
                    </button>
                </div>
            </header>

            <!-- Search -->
            <div class="mb-4 fade-in" style="animation-delay:0.05s">
                <div class="relative max-w-sm">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-white/20 text-xs"></i>
                    <input type="text" x-model="search" placeholder="Search fleet ID, plate, make, model..." class="w-full pl-10 pr-4 py-2.5 rounded-xl text-sm">
                </div>
            </div>

            <!-- Table -->
            <div class="glass rounded-2xl border border-white/5 overflow-hidden fade-in" style="animation-delay:0.1s">
                <div class="overflow-x-auto table-scroll">
                    <table class="w-full text-left whitespace-nowrap">
                        <thead>
                            <tr class="border-b border-white/5 bg-[#0a0a0a]">
                                <th class="px-5 py-3 text-[10px] font-black uppercase tracking-widest text-blue-500 sticky left-0 bg-[#0a0a0a] z-10 min-w-[200px]">Vehicle</th>
                                <th class="px-4 py-3 text-[10px] font-black uppercase tracking-widest text-white/30">Fleet ID</th>
                                <th class="px-4 py-3 text-[10px] font-black uppercase tracking-widest text-white/30">Plate #</th>
                                <th class="px-4 py-3 text-[10px] font-black uppercase tracking-widest text-white/30 text-right">Purchase Date</th>
                                <th class="px-4 py-3 text-[10px] font-black uppercase tracking-widest text-white/30 text-right">Purchase Cost</th>
                                <th class="px-4 py-3 text-[10px] font-black uppercase tracking-widest text-white/30 text-right">Maintenance Cost</th>
                                <th class="px-4 py-3 text-[10px] font-black uppercase tracking-widest text-white/30 text-center">Status</th>
                                <th class="px-4 py-3 text-[10px] font-black uppercase tracking-widest text-white/30 w-20"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <template x-for="v in filtered" :key="v.id">
                                <tr class="hover:bg-white/[0.02] transition-colors">
                                    <td class="px-5 py-3.5 sticky left-0 bg-[#050505] hover:bg-[#070707] transition-colors">
                                        <span class="text-sm font-semibold text-white/80 block" :class="v.status === 'disposed' ? 'line-through text-white/35' : ''" x-text="v.name"></span>
                                        <span class="text-[10px] text-white/25" x-text="v.year + ' ' + v.make + ' ' + v.model"></span>
                                    </td>
                                    <td class="px-4 py-3.5"><span class="font-mono text-xs text-white/40" x-text="v.id"></span></td>
                                    <td class="px-4 py-3.5"><span class="font-mono text-xs text-white/40" x-text="v.plate"></span></td>
                                    <td class="px-4 py-3.5 text-right"><span class="text-xs text-white/40 font-mono" x-text="dateStr(v.purchaseDate)"></span></td>
                                    <td class="px-4 py-3.5 text-right"><span class="text-sm text-white/70 font-mono" x-text="fmt(v.purchaseCost)"></span></td>
                                    <td class="px-4 py-3.5 text-right">
                                        <span class="text-sm font-mono"
                                              :class="v.maintenanceCost > 15000 ? 'text-rose-400' : v.maintenanceCost > 5000 ? 'text-amber-400' : 'text-white/50'"
                                              x-text="fmt(v.maintenanceCost)"></span>
                                    </td>
                                    <td class="px-4 py-3.5 text-center">
                                        <template x-if="v.status === 'active'">
                                            <span class="inline-flex items-center gap-1.5 text-[10px] font-bold text-emerald-400 bg-emerald-500/10 px-2.5 py-0.5 rounded-full border border-emerald-500/20">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>Active
                                            </span>
                                        </template>
                                        <template x-if="v.status === 'maintenance'">
                                            <span class="inline-flex items-center gap-1.5 text-[10px] font-bold text-amber-400 bg-amber-500/10 px-2.5 py-0.5 rounded-full border border-amber-500/20">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>Service
                                            </span>
                                        </template>
                                        <template x-if="v.status === 'disposed'">
                                            <span class="inline-flex items-center gap-1.5 text-[10px] font-bold text-white/25 bg-white/[0.03] px-2.5 py-0.5 rounded-full border border-white/10">
                                                <span class="w-1.5 h-1.5 rounded-full bg-white/15"></span>Disposed
                                            </span>
                                        </template>
                                    </td>
                                    <td class="px-4 py-3.5 text-center">
                                        <div class="flex items-center justify-center gap-0.5 opacity-0 hover:opacity-100 transition-opacity" style="opacity:0"
                                             @mouseenter="$el.style.opacity=1" @mouseleave="$el.style.opacity=0">
                                            <a href="#" class="w-7 h-7 rounded-lg hover:bg-white/10 flex items-center justify-center text-white/30 hover:text-white transition-all"><i class="fa-solid fa-eye text-[10px]"></i></a>
                                            <a href="#" class="w-7 h-7 rounded-lg hover:bg-white/10 flex items-center justify-center text-white/30 hover:text-white transition-all"><i class="fa-solid fa-pen text-[10px]"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Empty -->
                <div x-show="filtered.length === 0" class="py-14 text-center">
                    <i class="fa-solid fa-magnifying-glass text-white/10 text-2xl mb-3 block"></i>
                    <p class="text-white/30 text-sm">No vehicles found</p>
                </div>

                <!-- Footer -->
                <div class="px-5 py-3 border-t border-white/5 bg-white/[0.01] flex justify-between text-xs">
                    <span class="text-white/20" x-text="filtered.length + ' of ' + vehicles.length + ' vehicles'"></span>
                    <div class="flex items-center gap-5">
                        <span class="text-white/20">Purchase: <span class="text-white/60 font-mono font-bold" x-text="fmt(vehicles.reduce((s,v) => s + v.purchaseCost, 0))"></span></span>
                        <span class="text-white/20">Maintenance: <span class="text-amber-400/70 font-mono font-bold" x-text="fmt(vehicles.reduce((s,v) => s + v.maintenanceCost, 0))"></span></span>
                    </div>
                </div>
            </div>

            <div class="h-12"></div>
        </div>

        <!-- Modal -->
        <div x-show="showModal" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            @click.self="showModal = false" class="fixed inset-0 z-[100] bg-black/70 backdrop-blur-sm flex items-center justify-center p-4" style="display:none;">
            <div x-show="showModal" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0 scale-95"
                @click.stop class="w-full max-w-lg bg-[#0c0c0e] border border-white/10 rounded-2xl shadow-2xl overflow-hidden">

                <div class="px-6 py-4 border-b border-white/5 flex items-center justify-between">
                    <h3 class="text-sm font-bold">Add New Vehicle</h3>
                    <button @click="showModal = false" class="w-7 h-7 rounded-lg hover:bg-white/10 flex items-center justify-center text-white/30 hover:text-white transition-all"><i class="fa-solid fa-xmark text-sm"></i></button>
                </div>

                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] uppercase font-bold text-white/30 tracking-wider block mb-1.5">Vehicle Name</label>
                            <input type="text" x-model="newVehicle.name" placeholder="PUV #5" class="w-full px-3 py-2.5 rounded-xl text-sm">
                        </div>
                        <div>
                            <label class="text-[10px] uppercase font-bold text-white/30 tracking-wider block mb-1.5">Year</label>
                            <input type="number" x-model="newVehicle.year" placeholder="2024" class="w-full px-3 py-2.5 rounded-xl text-sm">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] uppercase font-bold text-white/30 tracking-wider block mb-1.5">Make</label>
                            <input type="text" x-model="newVehicle.make" placeholder="Toyota" class="w-full px-3 py-2.5 rounded-xl text-sm">
                        </div>
                        <div>
                            <label class="text-[10px] uppercase font-bold text-white/30 tracking-wider block mb-1.5">Model</label>
                            <input type="text" x-model="newVehicle.model" placeholder="HiAce Commuter" class="w-full px-3 py-2.5 rounded-xl text-sm">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] uppercase font-bold text-white/30 tracking-wider block mb-1.5">Plate Number</label>
                            <input type="text" x-model="newVehicle.plate" placeholder="MNO345" class="w-full px-3 py-2.5 rounded-xl text-sm font-mono">
                        </div>
                        <div>
                            <label class="text-[10px] uppercase font-bold text-white/30 tracking-wider block mb-1.5">Purchase Date</label>
                            <input type="date" x-model="newVehicle.purchaseDate" class="w-full px-3 py-2.5 rounded-xl text-sm">
                        </div>
                    </div>
                    <div>
                        <label class="text-[10px] uppercase font-bold text-white/30 tracking-wider block mb-1.5">Purchase Cost (₱)</label>
                        <input type="number" x-model="newVehicle.purchaseCost" placeholder="0.00" step="0.01" class="w-full px-3 py-2.5 rounded-xl text-sm font-mono">
                    </div>
                </div>

                <div class="px-6 py-3.5 border-t border-white/5 flex justify-end gap-3">
                    <button @click="showModal = false" class="px-4 py-2 rounded-xl bg-white/[0.04] border border-white/10 hover:bg-white/[0.07] text-white/60 text-sm font-semibold transition-all">Cancel</button>
                    <button @click="addVehicle()" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-sm font-semibold shadow-lg shadow-blue-900/40 transition-all">
                        <i class="fa-solid fa-plus mr-1.5 text-xs"></i>Add Vehicle
                    </button>
                </div>
            </div>
        </div>
    </main>
</body>

</html>
