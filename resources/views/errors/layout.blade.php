<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('code') · @yield('title') — SmartCommute</title>
    <script>
        (function() {
            var stored = null;
            try { stored = localStorage.getItem('color-theme'); } catch (e) {}
            var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.classList.toggle('dark', (stored || (prefersDark ? 'dark' : 'light')) === 'dark');
        })();
    </script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    @include('partials.theme')
    <style>
        body {
            min-height: 100svh;
            display: grid;
            place-items: center;
            padding: 24px;
        }

        .er {
            position: relative;
            width: 100%;
            max-width: 560px;
            padding: clamp(32px, 6vw, 56px);
            text-align: center;
            border-radius: 40px;
            background: var(--sc-surface);
            border: 1px solid var(--sc-line);
            box-shadow: var(--sc-shadow-3);
            animation: sc-rise .7s cubic-bezier(.16, 1, .3, 1) both;
        }

        .er-code {
            font-size: clamp(5rem, 22vw, 8.5rem);
            font-weight: 800;
            line-height: .95;
            letter-spacing: -.06em;
            background: var(--sc-grad-warm);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .er-ico {
            display: grid;
            place-items: center;
            width: 64px;
            height: 64px;
            margin: 0 auto 22px;
            border-radius: 22px;
            font-size: 26px;
            color: #fff;
            background: var(--sc-grad);
            box-shadow: 0 16px 30px -10px rgba(79, 70, 229, .7);
        }

        .er h1 {
            margin-top: 14px;
            font-size: 1.7rem;
            font-weight: 800;
            letter-spacing: -.03em;
            color: var(--sc-ink);
        }

        .er p {
            margin: 10px auto 0;
            max-width: 400px;
            font-size: 16px;
            line-height: 1.65;
            color: var(--sc-mute);
        }

        .er-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 12px;
            margin-top: 30px;
        }

        .er-brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-top: 34px;
            font-size: 14px;
            font-weight: 700;
            color: var(--sc-mute);
        }

        .er-brand i {
            color: var(--sc-brand);
        }
    </style>
</head>

<body>
    <main class="er" role="main">
        <div class="er-ico"><i class="fa-solid @yield('icon', 'fa-bus')"></i></div>
        <div class="er-code">@yield('code')</div>
        <h1>@yield('title')</h1>
        <p>@yield('message')</p>

        <div class="er-actions">
            @hasSection('actions')
                @yield('actions')
            @else
                <a href="{{ url('/') }}" class="sc-btn"><i class="fa-solid fa-house"></i> Back to home</a>
                <a href="javascript:history.back()" class="sc-btn sc-btn-ghost">Go back</a>
            @endif
        </div>

        <div class="er-brand"><i class="fa-solid fa-bus"></i> SmartCommute</div>
    </main>
</body>

</html>
