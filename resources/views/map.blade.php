<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartCommute | Live Map</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel='stylesheet' href='https://unpkg.com/maplibre-gl@5.18.0/dist/maplibre-gl.css' />
    <script src="https://unpkg.com/@maplibre/maplibre-gl-directions@latest/dist/maplibre-gl-directions.js"></script>
    <script src='https://unpkg.com/maplibre-gl@5.18.0/dist/maplibre-gl.js'></script>
    <script src="https://unpkg.com/laravel-echo@1.15.3/dist/echo.iife.js"></script>
    <script src="https://js.pusher.com/7.2/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@watergis/maplibre-gl-terradraw@1.0.1/dist/maplibre-gl-terradraw.umd.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@watergis/maplibre-gl-terradraw@1.0.1/dist/maplibre-gl-terradraw.css" />
    <script>
        tailwind.config = {
            theme: { extend: { fontFamily: { sans: ['Inter', 'sans-serif'] } } }
        }
    </script>
    <style>
        body, html { margin: 0; padding: 0; height: 100%; width: 100%; font-family: 'Inter', sans-serif; overflow: hidden; background: #050505; }
        #map { position: absolute; top: 0; bottom: 0; width: 100%; z-index: 0; }
        .glass { background: #111111; border: 1px solid #1e1e1e; box-shadow: 0 8px 32px 0 rgba(0,0,0,0.8); }
        .glass-panel { background: #111111 !important; border: 1px solid #1e1e1e; box-shadow: 0 4px 24px rgba(0,0,0,0.6); }
        .glass-card { background: #161616; border: 1px solid #222222; box-shadow: 0 4px 24px rgba(0,0,0,0.5); }
        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #333; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #444; }
        .custom-scroll::-webkit-scrollbar { width: 3px; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #333; border-radius: 10px; }
        .map-input { background: #0e0e0e !important; border: 1px solid #222222 !important; transition: all 0.3s ease; }
        .map-input::placeholder { color: #555; }
        .map-input:focus { background: #0e0e0e !important; border-color: #2563eb !important; box-shadow: 0 0 0 3px rgba(37,99,235,0.15) !important; outline: none; }
        button { color: white; }
        .bus-pulse { box-shadow: 0 0 0 0 rgba(59,130,246,0.7); animation: pulse-blue 2s infinite; }
        @keyframes pulse-blue {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(59,130,246,0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(59,130,246,0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(59,130,246,0); }
        }
        .custom-vehicle-marker {
            width: 34px; height: 34px; background: linear-gradient(135deg, #3b82f6, #2563eb);
            border: 3px solid white; border-radius: 50%;
            box-shadow: 0 0 20px rgba(59,130,246,0.6), 0 0 40px rgba(59,130,246,0.2);
            cursor: pointer; transition: all 0.3s ease; pointer-events: auto;
            display: flex; align-items: center; justify-content: center;
        }
        .custom-vehicle-marker:hover { transform: scale(1.2); box-shadow: 0 0 25px rgba(59,130,246,0.8), 0 0 50px rgba(59,130,246,0.3); }
        .custom-vehicle-marker i { font-size: 14px; color: white; }
        .rounded-rect { background: #111111; border: 1px solid #1e1e1e; border-radius: 14px; box-shadow: 0 4px 20px rgba(0,0,0,0.5); color: #666; transition: all 0.3s ease; }
        .rounded-rect:hover { color: #60a5fa; border-color: #2563eb; background: #1a1a1a; }
        .flex-center { position: absolute; display: flex; justify-content: center; align-items: center; }
        .flex-center.left { left: 0; }
        .flex-center.right { right: 0; }
        .sidebar-content { position: absolute; width: 95%; height: 95%; }
        .sidebar-toggle { position: absolute; width: 2em; height: 2em; overflow: visible; display: flex; justify-content: center; align-items: center; cursor: pointer; transition: all 0.3s ease; font-size: 16px; font-weight: 300; }
        .sidebar-toggle.left { right: -2.4em; }
        .sidebar-toggle.right { left: -2.4em; }
        .sidebar { transition: transform 0.6s cubic-bezier(0.16,1,0.3,1); z-index: 1; width: 360px; height: 100%; }
        .left.collapsed { transform: translateX(-300px); }
        .right.collapsed { transform: translateX(300px); }
        .modal-backdrop { transition: opacity 0.3s ease; }
        .modal-content { transition: all 0.35s cubic-bezier(0.16,1,0.3,1); }
        .modal-backdrop.active { opacity: 1; pointer-events: auto; }
        .modal-backdrop.active .modal-content { transform: scale(1); opacity: 1; }
        .header-btn { transition: all 0.3s ease; }
        .header-btn:hover { background: #1a1a1a !important; border-color: #333 !important; }
        .maplibregl-ctrl-group { background: #111111 !important; border: 1px solid #1e1e1e !important; border-radius: 14px !important; box-shadow: 0 8px 32px rgba(0,0,0,0.5) !important; overflow: hidden; }
        .maplibregl-ctrl-group button { width: 40px !important; height: 40px !important; border-bottom: 1px solid #1a1a1a !important; transition: background 0.2s ease; }
        .maplibregl-ctrl-group button:hover { background: #1a1a1a !important; }
        .maplibregl-ctrl-group button span { filter: invert(1) opacity(0.5); }
        .maplibregl-ctrl-group button:hover span { filter: invert(1) opacity(0.9); }
        .line-glow { background: #222; height: 1px; }
        @keyframes nav-slide-up { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .mobile-nav-animate { animation: nav-slide-up 0.5s cubic-bezier(0.16,1,0.3,1) 0.3s both; }
        @keyframes dot-pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.4; } }
        .dot-pulse { animation: dot-pulse 2s ease-in-out infinite; }

        /* ── Search Dropdown ── */
        .search-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            margin-top: 4px;
            background: #111;
            border: 1px solid #222;
            border-radius: 12px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 9999;
            display: none;
            box-shadow: 0 12px 40px rgba(0,0,0,0.6);
        }
        .search-dropdown.active { display: block; }
        .search-item {
            padding: 10px 14px;
            cursor: pointer;
            transition: background 0.15s ease;
            border-bottom: 1px solid #1a1a1a;
        }
        .search-item:last-child { border-bottom: none; }
        .search-item:hover,
        .search-item.highlighted { background: #1a1a1a; }
        .search-item .result-name { color: #ddd; font-size: 12px; font-weight: 600; }
        .search-item .result-detail { color: #555; font-size: 10px; margin-top: 2px; }
        .search-loading { padding: 14px; text-align: center; color: #555; font-size: 11px; }
        .search-no-results { padding: 14px; text-align: center; color: #444; font-size: 11px; }

        /* ── Mobile Bottom Sheet Modal ── */
        .mobile-sheet-backdrop {
            position: fixed;
            inset: 0;
            z-index: 90;
            background: rgba(0,0,0,0.6);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }
        .mobile-sheet-backdrop.visible {
            opacity: 1;
            pointer-events: auto;
        }
        .mobile-sheet {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 91;
            background: #0a0a0a;
            border-top: 1px solid #1e1e1e;
            border-radius: 1.5rem 1.5rem 0 0;
            max-height: 88vh;
            overflow: hidden;
            transform: translateY(100%);
            transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1);
            display: flex;
            flex-direction: column;
        }
        .mobile-sheet.open {
            transform: translateY(0);
        }
        .mobile-sheet-handle {
            display: flex;
            justify-content: center;
            padding: 12px 0 4px;
            flex-shrink: 0;
        }
        .mobile-sheet-handle div {
            width: 40px;
            height: 4px;
            background: #333;
            border-radius: 9999px;
        }
        .mobile-sheet-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 4px 20px 12px;
            flex-shrink: 0;
        }
        .mobile-sheet-close {
            width: 32px;
            height: 32px;
            border-radius: 12px;
            background: #1a1a1a;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
            transition: background 0.2s;
        }
        .mobile-sheet-close:hover { background: #222; }
        .mobile-sheet-body {
            padding: 0 20px 32px;
            overflow-y: auto;
            flex: 1;
        }

        /* ── Mobile FAB Buttons ── */
        .mobile-fab {
            width: 56px;
            height: 56px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .mobile-fab:active { transform: scale(0.92); }
        .mobile-fab-left {
            background: #2563eb;
            box-shadow: 0 4px 20px rgba(37,99,235,0.35);
        }
        .mobile-fab-right {
            background: #111;
            border: 1px solid #1e1e1e;
            box-shadow: 0 4px 20px rgba(0,0,0,0.4);
        }

        /* ── Tutorial Modal (centered) ── */
        .tutorial-backdrop {
            position: fixed;
            inset: 0;
            z-index: 60;
            display: none;
            align-items: center;
            justify-content: center;
        }
        .tutorial-backdrop.open {
            display: flex;
        }
        .tutorial-backdrop-bg {
            position: absolute;
            inset: 0;
            background: rgba(0,0,0,0.7);
        }
        .tutorial-modal-box {
            position: relative;
            z-index: 1;
            width: 340px;
            max-width: calc(100vw - 2rem);
            max-height: calc(100vh - 4rem);
            overflow-y: auto;
            background: #111;
            border: 1px solid #222;
            border-radius: 1.5rem;
            box-shadow: 0 24px 64px rgba(0,0,0,0.5);
            transform: scale(0.95);
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .tutorial-backdrop.open .tutorial-modal-box {
            transform: scale(1);
            opacity: 1;
        }
        @keyframes pulse-ring {
            0% { transform: scale(1); opacity: 0.4; }
            100% { transform: scale(1.6); opacity: 0; }
        }
        .clock-pulse { animation: pulse-ring 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
    </style>
</head>

<body class="antialiased">

    @include('components.flash')

    <header class="fixed top-4 left-4 right-4 sm:top-5 sm:left-5 sm:right-5 z-50 flex flex-col sm:flex-row justify-between items-center sm:items-center gap-3 pointer-events-none">
        <div class="glass-panel p-3 sm:p-3.5 rounded-2xl pointer-events-auto flex items-center gap-3">
            <div class="w-9 h-9 bg-blue-600 rounded-xl flex items-center justify-center">
                <i class="fa-solid fa-bus text-white text-sm"></i>
            </div>
            <span class="text-sm font-bold tracking-tight text-white">Smart<span class="text-blue-400">Commute</span></span>
            @if (Auth::check() && Auth::user()->roles[0]->name === 'commuter' && isset($balance))
                <div class="w-px h-6 bg-[#222] mx-1"></div>
                <a href="{{ route('payment.topup') }}" class="group">
                    <div class="flex items-center gap-2.5 py-1.5 px-3 rounded-xl hover:bg-[#1a1a1a] transition-all cursor-pointer">
                        <div class="w-7 h-7 bg-emerald-500/15 rounded-lg flex items-center justify-center border border-emerald-500/20">
                            <i class="fa-solid fa-wallet text-emerald-400 text-[10px]"></i>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-[7px] uppercase tracking-[0.15em] text-[#555] font-bold leading-none">Balance</span>
                            <span class="text-white font-bold text-[11px] leading-tight mt-0.5">₱{{ $balance }}</span>
                        </div>
                        <div class="w-5 h-5 rounded-md bg-[#1a1a1a] flex items-center justify-center group-hover:bg-blue-600 transition-colors ml-0.5">
                            <i class="fa-solid fa-plus text-[7px] text-[#666] group-hover:text-white transition"></i>
                        </div>
                    </div>
                </a>
            @endif
        </div>
        <div class="flex items-center gap-2 pointer-events-auto z-50 flex-wrap">
            <div class="glass-panel px-3.5 py-2 rounded-xl md:flex items-center gap-2 text-[11px] font-medium">
                <i class="fa-regular fa-calendar text-[10px] text-[#555]"></i>
                <span id="current-date" class="text-[#888]">Loading...</span>
            </div>
            @if (Auth::user())
                @if (Auth::check() && Auth::user()->roles[0]->name === 'driver')
                    <a href="{{ route('dashboard') }}">
                        <div class="header-btn glass-panel px-4 h-9 py-2 rounded-xl text-white text-[10px] font-bold cursor-pointer uppercase tracking-wider flex items-center gap-2">
                            <i class="fa-solid fa-gauge-high text-[9px] text-blue-400"></i> <span class="hidden sm:inline">Dashboard</span>
                        </div>
                    </a>
                @endif
                @if (Auth::check() && Auth::user()->roles[0]->name === 'admin')
                    <a href="{{ route('admin.dashboard') }}">
                        <div class="header-btn glass-panel px-4 h-9 py-2 rounded-xl text-white text-[10px] font-bold cursor-pointer uppercase tracking-wider flex items-center gap-2">
                            <i class="fa-solid fa-shield text-[9px] text-purple-400"></i> <span class="hidden sm:inline">Dashboard</span>
                        </div>
                    </a>
                @endif
                <a href="{{ route('profile') }}">
                    <div class="header-btn glass-panel px-4 h-9 py-2 rounded-xl text-white text-[10px] font-bold cursor-pointer uppercase tracking-wider flex items-center gap-2">
                        <i class="fa-solid fa-user text-[9px] text-[#666]"></i><span class="hidden sm:inline">Profile</span>
                    </div>
                </a>
                <button onclick="toggleLogoutModal()" class="header-btn glass-panel px-4 h-9 py-2 rounded-xl text-white text-[10px] font-bold uppercase tracking-wider flex items-center gap-2 hover:!border-red-500/30 hover:!bg-red-500/10">
                    <i class="fa-solid fa-right-from-bracket text-[9px] text-red-400"></i>
                    <span class="hidden sm:inline">Logout</span>
                </button>
            @else
                <a href="{{ route('register') }}">
                    <div class="header-btn glass-panel px-4 py-2 rounded-xl text-white text-[10px] font-bold cursor-pointer uppercase tracking-wider">Sign up</div>
                </a>
                <a href="{{ route('login') }}">
                    <div class="header-btn px-4 py-2 rounded-xl text-black text-[10px] font-bold cursor-pointer uppercase tracking-wider bg-white border border-white hover:bg-gray-200 transition">Log in</div>
                </a>
            @endif
        </div>
    </header>

    <div id="logout-modal" class="modal-backdrop fixed inset-0 z-[100] flex items-center justify-center bg-black/70 opacity-0 pointer-events-none">
        <div class="modal-content bg-[#111] border border-[#222] p-7 sm:p-8 rounded-[2rem] w-full max-w-[360px] mx-4 text-center transform scale-95 opacity-0 shadow-2xl shadow-black/50">
            <div class="w-14 h-14 bg-red-500/10 rounded-2xl flex items-center justify-center mx-auto mb-5 border border-red-500/20">
                <i class="fa-solid fa-right-from-bracket text-red-400 text-lg"></i>
            </div>
            <h3 class="text-lg font-bold text-white mb-1.5">Sign Out?</h3>
            <p class="text-xs text-[#666] mb-7 leading-relaxed">Are you sure you want to log out of SmartCommute?</p>
            <div class="grid gap-2.5">
                <button onclick="toggleLogoutModal()" class="px-5 py-3 rounded-xl bg-[#1a1a1a] border border-[#2a2a2a] text-white text-[10px] font-bold uppercase tracking-widest hover:bg-[#222] transition">Cancel</button>
                <form action="{{ route('users.logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full px-5 py-3 rounded-xl bg-red-600 text-white text-[10px] font-bold uppercase tracking-widest hover:bg-red-700 transition active:scale-[0.98]">Logout</button>
                </form>
            </div>
        </div>
    </div>

    <div id="limit-modal" class="modal-backdrop fixed inset-0 z-[100] flex items-center justify-center bg-black/70 opacity-0 pointer-events-none">
        <div class="modal-content bg-[#111] border border-[#222] p-7 sm:p-8 rounded-[2rem] w-full max-w-[360px] mx-4 text-center transform scale-95 opacity-0 shadow-2xl shadow-black/50">
            <div class="w-14 h-14 bg-amber-500/10 rounded-2xl flex items-center justify-center mx-auto mb-5 border border-amber-500/20">
                <i class="fa-solid fa-hourglass-end text-amber-400 text-lg"></i>
            </div>
            <h3 class="text-lg font-bold text-white mb-1.5">Daily Limit Reached</h3>
            <p class="text-xs text-[#666] mb-7 leading-relaxed">Guests are limited to 3 actions per day. Sign in to continue without limits.</p>
            <div class="grid gap-2.5">
                <button onclick="toggleLimitModal(false)" class="px-5 py-3 rounded-xl bg-[#1a1a1a] border border-[#2a2a2a] text-white text-[10px] font-bold uppercase tracking-widest hover:bg-[#222] transition">Maybe Later</button>
                <a href="/login" class="block px-5 py-3 rounded-xl bg-amber-500 text-white text-[10px] font-bold uppercase tracking-widest hover:bg-amber-600 transition text-center active:scale-[0.98]">Sign In / Register</a>
            </div>
        </div>
    </div>

    <!-- ══════════ MOBILE FAB BUTTONS (stacked on bottom-right) ══════════ -->
    <!-- NEW -->
    @if(Auth::check() && Auth::user()->roles[0]->name !== 'admin' || Auth::guest())
        <div class="fixed bottom-[8.5rem] left-5 z-50 md:hidden">
            <button onclick="openMobileSidebar('left')" class="mobile-fab mobile-fab-left">
                <i class="fa-solid fa-{{ Auth::check() && Auth::user()->roles[0]->name === 'driver' ? 'clock' : 'route' }} text-white text-base"></i>
            </button>
        </div>
    @endif
    @if(Auth::check() && Auth::user()->roles[0]->name !== 'admin' || !Auth::check())
        <div class="fixed bottom-[4.5rem] left-5 z-50 md:hidden">
            <button onclick="openMobileSidebar('right')" class="mobile-fab mobile-fab-right">
                <i class="fa-solid fa-ellipsis-vertical text-white text-base"></i>
            </button>
        </div>
    @endif

    <!-- ══════════ MOBILE LEFT SIDEBAR MODAL (Bottom Sheet) ══════════ -->
    <div id="mobile-left-backdrop" class="mobile-sheet-backdrop md:hidden" onclick="closeMobileSidebar('left')"></div>
    <div id="mobile-left-sheet" class="mobile-sheet md:hidden">
        <div class="mobile-sheet-handle"><div></div></div>
        <!-- NEW -->
        <div class="mobile-sheet-header">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 @if(Auth::check() && Auth::user()->roles[0]->name === 'driver') bg-amber-500/15 @else bg-blue-500/15 @endif rounded-lg flex items-center justify-center">
                    <i class="fa-solid @if(Auth::check() && Auth::user()->roles[0]->name === 'driver') fa-clock text-amber-400 @else fa-money-bill-wave text-blue-400 @endif text-xs"></i>
                </div>
                <h3 class="text-[10px] font-bold uppercase tracking-[0.15em] text-[#666]">@if(Auth::check() && Auth::user()->roles[0]->name === 'driver') Timekeeping @else Fare Calculator @endif</h3>
            </div>
            <button onclick="closeMobileSidebar('left')" class="mobile-sheet-close">
                <i class="fa-solid fa-xmark text-[#555] text-xs"></i>
            </button>
        </div>
        <div id="mobile-left-body" class="mobile-sheet-body custom-scroll"></div>
    </div>

    <!-- ══════════ MOBILE RIGHT SIDEBAR MODAL (Bottom Sheet) ══════════ -->
    <div id="mobile-right-backdrop" class="mobile-sheet-backdrop md:hidden" onclick="closeMobileSidebar('right')"></div>
    <div id="mobile-right-sheet" class="mobile-sheet md:hidden">
        <div class="mobile-sheet-handle"><div></div></div>
        <div class="mobile-sheet-header">
            <h3 class="text-[10px] font-bold uppercase tracking-[0.15em] text-[#666]">Details</h3>
            <button onclick="closeMobileSidebar('right')" class="mobile-sheet-close">
                <i class="fa-solid fa-xmark text-[#555] text-xs"></i>
            </button>
        </div>
        <div id="mobile-right-body" class="mobile-sheet-body custom-scroll"></div>
    </div>

    <div id="map">

        <!-- LEFT SIDEBAR -->
        <div id="left" class="sidebar flex-center left collapsed">
        <div class="sidebar-content flex-center">
            <div id="left-sidebar-anchor"></div>

            @if(Auth::check() && Auth::user()->roles[0]->name === 'commuter' || Auth::guest())
                <div id="left-sidebar-form" class="fixed top-24 left-4 sm:left-2 w-[340px] z-40 hidden md:flex flex-col gap-3 max-h-[calc(100vh-120px)]">
                    <form action="{{ route('payment.index') }}" method="GET">
                        <div class="glass-card p-6 rounded-[1.5rem]">
                            <div class="flex items-center gap-2.5 mb-5">
                                <div class="w-8 h-8 bg-blue-500/15 rounded-lg flex items-center justify-center">
                                    <i class="fa-solid fa-money-bill-wave text-blue-400 text-xs"></i>
                                </div>
                                <h3 class="text-[10px] font-bold uppercase tracking-[0.15em] text-[#666]">Fare Calculator</h3>
                            </div>
                            <div id="status-indicator" class="hidden mb-4 p-3 rounded-xl bg-blue-500/8 border border-blue-500/20 flex items-center gap-2.5">
                                <div class="w-2 h-2 rounded-full bg-blue-500 dot-pulse"></div>
                                <span id="status-text" class="text-[9px] uppercase tracking-[0.15em] text-blue-400 font-bold">Selecting Pick-up...</span>
                            </div>
                            <div class="space-y-3">
                                <div class="flex gap-2 items-center">
                                    <button type="button" onclick="handlePickupBtn()" class="flex items-center justify-center w-10 h-10 bg-blue-500/10 hover:bg-blue-500/20 p-2.5 rounded-xl border border-blue-500/20 hover:border-blue-500/40 transition shrink-0">
                                        <i class="fa-solid fa-circle-dot text-xs text-blue-400"></i>
                                    </button>
                                    <div class="relative flex-1">
                                        <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none">
                                            <i class="fa-solid fa-magnifying-glass text-[10px] text-[#444]"></i>
                                        </div>
                                        <input type="text" placeholder="Search pick-up point" name="pickup" id="pickup" autocomplete="off" class="map-input w-full rounded-xl pl-9 pr-4 py-2.5 text-xs text-white">
                                        <div id="pickup-dropdown" class="search-dropdown"></div>
                                    </div>
                                </div>
                                <div class="flex gap-2 items-center">
                                    <button type="button" onclick="handleDestinationBtn()" class="flex items-center justify-center w-10 h-10 bg-red-500/10 hover:bg-red-500/20 p-2.5 rounded-xl border border-red-500/20 hover:border-red-500/40 transition shrink-0">
                                        <i class="fa-solid fa-location-dot text-xs text-red-400"></i>
                                    </button>
                                    <div class="relative flex-1">
                                        <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none">
                                            <i class="fa-solid fa-magnifying-glass text-[10px] text-[#444]"></i>
                                        </div>
                                        <input type="text" placeholder="Search destination" name="destination" id="destination" autocomplete="off" class="map-input w-full rounded-xl pl-9 pr-4 py-2.5 text-xs text-white">
                                        <div id="destination-dropdown" class="search-dropdown"></div>
                                    </div>
                                </div>
                                <div class="line-glow w-full my-1"></div>
                                <div class="flex items-center justify-between">
                                    <span class="text-[9px] font-bold uppercase tracking-[0.15em] text-[#444]">Distance</span>
                                    <div class="flex items-center gap-1.5">
                                        <input type="text" readonly name="distance" id="distance" class="map-input w-20 text-center rounded-lg px-3 py-2 text-xs text-white font-semibold" value="0">
                                        <span class="text-[10px] font-bold text-[#444] uppercase">km</span>
                                    </div>
                                </div>
                                <div class="bg-[#111] rounded-xl p-3 border border-[#1e1e1e]">
                                    <span class="text-[9px] font-bold uppercase tracking-[0.15em] text-[#444] block mb-2">Regular</span>
                                    <div class="relative flex-1">
                                        <i class="fa-solid fa-peso-sign absolute left-3 top-1/2 -translate-y-1/2 text-[10px] text-[#555]"></i>
                                        <input type="text" readonly name="price-regular" id="price-regular" class="map-input w-full rounded-lg pl-7 pr-3 py-2 text-xs text-white text-center font-semibold" value="0">
                                    </div>
                                </div>
                                <div class="bg-[#111] rounded-xl p-3 border border-[#1e1e1e]">
                                    <span class="text-[9px] font-bold uppercase tracking-[0.15em] text-[#444] block mb-2">Student / Elderly / PWD</span>
                                    <div class="relative flex-1">
                                        <i class="fa-solid fa-peso-sign absolute left-3 top-1/2 -translate-y-1/2 text-[10px] text-[#555]"></i>
                                        <input type="text" readonly name="price-discount" id="price-discount" class="map-input w-full rounded-lg pl-7 pr-3 py-2 text-xs text-white text-center font-semibold" value="0">
                                    </div>
                                </div>
                                <button type="button" onclick="resetForm()" class="flex items-center justify-center gap-2 w-full bg-[#111] hover:bg-red-500/10 text-[#555] hover:text-red-400 font-bold py-2.5 px-4 rounded-xl text-[9px] uppercase tracking-[0.2em] transition-all duration-300 border border-[#1e1e1e] hover:border-red-500/20">
                                    <i class="fa-solid fa-rotate-left text-[8px]"></i> <span>Reset Route</span>
                                </button>
                                <button class="flex items-center justify-center gap-2 w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-3.5 px-5 rounded-xl text-[10px] uppercase tracking-[0.15em] transition-all duration-300 active:scale-[0.98]" type="submit">
                                    <span>Buy a Ride</span> <i class="fa-solid fa-arrow-right text-[9px]"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            @endif

            @if(Auth::check() && Auth::user()->roles[0]->name === 'driver')
                <div id="left-sidebar-form" class="fixed top-24 left-4 sm:left-2 w-[340px] z-40 hidden md:flex flex-col gap-3 max-h-[calc(100vh-120px)]">

                    <!-- Clock In/Out Card -->
                    <div class="glass-card p-5 rounded-[1.5rem] @if($todayRecord && $todayRecord->time_in && !$todayRecord->time_out) border-amber-500/20 @elseif($todayRecord && $todayRecord->time_out) border-emerald-500/20 @else border-blue-500/20 @endif">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <p class="text-[8px] uppercase tracking-[0.15em] text-[#444] font-bold mb-0.5">Today's Shift</p>
                                <h2 class="text-sm font-bold text-white">
                                    @if(!$todayRecord || !$todayRecord->time_in)
                                        Not Started
                                    @elseif(!$todayRecord->time_out)
                                        <span class="text-amber-400">In Progress</span>
                                    @else
                                        <span class="text-emerald-400">Completed</span>
                                    @endif
                                </h2>
                            </div>
                            <div class="w-8 h-8 rounded-lg @if($todayRecord && $todayRecord->time_in && !$todayRecord->time_out) bg-amber-500/10 border border-amber-500/15 @elseif($todayRecord && $todayRecord->time_out) bg-emerald-500/10 border border-emerald-500/15 @else bg-blue-500/10 border border-blue-500/15 @endif flex items-center justify-center">
                                <i class="fa-solid @if($todayRecord && $todayRecord->time_in && !$todayRecord->time_out) fa-clock text-amber-400 @elseif($todayRecord && $todayRecord->time_out) fa-check text-emerald-400 @else fa-hourglass-start text-blue-400 @endif text-xs"></i>
                            </div>
                        </div>

                        @if($todayRecord && $todayRecord->time_in)
                            <div class="grid grid-cols-2 gap-2 mb-4">
                                <div class="p-2.5 rounded-lg bg-[#111] border border-[#1e1e1e]">
                                    <p class="text-[7px] font-bold uppercase tracking-[0.15em] text-[#444] mb-0.5">Time In</p>
                                    <p class="text-[11px] font-bold text-white">{{ \Carbon\Carbon::parse($todayRecord->time_in)->format('h:i A') }}</p>
                                </div>
                                <div class="p-2.5 rounded-lg bg-[#111] border border-[#1e1e1e]">
                                    <p class="text-[7px] font-bold uppercase tracking-[0.15em] text-[#444] mb-0.5">Time Out</p>
                                    <p class="text-[11px] font-bold @if($todayRecord->time_out) text-white @else text-[#333] @endif">
                                        @if($todayRecord->time_out)
                                            {{ \Carbon\Carbon::parse($todayRecord->time_out)->format('h:i A') }}
                                        @else
                                            —
                                        @endif
                                    </p>
                                </div>
                            </div>
                        @endif

                        @if(!$todayRecord || !$todayRecord->time_in)
                            <div class="relative flex justify-center mb-2">
                                <div class="absolute inset-0 bg-blue-500/20 rounded-2xl clock-pulse"></div>
                                <form action="{{ route('driver.timekeeping.clock-in') }}" method="POST" class="relative">
                                    @csrf
                                    <button type="submit" class="w-full p-3.5 rounded-2xl bg-blue-600 hover:bg-blue-500 flex items-center justify-center gap-2.5 transition active:scale-[0.98] shadow-lg shadow-blue-600/20">
                                        <i class="fa-solid fa-right-to-bracket text-sm"></i>
                                        <span class="text-[10px] font-black uppercase tracking-widest">Clock In</span>
                                    </button>
                                </form>
                            </div>
                        @elseif(!$todayRecord->time_out)
                            <div class="relative flex justify-center mb-2">
                                <div class="absolute inset-0 bg-amber-500/20 rounded-2xl clock-pulse"></div>
                                <form action="{{ route('driver.timekeeping.clock-out') }}" method="POST" class="relative">
                                    @csrf
                                    <button type="submit" class="w-full p-3.5 rounded-2xl bg-amber-600 hover:bg-amber-500 flex items-center justify-center gap-2.5 transition active:scale-[0.98] shadow-lg shadow-amber-600/20">
                                        <i class="fa-solid fa-right-from-bracket text-sm"></i>
                                        <span class="text-[10px] font-black uppercase tracking-widest">Clock Out</span>
                                    </button>
                                </form>
                            </div>
                        @else
                            <div class="w-full py-3.5 rounded-2xl bg-emerald-500/10 border border-emerald-500/15 flex items-center justify-center gap-2.5 mb-2">
                                <i class="fa-solid fa-check text-emerald-400 text-sm"></i>
                                <span class="text-[10px] font-black uppercase tracking-widest text-emerald-400">Shift Complete</span>
                            </div>
                        @endif

                        @if($todayRecord && $todayRecord->time_out)
                            <div class="flex items-center justify-center gap-1.5">
                                <span class="text-[8px] text-[#444] uppercase tracking-wider font-bold">Total:</span>
                                <span class="text-[11px] font-bold text-emerald-400">{{ number_format($todayRecord->hours_worked, 1) }} hrs</span>
                                @if($todayRecord->overtime_hours && $todayRecord->overtime_hours > 0)
                                    <span class="text-[8px] text-amber-400 font-bold">+{{ number_format($todayRecord->overtime_hours, 1) }} OT</span>
                                @endif
                            </div>
                        @endif
                    </div>

                    <!-- Link to full timekeeping -->
                    <a href="{{ route('driver.timekeeping') }}" class="glass-card p-4 rounded-xl flex items-center gap-3 group hover:border-blue-500/20 transition">
                        <div class="w-9 h-9 bg-blue-500/10 rounded-xl flex items-center justify-center group-hover:scale-110 transition border border-blue-500/15">
                            <i class="fa-solid fa-calendar-week text-blue-400 text-xs"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-bold text-white">Weekly Log</p>
                            <p class="text-[8px] text-[#444] uppercase tracking-wider font-bold">View full timekeeping</p>
                        </div>
                        <i class="fa-solid fa-chevron-right text-[8px] text-[#333] ml-auto group-hover:text-blue-400 transition"></i>
                    </a>

                </div>
            @endif

            <div class="sidebar-toggle rounded-rect left hidden md:flex" onclick="toggleSidebar('left')">
                <i class="fa-solid fa-chevron-right text-base"></i>
            </div>
        </div>
    </div>

        <!-- RIGHT SIDEBAR -->
        <div id="right" class="sidebar flex-center right collapsed">
            <div class="sidebar-content flex-center">
                <div id="right-sidebar-anchor"></div>
                <div id="right-sidebar-content" class="fixed top-24 right-4 sm:right-2 w-[340px] z-40 hidden md:flex flex-col gap-3 max-h-[calc(100vh-120px)]">

                    @if (Auth::check() && Auth::user()->roles[0]->name === 'commuter')
                        <button onclick="openTutorialModal()" class="glass-card p-4 rounded-xl flex items-center gap-3 group hover:border-yellow-500/20 transition w-full text-left">
                            <div class="w-9 h-9 bg-yellow-500/10 rounded-xl flex items-center justify-center group-hover:scale-110 transition border border-yellow-500/15">
                                <i class="fa-solid fa-wand-magic-sparkles text-yellow-400 text-xs"></i>
                            </div>
                            <div>
                                <p class="text-[11px] font-bold text-white">App Tutorial</p>
                                <p class="text-[8px] text-[#444] uppercase tracking-wider font-bold">New user? Start here</p>
                            </div>
                            <i class="fa-solid fa-chevron-right text-[8px] text-[#333] ml-auto group-hover:text-yellow-400 transition"></i>
                        </button>

                        <!-- ══════════ TUTORIAL MODAL (centered) ══════════ -->
                        <div id="tutorialModalBackdrop" class="tutorial-backdrop" onclick="closeTutorialModal()">
                            <div class="tutorial-backdrop-bg"></div>
                            <div class="tutorial-modal-box" onclick="event.stopPropagation()">
                                <div class="flex items-center justify-between p-5 pb-0">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 bg-yellow-500/10 rounded-lg flex items-center justify-center">
                                            <i class="fa-solid fa-wand-magic-sparkles text-yellow-400 text-xs"></i>
                                        </div>
                                        <div>
                                            <h2 class="text-xs font-bold text-white leading-tight">Quick Start Guide</h2>
                                            <p class="text-[8px] text-[#444] uppercase tracking-wider font-bold">4 easy steps</p>
                                        </div>
                                    </div>
                                    <button onclick="closeTutorialModal()" class="w-7 h-7 rounded-lg bg-[#1a1a1a] hover:bg-[#222] flex items-center justify-center transition">
                                        <i class="fa-solid fa-xmark text-[#555] text-[10px]"></i>
                                    </button>
                                </div>
                                <div class="p-5 space-y-4">
                                    <div class="flex gap-3"><span class="w-6 h-6 rounded-full bg-blue-600/20 flex items-center justify-center text-blue-400 text-[9px] font-bold shrink-0 mt-0.5 border border-blue-500/20">1</span><div><h3 class="font-bold text-[11px] mb-0.5 text-white">Search Your Location</h3><p class="text-[#555] text-[10px] leading-relaxed">Type your starting point in the pick-up field.</p></div></div>
                                    <div class="flex gap-3"><span class="w-6 h-6 rounded-full bg-blue-600/20 flex items-center justify-center text-blue-400 text-[9px] font-bold shrink-0 mt-0.5 border border-blue-500/20">2</span><div><h3 class="font-bold text-[11px] mb-0.5 text-white">Search Destination</h3><p class="text-[#555] text-[10px] leading-relaxed">Type where you want to go in the destination field.</p></div></div>
                                    <div class="flex gap-3"><span class="w-6 h-6 rounded-full bg-blue-600/20 flex items-center justify-center text-blue-400 text-[9px] font-bold shrink-0 mt-0.5 border border-blue-500/20">3</span><div><h3 class="font-bold text-[11px] mb-0.5 text-white">Buy a Ride</h3><p class="text-[#555] text-[10px] leading-relaxed">Review the fare and proceed to payment.</p></div></div>
                                    <div class="flex gap-3"><span class="w-6 h-6 rounded-full bg-blue-600/20 flex items-center justify-center text-blue-400 text-[9px] font-bold shrink-0 mt-0.5 border border-blue-500/20">4</span><div><h3 class="font-bold text-[11px] mb-0.5 text-white">View History</h3><p class="text-[#555] text-[10px] leading-relaxed">Check "Recent Receipts" for past trips.</p></div></div>
                                    <button onclick="closeTutorialModal()" class="mt-2 block w-full text-center text-[#333] hover:text-[#555] text-[9px] font-semibold uppercase tracking-wider transition">Dismiss</button>
                                </div>
                            </div>
                        </div>

                        <div class="glass-card p-5 rounded-[1.5rem] flex flex-col overflow-hidden">
                            <div class="flex justify-between items-center mb-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 bg-[#1a1a1a] rounded-md flex items-center justify-center"><i class="fa-solid fa-receipt text-[9px] text-[#555]"></i></div>
                                    <h3 class="text-[9px] font-bold uppercase tracking-[0.15em] text-[#555]">Recent Receipts</h3>
                                </div>
                                <a href="{{ route('payment.history') }}" class="text-[9px] font-bold text-blue-400 hover:text-blue-300 transition">View All</a>
                            </div>
                            <div class="space-y-2.5 custom-scroll overflow-y-auto pr-1 max-h-[280px]">
                                @if (isset($recentReceipts) && count($recentReceipts) > 0)
                                    @foreach ($recentReceipts as $receipt)
                                        <div class="flex items-center justify-between p-2.5 rounded-xl hover:bg-[#1a1a1a] transition group cursor-default">
                                            <div class="flex items-center gap-2.5">
                                                <div class="w-8 h-8 bg-[#111] rounded-lg flex items-center justify-center border border-[#1e1e1e] group-hover:border-blue-500/20 transition"><i class="fa-solid fa-receipt text-[9px] text-[#444] group-hover:text-blue-400 transition"></i></div>
                                                <div>
                                                    <p class="text-[10px] font-semibold text-[#ccc]">{{ $receipt->transaction_id }}</p>
                                                    <p class="text-[8px] text-[#444] mt-0.5">{{ $receipt->paid_at }}</p>
                                                </div>
                                            </div>
                                            <span class="text-[11px] font-bold text-emerald-400">-₱{{ $receipt->price }}</span>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="flex flex-col items-center justify-center py-8 px-4 border border-dashed border-[#222] rounded-xl">
                                        <div class="w-10 h-10 bg-[#111] rounded-full flex items-center justify-center mb-3"><i class="fa-solid fa-file-invoice text-[#333] text-sm"></i></div>
                                        <p class="text-[10px] font-medium text-[#444] text-center">No receipts yet</p>
                                        <p class="text-[8px] text-[#333] text-center mt-1">New trips will appear here</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    @if (!Auth::check())
                        <div class="glass-card p-5 rounded-[1.5rem]">
                            <div class="flex items-center gap-2.5 mb-4">
                                <div class="w-8 h-8 bg-amber-500/10 rounded-lg flex items-center justify-center"><i class="fa-solid fa-bolt text-amber-400 text-xs"></i></div>
                                <h3 class="text-[9px] font-bold uppercase tracking-[0.15em] text-[#555]">Daily Usage</h3>
                            </div>
                            <div class="space-y-3">
                                <div class="flex justify-between items-end">
                                    <span class="text-[9px] font-bold uppercase tracking-widest text-[#444]">Limit</span>
                                    <span id="usage-text" class="text-[11px] font-bold text-[#aaa] tracking-wider">0 / 3</span>
                                </div>
                                <div class="w-full bg-[#0e0e0e] border border-[#1e1e1e] rounded-full h-2 overflow-hidden p-[1px]">
                                    <div id="usage-bar" class="h-full bg-gradient-to-r from-amber-500 to-orange-400 rounded-full transition-all duration-500" style="width: 0%"></div>
                                </div>
                                <p class="text-[8px] uppercase tracking-[0.12em] text-[#333] text-center">Guests get 3 free fare checks per day</p>
                            </div>
                        </div>
                    @endif

                    @if (Auth::check() && Auth::user()->roles[0]->name === 'driver')

                    <!-- ══════════ ROUTE + GPS CARD ══════════ -->
                    <div class="glass-card p-5 rounded-[1.5rem] border-green-500/15">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <p class="text-[8px] uppercase tracking-[0.15em] text-[#444] font-bold mb-0.5">Active Trip</p>
                                <h2 class="text-sm font-bold text-white">Current Route</h2>
                            </div>
                            <div class="w-8 h-8 bg-green-500/10 rounded-lg flex items-center justify-center border border-green-500/15"><i class="fa-solid fa-route text-green-400 text-xs"></i></div>
                        </div>
                        <div id="live-location-info" class="text-[11px] text-[#777] space-y-2 mb-5">
                            <div class="flex justify-between items-center"><span class="flex items-center gap-1.5"><i class="fa-solid fa-circle-dot text-green-400 text-[8px]"></i> Start</span><span class="font-mono text-[#aaa] text-[10px]">Minglanilla</span></div>
                            <div class="flex justify-between items-center"><span class="flex items-center gap-1.5"><i class="fa-solid fa-location-dot text-red-400 text-[8px]"></i> End</span><span class="font-mono text-[#aaa] text-[10px]">IT Park</span></div>
                        </div>
                        <div class="line-glow w-full mb-4"></div>
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <p class="text-[8px] uppercase tracking-[0.15em] text-[#444] font-bold mb-0.5">Live Status</p>
                                <h2 class="text-sm font-bold text-white">GPS Tracking</h2>
                            </div>
                            <div class="w-8 h-8 bg-blue-500/10 rounded-lg flex items-center justify-center border border-blue-500/15"><i class="fa-solid fa-satellite-dish text-blue-400 text-xs"></i></div>
                        </div>
                        <div id="gps-status" class="tracking-controls-panel text-center">
                            <div class="flex items-center justify-center gap-2 mb-3">
                                <div class="w-2 h-2 bg-[#555] rounded-full dot-pulse" id="gps-indicator"></div>
                                <span class="text-[10px] text-[#555]" id="gps-status-text">GPS: Not active</span>
                            </div>
                            <div id="live-location-info" class="text-[10px] text-[#555] space-y-1.5">
                                <div class="flex justify-between"><span><i class="fa-solid fa-location-dot text-green-400 mr-1 text-[8px]"></i> Position</span><span class="font-mono" id="current-coords">--, --</span></div>
                                <div class="flex justify-between"><span><i class="fa-solid fa-gauge-high text-blue-400 mr-1 text-[8px]"></i> Accuracy</span><span id="current-accuracy">-- m</span></div>
                                <div class="flex justify-between"><span><i class="fa-regular fa-clock text-yellow-400 mr-1 text-[8px]"></i> Updated</span><span id="update-time">--:--:--</span></div>
                            </div>
                        </div>
                        <p class="mt-4 text-[8px] text-[#333] text-center"><i class="fa-solid fa-map-pin mr-0.5"></i> Tap the GPS button on the map to begin tracking</p>
                    </div>

                @endif

                </div>

                @if (Auth::check() && Auth::user()->roles[0]->name !== 'admin')
                    <!-- Arrow button: hidden on mobile, visible on lg+ -->
                    <div class="sidebar-toggle rounded-rect right hidden md:flex" onclick="toggleSidebar('right')"><i class="fa-solid fa-chevron-left text-base"></i></div>
                @endif
                @if (!Auth::check())
                    <div class="sidebar-toggle rounded-rect right hidden md:flex" onclick="toggleSidebar('right')"><i class="fa-solid fa-chevron-left text-base"></i></div>
                @endif

            </div>
        </div>

        <!-- ═══════════════ MOBILE SIDEBAR MODAL LOGIC ═══════════════ -->
        <script>
            window._leftMobileOpen = false;
            window._rightMobileOpen = false;

            var LEFT_DESKTOP_CLASSES = 'fixed top-24 left-4 sm:left-5 w-[340px] z-40 hidden md:flex flex-col gap-3 max-h-[calc(100vh-120px)]';
            var LEFT_MOBILE_CLASSES = 'flex flex-col gap-3 w-full';
            var RIGHT_DESKTOP_CLASSES = 'fixed top-24 right-4 sm:right-5 w-[340px] z-40 hidden md:flex flex-col gap-3 max-h-[calc(100vh-120px)]';
            var RIGHT_MOBILE_CLASSES = 'flex flex-col gap-3 w-full';

            function openMobileSidebar(type) {
                if (type === 'left') {
                    var el = document.getElementById('left-sidebar-form');
                    if (!el) return;
                    document.getElementById('mobile-left-body').appendChild(el);
                    el.className = LEFT_MOBILE_CLASSES;
                    window._leftMobileOpen = true;

                    var backdrop = document.getElementById('mobile-left-backdrop');
                    var sheet = document.getElementById('mobile-left-sheet');
                    backdrop.style.display = 'block';
                    requestAnimationFrame(function() {
                        backdrop.classList.add('visible');
                        sheet.classList.add('open');
                    });
                } else if (type === 'right') {
                    var el = document.getElementById('right-sidebar-content');
                    if (!el) return;
                    document.getElementById('mobile-right-body').appendChild(el);
                    el.className = RIGHT_MOBILE_CLASSES;
                    window._rightMobileOpen = true;

                    var backdrop = document.getElementById('mobile-right-backdrop');
                    var sheet = document.getElementById('mobile-right-sheet');
                    backdrop.style.display = 'block';
                    requestAnimationFrame(function() {
                        backdrop.classList.add('visible');
                        sheet.classList.add('open');
                    });
                }
            }

            function closeMobileSidebar(type) {
                if (type === 'left') {
                    var el = document.getElementById('left-sidebar-form');
                    if (el && window._leftMobileOpen) {
                        document.getElementById('left-sidebar-anchor').after(el);
                        el.className = LEFT_DESKTOP_CLASSES;
                        window._leftMobileOpen = false;
                    }

                    var backdrop = document.getElementById('mobile-left-backdrop');
                    var sheet = document.getElementById('mobile-left-sheet');
                    sheet.classList.remove('open');
                    backdrop.classList.remove('visible');
                    setTimeout(function() { backdrop.style.display = 'none'; }, 350);
                } else if (type === 'right') {
                    var el = document.getElementById('right-sidebar-content');
                    if (el && window._rightMobileOpen) {
                        document.getElementById('right-sidebar-anchor').after(el);
                        el.className = RIGHT_DESKTOP_CLASSES;
                        window._rightMobileOpen = false;
                    }

                    var backdrop = document.getElementById('mobile-right-backdrop');
                    var sheet = document.getElementById('mobile-right-sheet');
                    sheet.classList.remove('open');
                    backdrop.classList.remove('visible');
                    setTimeout(function() { backdrop.style.display = 'none'; }, 350);
                }
            }

            function handlePickupBtn() {
                if (window.innerWidth < 768 && window._leftMobileOpen) {
                    closeMobileSidebar('left');
                }
                if (typeof toggleSelection === 'function') toggleSelection('pickup');
            }

            function handleDestinationBtn() {
                if (window.innerWidth < 768 && window._leftMobileOpen) {
                    closeMobileSidebar('left');
                }
                if (typeof toggleSelection === 'function') toggleSelection('destination');
            }

            function onMapLocationSelected() {
                if (window.innerWidth < 768) {
                    setTimeout(function() {
                        openMobileSidebar('left');
                    }, 300);
                }
            }
        </script>

        <!-- ═══════════════ MAP SCRIPT ═══════════════ -->
        <script type="module">
            const userRole = @json(Auth::user())?.roles[0]?.name ?? 'guest';
            const userId = @json(Auth::user())?.id ?? null;
            const pusherKey = '{{ env("PUSHER_APP_KEY") }}';
            const pusherCluster = '{{ env("PUSHER_APP_CLUSER") }}';
            const DAILY_LIMIT = 3;

            window.Pusher = Pusher;
            window.Echo = new Echo({ broadcaster: 'pusher', key: pusherKey, cluster: pusherCluster, forceTLS: true });

            maplibregl.setRTLTextPlugin('https://unpkg.com/@mapbox/mapbox-gl-rtl-text@0.3.0/dist/mapbox-gl-rtl-text.js');

            const bounds = [
                [123.77516124821591, 10.229235293025951],
                [123.91768276426876, 10.332535160307074]
            ];

            const map = new maplibregl.Map({
                container: 'map',
                style: 'https://tiles.openfreemap.org/styles/bright',
                center: [123.79, 10.24],
                zoom: 13,
                rollEnabled: true,
                maxBounds: bounds
            });

            // ═══════════════ NAV CONTROLS ═══════════════
            map.addControl(new maplibregl.NavigationControl({ visualizePitch: true }), 'bottom-right');

            // ── Share Location (Geolocate) Button ──
            map.addControl(
                new maplibregl.GeolocateControl({
                    positionOptions: {
                        enableHighAccuracy: true
                    },
                    trackUserLocation: true,
                    showUserHeading: true
                }),
                'bottom-right'
            );

            // ═══════════════ MAP STATE ═══════════════
            let userLat = null;
            let userLng = null;
            let vehicleLat = null;
            let vehicleLng = null;
            let selectionMode = null;
            let pickupMarker = null;
            let destinationMarker = null;
            let guestUsage = parseInt(localStorage.getItem('guestUsage') || '0');

            // ═══════════════ DATE DISPLAY ═══════════════
            const dateEl = document.getElementById('current-date');
            if (dateEl) {
                const now = new Date();
                const options = { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' };
                dateEl.textContent = now.toLocaleDateString('en-US', options);
            }

            // ═══════════════ GUEST USAGE ═══════════════
            function updateUsageUI() {
                const bar = document.getElementById('usage-bar');
                const text = document.getElementById('usage-text');
                if (!bar || !text) return;
                text.textContent = guestUsage + ' / ' + DAILY_LIMIT;
                bar.style.width = Math.min((guestUsage / DAILY_LIMIT) * 100, 100) + '%';
                if (guestUsage >= DAILY_LIMIT) {
                    bar.classList.remove('from-amber-500', 'to-orange-400');
                    bar.classList.add('from-red-500', 'to-red-400');
                }
            }
            updateUsageUI();

            // ═══════════════ MODAL TOGGLES ═══════════════
            window.toggleLogoutModal = function () {
                const modal = document.getElementById('logout-modal');
                modal.classList.toggle('active');
                if (!modal.classList.contains('active')) {
                    modal.querySelector('.modal-content').style.transform = 'scale(0.95)';
                    modal.querySelector('.modal-content').style.opacity = '0';
                } else {
                    modal.querySelector('.modal-content').style.transform = 'scale(1)';
                    modal.querySelector('.modal-content').style.opacity = '1';
                }
            };

            window.toggleLimitModal = function (show) {
                const modal = document.getElementById('limit-modal');
                if (show) {
                    modal.classList.add('active');
                    modal.querySelector('.modal-content').style.transform = 'scale(1)';
                    modal.querySelector('.modal-content').style.opacity = '1';
                } else {
                    modal.classList.remove('active');
                    modal.querySelector('.modal-content').style.transform = 'scale(0.95)';
                    modal.querySelector('.modal-content').style.opacity = '0';
                }
            };

            // Tutorial: uses the new centered .tutorial-backdrop
            window.openTutorialModal = function () {
                const backdrop = document.getElementById('tutorialModalBackdrop');
                if (backdrop) backdrop.classList.add('open');
            };

            window.closeTutorialModal = function () {
                const backdrop = document.getElementById('tutorialModalBackdrop');
                if (backdrop) backdrop.classList.remove('open');
            };

            // ═══════════════ SIDEBAR TOGGLE (DESKTOP) ═══════════════
            window.toggleSidebar = function (side) {
                if (window.innerWidth < 768) {
                    openMobileSidebar(side);
                    return;
                }
                const el = document.getElementById(side);
                el.classList.toggle('collapsed');
                const icon = el.querySelector('.sidebar-toggle i');
                if (side === 'left') {
                    icon.className = el.classList.contains('collapsed')
                        ? 'fa-solid fa-chevron-right text-base'
                        : 'fa-solid fa-chevron-left text-base';
                } else {
                    icon.className = el.classList.contains('collapsed')
                        ? 'fa-solid fa-chevron-left text-base'
                        : 'fa-solid fa-chevron-right text-base';
                }
            };

            // ═══════════════ SELECTION MODE ═══════════════
            window.toggleSelection = function (mode) {
                const statusIndicator = document.getElementById('status-indicator');
                const statusText = document.getElementById('status-text');

                if (selectionMode === mode) {
                    selectionMode = null;
                    if (statusIndicator) statusIndicator.classList.add('hidden');
                    map.getCanvas().style.cursor = '';
                    return;
                }

                selectionMode = mode;
                map.getCanvas().style.cursor = 'crosshair';

                if (statusIndicator) {
                    statusIndicator.classList.remove('hidden');
                    if (mode === 'pickup') {
                        statusText.textContent = 'Tap the map to set pick-up point';
                        statusText.className = 'text-[9px] uppercase tracking-[0.15em] text-blue-400 font-bold';
                        statusIndicator.querySelector('.dot-pulse').className = 'w-2 h-2 rounded-full bg-blue-500 dot-pulse';
                        statusIndicator.className = 'mb-4 p-3 rounded-xl bg-blue-500/8 border border-blue-500/20 flex items-center gap-2.5';
                    } else {
                        statusText.textContent = 'Tap the map to set destination';
                        statusText.className = 'text-[9px] uppercase tracking-[0.15em] text-red-400 font-bold';
                        statusIndicator.querySelector('.dot-pulse').className = 'w-2 h-2 rounded-full bg-red-500 dot-pulse';
                        statusIndicator.className = 'mb-4 p-3 rounded-xl bg-red-500/8 border border-red-500/20 flex items-center gap-2.5';
                    }
                }
            };

            // ═══════════════ MAP LAYERS ═══════════════
            map.on('load', function () {
                map.addSource('route', { type: 'geojson', data: { type: 'FeatureCollection', features: [] } });
                map.addLayer({
                    id: 'route-line', type: 'line', source: 'route',
                    layout: { 'line-join': 'round', 'line-cap': 'round' },
                    paint: { 'line-color': '#3b82f6', 'line-width': 4, 'line-opacity': 0.85 }
                });
                map.addLayer({
                    id: 'route-line-glow', type: 'line', source: 'route',
                    layout: { 'line-join': 'round', 'line-cap': 'round' },
                    paint: { 'line-color': '#3b82f6', 'line-width': 12, 'line-opacity': 0.15, 'line-blur': 6 }
                });

                map.addSource('pickup-point', { type: 'geojson', data: { type: 'FeatureCollection', features: [] } });
                map.addLayer({
                    id: 'pickup-circle', type: 'circle', source: 'pickup-point',
                    paint: { 'circle-radius': 7, 'circle-color': '#3b82f6', 'circle-stroke-width': 3, 'circle-stroke-color': '#ffffff' }
                });

                map.addSource('destination-point', { type: 'geojson', data: { type: 'FeatureCollection', features: [] } });
                map.addLayer({
                    id: 'destination-circle', type: 'circle', source: 'destination-point',
                    paint: { 'circle-radius': 7, 'circle-color': '#ef4444', 'circle-stroke-width': 3, 'circle-stroke-color': '#ffffff' }
                });

                map.addSource('vehicles', { type: 'geojson', data: { type: 'FeatureCollection', features: [] } });
                map.addLayer({
                    id: 'vehicle-circles', type: 'circle', source: 'vehicles',
                    paint: {
                        'circle-radius': 6,
                        'circle-color': '#3b82f6',
                        'circle-stroke-width': 2,
                        'circle-stroke-color': '#ffffff'
                    }
                });
            });

            // ═══════════════ MAP CLICK → SET LOCATION ═══════════════
            map.on('click', function (e) {
                if (!selectionMode) return;

                const lng = e.lngLat.lng;
                const lat = e.lngLat.lat;

                if (selectionMode === 'pickup') {
                    userLat = lat;
                    userLng = lng;

                    if (map.getSource('pickup-point')) {
                        map.getSource('pickup-point').setData({
                            type: 'FeatureCollection',
                            features: [{ type: 'Feature', geometry: { type: 'Point', coordinates: [lng, lat] }, properties: {} }]
                        });
                    }

                    reverseGeocode(lng, lat).then(name => {
                        document.getElementById('pickup').value = name;
                    });

                    selectionMode = null;
                    map.getCanvas().style.cursor = '';
                    const statusIndicator = document.getElementById('status-indicator');
                    if (statusIndicator) statusIndicator.classList.add('hidden');

                    calculateRoute();
                    onMapLocationSelected();

                } else if (selectionMode === 'destination') {
                    vehicleLat = lat;
                    vehicleLng = lng;

                    if (map.getSource('destination-point')) {
                        map.getSource('destination-point').setData({
                            type: 'FeatureCollection',
                            features: [{ type: 'Feature', geometry: { type: 'Point', coordinates: [lng, lat] }, properties: {} }]
                        });
                    }

                    reverseGeocode(lng, lat).then(name => {
                        document.getElementById('destination').value = name;
                    });

                    selectionMode = null;
                    map.getCanvas().style.cursor = '';
                    const statusIndicator = document.getElementById('status-indicator');
                    if (statusIndicator) statusIndicator.classList.add('hidden');

                    calculateRoute();
                    onMapLocationSelected();
                }
            });

            // ═══════════════ REVERSE GEOCODE ═══════════════
            async function reverseGeocode(lon, lat) {
                try {
                    const res = await fetch('https://photon.komoot.io/reverse?lon=' + lon + '&lat=' + lat + '&lang=en');
                    const data = await res.json();
                    if (data.features && data.features.length > 0) {
                        const f = data.features[0];
                        const name = f.properties.name || '';
                        const city = f.properties.city || '';
                        const state = f.properties.state || '';
                        const detail = [name, city, state].filter(Boolean).join(', ');
                        return detail || lat.toFixed(5) + ', ' + lon.toFixed(5);
                    }
                } catch (e) {
                    console.error('Reverse geocode error:', e);
                }
                return lat.toFixed(5) + ', ' + lon.toFixed(5);
            }

            // ═══════════════ ROUTE CALCULATION ═══════════════
            window.calculateRoute = async function () {
                if (userLat === null || userLng === null || vehicleLat === null || vehicleLng === null) return;

                if (userRole === 'guest') {
                    if (guestUsage >= DAILY_LIMIT) {
                        toggleLimitModal(true);
                        return;
                    }
                    guestUsage++;
                    localStorage.setItem('guestUsage', guestUsage.toString());
                    updateUsageUI();
                }

                const url = 'https://router.project-osrm.org/route/v1/driving/' +
                    userLng + ',' + userLat + ';' + vehicleLng + ',' + vehicleLat +
                    '?overview=full&geometries=geojson';

                try {
                    const res = await fetch(url);
                    const data = await res.json();

                    if (data.code === 'Ok' && data.routes.length > 0) {
                        const route = data.routes[0];
                        const distKm = (route.distance / 1000).toFixed(1);
                        const baseFare = 13;
                        const perKm = 2.5;
                        const regularFare = Math.ceil(baseFare + (parseFloat(distKm) * perKm));
                        const discountFare = Math.ceil(regularFare * 0.8);

                        document.getElementById('distance').value = distKm;
                        document.getElementById('price-regular').value = regularFare;
                        document.getElementById('price-discount').value = discountFare;

                        if (map.getSource('route')) {
                            map.getSource('route').setData(route.geometry);
                        }

                        const coords = route.geometry.coordinates;
                        if (coords.length > 0) {
                            map.fitBounds(
                                [[coords[0][0], coords[0][1]], [coords[coords.length - 1][0], coords[coords.length - 1][1]]],
                                { padding: { top: 100, bottom: 100, left: 400, right: 100 }, duration: 800 }
                            );
                        }
                    }
                } catch (e) {
                    console.error('Route error:', e);
                }
            };

            // ═══════════════ RESET FORM ═══════════════
            window.resetForm = function () {
                userLat = null;
                userLng = null;
                vehicleLat = null;
                vehicleLng = null;
                selectionMode = null;

                document.getElementById('pickup').value = '';
                document.getElementById('destination').value = '';
                document.getElementById('distance').value = '0';
                document.getElementById('price-regular').value = '0';
                document.getElementById('price-discount').value = '0';

                if (map.getSource('route')) {
                    map.getSource('route').setData({ type: 'FeatureCollection', features: [] });
                }
                if (map.getSource('pickup-point')) {
                    map.getSource('pickup-point').setData({ type: 'FeatureCollection', features: [] });
                }
                if (map.getSource('destination-point')) {
                    map.getSource('destination-point').setData({ type: 'FeatureCollection', features: [] });
                }

                map.getCanvas().style.cursor = '';
                const statusIndicator = document.getElementById('status-indicator');
                if (statusIndicator) statusIndicator.classList.add('hidden');
            };

            // ═══════════════ PLACE SEARCH ═══════════════
            async function searchPlaces(query) {
                if (query.length < 2) return [];
                try {
                    const res = await fetch(
                        'https://photon.komoot.io/api/?q=' + encodeURIComponent(query) +
                        '&bbox=123.775,10.229,123.918,10.333&limit=6&lang=en'
                    );
                    const data = await res.json();
                    return data.features.map(f => ({
                        name: f.properties.name || '',
                        city: f.properties.city || '',
                        state: f.properties.state || '',
                        country: f.properties.country || '',
                        lon: f.geometry.coordinates[0],
                        lat: f.geometry.coordinates[1]
                    }));
                } catch (e) {
                    console.error('Search error:', e);
                    return [];
                }
            }

            function renderResults(results, dropdownId, inputId, field) {
                const dropdown = document.getElementById(dropdownId);
                if (!dropdown) return;

                if (results.length === 0) {
                    dropdown.innerHTML = '<div class="search-no-results">No results found</div>';
                    dropdown.classList.add('active');
                    return;
                }

                dropdown.innerHTML = results.map((r, i) => {
                    const detail = [r.city, r.state, r.country].filter(Boolean).join(', ');
                    return '<div class="search-item" data-index="' + i + '" data-field="' + field + '">' +
                        '<div class="result-name">' + r.name + '</div>' +
                        (detail ? '<div class="result-detail">' + detail + '</div>' : '') +
                        '</div>';
                }).join('');

                dropdown.querySelectorAll('.search-item').forEach(item => {
                    item.addEventListener('mousedown', function (e) {
                        e.preventDefault();
                        const idx = parseInt(this.dataset.index);
                        const r = results[idx];
                        const display = r.name + ([r.city, r.state, r.country].filter(Boolean).join(', ') ? ' — ' + [r.city, r.state, r.country].filter(Boolean).join(', ') : '');

                        document.getElementById(inputId).value = display;

                        if (field === 'pickup') {
                            userLat = r.lat;
                            userLng = r.lon;
                            if (map.getSource('pickup-point')) {
                                map.getSource('pickup-point').setData({
                                    type: 'FeatureCollection',
                                    features: [{ type: 'Feature', geometry: { type: 'Point', coordinates: [r.lon, r.lat] }, properties: {} }]
                                });
                            }
                        } else {
                            vehicleLat = r.lat;
                            vehicleLng = r.lon;
                            if (map.getSource('destination-point')) {
                                map.getSource('destination-point').setData({
                                    type: 'FeatureCollection',
                                    features: [{ type: 'Feature', geometry: { type: 'Point', coordinates: [r.lon, r.lat] }, properties: {} }]
                                });
                            }
                        }

                        dropdown.classList.remove('active');
                        calculateRoute();
                    });
                });

                dropdown.classList.add('active');
            }

            let pickupTimer = null;
            let destinationTimer = null;

            document.getElementById('pickup').addEventListener('input', function () {
                clearTimeout(pickupTimer);
                const val = this.value.trim();
                const dropdown = document.getElementById('pickup-dropdown');

                if (val.length < 2) {
                    dropdown.classList.remove('active');
                    dropdown.innerHTML = '';
                    return;
                }

                dropdown.innerHTML = '<div class="search-loading"><i class="fa-solid fa-spinner fa-spin mr-1"></i> Searching...</div>';
                dropdown.classList.add('active');

                pickupTimer = setTimeout(async () => {
                    const results = await searchPlaces(val);
                    renderResults(results, 'pickup-dropdown', 'pickup', 'pickup');
                }, 300);
            });

            document.getElementById('destination').addEventListener('input', function () {
                clearTimeout(destinationTimer);
                const val = this.value.trim();
                const dropdown = document.getElementById('destination-dropdown');

                if (val.length < 2) {
                    dropdown.classList.remove('active');
                    dropdown.innerHTML = '';
                    return;
                }

                dropdown.innerHTML = '<div class="search-loading"><i class="fa-solid fa-spinner fa-spin mr-1"></i> Searching...</div>';
                dropdown.classList.add('active');

                destinationTimer = setTimeout(async () => {
                    const results = await searchPlaces(val);
                    renderResults(results, 'destination-dropdown', 'destination', 'destination');
                }, 300);
            });

            document.addEventListener('click', function (e) {
                if (!e.target.closest('#pickup') && !e.target.closest('#pickup-dropdown')) {
                    const dd = document.getElementById('pickup-dropdown');
                    if (dd) dd.classList.remove('active');
                }
                if (!e.target.closest('#destination') && !e.target.closest('#destination-dropdown')) {
                    const dd = document.getElementById('destination-dropdown');
                    if (dd) dd.classList.remove('active');
                }
            });

            function setupKeyboardNav(inputId, dropdownId) {
                const input = document.getElementById(inputId);
                const dropdown = document.getElementById(dropdownId);
                if (!input || !dropdown) return;
                let highlighted = -1;

                input.addEventListener('keydown', function (e) {
                    const items = dropdown.querySelectorAll('.search-item');
                    if (!items.length || !dropdown.classList.contains('active')) return;

                    if (e.key === 'ArrowDown') {
                        e.preventDefault();
                        highlighted = Math.min(highlighted + 1, items.length - 1);
                        items.forEach((it, i) => it.classList.toggle('highlighted', i === highlighted));
                        items[highlighted].scrollIntoView({ block: 'nearest' });
                    } else if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        highlighted = Math.max(highlighted - 1, 0);
                        items.forEach((it, i) => it.classList.toggle('highlighted', i === highlighted));
                        items[highlighted].scrollIntoView({ block: 'nearest' });
                    } else if (e.key === 'Enter' && highlighted >= 0) {
                        e.preventDefault();
                        items[highlighted].dispatchEvent(new MouseEvent('mousedown'));
                        highlighted = -1;
                    } else if (e.key === 'Escape') {
                        dropdown.classList.remove('active');
                        highlighted = -1;
                    }
                });

                const observer = new MutationObserver(() => {
                    if (!dropdown.classList.contains('active')) highlighted = -1;
                });
                observer.observe(dropdown, { attributes: true, attributeFilter: ['class'] });
            }

            setupKeyboardNav('pickup', 'pickup-dropdown');
            setupKeyboardNav('destination', 'destination-dropdown');

            // ═══════════════ VEHICLE MARKERS (HTML) ═══════════════
            const vehicleMarkers = {};

            function createVehicleElement() {
                const el = document.createElement('div');
                el.className = 'custom-vehicle-marker bus-pulse';
                el.innerHTML = '<i class="fa-solid fa-bus"></i>';
                return el;
            }

            function updateVehicleMarkers(vehicles) {
                Object.keys(vehicleMarkers).forEach(id => {
                    if (!vehicles.find(v => v.id == id)) {
                        vehicleMarkers[id].remove();
                        delete vehicleMarkers[id];
                    }
                });

                vehicles.forEach(v => {
                    if (v.latitude && v.longitude) {
                        if (vehicleMarkers[v.id]) {
                            vehicleMarkers[v.id].setLngLat([v.longitude, v.latitude]);
                        } else {
                            const el = createVehicleElement();
                            el.addEventListener('click', () => {
                                new maplibregl.Popup({ offset: 20, className: 'vehicle-popup' })
                                    .setLngLat([v.longitude, v.latitude])
                                    .setHTML(
                                        '<div style="background:#111;border:1px solid #222;border-radius:12px;padding:12px 16px;font-family:Inter,sans-serif;min-width:160px;">' +
                                        '<p style="color:#fff;font-size:12px;font-weight:700;margin:0 0 4px;">Bus ' + (v.plate_number || v.id) + '</p>' +
                                        '<p style="color:#666;font-size:10px;margin:0;">Route: ' + (v.route_name || 'N/A') + '</p>' +
                                        '<p style="color:#555;font-size:9px;margin:4px 0 0;">Updated just now</p>' +
                                        '</div>'
                                    )
                                    .addTo(map);
                            });
                            vehicleMarkers[v.id] = new maplibregl.Marker({ element: el, anchor: 'center' })
                                .setLngLat([v.longitude, v.latitude])
                                .addTo(map);
                        }
                    }
                });
            }

            // ═══════════════ REAL-TIME VEHICLE UPDATES (Echo) ═══════════════
            if (window.Echo) {
                window.Echo.channel('vehicles')
                    .listen('.vehicle.location.updated', (e) => {
                        if (e.vehicles) {
                            updateVehicleMarkers(e.vehicles);
                        } else if (e.vehicle) {
                            updateVehicleMarkers([e.vehicle]);
                        }
                    });
            }

            async function fetchVehicles() {
                try {
                    const res = await fetch('/api/vehicles');
                    if (res.ok) {
                        const data = await res.json();
                        if (data.vehicles) updateVehicleMarkers(data.vehicles);
                    }
                } catch (e) {
                    console.log('Vehicle fetch skipped:', e.message);
                }
            }
            fetchVehicles();

            // ═══════════════ DRIVER GPS TRACKING ═══════════════
            let gpsWatchId = null;
            const gpsIndicator = document.getElementById('gps-indicator');
            const gpsStatusText = document.getElementById('gps-status-text');
            const currentCoords = document.getElementById('current-coords');
            const currentAccuracy = document.getElementById('current-accuracy');
            const updateTime = document.getElementById('update-time');

            window.toggleGPSTracking = function () {
                if (gpsWatchId !== null) {
                    navigator.geolocation.clearWatch(gpsWatchId);
                    gpsWatchId = null;
                    if (gpsIndicator) { gpsIndicator.className = 'w-2 h-2 bg-[#555] rounded-full dot-pulse'; }
                    if (gpsStatusText) { gpsStatusText.textContent = 'GPS: Not active'; gpsStatusText.className = 'text-[10px] text-[#555]'; }
                    if (currentCoords) currentCoords.textContent = '--, --';
                    if (currentAccuracy) currentAccuracy.textContent = '-- m';
                    if (updateTime) updateTime.textContent = '--:--:--';
                } else {
                    if (!navigator.geolocation) {
                        alert('Geolocation is not supported by your browser.');
                        return;
                    }
                    if (gpsIndicator) { gpsIndicator.className = 'w-2 h-2 bg-green-500 rounded-full dot-pulse'; }
                    if (gpsStatusText) { gpsStatusText.textContent = 'GPS: Active'; gpsStatusText.className = 'text-[10px] text-green-400 font-semibold'; }

                    gpsWatchId = navigator.geolocation.watchPosition(
                        function (pos) {
                            const lat = pos.coords.latitude.toFixed(6);
                            const lon = pos.coords.longitude.toFixed(6);
                            const acc = pos.coords.accuracy.toFixed(0);
                            const now = new Date();
                            const timeStr = now.toLocaleTimeString('en-US', { hour12: false });

                            if (currentCoords) currentCoords.textContent = lat + ', ' + lon;
                            if (currentAccuracy) currentAccuracy.textContent = acc + ' m';
                            if (updateTime) updateTime.textContent = timeStr;

                            if (userId && userRole === 'driver') {
                                navigator.sendBeacon('/api/driver/location', JSON.stringify({
                                    latitude: pos.coords.latitude,
                                    longitude: pos.coords.longitude,
                                    accuracy: pos.coords.accuracy
                                }));
                            }
                        },
                        function (err) {
                            console.error('GPS error:', err);
                            if (gpsIndicator) { gpsIndicator.className = 'w-2 h-2 bg-red-500 rounded-full'; }
                            if (gpsStatusText) { gpsStatusText.textContent = 'GPS: Error'; gpsStatusText.className = 'text-[10px] text-red-400'; }
                        },
                        { enableHighAccuracy: true, maximumAge: 5000, timeout: 10000 }
                    );
                }
            };

            // ═══════════════ TERRA DRAW ═══════════════
            let terradraw = null;
            let drawActive = false;

            window.toggleDraw = function () {
                if (drawActive) {
                    if (terradraw) { terradraw.stop(); terradraw = null; }
                    drawActive = false;
                } else {
                    try {
                        if (typeof MapLibreTerradraw !== 'undefined') {
                            terradraw = new MapLibreTerradraw({
                                map: map,
                                mode: 'polygon',
                                controls: {
                                    polygon: true,
                                    linestring: true,
                                    point: true,
                                    select: true,
                                    delete: true
                                }
                            });
                            terradraw.start();
                            drawActive = true;
                        }
                    } catch (e) {
                        console.log('Terradraw not available:', e.message);
                    }
                }
            };

            // ═══════════════ MOBILE RESIZE HANDLER ═══════════════
            window.addEventListener('resize', function () {
                if (window.innerWidth >= 768) {
                    if (window._leftMobileOpen) closeMobileSidebar('left');
                    if (window._rightMobileOpen) closeMobileSidebar('right');
                }
            });


        </script>
    </div>
</body>
</html>
