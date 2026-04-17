<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartCommute Admin | Top-up Ledger</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700&display=swap');

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: radial-gradient(circle at top left, #0f172a, #020617);
            min-height: 100vh;
            color: white;
        }

        .glass-panel {
            background: rgba(30, 41, 59, 0.4) !important;
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .custom-scroll::-webkit-scrollbar {
            width: 4px;
        }

        .custom-scroll::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }
    </style>
</head>

<body class="antialiased p-6 md:p-12">

    <header class="max-w-7xl mx-auto mb-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
        <div class="flex items-center space-x-4">
            <a href="{{ route('admin.dashboard') }}"
                class="glass-panel w-10 h-10 rounded-2xl flex items-center justify-center hover:bg-white/10 transition group">
                <i class="fa-solid fa-arrow-left text-xs text-white/60 group-hover:text-white"></i>
            </a>

            <div>
                <div class="flex items-center space-x-3 mb-1">
                    <div
                        class="w-6 h-6 bg-blue-600 rounded-lg flex items-center justify-center shadow-lg shadow-blue-600/40">
                        <i class="fa-solid fa-wallet text-[10px]"></i>
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight">Wallet Top-ups</h1>
                </div>
                <p class="text-[10px] uppercase tracking-[0.2em] text-white/40 font-black ml-1">Global Fund Inflow</p>
            </div>
        </div>

        <div class="flex space-x-4 w-full md:w-auto">
            <div
                class="glass-panel px-6 py-4 rounded-2xl flex-1 md:flex-none flex items-center space-x-4 border-l-4 border-l-blue-500">
                <i class="fa-solid fa-vault text-blue-400"></i>
                <div>
                    <p class="text-[9px] uppercase text-white/40 font-bold">Total Funds In</p>
                    <p class="text-lg font-bold">₱{{ number_format($totalFundsAdded, 2) }}</p>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-8">

        <aside class="lg:col-span-3 space-y-4">
            <div class="glass-panel p-6 rounded-[2rem]">
                <form action="{{ route('admin.topups') }}" method="GET" class="space-y-5">
                    <div>
                        <label class="text-[10px] font-black uppercase tracking-widest text-white/40 ml-1">Search
                            Commuter</label>
                        <div class="relative mt-2">
                            <i
                                class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-white/20 text-xs"></i>
                            <input type="text" name="search" value="{{ request('search') }}"
                                placeholder="Email or ID..."
                                class="w-full bg-white/5 border border-white/10 rounded-xl pl-10 pr-4 py-3 text-xs text-white outline-none focus:border-blue-500/50 transition">
                        </div>
                    </div>

                    <div>
                        <label class="text-[10px] font-black uppercase tracking-widest text-white/40 ml-1">Date
                            Range</label>
                        <div class="space-y-2 mt-2">
                            <input type="date" name="from_date" value="{{ request('from_date') }}"
                                class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-xs text-white outline-none focus:border-blue-500/50">
                            <input type="date" name="to_date" value="{{ request('to_date') }}"
                                class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-xs text-white outline-none focus:border-blue-500/50">
                        </div>
                    </div>

                    <button type="submit"
                        class="w-full bg-blue-600 hover:bg-blue-500 text-white py-3 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all shadow-lg shadow-blue-900/20">
                        Filter History
                    </button>

                    @if(request()->anyFilled(['search', 'from_date', 'to_date']))
                        <a href="{{ route('admin.topups') }}"
                            class="block text-center text-[9px] text-white/40 hover:text-white font-bold uppercase tracking-widest">
                            Clear Filters
                        </a>
                    @endif
                </form>
            </div>
        </aside>

        <section class="lg:col-span-9">
            <div class="glass-panel rounded-[2.5rem] overflow-hidden border-white/10 shadow-2xl">
                <div class="overflow-x-auto custom-scroll">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-[10px] uppercase tracking-widest text-white/30 border-b border-white/5">
                                <th class="px-8 py-6 font-bold">Commuter</th>
                                <th class="px-8 py-6 font-bold">Transaction</th>
                                <th class="px-8 py-6 font-bold">Method</th>
                                <th class="px-8 py-6 font-bold text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @forelse($transactions as $tx)
                                <tr class="hover:bg-white/[0.03] transition-colors group">
                                    <td class="px-8 py-6">
                                        <div class="flex items-center space-x-3">
                                            <div
                                                class="w-8 h-8 rounded-full bg-white/5 flex items-center justify-center border border-white/10 group-hover:border-blue-500/30 transition">
                                                <i class="fa-solid fa-user text-[10px] text-white/40"></i>
                                            </div>
                                            <div>
                                                <p class="text-xs font-bold">{{ $tx->user->email }}</p>
                                                <p class="text-[9px] text-white/40 uppercase">User ID: #{{ $tx->user_id }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-8 py-6">
                                        <code
                                            class="text-[10px] text-blue-400 font-mono bg-blue-400/5 px-2 py-1 rounded">#{{ $tx->id }}</code>
                                        <p class="text-[10px] text-white/30 mt-1">
                                            {{ $tx->created_at->format('M d, h:i A') }}</p>
                                    </td>
                                    <td class="px-8 py-6">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="px-3 py-1 bg-white/5 rounded-lg text-[10px] font-bold uppercase tracking-tighter text-white/60 group-hover:text-blue-400/80 transition">
                                                {{ $tx->payment_method }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-6 text-right">
                                        <span
                                            class="text-sm font-bold text-blue-400">+₱{{ number_format($tx->amount_added, 2) }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-24 text-center">
                                        <div class="opacity-20">
                                            <i class="fa-solid fa-receipt text-4xl mb-4"></i>
                                            <p class="text-xs font-medium uppercase tracking-widest">No top-up records found
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($transactions->hasPages())
                    <div class="p-6 border-t border-white/5 bg-white/[0.01]">
                        {{ $transactions->links() }}
                    </div>
                @endif
            </div>
        </section>
    </main>

</body>

</html>