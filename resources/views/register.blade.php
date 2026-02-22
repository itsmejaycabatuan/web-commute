<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - SmartCommute</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .register-bg {
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)),
                url("{{ asset('images/newbg.jpg') }}");
            background-size: cover;
            background-position: center;
        }
    </style>
</head>

<body class="flex relative justify-center items-center p-6 min-h-screen register-bg">

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
                <h2 class="text-2xl font-bold tracking-tight">Create Account</h2>
                <p class="mt-1 text-xs opacity-60">Optimize your commute today</p>
            </div>

        <form action="{{ route('users.register') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block mb-1 ml-1 font-bold tracking-widest uppercase opacity-50 text-[10px]">Email</label>
                <input type="email" name="email" placeholder="email@example.com" value="{{ old('email') }}"
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
                    <input type="password" name="password" id="password" placeholder="••••••••" value="{{ old('password') }}"
                        class="py-2.5 px-4 w-full text-sm rounded-xl border focus:ring-2 focus:outline-none bg-white/5 border-white/10 focus:ring-white/30 pr-10">
                    <button type="button" onclick="togglePassword('password', 'eye-icon-1')" class="absolute inset-y-0 right-3 flex items-center text-white/40 hover:text-white transition">
                        <i id="eye-icon-1" class="fa-solid fa-eye text-xs"></i>
                    </button>
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

            <div>
                <label class="block mb-1 ml-1 font-bold tracking-widest uppercase opacity-50 text-[10px]">Confirm Password</label>
                <div class="relative">
                    <input type="password" name="confirm-password" id="confirm-password" placeholder="••••••••"
                        class="py-2.5 px-4 w-full text-sm rounded-xl border focus:ring-2 focus:outline-none bg-white/5 border-white/10 focus:ring-white/30 pr-10">
                    <button type="button" onclick="togglePassword('confirm-password', 'eye-icon-2')" class="absolute inset-y-0 right-3 flex items-center text-white/40 hover:text-white transition">
                        <i id="eye-icon-2" class="fa-solid fa-eye text-xs"></i>
                    </button>
                </div>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->get('confirm-password') as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="flex items-start pt-1 space-x-2">
                <input type="checkbox" id="terms" name="terms"
                    class="mt-0.5 bg-transparent rounded focus:ring-0 border-white/20">
                <label for="terms" class="leading-tight opacity-60 text-[10px]">I agree to the Terms of Service and
                    Privacy Policy.</label>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->get('terms') as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <button type="submit"
                class="py-3 mt-4 w-full text-xs font-bold tracking-widest text-black uppercase bg-white rounded-xl transition-all hover:bg-gray-200 active:scale-95">
                Sign up
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
    </script>

</body>

</html>
