<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit driver — SmartCommute</title>
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
            border: 1px solid rgba(152, 102, 102, 0.05);
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
            <a href="{{ route('admin.drivers.index') }}" class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-gray-500 hover:text-white mb-8">
                <i class="fa-solid fa-arrow-left"></i> Back to list
            </a>
            <h2 class="text-2xl font-black tracking-tight mb-2">Edit PUJ driver</h2>
            <p class="text-gray-500 text-sm mb-8">{{ $user->email }}</p>

            <div class="glass rounded-2xl border border-white/10 p-8">
                <form action="{{ route('admin.drivers.update', $user) }}" method="POST" enctype="multipart/form-data" class="space-y-5">
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
                            class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-sm focus:ring-2 focus:ring-blue-500/40 focus:outline-none">
                        @error('password')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block mb-1 text-[10px] font-bold uppercase tracking-widest text-gray-500">Confirm new password</label>
                        <input type="password" name="password_confirmation"
                            class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-sm focus:ring-2 focus:ring-blue-500/40 focus:outline-none">
                    </div>
                    <div>
                        <label class="block mb-1 text-[10px] font-bold uppercase tracking-widest text-gray-500">License number</label>
                        <input type="text" name="license_number" value="{{ old('license_number', $user->license_number) }}" required
                            class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-sm focus:ring-2 focus:ring-blue-500/40 focus:outline-none">
                        @error('license_number')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block mb-1 text-[10px] font-bold uppercase tracking-widest text-gray-500">License code</label>
                        <input type="text" name="license_code" value="{{ old('license_code', $user->license_code) }}" required
                            class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-sm focus:ring-2 focus:ring-blue-500/40 focus:outline-none">
                        @error('license_code')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    @php
                        $hasCurrentLicense = $user->license_image_path || filled($user->license_image_data);
                        $licenseFileUrl = $user->license_image_path ? asset('storage/'.$user->license_image_path) : null;
                        $licenseViewUrl = $licenseFileUrl ?? route('admin.drivers.license', $user, true);
                        $licenseDownloadUrl = $licenseFileUrl ?? route('admin.drivers.license', ['user' => $user, 'download' => 1], true);
                    @endphp
                    @if ($hasCurrentLicense)
                        <div>
                            <label class="block mb-2 text-[10px] font-bold uppercase tracking-widest text-gray-500">Current license on file</label>
                            <div class="rounded-xl border border-white/10 overflow-hidden bg-black/40 mb-2">
                                <img src="{{ $licenseViewUrl }}" alt="Current license"
                                    class="max-h-48 w-full object-contain object-center" loading="lazy" width="400" height="240">
                            </div>
                            <div class="flex flex-wrap gap-3 text-[10px] font-bold uppercase tracking-wider">
                                <a href="{{ $licenseViewUrl }}" target="_blank" rel="noopener"
                                    class="text-blue-400 hover:text-blue-300">Open full size</a>
                                <a href="{{ $licenseDownloadUrl }}" download
                                    class="text-gray-400 hover:text-white">Download</a>
                            </div>
                        </div>
                    @endif
                    <div>
                        <label class="block mb-1 text-[10px] font-bold uppercase tracking-widest text-gray-500">Replace license photo (optional)</label>
                        <input type="file" name="license_image" accept="image/jpeg,image/png,image/jpg"
                            class="w-full text-xs text-gray-400 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:bg-blue-600 file:text-white file:text-xs file:font-bold">
                        @error('license_image')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block mb-1 text-[10px] font-bold uppercase tracking-widest text-gray-500">Approval status</label>
                        <select name="driver_approval_status"
                            class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-sm focus:ring-2 focus:ring-blue-500/40 focus:outline-none">
                            <option value="pending" {{ old('driver_approval_status', $user->driver_approval_status) === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="approved" {{ old('driver_approval_status', $user->driver_approval_status) === 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="rejected" {{ old('driver_approval_status', $user->driver_approval_status) === 'rejected' ? 'selected' : '' }}>Rejected</option>
                        </select>
                        @error('driver_approval_status')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
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
