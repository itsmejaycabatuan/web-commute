<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description"
        content="Live PUJ tracking, fare planning and cash-free payments for commuters, with timekeeping and maintenance tools for the people who run the fleet.">
    <title>Smart Commute System</title>

    {{-- Pick the theme before first paint: signed-in users follow their profile, guests follow
         their last choice on this device, then the OS preference. --}}
    <script>
        (function() {
            var isGuest = {{ Auth::guest() ? 'true' : 'false' }};
            var stored = null;
            try { stored = localStorage.getItem('color-theme'); } catch (e) {}
            var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            var theme = isGuest ? (stored || (prefersDark ? 'dark' : 'light')) : '{{ $userTheme ?? 'light' }}';
            document.documentElement.classList.toggle('dark', theme === 'dark');
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
        html {
            scroll-behavior: smooth;
            scroll-padding-top: 88px;
        }

        body {
            overflow-x: hidden;
        }

        /* ── Navbar: floating glass pill ── */
        .lp-nav {
            position: fixed;
            z-index: 80;
            top: 14px;
            left: 50%;
            transform: translateX(-50%);
            width: min(1180px, calc(100% - 24px));
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 10px 12px 10px 18px;
            border-radius: 999px;
            background: color-mix(in srgb, var(--sc-surface) 78%, transparent);
            -webkit-backdrop-filter: blur(18px) saturate(1.5);
            backdrop-filter: blur(18px) saturate(1.5);
            border: 1px solid var(--sc-line);
            box-shadow: 0 14px 40px -20px rgba(30, 34, 110, .35);
        }

        .lp-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 19px;
            font-weight: 800;
            letter-spacing: -.02em;
            color: var(--sc-ink);
        }

        .lp-logo-mark {
            display: grid;
            place-items: center;
            width: 38px;
            height: 38px;
            border-radius: 13px;
            color: #fff;
            font-size: 15px;
            background: var(--sc-grad-warm);
            box-shadow: 0 10px 22px -8px rgba(124, 58, 237, .75), inset 0 1px 0 rgba(255, 255, 255, .3);
        }

        .lp-logo b {
            color: var(--sc-brand-text);
            font-weight: 800;
        }

        .lp-links {
            display: none;
            align-items: center;
            gap: 4px;
        }

        @media (min-width: 900px) {
            .lp-links {
                display: flex;
            }
        }

        .lp-link {
            padding: 9px 16px;
            border-radius: 999px;
            font-size: 14.5px;
            font-weight: 600;
            color: var(--sc-ink-2);
            transition: background-color .2s, color .2s;
        }

        .lp-link:hover {
            background: var(--sc-surface-3);
            color: var(--sc-ink);
        }

        .lp-icon-btn {
            display: grid;
            place-items: center;
            width: 42px;
            height: 42px;
            border-radius: 50%;
            color: var(--sc-ink-2);
            background: var(--sc-surface-2);
            border: 1px solid var(--sc-line);
            cursor: pointer;
        }

        .lp-icon-btn:hover {
            color: var(--sc-ink);
            border-color: var(--sc-line-strong);
        }

        .lp-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            height: 44px;
            padding: 0 22px;
            border-radius: 999px;
            font-size: 14.5px;
            font-weight: 700;
            color: #fff;
            background: var(--sc-grad);
            box-shadow: 0 14px 28px -12px rgba(79, 70, 229, .8), inset 0 1px 0 rgba(255, 255, 255, .25);
            transition: transform .2s, filter .2s;
            white-space: nowrap;
        }

        .lp-btn:hover {
            transform: translateY(-2px);
            filter: brightness(1.07);
        }

        .lp-btn.lg {
            height: 56px;
            padding: 0 30px;
            font-size: 16px;
        }

        .lp-btn.ghost {
            color: var(--sc-ink);
            background: var(--sc-surface);
            border: 1px solid var(--sc-line-strong);
            box-shadow: var(--sc-shadow-1);
        }

        .lp-btn.light {
            color: #3730a3;
            background: #fff;
            box-shadow: 0 16px 34px -14px rgba(0, 0, 0, .5);
        }

        .lp-btn.glass {
            color: #fff;
            background: rgba(255, 255, 255, .14);
            border: 1px solid rgba(255, 255, 255, .28);
            box-shadow: none;
        }

        /* ── Hero ── */
        .lp-hero {
            position: relative;
            padding: 160px 20px 70px;
            overflow: hidden;
        }

        .lp-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: radial-gradient(var(--sc-line-strong) 1px, transparent 1.2px);
            background-size: 26px 26px;
            -webkit-mask-image: radial-gradient(70% 60% at 50% 30%, #000 20%, transparent 78%);
            mask-image: radial-gradient(70% 60% at 50% 30%, #000 20%, transparent 78%);
            opacity: .8;
            pointer-events: none;
        }

        .lp-wrap {
            position: relative;
            max-width: 1180px;
            margin: 0 auto;
        }

        .lp-hero-grid {
            display: grid;
            gap: 56px;
            align-items: center;
        }

        @media (min-width: 1020px) {
            .lp-hero-grid {
                grid-template-columns: 1.05fr .95fr;
            }
        }

        .lp-pill {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 6px 16px 6px 6px;
            border-radius: 999px;
            font-size: 13.5px;
            font-weight: 600;
            color: var(--sc-ink-2);
            background: var(--sc-surface);
            border: 1px solid var(--sc-line);
            box-shadow: var(--sc-shadow-1);
        }

        .lp-pill i {
            display: grid;
            place-items: center;
            width: 26px;
            height: 26px;
            border-radius: 50%;
            font-size: 11px;
            color: #fff;
            background: var(--sc-grad);
        }

        .lp-h1 {
            margin: 24px 0 22px;
            font-size: clamp(2.5rem, 5.1vw, 4.4rem);
            font-weight: 800;
            line-height: 1.1;
            letter-spacing: -.045em;
            color: var(--sc-ink);
        }

        .lp-lead {
            max-width: 560px;
            font-size: clamp(1.05rem, 1.6vw, 1.25rem);
            line-height: 1.65;
            color: var(--sc-mute);
        }

        .lp-facts {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 40px;
        }

        .lp-fact {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 18px 10px 10px;
            border-radius: 18px;
            background: var(--sc-surface);
            border: 1px solid var(--sc-line);
            box-shadow: var(--sc-shadow-1);
        }

        .lp-fact i {
            display: grid;
            place-items: center;
            width: 38px;
            height: 38px;
            border-radius: 12px;
            font-size: 15px;
        }

        .lp-fact strong {
            display: block;
            font-size: 16px;
            font-weight: 800;
            color: var(--sc-ink);
            line-height: 1.1;
        }

        .lp-fact span {
            font-size: 12.5px;
            font-weight: 500;
            color: var(--sc-mute);
        }

        /* ── Hero product mock ── */
        .mock {
            position: relative;
            max-width: 520px;
            margin: 0 auto;
            width: 100%;
        }

        .mock-glow {
            position: absolute;
            inset: -8% -6%;
            background: radial-gradient(closest-side, rgba(124, 58, 237, .32), transparent 70%);
            filter: blur(30px);
            pointer-events: none;
        }

        .mock-frame {
            position: relative;
            aspect-ratio: 4 / 4.6;
            border-radius: 40px;
            overflow: hidden;
            background: var(--sc-surface);
            border: 1px solid var(--sc-line);
            box-shadow: var(--sc-shadow-3);
        }

        .mock-map {
            position: absolute;
            inset: 0;
            background:
                radial-gradient(60% 50% at 20% 15%, rgba(99, 102, 241, .16), transparent 70%),
                linear-gradient(var(--sc-line) 1px, transparent 1px) 0 0 / 44px 44px,
                linear-gradient(90deg, var(--sc-line) 1px, transparent 1px) 0 0 / 44px 44px,
                var(--sc-surface-2);
        }

        .mock-block {
            position: absolute;
            border-radius: 18px;
            background: var(--sc-surface-3);
            opacity: .75;
        }

        .mock-route {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
        }

        .mock-bus {
            position: absolute;
            left: 52%;
            top: 39%;
            transform: translate(-50%, -50%);
            display: grid;
            place-items: center;
            width: 54px;
            height: 54px;
            border-radius: 18px;
            color: #fff;
            font-size: 20px;
            background: var(--sc-grad);
            box-shadow: 0 16px 30px -10px rgba(79, 70, 229, .85);
            animation: lp-bob 3.2s ease-in-out infinite;
        }

        .mock-bus::after {
            content: '';
            position: absolute;
            inset: -10px;
            border-radius: 26px;
            border: 2px solid rgba(99, 102, 241, .45);
            animation: lp-ping 2.4s cubic-bezier(0, 0, .2, 1) infinite;
        }

        .mock-top {
            position: absolute;
            top: 18px;
            left: 18px;
            right: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            border-radius: 20px;
            background: color-mix(in srgb, var(--sc-surface) 86%, transparent);
            -webkit-backdrop-filter: blur(14px);
            backdrop-filter: blur(14px);
            border: 1px solid var(--sc-line);
            font-size: 14px;
            font-weight: 600;
            color: var(--sc-mute);
        }

        .mock-top i {
            color: var(--sc-brand);
        }

        .mock-sheet {
            position: absolute;
            left: 14px;
            right: 14px;
            bottom: 14px;
            padding: 20px;
            border-radius: 28px;
            background: var(--sc-surface);
            border: 1px solid var(--sc-line);
            box-shadow: 0 -12px 40px -18px rgba(30, 34, 110, .3);
        }

        .mock-eta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .mock-eta h4 {
            font-size: 17px;
            font-weight: 800;
            letter-spacing: -.02em;
            color: var(--sc-ink);
        }

        .mock-eta p {
            font-size: 13px;
            color: var(--sc-mute);
            margin-top: 2px;
        }

        .mock-badge {
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 12.5px;
            font-weight: 700;
            color: #047857;
            background: rgba(16, 185, 129, .14);
            white-space: nowrap;
        }

        .dark .mock-badge {
            color: #6ee7b7;
        }

        .mock-fare {
            display: flex;
            align-items: stretch;
            gap: 10px;
            margin-top: 16px;
        }

        .mock-fare>div {
            flex: 1;
            padding: 12px 14px;
            border-radius: 16px;
            background: var(--sc-surface-2);
            border: 1px solid var(--sc-line);
        }

        .mock-fare small {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: var(--sc-mute);
        }

        .mock-fare b {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: -.02em;
            color: var(--sc-ink);
        }

        .mock-fare>div.on {
            background: var(--sc-brand-soft);
            border-color: rgba(99, 102, 241, .4);
        }

        .mock-fare>div.on b {
            color: var(--sc-brand-text);
        }

        .mock-float {
            position: absolute;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px 12px 12px;
            border-radius: 20px;
            background: var(--sc-surface);
            border: 1px solid var(--sc-line);
            box-shadow: var(--sc-shadow-3);
            font-size: 13.5px;
            font-weight: 700;
            color: var(--sc-ink);
        }

        .mock-float small {
            display: block;
            font-size: 12px;
            font-weight: 500;
            color: var(--sc-mute);
        }

        .mock-float i {
            display: grid;
            place-items: center;
            width: 38px;
            height: 38px;
            border-radius: 12px;
            font-size: 15px;
        }

        .mock-float.a {
            top: 17%;
            right: -14px;
            animation: lp-bob 4s ease-in-out infinite;
        }

        .mock-float.b {
            top: 54%;
            left: -22px;
            animation: lp-bob 4.6s ease-in-out -2s infinite;
        }

        @media (max-width: 600px) {
            .mock-float.a {
                right: -4px;
            }

            .mock-float.b {
                left: -6px;
            }
        }

        @keyframes lp-bob {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-8px);
            }
        }

        .mock-bus {
            animation-name: lp-bob-c;
        }

        @keyframes lp-bob-c {

            0%,
            100% {
                transform: translate(-50%, -50%);
            }

            50% {
                transform: translate(-50%, calc(-50% - 7px));
            }
        }

        @keyframes lp-ping {
            75%,
            100% {
                transform: scale(1.5);
                opacity: 0;
            }
        }

        @keyframes lp-marquee {
            to {
                transform: translateX(-50%);
            }
        }

        /* ── Marquee ── */
        .lp-strip {
            border-block: 1px solid var(--sc-line);
            background: color-mix(in srgb, var(--sc-surface) 70%, transparent);
            overflow: hidden;
            padding: 18px 0;
        }

        .lp-strip-track {
            display: flex;
            width: max-content;
            animation: lp-marquee 36s linear infinite;
        }

        .lp-strip span {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 0 28px;
            font-size: 15px;
            font-weight: 600;
            color: var(--sc-ink-2);
            white-space: nowrap;
        }

        .lp-strip i {
            font-size: 14px;
            color: var(--sc-brand);
        }

        /* ── Sections ── */
        .lp-section {
            padding: 96px 20px;
            position: relative;
        }

        .lp-h2 {
            margin: 14px 0 16px;
            font-size: clamp(2rem, 4.2vw, 3.3rem);
            font-weight: 800;
            line-height: 1.08;
            letter-spacing: -.035em;
            color: var(--sc-ink);
        }

        .lp-sub {
            max-width: 620px;
            font-size: 1.1rem;
            line-height: 1.7;
            color: var(--sc-mute);
        }

        .lp-grid-4 {
            display: grid;
            gap: 20px;
            margin-top: 56px;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        }

        .lp-card {
            position: relative;
            padding: 30px;
            border-radius: 30px;
            background: var(--sc-surface);
            border: 1px solid var(--sc-line);
            box-shadow: var(--sc-shadow-2);
            transition: transform .3s cubic-bezier(.16, 1, .3, 1), box-shadow .3s, border-color .3s;
            overflow: hidden;
        }

        .lp-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--sc-shadow-3);
            border-color: var(--sc-line-strong);
        }

        .lp-card h4 {
            margin: 22px 0 10px;
            font-size: 20px;
            font-weight: 800;
            letter-spacing: -.02em;
            color: var(--sc-ink);
        }

        .lp-card p {
            font-size: 15.5px;
            line-height: 1.65;
            color: var(--sc-mute);
        }

        .lp-ico {
            display: grid;
            place-items: center;
            width: 54px;
            height: 54px;
            border-radius: 18px;
            font-size: 21px;
            color: rgb(var(--t));
            background: rgb(var(--t) / .13);
        }

        .lp-link-arrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 22px;
            font-size: 14.5px;
            font-weight: 700;
            color: var(--sc-brand-text);
        }

        .lp-link-arrow i {
            transition: transform .25s;
        }

        .lp-card:hover .lp-link-arrow i {
            transform: translateX(5px);
        }

        /* feature bento */
        .bento {
            display: grid;
            gap: 20px;
            margin-top: 56px;
            grid-template-columns: 1fr;
        }

        @media (min-width: 760px) {
            .bento {
                grid-template-columns: repeat(6, 1fr);
            }

            .bento .span-3 {
                grid-column: span 3;
            }

            .bento .span-2 {
                grid-column: span 2;
            }

            .bento .span-4 {
                grid-column: span 4;
            }
        }

        .bento .lp-card h4 {
            margin-top: 18px;
        }

        .lp-hero-card {
            color: #fff;
            border: 0;
            background:
                radial-gradient(90% 120% at 100% 0%, rgba(236, 72, 153, .5), transparent 55%),
                linear-gradient(135deg, #4338ca 0%, #6d28d9 100%);
        }

        .lp-hero-card h4,
        .lp-hero-card p {
            color: #fff;
        }

        .lp-hero-card p {
            opacity: .86;
        }

        .lp-hero-card .lp-ico {
            color: #fff;
            background: rgba(255, 255, 255, .18);
        }

        /* audience */
        .lp-role {
            display: flex;
            flex-direction: column;
        }

        .lp-role .tag {
            margin-top: 22px;
            font-size: 12.5px;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: rgb(var(--t));
        }

        .lp-role h4 {
            margin-top: 6px;
        }

        .lp-role .foot {
            margin-top: auto;
            padding-top: 22px;
        }

        .lp-role .roles {
            font-size: 13.5px;
            font-weight: 600;
            color: var(--sc-mute);
        }

        /* CTA */
        .lp-cta {
            position: relative;
            overflow: hidden;
            border-radius: 44px;
            padding: clamp(32px, 6vw, 72px);
            color: #fff;
            background:
                radial-gradient(70% 90% at 100% 0%, rgba(236, 72, 153, .55), transparent 60%),
                radial-gradient(60% 80% at 0% 100%, rgba(34, 211, 238, .28), transparent 60%),
                linear-gradient(135deg, #3730a3 0%, #5b21b6 100%);
            box-shadow: 0 50px 100px -40px rgba(67, 56, 202, .75);
        }

        .lp-cta::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: radial-gradient(rgba(255, 255, 255, .16) 1px, transparent 1.2px);
            background-size: 24px 24px;
            -webkit-mask-image: linear-gradient(120deg, transparent 30%, #000);
            mask-image: linear-gradient(120deg, transparent 30%, #000);
        }

        .lp-cta>* {
            position: relative;
        }

        .lp-step {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px 20px 16px 16px;
            border-radius: 22px;
            background: rgba(255, 255, 255, .1);
            border: 1px solid rgba(255, 255, 255, .16);
            -webkit-backdrop-filter: blur(10px);
            backdrop-filter: blur(10px);
        }

        .lp-step b {
            display: grid;
            place-items: center;
            flex: none;
            width: 42px;
            height: 42px;
            border-radius: 14px;
            font-size: 17px;
            font-weight: 800;
            color: #3730a3;
            background: #fff;
        }

        .lp-step small {
            display: block;
            font-size: 12.5px;
            font-weight: 600;
            opacity: .75;
        }

        .lp-step span {
            font-size: 15.5px;
            font-weight: 600;
        }

        /* Footer */
        .lp-footer {
            padding: 64px 20px 36px;
            border-top: 1px solid var(--sc-line);
            background: color-mix(in srgb, var(--sc-surface) 60%, transparent);
        }

        .lp-footer h6 {
            margin-bottom: 14px;
            font-size: 13.5px;
            font-weight: 800;
            color: var(--sc-ink);
        }

        .lp-footer li {
            margin-bottom: 10px;
        }

        .lp-footer a {
            font-size: 15px;
            font-weight: 500;
            color: var(--sc-mute);
            transition: color .2s;
        }

        .lp-footer a:hover {
            color: var(--sc-brand-text);
        }

        /* Mobile menu */
        .lp-sheet {
            position: fixed;
            z-index: 100;
            top: 12px;
            right: 12px;
            bottom: 12px;
            width: min(380px, calc(100% - 24px));
            display: flex;
            flex-direction: column;
            padding: 22px;
            border-radius: 32px;
            background: var(--sc-surface);
            border: 1px solid var(--sc-line);
            box-shadow: var(--sc-shadow-3);
            transform: translateX(115%);
            transition: transform .45s cubic-bezier(.16, 1, .3, 1);
        }

        .lp-sheet.open {
            transform: none;
        }

        .lp-scrim {
            position: fixed;
            z-index: 90;
            inset: 0;
            background: rgba(8, 10, 30, .5);
            -webkit-backdrop-filter: blur(5px);
            backdrop-filter: blur(5px);
            opacity: 0;
            pointer-events: none;
            transition: opacity .3s;
        }

        .lp-scrim.open {
            opacity: 1;
            pointer-events: auto;
        }

        .lp-sheet a.row {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px;
            border-radius: 20px;
            font-weight: 700;
            color: var(--sc-ink);
        }

        .lp-sheet a.row:hover {
            background: var(--sc-surface-2);
        }

        .lp-sheet a.row small {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: var(--sc-mute);
        }

        /* Scroll reveal */
        .reveal {
            opacity: 0;
            transform: translateY(26px);
            transition: opacity .8s cubic-bezier(.16, 1, .3, 1), transform .8s cubic-bezier(.16, 1, .3, 1);
        }

        .reveal.active {
            opacity: 1;
            transform: none;
        }

        .reveal[data-d="1"] {
            transition-delay: .08s;
        }

        .reveal[data-d="2"] {
            transition-delay: .16s;
        }

        .reveal[data-d="3"] {
            transition-delay: .24s;
        }

        .hero-in {
            animation: sc-rise .9s cubic-bezier(.16, 1, .3, 1) both;
        }

        @media (max-width: 899px) {
            .lp-hide-sm {
                display: none !important;
            }
        }

        @media (min-width: 900px) {
            .lp-show-sm {
                display: none !important;
            }
        }
    </style>
    <noscript>
        <style>
            .reveal {
                opacity: 1 !important;
                transform: none !important;
            }
        </style>
    </noscript>

    @include('partials.global-loading')
</head>

<body class="antialiased">

    <!-- ==================== NAVBAR ==================== -->
    <header class="lp-nav" role="banner">
        <a href="{{ route('home') }}" class="lp-logo" aria-label="SmartCommute home">
            <span class="lp-logo-mark"><i class="fa-solid fa-bus"></i></span>
            <span>Smart<b>Commute</b></span>
        </a>

        <nav class="lp-links" aria-label="Sections">
            <a href="#features" class="lp-link">Features</a>
            <a href="#services" class="lp-link">Services</a>
            <a href="#audience" class="lp-link">Who it's for</a>
            <a href="#get-started" class="lp-link">Get started</a>
        </nav>

        <div class="flex items-center gap-2">
            <button type="button" id="themeBtn" class="lp-icon-btn" aria-label="Toggle dark mode"
                onclick="toggleLandingTheme()">
                <i class="fa-solid fa-moon dark:hidden"></i>
                <i class="fa-solid fa-sun hidden dark:inline"></i>
            </button>

            @guest
                <a href="{{ url('/login') }}" class="lp-link lp-hide-sm">Log in</a>
                <a href="{{ route('map.guest') }}" class="lp-link lp-hide-sm"><i
                        class="fa-solid fa-map-location-dot mr-1.5 text-[13px]"></i>View map</a>
                <a href="{{ url('/register') }}" class="lp-btn lp-hide-sm">Get started <i
                        class="fa-solid fa-arrow-right text-xs"></i></a>
            @endguest

            @auth
                @php $homeRole = Auth::user()->roles->first()?->name; @endphp
                @if (in_array($homeRole, ['maintenance_manager', 'driver_manager']))
                    <a href="{{ route('dashboard') }}" class="lp-btn lp-hide-sm"><i
                            class="fa-solid fa-gauge-high text-xs"></i> Dashboard</a>
                @else
                    <a href="{{ route('map') }}" class="lp-btn lp-hide-sm"><i
                            class="fa-solid fa-map-location-dot text-xs"></i> Open map</a>
                @endif
            @endauth

            <button type="button" id="hamburgerBtn" onclick="openMobileMenu()" class="lp-icon-btn lp-show-sm"
                aria-label="Open menu">
                <i class="fa-solid fa-bars"></i>
            </button>
        </div>
    </header>

    <!-- Mobile menu -->
    <div id="mobileOverlay" class="lp-scrim" onclick="closeMobileMenu()"></div>
    <aside id="mobileMenu" class="lp-sheet" aria-label="Menu">
        <div class="flex items-center justify-between mb-4">
            <span class="lp-logo"><span class="lp-logo-mark"><i class="fa-solid fa-bus"></i></span><span>Smart<b>Commute</b></span></span>
            <button type="button" onclick="closeMobileMenu()" class="lp-icon-btn" aria-label="Close menu"><i
                    class="fa-solid fa-xmark"></i></button>
        </div>

        <nav class="flex-1 overflow-y-auto space-y-1">
            @guest
                <a href="{{ url('/login') }}" onclick="closeMobileMenu()" class="row">
                    <span class="lp-ico" style="--t: 168 85 247; width:46px;height:46px;font-size:18px;"><i
                            class="fa-solid fa-right-to-bracket"></i></span>
                    <span>Log in<small>Access your account</small></span>
                </a>
                <a href="{{ route('map.guest') }}" onclick="closeMobileMenu()" class="row">
                    <span class="lp-ico" style="--t: 16 185 129; width:46px;height:46px;font-size:18px;"><i
                            class="fa-solid fa-map"></i></span>
                    <span>View map<small>See PUJs on the road now</small></span>
                </a>
            @endguest
            @auth
                <a href="{{ route('map') }}" onclick="closeMobileMenu()" class="row">
                    <span class="lp-ico" style="--t: 99 102 241; width:46px;height:46px;font-size:18px;"><i
                            class="fa-solid fa-map-location-dot"></i></span>
                    <span>Map<small>Your commute hub</small></span>
                </a>
                <a href="{{ route('dashboard') }}" onclick="closeMobileMenu()" class="row">
                    <span class="lp-ico" style="--t: 168 85 247; width:46px;height:46px;font-size:18px;"><i
                            class="fa-solid fa-gauge-high"></i></span>
                    <span>Dashboard<small>Your account home</small></span>
                </a>
            @endauth

            <div class="my-3 h-px" style="background: var(--sc-line)"></div>
            <a href="#features" onclick="closeMobileMenu()" class="row">Features</a>
            <a href="#services" onclick="closeMobileMenu()" class="row">Services</a>
            <a href="#audience" onclick="closeMobileMenu()" class="row">Who it's for</a>
        </nav>

        @guest
            <a href="{{ url('/register') }}" onclick="closeMobileMenu()" class="lp-btn lg mt-4 w-full">Get started free</a>
        @endguest
    </aside>

    <main>
        <!-- ==================== HERO ==================== -->
        <section class="lp-hero">
            <div class="lp-wrap">
                <div class="lp-hero-grid">

                    <div class="hero-in">
                        <span class="lp-pill"><i class="fa-solid fa-location-arrow"></i> Live PUJ tracking, now in your
                            pocket</span>

                        <h1 class="lp-h1">
                            <span class="block">Track your PUJ.</span>
                            <span class="block">Know your <span class="sc-gradient-text">fare.</span></span>
                            <span class="block">Pay cash-free.</span>
                        </h1>

                        <p class="lp-lead">
                            One map for the things you usually have to guess: where the nearest PUJ is, how far you're
                            going, and what the ride will cost. Live, transparent and cash-free.
                        </p>

                        <div class="flex flex-wrap items-center gap-3 mt-9">
                            @guest
                                <a href="{{ url('/register') }}" class="lp-btn lg">Get started <i
                                        class="fa-solid fa-arrow-right text-sm"></i></a>
                                <a href="{{ route('map.guest') }}" class="lp-btn lg ghost"><i
                                        class="fa-solid fa-map-location-dot text-sm"
                                        style="color: var(--sc-brand)"></i> View the live map</a>
                            @else
                                <a href="{{ in_array(Auth::user()->roles->first()?->name, ['maintenance_manager', 'driver_manager']) ? route('dashboard') : route('map') }}"
                                    class="lp-btn lg">Continue to your account <i
                                        class="fa-solid fa-arrow-right text-sm"></i></a>
                            @endguest
                        </div>

                        <div class="lp-facts">
                            <div class="lp-fact">
                                <i style="color:#6366f1;background:rgba(99,102,241,.14)" class="fa-solid fa-satellite-dish"></i>
                                <div><strong>Live</strong><span>PUJ tracking</span></div>
                            </div>
                            <div class="lp-fact">
                                <i style="color:#10b981;background:rgba(16,185,129,.14)" class="fa-solid fa-wallet"></i>
                                <div><strong>Many</strong><span>ways to pay</span></div>
                            </div>
                            <div class="lp-fact">
                                <i style="color:#ec4899;background:rgba(236,72,153,.14)" class="fa-solid fa-gift"></i>
                                <div><strong>Free</strong><span>guest map access</span></div>
                            </div>
                        </div>
                    </div>

                    <!-- Illustrative product mock -->
                    <div class="mock hero-in" style="animation-delay:.15s" aria-hidden="true">
                        <div class="mock-glow"></div>
                        <div class="mock-frame">
                            <div class="mock-map">
                                <span class="mock-block" style="left:6%;top:26%;width:26%;height:15%"></span>
                                <span class="mock-block" style="left:68%;top:12%;width:24%;height:19%"></span>
                                <span class="mock-block" style="left:10%;top:52%;width:20%;height:12%"></span>
                                <span class="mock-block" style="left:72%;top:46%;width:22%;height:14%"></span>
                                <svg class="mock-route" viewBox="0 0 400 460" fill="none" preserveAspectRatio="none">
                                    <defs>
                                        <linearGradient id="rg" x1="0" y1="0" x2="1" y2="1">
                                            <stop offset="0" stop-color="#6366f1" />
                                            <stop offset="1" stop-color="#ec4899" />
                                        </linearGradient>
                                    </defs>
                                    <path d="M60 330 C 110 250, 150 290, 190 215 S 290 150, 340 90" stroke="url(#rg)"
                                        stroke-width="7" stroke-linecap="round" stroke-dasharray="1 0" opacity=".95" />
                                    <circle cx="60" cy="330" r="11" fill="#fff" stroke="#6366f1" stroke-width="5" />
                                    <circle cx="340" cy="90" r="11" fill="#fff" stroke="#ec4899" stroke-width="5" />
                                </svg>
                                <div class="mock-bus"><i class="fa-solid fa-bus"></i></div>
                            </div>

                            <div class="mock-top"><i class="fa-solid fa-magnifying-glass"></i> Where are you headed?
                            </div>

                            <div class="mock-sheet">
                                <div class="mock-eta">
                                    <div>
                                        <h4>PUJ approaching</h4>
                                        <p>Sample route · estimate updates live</p>
                                    </div>
                                    <span class="mock-badge">● On time</span>
                                </div>
                                <div class="mock-fare">
                                    <div class="on"><small>Regular</small><b>₱ 13.00</b></div>
                                    <div><small>Discounted</small><b>₱ 10.40</b></div>
                                </div>
                            </div>
                        </div>

                        <div class="mock-float a">
                            <i style="color:#10b981;background:rgba(16,185,129,.15)" class="fa-solid fa-check"></i>
                            <div>Fare paid<small>Wallet · receipt saved</small></div>
                        </div>
                        <div class="mock-float b">
                            <i style="color:#f59e0b;background:rgba(245,158,11,.16)" class="fa-solid fa-bolt"></i>
                            <div>GCash &amp; Maya<small>Top up in seconds</small></div>
                        </div>
                    </div>

                </div>
            </div>
        </section>

        <!-- ==================== MARQUEE ==================== -->
        <div class="lp-strip" aria-hidden="true">
            <div class="lp-strip-track">
                @for ($i = 0; $i < 4; $i++)
                    <span><i class="fa-solid fa-location-crosshairs"></i> Real-time GPS tracking</span>
                    <span><i class="fa-solid fa-wallet"></i> Cash-free payments</span>
                    <span><i class="fa-solid fa-chart-column"></i> Fleet &amp; driver reports</span>
                    <span><i class="fa-solid fa-calculator"></i> Fare calculator</span>
                @endfor
            </div>
        </div>

        <!-- ==================== FEATURES ==================== -->
        <section id="features" class="lp-section">
            <div class="lp-wrap">
                <div class="reveal">
                    <span class="sc-eyebrow">Features</span>
                    <h2 class="lp-h2">Your commute, <span class="sc-gradient-text">made smarter.</span></h2>
                    <p class="lp-sub">SmartCommute puts the things you usually have to guess on one map. Nothing to
                        install, and no account needed to take a look.</p>
                </div>

                <div class="bento">
                    <div class="lp-card lp-hero-card span-4 reveal">
                        <span class="lp-ico"><i class="fa-solid fa-satellite-dish"></i></span>
                        <h4 style="font-size:26px">Real-time, all the time</h4>
                        <p style="max-width:460px">Live PUJ positions and arrival estimates, updated as drivers move.
                            Head out when your ride is actually close, not when you hope it is.</p>
                    </div>
                    <div class="lp-card span-2 reveal" data-d="1" style="--t: 16 185 129">
                        <span class="lp-ico"><i class="fa-solid fa-gift"></i></span>
                        <h4>Open to guests</h4>
                        <p>Browse the live map and try the fare calculator before you ever create an account.</p>
                    </div>
                    <div class="lp-card span-3 reveal" style="--t: 99 102 241">
                        <span class="lp-ico"><i class="fa-solid fa-receipt"></i></span>
                        <h4>Official fares</h4>
                        <p>Regular and discounted fares, worked out by distance from the published fare table.</p>
                    </div>
                    <div class="lp-card span-3 reveal" data-d="1" style="--t: 236 72 153">
                        <span class="lp-ico"><i class="fa-solid fa-shield-halved"></i></span>
                        <h4>Secure by design</h4>
                        <p>Email-verified accounts, fares priced on the server, and a receipt for every payment.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ==================== SERVICES ==================== -->
        <section id="services" class="lp-section" style="padding-top:20px">
            <div class="lp-wrap">
                <div class="reveal">
                    <span class="sc-eyebrow">The SmartCommute system</span>
                    <h2 class="lp-h2">From the curb <span class="sc-gradient-text">to the garage.</span></h2>
                    <p class="lp-sub">Tools for the people who ride PUJs, the people who drive them, and the people
                        who keep them running.</p>
                </div>

                <div class="lp-grid-4">
                    <div class="lp-card reveal" style="--t: 99 102 241">
                        <span class="lp-ico"><i class="fa-solid fa-location-dot"></i></span>
                        <h4>Live GPS tracking</h4>
                        <p>See active PUJs on a live map, each with an arrival estimate, and spot the nearest one at a
                            glance.</p>
                        <span class="lp-link-arrow">Live update <i class="fa-solid fa-arrow-right"></i></span>
                    </div>
                    <div class="lp-card reveal" data-d="1" style="--t: 245 158 11">
                        <span class="lp-ico"><i class="fa-solid fa-clock"></i></span>
                        <h4>Time keeping</h4>
                        <p>Drivers clock in and out right from the map. Hours and overtime are logged per shift, ready
                            for managers to review.</p>
                        <span class="lp-link-arrow">Shift logs <i class="fa-solid fa-arrow-right"></i></span>
                    </div>
                    <div class="lp-card reveal" data-d="2" style="--t: 16 185 129">
                        <span class="lp-ico"><i class="fa-solid fa-wallet"></i></span>
                        <h4>Cash-free fares</h4>
                        <p>Top up your wallet and pay with wallet balance, GCash or Maya. Every payment gets a receipt
                            and a place in your history.</p>
                        <span class="lp-link-arrow">Wallet <i class="fa-solid fa-arrow-right"></i></span>
                    </div>
                    <div class="lp-card reveal" data-d="3" style="--t: 249 115 22">
                        <span class="lp-ico"><i class="fa-solid fa-screwdriver-wrench"></i></span>
                        <h4>Maintenance records</h4>
                        <p>Preventive maintenance records for every vehicle, with service dates, odometer readings and
                            costs on record.</p>
                        <span class="lp-link-arrow">Fleet health <i class="fa-solid fa-arrow-right"></i></span>
                    </div>
                </div>
            </div>
        </section>

        <!-- ==================== AUDIENCE ==================== -->
        <section id="audience" class="lp-section">
            <div class="lp-wrap">
                <div class="reveal" style="text-align:center">
                    <span class="sc-eyebrow">Who it's for</span>
                    <h2 class="lp-h2">One system, <span class="sc-gradient-text">three seats.</span></h2>
                    <p class="lp-sub" style="margin-inline:auto">Whether you ride, drive or run the fleet, there is a
                        side of SmartCommute built for you.</p>
                </div>

                <div class="lp-grid-4" style="grid-template-columns: repeat(auto-fit, minmax(290px, 1fr));">
                    <div class="lp-card lp-role reveal" style="--t: 99 102 241">
                        <span class="lp-ico"><i class="fa-solid fa-users"></i></span>
                        <span class="tag">For commuters</span>
                        <h4>Ride informed</h4>
                        <p>Watch PUJs approach on the live map, plan a trip to see the distance and fare, then pay with
                            your wallet, GCash or Maya. Every payment is saved to your history.</p>
                        @guest
                            <div class="foot"><a href="{{ url('/register') }}" class="lp-link-arrow" style="margin:0">Create
                                    an account <i class="fa-solid fa-arrow-right"></i></a></div>
                        @endguest
                    </div>
                    <div class="lp-card lp-role reveal" data-d="1" style="--t: 16 185 129">
                        <span class="lp-ico"><i class="fa-solid fa-id-card"></i></span>
                        <span class="tag">For drivers</span>
                        <h4>Your shift, on record</h4>
                        <p>Register online with your licence details. Once you are approved and assigned a vehicle,
                            clock in from the map, share your location with waiting riders, and review your own weekly
                            hours.</p>
                        @guest
                            <div class="foot"><a href="{{ route('driver.register.page') }}" class="lp-link-arrow"
                                    style="margin:0">Register as a driver <i class="fa-solid fa-arrow-right"></i></a>
                            </div>
                        @endguest
                    </div>
                    <div class="lp-card lp-role reveal" data-d="2" style="--t: 236 72 153">
                        <span class="lp-ico"><i class="fa-solid fa-sitemap"></i></span>
                        <span class="tag">For operators</span>
                        <h4>Run the fleet</h4>
                        <p>Approve drivers, review timekeeping, record violations, schedule vehicle maintenance, set
                            fare rates, and export financial, driver and fleet reports as CSV.</p>
                        <div class="foot"><span class="roles">Admin &middot; Driver Manager &middot; Maintenance
                                Manager</span></div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ==================== GET STARTED ==================== -->
        <section id="get-started" class="lp-section" style="padding-top:20px">
            <div class="lp-wrap">
                <div class="lp-cta reveal">
                    <div class="grid gap-10 lg:grid-cols-2 lg:items-center">
                        <div>
                            <span class="sc-eyebrow" style="color:#c7d2fe"><span
                                    style="display:none"></span>Get started</span>
                            <h2 class="lp-h2" style="color:#fff">See it live.</h2>
                            <p class="lp-sub" style="color:rgba(255,255,255,.82)">The map is open to everyone. Look at
                                the PUJs on the road right now, then create an account when you are ready to pay your
                                fare from your phone.</p>

                            <div class="flex flex-wrap gap-3 mt-8">
                                @guest
                                    <a href="{{ route('map.guest') }}" class="lp-btn lg light"><i
                                            class="fa-solid fa-map-location-dot text-sm"></i> View the live map</a>
                                    <a href="{{ url('/register') }}" class="lp-btn lg glass">Create a commuter account</a>
                                    <a href="{{ route('driver.register.page') }}" class="lp-btn lg glass">Register as a
                                        driver</a>
                                @else
                                    <a href="{{ route('dashboard') }}" class="lp-btn lg light">Go to your dashboard <i
                                            class="fa-solid fa-arrow-right text-sm"></i></a>
                                @endguest
                            </div>
                            @guest
                                <p class="mt-6 text-[15px]" style="color:rgba(255,255,255,.78)">Already have an account?
                                    <a href="{{ url('/login') }}" class="font-bold text-white underline underline-offset-4">Log
                                        in</a>
                                </p>
                            @endguest
                        </div>

                        <div class="space-y-3">
                            <div class="lp-step"><b>1</b>
                                <div><small>Step 1</small><span>Open the live map, no account needed</span></div>
                            </div>
                            <div class="lp-step"><b>2</b>
                                <div><small>Step 2</small><span>Set your pick-up and destination to see the fare</span>
                                </div>
                            </div>
                            <div class="lp-step"><b>3</b>
                                <div><small>Step 3</small><span>Sign up, top up, and pay cash-free</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- ==================== FOOTER ==================== -->
    <footer class="lp-footer">
        <div class="lp-wrap">
            <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
                <div class="lg:col-span-1">
                    <a href="{{ route('home') }}" class="lp-logo"><span class="lp-logo-mark"><i
                                class="fa-solid fa-bus"></i></span><span>Smart<b>Commute</b></span></a>
                    <p class="mt-4 text-[15px] leading-relaxed" style="color: var(--sc-mute); max-width: 320px">Live
                        PUJ tracking, fare planning and cash-free payments for commuters, with timekeeping and
                        maintenance tools for the people who run the fleet.</p>
                </div>

                <div>
                    <h6>Product</h6>
                    <ul>
                        <li><a href="#features">Features</a></li>
                        <li><a href="#services">Services</a></li>
                        <li><a href="#audience">Who it's for</a></li>
                    </ul>
                </div>

                @guest
                    <div>
                        <h6>Commuters</h6>
                        <ul>
                            <li><a href="{{ route('map.guest') }}">Live map</a></li>
                            <li><a href="{{ url('/register') }}">Create account</a></li>
                            <li><a href="{{ url('/login') }}">Log in</a></li>
                        </ul>
                    </div>
                    <div>
                        <h6>Drivers</h6>
                        <ul>
                            <li><a href="{{ route('driver.register.page') }}">Driver registration</a></li>
                            <li><a href="{{ url('/login') }}">Driver log in</a></li>
                        </ul>
                    </div>
                @else
                    <div>
                        <h6>Account</h6>
                        <ul>
                            <li><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li><a href="{{ route('profile') }}">Profile</a></li>
                        </ul>
                    </div>
                @endguest
            </div>

            <div class="mt-12 pt-6 text-[14px]" style="border-top:1px solid var(--sc-line); color: var(--sc-mute)">
                &copy; {{ date('Y') }} SmartCommute. All rights reserved.
            </div>
        </div>
    </footer>

    <!-- ==================== SCRIPTS ==================== -->
    <script>
        function openMobileMenu() {
            document.getElementById('mobileMenu').classList.add('open');
            document.getElementById('mobileOverlay').classList.add('open');
            document.body.style.overflow = 'hidden';
        }

        function closeMobileMenu() {
            document.getElementById('mobileMenu').classList.remove('open');
            document.getElementById('mobileOverlay').classList.remove('open');
            document.body.style.overflow = '';
        }

        // Guests keep their choice on this device; signed-in users change theme in Settings.
        function toggleLandingTheme() {
            var dark = !document.documentElement.classList.contains('dark');
            document.documentElement.classList.toggle('dark', dark);
            try { localStorage.setItem('color-theme', dark ? 'dark' : 'light'); } catch (e) {}
        }

        // Scroll reveal
        (function() {
            var els = document.querySelectorAll('.reveal');
            if (!('IntersectionObserver' in window)) {
                els.forEach(function(el) { el.classList.add('active'); });
                return;
            }
            var io = new IntersectionObserver(function(entries) {
                entries.forEach(function(e) {
                    if (e.isIntersecting) {
                        e.target.classList.add('active');
                        io.unobserve(e.target);
                    }
                });
            }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
            els.forEach(function(el) { io.observe(el); });
        })();

        window.addEventListener('resize', function() {
            if (window.innerWidth >= 900) closeMobileMenu();
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeMobileMenu();
        });
    </script>
</body>

</html>
