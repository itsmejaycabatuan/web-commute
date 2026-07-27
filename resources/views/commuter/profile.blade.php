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

    <div class="pt-10 pb-10 max-w-6xl mx-auto">

        <header class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-10">
            <div>
                <div
                    class="flex items-center gap-2 text-[10px] font-bold uppercase tracking-[0.2em] text-blue-500 mb-2">
                    <i class="fa-solid fa-circle-user"></i> Commuter Account
                </div>
                <h2 class="text-3xl font-black tracking-tight">{{ explode('@', Auth::user()->email)[0] }}</h2>
                <p class="text-gray-500 text-xs">{{ Auth::user()->email }}</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('payment.topup') }}"
                    class="glass px-5 py-3 rounded-2xl text-[10px] font-bold uppercase tracking-widest hover:bg-white/10 transition">
                    <i class="fa-solid fa-wallet mr-2 text-blue-400"></i> Top Up
                </a>
                <a href="{{ route('profile.edit') }}"
                    class="bg-blue-600 text-white px-5 py-3 rounded-2xl text-[10px] font-bold uppercase tracking-widest hover:bg-blue-500 transition shadow-lg shadow-blue-600/20">
                    Edit Profile
                </a>
                <a href="{{ route('map') }}" class="group flex items-center gap-2">
                    <div
                        class="w-8 h-8 glass rounded-lg flex items-center justify-center group-hover:bg-blue-500/10 group-hover:text-blue-400 transition-all">
                        <i class="fa-solid fa-arrow-left text-[10px]"></i>
                    </div>
                    <span
                        class="text-[10px] font-black uppercase tracking-widest opacity-50 group-hover:opacity-100 transition">Back
                        to Home</span>
                </a>
            </div>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

            <div class="lg:col-span-4 space-y-6">
                <div
                    class="glass p-8 rounded-[2.5rem] relative overflow-hidden bg-gradient-to-br from-blue-600/10 to-transparent">
                    <p class="text-[10px] font-bold text-blue-400 uppercase tracking-widest mb-1">Available Balance</p>
                    <h3 class="text-4xl font-black">₱{{ number_format($wallet->balance ?? 0, 2) }}</h3>
                    <i class="fa-solid fa-shield-halved absolute -right-4 -bottom-4 text-7xl opacity-5"></i>
                </div>

                <div class="glass p-8 rounded-[2.5rem] space-y-6">
                    <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-500">Security Details</h4>

                    <div class="space-y-4">
                        <div>
                            <p class="text-[10px] uppercase font-bold text-gray-500 mb-1">Account Status</p>
                            @if(Auth::user()->email_verified_at)
                                <span class="text-[10px] font-black text-emerald-400 flex items-center gap-1">
                                    <i class="fa-solid fa-circle-check"></i> VERIFIED COMMUTER
                                </span>
                            @else
                                <span class="text-[10px] font-black text-amber-500">PENDING VERIFICATION</span>
                            @endif
                        </div>
                        <div>
                            <p class="text-[10px] uppercase font-bold text-gray-500 mb-1">Member Since</p>
                            <p class="text-sm font-bold text-white/80">{{ Auth::user()->created_at->format('M d, Y') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-8 space-y-6">

                <div class="glass p-8 rounded-[2.5rem]">
                    <div class="flex items-center justify-between mb-8">
                        <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-blue-400">Recent Travels</h4>
                        <i class="fa-solid fa-bus-simple text-gray-600"></i>
                    </div>

                    <div class="space-y-4">
                        @forelse($payments as $payment)
                            <div
                                class="flex items-center justify-between p-4 rounded-2xl hover:bg-white/5 border border-white/5 transition-all group">
                                <div class="flex items-center gap-4">
                                    <div
                                        class="w-10 h-10 rounded-xl bg-blue-500/10 flex items-center justify-center text-blue-400">
                                        <i class="fa-solid fa-location-arrow text-xs"></i>
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold">{{ $payment->starting_point }} →
                                            {{ $payment->destination }}
                                        </p>
                                        <p class="text-[9px] text-gray-500 font-bold uppercase tracking-tighter">
                                            {{ \Carbon\Carbon::parse($payment->paid_at)->format('M d • h:i A') }}
                                            • {{ $payment->total_distance }}km
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-black text-white">-₱{{ number_format($payment->price, 2) }}</p>
                                    @if($payment->is_discounted)
                                        <span
                                            class="text-[8px] bg-blue-500/20 text-blue-300 px-2 py-0.5 rounded uppercase font-black">Discounted</span>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-center py-10 text-gray-500 text-xs italic">No travel history found.</p>
                        @endforelse
                    </div>
                </div>

                <div class="glass p-8 rounded-[2.5rem]">
                    <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 mb-8">Wallet Loads</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($topups as $topup)
                            <div class="bg-white/5 p-4 rounded-2xl flex items-center justify-between">
                                <div>
                                    <p class="text-[10px] font-black text-emerald-500 uppercase tracking-tighter">Reload
                                        Successful</p>
                                    <p class="text-[9px] text-gray-500">{{ $topup->created_at->diffForHumans() }} via
                                        {{ $topup->payment_method }}
                                    </p>
                                </div>
                                <p class="text-sm font-black text-white">+₱{{ number_format($topup->amount_added, 2) }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

            </div>
        </div>
    </div>
</body>

</html>
