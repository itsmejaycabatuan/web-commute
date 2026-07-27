<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartCommute | Receipt</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
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
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .receipt-border {
            background-image: radial-gradient(circle at 0 0, transparent 20px, rgba(255, 255, 255, 0.03) 21px);
        }

        /* Scissor cut effect for the bottom of the receipt */
        .jagged-edge {
            position: relative;
            height: 20px;
            width: 100%;
            background: radial-gradient(circle, transparent, transparent 10px, rgba(255, 255, 255, 0.03) 10px);
            background-size: 20px 40px;
            background-position: 0 -20px;
        }
    </style>
</head>

<body class="antialiased flex flex-col items-center justify-center p-6">

    <div id="receipt-content" class="w-full max-w-md relative">
        <div class="absolute -top-12 left-1/2 -translate-x-1/2 w-24 h-24 bg-green-500/20 rounded-full blur-2xl"></div>

        <div class="glass rounded-[2.5rem] overflow-hidden border border-white/10 shadow-2xl">
            <div class="p-8 text-center border-b border-white/5 bg-white/5">
                <div
                    class="w-16 h-16 bg-green-500 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg shadow-green-500/30">
                    <i class="fa-solid fa-check text-2xl text-white"></i>
                </div>
                <h2 class="text-xl font-black tracking-tight mb-1">Payment Successful</h2>
                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-[0.2em]">Transaction ID:
                    {{ $transactionId }}
                </p>
            </div>

            <div class="p-8 space-y-8">
                <div class="flex items-center justify-between relative">
                    <div class="z-10 pr-4">
                        <p class="text-[9px] uppercase text-blue-400 font-black tracking-widest mb-1">Starting point</p>
                        <p class="text-sm font-bold">{{ $pickup }}</p>
                    </div>
                    <div class="absolute top-1/2 left-0 w-full h-[1px]  -z-0">
                    </div>
                    <div class="z-10pl-4 text-right">
                        <p class="text-[9px] uppercase text-blue-400 font-black tracking-widest mb-1">Destination</p>
                        <p class="text-sm font-bold">{{ $destination }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-y-6 pt-4">
                    <div>
                        <p class="text-[9px] uppercase text-gray-500 font-black tracking-widest mb-1">Date</p>
                        <p class="text-xs font-bold">{{ now()->format('M d, Y') }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[9px] uppercase text-gray-500 font-black tracking-widest mb-1">Payment Method</p>
                        <p class="text-xs font-bold">{{ $paymentMethod }}</p>
                    </div>
                    <div>
                        <p class="text-[9px] uppercase text-gray-500 font-black tracking-widest mb-1">Distance</p>
                        <p class="text-xs font-bold">{{ $distance }} KM</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[9px] uppercase text-gray-500 font-black tracking-widest mb-1">Status</p>
                        <span
                            class="text-[9px] bg-green-500/10 text-green-400 px-2 py-1 rounded-lg font-black uppercase">Paid</span>
                    </div>
                </div>

                <div class="pt-8 border-t border-white/10 flex items-end justify-between">
                    <div>
                        <p class="text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] mb-1">Total Amount</p>
                        <div class="text-4xl font-black text-white flex items-baseline gap-1">
                            <span class="text-lg font-medium opacity-40">₱</span>
                            {{ number_format($price, 2) }}
                        </div>
                    </div>
                    {{-- <img
                        src="https://api.qrserver.com/v1/create-qr-code/?size=60x60&data=SmartCommute-{{ $price }}"
                        alt="QR Verify" class="rounded-xl border border-white/10 p-1 bg-white opacity-80"> --}}
                </div>
            </div>

            <div class="jagged-edge opacity-20"></div>
        </div>
    </div>

    <div class="mt-10 flex flex-col sm:flex-row items-center gap-4 w-full max-w-md">
        <button onclick="downloadReceipt()"
            class="w-full bg-white/5 hover:bg-white/10 border border-white/10 text-white font-black py-4 px-6 rounded-2xl text-[10px] uppercase tracking-[0.2em] transition-all flex items-center justify-center gap-3">
            <i class="fa-solid fa-arrow-down-to-bracket text-blue-400"></i>
            Save as Image
        </button>
        <a href="{{ route('map') }}"
            class="w-full bg-blue-600 hover:bg-blue-500 text-white font-black py-4 px-6 rounded-2xl text-[10px] uppercase tracking-[0.2em] text-center shadow-lg shadow-blue-600/20 transition-all active:scale-95">
            Back to Map
        </a>
    </div>

    <script>
        function downloadReceipt() {
            const receipt = document.getElementById('receipt-content');

            // Adjust scale for higher resolution image
            html2canvas(receipt, {
                backgroundColor: null, // Keeps transparency if needed
                scale: 3,
                useCORS: true,
                borderRadius: 40
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
