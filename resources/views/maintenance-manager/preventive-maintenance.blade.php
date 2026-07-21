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

        [x-cloak] { display: none !important; }

        .glass {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .sidebar-transition {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
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

        .modal-backdrop {
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }

        .modal-panel {
            animation: modalIn 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        @keyframes modalIn {
            0% { opacity: 0; transform: scale(0.95) translateY(10px); }
            100% { opacity: 1; transform: scale(1) translateY(0); }
        }
    </style>
</head>

<script>
    document.getElementById('fleet-selector')?.addEventListener('change', function () {
        const url = new URL(window.location.href);
        url.searchParams.set('fleet_id', this.value);
        window.location.href = url.toString();
    });
</script>

<body x-data="{
    open: true,
    showModal: false,
    taskName: '',
    form: {
        fleet_id: '{{ $fleet?->id ?? "" }}',
        task_id: '',
        last_service_odo: '',
        last_service_date: '',
        last_service_cost: '',
        comments: ''
    },
    openLogModal(taskId, name) {
        this.form = {
            fleet_id: '{{ $fleet?->id ?? "" }}',
            task_id: String(taskId),
            last_service_odo: '',
            last_service_date: '',
            last_service_cost: '',
            comments: ''
        };
        this.taskName = name;
        this.showModal = true;
    }
}" :class="showModal ? 'overflow-hidden' : ''">

    @include('components.flash')
    @include('maintenance-manager.layout.sidebar');

    <main :class="open ? 'ml-72' : 'ml-20'"
        class="sidebar-transition p-8 md:p-12 min-h-screen">

      <div class="max-w-[1600px] mx-auto space-y-6">

            <!-- Header -->
            <header class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-2">
                <div>
                    <h2 class="text-3xl font-black tracking-tight mb-1">Preventive <span class="text-blue-500">Maintenance</span></h2>
                    <p class="text-white/40 text-sm">Track service intervals and vehicle health.</p>
                </div>
            </header>

            @if(!$fleet)
                <div class="flex flex-col items-center justify-center py-32">
                    <div class="w-16 h-16 rounded-2xl bg-white/[0.03] border border-white/5 flex items-center justify-center text-white/15 mb-4">
                        <i class="fa-solid fa-car text-2xl"></i>
                    </div>
                    <p class="text-white/30 text-sm mb-1">No fleet entries found.</p>
                    <p class="text-white/15 text-xs">Add a fleet entry first to track preventive maintenance.</p>
                </div>
            @else

            <!-- TOP SECTION: Vehicle Overview -->
            <div class="glass rounded-[2rem] border border-white/5 overflow-hidden">

                <!-- Controls Bar -->
                <div class="p-6 border-b border-white/5 flex flex-col md:flex-row gap-4 justify-between bg-white/[0.02]">
                    <div class="flex items-center gap-4">
                        <div>
                            <label class="text-[10px] uppercase font-bold text-white/30 tracking-wider block mb-1">Select Vehicle</label>
                            <select id="fleet-selector" class="bg-[#0a0a0a] border border-white/10 text-white text-sm rounded-lg px-4 py-2.5 focus:ring-1 focus:ring-blue-500/50 outline-none appearance-none cursor-pointer pr-10">
                                @foreach($fleets as $f)
                                    <option value="{{ $f->id }}" {{ $fleet->id === $f->id ? 'selected' : '' }}>
                                        {{ $f->vehicle?->plate_number }} - {{ $f->vehicle?->brand }} {{ $f->vehicle?->model }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-[10px] uppercase font-bold text-white/30 tracking-wider block mb-1">Today's Date</label>
                            <div class="bg-[#0a0a0a] border border-white/10 text-white/50 text-sm rounded-lg px-4 py-2.5 font-mono">
                                {{ now()->format('m / d / Y') }}
                            </div>
                        </div>
                    </div>
                    <div class="flex items-end">
                        <span class="text-xs text-blue-400 font-semibold cursor-pointer hover:underline">See all logged & unlogged services <i class="fa-solid fa-arrow-up-right-from-square ml-1 text-[10px]"></i></span>
                    </div>
                </div>

                <!-- Overview Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left whitespace-nowrap">
                        <thead>
                            <tr class="border-b border-white/5 bg-white/[0.02]">
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.15em] text-blue-500">Service Item</th>
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.15em] text-white/40">Last Service</th>
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.15em] text-white/40">Frequency</th>
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.15em] text-white/40">Next Service Due</th>
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.15em] text-white/40">Cost</th>
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.15em] text-white/40">Remarks</th>
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-[0.15em] text-white/40 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @forelse($allTasks as $task)
                                @php
    $logged = $loggedTasks->get($task->id);
    $isOverdue = false;
    $nextOdo = null;
    $nextDate = null;

    if ($logged) {
        $nextOdo = $logged->last_service_odo + ($task->miles_between_service ?? 0);
        $nextDate = $logged->last_service_date ? \Carbon\Carbon::parse($logged->last_service_date)->addMonths($task->months_between_service ?? 0) : null;
        if ($nextDate && $nextDate->isPast()) {
            $isOverdue = true;
        }
    }
