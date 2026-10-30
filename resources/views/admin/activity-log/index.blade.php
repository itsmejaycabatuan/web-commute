<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SmartCommute | Activity Log</title>
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

        .table-row {
            transition: all 0.2s ease !important;
        }

        [x-cloak] {
            display: none !important;
        }

        .dark input[type="date"]::-webkit-calendar-picker-indicator {
            filter: invert(0.4);
            cursor: pointer;
        }
    </style>
</head>

@php
    $fieldClass =
        'w-full px-4 py-2.5 bg-gray-50 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] rounded-xl text-[11px] text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-[#333] focus:outline-none focus:border-gray-300 dark:focus:border-[#333] transition';
    $exportUrl = route('activity-log.export', array_filter($filters));
@endphp

<body class="antialiased text-gray-900 dark:text-white" x-data>
    <x-layout.sidebar />

    <main :class="$store.sidebar.open ? 'md:ml-72' : 'md:ml-20'"
        class="sidebar-transition pt-8 pr-4 sm:pr-8 pb-8 pl-4 sm:pl-8 min-h-screen mb-12">

        <!-- ── Page Header ── -->
        <div class="mb-6 lg:mb-8">
            <div class="flex items-center gap-2 mb-1.5">
                <span class="text-[9px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Audit</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-gray-900 dark:text-white">Activity
                <span class="text-blue-500 dark:text-blue-400">Log</span></h1>
            <p class="text-[11px] text-gray-500 dark:text-[#555] mt-1 flex flex-wrap items-center gap-x-2 gap-y-1">
                <i class="fa-solid fa-clipboard-list text-[9px] text-purple-500 dark:text-purple-400"></i>
                <span class="text-gray-700 dark:text-[#888] font-bold">{{ number_format($entries->total()) }}</span>
                {{ $hasFilters ? 'matching entries' : 'entries' }}
                <span class="text-gray-300 dark:text-[#333]">•</span>
                <span class="text-gray-700 dark:text-[#888] font-bold">{{ number_format($stats['today']) }}</span> today
                @if ($stats['failed_signins'] > 0)
                    <span class="text-gray-300 dark:text-[#333]">•</span>
                    <a href="{{ route('activity-log.index', ['event' => 'login_failed', 'from' => now()->subDay()->toDateString()]) }}"
                        class="text-red-500 dark:text-red-400 font-bold hover:underline">{{ number_format($stats['failed_signins']) }}
                        failed sign-in{{ $stats['failed_signins'] === 1 ? '' : 's' }} in the last 24 h</a>
                @endif
            </p>
        </div>

        <!-- ══════════ TABLE CARD ══════════ -->
        <div class="glass-card rounded-[1.25rem] sm:rounded-[1.5rem] overflow-hidden">

            <div class="p-4 sm:p-5 border-b border-gray-200 dark:border-[#1e1e1e]">
                <form action="{{ route('activity-log.index') }}" method="GET" class="flex flex-col gap-3">
                    <div class="flex flex-col lg:flex-row gap-3">
                        <div class="relative flex-1">
                            <i
                                class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-[10px] text-gray-400 dark:text-[#333]"></i>
                            <input type="text" name="q" value="{{ $filters['q'] }}" maxlength="100"
                                placeholder="Search by person (email) or what happened..."
                                class="{{ $fieldClass }} !pl-10">
                        </div>
                        <select name="category" onchange="this.form.submit()" aria-label="Category"
                            class="{{ $fieldClass }} lg:!w-auto">
                            <option value="">All categories</option>
                            @foreach ($categories as $value => $label)
                                <option value="{{ $value }}" @selected($filters['category'] === $value)>{{ $label }}
                                </option>
                            @endforeach
                        </select>
                        <select name="event" onchange="this.form.submit()" aria-label="Event"
                            class="{{ $fieldClass }} lg:!w-auto">
                            <option value="">All events</option>
                            @foreach ($events as $value => $label)
                                <option value="{{ $value }}" @selected($filters['event'] === $value)>{{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex flex-col lg:flex-row gap-3 lg:items-center">
                        <div class="relative flex-1 lg:max-w-[220px]">
                            <i
                                class="fa-regular fa-calendar absolute left-3.5 top-1/2 -translate-y-1/2 text-[10px] text-gray-400 dark:text-[#333]"></i>
                            <input type="date" name="from" value="{{ $filters['from'] }}" aria-label="From date"
                                class="{{ $fieldClass }} !pl-10">
                        </div>
                        <span class="hidden lg:inline text-[10px] text-gray-400 dark:text-[#444]">to</span>
                        <div class="relative flex-1 lg:max-w-[220px]">
                            <i
                                class="fa-regular fa-calendar absolute left-3.5 top-1/2 -translate-y-1/2 text-[10px] text-gray-400 dark:text-[#333]"></i>
                            <input type="date" name="to" value="{{ $filters['to'] }}" aria-label="To date"
                                class="{{ $fieldClass }} !pl-10">
                        </div>
                        <div class="flex gap-3 lg:ml-auto">
                            <button type="submit"
                                class="flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-gray-900 dark:bg-white/10 text-[10px] font-bold uppercase tracking-widest text-white transition active:scale-[0.98] shrink-0">
                                <i class="fa-solid fa-filter text-[9px]"></i><span>Filter</span>
                            </button>
                            @if ($hasFilters)
                                <a href="{{ route('activity-log.index') }}"
                                    class="flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-gray-50 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] text-[10px] font-bold uppercase tracking-widest text-gray-500 dark:text-[#555] shrink-0">
                                    <i class="fa-solid fa-xmark text-[9px]"></i><span>Clear</span>
                                </a>
                            @endif
                            <a href="{{ $exportUrl }}"
                                title="Download the entries that match these filters (newest 10,000)"
                                class="flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-[10px] font-bold uppercase tracking-widest text-white transition active:scale-[0.98] shrink-0">
                                <i class="fa-solid fa-download text-[9px]"></i><span>Export CSV</span>
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <div class="overflow-x-auto -mx-2 px-2 pb-2">
                <table class="w-full text-left min-w-[860px]">
                    <thead>
                        <tr
                            class="text-[8px] sm:text-[9px] uppercase tracking-[0.15em] text-gray-400 dark:text-[#444] border-b border-gray-200 dark:border-[#1e1e1e]">
                            <th class="pl-4 sm:pl-6 py-3 w-6"><span class="sr-only">Details</span></th>
                            <th class="px-3 py-3 font-bold">When</th>
                            <th class="px-3 py-3 font-bold">Who</th>
                            <th class="px-3 py-3 font-bold">What happened</th>
                            <th class="px-3 py-3 font-bold">Record</th>
                            <th class="px-4 sm:px-6 py-3 font-bold">IP address</th>
                        </tr>
                    </thead>

                    @forelse ($rows as $r)
                        <tbody x-data="{ open: false }"
                            class="border-b border-gray-100 dark:border-[#1a1a1a] last:border-0">
                            <tr class="table-row cursor-pointer hover:bg-gray-50/60 dark:hover:bg-white/[0.02]"
                                @click="open = !open">
                                <td class="pl-4 sm:pl-6 py-3 align-top">
                                    <button type="button" @click.stop="open = !open" :aria-expanded="open"
                                        aria-label="Show details for entry {{ $r['id'] }}"
                                        class="w-5 h-5 flex items-center justify-center text-gray-400 dark:text-[#444]">
                                        <i class="fa-solid fa-chevron-right text-[8px] transition-transform"
                                            :class="open ? 'rotate-90' : ''"></i>
                                    </button>
                                </td>
                                <td class="px-3 py-3 align-top whitespace-nowrap">
                                    <div class="text-[10px] sm:text-[11px] font-bold text-gray-700 dark:text-[#ccc]">
                                        {{ $r['when']?->format('M j, Y') }}</div>
                                    <div class="text-[9px] text-gray-400 dark:text-[#555]"
                                        title="{{ $r['when']?->diffForHumans() }}">
                                        {{ $r['when']?->format('g:i:s A') }}</div>
                                </td>
                                <td class="px-3 py-3 align-top">
                                    @if ($r['actor_email'])
                                        <div
                                            class="text-[10px] sm:text-[11px] font-bold text-gray-700 dark:text-[#ccc] truncate max-w-[200px]">
                                            {{ $r['actor_email'] }}</div>
                                        @if ($r['actor_role'])
                                            <div
                                                class="text-[8px] uppercase tracking-wider font-bold text-gray-400 dark:text-[#555]">
                                                {{ $r['actor_role'] }}</div>
                                        @endif
                                    @else
                                        <span class="text-[10px] text-gray-400 dark:text-[#555] italic">Not signed in /
                                            system</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 align-top">
                                    <div class="flex flex-wrap items-center gap-1.5 mb-1">
                                        <span
                                            class="text-[7px] sm:text-[8px] border px-1.5 py-0.5 rounded-md font-bold uppercase {{ $r['category_classes'] }}">{{ $r['category_label'] }}</span>
                                        <span
                                            class="text-[7px] sm:text-[8px] border px-1.5 py-0.5 rounded-md font-bold uppercase {{ $r['event_classes'] }}">{{ $r['event_label'] }}</span>
                                    </div>
                                    <div class="text-[10px] sm:text-[11px] text-gray-600 dark:text-[#aaa] break-words max-w-[420px]">
                                        {{ $r['description'] }}</div>
                                </td>
                                <td class="px-3 py-3 align-top whitespace-nowrap">
                                    <span
                                        class="text-[10px] font-bold text-gray-500 dark:text-[#666]">{{ $r['subject'] ?? '—' }}</span>
                                </td>
                                <td class="px-4 sm:px-6 py-3 align-top whitespace-nowrap">
                                    <span
                                        class="text-[10px] font-mono text-gray-500 dark:text-[#666]">{{ $r['ip'] ?? '—' }}</span>
                                </td>
                            </tr>

                            <!-- Details -->
                            <tr x-show="open" x-cloak style="display:none;">
                                <td></td>
                                <td colspan="5" class="px-3 pb-5 pt-1 pr-4 sm:pr-6">
                                    <div
                                        class="rounded-xl bg-gray-50 dark:bg-[#0d0d0d] border border-gray-200 dark:border-[#1e1e1e] p-4 grid gap-5 lg:grid-cols-3">

                                        {{-- what changed --}}
                                        <div class="lg:col-span-2 min-w-0">
                                            <p class="{{ 'mb-2 text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]' }}">
                                                What changed</p>

                                            @if ($r['changes'])
                                                <table class="w-full text-left text-[10px]">
                                                    <thead>
                                                        <tr class="text-[8px] uppercase tracking-wider text-gray-400 dark:text-[#444]">
                                                            <th class="py-1 pr-3 font-bold">Field</th>
                                                            <th class="py-1 pr-3 font-bold">Before</th>
                                                            <th class="py-1 font-bold">After</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-gray-200 dark:divide-[#1a1a1a]">
                                                        @foreach ($r['changes'] as $c)
                                                            <tr>
                                                                <td class="py-1.5 pr-3 font-bold text-gray-600 dark:text-[#bbb] align-top">
                                                                    {{ $c['field'] }}</td>
                                                                <td class="py-1.5 pr-3 text-red-600/80 dark:text-red-400/80 break-words align-top">
                                                                    {{ $c['old'] }}</td>
                                                                <td class="py-1.5 text-emerald-600 dark:text-emerald-400 break-words align-top">
                                                                    {{ $c['new'] }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            @elseif ($r['tiers'])
                                                <table class="w-full text-left text-[10px]">
                                                    <thead>
                                                        <tr class="text-[8px] uppercase tracking-wider text-gray-400 dark:text-[#444]">
                                                            <th class="py-1 pr-3 font-bold">Distance</th>
                                                            <th class="py-1 font-bold">Regular fare</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-gray-200 dark:divide-[#1a1a1a]">
                                                        @foreach ($r['tiers'] as $t)
                                                            <tr>
                                                                <td class="py-1.5 pr-3 font-bold text-gray-600 dark:text-[#bbb]">{{ $t['km'] }}</td>
                                                                <td class="py-1.5 text-gray-600 dark:text-[#aaa]">{{ $t['regular'] }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            @else
                                                <p class="text-[10px] text-gray-400 dark:text-[#555]">No field values were recorded for this entry.</p>
                                            @endif

                                            @if ($r['facts'])
                                                <dl class="mt-4 grid grid-cols-[auto,1fr] gap-x-4 gap-y-1.5 text-[10px]">
                                                    @foreach ($r['facts'] as $label => $value)
                                                        <dt class="font-bold text-gray-500 dark:text-[#666]">{{ $label }}</dt>
                                                        <dd class="text-gray-700 dark:text-[#bbb] break-words">{{ $value }}</dd>
                                                    @endforeach
                                                </dl>
                                            @endif
                                        </div>

                                        {{-- where it came from --}}
                                        <div class="min-w-0">
                                            <p class="mb-2 text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">
                                                Where it came from</p>
                                            <dl class="grid grid-cols-[auto,1fr] gap-x-4 gap-y-1.5 text-[10px]">
                                                <dt class="font-bold text-gray-500 dark:text-[#666]">Entry</dt>
                                                <dd class="text-gray-700 dark:text-[#bbb]">#{{ $r['id'] }}</dd>
                                                <dt class="font-bold text-gray-500 dark:text-[#666]">Exact time</dt>
                                                <dd class="text-gray-700 dark:text-[#bbb]">{{ $r['when']?->format('Y-m-d H:i:s') }}</dd>
                                                <dt class="font-bold text-gray-500 dark:text-[#666]">IP address</dt>
                                                <dd class="text-gray-700 dark:text-[#bbb] font-mono">{{ $r['ip'] ?? '—' }}</dd>
                                                <dt class="font-bold text-gray-500 dark:text-[#666]">Device</dt>
                                                <dd class="text-gray-700 dark:text-[#bbb]">{{ $r['device'] ?? '—' }}</dd>
                                                @if ($r['user_agent'])
                                                    <dt class="font-bold text-gray-500 dark:text-[#666]">Browser string</dt>
                                                    <dd class="text-gray-400 dark:text-[#555] break-all">{{ $r['user_agent'] }}</dd>
                                                @endif
                                            </dl>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    @empty
                        <tbody>
                            <tr>
                                <td colspan="6" class="py-12 sm:py-16">
                                    <div class="flex flex-col items-center justify-center">
                                        <div
                                            class="w-12 h-12 rounded-xl bg-gray-100 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] flex items-center justify-center mb-3">
                                            <i class="fa-solid fa-clipboard-list text-base text-gray-300 dark:text-[#222]"></i>
                                        </div>
                                        <p class="text-[11px] text-gray-400 dark:text-[#444] font-medium">
                                            {{ $hasFilters ? 'No entries match your filters' : 'Nothing has been recorded yet' }}
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    @endforelse
                </table>
            </div>

            @if ($entries->hasPages())
                <div class="px-4 sm:px-6 py-4 border-t border-gray-200 dark:border-[#1e1e1e]">
                    {{ $entries->links('vendor.pagination.custom') }}
                </div>
            @endif
        </div>

        <p class="mt-4 text-[10px] text-gray-400 dark:text-[#444] leading-relaxed">
            Read-only. Entries are kept for {{ config('activitylog.delete_records_older_than_days') }} days and are
            written by the system when something is created, changed, deleted, signed in to or exported — never when a
            page is only viewed. Times are Philippine time.
        </p>
    </main>
</body>

</html>
