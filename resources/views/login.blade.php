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

    @if (session('driver_pending'))
        <div id="driver-pending-modal" role="dialog" aria-modal="true"
            class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/75">
            <div class="w-full max-w-sm p-8 text-center text-gray-900 bg-white rounded-2xl shadow-2xl">
                <div class="flex justify-center mb-4">
                    <div class="flex items-center justify-center w-14 h-14 rounded-full bg-amber-100">
                        <i class="text-2xl text-amber-600 fa-solid fa-clock"></i>
                    </div>
                </div>
                <h3 class="text-lg font-bold tracking-tight">Your account is pending!</h3>
                <p class="mt-2 text-sm text-gray-600">An administrator must approve your driver account before you can sign in.</p>
                <button type="button" onclick="document.getElementById('driver-pending-modal').remove()"
                    class="py-3 mt-6 w-full text-xs font-bold tracking-widest text-white uppercase bg-blue-600 rounded-xl transition hover:bg-blue-500">
                    OK
                </button>
            </div>
        </div>
    @endif

    @if (session('driver_rejected'))
        <div id="driver-rejected-modal" role="dialog" aria-modal="true"
            class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/75">
            <div class="w-full max-w-sm p-8 text-center text-gray-900 bg-white rounded-2xl shadow-2xl">
                <div class="flex justify-center mb-4">
                    <div class="flex items-center justify-center w-14 h-14 rounded-full bg-red-100">
                        <i class="text-2xl text-red-600 fa-solid fa-ban"></i>
                    </div>
                </div>
                <h3 class="text-lg font-bold tracking-tight">Your account submission was rejected</h3>
                <p class="mt-2 text-sm text-gray-600">Your driver application was not approved. You cannot sign in with this account. If you believe this is a mistake, contact support.</p>
                <button type="button" onclick="document.getElementById('driver-rejected-modal').remove()"
                    class="py-3 mt-6 w-full text-xs font-bold tracking-widest text-white uppercase bg-blue-600 rounded-xl transition hover:bg-blue-500">
                    OK
                </button>
            </div>
        </div>
    @endif

    @if (session('success'))
        <div class="fixed top-6 left-1/2 z-[90] px-4 py-3 max-w-md text-sm text-center text-white -translate-x-1/2 bg-emerald-600/95 rounded-xl border border-emerald-500/30 shadow-lg"
            id="login-flash-success">
            {{ session('success') }}
        </div>
    @endif

    <a href="{{ url('/') }}"
        class="flex absolute top-8 left-8 items-center space-x-2 transition hover:text-white text-white/70 group">
        <div
            class="flex justify-center items-center w-10 h-10 rounded-full border border-white/20 bg-white/10 backdrop-blur-md group-hover:bg-white/20">
            <i class="text-sm fa-solid fa-arrow-left"></i>
        </div>
        <span class="text-xs font-bold tracking-widest uppercase">Back to Home</span>
    </a>

    <div
        class="p-8 w-full max-w-sm text-white rounded-3xl border shadow-2xl bg-white/10 backdrop-blur-xl border-white/20">

        <div class="mb-8 text-center">
    <div class="flex flex-col items-center justify-center mb-4">
        <div class="flex items-center justify-center w-12 h-12 bg-blue-600 rounded-xl shadow-lg shadow-blue-600/20 mb-3">
            <i class="fa-solid fa-bus text-white text-xl"></i>
        </div>
        <span class="text-2xl font-bold tracking-wider italic text-white">
            Smart<span class="text-blue-500">Commute</span>
        </span>
    </div>
    <h2 class="text-2xl font-bold tracking-tight">Login</h2>
    <p class="mt-1 text-xs opacity-60">Optimize your commute today</p>
</div>

        <form action="{{ route('users.login') }}" method="POST" class="space-y-5">
            @csrf
            <div>
                <label class="block mb-1 ml-1 font-bold tracking-widest uppercase opacity-50 text-[10px]">Email
                    Addess</label>
                <input type="text" placeholder="Enter Email Address" name="email" value="{{ old('email') }}"
                    class="py-2.5 px-4 w-full text-sm rounded-xl border focus:ring-2 focus:outline-none bg-white/5 border-white/10 focus:ring-white/30">
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->get('email') as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div>
                <label class="block mb-1 ml-1 font-bold tracking-widest uppercase opacity-50 text-[10px]">Password</label>

                <div class="relative">
                    <input type="password" id="password" placeholder="••••••••" name="password" value="{{ old('password') }}"
                        class="py-2.5 px-4 w-full text-sm rounded-xl border transition focus:ring-2 focus:outline-none bg-white/5 border-white/10 focus:ring-white/30 pr-10">

                    <button type="button" onclick="togglePassword()" class="absolute inset-y-0 right-3 flex items-center text-white/40 hover:text-white transition">
                        <i id="eye-icon" class="fa-solid fa-eye text-xs"></i>
                    </button>
                </div>

                <div class="flex justify-end mt-1 mr-1">
                    <a href="{{ route('password.request') }}"
                        class="opacity-40 transition hover:opacity-100 text-[10px]">Forgot Password?</a>
                </div>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->get('password') as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->get('credentials') as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="flex items-center space-x-2">
                <input type="checkbox" id="remember"
                    class="w-3 h-3 rounded cursor-pointer focus:ring-0 bg-white/10 border-white/20">
                <label for="remember" class="opacity-60 cursor-pointer text-[10px]">Remember me for 30 days</label>
            </div>

            <button type="submit"
                class="py-3 mt-2 w-full text-xs font-bold tracking-widest text-black uppercase bg-white rounded-xl transition-all hover:bg-gray-200 active:scale-95">
                Log In
            </button>
        </form>

        <div class="pt-6 mt-8 text-center border-t border-white/10">
            <p class="text-xs opacity-60">
                Don't have an account?
                <a href="{{ url('/register') }}" class="font-bold text-white hover:underline">Register now</a>
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
    </script>

</body>

</html>
