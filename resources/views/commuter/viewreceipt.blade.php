<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartCommute | View Receipt</title>

    {{-- Theme detection MUST run immediately to prevent flash --}}
    <script>
        if (localStorage.getItem('color-theme') === 'dark' ||
            (!localStorage.getItem('color-theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif']
                    }
                }
            }
        }
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">

    <style>
        html,
        body {
            margin: 0;
            padding: 0;
            height: 100%;
            width: 100%;
            font-family: 'Inter', sans-serif;
            overflow-x: hidden;
            background-color: #f8fafc;
        }

        .dark html,
        .dark body {
            background-color: #050505;
            background: radial-gradient(circle at top left, #0f172a, #050505);
        }

        *,
        *::before,
        *::after {
            transition-property: background-color, border-color, color, box-shadow, opacity, fill, stroke;
            transition-duration: 0.3s;
            transition-timing-function: ease;
        }

        .glass {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
        }

        .dark .glass {
            background: rgba(255, 255, 255, 0.02);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .jagged-edge {
            position: relative;
            height: 20px;
            width: 100%;
            background: radial-gradient(circle, transparent, transparent 10px, rgba(148, 163, 184, 0.1) 10px);
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

<body class="antialiased flex flex-col items-center justify-center p-6 text-slate-900 dark:text-white">

    <div class="w-full max-w-md mb-8 flex items-center justify-between">
        <a href="{{ route('payment.history') }}"
            class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-white/40 hover:text-slate-900 dark:hover:text-white transition flex items-center gap-2">
            <i class="fa-solid fa-arrow-left"></i> Back to History
        </a>
    </div>

    <div id="receipt-content" class="w-full max-w-md relative">
        <div class="absolute -top-12 left-1/2 -translate-x-1/2 w-24 h-24 bg-blue-500/10 rounded-full blur-3xl"></div>

        <div class="glass rounded-[2.5rem] overflow-hidden shadow-2xl dark:shadow-black/40">
            <div class="p-8 text-center border-b border-slate-100 dark:border-white/5 bg-slate-50 dark:bg-white/[0.02]">
                <div
                    class="w-14 h-14 bg-slate-100 dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-2xl flex items-center justify-center mx-auto mb-5 shadow-inner">
                    <i class="fa-solid fa-file-invoice text-xl text-blue-600 dark:text-blue-400"></i>
                </div>
                <h2 class="text-lg font-bold tracking-tight text-slate-900 dark:text-white mb-1">Transaction Record</h2>
                <p class="text-[9px] text-slate-400 dark:text-white/30 font-bold uppercase tracking-[0.2em]">Transaction
                    No: {{ $transactionId }}</p>
            </div>

            <div class="p-8 space-y-8">
                <div class="flex items-center justify-between relative px-2">
                    <div class="z-10 bg-white dark:bg-[#0c1220] pr-3">
                        <p
                            class="text-[8px] uppercase text-blue-500/60 dark:text-blue-400/60 font-black tracking-widest mb-1">
                            Origin</p>
                        <p class="text-xs font-bold text-slate-900 dark:text-white">{{ $pickup }}</p>
                    </div>

                    <div
                        class="absolute top-1/2 left-0 w-full h-[1px] bg-gradient-to-r from-transparent via-slate-200 dark:via-white/10 to-transparent -z-0">
                    </div>

                    <div class="z-10 bg-white dark:bg-[#0c1220] pl-3 text-right">
                        <p
                            class="text-[8px] uppercase text-blue-500/60 dark:text-blue-400/60 font-black tracking-widest mb-1">
                            Destination</p>
                        <p class="text-xs font-bold text-slate-900 dark:text-white">{{ $destination }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-y-6 pt-4 border-t border-slate-100 dark:border-white/5">
                    <div>
                        <p
                            class="text-[8px] uppercase text-slate-400 dark:text-white/30 font-black tracking-widest mb-1">
                            Date Processed</p>
                        <p class="text-xs font-bold text-slate-700 dark:text-white/80">{{ $paidAt }}</p>
                    </div>
                    <div class="text-right">
                        <p
                            class="text-[8px] uppercase text-slate-400 dark:text-white/30 font-black tracking-widest mb-1">
                            Payment Method</p>
                        <p class="text-xs font-bold text-slate-700 dark:text-white/80">{{ $paymentMethod }}</p>
                    </div>
                    <div>
                        <p
                            class="text-[8px] uppercase text-slate-400 dark:text-white/30 font-black tracking-widest mb-1">
                            Total Distance</p>
                        <p class="text-xs font-bold text-slate-700 dark:text-white/80">{{ $distance }} KM</p>
                    </div>
                    <div class="text-right">
                        <p
                            class="text-[8px] uppercase text-slate-400 dark:text-white/30 font-black tracking-widest mb-1">
                            Verified Status</p>
                        <span
                            class="text-[8px] bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 px-2 py-0.5 rounded-md font-black uppercase tracking-tighter">Settled</span>
                    </div>
                </div>

                <div class="pt-8 border-t border-slate-200 dark:border-white/10 flex items-center justify-between">
                    <div>
                        <p
                            class="text-[9px] font-black text-slate-400 dark:text-white/30 uppercase tracking-[0.2em] mb-1">
                            Amount Paid</p>
                        <div class="text-3xl font-black text-slate-900 dark:text-white flex items-baseline gap-1">
                            <span class="text-sm font-medium text-slate-300 dark:opacity-30">₱</span>
                            {{ number_format($price, 2) }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="jagged-edge"></div>
        </div>
    </div>

    <div class="mt-8 w-full max-w-md">
        <button onclick="downloadReceipt()"
            class="w-full bg-slate-100 dark:bg-white/5 hover:bg-slate-200 dark:hover:bg-white/10 border border-slate-200 dark:border-white/10 text-slate-900 dark:text-white font-bold py-4 px-6 rounded-2xl text-[10px] uppercase tracking-[0.2em] transition-all flex items-center justify-center gap-3">
            <i class="fa-solid fa-download text-blue-600 dark:text-blue-400"></i>
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
