<x-auth.shell title="Email Verified - SmartCommute" heading="You're all set."
    blurb="Your email is confirmed. Open the map to see PUJs on the road and plan your next ride.">

    <div class="text-center">
        <div class="au-modal-ico" style="width: 80px; height: 80px; font-size: 34px; background: rgba(16,185,129,.14); color: #10b981"><i
                class="fa-solid fa-circle-check"></i></div>

        <h1 class="au-title">{{ $alreadyVerified ? 'Email already verified' : 'Email verified' }}</h1>
        <p class="au-sub">
            {{ $alreadyVerified ? 'This email address was already verified.' : 'Thank you! Your email address has been verified.' }}
            You can now close this page.
        </p>

        <div class="au-divider">
            <a href="{{ auth()->check() ? route('map') : route('login') }}" class="sc-btn w-full"
                style="height: 54px; font-size: 15.5px">
                {{ auth()->check() ? 'Continue to SmartCommute' : 'Go to sign in' }}
            </a>
        </div>
    </div>
</x-auth.shell>
