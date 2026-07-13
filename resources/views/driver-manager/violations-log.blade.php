<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SmartCommute | Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap');

        [x-cloak] { display: none !important; }

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

        .modal-scroll::-webkit-scrollbar { width: 4px; }
        .modal-scroll::-webkit-scrollbar-track { background: transparent; }
        .modal-scroll::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.1);
            border-radius: 999px;
        }

        input[type="date"]::-webkit-calendar-picker-indicator,
        input[type="time"]::-webkit-calendar-picker-indicator {
            filter: invert(0.6);
            cursor: pointer;
        }

        select.form-input {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='rgba(255,255,255,0.3)' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
            padding-right: 40px;
        }

        select.form-input option {
            background: #111;
            color: #fff;
        }

        .bulk-row {
            animation: slideIn 0.25s ease-out;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes pulseGlow {
            0%, 100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0); }
            50% { box-shadow: 0 0 0 6px rgba(59, 130, 246, 0.08); }
        }

        .row-highlight {
            animation: pulseGlow 1.5s ease-in-out;
        }
    </style>
</head>

<body x-data="violationsLog()" @keydown.escape.window="showModal = false; showBulkModal = false">

    @include('driver-manager.layout.sidebar')

    <main :class="open ? 'ml-72' : 'ml-20'"
        class="sidebar-transition p-8 md:p-12 min-h-screen relative">

        <!-- Header -->
        <header class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-black tracking-tight text-white mb-1">Violations <span class="text-blue-500">Log</span></h1>
                <p class="text-white/40 text-sm">Overview of traffic violations and penalties.</p>
            </div>
            <div class="flex gap-3">
                <button @click="openBulkModal()"
                    class="px-5 py-2.5 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 hover:border-white/20 text-white text-sm font-semibold transition-all transform hover:-translate-y-0.5">
                    <i class="fa-solid fa-layer-group mr-2 text-blue-400"></i> Bulk Add
                </button>
                <button @click="openModal()"
                    class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-sm font-semibold shadow-lg shadow-blue-900/40 transition-all transform hover:-translate-y-0.5">
                    <i class="fa-solid fa-plus mr-2"></i> New Entry
                </button>
            </div>
        </header>

        <!-- Flash messages -->
        @if (session('success'))
            <div class="mb-6 px-4 py-3 rounded-xl border border-emerald-500/30 bg-emerald-500/10 text-emerald-300 text-sm">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 px-4 py-3 rounded-xl border border-red-500/30 bg-red-500/10 text-red-300 text-sm">
                {{ session('error') }}
            </div>
        @endif

        <!-- Table Container -->
        <div class="glass rounded-2xl overflow-hidden border border-white/5 shadow-2xl">

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-white/10 bg-white/[0.02]">
                            <th class="px-6 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-blue-500 w-80 sticky left-0 bg-[#0a0a0a]/95 backdrop-blur-sm z-10">
                                Driver Information
                            </th>
                            <th class="px-6 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-white/40">
                                Violation Details
                            </th>
                            <th class="px-6 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-white/40">
                                Location & Time
                            </th>
                            <th class="px-6 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-white/40 text-right">
                                Financials
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-white/5">
                        @forelse ($violations as $v)
                            @php
                                $isExpired = false;
                                if ($v['expirationDate'] !== 'N/A') {
                                    try {
                                        $isExpired = \Carbon\Carbon::parse($v['expirationDate'])->isPast();
                                    } catch (\Exception $e) {
                                        $isExpired = false;
                                    }
                                }

                                $offenseLabel = match($v['offenseCount']) {
                                    1 => '1st Offense',
                                    2 => '2nd Offense',
                                    3 => '3rd Offense',
                                    default => $v['offenseCount'] . 'th Offense',
                                };

                                $badgeColors = [
                                    'red'    => 'bg-red-500/10 text-red-400 border-red-500/20',
                                    'amber'  => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
                                    'green'  => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
                                    'blue'   => 'bg-blue-500/10 text-blue-400 border-blue-500/20',
                                ];
                                $badgeColor = $badgeColors[$v['codeColor']] ?? $badgeColors['amber'];
                                $penaltyTextClass = $v['penaltyColor'] === 'red' ? 'text-red-400' : 'text-red-400/60';
                            @endphp

                            <tr class="group hover:bg-white/[0.03] transition-colors">
                                <td class="px-6 py-5 sticky left-0 bg-[#0a0a0a] group-hover:bg-[#0a0a0a]/80 backdrop-blur-sm transition-colors z-10 border-r border-white/5">
                                    <div>
                                        <div class="font-bold text-white text-base">{{ $v['driverName'] }}</div>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="font-mono text-xs text-white/50 bg-white/5 px-1.5 py-0.5 rounded">{{ $v['license'] }}</span>
                                            @if ($isExpired)
                                                <span class="text-[10px] font-bold uppercase text-red-400 flex items-center gap-1">
                                                    <i class="fa-solid fa-triangle-exclamation"></i> Expired
                                                </span>
                                            @else
                                                <span class="text-[10px] font-bold uppercase text-emerald-400 flex items-center gap-1">
                                                    <i class="fa-solid fa-shield-halved"></i> Valid
                                                </span>
                                            @endif
                                        </div>
                                        <div class="text-[10px] text-white/30 mt-0.5">Exp: {{ $v['expirationDate'] }}</div>
                                    </div>
                                </td>

                                <td class="px-6 py-5">
                                    <div class="flex flex-col">
                                        <span class="text-sm font-semibold text-white mb-1">{{ $v['violationType'] }}</span>
                                        <div class="flex items-center gap-2">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold border font-mono {{ $badgeColor }}">{{ $v['violationCode'] }}</span>
                                            <span class="text-xs text-white/40">• {{ $offenseLabel }}</span>
                                        </div>
                                        @if ($v['remarks'])
                                            <div class="text-xs text-white/30 mt-2 italic line-clamp-1">
                                                "{{ $v['remarks'] }}"
                                            </div>
                                        @endif
                                    </div>
                                </td>

                                <td class="px-6 py-5">
                                    <div class="flex flex-col gap-2">
                                        <div class="flex items-center gap-2 text-white/70 text-sm">
                                            <i class="fa-solid fa-location-dot w-4 text-center text-blue-500"></i>
                                            <span>{{ $v['location'] }}</span>
                                        </div>
                                        <div class="flex items-center gap-2 text-white/40 text-xs">
                                            <i class="fa-regular fa-calendar w-4 text-center"></i>
                                            <span>{{ $v['date'] }}</span>
                                            <span class="w-1 h-1 rounded-full bg-white/20"></span>
                                            <span>{{ $v['time'] }}</span>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-5 text-right">
                                    <div class="text-lg font-bold text-white">₱ {{ number_format($v['fine'], 2) }}</div>
                                    <div class="text-xs {{ $penaltyTextClass }}">{{ $v['penalty'] ?: 'N/A' }}</div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-16 text-center">
                                    <i class="fa-regular fa-folder-open text-3xl text-white/10 mb-3 block"></i>
                                    <p class="text-white/30 text-sm">No violations recorded yet.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Table Footer -->
            <div class="px-6 py-4 bg-white/[0.01] border-t border-white/5 flex justify-between items-center text-xs text-white/30">
                <span>Showing {{ count($violations) }} entr{{ count($violations) === 1 ? 'y' : 'ies' }}</span>
                <div class="flex gap-2">
                    <button disabled class="px-3 py-1 rounded border border-white/10 opacity-30 cursor-not-allowed">Prev</button>
                    <button class="px-3 py-1 rounded border border-blue-500/30 bg-blue-500/10 text-blue-400">1</button>
                    <button disabled class="px-3 py-1 rounded border border-white/10 opacity-30 cursor-not-allowed">Next</button>
                </div>
            </div>
        </div>
    </main>

    <!-- ==================== SINGLE ENTRY MODAL ==================== -->
    <div x-show="showModal" x-cloak
        class="fixed inset-0 z-[250] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
        @click.self="showModal = false"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0">

        <div class="glass w-full max-w-lg rounded-[2rem] border border-white/10 shadow-2xl overflow-hidden"
            @click.stop
            x-show="showModal"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95">

            <!-- Header -->
            <div class="flex items-center justify-between px-8 pt-7 pb-4">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-blue-500/15 text-blue-400 border border-blue-500/25">
                        <i class="fa-solid fa-file text-sm"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold tracking-tight">New Violation</h3>
                        <p class="text-xs text-gray-500">Log a traffic violation entry</p>
                    </div>
                </div>
                <button type="button" @click="showModal = false"
                    class="flex items-center justify-center w-8 h-8 rounded-lg bg-white/5 hover:bg-white/10 transition text-gray-400 hover:text-white">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>

            <!-- Body -->
            <div class="px-8 pb-8 modal-scroll overflow-y-auto" style="max-height: 75vh;">
                <form id="violationForm" method="POST" action="{{ route('driver-manager.violations-log.store') }}" class="space-y-4">
                    @csrf

                    <!-- Driver Select -->
                    <div>
                        <label class="block mb-1 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                            Driver <span class="text-red-400">*</span>
                        </label>
                        <select name="user_id" x-model="form.driverId" @change="onDriverChange()"
                            class="form-input w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-sm text-white focus:ring-2 focus:ring-blue-500/40 focus:outline-none transition">
                            <option value="">Select a driver...</option>
                            <template x-for="d in drivers" :key="d.id">
                                <option :value="d.id" x-text="d.name + ' — ' + d.license" :selected="form.driverId == d.id"></option>
                            </template>
                        </select>
                        @error('user_id')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Driver preview card -->
                    <div x-show="selectedDriver" x-cloak
                        class="p-3 rounded-xl bg-white/[0.03] border border-white/5">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-semibold text-white" x-text="selectedDriver.name"></p>
                                <p class="text-[10px] text-gray-500" x-text="selectedDriver.license + ' · Exp: ' + selectedDriver.expirationDate"></p>
                            </div>
                            <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded"
                                :class="selectedDriver.isExpired ? 'bg-red-500/10 text-red-400 border border-red-500/20' : 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20'"
                                x-text="selectedDriver.isExpired ? 'Expired' : 'Valid'"></span>
                        </div>
                    </div>

                    <!-- Violation Select -->
                    <div>
                        <label class="block mb-1 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                            Violation Type <span class="text-red-400">*</span>
                        </label>
                        <select name="vc_id" x-model="form.violationCodeId" @change="onViolationChange()"
                            class="form-input w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-sm text-white focus:ring-2 focus:ring-blue-500/40 focus:outline-none transition">
                            <option value="">Select violation...</option>
                            <template x-for="vc in violationCodes" :key="vc.id">
                                <option :value="vc.id" x-text="vc.code + ' — ' + vc.violation_name" :selected="form.violationCodeId == vc.id"></option>
                            </template>
                        </select>
                        @error('vc_id')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Offense Instance -->
                    <div>
                        <label class="block mb-1 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                            Offense Instance <span class="text-red-400">*</span>
                        </label>
                        <select name="violation_instance" x-model="form.offenseCount" @change="calculateFine()"
                            class="form-input w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-sm text-white focus:ring-2 focus:ring-blue-500/40 focus:outline-none transition">
                            <option value="">Select...</option>
                            <option value="1" :selected="form.offenseCount == '1'">1st Offense</option>
                            <option value="2" :selected="form.offenseCount == '2'">2nd Offense</option>
                            <option value="3" :selected="form.offenseCount == '3'">3rd Offense</option>
                            <option value="4" :selected="form.offenseCount == '4'">4th+ Offense</option>
                        </select>
                        @error('violation_instance')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Fine Preview -->
                    <input type="hidden" name="violation_fine" :value="form.violationFine">
                    <div x-show="form.violationFine > 0"
                        class="p-3 rounded-xl bg-blue-500/5 border border-blue-500/15 flex items-center justify-between">
                        <span class="text-[10px] font-bold uppercase tracking-widest text-blue-400/70">Auto-calculated fine</span>
                        <span class="text-lg font-bold text-blue-400" x-text="'₱ ' + Number(form.violationFine).toLocaleString('en-PH', {minimumFractionDigits: 2})"></span>
                    </div>

                    <!-- Location -->
                    <div>
                        <label class="block mb-1 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                            Location <span class="text-red-400">*</span>
                        </label>
                        <input type="text" name="place_of_violation" x-model="form.location" value="{{ old('place_of_violation') }}" placeholder="e.g., Marikina City"
                            class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-sm text-white placeholder-gray-600 focus:ring-2 focus:ring-blue-500/40 focus:outline-none transition">
                        @error('place_of_violation')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Date & Time -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block mb-1 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                                Date <span class="text-red-400">*</span>
                            </label>
                            <input type="date" name="date_of_violation" x-model="form.date" value="{{ old('date_of_violation') }}"
                                class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-sm text-white focus:ring-2 focus:ring-blue-500/40 focus:outline-none transition">
                            @error('date_of_violation')
                                <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-1 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                                Time <span class="text-red-400">*</span>
                            </label>
                            <input type="time" name="time_of_violation" x-model="form.time" value="{{ old('time_of_violation') }}"
                                class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-sm text-white focus:ring-2 focus:ring-blue-500/40 focus:outline-none transition">
                            @error('time_of_violation')
                                <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Remarks -->
                    <div>
                        <label class="block mb-1 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                            Remarks
                        </label>
                        <textarea name="remarks" x-model="form.remarks" rows="2" placeholder="Optional notes..."
                            class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-sm text-white placeholder-gray-600 focus:ring-2 focus:ring-blue-500/40 focus:outline-none transition resize-none">{{ old('remarks') }}</textarea>
                    </div>

                    <!-- Buttons -->
                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="showModal = false"
                            class="flex-1 py-3 rounded-xl bg-white/5 hover:bg-white/10 transition font-bold text-xs uppercase tracking-widest text-gray-300">
                            Cancel
                        </button>
                        <button type="submit"
                            class="flex-1 py-3 rounded-xl bg-blue-600 hover:bg-blue-500 transition font-bold text-xs uppercase tracking-widest text-white">
                            <i class="mr-1.5 fa-solid fa-check"></i> Save Entry
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ==================== BULK ADD MODAL ==================== -->
    <div x-show="showBulkModal" x-cloak
        class="fixed inset-0 z-[250] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
        @click.self="showBulkModal = false"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0">

        <div class="glass w-full max-w-3xl rounded-[2rem] border border-white/10 shadow-2xl overflow-hidden flex flex-col"
            @click.stop
            x-show="showBulkModal"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            style="max-height: 90vh;">

            <!-- Header -->
            <div class="flex items-center justify-between px-8 pt-7 pb-4 shrink-0">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-violet-500/15 text-violet-400 border border-violet-500/25">
                        <i class="fa-solid fa-layer-group text-sm"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold tracking-tight">Bulk Add Violations</h3>
                        <p class="text-xs text-gray-500">Log multiple violations for a single driver</p>
                    </div>
                </div>
                <button type="button" @click="showBulkModal = false"
                    class="flex items-center justify-center w-8 h-8 rounded-lg bg-white/5 hover:bg-white/10 transition text-gray-400 hover:text-white">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>

            <!-- Scrollable Body -->
            <div class="px-8 pb-4 modal-scroll overflow-y-auto flex-1">

                <form id="bulkViolationForm" method="POST" action="{{ route('driver-manager.violations-log.store-bulk') }}" class="space-y-5">
                    @csrf

                    <!-- ===== VALIDATION ERROR SUMMARY ===== -->
                    @if ($errors->has('user_id') || $errors->has('violations'))
                        <div class="p-4 rounded-xl bg-red-500/5 border border-red-500/20">
                            <div class="flex items-center gap-2 mb-2">
                                <i class="fa-solid fa-circle-exclamation text-red-400 text-xs"></i>
                                <span class="text-[10px] font-bold uppercase tracking-widest text-red-400">Please fix the following</span>
                            </div>
                            <ul class="space-y-1">
                                @if ($errors->has('user_id'))
                                    <li class="text-xs text-red-300/80 flex items-start gap-2">
                                        <span class="text-red-500 mt-0.5">·</span>
                                        {{ $errors->first('user_id') }}
                                    </li>
                                @endif
                                @foreach ($errors->get('violations') as $key => $messages)
                                    @php
                                        $rowNum = is_int($key) ? $key + 1 : $key;
                                        $fieldLabel = '';
                                        if (str_contains($key, 'vc_id')) $fieldLabel = 'Violation Type';
                                        elseif (str_contains($key, 'violation_instance')) $fieldLabel = 'Offense';
                                        elseif (str_contains($key, 'violation_fine')) $fieldLabel = 'Fine';
                                        elseif (str_contains($key, 'place_of_violation')) $fieldLabel = 'Location';
                                        elseif (str_contains($key, 'date_of_violation')) $fieldLabel = 'Date';
                                        elseif (str_contains($key, 'time_of_violation')) $fieldLabel = 'Time';
                                        elseif (str_contains($key, 'remarks')) $fieldLabel = 'Remarks';
                                        else $fieldLabel = $key;
                                    @endphp
                                    @foreach ($messages as $message)
                                        <li class="text-xs text-red-300/80 flex items-start gap-2">
                                            <span class="text-red-500 mt-0.5">·</span>
                                            <span><span class="font-semibold text-red-400">Entry #{{ $rowNum }} ({{ $fieldLabel }}):</span> {{ $message }}</span>
                                        </li>
                                    @endforeach
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- ===== DRIVER SECTION (sticky top) ===== -->
                    <div class="p-4 rounded-xl bg-white/[0.03] border border-white/10 sticky top-0 z-20 backdrop-blur-xl" style="background: rgba(10,10,10,0.85);">
                        <label class="block mb-1.5 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                            Driver <span class="text-red-400">*</span>
                        </label>
                        <select name="user_id" x-model="bulk.driverId" @change="onBulkDriverChange()"
                            class="form-input w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-sm text-white focus:ring-2 focus:ring-violet-500/40 focus:outline-none transition"
                            :class="{ 'border-red-500/50': {{ $errors->has('user_id') ? 'true' : 'false' }} }">
                            <option value="">Select a driver...</option>
                            <template x-for="d in drivers" :key="d.id">
                                <option :value="d.id" x-text="d.name + ' — ' + d.license"></option>
                            </template>
                        </select>

                        <!-- Driver preview -->
                        <div x-show="bulk.selectedDriver" x-cloak class="mt-3 flex items-center justify-between">
                            <div>
                                <p class="text-xs font-semibold text-white" x-text="bulk.selectedDriver.name"></p>
                                <p class="text-[10px] text-gray-500" x-text="bulk.selectedDriver.license + ' · Exp: ' + bulk.selectedDriver.expirationDate"></p>
                            </div>
                            <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded"
                                :class="bulk.selectedDriver.isExpired ? 'bg-red-500/10 text-red-400 border border-red-500/20' : 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20'"
                                x-text="bulk.selectedDriver.isExpired ? 'Expired' : 'Valid'"></span>
                        </div>
                    </div>

                    <!-- ===== VIOLATION ROWS ===== -->
                    <div class="space-y-3">
                        <!-- Row counter header -->
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-bold uppercase tracking-widest text-gray-500">
                                Violation Entries
                                <span class="text-violet-400 ml-1" x-text="'(' + bulk.rows.length + ')'"></span>
                            </span>
                            <button type="button" @click="addBulkRow()"
                                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-violet-500/10 hover:bg-violet-500/20 border border-violet-500/20 text-violet-400 text-[11px] font-bold uppercase tracking-wider transition">
                                <i class="fa-solid fa-plus text-[9px]"></i> Add Row
                            </button>
                        </div>

                        <template x-for="(row, index) in bulk.rows" :key="row.id">
                            <div class="bulk-row p-4 rounded-xl border border-white/5 bg-white/[0.02] relative group"
                                :class="row._justAdded ? 'row-highlight border-violet-500/20' : ''"
                                x-init="$watch('bulk.rows[' + index + ']._justAdded', v => { if(v) setTimeout(() => { bulk.rows[' + index + ']._justAdded = false }, 1500) })">

                                <!-- Row header with number and delete -->
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center gap-2">
                                        <span class="flex items-center justify-center w-6 h-6 rounded-lg bg-violet-500/10 text-violet-400 text-[10px] font-black" x-text="index + 1"></span>
                                        <span class="text-xs font-semibold text-white/60">Entry #<span x-text="index + 1"></span></span>
                                    </div>
                                    <button type="button" @click="removeBulkRow(index)"
                                        x-show="bulk.rows.length > 1"
                                        class="opacity-0 group-hover:opacity-100 flex items-center justify-center w-7 h-7 rounded-lg bg-red-500/10 hover:bg-red-500/20 border border-red-500/20 text-red-400 text-xs transition-all">
                                        <i class="fa-solid fa-trash-can text-[10px]"></i>
                                    </button>
                                </div>

                                <!-- Row fields -->
                                <div class="grid grid-cols-2 gap-3 mb-3">
                                    <!-- Violation Type -->
                                    <div class="col-span-2">
                                        <label class="block mb-1 text-[9px] font-bold uppercase tracking-widest text-gray-600">Violation Type <span class="text-red-400/70">*</span></label>
                                        <select :name="'violations[' + index + '][vc_id]'" x-model="row.vcId" @change="calculateBulkRowFine(index)"
                                            class="form-input w-full px-3 py-2.5 rounded-lg bg-white/5 border border-white/10 text-xs text-white focus:ring-2 focus:ring-violet-500/40 focus:outline-none transition">
                                            <option value="">Select violation...</option>
                                            <template x-for="vc in violationCodes" :key="vc.id">
                                                <option :value="vc.id" x-text="vc.code + ' — ' + vc.violation_name"></option>
                                            </template>
                                        </select>
                                    </div>

                                    <!-- Offense Instance -->
                                    <div>
                                        <label class="block mb-1 text-[9px] font-bold uppercase tracking-widest text-gray-600">Offense <span class="text-red-400/70">*</span></label>
                                        <select :name="'violations[' + index + '][violation_instance]'" x-model="row.offenseCount" @change="calculateBulkRowFine(index)"
                                            class="form-input w-full px-3 py-2.5 rounded-lg bg-white/5 border border-white/10 text-xs text-white focus:ring-2 focus:ring-violet-500/40 focus:outline-none transition">
                                            <option value="">Select...</option>
                                            <option value="1">1st Offense</option>
                                            <option value="2">2nd Offense</option>
                                            <option value="3">3rd Offense</option>
                                            <option value="4">4th+ Offense</option>
                                        </select>
                                    </div>

                                    <!-- Location -->
                                    <div>
                                        <label class="block mb-1 text-[9px] font-bold uppercase tracking-widest text-gray-600">Location <span class="text-red-400/70">*</span></label>
                                        <input type="text" :name="'violations[' + index + '][place_of_violation]'" x-model="row.location" placeholder="e.g., Marikina City"
                                            class="w-full px-3 py-2.5 rounded-lg bg-white/5 border border-white/10 text-xs text-white placeholder-gray-700 focus:ring-2 focus:ring-violet-500/40 focus:outline-none transition">
                                    </div>

                                    <!-- Date -->
                                    <div>
                                        <label class="block mb-1 text-[9px] font-bold uppercase tracking-widest text-gray-600">Date <span class="text-red-400/70">*</span></label>
                                        <input type="date" :name="'violations[' + index + '][date_of_violation]'" x-model="row.date"
                                            class="w-full px-3 py-2.5 rounded-lg bg-white/5 border border-white/10 text-xs text-white focus:ring-2 focus:ring-violet-500/40 focus:outline-none transition">
                                    </div>

                                    <!-- Time -->
                                    <div>
                                        <label class="block mb-1 text-[9px] font-bold uppercase tracking-widest text-gray-600">Time <span class="text-red-400/70">*</span></label>
                                        <input type="time" :name="'violations[' + index + '][time_of_violation]'" x-model="row.time"
                                            class="w-full px-3 py-2.5 rounded-lg bg-white/5 border border-white/10 text-xs text-white focus:ring-2 focus:ring-violet-500/40 focus:outline-none transition">
                                    </div>

                                    <!-- Remarks -->
                                    <div class="col-span-2">
                                        <label class="block mb-1 text-[9px] font-bold uppercase tracking-widest text-gray-600">Remarks</label>
                                        <input type="text" :name="'violations[' + index + '][remarks]'" x-model="row.remarks" placeholder="Optional notes..."
                                            class="w-full px-3 py-2.5 rounded-lg bg-white/5 border border-white/10 text-xs text-white placeholder-gray-700 focus:ring-2 focus:ring-violet-500/40 focus:outline-none transition">
                                    </div>
                                </div>

                                <!-- Hidden fine input + row fine display -->
                                <input type="hidden" :name="'violations[' + index + '][violation_fine]'" :value="row.fine">
                                <div class="flex items-center justify-end">
                                    <div x-show="row.fine > 0" class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-blue-500/5 border border-blue-500/10">
                                        <span class="text-[9px] font-bold uppercase tracking-widest text-blue-400/60">Fine</span>
                                        <span class="text-sm font-bold text-blue-400" x-text="'₱ ' + Number(row.fine).toLocaleString('en-PH', {minimumFractionDigits: 2})"></span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </form>
            </div>
                <!-- ===== STICKY FOOTER: TOTAL + ACTIONS ===== -->
            <div class="px-8 py-5 border-t border-white/10 shrink-0 bg-[#0a0a0a]/90 backdrop-blur-xl">
                <!-- Total summary -->
                <div class="flex items-center justify-between mb-4 p-3 rounded-xl bg-violet-500/5 border border-violet-500/15">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-widest text-violet-400/60 block">Total Fines</span>
                        <span class="text-[10px] text-gray-600" x-text="bulk.rows.length + ' entr' + (bulk.rows.length === 1 ? 'y' : 'ies')"></span>
                    </div>
                    <span class="text-xl font-black text-violet-400" x-text="'₱ ' + bulkTotalFine.toLocaleString('en-PH', {minimumFractionDigits: 2})"></span>
                </div>

                <!-- Action buttons -->
                <div class="flex gap-3">
                    <button type="button" @click="showBulkModal = false"
                        class="flex-1 py-3 rounded-xl bg-white/5 hover:bg-white/10 transition font-bold text-xs uppercase tracking-widest text-gray-300">
                        Cancel
                    </button>
                    <button type="button" @click="addBulkRow()"
                        class="px-5 py-3 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 hover:border-white/20 transition font-bold text-xs uppercase tracking-widest text-gray-300">
                        <i class="fa-solid fa-plus mr-1.5"></i> Row
                    </button>
                    <button type="submit" form="bulkViolationForm"
                        class="flex-1 py-3 rounded-xl bg-violet-600 hover:bg-violet-500 transition font-bold text-xs uppercase tracking-widest text-white shadow-lg shadow-violet-900/30">
                        <i class="mr-1.5 fa-solid fa-check-double"></i> Save All
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Auto-open modal on validation failure -->
    @if ($errors->any())
        <script>
            document.addEventListener('alpine:init', () => {
                setTimeout(() => {
                    window.__openViolationModal = true;
                }, 100);
            });
        </script>
    @endif

    <script>
        function violationsLog() {
            let rowIdCounter = 0;

            function createEmptyRow() {
                return {
                    id: ++rowIdCounter,
                    vcId: '',
                    offenseCount: '',
                    fine: 0,
                    location: '',
                    date: new Date().toISOString().split('T')[0],
                    time: '',
                    remarks: '',
                    _justAdded: false,
                };
            }

            return {
                open: true,
                showModal: false,
                showBulkModal: false,

                // Data from controller
                drivers: @json($drivers),
                violationCodes: @json($violationCodes),

                // Single entry
                selectedDriver: null,
                form: {
                    driverId: '{{ old("user_id") }}',
                    violationCodeId: '{{ old("vc_id") }}',
                    offenseCount: '{{ old("violation_instance") }}',
                    violationFine: 0,
                    location: '{{ old("place_of_violation") }}',
                    date: '{{ old("date_of_violation") }}' || new Date().toISOString().split('T')[0],
                    time: '{{ old("time_of_violation") }}',
                    remarks: '{{ old("remarks") }}',
                },

                // Bulk entry
                bulk: {
                    driverId: '',
                    selectedDriver: null,
                    rows: [createEmptyRow()],
                },

                // Computed total
                get bulkTotalFine() {
                    return this.bulk.rows.reduce((sum, r) => sum + (parseFloat(r.fine) || 0), 0);
                },

                init() {
                    // Auto-open single modal on validation failure
                    if (window.__openViolationModal) {
                        this.showModal = true;
                        if (this.form.violationCodeId && this.form.offenseCount) {
                            this.calculateFine();
                        }
                        if (this.form.driverId) {
                            this.onDriverChange();
                        }
                    }

                    // Auto-open bulk modal on validation failure
                    @if(session('bulk_validation_failed'))
                        this.showBulkModal = true;
                        this.bulk.driverId = '{{ old("user_id") }}';
                        if (this.bulk.driverId) this.onBulkDriverChange();
                    @endif
                },

                // ===== SINGLE ENTRY METHODS =====
                openModal() {
                    this.form = {
                        driverId: '',
                        violationCodeId: '',
                        offenseCount: '',
                        violationFine: 0,
                        location: '',
                        date: new Date().toISOString().split('T')[0],
                        time: '',
                        remarks: '',
                    };
                    this.selectedDriver = null;
                    this.showModal = true;
                },

                onDriverChange() {
                    const driver = this.drivers.find(d => d.id == this.form.driverId);
                    if (driver) {
                        const expDate = new Date(driver.expirationDate);
                        driver.isExpired = expDate < new Date();
                        this.selectedDriver = driver;
                    } else {
                        this.selectedDriver = null;
                    }
                },

                onViolationChange() {
                    this.calculateFine();
                },

                calculateFine() {
                    const vc = this.violationCodes.find(v => v.id == this.form.violationCodeId);
                    const count = parseInt(this.form.offenseCount);

                    if (vc && count) {
                        const key = count >= 4 ? 'fourth_offense' : ['first_offense', 'second_offense', 'third_offense'][count - 1];
                        this.form.violationFine = vc[key] || 0;
                    } else {
                        this.form.violationFine = 0;
                    }
                },

                // ===== BULK ENTRY METHODS =====
                openBulkModal() {
                    rowIdCounter = 0;
                    this.bulk = {
                        driverId: '',
                        selectedDriver: null,
                        rows: [createEmptyRow()],
                    };
                    this.showBulkModal = true;
                },

                onBulkDriverChange() {
                    const driver = this.drivers.find(d => d.id == this.bulk.driverId);
                    if (driver) {
                        const expDate = new Date(driver.expirationDate);
                        driver.isExpired = expDate < new Date();
                        this.bulk.selectedDriver = driver;
                    } else {
                        this.bulk.selectedDriver = null;
                    }
                },

                addBulkRow() {
                    const row = createEmptyRow();
                    row._justAdded = true;
                    this.bulk.rows.push(row);

                    // Scroll to the new row after a tick
                    this.$nextTick(() => {
                        const container = this.$el.querySelector('.modal-scroll');
                        if (container) {
                            container.scrollTo({ top: container.scrollHeight, behavior: 'smooth' });
                        }
                    });
                },

                removeBulkRow(index) {
                    if (this.bulk.rows.length > 1) {
                        this.bulk.rows.splice(index, 1);
                    }
                },

                calculateBulkRowFine(index) {
                    const row = this.bulk.rows[index];
                    const vc = this.violationCodes.find(v => v.id == row.vcId);
                    const count = parseInt(row.offenseCount);

                    if (vc && count) {
                        const key = count >= 4 ? 'fourth_offense' : ['first_offense', 'second_offense', 'third_offense'][count - 1];
                        row.fine = vc[key] || 0;
                    } else {
                        row.fine = 0;
                    }
                },
            };
        }
    </script>

</body>

</html>
