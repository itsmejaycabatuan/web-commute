<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartCommute | Manage PUJ Commuters</title>
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
        <div class="max-w-5xl">
            <header class="flex flex-col gap-4 mb-10 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="text-3xl font-black tracking-tight mb-2">Manage PUJ commuter</h2>
                    <p class="text-gray-500 text-sm">Add, edit, or remove commuter accounts (PUJ passengers).</p>
                </div>
                <a href="{{ route('admin.commuters.create') }}"
                    class="inline-flex justify-center items-center px-5 py-3 rounded-xl text-xs font-bold uppercase tracking-widest bg-blue-600 hover:bg-blue-500 text-white transition">
                    <i class="mr-2 fa-solid fa-plus"></i> Add commuter
                </a>
            </header>

            @if (session('success'))
                <div class="mb-6 px-4 py-3 rounded-xl border border-emerald-500/30 bg-emerald-500/10 text-emerald-300 text-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-6 px-4 py-3 rounded-xl border border-red-500/30 bg-red-500/10 text-red-300 text-sm">
                    {{ session('error') }}
                </div>
            @endif

            <div class="glass rounded-2xl border border-white/10 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-white/5 text-gray-400 uppercase text-[10px] tracking-widest font-bold">
                            <tr>
                                <th class="px-6 py-4">#</th>
                                <th class="px-6 py-4">Email</th>
                                <th class="px-6 py-4">Verified</th>
                                <th class="px-6 py-4">Registered</th>
                                <th class="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @forelse ($commuters as $i => $user)
                                <tr class="hover:bg-white/[0.02]">
                                    <td class="px-6 py-4 text-gray-500">{{ $i + 1 }}</td>
                                    <td class="px-6 py-4 font-medium">{{ $user->email }}</td>
                                    <td class="px-6 py-4">
                                        @if ($user->email_verified_at)
                                            <span class="text-emerald-400 text-xs font-bold">Yes</span>
                                        @else
                                            <span class="text-amber-400 text-xs font-bold">Pending</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-gray-500">{{ $user->created_at->format('M j, Y g:i A') }}</td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex flex-wrap gap-2 justify-end">
                                            <a href="{{ route('admin.commuters.edit', $user) }}"
                                                class="inline-flex items-center px-3 py-2 rounded-xl text-[10px] font-bold uppercase tracking-widest bg-white/10 hover:bg-white/15 text-white transition">
                                                <i class="mr-1.5 fa-solid fa-pen text-[9px]"></i> Edit
                                            </a>
                                            <form action="{{ route('admin.commuters.destroy', $user) }}" method="POST" class="inline"
                                                onsubmit="return confirm('Delete this commuter permanently?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="inline-flex items-center px-3 py-2 rounded-xl text-[10px] font-bold uppercase tracking-widest bg-red-600/80 hover:bg-red-600 text-white transition">
                                                    <i class="mr-1.5 fa-solid fa-trash text-[9px]"></i> Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-gray-500">No commuter accounts yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

</body>

</html>
