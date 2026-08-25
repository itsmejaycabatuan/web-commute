<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartCommute | Payment</title>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">

    @include('partials.commuter-head-scripts')

    <style>
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
        }

        body {
            background: #f8fafc;
        }

        .dark body {
            background: #050505;
        }

        .dark body {
            color: #fff;
        }

        @keyframes card-enter {
            from {
                opacity: 0;
                transform: translateY(30px) scale(0.97);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .card-animate {
            animation: card-enter 0.7s cubic-bezier(0.16, 1, 0.3, 1) 0.1s both;
        }

        @keyframes fade-in {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-1 {
            animation: fade-in 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.25s both;
        }

        .fade-2 {
            animation: fade-in 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.35s both;
        }

        .fade-3 {
            animation: fade-in 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.45s both;
        }

        .fade-4 {
            animation: fade-in 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.55s both;
        }

        .method-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .method-box:hover {
            border-color: #cbd5e1;
            background: #ffffff;
        }

        .dark .method-box {
            background: #0e0e0e;
            border-color: #1e1e1e;
        }

        .dark .method-box:hover {
            border-color: #333;
            background: #141414;
        }

        input[type="radio"]:checked+.method-box {
            border-color: #2563eb;
            background: rgba(37, 99, 235, 0.08);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        input[type="radio"]:checked+.method-box .method-dot {
            background: #2563eb;
            box-shadow: 0 0 8px rgba(37, 99, 235, 0.5);
        }

        .btn-primary {
            background: #2563eb;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
            box-shadow: 0 12px 40px rgba(37, 99, 235, 0.25);
        }

        .btn-primary:active {
            transform: scale(0.98) translateY(0);
        }

        .line-glow {
            background: #e2e8f0;
            height: 1px;
        }

        .dark .line-glow {
            background: #1a1a1a;
        }

        @keyframes shield-pulse {

            0%,
            100% {
                opacity: 0.15;
            }

            50% {
                opacity: 0.3;
            }
        }

        .shield-pulse {
            animation: shield-pulse 3s ease-in-out infinite;
        }

        .dark .shield-pulse {
            opacity: 0.08;
        }
    </style>
</head>

<body class="antialiased flex items-center justify-center p-4 sm:p-6">

    <!-- Decorative orbs -->
    <div class="fixed top-1/4 left-1/4 w-80 h-80 bg-blue-500/5 rounded-full blur-[120px] pointer-events-none"></div>
    <div class="fixed bottom-1/3 right-1/4 w-60 h-60 bg-purple-500/5 rounded-full blur-[100px] pointer-events-none">
    </div>

    <div class="w-full max-w-[420px] relative z-10">

        <!-- Breadcrumb -->
        <div
            class="fade-1 flex items-center justify-center gap-2 text-[10px] font-bold uppercase tracking-[0.15em] mb-8">
            <a href="{{ route('map') }}"
                class="text-gray-400 dark:text-[#555] hover:text-blue-500 dark:hover:text-blue-400 transition flex items-center gap-1.5">
                <i class="fa-solid fa-map-location-dot text-[8px]"></i> Map
            </a>
            <i class="fa-solid fa-chevron-right text-[7px] text-gray-300 dark:text-[#333]"></i>
            <span class="text-blue-500 dark:text-blue-400 flex items-center gap-1.5">
                <i class="fa-solid fa-lock text-[8px]"></i> Checkout
            </span>
        </div>

        <!-- Main Card -->
        <div
            class="card-animate bg-white dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] rounded-[2rem] p-6 sm:p-8 relative overflow-hidden shadow-lg dark:shadow-black/40">

            <!-- Header -->
            <div class="fade-1 text-center mb-8">
                <div class="flex items-center justify-center gap-2 mb-3">
                    <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
                        <i class="fa-solid fa-bus text-white text-[10px]"></i>
                    </div>
                    <span class="text-sm font-bold tracking-tight text-gray-900 dark:text-white">Smart<span
                            class="text-blue-500 dark:text-blue-400">Commute</span></span>
                </div>
                <h2 class="text-xl font-extrabold tracking-tight text-gray-900 dark:text-white">Payment Details</h2>
                <p
                    class="text-[9px] text-gray-400 dark:text-[#555] font-bold mt-1.5 uppercase tracking-[0.2em] flex items-center justify-center gap-1.5">
                    <i class="fa-solid fa-shield-halved text-[8px] text-blue-500/50 dark:text-blue-500/40"></i>
                    Secure Checkout
                </p>
            </div>

            <!-- Trip Summary -->
            <div
                class="fade-2 bg-gray-50 dark:bg-[#0a0a0a] rounded-2xl p-5 border border-gray-200 dark:border-[#1a1a1a] mb-6">
                <div class="flex items-start gap-4 mb-5">
                    <div class="flex flex-col items-center gap-0 pt-1">
                        <div class="w-3 h-3 rounded-full bg-blue-500 dark:bg-blue-500 border-2 border-blue-400/30">
                        </div>
                        <div class="w-px h-8 bg-gray-200 dark:bg-[#222]"></div>
                        <div class="w-3 h-3 rounded-full bg-red-500 border-2 border-red-400/30"></div>
                    </div>
                    <div class="flex-1 space-y-4">
                        <div>
                            <p
                                class="text-[8px] uppercase text-gray-400 dark:text-[#444] font-bold tracking-[0.15em] mb-0.5">
                                Pick-up</p>
                            <p class="text-xs font-semibold text-gray-700 dark:text-[#ccc] leading-tight">
                                {{ $pickup }}</p>
                        </div>
                        <div>
                            <p
                                class="text-[8px] uppercase text-gray-400 dark:text-[#444] font-bold tracking-[0.15em] mb-0.5">
                                Destination</p>
                            <p class="text-xs font-semibold text-gray-700 dark:text-[#ccc] leading-tight">
                                {{ $destination }}</p>
                        </div>
                    </div>
                </div>

                <div class="line-glow w-full mb-4"></div>

                <div class="space-y-2.5">
                    <div class="flex justify-between items-center">
                        <span
                            class="text-[10px] font-semibold text-gray-400 dark:text-[#555] uppercase tracking-wider">Distance</span>
                        <span class="text-xs font-bold text-gray-500 dark:text-[#aaa]">{{ $distance }} km</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span
                            class="text-[10px] font-semibold text-gray-400 dark:text-[#555] uppercase tracking-wider">Fare</span>
                        <span
                            class="text-xs font-bold text-gray-500 dark:text-[#aaa]">₱{{ number_format($price, 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Payment Form -->
            <form action="{{ route('payment.process') }}" method="POST" id="payment-form" class="space-y-6">
                @csrf
                <input type="hidden" name="pickup" value="{{ $pickup }}">
                <input type="hidden" name="destination" value="{{ $destination }}">
                <input type="hidden" name="amount" value="{{ $price }}">
                <input type="hidden" name="transaction-id" value="#SC-{{ strtoupper(Str::random(8)) }}">
                <input type="hidden" name="distance" value="{{ $distance }}">

                <!-- Payment Method -->
                <div class="fade-3">
                    <h3
                        class="text-[9px] font-bold mb-3 uppercase tracking-[0.2em] text-gray-400 dark:text-[#555] px-0.5 flex items-center gap-2">
                        <i class="fa-solid fa-wallet text-[8px] text-blue-500/40"></i>
                        Payment Method
                    </h3>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="relative cursor-pointer group">
                            <input type="radio" name="payment-method" value="GCash" class="sr-only" checked>
                            <div class="method-box p-4 rounded-xl text-center">
                                <div class="flex items-center justify-center mb-2.5">
                                    <div
                                        class="w-10 h-10 rounded-xl bg-[#0a5c36]/20 dark:bg-[#0a5c36]/15 border border-[#0a5c36]/30 flex items-center justify-center">
                                        <span class="text-sm font-black text-[#00b386]">G</span>
                                    </div>
                                </div>
                                <p
                                    class="text-[10px] text-gray-600 dark:text-[#ccc] font-bold uppercase tracking-widest">
                                    GCash</p>
                                <div class="flex justify-center mt-2.5">
                                    <div
                                        class="method-dot w-3 h-3 rounded-full bg-gray-300 dark:bg-[#333] transition-all duration-300">
                                    </div>
                                </div>
                            </div>
                        </label>

                        <label class="relative cursor-pointer group">
                            <input type="radio" name="payment-method" value="Wallet" class="sr-only">
                            <div class="method-box p-4 rounded-xl text-center">
                                <div class="flex items-center justify-center mb-2.5">
                                    <div
                                        class="w-10 h-10 rounded-xl bg-blue-500/10 dark:bg-blue-500/15 border border-blue-500/20 flex items-center justify-center">
                                        <i class="fa-solid fa-wallet text-blue-500 dark:text-blue-400 text-sm"></i>
                                    </div>
                                </div>
                                <p
                                    class="text-[10px] text-gray-600 dark:text-[#ccc] font-bold uppercase tracking-widest">
                                    Wallet</p>
                                <p class="text-[9px] text-gray-400 dark:text-[#555] font-semibold mt-0.5">
                                    ₱{{ $balance }}</p>
                                <div class="flex justify-center mt-2">
                                    <div
                                        class="method-dot w-3 h-3 rounded-full bg-gray-300 dark:bg-[#333] transition-all duration-300">
                                    </div>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Total -->
                <div
                    class="fade-3 flex items-end justify-between px-1 pt-5 border-t border-gray-200 dark:border-[#1a1a1a]">
                    <div>
                        <p class="text-[9px] font-bold text-gray-400 dark:text-[#444] uppercase tracking-[0.2em] mb-1">
                            Total</p>
                        <div
                            class="text-3xl font-extrabold text-gray-900 dark:text-white flex items-baseline gap-1 tracking-tight">
                            <span class="text-sm font-medium text-gray-400 dark:text-[#555]">₱</span>
                            {{ number_format($price, 2) }}
                        </div>
                    </div>
                    <div class="shield-pulse">
                        <i class="fa-solid fa-shield-halved text-blue-500/20 dark:text-blue-500/10 text-3xl"></i>
                    </div>
                </div>

                <!-- Submit -->
                <div class="fade-4">
                    <button type="submit"
                        class="btn-primary w-full text-white font-bold py-4 px-6 rounded-xl text-[10px] uppercase tracking-[0.2em] flex items-center justify-center gap-2.5">
                        <i class="fa-solid fa-lock text-[9px] opacity-60"></i>
                        Confirm Payment
                    </button>
                </div>
            </form>

            <!-- Cancel -->
            <div class="fade-4 text-center mt-6">
                <a href="{{ url()->previous() }}"
                    class="inline-flex items-center gap-2 text-[10px] font-semibold text-gray-400 dark:text-[#444] hover:text-gray-900 dark:hover:text-white transition">
                    <i class="fa-solid fa-arrow-left text-[8px]"></i>
                    Cancel Transaction
                </a>
            </div>
        </div>

        <!-- Footer -->
        <div class="fade-4 flex items-center justify-center gap-2 mt-6">
            <i class="fa-solid fa-lock text-[7px] text-gray-300 dark:text-[#333]"></i>
            <p class="text-[8px] font-bold text-gray-300 dark:text-[#333] uppercase tracking-[0.15em]">
                Encrypted by SmartCommute SecurePay
            </p>
        </div>
    </div>

</body>

</html>
