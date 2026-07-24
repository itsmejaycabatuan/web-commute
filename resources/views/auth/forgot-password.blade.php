<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - SmartCommute</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: { extend: { fontFamily: { sans: ['Inter', 'sans-serif'] } } }
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

        .input-field::placeholder { color: rgba(255, 255, 255, 0.2); }

        @keyframes card-enter {
            from { opacity: 0; transform: translateY(30px) scale(0.96); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .card-animate { animation: card-enter 0.7s cubic-bezier(0.16, 1, 0.3, 1) 0.15s both; }

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

        .btn-primary:active { transform: scale(0.98) translateY(0); }

        .error-inline {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 0.75rem;
            padding: 0.5rem 0.75rem;
        }
    </style>
</head>

<body class="flex relative justify-center items-center p-4 sm:p-6 min-h-screen login-bg font-sans text-white overflow-x-hidden">

    <!-- Decorative orbs -->
    <div class="absolute top-1/4 right-1/3 w-72 h-72 bg-amber-500/8 rounded-full blur-[100px] pointer-events-none"></div>
    <div class="absolute bottom-1/3 left-1/4 w-56 h-56 bg-blue-500/10 rounded-full blur-[80px] pointer-events-none"></div>

    <!-- Back button -->
    <a href="{{ route('login') }}"
        class="flex absolute top-5 left-5 sm:top-8 sm:left-8 items-center gap-2.5 transition group z-10">
        <div class="flex justify-center items-center w-10 h-10 rounded-xl border border-white/10 bg-white/5 backdrop-blur-md group-hover:bg-white/10 group-hover:border-white/20 transition-all duration-300">
            <i class="text-sm fa-solid fa-arrow-left text-white/60 group-hover:text-white transition"></i>
        </div>
        <span class="hidden sm:inline text-[10px] font-bold tracking-widest uppercase text-white/50 group-hover:text-white/80 transition">Back to Login</span>
    </a>

    <!-- Card -->
    <div class="card-animate glass-card p-7 sm:p-8 w-full max-w-[400px] rounded-[2rem] shadow-2xl shadow-black/30">

        <div class="mb-8 text-center">
            <div class="flex flex-col items-center justify-center mb-5">
                <div class="flex items-center justify-center w-14 h-14 rounded-2xl bg-amber-500/10 border border-amber-500/20 mb-4">
                    <i class="fa-solid fa-key text-amber-400 text-lg"></i>
                </div>
                <span class="text-xl font-bold tracking-tight text-white mb-1">
                    Smart<span class="text-blue-400">Commute</span>
                </span>
            </div>
            <h2 class="text-2xl font-extrabold tracking-tight">Forgot password?</h2>
            <p class="mt-2 text-xs text-gray-400 leading-relaxed px-2">
                No worries. Enter your email address and we'll send you a link to reset your password.
            </p>
        </div>

        @if (session('status'))
            <div class="mb-5 flex items-center gap-2.5 bg-emerald-500/10 border border-emerald-500/20 rounded-xl p-3.5">
                <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-emerald-500/20 flex items-center justify-center">
                    <i class="fa-solid fa-circle-check text-emerald-400 text-sm"></i>
                </div>
                <p class="text-emerald-300 text-xs leading-relaxed">{{ session('status') }}</p>
            </div>
        @endif

        <form action="{{ route('password.email') }}" method="POST" class="space-y-4">
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

            <button type="submit"
                class="btn-primary py-3.5 mt-2 w-full text-xs font-bold tracking-widest uppercase rounded-xl">
                Send Reset Link
            </button>
        </form>

        <div class="pt-6 mt-7 text-center border-t border-white/5">
            <p class="text-xs text-gray-500">
                Remember your password?
                <a href="{{ route('login') }}" class="font-semibold text-white hover:text-blue-400 transition">Back to login</a>
            </p>
        </div>
    </div>
</body>

</html>
