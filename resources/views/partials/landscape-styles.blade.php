{{-- ═══════════════════════════════════════════════════════════════
     SHORT / LANDSCAPE VIEWPORT SUPPORT (shared by every console view)
     ═══════════════════════════════════════════════════════════════
     Without this, a phone held sideways renders the portrait layout
     squashed into ~400px of height: oversized chrome eats the screen and
     the content keeps the "compressed desktop" look.

     `is-landscape` is toggled on <html> below so the CSS never has to
     guess, and everything is scoped to viewports that are genuinely short
     (<=600px tall) AND narrower than the xl breakpoint, so desktop windows
     are never affected.
--}}
<script>
    (function () {
        var mq = window.matchMedia(
            '(orientation: landscape) and (max-height: 600px) and (max-width: 1279px)'
        );

        var apply = function () {
            var root = document.documentElement;
            root.classList.toggle('is-landscape', mq.matches);
            root.classList.toggle('is-short', mq.matches);
        };

        apply();

        if (mq.addEventListener) {
            mq.addEventListener('change', apply);
        } else if (mq.addListener) {
            mq.addListener(apply); /* older Safari */
        }
    })();
</script>

<style>
    /* ═══ MAIN CONTENT: TIGHTEN THE VERTICAL RHYTHM ═══ */
    html.is-landscape main.sidebar-transition {
        padding-top: 0.75rem !important;
        padding-bottom: 4.25rem !important;
        margin-bottom: 0 !important;
    }

    /* ═══ TWO-COLUMN SPLIT (opt-in, e.g. dashboard body grids) ═══
       Portrait stacks every block in one column, which wastes the extra
       width available sideways. Dashboards add `.landscape-split` to their
       `xl:grid-cols-12` wrapper and get a 50/50 layout in landscape. */
    html.is-landscape .landscape-split {
        display: grid !important;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) !important;
        gap: 0.75rem !important;
        align-items: start;
    }

    /* Sections that are decoration-only in landscape can opt out. */
    html.is-landscape .landscape-hide {
        display: none !important;
    }

    /* ═══ MOBILE BOTTOM NAV: COMPACT STRIP ═══ */
    html.is-landscape #mobile-bottom-bar {
        padding-left: env(safe-area-inset-left);
        padding-right: env(safe-area-inset-right);
    }

    html.is-landscape #mobile-bottom-bar .bar-inner {
        padding-top: 0.25rem;
        padding-bottom: 0.25rem;
    }

    html.is-landscape #mobile-bottom-bar .bar-link {
        padding-top: 0.15rem;
        padding-bottom: 0.15rem;
    }

    html.is-landscape #mobile-bottom-bar .bar-link-icon {
        font-size: 14px;
    }

    html.is-landscape #mobile-bottom-bar .bar-link-label {
        font-size: 6px;
    }

    /* The raised primary button loses its "lift" sideways: there is no room. */
    html.is-landscape #mobile-bottom-bar .bar-primary {
        margin-top: 0;
        padding-left: 0.75rem;
        padding-right: 0.75rem;
    }

    html.is-landscape #mobile-bottom-bar .bar-primary-icon {
        width: 38px;
        height: 38px;
        border-radius: 13px;
        margin-bottom: 0;
    }

    /* ═══ MOBILE DRAWER: USE THE WIDTH, SHRINK THE ROWS ═══ */
    html.is-landscape #mobile-drawer {
        width: min(20rem, 78vw);
    }

    html.is-landscape #mobile-drawer .drawer-nav-link {
        padding-top: 0.5rem;
        padding-bottom: 0.5rem;
    }

    /* ═══ FLASH MESSAGES: NARROW BANNER INSTEAD OF A FULL-WIDTH BLOCK ═══ */
    html.is-landscape .flash-stack {
        top: 3.25rem;
        left: auto;
        right: 0.75rem;
        max-width: min(24rem, 55vw);
        align-items: stretch;
    }

    /* ═══ SCROLL CONTAINERS: NEVER SQUEEZE HORIZONTALLY ═══ */
    html.is-landscape .table-scroll-x {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
</style>
