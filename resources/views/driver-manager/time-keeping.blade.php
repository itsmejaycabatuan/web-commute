<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartCommute | Time Keeping</title>
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
        .glass-panel { background: #111111 !important; border: 1px solid #1e1e1e; box-shadow: 0 4px 24px rgba(0,0,0,0.6); }
        .glass-card { background: #161616; border: 1px solid #222222; box-shadow: 0 4px 24px rgba(0,0,0,0.5); }
        .sidebar-transition { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        .table-row { transition: all 0.2s ease; }
        .table-row:hover { background: #1a1a1a; }
        [x-cloak] { display: none !important; }

        ::-webkit-scrollbar { width: 3px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #333; border-radius: 10px; }

        select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='rgba(255,255,255,0.2)' viewBox='0 0 16 16'%3E%3Cpath d='M8 11L3 6h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
        }

        input[type="time"]::-webkit-calendar-picker-indicator,
        input[type="date"]::-webkit-calendar-picker-indicator {
            filter: invert(1) brightness(0.3);
            cursor: pointer;
        }
    </style>
</head>

<body class="antialiased text-white" x-data="{ open: false }">

    @include('driver-manager.layout.sidebar')

    <main :class="open ? 'md:ml-[270px]' : 'md:ml-[76px]'" class="sidebar-transition pt-8 pr-4 sm:pr-8 pb-8 pl-4 sm:pl-8 min-h-screen mb-12"
          x-data="timeKeepingData()">

        @php
            $currentType = request('type', 'all');
        @endphp

        <!-- ── Mobile: Identity Card ── -->
        <div class="lg:hidden mb-5">
            <div class="glass-card p-4 rounded-[1.25rem]">
                <div class="flex items-center gap-3.5">
                    <div class="w-11 h-11 bg-blue-600 rounded-xl flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-gauge-high text-white text-sm"></i>
                    </div>
                    <div class="min-w-0">
                        <h2 class="text-sm font-bold text-white truncate">Driver Manager</h2>
                        <p class="text-[10px] text-[#555] truncate">{{ Auth::user()->email }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 pt-3.5 mt-3.5 border-t border-[#1e1e1e]">
                    <i class="fa-solid fa-id-badge text-[8px] text-blue-400"></i>
                    <span class="text-[10px] text-[#888] font-bold">Driver Oversight</span>
                    <span class="text-[#333]">•</span>
                    <span class="font-mono text-[9px] text-[#444]">Manager</span>
                </div>
            </div>
        </div>

        <!-- ── Page Header (desktop) ── -->
        <div class="hidden lg:block mb-8">
            <div class="flex items-center gap-2 mb-1.5">
                <span class="text-[9px] font-bold uppercase tracking-[0.15em] text-[#444]">Manage</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">Time Keeping</h1>
            <p class="text-[11px] text-[#555] mt-1 flex items-center gap-2">
                <i class="fa-solid fa-clock text-[9px] text-blue-400"></i>
                <span class="text-[#888] font-bold">{{ $entries instanceof \Illuminate\Pagination\LengthAwarePaginator ? $entries->total() : count($entries) }}</span> shift records logged
            </p>
        </div>

        @if (session('success'))
            <div class="mb-5 px-4 py-3 rounded-xl border border-emerald-500/15 bg-emerald-500/5 flex items-center gap-3">
                <div class="w-6 h-6 rounded-md bg-emerald-500/10 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-check text-[8px] text-emerald-400"></i>
                </div>
                <span class="text-[11px] text-emerald-400 font-medium">{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-5 px-4 py-3 rounded-xl border border-red-500/15 bg-red-500/5 flex items-center gap-3">
                <div class="w-6 h-6 rounded-md bg-red-500/10 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-xmark text-[8px] text-red-400"></i>
                </div>
                <span class="text-[11px] text-red-400 font-medium">{{ session('error') }}</span>
            </div>
        @endif

        <!-- ══════════ TABLE CARD ══════════ -->
        <div class="glass-card rounded-[1.25rem] sm:rounded-[1.5rem] overflow-hidden">

            <!-- ── Filters & Add Button ── -->
            <div class="p-4 sm:p-5 border-b border-[#1e1e1e]">
                <div class="flex flex-col sm:flex-row gap-3 mb-3.5">
                    <div class="flex-1"></div>
                    <button @click="openModal()"
                        class="flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-[10px] font-bold uppercase tracking-widest text-white transition active:scale-[0.98] shrink-0">
                        <i class="fa-solid fa-plus text-[9px]"></i>
                        <span>New Entry</span>
                    </button>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-1.5">
                        <a href="{{ route('driver-manager.time-keeping') }}?type=all"
                           class="px-3 py-1.5 rounded-lg text-[8px] font-bold uppercase tracking-widest border transition {{ $currentType === 'all' ? 'bg-white/10 text-white border-white/20' : 'bg-[#111] text-[#555] border-[#1e1e1e] hover:border-[#333] hover:text-[#888]' }}">
                            All
                        </a>
                        <a href="{{ route('driver-manager.time-keeping') }}?type=regular"
                           class="px-3 py-1.5 rounded-lg text-[8px] font-bold uppercase tracking-widest border transition {{ $currentType === 'regular' ? 'bg-blue-500/10 text-blue-400 border-blue-500/20' : 'bg-[#111] text-[#555] border-[#1e1e1e] hover:border-[#333] hover:text-[#888]' }}">
                            Regular
                        </a>
                        <a href="{{ route('driver-manager.time-keeping') }}?type=sick"
                           class="px-3 py-1.5 rounded-lg text-[8px] font-bold uppercase tracking-widest border transition {{ $currentType === 'sick' ? 'bg-red-500/10 text-red-400 border-red-500/20' : 'bg-[#111] text-[#555] border-[#1e1e1e] hover:border-[#333] hover:text-[#888]' }}">
                            Sick
                        </a>
                        <a href="{{ route('driver-manager.time-keeping') }}?type=vacation"
                           class="px-3 py-1.5 rounded-lg text-[8px] font-bold uppercase tracking-widest border transition {{ $currentType === 'vacation' ? 'bg-teal-500/10 text-teal-400 border-teal-500/20' : 'bg-[#111] text-[#555] border-[#1e1e1e] hover:border-[#333] hover:text-[#888]' }}">
                            Vacation
                        </a>
                        <a href="{{ route('driver-manager.time-keeping') }}?type=overtime"
                           class="px-3 py-1.5 rounded-lg text-[8px] font-bold uppercase tracking-widest border transition {{ $currentType === 'overtime' ? 'bg-orange-500/10 text-orange-400 border-orange-500/20' : 'bg-[#111] text-[#555] border-[#1e1e1e] hover:border-[#333] hover:text-[#888]' }}">
                            Overtime
                        </a>
                    </div>
                    <span class="text-[8px] font-bold text-[#333] uppercase tracking-widest">
                        {{ $entries instanceof \Illuminate\Pagination\LengthAwarePaginator ? $entries->total() : count($entries) }} entries
                    </span>
                </div>
            </div>

            <!-- ── Table ── -->
            <div class="overflow-x-auto">
                <table class="w-full text-left min-w-[700px]">
                    <thead>
                        <tr class="text-[8px] sm:text-[9px] uppercase tracking-[0.15em] text-[#444] border-b border-[#1e1e1e]">
                            <th class="px-4 sm:px-6 py-3 font-bold w-12">#</th>
                            <th class="px-4 sm:px-6 py-3 font-bold">Driver & Date</th>
                            <th class="px-4 sm:px-6 py-3 font-bold">Shift</th>
                            <th class="px-4 sm:px-6 py-3 font-bold">Hours</th>
                            <th class="px-4 sm:px-6 py-3 font-bold">Overtime</th>
                            <th class="px-4 sm:px-6 py-3 font-bold">Type</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#1a1a1a]">
                        @forelse($entries as $entry)
                            <tr class="table-row">
                                <td class="px-4 sm:px-6 py-3">
                                    <span class="text-[10px] font-bold text-[#333]">{{ str_pad(($entries->firstItem() ?? 0) + $loop->index, 2, '0', STR_PAD_LEFT) }}</span>
                                </td>
                                <td class="px-4 sm:px-6 py-3">
                                    <div class="flex items-center gap-3">
                                        <div>
                                            <span class="text-[10px] sm:text-[11px] font-bold text-[#ccc] block">{{ $entry->driver->name }}</span>
                                            <span class="text-[8px] sm:text-[9px] text-[#444] font-medium">{{ \Carbon\Carbon::parse($entry->date)->format('M d, Y') }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 sm:px-6 py-3">
                                    @if($entry->sick === 1 || $entry->vacation === 1)
                                        <span class="text-[10px] sm:text-[11px] text-[#222] font-medium">—</span>
                                    @else
                                        <div class="flex items-center gap-3">
                                            <div class="flex flex-col">
                                                <span class="text-[7px] sm:text-[8px] uppercase text-[#333] font-bold tracking-wider">In</span>
                                                <span class="text-[10px] sm:text-[11px] font-bold text-emerald-400">{{ \Carbon\Carbon::parse($entry->time_in)->format('g:i A') }}</span>
                                            </div>
                                            <div class="h-6 w-px bg-[#1e1e1e]"></div>
                                            <div class="flex flex-col">
                                                <span class="text-[7px] sm:text-[8px] uppercase text-[#333] font-bold tracking-wider">Out</span>
                                                <span class="text-[10px] sm:text-[11px] font-bold text-amber-400">{{ \Carbon\Carbon::parse($entry->time_out)->format('g:i A') }}</span>
                                            </div>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 sm:px-6 py-3">
                                    @if($entry->sick === 1 || $entry->vacation === 1)
                                        <span class="text-[10px] text-[#222]">—</span>
                                    @else
                                        <div class="flex items-baseline gap-1">
                                            <span class="text-[11px] font-bold text-[#ccc]">{{ number_format($entry->hours_worked, 2) }}</span>
                                            <span class="text-[8px] font-bold text-[#444]">hrs</span>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 sm:px-6 py-3">
                                    @if($entry->overtime_hours > 0)
                                        <span class="text-[7px] sm:text-[8px] bg-blue-500/10 text-blue-400 border border-blue-500/15 px-1.5 py-0.5 rounded-md font-bold uppercase">
                                            +{{ number_format($entry->overtime_hours, 2) }} hrs
                                        </span>
                                    @else
                                        <span class="text-[10px] text-[#222]">0</span>
                                    @endif
                                </td>
                                <td class="px-4 sm:px-6 py-3">
                                    @if($entry->sick)
                                        <span class="text-[7px] sm:text-[8px] bg-red-500/10 text-red-400 border border-red-500/15 px-1.5 py-0.5 rounded-md font-bold uppercase">Sick</span>
                                    @elseif($entry->vacation)
                                        <span class="text-[7px] sm:text-[8px] bg-teal-500/10 text-teal-400 border border-teal-500/15 px-1.5 py-0.5 rounded-md font-bold uppercase">Vacation</span>
                                    @elseif($entry->overtime_hours > 0)
                                        <span class="text-[7px] sm:text-[8px] bg-orange-500/10 text-orange-400 border border-orange-500/15 px-1.5 py-0.5 rounded-md font-bold uppercase">Overtime</span>
                                    @else
                                        <span class="text-[7px] sm:text-[8px] bg-blue-500/10 text-blue-400 border border-blue-500/15 px-1.5 py-0.5 rounded-md font-bold uppercase">Regular</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 sm:py-16">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="w-12 h-12 rounded-xl bg-[#111] border border-[#1e1e1e] flex items-center justify-center mb-3">
                                            <i class="fa-regular fa-calendar text-base text-[#222]"></i>
                                        </div>
                                        <p class="text-[11px] text-[#444] font-medium mb-4">No time entries yet</p>
                                        <button @click="openModal()"
                                            class="flex items-center gap-2 px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-[9px] font-bold uppercase tracking-widest text-white transition">
                                            <i class="fa-solid fa-plus text-[8px]"></i>
                                            <span>Add First Entry</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ══════════ PAGINATION ══════════ -->
        @if($entries->hasPages())
            <div class="flex items-center justify-between mt-5 px-1">
                <div class="text-[8px] font-bold text-[#333] uppercase tracking-widest">
                    Showing {{ $entries->firstItem() }}–{{ $entries->lastItem() }} of {{ $entries->total() }}
                </div>
                <div class="flex items-center gap-1">
                    @if(!$entries->onFirstPage())
                        <a href="{{ $entries->previousPageUrl() }}"
                           class="w-8 h-8 rounded-lg bg-[#111] border border-[#1e1e1e] flex items-center justify-center text-[#444] hover:bg-[#1a1a1a] hover:text-[#888] hover:border-[#333] transition-all">
                            <i class="fa-solid fa-chevron-left text-[9px]"></i>
                        </a>
                    @else
                        <span class="w-8 h-8 rounded-lg bg-[#0a0a0a] border border-[#151515] flex items-center justify-center text-[#1a1a1a] cursor-not-allowed">
                            <i class="fa-solid fa-chevron-left text-[9px]"></i>
                        </span>
                    @endif

                    @php
                        $onEachSide = 1;
                        $start = max(1, $entries->currentPage() - $onEachSide);
                        $end = min($entries->lastPage(), $entries->currentPage() + $onEachSide);
                    @endphp

                    @if($start > 1)
                        <a href="{{ $entries->url(1) }}"
                           class="w-8 h-8 rounded-lg bg-[#111] border border-[#1e1e1e] flex items-center justify-center text-[10px] font-bold text-[#444] hover:bg-[#1a1a1a] hover:text-[#888] hover:border-[#333] transition-all">
                            1
                        </a>
                        @if($start > 2)
                            <span class="w-8 h-8 flex items-center justify-center text-[#222] text-[9px] font-bold">...</span>
                        @endif
                    @endif

                    @for($i = $start; $i <= $end; $i++)
                        @if($i === $entries->currentPage())
                            <span class="w-8 h-8 rounded-lg bg-blue-600 border border-blue-500/30 flex items-center justify-center text-[10px] font-bold text-white shadow-lg shadow-blue-600/10">
                                {{ $i }}
                            </span>
                        @else
                            <a href="{{ $entries->url($i) }}"
                               class="w-8 h-8 rounded-lg bg-[#111] border border-[#1e1e1e] flex items-center justify-center text-[10px] font-bold text-[#444] hover:bg-[#1a1a1a] hover:text-[#888] hover:border-[#333] transition-all">
                                {{ $i }}
                            </a>
                        @endif
                    @endfor

                    @if($end < $entries->lastPage())
                        @if($end < $entries->lastPage() - 1)
                            <span class="w-8 h-8 flex items-center justify-center text-[#222] text-[9px] font-bold">...</span>
                        @endif
                        <a href="{{ $entries->url($entries->lastPage()) }}"
                           class="w-8 h-8 rounded-lg bg-[#111] border border-[#1e1e1e] flex items-center justify-center text-[10px] font-bold text-[#444] hover:bg-[#1a1a1a] hover:text-[#888] hover:border-[#333] transition-all">
                            {{ $entries->lastPage() }}
                        </a>
                    @endif

                    @if($entries->hasMorePages())
                        <a href="{{ $entries->nextPageUrl() }}"
                           class="w-8 h-8 rounded-lg bg-[#111] border border-[#1e1e1e] flex items-center justify-center text-[#444] hover:bg-[#1a1a1a] hover:text-[#888] hover:border-[#333] transition-all">
                            <i class="fa-solid fa-chevron-right text-[9px]"></i>
                        </a>
                    @else
                        <span class="w-8 h-8 rounded-lg bg-[#0a0a0a] border border-[#151515] flex items-center justify-center text-[#1a1a1a] cursor-not-allowed">
                            <i class="fa-solid fa-chevron-right text-[9px]"></i>
                        </span>
                    @endif
                </div>
            </div>
        @endif


        <!-- ══════════ ADD ENTRY MODAL ══════════ -->
        <div x-show="showModal" x-cloak
            class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/80"
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" style="display:none;">

            <div @click.away="closeModal()"
                class="glass-panel p-6 sm:p-8 rounded-[2rem] max-w-xl w-full max-h-[90vh] overflow-y-auto">

                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-base sm:text-lg font-bold text-white">New Time Entry</h3>
                        <p class="text-[10px] text-[#555] mt-0.5">Fill in shift details or log a leave</p>
                    </div>
                    <button @click="closeModal()"
                        class="w-8 h-8 rounded-lg bg-[#1a1a1a] border border-[#2a2a2a] flex items-center justify-center hover:bg-[#222] transition">
                        <i class="fa-solid fa-xmark text-[10px] text-[#555]"></i>
                    </button>
                </div>

                <form method="POST"
                      action="{{ route('driver-manager.time-keeping.store') }}"
                      @submit.prevent="syncAndSubmit($event)"
                      class="space-y-4">

                    @csrf
                    <input type="hidden" name="is_leave" :value="form.is_leave">
                    <input type="hidden" name="sick" :value="form.sick">
                    <input type="hidden" name="vacation" :value="form.vacation">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block mb-1.5 text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Driver</label>
                            <select name="driver_id" x-model="form.driver_id"
                                class="w-full px-4 py-3 rounded-xl bg-[#111] border border-[#1e1e1e] text-[11px] text-white focus:outline-none focus:border-[#333] transition pr-10">
                                <option value="" disabled selected class="bg-[#111] text-[#444]">Select driver</option>
                                <template x-for="d in drivers" :key="d.id">
                                    <option :value="d.id" x-text="d.name" class="bg-[#111]"></option>
                                </template>
                            </select>
                            @error('driver_id')
                                <p class="mt-1.5 text-[9px] text-red-400 flex items-center gap-1.5">
                                    <i class="fa-solid fa-circle-exclamation text-[7px]"></i> {{ $message }}
                                </p>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-1.5 text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Date</label>
                            <input type="date" name="date" x-model="form.date"
                                class="w-full px-4 py-3 rounded-xl bg-[#111] border border-[#1e1e1e] text-[11px] text-white focus:outline-none focus:border-[#333] transition">
                            @error('date')
                                <p class="mt-1.5 text-[9px] text-red-400 flex items-center gap-1.5">
                                    <i class="fa-solid fa-circle-exclamation text-[7px]"></i> {{ $message }}
                                </p>
                            @enderror
                        </div>
                    </div>

                    <div class="pt-1 border-t border-[#1e1e1e]">
                        <label class="flex items-center justify-between cursor-pointer group py-1">
                            <div>
                                <span class="text-[10px] font-bold text-[#888] group-hover:text-white transition">Mark as Leave</span>
                                <p class="text-[8px] text-[#444] mt-0.5">No shift will be recorded</p>
                            </div>
                            <div class="relative flex items-center justify-center" @click.prevent="isLeave = !isLeave; if (!isLeave) { selectedLeave = ''; form.sick = '0'; form.vacation = '0'; }">
                                <div class="w-9 h-5 rounded-full transition"
                                     :class="isLeave ? 'bg-blue-600 border border-blue-500' : 'bg-[#222] border border-[#2a2a2a]'"></div>
                                <div class="absolute left-0.5 w-4 h-4 rounded-full transition"
                                     :class="isLeave ? 'translate-x-4 bg-white' : 'translate-x-0 bg-[#555]'"></div>
                            </div>
                        </label>
                    </div>

                    <!-- Shift Times -->
                    <div x-show="!isLeave"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-2"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 -translate-y-2"
                         class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block mb-1.5 text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">
                                <i class="fa-solid fa-right-to-bracket text-emerald-500/50 mr-1"></i> Time In
                            </label>
                            <input type="time" name="time_in" x-model="form.time_in"
                                class="w-full px-4 py-3 rounded-xl bg-[#111] border border-[#1e1e1e] text-[11px] text-emerald-400 font-mono font-semibold focus:outline-none focus:border-[#333] transition">
                            @error('time_in')
                                <p class="mt-1.5 text-[9px] text-red-400 flex items-center gap-1.5">
                                    <i class="fa-solid fa-circle-exclamation text-[7px]"></i> {{ $message }}
                                </p>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-1.5 text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">
                                <i class="fa-solid fa-right-from-bracket text-amber-500/50 mr-1"></i> Time Out
                            </label>
                            <input type="time" name="time_out" x-model="form.time_out"
                                class="w-full px-4 py-3 rounded-xl bg-[#111] border border-[#1e1e1e] text-[11px] text-amber-400 font-mono font-semibold focus:outline-none focus:border-[#333] transition">
                            @error('time_out')
                                <p class="mt-1.5 text-[9px] text-red-400 flex items-center gap-1.5">
                                    <i class="fa-solid fa-circle-exclamation text-[7px]"></i> {{ $message }}
                                </p>
                            @enderror
                        </div>
                    </div>

                    <!-- Computed Hours -->
                    <div x-show="!isLeave && form.time_in && form.time_out"
                         x-transition
                         class="flex items-center gap-4 p-3.5 rounded-xl bg-[#0a0a0a] border border-[#1e1e1e]">
                        <div class="flex items-center gap-2">
                            <div class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></div>
                            <span class="text-[8px] font-bold uppercase tracking-[0.15em] text-[#333]">Computed</span>
                        </div>
                        <div class="h-4 w-px bg-[#1e1e1e]"></div>
                        <div class="flex items-center gap-4">
                            <span class="text-[10px] text-[#555]">Total: <span class="text-[#ccc] font-bold" x-text="calcHours() + ' hrs'"></span></span>
                            <span class="text-[10px] text-[#555]">OT: <span :class="parseFloat(calcOvertime()) > 0 ? 'text-blue-400 font-bold' : 'text-[#222]'" x-text="calcOvertime() + ' hrs'"></span></span>
                        </div>
                    </div>

                    <!-- Leave Type Selection -->
                    <div x-show="isLeave"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-2"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 -translate-y-2"
                         class="space-y-3">
                        <label class="block text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Leave Type</label>
                        <div class="grid grid-cols-2 gap-3">
                            <button type="button" @click="pickLeave('sick')"
                                :class="selectedLeave === 'sick' ? 'border-red-500/20 bg-red-500/5' : 'border-[#1e1e1e] bg-[#111] hover:bg-[#1a1a1a] hover:border-[#333]'"
                                class="flex items-center gap-3 p-3.5 rounded-xl border transition-all active:scale-[0.98]">
                                <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0"
                                     :class="selectedLeave === 'sick' ? 'bg-red-500/10 border border-red-500/15' : 'bg-[#1a1a1a] border border-[#222]'">
                                    <i class="fa-solid fa-thermometer-half text-[10px]"
                                       :class="selectedLeave === 'sick' ? 'text-red-400' : 'text-[#444]'"></i>
                                </div>
                                <div class="text-left">
                                    <div class="text-[10px] font-bold" :class="selectedLeave === 'sick' ? 'text-red-300' : 'text-[#555]'">Sick Leave</div>
                                    <div class="text-[8px] text-[#333]">Medical / health</div>
                                </div>
                            </button>
                            <button type="button" @click="pickLeave('vacation')"
                                :class="selectedLeave === 'vacation' ? 'border-teal-500/20 bg-teal-500/5' : 'border-[#1e1e1e] bg-[#111] hover:bg-[#1a1a1a] hover:border-[#333]'"
                                class="flex items-center gap-3 p-3.5 rounded-xl border transition-all active:scale-[0.98]">
                                <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0"
                                     :class="selectedLeave === 'vacation' ? 'bg-teal-500/10 border border-teal-500/15' : 'bg-[#1a1a1a] border border-[#222]'">
                                    <i class="fa-solid fa-umbrella-beach text-[10px]"
                                       :class="selectedLeave === 'vacation' ? 'text-teal-400' : 'text-[#444]'"></i>
                                </div>
                                <div class="text-left">
                                    <div class="text-[10px] font-bold" :class="selectedLeave === 'vacation' ? 'text-teal-300' : 'text-[#555]'">Vacation Leave</div>
                                    <div class="text-[8px] text-[#333]">Personal / time off</div>
                                </div>
                            </button>
                        </div>
                    </div>

                    <div class="flex gap-2.5 pt-1">
                        <button type="button" @click="closeModal()"
                            class="flex-1 py-3 rounded-xl bg-[#1a1a1a] border border-[#2a2a2a] text-white text-[10px] font-bold uppercase tracking-widest hover:bg-[#222] transition">
                            Cancel
                        </button>
                        <button type="submit"
                            :disabled="!form.driver_id || !form.date || (!isLeave && (!form.time_in || !form.time_out)) || (isLeave && !selectedLeave)"
                            :class="form.driver_id && form.date && ((isLeave && selectedLeave) || (!isLeave && form.time_in && form.time_out))
                                ? 'bg-blue-600 hover:bg-blue-500 text-white'
                                : 'bg-[#111] text-[#222] cursor-not-allowed border border-[#1e1e1e]'"
                            class="flex-1 py-3 rounded-xl text-[10px] font-bold uppercase tracking-widest transition active:scale-[0.98]">
                            <i class="fa-solid fa-check mr-1.5 text-[8px]"></i>
                            <span x-text="isLeave ? 'Log Leave' : 'Save Entry'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </main>

    <script>
        function timeKeepingData() {
            return {
                showModal: false,
                isLeave: false,
                selectedLeave: '',
                form: {
                    driver_id: '',
                    date: '',
                    time_in: '',
                    time_out: '',
                    is_leave: '0',
                    sick: '0',
                    vacation: '0'
                },
                drivers: @json($drivers),

                init() {
                    @if($errors->any())
                        this.showModal = true;
                        this.form.driver_id = '{{ old("driver_id", "") }}';
                        this.form.date = '{{ old("date", "") }}';
                        this.form.time_in = '{{ old("time_in", "") }}';
                        this.form.time_out = '{{ old("time_out", "") }}';
                        @if(old('is_leave') === '1')
                            this.isLeave = true;
                            @if(old('sick') === '1')
                                this.pickLeave('sick');
                            @elseif(old('vacation') === '1')
                                this.pickLeave('vacation');
                            @endif
                        @endif
                    @endif
                },

                resetForm() {
                    this.form = { driver_id: '', date: '', time_in: '', time_out: '', is_leave: '0', sick: '0', vacation: '0' };
                    this.isLeave = false;
                    this.selectedLeave = '';
                },

                openModal() {
                    this.resetForm();
                    this.showModal = true;
                },

                closeModal() {
                    this.showModal = false;
                },

                pickLeave(type) {
                    this.selectedLeave = type;
                    this.form.sick = type === 'sick' ? '1' : '0';
                    this.form.vacation = type === 'vacation' ? '1' : '0';
                },

                calcHours() {
                    if (!this.form.time_in || !this.form.time_out) return '—';
                    const [hIn, mIn] = this.form.time_in.split(':').map(Number);
                    const [hOut, mOut] = this.form.time_out.split(':').map(Number);
                    let diff = (hOut * 60 + mOut) - (hIn * 60 + mIn);
                    if (diff < 0) diff += 1440;
                    return (diff / 60).toFixed(2);
                },

                calcOvertime() {
                    const total = parseFloat(this.calcHours());
                    if (isNaN(total)) return '—';
                    const ot = Math.max(0, total - 8).toFixed(2);
                    return ot > 0 ? ot : '0';
                },

                syncAndSubmit(event) {
                    this.form.is_leave = this.isLeave ? '1' : '0';
                    if (this.isLeave) {
                        this.form.time_in = '';
                        this.form.time_out = '';
                    } else {
                        this.form.sick = '0';
                        this.form.vacation = '0';
                    }
                    event.target.submit();
                }
            };
        }
    </script>

</body>
</html>
