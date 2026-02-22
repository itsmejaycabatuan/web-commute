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

    @include('layout.sidebar');

    <main :class="open ? 'ml-72' : 'ml-20'" class="sidebar-transition p-8 md:p-12 min-h-screen">
        <div class="max-w-5xl">
            <header class="mb-12">
                <h2 class="text-3xl font-black tracking-tight mb-2">Routes</h2>
                <p class="text-gray-500 text-sm">Manage routes for PUJ</p>
            </header>

            <div class="pb-4 flex justify-end">
                <a href="{{ route('routes.create') }}">
                    <button
                        class="w-40 flex items-center justify-center gap-2 bg-green-500 text-black text-[10px] font-black py-2 rounded-2xl hover:bg-yellow-50 transition-all active:scale-95 uppercase tracking-widest relative z-10">
                        <i class="fa-solid fa-plus text-lg"></i> Add Route
                    </button>
                </a>
            </div>

            <div class="glass glass-inset p-8 rounded-[2.5rem]">
                <table class="table-fixed w-full">
                    <thead>
                        <tr class="text-xs font-bold uppercase tracking-widest opacity-70">
                            <th>ID</th>
                            <th>Name</th>
                            <th>Starting Point</th>
                            <th> Destination</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($routes as $route)
                            <tr class="text-center">
                                <td>{{ $route->id }}</th>
                                <td>{{ $route->name }}</th>
                                <td class="break-words whitespace-normal">{{ $route->starting_point }}</th>
                                <td class="break-words whitespace-normal">{{ $route->destination }}</th>
                                <td class="flex flex-row gap-2 justify-center items-center py-2">
                                    <a href="{{ route('routes.edit', $route->id) }}">
                                        <button
                                            class="w-20 bg-yellow-500 text-black text-[10px] font-black py-2 rounded-2xl hover:bg-yellow-50 transition-all active:scale-95 uppercase tracking-widest relative z-10">
                                            <i class="fa-regular fa-pen-to-square text-lg"></i>
                                        </button>
                                    </a>
                                    <form method="POST" action="{{ route('routes.destroy', $route->id) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="w-20 bg-red-500 text-black text-[10px] font-black py-2 rounded-2xl hover:bg-red-50 transition-all active:scale-95 uppercase tracking-widest relative z-10">
                                            <i class="fa-solid fa-trash text-lg"></i>
                                        </button>
                                    </form>
                                    </th>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </main>

</body>

</html>