<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Payment</title>
</head>

<body>
    <div class="min-h-screen bg-slate-900 flex items-center justify-center p-6">
        <div class="w-full max-w-md bg-white/5 backdrop-blur-xl border border-white/10 rounded-[2.5rem] p-8 shadow-2xl">

            <div class="text-center mb-8">
                <h2 class="text-xl font-bold text-white tracking-tight">Payment Details</h2>
                <p class="text-white/50 text-xs mt-1 uppercase tracking-widest">Complete your booking</p>
            </div>

            <div class="bg-white/5 rounded-3xl p-5 border border-white/10 mb-6">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <p class="text-[10px] uppercase text-blue-400 font-bold tracking-widest mb-1">From</p>
                        <p class="text-sm text-white font-medium">{{ $pickup }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] uppercase text-red-400 font-bold tracking-widest mb-1">To</p>
                        <p class="text-sm text-white font-medium">{{ $destination }}</p>
                    </div>
                </div>

                <div class="pt-4 border-t border-white/10 flex justify-between items-center">
                    <span class="text-xs text-white/60">Total Distance</span>
                    <span class="text-xs font-bold text-white">{{ $distance }} KM</span>
                </div>
            </div>

            <form action="{{ route('payment.process') }}" method="POST" id="payment-form">
                @csrf
                <input type="hidden" name="pickup" value="{{ $pickup }}">
                <input type="hidden" name="destination" value="{{ $destination }}">
                <input type="hidden" name="amount" value="{{ $price }}">

                <h3 class="text-[10px] font-bold mb-3 uppercase tracking-widest text-white/80 px-2">Select Method</h3>
                <div class="grid grid-cols-2 gap-3 mb-8">
                    <label class="relative cursor-pointer group">
                        <input type="radio" name="method" value="gcash" class="peer sr-only" checked>
                        <div
                            class="p-4 rounded-2xl border border-white/10 bg-white/5 text-center transition-all peer-checked:border-blue-500 peer-checked:bg-blue-500/10 group-hover:bg-white/10">
                            <i class="fa-solid fa-mobile-screen text-blue-400 mb-2"></i>
                            <p class="text-[10px] text-white font-bold uppercase">GCash</p>
                        </div>
                    </label>
                    <label class="relative cursor-pointer group">
                        <input type="radio" name="method" value="cash" class="peer sr-only">
                        <div
                            class="p-4 rounded-2xl border border-white/10 bg-white/5 text-center transition-all peer-checked:border-red-500 peer-checked:bg-red-500/10 group-hover:bg-white/10">
                            <i class="fa-solid fa-money-bill-wave text-red-400 mb-2"></i>
                            <p class="text-[10px] text-white font-bold uppercase">Cash</p>
                        </div>
                    </label>
                </div>

                <div class="flex items-center justify-between px-2 mb-8">
                    <span class="text-sm text-white/70">Amount to Pay</span>
                    <div class="text-2xl font-black text-white flex items-center gap-1">
                        <span class="text-sm font-normal opacity-50">₱</span>
                        {{ number_format($price, 2) }}
                    </div>
                </div>

                <button type="submit"
                    class="w-full bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-500 hover:to-blue-400 text-white font-bold py-4 px-6 rounded-2xl text-xs uppercase tracking-[0.2em] transition-all duration-300 shadow-lg shadow-blue-500/20 active:scale-[0.95]">
                    Confirm & Pay
                </button>
            </form>

            <a href="{{ url()->previous() }}"
                class="block text-center mt-6 text-[10px] uppercase tracking-widest text-white/40 hover:text-white transition">
                Go Back
            </a>
        </div>
    </div>
</body>

</html>