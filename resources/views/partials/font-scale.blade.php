{{--
    Large-text setting (Settings → Font size). It is applied as CSS `zoom` on <html>, which shrinks the room the
    layout has to (device width ÷ zoom) but NOT the width the media queries see: at 1024 px wide and 125 % the page
    is laid out in 819 px yet Tailwind still says "lg", so desktop layouts get squeezed (search boxes collapse,
    buttons are clipped). Rather than let that happen, the zoom actually applied is the chosen one limited so the
    layout is never narrower than 90 % of the breakpoint range the screen is in (640 / 768 / 1024 / 1280 px), and
    never narrower than a 320 px phone. Wide screens get the full size; the limit only bites on narrow ones. (The
    tolerance is what lets a 1366 px laptop have about 1.19x instead of 1.07x.)

    Exposes:
      scFontZoom(size)       the zoom that would be applied for 'small' | 'medium' | 'large' | 'xlarge'
      scApplyFontZoom(size)  applies it (size defaults to the saved choice) and returns { requested, applied, limited }
    and re-applies on resize. A page can listen for the `sc:fontzoom` event to explain a limited size.
--}}
<script>
    (function() {
        var LEVELS = { small: 0.875, medium: 1, large: 1.125, xlarge: 1.25 };
        var RANGES = [1280, 1024, 768, 640];   // Tailwind's steps (xl, lg, md, sm)
        var TOLERANCE = 0.9;                   // a layout may be this fraction of its range's lower edge
        var PHONE_MIN = 320;                   // WCAG reflow: nothing may need a layout narrower than 320 CSS px

        window.scFontZoom = function(size) {
            var requested = LEVELS[size] || 1;

            if (requested <= 1) {
                return requested;               // smaller text only widens the layout
            }

            var w = window.innerWidth;
            var range = RANGES.find(function(b) { return w >= b; });
            var floor = range ? range * TOLERANCE : PHONE_MIN;

            return Math.max(1, Math.min(requested, w / floor));
        };

        window.scApplyFontZoom = function(size) {
            size = size || localStorage.getItem('font-size') || 'medium';

            var requested = LEVELS[size] || 1;
            var applied = Math.round(window.scFontZoom(size) * 1000) / 1000;

            document.documentElement.style.zoom = applied;
            // Viewport units (vw) are not scaled by zoom, so anything sized from 100vw must divide this out.
            document.documentElement.style.setProperty('--sc-zoom', applied);
            // Usable height = screen height ÷ zoom. Under ~780px the sidebar switches to tighter rows (html.sc-short).
            document.documentElement.classList.toggle('sc-short', window.innerHeight / applied < 780);
            window.dispatchEvent(new CustomEvent('sc:fontzoom', {
                detail: { size: size, requested: requested, applied: applied, limited: applied < requested - 0.001 }
            }));

            return { requested: requested, applied: applied, limited: applied < requested - 0.001 };
        };

        var timer;
        window.addEventListener('resize', function() {
            clearTimeout(timer);
            timer = setTimeout(function() { window.scApplyFontZoom(); }, 120);
        });
    })();
</script>
