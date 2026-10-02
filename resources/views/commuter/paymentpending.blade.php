<!DOCTYPE html>
<html lang="en" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- JS-free auto-refresh fallback. The script below reloads sooner and
         more visibly; this one still works if JS is blocked. --}}
    <meta http-equiv="refresh" content="10">

    <title>SmartCommute | Confirming Payment</title>

    <script src="https://cdn.tailwindcss.com"></script>

    {{-- Font Awesome: required by the <i class="fa-…"> icons below. --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');

        body {
            font-family: 'Inter', sans-serif;
        }

        .spin {
            animation: spin 1.1s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        @keyframes pulse-soft {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: .45;
            }
        }

        .pulse-soft {
            animation: pulse-soft 1.8s ease-in-out infinite;
        }
    </style>
</head>

<body class="h-full bg-slate-100 dark:bg-[#0d0d0d] text-slate-900 dark:text-white">

    <div class="min-h-full flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-md">

            <!-- Spinner -->
            <div class="text-center mb-8">
                <div
                    class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-blue-500/10 border border-blue-500/20 mb-4">
                    <i class="fa-solid fa-circle-notch spin text-blue-500 text-2xl"></i>
                </div>
                <h1 class="text-xl font-extrabold tracking-tight">Confirming your payment</h1>
                <p class="text-[12px] text-slate-500 dark:text-[#555] mt-2 pulse-soft">
                    Waiting for {{ $payment->payment_method }} to confirm the transaction.
                </p>
            </div>

            <!-- Summary -->
            <div
                class="bg-white dark:bg-[#161616] border border-slate-200 dark:border-[#1e1e1e] rounded-2xl p-5 space-y-3 shadow-sm">
                <div class="flex items-center justify-between text-[12px]">
                    <span
                        class="text-slate-400 dark:text-[#555] uppercase tracking-wider font-bold text-[10px]">Transaction</span>
                    <span class="font-bold">{{ $payment->transaction_id }}</span>
                </div>
                <div class="flex items-center justify-between gap-4 text-[12px]">
                    <span
                        class="text-slate-400 dark:text-[#555] uppercase tracking-wider font-bold text-[10px] shrink-0">Route</span>
                    <span class="font-bold text-right">{{ $payment->starting_point }} → {{ $payment->destination }}</span>
                </div>
                <div
                    class="flex items-center justify-between text-[12px] border-t border-slate-200 dark:border-[#1e1e1e] pt-3">
                    <span
                        class="text-slate-400 dark:text-[#555] uppercase tracking-wider font-bold text-[10px]">Amount</span>
                    <span class="text-lg font-extrabold">₱{{ number_format((float) $payment->price, 2) }}</span>
                </div>
                <div class="flex items-center justify-between text-[12px]">
                    <span
                        class="text-slate-400 dark:text-[#555] uppercase tracking-wider font-bold text-[10px]">Status</span>
                    <span
                        class="text-[10px] font-bold uppercase tracking-wider px-2 py-1 rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20">
                        Pending
                    </span>
                </div>
            </div>

            <p class="text-[11px] text-slate-400 dark:text-[#444] text-center mt-6 leading-relaxed">
                This page checks automatically every few seconds.<br>
                Your receipt appears as soon as the payment settles.
            </p>

            <div class="mt-6 flex flex-col sm:flex-row gap-2 justify-center">
                <a href="{{ route('payment.returned', ['ref' => $payment->transaction_id]) }}"
                    class="text-[10px] font-bold uppercase tracking-wider px-4 py-2.5 rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 hover:bg-blue-500/20 transition flex items-center gap-2">
                    <i class="fa-solid fa-rotate-right text-[9px]"></i> Check again
                </a>
                <a href="{{ route('payment.history') }}"
                    class="text-[10px] font-bold uppercase tracking-wider px-4 py-2.5 rounded-xl text-slate-400 dark:text-[#555] hover:text-slate-900 dark:hover:text-white transition">
                    Go to Payment History
                </a>
            </div>

        </div>
    </div>

    <script>
        // Poll the return URL: the webhook normally settles the payment, and
        // this page re-reads it either way. The server throttles the actual
        // gateway lookup, so a fast reload is harmless.
        setTimeout(function () {
            window.location.replace(window.location.href);
        }, 4000);
    </script>

</body>

</html>