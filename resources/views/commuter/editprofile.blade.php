<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartCommute | User Profile</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700;800&display=swap');

        body {
            background: radial-gradient(circle at top left, #1a1a1a, #050505);
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #fff;
            min-height: 100vh;
        }

        .glass {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.8);
        }

        .glass-inset {
            box-shadow: inset 0 1px 1px rgba(255, 255, 255, 0.1);
        }

        .profile-glow {
            background: radial-gradient(circle at center, rgba(59, 130, 246, 0.15), transparent 70%);
        }

        .progress-shine {
            position: relative;
            overflow: hidden;
        }

        .progress-shine::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            animation: shine 3s infinite;
        }

        @keyframes shine {
            to {
                left: 100%;
            }
        }

        .list-item-hover {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .list-item-hover:hover {
            background: rgba(255, 255, 255, 0.05);
            transform: translateX(8px);
        }
    </style>
</head>

<body class="antialiased">

    @include('components.flash')

    <div class="max-w-4xl mx-auto p-10">

        <nav class="flex items-center space-x-2 text-[10px] font-bold uppercase tracking-widest opacity-50 mb-6">
            <a href="{{ route('profile') }}" class="hover:text-blue-400 transition">Profile</a>
            <span>/</span>
            <span class="text-blue-400">Edit Settings</span>
        </nav>

        <header class="mb-10">
            <h2 class="text-3xl font-black tracking-tight">Account <span class="text-blue-500">Settings</span></h2>
            <p class="text-gray-500 text-xs mt-1">Update your login credentials and security preferences.</p>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <div class="space-y-6">
                <div class="glass p-6 rounded-[2rem] border-blue-500/20">
                    <h4 class="text-sm font-bold mb-2 text-blue-400">Pro Tip</h4>
                    <p class="text-xs text-gray-500 leading-relaxed">
                        Use a unique password that you don't use for other online services.
                    </p>
                </div>
            </div>

            <div class="lg:col-span-2">
                <form action="{{ route('profile.update') }}" method="POST" class="grid gap-4">
                    @csrf
                    @method('PUT')

                    <div class="glass p-8 rounded-[2.5rem] space-y-6">
                        <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-blue-400">General Information
                        </h3>

                        <div class="space-y-1">
                            <label class="text-[10px] uppercase font-bold text-gray-500 ml-2">Email Address</label>
                            <div class="relative">
                                <i
                                    class="fa-solid fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-xs text-gray-500"></i>
                                <input type="email" name="email" value="{{ Auth::user()->email }}" readonly
                                    class="w-full bg-white/5 border border-white/10 rounded-2xl pl-12 pr-4 py-3.5 text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500/50 outline-none transition-all">
                            </div>
                        </div>
                    </div>

                    <div class="glass p-8 rounded-[2.5rem] space-y-6">
                        <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-red-400">Update Password</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-1 md:col-span-2">
                                <label class="text-[10px] uppercase font-bold text-gray-500 ml-2">Current
                                    Password</label>
                                <input type="password" name="current_password" placeholder="••••••••"
                                    class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-3.5 text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500/50 outline-none transition-all">
                                @if ($errors->any())
                                    <div class="alert alert-danger">
                                        <ul>
                                            @foreach ($errors->get('current_password') as $message)
                                                <li>{{ $message }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>


                            <div class="space-y-1">
                                <label class="text-[10px] uppercase font-bold text-gray-500 ml-2">New Password</label>
                                <input type="password" name="password" placeholder="Min. 8 characters"
                                    class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-3.5 text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500/50 outline-none transition-all">
                                @if ($errors->any())
                                    <div class="alert alert-danger">
                                        <ul>
                                            @foreach ($errors->get('password') as $message)
                                                <li>{{ $message }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>

                            <div class="space-y-1">
                                <label class="text-[10px] uppercase font-bold text-gray-500 ml-2">Confirm New
                                    Password</label>
                                <input type="password" name="password_confirmation" placeholder="Repeat new password"
                                    class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-3.5 text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500/50 outline-none transition-all">
                                @if ($errors->any())
                                    <div class="alert alert-danger">
                                        <ul>
                                            @foreach ($errors->get('password_confirmation') as $message)
                                                <li>{{ $message }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-4 pt-4">
                        <a href="{{ route('profile') }}"
                            class="text-[10px] font-bold uppercase tracking-widest text-gray-500 hover:text-white transition">
                            Discard Changes
                        </a>
                        <button type="submit"
                            class="bg-blue-600 text-white px-8 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-blue-500 transition shadow-lg shadow-blue-600/20 active:scale-95">
                            Save Account Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>

</html>
