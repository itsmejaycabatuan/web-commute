<x-auth.shell title="Verify Email - SmartCommute" heading="One quick step before you ride."
    blurb="Confirming your email keeps your wallet, receipts and ride history safe.">

    <div class="au-modal-ico" style="margin: 0 0 20px; background: var(--sc-brand-soft); color: var(--sc-brand-text)"><i
            class="fa-solid fa-envelope-open-text"></i></div>
    <h1 class="au-title">Verify your email</h1>
    <p class="au-sub">We've sent a verification link and a 6-digit code to your inbox. Click the link, or enter the
        code below if you can't open it.</p>

    @if (session('message'))
        <div class="au-alert ok mt-6"><i class="fa-solid fa-circle-check mt-0.5"></i>
            <span>{{ session('message') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="au-alert bad mt-6"><i class="fa-solid fa-circle-exclamation mt-0.5"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <form action="{{ route('verification.code') }}" method="POST" class="mt-7 space-y-3">
        @csrf
        <label for="code" class="sc-label">Verification code</label>
        <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="7"
            pattern="[0-9 ]{6,7}" placeholder="000000" value="{{ old('code') }}" required
            class="sc-field text-center font-extrabold"
            style="height: 64px; font-size: 1.7rem; letter-spacing: .5em; {{ $errors->has('code') ? 'border-color:#ef4444' : '' }}">
        @error('code')
            <p class="au-err" role="alert"><i class="fa-solid fa-circle-exclamation"></i><span>{{ $message }}</span></p>
        @enderror
        <button type="submit" class="sc-btn w-full" style="height: 54px; font-size: 15.5px">Verify with code</button>
    </form>

    <form action="{{ route('verification.send') }}" method="POST" class="mt-3">
        @csrf
        <button type="submit" class="sc-btn sc-btn-ghost w-full" style="height: 54px; font-size: 15.5px">
            <i class="fa-solid fa-paper-plane text-sm" style="color: var(--sc-brand)"></i> Resend link and code
        </button>
    </form>

    <p class="au-divider text-[14px]" style="text-align: left">
        <i class="fa-solid fa-circle-info mr-1.5" style="color: var(--sc-brand)"></i>
        Didn't receive the email? Check your spam folder or resend it. Each new email replaces the previous code; codes
        expire after 60 minutes.
    </p>

    <form action="{{ route('users.logout') }}" method="POST" class="mt-5 text-center">
        @csrf
        <button type="submit" class="au-link text-[14.5px]"><i
                class="fa-solid fa-arrow-right-from-bracket mr-1.5"></i>Log out</button>
    </form>
</x-auth.shell>
