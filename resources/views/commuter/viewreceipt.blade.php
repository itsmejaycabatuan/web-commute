<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartCommute | View Receipt</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700;800&display=swap');

        body {
            background: radial-gradient(circle at top left, #0f172a, #050505);
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #fff;
            min-height: 100vh;
        }

        .glass {
            background: rgba(255, 255, 255, 0.02);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

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

    <div class="w-full max-w-md mb-8 flex items-center justify-between">
        <a href="{{ route('payment.history') }}"
            class="text-[10px] font-bold uppercase tracking-widest text-white/40 hover:text-white transition flex items-center gap-2">
            <i class="fa-solid fa-arrow-left"></i> Back to History
        </a>
    </div>

    <div id="receipt-content" class="w-full max-w-md relative">
        <div class="absolute -top-12 left-1/2 -translate-x-1/2 w-24 h-24 bg-blue-500/10 rounded-full blur-3xl"></div>

        <div class="glass rounded-[2.5rem] overflow-hidden shadow-2xl">
            <div class="p-8 text-center border-b border-white/5 bg-white/[0.02]">
                <div
                    class="w-14 h-14 bg-white/5 border border-white/10 rounded-2xl flex items-center justify-center mx-auto mb-5 shadow-inner">
                    <i class="fa-solid fa-file-invoice text-xl text-blue-400"></i>
                </div>
                <h2 class="text-lg font-bold tracking-tight mb-1">Transaction Record</h2>
                <p class="text-[9px] text-white/30 font-bold uppercase tracking-[0.2em]">Transaction No:
                    {{ $transactionId }}
                </p>
            </div>

            <div class="p-8 space-y-8">
                <div class="flex items-center justify-between relative px-2">
                    <div class="z-10 bg-[#0c1220] pr-3">
                        <p class="text-[8px] uppercase text-blue-400/60 font-black tracking-widest mb-1">Origin</p>
                        <p class="text-xs font-bold">{{ $pickup }}</p>
                    </div>

                    <div
                        class="absolute top-1/2 left-0 w-full h-[1px] bg-gradient-to-r from-transparent via-white/10 to-transparent -z-0">
                    </div>

                    <div class="z-10 bg-[#0c1220] pl-3 text-right">
                        <p class="text-[8px] uppercase text-blue-400/60 font-black tracking-widest mb-1">Destination</p>
                        <p class="text-xs font-bold">{{ $destination }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-y-6 pt-4 border-t border-white/5">
                    <div>
                        <p class="text-[8px] uppercase text-white/30 font-black tracking-widest mb-1">Date Processed</p>
                        <p class="text-xs font-bold text-white/80">{{ $paidAt }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[8px] uppercase text-white/30 font-black tracking-widest mb-1">Payment Method</p>
                        <p class="text-xs font-bold text-white/80">{{ $paymentMethod }}</p>
                    </div>
                    <div>
                        <p class="text-[8px] uppercase text-white/30 font-black tracking-widest mb-1">Total Distance</p>
                        <p class="text-xs font-bold text-white/80">{{ $distance }} KM</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[8px] uppercase text-white/30 font-black tracking-widest mb-1">Verified Status
                        </p>
                        <span
                            class="text-[8px] bg-blue-500/10 text-blue-400 border border-blue-500/20 px-2 py-0.5 rounded-md font-black uppercase tracking-tighter">Settled</span>
                    </div>
                </div>

                <div class="pt-8 border-t border-white/10 flex items-center justify-between">
                    <div>
                        <p class="text-[9px] font-black text-white/30 uppercase tracking-[0.2em] mb-1">Amount Paid</p>
                        <div class="text-3xl font-black text-white flex items-baseline gap-1">
                            <span class="text-sm font-medium opacity-30">₱</span>
                            {{ number_format($price, 2) }}
                        </div>
                    </div>
                    {{-- <div class="relative group">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=60x60&data=SmartCommute-{{ $transactionId }}"
                            alt="QR Verify"
                            class="w-14 h-14 rounded-lg border border-white/10 p-1 bg-white opacity-60 group-hover:opacity-100 transition-opacity">
                    </div> --}}
                </div>
            </div>

            <div class="jagged-edge opacity-10"></div>
        </div>
    </div>

    <div class="mt-8 w-full max-w-md">
        <button onclick="downloadReceipt()"
            class="w-full bg-white/5 hover:bg-white/10 border border-white/10 text-white font-bold py-4 px-6 rounded-2xl text-[10px] uppercase tracking-[0.2em] transition-all flex items-center justify-center gap-3">
            <i class="fa-solid fa-download text-blue-400"></i>
            Download Receipt
        </button>
    </div>

    <script>
        function downloadReceipt() {
            const receipt = document.getElementById('receipt-content');
            html2canvas(receipt, {
                backgroundColor: null,
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