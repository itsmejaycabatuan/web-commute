<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartCommute | Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap');

        body {
            background: #050505;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #fff;
            overflow-x: hidden;
        }

        .glass {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .sidebar-transition {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
    </style>
</head>

<body x-data="{ open: true }">

    @include('layout.sidebar')

    <main :class="open ? 'ml-72' : 'ml-20'" class="sidebar-transition p-8 md:p-12 min-h-screen">
        <div class="max-w-6xl">
            <header class="mb-10">
                <h2 class="text-3xl font-black tracking-tight mb-2">Dashboard</h2>
                <p class="text-gray-500 text-sm">Administrator overview — users, drivers, and application pipeline at a glance.</p>
            </header>

            @isset($adminStats)
                @include('admin.partials.stats-grid', ['stats' => $adminStats])
            @endisset

            <div class="glass rounded-2xl border border-white/10 p-8">
                <h3 class="text-xs font-black uppercase tracking-widest text-blue-400 mb-2">Quick links</h3>
                <p class="text-sm text-gray-500 mb-6">Use the sidebar to manage commuters, review driver applications (especially pending), routes, and fares.</p>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('admin.commuters.index') }}"
                        class="inline-flex items-center px-4 py-2 rounded-xl text-[10px] font-bold uppercase tracking-widest bg-white/10 hover:bg-white/15 text-white transition">
                        Manage PUJ commuter
                    </a>
                    <a href="{{ route('admin.drivers.index') }}"
                        class="inline-flex items-center px-4 py-2 rounded-xl text-[10px] font-bold uppercase tracking-widest bg-amber-500/20 hover:bg-amber-500/30 text-amber-200 border border-amber-500/30 transition">
                        PUJ drivers &amp; pending
                    </a>
                </div>
            </div>
        </div>
    </main>

</body>

</html>
