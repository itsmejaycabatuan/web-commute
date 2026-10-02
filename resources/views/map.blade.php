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
    @include('partials.commuter-head-scripts')
    <script>
        /* Shared map globals MUST exist before the first page script runs.
           `window.userRole` used to be assigned ~900 lines further down the
           body, so `window.ETA.start()` bailed out (userRole still undefined)
           and nothing that depends on the ETA engine ever came alive: GPS
           polling, ETA badges and the Nearest-PUJ indicator. */
        window.userRole = '{{ Auth::check() ? Auth::user()->roles->first()->name : 'guest' }}';
        window.PRIVACY_RADIUS = 200;
        window.driverPrivacyZones = window.driverPrivacyZones || {};
        window.echoMarkers = window.echoMarkers || {};
        window.echoPopups = window.echoPopups || {};
        window.liveVehicleCache = window.liveVehicleCache || {};
        window.dummyMapMarkers = window.dummyMapMarkers || {};
        window.dummyMapPopups = window.dummyMapPopups || {};
    </script>
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

        /* ══════════════════════════════════════════════════════════════
         * OVERLAY THEME TOKENS
         * The status banner (#map-alert) and the floating Nearest-PUJ
         * indicator both sit on top of the map, so they can't inherit the
         * page panel colours - they use their own tokens, mirrored by a
         * `.dark` block. Light is the default, exactly like the rest of the
         * page (the `dark` class is set on <html>).
         * ══════════════════════════════════════════════════════════════ */
        #map-alert,
        #nearest-vehicle-indicator {
            --ov-bg: rgba(255, 255, 255, 0.94);
            --ov-bg-hover: rgba(255, 255, 255, 0.99);
            --ov-border: #e2e8f0;
            --ov-border-hover: #cbd5e1;
            --ov-shadow: 0 8px 32px rgba(15, 23, 42, 0.16);
            --ov-shadow-hover: 0 12px 40px rgba(15, 23, 42, 0.22);
            --ov-title: #0f172a;
            --ov-text: #475569;
            --ov-dim: #94a3b8;
            --ov-accent: #60a5fa;
        }

        .dark #map-alert,
        .dark #nearest-vehicle-indicator {
            --ov-bg: rgba(17, 17, 17, 0.92);
            --ov-bg-hover: rgba(17, 17, 17, 0.97);
            --ov-border: #222222;
            --ov-border-hover: #333333;
            --ov-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
            --ov-shadow-hover: 0 12px 40px rgba(0, 0, 0, 0.5);
            --ov-title: #eeeeee;
            --ov-text: #888888;
            --ov-dim: #555555;
            --ov-accent: #60a5fa;
        }

        /* ═══ MAP STATUS BANNER (E1 no PUJ / E2 permission / E3 map / E4 offline) ═══ */
        #map-alert {
            position: absolute;
            /* Sits just under the fixed header, which is taller on mobile. */
            top: 104px;
            left: 50%;
            transform: translateX(-50%);
            /* Above the canvas, but never over a marker popup (z-index 25). */
            z-index: 18;
            max-width: min(400px, calc(100vw - 24px));
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 11px 15px;
            border-radius: 14px;
            background: var(--ov-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--ov-border);
            box-shadow: var(--ov-shadow);
            font-family: Inter, sans-serif;
            /* Purely informational: never swallow map drags / marker clicks. */
            pointer-events: none;
            animation: mapAlertIn 0.25s ease;
        }

        @media (max-width: 640px) {
            #map-alert {
                top: 132px;
                padding: 10px 13px;
            }
        }

        @keyframes mapAlertIn {
            from {
                opacity: 0;
                transform: translateX(-50%) translateY(-6px);
            }

            to {
                opacity: 1;
                transform: translateX(-50%) translateY(0);
            }
        }

        #map-alert.hidden {
            display: none;
        }

        #map-alert .ma-icon {
            font-size: 13px;
            line-height: 1.4;
            flex-shrink: 0;
        }

        #map-alert .ma-title {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--ov-title);
            margin: 0 0 3px;
        }

        #map-alert .ma-msg {
            font-size: 10px;
            line-height: 1.5;
            color: var(--ov-text);
            margin: 0;
        }

        #map-alert[data-type="warning"] {
            border-color: rgba(251, 191, 36, 0.45);
        }

        #map-alert[data-type="warning"] .ma-icon {
            color: #d97706;
        }

        .dark #map-alert[data-type="warning"] .ma-icon {
            color: #fbbf24;
        }

        #map-alert[data-type="error"] {
            border-color: rgba(239, 68, 68, 0.4);
        }

        #map-alert[data-type="error"] .ma-icon {
            color: #dc2626;
        }

        .dark #map-alert[data-type="error"] .ma-icon {
            color: #ef4444;
        }

        #map-alert[data-type="info"] {
            border-color: rgba(96, 165, 250, 0.4);
        }

        #map-alert[data-type="info"] .ma-icon {
            color: var(--ov-accent);
        }

        /* ═══ NEAREST PUJ FLOATING INDICATOR ═══ */
        #nearest-vehicle-indicator {
            position: absolute;
            bottom: 80px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 20;
            max-width: calc(100vw - 24px);
            background: var(--ov-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--ov-border);
            border-radius: 14px;
            padding: 10px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            transition: background 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease, transform 0.3s ease;
            box-shadow: var(--ov-shadow);
            min-width: 200px;
        }

        #nearest-vehicle-indicator:hover {
            border-color: var(--ov-border-hover);
            background: var(--ov-bg-hover);
            transform: translateX(-50%) translateY(-1px);
            box-shadow: var(--ov-shadow-hover);
        }

        #nearest-vehicle-indicator:active {
            transform: translateX(-50%) translateY(0);
        }

        #nearest-vehicle-indicator.hidden {
            display: none;
        }

        .nv-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
            transition: background 0.3s ease;
        }

        .nv-info {
            display: flex;
            flex-direction: column;
            gap: 1px;
            min-width: 0;
        }

        .nv-label {
            font-size: 8px;
            color: var(--ov-dim);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.12em;
        }

        .nv-time {
            font-size: 14px;
            font-weight: 800;
            color: #34d399;
            transition: color 0.3s ease;
            line-height: 1.3;
            white-space: nowrap;
        }

        .nv-distance {
            font-size: 10px;
            color: var(--ov-text);
            font-weight: 500;
            margin-left: auto;
            font-family: 'SF Mono', 'Fira Code', monospace;
            white-space: nowrap;
        }

        .nv-arrow {
            color: var(--ov-dim);
            font-size: 10px;
            margin-left: 4px;
            transition: color 0.2s;
            flex-shrink: 0;
        }

        #nearest-vehicle-indicator:hover .nv-arrow {
            color: var(--ov-text);
        }

        /* ═══════════════ PUJ MARKER POPUP ═══════════════
           Themed via CSS variables so the popup follows the user's theme
           preference (the `dark` class on <html>), exactly like the rest of
           the map UI. Light is the default; .dark overrides the tokens. */
        .maplibregl-popup.puj-popup {
            --pp-bg: #ffffff;
            --pp-border: #e2e8f0;
            --pp-shadow: 0 12px 40px rgba(15, 23, 42, 0.18);
            --pp-text: #0f172a;
            --pp-muted: #64748b;
            --pp-dim: #94a3b8;
            --pp-divider: #e2e8f0;
            --pp-chip: rgba(59, 130, 246, 0.06);
            --pp-chip-border: rgba(59, 130, 246, 0.18);
            --pp-eta-chip: rgba(16, 185, 129, 0.08);
            --pp-eta-border: rgba(16, 185, 129, 0.2);
            z-index: 25;
        }

        .dark .maplibregl-popup.puj-popup {
            --pp-bg: rgba(17, 17, 17, 0.96);
            --pp-border: #262626;
            --pp-shadow: 0 12px 40px rgba(0, 0, 0, 0.55);
            --pp-text: #ffffff;
            --pp-muted: #9ca3af;
            --pp-dim: #6b7280;
            --pp-divider: #1e1e1e;
            --pp-chip: rgba(59, 130, 246, 0.06);
            --pp-chip-border: rgba(59, 130, 246, 0.12);
            --pp-eta-chip: rgba(16, 185, 129, 0.08);
            --pp-eta-border: rgba(16, 185, 129, 0.14);
        }

        .maplibregl-popup.puj-popup .maplibregl-popup-content {
            background: var(--pp-bg);
            border: 1px solid var(--pp-border);
            border-radius: 14px;
            box-shadow: var(--pp-shadow);
            padding: 12px;
            box-sizing: border-box;
            overflow: hidden;
        }

        /* Keep the tip the same colour as the panel for every anchor */
        .maplibregl-popup.puj-popup.maplibregl-popup-anchor-top .maplibregl-popup-tip,
        .maplibregl-popup.puj-popup.maplibregl-popup-anchor-top-left .maplibregl-popup-tip,
        .maplibregl-popup.puj-popup.maplibregl-popup-anchor-top-right .maplibregl-popup-tip {
            border-bottom-color: var(--pp-bg);
        }

        .maplibregl-popup.puj-popup.maplibregl-popup-anchor-bottom .maplibregl-popup-tip,
        .maplibregl-popup.puj-popup.maplibregl-popup-anchor-bottom-left .maplibregl-popup-tip,
        .maplibregl-popup.puj-popup.maplibregl-popup-anchor-bottom-right .maplibregl-popup-tip {
            border-top-color: var(--pp-bg);
        }

        .maplibregl-popup.puj-popup.maplibregl-popup-anchor-left .maplibregl-popup-tip {
            border-right-color: var(--pp-bg);
        }

        .maplibregl-popup.puj-popup.maplibregl-popup-anchor-right .maplibregl-popup-tip {
            border-left-color: var(--pp-bg);
        }

        .puj-popup .pp {
            font-family: Inter, sans-serif;
            width: 216px;
            max-width: 100%;
            box-sizing: border-box;
            color: var(--pp-text);
        }

        .puj-popup .pp-head {
            display: flex;
            align-items: center;
            gap: 9px;
            margin-bottom: 9px;
            min-width: 0;
        }

        .puj-popup .pp-head-icon {
            width: 30px;
            height: 30px;
            flex: 0 0 30px;
            border-radius: 9px;
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid var(--pp-chip-border);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* min-width:0 lets long names shrink + ellipsis instead of overflowing */
        .puj-popup .pp-head-text {
            min-width: 0;
            flex: 1 1 auto;
        }

        .puj-popup .pp-title {
            margin: 0;
            font-size: 11.5px;
            font-weight: 700;
            color: var(--pp-text);
            line-height: 1.25;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .puj-popup .pp-sub {
            margin: 2px 0 0;
            font-size: 8.5px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--pp-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .puj-popup .pp-note {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 9px;
            border-radius: 8px;
            background: var(--pp-chip);
            border: 1px solid var(--pp-chip-border);
            font-size: 8.5px;
            font-weight: 600;
            color: var(--pp-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .puj-popup .pp-status {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 8px;
            font-size: 8.5px;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }

        .puj-popup .pp-status .dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            flex: 0 0 6px;
        }

        .puj-popup .pp-status .lbl {
            color: var(--pp-dim);
            margin-left: auto;
            font-size: 7.5px;
            letter-spacing: 0.1em;
        }

        .puj-popup .pp-divider {
            height: 1px;
            background: var(--pp-divider);
            margin: 9px 0;
        }

        .puj-popup .pp-eta {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 10px;
            border-radius: 10px;
            background: var(--pp-eta-chip);
            border: 1px solid var(--pp-eta-border);
        }

        .puj-popup .pp-eta-icon {
            width: 26px;
            height: 26px;
            flex: 0 0 26px;
            border-radius: 8px;
            background: var(--pp-eta-chip);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .puj-popup .pp-eta-text {
            min-width: 0;
            flex: 1 1 auto;
        }

        .puj-popup .pp-eta-main {
            font-size: 11px;
            font-weight: 700;
            line-height: 1.3;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .puj-popup .pp-eta-sub {
            margin-top: 2px;
            font-size: 8px;
            color: var(--pp-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .puj-popup .pp-eta-label {
            flex: 0 0 auto;
            font-size: 7px;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            line-height: 1;
        }

        .puj-popup .pp-accuracy {
            margin-top: 6px;
            text-align: center;
            font-size: 7px;
            color: var(--pp-dim);
        }

        /* Driver rows (plate / type / route) */
        .puj-popup .pp-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding: 0 2px;
        }

        .puj-popup .pp-row + .pp-row {
            margin-top: 4px;
        }

        .puj-popup .pp-row .k {
            flex: 0 0 auto;
            font-size: 7.5px;
            color: var(--pp-dim);
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }

        .puj-popup .pp-row .v {
            min-width: 0;
            font-size: 9.5px;
            color: var(--pp-muted);
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            text-align: right;
        }

        .puj-popup .pp-row .v.mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        }

        .puj-popup .pp-driver-status {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px 10px;
            border-radius: 10px;
            margin-bottom: 8px;
            font-size: 8px;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .puj-popup .pp-driver-status .lbl {
            margin-left: auto;
            font-size: 7px;
            color: var(--pp-dim);
        }

        /* ── ETA Marker Badge ── */
        .custom-vehicle-marker {
            position: relative;
        }

        .eta-badge {
            position: absolute;
            top: -10px;
            left: 50%;
            transform: translateX(-50%);
            padding: 1px 6px;
            border-radius: 6px;
            font-size: 8px;
            font-weight: 700;
            font-family: 'Inter', sans-serif;
            white-space: nowrap;
            z-index: 10;
            pointer-events: none;
            line-height: 1.5;
            letter-spacing: 0.02em;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.5);
            transition: opacity 0.3s ease;
        }

        .eta-here {
            background: #059669;
            color: #fff;
        }

        .eta-soon {
            background: #d97706;
            color: #fff;
        }

        .eta-near {
            background: #ea580c;
            color: #fff;
        }

        .eta-far {
            background: #1f2937;
            color: #9ca3af;
            border: 1px solid #374151;
        }

        .eta-unknown {
            display: none;
        }

        /* ── Map Status Banner (E1 No PUJ / E2 Permission / E3 Map Service / E4 Offline) ── */
        /* ═══ SIMULATOR ═══ */
        .sim-waypoint-dot {
            width: 12px;
            height: 12px;
            background: #a78bfa;
            border: 2px solid white;
            border-radius: 50%;
            box-shadow: 0 0 10px rgba(167, 139, 250, 0.5);
            pointer-events: none;
        }

        .sim-waypoint-dot.first {
            background: #10b981;
            box-shadow: 0 0 10px rgba(16, 185, 129, 0.5);
        }

        .sim-route-line {
            position: absolute;
            top: 0;
            left: 0;
            pointer-events: none;
        }

        .sim-placeholder-mode {
            cursor: crosshair !important;
        }

        .sim-placeholder-mode .maplibregl-canvas {
            cursor: crosshair !important;
        }

        .sim-speed-btn {
            width: 32px;
            height: 28px;
            border-radius: 8px;
            font-size: 10px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            color: #64748b;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .sim-speed-btn:hover {
            background: #f1f5f9;
            color: #334155;
        }

        .sim-speed-btn.active {
            background: #2563eb;
            border-color: #2563eb;
            color: white;
        }

        .dark .sim-speed-btn {
            border-color: #222;
            background: #0e0e0e;
            color: #666;
        }

        .dark .sim-speed-btn:hover {
            background: #1a1a1a;
            color: #aaa;
        }

        .dark .sim-speed-btn.active {
            background: #2563eb;
            border-color: #2563eb;
            color: white;
        }

        @keyframes sim-pulse-ring {
            0% {
                box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.4);
            }

            100% {
                box-shadow: 0 0 0 14px rgba(37, 99, 235, 0);
            }
        }

        .sim-marker-active {
            animation: sim-pulse-ring 1.5s ease-out infinite !important;
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

        /* ═══ LANDSCAPE / SHORT VIEWPORT ═══
           Sideways on a phone there is width to spare but almost no height,
           so the two stacked FABs (136px + 72px above the bottom) would eat
           a third of the screen. Put them side by side on the bottom-left
           and let the sheets use the full height. `is-landscape` is toggled
           by partials/landscape-styles.blade.php. */
        html.is-landscape .mobile-fab-wrap {
            bottom: 1rem !important;
            left: auto !important;
        }

        html.is-landscape .mobile-fab-wrap-left {
            left: 1rem !important;
        }

        html.is-landscape .mobile-fab-wrap-right {
            left: 5.25rem !important;
        }

        html.is-landscape .mobile-fab {
            width: 48px;
            height: 48px;
            border-radius: 15px;
        }

        html.is-landscape .mobile-sheet {
            /* Full height, with the grab handle kept visible. */
            max-height: calc(100vh - 0.5rem);
            border-radius: 1.25rem 1.25rem 0 0;
        }

        html.is-landscape .mobile-sheet-handle {
            padding: 8px 0 2px;
        }

        html.is-landscape .mobile-sheet-body {
            padding-bottom: 20px;
        }

        /* The floating header would otherwise cover the top of the map. */
        html.is-landscape #map-alert,
        html.is-landscape #nearest-vehicle-indicator {
            max-width: min(22rem, 45vw);
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
                @if (Auth::check() && Auth::user()->roles[0]->name === 'commuter')
                    <a href="{{ route('profile') }}">
                        <div
                            class="header-btn glass-panel px-4 h-9 py-2 rounded-xl text-white text-[10px] font-bold cursor-pointer uppercase tracking-wider flex items-center gap-2">
                            <i class="fa-solid fa-user text-[9px] text-[#666]"></i><span
                                class="hidden sm:inline">Profile</span>
                        </div>
                    </a>
                @endif
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

    <!-- ══════════ MOBILE FAB BUTTONS (stacked on bottom-left) ══════════ -->
    {{-- The LEFT FAB opens the mobile sheet that holds #left-sidebar-form.
         That panel exists for guests, commuters AND drivers (driver duty
         status toggle + timekeeping), but the button used to be limited to
         guests/commuters — so on a phone the driver simply had no way to
         reach the left sidebar, while the right FAB (rendered for every
         non-admin role) kept working. That's the "right sidebar only" bug. --}}
    @php
        $mapRole = Auth::check() ? (Auth::user()->roles->first()->name ?? 'default') : 'guest';
        $hasMobileLeftSidebar = in_array($mapRole, ['guest', 'commuter', 'driver'], true);
        $hasMobileRightSidebar = $mapRole !== 'admin';
    @endphp

    @if ($hasMobileLeftSidebar)
        <div class="mobile-fab-wrap mobile-fab-wrap-left fixed bottom-[8.5rem] left-5 z-50 md:hidden">
            <button type="button" onclick="openMobileSidebar('left')" aria-label="Open sidebar"
                class="mobile-fab mobile-fab-left">
                <i class="fa-solid fa-{{ $mapRole === 'driver' ? 'clock' : 'route' }} text-white text-base"></i>
            </button>
        </div>
    @endif

    @if ($hasMobileRightSidebar)
        <div class="mobile-fab-wrap mobile-fab-wrap-right fixed bottom-[4.5rem] left-5 z-50 md:hidden">
            <button type="button" onclick="openMobileSidebar('right')" aria-label="Open details"
                class="mobile-fab mobile-fab-right">
                <i class="fa-solid fa-ellipsis-vertical text-base"></i>
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
        @if (Auth::guest() || Auth::check())
            <div id="left" class="sidebar flex-center left collapsed">
                <div class="sidebar-content flex-center">
                    <div id="left-sidebar-anchor"></div>

                    @if ((Auth::check() && Auth::user()->roles[0]->name === 'commuter') || Auth::guest())
                        <div id="left-sidebar-form"
                            class="fixed top-24 left-4 sm:left-2 w-[340px] z-40 hidden md:flex flex-col gap-3 max-h-[calc(100vh-120px)] overflow-y-auto custom-scroll p-3 pb-6">
                            <form action="{{ route('payment.index') }}" method="GET"
                                data-guest-register="{{ Auth::guest() ? route('register') : '' }}">
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



                        </div>
                    @endif

                        <script>
// ══════════════════════════════════════════════
                                // DUMMY MARKER RENDERING
                                // ══════════════════════════════════════════════
                                window.dummyMapMarkers = window.dummyMapMarkers || {};
                                window.driverPrivacyZones = window.driverPrivacyZones || {};
                                window.dummyMapPopups = window.dummyMapPopups || {};   // ← ADD

                                function renderDummyMarkers(markers) {
                                    var m = window.map;
                                    if (!m) return;

                                    Object.keys(window.dummyMapMarkers).forEach(function(id) {
                                        window.dummyMapMarkers[id].remove();
                                    });
                                    window.dummyMapMarkers = {};
                                    window.dummyMapPopups = {};
                                    window.driverPrivacyZones = {};

                                    if (!markers || !markers.length) {
                                        if (window.updatePrivacyZones) window.updatePrivacyZones();
                                        if (window.updatePujEmptyState) window.updatePujEmptyState();
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
                                            popup = new maplibregl.Popup({
                                                className: 'puj-popup',
                                                offset: 18,
                                                closeButton: false,
                                                focusAfterOpen: false,
                                                maxWidth: '260px'
                                            }).setHTML(window.createDriverPopup(d));
                                        } else {
                                            popup = new maplibregl.Popup({
                                                    className: 'puj-popup',
                                                    offset: 18,
                                                    closeButton: false,
                                                    focusAfterOpen: false,
                                                    maxWidth: '260px'
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

                                        if (!isDriver && d.privacy_radius) {
                                            window.driverPrivacyZones[d.id] = {
                                                lat: d.lat,
                                                lng: d.lng,
                                                radius: d.privacy_radius
                                            };
                                        }
                                    });

                                    if (!isDriver && window.updatePrivacyZones) {
                                        window.updatePrivacyZones();
                                    }
                                    if (window.updatePujEmptyState) window.updatePujEmptyState();
                                    // Refresh ETA badges after markers are (re)rendered
                                    if (window.ETA && window.userRole !== 'driver') {
                                        setTimeout(function() {
                                            window.ETA.refresh();
                                        }, 100);
                                    }
                                }

                                function loadDummyMarkers() {
                                    var m = window.map;
                                    if (!m || typeof m.on !== 'function') {
                                        setTimeout(loadDummyMarkers, 200);
                                        return;
                                    }
                                    console.log('[DEV] Map ready, fetching markers...');
                                    if (!navigator.onLine) {
                                        if (window.showMapAlert) window.showMapAlert('offline');
                                        return;
                                    }
                                    fetch('/api/markers?t=' + Date.now())
                                        .then(function(r) {
                                            return r.json();
                                        })
                                        .then(function(markers) {
                                            if (window.clearMapAlert) window.clearMapAlert('offline');
                                            renderDummyMarkers(markers);
                                            if (window.markPujSourceLoaded) window.markPujSourceLoaded('dummy');
                                        })
                                        .catch(function(err) {
                                            console.log('[DEV] Fetch error:', err);
                                            // E4 - cannot reach the API (offline / server unreachable)
                                            if (!navigator.onLine) {
                                                if (window.showMapAlert) window.showMapAlert('offline');
                                            } else if (window.showMapAlert) {
                                                window.showMapAlert('map-service');
                                            }
                                        });
                                }
                                window.loadDummyMarkers = loadDummyMarkers;

                                loadDummyMarkers();

                                // ══════════════════════════════════════════════
                                // ETA ENGINE — Commuter → PUJ
                                // ══════════════════════════════════════════════
                                window.ETA = {
                                    // ── State ──
                                    userLat: null,
                                    userLng: null,
                                    userAccuracy: null,
                                    watchId: null,
                                    _debounceTimer: null,
                                    _periodicTimer: null,
                                    _ready: false,

                                    // ── Config ──
                                    AVG_SPEED_KPH: 20, // Average PUJ speed (urban Cebu)
                                    PRIVACY_BUFFER_SEC: 30, // Extra seconds for privacy uncertainty
                                    BADGE_MAX_DIST_KM: 5, // Don't show badge beyond this
                                    INDICATOR_MAX_DIST_KM: 10, // Don't show floating indicator beyond this
                                    DEBOUNCE_MS: 2000, // Debounce GPS updates
                                    PERIODIC_MS: 15000, // Periodic refresh interval

                                    // ── Haversine (km) ──
                                    haversine: function(lat1, lon1, lat2, lon2) {
                                        var R = 6371;
                                        var dLat = (lat2 - lat1) * Math.PI / 180;
                                        var dLon = (lon2 - lon1) * Math.PI / 180;
                                        var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                                            Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                                            Math.sin(dLon / 2) * Math.sin(dLon / 2);
                                        return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
                                    },

                                    // ── Calculate ETA for one vehicle ──
                                    calc: function(vLat, vLng, privacyR) {
                                        if (this.userLat === null || !vLat || !vLng) return null;

                                        var distKm = this.haversine(this.userLat, this.userLng, vLat, vLng);

                                        // Privacy buffer: subtract half the privacy radius (optimistic estimate)
                                        var bufferKm = (privacyR || 200) / 2000;
                                        var effDistKm = Math.max(0, distKm - bufferKm);

                                        // Time in minutes at average speed
                                        var timeMin = (effDistKm / this.AVG_SPEED_KPH) * 60;
                                        var bufferMin = this.PRIVACY_BUFFER_SEC / 60;

                                        return {
                                            distKm: distKm,
                                            effDistKm: effDistKm,
                                            timeMin: timeMin,
                                            low: Math.max(1, Math.round(timeMin)),
                                            high: Math.max(2, Math.round(timeMin + bufferMin * 2)),
                                            display: Math.max(1, Math.ceil(timeMin + bufferMin)),
                                            here: distKm < 0.05 // ~50m
                                        };
                                    },

                                    // ── Format ETA for display ──
                                    fmt: function(e) {
                                        if (!e) return {
                                            text: '--',
                                            cls: 'eta-unknown'
                                        };
                                        if (e.here) return {
                                            text: 'Arriving',
                                            cls: 'eta-here'
                                        };
                                        if (e.display <= 1) return {
                                            text: '< 1 min',
                                            cls: 'eta-soon'
                                        };
                                        if (e.display <= 3) return {
                                            text: e.low + '–' + e.high + ' min',
                                            cls: 'eta-soon'
                                        };
                                        if (e.display <= 10) return {
                                            text: '~' + e.display + ' min',
                                            cls: 'eta-near'
                                        };
                                        return {
                                            text: '~' + e.display + ' min',
                                            cls: 'eta-far'
                                        };
                                    },

                                    fmtDist: function(km) {
                                        if (km < 0.1) return '< 100m';
                                        if (km < 1) return Math.round(km * 1000) + 'm';
                                        return km.toFixed(1) + ' km';
                                    },

                                    // ── Color for a given cls ──
                                    colorFor: function(cls) {
                                        switch (cls) {
                                            case 'eta-here':
                                                return '#34d399';
                                            case 'eta-soon':
                                                return '#fbbf24';
                                            case 'eta-near':
                                                return '#fb923c';
                                            default:
                                                return '#6b7280';
                                        }
                                    },

                                    // ── Start GPS tracking ──
                                    start: function() {
    if (this._started || window.userRole === 'driver') return;
    this._started = true;
    var self = this;

    console.log('[ETA] Starting…');

    // Always start periodic refresh — idle until location arrives,
    // then keeps badges/popups/indicator updated
    this._periodicTimer = setInterval(function() {
        self.refresh();
    }, this.PERIODIC_MS);

    // A single attempt is enough to discover that permission was already
    // granted; on success the first fix turns the locate control on (see
    // _receivePosition). If it is denied/unavailable we surface E2.
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(pos) {
                self._receivePosition(pos.coords.latitude, pos.coords.longitude, pos.coords.accuracy);
            },
            function(err) {
                // E2 - location unavailable/denied: tell the guest why ETAs are missing
                if (err && (err.code === 1 || err.code === 2)) {
                    if (window.showMapAlert) window.showMapAlert('location-denied');
                }
            },
            { enableHighAccuracy: true, maximumAge: 30000, timeout: 10000 }
        );
    }

    // The 📍 control may already be tracking (the guest tapped it earlier in
    // the session, or permission was granted in another tab). Re-arm the ETA
    // side of the feed from the control's last fix, without moving the camera.
    try {
        var ctrlRef = window.geolocateCtrlRef;
        var lastPos = ctrlRef && ctrlRef._lastKnownPosition;
        if (lastPos && window.ETA) {
            window.ETA._receivePosition(lastPos.coords.latitude, lastPos.coords.longitude, lastPos.coords.accuracy);
        }
    } catch (e) {
        console.warn('[ETA] Could not read the last known position:', e);
    }
},

                                    // ── Called by GeolocateControl event OR our own watch ──
                                    _receivePosition: function(lat, lng, accuracy) {
                                        var changed = (this.userLat !== lat || this.userLng !== lng);
                                        this.userLat = lat;
                                        this.userLng = lng;
                                        this.userAccuracy = accuracy;
                                        if (changed) {
                                            this._scheduleUpdate();
                                        }
                                        // E2 no longer applies once a fix arrives
                                        if (window.clearMapAlert) window.clearMapAlert('location-denied');
                                        // The first fix may arrive BEFORE the map exists
                                        // (getCurrentPosition resolves in ~50ms, while the
                                        // map is built in a deferred module script), so the
                                        // centring/auto-enable is retried by
                                        // window.applyInitialPosition() once the map is ready.
                                        if (typeof window.applyInitialPosition === 'function') {
                                            window.applyInitialPosition();
                                        }
                                    },

                                    stop: function() {
    clearTimeout(this._debounceTimer);
    clearInterval(this._periodicTimer);
    this._started = false;
},

                                    _scheduleUpdate: function() {
                                        var self = this;
                                        clearTimeout(this._debounceTimer);
                                        this._debounceTimer = setTimeout(function() {
                                            self.refresh();
                                        }, this.DEBOUNCE_MS);
                                    },

                                    // ── Main refresh ──
                                    // The Nearest-PUJ indicator runs FIRST and every
                                    // stage is isolated: a single throw in badge or
                                    // popup rendering must never leave the indicator
                                    // stuck hidden.
                                    refresh: function() {
                                        var self = this;
                                        this._safe('_updateNearestIndicator', this._updateNearestIndicator);
                                        if (this.userLat === null) return;
                                        this._safe('_updateBadges', this._updateBadges);
                                        this._safe('_updatePopups', this._updatePopups);
                                    },

                                    _safe: function(name, fn) {
                                        try {
                                            fn.call(this);
                                        } catch (err) {
                                            console.error('[ETA] ' + name + ' failed:', err);
                                        }
                                    },

                                    // ── Collect all vehicle markers ──
                                    _allMarkers: function() {
                                        var all = {};
                                        if (window.dummyMapMarkers) Object.assign(all, window.dummyMapMarkers);
                                        if (window.echoMarkers) Object.assign(all, window.echoMarkers);
                                        return all;
                                    },

                                    // ── Update ETA badges on marker elements ──
_updateBadges: function() {
    var markers = this._allMarkers();
    var self = this;

    Object.keys(markers).forEach(function(id) {
        var marker = markers[id];
        var el = marker.getElement();
        if (!el) return;

        var old = el.querySelector('.eta-badge');
        if (old) old.remove();

        var ll = marker.getLngLat();
        var pr = 200;
        if (window.driverPrivacyZones && window.driverPrivacyZones[id]) {
            pr = window.driverPrivacyZones[id].radius || 200;
        }

        var e = self.calc(ll.lat, ll.lng, pr);
        if (!e || e.distKm > self.BADGE_MAX_DIST_KM) return;

        var f = self.fmt(e);
        var badge = document.createElement('div');
        badge.className = 'eta-badge ' + f.cls;
        badge.textContent = f.text;
        el.appendChild(badge);
    });
},

                                    // ── Update ETA sections inside open popups ──
                                    _updatePopups: function() {
    if (this.userLat === null) return;
    var self = this;

    // Dummy markers
    Object.keys(window.dummyMapPopups || {}).forEach(function(id) {
        var entry = window.dummyMapPopups[id];
        if (!entry || !entry.popup) return;
        var marker = window.dummyMapMarkers[id];
        if (!marker) return;
        var ll = marker.getLngLat();
        var data = Object.assign({}, entry.data, { lat: ll.lat, lng: ll.lng });
        entry.popup.setHTML(window.createPrivacyPopup(data));
    });

    // Live (websocket) markers - always recompute from the marker's CURRENT
    // position so the popup never shows a stale/wrong location or ETA.
    Object.keys(window.echoPopups || {}).forEach(function(id) {
        var entry = window.echoPopups[id];
        if (!entry || !entry.popup) return;
        var marker = window.echoMarkers && window.echoMarkers[id];
        if (!marker) return;
        var ll = marker.getLngLat();
        var data = Object.assign({}, entry.data, { lat: ll.lat, lng: ll.lng });
        entry.popup.setLngLat([ll.lng, ll.lat]);
        entry.popup.setHTML(window.createPrivacyPopup(data));
    });
},
                                    // ── Update the floating nearest-vehicle indicator ──
                                    _updateNearestIndicator: function() {
                                        var indicator = document.getElementById('nearest-vehicle-indicator');
                                        if (!indicator) return;

                                        var markers = this._allMarkers();
                                        var self = this;
                                        var best = null;
                                        var bestMarker = null;

                                        Object.keys(markers).forEach(function(id) {
                                            var ll = markers[id].getLngLat();
                                            var pr = 200;
                                            if (window.driverPrivacyZones && window.driverPrivacyZones[id]) {
                                                pr = window.driverPrivacyZones[id].radius || 200;
                                            }
                                            var e = self.calc(ll.lat, ll.lng, pr);
                                            if (e && (!best || e.distKm < best.distKm)) {
                                                best = e;
                                                bestMarker = markers[id];
                                            }
                                        });

                                        if (!best || best.distKm > this.INDICATOR_MAX_DIST_KM) {
                                            indicator.classList.add('hidden');
                                            return;
                                        }

                                        var f = self.fmt(best);
                                        var color = self.colorFor(f.cls);

                                        indicator.classList.remove('hidden');
                                        indicator.querySelector('.nv-time').textContent = f.text;
                                        indicator.querySelector('.nv-time').style.color = color;
                                        indicator.querySelector('.nv-distance').textContent = self.fmtDist(best.distKm);
                                        indicator.querySelector('.nv-dot').style.background = color;

                                        // Click → fly to nearest vehicle
                                        indicator.onclick = function() {
                                            if (!bestMarker) return;
                                            var ll = bestMarker.getLngLat();
                                            window.map.flyTo({
                                                center: [ll.lng, ll.lat],
                                                zoom: Math.max(window.map.getZoom(), 16),
                                                duration: 800
                                            });
                                            // Also open the popup
                                            if (bestMarker.togglePopup) bestMarker.togglePopup();
                                        };
                                    },

                                    // ── Public: get nearest vehicle data ──
                                    getNearest: function() {
                                        if (this.userLat === null) return null;
                                        var markers = this._allMarkers();
                                        var self = this;
                                        var best = null;
                                        Object.keys(markers).forEach(function(id) {
                                            var ll = markers[id].getLngLat();
                                            var pr = 200;
                                            if (window.driverPrivacyZones && window.driverPrivacyZones[id]) {
                                                pr = window.driverPrivacyZones[id].radius || 200;
                                            }
                                            var e = self.calc(ll.lat, ll.lng, pr);
                                            if (e && (!best || e.distKm < best.distKm)) best = e;
                                        });
                                        return best;
                                    },
// ── Watch DOM for popup sections appearing ──

                                };

                                // ── Auto-start for commuters ──
                                if (window.userRole && window.userRole !== 'driver') {
                                    window.ETA.start();
                                }

                    </script>

                    @if (
                        (Auth::check() && Auth::user()->roles[0]->name === 'admin') ||
                            Auth::check() && (Auth::user()->roles[0]->name === 'commuter'))
                        @env('local')
                            <div id="left-sidebar-form"
    class="absolute top-24 left-[368px] w-[340px] z-40 hidden md:flex flex-col gap-3 max-h-[calc(100vh-120px)] overflow-y-auto custom-scroll p-3 pb-6">

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
                                            class="text-[7px] font-bold uppercase tracking-widest text-[#333] bg-[#111] px-2 py-1 rounded-md border border-[#1e1e1e]">
                                            {{ isset($dummyMarkers) ? $dummyMarkers->count() : 0 }} markers
                                        </span>
                                    </div>

                                    <!-- Add marker form -->
                                    <form action="{{ route('driver.dev.add-marker') }}" method="POST"
                                        id="dev-marker-form">
                                        @csrf
                                        <input type="hidden" name="lat" id="dev-marker-lat">
                                        <input type="hidden" name="lng" id="dev-marker-lng">
                                        <div class="flex gap-2 mb-3">
                                            <button type="submit" onclick="captureMapCenter()"
                                                class="flex-1 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 flex items-center justify-center gap-2 transition active:scale-[0.98] text-white text-[9px] font-bold uppercase tracking-widest">
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
                                    <div class="space-y-1.5 max-h-[140px] overflow-y-auto custom-scroll pr-0.5">
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
                                                        <button type="button"
                                                            onclick="simInitiate({{ $marker->id }}, '{{ $marker->name }}', 'fa-bus', {{ $marker->lng }}, {{ $marker->lat }})"
                                                            class="w-6 h-6 rounded-md bg-[#1a1a1a] hover:bg-purple-500/20 flex items-center justify-center transition"
                                                            title="Simulate route">
                                                            <i
                                                                class="fa-solid fa-route text-[7px] text-[#555] hover:text-purple-400"></i>
                                                        </button>
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

                                <!-- ══════════ ROUTE SIMULATOR ══════════ -->
                                <div id="sim-panel"
                                    class="glass-card rounded-[1.5rem] border-purple-500/15 overflow-y-auto  hidden">
                                    <div class="p-4 border-b border-[#1e1e1e]">
                                        <div class="flex items-center gap-2.5 mb-1.5">
                                            <div
                                                class="w-7 h-7 bg-purple-500/10 rounded-lg flex items-center justify-center border border-purple-500/20">
                                                <i class="fa-solid fa-route text-purple-400 text-[10px]"></i>
                                            </div>
                                            <h3 class="text-[10px] font-bold uppercase tracking-[0.15em] text-purple-400">
                                                Route Simulator</h3>
                                        </div>
                                        <p class="text-[10px] text-[#555] leading-relaxed" id="sim-status-text">
                                            Click the route icon on a marker to begin.
                                        </p>
                                    </div>

                                    <div id="sim-marker-info" class="hidden p-4 border-b border-[#1e1e1e]">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <div
                                                    class="w-6 h-6 rounded-md bg-blue-500/15 flex items-center justify-center">
                                                    <i class="fa-solid fa-bus text-blue-400 text-[9px]"
                                                        id="sim-marker-icon"></i>
                                                </div>
                                                <span class="text-[11px] font-semibold text-[#ddd]"
                                                    id="sim-marker-name">—</span>
                                            </div>
                                            <button onclick="simCancel()"
                                                class="text-[#555] hover:text-red-400 transition text-xs" title="Cancel">
                                                <i class="fa-solid fa-xmark"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div id="sim-waypoints-section" class="hidden">
                                        <div class="px-4 pt-3 pb-2 flex items-center justify-between">
                                            <span class="text-[9px] font-bold uppercase tracking-[0.15em] text-[#555]">
                                                Waypoints <span id="sim-wp-count" class="text-purple-400">0</span>
                                            </span>
                                            <button onclick="simClearWaypoints()"
                                                class="text-[9px] font-bold uppercase tracking-wider text-[#444] hover:text-red-400 transition">Clear</button>
                                        </div>
                                        <div id="sim-waypoint-list"
                                            class="px-4 pb-3 space-y-1 max-h-32 overflow-y-auto custom-scroll"></div>
                                    </div>

                                    <div id="sim-speed-section" class="hidden px-4 py-3 border-t border-[#1e1e1e]">
                                        <div class="flex items-center justify-between mb-2">
                                            <span
                                                class="text-[9px] font-bold uppercase tracking-[0.15em] text-[#555]">Speed</span>
                                            <span class="text-[10px] font-bold text-purple-400 font-mono"
                                                id="sim-kph-display">30 km/h</span>
                                        </div>
                                        <input type="range" id="sim-kph-slider" min="15" max="60"
                                            value="30" step="5"
                                            class="w-full h-1 bg-[#1a1a1a] rounded-full appearance-none cursor-pointer accent-purple-500">
                                        <div class="flex justify-between mt-1.5">
                                            <span class="text-[8px] text-[#333] font-mono">15</span>
                                            <span class="text-[8px] text-[#333] font-mono">60</span>
                                        </div>
                                    </div>
                                    <div id="sim-actions" class="hidden p-4 border-t border-[#1e1e1e] space-y-2">
                                        <div id="sim-actions-place" class="hidden space-y-2">
                                            <button onclick="simFinishPlacing()"
                                                class="w-full py-2.5 rounded-xl bg-purple-600 text-white text-[10px] font-bold uppercase tracking-widest hover:bg-purple-700 transition flex items-center justify-center gap-2 active:scale-[0.98]">
                                                <i class="fa-solid fa-check text-[9px]"></i> Done Placing
                                            </button>
                                            <p class="text-[9px] text-[#333] text-center">Click on the map to add waypoints
                                            </p>
                                        </div>
                                        <div id="sim-actions-ready" class="hidden">
                                            <button onclick="simStart()"
                                                class="w-full py-2.5 rounded-xl bg-blue-600 text-white text-[10px] font-bold uppercase tracking-widest hover:bg-blue-700 transition flex items-center justify-center gap-2 active:scale-[0.98]">
                                                <i class="fa-solid fa-play text-[9px]"></i> Start Simulation
                                            </button>
                                        </div>
                                        <div id="sim-actions-running" class="hidden flex gap-2">
                                            <button onclick="simTogglePause()"
                                                class="flex-1 py-2.5 rounded-xl bg-[#1a1a1a] border border-[#2a2a2a] text-[#ddd] text-[10px] font-bold uppercase tracking-widest hover:bg-[#222] transition flex items-center justify-center gap-2 active:scale-[0.98]">
                                                <i class="fa-solid fa-pause text-[9px]" id="sim-pause-icon"></i>
                                                <span id="sim-pause-label">Pause</span>
                                            </button>
                                            <button onclick="simStop()"
                                                class="flex-1 py-2.5 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-[10px] font-bold uppercase tracking-widest hover:bg-red-500/20 transition flex items-center justify-center gap-2 active:scale-[0.98]">
                                                <i class="fa-solid fa-stop text-[9px]"></i> Stop
                                            </button>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <script>
                                // ══════════════════════════════════════════════
                                // DEV MARKER PLACEMENT
                                // ══════════════════════════════════════════════
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
                                        // Dev placement takes priority
                                        if (devPlacingMode) {
                                            devPlacingMode = false;
                                            var btn = document.getElementById('dev-place-btn');
                                            if (btn) btn.classList.remove('placing');
                                            m.getCanvas().style.cursor = '';
                                            document.getElementById('dev-marker-lat').value = e.lngLat.lat;
                                            document.getElementById('dev-marker-lng').value = e.lngLat.lng;
                                            document.getElementById('dev-marker-form').submit();
                                            return;
                                        }
                                        // Simulator waypoint placement
                                        simHandleMapClick(e);
                                    });
                                    console.log('[DEV] Click handler attached');
                                }

                                attachDevMarkerClick();


                                // ══════════════════════════════════════════════
                                // ROUTE SIMULATOR
                                // ══════════════════════════════════════════════

                                var sim = {
                                    mode: 'idle',
                                    markerId: null,
                                    markerEl: null,
                                    originalLngLat: null,
                                    waypoints: [],
                                    waypointMarkers: [],
                                    routeLine: false,
                                    segmentIndex: 0,
                                    segmentProgress: 0,
                                    speed: 1, // multiplier (½× to 8×)
                                    kph: 30, // base speed in km/h
                                    animFrame: null,
                                    lastTime: null
                                };

                                function simInitiate(markerId, markerName, markerIcon, markerLng, markerLat) {
                                    if (sim.mode === 'running' || sim.mode === 'paused') simStop();
                                    simCleanTemp();

                                    sim.markerId = markerId;
                                    sim.originalLngLat = [markerLng, markerLat];
                                    sim.waypoints = [
                                        [markerLng, markerLat]
                                    ];
                                    sim.segmentIndex = 0;
                                    sim.segmentProgress = 0;

                                    document.getElementById('sim-panel').classList.remove('hidden');
                                    document.getElementById('sim-marker-info').classList.remove('hidden');
                                    document.getElementById('sim-marker-name').textContent = markerName;
                                    document.getElementById('sim-marker-icon').className = 'fa-solid ' + markerIcon + ' text-blue-400 text-[9px]';

                                    simSetMode('placing');
                                }

                                function simSetMode(mode) {
                                    sim.mode = mode;

                                    var statusText = document.getElementById('sim-status-text');
                                    var wpSection = document.getElementById('sim-waypoints-section');
                                    var speedSection = document.getElementById('sim-speed-section');
                                    var actions = document.getElementById('sim-actions');
                                    var actionPlace = document.getElementById('sim-actions-place');
                                    var actionReady = document.getElementById('sim-actions-ready');
                                    var actionRunning = document.getElementById('sim-actions-running');

                                    actionPlace.classList.add('hidden');
                                    actionReady.classList.add('hidden');
                                    actionRunning.classList.add('hidden');
                                    actions.classList.add('hidden');
                                    wpSection.classList.add('hidden');
                                    speedSection.classList.add('hidden');
                                    document.getElementById('map').classList.remove('sim-placeholder-mode');

                                    switch (mode) {
                                        case 'placing':
                                            statusText.textContent = 'Click on the map to add waypoints for the route.';
                                            wpSection.classList.remove('hidden');
                                            actions.classList.remove('hidden');
                                            actionPlace.classList.remove('hidden');
                                            document.getElementById('map').classList.add('sim-placeholder-mode');
                                            break;
                                        case 'ready':
                                            statusText.textContent = 'Route set with ' + (sim.waypoints.length - 1) +
                                            ' stop(s). Ready to simulate.';
                                            wpSection.classList.remove('hidden');
                                            speedSection.classList.remove('hidden');
                                            actions.classList.remove('hidden');
                                            actionReady.classList.remove('hidden');
                                            break;
                                        case 'running':
                                            statusText.textContent = 'Simulating...';
                                            wpSection.classList.remove('hidden');
                                            speedSection.classList.remove('hidden');
                                            actions.classList.remove('hidden');
                                            actionRunning.classList.remove('hidden');
                                            document.getElementById('sim-pause-icon').className = 'fa-solid fa-pause text-[9px]';
                                            document.getElementById('sim-pause-label').textContent = 'Pause';
                                            break;
                                        case 'paused':
                                            statusText.textContent = 'Paused.';
                                            wpSection.classList.remove('hidden');
                                            speedSection.classList.remove('hidden');
                                            actions.classList.remove('hidden');
                                            actionRunning.classList.remove('hidden');
                                            document.getElementById('sim-pause-icon').className = 'fa-solid fa-play text-[9px]';
                                            document.getElementById('sim-pause-label').textContent = 'Resume';
                                            break;
                                        case 'idle':
                                            statusText.textContent = 'Click the route icon on a marker to begin.';
                                            break;
                                    }

                                    simRenderWaypointList();
                                }

                                function simHandleMapClick(e) {
                                    if (sim.mode !== 'placing') return;
                                    var lng = e.lngLat.lng;
                                    var lat = e.lngLat.lat;
                                    sim.waypoints.push([lng, lat]);
                                    simAddWaypointDot(lng, lat);
                                    simUpdateRouteLine();
                                    simRenderWaypointList();
                                }

                                function simAddWaypointDot(lng, lat) {
                                    var el = document.createElement('div');
                                    el.className = 'sim-waypoint-dot' + (sim.waypointMarkers.length === 0 ? ' first' : '');
                                    var m = new maplibregl.Marker({
                                            element: el,
                                            anchor: 'center'
                                        })
                                        .setLngLat([lng, lat])
                                        .addTo(window.map);
                                    sim.waypointMarkers.push(m);
                                }

                                function simUpdateRouteLine() {
                                    if (sim.routeLine) {
                                        try {
                                            window.map.removeLayer('sim-route-line');
                                        } catch (e) {}
                                        try {
                                            window.map.removeSource('sim-route-source');
                                        } catch (e) {}
                                        sim.routeLine = false;
                                    }
                                    if (sim.waypoints.length < 2) return;

                                    window.map.addSource('sim-route-source', {
                                        type: 'geojson',
                                        data: {
                                            type: 'Feature',
                                            geometry: {
                                                type: 'LineString',
                                                coordinates: sim.waypoints.slice()
                                            }
                                        }
                                    });
                                    window.map.addLayer({
                                        id: 'sim-route-line',
                                        type: 'line',
                                        source: 'sim-route-source',
                                        layout: {
                                            'line-join': 'round',
                                            'line-cap': 'round'
                                        },
                                        paint: {
                                            'line-color': '#a78bfa',
                                            'line-width': 3,
                                            'line-opacity': 0.6,
                                            'line-dasharray': [2, 2]
                                        }
                                    });
                                    sim.routeLine = true;
                                }

                                function simRenderWaypointList() {
                                    var list = document.getElementById('sim-waypoint-list');
                                    var count = document.getElementById('sim-wp-count');
                                    count.textContent = Math.max(0, sim.waypoints.length - 1);
                                    list.innerHTML = '';

                                    sim.waypoints.forEach(function(wp, i) {
                                        var row = document.createElement('div');
                                        row.className = 'flex items-center gap-2 py-1.5 px-2 rounded-lg ' + (i === 0 ? 'bg-emerald-500/5' :
                                            'bg-purple-500/5');
                                        var removeBtn = i > 0 ?
                                            '<button onclick="simRemoveWaypoint(' + i +
                                            ')" class="ml-auto text-[#444] hover:text-red-400 transition"><i class="fa-solid fa-xmark text-[8px]"></i></button>' :
                                            '';
                                        row.innerHTML =
                                            '<span class="w-4 h-4 rounded-full text-[7px] font-bold flex items-center justify-center ' +
                                            (i === 0 ? 'bg-emerald-500/20 text-emerald-400' : 'bg-purple-500/20 text-purple-400') + '">' +
                                            i + '</span>' +
                                            '<span class="text-[10px] text-[#888] font-mono">' + wp[1].toFixed(5) + ', ' + wp[0].toFixed(
                                            5) + '</span>' +
                                            removeBtn;
                                        list.appendChild(row);
                                    });
                                }

                                function simRemoveWaypoint(index) {
                                    if (index <= 0 || sim.mode === 'running' || sim.mode === 'paused') return;
                                    sim.waypoints.splice(index, 1);
                                    sim.waypointMarkers.forEach(function(m) {
                                        m.remove();
                                    });
                                    sim.waypointMarkers = [];
                                    sim.waypoints.forEach(function(wp) {
                                        simAddWaypointDot(wp[0], wp[1]);
                                    });
                                    simUpdateRouteLine();
                                    simRenderWaypointList();
                                }

                                function simClearWaypoints() {
                                    if (sim.mode === 'running' || sim.mode === 'paused') return;
                                    sim.waypoints = [sim.originalLngLat];
                                    sim.waypointMarkers.forEach(function(m) {
                                        m.remove();
                                    });
                                    sim.waypointMarkers = [];
                                    if (sim.routeLine) {
                                        try {
                                            window.map.removeLayer('sim-route-line');
                                        } catch (e) {}
                                        try {
                                            window.map.removeSource('sim-route-source');
                                        } catch (e) {}
                                        sim.routeLine = false;
                                    }
                                    simRenderWaypointList();
                                }

                                function simFinishPlacing() {
                                    if (sim.waypoints.length < 2) {
                                        document.getElementById('sim-status-text').textContent = 'Add at least one waypoint before starting.';
                                        return;
                                    }
                                    simSetMode('ready');
                                }

                                // Speed buttons
                                document.getElementById('sim-speed-btns')?.addEventListener('click', function(e) {
                                    var btn = e.target.closest('.sim-speed-btn');
                                    if (!btn) return;
                                    sim.speed = parseFloat(btn.dataset.speed);
                                    var all = document.querySelectorAll('.sim-speed-btn');
                                    for (var i = 0; i < all.length; i++) all[i].classList.remove('active');
                                    btn.classList.add('active');
                                });

                                function simStart() {
                                    if (sim.waypoints.length < 2) return;

                                    sim.markerEl = window.dummyMapMarkers[sim.markerId] || null;
                                    if (sim.markerEl) {
                                        var el = sim.markerEl.getElement();
                                        if (el) el.classList.add('sim-marker-active');
                                    }

                                    sim.segmentIndex = 0;
                                    sim.segmentProgress = 0;
                                    sim.lastTime = null;
                                    simSetMode('running');
                                    sim.animFrame = requestAnimationFrame(simTick);
                                }

                                function simTick(timestamp) {
                                    if (sim.mode !== 'running') return;

                                    if (!sim.lastTime) sim.lastTime = timestamp;
                                    var deltaMs = timestamp - sim.lastTime;
                                    sim.lastTime = timestamp;

                                    var from = sim.waypoints[sim.segmentIndex];
                                    var to = sim.waypoints[sim.segmentIndex + 1];
                                    if (!to) {
                                        simComplete();
                                        return;
                                    }

                                    // Distance in km between the two waypoints
                                    var dLng = to[0] - from[0];
                                    var dLat = to[1] - from[1];
                                    var distKm = haversine(from[1], from[0], to[1], to[0]);

                                    if (distKm < 0.0001) {
                                        // Waypoints are basically on top of each other, skip
                                        sim.segmentProgress = 0;
                                        sim.segmentIndex++;
                                        if (sim.segmentIndex >= sim.waypoints.length - 1) sim.segmentIndex = 0;
                                        sim.lastTime = null;
                                        sim.animFrame = requestAnimationFrame(simTick);
                                        return;
                                    }

                                    // How long this segment takes at current speed
                                    var effectiveKph = sim.kph * sim.speed;
                                    var segmentDurationMs = (distKm / effectiveKph) * 3600000;

                                    sim.segmentProgress += deltaMs / segmentDurationMs;

                                    if (sim.segmentProgress >= 1) {
                                        sim.segmentProgress = 0;
                                        sim.segmentIndex++;
                                        if (sim.segmentIndex >= sim.waypoints.length - 1) {
                                            sim.segmentIndex = 0;
                                        }
                                    }

                                    var curFrom = sim.waypoints[sim.segmentIndex];
                                    var curTo = sim.waypoints[sim.segmentIndex + 1];
                                    if (!curTo) {
                                        simComplete();
                                        return;
                                    }

                                    var lng = curFrom[0] + (curTo[0] - curFrom[0]) * sim.segmentProgress;
                                    var lat = curFrom[1] + (curTo[1] - curFrom[1]) * sim.segmentProgress;

                                    if (sim.markerEl) {
                                        sim.markerEl.setLngLat([lng, lat]);
                                    }

 // Refresh ETA every ~2 seconds during simulation
    var now = Date.now();
    if (!sim._lastEtaUpdate || now - sim._lastEtaUpdate > 2000) {
        sim._lastEtaUpdate = now;
        if (window.ETA) window.ETA.refresh();
    }

                                    sim.animFrame = requestAnimationFrame(simTick);
                                }

                                function haversine(lat1, lon1, lat2, lon2) {
                                    var R = 6371;
                                    var dLat = (lat2 - lat1) * Math.PI / 180;
                                    var dLon = (lon2 - lon1) * Math.PI / 180;
                                    var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                                        Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                                        Math.sin(dLon / 2) * Math.sin(dLon / 2);
                                    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
                                }

                                function simTogglePause() {
                                    if (sim.mode === 'running') {
                                        sim.mode = 'paused';
                                        cancelAnimationFrame(sim.animFrame);
                                        simSetMode('paused');
                                    } else if (sim.mode === 'paused') {
                                        sim.lastTime = null;
                                        simSetMode('running');
                                        sim.animFrame = requestAnimationFrame(simTick);
                                    }
                                }

                                function simStop() {
                                    cancelAnimationFrame(sim.animFrame);
                                    sim.animFrame = null;
                                    sim._lastEtaUpdate = null;

                                    if (sim.markerEl && sim.originalLngLat) {
                                        sim.markerEl.setLngLat(sim.originalLngLat);
                                        var el = sim.markerEl.getElement();
                                        if (el) el.classList.remove('sim-marker-active');
                                    }

                                    simCleanTemp();
                                    simSetMode('idle');
                                    document.getElementById('sim-panel').classList.add('hidden');
                                }

                                function simComplete() {
                                    sim.segmentIndex = 0;
                                    sim.segmentProgress = 0;
                                    sim.lastTime = null;
                                }

                                function simCancel() {
                                    if (sim.mode === 'running' || sim.mode === 'paused') {
                                        cancelAnimationFrame(sim.animFrame);
                                        sim.animFrame = null;
                                    }
                                    if (sim.markerEl) {
                                        var el = sim.markerEl.getElement();
                                        if (el) el.classList.remove('sim-marker-active');
                                        if (sim.originalLngLat) sim.markerEl.setLngLat(sim.originalLngLat);
                                    }
                                    simCleanTemp();
                                    simSetMode('idle');
                                    document.getElementById('sim-panel').classList.add('hidden');
sim._lastEtaUpdate = null;
                                }

                                function simCleanTemp() {
                                    sim.waypointMarkers.forEach(function(m) {
                                        m.remove();
                                    });
                                    sim.waypointMarkers = [];
                                    sim.waypoints = [];
                                    sim.originalLngLat = null;
                                    sim.markerEl = null;
                                    sim.markerId = null;
                                    if (sim.routeLine) {
                                        try {
                                            window.map.removeLayer('sim-route-line');
                                        } catch (e) {}
                                        try {
                                            window.map.removeSource('sim-route-source');
                                        } catch (e) {}
                                        sim.routeLine = false;
                                    }

                                    var kphSlider = document.getElementById('sim-kph-slider');
                                    var kphDisplay = document.getElementById('sim-kph-display');
                                    if (kphSlider) {
                                        kphSlider.addEventListener('input', function() {
                                            sim.kph = parseInt(this.value);
                                            kphDisplay.textContent = sim.kph + ' km/h';
                                        });
                                    }
                                }
                            </script>
                        @endenv
                    @endif
                    @if (
                        !Auth::check() ||
                            (Auth::check() && !in_array(Auth::user()->roles[0]->name, ['maintenance_manager', 'driver_manager'])))
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
                    class="fixed top-24 right-4 sm:right-2 w-[340px] z-40 hidden md:flex flex-col gap-3 max-h-[calc(100vh-120px)] overflow-y-auto overscroll-contain custom-scroll pr-1 pb-6">

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
                                    <span class="text-[10px] text-[#555]" id="gps-status-text">GPS: Standby</span>
                                </div>
                                @unless ($driverVehicleId ?? null)
                                    <div
                                        class="w-full h-9 rounded-xl border border-amber-500/30 bg-amber-500/10 text-amber-400 text-[9px] font-bold uppercase tracking-wider flex items-center justify-center gap-2 mb-3">
                                        <i class="fa-solid fa-triangle-exclamation text-[9px]"></i>
                                        No vehicle assigned
                                    </div>
                                @endunless
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
                                    class="fa-solid fa-satellite-dish mr-0.5"></i> Your location is shared with
                                passengers automatically</p>
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

            var LEFT_MOBILE_CLASSES = 'flex flex-col gap-3 w-full';
            var RIGHT_MOBILE_CLASSES = 'flex flex-col gap-3 w-full';

            /* Remember the panel's server-rendered class list before it is
               moved into the sheet. Restoring a hardcoded string broke the
               driver/commuter panels (they are `fixed top-24 left-4`, not the
               `absolute left-[368px]` of the local dev-tools panel) once the
               sheet closed or the phone was rotated. */
            function stashDesktopClasses(el, key) {
                if (!el.dataset[key]) {
                    el.dataset[key] = el.className;
                }
            }

            function restoreDesktopClasses(el, key) {
                if (el && el.dataset[key]) {
                    el.className = el.dataset[key];
                    delete el.dataset[key];
                }
            }

            function openMobileSidebar(type) {
                if (type === 'left') {
                    var el = document.getElementById('left-sidebar-form');
                    if (!el) return;
                    stashDesktopClasses(el, 'desktopClasses');
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
                    stashDesktopClasses(el, 'desktopClasses');
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
                        restoreDesktopClasses(el, 'desktopClasses');
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
                        restoreDesktopClasses(el, 'desktopClasses');
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
            /* userRole / PRIVACY_RADIUS / marker registries are bootstrapped in
               <head> (see the note there) - don't clobber them here. */
            if (!window.userRole) {
                window.userRole = '{{ Auth::check() ? Auth::user()->roles->first()->name : 'guest' }}';
                window.PRIVACY_RADIUS = 200;
            }

            /* ══════════════════════════════════════════════
             * MAP STATUS BANNER  (UCN_SC_E003 exceptions)
             *  E1 - no PUJ markers            -> 'no-puj'
             *  E2 - location permission denied -> 'location-denied'
             *  E3 - map/tile service failure  -> 'map-service'
             *  E4 - device offline            -> 'offline'
             *  E4/E004 - routing service down -> 'routing-service'
             *  E4/E004 - no place found      -> 'location-not-found'
             * Priorities: offline > map-service > routing-service > location-denied > no-puj
             * ══════════════════════════════════════════════ */
            window.mapAlerts = {};
            var MAP_ALERT_META = {
                'offline': {
                    type: 'error',
                    icon: 'fa-wifi',
                    title: 'No internet connection',
                    msg: 'You are offline. The map and PUJ locations cannot be refreshed until the connection returns.'
                },
                'map-service': {
                    type: 'error',
                    icon: 'fa-triangle-exclamation',
                    title: 'Map service unavailable',
                    msg: 'The mapping service failed to load. Please refresh the page or try again later.'
                },
                'location-denied': {
                    type: 'warning',
                    icon: 'fa-location-crosshairs',
                    title: 'Location access needed',
                    msg: 'Enable location access to see the map centred on you and to get PUJ ETAs.'
                },
                'routing-service': {
                    type: 'error',
                    icon: 'fa-route',
                    title: 'Route unavailable',
                    msg: 'The routing service failed to respond, so the distance and fare could not be calculated. Please try again.'
                },
                'location-not-found': {
                    type: 'warning',
                    icon: 'fa-magnifying-glass',
                    title: 'Location not found',
                    msg: 'That place could not be found. Try a different name, or tap a point directly on the map.'
                },
                'no-puj': {
                    type: 'info',
                    icon: 'fa-bus',
                    title: 'No PUJs available',
                    msg: 'No PUJ drivers are clocked in right now, so there are no markers on the map.'
                }
            };
            var MAP_ALERT_PRIORITY = ['offline', 'map-service', 'routing-service', 'location-denied', 'no-puj'];

            window.showMapAlert = function(key) {
                window.mapAlerts[key] = true;
                window.renderMapAlert();
            };

            window.clearMapAlert = function(key) {
                delete window.mapAlerts[key];
                window.renderMapAlert();
            };

            window.renderMapAlert = function() {
                var el = document.getElementById('map-alert');
                if (!el) return;
                var active = MAP_ALERT_PRIORITY.filter(function(k) {
                    return window.mapAlerts[k];
                });
                if (!active.length) {
                    if (el.dataset.active) delete el.dataset.active;
                    el.classList.add('hidden');
                    el.setAttribute('aria-hidden', 'true');
                    return;
                }
                var key = active[0];
                var meta = MAP_ALERT_META[key];
                // Skip DOM work when the same alert is already on screen, so a
                // repeated event can't restart the entry animation/flicker.
                if (el.dataset.active === key) return;
                el.dataset.type = meta.type;
                el.classList.remove('hidden');
                el.setAttribute('aria-hidden', 'false');
                el.querySelector('.ma-icon').className = 'fa-solid ' + meta.icon + ' ma-icon';
                document.getElementById('map-alert-title').textContent = meta.title;
                document.getElementById('map-alert-msg').textContent = meta.msg;
            };

            /* ── E1: show/hide the "no PUJ" banner from marker state ── */
            window.updatePujEmptyState = function() {
                // Markers live in the DOM (not the style), so this works even
                // while the basemap style is still loading - it used to bail out
                // on isStyleLoaded() and the "no PUJ" banner never appeared.
                var count = Object.keys(window.dummyMapMarkers || {}).length +
                    Object.keys(window.echoMarkers || {}).length;
                window.hasPuj = count > 0;
                if (count > 0) {
                    window.clearMapAlert('no-puj');
                } else {
                    window.showMapAlert('no-puj');
                }
            };

            /* Don't announce "no PUJs" until BOTH marker sources have been asked,
             * otherwise production (where /api/markers is empty by design) flashes
             * the banner before the live poll arrives. A timer guarantees we still
             * resolve if an endpoint never answers. */
            window.pujSources = {
                dummy: false,
                live: false
            };

            window.markPujSourceLoaded = function(source) {
                window.pujSources[source] = true;
                clearTimeout(window.pujFallbackTimer);
                if (window.pujSources.dummy && window.pujSources.live) {
                    window.updatePujEmptyState();
                } else {
                    window.pujFallbackTimer = setTimeout(window.updatePujEmptyState, 4000);
                }
            };

            /* ── E4: offline / online listeners ── */
            window.addEventListener('offline', function() {
                window.showMapAlert('offline');
            });

            // A device that is *already* offline when the page loads never
            // fires an 'offline' event, so seed the state explicitly.
            if (navigator.onLine === false) {
                window.showMapAlert('offline');
            }
            window.addEventListener('online', function() {
                window.clearMapAlert('offline');
                if (typeof window.loadDummyMarkers === 'function') window.loadDummyMarkers();
            });

            /* ── A1 (UCN_SC_E002): guests buying a ride are sent to REGISTER,
             *    not login. Commuters fall through to the normal payment flow. ── */
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelectorAll('form[data-guest-register]').forEach(function(form) {
                    var target = form.getAttribute('data-guest-register');
                    if (!target) return;
                    form.addEventListener('submit', function(e) {
                        e.preventDefault();
                        // Preserve the chosen points so they survive registration
                        try {
                            var pickup = form.querySelector('[name="pickup"]');
                            var dropoff = form.querySelector('[name="dropoff"]');
                            if (pickup && pickup.value) {
                                sessionStorage.setItem('pendingPickup', pickup.value);
                            }
                            if (dropoff && dropoff.value) {
                                sessionStorage.setItem('pendingDropoff', dropoff.value);
                            }
                        } catch (err) {}
                        window.location.href = target;
                    });
                });
            });
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
                // Vehicle status: derived when the payload carries it, otherwise 'Active'
                var statusText = 'Active';
                var statusColor = '#34d399';
                if (d.driver_status === 'active') {
                    statusText = 'Available';
                } else if (d.driver_status === 'inactive' || d.driver_status === 'unavailable') {
                    statusText = 'Unavailable';
                    statusColor = '#ef4444';
                } else if (d.marker_status && d.marker_status !== 'active') {
                    statusText = 'Inactive';
                    statusColor = 'var(--pp-muted)';
                }
                if (d.last_update) {
                    var mins = (Date.now() - new Date(d.last_update).getTime()) / 60000;
                    if (mins > 5) {
                        statusText = 'Signal lost';
                        statusColor = 'var(--pp-muted)';
                    }
                }

                var esc = function(v) {
                    if (v === undefined || v === null) return '';
                    return String(v)
                        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;');
                };

                var pr = d.privacy_radius || window.PRIVACY_RADIUS || 200;

                var etaSection = '';
                if (window.userRole !== 'driver') {
                    var etaText = 'Locating you\u2026';
                    var etaDist = 'Tap \ud83d\udccd to see ETA';
                    var etaColor = 'var(--pp-muted)';
                    var etaLabel = 'ETA';

                    if (window.ETA && window.ETA.userLat !== null && d.lat && d.lng) {
                        var e = window.ETA.calc(d.lat, d.lng, pr);
                        if (e) {
                            var f = window.ETA.fmt(e);
                            etaText = f.text;
                            etaDist = window.ETA.fmtDist(e.distKm);
                            etaColor = window.ETA.colorFor(f.cls);
                            etaLabel = e.here ? 'NOW' : 'ETA';
                        }
                    }

                    etaSection =
                        '<div class="pp-divider"></div>' +
                        '<div class="pp-eta">' +
                        '<div class="pp-eta-icon"><i class="fa-solid fa-route" style="font-size:10px;color:' + etaColor + ';opacity:0.85;"></i></div>' +
                        '<div class="pp-eta-text">' +
                        '<div class="pp-eta-main" style="color:' + etaColor + ';">' + esc(etaText) + '</div>' +
                        '<div class="pp-eta-sub">' + esc(etaDist) + '</div>' +
                        '</div>' +
                        '<div class="pp-eta-label" style="color:' + etaColor + ';">' + etaLabel + '</div>' +
                        '</div>' +
                        '<div class="pp-accuracy">\u2248 ' + pr + 'm accuracy zone</div>';
                }

                return '<div class="pp">' +
                    '<div class="pp-head">' +
                    '<div class="pp-head-icon"><i class="fa-solid fa-bus" style="color:#60a5fa;font-size:12px;"></i></div>' +
                    '<div class="pp-head-text">' +
                    '<p class="pp-title">' + esc(d.plate_number || d.name || 'Vehicle') + '</p>' +
                    '<p class="pp-sub">' + esc(d.route || 'Route') + '</p>' +
                    '</div>' +
                    '</div>' +
                    '<div class="pp-note">' +
                    '<i class="fa-solid fa-shield-halved" style="color:rgba(59,130,246,0.55);font-size:9px;flex:0 0 auto;"></i>' +
                    '<span style="overflow:hidden;text-overflow:ellipsis;">Approximate location \u00b7 ~' + pr + 'm radius</span>' +
                    '</div>' +
                    '<div class="pp-status">' +
                    '<span class="dot" style="background:' + statusColor + ';"></span>' +
                    '<span style="color:' + statusColor + ';">' + esc(statusText) + '</span>' +
                    '<span class="lbl">Vehicle status</span>' +
                    '</div>' +
                    etaSection +
                    '</div>';
            };

            /* Driver-facing popup: exact plate / type / route + driver availability */
            window.createDriverPopup = function(d) {
                var esc = function(v) {
                    if (v === undefined || v === null) return '';
                    return String(v)
                        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;');
                };

                var isActive = d.driver_status === 'active';
                var statusBg = isActive ? 'rgba(16,185,129,0.1)' : 'rgba(239,68,68,0.1)';
                var statusBorder = isActive ? 'rgba(16,185,129,0.2)' : 'rgba(239,68,68,0.2)';
                var statusColor = isActive ? '#34d399' : '#ef4444';
                var statusLabel = isActive ? 'Available' : 'Unavailable';
                var statusIcon = isActive ? 'fa-circle-check' : 'fa-circle-xmark';

                var rows = [
                    ['Plate', d.plate_number || 'N/A', true],
                    ['Type', d.vehicle_type || 'N/A', false],
                    ['Route', d.route || 'N/A', false]
                ].map(function(r) {
                    return '<div class="pp-row"><span class="k">' + r[0] +
                        '</span><span class="v' + (r[2] ? ' mono' : '') + '">' + esc(r[1]) + '</span></div>';
                }).join('');

                return '<div class="pp">' +
                    '<div class="pp-head">' +
                    '<div class="pp-head-icon"><i class="fa-solid fa-bus" style="color:#60a5fa;font-size:12px;"></i></div>' +
                    '<div class="pp-head-text">' +
                    '<p class="pp-title">' + esc(d.name || 'Driver') + '</p>' +
                    '<p class="pp-sub">Driver</p>' +
                    '</div>' +
                    '</div>' +
                    '<div class="pp-divider"></div>' +
                    '<div class="pp-driver-status" style="background:' + statusBg + ';border:1px solid ' + statusBorder + ';">' +
                    '<i class="fa-solid ' + statusIcon + '" style="font-size:9px;color:' + statusColor + ';"></i>' +
                    '<span style="color:' + statusColor + ';">' + statusLabel + '</span>' +
                    '<span class="lbl">Driver status</span>' +
                    '</div>' +
                    rows +
                    '</div>';
            };
        </script>

        <script type="module">
            const userRole = @json(Auth::user())?.roles[0]?.name ?? 'guest';
            const userId = @json(Auth::user())?.id ?? null;
            const pusherKey = '{{ env('PUSHER_APP_KEY') }}';
            const pusherCluster = '{{ env('PUSHER_APP_CLUSTER') }}';
            const driverVehicleId = @json($driverVehicleId ?? null);
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

            // Basemap follows the theme that was applied in <head>
            const MAP_STYLE_FALLBACK = 'https://tiles.openfreemap.org/styles/bright';
            const prefersDark = document.documentElement.classList.contains('dark');
            window.currentMapStyleUrl = prefersDark ?
                'https://tiles.openfreemap.org/styles/liberty' :
                'https://tiles.openfreemap.org/styles/bright';

            const map = new maplibregl.Map({
                container: 'map',
                style: window.currentMapStyleUrl,
                center: [123.79, 10.24],
                zoom: 13,
                rollEnabled: true,
                maxBounds: bounds
            });

            window.map = map;

            // A permission fix may already be waiting (resolved before this
            // module ran) - apply it now.
            if (typeof window.applyInitialPosition === 'function') {
                window.applyInitialPosition();
            }

            // ═══════════════ NAV CONTROLS ═══════════════
            map.addControl(new maplibregl.NavigationControl({
                visualizePitch: true
            }), 'bottom-right');

            // ── Share Location (Geolocate) Button ──
            var geolocateCtrl = new maplibregl.GeolocateControl({
                positionOptions: {
                    enableHighAccuracy: true
                },
                trackUserLocation: true,
                showUserHeading: true
            });
            map.addControl(geolocateCtrl, 'bottom-right');

            // Expose the control so the ETA engine can reuse its last fix
            window.geolocateCtrlRef = geolocateCtrl;

            /* ── Auto-enable the locate control once permission is granted ──
               GeolocateControl only shows its active (blue) state after the
               user taps it, so a previously-granted permission left the button
               looking "off" even though we already had a fix. `trigger()` is a
               toggle (OFF <-> ACTIVE_LOCK), so it must only ever be called
               while the state is OFF - otherwise we would switch tracking
               back off again. */
            window.locationTrackingState = function() {
                return geolocateCtrl && geolocateCtrl._watchState ? geolocateCtrl._watchState : 'NONE';
            };

            window.enableLocationTracking = function() {
                try {
                    if (!geolocateCtrl || !geolocateCtrl._setup) return false; // not on the map yet
                    if (window.locationTrackingState() !== 'OFF') return true; // already tracking/waiting
                    geolocateCtrl.trigger(); // OFF -> WAITING_ACTIVE -> ACTIVE_LOCK
                    return true;
                } catch (e) {
                    console.warn('[Geo] Could not enable location tracking:', e);
                    return false;
                }
            };

            /* UCN_SC_E003 step 3 - centre the map on the Guest's location, and
               switch the locate control on the moment permission is granted
               (so the 📍 button is lit up, not just after a manual tap).
               Safe to call repeatedly: it only acts once. */
            window.applyInitialPosition = function() {
                try {
                    if (!window.map || !window.map.flyTo) return false;
                    if (window.userRole === 'driver') return false;
                    if (!window.ETA || window.ETA.userLat === null) return false;
                    if (window.ETA._centeredOnce) return true;

                    window.ETA._centeredOnce = true;

                    // Prefer the control: activating it lights up the button,
                    // draws the blue user dot + accuracy circle, and centres
                    // the map itself.
                    if (window.enableLocationTracking()) return true;

                    window.map.flyTo({
                        center: [window.ETA.userLng, window.ETA.userLat],
                        zoom: 15,
                        duration: 1500
                    });
                    return true;
                } catch (e) {
                    console.warn('[Geo] Could not apply the initial position:', e);
                    return false;
                }
            };

            window.disableLocationTracking = function() {
                try {
                    const state = window.locationTrackingState();
                    if (state === 'OFF' || state === 'NONE') return false;
                    // WAITING_ACTIVE/ACTIVE_LOCK/BACKGROUND(_ERROR) all toggle off
                    geolocateCtrl.trigger();
                    return true;
                } catch (e) {
                    return false;
                }
            };

            // ── Feed GeolocateControl position into ETA ──
            geolocateCtrl.on('geolocate', function(e) {
                // A driver tapping the map's own locate button should also start
                // broadcasting (the auto-start may have been waiting on permission).
                if (typeof window.startGPSTracking === 'function') {
                    window.startGPSTracking();
                }

                if (window.ETA) {
                    window.ETA._receivePosition(
                        e.coords.latitude,
                        e.coords.longitude,
                        e.coords.accuracy
                    );
                }
            });
            geolocateCtrl.on('error', function(e) {
                // E2 - PERMISSION_DENIED: the map cannot centre or compute ETAs.
                if (e.error.code === 1) {
                    if (window.showMapAlert) window.showMapAlert('location-denied');
                    return;
                }
                console.log('[ETA] Geolocate error:', e.error.message);
                if (window.showMapAlert) window.showMapAlert('location-denied');
            });

            /* ── E3 - external mapping service (tiles / style) failure ── */
            /* ── E3: detect a genuinely broken basemap, not noisy tile 404s ──
               MapLibre fires `error` for a single missing tile, an aborted
               request or a sprite miss, so counting errors and showing
               "Map service unavailable" produced false alarms. We only alert
               when the STYLE ITSELF never finishes loading (watchdog), or when
               the style fetch fails during a theme swap. */
            // Watchdog: if the style still isn't loaded after this long, it is
            // genuinely unavailable (bad network, blocked CDN, wrong URL).
            var styleWatchdog = setTimeout(function() {
                if (window.map && window.map.isStyleLoaded && !window.map.isStyleLoaded()) {
                    if (window.showMapAlert) window.showMapAlert('map-service');
                }
            }, 12000);

            map.on('load', function() {
                clearTimeout(styleWatchdog);
                if (window.clearMapAlert) window.clearMapAlert('map-service');
                // Marker overlays depend on a loaded map; re-evaluate them.
                if (window.applyInitialPosition) window.applyInitialPosition();
                if (window.ETA) window.ETA.refresh();
            });

            map.on('error', function(e) {
                var err = (e && e.error) || {};

                // Ignore benign, expected noise.
                if (err.name === 'AbortError') return;
                if (err.status === 404 || err.status === 204) return;

                // Only a failure while the style is still pending means the
                // basemap service itself is unreachable.
                var stylePending = !map.isStyleLoaded();

                // Theme swap failed -> fall back once, then stop trying.
                if (window.previousMapStyleUrl && stylePending) {
                    var previous = window.previousMapStyleUrl;
                    window.previousMapStyleUrl = null;
                    window.currentMapStyleUrl = previous;
                    console.warn('[Theme] Basemap style failed to load, reverting to', previous);
                    map.setStyle(previous);
                    return;
                }

                if (!stylePending) return; // per-tile noise on a live map

                clearTimeout(styleWatchdog);
                styleWatchdog = setTimeout(function() {
                    if (!map.isStyleLoaded()) {
                        if (window.showMapAlert) window.showMapAlert('map-service');
                    }
                }, 6000);
            });

            // E3 - style could not be loaded at all
            map.on('styledata', function() {
                if (window.clearMapAlert) window.clearMapAlert('map-service');
                if (typeof window.updatePujEmptyState === 'function') window.updatePujEmptyState();
            });

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
            // Named + idempotent so it can be re-run after a style swap
            // (setStyle() destroys every source/layer with the old style).
            window.initMapLayers = function() {

                // ── Privacy zone source + layers ──
                if (!map.getSource('driver-privacy-zones')) map.addSource('driver-privacy-zones', {
                    type: 'geojson',
                    data: {
                        type: 'FeatureCollection',
                        features: []
                    }
                });
                if (!map.getLayer('driver-privacy-glow')) map.addLayer({
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

                if (!map.getSource('route')) map.addSource('route', {
                    type: 'geojson',
                    data: {
                        type: 'FeatureCollection',
                        features: []
                    }
                });
                if (!map.getLayer('route-line')) map.addLayer({
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
                if (!map.getLayer('route-line-glow')) map.addLayer({
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

                if (!map.getSource('pickup-point')) map.addSource('pickup-point', {
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

                if (!map.getSource('destination-point')) map.addSource('destination-point', {
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

                if (!map.getSource('vehicles')) map.addSource('vehicles', {
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
                        };

            map.on('load', window.initMapLayers);
            map.on('style.load', function() {
                window.previousMapStyleUrl = null;
                window.initMapLayers();
                if (window.updatePrivacyZones) window.updatePrivacyZones();
                if (window.updatePujEmptyState) window.updatePujEmptyState();
                // ETA badges + the Nearest-PUJ indicator depend on marker DOM,
                // so re-evaluate them after every style (re)load.
                if (window.ETA) window.ETA.refresh();
            });

            // ═══════════════ MAP CLICK → SET LOCATION ═══════════════
            map.on('click', function(e) {
                if (!selectionMode) return;

                const lng = e.lngLat.lng;
                const lat = e.lngLat.lat;

                if (selectionMode === 'pickup') {
                    userLat = lat;
                    userLng = lng;


                    if (window.ETA) window.ETA._receivePosition(lat, lng, 20);

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

                // Guard against a stale/failed calculation
                document.getElementById('distance').value = '';
                document.getElementById('price-regular').value = '';
                document.getElementById('price-discount').value = '';

                try {
                    if (!navigator.onLine) {
                        if (window.showMapAlert) window.showMapAlert('offline');
                        return;
                    }

                    const res = await fetch(url);
                    if (!res.ok) throw new Error('Routing HTTP ' + res.status);
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

                        // Routing succeeded - drop any stale routing warning
                        if (window.clearMapAlert) window.clearMapAlert('routing-service');
                    } else if (data.code && data.code !== 'Ok') {
                        // OSRM returned NoRoute / InvalidInput / etc.
                        console.warn('[ETA] Routing failed:', data.code);
                        if (window.showMapAlert) window.showMapAlert('routing-service');
                    }
                } catch (e) {
                    // E3 (UCN_SC_E004) - Routing API timeout or failure
                    console.error('Route error:', e);
                    if (window.showMapAlert) window.showMapAlert('routing-service');
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
                    // E2 (UCN_SC_E004) - location/place not found
                    if (window.showMapAlert) window.showMapAlert('location-not-found');
                    setTimeout(function() {
                        if (window.clearMapAlert) window.clearMapAlert('location-not-found');
                    }, 6000);
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

            // The fare-calculator inputs only exist for guests/commuters. Never let a
            // missing element throw here — that would abort the whole module (Echo
            // subscriptions, GPS broadcasting, live vehicle markers) for other roles.
            const pickupInput = document.getElementById('pickup');
            const destinationInput = document.getElementById('destination');

            pickupInput?.addEventListener('input', function() {
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

            destinationInput?.addEventListener('input', function() {
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

            // ═══════════════ LIVE VEHICLE MARKERS ═══════════════
            // One renderer for BOTH sources (Echo event + REST poll) so a vehicle can
            // never end up with two markers, and so a dropped websocket event heals
            // itself on the next poll.
            function createVehicleElement() {
                const el = document.createElement('div');
                el.className = 'custom-vehicle-marker bus-pulse';
                el.innerHTML = '<i class="fa-solid fa-bus"></i>';
                return el;
            }

            function renderLiveVehicles(vehicles, opts) {
                // `partial: true` = a single-vehicle update (websocket push).
                // Anything else = authoritative snapshot (REST poll).
                const partial = !!(opts && opts.partial);
                if (!partial && !Array.isArray(vehicles)) return;
                if (partial && !Array.isArray(vehicles)) return;

                const cache = window.liveVehicleCache = window.liveVehicleCache || {};

                // Normalise one payload entry; null when it is unusable.
                const normalize = function(v) {
                    const lat = parseFloat(v.lat ?? v.latitude);
                    const lng = parseFloat(v.lng ?? v.longitude);
                    if (v.id === undefined || v.id === null) return null;
                    if (!isFinite(lat) || !isFinite(lng)) return null;
                    if (lat === 0 && lng === 0) return null; // null island = bad GPS fix
                    const out = { id: v.id, lat: lat, lng: lng };
                    // Only overwrite fields the source actually provided, so a
                    // websocket push never downgrades plate/route to a placeholder.
                    if (v.plate_number) out.plate_number = v.plate_number;
                    if (v.route_name || v.route) out.route = v.route_name || v.route;
                    if (v.privacy_radius) out.privacy_radius = v.privacy_radius;
                    if (v.last_update) out.last_update = v.last_update;
                    return out;
                };

                if (partial) {
                    // Merge: keep the cached plate/route, refresh position + freshness.
                    vehicles.forEach(v => {
                        const n = normalize(v);
                        if (!n) return;
                        cache[n.id] = Object.assign({}, cache[n.id] || {}, n);
                    });
                } else {
                    // Authoritative: vehicles absent from the snapshot have stopped
                    // reporting, so they leave the cache (and therefore the map).
                    const seen = {};
                    vehicles.forEach(v => {
                        const n = normalize(v);
                        if (!n) return;
                        seen[n.id] = Object.assign({}, cache[n.id] || {}, n);
                    });
                    Object.keys(cache).forEach(id => {
                        if (!(id in seen)) delete cache[id];
                    });
                    Object.assign(cache, seen);
                }

                // Fill in display defaults AFTER merging
                const live = {};
                Object.keys(cache).forEach(id => {
                    const c = cache[id];
                    live[id] = Object.assign({}, c, {
                        plate_number: c.plate_number || ('Vehicle ' + id),
                        route: c.route || 'Live',
                        privacy_radius: c.privacy_radius || window.PRIVACY_RADIUS
                    });
                });

                Object.keys(window.echoMarkers || {}).forEach(id => {
                    if (!(id in live)) {
                        window.echoMarkers[id].remove();
                        delete window.echoMarkers[id];
                        if (window.echoPopups && window.echoPopups[id]) delete window.echoPopups[id];
                    }
                });

                Object.keys(live).forEach(id => {
                    const v = live[id];

                    if (window.echoMarkers[id]) {
                        // Smooth move instead of teleporting
                        const m = window.echoMarkers[id];
                        m.setLngLat([v.lng, v.lat]);
                        if (window.echoPopups[id]) {
                            window.echoPopups[id].data = v;
                            window.echoPopups[id].popup.setLngLat([v.lng, v.lat]);
                        }
                    } else {
                        const el = createVehicleElement();
                        const popup = new maplibregl.Popup({
                                className: 'puj-popup',
                                offset: 18,
                                closeButton: false,
                                focusAfterOpen: false,
                                maxWidth: '260px'
                            })
                            .setHTML(window.createPrivacyPopup(v));

                        window.echoPopups[id] = {
                            popup: popup,
                            data: v
                        };
                        window.echoMarkers[id] = new maplibregl.Marker({
                                element: el,
                                anchor: 'center'
                            })
                            .setLngLat([v.lng, v.lat])
                            .setPopup(popup)
                            .addTo(map);
                    }

                    window.driverPrivacyZones[id] = {
                        lat: v.lat,
                        lng: v.lng,
                        radius: v.privacy_radius
                    };
                });

                // Zones for vehicles that vanished
                Object.keys(window.driverPrivacyZones).forEach(id => {
                    if (!(id in live)) delete window.driverPrivacyZones[id];
                });

                window.updatePrivacyZones();
                if (window.ETA) window.ETA.refresh();
            }
            window.renderLiveVehicles = renderLiveVehicles;

            // ═══════════════ REAL-TIME VEHICLE UPDATES (Echo) ═══════════════
            // Drivers are broadcasters only — they never render the fleet on their map.
            if (window.Echo && userRole !== 'driver') {
                window.Echo.channel('vehicle-locations')
                    .listen('.vehicle-location-updated', (e) => {
                        if (window.userRole === 'driver') return;
                        if (!e.lat || !e.lng) return;
                        if (window.noteLiveSignal) window.noteLiveSignal();
                        if (window.markPujSourceLoaded) window.markPujSourceLoaded('live');

                        // Partial (single-vehicle) update - merges into the cache so
                        // the real plate/route from the last poll are preserved.
                        renderLiveVehicles([{
                            id: e.vehicleId,
                            lat: e.lat,
                            lng: e.lng,
                            privacy_radius: e.privacy_radius || window.PRIVACY_RADIUS,
                            last_update: e.timestamp || new Date().toISOString()
                        }], { partial: true });
                    });
            }
            // ═══════════════ DEV MARKERS REAL-TIME SYNC ═══════════════
            if (window.Echo) {
                window.Echo.channel('dev-markers')
                .listen('.marker-updated', function() {
                    loadDummyMarkers();
                    // Refresh ETA after markers re-render (small delay for DOM)
                    setTimeout(function() {
                        if (window.ETA) window.ETA.refresh();
                    }, 300);
                });
            }

            // ── Self-healing poll ──
            // Echo delivers instantly, but a websocket hiccup (or a dropped publish)
            // must never freeze the bus on the map, so we also poll the active
            // vehicles endpoint. renderLiveVehicles() is shared, so this can't
            // create duplicate markers. Fast (3s) while buses are on the air,
            // slow (10s) when the map is idle.
            let vehiclePollBusy = false;
            let lastLiveSignalAt = 0;

            window.noteLiveSignal = function() {
                lastLiveSignalAt = Date.now();
            };

            async function fetchVehicles() {
                if (vehiclePollBusy) return;
                vehiclePollBusy = true;

                try {
                    const res = await fetch('/track/vehicles/active', {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    if (res.ok) {
                        const data = await res.json();
                        if (data && data.vehicles) {
                            renderLiveVehicles(data.vehicles);
                            if (data.vehicles.length) window.noteLiveSignal();
                        }
                        if (window.markPujSourceLoaded) window.markPujSourceLoaded('live');
                    }
                } catch (e) {
                    console.log('Vehicle fetch skipped:', e.message);
                    // E4 - device offline: PUJ positions cannot be refreshed
                    if (!navigator.onLine && window.showMapAlert) {
                        window.showMapAlert('offline');
                    }
                } finally {
                    vehiclePollBusy = false;
                }
            }

            function scheduleVehiclePoll() {
                const active = Object.keys(window.echoMarkers || {}).length > 0;
                const fresh = (Date.now() - lastLiveSignalAt) < 45000;
                const delay = (active || fresh) ? 3000 : 10000;
                setTimeout(async function() {
                    await fetchVehicles();
                    scheduleVehiclePoll();
                }, delay);
            }

            // Drivers are broadcasters only, so they never poll the fleet.
            if (userRole !== 'driver') {
                fetchVehicles().then(scheduleVehiclePoll);

                // Re-sync as soon as the tab becomes visible again
                document.addEventListener('visibilitychange', function() {
                    if (!document.hidden) fetchVehicles();
                });
            }

            // ═══════════════ DRIVER GPS TRACKING ═══════════════
            let gpsWatchId = null;
            let hasLiveFix = false;
            let gpsWatchToken = 0;
            const gpsIndicator = document.getElementById('gps-indicator');
            const gpsStatusText = document.getElementById('gps-status-text');
            const currentCoords = document.getElementById('current-coords');
            const currentAccuracy = document.getElementById('current-accuracy');
            const updateTime = document.getElementById('update-time');

            // Only the assigned driver ever broadcasts. Guests, commuters and
            // managers may share their own position for the ETA feature, but that
            // is never published to the vehicle-locations channel.
            const isBroadcastingDriver = (userRole === 'driver' && !!userId && !!driverVehicleId);

            // ── Broadcast driver GPS updates → LocationUpdated event ──
            let lastBroadcastAt = 0;

            function broadcastDriverLocation(coords) {
                if (!isBroadcastingDriver) return;

                var nowTs = Date.now();

                // Throttle: one broadcast every 2 seconds
                if (nowTs - lastBroadcastAt < 2000) return;
                lastBroadcastAt = nowTs;

                fetch('/track/vehicle/broadcast', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') || {}).content || ''
                        },
                        credentials: 'same-origin',
                        keepalive: true,
                        body: JSON.stringify({
                            vehicle_id: String(driverVehicleId),
                            user_id: userId,
                            latitude: coords.latitude,
                            longitude: coords.longitude,
                            speed: coords.speed ?? null,
                            accuracy: coords.accuracy ?? null,
                            timestamp: nowTs
                        })
                    })
                    .catch(function(err) {
                        console.warn('Location broadcast failed:', err.message);
                    });
            }

            function stopGPSTracking(silent) {
                if (gpsWatchId === null) return;
                gpsWatchToken++;
                navigator.geolocation.clearWatch(gpsWatchId);
                gpsWatchId = null;
                lastBroadcastAt = 0;
                hasLiveFix = false;
                if (!silent) {
                    if (gpsIndicator) gpsIndicator.className = 'w-2 h-2 bg-[#555] rounded-full dot-pulse';
                    if (gpsStatusText) {
                        gpsStatusText.textContent = 'GPS: Off';
                        gpsStatusText.className = 'text-[10px] text-[#555]';
                    }
                    if (currentCoords) currentCoords.textContent = '--, --';
                    if (currentAccuracy) currentAccuracy.textContent = '-- m';
                    if (updateTime) updateTime.textContent = '--:--:--';
                }
            }

            // No button: the driver's own watch starts by itself as soon as the
            // page is open (and is retried if the browser needs permission).
            function startGPSTracking() {
                if (!isBroadcastingDriver || gpsWatchId !== null) return;
                if (!navigator.geolocation) return;

                // Guards against late callbacks from a watch we already replaced.
                const watchToken = ++gpsWatchToken;
                hasLiveFix = false;

                if (gpsIndicator) gpsIndicator.className = 'w-2 h-2 bg-green-500 rounded-full dot-pulse';
                if (gpsStatusText) {
                    gpsStatusText.textContent = 'GPS: Live';
                    gpsStatusText.className = 'text-[10px] text-green-400 font-semibold';
                }

                gpsWatchId = navigator.geolocation.watchPosition(
                    function(pos) {
                        if (watchToken !== gpsWatchToken) return;

                        if (!hasLiveFix) {
                            hasLiveFix = true;
                            if (gpsIndicator) gpsIndicator.className = 'w-2 h-2 bg-green-500 rounded-full dot-pulse';
                            if (gpsStatusText) {
                                gpsStatusText.textContent = 'GPS: Live';
                                gpsStatusText.className = 'text-[10px] text-green-400 font-semibold';
                            }
                        }

                        const lat = pos.coords.latitude.toFixed(6);
                        const lon = pos.coords.longitude.toFixed(6);
                        const acc = pos.coords.accuracy.toFixed(0);
                        const timeStr = new Date().toLocaleTimeString('en-US', {
                            hour12: false
                        });

                        if (currentCoords) currentCoords.textContent = lat + ', ' + lon;
                        if (currentAccuracy) currentAccuracy.textContent = acc + ' m';
                        if (updateTime) updateTime.textContent = timeStr;

                        broadcastDriverLocation(pos.coords);
                    },
                    function(err) {
                        if (watchToken !== gpsWatchToken) return;
                        console.warn('GPS unavailable:', err.message);
                        if (gpsWatchId !== null) navigator.geolocation.clearWatch(gpsWatchId);
                        gpsWatchId = null;
                        hasLiveFix = false;
                        if (gpsIndicator) gpsIndicator.className = 'w-2 h-2 bg-amber-500 rounded-full dot-pulse';
                        if (gpsStatusText) {
                            gpsStatusText.textContent = 'GPS: Enable location';
                            gpsStatusText.className = 'text-[10px] text-amber-400';
                        }
                    }, {
                        enableHighAccuracy: true,
                        maximumAge: 5000,
                        // Long timeout: a backgrounded tab can be throttled and stop
                        // receiving fixes. Killing the watch on a short timeout would
                        // silently stop the broadcast.
                        timeout: 60000
                    }
                );
            }

            // Kept for the map's own locate button / manual controls
            window.toggleGPSTracking = function() {
                if (gpsWatchId !== null) {
                    stopGPSTracking(false);
                } else {
                    startGPSTracking();
                }
            };
            window.startGPSTracking = startGPSTracking;

            if (isBroadcastingDriver) {
                // Start immediately, then keep nudging while the browser is still
                // waiting for (or has yet to grant) location permission.
                startGPSTracking();
                [1500, 5000, 15000, 45000].forEach(function(ms) {
                    setTimeout(function() {
                        if (gpsWatchId === null) startGPSTracking();
                    }, ms);
                });

                // Safety net: if the watch ever dies (permission change, browser
                // throttling the tab, device sleep) bring it straight back.
                setInterval(function() {
                    if (gpsWatchId === null) startGPSTracking();
                }, 30000);

                document.addEventListener('visibilitychange', function() {
                    if (!document.hidden) startGPSTracking();
                });
            }

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
                        // ── Driver: detailed popup ──
                        popup = new maplibregl.Popup({
                            className: 'puj-popup',
                            offset: 18,
                            closeButton: false,
                            focusAfterOpen: false,
                            maxWidth: '260px'
                        }).setHTML(window.createDriverPopup(d));
                    } else {
                        // ── Commuter/Guest: privacy popup ──
                        popup = new maplibregl.Popup({
                                className: 'puj-popup',
                                offset: 18,
                                closeButton: false,
                                focusAfterOpen: false,
                                maxWidth: '260px'
                            })
                            .setHTML(window.createPrivacyPopup(d));
window.dummyMapPopups[d.id] = { popup: popup, data: d };
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
                if (window.updatePujEmptyState) window.updatePujEmptyState();
            }

            function loadDummyMarkers() {
                var m = window.map;
                if (!m || typeof m.on !== 'function') {
                    setTimeout(loadDummyMarkers, 200);
                    return;
                }
                console.log('[DEV] Map ready, fetching markers...');
                if (!navigator.onLine) {
                    if (window.showMapAlert) window.showMapAlert('offline');
                    return;
                }
                fetch('/api/markers?t=' + Date.now())
                    .then(function(r) {
                        console.log('[DEV] Fetch response status:', r.status);
                        return r.json();
                    })
                    .then(function(markers) {
                        console.log('[DEV] Parsed markers:', JSON.stringify(markers, null, 2));
                        if (window.clearMapAlert) window.clearMapAlert('offline');
                        renderDummyMarkers(markers);
                        if (window.markPujSourceLoaded) window.markPujSourceLoaded('dummy');
                    })
                    .catch(function(err) {
                        console.log('[DEV] Fetch error:', err);
                        if (!navigator.onLine) {
                            if (window.showMapAlert) window.showMapAlert('offline');
                        } else if (window.showMapAlert) {
                            window.showMapAlert('map-service');
                        }
                    });
            }
            window.loadDummyMarkers = loadDummyMarkers;

            loadDummyMarkers();
        </script>

        <!-- Map Status Banner: E1 no PUJ / E2 location denied / E3 map service down / E4 offline -->
        <div id="map-alert" class="hidden" data-type="info" role="status" aria-live="polite">
            <i class="fa-solid fa-circle-info ma-icon"></i>
            <div>
                <p class="ma-title" id="map-alert-title">Notice</p>
                <p class="ma-msg" id="map-alert-msg"></p>
            </div>
        </div>

        <!-- Nearest Vehicle ETA Indicator -->
        <div id="nearest-vehicle-indicator" class="hidden">
            <div class="nv-dot" style="background:#34d399;"></div>
            <div class="nv-info">
                <span class="nv-label">Nearest PUJ</span>
                <span class="nv-time">--</span>
            </div>
            <span class="nv-distance">--</span>
            <i class="fa-solid fa-chevron-right nv-arrow"></i>
        </div>
    </div>

    <script>
        /* ══════════════════════════════════════════════════════════
         * THEME
         * - `dark` class on <html> drives every CSS token (including the
         *   marker popup), so the class flip alone re-themes the page.
         * - The basemap style is swapped to match (see updateMapStyle).
         * - Preference is persisted in localStorage for everyone, and in the
         *   database for signed-in users. Guests keep their choice on reload.
         * ══════════════════════════════════════════════════════════ */
        const MAP_STYLES = {
            light: 'https://tiles.openfreemap.org/styles/bright',
            dark: 'https://tiles.openfreemap.org/styles/liberty'
        };
        const THEME_URL = '{{ route('settings.update.theme') }}';
        const IS_AUTHENTICATED = {{ Auth::check() ? 'true' : 'false' }};

        window.currentMapTheme = document.documentElement.classList.contains('dark') ? 'dark' : 'light';

        window.updateMapStyle = function(isDark) {
            if (!window.map) return;
            const next = isDark ? MAP_STYLES.dark : MAP_STYLES.light;
            if (!next || window.currentMapStyleUrl === next) return;

            window.previousMapStyleUrl = window.currentMapStyleUrl;
            window.currentMapStyleUrl = next;
            try {
                // Sources/layers are restored by the map's style.load handler.
                window.map.setStyle(next);
            } catch (e) {
                console.warn('[Theme] Could not switch basemap style:', e);
                window.map.setStyle(window.previousMapStyleUrl);
                window.previousMapStyleUrl = null;
                window.currentMapStyleUrl = window.previousMapStyleUrl;
            }
        };

        function applyTheme(theme) {
            const isDark = theme === 'dark';
            document.documentElement.classList.toggle('dark', isDark);
            localStorage.setItem('color-theme', theme);
            window.currentMapTheme = theme;
            window.updateMapStyle(isDark);
            // The popup / banner / nearest-indicator colours come from CSS
            // variables (so the class flip alone re-themes them), but the
            // popup markup is cached, so re-render what is on screen.
            if (window.ETA) window.ETA.refresh();
            if (window.renderMapAlert) window.renderMapAlert();
        }

        function toggleMapTheme() {
            const next = document.documentElement.classList.contains('dark') ? 'light' : 'dark';
            applyTheme(next);

            // Signed-in users persist to their profile; guests keep it locally.
            if (!IS_AUTHENTICATED) return;

            fetch(THEME_URL, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                },
                body: JSON.stringify({
                    theme: next
                })
            }).catch(() => {
                // Keep the local preference rather than yanking the UI back.
                console.warn('[Theme] Could not save preference to the profile.');
            });
        }

        window.addEventListener('load', function() {
            // Keep the basemap in step if the theme changed before load finished.
            const wanted = MAP_STYLES[window.currentMapTheme];
            if (wanted && window.currentMapStyleUrl !== wanted) window.updateMapStyle(
                window.currentMapTheme === 'dark'
            );
        });
    </script>

</body>

</html>
