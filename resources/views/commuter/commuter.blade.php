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
            top: 0; left: -100%;
            width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            animation: shine 3s infinite;
        }

        @keyframes shine {
            to { left: 100%; }
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

    <div class="max-w-7xl mx-auto p-4 md:p-10 space-y-8">

        <div class="flex items-center space-x-2 text-[10px] font-bold uppercase tracking-widest opacity-50">
            <a href="#" class="hover:text-blue-400">Dashboard</a>
            <span>/</span>
            <span class="text-blue-400">User Profile</span>
        </div>

        <div class="relative group">
            <div class="h-64 rounded-[3.5rem] glass overflow-hidden relative border border-white/10">
                <div class="absolute inset-0 bg-gradient-to-r from-blue-600/20 via-indigo-600/10 to-transparent"></div>
                <div class="absolute inset-0 profile-glow"></div>
                <div class="absolute top-10 right-20 w-32 h-32 bg-blue-500/10 rounded-full blur-3xl"></div>
            </div>

            <div class="absolute -bottom-10 left-6 md:left-12 right-6 md:right-12 flex flex-col md:flex-row items-end justify-between gap-6">
                <div class="flex items-end space-x-6">
                    <div class="relative">
                        <div class="w-32 h-32 md:w-40 md:h-40 rounded-[2.5rem] md:rounded-[3rem] glass p-1.5 border-2 border-white/20 shadow-2xl relative z-10 bg-[#0a0a0a]">
                            <img src="https://randomuser.me/api/portraits/men/32.jpg"
                                 class="w-full h-full object-cover rounded-[2.2rem] md:rounded-[2.5rem] grayscale hover:grayscale-0 transition duration-500"
                                 alt="James Wilson">
                        </div>
                        <div class="absolute bottom-2 right-2 w-6 h-6 bg-green-500 border-4 border-[#0a0a0a] rounded-full z-20"></div>
                    </div>

                    <div class="pb-6 hidden md:block">
                        <div class="flex items-center space-x-3 mb-1">
                            <h1 class="text-4xl font-black tracking-tighter">James Wilson</h1>
                            <i class="fa-solid fa-circle-check text-blue-400 text-xl"></i>
                        </div>
                        <div class="flex items-center space-x-4 opacity-60 text-xs font-medium">
                            <span><i class="fa-solid fa-location-dot mr-1.5 text-blue-400"></i> Manila, PH</span>
                            <span><i class="fa-solid fa-id-card-clip mr-1.5 text-blue-400"></i> #SC-99281</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center space-x-3 pb-8">
                    <button class="glass px-6 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-white/10 transition active:scale-95">
                        <i class="fa-solid fa-pen-to-square mr-2"></i> Edit Profile
                    </button>
                    <button class="bg-blue-600 px-6 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest text-white shadow-lg shadow-blue-600/20 hover:bg-blue-500 transition active:scale-95">
                        Account Settings
                    </button>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 pt-12">

            <div class="lg:col-span-4 space-y-8">
                <div class="glass glass-inset p-8 rounded-[2.5rem]">
                    <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-blue-400 mb-8 flex items-center">
                        <i class="fa-solid fa-user-gear mr-2"></i> Identity Info
                    </h3>

                    <div class="space-y-6">
                        <div class="list-item-hover p-2 -m-2 rounded-xl">
                            <p class="text-[10px] uppercase font-bold text-gray-500 mb-1">Full Legal Name</p>
                            <p class="text-sm font-bold">James Alexander Wilson</p>
                        </div>
                        <div class="list-item-hover p-2 -m-2 rounded-xl">
                            <p class="text-[10px] uppercase font-bold text-gray-500 mb-1">Contact Email</p>
                            <p class="text-sm font-bold">j.wilson@smartcommute.ph</p>
                        </div>
                        <div class="list-item-hover p-2 -m-2 rounded-xl">
                            <p class="text-[10px] uppercase font-bold text-gray-500 mb-1">Linked Mobile</p>
                            <p class="text-sm font-bold">+63 917 882 1994</p>
                        </div>
                    </div>

                    <div class="mt-10 pt-8 border-t border-white/5">
                        <p class="text-[10px] font-black uppercase tracking-widest opacity-30 mb-4">Security Level</p>
                        <div class="flex items-center justify-between">
                            <div class="flex space-x-1">
                                <div class="w-8 h-1 bg-blue-500 rounded-full"></div>
                                <div class="w-8 h-1 bg-blue-500 rounded-full"></div>
                                <div class="w-8 h-1 bg-white/10 rounded-full"></div>
                            </div>
                            <span class="text-[10px] font-bold text-blue-400 uppercase">Strong</span>
                        </div>
                    </div>
                </div>

                <div class="glass p-8 rounded-[2.5rem] bg-gradient-to-br from-purple-600/20 to-transparent border-purple-500/20">
                    <div class="flex justify-between items-start mb-4">
                        <i class="fa-solid fa-crown text-purple-400 text-xl"></i>
                        <span class="text-[10px] font-black text-purple-400 uppercase tracking-widest">Commuter Tier</span>
                    </div>
                    <p class="text-2xl font-black mb-1 tracking-tighter">Gold Status</p>
                    <p class="text-[10px] text-gray-500 uppercase font-bold mb-6 tracking-wide">2,450 Total Points</p>

                    <div class="w-full bg-white/5 rounded-full h-1.5 mb-2 progress-shine">
                        <div class="bg-purple-500 h-1.5 rounded-full" style="width: 75%"></div>
                    </div>
                    <p class="text-[9px] text-gray-600 font-bold uppercase tracking-tight text-center">550 pts until Platinum Status</p>
                </div>
            </div>

            <div class="lg:col-span-8 space-y-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="glass glass-inset p-8 rounded-[2.5rem] relative overflow-hidden group">
                        <i class="fa-solid fa-bus absolute -right-4 -bottom-4 text-6xl opacity-5 group-hover:scale-110 transition duration-700"></i>
                        <p class="text-3xl font-black mb-1">142</p>
                        <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Trips Completed</p>
                    </div>
                    <div class="glass glass-inset p-8 rounded-[2.5rem] relative overflow-hidden border-green-500/20 group">
                        <i class="fa-solid fa-leaf absolute -right-4 -bottom-4 text-6xl text-green-500/5 group-hover:scale-110 transition duration-700"></i>
                        <p class="text-3xl font-black text-green-400 mb-1">84%</p>
                        <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Eco-Score</p>
                    </div>
                    <div class="glass glass-inset p-8 rounded-[2.5rem] relative overflow-hidden border-orange-500/20 group">
                        <i class="fa-solid fa-clock absolute -right-4 -bottom-4 text-6xl text-orange-500/5 group-hover:scale-110 transition duration-700"></i>
                        <p class="text-3xl font-black text-orange-400 mb-1">18.5h</p>
                        <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Time Saved</p>
                    </div>
                </div>

                <div class="glass glass-inset p-8 rounded-[2.5rem]">
                    <div class="flex items-center justify-between mb-10">
                        <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-blue-400">Commute History</h3>
                        <button class="text-[10px] font-bold text-gray-500 hover:text-white transition uppercase tracking-widest underline decoration-blue-500/50">View Monthly Report</button>
                    </div>

                    <div class="space-y-4">
                        <div class="flex items-center justify-between group cursor-pointer hover:bg-white/5 p-4 rounded-2xl transition border border-transparent hover:border-white/10">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 bg-blue-500/10 rounded-2xl flex items-center justify-center">
                                    <i class="fa-solid fa-route text-blue-400 text-sm"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-bold tracking-tight">Central Terminal <i class="fa-solid fa-arrow-right-long mx-2 opacity-30 text-[8px]"></i> Greenhills</p>
                                    <p class="text-[10px] text-gray-500 uppercase font-bold tracking-wider mt-1">Feb 05 • 08:45 AM • Route 14B</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-black text-white">-₱15.00</p>
                                <span class="text-[8px] bg-green-500/10 text-green-400 px-2 py-0.5 rounded font-black uppercase tracking-tighter">Verified</span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between group cursor-pointer hover:bg-white/5 p-4 rounded-2xl transition border border-transparent hover:border-white/10">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 bg-blue-500/10 rounded-2xl flex items-center justify-center">
                                    <i class="fa-solid fa-route text-blue-400 text-sm"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-bold tracking-tight">Greenhills <i class="fa-solid fa-arrow-right-long mx-2 opacity-30 text-[8px]"></i> Central Terminal</p>
                                    <p class="text-[10px] text-gray-500 uppercase font-bold tracking-wider mt-1">Feb 04 • 05:30 PM • Route 14B</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-black text-white">-₱15.00</p>
                                <span class="text-[8px] bg-green-500/10 text-green-400 px-2 py-0.5 rounded font-black uppercase tracking-tighter">Verified</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
