<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartCommute | Maintenance Tasks</title>
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

        input[type="number"]::-webkit-inner-spin-button,
        input[type="number"]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        input[type="number"] {
            -moz-appearance: textfield;
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
    </style>
</head>

<body class="antialiased text-white" x-data="{ open: true, showLogoutModal: false, showModal: false, animating: false, editMode: false, editId: null,
    openModal() {
        this.editMode = false;
        this.editId = null;
        this.showModal = true;
        this.animating = true;
        setTimeout(() => this.animating = false, 300);
    },
    openEditModal(id, name, miles, months) {
        this.editMode = true;
        this.editId = id;
        document.getElementById('field_task').value = name;
        document.getElementById('field_miles').value = miles || '';
        document.getElementById('field_months').value = months || '';
        this.showModal = true;
        this.animating = true;
        setTimeout(() => this.animating = false, 300);
    },
    closeModal() {
        this.animating = true;
        setTimeout(() => {
            this.showModal = false;
            this.animating = false;
            document.getElementById('field_task').value = '';
            document.getElementById('field_miles').value = '';
            document.getElementById('field_months').value = '';
        }, 200);
    }
}" @keydown.escape="if(showModal) closeModal()">

    @include('components.flash')
    @include('maintenance-manager.layout.sidebar')

    <main :class="open ? 'md:ml-72' : 'md:ml-20'" class="sidebar-transition pt-8 pr-4 sm:pr-8 pb-8 pl-4 sm:pl-8 min-h-screen mb-12">

        @php
            $totalTasks = $tasks->count();
            $withMiles = $tasks->whereNotNull('miles_between_service')->where('miles_between_service', '>', 0)->count();
            $withMonths = $tasks->whereNotNull('months_between_service')->where('months_between_service', '>', 0)->count();
        @endphp

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
                    <span class="font-mono text-[9px] text-[#444]">Tasks</span>
                </div>
            </div>
        </div>

        <!-- ── Page Header (desktop) ── -->
        <div class="hidden lg:flex items-end justify-between mb-8">
            <div>
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="text-[9px] font-bold uppercase tracking-[0.15em] text-[#444]">Configuration</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight">Maintenance <span class="text-blue-500">Tasks</span></h1>
                <p class="text-[11px] text-[#555] mt-1 flex items-center gap-2">
                    <i class="fa-solid fa-wrench text-[9px] text-amber-400"></i>
                    Standard service intervals and frequency reference
                </p>
            </div>
            <button @click="openModal()"
                class="flex items-center gap-2 px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-[10px] font-bold uppercase tracking-widest shadow-lg shadow-blue-900/30 transition-all active:scale-[0.97]">
                <i class="fa-solid fa-plus text-[9px]"></i>
                <span>Add Task</span>
            </button>
        </div>

        <!-- ── Mobile Add Button ── -->
        <div class="lg:hidden mb-5">
            <button @click="openModal()"
                class="w-full flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-[10px] font-bold uppercase tracking-widest transition-all active:scale-[0.98]">
                <i class="fa-solid fa-plus text-[9px]"></i>
                <span>Add Task</span>
            </button>
        </div>

        <!-- ══════════ STAT CARDS ══════════ -->
        <div class="grid grid-cols-3 gap-3 sm:gap-4 mb-5 sm:mb-6">

            <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-blue-500">
                <div class="flex items-center gap-2 mb-2 sm:mb-3">
                    <div class="w-6 h-6 rounded-md bg-blue-500/10 flex items-center justify-center">
                        <i class="fa-solid fa-list-check text-[8px] text-blue-400"></i>
                    </div>
                    <span class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Total Tasks</span>
                </div>
                <div class="flex items-baseline gap-1.5">
                    <span class="text-xl sm:text-2xl font-black tracking-tight">{{ $totalTasks }}</span>
                    <span class="text-xs font-bold text-[#555]">{{ $totalTasks === 1 ? 'item' : 'items' }}</span>
                </div>
            </div>

            <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-emerald-500">
                <div class="flex items-center gap-2 mb-2 sm:mb-3">
                    <div class="w-6 h-6 rounded-md bg-emerald-500/10 flex items-center justify-center">
                        <i class="fa-solid fa-road text-[8px] text-emerald-400"></i>
                    </div>
                    <span class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Km-Based</span>
                </div>
                <div class="flex items-baseline gap-1.5">
                    <span class="text-xl sm:text-2xl font-black tracking-tight text-emerald-400">{{ $withMiles }}</span>
                    <span class="text-xs font-bold text-[#555]">tasks</span>
                </div>
            </div>

            <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-purple-500">
                <div class="flex items-center gap-2 mb-2 sm:mb-3">
                    <div class="w-6 h-6 rounded-md bg-purple-500/10 flex items-center justify-center">
                        <i class="fa-solid fa-calendar text-[8px] text-purple-400"></i>
                    </div>
                    <span class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Time-Based</span>
                </div>
                <div class="flex items-baseline gap-1.5">
                    <span class="text-xl sm:text-2xl font-black tracking-tight text-purple-400">{{ $withMonths }}</span>
                    <span class="text-xs font-bold text-[#555]">tasks</span>
                </div>
            </div>

        </div>

        <!-- ══════════ TASKS TABLE ══════════ -->
        <div class="glass-card rounded-[1.25rem] sm:rounded-[1.5rem] overflow-hidden">
            <div class="p-4 sm:p-6 pb-0">
                <div class="flex items-center justify-between mb-4 sm:mb-5">
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-lg bg-[#111] border border-[#1e1e1e] flex items-center justify-center">
                            <i class="fa-solid fa-clipboard-list text-[9px] text-blue-400"></i>
                        </div>
                        <span class="text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-[#555]">Task Definitions</span>
                    </div>
                    <span class="text-[7px] sm:text-[8px] font-bold text-[#333] uppercase tracking-widest">{{ $totalTasks }} tasks</span>
                </div>
            </div>
            <div class="overflow-x-auto -mx-2 px-2 pb-2">
                <table class="w-full text-left min-w-[550px]">
                    <thead>
                        <tr class="text-[7px] sm:text-[8px] uppercase tracking-[0.15em] text-[#444] border-b border-[#1e1e1e]">
                            <th class="px-4 sm:px-6 py-2.5 font-bold">Task Performed</th>
                            <th class="px-4 sm:px-6 py-2.5 font-bold text-right">Km Interval</th>
                            <th class="px-4 sm:px-6 py-2.5 font-bold text-right">Month Interval</th>
                            <th class="px-4 sm:px-6 py-2.5 font-bold text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#1a1a1a]">
                        @forelse ($tasks as $task)
                            <tr class="table-row group">
                                <td class="px-4 sm:px-6 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-blue-500/10 border border-blue-500/15 flex items-center justify-center shrink-0">
                                            <i class="fa-solid fa-wrench text-[8px] text-blue-400"></i>
                                        </div>
                                        <p class="text-[10px] sm:text-[11px] font-bold text-[#ccc] truncate max-w-[280px]">{{ $task->tasks_performed }}</p>
                                    </div>
                                </td>
                                <td class="px-4 sm:px-6 py-3.5 text-right">
                                    @if($task->miles_between_service)
                                        <span class="text-[10px] sm:text-[11px] font-bold text-[#888] font-mono">{{ number_format($task->miles_between_service) }} km</span>
                                    @else
                                        <span class="text-[10px] sm:text-[11px] text-[#222]">—</span>
                                    @endif
                                </td>
                                <td class="px-4 sm:px-6 py-3.5 text-right">
                                    @if($task->months_between_service)
                                        <span class="text-[10px] sm:text-[11px] font-bold text-[#888] font-mono">{{ $task->months_between_service }} mo</span>
                                    @else
                                        <span class="text-[10px] sm:text-[11px] text-[#222]">—</span>
                                    @endif
                                </td>
                                <td class="px-4 sm:px-6 py-3.5">
                                    <div class="flex items-center justify-center gap-1 opacity-30 group-hover:opacity-100 transition-opacity">
                                        <button type="button"
                                            @click="openEditModal({{ $task->id }}, '{{ $task->tasks_performed }}', {{ $task->miles_between_service ?? 'null' }}, {{ $task->months_between_service ?? 'null' }})"
                                            class="w-7 h-7 rounded-lg flex items-center justify-center text-[#555] hover:text-blue-400 hover:bg-blue-500/10 transition-all"
                                            title="Edit">
                                            <i class="fa-solid fa-pen-to-square text-[9px]"></i>
                                        </button>
                                        <form method="POST" action="{{ route('maintenance-manager.maintenance-tasks.destroy', $task->id) }}" class="inline" onsubmit="return confirm('Delete this task?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="w-7 h-7 rounded-lg flex items-center justify-center text-[#555] hover:text-rose-400 hover:bg-rose-500/10 transition-all"
                                                title="Delete">
                                                <i class="fa-solid fa-trash-can text-[9px]"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-10 sm:py-12">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="w-10 h-10 rounded-xl bg-[#111] border border-[#1e1e1e] flex items-center justify-center mb-2.5">
                                            <i class="fa-solid fa-inbox text-sm text-[#333]"></i>
                                        </div>
                                        <p class="text-[10px] sm:text-[11px] text-[#444] font-medium">No tasks defined yet</p>
                                        <p class="text-[8px] text-[#333] mt-0.5">Click "Add Task" to create a service interval</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Table Footer -->
            @if($tasks->isNotEmpty())
            <div class="px-4 sm:px-6 py-3 border-t border-[#1e1e1e] bg-[#111] flex flex-col sm:flex-row justify-between gap-2">
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-1.5">
                        <div class="w-2 h-2 rounded-sm bg-emerald-500"></div>
                        <span class="text-[7px] font-bold text-[#444] uppercase">{{ $withMiles }} km-based</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <div class="w-2 h-2 rounded-sm bg-purple-500"></div>
                        <span class="text-[7px] font-bold text-[#444] uppercase">{{ $withMonths }} time-based</span>
                    </div>
                </div>
                <span class="text-[7px] text-[#333] font-bold uppercase tracking-widest">Total: {{ $totalTasks }} tasks</span>
            </div>
            @endif
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

    <!-- ══════════ MODAL: Add / Edit Task ══════════ -->
    <template x-teleport="body">
        <div x-show="showModal" x-cloak
            class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/80"
            @click.self="closeModal()"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            style="display: none;">

            <div x-show="showModal"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                 @click.stop
                 class="relative w-full max-w-lg glass-panel rounded-[2rem] flex flex-col max-h-[90vh] overflow-hidden">

                <!-- Header -->
                <div class="flex items-center justify-between px-6 sm:px-8 pt-6 sm:pt-7 pb-5 border-b border-[#1e1e1e] shrink-0">
                    <div class="flex items-center gap-3.5">
                        <div class="w-10 h-10 rounded-xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center">
                            <i class="fa-solid fa-wrench text-[11px] text-blue-400"></i>
                        </div>
                        <div>
                            <h3 class="text-sm sm:text-base font-black tracking-tight" x-text="editMode ? 'Edit Task' : 'New Task'"></h3>
                            <p class="text-[9px] sm:text-[10px] text-[#555] mt-0.5">Define service interval</p>
                        </div>
                    </div>
                    <button @click="closeModal()"
                        class="w-8 h-8 rounded-lg bg-[#1a1a1a] border border-[#2a2a2a] hover:bg-[#222] flex items-center justify-center text-[#555] hover:text-white transition-colors">
                        <i class="fa-solid fa-xmark text-xs"></i>
                    </button>
                </div>

                <!-- Form -->
                <form x-bind:action="editMode ? '{{ route('maintenance-manager.maintenance-tasks.update', '') }}/' + editId : '{{ route('maintenance-manager.maintenance-tasks.store') }}'"
                    x-bind:method="editMode ? 'POST' : 'POST'"
                    class="flex-1 overflow-y-auto px-6 sm:px-8 py-5 sm:py-6 space-y-4 sm:space-y-5">
                    @csrf
                    @if(false) @method('PUT') @endif

                    <input type="hidden" name="_method" x-bind:value="editMode ? 'PUT' : ''">

                    <div>
                        <label class="flex items-center gap-2 text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-[#555] mb-2">
                            <span class="w-[3px] h-3 rounded-sm bg-blue-500 shrink-0"></span>
                            Task Performed <span class="text-rose-400/60">*</span>
                        </label>
                        <input type="text" id="field_task"
                            class="form-input w-full rounded-xl px-4 py-2.5 text-[10px] sm:text-xs placeholder:text-[#2a2a2a]"
                            placeholder="e.g. Transmission Fluid Change"
                            name="tasks_performed">
                    </div>

                    <div class="grid grid-cols-2 gap-3 sm:gap-4">
                        <div>
                            <label class="flex items-center gap-2 text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-[#555] mb-2">
                                <span class="w-[3px] h-3 rounded-sm bg-emerald-500 shrink-0"></span>
                                Km Interval
                            </label>
                            <div class="relative">
                                <input type="number" id="field_miles" min="0"
                                    class="form-input w-full rounded-xl px-4 py-2.5 text-[10px] sm:text-xs font-mono placeholder:text-[#2a2a2a] pr-12"
                                    placeholder="5,000"
                                    name="miles_between_service">
                                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-[#333] text-[9px] font-bold">km</span>
                            </div>
                        </div>
                        <div>
                            <label class="flex items-center gap-2 text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-[#555] mb-2">
                                <span class="w-[3px] h-3 rounded-sm bg-purple-500 shrink-0"></span>
                                Month Interval
                            </label>
                            <div class="relative">
                                <input type="number" id="field_months" min="0"
                                    class="form-input w-full rounded-xl px-4 py-2.5 text-[10px] sm:text-xs font-mono placeholder:text-[#2a2a2a] pr-14"
                                    placeholder="6"
                                    name="months_between_service">
                                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-[#333] text-[9px] font-bold">mos</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-start gap-2.5 p-3 rounded-xl bg-[#111] border border-[#1e1e1e]">
                        <i class="fa-solid fa-circle-info text-[9px] text-blue-500/40 mt-0.5 shrink-0"></i>
                        <p class="text-[9px] text-[#444] leading-relaxed">
                            At least one of <span class="text-[#666] font-bold">Km Interval</span> or <span class="text-[#666] font-bold">Month Interval</span> must be provided. Leave empty if not applicable.
                        </p>
                    </div>

                    <!-- Footer -->
                    <div class="flex items-center justify-end gap-2.5 pt-2">
                        <button type="button" @click="closeModal()"
                            class="px-5 py-2.5 rounded-xl bg-[#1a1a1a] border border-[#2a2a2a] text-[#888] text-[9px] sm:text-[10px] font-bold uppercase tracking-widest hover:bg-[#222] hover:text-white transition">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-6 py-2.5 rounded-xl bg-blue-600 text-white text-[9px] sm:text-[10px] font-bold uppercase tracking-widest hover:bg-blue-500 transition-all active:scale-[0.98] flex items-center gap-2">
                            <i class="fa-solid fa-check text-[9px]"></i>
                            <span x-text="editMode ? 'Update' : 'Add Task'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

</body>
</html>
