<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SmartCommute | Fare Rates</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">
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

        input[type="file"] {
            display: none;
        }

        input[type="file"]+label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            border-radius: 12px;
            cursor: pointer;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #64748b;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            transition: all 0.2s;
        }

        input[type="file"]+label:hover {
            background: #f1f5f9;
            color: #334155;
        }

        .dark input[type="file"]+label {
            background: #111;
            border-color: #1e1e1e;
            color: #555;
        }

        .dark input[type="file"]+label:hover {
            background: #1a1a1a;
            color: #888;
            border-color: #333;
        }

        input[type="number"]::-webkit-inner-spin-button,
        input[type="number"]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        input[type="number"] {
            -moz-appearance: textfield;
        }
    </style>
</head>

<body class="antialiased text-gray-900 dark:text-white" x-data>
    <div x-data="{ editing: false }" @keydown.escape.window="editing = false">
        <x-layout.sidebar />

        <main :class="$store.sidebar.open ? 'md:ml-72' : 'md:ml-20'"
            class="sidebar-transition pt-8 pr-4 sm:pr-8 pb-8 pl-4 sm:pl-8 min-h-screen mb-12">

            <!-- ── Mobile: Admin Identity Card ── -->
            <div class="lg:hidden mb-5">
                <div class="glass-card p-4 rounded-[1.25rem]">
                    <div class="flex items-center gap-3.5">
                        <div class="w-11 h-11 bg-red-600 rounded-xl flex items-center justify-center shrink-0">
                            <span
                                class="text-sm font-black text-white">{{ strtoupper(substr(explode('@', Auth::user()->email)[0], 0, 1)) }}</span>
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-sm font-bold text-gray-900 dark:text-white truncate">System Administrator
                            </h2>
                            <p class="text-[10px] text-gray-500 dark:text-[#555] truncate">{{ Auth::user()->email }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 pt-3.5 mt-3.5 border-t border-gray-200 dark:border-[#1e1e1e]">
                        <i class="fa-solid fa-shield-halved text-[8px] text-red-500 dark:text-red-400"></i>
                        <span class="text-[10px] text-gray-600 dark:text-[#888] font-bold">Full Access</span>
                        <span class="text-gray-300 dark:text-[#333]">•</span>
                        <span class="font-mono text-[9px] text-gray-400 dark:text-[#444]">Admin</span>
                    </div>
                </div>
            </div>

            <!-- ── Page Header (desktop) ── -->
            <div class="hidden lg:block mb-8">
                <div class="flex items-center gap-2 mb-1.5">
                    <span
                        class="text-[9px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Configure</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-gray-900 dark:text-white">Fare <span
                        class="text-blue-500 dark:text-blue-400">Rates</span></h1>
                <p class="text-[11px] text-gray-500 dark:text-[#555] mt-1 flex items-center gap-2">
                    <i class="fa-solid fa-road text-[9px] text-blue-500 dark:text-blue-400"></i>
                    <span class="text-gray-700 dark:text-[#888] font-bold">{{ count($rates) }}</span> distance tiers
                    configured
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

            <!-- ══════════ QUICK STATS ══════════ -->
            @php
                $minRegular = $rates->min('regular') ?? 0;
                $maxRegular = $rates->max('regular') ?? 0;
                $minDiscount = $rates->min('discount') ?? 0;
            @endphp

            <div class="grid grid-cols-2 xl:grid-cols-4 gap-3 sm:gap-4 mb-5 sm:mb-6">
                <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-blue-500">
                    <div class="flex items-center gap-2 mb-2 sm:mb-3">
                        <div class="w-6 h-6 rounded-md bg-blue-500/10 flex items-center justify-center">
                            <i class="fa-solid fa-road text-[8px] text-blue-500 dark:text-blue-400"></i>
                        </div>
                        <span
                            class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Tiers</span>
                    </div>
                    <span
                        class="text-2xl sm:text-3xl font-black tracking-tight text-gray-900 dark:text-white">{{ count($rates) }}</span>
                </div>

                <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-emerald-500">
                    <div class="flex items-center gap-2 mb-2 sm:mb-3">
                        <div class="w-6 h-6 rounded-md bg-emerald-500/10 flex items-center justify-center">
                            <i class="fa-solid fa-arrow-down text-[8px] text-emerald-500 dark:text-emerald-400"></i>
                        </div>
                        <span
                            class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Min
                            Regular</span>
                    </div>
                    <span
                        class="text-2xl sm:text-3xl font-black tracking-tight text-emerald-500 dark:text-emerald-400">₱{{ number_format($minRegular, 2) }}</span>
                </div>

                <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-amber-500">
                    <div class="flex items-center gap-2 mb-2 sm:mb-3">
                        <div class="w-6 h-6 rounded-md bg-amber-500/10 flex items-center justify-center">
                            <i class="fa-solid fa-arrow-up text-[8px] text-amber-500 dark:text-amber-400"></i>
                        </div>
                        <span
                            class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Max
                            Regular</span>
                    </div>
                    <span
                        class="text-2xl sm:text-3xl font-black tracking-tight text-amber-500 dark:text-amber-400">₱{{ number_format($maxRegular, 2) }}</span>
                </div>

                <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-purple-500">
                    <div class="flex items-center gap-2 mb-2 sm:mb-3">
                        <div class="w-6 h-6 rounded-md bg-purple-500/10 flex items-center justify-center">
                            <i class="fa-solid fa-percent text-[8px] text-purple-500 dark:text-purple-400"></i>
                        </div>
                        <span
                            class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Min
                            Discount</span>
                    </div>
                    <span
                        class="text-2xl sm:text-3xl font-black tracking-tight text-purple-500 dark:text-purple-400">₱{{ number_format($minDiscount, 2) }}</span>
                </div>
            </div>

            <!-- ══════════ TOOLBAR ══════════ -->
            <div class="flex flex-col sm:flex-row gap-3 mb-5">
                <div class="flex-1"></div>
                <div class="flex items-center gap-2">
                    <button @click="editing = !editing" type="button"
                        :class="editing ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20' :
                            'bg-gray-50 dark:bg-[#111] text-gray-500 dark:text-[#555] border-gray-200 dark:border-[#1e1e1e] hover:border-gray-300 dark:hover:border-[#333] hover:text-gray-700 dark:hover:text-[#888]'"
                        class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-[9px] font-bold uppercase tracking-widest border transition">
                        <i class="fa-solid text-[8px]" :class="editing ? 'fa-xmark' : 'fa-pen-to-square'"></i>
                        <span x-text="editing ? 'Cancel' : 'Edit Rates'"></span>
                    </button>

                    <form method="POST" action="{{ route('fares.upload') }}" enctype="multipart/form-data"
                        class="flex items-center gap-2">
                        @csrf
                        @method('PUT')
                        <input type="file" name="fare" id="fare">
                        <label for="fare" class="!cursor-pointer">
                            <i class="fa-solid fa-cloud-arrow-up text-[9px] text-gray-400 dark:text-[#555]"></i>
                            <span>Select File</span>
                        </label>
                        <button type="submit"
                            class="flex items-center gap-2 px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-[9px] font-bold uppercase tracking-widest text-white transition active:scale-[0.98]">
                            <i class="fa-solid fa-upload text-[8px]"></i>
                            <span>Upload</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- ══════════ RATES TABLE ══════════ -->
            <form action="{{ route('fares.bulk-update') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="glass-card rounded-[1.25rem] sm:rounded-[1.5rem] overflow-hidden">
                    <div class="overflow-x-auto -mx-2 px-2 pb-2">
                        <table class="w-full text-left min-w-[500px]">
                            <thead>
                                <tr
                                    class="text-[8px] sm:text-[9px] uppercase tracking-[0.15em] text-gray-400 dark:text-[#444] border-b border-gray-200 dark:border-[#1e1e1e]">
                                    <th class="px-4 sm:px-6 py-3 font-bold">
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="w-5 h-5 rounded-md bg-blue-500/10 flex items-center justify-center">
                                                <i
                                                    class="fa-solid fa-road text-[7px] text-blue-500 dark:text-blue-400"></i>
                                            </div>
                                            Distance
                                        </div>
                                    </th>
                                    <th class="px-4 sm:px-6 py-3 font-bold">
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="w-5 h-5 rounded-md bg-emerald-500/10 flex items-center justify-center">
                                                <i
                                                    class="fa-solid fa-tag text-[7px] text-emerald-500 dark:text-emerald-400"></i>
                                            </div>
                                            Regular
                                        </div>
                                    </th>
                                    <th class="px-4 sm:px-6 py-3 font-bold">
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="w-5 h-5 rounded-md bg-amber-500/10 flex items-center justify-center">
                                                <i
                                                    class="fa-solid fa-tag text-[7px] text-amber-500 dark:text-amber-400"></i>
                                            </div>
                                            Discounted
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-[#1a1a1a]">
                                @foreach ($rates as $rate)
                                    <tr class="table-row">
                                        <td class="px-4 sm:px-6 py-3.5">
                                            <div class="flex items-center gap-3">
                                                <div
                                                    class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] flex items-center justify-center shrink-0">
                                                    <span
                                                        class="text-[9px] font-black text-gray-500 dark:text-[#555] font-mono">{{ $rate->km }}</span>
                                                </div>
                                                <div>
                                                    <span
                                                        class="text-[11px] font-bold text-gray-700 dark:text-[#ccc]">{{ $rate->km }}
                                                        km</span>
                                                    <input type="hidden" name="rates[{{ $rate->id }}][id]"
                                                        value="{{ $rate->id }}">
                                                </div>
                                            </div>
                                        </td>

                                        <td class="px-4 sm:px-6 py-3.5">
                                            <template x-if="!editing">
                                                <span
                                                    class="text-[12px] font-bold text-emerald-500 dark:text-emerald-400 font-mono">₱{{ number_format($rate->regular, 2) }}</span>
                                            </template>
                                            <template x-if="editing">
                                                <div class="flex items-center gap-2">
                                                    <span
                                                        class="text-[10px] text-gray-400 dark:text-[#333] font-bold">₱</span>
                                                    <input type="number" step="0.01"
                                                        name="rates[{{ $rate->id }}][regular]"
                                                        value="{{ $rate->regular }}"
                                                        class="w-24 px-3 py-1.5 rounded-lg bg-gray-50 dark:bg-[#111] border border-emerald-500/15 text-[11px] text-emerald-500 dark:text-emerald-400 font-mono font-bold focus:outline-none focus:border-emerald-500/40 transition">
                                                </div>
                                            </template>
                                        </td>

                                        <td class="px-4 sm:px-6 py-3.5">
                                            <template x-if="!editing">
                                                <span
                                                    class="text-[12px] font-bold text-amber-500 dark:text-amber-400 font-mono">₱{{ number_format($rate->discount, 2) }}</span>
                                            </template>
                                            <template x-if="editing">
                                                <div class="flex items-center gap-2">
                                                    <span
                                                        class="text-[10px] text-gray-400 dark:text-[#333] font-bold">₱</span>
                                                    <input type="number" step="0.01"
                                                        name="rates[{{ $rate->id }}][discount]"
                                                        value="{{ $rate->discount }}"
                                                        class="w-24 px-3 py-1.5 rounded-lg bg-gray-50 dark:bg-[#111] border border-amber-500/15 text-[11px] text-amber-500 dark:text-amber-400 font-mono font-bold focus:outline-none focus:border-amber-500/40 transition">
                                                </div>
                                            </template>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Save Bar -->
                <div x-show="editing" x-cloak x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-y-2"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 translate-y-2"
                    class="flex items-center justify-between mt-4 p-4 rounded-xl bg-emerald-500/5 border border-emerald-500/10">

                    <div class="flex items-center gap-2.5">
                        <div class="w-6 h-6 rounded-md bg-emerald-500/10 flex items-center justify-center">
                            <i class="fa-solid fa-pen text-[7px] text-emerald-500 dark:text-emerald-400"></i>
                        </div>
                        <div>
                            <p
                                class="text-[9px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-widest">
                                Editing Mode</p>
                            <p class="text-[7px] text-gray-400 dark:text-[#333]">Modify the values above, then save</p>
                        </div>
                    </div>

                    <button type="submit"
                        class="flex items-center gap-2 px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-[9px] font-bold uppercase tracking-widest text-white transition active:scale-[0.98]">
                        <i class="fa-solid fa-check text-[8px]"></i>
                        <span>Save Changes</span>
                    </button>
                </div>
            </form>
        </main>
    </div>
</body>

</html>
