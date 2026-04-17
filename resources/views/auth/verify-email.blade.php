<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SmartCommute</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .login-bg {
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)),
                url("{{ asset('images/newbg.jpg') }}");
            background-size: cover;
            background-position: center;
        }
    </style>
</head>

<body class="flex relative justify-center items-center p-6 min-h-screen login-bg">

    <form action="{{ route('users.logout') }}" method="POST">
        @csrf
        <div class="flex absolute top-8 left-8 items-center space-x-2 transition hover:text-white text-white/70 group">

            <button type="submit" class="text-xs font-bold tracking-widest uppercase flex items-center gap-2">
                <div
                    class="flex justify-center items-center w-10 h-10 rounded-full border border-white/20 bg-white/10 backdrop-blur-md group-hover:bg-white/20">
                    <i class="text-sm fa-solid fa-arrow-left"></i>
                </div>
                Logout
            </button>
        </div>
    </form>


    <form action="{{ route('verification.send') }}" method="POST">
        @csrf
        <div
            class="p-8 w-full max-w-sm text-white rounded-3xl border shadow-2xl bg-white/10 backdrop-blur-xl border-white/20">

            <div class="mb-8 text-center">
                <div class="flex items-center gap-3 px-2 mb-10 overflow-hidden whitespace-nowrap">
                    <div
                        class="min-w-[40px] h-10 bg-blue-600 rounded-xl flex items-center justify-center shadow-lg shadow-blue-600/20">
                        <i class="fa-solid fa-bus text-white"></i>
                    </div>
                    <span class="text-2xl font-bold tracking-wider italic">SmartCommute</span>
                </div>
                <h2 class="text-2xl font-bold tracking-tight">Email Verification</h2>
                <p class="mt-1 text-xs opacity-60">We have sent you an activation link in your inbox.</p>
            </div>
            @if (session('message'))
                <div class="mb-2 text-center">
                    <p class="text-green-400">{{ session('message') }}</p>
                </div>
            @endif

            <button type="submit"
                class="py-3 mt-2 w-full text-xs font-bold tracking-widest text-black uppercase bg-white rounded-xl transition-all hover:bg-gray-200 active:scale-95">
                Resend Verification Link
            </button>
        </div>
    </form>

</body>

</html>