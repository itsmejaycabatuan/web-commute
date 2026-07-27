<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartCommute | Top-up Wallet</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700&display=swap');

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: radial-gradient(circle at top right, #1e293b, #0f172a);
            min-height: 100vh;
            overflow-x: hidden;
        }

        .glass-panel {
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .payment-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            border: 2px solid transparent;
        }

        .payment-card:hover {
            background: rgba(255, 255, 255, 0.05);
            transform: translateY(-2px);
        }

        input:checked+.payment-card {
            background: rgba(59, 130, 246, 0.1);
            border-color: #3b82f6;
        }

        .amount-btn {
            transition: all 0.2s;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .amount-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.3);
        }
    </style>
</head>

<body class="text-slate-200 flex items-center justify-center p-6">

    @include('components.flash');

    <div class="w-full max-w-xl">
        <div class="flex items-center justify-between mb-8">
            <a href="{{ route('map') }}"
                class="glass-panel px-4 py-2 rounded-2xl flex items-center gap-2 hover:bg-white/10 transition group">
                <i class="fa-solid fa-arrow-left text-xs group-hover:-translate-x-1 transition-transform"></i>
                <span class="text-xs font-bold uppercase tracking-wider">Back</span>
            </a>
            <div class="flex items-center gap-6">
                <a href="{{ route('payment.topup.history') }}" class="text-right group">
                    <p
                        class="text-[10px] uppercase tracking-[0.2em] text-blue-400 font-black group-hover:text-blue-300 transition">
                        Top-up History</p>
                </a>
                <div class="text-right border-l border-white/10 pl-6">
                    <p class="text-[10px] uppercase tracking-[0.2em] text-white/40 font-black">Current Balance</p>
                    <p class="text-xl font-bold text-white">₱{{ number_format($balance ?? 0, 2) }}</p>
                </div>
            </div>
        </div>

        <form action="{{ route('payment.topup.process') }}" method="POST"
            class="glass-panel p-8 md:p-10 rounded-[3rem] relative overflow-hidden">
            @csrf

            <div class="absolute -top-24 -right-24 w-48 h-48 bg-blue-600/10 blur-[80px] rounded-full"></div>

            <div class="relative">
                <div class="flex items-center gap-4 mb-8">
                    <div
                        class="w-12 h-12 bg-blue-600 rounded-2xl flex items-center justify-center shadow-lg shadow-blue-600/20">
                        <i class="fa-solid fa-wallet text-white text-lg"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-white">Top-up Wallet</h1>
                        <p class="text-xs text-white/50">Add funds to your SmartCommute account</p>
                    </div>
                </div>

                <div class="mb-8">
                    <label class="text-[10px] uppercase tracking-widest text-white/40 font-black mb-4 block">Enter
                        Amount</label>
                    <div class="relative">
                        <span class="absolute left-6 top-1/2 -translate-y-1/2 text-2xl font-bold text-blue-400">₱</span>
                        <input type="number" name="amount" id="amount-input" placeholder="0.00" min="10"
                            class="w-full bg-white/5 border border-white/10 rounded-3xl py-6 pl-12 pr-6 text-3xl font-bold text-white focus:outline-none focus:border-blue-500/50 focus:bg-white/10 transition-all appearance-none">
                    </div>

                    <div class="grid grid-cols-3 gap-3 mt-4">
                        @foreach([100, 200, 500] as $preset)
                            <button type="button" onclick="document.getElementById('amount-input').value = '{{ $preset }}'"
                                class="amount-btn py-3 rounded-2xl text-xs font-bold text-white/70">
                                ₱{{ $preset }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="mb-10">
                    <label class="text-[10px] uppercase tracking-widest text-white/40 font-black mb-4 block">Payment
                        Method</label>
                    <div class="space-y-3">
                        <label class="block relative">
                            <input type="radio" name="payment-method" value="gcash" class="hidden peer" checked>
                            <div class="payment-card glass-panel p-4 rounded-2xl flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 bg-blue-500/20 rounded-xl flex items-center justify-center">
                                        <i class="fa-solid fa-mobile-screen-button text-blue-400"></i>
                                    </div>
                                    <span class="text-sm font-bold">GCash</span>
                                </div>

                            </div>
                        </label>

                        <label class="block relative">
                            <input type="radio" name="payment-method" value="maya" class="hidden peer">
                            <div class="payment-card glass-panel p-4 rounded-2xl flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div
                                        class="w-10 h-10 bg-emerald-500/20 rounded-xl flex items-center justify-center">
                                        <i class="fa-solid fa-bolt text-emerald-400"></i>
                                    </div>
                                    <span class="text-sm font-bold">Maya</span>
                                </div>
                            </div>
                        </label>

                        {{-- <label class="block relative">
                            <input type="radio" name="method" value="card" class="hidden peer">
                            <div class="payment-card glass-panel p-4 rounded-2xl flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 bg-purple-500/20 rounded-xl flex items-center justify-center">
                                        <i class="fa-solid fa-credit-card text-purple-400"></i>
                                    </div>
                                    <span class="text-sm font-bold">Credit / Debit Card</span>
                                </div>
                                <div class="w-5 h-5 rounded-full border-2 border-white/10"></div>
                            </div>
                        </label> --}}

                        <label class="block relative">
                            <input type="radio" name="payment-method" value="admin" class="hidden peer">
                            <div class="payment-card glass-panel p-4 rounded-2xl flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 bg-yellow-500/20 rounded-xl flex items-center justify-center">
                                        <i class="fa-solid fa-code text-yellow-400"></i>
                                    </div>
                                    <span class="text-sm font-bold">Put it in the admin's tab</span>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                <button type="submit"
                    class="w-full bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-500 hover:to-blue-400 text-white font-bold py-5 rounded-2xl text-xs uppercase tracking-[0.2em] transition-all shadow-xl shadow-blue-600/20 active:scale-[0.98]">
                    Confirm Payment
                </button>

                <p class="text-center text-[9px] text-white/30 uppercase tracking-widest mt-6">
                    <i class="fa-solid fa-shield-halved mr-1"></i> Secured by SmartCommute Pay
                </p>
            </div>
        </form>
    </div>

    <script>
    </script>

</body>

</html>
