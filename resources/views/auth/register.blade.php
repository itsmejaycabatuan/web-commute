<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - SmartCommute</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif']
                    }
                }
            }
        }
    </script>
    <style>
        ::-webkit-scrollbar {
            width: 5px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.08);
            border-radius: 999px;
            transition: background 0.3s ease;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.18);
        }

        ::-webkit-scrollbar-thumb:active {
            background: rgba(255, 255, 255, 0.25);
        }

        * {
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, 0.08) transparent;
        }

        .register-bg {
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.75) 0%, rgba(0, 15, 50, 0.7) 50%, rgba(0, 0, 0, 0.85) 100%),
                url("{{ asset('images/newbg.jpg') }}");
            background-size: cover;
            background-position: center;
        }

        .glass-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.07) 0%, rgba(255, 255, 255, 0.03) 100%);
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        @media (max-width: 639px) {
            .glass-card {
                background: linear-gradient(135deg, rgba(255, 255, 255, 0.05) 0%, rgba(255, 255, 255, 0.02) 100%);
                backdrop-filter: blur(20px);
                -webkit-backdrop-filter: blur(20px);
                border: 1px solid rgba(255, 255, 255, 0.08);
            }
        }

        .glass-card {
            scrollbar-gutter: stable;
        }

        .glass-card::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.12);
        }

        .glass-card::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.22);
        }

        .input-field {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.08);
            transition: all 0.3s ease;
        }

        .input-field:focus {
            border-color: rgba(59, 130, 246, 0.5);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            background: rgba(255, 255, 255, 0.07);
        }

        .input-field::placeholder {
            color: rgba(255, 255, 255, 0.2);
        }

        @keyframes card-enter {
            from {
                opacity: 0;
                transform: translateY(30px) scale(0.96);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .card-animate {
            animation: card-enter 0.7s cubic-bezier(0.16, 1, 0.3, 1) 0.15s both;
        }

        .btn-primary {
            background: white;
            color: black;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: #e5e7eb;
            transform: translateY(-1px);
            box-shadow: 0 8px 30px rgba(255, 255, 255, 0.15);
        }

        .btn-primary:active {
            transform: scale(0.98) translateY(0);
        }

        .btn-secondary {
            background: rgba(59, 130, 246, 0.15);
            border: 1px solid rgba(59, 130, 246, 0.3);
            color: #93c5fd;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: rgba(59, 130, 246, 0.25);
            border-color: rgba(59, 130, 246, 0.5);
            color: white;
            transform: translateY(-1px);
        }

        .btn-secondary:active {
            transform: scale(0.98) translateY(0);
        }
    </style>
    @include('partials.global-loading')
</head>

<body
    class="flex relative justify-center items-center p-3 sm:p-4 md:p-6 min-h-[100svh] register-bg font-sans text-white overflow-x-hidden">

    <!-- Decorative orbs -->
    <div
        class="absolute top-1/3 right-1/4 w-36 h-36 sm:w-72 sm:h-72 bg-purple-500/10 rounded-full blur-[60px] sm:blur-[100px] pointer-events-none">
    </div>
    <div
        class="absolute bottom-1/4 left-1/3 w-28 h-28 sm:w-56 sm:h-56 bg-blue-500/10 rounded-full blur-[50px] sm:blur-[80px] pointer-events-none">
    </div>

    <!-- Back button -->
    <a href="{{ url('/') }}"
        class="flex absolute top-3 left-3 sm:top-5 sm:left-5 md:top-8 md:left-8 items-center gap-2 sm:gap-2.5 transition group z-10">
        <div
            class="flex justify-center items-center w-9 h-9 sm:w-10 sm:h-10 rounded-lg sm:rounded-xl border border-white/10 bg-white/5 backdrop-blur-md group-hover:bg-white/10 group-hover:border-white/20 transition-all duration-300">
            <i class="text-xs sm:text-sm fa-solid fa-arrow-left text-white/60 group-hover:text-white transition"></i>
        </div>
        <span
            class="hidden md:inline text-[10px] font-bold tracking-widest uppercase text-white/50 group-hover:text-white/80 transition">Back
            to Home</span>
    </a>

    <!-- Register Card -->
    <div
        class="card-animate glass-card p-5 sm:p-7 md:p-8 w-full max-w-[340px] sm:max-w-[400px] rounded-2xl sm:rounded-[2rem] shadow-2xl shadow-black/30 max-h-[92svh] overflow-y-auto">

        <div class="mb-5 sm:mb-7 text-center">
            <div class="flex flex-col items-center justify-center mb-3 sm:mb-4">
                <div
                    class="flex items-center justify-center w-10 h-10 sm:w-12 sm:h-12 bg-blue-600 rounded-lg sm:rounded-xl shadow-lg shadow-blue-600/30 mb-2 sm:mb-3">
                    <i class="fa-solid fa-bus text-white text-base sm:text-lg"></i>
                </div>
                <span class="text-lg sm:text-xl font-bold tracking-tight text-white">
                    Smart<span class="text-blue-400">Commute</span>
                </span>
            </div>
            <h2 class="text-xl sm:text-2xl font-extrabold tracking-tight">Create account</h2>
            <p class="mt-1 text-[11px] sm:text-xs text-gray-400">Start optimizing your daily commute</p>
        </div>

        <form action="{{ route('users.register') }}" method="POST" class="space-y-3 sm:space-y-4">
            @csrf

            <div>
                <label
                    class="block mb-1 sm:mb-1.5 ml-0.5 sm:ml-1 font-semibold tracking-widest uppercase text-[9px] sm:text-[10px] text-gray-400">Email</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-3 sm:left-3.5 flex items-center pointer-events-none">
                        <i class="fa-solid fa-envelope text-[10px] sm:text-xs text-white/20"></i>
                    </div>
                    <input type="email" name="email" placeholder="you@example.com" value="{{ old('email') }}"
                        class="input-field py-2.5 sm:py-3 pl-9 sm:pl-10 pr-3 sm:pr-4 w-full text-xs sm:text-sm rounded-lg sm:rounded-xl focus:outline-none">
                </div>
                @if ($errors->has('email'))
                    <div class="mt-1.5 sm:mt-2 flex items-center gap-1.5 sm:gap-2">
                        <i class="fa-solid fa-circle-exclamation text-red-400 text-[9px] sm:text-[10px]"></i>
                        @foreach ($errors->get('email') as $message)
                            <span class="text-red-400 text-[10px] sm:text-[11px]">{{ $message }}</span>
                        @endforeach
                    </div>
                @endif
            </div>

            <div>
                <label
                    class="block mb-1 sm:mb-1.5 ml-0.5 sm:ml-1 font-semibold tracking-widest uppercase text-[9px] sm:text-[10px] text-gray-400">Password</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-3 sm:left-3.5 flex items-center pointer-events-none">
                        <i class="fa-solid fa-lock text-[10px] sm:text-xs text-white/20"></i>
                    </div>
                    <input type="password" name="password" id="password" placeholder="••••••••"
                        value="{{ old('password') }}"
                        class="input-field py-2.5 sm:py-3 pl-9 sm:pl-10 pr-9 sm:pr-10 w-full text-xs sm:text-sm rounded-lg sm:rounded-xl focus:outline-none">
                    <button type="button" onclick="togglePassword('password', 'eye-icon-1')"
                        class="absolute inset-y-0 right-3 sm:right-3.5 flex items-center text-white/30 hover:text-white/70 transition">
                        <i id="eye-icon-1" class="fa-solid fa-eye text-[10px] sm:text-xs"></i>
                    </button>
                </div>
                @if ($errors->has('password'))
                    <div class="mt-1.5 sm:mt-2 flex items-center gap-1.5 sm:gap-2">
                        <i class="fa-solid fa-circle-exclamation text-red-400 text-[9px] sm:text-[10px]"></i>
                        @foreach ($errors->get('password') as $message)
                            <span class="text-red-400 text-[10px] sm:text-[11px]">{{ $message }}</span>
                        @endforeach
                    </div>
                @endif
            </div>

            <div>
                <label
                    class="block mb-1 sm:mb-1.5 ml-0.5 sm:ml-1 font-semibold tracking-widest uppercase text-[9px] sm:text-[10px] text-gray-400">Confirm
                    Password</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-3 sm:left-3.5 flex items-center pointer-events-none">
                        <i class="fa-solid fa-lock text-[10px] sm:text-xs text-white/20"></i>
                    </div>
                    <input type="password" name="confirm-password" id="confirm-password" placeholder="••••••••"
                        class="input-field py-2.5 sm:py-3 pl-9 sm:pl-10 pr-9 sm:pr-10 w-full text-xs sm:text-sm rounded-lg sm:rounded-xl focus:outline-none">
                    <button type="button" onclick="togglePassword('confirm-password', 'eye-icon-2')"
                        class="absolute inset-y-0 right-3 sm:right-3.5 flex items-center text-white/30 hover:text-white/70 transition">
                        <i id="eye-icon-2" class="fa-solid fa-eye text-[10px] sm:text-xs"></i>
                    </button>
                </div>
                @if ($errors->has('confirm-password'))
                    <div class="mt-1.5 sm:mt-2 flex items-center gap-1.5 sm:gap-2">
                        <i class="fa-solid fa-circle-exclamation text-red-400 text-[9px] sm:text-[10px]"></i>
                        @foreach ($errors->get('confirm-password') as $message)
                            <span class="text-red-400 text-[10px] sm:text-[11px]">{{ $message }}</span>
                        @endforeach
                    </div>
                @endif
            </div>

            <label class="flex items-start gap-2 sm:gap-2.5 cursor-pointer group pt-0.5 sm:pt-1">
                <input type="checkbox" id="terms" name="terms"
                    class="mt-0.5 w-3 h-3 sm:w-3.5 sm:h-3.5 rounded cursor-pointer bg-white/5 border-white/20 text-blue-600 focus:ring-blue-500/30 focus:ring-offset-0">
                <span
                    class="leading-tight text-[10px] sm:text-[11px] text-gray-400 group-hover:text-gray-300 transition">I
                    agree to
                    the <a href="#" class="text-blue-400 hover:underline">Terms of Service</a> and <a
                        href="#" class="text-blue-400 hover:underline">Privacy Policy</a>.</span>
            </label>
            @if ($errors->has('terms'))
                <div class="flex items-center gap-1.5 sm:gap-2">
                    <i class="fa-solid fa-circle-exclamation text-red-400 text-[9px] sm:text-[10px]"></i>
                    @foreach ($errors->get('terms') as $message)
                        <span class="text-red-400 text-[10px] sm:text-[11px]">{{ $message }}</span>
                    @endforeach
                </div>
            @endif

            <button type="submit"
                class="btn-primary py-2.5 sm:py-3.5 mt-1 sm:mt-2 w-full text-[10px] sm:text-xs font-bold tracking-widest uppercase rounded-lg sm:rounded-xl">
                Create Account
            </button>
        </form>

        <!-- OR Divider -->
        <div class="flex items-center my-4 sm:my-5 gap-2 sm:gap-3">
            <div class="flex-grow h-px bg-white/10"></div>
            <span class="text-[9px] sm:text-[10px] font-bold tracking-widest text-gray-600 uppercase">or</span>
            <div class="flex-grow h-px bg-white/10"></div>
        </div>

        <a href="{{ route('driver.register.page') }}"
            class="btn-secondary py-2.5 sm:py-3.5 w-full text-[10px] sm:text-xs font-bold tracking-widest uppercase rounded-lg sm:rounded-xl flex items-center justify-center gap-1.5 sm:gap-2">
            <i class="fa-solid fa-id-card text-[10px] sm:text-[11px]"></i>
            Register as Driver
        </a>

        <div class="pt-4 sm:pt-6 mt-4 sm:mt-6 text-center border-t border-white/5">
            <p class="text-[11px] sm:text-xs text-gray-500">
                Already have an account?
                <a href="{{ url('/login') }}" class="font-semibold text-white hover:text-blue-400 transition">Log
                    in</a>
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
    </script>
</body>

</html>
