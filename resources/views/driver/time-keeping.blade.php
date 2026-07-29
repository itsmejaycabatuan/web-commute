<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartCommute | Timekeeping</title>
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

        @keyframes pulse-ring {
            0% { transform: scale(1); opacity: 0.4; }
            100% { transform: scale(1.8); opacity: 0; }
        }
        .pulse-ring {
            animation: pulse-ring 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        @keyframes slide-up {
            0% { opacity: 0; transform: translateY(8px); }
            100% { opacity: 1; transform: translateY(0); }
        }
        .card-animate {
            animation: slide-up 0.3s ease-out forwards;
            opacity: 0;
        }

        ::-webkit-scrollbar { width: 3px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #333; border-radius: 10px; }
    </style>
</head>

<body class="antialiased text-white" x-data="{ open: false, showLogoutModal: false }" @resize.window="if(window.innerWidth >= 768) open = true">

    @include('driver.layout.sidebar')

    <main :class="open ? 'md:ml-72' : 'md:ml-20'" class="sidebar-transition pt-4 sm:pt-8 pr-3 sm:pr-8 pb-8 pl-3 sm:pl-8 min-h-screen mb-16 md:mb-12">

        <!-- Flash Messages -->
        @if (session('success'))
            <div class="mb-4 sm:mb-6 px-3.5 sm:px-4 py-3 rounded-xl border border-emerald-500/20 bg-emerald-500/5 text-emerald-400 text-[10px] sm:text-[11px] font-medium flex items-center gap-2">
                <i class="fa-solid fa-circle-check text-[9px] sm:text-[10px]"></i>
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="mb-4 sm:mb-6 px-3.5 sm:px-4 py-3 rounded-xl border border-red-500/20 bg-red-500/5 text-red-400 text-[10px] sm:text-[11px] font-medium flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation text-[9px] sm:text-[10px]"></i>
                {{ session('error') }}
            </div>
        @endif

        @php
            $weekDays = [];
            for ($i = 0; $i < 7; $i++) {
                $weekDays[] = $weekStart->copy()->addDays($i);
            }
            $recordsByDate = $weekRecords->keyBy(fn($r) => \Carbon\Carbon::parse($r->date)->toDateString());
        @endphp

        <!-- Page Header -->
        <div class="mb-5 sm:mb-8">
            <div class="flex items-center justify-between mb-1.5">
                <div class="flex items-center gap-2">
                    <span class="text-[9px] font-bold uppercase tracking-[0.15em] text-[#444]">Driver Console</span>
                </div>
            </div>
            <h1 class="text-xl sm:text-2xl md:text-3xl font-black tracking-tight">Time<span class="text-blue-400">keeping</span></h1>
            <p class="text-[10px] sm:text-[11px] text-[#555] mt-1">
                {{ now()->format('l, F j, Y') }}
            </p>
        </div>

        <!-- ══════════ WEEKLY SUMMARY BAR (Mobile) ══════════ -->
        <div class="md:hidden glass-card rounded-2xl p-4 mb-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[8px] font-bold uppercase tracking-[0.15em] text-[#444] mb-1">Week Total</p>
                    <p class="text-2xl font-black text-white">{{ number_format($weekHours, 1) }}<span class="text-[11px] font-bold text-[#555] ml-1">hrs</span></p>
                </div>
                <div class="flex gap-1.5">
                    @php
                        $completedDays = 0;
                        $totalPastDays = 0;
                        foreach($weekDays as $d) {
                            if(!$d->isAfter(today())) {
                                $totalPastDays++;
                                $r = $recordsByDate[$d->toDateString()] ?? null;
                                if($r && $r->time_in && $r->time_out) $completedDays++;
                            }
                        }
                        $weekProgress = $totalPastDays > 0 ? round(($completedDays / $totalPastDays) * 100) : 0;
                    @endphp
                    <div class="text-center">
                        <div class="w-12 h-12 rounded-xl bg-[#0e0e0e] border border-[#1a1a1a] flex items-center justify-center mb-1">
                            <span class="text-sm font-black text-emerald-400">{{ $completedDays }}</span>
                        </div>
                        <span class="text-[7px] font-bold uppercase text-[#333]">Done</span>
                    </div>
                    <div class="text-center">
                        <div class="w-12 h-12 rounded-xl bg-[#0e0e0e] border border-[#1a1a1a] flex items-center justify-center mb-1 relative overflow-hidden">
                            <!-- Mini progress ring -->
                            <svg class="w-10 h-10 absolute inset-0 m-auto" viewBox="0 0 36 36">
                                <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#1a1a1a" stroke-width="2.5"/>
                                <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#3b82f6" stroke-width="2.5" stroke-dasharray="{{ $weekProgress }}, 100" stroke-linecap="round"/>
                            </svg>
                            <span class="text-[10px] font-black text-blue-400 relative z-10">{{ $weekProgress }}%</span>
                        </div>
                        <span class="text-[7px] font-bold uppercase text-[#333]">Progress</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══════════ WEEKLY CONTENT ══════════ -->
        <div class="glass-card rounded-[1rem] sm:rounded-[1.5rem] overflow-hidden">

            <!-- Week Navigation Header -->
            <div class="p-4 sm:p-5 md:p-6 pb-0">
                <div class="flex items-center justify-between mb-4 sm:mb-5">
                    <div class="flex items-center gap-2 sm:gap-2.5">
                        <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-[#111] border border-[#1e1e1e] flex items-center justify-center">
                            <i class="fa-solid fa-calendar-week text-[9px] sm:text-[10px] text-[#555]"></i>
                        </div>
                        <span class="text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-[#555]">Weekly Log</span>
                    </div>
                    <div class="flex items-center gap-1.5 sm:gap-2">
                        <a href="{{ route('driver.timekeeping', ['week' => $prevWeek]) }}"
                            class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-[#111] border border-[#1e1e1e] flex items-center justify-center hover:bg-[#1a1a1a] hover:border-[#333] transition active:scale-95">
                            <i class="fa-solid fa-chevron-left text-[8px] sm:text-[9px] text-[#555]"></i>
                        </a>
                        <div class="px-2 sm:px-3 py-1.5 rounded-lg bg-[#111] border border-[#1e1e1e] min-w-[120px] sm:min-w-[160px] text-center">
                            <span class="text-[9px] sm:text-[10px] font-bold text-[#888]">{{ $weekLabel }}</span>
                        </div>
                        @if(!$isCurrentWeek)
                            <a href="{{ route('driver.timekeeping') }}"
                                class="px-2 sm:px-3 py-1.5 rounded-lg bg-blue-500/10 border border-blue-500/15 text-[8px] sm:text-[9px] font-bold text-blue-400 hover:bg-blue-500/20 transition active:scale-95">
                                Today
                            </a>
                        @endif
                        @if(!$isCurrentWeek)
                            <a href="{{ route('driver.timekeeping', ['week' => $nextWeek]) }}"
                                class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-[#111] border border-[#1e1e1e] flex items-center justify-center hover:bg-[#1a1a1a] hover:border-[#333] transition active:scale-95">
                                <i class="fa-solid fa-chevron-right text-[8px] sm:text-[9px] text-[#555]"></i>
                            </a>
                        @else
                            <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-[#0a0a0a] border border-[#151515] flex items-center justify-center">
                                <i class="fa-solid fa-chevron-right text-[8px] sm:text-[9px] text-[#222]"></i>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- ══════════ MOBILE CARD VIEW ══════════ -->
            <div class="md:hidden p-3 space-y-2">
                @foreach($weekDays as $index => $day)
                    @php
                        $dateStr = $day->toDateString();
                        $record = $recordsByDate[$dateStr] ?? null;
                        $isToday = $day->isToday();
                        $isFuture = $day->isAfter(today());
                    @endphp
                    <div class="card-animate rounded-xl border overflow-hidden {{ $isToday ? 'border-blue-500/20 bg-blue-500/[0.03]' : ($isFuture ? 'border-[#141414] bg-[#0e0e0e] opacity-40' : 'border-[#1a1a1a] bg-[#111]') }}"
                         style="animation-delay: {{ $index * 40 }}ms">

                        <!-- Card Top: Date + Status -->
                        <div class="flex items-center justify-between px-3.5 py-2.5">
                            <div class="flex items-center gap-2.5">
                                <div class="relative">
                                    <div class="w-9 h-9 rounded-lg {{ $isToday ? 'bg-blue-500/10 border border-blue-500/20' : 'bg-[#0a0a0a] border border-[#1a1a1a]' }} flex flex-col items-center justify-center">
                                        <span class="text-[8px] font-bold uppercase {{ $isToday ? 'text-blue-400' : 'text-[#444]' }}">{{ $day->format('M') }}</span>
                                        <span class="text-sm font-black -mt-0.5 {{ $isToday ? 'text-blue-300' : 'text-[#888]' }}">{{ $day->format('d') }}</span>
                                    </div>
                                    @if($isToday)
                                        <div class="absolute -top-0.5 -right-0.5 w-2 h-2 bg-blue-400 rounded-full">
                                            <div class="w-2 h-2 bg-blue-400 rounded-full pulse-ring"></div>
                                        </div>
                                    @endif
                                </div>
                                <div>
                                    <p class="text-[11px] font-bold {{ $isToday ? 'text-blue-400' : 'text-[#ccc]' }}">{{ $day->format('l') }}</p>
                                    <p class="text-[8px] text-[#444] font-medium">{{ $day->format('Y') }}</p>
                                </div>
                            </div>
                            <div>
                                @if($isFuture)
                                    <span class="text-[7px] bg-[#0a0a0a] text-[#333] border border-[#151515] px-2 py-0.5 rounded-md font-bold uppercase">Upcoming</span>
                                @elseif($record && $record->sick)
                                    <span class="text-[7px] bg-amber-500/10 text-amber-400 border border-amber-500/15 px-2 py-0.5 rounded-md font-bold uppercase">Sick</span>
                                @elseif($record && $record->vacation)
                                    <span class="text-[7px] bg-blue-500/10 text-blue-400 border border-blue-500/15 px-2 py-0.5 rounded-md font-bold uppercase">Vacation</span>
                                @elseif($record && $record->time_in && $record->time_out)
                                    <span class="text-[7px] bg-emerald-500/10 text-emerald-400 border border-emerald-500/15 px-2 py-0.5 rounded-md font-bold uppercase">Complete</span>
                                @elseif($record && $record->time_in)
                                    <span class="text-[7px] bg-amber-500/10 text-amber-400 border border-amber-500/15 px-2 py-0.5 rounded-md font-bold uppercase flex items-center gap-1">
                                        <span class="w-1 h-1 bg-amber-400 rounded-full animate-pulse"></span>Active
                                    </span>
                                @elseif(!$isFuture && !$isToday)
                                    <span class="text-[7px] bg-[#0a0a0a] text-[#444] border border-[#1e1e1e] px-2 py-0.5 rounded-md font-bold uppercase">Absent</span>
                                @else
                                    <span class="text-[7px] bg-[#0a0a0a] text-[#333] border border-[#151515] px-2 py-0.5 rounded-md font-bold uppercase">Waiting</span>
                                @endif
                            </div>
                        </div>

                        <!-- Card Bottom: Time Data -->
                        @if(!$isFuture)
                            <div class="flex items-stretch border-t {{ $isToday ? 'border-blue-500/10' : 'border-[#161616]' }}">
                                <!-- Time In -->
                                <div class="flex-1 px-3.5 py-2.5 flex items-center gap-2 border-r {{ $isToday ? 'border-blue-500/10' : 'border-[#161616]' }}">
                                    <i class="fa-solid fa-right-to-bracket text-[8px] text-[#333]"></i>
                                    <div>
                                        <p class="text-[7px] font-bold uppercase text-[#333]">In</p>
                                        <p class="text-[11px] font-bold @if($record && $record->time_in) text-[#aaa] @else text-[#282828] @endif">
                                            @if($record && $record->time_in)
                                                {{ \Carbon\Carbon::parse($record->time_in)->format('h:i A') }}
                                            @else
                                                —
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <!-- Time Out -->
                                <div class="flex-1 px-3.5 py-2.5 flex items-center gap-2 border-r {{ $isToday ? 'border-blue-500/10' : 'border-[#161616]' }}">
                                    <i class="fa-solid fa-right-from-bracket text-[8px] text-[#333]"></i>
                                    <div>
                                        <p class="text-[7px] font-bold uppercase text-[#333]">Out</p>
                                        <p class="text-[11px] font-bold @if($record && $record->time_out) text-[#aaa] @else text-[#282828] @endif">
                                            @if($record && $record->time_out)
                                                {{ \Carbon\Carbon::parse($record->time_out)->format('h:i A') }}
                                            @else
                                                —
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <!-- Hours -->
                                <div class="flex-1 px-3.5 py-2.5 flex items-center gap-2 border-r {{ $isToday ? 'border-blue-500/10' : 'border-[#161616]' }}">
                                    <i class="fa-regular fa-clock text-[8px] text-[#333]"></i>
                                    <div>
                                        <p class="text-[7px] font-bold uppercase text-[#333]">Hrs</p>
                                        <p class="text-[11px] font-bold @if($record && $record->hours_worked) text-white @else text-[#282828] @endif">
                                            @if($record && $record->hours_worked)
                                                {{ number_format($record->hours_worked, 1) }}
                                            @else
                                                —
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <!-- OT -->
                                <div class="flex-1 px-3.5 py-2.5 flex items-center gap-2">
                                    <i class="fa-solid fa-bolt text-[8px] text-[#333]"></i>
                                    <div>
                                        <p class="text-[7px] font-bold uppercase text-[#333]">OT</p>
                                        <p class="text-[11px] font-bold @if($record && $record->overtime_hours && $record->overtime_hours > 0) text-amber-400 @else text-[#282828] @endif">
                                            @if($record && $record->overtime_hours && $record->overtime_hours > 0)
                                                +{{ number_format($record->overtime_hours, 1) }}
                                            @else
                                                —
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <!-- ══════════ DESKTOP TABLE VIEW ══════════ -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-[9px] uppercase tracking-[0.15em] text-[#444] border-b border-[#1e1e1e]">
                            <th class="px-6 py-3 font-bold">Date</th>
                            <th class="px-6 py-3 font-bold">Time In</th>
                            <th class="px-6 py-3 font-bold">Time Out</th>
                            <th class="px-6 py-3 font-bold">Hours</th>
                            <th class="px-6 py-3 font-bold">OT</th>
                            <th class="px-6 py-3 font-bold">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#1a1a1a]">
                        @foreach($weekDays as $day)
                            @php
                                $dateStr = $day->toDateString();
                                $record = $recordsByDate[$dateStr] ?? null;
                                $isToday = $day->isToday();
                                $isFuture = $day->isAfter(today());
                            @endphp
                            <tr class="table-row @if($isToday) bg-blue-500/[0.03] @elseif($isFuture) opacity-30 @endif">
                                <td class="px-6 py-3.5">
                                    <div class="flex items-center gap-2.5">
                                        @if($isToday)
                                            <div class="w-1.5 h-1.5 rounded-full bg-blue-400 shrink-0"></div>
                                        @else
                                            <div class="w-1.5 h-1.5 rounded-full bg-[#222] shrink-0"></div>
                                        @endif
                                        <div>
                                            <p class="text-[11px] font-bold @if($isToday) text-blue-400 @else text-[#ccc] @endif">{{ $day->format('M d, Y') }}</p>
                                            <p class="text-[8px] text-[#444] font-bold uppercase">{{ $day->format('l') }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-3.5">
                                    @if($record && $record->time_in)
                                        <span class="text-[11px] font-bold text-[#888]">{{ \Carbon\Carbon::parse($record->time_in)->format('h:i A') }}</span>
                                    @else
                                        <span class="text-[11px] text-[#333]">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3.5">
                                    @if($record && $record->time_out)
                                        <span class="text-[11px] font-bold text-[#888]">{{ \Carbon\Carbon::parse($record->time_out)->format('h:i A') }}</span>
                                    @else
                                        <span class="text-[11px] text-[#333]">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3.5">
                                    @if($record && $record->hours_worked)
                                        <span class="text-[11px] font-bold text-white">{{ number_format($record->hours_worked, 1) }}</span>
                                    @else
                                        <span class="text-[11px] text-[#333]">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3.5">
                                    @if($record && $record->overtime_hours && $record->overtime_hours > 0)
                                        <span class="text-[10px] font-bold text-amber-400">+{{ number_format($record->overtime_hours, 1) }}</span>
                                    @else
                                        <span class="text-[10px] text-[#333]">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3.5">
                                    @if($isFuture)
                                        <span class="text-[8px] bg-[#0a0a0a] text-[#333] border border-[#151515] px-2 py-0.5 rounded-md font-bold uppercase">Upcoming</span>
                                    @elseif($record && $record->sick)
                                        <span class="text-[8px] bg-amber-500/10 text-amber-400 border border-amber-500/15 px-2 py-0.5 rounded-md font-bold uppercase">Sick</span>
                                    @elseif($record && $record->vacation)
                                        <span class="text-[8px] bg-blue-500/10 text-blue-400 border border-blue-500/15 px-2 py-0.5 rounded-md font-bold uppercase">Vacation</span>
                                    @elseif($record && $record->time_in && $record->time_out)
                                        <span class="text-[8px] bg-emerald-500/10 text-emerald-400 border border-emerald-500/15 px-2 py-0.5 rounded-md font-bold uppercase">Complete</span>
                                    @elseif($record && $record->time_in)
                                        <span class="text-[8px] bg-amber-500/10 text-amber-400 border border-amber-500/15 px-2 py-0.5 rounded-md font-bold uppercase">Active</span>
                                    @elseif(!$isFuture && !$isToday)
                                        <span class="text-[8px] bg-[#0a0a0a] text-[#444] border border-[#1e1e1e] px-2 py-0.5 rounded-md font-bold uppercase">Absent</span>
                                    @else
                                        <span class="text-[8px] bg-[#0a0a0a] text-[#333] border border-[#151515] px-2 py-0.5 rounded-md font-bold uppercase">Waiting</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Table Footer -->
            <div class="px-4 sm:px-6 py-3 sm:py-4 border-t border-[#1e1e1e] flex items-center justify-between">
                <div class="flex items-center gap-3 sm:gap-4">
                    <span class="text-[8px] sm:text-[9px] text-[#333] font-bold uppercase tracking-wider flex items-center gap-1">
                        <i class="fa-solid fa-circle text-[4px] sm:text-[5px] text-blue-400"></i> Today
                    </span>
                    <span class="text-[8px] sm:text-[9px] text-[#333] font-bold uppercase tracking-wider">
                        Week Total: <span class="text-[#555]">{{ number_format($weekHours, 1) }} hrs</span>
                    </span>
                </div>
                <span class="text-[8px] sm:text-[9px] text-[#222] font-bold uppercase tracking-wider">7 days</span>
            </div>
        </div>

        <!-- Footer Note -->
        <p class="text-center text-[7px] sm:text-[8px] text-[#222] uppercase tracking-[0.2em] pt-5 sm:pt-6">
            SmartCommute Driver Systems &bull; Timekeeping Module
        </p>

    </main>

    <!-- ══════════ LOGOUT MODAL ══════════ -->
    <div x-show="showLogoutModal"
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/80"
        x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" style="display: none;">

        <div @click.away="showLogoutModal = false"
            class="glass-panel p-6 sm:p-8 rounded-[1.5rem] sm:rounded-[2rem] max-w-sm w-full mx-4">
            <div class="text-center">
                <div class="w-12 h-12 sm:w-14 sm:h-14 bg-red-500/10 rounded-xl sm:rounded-2xl flex items-center justify-center mx-auto mb-4 sm:mb-5 border border-red-500/20">
                    <i class="fa-solid fa-power-off text-red-400 text-base sm:text-lg"></i>
                </div>
                <h3 class="text-base sm:text-lg font-bold text-white mb-1 sm:mb-1.5">End Session?</h3>
                <p class="text-[10px] sm:text-xs text-[#666] mb-5 sm:mb-7">Are you sure you want to exit the Driver Console?</p>

                <div class="flex gap-2 sm:gap-2.5">
                    <button @click="showLogoutModal = false"
                        class="flex-1 py-2.5 sm:py-3 rounded-xl bg-[#1a1a1a] border border-[#2a2a2a] text-white text-[9px] sm:text-[10px] font-bold uppercase tracking-widest hover:bg-[#222] transition active:scale-[0.98]">
                        Cancel
                    </button>
                    <form action="{{ route('users.logout') }}" method="POST" class="flex-1">
                        @csrf
                        <button type="submit"
                            class="w-full py-2.5 sm:py-3 rounded-xl bg-red-600 text-white text-[9px] sm:text-[10px] font-bold uppercase tracking-widest hover:bg-red-700 transition active:scale-[0.98]">
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
