<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code — {{ $code->name() }}</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #f1f5f9; font-family: system-ui, -apple-system, 'Segoe UI', sans-serif; color: #0f172a; }
        .sheet { background: #fff; border: 2px solid #0f172a; border-radius: 24px; padding: 32px; width: 100%; max-width: 420px; margin: 16px; text-align: center; }
        .brand { font-weight: 800; font-size: 20px; letter-spacing: -0.02em; }
        .brand span { color: #2563eb; }
        h1 { font-size: 22px; margin: 18px 0 4px; }
        .qr { display: inline-block; padding: 12px; background: #fff; line-height: 0; margin: 14px 0; }
        .qr img, .qr canvas { width: 280px !important; height: 280px !important; max-width: 100%; }
        .how { font-size: 14px; line-height: 1.5; color: #334155; margin: 0 0 4px; }
        .small { font-size: 11px; color: #64748b; margin-top: 14px; word-break: break-all; }
        .off { display: inline-block; background: #fee2e2; color: #b91c1c; font-weight: 700; font-size: 12px; padding: 4px 10px; border-radius: 8px; margin-top: 8px; }
        button { margin-top: 18px; padding: 10px 22px; border-radius: 12px; border: 0; background: #2563eb; color: #fff; font-weight: 700; cursor: pointer; }
        @media print { body { background: #fff; } button, .small { display: none; } .sheet { border-width: 3px; margin: 0 auto; } }
    </style>
</head>

<body>
    <div class="sheet">
        <div class="brand">Smart<span>Commute</span></div>
        <h1>Scan to pay your fare</h1>
        <p class="how">Open SmartCommute, press <strong>Buy a Ride</strong> and scan this code.</p>
        <div class="qr" id="qr" data-url="{{ $code->scanUrl() }}"></div>
        <div><strong>{{ $code->name() }}</strong></div>
        @unless ($code->is_active)
            <div class="off">This code is switched off</div>
        @endunless
        <p class="small">{{ $code->scanUrl() }}</p>
        <button onclick="window.print()">Print</button>
    </div>
    <script>
        var el = document.getElementById('qr');
        new QRCode(el, { text: el.dataset.url, width: 280, height: 280, correctLevel: QRCode.CorrectLevel.M });
    </script>
</body>

</html>
