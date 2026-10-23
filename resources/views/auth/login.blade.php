<x-auth.shell title="Login - SmartCommute" heading="Welcome back to your commute."
    blurb="Sign in to track PUJs on the live map, pay your fare cash-free and see your ride history.">

    <x-slot:modals>
        @if (session('driver_registered'))
            <div id="driver-registered-modal" role="dialog" aria-modal="true" aria-labelledby="driver-registered-title"
                class="au-modal">
                <div class="au-modal-card">
                    <div class="au-modal-ico" style="background: rgba(16,185,129,.14); color: #10b981"><i
                            class="fa-solid fa-circle-check"></i></div>
                    <h3 id="driver-registered-title">You're registered!</h3>
                    <p>Your driver account was submitted successfully. Please wait for an administrator to approve it
                        before you sign in.</p>
                    <button type="button" onclick="document.getElementById('driver-registered-modal').remove()"
                        class="sc-btn w-full mt-6">OK</button>
                </div>
            </div>
        @endif

        @if (session('driver_pending'))
            <div id="driver-pending-modal" role="dialog" aria-modal="true" aria-labelledby="driver-pending-title"
                class="au-modal">
                <div class="au-modal-card">
                    <div class="au-modal-ico" style="background: rgba(245,158,11,.16); color: #d97706"><i
                            class="fa-solid fa-clock"></i></div>
                    <h3 id="driver-pending-title">Your account is pending</h3>
                    <p>An administrator must approve your driver account before you can sign in.</p>
                    <button type="button" onclick="document.getElementById('driver-pending-modal').remove()"
                        class="sc-btn w-full mt-6">OK</button>
                </div>
            </div>
        @endif

        @if (session('driver_rejected'))
            <div id="driver-rejected-modal" role="dialog" aria-modal="true" aria-labelledby="driver-rejected-title"
                class="au-modal">
                <div class="au-modal-card">
                    <div class="au-modal-ico" style="background: rgba(239,68,68,.14); color: #ef4444"><i
                            class="fa-solid fa-ban"></i></div>
                    <h3 id="driver-rejected-title">Account submission rejected</h3>
                    <p>Your driver application was not approved. If you believe this is a mistake, contact support.</p>
                    <button type="button" onclick="document.getElementById('driver-rejected-modal').remove()"
                        class="sc-btn w-full mt-6">OK</button>
                </div>
            </div>
        @endif
    </x-slot:modals>

    <h1 class="au-title">Welcome back</h1>
    <p class="au-sub">Sign in to continue to your dashboard.</p>

    <form action="{{ route('users.login') }}" method="POST" class="mt-8 space-y-5">
        @csrf

        <div>
            <label for="email" class="sc-label">Email address</label>
            <div class="au-icon-field">
                <i class="fa-solid fa-envelope lead"></i>
                <input type="email" id="email" placeholder="you@example.com" name="email"
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
                <input type="password" id="password" placeholder="••••••••" name="password"
                    value="{{ old('password') }}" autocomplete="current-password" class="sc-field"
                    style="padding-right: 52px">
                <button type="button" onclick="togglePassword()" class="au-eye" aria-label="Show or hide password">
                    <i id="eye-icon" class="fa-solid fa-eye"></i>
                </button>
            </div>
            @if ($errors->has('password'))
                <div class="au-err"><i class="fa-solid fa-circle-exclamation"></i>
                    <span>{{ implode(' ', $errors->get('password')) }}</span>
                </div>
            @endif
        </div>

        @if ($errors->has('credentials'))
            <div class="au-alert bad"><i class="fa-solid fa-circle-exclamation mt-0.5"></i>
                <span>{{ implode(' ', $errors->get('credentials')) }}</span>
            </div>
        @endif

        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2.5 cursor-pointer text-[14.5px] font-medium"
                style="color: var(--sc-ink-2)">
                <input type="checkbox" id="remember" name="remember" class="w-4 h-4 rounded cursor-pointer">
                Remember me
            </label>
            <a href="{{ route('password.request') }}" class="au-link text-[14.5px]">Forgot password?</a>
        </div>

        <button type="submit" class="sc-btn w-full" style="height: 54px; font-size: 15.5px">Log in</button>
    </form>

    <p class="au-divider">
        Don't have an account? <a href="{{ url('/register') }}" class="au-link">Create one</a>
    </p>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }
    </script>
</x-auth.shell>
