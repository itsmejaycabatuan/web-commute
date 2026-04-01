<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartCommute | Payment History</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700&display=swap');

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: radial-gradient(circle at top left, #1e293b, #0f172a);
            min-height: 100vh;
            color: white;
        }

        .glass-panel {
            background: rgba(15, 23, 42, 0.6) !important;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
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

    <header class="max-w-6xl mx-auto mb-12 flex justify-between items-center">
        <div class="flex items-center space-x-4">
            <a href="{{ route('commuter.dashboard') }}"
                class="glass-panel w-10 h-10 rounded-2xl flex items-center justify-center hover:bg-white/10 transition">
                <i class="fa-solid fa-arrow-left text-xs"></i>
            </a>
            <div>
                <h1 class="text-xl font-bold tracking-tight">Payment History</h1>
                <p class="text-[10px] uppercase tracking-[0.2em] text-white/40 font-bold">Transaction Ledger</p>
            </div>
        </div>

        <div class="glass-panel px-6 py-3 rounded-2xl hidden md:flex items-center space-x-8">
            <div class="text-center">
                <p class="text-[9px] uppercase text-white/40 font-bold">Total Spent</p>
                <p class="text-sm font-bold text-blue-400">₱{{ $totalSpent }}</p>
            </div>
            <div class="w-px h-8 bg-white/10"></div>
            <div class="text-center">
                <p class="text-[9px] uppercase text-white/40 font-bold">Wallet Balance</p>
                <p class="text-sm font-bold text-green-400">₱{{ $balance }}</p>
            </div>
        </div>
    </header>

    <main class="max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-8">

        <aside class="lg:col-span-3 space-y-6">
            <div class="glass-panel p-6 rounded-[2rem]">
                <h3 class="text-[10px] font-black uppercase tracking-widest text-white/60 mb-6">Filter By</h3>
                <form action="{{ route('payment.history') }}" method="GET">

                    <div class="space-y-4">
                        {{-- <div>
                            <label class="text-[9px] uppercase text-white/40 font-bold ml-1">Status</label>
                            <select
                                class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-xs text-white outline-none mt-2 focus:border-blue-500/50">
                                <option>All Transactions</option>
                                <option>Completed</option>
                                <option>Pending</option>
                                <option>Refunded</option>
                            </select>
                        </div> --}}
                        {{-- <div>
                            <label class="text-[9px] uppercase text-white/40 font-bold ml-1">Date Range</label>
                            <input type="date"
                                class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-xs text-white outline-none mt-2">
                        </div> --}}
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="text-[9px] uppercase text-white/40 font-bold ml-1">From</label>
                                <input type="date" name="from_date" value="{{ request('from_date') }}"
                                    class="w-full bg-white/5 border border-white/10 rounded-xl px-3 py-3 text-[10px] text-white outline-none mt-2">
                            </div>
                            <div>
                                <label class="text-[9px] uppercase text-white/40 font-bold ml-1">To</label>
                                <input type="date" name="to_date" value="{{ request('to_date') }}"
                                    class="w-full bg-white/5 border border-white/10 rounded-xl px-3 py-3 text-[10px] text-white outline-none mt-2">
                            </div>
                        </div>
                        <button type="submit"
                            class="w-full bg-blue-600 hover:bg-blue-500 py-3 rounded-xl text-[10px] font-bold uppercase tracking-widest transition shadow-lg shadow-blue-600/20">
                            Apply Filters
                        </button>

                        @if(request()->hasAny(['from_date', 'to_date', 'status']))
                            <a href="{{ route('payment.history') }}"
                                class="block text-center text-[9px] text-white/40 hover:text-white mt-2 font-bold uppercase tracking-widest">
                                Clear Filters
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            {{-- <div
                class="glass-panel p-6 rounded-[2rem] border-blue-500/20 bg-gradient-to-br from-blue-600/10 to-transparent">
                <i class="fa-solid fa-file-export text-blue-400 mb-3"></i>
                <h4 class="text-xs font-bold mb-1">Export Data</h4>
                <p class="text-[10px] text-white/50 mb-4">Download your monthly statement in PDF format.</p>
                <button
                    class="text-[10px] font-bold text-blue-400 hover:text-blue-300 uppercase tracking-wider">Download
                    PDF &rarr;</button>
            </div> --}}
        </aside>

        <section class="lg:col-span-9">
            <div class="glass-panel rounded-[2.5rem] overflow-hidden">
                <div class="p-8 border-b border-white/5 flex justify-between items-center">
                    <h3 class="text-sm font-bold">Recent Receipts</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-[10px] uppercase tracking-widest text-white/30 border-b border-white/5">
                                <th class="px-8 py-4 font-bold">Transaction</th>
                                {{-- <th class="px-8 py-4 font-bold">Route / Details</th> --}}
                                <th class="px-8 py-4 font-bold">Date</th>
                                <th class="px-8 py-4 font-bold text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @forelse($recentReceipts as $receipt)
                                <tr class="group hover:bg-white/[0.02] transition-colors  cursor-pointer"
                                    onclick="window.location.href='{{  route('payment.showReceipt', $receipt->id) }}'">
                                    <td class="px-8 py-6">
                                        <div class="flex items-center space-x-4">
                                            <div
                                                class="w-10 h-10 bg-white/5 rounded-xl flex items-center justify-center border border-white/10 group-hover:border-blue-500/30 transition">
                                                <i
                                                    class="fa-solid fa-receipt text-xs text-white/40 group-hover:text-blue-400"></i>
                                            </div>
                                            <div>
                                                <p class="text-xs font-bold text-white">{{ $receipt->transaction_id }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-8 py-6">
                                        <p class="text-[11px] text-white/70">Ride to
                                            {{ $receipt->destination_name ?? 'Downtown' }}
                                        </p>
                                        <p class="text-[9px] text-white/30">Standard Regular Fare</p>
                                    </td>
                                    <td class="px-8 py-6 text-xs text-white/50">
                                        {{ $receipt->paid_at }}
                                    </td>
                                    <td class="px-8 py-6 text-right">
                                        <span
                                            class="text-sm font-bold text-white">-₱{{ number_format($receipt->price, 2) }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-20">
                                        <div class="flex flex-col items-center justify-center opacity-40">
                                            <i class="fa-solid fa-box-open text-3xl mb-4"></i>
                                            <p class="text-xs font-medium">No transaction history found</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-6 border-t border-white/5">
                    {{ $recentReceipts->appends(request()->query())->links('vendor.pagination.custom') }}
                </div>
            </div>
        </section>
    </main>

</body>

</html>