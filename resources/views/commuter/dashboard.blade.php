<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartCommute | Live Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel='stylesheet' href='https://unpkg.com/maplibre-gl@5.18.0/dist/maplibre-gl.css' />
    <script src='https://unpkg.com/maplibre-gl@5.18.0/dist/maplibre-gl.js'></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700&display=swap');

        body, html {
            margin: 0;
            padding: 0;
            height: 100%;
            width: 100%;
            font-family: 'Plus Jakarta Sans', sans-serif;
            overflow: hidden; /* Prevent body scroll, map is the container */
            background: #0f172a;
        }

        #map {
            position: absolute;
            top: 0;
            bottom: 0;
            width: 100%;
            z-index: 0;
        }

        .glass-panel {
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        .bus-pulse {
            box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7);
            animation: pulse-blue 2s infinite;
        }

        @keyframes pulse-blue {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(59, 130, 246, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(59, 130, 246, 0); }
        }

        .custom-scroll::-webkit-scrollbar { width: 4px; }
        .custom-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 10px; }
    </style>
</head>

<body class="antialiased">

    <div id="map"></div>

    <header class="fixed top-6 left-6 right-6 z-50 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 pointer-events-none">
        <div class="glass-panel p-4 rounded-3xl pointer-events-auto flex items-center space-x-4">
            <div class="w-10 h-10 bg-blue-600 rounded-2xl flex items-center justify-center shadow-lg shadow-blue-600/30">
                <i class="fa-solid fa-bus text-white"></i>
            </div>
            <div>
                <h1 class="text-white font-bold text-sm">SmartCommute</h1>
                <p class="text-[10px] text-green-400 font-bold uppercase tracking-widest">Live • System Normal</p>
            </div>
        </div>

        <div class="flex items-center space-x-3 pointer-events-auto">
            <div class="glass-panel px-4 py-2.5 rounded-full hidden md:flex items-center text-white text-xs font-medium">
    <i class="fa-solid fa-calendar-day mr-2 opacity-50"></i>
    <span id="current-date">Loading date...</span> </div>
            <div class="glass-panel w-10 h-10 rounded-full flex items-center justify-center text-white cursor-pointer hover:bg-white/10 transition">
                <i class="fa-solid fa-bell text-xs"></i>
            </div>
          <div class="flex items-center space-x-3 pointer-events-auto">
    <button onclick="toggleLogoutModal()" class="glass-panel px-5 py-2.5 rounded-full text-white text-xs font-bold uppercase tracking-wider hover:bg-red-500/20 transition">
        Logout
    </button>
