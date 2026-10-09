{{-- global-loading.blade.php --}}
{{-- Reusable app-wide loading indicator.
     - Shows a full-screen overlay for form submissions (submit buttons).
     - Shows a slim top progress bar for any background fetch/XHR request.
     - Adds an inline spinner + disables the button that triggered the request.
     Public API: window.AppLoading.show(text) / .hide() / .start() / .done() --}}
<style>
    /* ── Top progress bar (background AJAX) ── */
    #app-loading-bar {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        z-index: 2147483647;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.2s ease;
    }

    #app-loading-bar.active {
        opacity: 1;
    }

    #app-loading-bar>.app-loading-bar-fill {
        height: 100%;
        width: 35%;
        border-radius: 0 999px 999px 0;
        background: linear-gradient(90deg, #3b82f6, #60a5fa, #3b82f6);
        animation: app-bar-slide 1.1s ease-in-out infinite;
    }

    @keyframes app-bar-slide {
        0% {
            transform: translateX(-110%);
        }

        100% {
            transform: translateX(330%);
        }
    }

    /* ── Full-screen overlay (form submissions) ── */
    #app-loading-overlay {
        position: fixed;
        inset: 0;
        z-index: 2147483646;
        display: flex;
        align-items: center;
        justify-content: center;
        visibility: hidden;
        opacity: 0;
        background: rgba(15, 23, 42, 0.45);
        -webkit-backdrop-filter: blur(4px);
        backdrop-filter: blur(4px);
        transition: opacity 0.18s ease, visibility 0.18s ease;
    }

    #app-loading-overlay.active {
        visibility: visible;
        opacity: 1;
    }

    .dark #app-loading-overlay {
        background: rgba(0, 0, 0, 0.6);
    }

    .app-loading-card {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.85rem;
        padding: 1.75rem 2.25rem;
        border-radius: 1.25rem;
        background: rgba(255, 255, 255, 0.97);
        border: 1px solid #e2e8f0;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
    }

    .dark .app-loading-card {
        background: rgba(17, 17, 17, 0.97);
        border-color: #222222;
    }

    .app-loading-spinner {
        width: 2.25rem;
        height: 2.25rem;
        border-radius: 50%;
        border: 3px solid rgba(59, 130, 246, 0.2);
        border-top-color: #3b82f6;
        animation: app-spin 0.7s linear infinite;
    }

    .app-loading-text {
        font-size: 0.8rem;
        font-weight: 600;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #475569;
    }

    .dark .app-loading-text {
        color: #aaaaaa;
    }

    /* ── Inline button spinner ── */
    .app-btn-spinner {
        display: inline-block;
        width: 0.9em;
        height: 0.9em;
        margin-right: 0.5em;
        vertical-align: -0.1em;
        border: 2px solid currentColor;
        border-right-color: transparent;
        border-radius: 50%;
        animation: app-spin 0.6s linear infinite;
        opacity: 0.9;
    }

    .app-btn-loading {
        pointer-events: none !important;
        opacity: 0.75;
    }

    @keyframes app-spin {
        to {
            transform: rotate(360deg);
        }
    }

    @media (prefers-reduced-motion: reduce) {

        .app-loading-spinner,
        .app-btn-spinner,
        #app-loading-bar>.app-loading-bar-fill {
            animation-duration: 1.4s;
        }
    }
</style>

