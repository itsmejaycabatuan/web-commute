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

    // An error toast is skipped on the client when the form already shows the same text
    // (`covers` lists what to look for), so one problem is never reported twice.
    foreach (['success' => 'success', 'error' => 'error', 'warning' => 'warning', 'message' => 'info'] as $key => $type) {
        if (session()->has($key) && is_string(session($key)) && session($key) !== '') {
            $toastFlash[] = ['type' => $type, 'message' => session($key), 'covers' => $type === 'error' ? [session($key)] : []];
        }
    }

    if (isset($errors) && $errors->any() && ! $errors->has('credentials')) {
        $toastFlash[] = [
            'type' => 'error',
            'message' => $errors->count() === 1
                ? $errors->first()
                : 'Please correct the highlighted fields and try again.',
            'covers' => array_values(array_unique($errors->all())),
        ];
    }
@endphp

<style>
    /* Top-right on desktop, across the top on a phone. */
    .app-toast-stack {
        position: fixed;
        right: 1.5rem;
        top: 1.5rem;
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
            top: 1rem;
            align-items: stretch;
        }
    }

    .app-toast {
        pointer-events: auto;
        display: flex;
        align-items: center;
        gap: .75rem;
        padding: .625rem .625rem .625rem 1.25rem;
        border-radius: .75rem;
        border: 1px solid;
        box-shadow: 0 20px 40px -12px rgba(0, 0, 0, .35);
        font: 700 11px/1.35 system-ui, -apple-system, 'Segoe UI', sans-serif;
        max-width: 26rem;
        /* On a phone the toast spans the screen: the message takes the width, the X stays at the far end. */
        width: max-content;
        animation: app-toast-in .3s ease-out both;
        transition: opacity .25s ease, transform .25s ease;
    }

    @media (max-width: 767px) {
        .app-toast {
            width: auto;
            max-width: none;
        }
    }

    .app-toast.is-leaving {
        opacity: 0;
        transform: translateY(-.75rem) scale(.96);
    }

    .app-toast__text {
        flex: 1 1 auto;
        min-width: 0;
        overflow-wrap: anywhere;
        padding: .125rem 0;
    }

    .app-toast__close {
        flex: 0 0 auto;
        margin-left: auto;
        width: 1.5rem;
        height: 1.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: .375rem;
        opacity: .55;
        background: none;
        border: 0;
        color: inherit;
        cursor: pointer;
        font: 400 20px/1 system-ui, -apple-system, 'Segoe UI', sans-serif;
    }

    .app-toast__close:hover {
        opacity: 1;
    }

    .app-toast--success {
        background: #ecfdf5;
        border-color: #a7f3d0;
        color: #047857;
    }

    .app-toast--error {
        background: #fff1f2;
        border-color: #fecdd3;
        color: #be123c;
    }

    .app-toast--warning {
        background: #fffbeb;
        border-color: #fde68a;
        color: #b45309;
    }

    .app-toast--info {
        background: #eff6ff;
        border-color: #bfdbfe;
        color: #1d4ed8;
    }

    @keyframes app-toast-in {
        from {
            opacity: 0;
            transform: translateY(-.75rem) scale(.96);
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

    /* ── Select components ──
       One look for every <select> in the app: no native arrow squeezed against the
       edge, the same chevron and right padding everywhere, readable option lists
       in both themes. !important because pages style selects in many different
       ways (Tailwind utilities, .form-input, .form-select). */
    select:not([multiple]):not([size]) {
        -webkit-appearance: none !important;
        -moz-appearance: none !important;
        appearance: none !important;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E") !important;
        background-repeat: no-repeat !important;
        background-position: right .875rem center !important;
        background-size: .75rem !important;
        padding-right: 2.25rem !important;
        text-overflow: ellipsis;
        cursor: pointer;
    }

    .dark select:not([multiple]):not([size]) {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23737373' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E") !important;
    }

    select:disabled {
        cursor: not-allowed;
    }

    select option {
        background: #ffffff;
        color: #1e293b;
    }

    .dark select option {
        background: #111111;
        color: #ffffff;
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

        var TYPES = { success: 1, error: 1, warning: 1, info: 1 };
        var STORE_KEY = 'app-pending-toast';
        var stack = null;

        function ensureStack() {
            if (stack && document.body && document.body.contains(stack)) return stack;
            if (!document.body) return null;

            stack = document.createElement('div');
            stack.className = 'app-toast-stack';
            stack.setAttribute('aria-live', 'polite');
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
            type = TYPES[type] ? type : 'info';
            var host = ensureStack();
            if (!host || !message) return null;

            var el = document.createElement('div');
            el.className = 'app-toast app-toast--' + type;
            el.setAttribute('role', type === 'error' ? 'alert' : 'status');

            var text = document.createElement('span');
            text.className = 'app-toast__text';
            text.textContent = message;

            var close = document.createElement('button');
            close.type = 'button';
            close.className = 'app-toast__close';
            close.setAttribute('aria-label', 'Dismiss notification');
            close.textContent = '\u00d7'; // no icon font needed
            close.addEventListener('click', function () {
                dismiss(el);
            });

            el.appendChild(text);
            el.appendChild(close);
            host.appendChild(el);

            setTimeout(function () {
                dismiss(el);
            }, duration || (type === 'error' ? 6000 : (type === 'warning' ? 10000 : 4000)));

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

        /* True when every message is already written somewhere on the page (an error
           under a field, a form's error box). Hidden text (closed modal, x-show) is not
           counted: innerText only returns what is rendered. */
        function alreadyOnPage(messages) {
            if (!messages || !messages.length || !document.body) return false;
            var text = (document.body.innerText || '').replace(/\s+/g, ' ');
            return messages.every(function (m) {
                return text.indexOf(String(m).replace(/\s+/g, ' ').trim()) !== -1;
            });
        }

        function showServerFlash() {
            var node = document.getElementById('app-flash-data');
            if (!node) return;
            var items;
            try {
                items = JSON.parse(node.textContent || '[]');
            } catch (e) { return; }

            // Wait a tick so Alpine has hidden whatever x-show hides before we look.
            setTimeout(function () {
                items.forEach(function (item) {
                    if (item.type === 'error' && alreadyOnPage(item.covers)) return;
                    show(item.message, item.type);
                });
            }, 60);
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

        /* Show (or replace) one message under a field; it clears as soon as the
           field is edited. Used for server errors and for the client rules. */
        function setFieldError(field, message) {
            clearFieldError(field);

            field.classList.add('app-field-invalid');
            field.setAttribute('aria-invalid', 'true');

            var p = document.createElement('p');
            p.className = 'app-field-error';
            p.setAttribute('role', 'alert');
            p.textContent = message;
            errorAnchor(field).insertAdjacentElement('afterend', p);
            field.__errorEl = p;
        }

        function clearFieldError(field) {
            field.classList.remove('app-field-invalid');
            field.removeAttribute('aria-invalid');
            if (field.__errorEl) {
                field.__errorEl.remove();
                field.__errorEl = null;
            }
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

                firstField = firstField || field;
                setFieldError(field, messages[0]);
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

            // True when the form itself now shows the problem (under a field or in its
            // error box), so no separate toast is needed.
            return form.offsetParent !== null && (!!firstField || orphans.length > 0);
        }

        function setBusy(form, submitter, busy) {
            form.classList.toggle('is-submitting', busy);
            form.__busy = busy;

            // A visible "working" state: spinner on the button and, if the request
            // takes more than a moment, the blocking overlay. A request that ends
            // in success keeps the overlay until the page refreshes.
            if (window.AppLoading) {
                if (busy) {
                    AppLoading.button(submitter, true);
                    AppLoading.show(form.getAttribute('data-loading-text') || 'Saving\u2026', 250);
                } else {
                    AppLoading.button(submitter, false);
                    if (!form.__succeeded) AppLoading.hide();
                }
            }

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

        /* Always post to this site's own origin. A form action generated as an
           absolute URL (http://localhost, or http:// behind an https tunnel such
           as ngrok) would otherwise be a cross-origin / mixed-content request and
           fail with a network error. */
        function sameOrigin(url) {
            try {
                var u = new URL(url, window.location.href);
                return u.pathname + u.search;
            } catch (e) {
                return url;
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
                action = sameOrigin(action);

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
                // The messages appear in the form; a toast is only the fallback when they could not.
                var covered = showErrors(form, data.errors);
                if (!covered) {
                    var count = Object.keys(data.errors).length;
                    show(count === 1 ? [].concat(data.errors[Object.keys(data.errors)[0]])[0] :
                        'Please correct the highlighted fields and try again.', 'error');
                }
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
            form.__succeeded = true;
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

            // Client rules first: nothing is sent while a field is obviously wrong.
            // (The reject / delete style forms have no rules and go straight through.)
            var firstInvalid = form.querySelector('[data-rules]') ? validateForm(form) : null;
            if (firstInvalid) {
                // Each problem is already written under its field, so no toast.
                if (firstInvalid.focus) try { firstInvalid.focus(); } catch (err) { /* ignore */ }
                return;
            }

            clearErrors(form);
            form.__succeeded = false;
            setBusy(form, submitter, true);

            submit(form, submitter).finally(function () {
                setBusy(form, submitter, false);
            });
        });


        /* ═════════════ Client-side input rules (data-rules) ═════════════
           The same checks the registration screens make, applied to the admin
           modals: <input data-rules="required|email|min:8|same:password|phone|image:2048">
           Each rule except `required` is skipped while the field is empty, so an
           optional field only fails when something invalid was typed. The server
           still validates everything; this just answers sooner.
        */
        var PH_MOBILE = /^(?:\+?63|0)?\s?9\d{2}\s?\d{3}\s?\d{4}$/;

        function fieldLabel(field) {
            return (field.getAttribute('data-label') ||
                (field.name || 'field').replace(/\[|\]/g, ' ').replace(/[_-]+/g, ' ')).trim().toLowerCase();
        }

        function isEmpty(field) {
            if (field.type === 'file') return !(field.files && field.files.length);
            if (field.type === 'checkbox') return !field.checked;
            return String(field.value || '').trim() === '';
        }

        function checkField(field) {
            var spec = field.getAttribute('data-rules');
            if (!spec) return '';

            var rules = spec.split('|');
            var label = fieldLabel(field);
            var empty = isEmpty(field);

            for (var i = 0; i < rules.length; i++) {
                var parts = rules[i].split(':');
                var rule = parts[0];
                var arg = parts.slice(1).join(':');
                var value = field.value;

                if (rule === 'required') {
                    if (empty) return 'The ' + label + ' field is required.';
                    continue;
                }
                if (empty) continue;

                if (rule === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(value.trim())) {
                    return 'Enter a valid email address.';
                }
                if (rule === 'min' && value.length < parseInt(arg, 10)) {
                    return 'The ' + label + ' must be at least ' + arg + ' characters.';
                }
                if (rule === 'max' && value.length > parseInt(arg, 10)) {
                    return 'The ' + label + ' may not be greater than ' + arg + ' characters.';
                }
                if (rule === 'same') {
                    var other = field.form && field.form.querySelector('[name="' + cssEscape(arg) + '"]');
                    if (other && other.value !== value) return 'The ' + label + ' does not match.';
                }
                if (rule === 'phone' && !PH_MOBILE.test(value.trim())) {
                    return 'Enter a valid Philippine mobile number (e.g. 917 123 4567).';
                }
                if (rule === 'image') {
                    var file = field.files && field.files[0];
                    if (file) {
                        if (!/^image\/(jpe?g|png)$/i.test(file.type) && !/\.(jpe?g|png)$/i.test(file.name)) {
                            return 'The ' + label + ' must be a JPG or PNG image.';
                        }
                        var maxKb = parseInt(arg, 10) || 2048;
                        if (file.size > maxKb * 1024) {
                            return 'The ' + label + ' may not be greater than ' + (maxKb / 1024) + ' MB.';
                        }
                    }
                }
            }

            return '';
        }

        function validateForm(form) {
            var first = null;
            form.querySelectorAll('[data-rules]').forEach(function (field) {
                // A field hidden by x-show / x-if is not part of this submission.
                if (field.offsetParent === null && field.type !== 'hidden') return;
                var message = checkField(field);
                if (message) {
                    setFieldError(field, message);
                    first = first || field;
                } else {
                    clearFieldError(field);
                }
            });
            return first;
        }

        /* Philippine mobile number as typed in the registration form: 9XX XXX XXXX */
        function formatPhone(input) {
            var digits = input.value.replace(/\D/g, '');
            if (digits.indexOf('63') === 0 && digits.length > 10) digits = digits.substring(2);
            if (digits.charAt(0) === '0') digits = digits.substring(1);
            digits = digits.substring(0, 10);
            if (digits.length > 6) {
                input.value = digits.slice(0, 3) + ' ' + digits.slice(3, 6) + ' ' + digits.slice(6);
            } else if (digits.length > 3) {
                input.value = digits.slice(0, 3) + ' ' + digits.slice(3);
            } else {
                input.value = digits;
            }
        }

        document.addEventListener('input', function (e) {
            var field = e.target;
            if (!field || !field.matches) return;

            if (field.matches('[data-format="phone"]')) formatPhone(field);

            // Re-check while typing only once a message is showing, so the
            // user is not nagged before they have finished.
            if (field.__errorEl) {
                if (field.hasAttribute('data-rules')) {
                    var message = checkField(field);
                    message ? setFieldError(field, message) : clearFieldError(field);
                } else {
                    clearFieldError(field);
                }
            }
        });

        // A select / checkbox / file with a server error is cleared once changed.
        document.addEventListener('change', function (e) {
            var field = e.target;
            if (field && field.__errorEl && !field.hasAttribute('data-rules')) clearFieldError(field);
        });

        document.addEventListener('focusout', function (e) {
            var field = e.target;
            if (!field || !field.matches || !field.matches('form[data-ajax] [data-rules]')) return;
            var message = checkField(field);
            message ? setFieldError(field, message) : clearFieldError(field);
        });

        document.addEventListener('change', function (e) {
            var field = e.target;
            if (!field || !field.matches || !field.matches('form[data-ajax] input[type="file"][data-rules]')) return;
            var message = checkField(field);
            message ? setFieldError(field, message) : clearFieldError(field);
        });

        window.AppForms = { showErrors: showErrors, clearErrors: clearErrors };
    })();
</script>
