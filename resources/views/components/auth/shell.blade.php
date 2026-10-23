@props([
    'title' => 'SmartCommute',
    'wide' => false,
    'heading' => 'Your commute, made smarter.',
    'blurb' => 'Live PUJ tracking, transparent fares and cash-free payments — all in one place.',
])
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>

    {{-- Theme before first paint: guests follow their last choice on this device, then the OS. --}}
    <script>
        (function() {
            var stored = null;
            try { stored = localStorage.getItem('color-theme'); } catch (e) {}
            var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.classList.toggle('dark', (stored || (prefersDark ? 'dark' : 'light')) === 'dark');
        })();
    </script>

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    @include('partials.theme')
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: window.SC_FONT
                    },
                    colors: window.SC_COLORS
                }
            }
        }
    </script>

    <style>
        body {
            min-height: 100svh;
            overflow-x: hidden;
        }

        .au-grid {
            display: grid;
            min-height: 100svh;
            grid-template-columns: minmax(0, 1fr);
        }

        @media (min-width: 1024px) {
            .au-grid {
                grid-template-columns: minmax(0, 5fr) minmax(0, 6fr);
            }
        }

        /* ── Brand panel ── */
        .au-aside {
            position: relative;
            display: none;
            flex-direction: column;
            justify-content: space-between;
            margin: 14px 0 14px 14px;
            padding: 44px 48px;
            border-radius: 40px;
            overflow: hidden;
            color: #fff;
            background:
                radial-gradient(80% 60% at 100% 0%, rgba(236, 72, 153, .55), transparent 60%),
                radial-gradient(70% 60% at 0% 100%, rgba(34, 211, 238, .30), transparent 60%),
                linear-gradient(150deg, #3730a3 0%, #5b21b6 100%);
            box-shadow: 0 40px 90px -40px rgba(67, 56, 202, .8);
        }

        @media (min-width: 1024px) {
            .au-aside {
                display: flex;
            }
        }

        .au-aside::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: radial-gradient(rgba(255, 255, 255, .16) 1px, transparent 1.2px);
            background-size: 24px 24px;
            -webkit-mask-image: linear-gradient(160deg, transparent 15%, #000);
            mask-image: linear-gradient(160deg, transparent 15%, #000);
        }

        .au-aside>* {
            position: relative;
        }

        .au-logo {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            font-size: 21px;
            font-weight: 800;
            letter-spacing: -.02em;
        }

        .au-logo-mark {
            display: grid;
            place-items: center;
            width: 44px;
            height: 44px;
            border-radius: 15px;
            font-size: 17px;
            color: #fff;
            background: rgba(255, 255, 255, .18);
            border: 1px solid rgba(255, 255, 255, .28);
            -webkit-backdrop-filter: blur(8px);
            backdrop-filter: blur(8px);
        }

        .au-h {
            font-size: clamp(2.2rem, 3.4vw, 3.3rem);
            font-weight: 800;
            line-height: 1.06;
            letter-spacing: -.04em;
        }

        .au-benefit {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 18px 14px 14px;
            border-radius: 20px;
            background: rgba(255, 255, 255, .1);
            border: 1px solid rgba(255, 255, 255, .16);
            -webkit-backdrop-filter: blur(10px);
            backdrop-filter: blur(10px);
            font-size: 15.5px;
            font-weight: 600;
        }

        .au-benefit i {
            display: grid;
            place-items: center;
            flex: none;
            width: 40px;
            height: 40px;
            border-radius: 13px;
            font-size: 16px;
            background: rgba(255, 255, 255, .18);
        }

        .au-route {
            position: absolute;
            right: -40px;
            bottom: 90px;
            width: 360px;
            opacity: .22;
            pointer-events: none;
        }

        /* ── Form side ── */
        .au-main {
            display: flex;
            flex-direction: column;
            padding: 22px 20px 40px;
            min-width: 0;
        }

        .au-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .au-back {
            display: grid;
            place-items: center;
            flex: none;
            width: 42px;
            height: 42px;
            border-radius: 50%;
            font-size: 14px;
            color: var(--sc-ink-2);
            background: var(--sc-surface);
            border: 1px solid var(--sc-line);
            box-shadow: var(--sc-shadow-1);
            transition: transform .2s, border-color .2s;
        }

        .au-back:hover {
            transform: translateX(-2px);
            border-color: var(--sc-line-strong);
        }

        .au-theme {
            flex: none;
            display: grid;
            place-items: center;
            width: 42px;
            height: 42px;
            border-radius: 50%;
            color: var(--sc-ink-2);
            background: var(--sc-surface);
            border: 1px solid var(--sc-line);
            cursor: pointer;
        }

        .au-card-wrap {
            flex: 1;
            display: grid;
            place-items: center;
            padding: 28px 0 8px;
        }

        .au-card {
            width: 100%;
            max-width: 480px;
            padding: clamp(24px, 4vw, 40px);
            border-radius: 32px;
            background: var(--sc-surface);
            border: 1px solid var(--sc-line);
            box-shadow: var(--sc-shadow-3);
            animation: sc-rise .7s cubic-bezier(.16, 1, .3, 1) both;
        }

        .au-card.wide {
            max-width: 720px;
        }

        .au-mobile-logo {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 19px;
            font-weight: 800;
            letter-spacing: -.02em;
            color: var(--sc-ink);
        }

        @media (min-width: 1024px) {
            .au-mobile-logo {
                display: none;
            }
        }

        .au-mobile-logo .mk {
            display: grid;
            place-items: center;
            width: 38px;
            height: 38px;
            border-radius: 13px;
            font-size: 15px;
            color: #fff;
            background: var(--sc-grad-warm);
        }

        .au-mobile-logo b {
            color: var(--sc-brand-text);
        }

        /* Form building blocks used by every auth page */
        .au-title {
            font-size: 1.85rem;
            font-weight: 800;
            letter-spacing: -.035em;
            line-height: 1.15;
            color: var(--sc-ink);
        }

        .au-sub {
            margin-top: 8px;
            font-size: 15px;
            line-height: 1.6;
            color: var(--sc-mute);
        }

        .au-icon-field {
            position: relative;
        }

        .au-icon-field>i.lead {
            position: absolute;
            top: 50%;
            left: 16px;
            transform: translateY(-50%);
            font-size: 15px;
            color: var(--sc-mute);
            pointer-events: none;
        }

        .au-icon-field .sc-field {
            padding-left: 44px;
        }

        .au-icon-field .au-eye {
            position: absolute;
            top: 50%;
            right: 8px;
            transform: translateY(-50%);
            display: grid;
            place-items: center;
            width: 38px;
            height: 38px;
            border-radius: 12px;
            color: var(--sc-mute);
            cursor: pointer;
        }

        .au-icon-field .au-eye:hover {
            color: var(--sc-ink);
            background: var(--sc-surface-3);
        }

        .au-err {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            margin-top: 8px;
            font-size: 13.5px;
            font-weight: 500;
            color: #dc2626;
        }

        .dark .au-err {
            color: #fca5a5;
        }

        .au-err i {
            margin-top: 3px;
            font-size: 12px;
        }

        .au-alert {
            display: flex;
            gap: 12px;
            align-items: flex-start;
            padding: 14px 16px;
            border-radius: 18px;
            font-size: 14.5px;
            font-weight: 500;
            line-height: 1.5;
        }

        .au-alert.ok {
            color: #047857;
            background: rgba(16, 185, 129, .12);
            border: 1px solid rgba(16, 185, 129, .3);
        }

        .au-alert.bad {
            color: #b91c1c;
            background: rgba(239, 68, 68, .1);
            border: 1px solid rgba(239, 68, 68, .28);
        }

        .dark .au-alert.ok {
            color: #6ee7b7;
        }

        .dark .au-alert.bad {
            color: #fca5a5;
        }

        .au-link {
            font-weight: 700;
            color: var(--sc-brand-text);
        }

        .au-link:hover {
            text-decoration: underline;
            text-underline-offset: 3px;
        }

        .au-divider {
            margin-top: 26px;
            padding-top: 22px;
            border-top: 1px solid var(--sc-line);
            text-align: center;
            font-size: 14.5px;
            color: var(--sc-mute);
        }

        .au-modal {
            position: fixed;
            inset: 0;
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
            background: rgba(8, 10, 30, .55);
            -webkit-backdrop-filter: blur(6px);
            backdrop-filter: blur(6px);
        }

        .au-modal-card {
            width: 100%;
            max-width: 400px;
            padding: 32px;
            text-align: center;
            border-radius: 30px;
            background: var(--sc-surface);
            border: 1px solid var(--sc-line);
            box-shadow: var(--sc-shadow-3);
            animation: sc-rise .45s cubic-bezier(.16, 1, .3, 1) both;
        }

        .au-modal-card h3 {
            font-size: 1.25rem;
            font-weight: 800;
            letter-spacing: -.02em;
            color: var(--sc-ink);
        }

        .au-modal-card p {
            margin-top: 8px;
            font-size: 15px;
            line-height: 1.6;
            color: var(--sc-mute);
        }

        .au-modal-ico {
            display: grid;
            place-items: center;
            width: 64px;
            height: 64px;
            margin: 0 auto 18px;
            border-radius: 22px;
            font-size: 26px;
        }
    </style>
    @include('partials.global-loading')
    {{ $head ?? '' }}
</head>

<body class="font-sans antialiased">
    {{ $modals ?? '' }}

    <div class="au-grid">

        <!-- Brand panel -->
        <aside class="au-aside" aria-hidden="true">
            <a href="{{ url('/') }}" class="au-logo" tabindex="-1">
                <span class="au-logo-mark"><i class="fa-solid fa-bus"></i></span>
                <span>SmartCommute</span>
            </a>

            <svg class="au-route" viewBox="0 0 360 320" fill="none">
                <path d="M20 280 C 90 200, 130 250, 190 160 S 290 90, 340 30" stroke="#fff" stroke-width="6"
                    stroke-linecap="round" stroke-dasharray="2 14" />
                <circle cx="20" cy="280" r="12" fill="#fff" />
                <circle cx="340" cy="30" r="12" fill="#fff" />
            </svg>

            <div>
                <h2 class="au-h">{{ $heading }}</h2>
                <p class="mt-5 text-lg leading-relaxed" style="color: rgba(255,255,255,.82); max-width: 460px">
                    {{ $blurb }}</p>

                <div class="mt-9 space-y-3" style="max-width: 440px">
                    <div class="au-benefit"><i class="fa-solid fa-location-dot"></i> Live PUJ positions and arrival
                        estimates</div>
                    <div class="au-benefit"><i class="fa-solid fa-receipt"></i> Official fares, calculated for you
                    </div>
                    <div class="au-benefit"><i class="fa-solid fa-wallet"></i> Pay cash-free with wallet, GCash or
                        Maya</div>
                </div>
            </div>

            <p class="text-sm" style="color: rgba(255,255,255,.6)">&copy; {{ date('Y') }} SmartCommute</p>
        </aside>

        <!-- Form side -->
        <div class="au-main">
            <div class="au-top">
                <a href="{{ url('/') }}" class="au-back" aria-label="Back to home" title="Back to home"><i
                        class="fa-solid fa-arrow-left"></i></a>
                <a href="{{ url('/') }}" class="au-mobile-logo"><span class="mk"><i
                            class="fa-solid fa-bus"></i></span><span>Smart<b>Commute</b></span></a>
                <button type="button" class="au-theme" aria-label="Toggle dark mode" onclick="toggleAuthTheme()">
                    <i class="fa-solid fa-moon dark:hidden"></i>
                    <i class="fa-solid fa-sun hidden dark:inline"></i>
                </button>
            </div>

            <div class="au-card-wrap">
                <div class="au-card {{ $wide ? 'wide' : '' }}">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleAuthTheme() {
            var dark = !document.documentElement.classList.contains('dark');
            document.documentElement.classList.toggle('dark', dark);
            try { localStorage.setItem('color-theme', dark ? 'dark' : 'light'); } catch (e) {}
        }
    </script>
</body>

</html>
