<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SmartCommute | Maintenance Logs</title>
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
                <span class="text-[9px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Service
                    History</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-gray-900 dark:text-white">Maintenance <span
                    class="text-blue-600 dark:text-blue-500">Logs</span></h1>
            <p class="text-[11px] text-gray-500 dark:text-[#555] mt-1 flex items-center gap-2">
                <i class="fa-solid fa-clock-rotate-left text-[9px] text-amber-500 dark:text-amber-400"></i>
                Every service logged for a vehicle, newest first
            </p>
        </div>

        @if (!$vehicle)
            <div class="flex flex-col items-center justify-center py-32">
                <div
                    class="w-14 h-14 rounded-2xl bg-gray-100 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] flex items-center justify-center mb-4">
                    <i class="fa-solid fa-car text-gray-300 dark:text-[#333] text-xl"></i>
                </div>
                <p class="text-gray-400 dark:text-[#444] text-[11px] font-bold mb-1">No vehicles found.</p>
                <p class="text-gray-300 dark:text-[#333] text-[10px]">Add a vehicle first.</p>
            </div>
        @else
            <!-- ══════════ CONTROLS BAR ══════════ -->
            <div class="glass-card rounded-[1.25rem] sm:rounded-[1.5rem] p-4 sm:p-5 mb-5 sm:mb-6">
                <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
                    <div class="flex-1 sm:max-w-[320px]">
                        <label
                            class="text-[7px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#333] block mb-1.5">Select
                            Vehicle</label>
                        <select
                            class="vehicle-sel w-full bg-gray-50 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] rounded-xl px-4 py-2.5 text-[10px] font-bold text-gray-600 dark:text-[#888] focus:ring-1 focus:ring-blue-500/50 outline-none cursor-pointer pr-10">
                            @foreach ($vehicles as $v)
                                <option value="{{ $v->id }}" {{ $vehicle->id === $v->id ? 'selected' : '' }}>
                                    {{ $v->plate_number }} — {{ $v->brand }} {{ $v->model }}</option>
                            @endforeach
                        </select>
                    </div>
                    <a href="{{ route('maintenance-manager.preventive-maintenance', ['vehicle_id' => $vehicle->id]) }}"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/15 hover:bg-blue-500/20 text-[9px] font-bold uppercase tracking-widest transition">
                        <i class="fa-solid fa-wrench text-[8px]"></i> Log a Service
                    </a>
                </div>
            </div>

            <!-- ══════════ MAINTENANCE LOG ══════════ -->
            <div class="glass-card rounded-[1.25rem] sm:rounded-[1.5rem] overflow-hidden">
                <div class="p-4 sm:p-6 pb-0">
                    <div class="flex items-center justify-between mb-4 sm:mb-5 gap-3">
                        <div class="flex items-center gap-2.5">
                            <div
                                class="w-7 h-7 rounded-lg bg-gray-100 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] flex items-center justify-center">
                                <i class="fa-solid fa-clock-rotate-left text-[9px] text-blue-500 dark:text-blue-400"></i>
                            </div>
                            <div>
                                <span
                                    class="block text-[8px] sm:text-[9px] font-bold uppercase tracking-[0.15em] text-gray-500 dark:text-[#555]">Maintenance
                                    Log</span>
                                <span class="block text-[8px] text-gray-400 dark:text-[#333] mt-0.5">Every service logged
                                    for {{ $vehicle->plate_number }}</span>
                            </div>
                        </div>
                        <span
                            class="text-[7px] sm:text-[8px] font-bold text-gray-400 dark:text-[#333] uppercase tracking-widest shrink-0">{{ $logs->total() }}
                            {{ $logs->total() === 1 ? 'entry' : 'entries' }}</span>
                    </div>
                </div>
                <div class="overflow-x-auto -mx-2 px-2 pb-2">
                    <table class="w-full text-left min-w-[760px]">
                        <thead>
                            <tr
                                class="text-[7px] sm:text-[8px] uppercase tracking-[0.15em] text-gray-400 dark:text-[#444] border-b border-gray-200 dark:border-[#1e1e1e]">
                                <th class="px-4 sm:px-6 py-2.5 font-bold">Date</th>
                                <th class="px-4 sm:px-6 py-2.5 font-bold">Service</th>
                                <th class="px-4 sm:px-6 py-2.5 font-bold">Odometer</th>
                                <th class="px-4 sm:px-6 py-2.5 font-bold">Performed By</th>
                                <th class="px-4 sm:px-6 py-2.5 font-bold">Comments</th>
                                <th class="px-4 sm:px-6 py-2.5 font-bold text-right">Cost</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-[#1a1a1a]">
                            @forelse ($logs as $log)
                                <tr class="table-row">
                                    <td class="px-4 sm:px-6 py-3">
                                        <span
                                            class="text-[10px] sm:text-[11px] font-bold text-gray-600 dark:text-[#aaa] whitespace-nowrap">{{ $log->service_date->format('M d, Y') }}</span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-3">
                                        <span
                                            class="text-[10px] sm:text-[11px] font-bold text-gray-700 dark:text-[#ccc] block truncate max-w-[200px]">{{ $log->maintenanceTask?->tasks_performed ?? 'Removed task' }}</span>
                                        @if ($log->invoice_number)
                                            <span
                                                class="text-[7px] sm:text-[8px] font-mono text-gray-400 dark:text-[#444] uppercase">{{ $log->invoice_number }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 sm:px-6 py-3">
                                        <span
                                            class="text-[10px] sm:text-[11px] font-bold font-mono text-gray-500 dark:text-[#888] whitespace-nowrap">{{ number_format($log->mileage_at_service) }}
                                            km</span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-3">
                                        <span
                                            class="text-[10px] text-gray-500 dark:text-[#666]">{{ $log->performed_by ?: '—' }}</span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-3">
                                        <span
                                            class="text-[9px] text-gray-400 dark:text-[#555] italic block truncate max-w-[220px]">{{ $log->remarks ?: '—' }}</span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-3 text-right">
                                        <span
                                            class="text-[10px] sm:text-[11px] font-bold font-mono text-gray-900 dark:text-white whitespace-nowrap">₱{{ number_format($log->cost, 2) }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-10 sm:py-12">
                                        <div class="flex flex-col items-center justify-center">
                                            <div
                                                class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] flex items-center justify-center mb-2.5">
                                                <i
                                                    class="fa-solid fa-clock-rotate-left text-sm text-gray-300 dark:text-[#333]"></i>
                                            </div>
                                            <p
                                                class="text-[10px] sm:text-[11px] text-gray-400 dark:text-[#444] font-medium">
                                                No services logged for this vehicle yet</p>
                                            <p class="text-[8px] text-gray-300 dark:text-[#333] mt-0.5">Log a service
                                                above and it will appear here</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($logs->hasPages())
                    <div class="px-4 sm:px-6 py-4 border-t border-gray-200 dark:border-[#1e1e1e]">
                        {{ $logs->links('vendor.pagination.custom') }}
                    </div>
                @endif
            </div>
        @endif
    </main>

</body>

</html>
