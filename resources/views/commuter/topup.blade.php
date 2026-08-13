<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartCommute | Top-up Wallet</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: { extend: { fontFamily: { sans: ['Inter', 'sans-serif'] } } }
        }
    </script>
    <style>
        body, html { margin: 0; padding: 0; height: 100%; width: 100%; font-family: 'Inter', sans-serif; overflow-x: hidden; background: #050505; }
        .glass-panel { background: #111111 !important; border: 1px solid #1e1e1e; box-shadow: 0 4px 24px rgba(0,0,0,0.6); }
        .glass-card { background: #161616; border: 1px solid #222222; box-shadow: 0 4px 24px rgba(0,0,0,0.5); }
        .header-btn { transition: all 0.3s ease; }
        .header-btn:hover { background: #1a1a1a !important; border-color: #333 !important; }

        input::placeholder { color: #333; }
        input[type="number"]::-webkit-inner-spin-button,
        input[type="number"]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
        input[type="number"] { -moz-appearance: textfield; }
        input:focus { border-color: rgba(59, 130, 246, 0.4) !important; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.08); }

        .method-card {
            transition: all 0.25s ease;
            border: 2px solid #1e1e1e;
            cursor: pointer;
        }
        .method-card:hover {
            background: #1a1a1a;
            border-color: #333;
        }
        .method-card.selected {
            background: rgba(59, 130, 246, 0.06);
            border-color: rgba(59, 130, 246, 0.4);
        }
        .method-card.selected .method-dot {
            border-color: #3b82f6;
            background: #3b82f6;
            box-shadow: 0 0 8px rgba(59, 130, 246, 0.3);
        }
        .method-card.selected .method-dot-inner {
            opacity: 1;
            transform: scale(1);
        }

        .method-dot {
            width: 20px; height: 20px;
            border-radius: 50%;
            border: 2px solid #333;
            background: #111;
            display: flex; align-items: center; justify-content: center;
            transition: all 0.25s ease;
            flex-shrink: 0;
        }
        .method-dot-inner {
            width: 8px; height: 8px;
            border-radius: 50%;
            background: white;
            opacity: 0;
            transform: scale(0);
            transition: all 0.25s ease;
        }

        .preset-btn {
            transition: all 0.2s ease;
        }
        .preset-btn:hover {
            background: #1a1a1a !important;
            border-color: #333 !important;
            color: white !important;
        }
        .preset-btn.active {
            background: rgba(59, 130, 246, 0.1) !important;
            border-color: rgba(59, 130, 246, 0.4) !important;
            color: #3b82f6 !important;
        }

        ::-webkit-scrollbar { width: 3px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #333; border-radius: 10px; }
    </style>
</head>

<body class="antialiased text-white">

    @include('components.flash')

    <!-- ══════════ HEADER ══════════ -->
    <header class="fixed top-4 left-4 right-4 sm:top-5 sm:left-5 sm:right-5 z-50 flex items-center justify-between gap-3 pointer-events-none">
        <div class="glass-panel p-3 sm:p-3.5 rounded-2xl pointer-events-auto flex items-center gap-3 min-w-0">
            <a href="{{ route('map') }}" class="header-btn w-9 h-9 rounded-xl flex items-center justify-center shrink-0 border border-[#1e1e1e] bg-[#111] hover:bg-[#1a1a1a] transition cursor-pointer">
                <i class="fa-solid fa-arrow-left text-[10px] text-[#666]"></i>
            </a>
            <div class="w-px h-6 bg-[#222] mx-0.5 hidden sm:block"></div>
            <div class="w-8 h-8 sm:w-9 sm:h-9 bg-blue-600 rounded-lg sm:rounded-xl flex items-center justify-center shrink-0">
                <i class="fa-solid fa-bus text-white text-xs sm:text-sm"></i>
            </div>
            <span class="text-[13px] sm:text-sm font-bold tracking-tight text-white whitespace-nowrap">Smart<span class="text-blue-400">Commute</span></span>
            <div class="w-px h-6 bg-[#222] mx-0.5 hidden sm:block"></div>
            <span class="text-[9px] sm:text-[10px] font-bold uppercase tracking-[0.15em] text-[#555] hidden sm:inline">Top Up</span>
        </div>
        <div class="flex items-center gap-2 pointer-events-auto z-50 shrink-0">
            <a href="{{ route('payment.topup.history') }}" class="header-btn glass-panel px-3 sm:px-4 py-2 rounded-xl text-white text-[9px] sm:text-[10px] font-bold cursor-pointer uppercase tracking-wider flex items-center gap-2">
                <i class="fa-solid fa-clock-rotate-left text-[9px] text-[#555]"></i>
                <span class="hidden sm:inline">History</span>
            </a>
            <div class="hidden sm:flex items-center gap-2.5 glass-panel px-4 py-2 rounded-xl">
                <p class="text-[7px] font-bold uppercase tracking-[0.15em] text-[#444]">Balance</p>
                <p class="text-[11px] font-bold text-emerald-400">₱{{ number_format($balance ?? 0, 2) }}</p>
            </div>
        </div>
    </header>

    <!-- ══════════ MAIN CONTENT ══════════ -->
    <div class="pt-20 sm:pt-24 pb-8 sm:pb-10 max-w-6xl mx-auto px-4 sm:px-6 lg:px-5">

        <!-- ── Mobile: Balance Card ── -->
        <div class="sm:hidden mb-6">
            <div class="glass-card p-4 rounded-[1.25rem] flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 bg-emerald-500/10 rounded-lg flex items-center justify-center border border-emerald-500/15">
                        <i class="fa-solid fa-wallet text-emerald-400 text-[10px]"></i>
                    </div>
                    <span class="text-[9px] font-bold uppercase tracking-[0.15em] text-[#444]">Current Balance</span>
                </div>
                <span class="text-lg font-black text-white">₱{{ number_format($balance ?? 0, 2) }}</span>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            <!-- ══════════ LEFT SIDEBAR (desktop only) ══════════ -->
            <div class="hidden lg:flex lg:col-span-4 flex-col gap-6">

                <!-- Balance Card -->
                <div class="glass-card p-6 rounded-[1.5rem]">
                    <div class="flex items-center gap-2.5 mb-4">
                        <div class="w-8 h-8 bg-emerald-500/10 rounded-lg flex items-center justify-center border border-emerald-500/15">
                            <i class="fa-solid fa-wallet text-emerald-400 text-[10px]"></i>
                        </div>
                        <span class="text-[9px] font-bold uppercase tracking-[0.15em] text-[#555]">Current Balance</span>
                    </div>
                    <h3 class="text-3xl font-black tracking-tight">₱{{ number_format($balance ?? 0, 2) }}</h3>
                </div>

                <!-- Info -->
                <div class="glass-card p-6 rounded-[1.5rem]">
                    <div class="flex items-center gap-2.5 mb-4">
                        <div class="w-8 h-8 rounded-lg bg-[#111] border border-[#1e1e1e] flex items-center justify-center">
                            <i class="fa-solid fa-circle-info text-[10px] text-[#555]"></i>
                        </div>
                        <span class="text-[9px] font-bold uppercase tracking-[0.15em] text-[#555]">Good to Know</span>
                    </div>
                    <div class="space-y-3">
                        <div class="flex items-start gap-2.5">
                            <div class="w-5 h-5 rounded-md bg-blue-500/10 border border-blue-500/15 flex items-center justify-center shrink-0 mt-0.5">
                                <i class="fa-solid fa-bolt text-[7px] text-blue-400"></i>
                            </div>
                            <p class="text-[10px] text-[#666] leading-relaxed">Funds are credited instantly after payment confirmation.</p>
                        </div>
                        <div class="flex items-start gap-2.5">
                            <div class="w-5 h-5 rounded-md bg-blue-500/10 border border-blue-500/15 flex items-center justify-center shrink-0 mt-0.5">
                                <i class="fa-solid fa-coins text-[7px] text-blue-400"></i>
                            </div>
                            <p class="text-[10px] text-[#666] leading-relaxed">Minimum top-up amount is ₱10.00.</p>
                        </div>
                        <div class="flex items-start gap-2.5">
                            <div class="w-5 h-5 rounded-md bg-blue-500/10 border border-blue-500/15 flex items-center justify-center shrink-0 mt-0.5">
                                <i class="fa-solid fa-shield text-[7px] text-blue-400"></i>
                            </div>
                            <p class="text-[10px] text-[#666] leading-relaxed">All transactions are encrypted and secured.</p>
                        </div>
                    </div>
                </div>

            </div>

            <!-- ══════════ RIGHT CONTENT ══════════ -->
            <div class="lg:col-span-8 flex flex-col gap-6">

                <form id="topup-form" action="{{ route('payment.topup.process') }}" method="POST" class="flex flex-col gap-6">
                    @csrf

                    <!-- ── Amount Section ── -->
                    <div class="glass-card p-5 sm:p-6 rounded-[1.5rem]">
                        <div class="flex items-center gap-2.5 mb-5">
                            <div class="w-8 h-8 rounded-lg bg-[#111] border border-[#1e1e1e] flex items-center justify-center">
                                <i class="fa-solid fa-coins text-[10px] text-[#555]"></i>
                            </div>
                            <span class="text-[9px] font-bold uppercase tracking-[0.15em] text-[#555]">Enter Amount</span>
                        </div>

                        <div class="space-y-1.5 mb-4">
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-xl font-black text-blue-400">₱</span>
                                <input type="number" name="amount" id="amount-input" placeholder="0.00" min="10" step="0.01"
                                    class="w-full bg-[#111] border border-[#1e1e1e] rounded-xl pl-11 pr-4 py-4 text-2xl font-black text-white outline-none transition tracking-tight">
                            </div>
                            @error('amount')
                                <p class="text-[9px] text-red-400 ml-1 flex items-center gap-1.5">
                                    <i class="fa-solid fa-circle-exclamation text-[8px]"></i>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-4 gap-2">
                            @foreach([50, 100, 200, 500] as $preset)
                                <button type="button" onclick="setAmount({{ $preset }})"
                                    class="preset-btn py-3 rounded-xl bg-[#111] border border-[#1e1e1e] text-[11px] font-bold text-[#555]">
                                    ₱{{ $preset }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- ── Payment Method Section ── -->
                    <div class="glass-card p-5 sm:p-6 rounded-[1.5rem]">
                        <div class="flex items-center gap-2.5 mb-5">
                            <div class="w-8 h-8 rounded-lg bg-[#111] border border-[#1e1e1e] flex items-center justify-center">
                                <i class="fa-solid fa-credit-card text-[10px] text-[#555]"></i>
                            </div>
                            <span class="text-[9px] font-bold uppercase tracking-[0.15em] text-[#555]">Payment Method</span>
                        </div>

                        <div class="space-y-2.5" id="method-list">

                            <!-- GCash -->
                            <label class="block">
                                <input type="radio" name="payment-method" value="gcash" class="hidden" checked>
                                <div class="method-card selected bg-[#111] p-4 rounded-xl flex items-center justify-between" onclick="selectMethod(this)">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-lg bg-blue-500/10 border border-blue-500/15 flex items-center justify-center shrink-0">
                                            <i class="fa-solid fa-mobile-screen-button text-blue-400 text-sm"></i>
                                        </div>
                                        <div>
                                            <p class="text-[12px] font-bold text-white">GCash</p>
                                            <p class="text-[8px] text-[#444] font-bold uppercase tracking-wider">Mobile Wallet</p>
                                        </div>
                                    </div>
                                    <div class="method-dot">
                                        <div class="method-dot-inner"></div>
                                    </div>
                                </div>
                            </label>

                            <!-- Maya -->
                            <label class="block">
                                <input type="radio" name="payment-method" value="maya" class="hidden">
                                <div class="method-card bg-[#111] p-4 rounded-xl flex items-center justify-between" onclick="selectMethod(this)">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-lg bg-emerald-500/10 border border-emerald-500/15 flex items-center justify-center shrink-0">
                                            <i class="fa-solid fa-bolt text-emerald-400 text-sm"></i>
                                        </div>
                                        <div>
                                            <p class="text-[12px] font-bold text-white">Maya</p>
                                            <p class="text-[8px] text-[#444] font-bold uppercase tracking-wider">Mobile Wallet</p>
                                        </div>
                                    </div>
                                    <div class="method-dot">
                                        <div class="method-dot-inner"></div>
                                    </div>
                                </div>
                            </label>

                            <!-- Admin Tab -->
                            <label class="block">
                                <input type="radio" name="payment-method" value="admin" class="hidden">
                                <div class="method-card bg-[#111] p-4 rounded-xl flex items-center justify-between" onclick="selectMethod(this)">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-lg bg-amber-500/10 border border-amber-500/15 flex items-center justify-center shrink-0">
                                            <i class="fa-solid fa-user-tie text-amber-400 text-sm"></i>
                                        </div>
                                        <div>
                                            <p class="text-[12px] font-bold text-white">Admin Settlement</p>
                                            <p class="text-[8px] text-[#444] font-bold uppercase tracking-wider">Manual Processing</p>
                                        </div>
                                    </div>
                                    <div class="method-dot">
                                        <div class="method-dot-inner"></div>
                                    </div>
                                </div>
                            </label>

                        </div>

                        @error('payment-method')
                            <p class="text-[9px] text-red-400 mt-3 ml-1 flex items-center gap-1.5">
                                <i class="fa-solid fa-circle-exclamation text-[8px]"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- ── Mobile: inline info ── -->
                    <div class="lg:hidden flex items-start gap-2.5 p-3.5 rounded-xl bg-blue-500/5 border border-blue-500/10">
                        <i class="fa-solid fa-shield-halved text-blue-400 text-[10px] mt-0.5 shrink-0"></i>
                        <p class="text-[9px] text-[#555] leading-relaxed">Funds are credited instantly. Minimum top-up is ₱10. All transactions are encrypted.</p>
                    </div>

                    <!-- ── Actions ── -->
                    <div class="flex items-center justify-between pt-2">
                        <a href="{{ route('map') }}"
                            class="text-[9px] font-bold uppercase tracking-[0.15em] text-[#444] hover:text-white transition flex items-center gap-2">
                            <i class="fa-solid fa-xmark text-[10px]"></i>
                            Cancel
                        </a>
                        <button type="submit"
                            class="bg-blue-600 text-white px-8 py-3.5 rounded-xl text-[10px] font-bold uppercase tracking-wider hover:bg-blue-500 transition shadow-lg shadow-blue-600/20 active:scale-[0.98] flex items-center gap-2.5">
                            <i class="fa-solid fa-lock text-[9px]"></i>
                            Confirm Payment
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>

    <!-- ══════════ SCRIPTS ══════════ -->
    <script>
        function setAmount(value) {
            const input = document.getElementById('amount-input');
            input.value = value;
            input.focus();

            // Update active preset
            document.querySelectorAll('.preset-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
        }

        // Clear preset active state when manually typing
        document.getElementById('amount-input')?.addEventListener('input', function () {
            const val = parseFloat(this.value);
            document.querySelectorAll('.preset-btn').forEach(btn => {
                const btnVal = parseFloat(btn.textContent.replace('₱', '').replace(',', ''));
                if (btnVal === val) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            });
        });

        function selectMethod(card) {
            // Deselect all
            document.querySelectorAll('.method-card').forEach(c => {
                c.classList.remove('selected');
            });
            // Select clicked
            card.classList.add('selected');
            // Check the hidden radio
            const radio = card.parentElement.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;
        }
    </script>

</body>
</html>
