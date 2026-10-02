{{-- ═══════════════════════════════════════════════════════════════
     FLASH MESSAGES
     ═══════════════════════════════════════════════════════════════
     These are notifications, so they must sit above EVERY layer the app
     uses: desktop sidebar (z-50), mobile bottom bar (z-55), mobile drawer
     (z-65), map bottom sheets (z-91) and modals (z-100). Hence z-[9999].

     All four flavours share one markup block (previously four copies of
     the same markup, which is how they drifted apart). `data-flash-message`
     replaces the repeated `id="flash-message"` - duplicate ids are invalid
     HTML and the auto-hide script had to work around them.

     A session flash and validation errors can be present at the same time,
     so the stack is a column: messages no longer sit on top of each other.
--}}
@php
    $flashItems = [];

    if (session('success')) {
        $flashItems[] = [
            'tone' => 'from-green-500 to-emerald-600',
            'shadow' => 'shadow-green-500/20',
            'icon' => 'fa-check-circle',
            'title' => 'Success!',
            'messages' => [(string) session('success')],
        ];
    }

    if (session('error')) {
        $flashItems[] = [
            'tone' => 'from-red-500 to-rose-600',
            'shadow' => 'shadow-red-500/20',
            'icon' => 'fa-circle-exclamation',
            'title' => 'Error!',
            'messages' => [(string) session('error')],
        ];
    }

    if (session('warning')) {
        $flashItems[] = [
            'tone' => 'from-yellow-500 to-amber-600',
            'shadow' => 'shadow-yellow-500/20',
            'icon' => 'fa-triangle-exclamation',
            'title' => 'Warning!',
            'messages' => [(string) session('warning')],
        ];
    }

    if ($errors->any()) {
        $flashItems[] = [
            'tone' => 'from-red-500 to-rose-600',
            'shadow' => 'shadow-red-500/20',
            'icon' => 'fa-circle-exclamation',
            'title' => 'Error',
            'messages' => $errors->all(),
            'list' => true,
        ];
    }
@endphp

@if (!empty($flashItems))
    <div
        class="flash-stack fixed top-20 sm:top-24 left-3 right-3 sm:left-auto sm:right-6 z-[9999] pointer-events-none flex flex-col gap-2 items-stretch sm:items-end">
        @foreach ($flashItems as $flash)
            <div data-flash-message role="alert" aria-live="assertive"
                class="flash-message animate-slide-in pointer-events-none w-full sm:w-auto sm:min-w-[300px] sm:max-w-full">
                <div
                    class="pointer-events-auto bg-gradient-to-r {{ $flash['tone'] }} {{ $flash['shadow'] }} text-white px-4 sm:px-6 py-3.5 sm:py-4 rounded-2xl shadow-2xl flex items-start sm:items-center gap-3 w-full max-w-full">
                    <i
                        class="fa-solid {{ $flash['icon'] }} text-xl shrink-0 mt-0.5 sm:mt-0"></i>
                    <div class="flex-1 min-w-0">
                        <p class="font-bold text-sm">{{ $flash['title'] }}</p>
                        @if ($flash['list'] ?? false)
                            <ul class="text-xs opacity-90 mt-1 list-disc list-inside">
                                @foreach ($flash['messages'] as $message)
                                    <li>{{ $message }}</li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-xs opacity-90">{{ $flash['messages'][0] }}</p>
                        @endif
                    </div>
                    <button type="button" aria-label="Dismiss notification"
                        onclick="this.closest('[data-flash-message]').remove()"
                        class="hover:opacity-70 shrink-0">
                        <i class="fa-solid fa-times"></i>
                    </button>
                </div>
            </div>
        @endforeach
    </div>
@endif

<style>
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }

        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes slideInY {
        from {
            transform: translateY(-100%);
            opacity: 0;
        }

        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    .animate-slide-in {
        animation: slideIn 0.3s ease-out;
    }

    /* Never wider than the viewport, whatever the breakpoint. */
    .flash-stack,
    .flash-message {
        max-width: calc(100vw - 1.5rem);
    }

    /* Full-width block on a phone: slide down instead of from the side. */
    @media (max-width: 420px) {
        .animate-slide-in {
            animation-name: slideInY;
        }
    }

    /* Respect users who don't want motion. */
    @media (prefers-reduced-motion: reduce) {
        .animate-slide-in {
            animation: none;
        }
    }
</style>

<script>
    /* Auto-hide flash messages after 5 seconds. Uses the data attribute so
       several messages (session flash + validation errors) can coexist. */
    (function () {
        var bind = function () {
            document.querySelectorAll('[data-flash-message]').forEach(function (message) {
                setTimeout(function () {
                    message.style.transition = 'opacity 0.5s ease-out';
                    message.style.opacity = '0';
                    setTimeout(function () {
                        message.remove();
                    }, 500);
                }, 5000);
            });
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', bind);
        } else {
            bind();
        }
    })();
</script>
