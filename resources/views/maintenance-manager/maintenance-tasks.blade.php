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

    @include('maintenance-manager.layout.sidebar');

    <main :class="open ? 'ml-72' : 'ml-20'" x-data="{ editing: false }"
        class="sidebar-transition p-8 md:p-12 min-h-screen">

            <div class="max-w-4xl mx-auto">

            <header class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
                <div>
                    <h2 class="text-3xl font-black tracking-tight mb-1">Maintenance <span class="text-blue-500">Tasks</span></h2>
                    <p class="text-white/40 text-sm">Standard service intervals and frequency reference.</p>
                </div>
                <button class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-sm font-semibold shadow-lg shadow-blue-900/40 transition-all self-start">
                    <i class="fa-solid fa-plus mr-2"></i> Add Task
                </button>
            </header>

            <div class="glass rounded-[2rem] border border-white/5 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left whitespace-nowrap">
                        <thead>
                            <tr class="border-b border-white/5 bg-white/[0.02]">
                                <th class="px-8 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-blue-500">Task Performed</th>
                                <th class="px-8 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-white/40 text-right">Miles Between Service</th>
                                <th class="px-8 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-white/40 text-right">Months Between Service</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <tr class="hover:bg-white/[0.02] transition-colors">
                                <td class="px-8 py-4 text-sm text-white font-semibold">Oil & Filter Change</td>
                                <td class="px-8 py-4 text-sm text-white/60 font-mono text-right">5,000</td>
                                <td class="px-8 py-4 text-sm text-white/60 font-mono text-right">6</td>
                            </tr>
                            <tr class="hover:bg-white/[0.02] transition-colors">
                                <td class="px-8 py-4 text-sm text-white font-semibold">Tire Rotation / Balance</td>
                                <td class="px-8 py-4 text-sm text-white/60 font-mono text-right">10,000</td>
                                <td class="px-8 py-4 text-sm text-white/60 font-mono text-right">12</td>
                            </tr>
                            <tr class="hover:bg-white/[0.02] transition-colors">
                                <td class="px-8 py-4 text-sm text-white font-semibold">Air Filter Change</td>
                                <td class="px-8 py-4 text-sm text-white/60 font-mono text-right">10,000</td>
                                <td class="px-8 py-4 text-sm text-white/60 font-mono text-right">6</td>
                            </tr>
                            <tr class="hover:bg-white/[0.02] transition-colors">
                                <td class="px-8 py-4 text-sm text-white font-semibold">Battery Replacement</td>
                                <td class="px-8 py-4 text-sm text-white/20 font-mono text-right">—</td>
                                <td class="px-8 py-4 text-sm text-white/60 font-mono text-right">36</td>
                            </tr>
                            <tr class="hover:bg-white/[0.02] transition-colors">
                                <td class="px-8 py-4 text-sm text-white font-semibold">Belt Replacement</td>
                                <td class="px-8 py-4 text-sm text-white/60 font-mono text-right">50,000</td>
                                <td class="px-8 py-4 text-sm text-white/60 font-mono text-right">24</td>
                            </tr>
                            <tr class="hover:bg-white/[0.02] transition-colors">
                                <td class="px-8 py-4 text-sm text-white font-semibold">Brake Inspection</td>
                                <td class="px-8 py-4 text-sm text-white/60 font-mono text-right">20,000</td>
                                <td class="px-8 py-4 text-sm text-white/60 font-mono text-right">12</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</body>

</html>
