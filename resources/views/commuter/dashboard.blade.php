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
    <script
        src="https://cdn.jsdelivr.net/npm/@watergis/maplibre-gl-terradraw@1.0.1/dist/maplibre-gl-terradraw.umd.js"></script>
    <script src="https://unpkg.com/@maplibre/maplibre-gl-geocoder@1.5.0/dist/maplibre-gl-geocoder.min.js"></script>
    <link rel="stylesheet"
        href="https://unpkg.com/@maplibre/maplibre-gl-geocoder@1.5.0/dist/maplibre-gl-geocoder.css" />
    <link <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/@watergis/maplibre-gl-terradraw@1.0.1/dist/maplibre-gl-terradraw.css" />
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
                    <h2 class="text-2xl font-bold text-white">₱500.00</h2>
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
                <div class="glass glass-inset p-8 rounded-[2.5rem]">
                    <h3 class="text-xs font-bold mb-6 uppercase tracking-widest opacity-70 flex items-center">
                        <i class="fa-solid fa-money-bill mr-2 text-blue-400"></i>Fare Price
                    </h3>
                    <div class="space-y-4">
                        <div class="relative group">
                            <i
                                class="fa-solid fa-circle-dot absolute left-4 top-1/2 -translate-y-1/2 text-[10px] text-blue-400"></i>
                            <input type="text" placeholder="Pick-up point" name="pickup" id="pickup"
                                class="hidden w-full bg-white/5 border border-white/10 rounded-2xl pl-10 pr-4 py-3 text-xs focus:bg-white/10 focus:border-blue-500/50 outline-none transition">
                            <button onclick="getStartingPoint()"
                                class="w-full bg-white/5 border border-white/10 py-3 rounded-2xl text-[10px] font-bold uppercase hover:bg-white/10 transition-colors">
                                Add pick-up point
                            </button>
                        </div>
                        <div class="relative group">
                            <i
                                class="fa-solid fa-location-dot absolute left-4 top-1/2 -translate-y-1/2 text-[10px] text-red-400"></i>
                            <input type="text" placeholder="Destination" name="destination" id="destination"
                                class="hidden w-full bg-white/5 border border-white/10 rounded-2xl pl-10 pr-4 py-3 text-xs focus:bg-white/10 focus:border-red-500/50 outline-none transition">
                            <button onclick="getDestination()"
                                class="w-full bg-white/5 border border-white/10 py-3 rounded-2xl text-[10px] font-bold uppercase hover:bg-white/10 transition-colors">
                                Add destination
                            </button>
                        </div>
                        <h3
                            class="text-xs font-bold mb-6 uppercase tracking-widest text-white justify-center flex items-center">
                            Distance
                        </h3>
                        <div class="flex gap-2 items-center">
                            <input type="text" readonly name="distance" id="distance"
                                class="w-full bg-white/5 border border-white/10 rounded-2xl pl-10 pr-4 py-3 text-xs focus:bg-white/10 focus:border-red-500/50 outline-none transition">
                            <h3 class="text-xs font-bold uppercase tracking-widest opacity-70 ">
                                KM
                            </h3>
                        </div>
                        <h3
                            class="text-xs font-bold mb-6 uppercase tracking-widest text-white justify-center flex items-center">
                            Price
                        </h3>
                        <div class="flex gap-2 items-center">
                            <input type="text" readonly name="price-regular" id="price-regular"
                                class="w-20 bg-white/5 border border-white/10 rounded-2xl pl-10 pr-4 py-3 text-xs focus:bg-white/10 focus:border-red-500/50 outline-none transition">
                            <i class="fa-solid fa-peso-sign mr-5 opacity-70"></i>
                            <h3 class="text-xs font-bold uppercase tracking-widest opacity-70 ">
                                Regular
                            </h3>
                        </div>
                        <div class="flex gap-2 items-center">
                            <input type="text" readonly name="price-regular" id="price-discount"
                                class="w-20 bg-white/5 border border-white/10 rounded-2xl pl-10 pr-4 py-3 text-xs focus:bg-white/10 focus:border-red-500/50 outline-none transition">
                            <i class="fa-solid fa-peso-sign mr-5 opacity-70"></i>
                            <h3 class="text-xs font-bold uppercase tracking-widest opacity-70">
                                Student/ELDERLY/DISABLED
                            </h3>
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


            <pre id="info"></pre>

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

    <script>
        let marker = new maplibregl.Marker({ draggable: false });

        const distanceCoordinate = document.getElementById('distance');
        const pickupCoordinate = document.getElementById('pickup');
        const destinationCoordinates = document.getElementById('destination');
        const priceRegular = document.getElementById('price-regular');
        const priceDiscount = document.getElementById('price-discount');

        distanceCoordinate.value = "0";
        priceRegular.value = "0";
        priceDiscount.value = "0";

        rates = {!! $rates !!};

        function getFareRate(km) {

            if (km >= 1 && km < 50) {
                for (let i = 0; i < 50; i++) {
                    if (km == rates[i]['km']) {
                        fareRate = rates[i];
                        break;
                    }
                }
            }

            if (km < 1) {
                fareRate = rates[0];
            }

            if (km >= 50) {
                fareRate = rates[49];
            }

            return fareRate;
        }

        maplibregl.setRTLTextPlugin(
            'https://unpkg.com/@mapbox/mapbox-gl-rtl-text@0.3.0/dist/mapbox-gl-rtl-text.js'
        );

        const bounds = [
            [123.77516124821591, 10.229235293025951],
            [123.91768276426876, 10.332535160307074]
        ];

        const map = new maplibregl.Map({
            container: 'map', // container id
            style: 'https://tiles.openfreemap.org/styles/bright', // style URL
            center: [123.79, 10.24], // starting position [lng, lat]
            zoom: 13, // starting zoom
            rollEnabled: true,
            maxBounds: bounds
        });


        const geocoderApi = {
            forwardGeocode: async (config) => {
                const features = [];
                try {
                    const request =
                        `https://nominatim.openstreetmap.org/search?q=${config.query
                        }&format=geojson&polygon_geojson=1&addressdetails=1`;
                    const response = await fetch(request);
                    const geojson = await response.json();
                    for (const feature of geojson.features) {
                        const center = [
                            feature.bbox[0] +
                            (feature.bbox[2] - feature.bbox[0]) / 2,
                            feature.bbox[1] +
                            (feature.bbox[3] - feature.bbox[1]) / 2
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

                return {
                    features
                };
            }
        };

        const startPoint = {
            'type': 'FeatureCollection',
            'features': [
                {
                    'type': 'Feature',
                    'geometry': {
                        'type': 'Point',
                    }
                }
            ]
        };

        const endPoint = {
            'type': 'FeatureCollection',
            'features': [
                {
                    'type': 'Feature',
                    'geometry': {
                        'type': 'Point',
                    }
                }
            ]
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
            })
        );

        // Add zoom and rotation controls to the map.
        map.addControl(new maplibregl.NavigationControl({
            visualizePitch: true,
            visualizeRoll: true,
            showZoom: true,
            showCompass: true
        }));

        // Add geolocate control to the map.
        map.addControl(
            new maplibregl.GeolocateControl({
                positionOptions: {
                    enableHighAccuracy: true
                },
                trackUserLocation: true
            })
        );


        destinationLat = null;
        destinationLng = null;
        pickupLng = null;
        pickupLat = null;

        function getStartingPoint() {
            map.once('mousemove', (e) => {
                // UI indicator for clicking/hovering a point on the map
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
                    distanceKM = (haversineDistanceKM(pickupLat, pickupLng, destinationLat, destinationLng)) ? haversineDistanceKM(pickupLat, pickupLng, destinationLat, destinationLng) : null;
                    distanceCoordinate.value = distanceKM;

                    rate = getFareRate(parseInt(distanceKM));

                    priceRegular.value = rate['regular'];
                    priceDiscount.value = rate['discount'];
                }
            });
        }

        getStartingPoint();

        function getDestination() {
            map.once('mousemove', (e) => {
                // UI indicator for clicking/hovering a point on the map
                map.getCanvas().style.cursor = 'crosshair';
            });

            map.once('click', (e) => {
                let longLat = e.lngLat;

                let coordinates = `${e.lngLat.lng} ${e.lngLat.lat}`;
                let coordinatesArray = coordinates.split(" ");

                destinationLng = coordinatesArray[0];
                destinationLat = coordinatesArray[1];

                marker.setLngLat([longLat.lng, longLat.lat]);

                destinationCoordinates.value = coordinates;

                map.getCanvas().style.cursor = 'pointer';

                if (pickupLat && pickupLng) {
                    distanceKM = (haversineDistanceKM(pickupLat, pickupLng, destinationLat, destinationLng)) ? haversineDistanceKM(pickupLat, pickupLng, destinationLat, destinationLng) : null;
                    distanceCoordinate.value = distanceKM;

                    rate = getFareRate(parseInt(distanceKM));

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

            const R = 6371; // earth radius in km 
            const dLat = lat2 - lat1;
            const dLon = lon2 - lon1;
            const a = sin(dLat / 2) * sin(dLat / 2)
                + cos(lat1) * cos(lat2)
                * sin(dLon / 2) * sin(dLon / 2);
            const c = 2 * atan2(sqrt(a), sqrt(1 - a));
            const d = R * c;
            return d; // distance in km
        }

    </script>
</body>

</html>
