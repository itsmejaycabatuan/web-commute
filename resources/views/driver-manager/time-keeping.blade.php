<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartCommute | Time Keeping</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    @include('partials.head-scripts')

    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif']
                    }
                }
            }
        }
    </script>
    <style>
        body,
        html {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: 'Inter', sans-serif;
            overflow-x: hidden;
        }

        .glass-panel {
            background: rgba(255, 255, 255, 0.85);
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
        }

        .dark .glass-panel {
            background: #111111;
            border: 1px solid #1e1e1e;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.6);
        }

        .glass-card {
            background: #ffffff;
            border: 1px solid #f1f5f9;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        }

        .dark .glass-card {
            background: #161616;
            border: 1px solid #222222;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.5);
        }

        .sidebar-transition {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }

        .table-row {
            transition: all 0.2s ease !important;
        }

        .table-row:hover {
            background: #f8fafc;
        }

        .dark .table-row:hover {
            background: #1a1a1a;
        }

        [x-cloak] {
            display: none !important;
        }

        ::-webkit-scrollbar {
            width: 3px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        .dark ::-webkit-scrollbar-thumb {
            background: #333;
        }

        select.form-input {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
            padding-right: 40px;
        }

        select.form-input option {
            background: #ffffff;
            color: #1e293b;
        }

        .dark select.form-input {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='rgba(255,255,255,0.2)' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
        }

        .dark select.form-input option {
            background: #111;
            color: #fff;
        }

        input[type="time"]::-webkit-calendar-picker-indicator,
        input[type="date"]::-webkit-calendar-picker-indicator {
            cursor: pointer;
        }

        .dark input[type="time"]::-webkit-calendar-picker-indicator,
        .dark input[type="date"]::-webkit-calendar-picker-indicator {
            filter: invert(1) brightness(0.3);
        }
    </style>
</head>

@php
    // ── If your controller passes a paginator, unwrap it here.
    //    For full client-side filtering across ALL records,
    //    change your controller to return ALL entries (no ->paginate()).
    $rawEntries =
        $entries instanceof \Illuminate\Pagination\LengthAwarePaginator ? $entries->getCollection() : collect($entries);

    $mappedEntries = $rawEntries
        ->map(function ($e) {
            $type = 'regular';
            if ($e->sick) {
                $type = 'sick';
            } elseif ($e->vacation) {
                $type = 'vacation';
            } elseif ($e->overtime_hours > 0) {
                $type = 'overtime';
            }

            return [
                'id' => $e->id,
                'driver_name' => $e->driver->name ?? '',
                'date_formatted' => \Carbon\Carbon::parse($e->date)->format('M d, Y'),
                'time_in' =>
                    !$e->sick && !$e->vacation && $e->time_in
                        ? \Carbon\Carbon::parse($e->time_in)->format('g:i A')
                        : null,
                'time_out' =>
                    !$e->sick && !$e->vacation && $e->time_out
                        ? \Carbon\Carbon::parse($e->time_out)->format('g:i A')
                        : null,
                'hours_worked' => (float) $e->hours_worked,
                'overtime_hours' => (float) $e->overtime_hours,
                'type' => $type,
                'sick' => (bool) $e->sick,
                'vacation' => (bool) $e->vacation,
            ];
        })
        ->values();
@endphp

<script type="text/json" id="entries-data">
    @json($mappedEntries)
</script>

<body class="antialiased text-gray-900 dark:text-white bg-white dark:bg-[#050505]" x-data>

    <x-layout.sidebar :menu-items="$sidebarMenu" />

    <main :class="$store.sidebar.open ? 'md:ml-[270px]' : 'md:ml-[76px]'"
        class="sidebar-transition pt-8 pr-4 sm:pr-8 pb-8 pl-4 sm:pl-8 min-h-screen mb-12">

        <div x-data="timeKeepingData()" @keydown.escape.window="closeModal()">

            <!-- ── Mobile: Identity Card ── -->
            <div class="lg:hidden mb-5">
                <div class="glass-card p-4 rounded-[1.25rem]">
                    <div class="flex items-center gap-3.5">
                        <div class="w-11 h-11 bg-blue-600 rounded-xl flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-gauge-high text-white text-sm"></i>
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-sm font-bold text-gray-900 dark:text-white truncate">Driver Manager</h2>
                            <p class="text-[10px] text-gray-500 dark:text-[#555] truncate">{{ Auth::user()->email }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 pt-3.5 mt-3.5 border-t border-gray-200 dark:border-[#1e1e1e]">
                        <i class="fa-solid fa-id-badge text-[8px] text-blue-500 dark:text-blue-400"></i>
                        <span class="text-[10px] text-gray-600 dark:text-[#888] font-bold">Driver Oversight</span>
                        <span class="text-gray-300 dark:text-[#333]">•</span>
                        <span class="font-mono text-[9px] text-gray-400 dark:text-[#444]">Manager</span>
                    </div>
                </div>
            </div>

            <!-- ── Page Header (desktop) ── -->
            <div class="hidden lg:block mb-8">
                <div class="flex items-center gap-2 mb-1.5">
                    <span
                        class="text-[9px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Manage</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-gray-900 dark:text-white">Time Keeping
                </h1>
                <p class="text-[11px] text-gray-500 dark:text-[#555] mt-1 flex items-center gap-2">
                    <i class="fa-solid fa-clock text-[9px] text-blue-500 dark:text-blue-400"></i>
                    <span class="text-gray-700 dark:text-[#888] font-bold" x-text="filteredEntries.length"></span>
                    shift records logged
                </p>
            </div>

            @if (session('success'))
                <div
                    class="mb-5 px-4 py-3 rounded-xl border border-emerald-500/15 bg-emerald-500/5 flex items-center gap-3">
                    <div class="w-6 h-6 rounded-md bg-emerald-500/10 flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-check text-[8px] text-emerald-500 dark:text-emerald-400"></i>
                    </div>
                    <span
                        class="text-[11px] text-emerald-600 dark:text-emerald-400 font-medium">{{ session('success') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="mb-5 px-4 py-3 rounded-xl border border-red-500/15 bg-red-500/5 flex items-center gap-3">
                    <div class="w-6 h-6 rounded-md bg-red-500/10 flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-xmark text-[8px] text-red-500 dark:text-red-400"></i>
                    </div>
                    <span class="text-[11px] text-red-600 dark:text-red-400 font-medium">{{ session('error') }}</span>
                </div>
            @endif

            <!-- ══════════ TABLE CARD ══════════ -->
            <div class="glass-card rounded-[1.25rem] sm:rounded-[1.5rem] overflow-hidden">

                <!-- ── Filters & Add Button ── -->
                <div class="p-4 sm:p-5 border-b border-gray-200 dark:border-[#1e1e1e]">
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
                            <button @click="setFilter('all')"
                                :class="filter === 'all' ?
                                    'bg-gray-900 dark:bg-white/10 text-white border-gray-900 dark:border-white/20' :
                                    'bg-gray-50 dark:bg-[#111] text-gray-500 dark:text-[#555] border-gray-200 dark:border-[#1e1e1e] hover:border-gray-300 dark:hover:border-[#333] hover:text-gray-700 dark:hover:text-[#888]'"
                                class="px-3 py-1.5 rounded-lg text-[8px] font-bold uppercase tracking-widest border transition">
                                All
                            </button>
                            <button @click="setFilter('regular')"
                                :class="filter === 'regular' ?
                                    'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20' :
                                    'bg-gray-50 dark:bg-[#111] text-gray-500 dark:text-[#555] border-gray-200 dark:border-[#1e1e1e] hover:border-gray-300 dark:hover:border-[#333] hover:text-gray-700 dark:hover:text-[#888]'"
                                class="px-3 py-1.5 rounded-lg text-[8px] font-bold uppercase tracking-widest border transition">
                                Regular
                            </button>
                            <button @click="setFilter('sick')"
                                :class="filter === 'sick' ?
                                    'bg-red-500/10 text-red-600 dark:text-red-400 border-red-500/20' :
                                    'bg-gray-50 dark:bg-[#111] text-gray-500 dark:text-[#555] border-gray-200 dark:border-[#1e1e1e] hover:border-gray-300 dark:hover:border-[#333] hover:text-gray-700 dark:hover:text-[#888]'"
                                class="px-3 py-1.5 rounded-lg text-[8px] font-bold uppercase tracking-widest border transition">
                                Sick
                            </button>
                            <button @click="setFilter('vacation')"
                                :class="filter === 'vacation' ?
                                    'bg-teal-500/10 text-teal-600 dark:text-teal-400 border-teal-500/20' :
                                    'bg-gray-50 dark:bg-[#111] text-gray-500 dark:text-[#555] border-gray-200 dark:border-[#1e1e1e] hover:border-gray-300 dark:hover:border-[#333] hover:text-gray-700 dark:hover:text-[#888]'"
                                class="px-3 py-1.5 rounded-lg text-[8px] font-bold uppercase tracking-widest border transition">
                                Vacation
                            </button>
                            <button @click="setFilter('overtime')"
                                :class="filter === 'overtime' ?
                                    'bg-orange-500/10 text-orange-600 dark:text-orange-400 border-orange-500/20' :
                                    'bg-gray-50 dark:bg-[#111] text-gray-500 dark:text-[#555] border-gray-200 dark:border-[#1e1e1e] hover:border-gray-300 dark:hover:border-[#333] hover:text-gray-700 dark:hover:text-[#888]'"
                                class="px-3 py-1.5 rounded-lg text-[8px] font-bold uppercase tracking-widest border transition">
                                Overtime
                            </button>
                        </div>
                        <span class="text-[8px] font-bold text-gray-400 dark:text-[#333] uppercase tracking-widest"
                            x-text="filteredEntries.length + ' entries'"></span>
                    </div>
                </div>

                <!-- ── Table ── -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left min-w-[700px]">
                        <thead>
                            <tr
                                class="text-[8px] sm:text-[9px] uppercase tracking-[0.15em] text-gray-400 dark:text-[#444] border-b border-gray-200 dark:border-[#1e1e1e]">
                                <th class="px-4 sm:px-6 py-3 font-bold w-12">#</th>
                                <th class="px-4 sm:px-6 py-3 font-bold">Driver & Date</th>
                                <th class="px-4 sm:px-6 py-3 font-bold">Shift</th>
                                <th class="px-4 sm:px-6 py-3 font-bold">Hours</th>
                                <th class="px-4 sm:px-6 py-3 font-bold">Overtime</th>
                                <th class="px-4 sm:px-6 py-3 font-bold">Type</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-[#1a1a1a]">

                            <template x-for="(entry, index) in paginatedEntries" :key="entry.id">
                                <tr class="table-row">
                                    <td class="px-4 sm:px-6 py-3">
                                        <span class="text-[10px] font-bold text-gray-400 dark:text-[#333]"
                                            x-text="String(showingFrom + index).padStart(2, '0')"></span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-3">
                                        <div class="flex items-center gap-3">
                                            <div>
                                                <span
                                                    class="text-[10px] sm:text-[11px] font-bold text-gray-700 dark:text-[#ccc] block"
                                                    x-text="entry.driver_name"></span>
                                                <span
                                                    class="text-[8px] sm:text-[9px] text-gray-400 dark:text-[#444] font-medium"
                                                    x-text="entry.date_formatted"></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 sm:px-6 py-3">
                                        <template x-if="entry.sick || entry.vacation">
                                            <span
                                                class="text-[10px] sm:text-[11px] text-gray-300 dark:text-[#222] font-medium">—</span>
                                        </template>
                                        <template x-if="!entry.sick && !entry.vacation">
                                            <div class="flex items-center gap-3">
                                                <div class="flex flex-col">
                                                    <span
                                                        class="text-[7px] sm:text-[8px] uppercase text-gray-400 dark:text-[#333] font-bold tracking-wider">In</span>
                                                    <span
                                                        class="text-[10px] sm:text-[11px] font-bold text-emerald-600 dark:text-emerald-400"
                                                        x-text="entry.time_in"></span>
                                                </div>
                                                <div class="h-6 w-px bg-gray-200 dark:bg-[#1e1e1e]"></div>
                                                <div class="flex flex-col">
                                                    <span
                                                        class="text-[7px] sm:text-[8px] uppercase text-gray-400 dark:text-[#333] font-bold tracking-wider">Out</span>
                                                    <span
                                                        class="text-[10px] sm:text-[11px] font-bold text-amber-600 dark:text-amber-400"
                                                        x-text="entry.time_out"></span>
                                                </div>
                                            </div>
                                        </template>
                                    </td>
                                    <td class="px-4 sm:px-6 py-3">
                                        <template x-if="entry.sick || entry.vacation">
                                            <span class="text-[10px] text-gray-300 dark:text-[#222]">—</span>
                                        </template>
                                        <template x-if="!entry.sick && !entry.vacation">
                                            <div class="flex items-baseline gap-1">
                                                <span class="text-[11px] font-bold text-gray-700 dark:text-[#ccc]"
                                                    x-text="entry.hours_worked.toFixed(2)"></span>
                                                <span
                                                    class="text-[8px] font-bold text-gray-400 dark:text-[#444]">hrs</span>
                                            </div>
                                        </template>
                                    </td>
                                    <td class="px-4 sm:px-6 py-3">
                                        <template x-if="entry.overtime_hours > 0">
                                            <span
                                                class="text-[7px] sm:text-[8px] bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/15 px-1.5 py-0.5 rounded-md font-bold uppercase"
                                                x-text="'+' + entry.overtime_hours.toFixed(2) + ' hrs'"></span>
                                        </template>
                                        <template x-if="entry.overtime_hours <= 0">
                                            <span class="text-[10px] text-gray-300 dark:text-[#222]">0</span>
                                        </template>
                                    </td>
                                    <td class="px-4 sm:px-6 py-3">
                                        <span
                                            class="text-[7px] sm:text-[8px] px-1.5 py-0.5 rounded-md font-bold uppercase border"
                                            :class="{
                                                'bg-red-500/10 text-red-600 dark:text-red-400 border-red-500/15': entry
                                                    .type === 'sick',
                                                'bg-teal-500/10 text-teal-600 dark:text-teal-400 border-teal-500/15': entry
                                                    .type === 'vacation',
                                                'bg-orange-500/10 text-orange-600 dark:text-orange-400 border-orange-500/15': entry
                                                    .type === 'overtime',
                                                'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/15': entry
                                                    .type === 'regular'
                                            }"
                                            x-text="entry.type"></span>
                                    </td>
                                </tr>
                            </template>

                            <!-- Empty: no entries at all -->
                            <template x-if="entries.length === 0">
                                <tr>
                                    <td colspan="6" class="py-12 sm:py-16">
                                        <div class="flex flex-col items-center justify-center">
                                            <div
                                                class="w-12 h-12 rounded-xl bg-gray-100 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] flex items-center justify-center mb-3">
                                                <i
                                                    class="fa-regular fa-calendar text-base text-gray-300 dark:text-[#222]"></i>
                                            </div>
                                            <p class="text-[11px] text-gray-400 dark:text-[#444] font-medium mb-4">No
                                                time entries yet</p>
                                            <button @click="openModal()"
                                                class="flex items-center gap-2 px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-[9px] font-bold uppercase tracking-widest text-white transition">
                                                <i class="fa-solid fa-plus text-[8px]"></i>
                                                <span>Add First Entry</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>

                            <!-- Empty: no matching filter -->
                            <template x-if="entries.length > 0 && filteredEntries.length === 0">
                                <tr>
                                    <td colspan="6" class="py-12 sm:py-16">
                                        <div class="flex flex-col items-center justify-center">
                                            <div
                                                class="w-12 h-12 rounded-xl bg-gray-100 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] flex items-center justify-center mb-3">
                                                <i
                                                    class="fa-solid fa-filter text-base text-gray-300 dark:text-[#222]"></i>
                                            </div>
                                            <p class="text-[11px] text-gray-400 dark:text-[#444] font-medium">No
                                                entries match this filter</p>
                                            <button @click="setFilter('all')"
                                                class="mt-3 text-[9px] font-bold uppercase tracking-widest text-blue-500 dark:text-blue-400 hover:text-blue-600 dark:hover:text-white transition">
                                                Show all
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>

                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ══════════ PAGINATION ══════════ -->
            <div x-show="totalPages > 1" x-cloak class="flex items-center justify-between mt-5 px-1">
                <div class="text-[8px] font-bold text-gray-400 dark:text-[#333] uppercase tracking-widest">
                    Showing <span x-text="showingFrom"></span>–<span x-text="showingTo"></span> of <span
                        x-text="filteredEntries.length"></span>
                </div>
                <div class="flex items-center gap-1">
                    <!-- Prev -->
                    <button @click="goToPage(currentPage - 1)" :disabled="currentPage === 1"
                        :class="currentPage === 1 ?
                            'bg-gray-100 dark:bg-[#0a0a0a] border-gray-200 dark:border-[#151515] text-gray-300 dark:text-[#1a1a1a] cursor-not-allowed' :
                            'bg-gray-50 dark:bg-[#111] border-gray-200 dark:border-[#1e1e1e] text-gray-500 dark:text-[#444] hover:bg-gray-100 dark:hover:bg-[#1a1a1a] hover:text-gray-900 dark:hover:text-[#888] hover:border-gray-300 dark:hover:border-[#333]'"
                        class="w-8 h-8 rounded-lg border flex items-center justify-center transition-all">
                        <i class="fa-solid fa-chevron-left text-[9px]"></i>
                    </button>

                    <!-- Page numbers -->
                    <template x-for="p in pageNumbers" :key="'page-' + p">
                        <template x-if="p === '...'">
                            <span
                                class="w-8 h-8 flex items-center justify-center text-gray-300 dark:text-[#222] text-[9px] font-bold">...</span>
                        </template>
                        <template x-if="p !== '...'">
                            <button @click="goToPage(p)"
                                :class="p === currentPage ?
                                    'bg-blue-600 border-blue-500/30 text-white shadow-lg shadow-blue-600/10' :
                                    'bg-gray-50 dark:bg-[#111] border-gray-200 dark:border-[#1e1e1e] text-gray-500 dark:text-[#444] hover:bg-gray-100 dark:hover:bg-[#1a1a1a] hover:text-gray-900 dark:hover:text-[#888] hover:border-gray-300 dark:hover:border-[#333]'"
                                class="w-8 h-8 rounded-lg border flex items-center justify-center text-[10px] font-bold transition-all"
                                x-text="p"></button>
                        </template>
                    </template>

                    <!-- Next -->
                    <button @click="goToPage(currentPage + 1)" :disabled="currentPage === totalPages"
                        :class="currentPage === totalPages ?
                            'bg-gray-100 dark:bg-[#0a0a0a] border-gray-200 dark:border-[#151515] text-gray-300 dark:text-[#1a1a1a] cursor-not-allowed' :
                            'bg-gray-50 dark:bg-[#111] border-gray-200 dark:border-[#1e1e1e] text-gray-500 dark:text-[#444] hover:bg-gray-100 dark:hover:bg-[#1a1a1a] hover:text-gray-900 dark:hover:text-[#888] hover:border-gray-300 dark:hover:border-[#333]'"
                        class="w-8 h-8 rounded-lg border flex items-center justify-center transition-all">
                        <i class="fa-solid fa-chevron-right text-[9px]"></i>
                    </button>
                </div>
            </div>


            <!-- ══════════ ADD ENTRY MODAL ══════════ -->
            <div x-show="showModal" x-cloak
                class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 dark:bg-black/80"
                x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" @click.self="closeModal()"
                style="display:none;">

                <div class="glass-panel p-6 sm:p-8 rounded-[2rem] max-w-xl w-full max-h-[90vh] overflow-y-auto"
                    @click.stop x-show="showModal" x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">

                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-base sm:text-lg font-bold text-gray-900 dark:text-white">New Time Entry
                            </h3>
                            <p class="text-[10px] text-gray-500 dark:text-[#555] mt-0.5">Fill in shift details or log a
                                leave</p>
                        </div>
                        <button @click="closeModal()"
                            class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-[#1a1a1a] border border-gray-200 dark:border-[#2a2a2a] flex items-center justify-center hover:bg-gray-200 dark:hover:bg-[#222] transition">
                            <i class="fa-solid fa-xmark text-[10px] text-gray-500 dark:text-[#555]"></i>
                        </button>
                    </div>

                    <form method="POST" action="{{ route('driver-manager.time-keeping.store') }}"
                        @submit.prevent="syncAndSubmit($event)" class="space-y-4">

                        @csrf
                        <input type="hidden" name="is_leave" :value="form.is_leave">
                        <input type="hidden" name="sick" :value="form.sick">
                        <input type="hidden" name="vacation" :value="form.vacation">

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label
                                    class="block mb-1.5 text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Driver</label>
                                <select name="driver_id" x-model="form.driver_id"
                                    class="form-input w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] text-[11px] text-gray-900 dark:text-white focus:outline-none focus:border-gray-300 dark:focus:border-[#333] transition">
                                    <option value="" disabled selected class="text-gray-400">Select driver
                                    </option>
                                    <template x-for="d in drivers" :key="d.id">
                                        <option :value="d.id" x-text="d.name"></option>
                                    </template>
                                </select>
                                @error('driver_id')
                                    <p class="mt-1.5 text-[9px] text-red-500 dark:text-red-400 flex items-center gap-1.5">
                                        <i class="fa-solid fa-circle-exclamation text-[7px]"></i> {{ $message }}
                                    </p>
                                @enderror
                            </div>
                            <div>
                                <label
                                    class="block mb-1.5 text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Date</label>
                                <input type="date" name="date" x-model="form.date"
                                    class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] text-[11px] text-gray-900 dark:text-white focus:outline-none focus:border-gray-300 dark:focus:border-[#333] transition">
                                @error('date')
                                    <p class="mt-1.5 text-[9px] text-red-500 dark:text-red-400 flex items-center gap-1.5">
                                        <i class="fa-solid fa-circle-exclamation text-[7px]"></i> {{ $message }}
                                    </p>
                                @enderror
                            </div>
                        </div>

                        <div class="pt-1 border-t border-gray-200 dark:border-[#1e1e1e]">
                            <label class="flex items-center justify-between cursor-pointer group py-1">
                                <div>
                                    <span
                                        class="text-[10px] font-bold text-gray-600 dark:text-[#888] group-hover:text-gray-900 dark:group-hover:text-white transition">Mark
                                        as Leave</span>
                                    <p class="text-[8px] text-gray-400 dark:text-[#444] mt-0.5">No shift will be
                                        recorded</p>
                                </div>
                                <div class="relative flex items-center justify-center"
                                    @click.prevent="isLeave = !isLeave; if (!isLeave) { selectedLeave = ''; form.sick = '0'; form.vacation = '0'; }">
                                    <div class="w-9 h-5 rounded-full transition"
                                        :class="isLeave ? 'bg-blue-600 border border-blue-500' :
                                            'bg-gray-200 dark:bg-[#222] border border-gray-300 dark:border-[#2a2a2a]'">
                                    </div>
                                    <div class="absolute left-0.5 w-4 h-4 rounded-full transition"
                                        :class="isLeave ? 'translate-x-4 bg-white' : 'translate-x-0 bg-gray-400 dark:bg-[#555]'">
                                    </div>
                                </div>
                            </label>
                        </div>

                        <!-- Shift Times -->
                        <div x-show="!isLeave" x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 -translate-y-2"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 -translate-y-2"
                            class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label
                                    class="block mb-1.5 text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">
                                    <i class="fa-solid fa-right-to-bracket text-emerald-500/50 mr-1"></i> Time In
                                </label>
                                <input type="time" name="time_in" x-model="form.time_in"
                                    class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] text-[11px] text-emerald-600 dark:text-emerald-400 font-mono font-semibold focus:outline-none focus:border-gray-300 dark:focus:border-[#333] transition">
                                @error('time_in')
                                    <p class="mt-1.5 text-[9px] text-red-500 dark:text-red-400 flex items-center gap-1.5">
                                        <i class="fa-solid fa-circle-exclamation text-[7px]"></i> {{ $message }}
                                    </p>
                                @enderror
                            </div>
                            <div>
                                <label
                                    class="block mb-1.5 text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">
                                    <i class="fa-solid fa-right-from-bracket text-amber-500/50 mr-1"></i> Time Out
                                </label>
                                <input type="time" name="time_out" x-model="form.time_out"
                                    class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] text-[11px] text-amber-600 dark:text-amber-400 font-mono font-semibold focus:outline-none focus:border-gray-300 dark:focus:border-[#333] transition">
                                @error('time_out')
                                    <p class="mt-1.5 text-[9px] text-red-500 dark:text-red-400 flex items-center gap-1.5">
                                        <i class="fa-solid fa-circle-exclamation text-[7px]"></i> {{ $message }}
                                    </p>
                                @enderror
                            </div>
                        </div>

                        <!-- Computed Hours -->
                        <div x-show="!isLeave && form.time_in && form.time_out" x-transition
                            class="flex items-center gap-4 p-3.5 rounded-xl bg-gray-50 dark:bg-[#0a0a0a] border border-gray-200 dark:border-[#1e1e1e]">
                            <div class="flex items-center gap-2">
                                <div class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></div>
                                <span
                                    class="text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#333]">Computed</span>
                            </div>
                            <div class="h-4 w-px bg-gray-200 dark:bg-[#1e1e1e]"></div>
                            <div class="flex items-center gap-4">
                                <span class="text-[10px] text-gray-500 dark:text-[#555]">Total: <span
                                        class="text-gray-700 dark:text-[#ccc] font-bold"
                                        x-text="calcHours() + ' hrs'"></span></span>
                                <span class="text-[10px] text-gray-500 dark:text-[#555]">OT: <span
                                        :class="parseFloat(calcOvertime()) > 0 ? 'text-blue-600 dark:text-blue-400 font-bold' :
                                            'text-gray-300 dark:text-[#222]'"
                                        x-text="calcOvertime() + ' hrs'"></span></span>
                            </div>
                        </div>

                        <!-- Leave Type Selection -->
                        <div x-show="isLeave" x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 -translate-y-2"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 -translate-y-2" class="space-y-3">
                            <label
                                class="block text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Leave
                                Type</label>
                            <div class="grid grid-cols-2 gap-3">
                                <button type="button" @click="pickLeave('sick')"
                                    :class="selectedLeave === 'sick' ? 'border-red-500/20 bg-red-500/5' :
                                        'border-gray-200 dark:border-[#1e1e1e] bg-gray-50 dark:bg-[#111] hover:bg-gray-100 dark:hover:bg-[#1a1a1a] hover:border-gray-300 dark:hover:border-[#333]'"
                                    class="flex items-center gap-3 p-3.5 rounded-xl border transition-all active:scale-[0.98]">
                                    <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0"
                                        :class="selectedLeave === 'sick' ? 'bg-red-500/10 border border-red-500/15' :
                                            'bg-gray-100 dark:bg-[#1a1a1a] border border-gray-200 dark:border-[#222]'">
                                        <i class="fa-solid fa-thermometer-half text-[10px]"
                                            :class="selectedLeave === 'sick' ? 'text-red-500 dark:text-red-400' :
                                                'text-gray-400 dark:text-[#444]'"></i>
                                    </div>
                                    <div class="text-left">
                                        <div class="text-[10px] font-bold"
                                            :class="selectedLeave === 'sick' ? 'text-red-600 dark:text-red-300' :
                                                'text-gray-500 dark:text-[#555]'">
                                            Sick Leave</div>
                                        <div class="text-[8px] text-gray-400 dark:text-[#333]">Medical / health</div>
                                    </div>
                                </button>
                                <button type="button" @click="pickLeave('vacation')"
                                    :class="selectedLeave === 'vacation' ? 'border-teal-500/20 bg-teal-500/5' :
                                        'border-gray-200 dark:border-[#1e1e1e] bg-gray-50 dark:bg-[#111] hover:bg-gray-100 dark:hover:bg-[#1a1a1a] hover:border-gray-300 dark:hover:border-[#333]'"
                                    class="flex items-center gap-3 p-3.5 rounded-xl border transition-all active:scale-[0.98]">
                                    <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0"
                                        :class="selectedLeave === 'vacation' ? 'bg-teal-500/10 border border-teal-500/15' :
                                            'bg-gray-100 dark:bg-[#1a1a1a] border border-gray-200 dark:border-[#222]'">
                                        <i class="fa-solid fa-umbrella-beach text-[10px]"
                                            :class="selectedLeave === 'vacation' ? 'text-teal-500 dark:text-teal-400' :
                                                'text-gray-400 dark:text-[#444]'"></i>
                                    </div>
                                    <div class="text-left">
                                        <div class="text-[10px] font-bold"
                                            :class="selectedLeave === 'vacation' ? 'text-teal-600 dark:text-teal-300' :
                                                'text-gray-500 dark:text-[#555]'">
                                            Vacation Leave</div>
                                        <div class="text-[8px] text-gray-400 dark:text-[#333]">Personal / time off
                                        </div>
                                    </div>
                                </button>
                            </div>
                        </div>

                        <div class="flex gap-2.5 pt-1">
                            <button type="button" @click="closeModal()"
                                class="flex-1 py-3 rounded-xl bg-gray-100 dark:bg-[#1a1a1a] border border-gray-200 dark:border-[#2a2a2a] text-gray-900 dark:text-white text-[10px] font-bold uppercase tracking-widest hover:bg-gray-200 dark:hover:bg-[#222] transition">
                                Cancel
                            </button>
                            <button type="submit"
                                :disabled="!form.driver_id || !form.date || (!isLeave && (!form.time_in || !form.time_out)) || (
                                    isLeave && !selectedLeave)"
                                :class="form.driver_id && form.date && ((isLeave && selectedLeave) || (!isLeave && form
                                        .time_in && form.time_out)) ?
                                    'bg-blue-600 hover:bg-blue-500 text-white' :
                                    'bg-gray-100 dark:bg-[#111] text-gray-300 dark:text-[#222] cursor-not-allowed border border-gray-200 dark:border-[#1e1e1e]'"
                                class="flex-1 py-3 rounded-xl text-[10px] font-bold uppercase tracking-widest transition active:scale-[0.98]">
                                <i class="fa-solid fa-check mr-1.5 text-[8px]"></i>
                                <span x-text="isLeave ? 'Log Leave' : 'Save Entry'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </main>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('timeKeepingData', () => {
                const el = document.getElementById('entries-data');
                const entries = el ? JSON.parse(el.textContent) : [];

                return {
                    entries,
                    filter: 'all',
                    currentPage: 1,
                    perPage: 15,

                    // Modal
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

                    // ── Computed ──
                    get filteredEntries() {
                        if (this.filter === 'all') return this.entries;
                        return this.entries.filter(e => e.type === this.filter);
                    },

                    get totalPages() {
                        return Math.ceil(this.filteredEntries.length / this.perPage) || 1;
                    },

                    get paginatedEntries() {
                        const start = (this.currentPage - 1) * this.perPage;
                        return this.filteredEntries.slice(start, start + this.perPage);
                    },

                    get showingFrom() {
                        if (this.filteredEntries.length === 0) return 0;
                        return (this.currentPage - 1) * this.perPage + 1;
                    },

                    get showingTo() {
                        return Math.min(this.currentPage * this.perPage, this.filteredEntries.length);
                    },

                    get pageNumbers() {
                        const onEachSide = 1;
                        let start = Math.max(1, this.currentPage - onEachSide);
                        let end = Math.min(this.totalPages, this.currentPage + onEachSide);
                        let pages = [];

                        if (start > 1) {
                            pages.push(1);
                            if (start > 2) pages.push('...');
                        }
                        for (let i = start; i <= end; i++) pages.push(i);
                        if (end < this.totalPages) {
                            if (end < this.totalPages - 1) pages.push('...');
                            pages.push(this.totalPages);
                        }
                        return pages;
                    },

                    // ── Methods ──
                    setFilter(type) {
                        this.filter = type;
                        this.currentPage = 1;
                    },

                    goToPage(page) {
                        if (page >= 1 && page <= this.totalPages) {
                            this.currentPage = page;
                        }
                    },

                    init() {
                        @if ($errors->any())
                            this.showModal = true;
                            this.form.driver_id = '{{ old('driver_id', '') }}';
                            this.form.date = '{{ old('date', '') }}';
                            this.form.time_in = '{{ old('time_in', '') }}';
                            this.form.time_out = '{{ old('time_out', '') }}';
                            @if (old('is_leave') === '1')
                                this.isLeave = true;
                                @if (old('sick') === '1')
                                    this.pickLeave('sick');
                                @elseif (old('vacation') === '1')
                                    this.pickLeave('vacation');
                                @endif
                            @endif
                        @endif
                    },

                    resetForm() {
                        this.form = {
                            driver_id: '',
                            date: '',
                            time_in: '',
                            time_out: '',
                            is_leave: '0',
                            sick: '0',
                            vacation: '0'
                        };
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
            });
        });
    </script>

</body>

</html>
