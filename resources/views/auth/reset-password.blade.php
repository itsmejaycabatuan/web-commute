<x-auth.shell title="Reset Password - SmartCommute" heading="Choose a fresh password."
    blurb="Pick something strong that you don't use anywhere else, and you're back in.">

    <x-slot:head>
        <style>
            .strength-bar {
                height: 6px;
                border-radius: 99px;
                background: var(--sc-surface-3);
                transition: background-color .35s ease;
            }
        </style>
    </x-slot:head>

    <div class="au-modal-ico" style="margin: 0 0 20px; background: rgba(16,185,129,.14); color: #10b981"><i
            class="fa-solid fa-key"></i></div>
    <h1 class="au-title">Reset password</h1>
    <p class="au-sub">Enter your new credentials below to regain access to your account.</p>

    <div class="flex items-center gap-3 mt-6 p-3 pr-4 rounded-2xl"
        style="background: var(--sc-surface-2); border: 1px solid var(--sc-line)">
        <span class="grid place-items-center w-10 h-10 rounded-xl flex-none"
            style="background: var(--sc-brand-soft); color: var(--sc-brand-text)"><i
                class="fa-solid fa-envelope"></i></span>
        <div class="min-w-0">
            <p class="text-[12.5px] font-semibold" style="color: var(--sc-mute)">Resetting for</p>
            <p class="text-[14.5px] font-bold truncate" style="color: var(--sc-ink)">{{ $email }}</p>
        </div>
        <i class="fa-solid fa-circle-check ml-auto" style="color: #10b981"></i>
    </div>

    <form action="{{ route('password.update') }}" method="POST" class="mt-6 space-y-5">
        @csrf

        <input type="email" name="email" placeholder="you@example.com" value="{{ $email }}" class="hidden">

        @if ($errors->any() && !$errors->has('email') && !$errors->has('password') && !$errors->has('confirm-password'))
            <div class="au-alert bad"><i class="fa-solid fa-circle-exclamation mt-0.5"></i>
                <span>{{ implode(' ', $errors->all()) }}</span>
            </div>
        @endif

        <!-- New Password -->
        <div>
            <label for="password" class="sc-label">New password</label>
            <div class="au-icon-field">
                <i class="fa-solid fa-lock lead"></i>
                <input type="password" name="password" id="password" placeholder="••••••••"
                    value="{{ old('password') }}" oninput="checkStrength(this.value)" autocomplete="new-password"
                    class="sc-field" style="padding-right: 52px">
                <button type="button" onclick="togglePassword('password', 'eye-icon-1')" class="au-eye"
                    aria-label="Show or hide password"><i id="eye-icon-1" class="fa-solid fa-eye"></i></button>
            </div>
            <div class="mt-3 flex gap-1.5">
                <div class="strength-bar flex-1" id="str-1"></div>
                <div class="strength-bar flex-1" id="str-2"></div>
                <div class="strength-bar flex-1" id="str-3"></div>
                <div class="strength-bar flex-1" id="str-4"></div>
            </div>
            <p class="mt-2 text-[13px] font-medium" style="color: var(--sc-mute)" id="strength-text">Minimum 8
                characters</p>
            @if ($errors->has('password'))
                <div class="au-err"><i class="fa-solid fa-circle-exclamation"></i>
                    <span>{{ implode(' ', $errors->get('password')) }}</span>
                </div>
            @endif
        </div>

        <!-- Confirm Password -->
        <div>
            <label for="confirm-password" class="sc-label">Confirm password</label>
            <div class="au-icon-field">
                <i class="fa-solid fa-lock lead"></i>
                <input type="password" name="confirm-password" id="confirm-password" placeholder="••••••••"
                    oninput="checkMatch()" autocomplete="new-password" class="sc-field" style="padding-right: 52px">
                <button type="button" onclick="togglePassword('confirm-password', 'eye-icon-2')" class="au-eye"
                    aria-label="Show or hide password"><i id="eye-icon-2" class="fa-solid fa-eye"></i></button>
            </div>
            <div class="mt-2 flex items-center gap-2 hidden" id="match-indicator">
                <i class="text-[13px]" id="match-icon"></i>
                <span class="text-[13px] font-medium" id="match-text"></span>
            </div>
            @if ($errors->has('confirm-password'))
                <div class="au-err"><i class="fa-solid fa-circle-exclamation"></i>
                    <span>{{ implode(' ', $errors->get('confirm-password')) }}</span>
                </div>
            @endif
        </div>

        <input type="text" name="token" value="{{ $token }}" class="hidden">

        <button type="submit" class="sc-btn w-full" style="height: 54px; font-size: 15.5px">
            <i class="fa-solid fa-rotate-right text-sm"></i> Reset password
        </button>
    </form>

    <p class="au-divider">
        Remember your password? <a href="{{ route('login') }}" class="au-link">Back to login</a>
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

        function checkStrength(password) {
            const bars = [1, 2, 3, 4].map(n => document.getElementById('str-' + n));
            const text = document.getElementById('strength-text');

            let score = 0;
            if (password.length >= 8) score++;
            if (password.length >= 12) score++;
            if (/[A-Z]/.test(password) && /[a-z]/.test(password)) score++;
            if (/[0-9]/.test(password) && /[^A-Za-z0-9]/.test(password)) score++;

            const colors = ['#ef4444', '#f97316', '#eab308', '#10b981'];
            const labels = ['Weak', 'Fair', 'Good', 'Strong'];

            bars.forEach((bar, i) => {
                bar.style.backgroundColor = (password.length && i < score) ? colors[score - 1] : '';
            });

            if (password.length === 0) {
                text.style.color = '';
                text.textContent = 'Minimum 8 characters';
            } else {
                text.style.color = colors[score - 1] || '';
                text.textContent = labels[score - 1] || 'Too short';
            }

            checkMatch();
        }

        function checkMatch() {
            const pass = document.getElementById('password').value;
            const confirm = document.getElementById('confirm-password').value;
            const indicator = document.getElementById('match-indicator');
            const icon = document.getElementById('match-icon');
            const text = document.getElementById('match-text');

            if (confirm.length === 0) {
                indicator.classList.add('hidden');
                return;
            }

            indicator.classList.remove('hidden');

            if (pass === confirm) {
                icon.className = 'text-[13px] fa-solid fa-circle-check';
                icon.style.color = text.style.color = '#10b981';
                text.textContent = 'Passwords match';
            } else {
                icon.className = 'text-[13px] fa-solid fa-circle-xmark';
                icon.style.color = text.style.color = '#ef4444';
                text.textContent = 'Passwords do not match';
            }
        }
    </script>
</x-auth.shell>
