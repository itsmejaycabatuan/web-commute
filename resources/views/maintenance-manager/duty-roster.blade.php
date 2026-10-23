<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SmartCommute | Duty Roster</title>
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

        [x-cloak] {
            display: none !important;
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

        ::-webkit-scrollbar {
            width: 3px;
            height: 3px;
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

        select.vehicle-sel {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
        }

        .dark select.vehicle-sel {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='rgba(255,255,255,0.3)' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
        }

        select.vehicle-sel option {
            background: #fff;
            color: #1e293b;
        }

        .dark select.vehicle-sel option {
            background: #111;
            color: #fff;
        }

        .form-input {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #1e293b;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .dark .form-input {
            background: #111;
            border: 1px solid #1e1e1e;
            color: #fff;
        }

        .form-input:focus {
            border-color: rgba(59, 130, 246, 0.4);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.06);
        }

        .form-input::placeholder {
            color: #94a3b8;
        }

        .dark .form-input::placeholder {
            color: #2a2a2a;
        }

        .dark input[type="date"]::-webkit-calendar-picker-indicator {
            filter: invert(0.4);
            cursor: pointer;
        }

        .modal-panel {
            animation: modalIn 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes modalIn {
            0% {
                opacity: 0;
                transform: scale(0.95) translateY(10px);
            }

            100% {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }
    </style>
</head>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.vehicle-sel').forEach(function(sel) {
            sel.addEventListener('change', function() {
                const url = new URL(window.location.href);
                url.searchParams.set('vehicle_id', this.value);
                window.location.href = url.toString();
            });
        });
    });
</script>