@endphp

                                <!-- Logged / Overdue Row -->
                                <tr class="hover:bg-white/[0.02] transition-colors {{ !$logged ? 'bg-amber-500/[0.02]' : ($isOverdue ? 'bg-rose-500/[0.02]' : '') }}">
                                    <td class="px-6 py-4 font-semibold text-white">{{ $task->tasks_performed }}</td>

                                    <td class="px-6 py-4">
                                        @if($logged)
                                            <div class="text-sm text-white/80 font-mono">{{ number_format($logged->last_service_odo) }} km</div>
                                            <div class="text-xs text-white/30">{{ $logged->last_service_date ? $logged->last_service_date->format('M d, Y') : '—' }}</div>
                                        @else
                                            <div class="text-sm text-white/30 italic">Not logged</div>
                                        @endif
                                    </td>

                                    <td class="px-6 py-4">
    <div class="flex gap-2">
        @if($task->miles_between_service)
            <span class="px-2 py-0.5 rounded bg-white/5 text-white/50 text-xs font-medium">{{ number_format($task->miles_between_service) }} km</span>
        @else
            <span class="px-2 py-0.5 rounded bg-white/5 text-white/20 text-xs font-medium">N/A</span>
        @endif
        @if($task->months_between_service)
            <span class="px-2 py-0.5 rounded bg-white/5 text-white/50 text-xs font-medium">{{ $task->months_between_service }} mo</span>
        @else
            <span class="px-2 py-0.5 rounded bg-white/5 text-white/20 text-xs font-medium">N/A</span>
        @endif
    </div>
