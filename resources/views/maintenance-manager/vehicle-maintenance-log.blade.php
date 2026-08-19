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

        .modal-scroll::-webkit-scrollbar { width: 4px; }
        .modal-scroll::-webkit-scrollbar-track { background: transparent; }
        .modal-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 999px; }

        .modal-backdrop {
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }

        [x-cloak] { display: none !important; }

        .modal-panel {
            animation: modalIn 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        @keyframes modalIn {
            0% { opacity: 0; transform: scale(0.95) translateY(10px); }
            100% { opacity: 1; transform: scale(1) translateY(0); }
        }

        .modal-fade {
            animation: fadeIn 0.25s ease-out forwards;
        }
        @keyframes fadeIn {
            0% { opacity: 0; }
            100% { opacity: 1; }
        }

        select {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='rgba(255,255,255,0.3)' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
        }

        .form-input {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            color: #fff;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-input:focus {
            border-color: rgba(59, 130, 246, 0.5);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.08);
        }

        .table-scroll::-webkit-scrollbar { width: 4px; }
        .table-scroll::-webkit-scrollbar-track { background: transparent; }
        .table-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.08); border-radius: 999px; }

        .col-header {
            position: relative;
            padding-left: 14px;
        }
        .col-header::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 3px;
            height: 60%;
            border-radius: 2px;
            background: #3b82f6;
        }

        .input-error {
            border-color: rgba(239, 68, 68, 0.5) !important;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.08) !important;
        }
    </style>
</head>

<script>
    document.getElementById('fleet-selector')?.addEventListener('change', function () {
        const url = new URL(window.location.href);
        url.searchParams.set('vm_id', this.value);
        window.location.href = url.toString();
    });
</script>

