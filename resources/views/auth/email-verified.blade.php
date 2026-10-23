<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verified - SmartCommute</title>
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

        @media (prefers-reduced-motion: reduce) {
            .card-animate {
                animation: none;
            }
        }
    </style>
</head>

<body
    class="flex flex-col relative items-center p-3 sm:p-4 md:p-6 min-h-[100svh] login-bg font-sans text-white overflow-x-hidden">

    <div
        class="card-animate my-auto glass-card p-5 sm:p-7 md:p-10 w-full max-w-[340px] sm:max-w-[420px] rounded-2xl sm:rounded-[2rem] shadow-2xl shadow-black/30 text-center">

        <div class="flex justify-center mb-4 sm:mb-6">
            <div
                class="w-14 h-14 sm:w-20 sm:h-20 rounded-xl sm:rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center">
                <i class="fa-solid fa-circle-check text-emerald-400 text-2xl sm:text-3xl"></i>
            </div>
        </div>

        <div class="flex items-center justify-center gap-1.5 sm:gap-2 mb-1.5 sm:mb-2">
            <div
                class="flex items-center justify-center w-6 h-6 sm:w-7 sm:h-7 bg-blue-600 rounded-md sm:rounded-lg shadow-lg shadow-blue-600/30">
                <i class="fa-solid fa-bus text-white text-[9px] sm:text-[10px]"></i>
            </div>
            <span class="text-sm sm:text-base font-bold tracking-tight text-white">
                Smart<span class="text-blue-400">Commute</span>
            </span>
        </div>

        <h1 class="text-xl sm:text-2xl font-extrabold tracking-tight mt-3 sm:mt-4 mb-1.5 sm:mb-2">
            {{ $alreadyVerified ? 'Email already verified' : 'Email verified' }}
        </h1>
        <p class="text-[11px] sm:text-xs text-gray-400 leading-relaxed px-1 sm:px-2">
            {{ $alreadyVerified ? 'This email address was already verified.' : 'Thank you! Your email address has been verified.' }}
            You can now close this page.
        </p>

        <div class="mt-5 sm:mt-6 pt-4 sm:pt-5 border-t border-white/5">
            <a href="{{ auth()->check() ? route('map') : route('login') }}"
                class="inline-block w-full py-2.5 sm:py-3.5 text-[10px] sm:text-xs font-bold tracking-widest uppercase rounded-lg sm:rounded-xl bg-white text-black hover:bg-gray-200 transition">
                {{ auth()->check() ? 'Continue to SmartCommute' : 'Go to sign in' }}
            </a>
        </div>
    </div>
</body>

</html>