<script>
    (function() {
        'use strict';

        // Polling / background endpoints that should NEVER flash the indicator.
        var SILENT_PATTERNS = [
            /\/track\/vehicles\/active/,
            /\/track\/vehicle\/broadcast/,
            /\/api\/markers/,
            /settings\/theme/,
            /settings\/font-?size/
        ];

        var state = {
            requests: 0,
            buttons: new Map(), // button -> pending request count
            recentButton: null,
            recentAt: 0,
            barTimer: null,
            overlayTimer: null
        };

        // ── DOM injection ──────────────────────────────────────────────
        function injectMarkup() {
            if (!document.body) return;

            if (!document.getElementById('app-loading-bar')) {
                var bar = document.createElement('div');
                bar.id = 'app-loading-bar';
                bar.innerHTML = '<div class="app-loading-bar-fill"></div>';
                document.body.appendChild(bar);
            }

            if (!document.getElementById('app-loading-overlay')) {
                var overlay = document.createElement('div');
                overlay.id = 'app-loading-overlay';
                overlay.setAttribute('role', 'status');
                overlay.setAttribute('aria-live', 'polite');
                overlay.innerHTML =
                    '<div class="app-loading-card">' +
                    '<span class="app-loading-spinner" aria-hidden="true"></span>' +
                    '<span class="app-loading-text" id="app-loading-text">Processing…</span>' +
                    '</div>';
                document.body.appendChild(overlay);
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', injectMarkup);
        } else {
            injectMarkup();
        }

        // ── Top progress bar ───────────────────────────────────────────
        function showBar() {
            state.requests++;
            if (state.requests === 1) {
                clearTimeout(state.barTimer);
                // Small delay so very fast requests don't flicker the bar.
                state.barTimer = setTimeout(function() {
                    var bar = document.getElementById('app-loading-bar');
                    if (bar && state.requests > 0) bar.classList.add('active');
                }, 180);
            }
        }

        function hideBar() {
            state.requests = Math.max(0, state.requests - 1);
            if (state.requests === 0) {
                clearTimeout(state.barTimer);
                var bar = document.getElementById('app-loading-bar');
                if (bar) bar.classList.remove('active');
            }
        }

        // ── Full-screen overlay ────────────────────────────────────────
        function showOverlay(text, delay) {
            var overlay = document.getElementById('app-loading-overlay');
            if (!overlay) return;

            if (text) {
                var label = document.getElementById('app-loading-text');
                if (label) label.textContent = text;
            }

            clearTimeout(state.overlayTimer);
            if (delay) {
                state.overlayTimer = setTimeout(function() {
                    overlay.classList.add('active');
                }, delay);
            } else {
                overlay.classList.add('active');
            }
        }

        function hideOverlay() {
            clearTimeout(state.overlayTimer);
            var overlay = document.getElementById('app-loading-overlay');
            if (overlay) overlay.classList.remove('active');
        }

        // ── Inline button spinner ──────────────────────────────────────
        function setButtonLoading(btn, loading) {
            if (!btn || btn.nodeType !== 1) return;

            if (loading) {
                if (btn.dataset.appLoading === '1') return;
                btn.dataset.appLoading = '1';
                btn.classList.add('app-btn-loading');
                btn.setAttribute('aria-busy', 'true');

                var spinner = document.createElement('span');
                spinner.className = 'app-btn-spinner';
                spinner.setAttribute('aria-hidden', 'true');
                btn.insertBefore(spinner, btn.firstChild);
            } else {
                if (btn.dataset.appLoading !== '1') return;
                delete btn.dataset.appLoading;
                btn.classList.remove('app-btn-loading');
                btn.removeAttribute('aria-busy');

                var existing = btn.querySelector(':scope > .app-btn-spinner');
                if (existing) existing.remove();
            }
        }

        function resetAll() {
            clearTimeout(state.barTimer);
            clearTimeout(state.overlayTimer);
            state.requests = 0;

            var bar = document.getElementById('app-loading-bar');
            if (bar) bar.classList.remove('active');
            hideOverlay();

            state.buttons.forEach(function(count, btn) {
                setButtonLoading(btn, false);
            });
            state.buttons.clear();
            state.recentButton = null;
        }

        // ── Bind a clicked button to the request it triggers ───────────
        function attachRecentButton() {
            var btn = state.recentButton;
            state.recentButton = null;

            if (!btn || Date.now() - state.recentAt > 1500) return null;
            if (!document.body || !document.body.contains(btn)) return null;

            state.buttons.set(btn, (state.buttons.get(btn) || 0) + 1);
            setButtonLoading(btn, true);
            return btn;
        }

        function detachButton(btn) {
            if (!btn) return;
            var count = (state.buttons.get(btn) || 0) - 1;
            if (count <= 0) {
                state.buttons.delete(btn);
                setButtonLoading(btn, false);
            } else {
                state.buttons.set(btn, count);
            }
        }

        function isSilent(input) {
            var url = '';
            try {
                if (typeof input === 'string') url = input;
                else if (input && typeof input.url === 'string') url = input.url;
            } catch (e) {
                return false;
            }
            return SILENT_PATTERNS.some(function(rx) {
                return rx.test(url);
            });
        }

        // ── Track the last clicked button ──────────────────────────────
        document.addEventListener('click', function(e) {
            var btn = e.target && e.target.closest ?
                e.target.closest('button, input[type="submit"], input[type="button"], [role="button"]') :
                null;
            if (!btn) return;
            state.recentButton = btn;
            state.recentAt = Date.now();
        }, true);

        // ── Hook fetch ─────────────────────────────────────────────────
        if (typeof window.fetch === 'function') {
            var originalFetch = window.fetch;
            window.fetch = function(input, init) {
                if (isSilent(input)) return originalFetch.apply(this, arguments);

                var btn = attachRecentButton();
                showBar();

                var result;
                try {
                    result = originalFetch.apply(this, arguments);
                } catch (err) {
                    hideBar();
                    detachButton(btn);
                    throw err;
                }

                return Promise.resolve(result).finally(function() {
                    hideBar();
                    detachButton(btn);
                });
            };
        }

        // ── Hook XMLHttpRequest (axios, etc.) ──────────────────────────
        if (window.XMLHttpRequest && XMLHttpRequest.prototype) {
            var originalOpen = XMLHttpRequest.prototype.open;
            var originalSend = XMLHttpRequest.prototype.send;

            XMLHttpRequest.prototype.open = function() {
                this.__appTracked = !isSilent(arguments[1]);
                return originalOpen.apply(this, arguments);
            };

            XMLHttpRequest.prototype.send = function() {
                if (this.__appTracked) {
                    var btn = attachRecentButton();
                    showBar();
                    this.addEventListener('loadend', function() {
                        hideBar();
                        detachButton(btn);
                    });
                }
                return originalSend.apply(this, arguments);
            };
        }

        // ── Form submissions ───────────────────────────────────────────
        function beginFormProcessing(form, btn) {
            if (btn) setButtonLoading(btn, true);

            var method = (form.getAttribute('method') || 'GET').toUpperCase();
            var overlayPref = form.dataset.loadingOverlay; // "true" | "false"
            var shouldOverlay = overlayPref === 'true' ||
                (overlayPref !== 'false' && method !== 'GET');

            if (shouldOverlay) {
                showOverlay(form.dataset.loadingText || 'Processing…');
            }
        }

        document.addEventListener('submit', function(e) {
            var form = e.target;
            if (!form || form.tagName !== 'FORM') return;

            // Client-side / AJAX handlers (Alpine @submit.prevent, validation)
            // already cancelled it — don't flash the blocking overlay.
            if (e.defaultPrevented) return;

            var btn = e.submitter ||
                form.querySelector('button[type="submit"], input[type="submit"]');

            beginFormProcessing(form, btn);

            // If another handler cancels the native submission later, tear the
            // overlay back down. The fetch/XHR hooks keep the inline spinner
            // alive for the actual request.
            setTimeout(function() {
                if (e.defaultPrevented) {
                    hideOverlay();
                    if (btn && !state.buttons.has(btn)) setButtonLoading(btn, false);
                }
            }, 0);
        });

        // Forms submitted programmatically via form.submit() bypass the submit
        // event entirely (e.g. driver status toggle, custom AJAX flows).
        if (window.HTMLFormElement && HTMLFormElement.prototype.submit) {
            var originalFormSubmit = HTMLFormElement.prototype.submit;
            HTMLFormElement.prototype.submit = function() {
                try {
                    var btn = null;
                    if (state.recentButton &&
                        Date.now() - state.recentAt < 1500 &&
                        document.body && document.body.contains(state.recentButton)) {
                        btn = state.recentButton;
                    }
                    state.recentButton = null;
                    beginFormProcessing(this, btn);
                } catch (err) {
                    // Never let the indicator break a real submission.
                }
                return originalFormSubmit.apply(this, arguments);
            };
        }

        // Reset indicators when returning via browser back/forward cache.
        window.addEventListener('pageshow', function(e) {
            if (e.persisted) resetAll();
        });

        // ── Public API ─────────────────────────────────────────────────
        window.AppLoading = {
            show: function(text) {
                showOverlay(text || 'Processing…');
            },
            hide: hideOverlay,
            start: showBar,
            done: hideBar,
            button: setButtonLoading,
            reset: resetAll
        };
    })();
</script>