<body x-data="{
    open: true,
    showModal: false,
    showEditModal: false,
    editLog: {
        id: null,
        service_date: '',
        mileage_at_service: '',
        maintenance_task_id: '',
        performed_by: '',
        cost: '',
        invoice_number: '',
        remarks: '',
        vm_id: ''
    },
    openEdit(log) {
        this.editLog = {
            id: log.id,
            service_date: log.service_date_formatted,
            mileage_at_service: log.mileage_at_service,
            maintenance_task_id: String(log.maintenance_task_id),
            performed_by: log.performed_by,
            cost: log.cost,
            invoice_number: log.invoice_number || '',
            remarks: log.remarks || '',
            vm_id: log.vm_id
        };
        this.showEditModal = true;
    }
}" :class="(showModal || showEditModal) ? 'overflow-hidden' : ''">
    @include('components.flash');
    @include('maintenance-manager.layout.sidebar');

    <main :class="open ? 'ml-72' : 'ml-20'" x-data="{ editing: false, search: '' }"
        class="sidebar-transition p-8 md:p-12 min-h-screen">

        <div class="max-w-[1400px] mx-auto flex-1 flex flex-col min-h-0">

            <!-- Page Header -->
            <header class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6 shrink-0">
                <div>
                    <h2 class="text-3xl font-black tracking-tight mb-1">Fleet Maintenance <span class="text-blue-500">Log</span></h2>
                    <p class="text-white/40 text-sm">Detailed history, costs, and service records.</p>
                </div>
                <div class="flex gap-3 self-start">
                    <select id="fleet-selector"
                            class="bg-[#0a0a0a] border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:ring-1 focus:ring-blue-500/50 outline-none appearance-none cursor-pointer pr-10">
                        @foreach($fleets as $f)
                            <option value="{{ $f->id }}" {{ $fleet->id === $f->id ? 'selected' : '' }}>
                                {{ $f->vehicle?->plate_number }} ({{ $f->vehicle?->brand }} {{ $f->vehicle?->model }})
                            </option>
                        @endforeach
                    </select>
                    <input type="text" x-model="search" placeholder="Search logs..." class="bg-[#0a0a0a] border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:ring-1 focus:ring-blue-500/50 outline-none w-48">
                </div>
            </header>

            <!-- TOP SECTION: Info & Guide -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6 shrink-0">

                <!-- LEFT: Fleet Info & Cost Summary -->
                <div class="lg:col-span-2 glass rounded-[2rem] p-6 md:p-8 border border-white/5">
                    <div class="flex items-start justify-between mb-6">
                        <div>
                            <h3 class="text-lg font-bold text-white mb-3">Fleet Information</h3>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-x-8 gap-y-3 text-sm">
                                <div>
                                    <span class="text-[10px] uppercase font-bold text-white/30 block">Plate Number</span>
                                    <span class="text-white/80 font-medium font-mono">{{ $fleet?->vehicle?->plate_number ?? '—' }}</span>
                                </div>
                                <div>
                                    <span class="text-[10px] uppercase font-bold text-white/30 block">Year</span>
                                    <span class="text-white/80 font-medium">{{ $fleet?->vehicle?->year ?? '—' }}</span>
                                </div>
                                <div>
                                    <span class="text-[10px] uppercase font-bold text-white/30 block">Brand / Model</span>
                                    <span class="text-white/80 font-medium">{{ $fleet?->vehicle?->brand }} {{ $fleet?->vehicle?->model }}</span>
                                </div>
                                <div>
                                    <span class="text-[10px] uppercase font-bold text-white/30 block">Driver</span>
                                    <span class="text-white/80 font-medium">{{ $fleet?->vehicle?->driver?->name ?? 'Unassigned' }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="text-right bg-blue-500/5 border border-blue-500/20 rounded-xl px-5 py-3">
                            <span class="text-[10px] uppercase font-bold text-blue-400 block">Total Costs</span>
                            <span class="text-2xl font-black text-white">₱ {{ number_format($totalCost, 2) }}</span>
                        </div>
                    </div>

                    <!-- Cost Per Kilometer Summary -->
                    <div class="border-t border-white/5 pt-5">
                        <h4 class="text-[10px] uppercase font-black text-white/30 tracking-widest mb-3">Cost Per Kilometer Summary</h4>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                            <div class="bg-white/[0.02] rounded-lg p-3 border border-white/5">
                                <span class="text-[10px] text-white/30 block mb-1">Current Odometer</span>
                                <span class="text-white font-bold font-mono">{{ number_format($latestOdometer) }} km</span>
                            </div>
                            <div class="bg-white/[0.02] rounded-lg p-3 border border-white/5">
                                <span class="text-[10px] text-white/30 block mb-1">Total Services</span>
                                <span class="text-white font-bold">{{ $totalServices }} {{ $totalServices === 1 ? 'Log' : 'Logs' }}</span>
                            </div>
                            <div class="bg-white/[0.02] rounded-lg p-3 border border-white/5">
                                <span class="text-[10px] text-white/30 block mb-1">Avg Cost / Service</span>
                                <span class="text-white font-bold font-mono">₱ {{ $totalServices > 0 ? number_format($totalCost / $totalServices, 2) : '0.00' }}</span>
                            </div>
                            <div class="bg-white/[0.02] rounded-lg p-3 border border-white/5">
                                <span class="text-[10px] text-white/30 block mb-1">Cost / Kilometer</span>
                                <span class="text-emerald-400 font-bold font-mono">₱ {{ number_format($costPerMile, 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT: Maintenance Guide & Log Button -->
                <div class="glass rounded-[2rem] p-6 border border-white/5 flex flex-col gap-6">
                    <a rel="noopener noreferrer" target="_blank" href="https://www.edmunds.com/car-maintenance/guide-page.html" class="flex items-center gap-4 p-4 rounded-xl bg-white/[0.02] border border-white/5 hover:bg-white/[0.04] hover:border-white/10 transition-all group">
                        <div class="w-12 h-12 rounded-xl bg-blue-500/10 flex items-center justify-center text-blue-500 group-hover:bg-blue-500/20 transition-colors shrink-0">
                            <i class="fa-solid fa-book-open text-lg"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <span class="text-sm font-bold text-white/90 block group-hover:text-white transition-colors">Maintenance Guide</span>
                            <span class="text-xs text-white/40 flex items-center gap-1">View scheduled services <i class="fa-solid fa-arrow-right text-[10px] group-hover:translate-x-1 transition-transform"></i></span>
                        </div>
                    </a>

                    <button @click="showModal = true" class="w-full py-3.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-sm font-bold shadow-lg shadow-blue-900/40 transition-all flex items-center justify-center gap-2">
                        <i class="fa-solid fa-plus"></i> Log New Service
                    </button>
                </div>
            </div>

            <!-- BOTTOM SECTION: Log Table -->
            <div class="glass rounded-[2rem] border border-white/5 flex-1 flex flex-col overflow-hidden">

                <div class="overflow-y-auto flex-1 table-scroll">
                    <table class="w-full text-left">
                        <thead class="sticky top-0 z-10">
                            <tr class="border-b border-white/5 bg-[#0a0a0a]">
                                <th class="px-5 py-4 text-[10px] font-black uppercase tracking-[0.15em] text-blue-500 w-[15%]">Date & Odo</th>
                                <th class="px-5 py-4 text-[10px] font-black uppercase tracking-[0.15em] text-blue-500 w-[30%]">Service Details</th>
                                <th class="px-5 py-4 text-[10px] font-black uppercase tracking-[0.15em] text-blue-500 w-[25%]">Cost Breakdown</th>
                                <th class="px-5 py-4 text-[10px] font-black uppercase tracking-[0.15em] text-blue-500 w-[20%]">Notes</th>
                                <th class="px-5 py-4 text-[10px] font-black uppercase tracking-[0.15em] text-blue-500 w-[10%] text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @forelse($logs as $log)
                                <tr class="hover:bg-white/[0.02] transition-colors" x-show="!search || $el.textContent.toLowerCase().includes(search.toLowerCase())" x-cloak>
                                    <td class="px-5 py-4">
                                        <div class="text-sm text-white/70 font-medium">{{ $log->service_date?->format('M d, Y') ?? '—' }}</div>
                                        <div class="text-xs text-white/30 font-mono mt-0.5">{{ number_format($log->mileage_at_service) }} km</div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="text-sm text-white font-semibold">{{ $log->maintenanceTask?->tasks_performed ?? 'Unknown Task' }}</div>
                                        <div class="text-xs text-white/40 mt-0.5">{{ $log->performed_by ?? '—' }}</div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="text-sm text-white font-bold font-mono">₱ {{ number_format($log->cost, 2) }}</div>
                                        @if($log->cost == 0)
                                            <div class="text-[10px] text-emerald-400 mt-0.5">No charge</div>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4">
                                        @if($log->remarks && str($log->remarks)->lower()->contains('warranty'))
                                            <span class="inline-block text-xs text-emerald-400 bg-emerald-500/5 px-2 py-1 rounded border border-emerald-500/10">Under Warranty</span>
                                        @elseif($log->remarks)
                                            <span class="text-xs text-white/30">{{ \Illuminate\Support\Str::limit($log->remarks, 40) }}</span>
                                        @else
                                            <span class="text-sm text-white/30">—</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <div class="flex items-center justify-center gap-1">
                                            <button @click="openEdit(@js($log->only('id', 'vm_id', 'service_date_formatted', 'mileage_at_service', 'maintenance_task_id', 'performed_by', 'cost', 'invoice_number', 'remarks')))" class="w-8 h-8 rounded-lg hover:bg-white/[0.06] flex items-center justify-center text-white/30 hover:text-blue-400 transition-colors" title="Edit">
                                                <i class="fa-solid fa-pen text-xs"></i>
                                            </button>
                                            <form method="POST" :action="`{{ route('maintenance-manager.vehicle-maintenance-log.destroy', '__ID__') }}`.replace('__ID__', {{ $log->id }})" class="inline-flex">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" onclick="return confirm('Delete this maintenance log?')" class="w-8 h-8 rounded-lg hover:bg-rose-500/[0.08] flex items-center justify-center text-white/30 hover:text-rose-400 transition-colors" title="Delete">
                                                    <i class="fa-solid fa-trash-can text-xs"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-16 text-center">
                                        <div class="flex flex-col items-center gap-3">
                                            <div class="w-14 h-14 rounded-2xl bg-white/[0.03] border border-white/5 flex items-center justify-center text-white/15">
                                                <i class="fa-solid fa-clipboard-list text-xl"></i>
                                            </div>
                                            <p class="text-sm text-white/30">No maintenance logs recorded yet.</p>
                                            <button @click="showModal = true" class="text-xs text-blue-400 hover:text-blue-300 transition-colors flex items-center gap-1">
                                                <i class="fa-solid fa-plus text-[10px]"></i> Log first service
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Pagination Pinned to Bottom -->
        <div class="-mx-8 md:-mx-12 -mb-8 md:-mb-12 mt-4 px-8 md:px-12 py-4 bg-[#0a0a0a] border-t border-white/5 flex justify-between items-center text-xs text-white/30 shrink-0">
            <span>Showing {{ $totalServices }} {{ $totalServices === 1 ? 'entry' : 'entries' }}</span>
            <div class="flex gap-2">
                <button class="px-3 py-1 rounded border border-white/10 hover:bg-white/5 transition opacity-50 cursor-not-allowed" disabled>Prev</button>
                <button class="px-3 py-1 rounded border border-blue-500/30 bg-blue-500/10 text-blue-400">1</button>
                <button class="px-3 py-1 rounded border border-white/10 hover:bg-white/5 transition opacity-50 cursor-not-allowed" disabled>Next</button>
            </div>
        </div>

    </main>

    <!-- ═══════════════════════════════════════════════════════════ -->
    <!-- MODAL: Log New Service -->
    <!-- ═══════════════════════════════════════════════════════════ -->
    <template x-teleport="body">
        <div x-show="showModal"
             x-cloak
             @keydown.escape.window="showModal = false"
             class="fixed inset-0 z-[100] flex items-center justify-center p-4 modal-backdrop bg-black/60"
             role="dialog" aria-modal="true" aria-labelledby="modal-title">

            <div x-show="showModal" @click="showModal = false" class="absolute inset-0 modal-fade"></div>

            <form x-show="showModal"
                  method="POST"
                  action="{{ route('maintenance-manager.vehicle-maintenance-log.store') }}"
                  class="relative w-full max-w-[680px] bg-[#0e0e0e] border border-white/[0.08] rounded-[2rem] shadow-2xl shadow-black/60 flex flex-col max-h-[90vh] overflow-hidden modal-panel">

                @csrf

                <input type="hidden" name="vm_id" value="{{ $fleet->id }}">

                <!-- Header -->
                <div class="flex items-center justify-between px-8 pt-7 pb-5 border-b border-white/5 shrink-0">
                    <div class="flex items-center gap-4">
                        <div class="w-11 h-11 rounded-2xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400">
                            <i class="fa-solid fa-plus text-sm"></i>
                        </div>
                        <div>
                            <h3 id="modal-title" class="text-lg font-black tracking-tight">Log New Service</h3>
                            <p class="text-xs text-white/30 mt-0.5">{{ $fleet?->vehicle?->plate_number }} &middot; {{ $fleet?->vehicle?->brand }} {{ $fleet?->vehicle?->model }} ({{ $fleet?->vehicle?->year }})</p>
                        </div>
                    </div>
                    <button type="button" @click="showModal = false"
                        class="w-9 h-9 rounded-xl bg-white/5 hover:bg-white/10 flex items-center justify-center text-white/40 hover:text-white transition-colors"
                        aria-label="Close modal">
                        <i class="fa-solid fa-xmark text-sm"></i>
                    </button>
                </div>

                <!-- Pill strip -->
                <div class="px-8 pt-5 pb-0 shrink-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-[9px] uppercase font-bold tracking-[0.12em] text-blue-400/60 bg-blue-500/5 border border-blue-500/10 px-2.5 py-1 rounded-md">Date</span>
                        <span class="text-[9px] uppercase font-bold tracking-[0.12em] text-blue-400/60 bg-blue-500/5 border border-blue-500/10 px-2.5 py-1 rounded-md">Odometer</span>
                        <span class="text-[9px] uppercase font-bold tracking-[0.12em] text-blue-400/60 bg-blue-500/5 border border-blue-500/10 px-2.5 py-1 rounded-md">Work Performed</span>
                        <span class="text-[9px] uppercase font-bold tracking-[0.12em] text-blue-400/60 bg-blue-500/5 border border-blue-500/10 px-2.5 py-1 rounded-md">Performed By</span>
                        <span class="text-[9px] uppercase font-bold tracking-[0.12em] text-blue-400/60 bg-blue-500/5 border border-blue-500/10 px-2.5 py-1 rounded-md">Cost</span>
                        <span class="text-[9px] uppercase font-bold tracking-[0.12em] text-blue-400/60 bg-blue-500/5 border border-blue-500/10 px-2.5 py-1 rounded-md">Invoice #</span>
                        <span class="text-[9px] uppercase font-bold tracking-[0.12em] text-blue-400/60 bg-blue-500/5 border border-blue-500/10 px-2.5 py-1 rounded-md">Remarks</span>
                    </div>
                </div>

                <!-- Body -->
                <div class="flex-1 overflow-y-auto modal-scroll px-8 py-6 space-y-5">

                    @if ($errors->any())
                        <div class="bg-red-500/5 border border-red-500/20 text-red-400 text-xs font-medium px-4 py-3 rounded-xl flex items-start gap-2">
                            <i class="fa-solid fa-circle-exclamation shrink-0 mt-0.5"></i>
                            <ul class="list-disc list-inside space-y-0.5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Row 1: Service Date + Odometer -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="service_date" class="col-header block text-[10px] uppercase font-bold text-white/40 tracking-widest mb-2">
                                Service Date <span class="text-red-400/70">*</span>
                            </label>
                            <input id="service_date"
                                   type="date"
                                   name="service_date"
                                   value="{{ old('service_date') }}"
                                   required
                                   class="form-input w-full rounded-xl px-4 py-3 text-sm [color-scheme:dark] {{ $errors->has('service_date') ? 'input-error' : '' }}">
                        </div>
                        <div>
                            <label for="mileage_at_service" class="col-header block text-[10px] uppercase font-bold text-white/40 tracking-widest mb-2">
                                Odometer (km) <span class="text-red-400/70">*</span>
                            </label>
                            <input id="mileage_at_service"
                                   type="number"
                                   name="mileage_at_service"
                                   value="{{ old('mileage_at_service') }}"
                                   min="0"
                                   placeholder="e.g. 21000"
                                   required
                                   class="form-input w-full rounded-xl px-4 py-3 text-sm placeholder:text-white/15 font-mono {{ $errors->has('mileage_at_service') ? 'input-error' : '' }}">
                        </div>
                    </div>

                    <!-- Row 2: Work Performed -->
                    <div>
                        <label for="maintenance_task_id" class="col-header block text-[10px] uppercase font-bold text-white/40 tracking-widest mb-2">
                            Work Performed <span class="text-red-400/70">*</span>
                        </label>
                        <select id="maintenance_task_id"
                                name="maintenance_task_id"
                                required
                                class="form-input w-full rounded-xl px-4 py-3 text-sm appearance-none cursor-pointer pr-10 {{ $errors->has('maintenance_task_id') ? 'input-error' : '' }}">
                            <option value="" disabled {{ !old('maintenance_task_id') ? 'selected' : '' }}>Select a task...</option>
                            @foreach($maintenanceTasks as $task)
                                <option value="{{ $task->id }}" {{ old('maintenance_task_id') == $task->id ? 'selected' : '' }}>
                                    {{ $task->tasks_performed }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Row 3: Performed By -->
                    <div>
                        <label for="performed_by" class="col-header block text-[10px] uppercase font-bold text-white/40 tracking-widest mb-2">
                            Performed By <span class="text-red-400/70">*</span>
                        </label>
                        <input id="performed_by"
                               type="text"
                               name="performed_by"
                               value="{{ old('performed_by') }}"
                               placeholder="e.g. Castrol Shop, In-House"
                               required
                               class="form-input w-full rounded-xl px-4 py-3 text-sm placeholder:text-white/15 {{ $errors->has('performed_by') ? 'input-error' : '' }}">
                    </div>

                    <!-- Row 4: Cost + Invoice Number -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="cost" class="col-header block text-[10px] uppercase font-bold text-white/40 tracking-widest mb-2">
                                Cost <span class="text-red-400/70">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-xs text-white/20 font-mono">₱</span>
                                <input id="cost"
                                       type="number"
                                       name="cost"
                                       value="{{ old('cost') }}"
                                       min="0"
                                       step="0.01"
                                       placeholder="0.00"
                                       required
                                       class="form-input w-full rounded-xl pl-9 pr-4 py-3 text-sm placeholder:text-white/15 font-mono {{ $errors->has('cost') ? 'input-error' : '' }}">
                            </div>
                            <p class="text-[10px] text-white/20 mt-1.5 pl-1">Leave 0 if covered under warranty.</p>
                        </div>
                        <div>
                            <label for="invoice_number" class="col-header block text-[10px] uppercase font-bold text-white/40 tracking-widest mb-2">
                                Invoice Number
                            </label>
                            <input id="invoice_number"
                                   type="text"
                                   name="invoice_number"
                                   value="{{ old('invoice_number') }}"
                                   placeholder="e.g. INV-0050"
                                   class="form-input w-full rounded-xl px-4 py-3 text-sm placeholder:text-white/15 font-mono {{ $errors->has('invoice_number') ? 'input-error' : '' }}">
                        </div>
                    </div>

                    <!-- Row 5: Remarks -->
                    <div>
                        <label for="remarks" class="col-header block text-[10px] uppercase font-bold text-white/40 tracking-widest mb-2">
                            Remarks
                        </label>
                        <textarea id="remarks"
                                  name="remarks"
                                  rows="3"
                                  placeholder="e.g. Covered under warranty, Parts replaced: front brake pads (OEM)..."
                                  class="form-input w-full rounded-xl px-4 py-3 text-sm placeholder:text-white/15 resize-none leading-relaxed {{ $errors->has('remarks') ? 'input-error' : '' }}">{{ old('remarks') }}</textarea>
                    </div>
                </div>

                <!-- Footer -->
                <div class="flex items-center justify-end gap-3 px-8 py-5 border-t border-white/5 shrink-0 bg-[#0a0a0a]/60">
                    <button type="button" @click="showModal = false"
                        class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white/50 hover:text-white/80 hover:bg-white/5 transition-all">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-sm font-bold shadow-lg shadow-blue-900/40 transition-all flex items-center gap-2 active:scale-[0.97]">
                        <i class="fa-solid fa-check text-xs"></i>
                        Save Entry
                    </button>
                </div>
            </form>
        </div>
    </template>

    <!-- ═══════════════════════════════════════════════════════════ -->
    <!-- MODAL: Edit Service -->
    <!-- ═══════════════════════════════════════════════════════════ -->
    <template x-teleport="body">
        <div x-show="showEditModal"
             x-cloak
             @keydown.escape.window="showEditModal = false"
             class="fixed inset-0 z-[100] flex items-center justify-center p-4 modal-backdrop bg-black/60"
             role="dialog" aria-modal="true">

            <div x-show="showEditModal" @click="showEditModal = false" class="absolute inset-0 modal-fade"></div>

            <form x-show="showEditModal"
                  method="POST"
                  :action="`{{ route('maintenance-manager.vehicle-maintenance-log.update', 0) }}`.replace('/0', '/' + editLog.id)"
                  class="relative w-full max-w-[680px] bg-[#0e0e0e] border border-white/[0.08] rounded-[2rem] shadow-2xl shadow-black/60 flex flex-col max-h-[90vh] overflow-hidden modal-panel">

                @csrf
                @method('PATCH')

                <input type="hidden" name="vm_id" x-model="editLog.vm_id">

                <!-- Header -->
                <div class="flex items-center justify-between px-8 pt-7 pb-5 border-b border-white/5 shrink-0">
                    <div class="flex items-center gap-4">
                        <div class="w-11 h-11 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400">
                            <i class="fa-solid fa-pen text-sm"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-black tracking-tight">Edit Service Log</h3>
                            <p class="text-xs text-white/30 mt-0.5">
                                {{ $fleet?->vehicle?->plate_number }} &middot; {{ $fleet?->vehicle?->brand }} {{ $fleet?->vehicle?->model }} ({{ $fleet?->vehicle?->year }})
                            </p>
                        </div>
                    </div>
                    <button type="button" @click="showEditModal = false"
                        class="w-9 h-9 rounded-xl bg-white/5 hover:bg-white/10 flex items-center justify-center text-white/40 hover:text-white transition-colors"
                        aria-label="Close modal">
                        <i class="fa-solid fa-xmark text-sm"></i>
                    </button>
                </div>

                <!-- Pill strip -->
                <div class="px-8 pt-5 pb-0 shrink-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-[9px] uppercase font-bold tracking-[0.12em] text-amber-400/60 bg-amber-500/5 border border-amber-500/10 px-2.5 py-1 rounded-md">Date</span>
                        <span class="text-[9px] uppercase font-bold tracking-[0.12em] text-amber-400/60 bg-amber-500/5 border border-amber-500/10 px-2.5 py-1 rounded-md">Odometer</span>
                        <span class="text-[9px] uppercase font-bold tracking-[0.12em] text-amber-400/60 bg-amber-500/5 border border-amber-500/10 px-2.5 py-1 rounded-md">Work Performed</span>
                        <span class="text-[9px] uppercase font-bold tracking-[0.12em] text-amber-400/60 bg-amber-500/5 border border-amber-500/10 px-2.5 py-1 rounded-md">Performed By</span>
                        <span class="text-[9px] uppercase font-bold tracking-[0.12em] text-amber-400/60 bg-amber-500/5 border border-amber-500/10 px-2.5 py-1 rounded-md">Cost</span>
                        <span class="text-[9px] uppercase font-bold tracking-[0.12em] text-amber-400/60 bg-amber-500/5 border border-amber-500/10 px-2.5 py-1 rounded-md">Invoice #</span>
                        <span class="text-[9px] uppercase font-bold tracking-[0.12em] text-amber-400/60 bg-amber-500/5 border border-amber-500/10 px-2.5 py-1 rounded-md">Remarks</span>
                    </div>
                </div>

                <!-- Body -->
                <div class="flex-1 overflow-y-auto modal-scroll px-8 py-6 space-y-5">

                    @if ($errors->any())
                        <div class="bg-red-500/5 border border-red-500/20 text-red-400 text-xs font-medium px-4 py-3 rounded-xl flex items-start gap-2">
                            <i class="fa-solid fa-circle-exclamation shrink-0 mt-0.5"></i>
                            <ul class="list-disc list-inside space-y-0.5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Service Date + Odometer -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="col-header block text-[10px] uppercase font-bold text-white/40 tracking-widest mb-2">
                                Service Date <span class="text-red-400/70">*</span>
                            </label>
                            <input type="date"
                                   name="service_date"
                                   x-model="editLog.service_date"
                                   required
                                   class="form-input w-full rounded-xl px-4 py-3 text-sm [color-scheme:dark]">
                        </div>
                        <div>
                            <label class="col-header block text-[10px] uppercase font-bold text-white/40 tracking-widest mb-2">
                                Odometer (km) <span class="text-red-400/70">*</span>
                            </label>
                            <input type="number"
                                   name="mileage_at_service"
                                   x-model="editLog.mileage_at_service"
                                   min="0"
                                   required
                                   class="form-input w-full rounded-xl px-4 py-3 text-sm placeholder:text-white/15 font-mono">
                        </div>
                    </div>

                    <!-- Work Performed -->
                    <div>
                        <label class="col-header block text-[10px] uppercase font-bold text-white/40 tracking-widest mb-2">
                            Work Performed <span class="text-red-400/70">*</span>
                        </label>
                        <select name="maintenance_task_id"
                                x-model="editLog.maintenance_task_id"
                                required
                                class="form-input w-full rounded-xl px-4 py-3 text-sm appearance-none cursor-pointer pr-10">
                            <option value="" disabled>Select a task...</option>
                            @foreach($maintenanceTasks as $task)
                                <option value="{{ $task->id }}">{{ $task->tasks_performed }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Performed By -->
                    <div>
                        <label class="col-header block text-[10px] uppercase font-bold text-white/40 tracking-widest mb-2">
                            Performed By <span class="text-red-400/70">*</span>
                        </label>
                        <input type="text"
                               name="performed_by"
                               x-model="editLog.performed_by"
                               required
                               class="form-input w-full rounded-xl px-4 py-3 text-sm placeholder:text-white/15">
                    </div>

                    <!-- Cost + Invoice -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="col-header block text-[10px] uppercase font-bold text-white/40 tracking-widest mb-2">
                                Cost <span class="text-red-400/70">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-xs text-white/20 font-mono">₱</span>
                                <input type="number"
                                       name="cost"
                                       x-model="editLog.cost"
                                       min="0"
                                       required
                                       class="form-input w-full rounded-xl pl-9 pr-4 py-3 text-sm placeholder:text-white/15 font-mono">
                            </div>
                        </div>
                        <div>
                            <label class="col-header block text-[10px] uppercase font-bold text-white/40 tracking-widest mb-2">
                                Invoice Number
                            </label>
                            <input type="text"
                                   name="invoice_number"
                                   x-model="editLog.invoice_number"
                                   class="form-input w-full rounded-xl px-4 py-3 text-sm placeholder:text-white/15 font-mono">
                        </div>
                    </div>

                    <!-- Remarks -->
                    <div>
                        <label class="col-header block text-[10px] uppercase font-bold text-white/40 tracking-widest mb-2">
                            Remarks
                        </label>
                        <textarea name="remarks"
                                  x-model="editLog.remarks"
                                  rows="3"
                                  class="form-input w-full rounded-xl px-4 py-3 text-sm placeholder:text-white/15 resize-none leading-relaxed"></textarea>
                    </div>
                </div>

                <!-- Footer -->
                <div class="flex items-center justify-end gap-3 px-8 py-5 border-t border-white/5 shrink-0 bg-[#0a0a0a]/60">
                    <button type="button" @click="showEditModal = false"
                        class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white/50 hover:text-white/80 hover:bg-white/5 transition-all">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-6 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-500 text-white text-sm font-bold shadow-lg shadow-amber-900/40 transition-all flex items-center gap-2 active:scale-[0.97]">
                        <i class="fa-solid fa-check text-xs"></i>
                        Update Entry
                    </button>
                </div>
            </form>
        </div>
    </template>

</body>
</html>
