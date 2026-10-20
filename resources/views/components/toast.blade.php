{{-- ═══════════════════════════════════════════════════════════════
     TOAST + AJAX FORMS
     ═══════════════════════════════════════════════════════════════
     One notification style for the whole app - the one the vehicle page uses.
     It replaces the old flash.blade.php banner and every hand-written
     "session('success')" box.

     It does three things:

       1. Renders the session flash (success / error / warning / message) and a
          summary of any validation errors as toasts.
       2. Exposes window.Toast.show(message, type) for scripts.
       3. Drives every <form data-ajax> (see window.AppForms below): the form is
          posted with fetch, so a modal stays open on a validation error, the
          messages appear under their own inputs, and on success the modal
          closes and the page refreshes with a toast.

     The component only emits <style> and <script>, so it is safe to include
     from <head> (partials/global-loading does, which covers every page).
--}}
@php
    $toastFlash = [];

    foreach (['success' => 'success', 'error' => 'error', 'warning' => 'warning', 'message' => 'info'] as $key => $type) {
        if (session()->has($key) && is_string(session($key)) && session($key) !== '') {
            $toastFlash[] = ['type' => $type, 'message' => session($key)];
        }
    }

    if (isset($errors) && $errors->any() && ! $errors->has('credentials')) {
        $toastFlash[] = [
            'type' => 'error',
            'message' => $errors->count() === 1
                ? $errors->first()
                : 'Please correct the highlighted fields and try again.',
        ];
    }
@endphp

<style>
    .app-toast-stack {
        position: fixed;
        right: 1.5rem;
        bottom: 1.5rem;
        z-index: 2147483000;
        display: flex;
        flex-direction: column;
        gap: .5rem;
        align-items: flex-end;
        pointer-events: none;
        max-width: calc(100vw - 2rem);
    }

    @media (max-width: 767px) {
        .app-toast-stack {
            right: 1rem;
            left: 1rem;
            /* clear the mobile bottom navigation bar */
            bottom: 5.25rem;
            align-items: stretch;
        }
    }

    .app-toast {
        pointer-events: auto;
        display: flex;
        align-items: center;
        gap: .75rem;
        padding: .875rem 1.25rem;
        border-radius: .75rem;
        border: 1px solid;
        box-shadow: 0 20px 40px -12px rgba(0, 0, 0, .35);
        font: 700 11px/1.35 system-ui, -apple-system, 'Segoe UI', sans-serif;
        max-width: 26rem;
        animation: app-toast-in .3s ease-out both;
        transition: opacity .25s ease, transform .25s ease;
    }

    .app-toast.is-leaving {
        opacity: 0;
        transform: translateY(.75rem) scale(.96);
    }

    .app-toast__icon {
        width: 1.5rem;
        height: 1.5rem;
        border-radius: .375rem;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 10px;
    }

    .app-toast__close {
        margin-left: .25rem;
        opacity: .5;
        background: none;
        border: 0;
        color: inherit;
        cursor: pointer;
        font-size: 11px;
    }

    .app-toast__close:hover {
        opacity: 1;
    }

    .app-toast--success {
        background: #ecfdf5;
        border-color: #a7f3d0;
        color: #047857;
    }

    .app-toast--success .app-toast__icon {
        background: rgba(16, 185, 129, .15);
    }

    .app-toast--error {
        background: #fff1f2;
        border-color: #fecdd3;
        color: #be123c;
    }

    .app-toast--error .app-toast__icon {
        background: rgba(244, 63, 94, .15);
    }

    .app-toast--warning {
        background: #fffbeb;
        border-color: #fde68a;
        color: #b45309;
    }

    .app-toast--warning .app-toast__icon {
        background: rgba(245, 158, 11, .15);
    }

    .app-toast--info {
        background: #eff6ff;
        border-color: #bfdbfe;
        color: #1d4ed8;
    }

    .app-toast--info .app-toast__icon {
        background: rgba(59, 130, 246, .15);
    }

    .dark .app-toast--success {
        background: rgba(16, 185, 129, .12);
        border-color: rgba(16, 185, 129, .25);
        color: #6ee7b7;
    }

    .dark .app-toast--error {
        background: rgba(244, 63, 94, .12);
        border-color: rgba(244, 63, 94, .25);
        color: #fda4af;
    }

    .dark .app-toast--warning {
        background: rgba(245, 158, 11, .12);
        border-color: rgba(245, 158, 11, .25);
        color: #fcd34d;
    }

    .dark .app-toast--info {
        background: rgba(59, 130, 246, .12);
        border-color: rgba(59, 130, 246, .25);
        color: #93c5fd;
    }

    /* The login / register screens are always dark. */
    .app-toast-stack.is-dark .app-toast--success {
        background: rgba(16, 185, 129, .16);
        border-color: rgba(16, 185, 129, .3);
        color: #6ee7b7;
    }

    .app-toast-stack.is-dark .app-toast--error {
        background: rgba(244, 63, 94, .16);
        border-color: rgba(244, 63, 94, .3);
        color: #fda4af;
    }

    @keyframes app-toast-in {
        from {
            opacity: 0;
            transform: translateY(.75rem) scale(.96);
        }

        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .app-toast {
            animation: none;
            transition: none;
        }
    }

    /* ── Field-level validation (AppForms) ── */
    .app-field-error {
        margin: .3rem 0 0;
        font: 600 10px/1.35 system-ui, -apple-system, 'Segoe UI', sans-serif;
        color: #e11d48;
    }

    .dark .app-field-error {
        color: #fb7185;
    }

    .app-field-invalid {
        border-color: #f43f5e !important;
    }

    .app-form-errors {
        margin: 0 0 .75rem;
        padding: .625rem .75rem;
        border-radius: .625rem;
        border: 1px solid rgba(244, 63, 94, .3);
        background: rgba(244, 63, 94, .08);
        color: #e11d48;
        font: 600 10px/1.4 system-ui, -apple-system, 'Segoe UI', sans-serif;
    }

    .dark .app-form-errors {
        color: #fb7185;
    }

    form[data-ajax].is-submitting {
        cursor: progress;
    }
