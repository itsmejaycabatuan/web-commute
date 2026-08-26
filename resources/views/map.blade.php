<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartCommute | Live Map</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <link rel='stylesheet' href='https://unpkg.com/maplibre-gl@5.18.0/dist/maplibre-gl.css' />
    <script src="https://unpkg.com/@maplibre/maplibre-gl-directions@latest/dist/maplibre-gl-directions.js"></script>
    <script src='https://unpkg.com/maplibre-gl@5.18.0/dist/maplibre-gl.js'></script>
    <script src="https://unpkg.com/laravel-echo@1.15.3/dist/echo.iife.js"></script>
    <script src="https://js.pusher.com/7.2/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@watergis/maplibre-gl-terradraw@1.0.1/dist/maplibre-gl-terradraw.umd.js">
    </script>

    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/@watergis/maplibre-gl-terradraw@1.0.1/dist/maplibre-gl-terradraw.css" />
    @include('partials.commuter-head-scripts');
    <style>
        body,
        html {
            margin: 0;
            padding: 0;
            height: 100%;
            width: 100%;
            font-family: 'Inter', sans-serif;
            overflow: hidden;
            background: #f1f5f9;
        }

        .dark body,
        .dark html {
            background: #050505;
        }

        #map {
            position: absolute;
            top: 0;
            bottom: 0;
            width: 100%;
            z-index: 0;
        }

        /* ═══ GLASS ═══ */
        .glass {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
        }

        .dark .glass {
            background: #111111;
            border: 1px solid #1e1e1e;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.8);
        }

        .glass-panel {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
        }

        .dark .glass-panel {
            background: #111111;
            border: 1px solid #1e1e1e;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.6);
        }

        .glass-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .dark .glass-card {
            background: #161616;
            border: 1px solid #222222;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.5);
        }

        /* ═══ SCROLLBAR ═══ */
        ::-webkit-scrollbar {
            width: 4px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        .dark ::-webkit-scrollbar-thumb {
            background: #333;
        }

        .dark ::-webkit-scrollbar-thumb:hover {
            background: #444;
        }

        .custom-scroll::-webkit-scrollbar {
            width: 3px;
        }

        .custom-scroll::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        .dark .custom-scroll::-webkit-scrollbar-thumb {
            background: #333;
        }

        /* ═══ INPUT ═══ */
        .map-input {
            background: #f8fafc !important;
            border: 1px solid #e2e8f0 !important;
            color: #0f172a;
            transition: all 0.3s ease;
        }

        .map-input::placeholder {
            color: #94a3b8;
        }

        .map-input:focus {
            border-color: #2563eb !important;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15) !important;
            outline: none;
        }

        .dark .map-input {
            background: #0e0e0e !important;
            border: 1px solid #222222 !important;
            color: #ffffff;
        }

        .dark .map-input::placeholder {
            color: #555;
        }

        /* ═══ VEHICLE MARKER ═══ */
        .custom-vehicle-marker {
            width: 34px;
            height: 34px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            border: 3px solid white;
            border-radius: 50%;
            box-shadow: 0 0 20px rgba(59, 130, 246, 0.6), 0 0 40px rgba(59, 130, 246, 0.2);
            cursor: pointer;
            transition: box-shadow 0.3s ease;
            pointer-events: auto;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .custom-vehicle-marker:hover {
            box-shadow: 0 0 25px rgba(59, 130, 246, 0.8), 0 0 50px rgba(59, 130, 246, 0.3);
        }

        .custom-vehicle-marker i {
            font-size: 14px;
            color: white;
        }

        .bus-pulse {
            animation: pulse-blue-glow 2s ease-in-out infinite;
        }

        @keyframes pulse-blue-glow {

            0%,
            100% {
                box-shadow: 0 0 20px rgba(59, 130, 246, 0.6), 0 0 40px rgba(59, 130, 246, 0.2);
            }

            50% {
                box-shadow: 0 0 30px rgba(59, 130, 246, 0.8), 0 0 60px rgba(59, 130, 246, 0.3), 0 0 0 12px rgba(59, 130, 246, 0);
            }
        }

        /* ═══ SIDEBAR TOGGLE ═══ */
        .rounded-rect {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            color: #64748b;
            transition: all 0.3s ease;
        }

        .rounded-rect:hover {
            color: #2563eb;
            border-color: #2563eb;
            background: #f1f5f9;
        }

        .dark .rounded-rect {
            background: #111111;
            border: 1px solid #1e1e1e;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
            color: #666;
        }

        .dark .rounded-rect:hover {
            color: #60a5fa;
            border-color: #2563eb;
            background: #1a1a1a;
        }

        .flex-center {
            position: absolute;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .flex-center.left {
            left: 0;
        }

        .flex-center.right {
            right: 0;
        }

        .sidebar-content {
            position: absolute;
            width: 95%;
            height: 95%;
        }

        .sidebar-toggle {
            position: absolute;
            width: 2em;
            height: 2em;
            overflow: visible;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 16px;
            font-weight: 300;
        }

        .sidebar-toggle.left {
            right: -2.4em;
        }

        .sidebar-toggle.right {
            left: -2.4em;
        }

        .sidebar {
            transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1);
            z-index: 1;
            width: 360px;
            height: 100%;
        }

        .left.collapsed {
            transform: translateX(-300px);
        }

        .right.collapsed {
            transform: translateX(300px);
        }

        /* ═══ MODALS ═══ */
        .modal-backdrop {
            transition: opacity 0.3s ease;
        }

        .modal-content {
            transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .modal-backdrop.active {
            opacity: 1;
            pointer-events: auto;
        }

        .modal-backdrop.active .modal-content {
            transform: scale(1);
            opacity: 1;
        }

        .header-btn {
            transition: all 0.3s ease;
        }

        .header-btn:hover {
            background: #f1f5f9 !important;
            border-color: #cbd5e1 !important;
        }

        .dark .header-btn:hover {
            background: #1a1a1a !important;
            border-color: #333 !important;
        }

        /* ═══ MAPLIBRE CONTROLS ═══ */
        .maplibregl-ctrl-group {
            background: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 14px !important;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08) !important;
            overflow: hidden;
        }

        .maplibregl-ctrl-group button {
            width: 40px !important;
            height: 40px !important;
            border-bottom: 1px solid #e2e8f0 !important;
            transition: background 0.2s ease;
        }

        .maplibregl-ctrl-group button:hover {
            background: #f1f5f9 !important;
        }

        .maplibregl-ctrl-group button span {
            opacity: 0.4;
        }

        .maplibregl-ctrl-group button:hover span {
            opacity: 0.9;
        }

        .dark .maplibregl-ctrl-group {
            background: #111111 !important;
            border: 1px solid #1e1e1e !important;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5) !important;
        }

        .dark .maplibregl-ctrl-group button {
            border-bottom: 1px solid #1a1a1a !important;
        }

        .dark .maplibregl-ctrl-group button:hover {
            background: #1a1a1a !important;
        }

        .dark .maplibregl-ctrl-group button span {
            filter: invert(1) opacity(0.5);
        }

        .dark .maplibregl-ctrl-group button:hover span {
            filter: invert(1) opacity(0.9);
        }

        .line-glow {
            background: #e2e8f0;
            height: 1px;
        }

        .dark .line-glow {
            background: #222;
        }

        @keyframes nav-slide-up {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .mobile-nav-animate {
            animation: nav-slide-up 0.5s cubic-bezier(0.16, 1, 0.3, 1) 0.3s both;
        }

        @keyframes dot-pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.4;
            }
        }

        .dot-pulse {
            animation: dot-pulse 2s ease-in-out infinite;
        }

        /* ═══ SEARCH DROPDOWN ═══ */
        .search-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            margin-top: 4px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 9999;
            display: none;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.1);
        }

        .dark .search-dropdown {
            background: #111;
            border: 1px solid #222;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.6);
        }

        .search-dropdown.active {
            display: block;
        }

        .search-item {
            padding: 10px 14px;
            cursor: pointer;
            transition: background 0.15s ease;
            border-bottom: 1px solid #f1f5f9;
        }

        .dark .search-item {
            border-bottom: 1px solid #1a1a1a;
        }

        .search-item:last-child {
            border-bottom: none;
        }

        .search-item:hover,
        .search-item.highlighted {
            background: #f1f5f9;
        }

        .dark .search-item:hover,
        .dark .search-item.highlighted {
            background: #1a1a1a;
        }

        .search-item .result-name {
            color: #0f172a;
            font-size: 12px;
            font-weight: 600;
        }

        .dark .search-item .result-name {
            color: #ddd;
        }

        .search-item .result-detail {
            color: #94a3b8;
            font-size: 10px;
            margin-top: 2px;
        }

        .dark .search-item .result-detail {
            color: #555;
        }

        .search-loading {
            padding: 14px;
            text-align: center;
            color: #94a3b8;
            font-size: 11px;
        }

        .dark .search-loading {
            color: #555;
        }

        .search-no-results {
            padding: 14px;
            text-align: center;
            color: #cbd5e1;
            font-size: 11px;
        }

        .dark .search-no-results {
            color: #444;
        }

        /* ═══ MOBILE SHEET ═══ */
        .mobile-sheet-backdrop {
            position: fixed;
            inset: 0;
            z-index: 90;
            background: rgba(0, 0, 0, 0.4);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }

        .dark .mobile-sheet-backdrop {
            background: rgba(0, 0, 0, 0.6);
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
            background: #ffffff;
            border-top: 1px solid #e2e8f0;
            border-radius: 1.5rem 1.5rem 0 0;
            max-height: 88vh;
            overflow: hidden;
            transform: translateY(100%);
            transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1);
            display: flex;
            flex-direction: column;
        }

        .dark .mobile-sheet {
            background: #0a0a0a;
            border-top: 1px solid #1e1e1e;
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
            background: #cbd5e1;
            border-radius: 9999px;
        }

        .dark .mobile-sheet-handle div {
            background: #333;
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
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
            transition: background 0.2s;
        }

        .mobile-sheet-close:hover {
            background: #e2e8f0;
        }

        .dark .mobile-sheet-close {
            background: #1a1a1a;
        }

        .dark .mobile-sheet-close:hover {
            background: #222;
        }

        .mobile-sheet-body {
            padding: 0 20px 32px;
            overflow-y: auto;
            flex: 1;
        }

        /* ═══ MOBILE FAB ═══ */
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

        .mobile-fab:active {
            transform: scale(0.92);
        }

        .mobile-fab-left {
            background: #2563eb;
            box-shadow: 0 4px 20px rgba(37, 99, 235, 0.35);
        }

        .mobile-fab-right {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            color: #334155;
        }

        .dark .mobile-fab-right {
            background: #111;
            border: 1px solid #1e1e1e;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
            color: white;
        }

        /* ═══ TUTORIAL MODAL ═══ */
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
            background: rgba(0, 0, 0, 0.4);
        }

        .dark .tutorial-backdrop-bg {
            background: rgba(0, 0, 0, 0.7);
        }

        .tutorial-modal-box {
            position: relative;
            z-index: 1;
            width: 340px;
            max-width: calc(100vw - 2rem);
            max-height: calc(100vh - 4rem);
            overflow-y: auto;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1.5rem;
            box-shadow: 0 24px 64px rgba(0, 0, 0, 0.1);
            transform: scale(0.95);
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .dark .tutorial-modal-box {
            background: #111;
            border: 1px solid #222;
            box-shadow: 0 24px 64px rgba(0, 0, 0, 0.5);
        }

        .tutorial-backdrop.open .tutorial-modal-box {
            transform: scale(1);
            opacity: 1;
        }

        @keyframes pulse-ring {
            0% {
                transform: scale(1);
                opacity: 0.4;
            }

            100% {
                transform: scale(1.6);
                opacity: 0;
            }
        }

        .clock-pulse {
            animation: pulse-ring 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        .dev-place-btn.placing {
            background: rgba(147, 51, 234, 0.15) !important;
            border-color: rgba(147, 51, 234, 0.4) !important;
            color: #a78bfa !important;
        }

        /* ═══ THEME TOGGLE BUTTON ═══ */
        .theme-toggle-btn {
            position: relative;
            width: 36px;
            height: 36px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: transparent;
            border: none;
        }

        .theme-toggle-btn:hover {
            background: #f1f5f9 !important;
        }

        .dark .theme-toggle-btn:hover {
            background: #1a1a1a !important;
        }

        .theme-toggle-btn .icon-sun,
        .theme-toggle-btn .icon-moon {
            position: absolute;
            transition: all 0.3s ease;
        }

        .dark .theme-toggle-btn .icon-sun {
            opacity: 0;
            transform: rotate(90deg) scale(0.5);
        }

        .dark .theme-toggle-btn .icon-moon {
            opacity: 1;
            transform: rotate(0deg) scale(1);
        }

        .theme-toggle-btn .icon-sun {
            opacity: 1;
            transform: rotate(0deg) scale(1);
        }

        .theme-toggle-btn .icon-moon {
            opacity: 0;
            transform: rotate(-90deg) scale(0.5);
        }

        /* ═══ STATUS TOGGLE ═══ */
        .status-toggle-track {
            width: 44px;
            height: 24px;
            border-radius: 12px;
            background: #cbd5e1;
            position: relative;
            transition: background 0.3s ease;
            cursor: pointer;
        }

        .status-toggle-track.active {
            background: #10b981;
        }

        .dark .status-toggle-track {
            background: #222;
        }

        .status-toggle-thumb {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: white;
            position: absolute;
            top: 3px;
            left: 3px;
            transition: transform 0.3s ease;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        .status-toggle-track.active .status-toggle-thumb {
            transform: translateX(20px);
        }

        .status-dot-active {
            animation: dot-pulse-active 2s ease-in-out infinite;
        }

        @keyframes dot-pulse-active {

            0%,
            100% {
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4);
            }

            50% {
                box-shadow: 0 0 0 4px rgba(16, 185, 129, 0);
            }
        }

        /* ═══════════════════════════════════════════════════════════
   CATCH HARDCODED TAILWIND — TWO-PASS APPROACH
   Pass 1: Override text-white → dark (body text)
   Pass 2: Restore text-white → white (on colored backgrounds)
   ═══════════════════════════════════════════════════════════ */

        /* — PASS 1: text-white → dark in light mode — */
        .glass-panel .text-white,
        .glass-card .text-white,
        .glass .text-white,
        .modal-content .text-white,
        .tutorial-modal-box .text-white,
        .mobile-sheet .text-white {
            color: #0f172a !important;
        }

        .dark .glass-panel .text-white,
        .dark .glass-card .text-white,
        .dark .glass .text-white,
        .dark .modal-content .text-white,
        .dark .tutorial-modal-box .text-white,
        .dark .mobile-sheet .text-white {
            color: #ffffff !important;
        }

        /* — PASS 2: restore white on colored backgrounds — */
        .glass-panel [class*="bg-blue-"] .text-white,
        .glass-card [class*="bg-blue-"] .text-white,
        .glass [class*="bg-blue-"] .text-white,
        .modal-content [class*="bg-blue-"] .text-white,
        .tutorial-modal-box [class*="bg-blue-"] .text-white,
        .mobile-sheet [class*="bg-blue-"] .text-white,
        .glass-panel [class*="bg-red-"] .text-white,
        .glass-card [class*="bg-red-"] .text-white,
        .glass [class*="bg-red-"] .text-white,
        .modal-content [class*="bg-red-"] .text-white,
        .tutorial-modal-box [class*="bg-red-"] .text-white,
        .mobile-sheet [class*="bg-red-"] .text-white,
        .glass-panel [class*="bg-amber-"] .text-white,
        .glass-card [class*="bg-amber-"] .text-white,
        .glass [class*="bg-amber-"] .text-white,
        .modal-content [class*="bg-amber-"] .text-white,
        .tutorial-modal-box [class*="bg-amber-"] .text-white,
        .mobile-sheet [class*="bg-amber-"] .text-white,
        .glass-panel [class*="bg-emerald-"] .text-white,
        .glass-card [class*="bg-emerald-"] .text-white,
        .glass [class*="bg-emerald-"] .text-white,
        .modal-content [class*="bg-emerald-"] .text-white,
        .tutorial-modal-box [class*="bg-emerald-"] .text-white,
        .mobile-sheet [class*="bg-emerald-"] .text-white,
        .glass-panel [class*="bg-purple-"] .text-white,
        .glass-card [class*="bg-purple-"] .text-white,
        .glass [class*="bg-purple-"] .text-white,
        .modal-content [class*="bg-purple-"] .text-white,
        .tutorial-modal-box [class*="bg-purple-"] .text-white,
        .mobile-sheet [class*="bg-purple-"] .text-white,
        .glass-panel [class*="bg-green-"] .text-white,
        .glass-card [class*="bg-green-"] .text-white,
        .glass [class*="bg-green-"] .text-white,
        .modal-content [class*="bg-green-"] .text-white,
        .tutorial-modal-box [class*="bg-green-"] .text-white,
        .mobile-sheet [class*="bg-green-"] .text-white,
        .glass-panel [class*="bg-yellow-"] .text-white,
        .glass-card [class*="bg-yellow-"] .text-white,
        .glass [class*="bg-yellow-"] .text-white,
        .modal-content [class*="bg-yellow-"] .text-white,
        .tutorial-modal-box [class*="bg-yellow-"] .text-white,
        .mobile-sheet [class*="bg-yellow-"] .text-white,
        .glass-panel [class*="bg-pink-"] .text-white,
        .glass-card [class*="bg-pink-"] .text-white,
        .glass [class*="bg-pink-"] .text-white,
        .modal-content [class*="bg-pink-"] .text-white,
        .tutorial-modal-box [class*="bg-pink-"] .text-white,
        .mobile-sheet [class*="bg-pink-"] .text-white,
        .glass-panel [class*="bg-orange-"] .text-white,
        .glass-card [class*="bg-orange-"] .text-white,
        .glass [class*="bg-orange-"] .text-white,
        .modal-content [class*="bg-orange-"] .text-white,
        .tutorial-modal-box [class*="bg-orange-"] .text-white,
        .mobile-sheet [class*="bg-orange-"] .text-white,
        .glass-panel [class*="bg-indigo-"] .text-white,
        .glass-card [class*="bg-indigo-"] .text-white,
        .glass [class*="bg-indigo-"] .text-white,
        .modal-content [class*="bg-indigo-"] .text-white,
        .tutorial-modal-box [class*="bg-indigo-"] .text-white,
        .mobile-sheet [class*="bg-indigo-"] .text-white,
        /* Element itself has colored bg */
        .glass-panel .text-white[class*="bg-blue-"],
        .glass-card .text-white[class*="bg-blue-"],
        .glass .text-white[class*="bg-blue-"],
        .modal-content .text-white[class*="bg-blue-"],
        .tutorial-modal-box .text-white[class*="bg-blue-"],
        .mobile-sheet .text-white[class*="bg-blue-"],
        .glass-panel .text-white[class*="bg-red-"],
        .glass-card .text-white[class*="bg-red-"],
        .glass .text-white[class*="bg-red-"],
        .modal-content .text-white[class*="bg-red-"],
        .tutorial-modal-box .text-white[class*="bg-red-"],
        .mobile-sheet .text-white[class*="bg-red-"],
        .glass-panel .text-white[class*="bg-amber-"],
        .glass-card .text-white[class*="bg-amber-"],
        .glass .text-white[class*="bg-amber-"],
        .modal-content .text-white[class*="bg-amber-"],
        .tutorial-modal-box .text-white[class*="bg-amber-"],
        .mobile-sheet .text-white[class*="bg-amber-"],
        .glass-panel .text-white[class*="bg-emerald-"],
        .glass-card .text-white[class*="bg-emerald-"],
        .glass .text-white[class*="bg-emerald-"],
        .modal-content .text-white[class*="bg-emerald-"],
        .tutorial-modal-box .text-white[class*="bg-emerald-"],
        .mobile-sheet .text-white[class*="bg-emerald-"],
        .glass-panel .text-white[class*="bg-purple-"],
        .glass-card .text-white[class*="bg-purple-"],
        .glass .text-white[class*="bg-purple-"],
        .modal-content .text-white[class*="bg-purple-"],
        .tutorial-modal-box .text-white[class*="bg-purple-"],
        .mobile-sheet .text-white[class*="bg-purple-"],
        .glass-panel .text-white[class*="bg-green-"],
        .glass-card .text-white[class*="bg-green-"],
        .glass .text-white[class*="bg-green-"],
        .modal-content .text-white[class*="bg-green-"],
        .tutorial-modal-box .text-white[class*="bg-green-"],
        .mobile-sheet .text-white[class*="bg-green-"],
        .glass-panel .text-white[class*="bg-yellow-"],
        .glass-card .text-white[class*="bg-yellow-"],
        .glass .text-white[class*="bg-yellow-"],
        .modal-content .text-white[class*="bg-yellow-"],
        .tutorial-modal-box .text-white[class*="bg-yellow-"],
        .mobile-sheet .text-white[class*="bg-yellow-"] {
            color: #ffffff !important;
        }

        /* ═══ TEXT-[#XXX] CATCHES ═══ */
        .dark .glass-panel .text-\[\#888\],
        .dark .glass-card .text-\[\#888\],
        .dark .mobile-sheet .text-\[\#888\] {
            color: #888 !important;
        }

        .glass-panel .text-\[\#888\],
        .glass-card .text-\[\#888\],
        .mobile-sheet .text-\[\#888\] {
            color: #64748b !important;
        }

        .dark .glass-panel .text-\[\#666\],
        .dark .glass-card .text-\[\#666\],
        .dark .modal-content .text-\[\#666\],
        .dark .tutorial-modal-box .text-\[\#666\] {
            color: #666 !important;
        }

        .glass-panel .text-\[\#666\],
        .glass-card .text-\[\#666\],
        .modal-content .text-\[\#666\],
        .tutorial-modal-box .text-\[\#666\] {
            color: #64748b !important;
        }

        .dark .glass-panel .text-\[\#555\],
        .dark .glass-card .text-\[\#555\],
        .dark .modal-content .text-\[\#555\],
        .dark .tutorial-modal-box .text-\[\#555\],
        .dark .mobile-sheet .text-\[\#555\] {
            color: #555 !important;
        }

        .glass-panel .text-\[\#555\],
        .glass-card .text-\[\#555\],
        .modal-content .text-\[\#555\],
        .tutorial-modal-box .text-\[\#555\],
        .mobile-sheet .text-\[\#555\] {
            color: #94a3b8 !important;
        }

        .dark .glass-panel .text-\[\#444\],
        .dark .glass-card .text-\[\#444\],
        .dark .modal-content .text-\[\#444\],
        .dark .tutorial-modal-box .text-\[\#444\],
        .dark .mobile-sheet .text-\[\#444\] {
            color: #444 !important;
        }

        .glass-panel .text-\[\#444\],
        .glass-card .text-\[\#444\],
        .modal-content .text-\[\#444\],
        .tutorial-modal-box .text-\[\#444\],
        .mobile-sheet .text-\[\#444\] {
            color: #94a3b8 !important;
        }

        .dark .glass-panel .text-\[\#333\],
        .dark .glass-card .text-\[\#333\],
        .dark .modal-content .text-\[\#333\],
        .dark .tutorial-modal-box .text-\[\#333\] {
            color: #333 !important;
        }

        .glass-panel .text-\[\#333\],
        .glass-card .text-\[\#333\],
        .modal-content .text-\[\#333\],
        .tutorial-modal-box .text-\[\#333\] {
            color: #cbd5e1 !important;
        }

        .dark .glass-panel .text-\[\#222\],
        .dark .glass-card .text-\[\#222\],
        .dark .mobile-sheet .text-\[\#222\] {
            color: #222 !important;
        }

        .glass-panel .text-\[\#222\],
        .glass-card .text-\[\#222\],
        .mobile-sheet .text-\[\#222\] {
            color: #e2e8f0 !important;
        }

        .dark .glass-panel .text-\[\#bbb\],
        .dark .glass-card .text-\[\#bbb\] {
            color: #bbb !important;
        }

        .glass-panel .text-\[\#bbb\],
        .glass-card .text-\[\#bbb\] {
            color: #334155 !important;
        }

        .dark .glass-panel .text-\[\#ccc\],
        .dark .glass-card .text-\[\#ccc\] {
            color: #ccc !important;
        }

        .glass-panel .text-\[\#ccc\],
        .glass-card .text-\[\#ccc\] {
            color: #334155 !important;
        }

        .dark .glass-panel .text-\[\#ddd\],
        .dark .glass-card .text-\[\#ddd\] {
            color: #ddd !important;
        }

        .glass-panel .text-\[\#ddd\],
        .glass-card .text-\[\#ddd\] {
            color: #0f172a !important;
        }

        /* ═══ BG-[#XXX] CATCHES ═══ */
        .dark .glass-panel .bg-\[\#111\],
        .dark .glass-card .bg-\[\#111\],
        .dark .modal-content .bg-\[\#111\],
        .dark .tutorial-modal-box .bg-\[\#111\],
        .dark .mobile-sheet .bg-\[\#111\] {
            background: #111 !important;
        }

        .glass-panel .bg-\[\#111\],
        .glass-card .bg-\[\#111\],
        .modal-content .bg-\[\#111\],
        .tutorial-modal-box .bg-\[\#111\],
        .mobile-sheet .bg-\[\#111\] {
            background: #f8fafc !important;
        }

        .dark .glass-panel .bg-\[\#1a1a1a\],
        .dark .glass-card .bg-\[\#1a1a1a\],
        .dark .modal-content .bg-\[\#1a1a1a\],
        .dark .tutorial-modal-box .bg-\[\#1a1a1a\] {
            background: #1a1a1a !important;
        }

        .glass-panel .bg-\[\#1a1a1a\],
        .glass-card .bg-\[\#1a1a1a\],
        .modal-content .bg-\[\#1a1a1a\],
        .tutorial-modal-box .bg-\[\#1a1a1a\] {
            background: #f1f5f9 !important;
        }

        .dark .glass-card .bg-\[\#0a0a0a\] {
            background: #0a0a0a !important;
        }

        .glass-card .bg-\[\#0a0a0a\] {
            background: #f8fafc !important;
        }

        /* ═══ BORDER-[#XXX] CATCHES ═══ */
        .dark .glass-panel .border-\[\#1e1e1e\],
        .dark .glass-card .border-\[\#1e1e1e\],
        .dark .modal-content .border-\[\#1e1e1e\],
        .dark .tutorial-modal-box .border-\[\#1e1e1e\],
        .dark .mobile-sheet .border-\[\#1e1e1e\] {
            border-color: #1e1e1e !important;
        }

        .glass-panel .border-\[\#1e1e1e\],
        .glass-card .border-\[\#1e1e1e\],
        .modal-content .border-\[\#1e1e1e\],
        .tutorial-modal-box .border-\[\#1e1e1e\],
        .mobile-sheet .border-\[\#1e1e1e\] {
            border-color: #e2e8f0 !important;
        }

        .dark .glass-panel .border-\[\#222\],
        .dark .glass-card .border-\[\#222\],
        .dark .modal-content .border-\[\#222\],
        .dark .tutorial-modal-box .border-\[\#222\] {
            border-color: #222 !important;
        }

        .glass-panel .border-\[\#222\],
        .glass-card .border-\[\#222\],
        .modal-content .border-\[\#222\],
        .tutorial-modal-box .border-\[\#222\] {
            border-color: #e2e8f0 !important;
        }

        .dark .glass-panel .border-\[\#2a2a2a\],
        .dark .glass-card .border-\[\#2a2a2a\],
        .dark .modal-content .border-\[\#2a2a2a\] {
            border-color: #2a2a2a !important;
        }

        .glass-panel .border-\[\#2a2a2a\],
        .glass-card .border-\[\#2a2a2a\],
        .modal-content .border-\[\#2a2a2a\] {
            border-color: #cbd5e1 !important;
        }

        .dark .glass-panel .border-\[\#1a1a1a\],
        .dark .glass-card .border-\[\#1a1a1a\] {
            border-color: #1a1a1a !important;
        }

        .glass-panel .border-\[\#1a1a1a\],
        .glass-card .border-\[\#1a1a1a\] {
            border-color: #e2e8f0 !important;
        }

        /* ═══ PRESERVE ACCENT COLORS ═══ */
        .text-blue-400 {
            color: #3b82f6 !important;
        }

        .text-emerald-400 {
            color: #10b981 !important;
        }

        .text-red-400 {
            color: #f87171 !important;
        }

        .text-amber-400 {
            color: #f59e0b !important;
        }

        .text-purple-400 {
            color: #a78bfa !important;
        }

        .text-yellow-400 {
            color: #facc15 !important;
        }

        /* ═══ FIX: Same-element class combos (no space = self, not descendant) ═══ */

        /* text-white on the glass element itself */
        .glass-panel.text-white {
            color: #0f172a !important;
        }

        .glass-card.text-white {
            color: #0f172a !important;
        }

        .glass.text-white {
            color: #0f172a !important;
        }

        .dark .glass-panel.text-white {
            color: #ffffff !important;
        }

        .dark .glass-card.text-white {
            color: #ffffff !important;
        }

        .dark .glass.text-white {
            color: #ffffff !important;
        }

        /* text-[#xxx] on the glass element itself */
        .glass-panel.text-\[\#888\] {
            color: #64748b !important;
        }

        .glass-panel.text-\[\#666\] {
            color: #64748b !important;
        }

        .glass-panel.text-\[\#555\] {
            color: #94a3b8 !important;
        }

        .glass-panel.text-\[\#444\] {
            color: #94a3b8 !important;
        }

        .dark .glass-panel.text-\[\#888\] {
            color: #888 !important;
        }

        .dark .glass-panel.text-\[\#666\] {
            color: #666 !important;
        }

        .dark .glass-panel.text-\[\#555\] {
            color: #555 !important;
        }

        .dark .glass-panel.text-\[\#444\] {
            color: #444 !important;
        }

        /* bg-[#xxx] on the glass element itself */
        .glass-card.bg-\[\#111\] {
            background: #f8fafc !important;
        }

        .glass-card.bg-\[\#1a1a1a\] {
            background: #f1f5f9 !important;
        }

        .glass-card.bg-\[\#0a0a0a\] {
            background: #f8fafc !important;
        }

        .dark .glass-card.bg-\[\#111\] {
            background: #111 !important;
        }

        .dark .glass-card.bg-\[\#1a1a1a\] {
            background: #1a1a1a !important;
        }

        .dark .glass-card.bg-\[\#0a0a0a\] {
            background: #0a0a0a !important;
        }

        /* border-[#xxx] on the glass element itself */
        .glass-card.border-\[\#1e1e1e\] {
            border-color: #e2e8f0 !important;
        }

        .glass-card.border-\[\#222\] {
            border-color: #e2e8f0 !important;
        }

        .glass-card.border-\[\#1a1a1a\] {
            border-color: #e2e8f0 !important;
        }

        .dark .glass-card.border-\[\#1e1e1e\] {
            border-color: #1e1e1e !important;
        }

        .dark .glass-card.border-\[\#222\] {
            border-color: #222 !important;
        }

        .dark .glass-card.border-\[\#1a1a1a\] {
            border-color: #1a1a1a !important;
        }

        /* modal-content self-referencing */
        .modal-content.bg-\[\#111\] {
            background: #ffffff !important;
        }

        .modal-content.border-\[\#222\] {
            border-color: #e2e8f0 !important;
        }

        .dark .modal-content.bg-\[\#111\] {
            background: #111 !important;
        }

        .dark .modal-content.border-\[\#222\] {
            border-color: #222 !important;
        }

        /* Theme toggle — solid bg in both modes */
        .theme-toggle-btn {
            background: #f8fafc !important;
            border: 1px solid #e2e8f0;
        }

        .theme-toggle-btn:hover {
            background: #f1f5f9 !important;
        }

        .dark .theme-toggle-btn {
            background: #111111 !important;
            border: 1px solid #1e1e1e;
        }

        .dark .theme-toggle-btn:hover {
            background: #1a1a1a !important;
        }

        /* Log in button — blue in light, white in dark */
        .header-btn.bg-white {
            background: #2563eb !important;
            border-color: #2563eb !important;
            color: #ffffff !important;
        }

        .header-btn.bg-white:hover {
            background: #1d4ed8 !important;
        }

        .dark .header-btn.bg-white {
            background: #ffffff !important;
            border-color: #ffffff !important;
            color: #000000 !important;
        }

        .dark .header-btn.bg-white:hover {
            background: #e2e8f0 !important;
        }

        .dark .header-btn.border-white {
            border-color: #ffffff !important;
        }
    </style>
</head>

<body class="antialiased">

    @include('components.flash')

    <header
        class="fixed top-4 left-4 right-4 sm:top-5 sm:left-5 sm:right-5 z-50 flex flex-col sm:flex-row justify-between items-center sm:items-center gap-3 pointer-events-none">
        <div class="glass-panel p-3 sm:p-3.5 rounded-2xl pointer-events-auto flex items-center gap-3">
            <div class="w-9 h-9 bg-blue-600 rounded-xl flex items-center justify-center">
                <i class="fa-solid fa-bus text-white text-sm"></i>
            </div>
            <span class="text-sm font-bold tracking-tight text-white">Smart<span
                    class="text-blue-400">Commute</span></span>
            @if (Auth::check() && Auth::user()->roles[0]->name === 'commuter' && isset($balance))
                <div class="w-px h-6 bg-[#222] mx-1"></div>
                <a href="{{ route('payment.topup') }}" class="group">
                    <div
                        class="flex items-center gap-2.5 py-1.5 px-3 rounded-xl hover:bg-slate-100 dark:hover:bg-[#1a1a1a] transition-all cursor-pointer">
                        <div
                            class="w-7 h-7 bg-emerald-500/15 rounded-lg flex items-center justify-center border border-emerald-500/20">
                            <i class="fa-solid fa-wallet text-emerald-400 text-[10px]"></i>
                        </div>
                        <div class="flex flex-col">
                            <span
                                class="text-[7px] uppercase tracking-[0.15em] text-[#555] font-bold leading-none">Balance</span>
                            <span
                                class="text-white font-bold text-[11px] leading-tight mt-0.5">₱{{ $balance }}</span>
                        </div>
                        <div
                            class="w-5 h-5 rounded-md bg-[#1a1a1a] flex items-center justify-center group-hover:bg-blue-600 transition-colors ml-0.5">
                            <i
                                class="fa-solid fa-plus text-[7px] text-slate-400 dark:text-[#666] group-hover:text-black dark:group-hover:text-white transition"></i>
                        </div>
                    </div>
                </a>
            @endif
        </div>
        <div class="flex items-center gap-2 pointer-events-auto z-50 flex-wrap">

            <button class="theme-toggle-btn glass-panel" onclick="toggleMapTheme()" title="Toggle theme">
                <i class="fa-solid fa-sun text-amber-500 text-[11px] icon-sun"></i>
                <i class="fa-solid fa-moon text-blue-400 text-[11px] icon-moon"></i>
            </button>

            <div class="glass-panel px-3.5 py-2 rounded-xl md:flex items-center gap-2 text-[11px] font-medium">
                <i class="fa-regular fa-calendar text-[10px] text-[#555]"></i>
                <span id="current-date" class="text-[#888]">Loading...</span>
            </div>
            @if (Auth::user())
                @if (Auth::check() && in_array(Auth::user()->roles[0]->name, ['driver', 'maintenance_manager', 'driver_manager']))
                    <a href="{{ route('dashboard') }}">
                        <div
                            class="header-btn glass-panel px-4 h-9 py-2 rounded-xl text-white text-[10px] font-bold cursor-pointer uppercase tracking-wider flex items-center gap-2">
                            <i class="fa-solid fa-gauge-high text-[9px] text-blue-400"></i> <span
                                class="hidden sm:inline">Dashboard</span>
                        </div>
                    </a>
                @endif
                @if (Auth::check() && Auth::user()->roles[0]->name === 'admin')
                    <a href="{{ route('dashboard') }}">
                        <div
                            class="header-btn glass-panel px-4 h-9 py-2 rounded-xl text-white text-[10px] font-bold cursor-pointer uppercase tracking-wider flex items-center gap-2">
                            <i class="fa-solid fa-shield text-[9px] text-purple-400"></i> <span
                                class="hidden sm:inline">Dashboard</span>
                        </div>
                    </a>
                @endif
                <a href="{{ route('profile') }}">
                    <div
                        class="header-btn glass-panel px-4 h-9 py-2 rounded-xl text-white text-[10px] font-bold cursor-pointer uppercase tracking-wider flex items-center gap-2">
                        <i class="fa-solid fa-user text-[9px] text-[#666]"></i><span
                            class="hidden sm:inline">Profile</span>
                    </div>
                </a>
                <button onclick="toggleLogoutModal()"
                    class="header-btn glass-panel px-4 h-9 py-2 rounded-xl text-white text-[10px] font-bold uppercase tracking-wider flex items-center gap-2 hover:!border-red-500/30 hover:!bg-red-500/10">
                    <i class="fa-solid fa-right-from-bracket text-[9px] text-red-400"></i>
                    <span class="hidden sm:inline">Logout</span>
                </button>
            @else
                <a href="{{ route('register') }}">
                    <div
                        class="header-btn glass-panel px-4 py-2 rounded-xl text-white text-[10px] font-bold cursor-pointer uppercase tracking-wider">
                        Sign up</div>
                </a>
                <a href="{{ route('login') }}">
                    <div
                        class="header-btn px-4 py-2 rounded-xl text-black text-[10px] font-bold cursor-pointer uppercase tracking-wider bg-white border border-white hover:bg-gray-200 transition">
                        Log in</div>
                </a>
            @endif
        </div>
    </header>

    <div id="logout-modal"
        class="modal-backdrop fixed inset-0 z-[100] flex items-center justify-center bg-black/70 opacity-0 pointer-events-none">
        <div
            class="modal-content bg-[#111] border border-[#222] p-7 sm:p-8 rounded-[2rem] w-full max-w-[360px] mx-4 text-center transform scale-95 opacity-0 shadow-2xl shadow-black/50">
            <div
                class="w-14 h-14 bg-red-500/10 rounded-2xl flex items-center justify-center mx-auto mb-5 border border-red-500/20">
                <i class="fa-solid fa-right-from-bracket text-red-400 text-lg"></i>
            </div>
            <h3 class="text-lg font-bold text-white mb-1.5">Sign Out?</h3>
            <p class="text-xs text-[#666] mb-7 leading-relaxed">Are you sure you want to log out of SmartCommute?</p>
            <div class="grid gap-2.5">
                <button onclick="toggleLogoutModal()"
                    class="px-5 py-3 rounded-xl bg-[#1a1a1a] border border-[#2a2a2a] text-white text-[10px] font-bold uppercase tracking-widest hover:bg-[#222] transition">Cancel</button>
                <form action="{{ route('users.logout') }}" method="POST">
                    @csrf
                    <button type="submit"
                        class="w-full px-5 py-3 rounded-xl bg-red-600 text-white text-[10px] font-bold uppercase tracking-widest hover:bg-red-700 transition active:scale-[0.98]">Logout</button>
                </form>
            </div>
        </div>
    </div>

    <div id="limit-modal"
        class="modal-backdrop fixed inset-0 z-[100] flex items-center justify-center bg-black/70 opacity-0 pointer-events-none">
        <div
            class="modal-content bg-[#111] border border-[#222] p-7 sm:p-8 rounded-[2rem] w-full max-w-[360px] mx-4 text-center transform scale-95 opacity-0 shadow-2xl shadow-black/50">
            <div
                class="w-14 h-14 bg-amber-500/10 rounded-2xl flex items-center justify-center mx-auto mb-5 border border-amber-500/20">
                <i class="fa-solid fa-hourglass-end text-amber-400 text-lg"></i>
            </div>
            <h3 class="text-lg font-bold text-white mb-1.5">Daily Limit Reached</h3>
            <p class="text-xs text-[#666] mb-7 leading-relaxed">Guests are limited to 3 actions per day. Sign in to
                continue without limits.</p>
            <div class="grid gap-2.5">
                <button onclick="toggleLimitModal(false)"
                    class="px-5 py-3 rounded-xl bg-[#1a1a1a] border border-[#2a2a2a] text-white text-[10px] font-bold uppercase tracking-widest hover:bg-[#222] transition">Maybe
                    Later</button>
                <a href="/login"
                    class="block px-5 py-3 rounded-xl bg-amber-500 text-white text-[10px] font-bold uppercase tracking-widest hover:bg-amber-600 transition text-center active:scale-[0.98]">Sign
                    In / Register</a>
            </div>
        </div>
    </div>

    <!-- ══════════ MOBILE FAB BUTTONS (stacked on bottom-right) ══════════ -->
    <!-- NEW -->
    @if ((Auth::check() && Auth::user()->roles[0]->name !== 'admin') || Auth::guest())
        <div class="fixed bottom-[8.5rem] left-5 z-50 md:hidden">
            <button onclick="openMobileSidebar('left')" class="mobile-fab mobile-fab-left">
                <i
                    class="fa-solid fa-{{ Auth::check() && Auth::user()->roles[0]->name === 'driver' ? 'clock' : 'route' }} text-white text-base"></i>
            </button>
        </div>
    @endif
    @if ((Auth::check() && Auth::user()->roles[0]->name !== 'admin') || !Auth::check())
        <div class="fixed bottom-[4.5rem] left-5 z-50 md:hidden">
            <button onclick="openMobileSidebar('right')" class="mobile-fab mobile-fab-right">
                <i class="fa-solid fa-ellipsis-vertical text-white text-base"></i>
            </button>
        </div>
    @endif

    <!-- ══════════ MOBILE LEFT SIDEBAR MODAL (Bottom Sheet) ══════════ -->
    <div id="mobile-left-backdrop" class="mobile-sheet-backdrop md:hidden" onclick="closeMobileSidebar('left')">
    </div>
    <div id="mobile-left-sheet" class="mobile-sheet md:hidden">
        <div class="mobile-sheet-handle">
            <div></div>
        </div>
        <!-- NEW -->
        <div class="mobile-sheet-header">
            <div class="flex items-center gap-2.5">
                <div
                    class="w-8 h-8 @if (Auth::check() && Auth::user()->roles[0]->name === 'driver') bg-amber-500/15 @else bg-blue-500/15 @endif rounded-lg flex items-center justify-center">
                    <i
                        class="fa-solid @if (Auth::check() && Auth::user()->roles[0]->name === 'driver') fa-clock text-amber-400 @else fa-money-bill-wave text-blue-400 @endif text-xs"></i>
                </div>
                <h3 class="text-[10px] font-bold uppercase tracking-[0.15em] text-[#666]">
                    @if (Auth::check() && Auth::user()->roles[0]->name === 'driver')
                        Timekeeping
                    @else
                        Fare Calculator
                    @endif
                </h3>
            </div>
            <button onclick="closeMobileSidebar('left')" class="mobile-sheet-close">
                <i class="fa-solid fa-xmark text-[#555] text-xs"></i>
            </button>
        </div>
        <div id="mobile-left-body" class="mobile-sheet-body custom-scroll"></div>
    </div>

    <!-- ══════════ MOBILE RIGHT SIDEBAR MODAL (Bottom Sheet) ══════════ -->
    <div id="mobile-right-backdrop" class="mobile-sheet-backdrop md:hidden" onclick="closeMobileSidebar('right')">
    </div>
    <div id="mobile-right-sheet" class="mobile-sheet md:hidden">
        <div class="mobile-sheet-handle">
            <div></div>
        </div>
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
        @if (Auth::guest() || (Auth::check() && Auth::user()->roles[0]->name !== 'admin'))
            <div id="left" class="sidebar flex-center left collapsed">
                <div class="sidebar-content flex-center">
                    <div id="left-sidebar-anchor"></div>

                    @if ((Auth::check() && Auth::user()->roles[0]->name === 'commuter') || Auth::guest())
                        <div id="left-sidebar-form"
                            class="fixed top-24 left-4 sm:left-2 w-[340px] z-40 hidden md:flex flex-col gap-3 max-h-[calc(100vh-120px)] overflow-y-auto custom-scroll p-3 pb-6">
                            <form action="{{ route('payment.index') }}" method="GET">
                                <div class="glass-card p-6 rounded-[1.5rem]">
                                    <div class="flex items-center gap-2.5 mb-5">
                                        <div
                                            class="w-8 h-8 bg-blue-500/15 rounded-lg flex items-center justify-center">
                                            <i class="fa-solid fa-money-bill-wave text-blue-400 text-xs"></i>
                                        </div>
                                        <h3 class="text-[10px] font-bold uppercase tracking-[0.15em] text-[#666]">Fare
                                            Calculator</h3>
                                    </div>
                                    <div id="status-indicator"
                                        class="hidden mb-4 p-3 rounded-xl bg-blue-500/8 border border-blue-500/20 flex items-center gap-2.5">
                                        <div class="w-2 h-2 rounded-full bg-blue-500 dot-pulse"></div>
                                        <span id="status-text"
                                            class="text-[9px] uppercase tracking-[0.15em] text-blue-400 font-bold">Selecting
                                            Pick-up...</span>
                                    </div>
                                    <div class="space-y-3">
                                        <div class="flex gap-2 items-center">
                                            <button type="button" onclick="handlePickupBtn()"
                                                class="flex items-center justify-center w-10 h-10 bg-blue-500/10 hover:bg-blue-500/20 p-2.5 rounded-xl border border-blue-500/20 hover:border-blue-500/40 transition shrink-0">
                                                <i class="fa-solid fa-circle-dot text-xs text-blue-400"></i>
                                            </button>
                                            <div class="relative flex-1">
                                                <div
                                                    class="absolute inset-y-0 left-3 flex items-center pointer-events-none">
                                                    <i
                                                        class="fa-solid fa-magnifying-glass text-[10px] text-[#444]"></i>
                                                </div>
                                                <input type="text" placeholder="Search pick-up point"
                                                    name="pickup" id="pickup" autocomplete="off"
                                                    class="map-input w-full rounded-xl pl-9 pr-4 py-2.5 text-xs text-white">
                                                <div id="pickup-dropdown" class="search-dropdown"></div>
                                            </div>
                                        </div>
                                        <div class="flex gap-2 items-center">
                                            <button type="button" onclick="handleDestinationBtn()"
                                                class="flex items-center justify-center w-10 h-10 bg-red-500/10 hover:bg-red-500/20 p-2.5 rounded-xl border border-red-500/20 hover:border-red-500/40 transition shrink-0">
                                                <i class="fa-solid fa-location-dot text-xs text-red-400"></i>
                                            </button>
                                            <div class="relative flex-1">
                                                <div
                                                    class="absolute inset-y-0 left-3 flex items-center pointer-events-none">
                                                    <i
                                                        class="fa-solid fa-magnifying-glass text-[10px] text-[#444]"></i>
                                                </div>
                                                <input type="text" placeholder="Search destination"
                                                    name="destination" id="destination" autocomplete="off"
                                                    class="map-input w-full rounded-xl pl-9 pr-4 py-2.5 text-xs text-white">
                                                <div id="destination-dropdown" class="search-dropdown"></div>
                                            </div>
                                        </div>
                                        <div class="line-glow w-full my-1"></div>
                                        <div class="flex items-center justify-between">
                                            <span
                                                class="text-[9px] font-bold uppercase tracking-[0.15em] text-[#444]">Distance</span>
                                            <div class="flex items-center gap-1.5">
                                                <input type="text" readonly name="distance" id="distance"
                                                    class="map-input w-20 text-center rounded-lg px-3 py-2 text-xs text-white font-semibold"
                                                    value="0">
                                                <span class="text-[10px] font-bold text-[#444] uppercase">km</span>
                                            </div>
                                        </div>
                                        <div class="bg-[#111] rounded-xl p-3 border border-[#1e1e1e]">
                                            <span
                                                class="text-[9px] font-bold uppercase tracking-[0.15em] text-[#444] block mb-2">Regular</span>
                                            <div class="relative flex-1">
                                                <i
                                                    class="fa-solid fa-peso-sign absolute left-3 top-1/2 -translate-y-1/2 text-[10px] text-[#555]"></i>
                                                <input type="text" readonly name="price-regular"
                                                    id="price-regular"
                                                    class="map-input w-full rounded-lg pl-7 pr-3 py-2 text-xs text-white text-center font-semibold"
                                                    value="0">
                                            </div>
                                        </div>
                                        <div class="bg-[#111] rounded-xl p-3 border border-[#1e1e1e]">
                                            <span
                                                class="text-[9px] font-bold uppercase tracking-[0.15em] text-[#444] block mb-2">Student
                                                / Elderly / PWD</span>
                                            <div class="relative flex-1">
                                                <i
                                                    class="fa-solid fa-peso-sign absolute left-3 top-1/2 -translate-y-1/2 text-[10px] text-[#555]"></i>
                                                <input type="text" readonly name="price-discount"
                                                    id="price-discount"
                                                    class="map-input w-full rounded-lg pl-7 pr-3 py-2 text-xs text-white text-center font-semibold"
                                                    value="0">
                                            </div>
                                        </div>
                                        <button type="button" onclick="resetForm()"
                                            class="flex items-center justify-center gap-2 w-full bg-[#111] hover:bg-red-500/10 text-[#555] hover:text-red-400 font-bold py-2.5 px-4 rounded-xl text-[9px] uppercase tracking-[0.2em] transition-all duration-300 border border-[#1e1e1e] hover:border-red-500/20">
                                            <i class="fa-solid fa-rotate-left text-[8px]"></i> <span>Reset Route</span>
                                        </button>
                                        <button
                                            class="flex items-center justify-center gap-2 w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-3.5 px-5 rounded-xl text-[10px] uppercase tracking-[0.15em] transition-all duration-300 active:scale-[0.98]"
                                            type="submit">
                                            <span>Buy a Ride</span> <i class="fa-solid fa-arrow-right text-[9px]"></i>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    @endif

                    @if (Auth::check() && Auth::user()->roles[0]->name === 'driver')
                        <div id="left-sidebar-form"
                            class="fixed top-24 left-4 sm:left-2 w-[340px] z-40 hidden md:flex flex-col gap-3 max-h-[calc(100vh-120px)] overflow-y-auto custom-scroll p-3 pb-6">

                            <!-- ══════════ DRIVER STATUS TOGGLE CARD ══════════ -->
                            <form action="{{ route('driver.status.update') }}" method="POST" class="contents">
                                @csrf
                                <input type="hidden" name="status"
                                    value="{{ $driverStatus === 'active' ? 'inactive' : 'active' }}">
                                <div id="driver-status-card"
                                    class="glass-card status-card {{ $driverStatus === 'active' ? 'active' : 'inactive' }} p-5 rounded-[1.5rem] cursor-pointer select-none"
                                    onclick="this.closest('form').submit()">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="w-10 h-10 rounded-xl flex items-center justify-center {{ $driverStatus === 'active' ? 'bg-emerald-500/10 border border-emerald-500/20' : 'bg-[#1a1a1a] border border-[#222]' }}">
                                                <div class="relative flex items-center justify-center">
                                                    <i
                                                        class="fa-solid fa-signal {{ $driverStatus === 'active' ? 'text-emerald-400' : 'text-[#444]' }} text-sm"></i>
                                                    <div
                                                        class="absolute -top-0.5 -right-0.5 w-2.5 h-2.5 rounded-full border-2 border-[#161616] {{ $driverStatus === 'active' ? 'bg-emerald-400 status-dot-active' : 'bg-[#444]' }}">
                                                    </div>
                                                </div>
                                            </div>
                                            <div>
                                                <p
                                                    class="text-[8px] uppercase tracking-[0.15em] text-[#444] font-bold mb-0.5">
                                                    Availability</p>
                                                <p
                                                    class="text-[13px] font-bold {{ $driverStatus === 'active' ? 'text-emerald-400' : 'text-[#555]' }}">
                                                    {{ $driverStatus === 'active' ? 'Active' : 'Inactive' }}</p>
                                            </div>
                                        </div>
                                        <div
                                            class="status-toggle-track {{ $driverStatus === 'active' ? 'active' : '' }}">
                                            <div class="status-toggle-thumb"></div>
                                        </div>
                                    </div>
                                    <p class="text-[9px] text-[#444] mt-3 leading-relaxed">
                                        @if ($driverStatus === 'active')
                                            You are visible to commuters and accepting trips.
                                        @else
                                            Tap to go online and start accepting trips.
                                        @endif
                                    </p>
                                </div>
                            </form>

                            <div
                                class="glass-card p-5 rounded-[1.5rem] @if ($todayRecord && $todayRecord->time_in && !$todayRecord->time_out) border-amber-500/20 @elseif($todayRecord && $todayRecord->time_out) border-emerald-500/20 @else border-blue-500/20 @endif">
                                <div class="flex items-center justify-between mb-4">
                                    <div>
                                        <p class="text-[8px] uppercase tracking-[0.15em] text-[#444] font-bold mb-0.5">
                                            Today's Shift</p>
                                        <h2 class="text-sm font-bold text-white">
                                            @if (!$todayRecord || !$todayRecord->time_in)
                                                Not Started
                                            @elseif(!$todayRecord->time_out)
                                                <span class="text-amber-400">In Progress</span>
                                            @else
                                                <span class="text-emerald-400">Completed</span>
                                            @endif
                                        </h2>
                                    </div>
                                    <div
                                        class="w-8 h-8 rounded-lg @if ($todayRecord && $todayRecord->time_in && !$todayRecord->time_out) bg-amber-500/10 border border-amber-500/15 @elseif($todayRecord && $todayRecord->time_out) bg-emerald-500/10 border border-emerald-500/15 @else bg-blue-500/10 border border-blue-500/15 @endif flex items-center justify-center">
                                        <i
                                            class="fa-solid @if ($todayRecord && $todayRecord->time_in && !$todayRecord->time_out) fa-clock text-amber-400 @elseif($todayRecord && $todayRecord->time_out) fa-check text-emerald-400 @else fa-hourglass-start text-blue-400 @endif text-xs"></i>
                                    </div>
                                </div>

                                @if ($todayRecord && $todayRecord->time_in)
                                    <div class="grid grid-cols-2 gap-2 mb-4">
                                        <div class="p-2.5 rounded-lg bg-[#111] border border-[#1e1e1e]">
                                            <p
                                                class="text-[7px] font-bold uppercase tracking-[0.15em] text-[#444] mb-0.5">
                                                Time In</p>
                                            <p class="text-[11px] font-bold text-white">{{ $todayRecord->time_in }}
                                            </p>
                                        </div>
                                        <div class="p-2.5 rounded-lg bg-[#111] border border-[#1e1e1e]">
                                            <p
                                                class="text-[7px] font-bold uppercase tracking-[0.15em] text-[#444] mb-0.5">
                                                Time Out</p>
                                            <p
                                                class="text-[11px] font-bold @if ($todayRecord->time_out) text-white @else text-[#333] @endif">
                                                @if ($todayRecord->time_out)
                                                    {{ $todayRecord->time_out }}
                                                @else
                                                    —
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                @endif

                                @if (!$todayRecord || !$todayRecord->time_in)
                                    <form action="{{ route('driver.timekeeping.clock-in') }}" method="POST">
                                        @csrf
                                        <button type="submit"
                                            class="w-full p-3.5 rounded-2xl bg-blue-600 hover:bg-blue-500 flex items-center justify-center gap-2.5 transition active:scale-[0.98] btn-glow-blue">
                                            <i class="fa-solid fa-right-to-bracket text-sm"></i>
                                            <span class="text-[10px] font-black uppercase tracking-widest">Clock
                                                In</span>
                                        </button>
                                    </form>
                                @elseif(!$todayRecord->time_out)
                                    <form action="{{ route('driver.timekeeping.clock-out') }}" method="POST">
                                        @csrf
                                        <button type="submit"
                                            class="w-full p-3.5 rounded-2xl bg-amber-600 hover:bg-amber-500 flex items-center justify-center gap-2.5 transition active:scale-[0.98] btn-glow-amber">
                                            <i class="fa-solid fa-right-from-bracket text-sm"></i>
                                            <span class="text-[10px] font-black uppercase tracking-widest">Clock
                                                Out</span>
                                        </button>
                                    </form>
                                @else
                                    <div
                                        class="w-full py-3.5 rounded-2xl bg-emerald-500/10 border border-emerald-500/15 flex items-center justify-center gap-2.5">
                                        <i class="fa-solid fa-check text-emerald-400 text-sm"></i>
                                        <span
                                            class="text-[10px] font-black uppercase tracking-widest text-emerald-400">Shift
                                            Complete</span>
                                    </div>
                                @endif

                                @if ($todayRecord && $todayRecord->time_out)
                                    <div class="flex items-center justify-center gap-1.5 mt-2">
                                        <span
                                            class="text-[8px] text-[#444] uppercase tracking-wider font-bold">Total:</span>
                                        <span
                                            class="text-[11px] font-bold text-emerald-400">{{ number_format($todayRecord->hours_worked, 1) }}
                                            hrs</span>
                                        @if ($todayRecord->overtime_hours && $todayRecord->overtime_hours > 0)
                                            <span
                                                class="text-[8px] text-amber-400 font-bold">+{{ number_format($todayRecord->overtime_hours, 1) }}
                                                OT</span>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            <!-- Link to full timekeeping -->
                            <a href="{{ route('driver.timekeeping') }}"
                                class="glass-card p-4 rounded-xl flex items-center gap-3 group hover:border-blue-500/20 transition">
                                <div
                                    class="w-9 h-9 bg-blue-500/10 rounded-xl flex items-center justify-center group-hover:scale-110 transition border border-blue-500/15">
                                    <i class="fa-solid fa-calendar-week text-blue-400 text-xs"></i>
                                </div>
                                <div>
                                    <p class="text-[11px] font-bold text-white">Weekly Log</p>
                                    <p class="text-[8px] text-[#444] uppercase tracking-wider font-bold">View full
                                        timekeeping</p>
                                </div>
                                <i
                                    class="fa-solid fa-chevron-right text-[8px] text-[#333] ml-auto group-hover:text-blue-400 transition"></i>
                            </a>

                            @env('local')
                                <!-- ══════════ DEV TOOLS: DUMMY DRIVER MARKERS ══════════ -->
                                <div class="glass-card p-5 rounded-[1.5rem] border-purple-500/15">
                                    <div class="flex items-center justify-between mb-4">
                                        <div class="flex items-center gap-2.5">
                                            <div
                                                class="w-8 h-8 bg-purple-500/10 rounded-lg flex items-center justify-center border border-purple-500/20">
                                                <i class="fa-solid fa-flask text-purple-400 text-xs"></i>
                                            </div>
                                            <div>
                                                <h3
                                                    class="text-[10px] font-bold uppercase tracking-[0.15em] text-purple-400">
                                                    Dev Tools</h3>
                                                <p class="text-[7px] text-[#333] uppercase tracking-wider font-bold">Local
                                                    env only</p>
                                            </div>
                                        </div>
                                        <span
                                            class="text-[7px] font-bold uppercase tracking-widest text-[#333] bg-[#111] px-2 py-1 rounded-md border border-[#1e1e1e]">{{ isset($dummyMarkers) ? $dummyMarkers->count() : 0 }}
                                            markers</span>
                                    </div>

                                    <!-- Add marker form -->
                                    <form action="{{ route('driver.dev.add-marker') }}" method="POST"
                                        id="dev-marker-form">
                                        @csrf
                                        <input type="hidden" name="lat" id="dev-marker-lat">
                                        <input type="hidden" name="lng" id="dev-marker-lng">
                                        <div class="flex gap-2 mb-3">
                                            <button type="submit" onclick="captureMapCenter()"
                                                class="flex-1 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 flex items-center justify-center gap-2 transition active:scale-[0.98] text-[9px] font-bold uppercase tracking-widest">
                                                <i class="fa-solid fa-location-crosshairs text-[8px]"></i>
                                                <span>Add at Center</span>
                                            </button>
                                            <button type="button" onclick="enableMarkerPlacement()" id="dev-place-btn"
                                                class="dev-place-btn py-2.5 px-3 rounded-xl bg-[#111] hover:bg-[#1a1a1a] border border-[#222] hover:border-purple-500/30 flex items-center justify-center gap-2 transition active:scale-[0.98] text-[9px] font-bold uppercase tracking-widest text-[#666] hover:text-purple-400">
                                                <i class="fa-solid fa-map-pin text-[8px]"></i>
                                                <span>Pin</span>
                                            </button>
                                        </div>
                                    </form>

                                    <!-- Marker list -->
                                    <div class="space-y-1.5 max-h-[220px] overflow-y-auto custom-scroll pr-0.5">
                                        @if (isset($dummyMarkers) && $dummyMarkers->count() > 0)
                                            @foreach ($dummyMarkers as $marker)
                                                <div
                                                    class="flex items-center justify-between p-2.5 rounded-xl bg-[#111] border border-[#1e1e1e] group hover:border-[#2a2a2a] transition">
                                                    <div class="flex items-center gap-2.5 min-w-0">
                                                        <div
                                                            class="w-7 h-7 rounded-lg {{ $marker->status === 'active' ? 'bg-emerald-500/10 border border-emerald-500/15' : 'bg-[#1a1a1a] border border-[#222]' }} flex items-center justify-center shrink-0">
                                                            <i
                                                                class="fa-solid fa-bus text-[9px] {{ $marker->status === 'active' ? 'text-emerald-400' : 'text-[#444]' }}"></i>
                                                        </div>
                                                        <div class="min-w-0">
                                                            <p class="text-[10px] font-semibold text-[#bbb] truncate">
                                                                {{ $marker->name }}</p>
                                                            <div class="flex items-center gap-1.5 mt-0.5">
                                                                <div
                                                                    class="w-1.5 h-1.5 rounded-full {{ $marker->status === 'active' ? 'bg-emerald-400' : 'bg-[#444]' }}">
                                                                </div>
                                                                <span
                                                                    class="text-[8px] font-bold uppercase tracking-wider {{ $marker->status === 'active' ? 'text-emerald-400/70' : 'text-[#444]' }}">{{ $marker->status }}</span>
                                                                <span
                                                                    class="text-[8px] text-[#333] font-mono ml-1">{{ number_format($marker->lat, 4) }},
                                                                    {{ number_format($marker->lng, 4) }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div
                                                        class="flex items-center gap-1 shrink-0 opacity-0 group-hover:opacity-100 transition">
                                                        <form
                                                            action="{{ route('driver.dev.toggle-marker', $marker->id) }}"
                                                            method="POST">
                                                            @csrf
                                                            <button type="submit"
                                                                class="w-6 h-6 rounded-md bg-[#1a1a1a] hover:bg-{{ $marker->status === 'active' ? 'amber-500/20' : 'emerald-500/20' }} flex items-center justify-center transition"
                                                                title="Toggle status">
                                                                <i
                                                                    class="fa-solid fa-{{ $marker->status === 'active' ? 'pause' : 'play' }} text-[7px] text-[#555] hover:text-{{ $marker->status === 'active' ? 'amber-400' : 'emerald-400' }}"></i>
                                                            </button>
                                                        </form>
                                                        <form
                                                            action="{{ route('driver.dev.remove-marker', $marker->id) }}"
                                                            method="POST">
                                                            @csrf @method('DELETE')
                                                            <button type="submit"
                                                                class="w-6 h-6 rounded-md bg-[#1a1a1a] hover:bg-red-500/20 flex items-center justify-center transition"
                                                                title="Remove">
                                                                <i
                                                                    class="fa-solid fa-xmark text-[8px] text-[#555] hover:text-red-400"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            @endforeach
                                        @else
                                            <div
                                                class="flex flex-col items-center justify-center py-5 px-4 border border-dashed border-[#1e1e1e] rounded-xl">
                                                <i class="fa-solid fa-ghost text-[#222] text-lg mb-2"></i>
                                                <p class="text-[9px] text-[#333] text-center">No dummy markers yet</p>
                                                <p class="text-[7px] text-[#222] text-center mt-0.5">Add markers to test
                                                    commuter view</p>
                                            </div>
                                        @endif
                                    </div>

                                    @if (isset($dummyMarkers) && $dummyMarkers->count() > 0)
                                        <form action="{{ route('driver.dev.clear-markers') }}" method="POST"
                                            class="mt-3">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                class="w-full py-2 rounded-lg bg-[#111] hover:bg-red-500/10 border border-[#1e1e1e] hover:border-red-500/20 text-[#444] hover:text-red-400 text-[8px] font-bold uppercase tracking-widest transition">
                                                <i class="fa-solid fa-trash text-[7px] mr-1"></i> Clear All
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            @endenv

                        </div>
                    @endif

                    @if (
                        !Auth::check() ||
                            (Auth::check() && !in_array(Auth::user()->roles[0]->name, ['admin', 'maintenance_manager', 'driver_manager'])))
                        <div class="sidebar-toggle rounded-rect left hidden md:flex" onclick="toggleSidebar('left')">
                            <i class="fa-solid fa-chevron-right text-base"></i>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <!-- RIGHT SIDEBAR -->
        <div id="right" class="sidebar flex-center right collapsed">
            <div class="sidebar-content flex-center">
                <div id="right-sidebar-anchor"></div>
                <div id="right-sidebar-content"
                    class="fixed top-24 right-4 sm:right-2 w-[340px] z-40 hidden md:flex flex-col gap-3 max-h-[calc(100vh-120px)]">

                    @if (Auth::check() && Auth::user()->roles[0]->name === 'commuter')
                        <button onclick="openTutorialModal()"
                            class="glass-card p-4 rounded-xl flex items-center gap-3 group hover:border-yellow-500/20 transition w-full text-left">
                            <div
                                class="w-9 h-9 bg-yellow-500/10 rounded-xl flex items-center justify-center group-hover:scale-110 transition border border-yellow-500/15">
                                <i class="fa-solid fa-wand-magic-sparkles text-yellow-400 text-xs"></i>
                            </div>
                            <div>
                                <p class="text-[11px] font-bold text-white">App Tutorial</p>
                                <p class="text-[8px] text-[#444] uppercase tracking-wider font-bold">New user? Start
                                    here</p>
                            </div>
                            <i
                                class="fa-solid fa-chevron-right text-[8px] text-[#333] ml-auto group-hover:text-yellow-400 transition"></i>
                        </button>

                        <!-- ══════════ TUTORIAL MODAL (centered) ══════════ -->
                        <div id="tutorialModalBackdrop" class="tutorial-backdrop" onclick="closeTutorialModal()">
                            <div class="tutorial-backdrop-bg"></div>
                            <div class="tutorial-modal-box" onclick="event.stopPropagation()">
                                <div class="flex items-center justify-between p-5 pb-0">
                                    <div class="flex items-center gap-2.5">
                                        <div
                                            class="w-8 h-8 bg-yellow-500/10 rounded-lg flex items-center justify-center">
                                            <i class="fa-solid fa-wand-magic-sparkles text-yellow-400 text-xs"></i>
                                        </div>
                                        <div>
                                            <h2 class="text-xs font-bold text-white leading-tight">Quick Start Guide
                                            </h2>
                                            <p class="text-[8px] text-[#444] uppercase tracking-wider font-bold">4 easy
                                                steps</p>
                                        </div>
                                    </div>
                                    <button onclick="closeTutorialModal()"
                                        class="w-7 h-7 rounded-lg bg-[#1a1a1a] hover:bg-[#222] flex items-center justify-center transition">
                                        <i class="fa-solid fa-xmark text-[#555] text-[10px]"></i>
                                    </button>
                                </div>
                                <div class="p-5 space-y-4">
                                    <div class="flex gap-3"><span
                                            class="w-6 h-6 rounded-full bg-blue-600/20 flex items-center justify-center text-blue-400 text-[9px] font-bold shrink-0 mt-0.5 border border-blue-500/20">1</span>
                                        <div>
                                            <h3 class="font-bold text-[11px] mb-0.5 text-white">Search Your Location
                                            </h3>
                                            <p class="text-[#555] text-[10px] leading-relaxed">Type your starting point
                                                in the pick-up field.</p>
                                        </div>
                                    </div>
                                    <div class="flex gap-3"><span
                                            class="w-6 h-6 rounded-full bg-blue-600/20 flex items-center justify-center text-blue-400 text-[9px] font-bold shrink-0 mt-0.5 border border-blue-500/20">2</span>
                                        <div>
                                            <h3 class="font-bold text-[11px] mb-0.5 text-white">Search Destination</h3>
                                            <p class="text-[#555] text-[10px] leading-relaxed">Type where you want to
                                                go in the destination field.</p>
                                        </div>
                                    </div>
                                    <div class="flex gap-3"><span
                                            class="w-6 h-6 rounded-full bg-blue-600/20 flex items-center justify-center text-blue-400 text-[9px] font-bold shrink-0 mt-0.5 border border-blue-500/20">3</span>
                                        <div>
                                            <h3 class="font-bold text-[11px] mb-0.5 text-white">Buy a Ride</h3>
                                            <p class="text-[#555] text-[10px] leading-relaxed">Review the fare and
                                                proceed to payment.</p>
                                        </div>
                                    </div>
                                    <div class="flex gap-3"><span
                                            class="w-6 h-6 rounded-full bg-blue-600/20 flex items-center justify-center text-blue-400 text-[9px] font-bold shrink-0 mt-0.5 border border-blue-500/20">4</span>
                                        <div>
                                            <h3 class="font-bold text-[11px] mb-0.5 text-white">View History</h3>
                                            <p class="text-[#555] text-[10px] leading-relaxed">Check "Recent Receipts"
                                                for past trips.</p>
                                        </div>
                                    </div>
                                    <button onclick="closeTutorialModal()"
                                        class="mt-2 block w-full text-center text-[#333] hover:text-[#555] text-[9px] font-semibold uppercase tracking-wider transition">Dismiss</button>
                                </div>
                            </div>
                        </div>

                        <div class="glass-card p-5 rounded-[1.5rem] flex flex-col overflow-hidden">
                            <div class="flex justify-between items-center mb-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 bg-[#1a1a1a] rounded-md flex items-center justify-center"><i
                                            class="fa-solid fa-receipt text-[9px] text-[#555]"></i></div>
                                    <h3 class="text-[9px] font-bold uppercase tracking-[0.15em] text-[#555]">Recent
                                        Receipts</h3>
                                </div>
                                <a href="{{ route('payment.history') }}"
                                    class="text-[9px] font-bold text-blue-400 hover:text-blue-300 transition">View
                                    All</a>
                            </div>
                            <div class="space-y-2.5 custom-scroll overflow-y-auto pr-1 max-h-[280px]">
                                @if (isset($recentReceipts) && count($recentReceipts) > 0)
                                    @foreach ($recentReceipts as $receipt)
                                        <div
                                            class="flex items-center justify-between p-2.5 rounded-xl hover:bg-slate-100 dark:hover:bg-[#1a1a1a] transition group cursor-default">
                                            <div class="flex items-center gap-2.5">
                                                <div
                                                    class="w-8 h-8 bg-[#111] rounded-lg flex items-center justify-center border border-[#1e1e1e] group-hover:border-blue-500/20 transition">
                                                    <i
                                                        class="fa-solid fa-receipt text-[9px] text-[#444] group-hover:text-blue-400 transition"></i>
                                                </div>
                                                <div>
                                                    <p class="text-[10px] font-semibold text-[#ccc]">
                                                        {{ $receipt->transaction_id }}</p>
                                                    <p class="text-[8px] text-[#444] mt-0.5">{{ $receipt->paid_at }}
                                                    </p>
                                                </div>
                                            </div>
                                            <span
                                                class="text-[11px] font-bold text-emerald-400">-₱{{ $receipt->price }}</span>
                                        </div>
                                    @endforeach
                                @else
                                    <div
                                        class="flex flex-col items-center justify-center py-8 px-4 border border-dashed border-[#222] rounded-xl">
                                        <div
                                            class="w-10 h-10 bg-[#111] rounded-full flex items-center justify-center mb-3">
                                            <i class="fa-solid fa-file-invoice text-[#333] text-sm"></i>
                                        </div>
                                        <p class="text-[10px] font-medium text-[#444] text-center">No receipts yet</p>
                                        <p class="text-[8px] text-[#333] text-center mt-1">New trips will appear here
                                        </p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    @if (!Auth::check())
                        <div class="glass-card p-5 rounded-[1.5rem]">
                            <div class="flex items-center gap-2.5 mb-4">
                                <div class="w-8 h-8 bg-amber-500/10 rounded-lg flex items-center justify-center"><i
                                        class="fa-solid fa-bolt text-amber-400 text-xs"></i></div>
                                <h3 class="text-[9px] font-bold uppercase tracking-[0.15em] text-[#555]">Daily Usage
                                </h3>
                            </div>
                            <div class="space-y-3">
                                <div class="flex justify-between items-end">
                                    <span
                                        class="text-[9px] font-bold uppercase tracking-widest text-[#444]">Limit</span>
                                    <span id="usage-text" class="text-[11px] font-bold text-[#aaa] tracking-wider">0 /
                                        3</span>
                                </div>
                                <div
                                    class="w-full bg-[#0e0e0e] border border-[#1e1e1e] rounded-full h-2 overflow-hidden p-[1px]">
                                    <div id="usage-bar"
                                        class="h-full bg-gradient-to-r from-amber-500 to-orange-400 rounded-full transition-all duration-500"
                                        style="width: 0%"></div>
                                </div>
                                <p class="text-[8px] uppercase tracking-[0.12em] text-[#333] text-center">Guests get 3
                                    free fare checks per day</p>
                            </div>
                        </div>
                    @endif

                    @if (Auth::check() && Auth::user()->roles[0]->name === 'driver')
                        <!-- ══════════ ROUTE + GPS CARD ══════════ -->
                        <div class="glass-card p-5 rounded-[1.5rem] border-green-500/15">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <p class="text-[8px] uppercase tracking-[0.15em] text-[#444] font-bold mb-0.5">
                                        Active Trip</p>
                                    <h2 class="text-sm font-bold text-white">Current Route</h2>
                                </div>
                                <div
                                    class="w-8 h-8 bg-green-500/10 rounded-lg flex items-center justify-center border border-green-500/15">
                                    <i class="fa-solid fa-route text-green-400 text-xs"></i>
                                </div>
                            </div>
                            <div id="live-location-info" class="text-[11px] text-[#777] space-y-2 mb-5">
                                <div class="flex justify-between items-center"><span
                                        class="flex items-center gap-1.5"><i
                                            class="fa-solid fa-circle-dot text-green-400 text-[8px]"></i>
                                        Start</span><span class="font-mono text-[#aaa] text-[10px]">Minglanilla</span>
                                </div>
                                <div class="flex justify-between items-center"><span
                                        class="flex items-center gap-1.5"><i
                                            class="fa-solid fa-location-dot text-red-400 text-[8px]"></i>
                                        End</span><span class="font-mono text-[#aaa] text-[10px]">IT Park</span></div>
                            </div>
                            <div class="line-glow w-full mb-4"></div>
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <p class="text-[8px] uppercase tracking-[0.15em] text-[#444] font-bold mb-0.5">Live
                                        Status</p>
                                    <h2 class="text-sm font-bold text-white">GPS Tracking</h2>
                                </div>
                                <div
                                    class="w-8 h-8 bg-blue-500/10 rounded-lg flex items-center justify-center border border-blue-500/15">
                                    <i class="fa-solid fa-satellite-dish text-blue-400 text-xs"></i>
                                </div>
                            </div>
                            <div id="gps-status" class="tracking-controls-panel text-center">
                                <div class="flex items-center justify-center gap-2 mb-3">
                                    <div class="w-2 h-2 bg-[#555] rounded-full dot-pulse" id="gps-indicator"></div>
                                    <span class="text-[10px] text-[#555]" id="gps-status-text">GPS: Not active</span>
                                </div>
                                <div id="live-location-info" class="text-[10px] text-[#555] space-y-1.5">
                                    <div class="flex justify-between"><span><i
                                                class="fa-solid fa-location-dot text-green-400 mr-1 text-[8px]"></i>
                                            Position</span><span class="font-mono" id="current-coords">--, --</span>
                                    </div>
                                    <div class="flex justify-between"><span><i
                                                class="fa-solid fa-gauge-high text-blue-400 mr-1 text-[8px]"></i>
                                            Accuracy</span><span id="current-accuracy">-- m</span></div>
                                    <div class="flex justify-between"><span><i
                                                class="fa-regular fa-clock text-yellow-400 mr-1 text-[8px]"></i>
                                            Updated</span><span id="update-time">--:--:--</span></div>
                                </div>
                            </div>
                            <p class="mt-4 text-[8px] text-[#333] text-center"><i
                                    class="fa-solid fa-map-pin mr-0.5"></i> Tap the GPS button on the map to begin
                                tracking</p>
                        </div>
                    @endif

                </div>

                @if (Auth::check() && !in_array(Auth::user()->roles[0]->name, ['admin', 'maintenance_manager', 'driver_manager']))
                    <div class="sidebar-toggle rounded-rect right hidden md:flex" onclick="toggleSidebar('right')"><i
                            class="fa-solid fa-chevron-left text-base"></i></div>
                @endif
                @if (!Auth::check())
                    <div class="sidebar-toggle rounded-rect right hidden md:flex" onclick="toggleSidebar('right')"><i
                            class="fa-solid fa-chevron-left text-base"></i></div>
                @endif

            </div>
        </div>
        <!-- ═══════════════ MOBILE SIDEBAR MODAL LOGIC ═══════════════ -->
        <script>
            window._leftMobileOpen = false;
            window._rightMobileOpen = false;

            var LEFT_DESKTOP_CLASSES =
                'fixed top-24 left-4 sm:left-5 w-[340px] z-40 hidden md:flex flex-col gap-3 max-h-[calc(100vh-120px)]';
            var LEFT_MOBILE_CLASSES = 'flex flex-col gap-3 w-full';
            var RIGHT_DESKTOP_CLASSES =
                'fixed top-24 right-4 sm:right-5 w-[340px] z-40 hidden md:flex flex-col gap-3 max-h-[calc(100vh-120px)]';
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
                    setTimeout(function() {
                        backdrop.style.display = 'none';
                    }, 350);
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
                    setTimeout(function() {
                        backdrop.style.display = 'none';
                    }, 350);
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
        <script>
            window.userRole = '{{ Auth::check() ? Auth::user()->roles->first()->name : 'guest' }}';
            window.PRIVACY_RADIUS = 200;
            window.driverPrivacyZones = {};
            window.echoMarkers = {};
            window.dummyMapMarkers = {};
            window.initialDrivers = @json($obfuscatedMarkers ?? []);

            window.updatePrivacyZones = function() {
                var m = window.map;
                if (!m) return;
                var source = m.getSource('driver-privacy-zones');
                if (!source) return;
                var features = Object.keys(window.driverPrivacyZones).map(function(id) {
                    var data = window.driverPrivacyZones[id];
                    return {
                        type: 'Feature',
                        properties: {
                            driverId: id
                        },
                        geometry: {
                            type: 'Point',
                            coordinates: [data.lng, data.lat]
                        }
                    };
                });
                source.setData({
                    type: 'FeatureCollection',
                    features: features
                });
            };

            window.createPrivacyPopup = function(d) {
                return '<div style="font-family:Inter,sans-serif;padding:6px 4px;min-width:180px;">' +
                    '<div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">' +
                    '<div style="width:32px;height:32px;border-radius:10px;background:rgba(59,130,246,0.1);border:1px solid rgba(59,130,246,0.2);display:flex;align-items:center;justify-content:center;">' +
                    '<i class="fa-solid fa-bus" style="color:#60a5fa;font-size:12px;"></i>' +
                    '</div>' +
                    '<div>' +
                    '<div style="font-size:12px;font-weight:700;color:#fff;">' + (d.plate_number || d.name || 'Vehicle') +
                    '</div>' +
                    '<div style="font-size:9px;color:#555;font-weight:600;text-transform:uppercase;letter-spacing:0.1em;">' +
                    (d.route || 'Route') + '</div>' +
                    '</div>' +
                    '</div>' +
                    '<div style="display:flex;align-items:center;gap:6px;padding:6px 10px;border-radius:8px;background:rgba(59,130,246,0.06);border:1px solid rgba(59,130,246,0.1);margin-bottom:8px;">' +
                    '<i class="fa-solid fa-shield-halved" style="color:rgba(59,130,246,0.5);font-size:9px;"></i>' +
                    '<span style="font-size:9px;color:rgba(59,130,246,0.7);font-weight:600;">Approximate location · ~' + (d
                        .privacy_radius || window.PRIVACY_RADIUS) + 'm radius</span>' +
                    '</div>' +
                    '<div style="display:flex;align-items:center;gap:6px;">' +
                    '<div style="width:6px;height:6px;border-radius:50%;background:#34d399;"></div>' +
                    '<span style="font-size:9px;color:#34d399;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;">Active</span>' +
                    '</div>' +
                    '</div>';
            };
        </script>

        <script type="module">
            const userRole = @json(Auth::user())?.roles[0]?.name ?? 'guest';
            const userId = @json(Auth::user())?.id ?? null;
            const pusherKey = '{{ env('PUSHER_APP_KEY') }}';
            const pusherCluster = '{{ env('PUSHER_APP_CLUSTER') }}'
            const DAILY_LIMIT = 3;

            window.Pusher = Pusher;
            window.Echo = new Echo({
                broadcaster: 'pusher',
                key: pusherKey,
                cluster: pusherCluster,
                forceTLS: true
            });

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

            window.map = map;

            // ═══════════════ NAV CONTROLS ═══════════════
            map.addControl(new maplibregl.NavigationControl({
                visualizePitch: true
            }), 'bottom-right');

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

            function getTodayLocal() {
                const d = new Date();
                return d.getFullYear() + '-' +
                    String(d.getMonth() + 1).padStart(2, '0') + '-' +
                    String(d.getDate()).padStart(2, '0');
            }

            function loadGuestUsage() {
                try {
                    const raw = localStorage.getItem('guestUsage');
                    if (!raw) return 0;
                    const data = JSON.parse(raw);
                    if (data && data.date === getTodayLocal()) return data.count;
                } catch (e) {}
                return 0;
            }

            function saveGuestUsage(count) {
                localStorage.setItem('guestUsage', JSON.stringify({
                    date: getTodayLocal(),
                    count: count
                }));
            }

            let guestUsage = loadGuestUsage();

            // ═══════════════ DATE DISPLAY ═══════════════
            const dateEl = document.getElementById('current-date');
            if (dateEl) {
                const now = new Date();
                const options = {
                    weekday: 'short',
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric'
                };
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
                } else {
                    bar.classList.remove('from-red-500', 'to-red-400');
                    bar.classList.add('from-amber-500', 'to-orange-400');
                }
            }
            updateUsageUI();

            // ═══════════════ MODAL TOGGLES ═══════════════
            window.toggleLogoutModal = function() {
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

            window.toggleLimitModal = function(show) {
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
            window.openTutorialModal = function() {
                const backdrop = document.getElementById('tutorialModalBackdrop');
                if (backdrop) backdrop.classList.add('open');
            };

            window.closeTutorialModal = function() {
                const backdrop = document.getElementById('tutorialModalBackdrop');
                if (backdrop) backdrop.classList.remove('open');
            };

            // ═══════════════ SIDEBAR TOGGLE (DESKTOP) ═══════════════
            window.toggleSidebar = function(side) {
                if (window.innerWidth < 768) {
                    openMobileSidebar(side);
                    return;
                }
                const el = document.getElementById(side);
                el.classList.toggle('collapsed');
                const icon = el.querySelector('.sidebar-toggle i');
                if (side === 'left') {
                    icon.className = el.classList.contains('collapsed') ?
                        'fa-solid fa-chevron-right text-base' :
                        'fa-solid fa-chevron-left text-base';
                } else {
                    icon.className = el.classList.contains('collapsed') ?
                        'fa-solid fa-chevron-left text-base' :
                        'fa-solid fa-chevron-right text-base';
                }
            };

            // ═══════════════ SELECTION MODE ═══════════════
            window.toggleSelection = function(mode) {
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
                        statusIndicator.querySelector('.dot-pulse').className =
                            'w-2 h-2 rounded-full bg-blue-500 dot-pulse';
                        statusIndicator.className =
                            'mb-4 p-3 rounded-xl bg-blue-500/8 border border-blue-500/20 flex items-center gap-2.5';
                    } else {
                        statusText.textContent = 'Tap the map to set destination';
                        statusText.className = 'text-[9px] uppercase tracking-[0.15em] text-red-400 font-bold';
                        statusIndicator.querySelector('.dot-pulse').className = 'w-2 h-2 rounded-full bg-red-500 dot-pulse';
                        statusIndicator.className =
                            'mb-4 p-3 rounded-xl bg-red-500/8 border border-red-500/20 flex items-center gap-2.5';
                    }
                }
            };

            // ═══════════════ MAP LAYERS ═══════════════
            map.on('load', function() {

                // ── Privacy zone source + layers ──
                map.addSource('driver-privacy-zones', {
                    type: 'geojson',
                    data: {
                        type: 'FeatureCollection',
                        features: []
                    }
                });
                map.addLayer({
                    id: 'driver-privacy-glow',
                    type: 'circle',
                    source: 'driver-privacy-zones',
                    paint: {
                        'circle-radius': ['interpolate', ['linear'],
                            ['zoom'], 12, 40, 15, 120, 18, 350
                        ],
                        'circle-color': 'rgba(59,130,246,0.04)',
                        'circle-blur': 0.8
                    }
                });
                map.addLayer({
                    id: 'driver-privacy-fill',
                    type: 'circle',
                    source: 'driver-privacy-zones',
                    paint: {
                        'circle-radius': ['interpolate', ['linear'],
                            ['zoom'], 12, 30, 15, 90, 18, 260
                        ],
                        'circle-color': 'rgba(59,130,246,0.07)',
                        'circle-stroke-width': 1.5,
                        'circle-stroke-color': 'rgba(59,130,246,0.15)',
                        'circle-blur': 0.3
                    }
                });
                map.addLayer({
                    id: 'driver-privacy-border',
                    type: 'circle',
                    source: 'driver-privacy-zones',
                    paint: {
                        'circle-radius': ['interpolate', ['linear'],
                            ['zoom'], 12, 35, 15, 105, 18, 300
                        ],
                        'circle-color': 'transparent',
                        'circle-stroke-width': 1,
                        'circle-stroke-color': 'rgba(59,130,246,0.10)'
                    }
                });

                map.addSource('route', {
                    type: 'geojson',
                    data: {
                        type: 'FeatureCollection',
                        features: []
                    }
                });
                map.addLayer({
                    id: 'route-line',
                    type: 'line',
                    source: 'route',
                    layout: {
                        'line-join': 'round',
                        'line-cap': 'round'
                    },
                    paint: {
                        'line-color': '#3b82f6',
                        'line-width': 4,
                        'line-opacity': 0.85
                    }
                });
                map.addLayer({
                    id: 'route-line-glow',
                    type: 'line',
                    source: 'route',
                    layout: {
                        'line-join': 'round',
                        'line-cap': 'round'
                    },
                    paint: {
                        'line-color': '#3b82f6',
                        'line-width': 12,
                        'line-opacity': 0.15,
                        'line-blur': 6
                    }
                });

                map.addSource('pickup-point', {
                    type: 'geojson',
                    data: {
                        type: 'FeatureCollection',
                        features: []
                    }
                });
                map.addLayer({
                    id: 'pickup-circle',
                    type: 'circle',
                    source: 'pickup-point',
                    paint: {
                        'circle-radius': 7,
                        'circle-color': '#3b82f6',
                        'circle-stroke-width': 3,
                        'circle-stroke-color': '#ffffff'
                    }
                });

                map.addSource('destination-point', {
                    type: 'geojson',
                    data: {
                        type: 'FeatureCollection',
                        features: []
                    }
                });
                map.addLayer({
                    id: 'destination-circle',
                    type: 'circle',
                    source: 'destination-point',
                    paint: {
                        'circle-radius': 7,
                        'circle-color': '#ef4444',
                        'circle-stroke-width': 3,
                        'circle-stroke-color': '#ffffff'
                    }
                });

                map.addSource('vehicles', {
                    type: 'geojson',
                    data: {
                        type: 'FeatureCollection',
                        features: []
                    }
                });
                map.addLayer({
                    id: 'vehicle-circles',
                    type: 'circle',
                    source: 'vehicles',
                    paint: {
                        'circle-radius': 6,
                        'circle-color': '#3b82f6',
                        'circle-stroke-width': 2,
                        'circle-stroke-color': '#ffffff'
                    }
                });
            });

            // ═══════════════ MAP CLICK → SET LOCATION ═══════════════
            map.on('click', function(e) {
                if (!selectionMode) return;

                const lng = e.lngLat.lng;
                const lat = e.lngLat.lat;

                if (selectionMode === 'pickup') {
                    userLat = lat;
                    userLng = lng;

                    if (map.getSource('pickup-point')) {
                        map.getSource('pickup-point').setData({
                            type: 'FeatureCollection',
                            features: [{
                                type: 'Feature',
                                geometry: {
                                    type: 'Point',
                                    coordinates: [lng, lat]
                                },
                                properties: {}
                            }]
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
                            features: [{
                                type: 'Feature',
                                geometry: {
                                    type: 'Point',
                                    coordinates: [lng, lat]
                                },
                                properties: {}
                            }]
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

            const fareRates = @json($rates->sortBy('km')->values());

            window.calculateRoute = async function() {
                if (userLat === null || userLng === null || vehicleLat === null || vehicleLng === null) return;

                if (userRole === 'guest') {
                    // Re-check in case the day rolled over since page load
                    guestUsage = loadGuestUsage();
                    if (guestUsage >= DAILY_LIMIT) {
                        toggleLimitModal(true);
                        return;
                    }
                    guestUsage++;
                    saveGuestUsage(guestUsage);
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
                        const distKm = parseFloat((route.distance / 1000).toFixed(1));

                        document.getElementById('distance').value = distKm;

                        // Direct lookup from database
                        const fare = getFareFromDB(distKm);
                        document.getElementById('price-regular').value = fare.regular;
                        document.getElementById('price-discount').value = fare.discount;

                        // Draw route on map
                        if (map.getSource('route')) {
                            map.getSource('route').setData(route.geometry);
                        }

                        const coords = route.geometry.coordinates;
                        if (coords.length > 0) {
                            map.fitBounds(
                                [
                                    [coords[0][0], coords[0][1]],
                                    [coords[coords.length - 1][0], coords[coords.length - 1][1]]
                                ], {
                                    padding: {
                                        top: 100,
                                        bottom: 100,
                                        left: 400,
                                        right: 100
                                    },
                                    duration: 800
                                }
                            );
                        }
                    }
                } catch (e) {
                    console.error('Route error:', e);
                }
            };

            // Simple lookup function
            function getFareFromDB(distKm) {
                if (fareRates.length === 0) {
                    return {
                        regular: 0,
                        discount: 0
                    };
                }

                // Find the highest km tier that is <= distance
                const applicable = fareRates.filter(r => r.km <= distKm);

                if (applicable.length > 0) {
                    const rate = applicable[applicable.length - 1];
                    return {
                        regular: Math.ceil(rate.regular),
                        discount: Math.ceil(rate.discount)
                    };
                }

                // If distance is less than first tier, use first tier
                return {
                    regular: Math.ceil(fareRates[0].regular),
                    discount: Math.ceil(fareRates[0].discount)
                };
            }

            // ═══════════════ RESET FORM ═══════════════
            window.resetForm = function() {
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
                    map.getSource('route').setData({
                        type: 'FeatureCollection',
                        features: []
                    });
                }
                if (map.getSource('pickup-point')) {
                    map.getSource('pickup-point').setData({
                        type: 'FeatureCollection',
                        features: []
                    });
                }
                if (map.getSource('destination-point')) {
                    map.getSource('destination-point').setData({
                        type: 'FeatureCollection',
                        features: []
                    });
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
                    item.addEventListener('mousedown', function(e) {
                        e.preventDefault();
                        const idx = parseInt(this.dataset.index);
                        const r = results[idx];
                        const display = r.name + ([r.city, r.state, r.country].filter(Boolean).join(', ') ?
                            ' — ' + [r.city, r.state, r.country].filter(Boolean).join(', ') : '');

                        document.getElementById(inputId).value = display;

                        if (field === 'pickup') {
                            userLat = r.lat;
                            userLng = r.lon;
                            if (map.getSource('pickup-point')) {
                                map.getSource('pickup-point').setData({
                                    type: 'FeatureCollection',
                                    features: [{
                                        type: 'Feature',
                                        geometry: {
                                            type: 'Point',
                                            coordinates: [r.lon, r.lat]
                                        },
                                        properties: {}
                                    }]
                                });
                            }
                        } else {
                            vehicleLat = r.lat;
                            vehicleLng = r.lon;
                            if (map.getSource('destination-point')) {
                                map.getSource('destination-point').setData({
                                    type: 'FeatureCollection',
                                    features: [{
                                        type: 'Feature',
                                        geometry: {
                                            type: 'Point',
                                            coordinates: [r.lon, r.lat]
                                        },
                                        properties: {}
                                    }]
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

            document.getElementById('pickup').addEventListener('input', function() {
                clearTimeout(pickupTimer);
                const val = this.value.trim();
                const dropdown = document.getElementById('pickup-dropdown');

                if (val.length < 2) {
                    dropdown.classList.remove('active');
                    dropdown.innerHTML = '';
                    return;
                }

                dropdown.innerHTML =
                    '<div class="search-loading"><i class="fa-solid fa-spinner fa-spin mr-1"></i> Searching...</div>';
                dropdown.classList.add('active');

                pickupTimer = setTimeout(async () => {
                    const results = await searchPlaces(val);
                    renderResults(results, 'pickup-dropdown', 'pickup', 'pickup');
                }, 300);
            });

            document.getElementById('destination').addEventListener('input', function() {
                clearTimeout(destinationTimer);
                const val = this.value.trim();
                const dropdown = document.getElementById('destination-dropdown');

                if (val.length < 2) {
                    dropdown.classList.remove('active');
                    dropdown.innerHTML = '';
                    return;
                }

                dropdown.innerHTML =
                    '<div class="search-loading"><i class="fa-solid fa-spinner fa-spin mr-1"></i> Searching...</div>';
                dropdown.classList.add('active');

                destinationTimer = setTimeout(async () => {
                    const results = await searchPlaces(val);
                    renderResults(results, 'destination-dropdown', 'destination', 'destination');
                }, 300);
            });

            document.addEventListener('click', function(e) {
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

                input.addEventListener('keydown', function(e) {
                    const items = dropdown.querySelectorAll('.search-item');
                    if (!items.length || !dropdown.classList.contains('active')) return;

                    if (e.key === 'ArrowDown') {
                        e.preventDefault();
                        highlighted = Math.min(highlighted + 1, items.length - 1);
                        items.forEach((it, i) => it.classList.toggle('highlighted', i === highlighted));
                        items[highlighted].scrollIntoView({
                            block: 'nearest'
                        });
                    } else if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        highlighted = Math.max(highlighted - 1, 0);
                        items.forEach((it, i) => it.classList.toggle('highlighted', i === highlighted));
                        items[highlighted].scrollIntoView({
                            block: 'nearest'
                        });
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
                observer.observe(dropdown, {
                    attributes: true,
                    attributeFilter: ['class']
                });
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
                                new maplibregl.Popup({
                                        offset: 20,
                                        className: 'vehicle-popup'
                                    })
                                    .setLngLat([v.longitude, v.latitude])
                                    .setHTML(
                                        '<div style="background:#111;border:1px solid #222;border-radius:12px;padding:12px 16px;font-family:Inter,sans-serif;min-width:160px;">' +
                                        '<p style="color:#fff;font-size:12px;font-weight:700;margin:0 0 4px;">Bus ' +
                                        (v.plate_number || v.id) + '</p>' +
                                        '<p style="color:#666;font-size:10px;margin:0;">Route: ' + (v
                                            .route_name || 'N/A') + '</p>' +
                                        '<p style="color:#555;font-size:9px;margin:4px 0 0;">Updated just now</p>' +
                                        '</div>'
                                    )
                                    .addTo(map);
                            });
                            vehicleMarkers[v.id] = new maplibregl.Marker({
                                    element: el,
                                    anchor: 'center'
                                })
                                .setLngLat([v.longitude, v.latitude])
                                .addTo(map);
                        }
                    }
                });
            }

            // ═══════════════ REAL-TIME VEHICLE UPDATES (Echo) ═══════════════
            if (window.Echo) {
                window.Echo.channel('vehicle-locations')
                    .listen('.vehicle-location-updated', (e) => {
                        if (window.userRole === 'driver') return;
                        if (!e.lat || !e.lng) return;

                        var id = e.vehicleId;

                        if (window.echoMarkers[id]) {
                            window.echoMarkers[id].setLngLat([e.lng, e.lat]);
                        } else {
                            var el = document.createElement('div');
                            el.className = 'custom-vehicle-marker bus-pulse';
                            el.innerHTML = '<i class="fa-solid fa-bus"></i>';

                            var popup = new maplibregl.Popup({
                                    offset: 20,
                                    closeButton: false,
                                    maxWidth: '220px'
                                })
                                .setHTML(window.createPrivacyPopup({
                                    plate_number: 'Vehicle ' + id,
                                    route: 'Live',
                                    privacy_radius: e.privacy_radius
                                }));

                            window.echoMarkers[id] = new maplibregl.Marker({
                                    element: el,
                                    anchor: 'center'
                                })
                                .setLngLat([e.lng, e.lat])
                                .setPopup(popup)
                                .addTo(map);
                        }

                        window.driverPrivacyZones[id] = {
                            lat: e.lat,
                            lng: e.lng,
                            radius: e.privacy_radius || window.PRIVACY_RADIUS
                        };
                        window.updatePrivacyZones();
                    });
            }

            // ═══════════════ DEV MARKERS REAL-TIME SYNC ═══════════════
            if (window.Echo) {
                window.Echo.channel('dev-markers')
                    .listen('.marker-updated', function() {
                        loadDummyMarkers();
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

            window.toggleGPSTracking = function() {
                if (gpsWatchId !== null) {
                    navigator.geolocation.clearWatch(gpsWatchId);
                    gpsWatchId = null;
                    if (gpsIndicator) {
                        gpsIndicator.className = 'w-2 h-2 bg-[#555] rounded-full dot-pulse';
                    }
                    if (gpsStatusText) {
                        gpsStatusText.textContent = 'GPS: Not active';
                        gpsStatusText.className = 'text-[10px] text-[#555]';
                    }
                    if (currentCoords) currentCoords.textContent = '--, --';
                    if (currentAccuracy) currentAccuracy.textContent = '-- m';
                    if (updateTime) updateTime.textContent = '--:--:--';
                } else {
                    if (!navigator.geolocation) {
                        alert('Geolocation is not supported by your browser.');
                        return;
                    }
                    if (gpsIndicator) {
                        gpsIndicator.className = 'w-2 h-2 bg-green-500 rounded-full dot-pulse';
                    }
                    if (gpsStatusText) {
                        gpsStatusText.textContent = 'GPS: Active';
                        gpsStatusText.className = 'text-[10px] text-green-400 font-semibold';
                    }

                    gpsWatchId = navigator.geolocation.watchPosition(
                        function(pos) {
                            const lat = pos.coords.latitude.toFixed(6);
                            const lon = pos.coords.longitude.toFixed(6);
                            const acc = pos.coords.accuracy.toFixed(0);
                            const now = new Date();
                            const timeStr = now.toLocaleTimeString('en-US', {
                                hour12: false
                            });

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
                        function(err) {
                            console.error('GPS error:', err);
                            if (gpsIndicator) {
                                gpsIndicator.className = 'w-2 h-2 bg-red-500 rounded-full';
                            }
                            if (gpsStatusText) {
                                gpsStatusText.textContent = 'GPS: Error';
                                gpsStatusText.className = 'text-[10px] text-red-400';
                            }
                        }, {
                            enableHighAccuracy: true,
                            maximumAge: 5000,
                            timeout: 10000
                        }
                    );
                }
            };

            // ═══════════════ TERRA DRAW ═══════════════
            let terradraw = null;
            let drawActive = false;

            window.toggleDraw = function() {
                if (drawActive) {
                    if (terradraw) {
                        terradraw.stop();
                        terradraw = null;
                    }
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
            window.addEventListener('resize', function() {
                if (window.innerWidth >= 768) {
                    if (window._leftMobileOpen) closeMobileSidebar('left');
                    if (window._rightMobileOpen) closeMobileSidebar('right');
                }
            });
        </script>

        <script>
            var devPlacingMode = false;

            function captureMapCenter() {
                var m = window.map;
                if (!m || typeof m.getCanvas !== 'function') return;
                var c = m.getCenter();
                document.getElementById('dev-marker-lat').value = c.lat;
                document.getElementById('dev-marker-lng').value = c.lng;
            }

            function enableMarkerPlacement() {
                var m = window.map;
                if (!m || typeof m.getCanvas !== 'function') return;
                devPlacingMode = !devPlacingMode;
                var btn = document.getElementById('dev-place-btn');
                if (devPlacingMode) {
                    btn.classList.add('placing');
                    m.getCanvas().style.cursor = 'crosshair';
                } else {
                    btn.classList.remove('placing');
                    m.getCanvas().style.cursor = '';
                }
            }

            function attachDevMarkerClick() {
                var m = window.map;
                if (!m || typeof m.on !== 'function') {
                    setTimeout(attachDevMarkerClick, 200);
                    return;
                }
                m.on('click', function(e) {
                    if (!devPlacingMode) return;
                    devPlacingMode = false;
                    var btn = document.getElementById('dev-place-btn');
                    if (btn) btn.classList.remove('placing');
                    m.getCanvas().style.cursor = '';
                    document.getElementById('dev-marker-lat').value = e.lngLat.lat;
                    document.getElementById('dev-marker-lng').value = e.lngLat.lng;
                    document.getElementById('dev-marker-form').submit();
                });
                console.log('[DEV] Click handler attached');
            }

            attachDevMarkerClick();

            function renderDummyMarkers(markers) {
                var m = window.map;
                if (!m) return;

                // Clear old markers so we don't get duplicates
                Object.keys(window.dummyMapMarkers).forEach(function(id) {
                    window.dummyMapMarkers[id].remove();
                });
                window.dummyMapMarkers = {};
                window.driverPrivacyZones = {}; // reset privacy circles too

                if (!markers || !markers.length) {
                    window.updatePrivacyZones();
                    return;
                }

                var isDriver = window.userRole === 'driver';

                markers.forEach(function(d) {
                    var isMarkerActive = d.marker_status === 'active';

                    var el = document.createElement('div');
                    el.className = 'custom-vehicle-marker' + (isMarkerActive ? ' bus-pulse' : '');
                    if (!isMarkerActive) {
                        el.style.background = 'linear-gradient(135deg, #444, #333)';
                        el.style.borderColor = '#555';
                        el.style.boxShadow = '0 0 10px rgba(100,100,100,0.2)';
                    }
                    el.innerHTML = '<i class="fa-solid fa-bus"></i>';

                    var popup;
                    if (isDriver) {
                        // ── Driver: keep original detailed popup ──
                        var isDriverActive = d.driver_status === 'active';
                        var statusBg = isDriverActive ? 'rgba(16,185,129,0.1)' : 'rgba(239,68,68,0.1)';
                        var statusBorder = isDriverActive ? 'rgba(16,185,129,0.2)' : 'rgba(239,68,68,0.2)';
                        var statusColor = isDriverActive ? '#34d399' : '#ef4444';
                        var statusLabel = isDriverActive ? 'Available' : 'Unavailable';
                        var statusIcon = isDriverActive ? 'fa-circle-check' : 'fa-circle-xmark';

                        popup = new maplibregl.Popup({
                            offset: 20,
                            closeButton: false,
                            maxWidth: '220px'
                        }).setHTML(
                            '<div style="background:#111;border:1px solid #222;border-radius:16px;padding:16px;font-family:Inter,sans-serif;">' +
                            '<div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">' +
                            '<div style="width:36px;height:36px;border-radius:12px;background:rgba(59,130,246,0.1);border:1px solid rgba(59,130,246,0.2);display:flex;align-items:center;justify-content:center;flex-shrink:0;">' +
                            '<i class="fa-solid fa-bus" style="font-size:13px;color:#60a5fa;"></i>' +
                            '</div>' +
                            '<div style="min-width:0;">' +
                            '<p style="font-size:12px;font-weight:700;color:#eee;margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' +
                            d.name + '</p>' +
                            '<p style="font-size:9px;color:#555;margin:2px 0 0;">Driver</p>' +
                            '</div>' +
                            '</div>' +
                            '<div style="height:1px;background:#1e1e1e;margin:0 0 12px;"></div>' +
                            '<div style="display:flex;align-items:center;justify-content:space-between;padding:8px 10px;border-radius:10px;background:' +
                            statusBg + ';border:1px solid ' + statusBorder + ';margin-bottom:8px;">' +
                            '<div style="display:flex;align-items:center;gap:6px;">' +
                            '<i class="fa-solid ' + statusIcon + '" style="font-size:10px;color:' + statusColor +
                            ';"></i>' +
                            '<span style="font-size:8px;font-weight:700;text-transform:uppercase;letter-spacing:0.12em;color:' +
                            statusColor + ';">' + statusLabel + '</span>' +
                            '</div>' +
                            '<span style="font-size:7px;color:#444;text-transform:uppercase;letter-spacing:0.1em;font-weight:600;">Driver Status</span>' +
                            '</div>' +
                            '<div style="display:flex;justify-content:space-between;align-items:center;padding:0 2px;margin-bottom:4px;">' +
                            '<span style="font-size:8px;color:#444;text-transform:uppercase;letter-spacing:0.1em;font-weight:700;">Plate</span>' +
                            '<span style="font-size:10px;color:#888;font-weight:600;font-family:monospace;">' + d
                            .plate_number + '</span>' +
                            '</div>' +
                            '<div style="display:flex;justify-content:space-between;align-items:center;padding:0 2px;margin-bottom:4px;">' +
                            '<span style="font-size:8px;color:#444;text-transform:uppercase;letter-spacing:0.1em;font-weight:700;">Type</span>' +
                            '<span style="font-size:10px;color:#888;font-weight:600;">' + d.vehicle_type +
                            '</span>' +
                            '</div>' +
                            '<div style="display:flex;justify-content:space-between;align-items:center;padding:0 2px;">' +
                            '<span style="font-size:8px;color:#444;text-transform:uppercase;letter-spacing:0.1em;font-weight:700;">Route</span>' +
                            '<span style="font-size:10px;color:#888;font-weight:600;">' + d.route + '</span>' +
                            '</div>' +
                            '</div>'
                        );
                    } else {
                        // ── Commuter/Guest: privacy popup ──
                        popup = new maplibregl.Popup({
                                offset: 20,
                                closeButton: false,
                                maxWidth: '220px'
                            })
                            .setHTML(window.createPrivacyPopup(d));
                    }

                    var mapMarker = new maplibregl.Marker({
                            element: el
                        })
                        .setLngLat([d.lng, d.lat])
                        .setPopup(popup)
                        .addTo(m);

                    window.dummyMapMarkers[d.id] = mapMarker;

                    // Track privacy zone for non-drivers
                    if (!isDriver && d.privacy_radius) {
                        window.driverPrivacyZones[d.id] = {
                            lat: d.lat,
                            lng: d.lng,
                            radius: d.privacy_radius
                        };
                    }
                });

                if (!isDriver) {
                    window.updatePrivacyZones();
                }
            }

            function loadDummyMarkers() {
                var m = window.map;
                if (!m || typeof m.on !== 'function') {
                    setTimeout(loadDummyMarkers, 200);
                    return;
                }
                console.log('[DEV] Map ready, fetching markers...');
                fetch('/api/markers?t=' + Date.now())
                    .then(function(r) {
                        console.log('[DEV] Fetch response status:', r.status);
                        return r.json();
                    })
                    .then(function(markers) {
                        console.log('[DEV] Parsed markers:', JSON.stringify(markers, null, 2));
                        renderDummyMarkers(markers);
                    })
                    .catch(function(err) {
                        console.log('[DEV] Fetch error:', err);
                    });
            }

            loadDummyMarkers();
        </script>
    </div>

    <script>
        function toggleMapTheme() {
            const isDark = document.documentElement.classList.toggle('dark');
            const theme = isDark ? 'dark' : 'light';
            localStorage.setItem('color-theme', theme);

            // Update map style if needed
            if (typeof updateMapStyle === 'function') updateMapStyle(isDark);

            // Persist to database
            fetch('{{ route('settings.update.theme') }}', {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                },
                body: JSON.stringify({
                    theme
                })
            }).catch(() => {
                // Revert on failure
                document.documentElement.classList.toggle('dark');
                localStorage.setItem('color-theme', isDark ? 'light' : 'dark');
            });
        }
    </script>

</body>

</html>
