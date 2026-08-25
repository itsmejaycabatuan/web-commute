{{-- commuter-head-scripts.blade.php --}}

{{-- Theme & Font Size detection MUST run immediately to prevent flash --}}
<script>
    (function() {
        // Theme
        const dbTheme = '{{ $userTheme }}';
        localStorage.setItem('color-theme', dbTheme);
        document.documentElement.classList.toggle('dark', dbTheme === 'dark');

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
