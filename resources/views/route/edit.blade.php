<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartCommute | Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel='stylesheet' href='https://unpkg.com/maplibre-gl@5.18.0/dist/maplibre-gl.css' />
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
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



<body class="antialiased" x-data="{ open: true }">

    @include('layout.sidebar');

    <div class="max-w-7xl mx-auto p-4 md:p-10 space-y-8">

        <main class="grid grid-cols-1 lg:grid-cols-4 gap-8" :class=" open ? 'ml-72' : 'ml-20'" class=" sidebar-transition
            p-8 md:p-12 min-h-screen">



            <aside class=" lg:col-span-1 space-y-8">


                <div class="glass glass-inset p-8 rounded-[2.5rem]">
                    <h3 class="text-xs font-bold mb-6 uppercase tracking-widest opacity-70 flex items-center">
                        <i class="fa-solid fa-calculator mr-2 text-blue-400"></i> Update Route
                    </h3>
                    <form method="POST" action="{{ route('routes.update', $route->id) }}">
                        @csrf
                        <div class="space-y-4">
                            <div class="relative group">
                                <i
                                    class="fa-solid fa-pen absolute left-4 top-1/2 -translate-y-1/2 text-[10px] text-blue-400"></i>
                                <input type="text" placeholder="Route name" name="name" value="{{ $route->name }}"
                                    class="w-full bg-white/5 border border-white/10 rounded-2xl pl-10 pr-4 py-3 text-xs focus:bg-white/10 focus:border-blue-500/50 outline-none transition">
                            </div>
                            <div class="relative group">
                                <i
                                    class="fa-solid fa-circle-dot absolute left-4 top-1/2 -translate-y-1/2 text-[10px] text-blue-400"></i>
                                <input type="text" name="starting_point" placeholder="Starting point" id="start"
                                    readonly value="{{ $route->starting_point }}"
                                    class="w-full bg-white/5 border border-white/10 rounded-2xl pl-10 pr-4 py-3 text-xs focus:bg-white/10 focus:border-blue-500/50 outline-none transition">
                            </div>
                            <button type="button" onclick="getStartingPoint()"
                                class="w-full bg-white/5 border border-white/10 py-3 rounded-2xl text-[10px] font-bold uppercase hover:bg-white/10 transition-colors">
                                Add Starting point
                            </button>
                            <div class="relative group">
                                <i
                                    class="fa-solid fa-location-dot absolute left-4 top-1/2 -translate-y-1/2 text-[10px] text-red-400"></i>
                                <input type="text" type="text" placeholder="Destination" id="end" name="destination"
                                    readonly value="{{ $route->destination }}"
                                    class="w-full bg-white/5 border border-white/10 rounded-2xl pl-10 pr-4 py-3 text-xs focus:bg-white/10 focus:border-red-500/50 outline-none transition">
                            </div>
                            <button type="button" onclick="getDestination()"
                                class="w-full bg-white/5 border border-white/10 py-3 rounded-2xl text-[10px] font-bold uppercase hover:bg-white/10 transition-colors">
                                Add Destination
                            </button>
                            <button type="submit"
                                class="w-full bg-white text-black border border-white/10 py-3 rounded-2xl text-[10px] font-bold uppercase hover:bg-green-50 transition-colors">
                                Update Route
                            </button>
                        </div>
                    </form>
                </div>
            </aside>

            <div id="map"
                class="lg:col-span-2 relative h-[550px] glass rounded-[3rem] overflow-hidden border border-white/10 shadow-inner group">
            </div>

            {{--
            <pre id="info"></pre> --}}
        </main>
    </div>


    <script>
        var isClicked = false;

        let startCoords = document.getElementById('start').value;
        let endCoords = document.getElementById('end').value;

        let startArray = startCoords.split(" ");
        let endArray = endCoords.split(" ");

        let startLong = startCoords.split(" ")[0];
        let startLat = startCoords.split(" ")[1];

        let endLong = endCoords.split(" ")[0];
        let endLat = endCoords.split(" ")[1];

        let marker = new maplibregl.Marker({ draggable: false });

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
                        'coordinates': startArray
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

            marker.setLngLat(endArray).addTo(map);
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

        map.addControl(draw, 'top-left');

        function getStartingPoint() {
            map.once('mousemove', (e) => {
                // UI indicator for clicking/hovering a point on the map
                map.getCanvas().style.cursor = 'crosshair';
            });

            map.once('click', (e) => {

                const coords = e.lngLat;

                startPoint.features[0].geometry.coordinates = [coords.lng, coords.lat];
                map.getSource('point').setData(startPoint);

                let coordinates = `${e.lngLat.lng}, ${e.lngLat.lat}`;

                document.getElementById('start').value = coordinates;

                map.getCanvas().style.cursor = 'pointer';
            });
        }

        function getDestination() {
            map.once('mousemove', (e) => {
                // UI indicator for clicking/hovering a point on the map
                map.getCanvas().style.cursor = 'crosshair';
            });

            map.once('click', (e) => {
                let longLat = e.lngLat;

                let coordinates = `${e.lngLat.lng}, ${e.lngLat.lat} `;

                marker.setLngLat([longLat.lng, longLat.lat]);

                document.getElementById('end').value = coordinates;

                map.getCanvas().style.cursor = 'pointer';
            });
        }

    </script>
</body>

</html>