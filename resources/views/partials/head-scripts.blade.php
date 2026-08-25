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

    function applyFontSize(size, skipSave) {
        const sizes = {
            small: {
                base: 14,
                label: 12,
                small: 10,
                tiny: 8,
                mini: 7,
                db: 10
            },
            medium: {
                base: 16,
                label: 13,
                small: 11,
                tiny: 9,
                mini: 8,
                db: 11
            },
            large: {
                base: 18,
                label: 15,
                small: 12,
                tiny: 10,
                mini: 9,
                db: 12
            },
            xlarge: {
                base: 20,
                label: 17,
                small: 14,
                tiny: 11,
                mini: 10,
                db: 13
            }
        };

        const s = sizes[size] || sizes.medium;
        const root = document.documentElement;
        root.style.setProperty('--fs-base', s.base + 'px');
        root.style.setProperty('--fs-label', s.label + 'px');
        root.style.setProperty('--fs-small', s.small + 'px');
        root.style.setProperty('--fs-tiny', s.tiny + 'px');
        root.style.setProperty('--fs-mini', s.mini + 'px');

        if (!skipSave) {
            localStorage.setItem('font-size', size);
        }

        return s.db; // Return the integer value for API calls
    }

    function setFontSize(size) {
        const dbValue = applyFontSize(size, false);

        fetch('{{ route('settings.update.fontsize') }}', {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            },
            body: JSON.stringify({
                font_size: dbValue
            })
        }).catch(() => {
            const reverted = localStorage.getItem('font-size') || 'medium';
            applyFontSize(reverted, false);
        });
    }
</script>

<script>
    tailwind.config = {
        darkMode: 'class',
        theme: {
            extend: {
                fontFamily: {
                    sans: ['Inter', 'sans-serif']
                },
                fontSize: {
                    'base': 'var(--fs-base, 16px)',
                    'label': 'var(--fs-label, 13px)',
                    'small': 'var(--fs-small, 11px)',
                    'tiny': 'var(--fs-tiny, 9px)',
                    'mini': 'var(--fs-mini, 8px)',
                }
            }
        }
    }
