<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SmartCommute | My Violations</title>
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
    </style>
</head>

<body class="antialiased text-white" x-data="{ open: false }">

    @include('driver.layout.sidebar')

    <main :class="open ? 'md:ml-[270px]' : 'md:ml-[76px]'" class="sidebar-transition pt-8 pr-4 sm:pr-8 pb-8 pl-4 sm:pl-8 min-h-screen mb-12">

        <!-- ── Mobile: Identity Card ── -->
        <div class="lg:hidden mb-5">
            <div class="glass-card p-4 rounded-[1.25rem]">
                <div class="flex items-center gap-3.5">
                    <div class="w-11 h-11 bg-amber-600 rounded-xl flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-id-card text-white text-sm"></i>
                    </div>
                    <div class="min-w-0">
                        <h2 class="text-sm font-bold text-white truncate">{{ Auth::user()->driver->name }}</h2>
                        <p class="text-[10px] text-[#555] truncate font-mono">{{ Auth::user()->driver->license_number }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 pt-3.5 mt-3.5 border-t border-[#1e1e1e]">
                    <i class="fa-solid fa-file-circle-exclamation text-[8px] text-amber-400"></i>
                    <span class="text-[10px] text-[#888] font-bold">Violation Records</span>
                    <span class="text-[#333]">•</span>
                    <span class="font-mono text-[9px] text-[#444]">{{ count($violations) }} entries</span>
                </div>
            </div>
        </div>

        <!-- ── Page Header (desktop) ── -->
        <div class="hidden lg:block mb-8">
            <div class="flex items-center gap-2 mb-1.5">
                <span class="text-[9px] font-bold uppercase tracking-[0.15em] text-[#444]">My Records</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">My Violations</h1>
            <p class="text-[11px] text-[#555] mt-1 flex items-center gap-2">
                <i class="fa-solid fa-list-check text-[9px] text-amber-400"></i>
                <span class="text-[#888] font-bold">{{ count($violations) }}</span> violation records on file
            </p>
        </div>

        <!-- ══════════ SUMMARY CARDS ══════════ -->
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 sm:gap-4 mb-6">

            <!-- Total Violations -->
            <div class="glass-card rounded-[1.25rem] p-4 sm:p-5">
                <div class="flex items-center gap-2.5 mb-3">
                    <div class="w-8 h-8 rounded-lg bg-amber-500/10 border border-amber-500/15 flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-file text-[10px] text-amber-400"></i>
                    </div>
                    <span class="text-[8px] font-bold uppercase tracking-[0.15em] text-[#333]">Total</span>
                </div>
                <p class="text-2xl sm:text-3xl font-black text-white">{{ count($violations) }}</p>
                <p class="text-[8px] text-[#444] mt-0.5">violation records</p>
            </div>

            <!-- Total Fines -->
            <div class="glass-card rounded-[1.25rem] p-4 sm:p-5">
                <div class="flex items-center gap-2.5 mb-3">
                    <div class="w-8 h-8 rounded-lg bg-red-500/10 border border-red-500/15 flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-peso-sign text-[10px] text-red-400"></i>
                    </div>
                    <span class="text-[8px] font-bold uppercase tracking-[0.15em] text-[#333]">Fines</span>
                </div>
                <p class="text-2xl sm:text-3xl font-black text-white">₱{{ number_format($totalFines, 0) }}</p>
                <p class="text-[8px] text-[#444] mt-0.5">total amount</p>
            </div>

            <!-- Latest Violation -->
            <div class="glass-card rounded-[1.25rem] p-4 sm:p-5 col-span-2 sm:col-span-1">
                <div class="flex items-center gap-2.5 mb-3">
                    <div class="w-8 h-8 rounded-lg bg-blue-500/10 border border-blue-500/15 flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-clock-rotate-left text-[10px] text-blue-400"></i>
                    </div>
                    <span class="text-[8px] font-bold uppercase tracking-[0.15em] text-[#333]">Latest</span>
                </div>
                @if(count($violations) > 0)
                    <p class="text-[11px] sm:text-xs font-bold text-white truncate">{{ $violations->first()['violationType'] }}</p>
                    <p class="text-[8px] text-[#444] mt-0.5">{{ $violations->first()['date'] }}</p>
                @else
                    <p class="text-[11px] font-bold text-[#333]">None</p>
                    <p class="text-[8px] text-[#333] mt-0.5">No records yet</p>
                @endif
            </div>
        </div>

        <!-- ══════════ TABLE CARD ══════════ -->
        <div class="glass-card rounded-[1.25rem] sm:rounded-[1.5rem] overflow-hidden">

            <!-- ── Info Bar ── -->
            <div class="p-4 sm:p-5 border-b border-[#1e1e1e]">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></div>
                        <span class="text-[8px] font-bold text-[#333] uppercase tracking-widest">
                            {{ count($violations) }} entries
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-[8px] font-bold text-[#222] uppercase tracking-wider flex items-center gap-1.5">
                            <i class="fa-solid fa-lock text-[7px]"></i> Read-only
                        </span>
                    </div>
                </div>
            </div>

            <!-- ── Table ── -->
            <div class="overflow-x-auto">
                <table class="w-full text-left min-w-[700px]">
                    <thead>
                        <tr class="text-[8px] sm:text-[9px] uppercase tracking-[0.15em] text-[#444] border-b border-[#1e1e1e]">
                            <th class="px-4 sm:px-6 py-3 font-bold w-12">#</th>
                            <th class="px-4 sm:px-6 py-3 font-bold">Violation</th>
                            <th class="px-4 sm:px-6 py-3 font-bold">Date & Location</th>
                            <th class="px-4 sm:px-6 py-3 font-bold text-right">Fine</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-[#1a1a1a]">
                        @forelse ($violations as $v)
                            @php
                                $offenseLabel = match($v['offenseCount']) {
                                    1 => '1st Offense',
                                    2 => '2nd Offense',
                                    3 => '3rd Offense',
                                    default => $v['offenseCount'] . 'th Offense',
                                };

                                $badgeMap = [
                                    'red'   => 'bg-red-500/10 text-red-400 border border-red-500/15',
                                    'amber' => 'bg-amber-500/10 text-amber-400 border border-amber-500/15',
                                    'green' => 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/15',
                                    'blue'  => 'bg-blue-500/10 text-blue-400 border border-blue-500/15',
                                ];
                                $badgeClass = $badgeMap[$v['codeColor']] ?? $badgeMap['amber'];
                            @endphp

                            <tr class="table-row">
                                <td class="px-4 sm:px-6 py-3">
                                    <span class="text-[10px] font-bold text-[#333]">{{ str_pad($loop->index + 1, 2, '0', STR_PAD_LEFT) }}</span>
                                </td>
                                <td class="px-4 sm:px-6 py-3">
                                    <div>
                                        <span class="text-[10px] sm:text-[11px] font-bold text-[#ccc] block">{{ $v['violationType'] }}</span>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="text-[7px] sm:text-[8px] font-bold uppercase font-mono px-1.5 py-0.5 rounded-md {{ $badgeClass }}">{{ $v['violationCode'] }}</span>
                                            <span class="text-[8px] text-[#444] font-medium">{{ $offenseLabel }}</span>
                                        </div>
                                        @if ($v['remarks'])
                                            <p class="text-[8px] text-[#333] mt-1.5 italic truncate max-w-[240px]">"{{ $v['remarks'] }}"</p>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 sm:px-6 py-3">
                                    <div class="flex items-center gap-2 text-[10px] font-bold text-[#555] mb-1">
                                        <i class="fa-solid fa-location-dot text-[8px] text-blue-400/60"></i>
                                        <span class="truncate max-w-[140px]">{{ $v['location'] }}</span>
                                    </div>
                                    <div class="flex items-center gap-2 text-[8px] text-[#444] font-medium">
                                        <i class="fa-regular fa-calendar text-[7px]"></i>
                                        <span>{{ $v['date'] }}</span>
                                        <span class="text-[#222]">·</span>
                                        <span>{{ $v['time'] }}</span>
                                    </div>
                                </td>
                                <td class="px-4 sm:px-6 py-3 text-right">
                                    <div class="flex items-baseline justify-end gap-1">
                                        <span class="text-[11px] font-bold text-[#ccc]">₱{{ number_format($v['fine'], 2) }}</span>
                                    </div>
                                    <div class="text-[8px] text-red-400/60 font-medium mt-0.5">{{ $v['penalty'] ?: '' }}</div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-16 sm:py-20">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="w-14 h-14 rounded-2xl bg-[#111] border border-[#1e1e1e] flex items-center justify-center mb-4">
                                            <i class="fa-solid fa-shield-halved text-xl text-[#1a1a1a]"></i>
                                        </div>
                                        <p class="text-[12px] text-[#444] font-bold mb-1.5">Clean Record</p>
                                        <p class="text-[10px] text-[#333] max-w-[240px] text-center leading-relaxed">
                                            No violations have been recorded against your license. Keep driving safely.
                                        </p>
                                        <div class="flex items-center gap-1.5 mt-4 px-3 py-1.5 rounded-lg bg-emerald-500/5 border border-emerald-500/10">
                                            <i class="fa-solid fa-circle-check text-[8px] text-emerald-500/40"></i>
                                            <span class="text-[8px] font-bold text-emerald-500/40 uppercase tracking-wider">No offenses</span>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- ── Footer Summary ── -->
            @if(count($violations) > 0)
                <div class="px-4 sm:px-6 py-4 border-t border-[#1e1e1e] flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="text-[8px] font-bold uppercase tracking-[0.15em] text-[#333]">Total accumulated fines</span>
                    </div>
                    <span class="text-sm font-black text-amber-400">₱{{ number_format($totalFines, 2) }}</span>
                </div>
            @endif
        </div>

        <!-- ── Disclaimer ── -->
        <div class="mt-5 px-4 py-3 rounded-xl bg-[#0a0a0a] border border-[#141414] flex items-start gap-3">
            <i class="fa-solid fa-circle-info text-[9px] text-[#333] mt-0.5 shrink-0"></i>
            <p class="text-[9px] text-[#333] leading-relaxed">
                This is a read-only record of violations filed against your license. If you believe any entry is incorrect, please contact your assigned driver manager for review.
            </p>
        </div>

    </main>

</body>

</html>
