<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - SmartCommute</title>
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

        @keyframes card-enter {
            from { opacity: 0; transform: translateY(30px) scale(0.96); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .card-animate { animation: card-enter 0.7s cubic-bezier(0.16, 1, 0.3, 1) 0.15s both; }

        @keyframes envelope-float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }

        .envelope-animate { animation: envelope-float 3s ease-in-out infinite; }

        @keyframes pulse-ring {
            0% { transform: scale(1); opacity: 0.4; }
            100% { transform: scale(1.5); opacity: 0; }
        }

        .pulse-ring::before {
            content: '';
            position: absolute;
            inset: -8px;
            border-radius: inherit;
            border: 2px solid rgba(59, 130, 246, 0.3);
            animation: pulse-ring 2s ease-out infinite;
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

        .btn-primary:active { transform: scale(0.98) translateY(0); }

        .btn-ghost {
            background: transparent;
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.6);
            transition: all 0.3s ease;
        }

        .btn-ghost:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .btn-ghost:active { transform: scale(0.98); }
    </style>
</head>

<body class="flex relative justify-center items-center p-4 sm:p-6 min-h-screen login-bg font-sans text-white overflow-x-hidden">

    <!-- Decorative orbs -->
    <div class="absolute top-1/4 left-1/3 w-72 h-72 bg-blue-500/10 rounded-full blur-[100px] pointer-events-none"></div>
    <div class="absolute bottom-1/3 right-1/4 w-56 h-56 bg-cyan-500/8 rounded-full blur-[80px] pointer-events-none"></div>

    <!-- Logout button (top-left) -->
    <form action="{{ route('users.logout') }}" method="POST" class="absolute top-5 left-5 sm:top-8 sm:left-8 z-10">
        @csrf
        <button type="submit" class="flex items-center gap-2.5 group transition">
            <div class="flex justify-center items-center w-10 h-10 rounded-xl border border-white/10 bg-white/5 backdrop-blur-md group-hover:bg-white/10 group-hover:border-white/20 transition-all duration-300">
                <i class="text-sm fa-solid fa-arrow-right-from-bracket text-white/60 group-hover:text-white transition"></i>
            </div>
            <span class="hidden sm:inline text-[10px] font-bold tracking-widest uppercase text-white/50 group-hover:text-white/80 transition">Logout</span>
        </button>
    </form>

    <!-- Card -->
    <div class="card-animate glass-card p-7 sm:p-10 w-full max-w-[420px] rounded-[2rem] shadow-2xl shadow-black/30 text-center">

        <!-- Envelope icon with pulse -->
        <div class="flex justify-center mb-6">
            <div class="relative envelope-animate">
                <div class="pulse-ring w-20 h-20 rounded-2xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center">
                    <i class="fa-solid fa-envelope-open-text text-blue-400 text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-center gap-2 mb-2">
            <div class="flex items-center justify-center w-7 h-7 bg-blue-600 rounded-lg shadow-lg shadow-blue-600/30">
                <i class="fa-solid fa-bus text-white text-[10px]"></i>
            </div>
            <span class="text-base font-bold tracking-tight text-white">
                Smart<span class="text-blue-400">Commute</span>
            </span>
        </div>

        <h2 class="text-2xl font-extrabold tracking-tight mt-4 mb-2">Verify your email</h2>
        <p class="text-xs text-gray-400 leading-relaxed px-2 mb-2">
            We've sent an activation link to your inbox. Please check your email and click the link to verify your account.
        </p>

        @if (session('message'))
            <div class="my-5 flex items-center justify-center gap-2.5 bg-emerald-500/10 border border-emerald-500/20 rounded-xl p-3.5">
                <i class="fa-solid fa-circle-check text-emerald-400 text-sm"></i>
                <p class="text-emerald-300 text-xs leading-relaxed">{{ session('message') }}</p>
            </div>
        @endif

        <form action="{{ route('verification.send') }}" method="POST" class="space-y-3 mt-6">
            @csrf
            <button type="submit"
                class="btn-primary py-3.5 w-full text-xs font-bold tracking-widest uppercase rounded-xl">
                Resend Verification Link
            </button>
        </form>

        <div class="mt-4 pt-5 border-t border-white/5">
            <p class="text-[11px] text-gray-600">
                Didn't receive the email? Check your spam folder or try again in a few minutes.
            </p>
        </div>
    </div>
</body>

</html>
