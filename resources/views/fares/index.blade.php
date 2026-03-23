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
                <h2 class="text-3xl font-black tracking-tight mb-2">Fare Rates</h2>
                <p class="text-gray-500 text-sm">Manage the current fare trends</p>
            </header>

            <div class="p-4">
                <div class="overflow-x-auto rounded-lg border border-gray-200">
                    <table class="table-auto w-full">
                        <thead class="border-b">
                            <tr>
                                <th scope="col"
                                    class="text-center px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider border-r border-gray-200">
                                    DISTANCE (KMS)
                                </th>
                                <th scope="col"
                                    class="text-center px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider border-r border-gray-200">
                                    REGULAR
                                </th>
                                <th scope="col"
                                    class="text-center px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider border-r border-gray-200">
                                    STUDENT/ELDERLY/DISABLED
                                </th>
                            </tr>
                        </thead>
                        <tbody class=" divide-y divide-gray-200">
                            @foreach ($rates as $rate)
                                <tr>
                                    <td
                                        class="text-center px-6 py-4 whitespace-nowrap text-sm text-white border-r border-gray-200 align-top">
                                        {{ $rate->km }}
                                    </td>
                                    <td
                                        class="text-center px-6 py-4 whitespace-nowrap text-sm text-white border-r border-gray-200 align-top">
                                        {{$rate->regular}}
                                    </td>
                                    <td class="text-center px-6 py-4 text-sm text-white border-r border-gray-200 font-mono">
                                        {{$rate->discount}}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <form method="POST" action="{{ route('fares.upload') }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="pb-4 flex justify-end gap-2">
                    <input type="file" name="fare" id="fare"
                        class="uploads form-control w-full bg-white/5 border border-white/10 rounded-2xl px-4 py-3 text-xs file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-500/20 file:text-blue-400 hover:file:bg-blue-500/30 file:cursor-pointer file:transition text-white/70 focus:bg-white/10 focus:border-blue-500/50 outline-none cursor-pointer transition">
                    <button type="submit"
                        class="w-40 flex items-center justify-center gap-2 bg-green-500 text-black text-[10px] font-black py-2 rounded-2xl hover:bg-yellow-50 transition-all active:scale-95 uppercase tracking-widest relative z-10">
                        <i class="fa-solid fa-plus text-lg"></i> Add Fares
                    </button>
                </div>
            </form>

            <div class="glass glass-inset p-8 rounded-[2.5rem]">
                <table class="table-fixed w-full">
                    <thead>
                        <tr class="text-xs font-bold uppercase tracking-widest opacity-70">
                            <th>ID</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($fares as $fare)
                            <tr class="text-center">
                                <td>{{ $fare->id }}</th>
                                <td class="flex flex-row gap-2 justify-center items-center py-2">
                                    <a href="{{ route('fares.view', $fare->id) }}">
                                        <button
                                            class="w-20 bg-green-500 text-black text-[10px] font-black py-2 rounded-2xl hover:bg-green-50 transition-all active:scale-95 uppercase tracking-widest relative z-10">
                                            <i class="fa-solid fa-eye text-lg"></i>
                                        </button>
                                    </a>
                                    <form method="POST" action="{{ route('fares.destroy', $fare->id) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="w-20 bg-red-500 text-black text-[10px] font-black py-2 rounded-2xl hover:bg-red-50 transition-all active:scale-95 uppercase tracking-widest relative z-10">
                                            <i class="fa-solid fa-trash text-lg"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>

</html>