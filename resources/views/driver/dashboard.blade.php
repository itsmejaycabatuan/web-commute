<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SmartCommute | Driver Console</title>

    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>

    @include('partials.head-scripts')

    <style>
        .table-row:hover {
            background: #f8fafc;
        }

        .dark .table-row:hover {
            background: #1a1a1a;
        }
    </style>
</head>

<body class="antialiased text-gray-900 dark:text-white" x-data>

    <div x-data="{ showLogoutModal: false }" @keydown.escape.window="showLogoutModal = false">

        <x-layout.sidebar />

        <main :class="$store.sidebar.open ? 'md:ml-72' : 'md:ml-20'"
            class="sidebar-transition pt-8 pr-4 sm:pr-8 pb-8 pl-4 sm:pl-8 min-h-screen mb-12">

            <!-- ── Mobile: Driver Identity Card ── -->
            <div class="lg:hidden mb-5">
                <div class="glass-card p-4 rounded-[1.25rem]">
                    <div class="flex items-center gap-3.5">
                        <div class="w-11 h-11 bg-blue-600 rounded-xl flex items-center justify-center shrink-0">
                            <span
                                class="text-sm font-black text-white">{{ strtoupper(substr(explode('@', Auth::user()->email)[0], 0, 1)) }}</span>
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-sm font-bold text-gray-900 dark:text-white truncate">
                                {{ $driver->name ?? 'Driver' }}</h2>
                            <p class="text-[10px] text-gray-500 dark:text-[#555] truncate">{{ Auth::user()->email }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 pt-3.5 mt-3.5 border-t border-gray-200 dark:border-[#1e1e1e]">
                        <i class="fa-solid fa-route text-[8px] text-blue-500 dark:text-blue-400"></i>
                        <span class="text-[10px] text-gray-600 dark:text-[#888] font-bold">Minglanilla - IT Park</span>
                        <span class="text-gray-300 dark:text-[#555]">•</span>
                        <span
                            class="font-mono text-[9px] text-gray-400 dark:text-[#444]">{{ $driver->driver_code ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>

            <!-- ── Page Header (desktop) ── -->
            <div class="hidden lg:block mb-8">
                <div class="flex items-center gap-2 mb-1.5">
                    <span
                        class="text-[9px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Welcome
                        back,</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-gray-900 dark:text-white">
                    {{ $driver->name ?? 'Driver' }}</h1>
                <p class="text-[11px] text-gray-500 dark:text-[#555] mt-1 flex items-center gap-2">
                    <i class="fa-solid fa-route text-[9px] text-blue-500 dark:text-blue-400"></i>
                    Route: <span class="text-gray-700 dark:text-[#888] font-bold">Minglanilla - IT Park</span>
                    <span class="text-gray-300 dark:text-[#555]">•</span>
                    <span
                        class="font-mono text-[10px] text-gray-400 dark:text-[#444]">{{ $driver->driver_code ?? 'N/A' }}</span>
                </p>
            </div>

            <!-- ══════════ STAT CARDS ══════════ -->
            <div class="grid grid-cols-2 xl:grid-cols-3 gap-3 sm:gap-4 mb-5 sm:mb-6">

                <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-blue-500">
                    <div class="flex items-center gap-2 mb-2 sm:mb-3">
                        <div class="w-6 h-6 rounded-md bg-blue-500/10 flex items-center justify-center">
                            <i class="fa-solid fa-road text-[8px] text-blue-500 dark:text-blue-400"></i>
                        </div>
                        <span
                            class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Distance
                            Today</span>
                    </div>
                    <div class="flex items-baseline gap-1 sm:gap-1.5">
                        <span
                            class="text-2xl sm:text-3xl font-black tracking-tight text-gray-900 dark:text-white">{{ number_format($total_distance, 1) }}</span>
                        <span class="text-xs sm:text-sm font-bold text-blue-500 dark:text-blue-400">km</span>
                    </div>
                </div>

                <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-emerald-500">
                    <div class="flex items-center gap-2 mb-2 sm:mb-3">
                        <div class="w-6 h-6 rounded-md bg-emerald-500/10 flex items-center justify-center">
                            <i class="fa-solid fa-clock text-[8px] text-emerald-500 dark:text-emerald-400"></i>
                        </div>
                        <span
                            class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Hours
                            Today</span>
                    </div>
                    <div class="flex items-baseline gap-1 sm:gap-1.5">
                        <span
                            class="text-2xl sm:text-3xl font-black tracking-tight text-gray-900 dark:text-white">{{ $todayRecord->hours_worked ?? '0.0' }}</span>
                        <span class="text-xs sm:text-sm font-bold text-emerald-500 dark:text-emerald-400">hrs</span>
                    </div>
                </div>

                <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-amber-500">
                    <div class="flex items-center gap-2 mb-2 sm:mb-3">
                        <div class="w-6 h-6 rounded-md bg-amber-500/10 flex items-center justify-center">
                            <i class="fa-solid fa-fire text-[8px] text-amber-500 dark:text-amber-400"></i>
                        </div>
                        <span
                            class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">OT
                            This Week</span>
                    </div>
                    <div class="flex items-baseline gap-1 sm:gap-1.5">
                        <span
                            class="text-2xl sm:text-3xl font-black tracking-tight text-gray-900 dark:text-white">{{ number_format($weekOvertime ?? 0, 1) }}</span>
                        <span class="text-xs sm:text-sm font-bold text-amber-500 dark:text-amber-400">hrs</span>
                    </div>
                </div>

                <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-purple-500">
                    <div class="flex items-center gap-2 mb-2 sm:mb-3">
                        <div class="w-6 h-6 rounded-md bg-purple-500/10 flex items-center justify-center">
                            <i class="fa-solid fa-calendar-week text-[8px] text-purple-500 dark:text-purple-400"></i>
                        </div>
                        <span
                            class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Hours
                            This Week</span>
                    </div>
                    <div class="flex items-baseline gap-1 sm:gap-1.5">
                        <span
                            class="text-2xl sm:text-3xl font-black tracking-tight text-gray-900 dark:text-white">{{ number_format($weekHours ?? 0, 1) }}</span>
                        <span class="text-xs sm:text-sm font-bold text-purple-500 dark:text-purple-400">hrs</span>
                    </div>
                </div>

                <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-red-500">
                    <div class="flex items-center gap-2 mb-2 sm:mb-3">
                        <div class="w-6 h-6 rounded-md bg-red-500/10 flex items-center justify-center">
                            <i class="fa-solid fa-circle-exclamation text-[8px] text-red-500 dark:text-red-400"></i>
                        </div>
                        <span
                            class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Violations</span>
                    </div>
                    <div class="flex items-baseline gap-1 sm:gap-1.5">
                        <span
                            class="text-2xl sm:text-3xl font-black tracking-tight text-gray-900 dark:text-white">{{ $totalViolations }}</span>
                        <span class="text-xs sm:text-sm font-bold text-red-500 dark:text-red-400">total</span>
                    </div>
                </div>

                <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-rose-500">
                    <div class="flex items-center gap-2 mb-2 sm:mb-3">
                        <div class="w-6 h-6 rounded-md bg-rose-500/10 flex items-center justify-center">
                            <i class="fa-solid fa-peso-sign text-[8px] text-rose-500 dark:text-rose-400"></i>
                        </div>
                        <span
                            class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Violation
                            Fines</span>
                    </div>
                    <span
                        class="text-xl sm:text-2xl font-black tracking-tight text-gray-900 dark:text-white">₱{{ number_format($totalViolationFines, 0) }}</span>
                </div>

            </div>

            <div class="grid grid-cols-1 xl:grid-cols-12 gap-5 sm:gap-6">

                <!-- ══════════ LEFT COLUMN ══════════ -->
                <div class="xl:col-span-8 flex flex-col gap-5 sm:gap-6">

                    <!-- ── Today's Shift ── -->
                    <div class="glass-card p-4 sm:p-6 rounded-[1.25rem] sm:rounded-[1.5rem]">
                        <div class="flex items-center justify-between mb-4 sm:mb-5">
                            <div class="flex items-center gap-2.5">
                                <div
                                    class="w-7 h-7 rounded-lg bg-gray-100 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] flex items-center justify-center">
                                    <i class="fa-solid fa-business-time text-[9px] text-gray-400 dark:text-[#555]"></i>
                                </div>
                                <span
                                    class="text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#555]">Today's
                                    Shift</span>
                            </div>
                            @if ($todayRecord)
                                <span
                                    class="text-[8px] bg-emerald-500/10 text-emerald-500 dark:text-emerald-400 border border-emerald-500/15 px-2 py-0.5 rounded-md font-bold uppercase">Active</span>
                            @else
                                <span
                                    class="text-[8px] bg-gray-100 dark:bg-[#111] text-gray-400 dark:text-[#444] border border-gray-200 dark:border-[#1e1e1e] px-2 py-0.5 rounded-md font-bold uppercase">Not
                                    Started</span>
                            @endif
                        </div>

                        @if ($todayRecord)
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 sm:gap-3">
                                <div
                                    class="p-3 sm:p-3.5 rounded-xl bg-gray-50 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e]">
                                    <p
                                        class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444] mb-0.5">
                                        Time In</p>
                                    <p class="text-sm sm:text-lg font-black text-gray-900 dark:text-white">
                                        {{ $todayRecord->time_in ? \Carbon\Carbon::parse($todayRecord->time_in)->format('h:i A') : '—' }}
                                    </p>
                                </div>
                                <div
                                    class="p-3 sm:p-3.5 rounded-xl bg-gray-50 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e]">
                                    <p
                                        class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444] mb-0.5">
                                        Time Out</p>
                                    <p class="text-sm sm:text-lg font-black text-gray-900 dark:text-white">
                                        {{ $todayRecord->time_out ? \Carbon\Carbon::parse($todayRecord->time_out)->format('h:i A') : '—' }}
                                    </p>
                                </div>
                                <div
                                    class="p-3 sm:p-3.5 rounded-xl bg-gray-50 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] col-span-2 sm:col-span-1">
                                    <p
                                        class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444] mb-0.5">
                                        Hours Worked</p>
                                    <div class="flex items-baseline gap-1 sm:gap-1.5">
                                        <p class="text-sm sm:text-lg font-black text-gray-900 dark:text-white">
                                            {{ $todayRecord->hours_worked ?? '0.0' }}</p>
                                        <span
                                            class="text-[9px] sm:text-[10px] font-bold text-gray-400 dark:text-[#555]">hrs</span>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="text-center py-6 sm:py-8">
                                <div
                                    class="w-12 h-12 rounded-xl bg-gray-100 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] flex items-center justify-center mx-auto mb-3">
                                    <i class="fa-regular fa-clock text-lg text-gray-300 dark:text-[#555]"></i>
                                </div>
                                <p class="text-[11px] text-gray-400 dark:text-[#444] font-medium">No shift recorded
                                    today</p>
                            </div>
                        @endif
                    </div>

                    <!-- ── Timekeeping History ── -->
                    <div class="glass-card rounded-[1.25rem] sm:rounded-[1.5rem] overflow-hidden">
                        <div class="p-4 sm:p-6 pb-0">
                            <div class="flex items-center justify-between mb-4 sm:mb-5">
                                <div class="flex items-center gap-2.5">
                                    <div
                                        class="w-7 h-7 rounded-lg bg-gray-100 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] flex items-center justify-center">
                                        <i
                                            class="fa-solid fa-table-list text-[9px] text-gray-400 dark:text-[#555]"></i>
                                    </div>
                                    <span
                                        class="text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#555]">Timekeeping</span>
                                </div>
                                <span class="text-[8px] sm:text-[9px] font-bold text-gray-400 dark:text-[#333]">Last 7
                                    days</span>
                            </div>
                        </div>

                        <div class="overflow-x-auto -mx-2 px-2 pb-2">
                            <table class="w-full text-left min-w-[600px]">
                                <thead>
                                    <tr
                                        class="text-[8px] sm:text-[9px] uppercase tracking-[0.15em] text-gray-400 dark:text-[#444] border-b border-gray-200 dark:border-[#1e1e1e]">
                                        <th class="px-4 sm:px-6 py-2.5 font-bold">Date</th>
                                        <th class="px-4 sm:px-6 py-2.5 font-bold">Time In</th>
                                        <th class="px-4 sm:px-6 py-2.5 font-bold">Time Out</th>
                                        <th class="px-4 sm:px-6 py-2.5 font-bold">Hours</th>
                                        <th class="px-4 sm:px-6 py-2.5 font-bold">OT</th>
                                        <th class="px-4 sm:px-6 py-2.5 font-bold">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-[#1a1a1a]">
                                    @forelse($recentTimeKeeping as $record)
                                        <tr class="table-row">
                                            <td class="px-4 sm:px-6 py-3">
                                                <p
                                                    class="text-[10px] sm:text-[11px] font-bold text-gray-700 dark:text-[#ccc]">
                                                    {{ \Carbon\Carbon::parse($record->date)->format('M d, Y') }}</p>
                                                <p
                                                    class="text-[7px] sm:text-[8px] text-gray-400 dark:text-[#444] font-bold uppercase">
                                                    {{ \Carbon\Carbon::parse($record->date)->format('l') }}</p>
                                            </td>
                                            <td class="px-4 sm:px-6 py-3">
                                                <span
                                                    class="text-[10px] sm:text-[11px] font-bold text-gray-500 dark:text-[#888]">{{ $record->time_in ? \Carbon\Carbon::parse($record->time_in)->format('h:i A') : '—' }}</span>
                                            </td>
                                            <td class="px-4 sm:px-6 py-3">
                                                <span
                                                    class="text-[10px] sm:text-[11px] font-bold text-gray-500 dark:text-[#888]">{{ $record->time_out ? \Carbon\Carbon::parse($record->time_out)->format('h:i A') : '—' }}</span>
                                            </td>
                                            <td class="px-4 sm:px-6 py-3">
                                                <span
                                                    class="text-[10px] sm:text-[11px] font-bold text-gray-900 dark:text-white">{{ $record->hours_worked ?? '0.0' }}</span>
                                            </td>
                                            <td class="px-4 sm:px-6 py-3">
                                                @if ($record->overtime_hours && $record->overtime_hours > 0)
                                                    <span
                                                        class="text-[9px] sm:text-[10px] font-bold text-amber-500 dark:text-amber-400">+{{ number_format($record->overtime_hours, 1) }}</span>
                                                @else
                                                    <span
                                                        class="text-[9px] sm:text-[10px] text-gray-300 dark:text-[#333]">—</span>
                                                @endif
                                            </td>
                                            <td class="px-4 sm:px-6 py-3">
                                                @if ($record->sick)
                                                    <span
                                                        class="text-[7px] sm:text-[8px] bg-amber-500/10 text-amber-500 dark:text-amber-400 border border-amber-500/15 px-1.5 py-0.5 rounded-md font-bold uppercase">Sick</span>
                                                @elseif($record->vacation)
                                                    <span
                                                        class="text-[7px] sm:text-[8px] bg-blue-500/10 text-blue-500 dark:text-blue-400 border border-blue-500/15 px-1.5 py-0.5 rounded-md font-bold uppercase">Vacation</span>
                                                @elseif($record->time_in && $record->time_out)
                                                    <span
                                                        class="text-[7px] sm:text-[8px] bg-emerald-500/10 text-emerald-500 dark:text-emerald-400 border border-emerald-500/15 px-1.5 py-0.5 rounded-md font-bold uppercase">Complete</span>
                                                @elseif($record->time_in)
                                                    <span
                                                        class="text-[7px] sm:text-[8px] bg-blue-500/10 text-blue-500 dark:text-blue-400 border border-blue-500/15 px-1.5 py-0.5 rounded-md font-bold uppercase">In
                                                        Progress</span>
                                                @else
                                                    <span
                                                        class="text-[7px] sm:text-[8px] bg-gray-100 dark:bg-[#111] text-gray-400 dark:text-[#444] border border-gray-200 dark:border-[#1e1e1e] px-1.5 py-0.5 rounded-md font-bold uppercase">Absent</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="py-10 sm:py-12">
                                                <div class="flex flex-col items-center justify-center">
                                                    <div
                                                        class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-[#1c1c1c] border border-gray-200 dark:border-[#2a2a2a] flex items-center justify-center mb-2.5">
                                                        <i
                                                            class="fa-solid fa-table-list text-sm text-gray-300 dark:text-[#555]"></i>
                                                    </div>
                                                    <p
                                                        class="text-[10px] sm:text-[11px] text-gray-400 dark:text-[#555] font-medium">
                                                        No records yet</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ══════════ RIGHT COLUMN ══════════ -->
                <div class="xl:col-span-4 flex flex-col gap-5 sm:gap-6">

                    @if ($vehicle)
                        <div class="glass-card p-4 sm:p-6 rounded-[1.25rem] sm:rounded-[1.5rem]">
                            <div class="flex items-center gap-2.5 mb-4 sm:mb-5">
                                <div
                                    class="w-7 h-7 rounded-lg bg-gray-100 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] flex items-center justify-center">
                                    <i class="fa-solid fa-bus text-[9px] text-gray-400 dark:text-[#555]"></i>
                                </div>
                                <span
                                    class="text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#555]">Assigned
                                    Vehicle</span>
                            </div>

                            <div class="space-y-2.5">
                                <div
                                    class="flex items-center gap-3 p-3 sm:p-3.5 rounded-xl bg-gray-50 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e]">
                                    <div
                                        class="w-9 h-9 sm:w-10 h-10 rounded-lg bg-blue-500/10 border border-blue-500/15 flex items-center justify-center shrink-0">
                                        <i
                                            class="fa-solid fa-van-shuttle text-blue-500 dark:text-blue-400 text-xs sm:text-sm"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <p
                                            class="text-[11px] sm:text-[12px] font-bold text-gray-900 dark:text-white truncate">
                                            {{ $vehicle->year }} {{ $vehicle->brand }} {{ $vehicle->model }}</p>
                                        <p
                                            class="text-[8px] sm:text-[9px] text-gray-400 dark:text-[#444] font-mono uppercase">
                                            {{ $vehicle->plate_number }}</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-2">
                                    <div
                                        class="p-2.5 sm:p-3 rounded-xl bg-gray-50 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e]">
                                        <p
                                            class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444] mb-0.5">
                                            Fuel Type</p>
                                        <p
                                            class="text-[10px] sm:text-[11px] font-bold text-gray-600 dark:text-[#888] capitalize">
                                            {{ $vehicle->fuel_type }}</p>
                                    </div>
                                    <div
                                        class="p-2.5 sm:p-3 rounded-xl bg-gray-50 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e]">
                                        <p
                                            class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444] mb-0.5">
                                            Tank Cap.</p>
                                        <p class="text-[10px] sm:text-[11px] font-bold text-gray-600 dark:text-[#888]">
                                            {{ $vehicle->tank_capacity }}L</p>
                                    </div>
                                </div>

                                <div
                                    class="pt-2.5 sm:pt-3 border-t border-gray-200 dark:border-[#1e1e1e] space-y-2 sm:space-y-2.5">
                                    <div class="flex items-center justify-between">
                                        <span
                                            class="text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Status</span>
                                        @if ($vehicle->status === 'active')
                                            <span
                                                class="text-[8px] sm:text-[9px] font-bold text-emerald-500 dark:text-emerald-400 flex items-center gap-1.5">
                                                <i class="fa-solid fa-circle-check text-[7px] sm:text-[8px]"></i>
                                                Active
                                            </span>
                                        @else
                                            <span
                                                class="text-[8px] sm:text-[9px] font-bold text-red-500 dark:text-red-400 flex items-center gap-1.5">
                                                <i class="fa-solid fa-circle-xmark text-[7px] sm:text-[8px]"></i>
                                                {{ ucfirst($vehicle->status) }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span
                                            class="text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">VIN</span>
                                        <span
                                            class="text-[8px] sm:text-[9px] font-mono text-gray-500 dark:text-[#555]">{{ $vehicle->vin ?? 'N/A' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- ── License Info ── -->
                    <div class="glass-card p-4 sm:p-6 rounded-[1.25rem] sm:rounded-[1.5rem]">
                        <div class="flex items-center gap-2.5 mb-4 sm:mb-5">
                            <div
                                class="w-7 h-7 rounded-lg bg-gray-100 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] flex items-center justify-center">
                                <i class="fa-solid fa-id-card text-[9px] text-gray-400 dark:text-[#555]"></i>
                            </div>
                            <span
                                class="text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#555]">License
                                Info</span>
                        </div>

                        <div class="space-y-2.5">
                            <div
                                class="flex items-center justify-between p-3 sm:p-3.5 rounded-xl bg-gray-50 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e]">
                                <div>
                                    <p
                                        class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444] mb-0.5">
                                        License Number</p>
                                    <p
                                        class="text-[10px] sm:text-[11px] font-bold text-gray-700 dark:text-[#ccc] font-mono">
                                        {{ $driver->license_number ?? 'N/A' }}</p>
                                </div>
                                <div
                                    class="w-7 h-7 sm:w-8 h-8 rounded-lg @if ($driver->license_status === 'approved' || $driver->is_approved) bg-emerald-500/10 border border-emerald-500/15 @else bg-amber-500/10 border border-amber-500/15 @endif flex items-center justify-center shrink-0">
                                    <i
                                        class="fa-solid @if ($driver->license_status === 'approved' || $driver->is_approved) fa-check text-emerald-500 dark:text-emerald-400 @elseif($driver->is_rejected) fa-xmark text-red-500 dark:text-red-400 @else fa-clock text-amber-500 dark:text-amber-400 @endif text-[8px] sm:text-[9px]"></i>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <div
                                    class="p-2.5 sm:p-3 rounded-xl bg-gray-50 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e]">
                                    <p
                                        class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444] mb-0.5">
                                        Code</p>
                                    <p
                                        class="text-[10px] sm:text-[11px] font-bold text-gray-600 dark:text-[#888] font-mono">
                                        {{ $driver->license_code ?? 'N/A' }}</p>
                                </div>
                                <div
                                    class="p-2.5 sm:p-3 rounded-xl bg-gray-50 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e]">
                                    <p
                                        class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444] mb-0.5">
                                        Expires</p>
                                    <p
                                        class="text-[10px] sm:text-[11px] font-bold @if ($driver->expiration_date && \Carbon\Carbon::parse($driver->expiration_date)->isPast()) text-red-500 dark:text-red-400 @else text-gray-600 dark:text-[#888] @endif">
                                        {{ $driver->expiration_date ? \Carbon\Carbon::parse($driver->expiration_date)->format('M d, Y') : 'N/A' }}
                                    </p>
                                </div>
                            </div>

                            @if ($driver->contact_info)
                                <div class="pt-2.5 sm:pt-3 border-t border-gray-200 dark:border-[#1e1e1e]">
                                    <div class="flex items-center justify-between">
                                        <span
                                            class="text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Contact</span>
                                        <span
                                            class="text-[10px] sm:text-[11px] font-bold text-gray-600 dark:text-[#888]">{{ $driver->contact_info }}</span>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- ══════════ RECENT VIOLATIONS ══════════ -->
                    <div class="glass-card rounded-[1.25rem] sm:rounded-[1.5rem] overflow-hidden"
                        x-data="driverViolations()">
                        <div class="p-4 sm:p-6 pb-0">
                            <div class="flex items-center justify-between mb-4 sm:mb-5">
                                <div class="flex items-center gap-2.5">
                                    <div
                                        class="w-7 h-7 rounded-lg bg-gray-100 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] flex items-center justify-center">
                                        <i
                                            class="fa-solid fa-list-check text-[9px] text-amber-500 dark:text-amber-400"></i>
                                    </div>
                                    <span
                                        class="text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#555]">Violations</span>
                                </div>
                                <a href="{{ route('driver.violations') }}"
                                    class="text-[8px] sm:text-[9px] font-bold uppercase tracking-widest text-amber-500 dark:text-amber-400 hover:text-amber-600 dark:hover:text-white transition flex items-center gap-1.5">
                                    <span>View All</span>
                                    <i class="fa-solid fa-arrow-right text-[7px]"></i>
                                </a>
                            </div>
                        </div>

                        <div class="overflow-y-auto px-4 sm:px-6 pb-4 sm:pb-6 space-y-2.5" style="max-height: 380px;">
                            <template x-for="v in violations" :key="v.id">
                                <div
                                    class="p-3.5 rounded-xl bg-gray-50 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] hover:bg-gray-100 dark:hover:bg-[#1a1a1a] hover:border-gray-300 dark:hover:border-[#333] transition-all cursor-pointer flex gap-3.5 items-start group">
                                    <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0 transition-all"
                                        :class="iconClasses(v)">
                                        <i class="fa-solid text-[10px]" :class="icon(v)"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-[10px] sm:text-[11px] font-bold text-gray-700 dark:text-[#ccc] truncate"
                                            x-text="v.violationType"></h4>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span
                                                class="text-[7px] sm:text-[8px] font-bold uppercase font-mono px-1.5 py-0.5 rounded-md"
                                                :class="badgeClasses(v.codeColor)" x-text="v.violationCode"></span>
                                            <span class="text-[8px] text-gray-400 dark:text-[#444] font-medium"
                                                x-text="offenseLabel(v.offenseCount)"></span>
                                        </div>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <p class="text-[10px] sm:text-[11px] font-bold text-red-500 dark:text-red-400"
                                            x-text="formatFine(v.fine)"></p>
                                        <p class="text-[7px] sm:text-[8px] text-gray-400 dark:text-[#444] font-medium mt-0.5"
                                            x-text="v.date"></p>
                                    </div>
                                </div>
                            </template>

                            <div x-show="violations.length === 0" x-cloak
                                class="flex flex-col items-center justify-center py-10 sm:py-14">
                                <div
                                    class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] flex items-center justify-center mb-2.5">
                                    <i class="fa-solid fa-shield text-sm text-gray-300 dark:text-[#333]"></i>
                                </div>
                                <span
                                    class="text-[10px] sm:text-[11px] text-gray-400 dark:text-[#333] font-medium">Clean
                                    record</span>
                                <span class="text-[8px] text-gray-300 dark:text-[#222] mt-0.5">No violations on
                                    file</span>
                            </div>
                        </div>
                    </div>

                    <!-- ── Quick Actions ── -->
                    <div class="glass-card p-4 sm:p-6 rounded-[1.25rem] sm:rounded-[1.5rem]">
                        <div class="flex items-center gap-2.5 mb-4 sm:mb-5">
                            <div
                                class="w-7 h-7 rounded-lg bg-gray-100 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] flex items-center justify-center">
                                <i class="fa-solid fa-bolt text-[9px] text-gray-400 dark:text-[#555]"></i>
                            </div>
                            <span
                                class="text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#555]">Quick
                                Actions</span>
                        </div>
                        <div class="space-y-2">
                            <a
                                class="flex items-center justify-between p-3 sm:p-3.5 rounded-xl bg-gray-50 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] hover:bg-gray-100 dark:hover:bg-[#1a1a1a] hover:border-gray-300 dark:hover:border-[#333] transition group cursor-pointer">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-8 h-8 rounded-lg bg-blue-500/10 border border-blue-500/15 flex items-center justify-center">
                                        <i class="fa-solid fa-bus text-[9px] text-blue-500 dark:text-blue-400"></i>
                                    </div>
                                    <span
                                        class="text-[10px] font-bold text-gray-600 dark:text-[#888] group-hover:text-gray-900 dark:group-hover:text-white transition">My
                                        Vehicle</span>
                                </div>
                                <i
                                    class="fa-solid fa-chevron-right text-[8px] text-gray-300 dark:text-[#555] group-hover:text-gray-500 dark:group-hover:text-[#777] transition"></i>
                            </a>
                            <a href="{{ route('driver.timekeeping') }}"
                                class="flex items-center justify-between p-3 sm:p-3.5 rounded-xl bg-gray-50 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] hover:bg-gray-100 dark:hover:bg-[#1a1a1a] hover:border-gray-300 dark:hover:border-[#333] transition group cursor-pointer">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-8 h-8 rounded-lg bg-purple-500/10 border border-purple-500/15 flex items-center justify-center">
                                        <i
                                            class="fa-solid fa-chart-line text-[9px] text-purple-500 dark:text-purple-400"></i>
                                    </div>
                                    <span
                                        class="text-[10px] font-bold text-gray-600 dark:text-[#888] group-hover:text-gray-900 dark:group-hover:text-white transition">History</span>
                                </div>
                                <i
                                    class="fa-solid fa-chevron-right text-[8px] text-gray-300 dark:text-[#555] group-hover:text-gray-500 dark:group-hover:text-[#777] transition"></i>
                            </a>
                            <a href="{{ route('driver.violations') }}"
                                class="flex items-center justify-between p-3 sm:p-3.5 rounded-xl bg-gray-50 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] hover:bg-gray-100 dark:hover:bg-[#1a1a1a] hover:border-gray-300 dark:hover:border-[#333] transition group cursor-pointer">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-8 h-8 rounded-lg bg-amber-500/10 border border-amber-500/15 flex items-center justify-center">
                                        <i class="fa-solid fa-file text-[9px] text-amber-500 dark:text-amber-400"></i>
                                    </div>
                                    <span
                                        class="text-[10px] font-bold text-gray-600 dark:text-[#888] group-hover:text-gray-900 dark:group-hover:text-white transition">My
                                        Violations</span>
                                </div>
                                <i
                                    class="fa-solid fa-chevron-right text-[8px] text-gray-300 dark:text-[#555] group-hover:text-gray-500 dark:group-hover:text-[#777] transition"></i>
                            </a>
                        </div>
                    </div>

                </div>
            </div>
        </main>

        <!-- ══════════ LOGOUT MODAL ══════════ -->
        <div x-show="showLogoutModal" x-cloak
            class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 dark:bg-black/80"
            @click.self="showLogoutModal = false" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0">

            <div @click.stop class="glass-panel p-7 sm:p-8 rounded-[2rem] max-w-sm w-full" x-show="showLogoutModal"
                x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
                <div class="text-center">
                    <div
                        class="w-14 h-14 bg-red-500/10 rounded-2xl flex items-center justify-center mx-auto mb-5 border border-red-500/20">
                        <i class="fa-solid fa-power-off text-red-500 dark:text-red-400 text-lg"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1.5">End Session?</h3>
                    <p class="text-xs text-gray-500 dark:text-[#666] mb-7">Are you sure you want to exit the Driver
                        Console?</p>

                    <div class="flex gap-2.5">
                        <button @click="showLogoutModal = false"
                            class="flex-1 py-3 rounded-xl bg-gray-100 dark:bg-[#1a1a1a] border border-gray-200 dark:border-[#2a2a2a] text-gray-900 dark:text-white text-[10px] font-bold uppercase tracking-widest hover:bg-gray-200 dark:hover:bg-[#222] transition">
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

    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('driverViolations', () => {
                return {
                    violations: @json($violationLogs),

                    icon(v) {
                        const t = (v.violationType || '').toLowerCase();
                        if (t.includes('speed') || t.includes('over')) return 'fa-gauge-high';
                        if (t.includes('phone') || t.includes('mobile') || t.includes('device'))
                            return 'fa-mobile-screen-button';
                        if (t.includes('traffic') || t.includes('light') || t.includes('signal'))
                            return 'fa-traffic-light';
                        if (t.includes('parking') || t.includes('park')) return 'fa-square-parking';
                        if (t.includes('lane') || t.includes('swerv') || t.includes('weaving'))
                            return 'fa-road';
                        return 'fa-triangle-exclamation';
                    },

                    iconClasses(v) {
                        const t = (v.violationType || '').toLowerCase();
                        if (t.includes('phone') || t.includes('mobile'))
                            return 'bg-orange-500/10 border border-orange-500/15 text-orange-500 dark:text-orange-400 group-hover:bg-orange-500 group-hover:text-white group-hover:border-orange-500';
                        if (t.includes('traffic') || t.includes('park'))
                            return 'bg-yellow-500/10 border border-yellow-500/15 text-yellow-500 dark:text-yellow-400 group-hover:bg-yellow-500 group-hover:text-white group-hover:border-yellow-500';
                        return 'bg-red-500/10 border border-red-500/15 text-red-500 dark:text-red-400 group-hover:bg-red-500 group-hover:text-white group-hover:border-red-500';
                    },

                    badgeClasses(color) {
                        const map = {
                            'red': 'bg-red-500/10 text-red-500 dark:text-red-400 border border-red-500/15',
                            'amber': 'bg-amber-500/10 text-amber-500 dark:text-amber-400 border border-amber-500/15',
                            'green': 'bg-emerald-500/10 text-emerald-500 dark:text-emerald-400 border border-emerald-500/15',
                            'blue': 'bg-blue-500/10 text-blue-500 dark:text-blue-400 border border-blue-500/15',
                        };
                        return map[color] || map['amber'];
                    },

                    offenseLabel(count) {
                        const labels = {
                            1: '1st Offense',
                            2: '2nd Offense',
                            3: '3rd Offense'
                        };
                        return labels[count] || (count + 'th Offense');
                    },

                    formatFine(n) {
                        return '₱' + Number(n).toLocaleString('en-PH', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        });
                    }
                };
            });
        });
    </script>
</body>

</html>
