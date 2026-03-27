<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartCommute | Live Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel='stylesheet' href='https://unpkg.com/maplibre-gl@5.18.0/dist/maplibre-gl.css' />
    <script src='https://unpkg.com/maplibre-gl@5.18.0/dist/maplibre-gl.js'></script>
    <script src="https://unpkg.com/laravel-echo@1.15.3/dist/echo.iife.js"></script>
    <script src="https://js.pusher.com/7.2/pusher.min.js"></script>
    <script
        src="https://cdn.jsdelivr.net/npm/@watergis/maplibre-gl-terradraw@1.0.1/dist/maplibre-gl-terradraw.umd.js"></script>
    <script src="https://unpkg.com/@maplibre/maplibre-gl-geocoder@1.5.0/dist/maplibre-gl-geocoder.min.js"></script>
    <link rel="stylesheet"
        href="https://unpkg.com/@maplibre/maplibre-gl-geocoder@1.5.0/dist/maplibre-gl-geocoder.css" />
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/@watergis/maplibre-gl-terradraw@1.0.1/dist/maplibre-gl-terradraw.css" />
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700&display=swap');

        body,
        html {
            margin: 0;
            padding: 0;
            height: 100%;
            width: 100%;
            font-family: 'Plus Jakarta Sans', sans-serif;
            overflow: hidden;
            background: #0f172a;
        }

        #map {
            position: absolute;
            top: 0;
            bottom: 0;
            width: 100%;
            z-index: 0;
        }

        /* Fixed glass classes - moved outside of #map selector */
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
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
        }

        .glass-panel {
            background: rgba(15, 23, 42, 0.85) !important;
            /* Increased opacity */
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
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

        .custom-scroll::-webkit-scrollbar {
            width: 4px;
        }

        .custom-scroll::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
        }

        /* Fixed input styles */
        input {
            background: rgba(255, 255, 255, 0.08) !important;
        }

        input::placeholder {
            color: rgba(255, 255, 255, 0.3);
        }

        input:focus {
            background: rgba(255, 255, 255, 0.12) !important;
        }

        /* Fixed button styles */
        button {
            color: white;
        }

        /* Modal styles */
        #logout-modal {
            transition: opacity 0.3s ease;
        }

        /* Custom vehicle marker style */
        .custom-vehicle-marker {
            width: 32px;
            height: 32px;
            background: #3b82f6;
            border: 3px solid white;
            border-radius: 50%;
            box-shadow: 0 0 15px rgba(59, 130, 246, 0.8);
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .custom-vehicle-marker:hover {
            transform: scale(1.2);
            background: #60a5fa;
        }

        .custom-vehicle-marker i {
            font-size: 16px;
            color: white;
        }


        .rounded-rect {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 50px -25px black;
        }

        .flex-center {
            position: absolute;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .flex-center.left {
            left: 0px;
        }

        .flex-center.right {
            right: 0px;
        }

        .sidebar-content {
            position: absolute;
            width: 95%;
            height: 95%;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 32px;
            color: gray;
        }

        .sidebar-toggle {
            position: absolute;
            width: 1.3em;
            height: 1.3em;
            overflow: visible;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .sidebar-toggle.left {
            right: -1.5em;
        }

        .sidebar-toggle.right {
            left: -1.5em;
        }

        .sidebar-toggle:hover {
            color: #0aa1cf;
            cursor: pointer;
        }

        .sidebar {
            transition: transform 1s;
            z-index: 1;
            width: 350px;
            height: 100%;
        }

        /*
  The sidebar styling has them "expanded" by default, we use CSS transforms to push them offscreen
  The toggleSidebar() function removes this class from the element in order to expand it.
*/
        .left.collapsed {
            transform: translateX(-295px);
        }

        .right.collapsed {
            transform: translateX(295px);
        }
    </style>
</head>

<body class="antialiased bg-slate-900">

    <div id="map">
        <div id="left" class="sidebar flex-center left collapsed">
            <div class="sidebar-content flex-center">
                <div class="fixed top-28 left-6 w-80 z-40 hidden md:flex flex-col gap-4 max-h-[calc(100vh-140px)]">
                    <div class="glass-panel p-8 rounded-[2.5rem]"> <!-- Fixed class -->
                        <h3
                            class="text-xs font-bold mb-6 uppercase text-white tracking-widest opacity-80 flex items-center">
                            <i class="fa-solid fa-money-bill mr-2 text-blue-400"></i>Fare Price
                        </h3>
                        <div class="space-y-4">
                            <div class="relative group">
                                <i
                                    class="fa-solid fa-circle-dot absolute left-4 top-1/2 -translate-y-1/2 text-[10px] text-blue-400"></i>
                                <input type="text" placeholder="Pick-up point" name="pickup" id="pickup" readonly
                                    class="w-full bg-white/10 border border-white/20 rounded-2xl pl-10 pr-4 py-3 text-xs text-white focus:bg-white/20 focus:border-blue-500/50 outline-none transition cursor-pointer">
                                <button onclick="getStartingPoint()"
                                    class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                                    <!-- Invisible button for functionality -->
                                </button>
                            </div>
                            <div class="relative group">
                                <i
                                    class="fa-solid fa-location-dot absolute left-4 top-1/2 -translate-y-1/2 text-[10px] text-red-400"></i>
                                <input type="text" placeholder="Destination" name="destination" id="destination"
                                    readonly
                                    class="w-full bg-white/10 border border-white/20 rounded-2xl pl-10 pr-4 py-3 text-xs text-white focus:bg-white/20 focus:border-red-500/50 outline-none transition cursor-pointer">
                                <button onclick="getDestination()"
                                    class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                                    <!-- Invisible button for functionality -->
                                </button>
                            </div>
                            <h3
                                class="text-xs font-bold mb-2 uppercase tracking-widest text-white/80 justify-center flex items-center">
                                Distance
                            </h3>
                            <div class="flex gap-2 items-center">
                                <input type="text" readonly name="distance" id="distance"
                                    class="w-full bg-white/10 border border-white/20 rounded-2xl px-4 py-3 text-xs text-white text-center"
                                    value="0">
                                <span class="text-xs font-bold uppercase tracking-widest text-white/70">KM</span>
                            </div>
                            <h3
                                class="text-xs font-bold mb-2 uppercase tracking-widest text-white/80 justify-center flex items-center">
                                Price
                            </h3>
                            <div class="space-y-3">
                                <div class="flex gap-2 items-center">
                                    <div class="relative flex-1">
                                        <i
                                            class="fa-solid fa-peso-sign absolute left-3 top-1/2 -translate-y-1/2 text-xs opacity-70 text-white/60"></i>
                                        <input type="text" readonly name="price-regular" id="price-regular"
                                            class="w-full bg-white/10 border border-white/20 rounded-2xl pl-8 pr-4 py-3 text-xs text-white text-center"
                                            value="0">
                                    </div>
                                    <span
                                        class="text-xs font-bold uppercase tracking-widest text-white/70">Regular</span>
                                </div>
                                <div class="flex gap-2 items-center">
                                    <div class="relative flex-1">
                                        <i
                                            class="fa-solid fa-peso-sign absolute left-3 top-1/2 -translate-y-1/2 text-xs opacity-70 text-white/60"></i>
                                        <input type="text" readonly name="price-discount" id="price-discount"
                                            class="w-full bg-white/10 border border-white/20 rounded-2xl pl-8 pr-4 py-3 text-xs text-white text-center"
                                            value="0">
                                    </div>
                                    <span
                                        class="text-xs font-bold uppercase tracking-widest text-white/70">Discount</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if (Auth::user())

                        <div
                            class="glass-panel p-6 rounded-[2rem] bg-gradient-to-br from-blue-600/30 to-transparent border-blue-500/30">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <p class="text-[9px] uppercase tracking-widest text-white/60 font-bold mb-1">Digital
                                        Wallet</p>
                                    <h2 class="text-2xl font-bold text-white">₱500.00</h2>
                                </div>
                                <i class="fa-solid fa-wallet text-blue-400 opacity-80"></i>
                            </div>
                            <button
                                class="w-full bg-white/20 border border-white/30 py-2.5 rounded-xl text-[10px] font-bold text-white uppercase hover:bg-white/30 transition">
                                + Top Up
                            </button>
                        </div>
                    @endif
                </div>
                <div class="sidebar-toggle rounded-rect left" onclick="toggleSidebar('left')">

                    &rarr;

                </div>

            </div>

        </div>

        <div id="right" class="sidebar flex-center right collapsed">

            <div class="sidebar-content flex-center">
                <div class="fixed top-28 right-6 w-80 z-40 hidden lg:flex flex-col gap-4 max-h-[calc(100vh-140px)]">
                    <a href="{{ route('tutorial') }}" class=" glass-panel p-5 rounded-3xl flex items-center space-x-4 border-yellow-500/30 group
            hover:bg-yellow-500/10 transition">
                        <div class=" w-10 h-10 bg-yellow-500/30 rounded-xl flex items-center justify-center
                        group-hover:rotate-12 transition">
                            <i class="fa-solid fa-wand-magic-sparkles text-yellow-500"></i>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-white">App Tutorial</p>
                            <p class="text-[9px] text-white/50 uppercase tracking-wider font-bold">New user? Start here
                            </p>
                        </div>
                    </a>

                    @if (Auth::user())

                        <div class="glass-panel p-6 rounded-[2.5rem] flex flex-col overflow-hidden">
                            <div class="flex justify-between items-center mb-6">
                                <h3 class="text-[10px] font-black uppercase tracking-widest text-white/60">Recent Receipts
                                </h3>
                                <button class="text-[10px] font-bold text-blue-400 hover:underline">History</button>
                            </div>
                            <div class="space-y-4 custom-scroll overflow-y-auto pr-2">
                                <div class="flex items-center justify-between group">
                                    <div class="flex items-center space-x-3">
                                        <div
                                            class="w-9 h-9 bg-white/10 rounded-lg flex items-center justify-center border border-white/10 group-hover:border-blue-500/30 transition">
                                            <i
                                                class="fa-solid fa-receipt text-[10px] text-white/60 group-hover:text-blue-400"></i>
                                        </div>
                                        <div>
                                            <p class="text-[11px] font-bold text-white">#INV-8821</p>
                                            <p class="text-[9px] text-white/40">Today, 8:45 AM</p>
                                        </div>
                                    </div>
                                    <span class="text-xs font-bold text-green-400">-₱22.50</span>
                                </div>
                                <div class="flex items-center justify-between group opacity-80">
                                    <div class="flex items-center space-x-3">
                                        <div
                                            class="w-9 h-9 bg-white/10 rounded-lg flex items-center justify-center border border-white/10">
                                            <i class="fa-solid fa-receipt text-[10px] text-white/60"></i>
                                        </div>
                                        <div>
                                            <p class="text-[11px] font-bold text-white">#INV-8819</p>
                                            <p class="text-[9px] text-white/40">Feb 4, 2026</p>
                                        </div>
                                    </div>
                                    <span class="text-xs font-bold text-green-400">-₱18.75</span>
                                </div>
                            </div>
                        </div>
                    @endif


                    <!-- Vehicle Tracking Info Panel -->
                    <div
                        class="glass-panel p-6 rounded-[2rem] bg-gradient-to-br from-green-600/30 to-transparent border-green-500/30">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <p class="text-[9px] uppercase tracking-widest text-white/60 font-bold mb-1">Live
                                    Vehicle Status</p>
                                <h2 class="text-lg font-bold text-white">GPS Tracking</h2>
                            </div>
                            <i class="fa-solid fa-satellite-dish text-green-400 opacity-80"></i>
                        </div>

                        <div id="gps-status" class="tracking-controls-panel text-center">
                            <div class="flex items-center justify-center space-x-2 mb-3">
                                <div class="w-2 h-2 bg-gray-400 rounded-full animate-pulse" id="gps-indicator"></div>
                                <span class="text-xs text-white/70" id="gps-status-text">GPS: Not active</span>
                            </div>
                            <div id="live-location-info" class="text-xs text-white/80 space-y-1">
                                <div class="flex justify-between">
                                    <span><i class="fa-solid fa-location-dot text-green-400 mr-1"></i> Position:</span>
                                    <span class="font-mono" id="current-coords">--, --</span>
                                </div>
                                <div class="flex justify-between">
                                    <span><i class="fa-solid fa-gauge-high text-blue-400 mr-1"></i> Accuracy:</span>
                                    <span id="current-accuracy">-- m</span>
                                </div>
                                <div class="flex justify-between">
                                    <span><i class="fa-regular fa-clock text-yellow-400 mr-1"></i> Updated:</span>
                                    <span id="update-time">--:--:--</span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 text-[10px] text-white/40 text-center">
                            <i class="fa-solid fa-map-marker-alt"></i> Click the GPS button (📍) on the map to track
                            your location
                        </div>
                    </div>
                </div>

                <div class="sidebar-toggle rounded-rect right" onclick="toggleSidebar('right')">

                    &larr;

                </div>

            </div>

        </div>
    </div>

    <header
        class="fixed top-6 left-6 right-6 z-50 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 pointer-events-none">
        <div class="glass-panel p-4 rounded-3xl pointer-events-auto flex items-center space-x-4">
            <div
                class="w-10 h-10 bg-blue-600 rounded-2xl flex items-center justify-center shadow-lg shadow-blue-600/30">
                <i class="fa-solid fa-bus text-white"></i>
            </div>
            <div>
                <h1 class="text-white font-bold text-sm">SmartCommute</h1>
            </div>
        </div>

        <div class="flex items-center space-x-3 pointer-events-auto">
            <div
                class="glass-panel px-4 py-2.5 rounded-full hidden md:flex items-center text-white text-xs font-medium">
                <i class="fa-solid fa-calendar-day mr-2 opacity-70"></i>
                <span id="current-date">Loading date...</span>
            </div>

            @if (Auth::user())
                <a href="{{ route('commuter.profile') }}">
                    <div
                        class="glass-panel w-10 h-10 rounded-full flex items-center justify-center text-white cursor-pointer hover:bg-white/10 transition">
                        <i class="fa-solid fa-user text-xs"></i>
                    </div>
                </a>
                <div class="flex items-center space-x-3 pointer-events-auto">
                    <button onclick="toggleLogoutModal()"
                        class="glass-panel px-5 py-2.5 rounded-full text-white text-xs font-bold uppercase tracking-wider hover:bg-red-500/20 transition">
                        Logout
                    </button>
                </div>
            @else
                <a href="{{ route('register') }}">
                    <div
                        class="glass-panel px-5 py-2.5 rounded-full text-white text-xs font-bold uppercase tracking-wider hover:bg-red-500/20 transition">
                        Sign up
                    </div>
                </a>
            @endif

        </div>
    </header>





    <!-- Logout Modal -->
    <div id="logout-modal"
        class="fixed inset-0 z-[100] flex items-center justify-center bg-black/70 backdrop-blur-sm opacity-0 pointer-events-none transition-all duration-300">
        <div class="glass-panel p-8 rounded-[2.5rem] w-full max-w-sm mx-4 text-center border-white/20 shadow-2xl transform scale-95 transition-transform duration-300"
            id="modal-content">
            <div class="w-16 h-16 bg-red-500/20 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fa-solid fa-right-from-bracket text-red-500 text-xl"></i>
            </div>

            <h3 class="text-xl font-bold text-white mb-2">Sign Out?</h3>
            <p class="text-sm text-white/60 mb-8">Are you sure you want to log out of SmartCommute?</p>

            <div class="flex gap-3">
                <button onclick=" toggleLogoutModal()"
                    class="flex-1 px-6 py-3 rounded-2xl bg-white/10 border border-white/20 text-white text-xs font-bold uppercase tracking-widest hover:bg-white/20 transition">
                    Cancel
                </button>
                <form action="{{ route('users.logout') }}" method="POST" class="flex-1">
                    @csrf
                    <button type="submit"
                        class="w-full px-6 py-3 rounded-2xl bg-red-600 text-white text-xs font-bold uppercase tracking-widest hover:bg-red-700 shadow-lg shadow-red-600/20 transition">
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Mobile Bottom Navigation -->
    <div class="fixed bottom-6 left-6 right-6 z-50 md:hidden">
        <div class="glass-panel p-4 rounded-3xl flex justify-around items-center">
            <i class="fa-solid fa-map-location-dot text-blue-400 text-lg"></i>
            <i class="fa-solid fa-wallet text-white/60 text-lg"></i>
            <div
                class="w-12 h-12 bg-blue-600 rounded-2xl flex items-center justify-center -mt-10 shadow-lg shadow-blue-600/40">
                <i class="fa-solid fa-bus text-white"></i>
            </div>
            <i class="fa-solid fa-receipt text-white/60 text-lg"></i>
            <i class="fa-solid fa-user text-white/60 text-lg"></i>
        </div>
    </div>

    <script>

        const pusherKey = '{{ env("PUSHER_APP_KEY") }}';
        const pusherCluster = '{{ env("PUSHER_APP_CLUSER") }}';

        window.Pusher = Pusher;
        window.Echo = new Echo({
            broadcaster: 'pusher',
            key: pusherKey,
            cluster: pusherCluster,
            forceTLS: true
        });



        maplibregl.setRTLTextPlugin(
            'https://unpkg.com/@mapbox/mapbox-gl-rtl-text@0.3.0/dist/mapbox-gl-rtl-text.js'
        );

        const bounds = [
            [123.77516124821591, 10.229235293025951],
            [123.91768276426876, 10.332535160307074]
        ];

        const map = new maplibregl.Map({
            container: 'map',
            style: 'https://tiles.openfreemap.org/styles/bright',
            center: [123.79, 10.24],
            zoom: 13,
            rollEnabled: true,
            maxBounds: bounds
        });

        function toggleSidebar(id) {
            const elem = document.getElementById(id);
            const classes = elem.className.split(' ');
            const collapsed = classes.indexOf('collapsed') !== -1;

            const padding = {};

            if (collapsed) {
                classes.splice(classes.indexOf('collapsed'), 1);

                padding[id] = 300;
                map.easeTo({
                    padding,
                    duration: 1000
                });
            } else {
                padding[id] = 0;
                classes.push('collapsed');

                map.easeTo({
                    padding,
                    duration: 1000
                });
            }
            elem.className = classes.join(' ');
        }

        map.addControl(new maplibregl.NavigationControl(), 'bottom-right');
        const geolocate = new maplibregl.GeolocateControl({
            positionOptions: {
                enableHighAccuracy: true,
                timeout: 6000,
                maximumAge: 0
            },
            trackUserLocation: true,
            showUserLocation: true,
            showAccuracyCircle: true,
            fitBoundsOptions: {
                maxZoom: 15,
                duration: 1000
            }
        });
        map.addControl(geolocate, 'bottom-right');

        let vehicleMarker = null;
        let vehiclePathCoordinates = [];
        let watchId = null;
        let currentLocation = null;
        let currentVehicleId = 'vehicle-{{ Auth::id() ?? "guest" }}';

        const gpsIndicator = document.getElementById('gps-indicator');
        const gpsStatusText = document.getElementById('gps-status-text');
        const currentCoords = document.getElementById('current-coords');
        const currentAccuracy = document.getElementById('current-accuracy');
        const updateTime = document.getElementById('update-time');

        // Function to start broadcasting location
        function startBroadcastingLocation() {
            if (!navigator.geolocation) {
                console.error('Geolocation not supported');
                return;
            }

            watchId = navigator.geolocation.watchPosition(
                async (position) => {
                    const { latitude, longitude, accuracy, speed } = position.coords;
                    currentLocation = { latitude, longitude, accuracy, speed };

                    // Update local map marker
                    updateVehicleMarker(latitude, longitude);

                    // BROADCAST TO ALL USERS via Pusher
                    try {
                        await fetch(`/api/track/vehicle/broadcast`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                vehicle_id: '{{ "vehicle-" . Auth::id() ?? "default-vehicle" }}',
                                latitude: latitude,
                                longitude: longitude,
                                accuracy: accuracy,
                                speed: speed,
                                timestamp: new Date().toISOString()
                            })
                        });
                    } catch (error) {
                        console.error('Error broadcasting location:', error);
                    }
                },
                (error) => {
                    console.error('Geolocation error:', error);
                },
                {
                    enableHighAccuracy: true,
                    timeout: 5000,
                    maximumAge: 0
                }
            );
        }

        // Function to update vehicle marker on map
        function updateVehicleMarker(latitude, longitude) {
            if (!vehicleMarker) {
                // Create marker if it doesn't exist
                const markerElement = document.createElement('div');
                markerElement.className = 'custom-vehicle-marker';
                markerElement.innerHTML = '<i class="fa-solid fa-bus"></i>';
                vehicleMarker = new maplibregl.Marker({ element: markerElement })
                    .setLngLat([longitude, latitude])
                    .addTo(map);
            } else {
                vehicleMarker.setLngLat([longitude, latitude]);
            }
        }

        // function updateLiveDate() {
        //     const dateElement = document.getElementById('current-date');
        //     const now = new Date();
        //     const options = {
        //         weekday: 'long',
        //         year: 'numeric',
        //         month: 'long',
        //         day: 'numeric'
        //     };
        //     dateElement.textContent = now.toLocaleDateString('en-US', options);
        // }

        // updateLiveDate();

        // setInterval(updateLiveDate, 3600000);

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

        window.onclick = function (event) {
            const modal = document.getElementById('logout-modal');
            if (event.target == modal) {
                toggleLogoutModal();
            }
        }

        map.on('load', () => {
            // Create custom vehicle marker
            const markerElement = document.createElement('div');
            markerElement.className = 'vehicle-marker';
            markerElement.innerHTML = '<i class="fa-solid fa-bus" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 14px; color: white;"></i>';

            vehicleMarker = new maplibregl.Marker({ element: markerElement })
                .setLngLat([123.79, 10.24])
                .addTo(map);

            // Add source for vehicle path
            map.addSource('vehicle-path', {
                type: 'geojson',
                data: {
                    type: 'FeatureCollection',
                    features: []
                }
            });

            // Add layer for vehicle path
            map.addLayer({
                id: 'vehicle-path-line',
                type: 'line',
                source: 'vehicle-path',
                paint: {
                    'line-color': '#3b82f6',
                    'line-width': 3,
                    'line-opacity': 0.7,
                    'line-dasharray': [2, 2]
                }
            });

            vehicleMarker.remove();
        });

        geolocate.on('geolocate', (position) => {
            const { latitude, longitude, accuracy } = position.coords;

            console.log('📍 GPS fix acquired:', { latitude, longitude, accuracy });

            // Update UI
            gpsIndicator.className = 'w-2 h-2 bg-green-500 rounded-full animate-pulse';
            gpsStatusText.textContent = 'GPS: Active';
            currentCoords.textContent = `${latitude.toFixed(6)}, ${longitude.toFixed(6)}`;
            currentAccuracy.textContent = `${accuracy.toFixed(1)} m`;
            updateTime.textContent = new Date().toLocaleTimeString();

            // Show and update vehicle marker
            if (vehicleMarker) {
                vehicleMarker.setLngLat([longitude, latitude]);
                vehicleMarker.addTo(map);
            }

            // Update path
            vehiclePathCoordinates.push([longitude, latitude]);
            if (vehiclePathCoordinates.length > 100) {
                vehiclePathCoordinates.shift();
            }

            const pathSource = map.getSource('vehicle-path');
            if (pathSource && vehiclePathCoordinates.length > 1) {
                pathSource.setData({
                    type: 'FeatureCollection',
                    features: [{
                        type: 'Feature',
                        geometry: {
                            type: 'LineString',
                            coordinates: vehiclePathCoordinates
                        },
                        properties: {}
                    }]
                });
            }

            // Get CSRF token safely
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (!csrfToken) {
                console.error('CSRF token not found');
                return;
            }

            // IMPORTANT: Include vehicle_id in the request body
            const requestData = {
                vehicle_id: currentVehicleId,  // ← THIS IS CRITICAL - ADD THIS!
                latitude: latitude,
                longitude: longitude,
                speed: 0,
                accuracy: accuracy || 0
            };

            console.log('Sending location data:', requestData); // Debug log

            fetch(`/track/vehicle/broadcast`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken.content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(requestData)
            })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(errorData => {
                            console.error('Server error:', errorData);
                            throw new Error(JSON.stringify(errorData));
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Location sent successfully:', data);
                })
                .catch(error => console.error('Error broadcasting:', error));
        });

        geolocate.on('error', (error) => {
            console.error('Geolocation error:', error);
            gpsIndicator.className = 'w-2 h-2 bg-red-500 rounded-full';
            gpsStatusText.textContent = 'GPS: Error - ' + error.message;
        });

        geolocate.on('outofmaxbounds', () => {
            console.log('GPS out of bounds');
        });

        geolocate.on('trackuserlocationstart', () => {
            console.log('Tracking started');
            gpsIndicator.className = 'w-2 h-2 bg-yellow-500 rounded-full animate-pulse';
            gpsStatusText.textContent = 'GPS: Acquiring...';
        });

        geolocate.on('trackuserlocationend', () => {
            console.log('Tracking ended');
            gpsIndicator.className = 'w-2 h-2 bg-gray-400 rounded-full';
            gpsStatusText.textContent = 'GPS: Not active';
        });

        // Listen for ALL vehicle location updates (this runs for every user)
        window.Echo.channel('vehicle-locations')
            .listen('vehicle-location-updated', (e) => {
                console.log('🚍 Vehicle location update:', e);

                // Update or create marker for this vehicle
                updateVehicleMarkerOnMap(e.vehicleId, e.coordinates[0], e.coordinates[1]);

                // Update vehicle info panel if visible
                updateVehicleInfoPanel(e.vehicleId, e.coordinates, e.speed, e.timestamp);
            });

        const vehicleMarkers = new Map();

        function updateVehicleMarkerOnMap(vehicleId, longitude, latitude) {
            if (vehicleMarkers.has(vehicleId)) {
                // Update existing marker
                const marker = vehicleMarkers.get(vehicleId);
                marker.setLngLat([longitude, latitude]);
            } else {
                // Create new marker for this vehicle
                const markerElement = document.createElement('div');
                markerElement.className = 'custom-vehicle-marker';
                markerElement.innerHTML = '<i class="fa-solid fa-bus"></i>';
                markerElement.style.backgroundColor = getRandomColor(vehicleId);

                const marker = new maplibregl.Marker({ element: markerElement })
                    .setLngLat([longitude, latitude])
                    .addTo(map);

                vehicleMarkers.set(vehicleId, marker);

                // Add popup with vehicle info
                const popup = new maplibregl.Popup({ offset: 25 })
                    .setHTML(`
                <div style="background: #1e293b; color: white; padding: 8px; border-radius: 8px;">
                    <strong>Vehicle: ${vehicleId}</strong><br>
                    Last update: ${new Date().toLocaleTimeString()}
                </div>
            `);

                marker.setPopup(popup);
            }
        }

        function getRandomColor(vehicleId) {
            // Generate consistent color based on vehicle ID
            const colors = ['#3b82f6', '#ef4444', '#10b981', '#f59e0b', '#8b5cf6'];
            let hash = 0;
            for (let i = 0; i < vehicleId.length; i++) {
                hash = vehicleId.charCodeAt(i) + ((hash << 5) - hash);
            }
            return colors[Math.abs(hash) % colors.length];
        }

        // Fare calculation functions (existing)
        let marker = new maplibregl.Marker({ draggable: false });



        // Function to fetch and display active vehicles from database
        function refreshActiveVehicles() {
            fetch('/track/vehicles/active').then(response => response.json()).then(data => {
                console.log('Active vehicles from DB:', data);
                if (Array.isArray(data)) {
                    data.forEach(vehicle => {
                        updateVehicleMarkerOnMap(
                            vehicle.vehicle_id,
                            vehicle.longitude,
                            vehicle.latitude
                        );
                    });
                    console.log(`✅ Loaded ${data.length} active vehicles`);
                }
            }).catch(error => console.error('Error fetching active vehicles:', error));
        }

        // Refresh active vehicles every 10 seconds
        setInterval(refreshActiveVehicles, 10000);

        // Initial refresh after 2 seconds
        setTimeout(refreshActiveVehicles, 2000);

        const distanceCoordinate = document.getElementById('distance');
        const pickup = document.getElementById('pickup');
        const destination = document.getElementById('destination');
        const priceRegular = document.getElementById('price-regular');
        const priceDiscount = document.getElementById('price-discount');

        pickup.value = "";
        destination.value = "";
        distanceCoordinate.value = "0";
        priceRegular.value = "0";
        priceDiscount.value = "0";

        let rates = {!! $rates !!};

        function getFareRate(km) {
            if (km >= 1 && km < 50) {
                for (let i = 0; i < 50; i++) {
                    if (km == rates[i]['km']) {
                        return rates[i];
                    }
                }
            }
            if (km < 1) {
                return rates[0];
            }
            if (km >= 50) {
                return rates[49];
            }
            return rates[0];
        }

        const geocoderApi = {
            forwardGeocode: async (config) => {
                const features = [];
                try {
                    const request =
                        `https://nominatim.openstreetmap.org/search?q=${config.query}&format=geojson&polygon_geojson=1&addressdetails=1`;
                    const response = await fetch(request);
                    const geojson = await response.json();
                    for (const feature of geojson.features) {
                        const center = [
                            feature.bbox[0] + (feature.bbox[2] - feature.bbox[0]) / 2,
                            feature.bbox[1] + (feature.bbox[3] - feature.bbox[1]) / 2
                        ];
                        const point = {
                            type: 'Feature',
                            geometry: {
                                type: 'Point',
                                coordinates: center
                            },
                            place_name: feature.properties.display_name,
                            properties: feature.properties,
                            text: feature.properties.display_name,
                            place_type: ['place'],
                            center
                        };
                        features.push(point);
                    }
                } catch (e) {
                    console.error(`Failed to forwardGeocode with error: ${e}`);
                }
                return { features };
            }
        };

        const startPoint = {
            'type': 'FeatureCollection',
            'features': [{
                'type': 'Feature',
                'geometry': {
                    'type': 'Point',
                }
            }]
        };

        map.on('load', () => {
            map.addSource('point', {
                'type': 'geojson',
                'data': startPoint
            });

            map.addLayer({
                'id': 'point',
                'type': 'circle',
                'source': 'point',
                'paint': {
                    'circle-radius': 10,
                    'circle-color': '#3887be'
                }
            });

            map.setLayoutProperty('label_country', 'text-field', [
                'format',
                ['get', 'name_en'],
                { 'font-scale': 1.2 },
                '\n',
                {},
                ['get', 'name'],
                {
                    'font-scale': 0.8,
                    'text-font': [
                        'literal',
                        ['Noto Sans Regular']
                    ]
                }
            ]);

            marker.setLngLat([0, 0]).addTo(map);
        });

        map.addControl(
            new MaplibreGeocoder(geocoderApi, {
                maplibregl
            }), 'bottom-left'
        );

        let destinationLat = null;
        let destinationLng = null;
        let pickupLng = null;
        let pickupLat = null;

        function getStartingPoint() {
            map.once('mousemove', (e) => {
                map.getCanvas().style.cursor = 'crosshair';
            });

            map.once('click', (e) => {
                const coords = e.lngLat;

                startPoint.features[0].geometry.coordinates = [coords.lng, coords.lat];
                map.getSource('point').setData(startPoint);

                let coordinates = `${e.lngLat.lng} ${e.lngLat.lat}`;
                let coordinatesArray = coordinates.split(" ");

                pickupLng = coordinatesArray[0];
                pickupLat = coordinatesArray[1];

                pickup.value = coordinates;

                map.getCanvas().style.cursor = 'pointer';

                if (destinationLat && destinationLng) {
                    let distanceKM = haversineDistanceKM(pickupLat, pickupLng, destinationLat, destinationLng);
                    distanceCoordinate.value = distanceKM.toFixed(2);

                    let rate = getFareRate(parseInt(distanceKM));
                    priceRegular.value = rate['regular'];
                    priceDiscount.value = rate['discount'];
                }
            });
        }

        function getDestination() {
            map.once('mousemove', (e) => {
                map.getCanvas().style.cursor = 'crosshair';
            });

            map.once('click', (e) => {
                let longLat = e.lngLat;

                let coordinates = `${e.lngLat.lng} ${e.lngLat.lat}`;
                let coordinatesArray = coordinates.split(" ");

                destinationLng = coordinatesArray[0];
                destinationLat = coordinatesArray[1];

                marker.setLngLat([longLat.lng, longLat.lat]);

                destination.value = coordinates;

                map.getCanvas().style.cursor = 'pointer';

                if (pickupLat && pickupLng) {
                    let distanceKM = haversineDistanceKM(pickupLat, pickupLng, destinationLat, destinationLng);
                    distanceCoordinate.value = distanceKM.toFixed(2);

                    let rate = getFareRate(parseInt(distanceKM));
                    priceRegular.value = rate['regular'];
                    priceDiscount.value = rate['discount'];
                }
            });
        }

        function haversineDistanceKM(lat1Deg, lon1Deg, lat2Deg, lon2Deg) {
            function toRad(degree) {
                return degree * Math.PI / 180;
            }

            const lat1 = toRad(lat1Deg);
            const lon1 = toRad(lon1Deg);
            const lat2 = toRad(lat2Deg);
            const lon2 = toRad(lon2Deg);

            const { sin, cos, sqrt, atan2 } = Math;

            const R = 6371;
            const dLat = lat2 - lat1;
            const dLon = lon2 - lon1;
            const a = sin(dLat / 2) * sin(dLat / 2) +
                cos(lat1) * cos(lat2) * sin(dLon / 2) * sin(dLon / 2);
            const c = 2 * atan2(sqrt(a), sqrt(1 - a));
            const d = R * c;
            return d;
        }

        // Optional: Auto - start GPS tracking when page loads
        setTimeout(() => {
            geolocate.trigger();
        }, 1000);

        // Initialize the functions
    </script>

</body>

</html>