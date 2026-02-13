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

    <a href="{{ route('login') }}"
        class="flex absolute top-8 left-8 items-center space-x-2 transition hover:text-white text-white/70 group">
        <div
            class="flex justify-center items-center w-10 h-10 rounded-full border border-white/20 bg-white/10 backdrop-blur-md group-hover:bg-white/20">
            <i class="text-sm fa-solid fa-arrow-left"></i>
        </div>
        <span class="text-xs font-bold tracking-widest uppercase">Back to Login</span>
    </a>

    <div
        class="p-8 w-full max-w-sm text-white rounded-3xl border shadow-2xl bg-white/10 backdrop-blur-xl border-white/20">

        <div class="mb-8 text-center">
            <div class="flex justify-center items-center mb-4">
                <div class="flex justify-center items-center w-10 h-10 rounded-full border-2 border-white">
                    <div class="w-5 h-5 bg-white rounded-full"></div>
                </div>
                <span class="ml-3 text-2xl italic font-bold tracking-wider">SmartCommute</span>
            </div>
            <h2 class="text-2xl font-bold tracking-tight">Reset Password</h2>
            <p class="mt-1 text-xs opacity-60">Please enter your email address to reset your password</p>
        </div>

        <form action="{{ route('password.email') }}" method="POST" class="space-y-5">
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

            <button type="submit"
                class="py-3 mt-2 w-full text-xs font-bold tracking-widest text-black uppercase bg-white rounded-xl transition-all hover:bg-gray-200 active:scale-95">
                Send Reset Password Link
            </button>
        </form>
    </div>

</body>

</html>