<script>
    (function() {
        const dbTheme = '{{ $userTheme }}';
        localStorage.setItem('color-theme', dbTheme);
        document.documentElement.classList.toggle('dark', dbTheme === 'dark');
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
