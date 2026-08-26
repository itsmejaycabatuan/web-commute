<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SmartCommute | View Receipt</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    @include('partials.head-scripts')

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700;800&display=swap');

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        /* Theme-aware jagged edge */
        .jagged-edge {
            position: relative;
            height: 20px;
            width: 100%;
            background: radial-gradient(circle, transparent, transparent 10px, #e2e8f0 10px);
            background-size: 20px 40px;
            background-position: 0 -20px;
        }

        .dark .jagged-edge {
            background: radial-gradient(circle, transparent, transparent 10px, rgba(255, 255, 255, 0.03) 10px);
            background-size: 20px 40px;
            background-position: 0 -20px;
        }
    </style>
</head>

<!-- Base background changes based on theme -->

<body
    class="antialiased flex flex-col items-center justify-center p-6 bg-[#f8fafc] dark:bg-[#050505] transition-colors duration-300">

    <div class="w-full max-w-md mb-8 flex items-center justify-between">
        <a href="{{ route('faretransactions') }}"
            class="text-small font-bold uppercase tracking-widest text-gray-500 dark:text-white/40 hover:text-gray-900 dark:hover:text-white transition flex items-center gap-2">
            <i class="fa-solid fa-arrow-left"></i> Back to History
        </a>
    </div>

    <div id="receipt-content" class="w-full max-w-md relative">
        <!-- Glow effect (subtle in light, strong in dark) -->
        <div
            class="absolute -top-12 left-1/2 -translate-x-1/2 w-24 h-24 bg-blue-500/10 rounded-full blur-3xl dark:blur-3xl">
        </div>

        <!-- Using your global .glass-panel class for perfect theme integration -->
        <div class="glass-panel rounded-[2.5rem] overflow-hidden shadow-2xl">

            <!-- Header -->
            <div class="p-8 text-center border-b border-gray-200 dark:border-white/5 bg-gray-50 dark:bg-white/[0.02]">
                <div
                    class="w-14 h-14 bg-gray-100 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-2xl flex items-center justify-center mx-auto mb-5 shadow-inner">
                    <i class="fa-solid fa-file-invoice text-lg text-blue-500 dark:text-blue-400"></i>
                </div>
                <h2 class="text-base font-bold tracking-tight mb-1 text-gray-900 dark:text-white">Transaction Record
                </h2>
                <p class="text-tiny text-gray-500 dark:text-white/60 font-bold uppercase tracking-[0.2em]">Commuter:
                    {{ $user->email }}</p>
                <p class="text-mini text-gray-400 dark:text-white/30 font-bold uppercase tracking-[0.2em]">Transaction
                    No: {{ $transactionId }}</p>
            </div>

            <div class="p-8 space-y-8">
                <!-- Origin / Destination -->
                <div class="flex items-center justify-between relative px-2">
                    <div class="z-10 bg-white dark:bg-[#0c1220] pr-3">
                        <p
                            class="text-mini uppercase text-blue-600 dark:text-blue-400/60 font-black tracking-widest mb-1">
                            Origin</p>
                        <p class="text-small font-bold text-gray-900 dark:text-white">{{ $pickup }}</p>
                    </div>

                    <div
                        class="absolute top-1/2 left-0 w-full h-[1px] bg-gradient-to-r from-transparent via-gray-300 dark:via-white/10 to-transparent -z-0">
                    </div>

                    <div class="z-10 bg-white dark:bg-[#0c1220] pl-3 text-right">
                        <p
                            class="text-mini uppercase text-blue-600 dark:text-blue-400/60 font-black tracking-widest mb-1">
                            Destination</p>
                        <p class="text-small font-bold text-gray-900 dark:text-white">{{ $destination }}</p>
                    </div>
                </div>

                <!-- Details Grid -->
                <div class="grid grid-cols-2 gap-y-6 pt-4 border-t border-gray-200 dark:border-white/5">
                    <div>
                        <p class="text-mini uppercase text-gray-400 dark:text-white/30 font-black tracking-widest mb-1">
                            Date Processed</p>
                        <p class="text-small font-bold text-gray-700 dark:text-white/80">{{ $paidAt }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-mini uppercase text-gray-400 dark:text-white/30 font-black tracking-widest mb-1">
                            Payment Method</p>
                        <p class="text-small font-bold text-gray-700 dark:text-white/80">{{ $paymentMethod }}</p>
                    </div>
                    <div>
                        <p class="text-mini uppercase text-gray-400 dark:text-white/30 font-black tracking-widest mb-1">
                            Total Distance</p>
                        <p class="text-small font-bold text-gray-700 dark:text-white/80">{{ $distance }} KM</p>
                    </div>
                    <div class="text-right">
                        <p class="text-mini uppercase text-gray-400 dark:text-white/30 font-black tracking-widest mb-1">
                            Verified Status</p>
                        <span
                            class="text-mini bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-blue-500/20 px-2 py-0.5 rounded-md font-black uppercase tracking-tighter">Settled</span>
                    </div>
                </div>

                <!-- Amount Paid -->
                <div class="pt-8 border-t border-gray-200 dark:border-white/10 flex items-center justify-between">
                    <div>
                        <p
                            class="text-tiny font-black text-gray-400 dark:text-white/30 uppercase tracking-[0.2em] mb-1">
                            Amount Paid</p>
                        <div class="text-3xl font-black text-gray-900 dark:text-white flex items-baseline gap-1">
                            <span class="text-sm font-medium opacity-30">₱</span>
                            {{ number_format($price, 2) }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="jagged-edge opacity-100 dark:opacity-10"></div>
        </div>
    </div>

    <div class="mt-8 w-full max-w-md">
        <button onclick="downloadReceipt()"
            class="w-full bg-gray-100 dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10 border border-gray-200 dark:border-white/10 text-gray-700 dark:text-white font-bold py-4 px-6 rounded-2xl text-small uppercase tracking-[0.2em] transition-all flex items-center justify-center gap-3">
            <i class="fa-solid fa-download text-blue-500 dark:text-blue-400"></i>
            Download Receipt
        </button>
    </div>

    <script>
        function downloadReceipt() {
            const receipt = document.getElementById('receipt-content');
            html2canvas(receipt, {
                backgroundColor: null, // This allows it to capture the white/dark background perfectly
                scale: 3,
                useCORS: true
            }).then(canvas => {
                const link = document.createElement('a');
                link.download = 'Receipt-{{ $transactionId }}.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
            });
        }
    </script>

</body>

</html>
