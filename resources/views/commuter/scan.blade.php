<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SmartCommute | Scan QR Code</title>

    @include('partials.commuter-head-scripts')

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

    <style>
        html,
        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
        }

        .dark html,
        .dark body {
            background-color: #050505;
        }

        .glass-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.06);
        }

        .dark .glass-card {
            background: #111111;
            border: 1px solid #1e1e1e;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
        }

        #qr-reader {
            width: 100%;
            min-height: 0;
            border: 0 !important;
        }

        #qr-reader video {
            border-radius: 1rem;
            width: 100% !important;
        }

        #qr-reader__scan_region img {
            display: none;
        }
    </style>
</head>

<body class="antialiased text-slate-900 dark:text-white min-h-screen flex flex-col items-center p-3 sm:p-5 lg:p-6">

    <a href="{{ route('map') }}"
        class="self-start shrink-0 mb-3 sm:mb-4 flex items-center gap-2 text-[11px] font-bold uppercase tracking-widest text-slate-400 dark:text-white/40 hover:text-slate-900 dark:hover:text-white transition">
        <span
            class="w-9 h-9 rounded-xl border border-slate-200 dark:border-[#1e1e1e] bg-white dark:bg-[#111] flex items-center justify-center">
            <i class="fa-solid fa-arrow-left text-[11px]"></i>
        </span>
        <span class="hidden sm:inline">Back to Map</span>
    </a>

    <div class="my-auto w-full max-w-[420px]">
        <div class="glass-card rounded-[1.75rem] p-5 sm:p-7 text-center">

            <div
                class="w-14 h-14 rounded-2xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center mx-auto mb-4">
                <i class="fa-solid fa-qrcode text-blue-500 text-xl"></i>
            </div>
            <h1 class="text-xl font-extrabold tracking-tight">Scan the QR code</h1>
            <p class="text-[12px] text-slate-500 dark:text-[#666] mt-1.5 leading-relaxed">
                Fares are paid on board. Scan any SmartCommute QR code inside the PUJ to continue to payment.
            </p>

            @if ($scan)
                <div
                    class="mt-5 rounded-xl bg-emerald-500/10 border border-emerald-500/20 p-3.5 flex items-center gap-3 text-left">
                    <i class="fa-solid fa-circle-check text-emerald-500 text-lg shrink-0"></i>
                    <p class="text-[11px] text-emerald-700 dark:text-emerald-300 leading-relaxed">
                        <strong>Verified.</strong>
                        Good for {{ config('qr.valid_minutes') }} minutes, for one fare.
                    </p>
                </div>
                <a href="{{ route('map') }}"
                    class="mt-3 block w-full py-3 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-[10px] font-bold uppercase tracking-widest transition">
                    Choose my trip
                </a>
            @endif

            <div id="scanner-wrap" class="mt-5">
                <div id="qr-reader"></div>
                <p id="scan-status" class="text-[11px] text-slate-500 dark:text-[#666] mt-3" role="status"></p>
                <button type="button" id="scan-start"
                    class="mt-3 w-full py-3 rounded-xl bg-slate-900 dark:bg-white text-white dark:text-black text-[10px] font-bold uppercase tracking-widest hover:opacity-90 transition">
                    <i class="fa-solid fa-camera mr-1.5"></i> {{ $scan ? 'Scan again' : 'Open camera' }}
                </button>
            </div>

            <div class="flex items-center gap-3 my-5">
                <div class="flex-1 h-px bg-slate-200 dark:bg-[#1e1e1e]"></div>
                <span class="text-[9px] font-bold uppercase tracking-widest text-slate-400 dark:text-[#444]">or type
                    the code</span>
                <div class="flex-1 h-px bg-slate-200 dark:bg-[#1e1e1e]"></div>
            </div>

            <form id="scan-form" action="{{ route('payment.scan.verify') }}" method="POST" class="space-y-2.5 text-left">
                @csrf
                <input type="text" id="code" name="code" autocomplete="off" autocapitalize="off" spellcheck="false"
                    placeholder="Code printed under the QR, or its link" value="{{ old('code') }}"
                    class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-[#0a0a0a] border {{ $errors->has('code') ? 'border-red-500/60' : 'border-slate-200 dark:border-[#1e1e1e]' }} text-[12px] font-mono focus:outline-none focus:border-blue-500/50 transition">
                @error('code')
                    <p class="text-[11px] text-red-500 dark:text-red-400" role="alert">{{ $message }}</p>
                @enderror
                <button type="submit"
                    class="w-full py-3 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-[10px] font-bold uppercase tracking-widest transition">
                    Verify
                </button>
            </form>

            <p class="text-[10px] text-slate-400 dark:text-[#444] mt-4 leading-relaxed">
                You can also scan the code with your phone's own camera app: it opens SmartCommute and verifies you.
            </p>
        </div>
    </div>

    <script>
        (function() {
            var status = document.getElementById('scan-status');
            var startBtn = document.getElementById('scan-start');
            var scanner = null;

            function say(text) { status.textContent = text || ''; }

            function submit(text) {
                document.getElementById('code').value = text;
                document.getElementById('scan-form').submit();
            }

            startBtn.addEventListener('click', function() {
                if (typeof Html5Qrcode === 'undefined') {
                    say('The scanner could not be loaded. Type the code below instead.');
                    return;
                }
                if (scanner) return;

                say('Starting the camera…');
                scanner = new Html5Qrcode('qr-reader');
                scanner.start({ facingMode: 'environment' }, { fps: 10, qrbox: { width: 220, height: 220 } },
                    function(decoded) {
                        say('Code read. Verifying…');
                        scanner.stop().catch(function() {}).then(function() { submit(decoded); });
                    },
                    function() { /* no code in this frame */ }
                ).then(function() {
                    startBtn.classList.add('hidden');
                    say('Point the camera at the QR code.');
                }).catch(function() {
                    scanner = null;
                    say('The camera is not available (it needs permission, and a secure connection). Use your phone\'s camera app, or type the code below.');
                });
            });
        })();
    </script>
</body>

</html>
