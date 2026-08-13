<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartCommute | Maintenance Calendar</title>
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

        /* Pagination */
        .pagination a,
        .pagination span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 28px;
            height: 28px;
            padding: 0 8px;
            border-radius: 6px;
            font-size: 9px;
            font-weight: 700;
            transition: all 0.15s;
            border: 1px solid #1e1e1e;
            color: #555;
            background: transparent;
        }
        .pagination a:hover {
            background: #1a1a1a;
            border-color: #333;
            color: #888;
        }
        .pagination .active {
            background: rgba(59, 130, 246, 0.08);
            border-color: rgba(59, 130, 246, 0.25);
            color: #60a5fa;
        }
        .pagination .disabled {
            opacity: 0.2;
            pointer-events: none;
        }
    </style>
</head>

<body class="antialiased text-white" x-data="{ open: true, showLogoutModal: false }" x-init="
    $nextTick(() => {
        const sel = document.getElementById('fleet-filter');
        if (sel) {
            sel.addEventListener('change', function () {
                const url = new URL(window.location.href);
                if (this.value) {
                    url.searchParams.set('fleet', this.value);
                } else {
                    url.searchParams.delete('fleet');
                }
                window.location.href = url.toString();
            });
        }
    })
">

    @include('maintenance-manager.layout.sidebar')

    <main :class="open ? 'md:ml-72' : 'md:ml-20'" class="sidebar-transition pt-8 pr-4 sm:pr-8 pb-8 pl-4 sm:pl-8 min-h-screen mb-12">

        @php
            // Summary stats for top cards
            $totalCost = $logs->sum('last_service_cost');
            $uniqueVehicles = $logs->pluck('fleet_id')->unique()->count();
            $latestDate = $logs->first()?->last_service_date;
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
                    <span class="font-mono text-[9px] text-[#444]">Calendar</span>
                </div>
            </div>
        </div>

        <!-- ── Page Header (desktop) ── -->
        <div class="hidden lg:flex items-end justify-between mb-8">
            <div>
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="text-[9px] font-bold uppercase tracking-[0.15em] text-[#444]">Service History</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight">Maintenance <span class="text-blue-500">Calendar</span></h1>
                <p class="text-[11px] text-[#555] mt-1 flex items-center gap-2">
                    <i class="fa-solid fa-wrench text-[9px] text-amber-400"></i>
                    Historical record of all fleet preventive services
                </p>
            </div>
            <div>
                <label class="text-[7px] font-bold uppercase tracking-[0.15em] text-[#333] block mb-1.5 text-right">Filter Vehicle</label>
                <select id="fleet-filter"
                        class="bg-[#111] border border-[#1e1e1e] rounded-xl px-4 py-2.5 text-[10px] font-bold text-[#888] focus:ring-1 focus:ring-blue-500/50 outline-none appearance-none cursor-pointer pr-10 w-56">
                    <option value="">All Vehicles</option>
                    @foreach($fleetOptions ?? [] as $id => $label)
                        <option value="{{ $id }}" {{ request('fleet') == (string) $id ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    <!-- ── Mobile Fleet Filter ── -->
    <div class="lg:hidden mb-5">
        <label class="text-[7px] font-bold uppercase tracking-[0.15em] text-[#333] block mb-1.5">Filter Vehicle</label>
        <select id="fleet-filter"
                class="w-full bg-[#161616] border border-[#222] rounded-xl px-4 py-2.5 text-[10px] font-bold text-[#888] focus:ring-1 focus:ring-blue-500/50 outline-none appearance-none cursor-pointer pr-10">
            <option value="">All Vehicles</option>
            @foreach($fleetOptions ?? [] as $id => $label)
                <option value="{{ $id }}" {{ request('fleet') == (string) $id ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>
    </div>

    <!-- ══════════ STAT CARDS ══════════ -->
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 sm:gap-4 mb-5 sm:mb-6">

        <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-blue-500">
            <div class="flex items-center gap-2 mb-2 sm:mb-3">
                <div class="w-6 h-6 rounded-md bg-blue-500/10 flex items-center justify-center">
                    <i class="fa-solid fa-list-check text-[8px] text-blue-400"></i>
                </div>
                <span class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Total Entries</span>
            </div>
            <div class="flex items-baseline gap-1.5">
                <span class="text-xl sm:text-2xl font-black tracking-tight">{{ $logs->total() }}</span>
                <span class="text-xs font-bold text-[#555]">logs</span>
            </div>
        </div>

        <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-purple-500">
            <div class="flex items-center gap-2 mb-2 sm:mb-3">
                <div class="w-6 h-6 rounded-md bg-purple-500/10 flex items-center justify-center">
                    <i class="fa-solid fa-car text-[8px] text-purple-400"></i>
                </div>
                <span class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Vehicles</span>
            </div>
            <div class="flex items-baseline gap-1.5">
                <span class="text-xl sm:text-2xl font-black tracking-tight">{{ $uniqueVehicles }}</span>
                <span class="text-xs font-bold text-[#555]">{{ $uniqueVehicles === 1 ? 'unit' : 'units' }}</span>
            </div>
        </div>

        <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-emerald-500 col-span-2 sm:col-span-1">
            <div class="flex items-center gap-2 mb-2 sm:mb-3">
                <div class="w-6 h-6 rounded-md bg-emerald-500/10 flex items-center justify-center">
                    <i class="fa-solid fa-coins text-[8px] text-emerald-400"></i>
                </div>
                <span class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Total Cost</span>
            </div>
            <div class="flex items-baseline gap-1.5">
                <span class="text-xl sm:text-2xl font-black tracking-tight text-emerald-400">₱{{ number_format($totalCost, 2) }}</span>
            </div>
        </div>

    </div>

    <!-- ══════════ SERVICE LOG TABLE ══════════ -->
    <div class="glass-card rounded-[1.25rem] sm:rounded-[1.5rem] overflow-hidden">
        <div class="p-4 sm:p-6 pb-0">
            <div class="flex items-center justify-between mb-4 sm:mb-5">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-[#111] border border-[#1e1e1e] flex items-center justify-center">
                        <i class="fa-solid fa-calendar-days text-[9px] text-amber-400"></i>
                    </div>
                    <span class="text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-[#555]">Service Log</span>
                </div>
                <span class="text-[7px] sm:text-[8px] font-bold text-[#333] uppercase tracking-widest">{{ $logs->total() }} entries</span>
            </div>
        </div>
        <div class="overflow-x-auto -mx-2 px-2 pb-2">
            <table class="w-full text-left min-w-[750px]">
                <thead>
                    <tr class="text-[7px] sm:text-[8px] uppercase tracking-[0.15em] text-[#444] border-b border-[#1e1e1e]">
                        <th class="px-4 sm:px-6 py-2.5 font-bold">Date</th>
                        <th class="px-4 sm:px-6 py-2.5 font-bold">Vehicle</th>
                        <th class="px-4 sm:px-6 py-2.5 font-bold">Service Performed</th>
                        <th class="px-4 sm:px-6 py-2.5 font-bold">Mileage</th>
                        <th class="px-4 sm:px-6 py-2.5 font-bold text-right">Cost</th>
                        <th class="px-4 sm:px-6 py-2.5 font-bold">Comments</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#1a1a1a]">
                    @forelse($logs as $log)
                        @php
                            $plate = $log->fleet?->vehicle?->plate_number ?? 'N/A';
                            $brand = $log->fleet?->vehicle?->brand ?? '';
                            $model = $log->fleet?->vehicle?->model ?? '';

                            $badgeColors = [
                                ['bg' => 'rgba(59,130,246,0.08)',  'text' => '#60a5fa', 'border' => 'rgba(59,130,246,0.2)'],
                                ['bg' => 'rgba(168,85,247,0.08)',  'text' => '#c084fc', 'border' => 'rgba(168,85,247,0.2)'],
                                ['bg' => 'rgba(16,185,129,0.08)',  'text' => '#34d399', 'border' => 'rgba(16,185,129,0.2)'],
                                ['bg' => 'rgba(251,146,60,0.08)',  'text' => '#fb923c', 'border' => 'rgba(251,146,60,0.2)'],
                                ['bg' => 'rgba(244,63,94,0.08)',   'text' => '#fb7185', 'border' => 'rgba(244,63,94,0.2)'],
                                ['bg' => 'rgba(45,212,191,0.08)',  'text' => '#2dd4bf', 'border' => 'rgba(45,212,191,0.2)'],
                            ];
                            $colorIdx = crc32($plate) % count($badgeColors);
                            $color = $badgeColors[$colorIdx];
                        @endphp

                        <tr class="table-row">
                            <!-- Date -->
                            <td class="px-4 sm:px-6 py-3.5">
                                <span class="text-[10px] sm:text-[11px] font-bold text-[#888] block">{{ $log->last_service_date?->format('M d, Y') ?? '—' }}</span>
                                <span class="text-[7px] sm:text-[8px] text-[#333] font-bold uppercase">{{ $log->last_service_date?->format('l') ?? '' }}</span>
                            </td>

                            <!-- Vehicle Badge -->
                            <td class="px-4 sm:px-6 py-3.5">
                                <span class="inline-flex items-center gap-1.5 text-[8px] sm:text-[9px] font-mono font-bold px-2 py-1 rounded-md"
                                      style="background: {{ $color['bg'] }}; color: {{ $color['text'] }}; border: 1px solid {{ $color['border'] }};">
                                    <i class="fa-solid fa-car text-[7px]"></i>
                                    {{ $plate }}
                                </span>
                            </td>

                            <!-- Service Performed -->
                            <td class="px-4 sm:px-6 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-emerald-500/10 border border-emerald-500/15 flex items-center justify-center shrink-0">
                                        <i class="fa-solid fa-check text-[8px] text-emerald-400"></i>
                                    </div>
                                    <p class="text-[10px] sm:text-[11px] font-bold text-[#ccc] truncate max-w-[220px]">{{ $log->maintenanceTask?->tasks_performed ?? '—' }}</p>
                                </div>
                            </td>

                            <!-- Mileage -->
                            <td class="px-4 sm:px-6 py-3.5">
                                <span class="text-[10px] sm:text-[11px] font-bold text-[#666] font-mono">{{ $log->last_service_odo ? number_format($log->last_service_odo) . ' km' : '—' }}</span>
                            </td>

                            <!-- Cost -->
                            <td class="px-4 sm:px-6 py-3.5 text-right">
                                <span class="text-[10px] sm:text-[11px] font-bold text-white font-mono">₱{{ $log->last_service_cost ? number_format($log->last_service_cost, 2) : '0.00' }}</span>
                            </td>

                            <!-- Comments -->
                            <td class="px-4 sm:px-6 py-3.5">
                                <span class="text-[9px] sm:text-[10px] text-[#444] truncate block max-w-[180px]" title="{{ $log->comments ?? '' }}">{{ $log->comments ?? '—' }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-10 sm:py-12">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-10 h-10 rounded-xl bg-[#111] border border-[#1e1e1e] flex items-center justify-center mb-2.5">
                                        <i class="fa-solid fa-calendar-xmark text-sm text-[#333]"></i>
                                    </div>
                                    <p class="text-[10px] sm:text-[11px] text-[#444] font-medium">No maintenance logs recorded yet</p>
                                    <p class="text-[8px] text-[#333] mt-0.5">Service logs will appear here once preventive maintenance is logged</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($logs->hasPages())
        <div class="px-4 sm:px-6 py-3 border-t border-[#1e1e1e] bg-[#111] flex flex-col sm:flex-row justify-between items-center gap-2">
            <span class="text-[7px] text-[#333] font-bold uppercase tracking-widest">
                Showing {{ $logs->firstItem() }}–{{ $logs->lastItem() }} of {{ $logs->total() }}
            </span>
            <div class="flex gap-1">
                {{ $logs->links('maintenance-manager.partials.pagination') }}
            </div>
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
</div></body>
</html>
