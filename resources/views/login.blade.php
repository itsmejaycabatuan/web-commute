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
<body class="login-bg min-h-screen flex items-center justify-center p-6 relative">

    <a href="{{ url('/home') }}" class="absolute top-8 left-8 flex items-center space-x-2 text-white/70 hover:text-white transition group">
        <div class="w-10 h-10 border border-white/20 rounded-full flex items-center justify-center bg-white/10 backdrop-blur-md group-hover:bg-white/20">
            <i class="fa-solid fa-arrow-left text-sm"></i>
        </div>
        <span class="text-xs uppercase tracking-widest font-bold">Back to Home</span>
    </a>

    <div class="w-full max-w-sm bg-white/10 backdrop-blur-xl border border-white/20 p-8 rounded-3xl shadow-2xl text-white">

        <div class="text-center mb-8">
            <div class="flex justify-center items-center mb-4">
                 <div class="w-10 h-10 border-2 border-white rounded-full flex items-center justify-center">
                    <div class="w-5 h-5 bg-white rounded-full"></div>
                </div>
                <span class="text-2xl font-bold tracking-wider italic ml-3">SmartCommute</span>
            </div>
            <h2 class="text-2xl font-bold tracking-tight">Welcome Back</h2>
            <p class="text-xs opacity-60 mt-1">Please enter your details</p>
        </div>

        <form action="#" method="GET" class="space-y-5">
             <div>
                <label class="block text-[10px] uppercase tracking-widest font-bold opacity-50 mb-1 ml-1">Username</label>
                <input type="text" placeholder="Enter Username"
                    class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-white/30">
            </div>


       <div>
    <label class="block text-[10px] uppercase tracking-widest font-bold opacity-50 mb-1 ml-1">Password</label>

    <input type="password" placeholder="••••••••"
        class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-white/30 transition">

    <div class="flex justify-end mt-1 mr-1">
        <a href="#" class="text-[10px] opacity-40 hover:opacity-100 transition">Forgot Password?</a>
    </div>
</div>

            <div class="flex items-center space-x-2">
                <input type="checkbox" id="remember" class="w-3 h-3 bg-white/10 border-white/20 rounded focus:ring-0 cursor-pointer">
                <label for="remember" class="text-[10px] opacity-60 cursor-pointer">Remember me for 30 days</label>
            </div>

            <button type="submit" class="w-full bg-white text-black font-bold py-3 rounded-xl hover:bg-gray-200 transition-all active:scale-95 text-xs uppercase tracking-widest mt-2">
                Log In
            </button>
        </form>

        <div class="mt-8 pt-6 border-t border-white/10 text-center">
            <p class="text-xs opacity-60">
                Don't have an account?
                <a href="{{ url('/register') }}" class="text-white font-bold hover:underline">Register now</a>
            </p>
        </div>
    </div>

</body>
</html>
