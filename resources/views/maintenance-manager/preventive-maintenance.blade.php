<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartCommute | Preventive Maintenance</title>
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

        select {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='rgba(255,255,255,0.3)' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
        }

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
        .form-input::placeholder {
            color: #2a2a2a;
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

<body class="antialiased text-white" x-data="{
    open: true,
    showLogoutModal: false,
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
    @include('maintenance-manager.layout.sidebar')

    <main :class="open ? 'md:ml-72' : 'md:ml-20'" class="sidebar-transition pt-8 pr-4 sm:pr-8 pb-8 pl-4 sm:pl-8 min-h-screen mb-12">

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
                    <span class="font-mono text-[9px] text-[#444]">Maintenance</span>
                </div>
            </div>
        </div>

        <!-- ── Page Header (desktop) ── -->
        <div class="hidden lg:block mb-8">
            <div class="flex items-center gap-2 mb-1.5">
                <span class="text-[9px] font-bold uppercase tracking-[0.15em] text-[#444]">Service Tracking</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">Preventive <span class="text-blue-500">Maintenance</span></h1>
            <p class="text-[11px] text-[#555] mt-1 flex items-center gap-2">
                <i class="fa-solid fa-wrench text-[9px] text-amber-400"></i>
                Track service intervals and vehicle health
            </p>
        </div>

        @if(!$fleet)
            <div class="flex flex-col items-center justify-center py-32">
                <div class="w-14 h-14 rounded-2xl bg-[#111] border border-[#1e1e1e] flex items-center justify-center mb-4">
                    <i class="fa-solid fa-car text-[#333] text-xl"></i>
                </div>
                <p class="text-[#444] text-[11px] font-bold mb-1">No fleet entries found.</p>
                <p class="text-[#333] text-[10px]">Add a fleet entry first to track preventive maintenance.</p>
            </div>
        @else

        <!-- ══════════ CONTROLS BAR ══════════ -->
        <div class="glass-card rounded-[1.25rem] sm:rounded-[1.5rem] p-4 sm:p-5 mb-5 sm:mb-6">
            <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
                <div class="flex flex-col sm:flex-row gap-3 sm:gap-4 flex-1">
                    <div class="flex-1 sm:max-w-[320px]">
                        <label class="text-[7px] font-bold uppercase tracking-[0.15em] text-[#333] block mb-1.5">Select Vehicle</label>
                        <select id="fleet-selector"
                            class="w-full bg-[#111] border border-[#1e1e1e] rounded-xl px-4 py-2.5 text-[10px] font-bold text-[#888] focus:ring-1 focus:ring-blue-500/50 outline-none appearance-none cursor-pointer pr-10">
                            @foreach($fleets as $f)
                                <option value="{{ $f->id }}" {{ $fleet->id === $f->id ? 'selected' : '' }}>
                                    {{ $f->vehicle?->plate_number }} — {{ $f->vehicle?->brand }} {{ $f->vehicle?->model }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-[7px] font-bold uppercase tracking-[0.15em] text-[#333] block mb-1.5">Today's Date</label>
                        <div class="bg-[#111] border border-[#1e1e1e] rounded-xl px-4 py-2.5 font-mono text-[10px] font-bold text-[#555]">
                            {{ now()->format('m / d / Y') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══════════ SUMMARY BADGES ══════════ -->
        @php
            $totalTasks = $allTasks->count();
            $loggedCount = 0;
            $overdueCount = 0;
            $unloggedCount = 0;
            foreach ($allTasks as $task) {
                $logged = $loggedTasks->get($task->id);
                if ($logged) {
                    $loggedCount++;
                    $nextDate = $logged->last_service_date ? \Carbon\Carbon::parse($logged->last_service_date)->addMonths($task->months_between_service ?? 0) : null;
                    if ($nextDate && $nextDate->isPast()) {
                        $overdueCount++;
                    }
                } else {
                    $unloggedCount++;
                }
            }
            $healthyCount = $loggedCount - $overdueCount;
        @endphp

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4 mb-5 sm:mb-6">
            <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-blue-500">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-6 h-6 rounded-md bg-blue-500/10 flex items-center justify-center">
                        <i class="fa-solid fa-list-check text-[8px] text-blue-400"></i>
                    </div>
                    <span class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Total Tasks</span>
                </div>
                <div class="flex items-baseline gap-1.5">
                    <span class="text-xl sm:text-2xl font-black tracking-tight">{{ $totalTasks }}</span>
                    <span class="text-xs font-bold text-[#555]">items</span>
                </div>
            </div>
            <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-emerald-500">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-6 h-6 rounded-md bg-emerald-500/10 flex items-center justify-center">
                        <i class="fa-solid fa-circle-check text-[8px] text-emerald-400"></i>
                    </div>
                    <span class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Healthy</span>
                </div>
                <div class="flex items-baseline gap-1.5">
                    <span class="text-xl sm:text-2xl font-black tracking-tight text-emerald-400">{{ $healthyCount }}</span>
                    <span class="text-xs font-bold text-[#555]">on track</span>
                </div>
            </div>
            <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-rose-500">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-6 h-6 rounded-md bg-rose-500/10 flex items-center justify-center">
                        <i class="fa-solid fa-triangle-exclamation text-[8px] text-rose-400"></i>
                    </div>
                    <span class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Overdue</span>
                </div>
                <div class="flex items-baseline gap-1.5">
                    <span class="text-xl sm:text-2xl font-black tracking-tight {{ $overdueCount > 0 ? 'text-rose-400' : 'text-[#555]' }}">{{ $overdueCount }}</span>
                    <span class="text-xs font-bold text-[#555]">{{ $overdueCount === 1 ? 'item' : 'items' }}</span>
                </div>
            </div>
            <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-amber-500 col-span-2 sm:col-span-1">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-6 h-6 rounded-md bg-amber-500/10 flex items-center justify-center">
                        <i class="fa-solid fa-clock text-[8px] text-amber-400"></i>
                    </div>
                    <span class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Unlogged</span>
                </div>
                <div class="flex items-baseline gap-1.5">
                    <span class="text-xl sm:text-2xl font-black tracking-tight {{ $unloggedCount > 0 ? 'text-amber-400' : 'text-[#555]' }}">{{ $unloggedCount }}</span>
                    <span class="text-xs font-bold text-[#555]">{{ $unloggedCount === 1 ? 'item' : 'items' }}</span>
                </div>
            </div>
        </div>

        <!-- ══════════ SERVICE TABLE ══════════ -->
        <div class="glass-card rounded-[1.25rem] sm:rounded-[1.5rem] overflow-hidden">
            <div class="p-4 sm:p-6 pb-0">
                <div class="flex items-center justify-between mb-4 sm:mb-5">
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-lg bg-[#111] border border-[#1e1e1e] flex items-center justify-center">
                            <i class="fa-solid fa-clipboard-list text-[9px] text-amber-400"></i>
                        </div>
                        <span class="text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-[#555]">Service Items</span>
                    </div>
                    <span class="text-[7px] sm:text-[8px] font-bold text-[#333] uppercase tracking-widest">{{ $allTasks->count() }} tasks</span>
                </div>
            </div>
            <div class="overflow-x-auto -mx-2 px-2 pb-2">
                <table class="w-full text-left min-w-[900px]">
                    <thead>
                        <tr class="text-[7px] sm:text-[8px] uppercase tracking-[0.15em] text-[#444] border-b border-[#1e1e1e]">
                            <th class="px-4 sm:px-6 py-2.5 font-bold">Service Item</th>
                            <th class="px-4 sm:px-6 py-2.5 font-bold">Last Service</th>
                            <th class="px-4 sm:px-6 py-2.5 font-bold">Frequency</th>
                            <th class="px-4 sm:px-6 py-2.5 font-bold">Next Due</th>
                            <th class="px-4 sm:px-6 py-2.5 font-bold text-right">Cost</th>
                            <th class="px-4 sm:px-6 py-2.5 font-bold">Status</th>
                            <th class="px-4 sm:px-6 py-2.5 font-bold text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#1a1a1a]">
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

                            <tr class="table-row {{ !$logged ? 'bg-amber-500/[0.015]' : ($isOverdue ? 'bg-rose-500/[0.015]' : '') }}">
                                <td class="px-4 sm:px-6 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg shrink-0 flex items-center justify-center
                                            @if(!$logged) bg-amber-500/10 border border-amber-500/15
                                            @elseif($isOverdue) bg-rose-500/10 border border-rose-500/15
                                            @else bg-emerald-500/10 border border-emerald-500/15 @endif">
                                            @if(!$logged)
                                                <i class="fa-solid fa-clock text-[9px] text-amber-400"></i>
                                            @elseif($isOverdue)
                                                <i class="fa-solid fa-triangle-exclamation text-[9px] text-rose-400"></i>
                                            @else
                                                <i class="fa-solid fa-check text-[9px] text-emerald-400"></i>
                                            @endif
                                        </div>
                                        <p class="text-[10px] sm:text-[11px] font-bold text-[#ccc] truncate max-w-[200px]">{{ $task->tasks_performed }}</p>
                                    </div>
                                </td>

                                <td class="px-4 sm:px-6 py-3.5">
                                    @if($logged)
                                        <span class="text-[10px] sm:text-[11px] font-bold text-[#888] font-mono block">{{ number_format($logged->last_service_odo) }} km</span>
                                        <span class="text-[7px] sm:text-[8px] text-[#444] font-bold uppercase">{{ $logged->last_service_date ? $logged->last_service_date->format('M d, Y') : '—' }}</span>
                                    @else
                                        <span class="text-[10px] sm:text-[11px] text-[#333] italic font-medium">Not logged</span>
                                    @endif
                                </td>

                                <td class="px-4 sm:px-6 py-3.5">
                                    <div class="flex gap-1.5">
                                        @if($task->miles_between_service)
                                            <span class="text-[7px] sm:text-[8px] bg-[#111] text-[#666] border border-[#1e1e1e] px-1.5 py-0.5 rounded-md font-bold">{{ number_format($task->miles_between_service) }} km</span>
                                        @else
                                            <span class="text-[7px] sm:text-[8px] bg-[#111] text-[#222] border border-[#1e1e1e] px-1.5 py-0.5 rounded-md font-bold">—</span>
                                        @endif
                                        @if($task->months_between_service)
                                            <span class="text-[7px] sm:text-[8px] bg-[#111] text-[#666] border border-[#1e1e1e] px-1.5 py-0.5 rounded-md font-bold">{{ $task->months_between_service }} mo</span>
                                        @else
                                            <span class="text-[7px] sm:text-[8px] bg-[#111] text-[#222] border border-[#1e1e1e] px-1.5 py-0.5 rounded-md font-bold">—</span>
                                        @endif
                                    </div>
                                </td>

                                <td class="px-4 sm:px-6 py-3.5">
                                    @if($logged)
                                        <span class="text-[10px] sm:text-[11px] font-bold font-mono block {{ $isOverdue ? 'text-rose-400' : 'text-emerald-400' }}">
                                            {{ $isOverdue ? 'Overdue' : number_format($nextOdo) . ' km' }}
                                        </span>
                                        @if($nextDate)
                                            <span class="text-[7px] sm:text-[8px] {{ $isOverdue ? 'text-rose-400/60' : 'text-[#444]' }} font-bold uppercase">
                                                {{ $nextDate->format('M d, Y') }}
                                            </span>
                                        @endif
                                    @else
                                        <span class="text-[10px] sm:text-[11px] text-amber-400 font-bold font-mono">Needs logging</span>
                                    @endif
                                </td>

                                <td class="px-4 sm:px-6 py-3.5 text-right">
                                    @if($logged)
                                        <span class="text-[10px] sm:text-[11px] font-bold text-white font-mono">₱{{ number_format($logged->last_service_cost, 2) }}</span>
                                    @else
                                        <span class="text-[10px] sm:text-[11px] text-[#222]">—</span>
                                    @endif
                                </td>

                                <td class="px-4 sm:px-6 py-3.5">
                                    @if($logged && !$isOverdue)
                                        <span class="text-[8px] bg-emerald-500/10 text-emerald-400 border border-emerald-500/15 px-2 py-0.5 rounded-md font-bold uppercase">On Track</span>
                                    @elseif($logged && $isOverdue)
                                        <span class="text-[8px] bg-rose-500/10 text-rose-400 border border-rose-500/15 px-2 py-0.5 rounded-md font-bold uppercase">Past Due</span>
                                    @else
                                        <span class="text-[8px] bg-amber-500/10 text-amber-400 border border-amber-500/15 px-2 py-0.5 rounded-md font-bold uppercase">Unlogged</span>
                                    @endif
                                </td>

                                <td class="px-4 sm:px-6 py-3.5 text-center">
                                    <button @click="openLogModal({{ $task->id }}, '{{ $task->tasks_performed }}')"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[8px] sm:text-[9px] font-bold uppercase tracking-wider transition-all
                                            @if($logged && !$isOverdue) bg-[#111] text-[#555] border border-[#1e1e1e] hover:bg-[#1a1a1a] hover:text-[#888] hover:border-[#333]
                                            @else bg-blue-500/10 text-blue-400 border border-blue-500/15 hover:bg-blue-500/20 hover:border-blue-500/25 @endif">
                                        <i class="fa-solid fa-pen-to-square text-[7px]"></i>
                                        <span>{{ $logged ? 'Update' : 'Log' }}</span>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-10 sm:py-12">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="w-10 h-10 rounded-xl bg-[#111] border border-[#1e1e1e] flex items-center justify-center mb-2.5">
                                            <i class="fa-solid fa-clipboard-list text-sm text-[#333]"></i>
                                        </div>
                                        <p class="text-[10px] sm:text-[11px] text-[#444] font-medium">No maintenance tasks defined yet</p>
                                        <p class="text-[8px] text-[#333] mt-0.5">Add tasks in the settings to get started</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Table Footer -->
            @if($allTasks->isNotEmpty())
            <div class="px-4 sm:px-6 py-3 border-t border-[#1e1e1e] bg-[#111] flex flex-col sm:flex-row justify-between gap-2">
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-1.5">
                        <div class="w-2 h-2 rounded-sm bg-emerald-500"></div>
                        <span class="text-[7px] font-bold text-[#444] uppercase">Healthy {{ $healthyCount }}</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <div class="w-2 h-2 rounded-sm bg-rose-500"></div>
                        <span class="text-[7px] font-bold text-[#444] uppercase">Overdue {{ $overdueCount }}</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <div class="w-2 h-2 rounded-sm bg-amber-500"></div>
                        <span class="text-[7px] font-bold text-[#444] uppercase">Unlogged {{ $unloggedCount }}</span>
                    </div>
                </div>
                <span class="text-[7px] text-[#333] font-bold uppercase tracking-widest">Total: {{ $totalTasks }} tasks</span>
            </div>
            @endif
        </div>

        @endif
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

    <!-- ══════════ MODAL: Log / Update Preventive Service ══════════ -->
    <template x-teleport="body">
        <div x-show="showModal"
             x-cloak
             @keydown.escape.window="showModal = false"
             class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/80"
             role="dialog" aria-modal="true"
             style="display: none;">

            <div x-show="showModal"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="showModal = false"
                 class="absolute inset-0"></div>

            <form x-show="showModal"
                  x-transition:enter="transition ease-out duration-300"
                  x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                  x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                  method="POST"
                  action="{{ route('maintenance-manager.preventive-maintenance.store') }}"
                  @click.stop
                  class="relative w-full max-w-lg glass-panel rounded-[2rem] flex flex-col max-h-[90vh] overflow-hidden modal-panel">

                @csrf

                <!-- Hidden Fields -->
                <input type="hidden" name="fleet_id" x-model="form.fleet_id">
                <input type="hidden" name="task_id" x-model="form.task_id">

                <!-- Header -->
                <div class="flex items-center justify-between px-6 sm:px-8 pt-6 sm:pt-7 pb-5 border-b border-[#1e1e1e] shrink-0">
                    <div class="flex items-center gap-3.5">
                        <div class="w-10 h-10 rounded-xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center">
                            <i class="fa-solid fa-wrench text-[11px] text-blue-400"></i>
                        </div>
                        <div>
                            <h3 class="text-sm sm:text-base font-black tracking-tight">Log Service</h3>
                            <p class="text-[9px] sm:text-[10px] text-[#555] mt-0.5 truncate max-w-[240px]" x-text="taskName"></p>
                        </div>
                    </div>
                    <button type="button" @click="showModal = false"
                        class="w-8 h-8 rounded-lg bg-[#1a1a1a] border border-[#2a2a2a] hover:bg-[#222] flex items-center justify-center text-[#555] hover:text-white transition-colors"
                        aria-label="Close modal">
                        <i class="fa-solid fa-xmark text-xs"></i>
                    </button>
                </div>

                <!-- Body -->
                <div class="flex-1 overflow-y-auto px-6 sm:px-8 py-5 sm:py-6 space-y-4 sm:space-y-5">

                    @if ($errors->any())
                        <div class="bg-rose-500/5 border border-rose-500/15 text-rose-400 text-[9px] sm:text-[10px] font-medium px-4 py-3 rounded-xl flex items-start gap-2">
                            <i class="fa-solid fa-circle-exclamation shrink-0 mt-0.5 text-[10px]"></i>
                            <ul class="list-disc list-inside space-y-0.5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="grid grid-cols-2 gap-3 sm:gap-4">
                        <div>
                            <label class="flex items-center gap-2 text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-[#555] mb-2">
                                <span class="w-[3px] h-3 rounded-sm bg-blue-500 shrink-0"></span>
                                Odometer (km) <span class="text-rose-400/60">*</span>
                            </label>
                            <input type="number"
                                   name="last_service_odo"
                                   x-model="form.last_service_odo"
                                   min="0"
                                   placeholder="e.g. 25000"
                                   required
                                   class="form-input w-full rounded-xl px-4 py-2.5 text-[10px] sm:text-xs font-mono placeholder:text-[#2a2a2a]">
                        </div>
                        <div>
                            <label class="flex items-center gap-2 text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-[#555] mb-2">
                                <span class="w-[3px] h-3 rounded-sm bg-blue-500 shrink-0"></span>
                                Service Date <span class="text-rose-400/60">*</span>
                            </label>
                            <input type="date"
                                   name="last_service_date"
                                   x-model="form.last_service_date"
                                   required
                                   class="form-input w-full rounded-xl px-4 py-2.5 text-[10px] sm:text-xs [color-scheme:dark]">
                        </div>
                    </div>

                    <div>
                        <label class="flex items-center gap-2 text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-[#555] mb-2">
                            <span class="w-[3px] h-3 rounded-sm bg-blue-500 shrink-0"></span>
                            Service Cost <span class="text-rose-400/60">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-[10px] text-[#333] font-mono">₱</span>
                            <input type="number"
                                   name="last_service_cost"
                                   x-model="form.last_service_cost"
                                   min="0"
                                   step="0.01"
                                   placeholder="0.00"
                                   required
                                   class="form-input w-full rounded-xl pl-8 pr-4 py-2.5 text-[10px] sm:text-xs font-mono placeholder:text-[#2a2a2a]">
                        </div>
                    </div>

                    <div>
                        <label class="flex items-center gap-2 text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-[#555] mb-2">
                            <span class="w-[3px] h-3 rounded-sm bg-[#333] shrink-0"></span>
                            Comments
                        </label>
                        <textarea name="comments"
                                  x-model="form.comments"
                                  rows="3"
                                  placeholder="e.g. Used OEM parts, next service due at 30,000 km..."
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
                        Save Log
                    </button>
                </div>
            </form>
        </div>
    </template>

</body>
</html>
