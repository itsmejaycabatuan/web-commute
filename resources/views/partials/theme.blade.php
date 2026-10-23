{{--
    SmartCommute "Aurora" design system — include once in <head>, before any
    `tailwind.config = …` assignment (partials/head-scripts and
    partials/commuter-head-scripts already do; standalone pages include it directly).

      • Plus Jakarta Sans as the product typeface
      • public/css/theme.css  — tokens + restyling of every shared utility
      • window.SC_COLORS      — Tailwind palette overrides (`blue` is the brand: indigo)
--}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link
    href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,500&display=swap"
    rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/theme.css') }}?v={{ @filemtime(public_path('css/theme.css')) }}">
<script>
    window.SC_FONT = ['Plus Jakarta Sans', 'ui-sans-serif', 'system-ui', 'sans-serif'];
    window.SC_COLORS = {
        // The brand. Existing views say `blue-*`; it now resolves to indigo.
        blue: {
            50: '#eef2ff', 100: '#e0e7ff', 200: '#c7d2fe', 300: '#a5b4fc', 400: '#818cf8',
            500: '#6366f1', 600: '#4f46e5', 700: '#4338ca', 800: '#3730a3', 900: '#312e81', 950: '#1e1b4b'
        },
        // Cool, slightly blue neutrals so greys sit well beside the brand.
        gray: {
            50: '#f7f8fc', 100: '#eceef8', 200: '#e1e4f1', 300: '#cdd2e6', 400: '#9aa2c2', 500: '#6b7394',
            600: '#4e5578', 700: '#383e5e', 800: '#232846', 900: '#151932', 950: '#0b0e20'
        }
    };
</script>