</div>
        </div>
    </header>

    <div class="fixed top-28 left-6 w-80 z-40 hidden md:flex flex-col gap-4 max-h-[calc(100vh-140px)]">
        <div class="glass-panel p-6 rounded-[2rem] flex flex-col gap-4">
            <h3 class="text-[10px] font-black uppercase tracking-widest text-blue-400 mb-2">Real-Time Planning</h3>
            <div class="space-y-3">
                <div class="relative">
                    <i class="fa-solid fa-circle-dot absolute left-4 top-1/2 -translate-y-1/2 text-[10px] text-blue-400"></i>
                    <input type="text" placeholder="Your location..." class="w-full bg-white/5 border border-white/10 rounded-2xl pl-10 pr-4 py-3 text-xs text-white focus:outline-none focus:border-blue-500">
                </div>
                <div class="relative">
                    <i class="fa-solid fa-location-dot absolute left-4 top-1/2 -translate-y-1/2 text-[10px] text-red-400"></i>
                    <input type="text" placeholder="Where to?" class="w-full bg-white/5 border border-white/10 rounded-2xl pl-10 pr-4 py-3 text-xs text-white focus:outline-none focus:border-red-500">
                </div>
            </div>

            <div class="bg-blue-600/10 border border-blue-500/20 rounded-2xl p-4 mt-2">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-[10px] font-bold text-blue-400 uppercase tracking-tighter">Estimated Arrival</span>
                    <span class="text-xs font-black text-white">12 Mins</span>
                </div>
                <div class="w-full bg-white/10 h-1 rounded-full overflow-hidden">
                    <div class="bg-blue-500 h-full w-2/3"></div>
                </div>
            </div>

            <button class="w-full bg-white text-black py-3.5 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-gray-200 transition">
                Calculate Fare
            </button>
        </div>

        <div class="glass-panel p-6 rounded-[2rem] bg-gradient-to-br from-blue-600/20 to-transparent border-blue-500/30">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <p class="text-[9px] uppercase tracking-widest text-white/50 font-bold mb-1">Digital Wallet</p>
                    <h2 class="text-2xl font-bold text-white">₱450.00</h2>
                </div>
                <i class="fa-solid fa-wallet text-blue-400 opacity-60"></i>
            </div>
            <button class="w-full bg-white/10 border border-white/20 py-2.5 rounded-xl text-[10px] font-bold text-white uppercase hover:bg-white/20 transition">
                + Top Up
            </button>
        </div>
    </div>

    <div class="fixed top-28 right-6 w-80 z-40 hidden lg:flex flex-col gap-4 max-h-[calc(100vh-140px)]">

        <a href="/tutorial" class="glass-panel p-5 rounded-3xl flex items-center space-x-4 border-yellow-500/20 group hover:bg-yellow-500/5 transition">
            <div class="w-10 h-10 bg-yellow-500/20 rounded-xl flex items-center justify-center group-hover:rotate-12 transition">
                <i class="fa-solid fa-wand-magic-sparkles text-yellow-500"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-white">App Tutorial</p>
                <p class="text-[9px] text-white/40 uppercase tracking-wider font-bold">New user? Start here</p>
            </div>
        </a>

        <div class="glass-panel p-6 rounded-[2.5rem] flex flex-col overflow-hidden">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-[10px] font-black uppercase tracking-widest text-white/60">Recent Receipts</h3>
                <button class="text-[10px] font-bold text-blue-400 hover:underline">History</button>
            </div>
            <div class="space-y-4 custom-scroll overflow-y-auto pr-2">
                <div class="flex items-center justify-between group">
                    <div class="flex items-center space-x-3">
                        <div class="w-9 h-9 bg-white/5 rounded-lg flex items-center justify-center border border-white/5 group-hover:border-blue-500/30 transition">
                            <i class="fa-solid fa-receipt text-[10px] text-white/40 group-hover:text-blue-400"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-bold text-white">#INV-8821</p>
                            <p class="text-[9px] text-white/30">Today, 8:45 AM</p>
                        </div>
                    </div>
                    <span class="text-xs font-bold text-green-400">-₱15.00</span>
                </div>
                <div class="flex items-center justify-between group opacity-70">
                    <div class="flex items-center space-x-3">
                        <div class="w-9 h-9 bg-white/5 rounded-lg flex items-center justify-center border border-white/5">
                            <i class="fa-solid fa-receipt text-[10px] text-white/40"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-bold text-white">#INV-8819</p>
                            <p class="text-[9px] text-white/30">Feb 4, 2026</p>
                        </div>
                    </div>
                    <span class="text-xs font-bold text-green-400">-₱22.50</span>
                </div>
            </div>
        </div>
    </div>
     <div id="logout-modal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 backdrop-blur-sm opacity-0 pointer-events-none transition-all duration-300">
    <div class="glass-panel p-8 rounded-[2.5rem] w-full max-w-sm mx-4 text-center border-white/20 shadow-2xl transform scale-95 transition-transform duration-300" id="modal-content">
        <div class="w-16 h-16 bg-red-500/20 rounded-full flex items-center justify-center mx-auto mb-6">
            <i class="fa-solid fa-right-from-bracket text-red-500 text-xl"></i>
        </div>

        <h3 class="text-xl font-bold text-white mb-2">Sign Out?</h3>
        <p class="text-sm text-white/60 mb-8">Are you sure you want to log out of SmartCommute?</p>

        <div class="flex gap-3">
            <button onclick="toggleLogoutModal()" class="flex-1 px-6 py-3 rounded-2xl bg-white/5 border border-white/10 text-white text-xs font-bold uppercase tracking-widest hover:bg-white/10 transition">
                Cancel
            </button>
            <form action="{{ route('users.logout') }}" method="POST" class="flex-1">
                @csrf
                <button type="submit" class="w-full px-6 py-3 rounded-2xl bg-red-600 text-white text-xs font-bold uppercase tracking-widest hover:bg-red-700 shadow-lg shadow-red-600/20 transition">
                    Logout
                </button>
            </form>
        </div>
    </div>
</div>

    <div class="fixed bottom-6 left-6 right-6 z-50 md:hidden">
        <div class="glass-panel p-4 rounded-3xl flex justify-around items-center">
            <i class="fa-solid fa-map-location-dot text-blue-400 text-lg"></i>
            <i class="fa-solid fa-wallet text-white/40 text-lg"></i>
            <div class="w-12 h-12 bg-blue-600 rounded-2xl flex items-center justify-center -mt-10 shadow-lg shadow-blue-600/40">
                <i class="fa-solid fa-bus text-white"></i>
            </div>
            <i class="fa-solid fa-receipt text-white/40 text-lg"></i>
            <i class="fa-solid fa-user text-white/40 text-lg"></i>
        </div>
    </div>

    <script>
        const map = new maplibregl.Map({
            container: 'map',
            style: 'https://tiles.openfreemap.org/styles/bright', // Changed to dark for better UI contrast
            center: [123.79, 10.24],
            zoom: 13
        });

        map.addControl(new maplibregl.NavigationControl(), 'bottom-right');
        map.addControl(new maplibregl.GeolocateControl({
            positionOptions: { enableHighAccuracy: true },
            trackUserLocation: true
        }), 'bottom-right');
                function updateLiveDate() {
            const dateElement = document.getElementById('current-date');
            const now = new Date();

            const options = {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            };


            dateElement.textContent = now.toLocaleDateString('en-US', options);
        }

        updateLiveDate();


        setInterval(updateLiveDate, 3600000);
                    function toggleLogoutModal() {
                const modal = document.getElementById('logout-modal');
                const content = document.getElementById('modal-content');

                if (modal.classList.contains('opacity-0')) {

                    modal.classList.remove('opacity-0', 'pointer-events-none');
                    content.classList.remove('scale-95');
                    content.classList.add('scale-100');
                } else {

                    modal.classList.add('opacity-0', 'pointer-events-none');
                    content.classList.remove('scale-100');
                    content.classList.add('scale-95');
                }
            }


            window.onclick = function(event) {
                const modal = document.getElementById('logout-modal');
                if (event.target == modal) {
                    toggleLogoutModal();
                }
            }

    </script>
</body>

</html>