</style>

<script type="application/json" id="app-flash-data">@json($toastFlash, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)</script>

<script>
    (function () {
        if (window.Toast) return;

        var ICONS = {
            success: 'fa-solid fa-check',
            error: 'fa-solid fa-xmark',
            warning: 'fa-solid fa-exclamation',
            info: 'fa-solid fa-info'
        };
        var STORE_KEY = 'app-pending-toast';
        var stack = null;

        function ensureStack() {
            if (stack && document.body && document.body.contains(stack)) return stack;
            if (!document.body) return null;

            stack = document.createElement('div');
            stack.className = 'app-toast-stack';
            stack.setAttribute('aria-live', 'polite');
            // Pages that never load the theme script (login, register) are dark.
            if (!document.documentElement.classList.contains('dark') &&
                document.body.className.indexOf('login-bg') !== -1) {
                stack.classList.add('is-dark');
            }
            document.body.appendChild(stack);

            return stack;
        }

        function dismiss(el) {
            if (!el || el.__gone) return;
            el.__gone = true;
            el.classList.add('is-leaving');
            setTimeout(function () {
                el.remove();
            }, 260);
        }

        function show(message, type, duration) {
            type = ICONS[type] ? type : 'info';
            var host = ensureStack();
            if (!host || !message) return null;

            var el = document.createElement('div');
            el.className = 'app-toast app-toast--' + type;
            el.setAttribute('role', type === 'error' ? 'alert' : 'status');

            var icon = document.createElement('span');
            icon.className = 'app-toast__icon';
            icon.innerHTML = '<i class="' + ICONS[type] + '"></i>';

            var text = document.createElement('span');
            text.textContent = message;

            var close = document.createElement('button');
            close.type = 'button';
            close.className = 'app-toast__close';
            close.setAttribute('aria-label', 'Dismiss notification');
            close.innerHTML = '<i class="fa-solid fa-xmark"></i>';
            close.addEventListener('click', function () {
                dismiss(el);
            });

            el.appendChild(icon);
            el.appendChild(text);
            el.appendChild(close);
            host.appendChild(el);

            setTimeout(function () {
                dismiss(el);
            }, duration || (type === 'error' ? 6000 : 4000));

            return el;
        }

        window.Toast = {
            show: show,
            success: function (m) { return show(m, 'success'); },
            error: function (m) { return show(m, 'error'); },
            warning: function (m) { return show(m, 'warning'); },
            info: function (m) { return show(m, 'info'); },
            /** Show a toast on the next page load (use right before a reload / redirect). */
            queue: function (m, type) { queue(m, type || 'success'); }
        };

        /* A toast queued before a page refresh / redirect is shown on the next page. */
        function queue(message, type) {
            try {
                sessionStorage.setItem(STORE_KEY, JSON.stringify({ message: message, type: type }));
            } catch (e) { /* storage unavailable: the toast is simply skipped */ }
        }

        function flushQueued() {
            try {
                var raw = sessionStorage.getItem(STORE_KEY);
                if (!raw) return;
                sessionStorage.removeItem(STORE_KEY);
                var item = JSON.parse(raw);
                show(item.message, item.type);
            } catch (e) { /* ignore */ }
        }

        function showServerFlash() {
            var node = document.getElementById('app-flash-data');
            if (!node) return;
            try {
                JSON.parse(node.textContent || '[]').forEach(function (item) {
                    show(item.message, item.type);
                });
            } catch (e) { /* ignore */ }
        }

        function boot() {
            showServerFlash();
            flushQueued();
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', boot);
        } else {
            boot();
        }

        window.addEventListener('pageshow', function (e) {
            if (e.persisted) flushQueued();
        });

        /* ═════════════════ AppForms: <form data-ajax> ═════════════════
           The form is submitted with fetch(). On a validation error the modal
           stays open and each message is shown under its own input; on success
           a toast is queued and the page refreshes (which closes every modal).
        */
        function csrfToken() {
            var meta = document.querySelector('meta[name="csrf-token"]');
            if (meta) return meta.getAttribute('content');
            var input = document.querySelector('input[name="_token"]');
            return input ? input.value : '';
        }

        /* "violations.0.vc_id" -> violations[0][vc_id] */
        function toFieldName(key) {
            var parts = key.split('.');
            return parts.shift() + parts.map(function (p) { return '[' + p + ']'; }).join('');
        }

        function cssEscape(value) {
            return window.CSS && CSS.escape ? CSS.escape(value) : value.replace(/["\\]/g, '\\$&');
        }

        function clearErrors(form) {
            form.querySelectorAll('.app-field-error, .app-form-errors').forEach(function (n) { n.remove(); });
            form.querySelectorAll('.app-field-invalid').forEach(function (n) {
                n.classList.remove('app-field-invalid');
                n.removeAttribute('aria-invalid');
            });
        }

        function findField(form, key) {
            var name = toFieldName(key);
            var field = form.querySelector('[name="' + cssEscape(name) + '"]');
            if (!field && key.indexOf('.') === -1) {
                field = form.querySelector('[name="' + cssEscape(key) + '[]"]');
            }
            return field;
        }

        function errorAnchor(field) {
            // An input with an icon sits inside a ".relative" wrapper: put the
            // message below the wrapper so it does not collide with the icon.
            var parent = field.parentElement;
            if (parent && parent.classList.contains('relative') && parent.children.length > 1) {
                return parent;
            }
            return field;
        }

        function showErrors(form, errors) {
            clearErrors(form);
            var orphans = [];
            var firstField = null;

            Object.keys(errors || {}).forEach(function (key) {
                var messages = [].concat(errors[key]);
                var field = findField(form, key);

                if (!field || field.type === 'hidden') {
                    orphans = orphans.concat(messages);
                    return;
                }

                field.classList.add('app-field-invalid');
                field.setAttribute('aria-invalid', 'true');
                firstField = firstField || field;

                var p = document.createElement('p');
                p.className = 'app-field-error';
                p.setAttribute('role', 'alert');
                p.textContent = messages[0];
                errorAnchor(field).insertAdjacentElement('afterend', p);

                var clear = function () {
                    field.classList.remove('app-field-invalid');
                    field.removeAttribute('aria-invalid');
                    p.remove();
                    field.removeEventListener('input', clear);
                    field.removeEventListener('change', clear);
                };
                field.addEventListener('input', clear);
                field.addEventListener('change', clear);
            });

            if (orphans.length) {
                var box = document.createElement('div');
                box.className = 'app-form-errors';
                box.setAttribute('role', 'alert');
                box.textContent = orphans.join(' ');
                form.insertBefore(box, form.firstChild);
            }

            if (firstField && firstField.focus && firstField.offsetParent !== null) {
                try { firstField.focus({ preventScroll: false }); } catch (e) { /* ignore */ }
            }
        }

        function setBusy(form, submitter, busy) {
            form.classList.toggle('is-submitting', busy);
            form.__busy = busy;
            if (!submitter) return;
            if (busy) {
                submitter.__wasDisabled = submitter.disabled;
                submitter.disabled = true;
                submitter.setAttribute('aria-busy', 'true');
            } else {
                submitter.disabled = !!submitter.__wasDisabled;
                submitter.removeAttribute('aria-busy');
            }
        }

        function methodOf(form, formData) {
            var spoof = formData.get('_method');
            return (spoof || form.getAttribute('method') || 'POST').toUpperCase();
        }

        async function submit(form, submitter) {
            var formData = new FormData(form);
            if (submitter && submitter.name && !formData.has(submitter.name)) {
                formData.append(submitter.name, submitter.value);
            }

            var response;
            try {
                // A submit button may post the same fields somewhere else (formaction).
                var action = (submitter && submitter.getAttribute('formaction')) ||
                    form.getAttribute('action') || window.location.href;

                response = await fetch(action, {
                    method: 'POST', // _method is spoofed in the body when needed
                    body: formData,
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken()
                    }
                });
            } catch (e) {
                show('Network error. Please check your connection and try again.', 'error');
                return;
            }

            var data = {};
            try { data = await response.json(); } catch (e) { /* non-JSON body */ }

            if (response.status === 422 && data.errors) {
                showErrors(form, data.errors);
                var count = Object.keys(data.errors).length;
                show(count === 1 ? [].concat(data.errors[Object.keys(data.errors)[0]])[0] :
                    'Please correct the highlighted fields and try again.', 'error');
                form.dispatchEvent(new CustomEvent('ajax-form:invalid', { bubbles: true, detail: data }));
                return;
            }

            if (response.status === 419) {
                show('Your session expired. Reload the page and try again.', 'error');
                return;
            }

            if (response.status === 401 || response.status === 403) {
                var msg = data.message || 'You are not allowed to do that.';
                if (data.redirect) {
                    queue(msg, 'error');
                    window.location.href = data.redirect;
                } else {
                    show(msg, 'error');
                }
                return;
            }

            if (!response.ok) {
                show(data.message || 'Something went wrong. Please try again.', 'error');
                return;
            }

            // Success: let the page react, then refresh so every list is current
            // and every modal is closed.
            form.dispatchEvent(new CustomEvent('ajax-form:success', { bubbles: true, detail: data }));
            queue(data.message || 'Saved.', data.type || 'success');

            var target = data.redirect;
            var here = window.location.href.split('#')[0];
            if (target && target.split('#')[0] !== here) {
                window.location.href = target;
            } else {
                window.location.reload();
            }
        }

        document.addEventListener('submit', function (e) {
            var form = e.target;
            if (!form || !form.matches || !form.matches('form[data-ajax]')) return;
            if (e.defaultPrevented) return;

            e.preventDefault();

            // Double-click / double-enter guard.
            if (form.__busy) return;

            var submitter = e.submitter || form.querySelector('button[type="submit"], input[type="submit"]');
            clearErrors(form);
            setBusy(form, submitter, true);

            submit(form, submitter).finally(function () {
                setBusy(form, submitter, false);
            });
        });

        window.AppForms = { showErrors: showErrors, clearErrors: clearErrors };
    })();
</script>
