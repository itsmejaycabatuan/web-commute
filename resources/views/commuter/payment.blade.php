<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartCommute | Payment</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700;800&display=swap');

        body {
            background: radial-gradient(circle at top left, #1a1a1a, #050505);
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #fff;
            min-height: 100vh;
        }

        .glass {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.8);
        }

        .glass-inset {
            box-shadow: inset 0 1px 1px rgba(255, 255, 255, 0.1);
        }

        .payment-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        input[type="radio"]:checked+.method-box {
            border-color: #3b82f6;
            background: rgba(59, 130, 246, 0.1);
            transform: translateY(-2px);
        }
    </style>
</head>

<body class="antialiased flex items-center justify-center p-6">

    <div class="w-full max-w-md">
        <div
            class="flex items-center space-x-2 text-[10px] font-bold uppercase tracking-widest opacity-50 mb-8 justify-center">
            <a href="{{ route('commuter.dashboard') }}" class="hover:text-blue-400 transition">Dashboard</a>
            <span>/</span>
            <span class="text-blue-400">Checkout</span>
        </div>

        <div class="glass glass-inset rounded-[2.5rem] p-8 relative overflow-hidden">
            <div class="absolute -top-24 -right-24 w-48 h-48 bg-blue-600/10 blur-[80px] rounded-full"></div>

            <div class="text-center mb-10 relative">
                <h2 class="text-xl font-black text-white tracking-tight">Payment Details</h2>
                <p class="text-[10px] text-blue-400 font-bold mt-2 uppercase tracking-[0.2em]">Secure Checkout</p>
            </div>

            <div class="bg-white/5 rounded-3xl p-6 border border-white/5 mb-8">
                <div class="flex justify-between items-start mb-6">
                    <div class="space-y-1">
                        <p class="text-[9px] uppercase text-gray-500 font-black tracking-widest">From</p>
                        <p class="text-sm font-bold text-white">{{ $pickup }}</p>
                    </div>
                    <div class="text-right space-y-1">
                        <p class="text-[9px] uppercase text-gray-500 font-black tracking-widest">To</p>
                        <p class="text-sm font-bold text-white">{{ $destination }}</p>
                    </div>
                </div>

                <div class="space-y-3 pt-4 border-t border-white/10">
                    <div class="flex justify-between items-center">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Distance</span>
                        <span class="text-xs font-black text-white">{{ $distance }} KM</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Price</span>
                        <span class="text-xs font-black text-white">₱ {{ number_format($price, 2) }}</span>
                    </div>
                </div>
            </div>

            <form action="{{ route('payment.process') }}" method="POST" id="payment-form" class="space-y-8">
                @csrf
                <input type="hidden" name="pickup" value="{{ $pickup }}">
                <input type="hidden" name="destination" value="{{ $destination }}">
                <input type="hidden" name="amount" value="{{ $price }}">
                <input type="hidden" name="transaction-id" value="#SC-{{ strtoupper(Str::random(8)) }}">
                <input type="hidden" name="distance" value="{{ $distance }}">

                <div>
                    <h3 class="text-[10px] font-black mb-4 uppercase tracking-[0.2em] text-blue-400 px-1">Select Method
                    </h3>
                    <div class="grid grid-cols-2 gap-4">
                        <label class="relative cursor-pointer group">
                            <input type="radio" name="payment-method" value="GCash" class="peer sr-only" checked>
                            <div
                                class="method-box p-4 rounded-2xl border border-white/10 bg-white/5 text-center transition-all group-hover:bg-white/10">
                                {{-- <i class="fa-solid fa-mobile-screen text-blue-400 mb-2 block text-lg"></i> --}}
                                <p class="text-[10px] text-white font-black uppercase tracking-widest">GCash</p>
                            </div>
                        </label>

                        <label class="relative cursor-pointer group opacity-50">
                            <input type="radio" name="payment-method" value="card" class="peer sr-only" disabled>
                            <div
                                class="method-box p-4 rounded-2xl border border-white/5 bg-white/5 text-center transition-all">
                                <i class="fa-solid fa-credit-card text-gray-500 mb-2 block text-lg"></i>
                                <p class="text-[10px] text-gray-500 font-black uppercase tracking-widest">Card</p>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="flex items-end justify-between px-2 pt-4 border-t border-white/10">
                    <div>
                        <p class="text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] mb-1">Total to Pay</p>
                        <div class="text-3xl font-black text-white flex items-baseline gap-1">
                            <span class="text-sm font-medium opacity-40">₱</span>
                            {{ number_format($price, 2) }}
                        </div>
                    </div>
                    <i class="fa-solid fa-shield-check text-blue-500/20 text-4xl"></i>
                </div>

                <button type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-500 text-white font-black py-5 px-6 rounded-2xl text-[10px] uppercase tracking-[0.3em] transition-all duration-300 shadow-xl shadow-blue-600/20 active:scale-[0.98]">
                    Confirm & Process Payment
                </button>
            </form>

            <a href="{{ url()->previous() }}"
                class="block text-center mt-8 text-[9px] font-black uppercase tracking-[0.2em] text-gray-500 hover:text-white transition underline decoration-blue-500/30 underline-offset-4">
                <i class="fa-solid fa-chevron-left mr-2"></i> Cancel Transaction
            </a>
        </div>

        <p class="text-center mt-8 text-[9px] font-bold text-gray-600 uppercase tracking-widest">
            Encrypted by SmartCommute SecurePay
        </p>
    </div>

</body>

</html>