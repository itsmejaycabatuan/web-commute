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

        /* Pagination link overrides */
        .pagination a,
        .pagination span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 32px;
            height: 32px;
            padding: 0 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.15s;
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.4);
            background: transparent;
        }
        .pagination a:hover {
            background: rgba(255, 255, 255, 0.05);
            color: rgba(255, 255, 255, 0.8);
        }
        .pagination .active {
            background: rgba(59, 130, 246, 0.1);
            border-color: rgba(59, 130, 246, 0.3);
            color: #60a5fa;
        }
        .pagination .disabled {
            opacity: 0.25;
            pointer-events: none;
        }
    </style>
</head>

<body x-data="{ open: true }" x-init="
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

    @include('maintenance-manager.layout.sidebar');

    <main :class="open ? 'ml-72' : 'ml-20'"
        class="sidebar-transition p-8 md:p-12 min-h-screen">

        <div class="max-w-[1400px] mx-auto">

            <!-- Header -->
            <header class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
                <div>
                    <h2 class="text-3xl font-black tracking-tight mb-1">Maintenance <span class="text-blue-500">Calendar</span></h2>
                    <p class="text-white/40 text-sm">Historical record of all fleet preventive services.</p>
                </div>
                <div class="flex items-center gap-3 self-start">
                    <div>
                        <select id="fleet-filter"
                                class="bg-[#0a0a0a] border border-white/10 text-white text-sm rounded-xl px-4 py-2.5 focus:ring-1 focus:ring-blue-500/50 outline-none appearance-none cursor-pointer pr-10 w-56">
                            <option value="">All Vehicles</option>
                            @foreach($fleetOptions ?? [] as $id => $label)
                                <option value="{{ $id }}" {{ request('fleet') == (string) $id ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </header>

            <!-- Table -->
            <div class="glass rounded-[2rem] border border-white/5 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left whitespace-nowrap">
                        <thead>
                            <tr class="border-b border-white/5 bg-white/[0.02]">
                                <th class="px-6 py-5 text-[10px] font-black uppercase tracking-[0.15em] text-blue-500">Date</th>
                                <th class="px-6 py-5 text-[10px] font-black uppercase tracking-[0.15em] text-blue-500">Vehicle</th>
                                <th class="px-6 py-5 text-[10px] font-black uppercase tracking-[0.15em] text-blue-500">Service Performed</th>
                                <th class="px-6 py-5 text-[10px] font-black uppercase tracking-[0.15em] text-blue-500">Mileage</th>
                                <th class="px-6 py-5 text-[10px] font-black uppercase tracking-[0.15em] text-blue-500 text-right">Cost</th>
                                <th class="px-6 py-5 text-[10px] font-black uppercase tracking-[0.15em] text-blue-500">Comments</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @forelse($logs as $log)
                                @php
                                    $plate = $log->fleet?->vehicle?->plate_number ?? 'N/A';
                                    $brand = $log->fleet?->vehicle?->brand ?? '';
                                    $model = $log->fleet?->vehicle?->model ?? '';
                                    $vehicleLabel = trim("$plate — $brand $model");

                                    // Deterministic color per plate
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

                                <tr class="hover:bg-white/[0.02] transition-colors">
                                    <!-- Date -->
                                    <td class="px-6 py-4 text-sm text-white/50">
                                        {{ $log->last_service_date?->format('M d, Y') ?? '—' }}
                                    </td>

                                    <!-- Vehicle Badge -->
                                    <td class="px-6 py-4">
                                        <span class="text-xs font-mono px-2.5 py-1 rounded-md font-semibold"
                                              style="background: {{ $color['bg'] }}; color: {{ $color['text'] }}; border: 1px solid {{ $color['border'] }};">
                                            {{ $plate }}
                                        </span>
                                    </td>

                                    <!-- Service Performed -->
                                    <td class="px-6 py-4 text-sm text-white font-medium">
                                        {{ $log->maintenanceTask?->tasks_performed ?? '—' }}
                                    </td>

                                    <!-- Mileage -->
                                    <td class="px-6 py-4 text-sm text-white/60 font-mono">
                                        {{ $log->last_service_odo ? number_format($log->last_service_odo) . ' km' : '—' }}
                                    </td>

                                    <!-- Cost -->
                                    <td class="px-6 py-4 text-sm text-white font-mono text-right font-semibold">
                                        ₱ {{ $log->last_service_cost ? number_format($log->last_service_cost, 2) : '0.00' }}
                                    </td>

                                    <!-- Comments -->
                                    <td class="px-6 py-4 text-sm text-white/30 max-w-[250px] truncate">
                                        {{ $log->comments ?? '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-20 text-center">
                                        <div class="flex flex-col items-center">
                                            <div class="w-14 h-14 rounded-2xl bg-white/[0.03] border border-white/5 flex items-center justify-center text-white/10 mb-4">
                                                <i class="fa-solid fa-calendar-xmark text-xl"></i>
                                            </div>
                                            <p class="text-white/30 text-sm mb-1">No maintenance logs recorded yet.</p>
                                            <p class="text-white/15 text-xs">Service logs will appear here once preventive maintenance is logged from the schedule.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($logs->hasPages())
                <!-- Pagination -->
                <div class="px-6 py-4 bg-white/[0.01] border-t border-white/5 flex flex-col sm:flex-row justify-between items-center gap-3 text-xs text-white/30">
                    <span>
                        Showing {{ $logs->firstItem() }}–{{ $logs->lastItem() }} of {{ $logs->total() }} entries
                    </span>
                    <div class="flex gap-1.5">
                        {{ $logs->links('maintenance-manager.partials.pagination') }}
                    </div>
                </div>
                @endif
            </div>
        </div>
    </main>
</body>

</html>
