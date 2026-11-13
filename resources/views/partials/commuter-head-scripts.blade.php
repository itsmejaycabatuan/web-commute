@include('partials.theme')
@include('partials.font-scale')

{{-- commuter-head-scripts.blade.php --}}

{{-- Theme & Font Size detection MUST run immediately to prevent flash --}}
<script>
    (function() {
        // Theme
        const isGuest = {{ Auth::guest() ? 'true' : 'false' }};
        // Signed-in users are authoritative from their profile.
        // Guests (no profile) keep whatever they last chose as a guest on this device
        // (own 'guest-theme' key, so a signed-in theme never leaks in), falling back to their OS preference.
        let storedTheme = null;
        try { storedTheme = localStorage.getItem('guest-theme'); } catch (e) {}
        const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        const dbTheme = '{{ Auth::check() ? ($userTheme ?? 'light') : 'light' }}';
        const theme = isGuest ? (storedTheme || (prefersDark ? 'dark' : 'light')) : dbTheme;

        if (!isGuest) { try { localStorage.setItem('color-theme', theme); } catch (e) {} }
        document.documentElement.classList.toggle('dark', theme === 'dark');

        // Font size: applied as a (width-limited) zoom, see partials/font-scale
        const intToLabel = {
            10: 'small',
            11: 'medium',
            12: 'large',
            13: 'xlarge'
        };

        const dbInt = parseInt('{{ $userFontSize ?? 11 }}') || 11;
        const currentSize = intToLabel[dbInt] || localStorage.getItem('font-size') || 'medium';
        localStorage.setItem('font-size', currentSize);

        // The zoom actually applied is limited on narrow screens (see partials/font-scale).
        scApplyFontZoom(currentSize);
    })();
</script>
<script src="https://cdn.tailwindcss.com"></script>
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

{{-- Short/landscape viewport support (phones & small tablets held sideways) --}}
@include('partials.landscape-styles')

{{-- Colour-coded elements that stay readable in light and dark mode --}}
@include('partials.accessible-colors')

{{-- Global loading indicators (form submits & AJAX requests) --}}
@include('partials.global-loading')
