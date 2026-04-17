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



    <div class="max-w-7xl mx-auto p-6 md:p-10">
        <main class="transition-all duration-300" :class="open ? 'ml-72' : 'ml-20'">

            <div
                class="max-w-md mx-auto bg-slate-900/50 backdrop-blur-xl border border-white/10 rounded-[2rem] p-8 shadow-2xl">

                <div class="flex items-center gap-3 mb-8">
                    <div class="p-2 bg-blue-500/20 rounded-lg">
                        <i class="fa-solid fa-route text-blue-400"></i>
                    </div>
                    <h3 class="text-sm font-bold uppercase tracking-widest text-white/90">
                        Create New Route
                    </h3>
                </div>

                <form method="POST" action="{{ route('routes.update', $route) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div class="relative space-y-4">
                        <div class="space-y-2">
                            <label class="text-[10px] font-bold text-white/40 uppercase ml-2">Starting point</label>
                            <div class="flex gap-2">
                                <div class="relative flex-grow">
                                    <i
                                        class="fa-solid fa-circle-dot absolute left-4 top-1/2 -translate-y-1/2 text-[10px] text-blue-400"></i>
                                    <input type="text" name="starting_point" placeholder="Set start point..." id="start"
                                        value="{{ $route->starting_point }}"
                                        class="w-full bg-white/5 border border-white/10 rounded-2xl pl-10 pr-4 py-3.5 text-sm text-white/70 cursor-default">
                                </div>
                                <div
                                    class="flex items-center px-4 bg-blue-500/10 hover:bg-blue-500/20 border border-blue-500/20 rounded-2xl text-blue-400 transition-all">
                                    <i class="fa-solid fa-location-crosshairs"></i>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="text-[10px] font-bold text-white/40 uppercase ml-2">Destination</label>
                            <div class="flex gap-2">
                                <div class="relative flex-grow">
                                    <i
                                        class="fa-solid fa-location-dot absolute left-4 top-1/2 -translate-y-1/2 text-[10px] text-red-400"></i>
                                    <input type="text" placeholder="Set destination..." id="end" name="destination"
                                        value="{{ $route->destination }}" class=" w-full bg-white/5 border border-white/10 rounded-2xl pl-10 pr-4 py-3.5
                                        text-sm text-white/70 cursor-default">
                                </div>
                                <div
                                    class="flex items-center px-4 bg-red-500/10 hover:bg-red-500/20 border border-red-500/20 rounded-2xl text-red-400 transition-all">
                                    <i class="fa-solid fa-map-pin"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="submit"
                        class="w-full mt-4 bg-blue-600 hover:bg-blue-500 text-white py-4 rounded-2xl text-xs font-bold uppercase tracking-widest shadow-lg shadow-blue-500/20 transition-all active:scale-[0.98]">
                        Update Route
                    </button>
                </form>
            </div>
        </main>
    </div>
</body>

</html>