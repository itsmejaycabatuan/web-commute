<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SmartCommute | Fleet Analytics</title>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>

    @include('partials.head-scripts')

    <style>
        .sidebar-transition {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }

        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="antialiased text-gray-900 dark:text-white" x-data>
    <div>
        <x-layout.sidebar />

        <main :class="$store.sidebar.open ? 'md:ml-72' : 'md:ml-20'"
            class="sidebar-transition pt-8 pr-4 sm:pr-8 pb-8 pl-4 sm:pl-8 min-h-screen mb-12">

            <div class="mb-6 lg:mb-8 flex flex-col sm:flex-row sm:items-end justify-between gap-3">
                <div>
                    <span
                        class="text-[9px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444] block mb-1.5">Insights</span>
                    <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-gray-900 dark:text-white">Fleet <span
                            class="text-blue-500 dark:text-blue-400">Analytics</span></h1>
                    <p class="text-[11px] text-gray-500 dark:text-[#555] mt-1">
                        {{ $from->format('M j') }} – {{ $to->format('M j, Y') }} · worked out automatically from time-keeping, GPS, payments, violations and maintenance logs.
                    </p>
                </div>
                <div class="flex items-center gap-1.5">
                    @foreach ($periods as $p)
                        <a href="{{ route('analytics.index', ['days' => $p]) }}"
                            class="px-3 py-1.5 rounded-lg text-[9px] font-bold uppercase tracking-widest border transition {{ $p === $days ? 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20' : 'bg-gray-50 dark:bg-[#111] text-gray-500 dark:text-[#555] border-gray-200 dark:border-[#1e1e1e] hover:text-gray-700 dark:hover:text-[#888]' }}">Last
                            {{ $p }} days</a>
                    @endforeach
                </div>
            </div>

            <!-- ── Headline numbers ── -->
            <div class="grid grid-cols-2 lg:grid-cols-6 gap-3 sm:gap-4 mb-5 sm:mb-6">
                @foreach ([
                    ['Hours worked', number_format($totals['hours'], 1), 'amber'],
                    ['Overtime', number_format($totals['overtime'], 1), 'orange'],
                    ['Distance (km)', number_format($totals['km'], 1), 'blue'],
                    ['Fare revenue', '₱'.number_format($totals['revenue'], 2), 'emerald'],
                    ['Violations', number_format($totals['violations']), 'rose'],
                    ['Maintenance cost', '₱'.number_format($totals['maintenance_cost'], 2), 'purple'],
                ] as [$label, $value, $color])
                    <div class="glass-card p-4 rounded-[1.25rem] border-l-2 border-l-{{ $color }}-500">
                        <span
                            class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444] block mb-1">{{ $label }}</span>
                        <span class="text-base sm:text-lg 2xl:text-xl font-black tracking-tight text-gray-900 dark:text-white break-all">{{ $value }}</span>
                    </div>
                @endforeach
            </div>

            <!-- ── Flags ── -->
            @if ($repeatViolators->isNotEmpty() || $abandoned->isNotEmpty())
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-5 mb-5 sm:mb-6">
                    <div class="glass-card rounded-[1.25rem] p-4 sm:p-5 border-l-2 border-l-rose-500">
                        <span class="text-[9px] font-bold uppercase tracking-[0.15em] text-gray-500 dark:text-[#555] block mb-3">
                            <i class="fa-solid fa-flag mr-1"></i> Repeat violators
                            ({{ config('alerts.repeat_violator_count') }}+ in {{ config('alerts.repeat_violator_days') }} days)
                        </span>
                        @forelse ($repeatViolators as $row)
                            <div class="flex items-center justify-between text-[11px] py-1.5">
                                <span class="font-bold text-gray-700 dark:text-[#ccc]">{{ $row['driver']->name }}</span>
                                <span class="text-rose-600 dark:text-rose-400 font-bold">{{ $row['count'] }} violations · ₱{{ number_format($row['fines'], 2) }}</span>
                            </div>
                        @empty
                            <p class="text-[11px] text-gray-400 dark:text-[#444]">No driver is over the limit.</p>
                        @endforelse
                    </div>
                    <div class="glass-card rounded-[1.25rem] p-4 sm:p-5 border-l-2 border-l-amber-500">
                        <span class="text-[9px] font-bold uppercase tracking-[0.15em] text-gray-500 dark:text-[#555] block mb-3">
                            <i class="fa-solid fa-qrcode mr-1"></i> Scans never paid for
                            ({{ config('alerts.fare_evasion_scans') }}+ in {{ config('alerts.fare_evasion_days') }} days)
                        </span>
                        @forelse ($abandoned as $row)
                            <div class="flex items-center justify-between text-[11px] py-1.5">
                                <span class="font-bold text-gray-700 dark:text-[#ccc] truncate">{{ $row['email'] }}</span>
                                <span class="text-amber-600 dark:text-amber-400 font-bold">{{ $row['count'] }} unpaid scans</span>
                            </div>
                        @empty
                            <p class="text-[11px] text-gray-400 dark:text-[#444]">No commuter is over the limit.</p>
                        @endforelse
                    </div>
                </div>
            @endif

            <!-- ── Overtime trend ── -->
            @php $peak = max(1, collect($trend)->max('hours')); @endphp
            <div class="glass-card rounded-[1.25rem] p-4 sm:p-5 mb-5 sm:mb-6">
                <span class="text-[9px] font-bold uppercase tracking-[0.15em] text-gray-500 dark:text-[#555] block mb-4">
                    <i class="fa-solid fa-chart-column mr-1"></i> Hours per week (overtime in orange)
                </span>
                <div class="flex items-end gap-2 sm:gap-3 h-36">
                    @foreach ($trend as $week)
                        @php
                            $total = ($week['hours'] / $peak) * 100;
                            $ot = $week['hours'] > 0 ? ($week['overtime'] / $week['hours']) * 100 : 0;
                        @endphp
                        <div class="flex-1 flex flex-col items-center justify-end h-full min-w-0"
                            title="Week of {{ $week['label'] }}: {{ $week['hours'] }} h, {{ $week['overtime'] }} h overtime">
                            <div class="w-full rounded-t-md bg-blue-500/30 flex flex-col justify-end overflow-hidden" style="height: {{ max($total, 2) }}%">
                                <div class="w-full bg-orange-500" style="height: {{ $ot }}%"></div>
                            </div>
                            <span class="text-[8px] text-gray-400 dark:text-[#444] mt-1.5 truncate">{{ $week['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- ── Drivers ── -->
            <div class="glass-card rounded-[1.25rem] sm:rounded-[1.5rem] overflow-hidden mb-5 sm:mb-6">
                <div class="p-4 sm:p-5 border-b border-gray-200 dark:border-[#1e1e1e]">
                    <span class="text-[9px] font-bold uppercase tracking-[0.15em] text-gray-500 dark:text-[#555]">Drivers</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left min-w-[760px]">
                        <thead>
                            <tr class="text-[8px] sm:text-[9px] uppercase tracking-[0.15em] text-gray-400 dark:text-[#444] border-b border-gray-200 dark:border-[#1e1e1e]">
                                <th class="px-4 sm:px-6 py-3 font-bold">Driver</th>
                                <th class="px-4 py-3 font-bold text-right">Shifts</th>
                                <th class="px-4 py-3 font-bold text-right">Hours</th>
                                <th class="px-4 py-3 font-bold text-right">Overtime</th>
                                <th class="px-4 py-3 font-bold text-right">Auto-closed</th>
                                <th class="px-4 py-3 font-bold text-right">Fares</th>
                                <th class="px-4 py-3 font-bold text-right">Revenue</th>
                                <th class="px-4 py-3 font-bold text-right">Violations</th>
                                <th class="px-4 sm:px-6 py-3 font-bold text-right">Fines</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-[#1a1a1a] text-[11px]">
                            @forelse ($drivers as $d)
                                <tr class="table-row">
                                    <td class="px-4 sm:px-6 py-3"><span class="font-bold text-gray-700 dark:text-[#ccc] block">{{ $d['name'] }}</span><span class="text-[9px] text-gray-400 dark:text-[#444]">{{ $d['code'] }}</span></td>
                                    <td class="px-4 py-3 text-right">{{ $d['shifts'] }}</td>
                                    <td class="px-4 py-3 text-right font-bold">{{ number_format($d['hours'], 1) }}</td>
                                    <td class="px-4 py-3 text-right {{ $d['overtime'] > 0 ? 'text-orange-600 dark:text-orange-400 font-bold' : 'text-gray-400' }}">{{ number_format($d['overtime'], 1) }}</td>
                                    <td class="px-4 py-3 text-right {{ $d['auto_closed'] > 0 ? 'text-amber-600 dark:text-amber-400 font-bold' : 'text-gray-400' }}">{{ $d['auto_closed'] }}</td>
                                    <td class="px-4 py-3 text-right">{{ $d['fares'] }}</td>
                                    <td class="px-4 py-3 text-right">₱{{ number_format($d['revenue'], 2) }}</td>
                                    <td class="px-4 py-3 text-right {{ $d['violations'] > 0 ? 'text-rose-600 dark:text-rose-400 font-bold' : 'text-gray-400' }}">{{ $d['violations'] }}</td>
                                    <td class="px-4 sm:px-6 py-3 text-right">₱{{ number_format($d['fines'], 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="px-6 py-8 text-center text-[11px] text-gray-400 dark:text-[#444]">No approved drivers yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ── Vehicles ── -->
            <div class="glass-card rounded-[1.25rem] sm:rounded-[1.5rem] overflow-hidden">
                <div class="p-4 sm:p-5 border-b border-gray-200 dark:border-[#1e1e1e]">
                    <span class="text-[9px] font-bold uppercase tracking-[0.15em] text-gray-500 dark:text-[#555]">Vehicles</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left min-w-[760px]">
                        <thead>
                            <tr class="text-[8px] sm:text-[9px] uppercase tracking-[0.15em] text-gray-400 dark:text-[#444] border-b border-gray-200 dark:border-[#1e1e1e]">
                                <th class="px-4 sm:px-6 py-3 font-bold">Vehicle</th>
                                <th class="px-4 py-3 font-bold text-right">Distance</th>
                                <th class="px-4 py-3 font-bold text-right">Hours in service</th>
                                <th class="px-4 py-3 font-bold text-right">Fares</th>
                                <th class="px-4 py-3 font-bold text-right">Revenue</th>
                                <th class="px-4 py-3 font-bold text-right" title="Scans on this vehicle that ended in a fare">Scans paid</th>
                                <th class="px-4 sm:px-6 py-3 font-bold text-right">Maintenance</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-[#1a1a1a] text-[11px]">
                            @forelse ($vehicles as $v)
                                <tr class="table-row">
                                    <td class="px-4 sm:px-6 py-3"><span class="font-mono font-bold text-gray-700 dark:text-[#ccc] block">{{ $v['plate'] }}</span><span class="text-[9px] text-gray-400 dark:text-[#444]">{{ $v['drivers'] ?: 'Unassigned' }}</span></td>
                                    <td class="px-4 py-3 text-right font-bold">{{ number_format($v['km'], 1) }} km</td>
                                    <td class="px-4 py-3 text-right">{{ number_format($v['hours'], 1) }}</td>
                                    <td class="px-4 py-3 text-right">{{ $v['fares'] }}</td>
                                    <td class="px-4 py-3 text-right">₱{{ number_format($v['revenue'], 2) }}</td>
                                    <td class="px-4 py-3 text-right {{ $v['collection_rate'] !== null && $v['collection_rate'] < 70 ? 'text-amber-600 dark:text-amber-400 font-bold' : '' }}">
                                        {{ $v['collection_rate'] === null ? '—' : $v['collection_rate'].'% of '.$v['scans'] }}</td>
                                    <td class="px-4 sm:px-6 py-3 text-right">₱{{ number_format($v['maintenance_cost'], 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-6 py-8 text-center text-[11px] text-gray-400 dark:text-[#444]">No vehicles yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>

</html>
