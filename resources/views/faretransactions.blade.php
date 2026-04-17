<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartCommute Admin | Global Ledger</title>
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

        .status-pill {
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 9px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
    </style>
</head>

<body class="antialiased p-6 md:p-12">

    <header class="max-w-7xl mx-auto mb-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
        <div>
            <div class="flex items-center space-x-3 mb-2">
                <a href="{{ route('admin.dashboard') }}"
                    class="glass-panel w-10 h-10 rounded-2xl flex items-center justify-center hover:bg-white/10 transition group">
                    <i class="fa-solid fa-arrow-left text-xs text-white/60 group-hover:text-white"></i>
                </a>
                <div
                    class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center shadow-lg shadow-blue-600/40">
                    <i class="fa-solid fa-receipt text-xs"></i>
                </div>
                <h1 class="text-2xl font-bold tracking-tight">Fare Transactions</h1>
            </div>
            <p class="text-[10px] uppercase tracking-[0.2em] text-white/40 font-black">Global Transaction Monitoring</p>
        </div>

        <div class="flex space-x-4 w-full md:w-auto">
            <div class="glass-panel px-6 py-4 rounded-2xl flex-1 md:flex-none flex items-center space-x-4">
                <i class="fa-solid fa-chart-line text-blue-400"></i>
                <div>
                    <p class="text-[9px] uppercase text-white/40 font-bold">Total Revenue</p>
                    <p class="text-lg font-bold">₱{{ number_format($totalRevenue, 2) }}</p>
                </div>
            </div>
            <div
                class="glass-panel px-6 py-4 rounded-2xl flex-1 md:flex-none flex items-center space-x-4 border-l-4 border-l-green-500">
                <i class="fa-solid fa-users text-green-400"></i>
                <div>
                    <p class="text-[9px] uppercase text-white/40 font-bold">Active Payers</p>
                    <p class="text-lg font-bold">{{ $activeUsersCount }}</p>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-8">

        <aside class="lg:col-span-3 space-y-4">
            <div class="glass-panel p-6 rounded-[2rem]">
                <form action="{{ route('faretransactions') }}" method="GET" class="space-y-5">
                    <div>
                        <label class="text-[10px] font-black uppercase tracking-widest text-white/40 ml-1">Search
                            User</label>
                        <div class="relative mt-2">
                            <i
                                class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-white/20 text-xs"></i>
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Name or ID..."
                                class="w-full bg-white/5 border border-white/10 rounded-xl pl-10 pr-4 py-3 text-xs text-white outline-none focus:border-blue-500/50 transition">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label class="text-[10px] font-black uppercase tracking-widest text-white/40 ml-1">Date
                                Range</label>
                            <input type="date" name="from_date" value="{{ request('from_date') }}"
                                class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-xs text-white outline-none mt-2">
                            <input type="date" name="to_date" value="{{ request('to_date') }}"
                                class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-xs text-white outline-none mt-2">
                        </div>
                    </div>

                    <button type="submit"
                        class="w-full bg-white text-slate-900 hover:bg-blue-400 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all">
                        Update Report
                    </button>

                    @if(request()->anyFilled(['search', 'from_date', 'to_date']))
                        <a href="{{ route('faretransactions') }}"
                            class="block text-center text-[9px] text-white/40 hover:text-white font-bold uppercase tracking-widest">
                            Reset Filters
                        </a>
                    @endif
                </form>
            </div>

            <button
                class="w-full glass-panel p-4 rounded-2xl flex items-center justify-between group hover:bg-white/10 transition">
                <span class="text-[10px] font-bold uppercase tracking-wider">Export CSV Report</span>
                <i class="fa-solid fa-download text-xs text-white/20 group-hover:text-white"></i>
            </button>
        </aside>

        <section class="lg:col-span-9">
            <div class="glass-panel rounded-[2.5rem] overflow-hidden border-white/10">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-[10px] uppercase tracking-widest text-white/30 border-b border-white/5">
                                <th class="px-8 py-5 font-bold">Commuter</th>
                                <th class="px-8 py-5 font-bold">Transaction ID</th>
                                <th class="px-8 py-5 font-bold">Timestamp</th>
                                <th class="px-8 py-5 font-bold text-right">Amount</th>
                                <th class="px-8 py-5"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @forelse($allTransactions as $tx)
                                <tr class="hover:bg-white/[0.03] transition-colors group">
                                    <td class="px-8 py-5">
                                        <div class="flex items-center space-x-3">
                                            <div>
                                                <p class="text-xs font-bold">{{ $tx->user->email }}</p>
                                                <p class="text-[9px] text-white/40">ID: #{{ $tx->paid_by }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-8 py-5">
                                        <code class="text-[10px] text-blue-400 font-mono">{{ $tx->transaction_id }}</code>
                                    </td>
                                    <td class="px-8 py-5">
                                        <p class="text-xs text-white/70">{{ $tx->created_at->format('M d, Y') }}</p>
                                        <p class="text-[9px] text-white/30">{{ $tx->created_at->format('h:i A') }}</p>
                                    </td>
                                    <td class="px-8 py-5 text-right">
                                        <span
                                            class="text-sm font-bold text-white">₱{{ number_format($tx->price, 2) }}</span>
                                    </td>
                                    <td class="px-8 py-5 text-right">
                                        <a href="{{ route('admin.receipt.show', $tx->id) }}"
                                            class="opacity-0 group-hover:opacity-100 transition-opacity px-4 py-2 bg-white/5 hover:bg-white/10 rounded-lg text-[10px] font-bold uppercase tracking-tighter">
                                            Details
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-24 text-center">
                                        <div class="opacity-20">
                                            <i class="fa-solid fa-magnifying-glass text-4xl mb-4"></i>
                                            <p class="text-xs font-medium uppercase tracking-widest">No transactions matched
                                                your criteria</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-6 border-t border-white/5 bg-white/[0.01]">
                    {{ $allTransactions->links() }}
                </div>
            </div>
        </section>
    </main>
</body>

</html>