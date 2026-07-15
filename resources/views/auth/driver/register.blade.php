<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Registration - SmartCommute</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <style>
        .register-bg {
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)),
                url("{{ asset('images/newbg.jpg') }}");
            background-size: cover;
            background-position: center;
        }

        /* Remove default number input spinners */
        input[type="tel"]::-webkit-inner-spin-button,
        input[type="tel"]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        input[type="tel"] {
            -moz-appearance: textfield;
        }
    </style>
</head>

<body class="flex relative justify-center items-center p-6 min-h-screen register-bg">

    @if (session('success'))
        <div class="fixed top-6 left-1/2 z-[90] px-4 py-3 max-w-md text-sm text-center text-white -translate-x-1/2 bg-emerald-600/95 rounded-xl border border-emerald-500/30 shadow-lg"
            id="login-flash-success">
            {{ session('success') }}
        </div>
    @endif

    <a href="{{ route('register') }}"
        class="flex absolute top-8 left-8 items-center space-x-2 transition hover:text-white text-white/70 group">
        <div
            class="flex justify-center items-center w-10 h-10 rounded-full border border-white/20 bg-white/10 backdrop-blur-md group-hover:bg-white/20">
            <i class="text-sm fa-solid fa-arrow-left"></i>
        </div>
        <span class="text-xs font-bold tracking-widest uppercase">Back to User Registration</span>
    </a>

    <div
        class="p-8 w-full max-w-sm text-white rounded-3xl border shadow-2xl bg-white/10 backdrop-blur-xl border-white/20">

        <div class="mb-8 text-center">
            <div class="flex flex-col items-center justify-center mb-4">
                <div class="flex items-center justify-center w-12 h-12 bg-blue-600 rounded-xl shadow-lg shadow-blue-600/20 mb-3">
                    <i class="fa-solid fa-id-card text-white text-xl"></i>
                </div>
                <span class="text-2xl font-bold tracking-wider italic text-white">
                    Smart<span class="text-blue-500">Commute</span>
                </span>
            </div>

            <h2 class="text-2xl font-bold tracking-tight">Driver Registration</h2>
            <p class="mt-1 text-xs opacity-60">Become a PUJ Operator</p>
        </div>

        <form action="{{ route('driver.register') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf

            <div>
                <label class="block mb-1 ml-1 font-bold tracking-widest uppercase opacity-50 text-[10px]">
                    Email
                </label>
                <input type="email" name="email" placeholder="email@example.com" value="{{ old('email') }}"
                    class="py-2.5 px-4 w-full text-sm rounded-xl border focus:ring-2 focus:outline-none bg-white/5 border-white/10 focus:ring-white/30">
            </div>
            @error('email')
                <p class="text-amber-300 text-xs">{{ $message }}</p>
            @enderror

            <div>
                <label class="block mb-1 ml-1 font-bold tracking-widest uppercase opacity-50 text-[10px]">
                    Contact Info
                </label>
                <div class="flex">
                    <div class="flex items-center gap-1.5 px-3 rounded-l-xl border border-r-0 bg-white/5 border-white/10">
                        <span class="text-xs font-bold opacity-60">+63</span>
                    </div>
                    <input type="tel" name="contact_info" id="contact-info"
                        placeholder="9XX XXX XXXX"
                        maxlength="11"
                        value="{{ old('contact_info') }}"
                        oninput="formatPhoneNumber(this)"
                        class="py-2.5 px-4 w-full text-sm rounded-r-xl border focus:ring-2 focus:outline-none bg-white/5 border-white/10 focus:ring-white/30 placeholder:text-white/30">
                </div>
                <p class="mt-1 ml-1 text-[10px] opacity-30">Philippine mobile number (e.g., 917 123 4567)</p>
            </div>
            @error('contact_info')
                <p class="text-amber-300 text-xs">{{ $message }}</p>
            @enderror

            <div>
                <label class="block mb-1 ml-1 font-bold tracking-widest uppercase opacity-50 text-[10px]">
                    Password
                </label>
                <div class="relative">
                    <input type="password" name="password" id="password" placeholder="••••••••"
                        class="py-2.5 px-4 w-full text-sm rounded-xl border focus:ring-2 focus:outline-none bg-white/5 border-white/10 focus:ring-white/30 pr-10">
                    <button type="button" onclick="togglePassword('password', 'eye-icon-1')"
                        class="absolute inset-y-0 right-3 flex items-center text-white/40 hover:text-white transition">
                        <i id="eye-icon-1" class="fa-solid fa-eye text-xs"></i>
                    </button>
                </div>
            </div>
            @error('password')
                <p class="text-amber-300 text-xs">{{ $message }}</p>
            @enderror

            <div>
                <label class="block mb-1 ml-1 font-bold tracking-widest uppercase opacity-50 text-[10px]">
                    Confirm Password
                </label>
                <div class="relative">
                    <input type="password" name="confirm-password" id="confirm-password" placeholder="••••••••"
                        class="py-2.5 px-4 w-full text-sm rounded-xl border focus:ring-2 focus:outline-none bg-white/5 border-white/10 focus:ring-white/30 pr-10">
                    <button type="button" onclick="togglePassword('confirm-password', 'eye-icon-2')"
                        class="absolute inset-y-0 right-3 flex items-center text-white/40 hover:text-white transition">
                        <i id="eye-icon-2" class="fa-solid fa-eye text-xs"></i>
                    </button>
                </div>
            </div>
            @error('confirm-password')
                <p class="text-amber-300 text-xs">{{ $message }}</p>
            @enderror

            <div>
                <label class="block mb-1 ml-1 font-bold tracking-widest uppercase opacity-50 text-[10px]">
                    Upload License ID
                </label>
                <input type="file" name="license_image" accept="image/jpeg,image/png,image/jpg"
                    class="w-full mt-1 text-xs text-white file:bg-blue-600 file:border-0 file:px-3 file:py-1 file:rounded-lg">
            </div>
            @error('license_image')
                <p class="text-amber-300 text-xs">{{ $message }}</p>
            @enderror

            <div class="flex items-start pt-1 space-x-2">
                <input type="checkbox" id="terms" name="terms" value="1"
                    class="mt-0.5 bg-transparent rounded focus:ring-0 border-white/20"
                    {{ old('terms') ? 'checked' : '' }}>
                <label for="terms" class="leading-tight opacity-60 text-[10px]">I agree to the Terms of Service and
                    Privacy Policy.</label>
            </div>
            @error('terms')
                <p class="text-amber-300 text-xs">{{ $message }}</p>
            @enderror

            <button type="submit"
                class="py-3 mt-4 w-full text-xs font-bold tracking-widest text-black uppercase bg-white rounded-xl transition-all hover:bg-gray-200 active:scale-95">
                Register
            </button>
        </form>

        <div class="pt-6 mt-8 text-center border-t border-white/10">
            <p class="text-xs opacity-60">
                Already have an account?
                <a href="{{ url('/login') }}" class="font-bold text-white hover:underline">Log in</a>
            </p>
        </div>
    </div>

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

        function formatPhoneNumber(input) {
            // Strip all non-digit characters
            let digits = input.value.replace(/\D/g, '');

            // Remove leading 0 if user typed 09XX (we already show +63 prefix)
            if (digits.startsWith('0')) {
                digits = digits.substring(1);
            }

            // Cap at 10 digits (since +63 is already shown)
            if (digits.length > 10) {
                digits = digits.substring(0, 10);
            }

            // Format: 9XX XXX XXXX
            if (digits.length > 6) {
                input.value = digits.slice(0, 3) + ' ' + digits.slice(3, 6) + ' ' + digits.slice(5);
            } else if (digits.length > 3) {
                input.value = digits.slice(0, 3) + ' ' + digits.slice(3);
            } else {
                input.value = digits;
            }
        }
    </script>

</body>

</html>