</script>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.store('sidebar', {
            open: false,
            isDark: false,
            fontSize: (function() {
                const intToLabel = {
                    10: 'small',
                    11: 'medium',
                    12: 'large',
                    13: 'xlarge'
                };
                const dbInt = parseInt('{{ $userFontSize ?? 11 }}') || 11;
                return intToLabel[dbInt] || localStorage.getItem('font-size') || 'medium';
            })(),
            showLogoutModal: false,
            syncing: false,

            init() {
                this.isDark = document.documentElement.classList.contains('dark');
            },

            toggleTheme(theme) {
                this.isDark = theme === 'dark';
                document.documentElement.classList.add('theme-switching');

                if (this.isDark) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }

                localStorage.setItem('color-theme', theme);

                setTimeout(() => {
                    document.documentElement.classList.remove('theme-switching');
                }, 400);

                this.syncing = true;
                fetch('{{ route('settings.update.theme') }}', {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                ?.content
                        },
                        body: JSON.stringify({
                            theme
                        })
                    })
                    .catch(() => {
                        const reverted = this.isDark ? 'light' : 'dark';
                        this.isDark = !this.isDark;
                        document.documentElement.classList.toggle('dark', this.isDark);
                        localStorage.setItem('color-theme', reverted);
                    })
                    .finally(() => {
                        this.syncing = false;
                    });
            },

            setFontSize(size) {
                this.fontSize = size;
                const dbValue = applyFontSize(size, false);
                localStorage.setItem('font-size-db', dbValue);

                this.syncing = true;
                fetch('{{ route('settings.update.fontsize') }}', {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                ?.content
                        },
                        body: JSON.stringify({
                            font_size: dbValue
                        })
                    })
                    .catch(() => {
                        const reverted = this.fontSize === size ? 'medium' : this.fontSize;
                        this.fontSize = reverted;
                        applyFontSize(reverted, false);
                    })
                    .finally(() => {
                        this.syncing = false;
                    });
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
        font-size: var(--fs-base, 16px);
        overflow-x: hidden;
        background-color: #f8fafc;
    }

    .dark html,
    .dark body {
        background-color: #050505;
    }

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

    /* ═══ THEME SWITCH TRANSITION ═══ */
    .theme-switching,
    .theme-switching *,
    .theme-switching *::before,
    .theme-switching *::after {
        transition-property: background-color, border-color, color, box-shadow, opacity, fill, stroke, font-size !important;
        transition-duration: 0.3s !important;
        transition-timing-function: ease !important;
    }

    /* ═══ ELEMENT TRANSITIONS ═══ */
    .sidebar-transition {
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .table-row {
        transition: all 0.2s ease;
    }

    .table-row:hover {
        background: #f8fafc;
    }

    .dark .table-row:hover {
        background: #1a1a1a;
    }

    #mobile-drawer {
        transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }

    #mobile-drawer-backdrop {
        transition: opacity 0.3s ease;
    }

    /* ═══ SCROLLBAR ═══ */
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

    /* ═══ GLASS PANEL ═══ */
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

    /* ═══ GLASS CARD ═══ */
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

    /* ═══════════════════════════════════════════════════════════════
       THEME-AWARE UTILITY CLASSES
    ═══════════════════════════════════════════════════════════════ */

    /* ── Inner card backgrounds ── */
    .inner-card {
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
    }

    .dark .inner-card {
        background: #111;
        border: 1px solid #1e1e1e;
    }

    .inner-card-hover:hover {
        background: #e2e8f0;
        border-color: #cbd5e1;
    }

    .dark .inner-card-hover:hover {
        background: #1a1a1a;
        border-color: #333;
    }

    /* ── Icon boxes ── */
    .icon-box {
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
    }

    .dark .icon-box {
        background: #111;
        border: 1px solid #1e1e1e;
    }

    /* ── Inactive badge ── */
    .badge-inactive {
        background: #f1f5f9;
        color: #94a3b8;
        border: 1px solid #e2e8f0;
    }

    .dark .badge-inactive {
        background: #111;
        color: #444;
        border: 1px solid #1e1e1e;
    }

    /* ── Borders ── */
    .border-subtle {
        border-color: #e2e8f0;
    }

    .dark .border-subtle {
        border-color: #1e1e1e;
    }

    .border-faint {
        border-color: #e2e8f0;
    }

    .dark .border-faint {
        border-color: #1a1a1a;
    }

    /* ── Dividers ── */
    .divide-subtle> :not([hidden])~ :not([hidden]) {
        border-color: #e2e8f0;
    }

    .dark .divide-subtle> :not([hidden])~ :not([hidden]) {
        border-color: #1a1a1a;
    }

    /* ── Text colors ── */
    .text-subtle {
        color: #334155;
    }

    .dark .text-subtle {
        color: #ccc;
    }

    .text-muted {
        color: #64748b;
    }

    .dark .text-muted {
        color: #888;
    }

    .text-faint {
        color: #64748b;
    }

    .dark .text-faint {
        color: #555;
    }

    .text-dim {
        color: #94a3b8;
    }

    .dark .text-dim {
        color: #444;
    }

    .text-ghost {
        color: #cbd5e1;
    }

    .dark .text-ghost {
        color: #333;
    }

    .text-invisible {
        color: #e2e8f0;
    }

    .dark .text-invisible {
        color: #222;
    }

    /* ── Group hover text ── */
    .group:hover .group-hover\:text-bright {
        color: #0f172a;
    }

    .dark .group:hover .group-hover\:text-bright {
        color: #ffffff;
    }

    .group:hover .group-hover\:text-faint {
        color: #475569;
    }

    .dark .group:hover .group-hover\:text-faint {
        color: #555;
    }

    /* ═══ FONT SIZE BUTTON ═══ */
    .font-size-btn {
        position: relative;
        line-height: 1;
        cursor: pointer;
    }

    .font-size-btn.active {
        background: #ffffff;
        color: #3b82f6;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .dark .font-size-btn.active {
        background: #1a1a1a;
        color: #60a5fa;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.4);
    }

    /* ═══ ACCENT PRESERVATION ═══ */
    .text-blue-400 {
        color: #3b82f6 !important;
    }

    .text-emerald-400 {
        color: #10b981 !important;
    }

    .text-amber-400 {
        color: #f59e0b !important;
    }

    .text-red-400 {
        color: #f87171 !important;
    }

    .text-purple-400 {
        color: #a78bfa !important;
    }

    .text-rose-400 {
        color: #fb7185 !important;
    }

    .text-orange-400 {
        color: #fb923c !important;
    }

    .text-yellow-400 {
        color: #facc15 !important;
    }

    /* ═══ MISC ═══ */
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
