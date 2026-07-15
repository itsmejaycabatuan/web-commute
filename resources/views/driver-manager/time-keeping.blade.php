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

        .modal-backdrop {
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(8px);
        }

        [x-cloak] { display: none !important; }

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

        select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='rgba(255,255,255,0.3)' viewBox='0 0 16 16'%3E%3Cpath d='M8 11L3 6h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
        }

        input[type="time"]::-webkit-calendar-picker-indicator,
        input[type="date"]::-webkit-calendar-picker-indicator {
            filter: invert(1) brightness(0.5);
            cursor: pointer;
        }

        .toggle-track { transition: background-color 0.25s ease; }
        .toggle-thumb { transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1); }

        .toast-enter { animation: toastIn 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
        .toast-leave { animation: toastOut 0.3s ease forwards; }
        @keyframes toastIn { from { opacity: 0; transform: translateY(-12px) scale(0.95); } to { opacity: 1; transform: translateY(0) scale(1); } }
        @keyframes toastOut { from { opacity: 1; transform: translateY(0) scale(1); } to { opacity: 0; transform: translateY(-12px) scale(0.95); } }


    </style>
</head>

<body x-data="{ open: true }">

    @include('driver-manager.layout.sidebar')

    <main :class="open ? 'ml-72' : 'ml-20'"
        class="sidebar-transition p-8 md:p-12 min-h-screen"
        x-data="{
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
            drivers: {{ $drivers }},
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
        }">

        <!-- Success Toast -->
        @if(session('success'))
        <div x-data="{ show: true }"
             x-init="setTimeout(() => { show = false; }, 4000)"
             x-show="show"
             x-transition:enter="toast-enter"
             x-transition:leave="toast-leave"
             class="fixed top-6 right-6 z-[99999] flex items-center gap-3 px-5 py-3.5 rounded-xl border border-emerald-500/20 bg-emerald-500/10 backdrop-blur-xl shadow-2xl shadow-emerald-900/30">
            <div class="w-7 h-7 rounded-lg bg-emerald-500/20 flex items-center justify-center flex-shrink-0">
                <i class="fa-solid fa-check text-emerald-400 text-xs"></i>
            </div>
            <span class="text-sm font-semibold text-emerald-300">{{ session('success') }}</span>
        </div>
        @endif

        <div class="max-w-[1400px] mx-auto">
            <header class="mb-8 flex flex-col md:flex-row md:items-end justify-between gap-6">
                <div>
                    <h2 class="text-3xl font-black tracking-tight mb-2">Time <span class="text-blue-500">Keeping</span></h2>
                    <p class="text-white/40 text-sm">Track driver shifts, hours, and leaves.</p>
                </div>
                <div class="flex gap-3">
                    <button @click="openModal()"
                        class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-500 text-white text-sm font-medium shadow-lg shadow-blue-900/40 transition-all active:scale-95">
                        <i class="fa-solid fa-plus mr-2"></i> New Entry
                    </button>
                </div>
            </header>

            <div class="glass rounded-[2rem] overflow-hidden border border-white/5">
                <div class="overflow-x-auto">
                    <table class="w-full text-left whitespace-nowrap">
                        <thead>
                            <tr class="border-b border-white/5 bg-white/5">
                                <th class="px-6 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-blue-500 w-64">Driver & Date</th>
                                <th class="px-6 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-blue-500">Shift (In / Out)</th>
                                <th class="px-6 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-blue-500">Total Hours</th>
                                <th class="px-6 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-blue-500">Overtime</th>
                                <th class="px-6 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-blue-500">Leaves</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @forelse($entries as $entry)
                            <tr class="hover:bg-white/[0.02] transition-colors group">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-white">{{ $entry->driver->name }}</div>
                                    <div class="text-sm text-white/40">{{ \Carbon\Carbon::parse($entry->date)->format('F d, Y') }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    @if($entry->sick === 1 || $entry->vacation === 1)
                                        <span class="text-sm text-white/30 italic">— No Shift —</span>
                                    @else
                                        <div class="flex items-center gap-3">
                                            <div class="flex flex-col">
                                                <span class="text-[10px] uppercase text-white/30 font-bold tracking-wider">In</span>
                                                <span class="text-sm font-mono text-emerald-400 font-semibold">{{ \Carbon\Carbon::parse($entry->time_in)->format('g:i A') }}</span>
                                            </div>
                                            <div class="h-8 w-[1px] bg-white/10"></div>
                                            <div class="flex flex-col">
                                                <span class="text-[10px] uppercase text-white/30 font-bold tracking-wider">Out</span>
                                                <span class="text-sm font-mono text-amber-400 font-semibold">{{ \Carbon\Carbon::parse($entry->time_out)->format('g:i A') }}</span>
                                            </div>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-lg font-bold text-white">{{ number_format($entry->hours_worked, 2) }}</span>
                                    <span class="text-xs text-white/30 ml-1">hrs</span>
                                </td>
                                <td class="px-6 py-4">
                                    @if($entry->overtime_hours > 0)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-blue-500/20 text-blue-400 border border-blue-500/20">
                                            +{{ number_format($entry->overtime_hours, 2) }} hrs
                                        </span>
                                    @else
                                        <span class="text-sm text-white/20">0</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex gap-2">
                                        <span class="px-2 py-1 rounded {{ $entry->sick ? 'bg-red-500/10 text-red-400 font-bold border border-red-500/20' : 'bg-white/5 text-white/20 font-medium' }} text-xs">
                                            Sick: {{ $entry->sick ? '1' : '0' }}
                                        </span>
                                        <span class="px-2 py-1 rounded {{ $entry->vacation ? 'bg-cyan-500/10 text-cyan-400 font-bold border border-cyan-500/20' : 'bg-white/5 text-white/20 font-medium' }} text-xs">
                                            Vac: {{ $entry->vacation ? '1' : '0' }}
                                        </span>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-6 py-16 text-center">
                                    <div class="text-white/15 text-lg font-semibold">No entries yet</div>
                                    <div class="text-white/10 text-sm mt-1">Click "New Entry" to add one.</div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            @if($entries->hasPages())
            <div class="flex items-center justify-between mt-6 px-2">
                <div class="text-xs text-white/25">
                    Showing {{ $entries->firstItem() }}–{{ $entries->lastItem() }} of {{ $entries->total() }} entries
                </div>
                <div class="flex items-center gap-1">
                    {{-- Previous --}}
                    @if(!$entries->onFirstPage())
                    <a href="{{ $entries->previousPageUrl() }}"
                       class="w-9 h-9 rounded-lg bg-white/[0.04] border border-white/[0.06] flex items-center justify-center text-white/40 hover:bg-white/[0.08] hover:text-white/70 transition-all">
                        <i class="fa-solid fa-chevron-left text-[10px]"></i>
                    </a>
                    @else
                    <span class="w-9 h-9 rounded-lg bg-white/[0.02] border border-white/[0.05] flex items-center justify-center text-white/10 cursor-not-allowed">
                        <i class="fa-solid fa-chevron-left text-[10px]"></i>
                    </span>
                    @endif

                    @php
                        $onEachSide = 1;
                        $start = max(1, $entries->currentPage() - $onEachSide);
                        $end = min($entries->lastPage(), $entries->currentPage() + $onEachSide);
                    @endphp

                    {{-- First page + ellipsis --}}
                    @if($start > 1)
                        <a href="{{ $entries->url(1) }}"
                           class="w-9 h-9 rounded-lg bg-white/[0.04] border border-white/[0.06] flex items-center justify-center text-sm text-white/40 hover:bg-white/[0.08] hover:text-white/70 transition-all">
                            1
                        </a>
                        @if($start > 2)
                            <span class="w-9 h-9 flex items-center justify-center text-white/15 text-xs">...</span>
                        @endif
                    @endif

                    {{-- Page numbers --}}
                    @for($i = $start; $i <= $end; $i++)
                        @if($i === $entries->currentPage())
                            <span class="w-9 h-9 rounded-lg bg-blue-600 border border-blue-500/30 flex items-center justify-center text-sm font-bold text-white shadow-lg shadow-blue-900/30">
                                {{ $i }}
                            </span>
                        @else
                            <a href="{{ $entries->url($i) }}"
                               class="w-9 h-9 rounded-lg bg-white/[0.04] border border-white/[0.06] flex items-center justify-center text-sm text-white/40 hover:bg-white/[0.08] hover:text-white/70 transition-all">
                                {{ $i }}
                            </a>
                        @endif
                    @endfor

                    {{-- Last page + ellipsis --}}
                    @if($end < $entries->lastPage())
                        @if($end < $entries->lastPage() - 1)
                            <span class="w-9 h-9 flex items-center justify-center text-white/15 text-xs">...</span>
                        @endif
                        <a href="{{ $entries->url($entries->lastPage()) }}"
                           class="w-9 h-9 rounded-lg bg-white/[0.04] border border-white/[0.06] flex items-center justify-center text-sm text-white/40 hover:bg-white/[0.08] hover:text-white/70 transition-all">
                            {{ $entries->lastPage() }}
                        </a>
                    @endif

                    {{-- Next --}}
                    @if($entries->hasMorePages())
                    <a href="{{ $entries->nextPageUrl() }}"
                       class="w-9 h-9 rounded-lg bg-white/[0.04] border border-white/[0.06] flex items-center justify-center text-white/40 hover:bg-white/[0.08] hover:text-white/70 transition-all">
                        <i class="fa-solid fa-chevron-right text-[10px]"></i>
                    </a>
                    @else
                    <span class="w-9 h-9 rounded-lg bg-white/[0.02] border border-white/[0.05] flex items-center justify-center text-white/10 cursor-not-allowed">
                        <i class="fa-solid fa-chevron-right text-[10px]"></i>
                    </span>
                    @endif
                </div>
            </div>
            @endif

        </div>

        <!-- ==================== MODAL ==================== -->
        <template x-teleport="body">
            <div x-show="showModal"
                 x-cloak
                 @keydown.escape.window="closeModal()"
                 class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
                 x-transition:enter="backdrop-enter"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="backdrop-leave"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0">

                <div class="modal-backdrop absolute inset-0" @click="closeModal()"></div>

                <div x-show="showModal"
                     @click.stop
                     class="relative w-full max-w-xl rounded-2xl border border-white/[0.08] shadow-2xl shadow-black/60 modal-enter"
                     style="background: linear-gradient(165deg, rgba(20,20,25,0.98) 0%, rgba(10,10,12,0.99) 100%);">

                    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-48 h-px bg-gradient-to-r from-transparent via-blue-500/60 to-transparent"></div>

                    <form method="POST"
                          :action="{{ route('driver-manager.time-keeping.store') }}"
                          @submit.prevent="syncAndSubmit($event)">

                        @csrf

                        <input type="hidden" name="is_leave" :value="form.is_leave">
                        <input type="hidden" name="sick" :value="form.sick">
                        <input type="hidden" name="vacation" :value="form.vacation">

                        <div class="flex items-center justify-between px-7 pt-7 pb-2">
                            <div>
                                <h3 class="text-xl font-black tracking-tight">New Time Entry</h3>
                                <p class="text-white/30 text-xs mt-1">Fill in shift details or log a leave.</p>
                            </div>
                            <button type="button" @click="closeModal()"
                                class="w-9 h-9 rounded-lg bg-white/5 hover:bg-white/10 border border-white/5 flex items-center justify-center transition-all active:scale-90">
                                <i class="fa-solid fa-xmark text-white/40 text-sm"></i>
                            </button>
                        </div>

                        <div class="mx-7 my-4 h-px bg-white/[0.06]"></div>

                        <div class="px-7 pb-2 space-y-5">

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-black uppercase tracking-[0.15em] text-white/40 mb-2">Driver</label>
                                    <select name="driver_id" x-model="form.driver_id"
                                        class="w-full bg-white/[0.04] border border-white/[0.08] rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-blue-500/50 focus:ring-1 focus:ring-blue-500/20 transition-all">
                                        <option value="" disabled selected class="bg-[#111]">Select driver</option>
                                        <template x-for="d in drivers" :key="d.id">
                                            <option :value="d.id" x-text="d.name" class="bg-[#111]"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black uppercase tracking-[0.15em] text-white/40 mb-2">Date</label>
                                    <input type="date" name="date" x-model="form.date"
                                        class="w-full bg-white/[0.04] border border-white/[0.08] rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-blue-500/50 focus:ring-1 focus:ring-blue-500/20 transition-all">
                                </div>
                            </div>

                            <div class="flex items-center justify-between py-1">
                                <div>
                                    <span class="text-sm font-semibold text-white/80">Mark as Leave</span>
                                    <p class="text-[11px] text-white/25 mt-0.5">No shift will be recorded for this date.</p>
                                </div>
                                <button type="button" @click="isLeave = !isLeave; if (!isLeave) { selectedLeave = ''; form.sick = '0'; form.vacation = '0'; }"
                                    :class="isLeave ? 'bg-blue-600' : 'bg-white/10'"
                                    class="relative w-11 h-6 rounded-full toggle-track flex-shrink-0">
                                    <span :class="isLeave ? 'translate-x-5' : 'translate-x-0.5'"
                                        class="absolute top-0.5 left-0 w-5 h-5 bg-white rounded-full shadow toggle-thumb"></span>
                                </button>
                            </div>

                            <div x-show="!isLeave" x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 -translate-y-2"
                                 x-transition:enter-end="opacity-100 translate-y-0"
                                 x-transition:leave="transition ease-in duration-150"
                                 x-transition:leave-start="opacity-100 translate-y-0"
                                 x-transition:leave-end="opacity-0 -translate-y-2"
                                 class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-black uppercase tracking-[0.15em] text-white/40 mb-2">
                                        <i class="fa-solid fa-right-to-bracket text-emerald-500/60 mr-1"></i> Time In
                                    </label>
                                    <input type="time" name="time_in" x-model="form.time_in"
                                        class="w-full bg-white/[0.04] border border-white/[0.08] rounded-xl px-4 py-2.5 text-sm text-emerald-400 font-mono font-semibold focus:outline-none focus:border-emerald-500/40 focus:ring-1 focus:ring-emerald-500/20 transition-all">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black uppercase tracking-[0.15em] text-white/40 mb-2">
                                        <i class="fa-solid fa-right-from-bracket text-amber-500/60 mr-1"></i> Time Out
                                    </label>
                                    <input type="time" name="time_out" x-model="form.time_out"
                                        class="w-full bg-white/[0.04] border border-white/[0.08] rounded-xl px-4 py-2.5 text-sm text-amber-400 font-mono font-semibold focus:outline-none focus:border-amber-500/40 focus:ring-1 focus:ring-amber-500/20 transition-all">
                                </div>
                            </div>

                            <div x-show="!isLeave && form.time_in && form.time_out"
                                 x-transition
                                 class="flex items-center gap-4 p-3.5 rounded-xl bg-white/[0.02] border border-white/[0.06]">
                                <div class="flex items-center gap-2">
                                    <div class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></div>
                                    <span class="text-[10px] font-black uppercase tracking-[0.15em] text-white/30">Computed</span>
                                </div>
                                <div class="h-5 w-px bg-white/10"></div>
                                <div class="flex items-center gap-4">
                                    <span class="text-sm text-white/60">Total: <span class="text-white font-bold" x-text="calcHours() + ' hrs'"></span></span>
                                    <span class="text-sm text-white/60">OT: <span :class="parseFloat(calcOvertime()) > 0 ? 'text-blue-400 font-bold' : 'text-white/30'" x-text="calcOvertime() + ' hrs'"></span></span>
                                </div>
                            </div>

                            <div x-show="isLeave"
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 -translate-y-2"
                                 x-transition:enter-end="opacity-100 translate-y-0"
                                 x-transition:leave="transition ease-in duration-150"
                                 x-transition:leave-start="opacity-100 translate-y-0"
                                 x-transition:leave-end="opacity-0 -translate-y-2"
                                 class="space-y-3">
                                <label class="block text-[10px] font-black uppercase tracking-[0.15em] text-white/40">Leave Type</label>
                                <div class="grid grid-cols-2 gap-3">
                                    <button type="button" @click="pickLeave('sick')"
                                        :class="selectedLeave === 'sick' ? 'border-red-500/40 bg-red-500/[0.08] ring-1 ring-red-500/20' : 'border-white/[0.06] bg-white/[0.02] hover:bg-white/[0.04]'"
                                        class="flex items-center gap-3 p-4 rounded-xl border transition-all active:scale-[0.98]">
                                        <div class="w-9 h-9 rounded-lg flex items-center justify-center"
                                             :class="selectedLeave === 'sick' ? 'bg-red-500/20' : 'bg-white/5'">
                                            <i class="fa-solid fa-thermometer-half text-sm"
                                               :class="selectedLeave === 'sick' ? 'text-red-400' : 'text-white/25'"></i>
                                        </div>
                                        <div class="text-left">
                                            <div class="text-sm font-bold" :class="selectedLeave === 'sick' ? 'text-red-300' : 'text-white/50'">Sick Leave</div>
                                            <div class="text-[10px] text-white/20">Medical / health</div>
                                        </div>
                                    </button>
                                    <button type="button" @click="pickLeave('vacation')"
                                        :class="selectedLeave === 'vacation' ? 'border-cyan-500/40 bg-cyan-500/[0.08] ring-1 ring-cyan-500/20' : 'border-white/[0.06] bg-white/[0.02] hover:bg-white/[0.04]'"
                                        class="flex items-center gap-3 p-4 rounded-xl border transition-all active:scale-[0.98]">
                                        <div class="w-9 h-9 rounded-lg flex items-center justify-center"
                                             :class="selectedLeave === 'vacation' ? 'bg-cyan-500/20' : 'bg-white/5'">
                                            <i class="fa-solid fa-umbrella-beach text-sm"
                                               :class="selectedLeave === 'vacation' ? 'text-cyan-400' : 'text-white/25'"></i>
                                        </div>
                                        <div class="text-left">
                                            <div class="text-sm font-bold" :class="selectedLeave === 'vacation' ? 'text-cyan-300' : 'text-white/50'">Vacation Leave</div>
                                            <div class="text-[10px] text-white/20">Personal / time off</div>
                                        </div>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 px-7 py-6 mt-2">
                            <button type="button" @click="closeModal()"
                                class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white/50 hover:text-white/80 hover:bg-white/5 border border-white/[0.06] transition-all active:scale-95">
                                Cancel
                            </button>
                            <button type="submit"
                                :disabled="!form.driver_id || !form.date || (!isLeave && (!form.time_in || !form.time_out)) || (isLeave && !selectedLeave)"
                                :class="form.driver_id && form.date && ((isLeave && selectedLeave) || (!isLeave && form.time_in && form.time_out))
                                    ? 'bg-blue-600 hover:bg-blue-500 shadow-lg shadow-blue-900/40 text-white'
                                    : 'bg-white/5 text-white/20 cursor-not-allowed'"
                                class="px-6 py-2.5 rounded-xl text-sm font-bold transition-all active:scale-95">
                                <i class="fa-solid fa-check mr-2"></i>
                                <span x-text="isLeave ? 'Log Leave' : 'Save Entry'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </template>

    </main>
</body>
</html>
