<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SmartCommute | QR Codes</title>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    @include('partials.head-scripts')

    <style>
        .sidebar-transition {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }

        [x-cloak] {
            display: none !important;
        }

        /* A QR code is always dark on white, in either theme, or scanners cannot read it. */
        .qr-thumb {
            background: #fff;
            padding: 4px;
            border-radius: 8px;
            line-height: 0;
        }

        .qr-thumb img,
        .qr-thumb canvas {
            width: 64px !important;
            height: 64px !important;
        }
    </style>
</head>

<script type="application/json" id="qr-data">@json($codes->keyBy('id'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)</script>

<body class="antialiased text-gray-900 dark:text-white" x-data>
    <div x-data="{
        codes: JSON.parse(document.getElementById('qr-data').textContent),
        modal: null, // 'delete'
        selected: null,
        open(modal, id = null) { this.selected = id ? this.codes[id] : null; this.modal = modal; },
        close() { this.modal = null; }
    }" x-init="$nextTick(() => document.querySelectorAll('[data-qr]').forEach(el => new QRCode(el, { text: el.dataset.qr, width: 128, height: 128, correctLevel: QRCode.CorrectLevel.M })))"
        @keydown.escape.window="close()">
        <x-layout.sidebar />

        <main :class="$store.sidebar.open ? 'md:ml-72' : 'md:ml-20'"
            class="sidebar-transition pt-8 pr-4 sm:pr-8 pb-8 pl-4 sm:pl-8 min-h-screen mb-12">

            <div class="mb-6 lg:mb-8">
                <div class="flex items-center gap-2 mb-1.5">
                    <span
                        class="text-[9px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Manage</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-gray-900 dark:text-white">Ride <span
                        class="text-blue-500 dark:text-blue-400">QR Codes</span></h1>
                <p class="text-[11px] text-gray-500 dark:text-[#555] mt-1 flex items-center gap-2">
                    <i class="fa-solid fa-qrcode text-[9px] text-emerald-500 dark:text-emerald-400"></i>
                    A commuter must scan one of these, inside the PUJ, before they can pay a fare.
                </p>
            </div>

            <div class="glass-card rounded-[1.25rem] sm:rounded-[1.5rem] overflow-hidden">
                <div
                    class="p-4 sm:p-5 border-b border-gray-200 dark:border-[#1e1e1e] flex items-center justify-between gap-3">
                    <span class="text-[8px] font-bold text-gray-400 dark:text-[#333] uppercase tracking-widest">
                        {{ $codes->count() }} {{ $codes->count() === 1 ? 'code' : 'codes' }}
                        · {{ $codes->where('is_active', true)->count() }} active
                    </span>
                    <form method="POST" action="{{ route('qr-codes.store') }}" data-ajax class="m-0">
                        @csrf
                        <button type="submit"
                            class="flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-[10px] font-bold uppercase tracking-widest text-white transition active:scale-[0.98]">
                            <i class="fa-solid fa-qrcode text-[9px]"></i><span>Generate QR Code</span>
                        </button>
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left min-w-[640px]">
                        <thead>
                            <tr
                                class="text-[8px] sm:text-[9px] uppercase tracking-[0.15em] text-gray-400 dark:text-[#444] border-b border-gray-200 dark:border-[#1e1e1e]">
                                <th class="px-4 sm:px-6 py-3 font-bold w-24">Code</th>
                                <th class="px-4 sm:px-6 py-3 font-bold">Name</th>
                                <th class="px-4 sm:px-6 py-3 font-bold">Status</th>
                                <th class="px-4 sm:px-6 py-3 font-bold">Scans</th>
                                <th class="px-4 sm:px-6 py-3 font-bold">Created</th>
                                <th class="px-4 sm:px-6 py-3 font-bold text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-[#1a1a1a]">
                            @forelse ($codes as $c)
                                <tr class="table-row">
                                    <td class="px-4 sm:px-6 py-3">
                                        <div class="qr-thumb inline-block" data-qr="{{ $c['scan_url'] }}"></div>
                                    </td>
                                    <td class="px-4 sm:px-6 py-3">
                                        <span
                                            class="text-[11px] font-bold text-gray-700 dark:text-[#ccc] block whitespace-nowrap">{{ $c['name'] }}</span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-3">
                                        <span
                                            class="inline-block text-[8px] px-2 py-0.5 rounded-md font-bold uppercase border {{ $c['is_active'] ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/15' : 'bg-gray-500/10 text-gray-500 dark:text-[#888] border-gray-500/15' }}">{{ $c['is_active'] ? 'Active' : 'Off' }}</span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-3 text-[11px] font-bold font-mono text-gray-600 dark:text-[#888]">
                                        {{ $c['scans'] }}</td>
                                    <td class="px-4 sm:px-6 py-3 text-[10px] text-gray-500 dark:text-[#555] whitespace-nowrap">
                                        {{ $c['created'] }}</td>
                                    <td class="px-4 sm:px-6 py-3">
                                        <div class="flex items-center gap-1.5 justify-end">
                                            <a href="{{ $c['print_url'] }}" target="_blank" rel="noopener"
                                                class="flex items-center gap-1.5 px-3 h-8 rounded-lg bg-gray-100 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] text-[8px] font-bold uppercase tracking-widest text-gray-600 dark:text-[#888] hover:bg-gray-200 dark:hover:bg-[#1a1a1a] transition">
                                                <i class="fa-solid fa-print text-[8px]"></i> Print
                                            </a>
                                            <form method="POST" action="{{ $c['toggle_url'] }}" data-ajax class="m-0">
                                                @csrf @method('PATCH')
                                                <button type="submit"
                                                    class="px-3 h-8 rounded-lg bg-gray-100 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] text-[8px] font-bold uppercase tracking-widest text-gray-600 dark:text-[#888] hover:bg-gray-200 dark:hover:bg-[#1a1a1a] transition">
                                                    {{ $c['is_active'] ? 'Switch off' : 'Switch on' }}
                                                </button>
                                            </form>
                                            <button @click="open('delete', {{ $c['id'] }})" title="Delete"
                                                class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] hover:bg-red-500/10 hover:border-red-500/20 flex items-center justify-center transition group">
                                                <i
                                                    class="fa-solid fa-trash-can text-[8px] text-gray-400 dark:text-[#555] group-hover:text-red-500 transition"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-14">
                                        <div class="flex flex-col items-center justify-center">
                                            <div
                                                class="w-12 h-12 rounded-xl bg-gray-100 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] flex items-center justify-center mb-3">
                                                <i class="fa-solid fa-qrcode text-base text-gray-300 dark:text-[#333]"></i>
                                            </div>
                                            <p class="text-[11px] text-gray-400 dark:text-[#444] font-medium mb-1">No QR codes yet
                                            </p>
                                            <p class="text-[9px] text-gray-300 dark:text-[#333]">Generate one, print it and put it up
                                                inside a PUJ. Until then commuters cannot pay a fare.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </main>

        <!-- ══════════ DELETE ══════════ -->
        <div x-show="modal === 'delete'" x-cloak
            class="fixed inset-0 z-[250] flex items-center justify-center p-4 bg-black/50 dark:bg-black/80"
            @click.self="close()">
            <div class="glass-panel w-full max-w-sm rounded-[2rem] p-6 sm:p-8 text-center" @click.stop>
                <div
                    class="w-14 h-14 bg-red-500/10 rounded-2xl flex items-center justify-center mx-auto mb-5 border border-red-500/20">
                    <i class="fa-solid fa-triangle-exclamation text-red-400 text-lg"></i>
                </div>
                <h3 class="text-base font-bold text-gray-900 dark:text-white mb-1.5">Delete this QR code?</h3>
                <p class="text-[11px] text-gray-500 dark:text-[#666] mb-6">
                    <span class="font-bold" x-text="selected ? selected.name : ''"></span><br>
                    Printed copies stop working straight away.
                </p>
                <form :action="selected ? selected.delete_url : ''" method="POST" data-ajax class="space-y-2.5">
                    @csrf @method('DELETE')
                    <button type="submit"
                        class="w-full py-3 rounded-xl bg-red-600 hover:bg-red-500 text-white text-[10px] font-bold uppercase tracking-widest transition active:scale-[0.98]">Delete</button>
                </form>
                <button @click="close()"
                    class="mt-3 text-[10px] font-bold text-gray-400 dark:text-[#555] hover:text-gray-900 dark:hover:text-white transition">Cancel</button>
            </div>
        </div>
    </div>
</body>

</html>
