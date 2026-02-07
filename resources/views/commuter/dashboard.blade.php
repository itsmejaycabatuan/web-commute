<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartCommute | Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700&display=swap');

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


        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }


        .bus-pulse {
            box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7);
            animation: pulse-blue 2s infinite;
        }

        @keyframes pulse-blue {
            0% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7);
            }

            70% {
                transform: scale(1);
                box-shadow: 0 0 0 10px rgba(59, 130, 246, 0);
            }

            100% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(59, 130, 246, 0);
            }
        }
    </style>
</head>

<body class="antialiased">

    <div class="max-w-7xl mx-auto p-4 md:p-10 space-y-8">

        <header class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4 mb-4">
            <div>
                <h1 class="text-3xl font-bold tracking-tight">Good Morning, Commuter</h1>
                <p class="text-sm opacity-50 flex items-center">
                    <i class="fa-solid fa-calendar-day mr-2"></i> Thursday, February 5, 2026
                </p>
            </div>
            <div class="flex items-center space-x-4">
                <span
                    class="bg-white/5 border border-white/10 px-4 py-2 rounded-full text-[10px] font-bold uppercase tracking-widest hidden md:inline-block">
                    System Status: <span class="text-green-400">Normal</span>
                </span>
                <div
                    class="w-10 h-10 rounded-full border border-white/20 flex items-center justify-center glass cursor-pointer">
                    <i class="fa-solid fa-bell text-xs"></i>
                </div>
                <form action="{{ route('users.logout') }}" method="POST">
                    @csrf
                    <div
                        class="w-35 h-10 rounded-full border border-white/20 flex items-center justify-center glass cursor-pointer">
                        <button type="submit" class="p-4">Logout</button>
                    </div>
                </form>

            </div>
        </header>

        <main class="grid grid-cols-1 lg:grid-cols-4 gap-8">

            <aside class="lg:col-span-1 space-y-8">
                <div
                    class="glass glass-inset p-8 rounded-[2.5rem] bg-gradient-to-br from-blue-600/20 to-transparent relative overflow-hidden group">
                    <div
                        class="absolute -right-4 -top-4 w-24 h-24 bg-blue-500/10 rounded-full blur-3xl group-hover:bg-blue-500/20 transition duration-500">
                    </div>

                    <div class="flex justify-between items-center mb-10 relative z-10">
                        <div class="w-10 h-6 bg-white/10 rounded-md border border-white/20"></div>
                        <i class="fa-solid fa-wallet text-xl opacity-40"></i>
                    </div>

                    <div class="relative z-10">
                        <p class="text-[10px] uppercase tracking-[0.2em] opacity-50 mb-1 font-bold">Available Credits
                        </p>
                        <p class="text-4xl font-bold mb-8">₱450<span class="text-lg opacity-40">.00</span></p>
                    </div>

                    <button
                        class="w-full bg-white text-black text-[10px] font-black py-4 rounded-2xl hover:bg-blue-50 transition-all active:scale-95 uppercase tracking-widest relative z-10">
                        + Top Up Funds
                    </button>
                </div>

                <div class="glass glass-inset p-8 rounded-[2.5rem]">
                    <h3 class="text-xs font-bold mb-6 uppercase tracking-widest opacity-70 flex items-center">
                        <i class="fa-solid fa-calculator mr-2 text-blue-400"></i> Fare Calculator
                    </h3>
                    <div class="space-y-4">
                        <div class="relative group">
                            <i
                                class="fa-solid fa-circle-dot absolute left-4 top-1/2 -translate-y-1/2 text-[10px] text-blue-400"></i>
                            <input type="text" placeholder="Pick-up point"
                                class="w-full bg-white/5 border border-white/10 rounded-2xl pl-10 pr-4 py-3 text-xs focus:bg-white/10 focus:border-blue-500/50 outline-none transition">
                        </div>
                        <div class="relative group">
                            <i
                                class="fa-solid fa-location-dot absolute left-4 top-1/2 -translate-y-1/2 text-[10px] text-red-400"></i>
                            <input type="text" placeholder="Destination"
                                class="w-full bg-white/5 border border-white/10 rounded-2xl pl-10 pr-4 py-3 text-xs focus:bg-white/10 focus:border-red-500/50 outline-none transition">
                        </div>
                        <button
                            class="w-full bg-white/5 border border-white/10 py-3 rounded-2xl text-[10px] font-bold uppercase hover:bg-white/10 transition-colors">
                            Calculate Estimate
                        </button>
                    </div>
                </div>
            </aside>

            <section class="lg:col-span-2">
                <div
                    class="relative h-[550px] glass rounded-[3rem] overflow-hidden border border-white/10 shadow-inner group">
                    <div class="absolute inset-0 bg-[#0f172a] group-hover:bg-[#1e293b] transition duration-700">
                        <div class="w-full h-full flex items-center justify-center opacity-10">
                            <i class="fa-solid fa-map-location-dot text-9xl"></i>
                        </div>
                        <div class="absolute top-1/4 left-1/3 w-3 h-3 bg-blue-500 rounded-full blur-[2px]"></div>
                        <div class="absolute top-1/2 left-2/3 w-3 h-3 bg-red-500 rounded-full blur-[2px]"></div>
                    </div>

                    <div class="absolute top-6 right-6 flex flex-col space-y-2">
                        <button
                            class="w-10 h-10 glass rounded-full flex items-center justify-center hover:bg-white/10 transition"><i
                                class="fa-solid fa-plus text-xs"></i></button>
                        <button
                            class="w-10 h-10 glass rounded-full flex items-center justify-center hover:bg-white/10 transition"><i
                                class="fa-solid fa-minus text-xs"></i></button>
                        <button
                            class="w-10 h-10 glass rounded-full flex items-center justify-center hover:bg-white/10 transition"><i
                                class="fa-solid fa-location-arrow text-xs"></i></button>
                    </div>

                    <div class="absolute bottom-8 left-8 right-8">
                        <div
                            class="glass glass-inset p-6 rounded-3xl flex items-center justify-between border-white/20 shadow-2xl">
                            <div class="flex items-center space-x-5">
                                <div
                                    class="w-14 h-14 bg-blue-600 text-white rounded-2xl flex items-center justify-center text-xl bus-pulse shadow-lg shadow-blue-600/40">
                                    <i class="fa-solid fa-bus"></i>
                                </div>
                                <div>
                                    <div class="flex items-center space-x-2">
                                        <span class="text-xl font-black">12</span>
                                        <span class="text-[10px] uppercase font-bold opacity-50 tracking-widest">Minutes
                                            Away</span>
                                    </div>
                                    <p class="text-[10px] text-blue-400 font-bold tracking-widest uppercase">Route 14B •
                                        3.2 KM Remaining</p>
                                </div>
                            </div>
                            <button
                                class="bg-white text-black px-6 py-3 rounded-xl text-[10px] font-black uppercase tracking-tighter hover:bg-blue-50 transition active:scale-95">
                                Track Live
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <aside class="lg:col-span-1 space-y-8">
                <a href="/tutorial"
                    class="block glass glass-inset p-8 rounded-[2.5rem] border-blue-500/30 hover:bg-blue-600/5 transition duration-500 group">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-10 h-10 bg-blue-500/20 rounded-xl flex items-center justify-center">
                            <i
                                class="fa-solid fa-wand-magic-sparkles text-blue-400 text-sm group-hover:rotate-12 transition duration-300"></i>
                        </div>
                        <i
                            class="fa-solid fa-arrow-up-right-from-square text-[10px] opacity-20 group-hover:opacity-100"></i>
                    </div>
                    <h4 class="font-bold text-sm mb-1">New to SmartCommute?</h4>
                    <p class="text-[10px] opacity-40 leading-relaxed uppercase tracking-wider font-bold">Quick Tutorial
                        Available</p>
                </a>

                <div class="glass glass-inset p-8 rounded-[2.5rem]">
                    <div class="flex justify-between items-center mb-8">
                        <h3 class="text-xs font-bold uppercase tracking-widest opacity-70">Digital Receipts</h3>
                        <button class="text-[10px] font-bold text-blue-400 hover:underline">View All</button>
                    </div>

                    <div class="space-y-6">
                        <div class="flex items-center justify-between group cursor-pointer">
                            <div class="flex items-center space-x-4">
                                <div
                                    class="w-10 h-10 bg-white/5 rounded-xl flex items-center justify-center border border-white/5 group-hover:border-blue-500/30 transition">
                                    <i
                                        class="fa-solid fa-file-invoice text-xs opacity-40 group-hover:text-blue-400 group-hover:opacity-100 transition"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-bold group-hover:text-blue-400 transition">#INV-8821</p>
                                    <p class="text-[9px] opacity-40">Feb 05, 2026</p>
                                </div>
                            </div>
                            <p class="text-xs font-bold text-green-400/80">-₱15.00</p>
                        </div>

                        <div class="flex items-center justify-between group cursor-pointer">
                            <div class="flex items-center space-x-4">
                                <div
                                    class="w-10 h-10 bg-white/5 rounded-xl flex items-center justify-center border border-white/5 group-hover:border-blue-500/30 transition">
                                    <i
                                        class="fa-solid fa-file-invoice text-xs opacity-40 group-hover:text-blue-400 group-hover:opacity-100 transition"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-bold group-hover:text-blue-400 transition">#INV-8819</p>
                                    <p class="text-[9px] opacity-40">Feb 04, 2026</p>
                                </div>
                            </div>
                            <p class="text-xs font-bold text-green-400/80">-₱22.50</p>
                        </div>
                    </div>
                </div>
            </aside>
        </main>
    </div>

</body>

</html>