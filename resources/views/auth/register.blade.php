<x-auth.shell title="Register - SmartCommute" heading="Start optimizing your daily commute."
    blurb="Create a free commuter account to pay fares cash-free and keep a receipt for every ride.">

    <h1 class="au-title">Create account</h1>
    <p class="au-sub">Start optimizing your daily commute.</p>

    <form action="{{ route('users.register') }}" method="POST" class="mt-8 space-y-5">
        @csrf

        <div>
            <label for="email" class="sc-label">Email</label>
            <div class="au-icon-field">
                <i class="fa-solid fa-envelope lead"></i>
                <input type="email" id="email" name="email" placeholder="you@example.com"
                    value="{{ old('email') }}" autocomplete="email" class="sc-field">
            </div>
            @if ($errors->has('email'))
                <div class="au-err"><i class="fa-solid fa-circle-exclamation"></i>
                    <span>{{ implode(' ', $errors->get('email')) }}</span>
                </div>
            @endif
        </div>

        <div>
            <label for="password" class="sc-label">Password</label>
            <div class="au-icon-field">
                <i class="fa-solid fa-lock lead"></i>
                <input type="password" name="password" id="password" placeholder="••••••••"
                    value="{{ old('password') }}" autocomplete="new-password" class="sc-field"
                    style="padding-right: 52px">
                <button type="button" onclick="togglePassword('password', 'eye-icon-1')" class="au-eye"
                    aria-label="Show or hide password"><i id="eye-icon-1" class="fa-solid fa-eye"></i></button>
            </div>
            @if ($errors->has('password'))
                <div class="au-err"><i class="fa-solid fa-circle-exclamation"></i>
                    <span>{{ implode(' ', $errors->get('password')) }}</span>
                </div>
            @endif
        </div>

        <div>
            <label for="confirm-password" class="sc-label">Confirm password</label>
            <div class="au-icon-field">
                <i class="fa-solid fa-lock lead"></i>
                <input type="password" name="confirm-password" id="confirm-password" placeholder="••••••••"
                    autocomplete="new-password" class="sc-field" style="padding-right: 52px">
                <button type="button" onclick="togglePassword('confirm-password', 'eye-icon-2')" class="au-eye"
                    aria-label="Show or hide password"><i id="eye-icon-2" class="fa-solid fa-eye"></i></button>
            </div>
            @if ($errors->has('confirm-password'))
                <div class="au-err"><i class="fa-solid fa-circle-exclamation"></i>
                    <span>{{ implode(' ', $errors->get('confirm-password')) }}</span>
                </div>
            @endif
        </div>

        <div>
            <label class="flex items-start gap-3 cursor-pointer text-[14.5px] leading-snug"
                style="color: var(--sc-ink-2)">
                <input type="checkbox" id="terms" name="terms" class="mt-0.5 w-4 h-4 rounded cursor-pointer">
                <span>I agree to the <a href="#" class="au-link">Terms of Service</a> and <a href="#"
                        class="au-link">Privacy Policy</a>.</span>
            </label>
            @if ($errors->has('terms'))
                <div class="au-err"><i class="fa-solid fa-circle-exclamation"></i>
                    <span>{{ implode(' ', $errors->get('terms')) }}</span>
                </div>
            @endif
        </div>

        <button type="submit" class="sc-btn w-full" style="height: 54px; font-size: 15.5px">Create account</button>
    </form>

    <div class="flex items-center gap-3 my-6">
        <div class="flex-1 h-px" style="background: var(--sc-line)"></div>
        <span class="text-[13px] font-semibold" style="color: var(--sc-mute)">or</span>
        <div class="flex-1 h-px" style="background: var(--sc-line)"></div>
    </div>

    <a href="{{ route('driver.register.page') }}" class="sc-btn sc-btn-ghost w-full">
        <i class="fa-solid fa-id-card" style="color: var(--sc-brand)"></i> Register as a driver
    </a>

    <p class="au-divider">
        Already have an account? <a href="{{ url('/login') }}" class="au-link">Log in</a>
    </p>

    <script>
        function togglePassword(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = "password";
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</x-auth.shell>
