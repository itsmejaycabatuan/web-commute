<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - SmartCommute</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        .reset-bg {
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.78) 0%, rgba(0, 12, 45, 0.72) 50%, rgba(0, 0, 0, 0.85) 100%),
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

        @keyframes shield-pulse {

            0%,
            100% {
                box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.3);
            }

            50% {
                box-shadow: 0 0 0 12px rgba(34, 197, 94, 0);
            }
        }

        .shield-pulse {
            animation: shield-pulse 2.5s ease-in-out infinite;
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

        .strength-bar {
            height: 3px;
            border-radius: 2px;
            transition: all 0.4s ease;
        }
    </style>
</head>

<body
    class="flex relative justify-center items-center p-3 sm:p-4 md:p-6 min-h-[100svh] reset-bg font-sans text-white overflow-x-hidden">

    <!-- Decorative orbs -->
    <div
        class="absolute top-1/3 left-1/4 w-40 h-40 sm:w-80 sm:h-80 bg-green-500/8 rounded-full blur-[80px] sm:blur-[120px] pointer-events-none">
    </div>
    <div
        class="absolute bottom-1/4 right-1/3 w-28 h-28 sm:w-60 sm:h-60 bg-blue-500/10 rounded-full blur-[60px] sm:blur-[100px] pointer-events-none">
    </div>

    <!-- Back button -->
    <a href="{{ route('login') }}"
        class="flex absolute top-3 left-3 sm:top-5 sm:left-5 md:top-8 md:left-8 items-center gap-2 sm:gap-2.5 transition group z-10">
        <div
            class="flex justify-center items-center w-9 h-9 sm:w-10 sm:h-10 rounded-lg sm:rounded-xl border border-white/10 bg-white/5 backdrop-blur-md group-hover:bg-white/10 group-hover:border-white/20 transition-all duration-300">
            <i class="text-xs sm:text-sm fa-solid fa-arrow-left text-white/60 group-hover:text-white transition"></i>
        </div>
        <span
            class="hidden md:inline text-[10px] font-bold tracking-widest uppercase text-white/50 group-hover:text-white/80 transition">Back
            to Login</span>
    </a>

    <!-- Card -->
    <div
        class="card-animate glass-card p-5 sm:p-7 md:p-8 w-full max-w-[340px] sm:max-w-[400px] rounded-2xl sm:rounded-[2rem] shadow-2xl shadow-black/30">

        <div class="mb-5 sm:mb-8 text-center">
            <div class="flex justify-center mb-4 sm:mb-5">
                <div
                    class="shield-pulse flex items-center justify-center w-12 h-12 sm:w-16 sm:h-16 rounded-xl sm:rounded-2xl bg-green-500/10 border border-green-500/20">
                    <i class="fa-solid fa-key text-green-400 text-lg sm:text-2xl"></i>
                </div>
            </div>

            <div class="flex items-center justify-center gap-1.5 sm:gap-2 mb-3 sm:mb-4">
                <div
                    class="flex items-center justify-center w-6 h-6 sm:w-7 sm:h-7 bg-blue-600 rounded-md sm:rounded-lg shadow-lg shadow-blue-600/30">
                    <i class="fa-solid fa-bus text-white text-[9px] sm:text-[10px]"></i>
                </div>
                <span class="text-sm sm:text-base font-bold tracking-tight text-white">
                    Smart<span class="text-blue-400">Commute</span>
                </span>
            </div>

            <h2 class="text-xl sm:text-2xl font-extrabold tracking-tight">Reset password</h2>
            <p class="mt-1.5 sm:mt-2 text-[11px] sm:text-xs text-gray-400 leading-relaxed px-2 sm:px-3">
                Enter your new credentials below to regain access to your account.
            </p>

            <div class="mt-4 sm:mt-5 mx-auto max-w-[260px] sm:max-w-[280px]">
                <div
                    class="flex items-center gap-2 sm:gap-2.5 px-3 sm:px-3.5 py-2 sm:py-2.5 rounded-lg sm:rounded-xl bg-white/[0.03] border border-white/[0.06]">
                    <div
                        class="w-6 h-6 sm:w-7 sm:h-7 rounded-md sm:rounded-lg bg-white/[0.05] flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-envelope text-[9px] sm:text-[10px] text-gray-500"></i>
                    </div>
                    <div class="min-w-0 text-left">
                        <p
                            class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.12em] sm:tracking-[0.15em] text-gray-600">
                            Resetting for</p>
                        <p class="text-[10px] sm:text-[11px] font-semibold text-gray-300 truncate">{{ $email }}
                        </p>
                    </div>
                    <i
                        class="fa-solid fa-circle-check text-[10px] sm:text-[11px] text-green-500/60 shrink-0 ml-auto"></i>
                </div>
            </div>
        </div>

        <form action="{{ route('password.update') }}" method="POST" class="space-y-3 sm:space-y-4">
            @csrf

            <input type="email" name="email" placeholder="you@example.com" value="{{ $email }}"
                class="hidden">

            @if ($errors->any() && !$errors->has('email') && !$errors->has('password') && !$errors->has('confirm-password'))
                <div class="flex items-center gap-1.5 sm:gap-2">
                    <i class="fa-solid fa-circle-exclamation text-red-400 text-[9px] sm:text-[10px]"></i>
                    @foreach ($errors->all() as $message)
                        <span class="text-red-400 text-[10px] sm:text-[11px]">{{ $message }}</span>
                    @endforeach
                </div>
            @endif

            <!-- New Password -->
            <div>
                <label
                    class="block mb-1 sm:mb-1.5 ml-0.5 sm:ml-1 font-semibold tracking-widest uppercase text-[9px] sm:text-[10px] text-gray-400">New
                    Password</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-3 sm:left-3.5 flex items-center pointer-events-none">
                        <i class="fa-solid fa-lock text-[10px] sm:text-xs text-white/20"></i>
                    </div>
                    <input type="password" name="password" id="password" placeholder="••••••••"
                        value="{{ old('password') }}" oninput="checkStrength(this.value)"
                        class="input-field py-2.5 sm:py-3 pl-9 sm:pl-10 pr-9 sm:pr-10 w-full text-xs sm:text-sm rounded-lg sm:rounded-xl focus:outline-none">
                    <button type="button" onclick="togglePassword('password', 'eye-icon-1')"
                        class="absolute inset-y-0 right-3 sm:right-3.5 flex items-center text-white/30 hover:text-white/70 transition">
                        <i id="eye-icon-1" class="fa-solid fa-eye text-[10px] sm:text-xs"></i>
                    </button>
                </div>
                <div class="mt-1.5 sm:mt-2 flex gap-1 sm:gap-1.5 px-0.5 sm:px-1">
                    <div class="strength-bar flex-1 bg-white/5" id="str-1"></div>
                    <div class="strength-bar flex-1 bg-white/5" id="str-2"></div>
                    <div class="strength-bar flex-1 bg-white/5" id="str-3"></div>
                    <div class="strength-bar flex-1 bg-white/5" id="str-4"></div>
                </div>
                <p class="mt-1 ml-0.5 sm:ml-1 text-[9px] sm:text-[10px] text-gray-600" id="strength-text">Minimum 8
                    characters</p>
                @if ($errors->has('password'))
                    <div class="mt-1.5 sm:mt-2 flex items-center gap-1.5 sm:gap-2">
                        <i class="fa-solid fa-circle-exclamation text-red-400 text-[9px] sm:text-[10px]"></i>
                        @foreach ($errors->get('password') as $message)
                            <span class="text-red-400 text-[10px] sm:text-[11px]">{{ $message }}</span>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Confirm Password -->
            <div>
                <label
                    class="block mb-1 sm:mb-1.5 ml-0.5 sm:ml-1 font-semibold tracking-widest uppercase text-[9px] sm:text-[10px] text-gray-400">Confirm
                    Password</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-3 sm:left-3.5 flex items-center pointer-events-none">
                        <i class="fa-solid fa-lock text-[10px] sm:text-xs text-white/20"></i>
                    </div>
                    <input type="password" name="confirm-password" id="confirm-password" placeholder="••••••••"
                        oninput="checkMatch()"
                        class="input-field py-2.5 sm:py-3 pl-9 sm:pl-10 pr-9 sm:pr-10 w-full text-xs sm:text-sm rounded-lg sm:rounded-xl focus:outline-none">
                    <button type="button" onclick="togglePassword('confirm-password', 'eye-icon-2')"
                        class="absolute inset-y-0 right-3 sm:right-3.5 flex items-center text-white/30 hover:text-white/70 transition">
                        <i id="eye-icon-2" class="fa-solid fa-eye text-[10px] sm:text-xs"></i>
                    </button>
                </div>
                <div class="mt-1 ml-0.5 sm:ml-1 flex items-center gap-1 sm:gap-1.5 hidden" id="match-indicator">
                    <i class="text-[8px] sm:text-[9px]" id="match-icon"></i>
                    <span class="text-[9px] sm:text-[10px]" id="match-text"></span>
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

            <input type="text" name="token" value="{{ $token }}" class="hidden">

            <button type="submit"
                class="btn-primary py-2.5 sm:py-3.5 mt-1 sm:mt-2 w-full text-[10px] sm:text-xs font-bold tracking-widest uppercase rounded-lg sm:rounded-xl flex items-center justify-center gap-1.5 sm:gap-2">
                <i class="fa-solid fa-rotate-right text-[9px] sm:text-[10px]"></i>
                Reset Password
            </button>
        </form>

        <div class="pt-4 sm:pt-6 mt-5 sm:mt-7 text-center border-t border-white/5">
            <p class="text-[11px] sm:text-xs text-gray-500">
                Remember your password?
                <a href="{{ route('login') }}" class="font-semibold text-white hover:text-blue-400 transition">Back
                    to login</a>
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

        function checkStrength(password) {
            const bars = [
                document.getElementById('str-1'),
                document.getElementById('str-2'),
                document.getElementById('str-3'),
                document.getElementById('str-4')
            ];
            const text = document.getElementById('strength-text');

            let score = 0;
            if (password.length >= 8) score++;
            if (password.length >= 12) score++;
            if (/[A-Z]/.test(password) && /[a-z]/.test(password)) score++;
            if (/[0-9]/.test(password) && /[^A-Za-z0-9]/.test(password)) score++;

            const colors = ['bg-red-500', 'bg-orange-500', 'bg-yellow-500', 'bg-green-500'];
            const labels = ['Weak', 'Fair', 'Good', 'Strong'];
            const textColors = ['text-red-400', 'text-orange-400', 'text-yellow-400', 'text-green-400'];

            bars.forEach((bar, i) => {
                bar.className = 'strength-bar flex-1';
                if (password.length === 0) {
                    bar.classList.add('bg-white/5');
                } else if (i < score) {
                    bar.classList.add(colors[score - 1]);
                } else {
                    bar.classList.add('bg-white/5');
                }
            });

            if (password.length === 0) {
                text.className = 'mt-1 ml-0.5 sm:ml-1 text-[9px] sm:text-[10px] text-gray-600';
                text.textContent = 'Minimum 8 characters';
            } else {
                text.className = 'mt-1 ml-0.5 sm:ml-1 text-[9px] sm:text-[10px] ' + (textColors[score - 1] ||
                    'text-gray-600');
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
                icon.className = 'text-[8px] sm:text-[9px] fa-solid fa-circle-check text-green-4003';
                text.className = 'text-[9px] sm:text-[10px] text-green-400';
                text.textContent = 'Passwords match';
            } else {
                icon.className = 'text-[8px] sm:text-[9px] fa-solid fa-circle-xmark text-red-400';
                text.className = 'text-[9px] sm:text-[10px] text-red-400';
                text.textContent = 'Passwords do not match';
            }
        }
    </script>
</body>

</html>
