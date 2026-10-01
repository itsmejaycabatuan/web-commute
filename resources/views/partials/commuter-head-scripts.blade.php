{{-- commuter-head-scripts.blade.php --}}

{{-- Theme & Font Size detection MUST run immediately to prevent flash --}}
<script>
    (function() {
        // Theme
        const isGuest = {{ Auth::guest() ? 'true' : 'false' }};
        const storedTheme = localStorage.getItem('color-theme');
        // Signed-in users are authoritative from their profile.
        // Guests (no profile) keep whatever they last chose on this device,
        // falling back to their OS preference.
        const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        const dbTheme = '{{ Auth::check() ? ($userTheme ?? 'light') : 'light' }}';
        const theme = isGuest ? (storedTheme || (prefersDark ? 'dark' : 'light')) : dbTheme;

        localStorage.setItem('color-theme', theme);
        document.documentElement.classList.toggle('dark', theme === 'dark');

        // Font Size (using zoom for proportional scaling)
        const fontZoomLevels = {
            small: 0.875,
            medium: 1,
            large: 1.125,
            xlarge: 1.25
        };
        const intToLabel = {
            10: 'small',
            11: 'medium',
            12: 'large',
            13: 'xlarge'
        };

        const dbInt = parseInt('{{ $userFontSize ?? 11 }}') || 11;
        const currentSize = intToLabel[dbInt] || localStorage.getItem('font-size') || 'medium';
        localStorage.setItem('font-size', currentSize);

        document.documentElement.style.zoom = fontZoomLevels[currentSize] || 1;
    })();
</script>
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        darkMode: 'class',
        theme: {
            extend: {
                fontFamily: {
                    sans: ['Inter', 'sans-serif']
                }
            }
        }
    }
</script>
