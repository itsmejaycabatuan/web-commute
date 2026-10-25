<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SmartCommute | Scan registration papers</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script>tailwind.config = { darkMode: 'media' }</script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .slot input[type=file] { position: absolute; inset: 0; opacity: 0; width: 100%; height: 100%; cursor: pointer; }
    </style>
</head>

<body class="bg-slate-50 dark:bg-[#0b0b0b] text-slate-900 dark:text-white min-h-screen antialiased">
    <main class="mx-auto max-w-md px-4 py-6">
        <div class="flex items-center gap-2 mb-5">
            <div class="w-9 h-9 rounded-xl bg-blue-600 flex items-center justify-center"><i class="fa-solid fa-bus text-white text-sm"></i></div>
            <span class="font-extrabold tracking-tight">Smart<span class="text-blue-500">Commute</span></span>
        </div>

        @if (! $session)
            <div class="rounded-2xl border border-slate-200 dark:border-[#222] bg-white dark:bg-[#111] p-6 text-center">
                <i class="fa-regular fa-clock text-3xl text-amber-500 mb-3"></i>
                <h1 class="text-lg font-extrabold">This scan link has expired</h1>
                <p class="text-sm text-slate-500 dark:text-[#888] mt-2">Go back to your computer and press <strong>Scan with phone</strong> again to get a new code.</p>
            </div>
        @else
            <div id="form-card" class="rounded-2xl border border-slate-200 dark:border-[#222] bg-white dark:bg-[#111] p-5">
                <h1 class="text-xl font-extrabold tracking-tight">Photograph the papers</h1>
                <p class="text-sm text-slate-500 dark:text-[#888] mt-1">Your computer is waiting for them. The photos are read on the company server and deleted after the vehicle is saved.</p>

                <ul class="mt-4 space-y-1.5 text-[13px] text-slate-600 dark:text-[#aaa]">
                    <li><i class="fa-solid fa-check text-emerald-500 mr-2"></i>Lay the paper <strong>flat</strong> and fill the frame.</li>
                    <li><i class="fa-solid fa-check text-emerald-500 mr-2"></i>Good light, <strong>no shadow</strong> across the text.</li>
                    <li><i class="fa-solid fa-check text-emerald-500 mr-2"></i>Hold still until it is sharp. Your phone's <em>scan document</em> mode works too.</li>
                </ul>

                <form id="scan-form" method="POST" action="{{ route('vehicle-scan.upload', $token) }}" enctype="multipart/form-data" class="mt-5 space-y-3">
                    @csrf
                    @foreach ([['Certificate of Registration (CR)', 'Has the make, model and chassis number.', true], ['Official Receipt (OR)', 'Optional. A second paper to cross-check the plate.', false]] as $i => [$title, $hint, $main])
                        <label class="slot relative block rounded-xl border-2 border-dashed {{ $main ? 'border-blue-400/60' : 'border-slate-300 dark:border-[#333]' }} p-4 text-center overflow-hidden">
                            <input type="file" name="photos[]" accept="image/*" data-slot="{{ $i }}">
                            <img data-preview="{{ $i }}" class="hidden mx-auto max-h-40 rounded-lg mb-2" alt="">
                            <div data-empty="{{ $i }}">
                                <i class="fa-solid fa-camera text-2xl {{ $main ? 'text-blue-500' : 'text-slate-400' }}"></i>
                                <p class="font-bold text-sm mt-1.5">{{ $title }}</p>
                                <p class="text-xs text-slate-500 dark:text-[#777]">{{ $hint }}</p>
                            </div>
                            <p data-picked="{{ $i }}" class="hidden text-xs font-bold text-emerald-600 dark:text-emerald-400"><i class="fa-solid fa-check mr-1"></i> Photo ready. Tap to retake.</p>
                        </label>
                    @endforeach

                    <p id="error" class="hidden text-sm font-semibold text-rose-600 dark:text-rose-400"></p>

                    <button id="send" type="submit" disabled
                        class="w-full rounded-xl bg-blue-600 disabled:bg-slate-300 dark:disabled:bg-[#222] disabled:text-slate-500 text-white font-bold py-3.5 text-sm tracking-wide transition">
                        <i class="fa-solid fa-paper-plane mr-1.5"></i> Send to computer
                    </button>
                </form>
            </div>

            <div id="busy" class="hidden rounded-2xl border border-slate-200 dark:border-[#222] bg-white dark:bg-[#111] p-8 text-center">
                <i class="fa-solid fa-circle-notch fa-spin text-3xl text-blue-500"></i>
                <h2 class="text-lg font-extrabold mt-3">Reading your photos…</h2>
                <p class="text-sm text-slate-500 dark:text-[#888] mt-1">This can take up to half a minute. Keep this page open.</p>
            </div>

            <div id="done" class="hidden rounded-2xl border border-emerald-300/50 bg-white dark:bg-[#111] p-8 text-center">
                <i class="fa-solid fa-circle-check text-4xl text-emerald-500"></i>
                <h2 class="text-lg font-extrabold mt-3">Sent to your computer</h2>
                <p class="text-sm text-slate-500 dark:text-[#888] mt-1">Go back to the vehicle form. You will check the details there before saving.</p>
            </div>
        @endif
    </main>

    @if ($session)
        <script>
            (function () {
                var form = document.getElementById('scan-form');
                var send = document.getElementById('send');
                var errorBox = document.getElementById('error');
                var picked = {};
                var MAX_SIDE = 3000;

                function show(id) {
                    ['form-card', 'busy', 'done'].forEach(function (x) { document.getElementById(x).classList.toggle('hidden', x !== id); });
                }

                function fail(message) {
                    errorBox.textContent = message;
                    errorBox.classList.remove('hidden');
                    show('form-card');
                    send.disabled = Object.keys(picked).length === 0;
                }

                // Shrinks a big phone photo before it is sent (and applies its rotation), so the upload is quick.
                function shrink(file) {
                    if (!window.createImageBitmap) return Promise.resolve(file);
                    return createImageBitmap(file, { imageOrientation: 'from-image' }).then(function (bmp) {
                        var scale = Math.min(1, MAX_SIDE / Math.max(bmp.width, bmp.height));
                        var canvas = document.createElement('canvas');
                        canvas.width = Math.round(bmp.width * scale);
                        canvas.height = Math.round(bmp.height * scale);
                        canvas.getContext('2d').drawImage(bmp, 0, 0, canvas.width, canvas.height);
                        return new Promise(function (resolve) {
                            canvas.toBlob(function (blob) { resolve(blob ? new File([blob], 'page.jpg', { type: 'image/jpeg' }) : file); }, 'image/jpeg', 0.9);
                        });
                    }).catch(function () { return file; });
                }

                document.querySelectorAll('input[type=file]').forEach(function (input) {
                    input.addEventListener('change', function () {
                        var slot = input.dataset.slot;
                        var file = input.files && input.files[0];
                        var preview = document.querySelector('[data-preview="' + slot + '"]');

                        if (!file) { delete picked[slot]; } else {
                            picked[slot] = file;
                            preview.src = URL.createObjectURL(file);
                            preview.classList.remove('hidden');
                            document.querySelector('[data-empty="' + slot + '"]').classList.add('hidden');
                            document.querySelector('[data-picked="' + slot + '"]').classList.remove('hidden');
                        }

                        errorBox.classList.add('hidden');
                        send.disabled = Object.keys(picked).length === 0;
                    });
                });

                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    errorBox.classList.add('hidden');
                    show('busy');

                    Promise.all(Object.keys(picked).sort().map(function (k) { return shrink(picked[k]); })).then(function (files) {
                        var data = new FormData();
                        files.forEach(function (f) { data.append('photos[]', f); });

                        return fetch(form.action, {
                            method: 'POST', body: data, credentials: 'same-origin',
                            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                        });
                    }).then(function (response) {
                        if (response.status === 419) {
                            return { status: 'failed', message: 'This page timed out. Reload it, then send the photos again.' };
                        }
                        return response.json().catch(function () { return { status: 'failed', message: 'Something went wrong. Please try again.' }; });
                    }).then(function (result) {
                        if (result.status === 'ready') { show('done'); } else { fail(result.message || 'Something went wrong. Please try again.'); }
                    }).catch(function () {
                        fail('We lost the connection. Check your signal and try again.');
                    });
                });
            })();
        </script>
    @endif
</body>

</html>
