<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit commuter — SmartCommute</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap');

        body {
            background: #050505;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #fff;
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

    <main :class="open ? 'ml-72' : 'ml-20'" class="sidebar-transition min-h-screen p-8 md:p-12">
        <div class="mx-auto w-full max-w-lg">
            <a href="{{ route('admin.commuters.index') }}" class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-gray-500 hover:text-white mb-8">
                <i class="fa-solid fa-arrow-left"></i> Back to list
            </a>
            <h2 class="text-2xl font-black tracking-tight mb-2">Edit commuter</h2>
            <p class="text-gray-500 text-sm mb-8">{{ $user->email }}</p>

            <div class="glass rounded-2xl border border-white/10 p-8">
                <form action="{{ route('admin.commuters.update', $user) }}" method="POST" class="space-y-5">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block mb-1 text-[10px] font-bold uppercase tracking-widest text-gray-500">Email</label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                            class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-sm focus:ring-2 focus:ring-blue-500/40 focus:outline-none">
                        @error('email')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block mb-1 text-[10px] font-bold uppercase tracking-widest text-gray-500">New password (optional)</label>
                        <input type="password" name="password"
                            class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-sm focus:ring-2 focus:ring-blue-500/40 focus:outline-none"
                            placeholder="Leave blank to keep current">
                        @error('password')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block mb-1 text-[10px] font-bold uppercase tracking-widest text-gray-500">Confirm new password</label>
                        <input type="password" name="password_confirmation"
                            class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-sm focus:ring-2 focus:ring-blue-500/40 focus:outline-none">
                    </div>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="mark_verified" value="1" class="rounded border-white/20 bg-transparent"
                            {{ old('mark_verified', $user->email_verified_at ? '1' : null) ? 'checked' : '' }}>
                        <span class="text-xs text-gray-400">Email verified</span>
                    </label>
                    <button type="submit"
                        class="w-full py-3 rounded-xl text-xs font-bold uppercase tracking-widest bg-blue-600 hover:bg-blue-500 text-white transition">
                        Save changes
                    </button>
                </form>
            </div>
        </div>
    </main>

</body>

</html>