<body class="antialiased text-gray-900 dark:text-white bg-white dark:bg-[#050505]" x-data>

    <x-layout.sidebar />

    <main :class="$store.sidebar.open ? 'md:ml-72' : 'md:ml-20'"
        class="sidebar-transition pt-8 pr-4 sm:pr-8 pb-8 pl-4 sm:pl-8 min-h-screen mb-12">

        <!-- ── Mobile: Identity Card ── -->
        <div class="lg:hidden mb-5">
            <div class="glass-card p-4 rounded-[1.25rem]">
                <div class="flex items-center gap-3.5">
                    <div class="w-11 h-11 bg-amber-600 rounded-xl flex items-center justify-center shrink-0"><i
                            class="fa-solid fa-wrench text-sm text-white"></i></div>
                    <div class="min-w-0">
                        <h2 class="text-sm font-bold text-gray-900 dark:text-white truncate">Maintenance Manager</h2>
                        <p class="text-[10px] text-gray-500 dark:text-[#555] truncate">{{ Auth::user()->email }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 pt-3.5 mt-3.5 border-t border-gray-200 dark:border-[#1e1e1e]">
                    <i class="fa-solid fa-wrench text-[8px] text-amber-500 dark:text-amber-400"></i>
                    <span class="text-[10px] text-gray-600 dark:text-[#888] font-bold">Fleet Access</span>
                    <span class="text-gray-300 dark:text-[#333]">•</span>
                    <span class="font-mono text-[9px] text-gray-400 dark:text-[#444]">Maintenance</span>
                </div>
            </div>
        </div>

        <!-- ── Page Header (desktop) ── -->
        <div class="hidden lg:block mb-8">
            <div class="flex items-center gap-2 mb-1.5">
                <span class="text-[9px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Shared
                    Vehicles</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-gray-900 dark:text-white">Duty <span
                    class="text-blue-600 dark:text-blue-500">Roster</span></h1>
            <p class="text-[11px] text-gray-500 dark:text-[#555] mt-1 flex items-center gap-2">
                <i class="fa-solid fa-calendar-check text-[9px] text-amber-500 dark:text-amber-400"></i>
                Which of a vehicle's two drivers drives it on which day
            </p>
        </div>

        @if (!$vehicle)
            <div class="flex flex-col items-center justify-center py-32 text-center">
                <div
                    class="w-14 h-14 rounded-2xl bg-gray-100 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] flex items-center justify-center mb-4">
                    <i class="fa-solid fa-calendar-check text-gray-300 dark:text-[#333] text-xl"></i>
                </div>
                <p class="text-gray-400 dark:text-[#444] text-[11px] font-bold mb-1">No shared vehicles yet.</p>
                <p class="text-gray-300 dark:text-[#333] text-[10px] max-w-xs">A roster is only needed when two drivers
                    share a vehicle. Assign two drivers to a vehicle on the Vehicles page first.</p>
            </div>
        @else
            @php
                $drivers = $vehicle->drivers->sortBy('pivot.id')->values();
                $driverName = fn ($id) => $drivers->firstWhere('id', $id)?->name;
                $inputClass = 'w-full bg-gray-50 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] rounded-xl px-4 py-2.5 text-[10px] font-bold text-gray-600 dark:text-[#888] focus:ring-1 focus:ring-blue-500/50 outline-none';
            @endphp

            <!-- ══════════ VEHICLE + TODAY ══════════ -->
            <div class="glass-card rounded-[1.25rem] sm:rounded-[1.5rem] p-4 sm:p-5 mb-5 sm:mb-6">
                <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-4">
                    <div class="flex-1 sm:max-w-[320px]">
                        <label
                            class="text-[7px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#333] block mb-1.5">Shared
                            vehicle</label>
                        <select class="vehicle-sel {{ $inputClass }} cursor-pointer pr-10">
                            @foreach ($vehicles as $v)
                                <option value="{{ $v->id }}" {{ $vehicle->id === $v->id ? 'selected' : '' }}>
                                    {{ $v->plate_number }} — {{ $v->brand }} {{ $v->model }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-center gap-3">
                        <div
                            class="w-9 h-9 rounded-xl bg-blue-500/10 border border-blue-500/15 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-id-card-clip text-[11px] text-blue-500 dark:text-blue-400"></i>
                        </div>
                        <div>
                            <p
                                class="text-[7px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">
                                On duty today</p>
                            <p class="text-[12px] font-bold {{ $dutyToday ? 'text-gray-900 dark:text-white' : 'text-amber-600 dark:text-amber-400' }}">
                                {{ $dutyToday?->name ?? 'Nobody — no roster line for today' }}</p>
                        </div>
                    </div>
                </div>
                <p class="mt-3 text-[10px] text-gray-500 dark:text-[#555]">
                    Drivers:
                    @foreach ($drivers as $d)
                        <span class="font-bold text-gray-700 dark:text-[#aaa]">{{ $d->name }}</span>{{ $loop->last ? '' : ' · ' }}
                    @endforeach
                </p>
            </div>

            <!-- ══════════ GENERATE ══════════ -->
            <div class="glass-card rounded-[1.25rem] sm:rounded-[1.5rem] p-4 sm:p-5 mb-5 sm:mb-6">
                <p class="text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-gray-500 dark:text-[#555] mb-3">
                    Generate an alternating schedule</p>
                <form method="POST" action="{{ route('maintenance-manager.duty-roster.generate', $vehicle) }}" data-ajax novalidate
                    class="grid grid-cols-1 sm:grid-cols-4 gap-3 sm:items-start">
                    @csrf
                    <div>
                        <label class="text-[7px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#333] block mb-1.5">Start date</label>
                        <input type="date" name="start_date" value="{{ today()->toDateString() }}" min="{{ today()->toDateString() }}" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="text-[7px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#333] block mb-1.5">Drives first</label>
                        <select name="first_driver_id" class="{{ $inputClass }} cursor-pointer pr-10">
                            @foreach ($drivers as $d)
                                <option value="{{ $d->id }}">{{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-[7px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#333] block mb-1.5">Days</label>
                        <input type="number" name="days" value="30" min="2" max="120" class="{{ $inputClass }}">
                    </div>
                    <div class="sm:pt-[1.15rem]">
                        <button type="submit"
                            class="w-full px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-[9px] font-bold uppercase tracking-widest transition active:scale-[0.98]">
                            Generate
                        </button>
                    </div>
                </form>
                <p class="mt-2 text-[9px] text-gray-400 dark:text-[#444]">The two drivers take turns, day by day. It replaces
                    whatever those days held; you can swap single days below afterwards.</p>
            </div>

            <!-- ══════════ ROSTER ══════════ -->
            <form method="POST" action="{{ route('maintenance-manager.duty-roster.update', $vehicle) }}" data-ajax novalidate
                class="glass-card rounded-[1.25rem] sm:rounded-[1.5rem] overflow-hidden">
                @csrf
                @method('PUT')
                <div class="p-4 sm:p-5 flex items-center justify-between gap-3 border-b border-gray-200 dark:border-[#1e1e1e]">
                    <div class="flex items-center gap-2">
                        <a href="{{ route('maintenance-manager.duty-roster', ['vehicle_id' => $vehicle->id, 'from' => $prevFrom]) }}"
                            class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] flex items-center justify-center text-gray-500 dark:text-[#666] hover:text-gray-900 dark:hover:text-white transition"
                            aria-label="Previous 4 weeks"><i class="fa-solid fa-chevron-left text-[9px]"></i></a>
                        <a href="{{ route('maintenance-manager.duty-roster', ['vehicle_id' => $vehicle->id]) }}"
                            class="px-3 h-8 rounded-lg bg-gray-100 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] flex items-center text-[8px] font-bold uppercase tracking-widest text-gray-500 dark:text-[#666] hover:text-gray-900 dark:hover:text-white transition">Today</a>
                        <a href="{{ route('maintenance-manager.duty-roster', ['vehicle_id' => $vehicle->id, 'from' => $nextFrom]) }}"
                            class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] flex items-center justify-center text-gray-500 dark:text-[#666] hover:text-gray-900 dark:hover:text-white transition"
                            aria-label="Next 4 weeks"><i class="fa-solid fa-chevron-right text-[9px]"></i></a>
                        <span class="text-[10px] font-bold text-gray-500 dark:text-[#666] ml-1">{{ $days->first()['label'] }} – {{ $days->last()['label'] }}</span>
                    </div>
                    <button type="submit"
                        class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-[9px] font-bold uppercase tracking-widest transition active:scale-[0.98]">
                        Save roster
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left min-w-[520px]">
                        <thead>
                            <tr class="text-[7px] sm:text-[8px] uppercase tracking-[0.15em] text-gray-400 dark:text-[#444] border-b border-gray-200 dark:border-[#1e1e1e]">
                                <th class="px-4 sm:px-6 py-2.5 font-bold">Date</th>
                                <th class="px-4 sm:px-6 py-2.5 font-bold">Driver</th>
                                <th class="px-4 sm:px-6 py-2.5 font-bold text-right">Swap</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-[#1a1a1a]">
                            @foreach ($days as $day)
                                <tr class="table-row {{ $day['is_today'] ? 'bg-blue-50 dark:bg-blue-500/[0.04]' : '' }} {{ $day['is_past'] ? 'opacity-50' : '' }}">
                                    <td class="px-4 sm:px-6 py-2.5 whitespace-nowrap">
                                        <span class="text-[10px] sm:text-[11px] font-bold text-gray-700 dark:text-[#ccc]">{{ $day['label'] }}</span>
                                        <span class="text-[8px] text-gray-400 dark:text-[#444] uppercase ml-1.5">{{ $day['weekday'] }}</span>
                                        @if ($day['is_today'])
                                            <span class="ml-1.5 text-[7px] font-bold uppercase px-1.5 py-0.5 rounded-md bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/15">Today</span>
                                        @endif
                                    </td>
                                    <td class="px-4 sm:px-6 py-2.5">
                                        <select name="days[{{ $day['date'] }}]" data-day-select @disabled($day['is_past'])
                                            class="w-full max-w-[240px] bg-gray-50 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] rounded-lg px-3 py-1.5 text-[10px] font-bold text-gray-600 dark:text-[#888] outline-none cursor-pointer pr-8 disabled:cursor-not-allowed">
                                            <option value="">— Not set —</option>
                                            @foreach ($drivers as $d)
                                                <option value="{{ $d->id }}" @selected($day['driver_id'] === $d->id)>{{ $d->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-4 sm:px-6 py-2.5 text-right">
                                        @unless ($day['is_past'])
                                            <button type="button" data-swap
                                                class="px-3 h-7 rounded-lg bg-gray-100 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] text-[8px] font-bold uppercase tracking-widest text-gray-500 dark:text-[#666] hover:text-gray-900 dark:hover:text-white hover:border-gray-300 dark:hover:border-[#333] transition">
                                                <i class="fa-solid fa-right-left text-[7px] mr-1"></i>Swap
                                            </button>
                                        @endunless
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="px-4 sm:px-6 py-3 border-t border-gray-200 dark:border-[#1e1e1e] text-[9px] text-gray-400 dark:text-[#444]">
                    Swap switches a day to the other driver; change the dropdown to set or clear a day by hand, then press
                    <strong>Save roster</strong>. Past days are history and cannot be changed. A driver can only clock in on
                    this vehicle on a day the roster gives to them.
                </p>
            </form>
        @endif
    </main>

    <script>
        // Swap: move a day to the other driver (a blank day goes to the first driver).
        document.addEventListener('click', function(e) {
            var btn = e.target.closest('[data-swap]');
            if (!btn) return;
            var select = btn.closest('tr').querySelector('[data-day-select]');
            var ids = Array.from(select.options).map(function(o) { return o.value; }).filter(Boolean);
            var next = ids.indexOf(select.value) === 0 ? ids[1] : ids[0];
            select.value = next;
            select.dispatchEvent(new Event('change', { bubbles: true }));
        });
    </script>
</body>

</html>
