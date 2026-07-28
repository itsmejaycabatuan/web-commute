<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartCommute | Receipt</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script>
        tailwind.config = { theme: { extend: { fontFamily: { sans: ['Inter', 'sans-serif'] } } } }
    </script>
    <style>
        body { background: #050505; font-family: 'Inter', sans-serif; color: #fff; min-height: 100vh; }
        @keyframes card-enter { from { opacity: 0; transform: translateY(20px) scale(0.98); } to { opacity: 1; transform: translateY(0) scale(1); } }
        .card-animate { animation: card-enter 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.1s both; }
        @keyframes check-pop { 0% { transform: scale(0); } 60% { transform: scale(1.15); } 100% { transform: scale(1); } }
        .check-animate { animation: check-pop 0.5s cubic-bezier(0.16, 1, 0.3, 1) 0.3s both; }
        .jagged-edge { position: relative; height: 16px; width: 100%; background: radial-gradient(circle, transparent, transparent 8px, #1a1a1a 8px); background-size: 16px 32px; background-position: 0 -16px; }
    </style>
</head>

<body class="antialiased flex flex-col items-center justify-center p-4 sm:p-6">

    <div id="receipt-content" class="w-full max-w-[420px] relative card-animate">
        <div class="bg-[#111] border border-[#1e1e1e] rounded-2xl overflow-hidden shadow-2xl shadow-black/40">
            <!-- Success Header -->
            <div class="p-6 sm:p-8 text-center border-b border-[#1a1a1a] bg-[#0a0a0a]">
                <div class="check-animate w-14 h-14 sm:w-16 sm:h-16 bg-emerald-500 rounded-2xl flex items-center justify-center mx-auto mb-5 shadow-lg shadow-emerald-500/25">
                    <i class="fa-solid fa-check text-xl sm:text-2xl text-white"></i>
                </div>
                <h2 class="text-lg sm:text-xl font-extrabold tracking-tight mb-1">Payment Successful</h2>
                <p class="text-[9px] sm:text-[10px] text-[#555] font-bold uppercase tracking-[0.15em]">
                    Transaction ID: {{ $transactionId }}
                </p>
            </div>

            <!-- Trip Details -->
            <div class="p-6 sm:p-8 space-y-6">
                <!-- Route -->
                <div class="flex items-start gap-3">
                    <div class="flex flex-col items-center gap-0 pt-1.5">
                        <div class="w-2.5 h-2.5 rounded-full bg-blue-500 border-2 border-blue-400/30"></div>
                        <div class="w-px h-8 bg-[#1a1a1a]"></div>
                        <div class="w-2.5 h-2.5 rounded-full bg-red-500 border-2 border-red-400/30"></div>
                    </div>
                    <div class="flex-1 space-y-3">
                        <div>
                            <p class="text-[8px] uppercase text-[#444] font-bold tracking-[0.15em] mb-0.5">Pick-up</p>
                            <p class="text-xs font-semibold text-[#ccc] leading-tight">{{ $pickup }}</p>
                        </div>
                        <div>
                            <p class="text-[8px] uppercase text-[#444] font-bold tracking-[0.15em] mb-0.5">Destination</p>
                            <p class="text-xs font-semibold text-[#ccc] leading-tight">{{ $destination }}</p>
                        </div>
                    </div>
                </div>

                <!-- Info Grid -->
                <div class="grid grid-cols-2 gap-4 sm:gap-6 pt-5 border-t border-[#1a1a1a]">
                    <div>
                        <p class="text-[8px] uppercase text-[#444] font-bold tracking-[0.15em] mb-1">Date</p>
                        <p class="text-[11px] sm:text-xs font-bold text-[#aaa]">{{ now()->format('M d, Y') }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[8px] uppercase text-[#444] font-bold tracking-[0.15em] mb-1">Payment</p>
                        <p class="text-[11px] sm:text-xs font-bold text-[#aaa]">{{ $paymentMethod }}</p>
                    </div>
                    <div>
                        <p class="text-[8px] uppercase text-[#444] font-bold tracking-[0.15em] mb-1">Distance</p>
                        <p class="text-[11px] sm:text-xs font-bold text-[#aaa]">{{ $distance }} km</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[8px] uppercase text-[#444] font-bold tracking-[0.15em] mb-1">Status</p>
                        <span class="inline-block text-[8px] sm:text-[9px] bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 px-2.5 py-1 rounded-lg font-bold uppercase tracking-wider">Paid</span>
                    </div>
                </div>

                <!-- Total -->
                <div class="pt-6 border-t border-[#1a1a1a] flex items-end justify-between">
                    <div>
                        <p class="text-[9px] font-bold text-[#444] uppercase tracking-[0.2em] mb-1">Total Paid</p>
                        <div class="text-2xl sm:text-3xl font-extrabold text-white flex items-baseline gap-1 tracking-tight">
                            <span class="text-sm font-medium text-[#555]">₱</span>
                            {{ number_format($price, 2) }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="jagged-edge opacity-20"></div>
        </div>
    </div>

    <!-- Actions -->
    <div class="mt-6 sm:mt-8 flex flex-col sm:flex-row items-center gap-3 w-full max-w-[420px] card-animate" style="animation-delay: 0.2s;">
        <button onclick="downloadReceipt()"
            class="w-full bg-[#111] hover:bg-[#1a1a1a] border border-[#222] text-white font-bold py-3.5 px-5 rounded-xl text-[10px] uppercase tracking-[0.15em] transition-all flex items-center justify-center gap-2.5 active:scale-[0.98]">
            <i class="fa-solid fa-download text-blue-400 text-xs"></i>
            Save as Image
        </button>
        <a href="{{ route('map') }}"
            class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-3.5 px-5 rounded-xl text-[10px] uppercase tracking-[0.15em] text-center shadow-lg shadow-blue-600/15 transition-all active:scale-[0.98]">
            Back to Map
        </a>
    </div>

    <script>
        function downloadReceipt() {
            html2canvas(document.getElementById('receipt-content'), {
                backgroundColor: null, scale: 3, useCORS: true
            }).then(canvas => {
                const link = document.createElement('a');
                link.download = 'SmartCommute-Receipt.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
            });
        }
    </script>
</body>

</html>
