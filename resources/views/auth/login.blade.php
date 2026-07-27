<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SmartCommute</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] }
                }
            }
        }
    </script>
    <style>
        .login-bg {
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
            from { opacity: 0; transform: translateY(30px) scale(0.96); }
            to { opacity: 1; transform: translateY(0) scale(1); }
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

        @keyframes modal-enter {
            from { opacity: 0; transform: scale(0.9) translateY(20px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        .modal-animate {
            animation: modal-enter 0.4s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        @keyframes flash-in {
            from { opacity: 0; transform: translate(-50%, -20px); }
            to { opacity: 1; transform: translate(-50%, 0); }
        }

        .flash-animate {
            animation: flash-in 0.5s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        .error-inline {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 0.75rem;
            padding: 0.5rem 0.75rem;
        }
    </style>
</head>

<body class="flex relative justify-center items-center p-4 sm:p-6 min-h-screen login-bg font-sans text-white overflow-x-hidden">

    @include('components.flash');

    @if (session('driver_pending'))
        <div id="driver-pending-modal" role="dialog" aria-modal="true"
            class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
            <div class="modal-animate w-full max-w-sm p-8 text-center text-gray-900 bg-white rounded-3xl shadow-2xl">
                <div class="flex justify-center mb-5">
                    <div class="flex items-center justify-center w-16 h-16 rounded-2xl bg-amber-50">
                        <i class="text-3xl text-amber-500 fa-solid fa-clock"></i>
                    </div>
                </div>
                <h3 class="text-lg font-bold tracking-tight">Your account is pending</h3>
                <p class="mt-2 text-sm text-gray-500 leading-relaxed">An administrator must approve your driver account before you can sign in.</p>
                <button type="button" onclick="document.getElementById('driver-pending-modal').remove()"
                    class="py-3 mt-6 w-full text-xs font-bold tracking-widest text-white uppercase bg-gray-900 rounded-xl transition hover:bg-gray-800 active:scale-[0.98]">
                    OK
                </button>
            </div>
        </div>
    @endif

    @if (session('driver_rejected'))
        <div id="driver-rejected-modal" role="dialog" aria-modal="true"
            class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
            <div class="modal-animate w-full max-w-sm p-8 text-center text-gray-900 bg-white rounded-3xl shadow-2xl">
                <div class="flex justify-center mb-5">
                    <div class="flex items-center justify-center w-16 h-16 rounded-2xl bg-red-50">
                        <i class="text-3xl text-red-500 fa-solid fa-ban"></i>
                    </div>
                </div>
                <h3 class="text-lg font-bold tracking-tight">Account submission rejected</h3>
                <p class="mt-2 text-sm text-gray-500 leading-relaxed">Your driver application was not approved. If you believe this is a mistake, contact support.</p>
                <button type="button" onclick="document.getElementById('driver-rejected-modal').remove()"
                    class="py-3 mt-6 w-full text-xs font-bold tracking-widest text-white uppercase bg-gray-900 rounded-xl transition hover:bg-gray-800 active:scale-[0.98]">
                    OK
                </button>
            </div>
        </div>
    @endif

    <!-- Decorative orbs -->
    <div class="absolute top-1/4 left-1/4 w-72 h-72 bg-blue-500/10 rounded-full blur-[100px] pointer-events-none"></div>
    <div class="absolute bottom-1/4 right-1/4 w-56 h-56 bg-purple-500/10 rounded-full blur-[80px] pointer-events-none"></div>

    <!-- Back button -->
    <a href="{{ url('/') }}"
        class="flex absolute top-5 left-5 sm:top-8 sm:left-8 items-center gap-2.5 transition group z-10">
        <div
            class="flex justify-center items-center w-10 h-10 rounded-xl border border-white/10 bg-white/5 backdrop-blur-md group-hover:bg-white/10 group-hover:border-white/20 transition-all duration-300">
            <i class="text-sm fa-solid fa-arrow-left text-white/60 group-hover:text-white transition"></i>
        </div>
        <span class="hidden sm:inline text-[10px] font-bold tracking-widest uppercase text-white/50 group-hover:text-white/80 transition">Back to Home</span>
    </a>

    <!-- Login Card -->
    <div class="card-animate glass-card p-7 sm:p-8 w-full max-w-[400px] rounded-[2rem] shadow-2xl shadow-black/30">

        <div class="mb-8 text-center">
            <div class="flex flex-col items-center justify-center mb-5">
                <div class="flex items-center justify-center w-12 h-12 bg-blue-600 rounded-xl shadow-lg shadow-blue-600/30 mb-3">
                    <i class="fa-solid fa-bus text-white text-lg"></i>
                </div>
                <span class="text-xl font-bold tracking-tight text-white">
                    Smart<span class="text-blue-400">Commute</span>
                </span>
            </div>
            <h2 class="text-2xl font-extrabold tracking-tight">Welcome back</h2>
            <p class="mt-1.5 text-xs text-gray-400">Sign in to continue to your dashboard</p>
        </div>

        <form action="{{ route('users.login') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label class="block mb-1.5 ml-1 font-semibold tracking-widest uppercase text-[10px] text-gray-400">Email Address</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-3.5 flex items-center pointer-events-none">
                        <i class="fa-solid fa-envelope text-xs text-white/20"></i>
                    </div>
                    <input type="text" placeholder="you@example.com" name="email" value="{{ old('email') }}"
                        class="input-field py-3 pl-10 pr-4 w-full text-sm rounded-xl focus:outline-none">
                </div>
                @if ($errors->has('email'))
                    <div class="error-inline mt-2 flex items-center gap-2">
                        <i class="fa-solid fa-circle-exclamation text-red-400 text-[10px]"></i>
                        @foreach ($errors->get('email') as $message)
                            <span class="text-red-400 text-[11px]">{{ $message }}</span>
                        @endforeach
                    </div>
                @endif
            </div>

            <div>
                <label class="block mb-1.5 ml-1 font-semibold tracking-widest uppercase text-[10px] text-gray-400">Password</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-3.5 flex items-center pointer-events-none">
                        <i class="fa-solid fa-lock text-xs text-white/20"></i>
                    </div>
                    <input type="password" id="password" placeholder="••••••••" name="password"
                        value="{{ old('password') }}"
                        class="input-field py-3 pl-10 pr-10 w-full text-sm rounded-xl focus:outline-none">
                    <button type="button" onclick="togglePassword()"
                        class="absolute inset-y-0 right-3.5 flex items-center text-white/30 hover:text-white/70 transition">
                        <i id="eye-icon" class="fa-solid fa-eye text-xs"></i>
                    </button>
                </div>
                @if ($errors->has('password'))
                    <div class="error-inline mt-2 flex items-center gap-2">
                        <i class="fa-solid fa-circle-exclamation text-red-400 text-[10px]"></i>
                        @foreach ($errors->get('password') as $message)
                            <span class="text-red-400 text-[11px]">{{ $message }}</span>
                        @endforeach
                    </div>
                @endif
            </div>

            @if ($errors->has('credentials'))
                <div class="error-inline flex items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation text-red-400 text-[10px]"></i>
                    @foreach ($errors->get('credentials') as $message)
                        <span class="text-red-400 text-[11px]">{{ $message }}</span>
                    @endforeach
                </div>
            @endif

            <div class="flex items-center justify-between pt-1">
                <label class="flex items-center gap-2 cursor-pointer group">
                    <input type="checkbox" id="remember" name="remember"
                        class="w-3.5 h-3.5 rounded cursor-pointer bg-white/5 border-white/20 text-blue-600 focus:ring-blue-500/30 focus:ring-offset-0">
                    <span class="text-[11px] text-gray-400 group-hover:text-gray-300 transition">Remember me</span>
                </label>
                <a href="{{ route('password.request') }}"
                    class="text-[11px] text-blue-400 hover:text-blue-300 transition font-medium">Forgot password?</a>
            </div>

            <button type="submit"
                class="btn-primary py-3.5 mt-2 w-full text-xs font-bold tracking-widest uppercase rounded-xl">
                Log In
            </button>
        </form>

        <div class="pt-6 mt-7 text-center border-t border-white/5">
            <p class="text-xs text-gray-500">
                Don't have an account?
                <a href="{{ url('/register') }}" class="font-semibold text-white hover:text-blue-400 transition">Create one</a>
            </p>
        </div>
    </div>

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

        // Auto-dismiss flash
        const flash = document.getElementById('login-flash-success');
        if (flash) {
            setTimeout(() => {
                flash.style.transition = 'all 0.4s ease';
                flash.style.opacity = '0';
                flash.style.transform = 'translate(-50%, -20px)';
                setTimeout(() => flash.remove(), 400);
            }, 3500);
        }
    </script>
</body>

</html>
