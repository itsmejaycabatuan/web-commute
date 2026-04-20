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
                <h2 class="text-3xl font-black tracking-tight mb-2">Fare Rate</h2>
                <p class="text-gray-500 text-sm">Current fare trend</p>
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
        </div>
        </div>
    </main>
</body>

</html>