</td>
                                    <td class="px-6 py-4">
                                        @if($logged)
                                            <div class="text-sm font-mono font-semibold {{ $isOverdue ? 'text-rose-400' : 'text-emerald-400' }}">
                                                {{ $isOverdue ? 'Overdue' : number_format($nextOdo) . ' km' }}
                                            </div>
                                            @if($nextDate)
                                                <div class="text-xs {{ $isOverdue ? 'text-rose-400/60' : 'text-white/40' }}">
                                                    {{ $nextDate->format('M d, Y') }}
                                                </div>
                                            @endif
                                        @else
                                            <div class="text-sm text-amber-400 font-mono font-semibold">Requires logging</div>
                                        @endif
                                    </td>

                                    <td class="px-6 py-4">
                                        @if($logged)
                                            <div class="text-sm text-white/60 font-mono">₱ {{ number_format($logged->last_service_cost, 2) }}</div>
                                        @else
                                            <div class="text-sm text-white/20">—</div>
                                        @endif
                                    </td>

                                    <td class="px-6 py-4 text-sm @if(!$logged) text-amber-400/70 @elseif($isOverdue) text-rose-400/70 @else text-white/40 @endif">
                                        @if($logged && !$isOverdue)
                                            —
                                        @elseif($logged && $isOverdue)
                                            Past due date
                                        @else
                                            Unlogged service
                                        @endif
                                    </td>

                                    <td class="px-6 py-4 text-center">
                                        <button @click="openLogModal({{ $task->id }}, '{{ $task->tasks_performed }}')"
                                                class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all @if($logged && !$isOverdue) bg-white/5 text-white/50 hover:bg-white/10 hover:text-white @else bg-blue-600/20 text-blue-400 hover:bg-blue-600/30 border border-blue-500/20 @endif">
                                            <i class="fa-solid fa-pen-to-square mr-1.5 text-[10px]"></i>{{ $logged ? 'Update' : 'Log Service' }}
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-16 text-center text-white/30 text-sm">
                                        No maintenance tasks defined yet. Please add tasks in the settings.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @endif
        </div>
    </main>

    <!-- ═══════════════════════════════════════════════════ -->
    <!-- MODAL: Log / Update Preventive Service           -->
    <!-- ═══════════════════════════════════════════════════ -->
    <template x-teleport="body">
        <div x-show="showModal"
             x-cloak
             @keydown.escape.window="showModal = false"
             class="fixed inset-0 z-[100] flex items-center justify-center p-4 modal-backdrop bg-black/60"
             role="dialog" aria-modal="true">

            <div x-show="showModal" @click="showModal = false" class="absolute inset-0" style="animation: modalIn 0.25s ease forwards;"></div>

            <form x-show="showModal"
                  method="POST"
                  action="{{ route('maintenance-manager.preventive-maintenance.store') }}"
                  @click.stop
                  class="relative w-full max-w-lg bg-[#0e0e0e] border border-white/[0.08] rounded-[2rem] shadow-2xl shadow-black/60 flex flex-col max-h-[90vh] overflow-hidden modal-panel">

                @csrf

                <!-- Hidden Fields -->
                <input type="hidden" name="fleet_id" x-model="form.fleet_id">
                <input type="hidden" name="task_id" x-model="form.task_id">

                <!-- Header -->
                <div class="flex items-center justify-between px-8 pt-7 pb-5 border-b border-white/5 shrink-0">
                    <div class="flex items-center gap-4">
                        <div class="w-11 h-11 rounded-2xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400">
                            <i class="fa-solid fa-wrench text-sm"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-black tracking-tight">Log Service</h3>
                            <p class="text-xs text-white/30 mt-0.5" x-text="taskName"></p>
                        </div>
                    </div>
                    <button type="button" @click="showModal = false"
                        class="w-9 h-9 rounded-xl bg-white/5 hover:bg-white/10 flex items-center justify-center text-white/40 hover:text-white transition-colors"
                        aria-label="Close modal">
                        <i class="fa-solid fa-xmark text-sm"></i>
                    </button>
                </div>

                <!-- Body -->
                <div class="flex-1 overflow-y-auto px-8 py-6 space-y-5">

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

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] uppercase font-bold text-white/40 tracking-widest mb-2 pl-3 relative before:content-[''] before:absolute before:left-0 before:top-1/2 before:-translate-y-1/2 before:w-[3px] before:h-[60%] before:rounded before:bg-blue-500">
                                Odometer (km) <span class="text-red-400/70">*</span>
                            </label>
                            <input type="number"
                                   name="last_service_odo"
                                   x-model="form.last_service_odo"
                                   min="0"
                                   placeholder="e.g. 25000"
                                   required
                                   class="form-input w-full rounded-xl px-4 py-3 text-sm placeholder:text-white/15 font-mono">
                        </div>
                        <div>
                            <label class="block text-[10px] uppercase font-bold text-white/40 tracking-widest mb-2 pl-3 relative before:content-[''] before:absolute before:left-0 before:top-1/2 before:-translate-y-1/2 before:w-[3px] before:h-[60%] before:rounded before:bg-blue-500">
                                Service Date <span class="text-red-400/70">*</span>
                            </label>
                            <input type="date"
                                   name="last_service_date"
                                   x-model="form.last_service_date"
                                   required
                                   class="form-input w-full rounded-xl px-4 py-3 text-sm [color-scheme:dark]">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] uppercase font-bold text-white/40 tracking-widest mb-2 pl-3 relative before:content-[''] before:absolute before:left-0 before:top-1/2 before:-translate-y-1/2 before:w-[3px] before:h-[60%] before:rounded before:bg-blue-500">
                            Service Cost <span class="text-red-400/70">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-xs text-white/20 font-mono">₱</span>
                            <input type="number"
                                   name="last_service_cost"
                                   x-model="form.last_service_cost"
                                   min="0"
                                   step="0.01"
                                   placeholder="0.00"
                                   required
                                   class="form-input w-full rounded-xl pl-9 pr-4 py-3 text-sm placeholder:text-white/15 font-mono">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] uppercase font-bold text-white/40 tracking-widest mb-2 pl-3 relative before:content-[''] before:absolute before:left-0 before:top-1/2 before:-translate-y-1/2 before:w-[3px] before:h-[60%] before:rounded before:bg-blue-500">
                            Comments
                        </label>
                        <textarea name="comments"
                                  x-model="form.comments"
                                  rows="3"
                                  placeholder="e.g. Used OEM parts, next service due at 30,000 km..."
                                  class="form-input w-full rounded-xl px-4 py-3 text-sm placeholder:text-white/15 resize-none leading-relaxed"></textarea>
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
                        Save Log
                    </button>
                </div>
            </form>
        </div>
    </template>

</body>
</html>
