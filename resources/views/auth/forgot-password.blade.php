<x-auth.shell title="Forgot Password - SmartCommute" heading="Locked out? It happens."
    blurb="We'll email you a secure link so you can choose a new password and get back on the road.">

    <div class="au-modal-ico" style="margin: 0 0 20px; background: rgba(245,158,11,.16); color: #d97706"><i
            class="fa-solid fa-key"></i></div>
    <h1 class="au-title">Forgot password?</h1>
    <p class="au-sub">No worries. Enter your email address and we'll send you a link to reset your password.</p>

    @if (session('status'))
        <div class="au-alert ok mt-6"><i class="fa-solid fa-circle-check mt-0.5"></i>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <form action="{{ route('password.email') }}" method="POST" class="mt-8 space-y-5">
        @csrf

        <div>
            <label for="email" class="sc-label">Email address</label>
            <div class="au-icon-field">
                <i class="fa-solid fa-envelope lead"></i>
                <input type="text" id="email" placeholder="you@example.com" name="email"
                    value="{{ old('email') }}" autocomplete="email" class="sc-field">
            </div>
            @if ($errors->has('email'))
                <div class="au-err"><i class="fa-solid fa-circle-exclamation"></i>
                    <span>{{ implode(' ', $errors->get('email')) }}</span>
                </div>
            @endif
        </div>

        <button type="submit" class="sc-btn w-full" style="height: 54px; font-size: 15.5px">Send reset link</button>
    </form>

    <p class="au-divider">
        Remember your password? <a href="{{ route('login') }}" class="au-link">Back to login</a>
    </p>
</x-auth.shell>
