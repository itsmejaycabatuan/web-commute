<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartCommute | Top-up History</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700&display=swap');

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: radial-gradient(circle at top right, #1e293b, #0f172a);
            min-height: 100vh;
        }

        .glass-panel {
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .custom-scroll::-webkit-scrollbar {
            width: 6px;
        }

        .custom-scroll::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }
    </style>
</head>

<body class="text-slate-200 p-4 md:p-10">

    <div class="max-w-4xl mx-auto">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-10">
            <div>
                <h1 class="text-3xl font-bold text-white tracking-tight">Top-up History</h1>
                <p class="text-white/50 text-sm">Monitor your wallet top-ups and status</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('payment.topup') }}"
                    class="glass-panel px-6 py-3 rounded-2xl flex items-center gap-2 hover:bg-blue-600 transition group border-blue-500/30">
                    <i class="fa-solid fa-plus text-xs"></i>
                    <span class="text-xs font-bold uppercase tracking-widest text-white">New Top-up</span>
                </a>
                <a href="{{ route('commuter.dashboard') }}"
                    class="glass-panel px-6 py-3 rounded-2xl flex items-center gap-2 hover:bg-white/10 transition">
                    <i class="fa-solid fa-house text-xs text-white/60"></i>
                </a>
            </div>
        </div>

        <div class="glass-panel rounded-[2.5rem] overflow-hidden border-white/5 shadow-2xl">
            <div class="overflow-x-auto custom-scroll">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-white/10">
                            <th class="px-8 py-6 text-[10px] uppercase tracking-[0.2em] text-white/40 font-black">Date &
                                Time</th>
                            <th class="px-8 py-6 text-[10px] uppercase tracking-[0.2em] text-white/40 font-black">
                                Transaction ID</th>
                            <th class="px-8 py-6 text-[10px] uppercase tracking-[0.2em] text-white/40 font-black">Method
                            </th>
                            <th class="px-8 py-6 text-[10px] uppercase tracking-[0.2em] text-white/40 font-black">Amount
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @forelse($transactions as $tx)
                            <tr class="hover:bg-white/[0.02] transition-colors group">
                                <td class="px-8 py-6">
                                    <p class="text-sm font-bold text-white">{{ $tx->created_at->format('M d, Y') }}</p>
                                    <p class="text-[10px] text-white/40">{{ $tx->created_at->format('h:i A') }}</p>
                                </td>
                                <td class="px-8 py-6">
                                    <span class="font-mono text-xs text-blue-400/80 bg-blue-400/10 px-2 py-1 rounded-md">
                                        #{{ $tx->id }}
                                    </span>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-lg bg-white/5 flex items-center justify-center">
                                            @if($tx->method == 'gcash') <i
                                                class="fa-solid fa-mobile-screen text-[10px] text-blue-400"></i>
                                            @elseif($tx->method == 'maya') <i
                                                class="fa-solid fa-bolt text-[10px] text-emerald-400"></i>
                                            @else <i class="fa-solid fa-code text-[10px] text-yellow-400"></i>
                                            @endif
                                        </div>
                                        <span class="text-xs font-medium capitalize">{{ $tx->payment_method }}</span>
                                    </div>
                                </td>
                                <td class="px-8 py-6 text-sm font-bold text-white">
                                    ₱{{ number_format($tx->amount_added, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-8 py-20 text-center">
                                    <div
                                        class="w-16 h-16 bg-white/5 rounded-full flex items-center justify-center mx-auto mb-4">
                                        <i class="fa-solid fa-receipt text-white/20 text-xl"></i>
                                    </div>
                                    <p class="text-white/40 font-medium">No transactions found yet</p>
                                    <p class="text-[10px] uppercase tracking-widest text-white/20 mt-1">Start by adding
                                        funds to your wallet</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($transactions->hasPages())
                <div class="px-8 py-4 border-t border-white/10 bg-black/20">
                    {{ $transactions->links() }}
                </div>
            @endif
        </div>

        <p class="text-center text-[9px] text-white/20 uppercase tracking-[0.3em] mt-10">
            SmartCommute Payment Systems &bull; End-to-End Encrypted
        </p>
    </div>

</body>

</html>