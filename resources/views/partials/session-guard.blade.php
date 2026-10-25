{{-- session-guard.blade.php --}}
{{-- Included for signed-in users on every page (via global-loading).

     Laravel signs a user out after `session.lifetime` minutes without a request. Left alone, the
     page just sits there and the next click ends on a "419 Page Expired" screen. This script
     keeps its own clock for that window: every same-origin fetch / XHR restarts it, and when it
     runs out the server is asked (GET /session/status) whether the session really ended. If
     it did, a notice explains why and the user is sent to the login page, which repeats the
     message as a toast. If it did not (another tab was active, or a "remember me" cookie signed
     them back in) the clock simply restarts. The check never extends a session that had ended. --}}
<style>
    .sg-backdrop {
        position: fixed;
        inset: 0;
        z-index: 2147483600;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        background: rgba(15, 23, 42, .6);
        backdrop-filter: blur(4px);
        font-family: system-ui, -apple-system, 'Segoe UI', sans-serif;
    }

    .sg-card {
        width: 100%;
        max-width: 24rem;
        padding: 1.75rem 1.5rem 1.5rem;
        border-radius: 1rem;
        text-align: center;
        background: #fff;
        color: #0f172a;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, .45);
    }

    .dark .sg-card {
        background: #111;
        color: #fff;
        border: 1px solid #262626;
    }

    .sg-ico {
        width: 3rem;
        height: 3rem;
        margin: 0 auto 1rem;
        border-radius: 999px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        background: rgba(245, 158, 11, .16);
        color: #d97706;
    }

    .sg-card h2 {
        margin: 0 0 .5rem;
        font-size: 1.15rem;
        font-weight: 800;
    }

    .sg-card p {
        margin: 0 0 1.25rem;
        font-size: .9rem;
        line-height: 1.5;
        opacity: .75;
    }

    .sg-btn {
        width: 100%;
        padding: .7rem 1rem;
        border: 0;
        border-radius: .65rem;
        background: #6366f1;
        color: #fff;
        font: 700 .9rem system-ui, -apple-system, 'Segoe UI', sans-serif;
        cursor: pointer;
    }

    .sg-btn:hover {
        background: #4f46e5;
    }
</style>

<script>
    (function () {
        if (window.__sessionGuard) return;
        window.__sessionGuard = true;

        var LIFETIME = {{ (int) config('session.lifetime') }} * 60 * 1000;
        var STATUS_URL = {!! json_encode(route('session.status', [], false), JSON_UNESCAPED_SLASHES) !!};
        var LOGIN_URL = {!! json_encode(route('login', [], false), JSON_UNESCAPED_SLASHES) !!};
        var MESSAGE = @json(\App\Support\SessionExpiry::EXPIRED_MESSAGE);
        var REDIRECT_AFTER = 5000;

        var deadline = Date.now() + LIFETIME;
        var checking = false;
        var ended = false;
        var nativeFetch = window.fetch;

        function sameOrigin(url) {
            try {
                return new URL(url, window.location.href).origin === window.location.origin;
            } catch (e) {
                return false;
            }
        }

        function touch() {
            if (!ended) deadline = Date.now() + LIFETIME;
        }

        function goToLogin() {
            window.location.replace(LOGIN_URL);
        }

        /* The session is gone: say so, then go to the login page. */
        function sessionEnded() {
            if (ended) return;
            ended = true;

            if (window.Toast) window.Toast.queue(MESSAGE, 'warning');

            var backdrop = document.createElement('div');
            backdrop.className = 'sg-backdrop';
            backdrop.setAttribute('role', 'alertdialog');
            backdrop.setAttribute('aria-modal', 'true');
            backdrop.setAttribute('aria-labelledby', 'sg-title');
            backdrop.innerHTML =
                '<div class="sg-card">' +
                '<div class="sg-ico" aria-hidden="true">&#9201;</div>' +
                '<h2 id="sg-title">Session expired</h2>' +
                '<p></p>' +
                '<button type="button" class="sg-btn">Sign in again</button>' +
                '</div>';
            backdrop.querySelector('p').textContent = MESSAGE + ' Taking you to the login page…';
            backdrop.querySelector('button').addEventListener('click', goToLogin);

            var attach = function () {
                document.body.appendChild(backdrop);
                backdrop.querySelector('button').focus();
            };
            if (document.body) attach(); else document.addEventListener('DOMContentLoaded', attach);

            setTimeout(goToLogin, REDIRECT_AFTER);
        }

        /* Ask the server whether we are still signed in. */
        async function check() {
            if (checking || ended) return;
            checking = true;

            try {
                var response = await nativeFetch(STATUS_URL, {
                    credentials: 'same-origin',
                    cache: 'no-store',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });

                if (response.status === 401) sessionEnded();
                else if (response.ok) touch();
                /* any other status (server hiccup): leave the clock, try again on the next tick */
            } catch (e) {
                /* offline: try again on the next tick */
            } finally {
                checking = false;
            }
        }

        function tick() {
            if (!ended && Date.now() >= deadline) check();
        }

        /* A 401 from our own server on a signed-in page means the session ended mid-use. */
        function observe(status, url) {
            if (ended || !sameOrigin(url)) return;
            if (status === 401) check();
            else if (status > 0 && status < 500 && url.indexOf(STATUS_URL) === -1) touch();
        }

        if (nativeFetch) {
            window.fetch = function () {
                var promise = nativeFetch.apply(this, arguments);
                promise.then(function (response) {
                    observe(response.status, response.url || '');
                }, function () { /* network error: not our concern */ });
                return promise;
            };
        }

        var nativeSend = XMLHttpRequest.prototype.send;
        XMLHttpRequest.prototype.send = function () {
            this.addEventListener('loadend', function () {
                observe(this.status, this.responseURL || '');
            });
            return nativeSend.apply(this, arguments);
        };

        setInterval(tick, 15000);
        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) tick();
        });
        window.addEventListener('focus', tick);
        window.addEventListener('pageshow', function (e) {
            if (e.persisted) tick();
        });
    })();
</script>
