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

        .glass {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .sidebar-transition {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
    </style>
</head>

<body x-data="{ open: true }">

    @include('layout.sidebar');

    <main :class="open ? 'ml-72' : 'ml-20'" x-data="{ editing: false }"
        class="sidebar-transition p-8 md:p-12 min-h-screen">

        <div class="max-w-5xl mx-auto">
            <header class="mb-12 flex flex-col md:flex-row md:items-end justify-between gap-6">
                <div>
                    <h2 class="text-3xl font-black tracking-tight mb-2">Fare <span class="text-blue-500">Rates</span>
                    </h2>
                    <p class="text-gray-500 text-sm">Review and manage current transportation fare trends.</p>
                </div>

                <div class="flex gap-3">
                    <button @click="editing = !editing" type="button"
                        :class="editing ? 'bg-amber-500/20 text-amber-500 border-amber-500/50' : 'bg-white/5 text-gray-400 border-white/10'"
                        class="flex items-center gap-3 px-4 py-3 border rounded-2xl text-[10px] font-bold uppercase tracking-widest transition-all">
                        <i class="fa-solid" :class="editing ? 'fa-xmark' : 'fa-pen-to-square'"></i>
                        <span x-text="editing ? 'Cancel' : 'Edit Rates'"></span>
                    </button>

                    <form method="POST" action="{{ route('fares.upload') }}" enctype="multipart/form-data"
                        class="flex gap-3">
                        @csrf
                        @method('PUT')
                        <input type="file" name="fare" id="fare" class="hidden">
                        <label for="fare"
                            class="flex items-center gap-3 px-4 py-3 bg-white/5 border border-white/10 rounded-2xl text-[10px] font-bold uppercase tracking-widest cursor-pointer hover:bg-white/10 transition-all">
                            <i class="fa-solid fa-cloud-arrow-up text-blue-400"></i>
                            <span>Select File</span>
                        </label>
                        <button type="submit"
                            class="bg-blue-600 text-white px-6 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-blue-500 transition active:scale-95 shadow-lg shadow-blue-500/20">
                            Upload Fares
                        </button>
                    </form>
                </div>
            </header>

            <form action="{{ route('fares.bulk-update') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="glass rounded-[2.5rem] overflow-hidden border border-white/5 mb-8">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="border-b border-white/5 bg-white/5">
                                    <th
                                        class="px-8 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-blue-500">
                                        Distance (KMS)</th>
                                    <th
                                        class="px-8 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-blue-500">
                                        Regular</th>
                                    <th
                                        class="px-8 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-blue-500">
                                        Discounted</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                @foreach ($rates as $rate)
                                    <tr class="hover:bg-white/[0.02] transition-colors">
                                        <td class="px-8 py-4 text-sm font-semibold text-white/90">
                                            {{ $rate->km }} km
                                            <input type="hidden" name="rates[{{ $rate->id }}][id]" value="{{ $rate->id }}">
                                        </td>

                                        <td class="px-8 py-4 text-sm font-mono text-emerald-400">
                                            <template x-if="!editing">
                                                <span>₱{{ number_format($rate->regular, 2) }}</span>
                                            </template>
                                            <template x-if="editing">
                                                <input type="number" step="0.01" name="rates[{{ $rate->id }}][regular]"
                                                    value="{{ $rate->regular }}"
                                                    class="bg-white/5 border border-white/10 rounded-lg px-2 py-1 text-emerald-400 focus:outline-none focus:border-emerald-500/50 w-24">
                                            </template>
                                        </td>

                                        <td class="px-8 py-4 text-sm font-mono text-amber-400">
                                            <template x-if="!editing">
                                                <span>₱{{ number_format($rate->discount, 2) }}</span>
                                            </template>
                                            <template x-if="editing">
                                                <input type="number" step="0.01" name="rates[{{ $rate->id }}][discount]"
                                                    value="{{ $rate->discount }}"
                                                    class="bg-white/5 border border-white/10 rounded-lg px-2 py-1 text-amber-400 focus:outline-none focus:border-amber-500/50 w-24">
                                            </template>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div x-show="editing" x-transition class="flex justify-end mb-8">
                    <button type="submit"
                        class="bg-emerald-600 text-white px-8 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-emerald-500 transition active:scale-95 shadow-lg shadow-emerald-500/20">
                        Save All Changes
                    </button>
                </div>
            </form>

        </div>
    </main>
</body>

</html>