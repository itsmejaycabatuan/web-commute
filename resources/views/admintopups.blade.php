<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartCommute Admin | Wallet Top-ups</title>
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

    @include('layout.sidebar')

    <main :class="open ? 'ml-72' : 'ml-20'" class="sidebar-transition p-8 md:p-12 min-h-screen">
        <div class="max-w-6xl">
            <header class="flex flex-col gap-4 mb-10 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="text-3xl font-black tracking-tight mb-2">Wallet Top-ups</h2>
                    <p class="text-gray-500 text-sm">Audit trail for all commuter wallet fund additions.</p>
                </div>
                <div class="flex gap-3">
                    <div class="glass px-4 py-2 rounded-xl border border-blue-500/20">
                        <p class="text-[9px] uppercase text-blue-400 font-black tracking-widest">Total Inflow</p>
                        <p class="text-lg font-bold text-white">₱{{ number_format($totalFundsAdded, 2) }}</p>
                    </div>
                </div>
            </header>

            <div class="glass p-6 rounded-2xl border border-white/10 mb-8">
                <form action="{{ route('admin.topups') }}" method="GET"
                    class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    <div class="md:col-span-2">
                        <label
                            class="text-[10px] font-bold uppercase tracking-widest text-gray-500 ml-1 mb-2 block">Search
                            Commuter</label>
                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="Email or User ID..."
                            class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white outline-none focus:border-blue-500/50 transition">
                    </div>
                    <div>
                        <label
                            class="text-[10px] font-bold uppercase tracking-widest text-gray-500 ml-1 mb-2 block">Date</label>
                        <input type="date" name="from_date" value="{{ request('from_date') }}"
                            class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2 text-sm text-white outline-none focus:border-blue-500/50">
                    </div>
                    <button type="submit"
                        class="bg-blue-600 hover:bg-blue-500 py-2.5 rounded-xl text-[10px] font-bold uppercase tracking-widest transition shadow-lg shadow-blue-900/20">
                        Update Ledger
                    </button>
                </form>
            </div>

            <div class="glass rounded-2xl border border-white/10 overflow-hidden shadow-2xl">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-white/5 text-gray-400 uppercase text-[10px] tracking-widest font-bold">
                            <tr>
                                <th class="px-6 py-4">Commuter</th>
                                <th class="px-6 py-4">Transaction #</th>
                                <th class="px-6 py-4">Method</th>
                                <th class="px-6 py-4">Timestamp</th>
                                <th class="px-6 py-4 text-right">Amount Added</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @forelse ($transactions as $tx)
                                <tr class="hover:bg-white/[0.02] transition group">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="w-8 h-8 rounded-full bg-white/5 flex items-center justify-center border border-white/10 group-hover:border-blue-500/30 transition">
                                                <i
                                                    class="fa-solid fa-user text-[10px] text-gray-500 group-hover:text-blue-400"></i>
                                            </div>
                                            <p class="font-medium text-white">{{ $tx->user->email }}</p>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 font-mono text-blue-400 text-xs">#{{ $tx->id }}</td>
                                    <td class="px-6 py-4">
                                        <span
                                            class="px-3 py-1 bg-white/5 rounded-lg text-[10px] font-bold uppercase tracking-tighter text-gray-400 group-hover:text-blue-300 transition">
                                            {{ $tx->payment_method }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-gray-400 text-xs">
                                        {{ $tx->created_at->format('M j, Y • g:i A') }}
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <span
                                            class="text-sm font-bold text-blue-400">+₱{{ number_format($tx->amount_added, 2) }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-gray-500 font-medium">No top-up
                                        records found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($transactions->hasPages())
                    <div class="px-6 py-4 border-t border-white/5 bg-white/[0.01]">
                        {{ $transactions->links() }}
                    </div>
                @endif
            </div>
        </div>
    </main>
</body>

</html>