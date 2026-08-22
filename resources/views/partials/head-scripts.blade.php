{{-- Theme detection MUST run immediately to prevent flash --}}
<script>
    if (localStorage.getItem('color-theme') === 'dark' ||
        (!localStorage.getItem('color-theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
</script>

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

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.store('sidebar', {
            open: false,
            isDark: localStorage.getItem('color-theme') === 'dark' ||
                (!localStorage.getItem('color-theme') && window.matchMedia(
                    '(prefers-color-scheme: dark)').matches),
            showLogoutModal: false,

            toggleTheme(theme) {
                this.isDark = theme === 'dark';
                if (this.isDark) {
                    document.documentElement.classList.add('dark');
                    localStorage.setItem('color-theme', 'dark');
                } else {
                    document.documentElement.classList.remove('dark');
                    localStorage.setItem('color-theme', 'light');
                }
            },

            toggleLogoutModal() {
                this.showLogoutModal = !this.showLogoutModal;
            }
        })
    })
</script>

<style>
    /* ═══ BASE STYLES ═══ */
    html,
    body {
        margin: 0;
        padding: 0;
        height: 100%;
        font-family: 'Inter', sans-serif;
        overflow-x: hidden;
        background-color: #f8fafc;
        /* ← Use longhand */
    }

    .dark html,
    .dark body {
        background-color: #050505;
        /* ← Use longhand */
    }

    /* Option: Use a pseudo-element overlay instead */
    body::after {
        content: '';
        position: fixed;
        inset: 0;
        background-color: #050505;
        opacity: 0;
        pointer-events: none;
        z-index: -1;
        transition: opacity 0.3s ease;
    }

    .dark body::after {
        opacity: 1;
    }


    /* ═══ SMOOTH THEME TRANSITIONS ═══ */
    *,
    *::before,
    *::after {
        transition-property: background-color, border-color, color, box-shadow, opacity, fill, stroke;
        transition-duration: 0.3s;
        transition-timing-function: ease;
    }


    /* Preserve element-specific transitions — these MUST use !important to win over * */
    .sidebar-transition {
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }

    .table-row {
        transition: all 0.2s ease !important;
    }

    #mobile-drawer {
        transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1) !important;
    }

    #mobile-drawer-backdrop {
        transition: opacity 0.3s ease !important;
    }

    ::-webkit-scrollbar {
        width: 3px;
    }

    ::-webkit-scrollbar-track {
        background: transparent;
    }

    ::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 10px;
    }

    .dark ::-webkit-scrollbar-thumb {
        background: #333;
    }

    /* ═══ GLASS PANEL - Light Mode ═══ */
    .glass-panel {
        background: #ffffff !important;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
    }

    .dark .glass-panel {
        background: #111111 !important;
        border: 1px solid #1e1e1e;
        box-shadow: 0 4px 24px rgba(0, 0, 0, 0.6);
    }

    /* ═══ GLASS CARD - Light Mode ═══ */
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

    /* ═══ TABLE ROW HOVER - Light Mode ═══ */
    .table-row:hover {
        background: #f8fafc;
    }

    .dark .table-row:hover {
        background: #1a1a1a;
    }

    /* ═══ DONUT CENTER ═══ */
    .donut-center {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        pointer-events: none;
    }

    [x-cloak] {
        display: none !important;
    }
</style>
