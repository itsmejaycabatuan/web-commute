<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SmartCommute | PUJ drivers</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap');

        [x-cloak] { display: none !important; }

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

        .modal-scroll::-webkit-scrollbar { width: 4px; }
        .modal-scroll::-webkit-scrollbar-track { background: transparent; }
        .modal-scroll::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.1);
            border-radius: 999px;
        }

        input[type="date"]::-webkit-calendar-picker-indicator {
            filter: invert(0.6);
            cursor: pointer;
        }

        input[type="file"]::file-selector-button {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            color: rgba(255,255,255,0.5);
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            cursor: pointer;
            margin-right: 12px;
            transition: all 0.2s;
        }

        input[type="file"]::file-selector-button:hover {
            background: rgba(255,255,255,0.1);
            color: rgba(255,255,255,0.8);
        }

        select.form-input {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='rgba(255,255,255,0.3)' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
            padding-right: 40px;
        }

        select.form-input option {
            background: #111;
            color: #fff;
        }
    </style>
</head>

<body x-data="driverReview()" @keydown.escape.window="closeAllModals()">

    @include('layout.sidebar')

    <main :class="open ? 'ml-72' : 'ml-20'" class="sidebar-transition p-8 md:p-12 min-h-screen">
        <div class="max-w-7xl">
            <header class="flex flex-col gap-4 mb-10 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h2 class="text-3xl font-black tracking-tight mb-2">PUJ drivers</h2>
                    <p class="text-gray-500 text-sm">Manage drivers and approvals. Click review to verify and fill in driver details.</p>
                </div>
                <button type="button" @click="addModal = true"
                    class="inline-flex justify-center items-center px-5 py-3 rounded-xl text-xs font-bold uppercase tracking-widest bg-blue-600 hover:bg-blue-500 text-white transition shrink-0">
                    <i class="mr-2 fa-solid fa-plus"></i> Add driver
                </button>
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
                                <th class="px-4 py-4">Email</th>
                                <th class="px-4 py-4">Status</th>
                                <th class="px-4 py-4">Registered</th>
                                <th class="px-4 py-4 text-right min-w-[220px]">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @forelse ($drivers as $driver)
                                @php
                                    $hasLicenseInDb = ($driver->has_license_in_db ?? false);
                                    $hasLicense = $driver->license_image_path || $hasLicenseInDb;
                                    if ($driver->license_image_path) {
                                        $licenseRoute = asset('storage/' . $driver->license_image_path);
                                    } elseif ($hasLicenseInDb) {
                                        $licenseRoute = route('admin.drivers.license', $driver, true);
                                    } else {
                                        $licenseRoute = '';
                                    }
                                    if ($driver->is_approved === 1) {
                                        $currentStatus = 'approved';
                                    } elseif ($driver->is_rejected === 1) {
                                        $currentStatus = 'rejected';
                                    } else {
                                        $currentStatus = 'pending';
                                    }
                                @endphp
                                <tr class="hover:bg-white/[0.02]">
                                    <td class="px-4 py-4 font-medium">{{ $driver->email }}</td>
                                    <td class="px-4 py-4">
                                        @if ($currentStatus === 'approved')
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider bg-emerald-500/15 text-emerald-400 border border-emerald-500/25">Approved</span>
                                        @elseif ($currentStatus === 'rejected')
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider bg-red-500/15 text-red-400 border border-red-500/25">Rejected</span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider bg-amber-500/15 text-amber-400 border border-amber-500/25">Pending</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 text-gray-500 whitespace-nowrap">
                                        {{ $driver->created_at->format('M j, Y') }}
                                    </td>
                                    <td class="px-4 py-4 text-right">
                                        <div class="flex flex-wrap gap-1.5 justify-end items-center">
                                            @if ($driver->is_approved === true || $driver->is_rejected === true)
                                                <button type="button"
                                                    data-did="{{ $driver->id }}"
                                                    data-email="{{ $driver->email }}"
                                                    data-registered="{{ $driver->created_at->format('M j, Y') }}"
                                                    data-license-url="{{ $licenseRoute }}"
                                                    data-has-license="{{ $hasLicense ? '1' : '0' }}"
                                                    data-approve-url="{{ route('admin.drivers.approve', $driver->id) }}"
                                                    @click="openReview($el)"
                                                    class="inline-flex items-center px-2.5 py-1.5 rounded-lg text-[9px] font-bold uppercase tracking-wider bg-blue-600 hover:bg-blue-500 text-white transition">
                                                    <i class="mr-1.5 fa-solid fa-eye"></i> Review
                                                </button>
                                            @endif
                                            <button type="button"
                                                data-edit-did="{{ $driver->id }}"
                                                data-edit-email="{{ $driver->email }}"
                                                data-edit-name="{{ $driver->name ?? '' }}"
                                                data-edit-license-number="{{ $driver->license_number ?? '' }}"
                                                data-edit-license-code="{{ $driver->license_code ?? '' }}"
                                                data-edit-expiration="{{ $driver->expiration_date ? (is_string($driver->expiration_date) ? substr($driver->expiration_date, 0, 10) : $driver->expiration_date->format('Y-m-d')) : '' }}"
                                                data-edit-contact="{{ $driver->contact_info ?? '' }}"
                                                data-edit-driver-code="{{ $driver->driver_code ?? '' }}"
                                                data-edit-status="{{ $driver->is_approved === 1 ? '1' : '0' }}"
                                                data-edit-url="{{ route('admin.drivers.update', $driver->id) }}"
                                                @click="openEdit($el)"
                                                class="inline-flex items-center px-2.5 py-1.5 rounded-lg text-[9px] font-bold uppercase tracking-wider bg-white/10 hover:bg-white/15 text-white transition">
                                                <i class="mr-1.5 fa-solid fa-pen"></i> Edit
                                            </button>
                                            <form action="{{ route('admin.drivers.destroy', $driver->id) }}" method="POST"
                                                class="inline"
                                                onsubmit="return confirm('Delete this driver and license file permanently?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="px-2.5 py-1.5 rounded-lg text-[9px] font-bold uppercase tracking-wider bg-red-600/80 hover:bg-red-600 text-white transition">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-12 text-center text-gray-500">No drivers registered yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- ==================== ADD DRIVER MODAL ==================== -->
    <div x-show="addModal" x-cloak
        class="fixed inset-0 z-[250] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
        @click.self="addModal = false"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0">

        <div class="glass w-full max-w-lg rounded-[2rem] border border-white/10 shadow-2xl overflow-hidden"
            @click.stop
            x-show="addModal"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95">

            <div class="flex items-center justify-between px-8 pt-7 pb-4">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-blue-500/15 text-blue-400 border border-blue-500/25">
                        <i class="fa-solid fa-user-plus text-sm"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold tracking-tight">Add Driver</h3>
                        <p class="text-xs text-gray-500">Create a new driver account</p>
                    </div>
                </div>
                <button type="button" @click="addModal = false"
                    class="flex items-center justify-center w-8 h-8 rounded-lg bg-white/5 hover:bg-white/10 transition text-gray-400 hover:text-white">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>

            <div class="px-8 pb-8 modal-scroll overflow-y-auto" style="max-height: 75vh;">
                <form action="{{ route('admin.drivers.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf

                    <!-- Account section -->
                    <div class="flex items-center gap-3 !mb-5">
                        <div class="flex-1 h-px bg-white/5"></div>
                        <span class="text-[9px] font-bold uppercase tracking-widest text-gray-600">Account credentials</span>
                        <div class="flex-1 h-px bg-white/5"></div>
                    </div>

                    <div>
                        <label class="block mb-1 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                            Email <span class="text-red-400">*</span>
                        </label>
                        <input type="email" name="email" value="{{ old('email') }}" required
                            class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-sm text-white placeholder-gray-600 focus:ring-2 focus:ring-blue-500/40 focus:outline-none transition"
                            placeholder="driver@email.com">
                        @error('email')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-1 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                            Password <span class="text-red-400">*</span>
                        </label>
                        <input type="password" name="password" required minlength="8"
                            class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-sm text-white placeholder-gray-600 focus:ring-2 focus:ring-blue-500/40 focus:outline-none transition"
                            placeholder="Minimum 8 characters">
                        @error('password')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-1 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                            Confirm Password <span class="text-red-400">*</span>
                        </label>
                        <input type="password" name="password_confirmation" required
                            class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-sm text-white placeholder-gray-600 focus:ring-2 focus:ring-blue-500/40 focus:outline-none transition"
                            placeholder="Re-enter password">
                    </div>

                    <!-- Driver details section -->
                    <div class="flex items-center gap-3 !my-5">
                        <div class="flex-1 h-px bg-white/5"></div>
                        <span class="text-[9px] font-bold uppercase tracking-widest text-gray-600">Driver details</span>
                        <div class="flex-1 h-px bg-white/5"></div>
                    </div>

                    <div>
                        <label class="block mb-1 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                            Name <span class="text-red-400">*</span>
                        </label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                            class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-sm text-white placeholder-gray-600 focus:ring-2 focus:ring-blue-500/40 focus:outline-none transition"
                            placeholder="Full name">
                        @error('name')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-1 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                            License Number <span class="text-red-400">*</span>
                        </label>
                        <input type="text" name="license_number" value="{{ old('license_number') }}" required
                            class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-sm text-white placeholder-gray-600 focus:ring-2 focus:ring-blue-500/40 focus:outline-none transition"
                            placeholder="e.g., S45-98-765432">
                        @error('license_number')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-1 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                            License Code <span class="text-red-400">*</span>
                        </label>
                        <input type="text" name="license_code" value="{{ old('license_code') }}" required
                            class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-sm text-white placeholder-gray-600 focus:ring-2 focus:ring-blue-500/40 focus:outline-none transition"
                            placeholder="e.g., A, B, C">
                        @error('license_code')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-1 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                            Expiration Date <span class="text-red-400">*</span>
                        </label>
                        <input type="date" name="expiration_date" value="{{ old('expiration_date') }}" required
                            class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-sm text-white focus:ring-2 focus:ring-blue-500/40 focus:outline-none transition">
                        @error('expiration_date')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-1 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                            Contact Information <span class="text-red-400">*</span>
                        </label>
                        <input type="text" name="contact_info" value="{{ old('contact_info') }}" required
                            class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-sm text-white placeholder-gray-600 focus:ring-2 focus:ring-blue-500/40 focus:outline-none transition"
                            placeholder="Phone number or mobile">
                        @error('contact_info')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-1 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                            Driver Code <span class="text-red-400">*</span>
                        </label>
                        <input type="text" name="driver_code" value="{{ old('driver_code') }}" required
                            class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-sm text-white placeholder-gray-600 focus:ring-2 focus:ring-blue-500/40 focus:outline-none transition"
                            placeholder="Unique driver identifier">
                        @error('driver_code')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- License image -->
                    <div>
                        <label class="block mb-1 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                            License Image
                        </label>
                        <input type="file" name="license_image" accept="image/jpg,image/jpeg,image/png"
                            class="w-full px-4 py-2.5 rounded-xl bg-white/5 border border-white/10 text-sm text-gray-400 focus:ring-2 focus:ring-blue-500/40 focus:outline-none transition file:mr-4">
                        <p class="mt-1.5 text-[10px] text-gray-600">JPG, JPEG, or PNG. Max 4MB.</p>
                        @error('license_image')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="addModal = false"
                            class="flex-1 py-3 rounded-xl bg-white/5 hover:bg-white/10 transition font-bold text-xs uppercase tracking-widest text-gray-300">
                            Cancel
                        </button>
                        <button type="submit"
                            class="flex-1 py-3 rounded-xl bg-blue-600 hover:bg-blue-500 transition font-bold text-xs uppercase tracking-widest text-white">
                            <i class="mr-1.5 fa-solid fa-plus"></i> Create Driver
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ==================== REVIEW MODAL ==================== -->
    <div x-show="reviewModal" x-cloak
        class="fixed inset-0 z-[250] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
        @click.self="reviewModal = false"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0">

        <div class="glass w-full max-w-lg rounded-[2rem] border border-white/10 shadow-2xl overflow-hidden"
            @click.stop
            x-show="reviewModal"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95">

            <div class="flex items-center justify-between px-8 pt-7 pb-4">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-blue-500/15 text-blue-400 border border-blue-500/25">
                        <i class="fa-solid fa-id-card text-sm"></i>
                    </div>
                    <h3 class="text-lg font-bold tracking-tight">Driver Review</h3>
                </div>
                <button type="button" @click="reviewModal = false"
                    class="flex items-center justify-center w-8 h-8 rounded-lg bg-white/5 hover:bg-white/10 transition text-gray-400 hover:text-white">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>

            <div class="px-8 pb-8 modal-scroll overflow-y-auto" style="max-height: 75vh;">
                <div class="flex items-center gap-3 mb-6 flex-wrap">
                    <span class="text-sm text-gray-400" x-text="reviewEmail"></span>
                    <span class="text-xs text-gray-600" x-text="'· ' + reviewRegistered"></span>
                </div>

                <div class="space-y-2 mb-8">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Submitted ID</p>
                    <template x-if="reviewHasLicense">
                        <div>
                            <a :href="reviewLicenseUrl" target="_blank" rel="noopener"
                                class="block rounded-xl border border-white/10 overflow-hidden bg-black/60 hover:border-white/20 transition">
                                <img :src="reviewLicenseUrl" alt="ID" loading="lazy"
                                    class="w-full max-h-64 object-contain">
                            </a>
                            <a :href="reviewLicenseUrl" target="_blank" rel="noopener"
                                class="inline-flex items-center gap-2 mt-2 text-blue-400 hover:text-blue-300 text-[10px] font-bold uppercase tracking-wider transition">
                                <i class="fa-solid fa-arrow-up-right-from-square"></i> Open full size
                            </a>
                        </div>
                    </template>
                    <template x-if="!reviewHasLicense">
                        <div class="flex flex-col items-center justify-center py-10 rounded-xl border border-dashed border-white/10 bg-white/[0.02]">
                            <i class="fa-regular fa-image text-2xl text-gray-600 mb-2"></i>
                            <p class="text-xs text-gray-500">No ID image uploaded</p>
                        </div>
                    </template>
                </div>

                <div class="flex items-center gap-3 mb-6">
                    <div class="flex-1 h-px bg-white/10"></div>
                    <span class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Required to approve</span>
                    <div class="flex-1 h-px bg-white/10"></div>
                </div>

                <form :action="reviewApproveUrl" method="POST" class="space-y-4 mb-8">
                    @csrf

                    <div>
                        <label class="block mb-1 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                            Name <span class="text-red-400">*</span>
                        </label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                            class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-sm text-white placeholder-gray-600 focus:ring-2 focus:ring-blue-500/40 focus:outline-none transition">
                        @error('name')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-1 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                            License Number <span class="text-red-400">*</span>
                        </label>
                        <input type="text" name="license_number" value="{{ old('license_number') }}" required
                            class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-sm text-white placeholder-gray-600 focus:ring-2 focus:ring-blue-500/40 focus:outline-none transition">
                        @error('license_number')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-1 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                            License Code <span class="text-red-400">*</span>
                        </label>
                        <input type="text" name="license_code" value="{{ old('license_code') }}" required
                            class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-sm text-white placeholder-gray-600 focus:ring-2 focus:ring-blue-500/40 focus:outline-none transition">
                        @error('license_code')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-1 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                            Expiration Date <span class="text-red-400">*</span>
                        </label>
                        <input type="date" name="expiration_date" value="{{ old('expiration_date') }}" required
                            class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-sm text-white focus:ring-2 focus:ring-blue-500/40 focus:outline-none transition">
                        @error('expiration_date')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-1 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                            Contact Information <span class="text-red-400">*</span>
                        </label>
                        <input type="text" name="contact_info" value="{{ old('contact_info') }}" required
                            class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-sm text-white placeholder-gray-600 focus:ring-2 focus:ring-blue-500/40 focus:outline-none transition">
                        @error('contact_info')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-1 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                            Driver Code <span class="text-red-400">*</span>
                        </label>
                        <input type="text" name="driver_code" value="{{ old('driver_code') }}" required
                            class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-sm text-white placeholder-gray-600 focus:ring-2 focus:ring-blue-500/40 focus:outline-none transition">
                        @error('driver_code')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="reviewModal = false"
                            class="flex-1 py-3 rounded-xl bg-white/5 hover:bg-white/10 transition font-bold text-xs uppercase tracking-widest text-gray-300">
                            Cancel
                        </button>
                        <button type="button" @click="rejectDriver()"
                            class="flex-1 py-3 rounded-xl bg-red-600/90 hover:bg-red-500 transition font-bold text-xs uppercase tracking-widest text-white">
                            <i class="mr-1.5 fa-solid fa-ban"></i> Reject
                        </button>
                        <button type="submit"
                            class="flex-1 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 transition font-bold text-xs uppercase tracking-widest text-white">
                            <i class="mr-1.5 fa-solid fa-check"></i> Approve
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ==================== EDIT MODAL ==================== -->
    <div x-show="editModal" x-cloak
        class="fixed inset-0 z-[250] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
        @click.self="editModal = false"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0">

        <div class="glass w-full max-w-lg rounded-[2rem] border border-white/10 shadow-2xl overflow-hidden"
            @click.stop
            x-show="editModal"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95">

            <div class="flex items-center justify-between px-8 pt-7 pb-4">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-amber-500/15 text-amber-400 border border-amber-500/25">
                        <i class="fa-solid fa-pen-to-square text-sm"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold tracking-tight">Edit Driver</h3>
                        <p class="text-xs text-gray-500" x-text="editEmail"></p>
                    </div>
                </div>
                <button type="button" @click="editModal = false"
                    class="flex items-center justify-center w-8 h-8 rounded-lg bg-white/5 hover:bg-white/10 transition text-gray-400 hover:text-white">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>

            <div class="px-8 pb-8 modal-scroll overflow-y-auto" style="max-height: 75vh;">
                <form id="editForm" @submit.prevent="submitEdit()" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <!-- Approval Status -->
                    <div>
                        <label class="block mb-1 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                            Approval Status
                        </label>
                        <div class="relative">
                            <select x-model="editStatus"
                                class="form-input w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-sm text-white focus:ring-2 focus:outline-none transition"
                                :class="{
                                    'border-emerald-500/30 focus:ring-emerald-500/40': editStatus === '1',
                                    'border-red-500/30 focus:ring-red-500/40': editStatus === '0',
                                }">
                                <option value="1">Approved</option>
                                <option value="0">Rejected</option>
                            </select>
                            <div class="absolute right-10 top-1/2 -translate-y-1/2 pointer-events-none">
                                <template x-if="editStatus === '1'">
                                    <i class="fa-solid fa-circle-check text-emerald-400 text-xs"></i>
                                </template>
                                <template x-if="editStatus === '0'">
                                    <i class="fa-solid fa-circle-xmark text-red-400 text-xs"></i>
                                </template>
                            </div>
                        </div>
                        <p class="mt-1.5 text-[10px] text-gray-600 flex items-center gap-1">
                            <i class="fa-solid fa-info-circle"></i>
                            Rejecting will disable the driver's ability to sign in.
                        </p>
                    </div>

                    <!-- Divider -->
                    <div class="flex items-center gap-3 !my-5">
                        <div class="flex-1 h-px bg-white/5"></div>
                        <span class="text-[9px] font-bold uppercase tracking-widest text-gray-600">Driver details</span>
                        <div class="flex-1 h-px bg-white/5"></div>
                    </div>

                    <div>
                        <label class="block mb-1 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                            Name <span class="text-red-400">*</span>
                        </label>
                        <input type="text" name="name" x-model="editName" required
                            class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-sm text-white placeholder-gray-600 focus:ring-2 focus:ring-amber-500/40 focus:outline-none transition">
                        @error('name')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-1 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                            License Number <span class="text-red-400">*</span>
                        </label>
                        <input type="text" name="license_number" x-model="editLicenseNumber" required
                            class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-sm text-white placeholder-gray-600 focus:ring-2 focus:ring-amber-500/40 focus:outline-none transition">
                        @error('license_number')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-1 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                            License Code <span class="text-red-400">*</span>
                        </label>
                        <input type="text" name="license_code" x-model="editLicenseCode" required
                            class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-sm text-white placeholder-gray-600 focus:ring-2 focus:ring-amber-500/40 focus:outline-none transition">
                        @error('license_code')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-1 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                            Expiration Date <span class="text-red-400">*</span>
                        </label>
                        <input type="date" name="expiration_date" x-model="editExpiration" required
                            class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-sm text-white focus:ring-2 focus:ring-amber-500/40 focus:outline-none transition">
                        @error('expiration_date')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-1 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                            Contact Information <span class="text-red-400">*</span>
                        </label>
                        <input type="text" name="contact_info" x-model="editContact" required
                            class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-sm text-white placeholder-gray-600 focus:ring-2 focus:ring-amber-500/40 focus:outline-none transition">
                        @error('contact_info')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-1 text-[10px] font-bold uppercase tracking-widest text-gray-500">
                            Driver Code <span class="text-red-400">*</span>
                        </label>
                        <input type="text" name="driver_code" x-model="editDriverCode" required
                            class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-sm text-white placeholder-gray-600 focus:ring-2 focus:ring-amber-500/40 focus:outline-none transition">
                        @error('driver_code')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="editModal = false"
                            class="flex-1 py-3 rounded-xl bg-white/5 hover:bg-white/10 transition font-bold text-xs uppercase tracking-widest text-gray-300">
                            Cancel
                        </button>
                        <button type="submit"
                            class="flex-1 py-3 rounded-xl bg-amber-600 hover:bg-amber-500 transition font-bold text-xs uppercase tracking-widest text-white">
                            <i class="mr-1.5 fa-solid fa-floppy-disk"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @php
        $autoReviewId = session('review_driver_id');
    @endphp
    @if($autoReviewId)
        <script>
            document.addEventListener('alpine:init', () => {
                setTimeout(() => {
                    const btn = document.querySelector('[data-did="{{ $autoReviewId }}"]');
                    if (btn) btn.click();
                }, 200);
            });
        </script>
    @endif

    <script>
        function driverReview() {
            return {
                open: true,

                // Add modal state
                addModal: false,

                // Review modal state
                reviewModal: false,
                reviewDriverId: null,
                reviewEmail: '',
                reviewRegistered: '',
                reviewLicenseUrl: '',
                reviewHasLicense: false,
                reviewApproveUrl: '',

                // Edit modal state
                editModal: false,
                editDriverId: null,
                editEmail: '',
                editName: '',
                editLicenseNumber: '',
                editLicenseCode: '',
                editExpiration: '',
                editContact: '',
                editDriverCode: '',
                editStatus: '',
                editUrl: '',

                closeAllModals() {
                    this.addModal = false;
                    this.reviewModal = false;
                    this.editModal = false;
                },

                openReview(el) {
                    this.reviewDriverId   = el.dataset.did || null;
                    this.reviewEmail      = el.dataset.email || '';
                    this.reviewRegistered = el.dataset.registered || '';
                    this.reviewLicenseUrl = el.dataset.licenseUrl || '';
                    this.reviewHasLicense = el.dataset.hasLicense === '1';
                    this.reviewApproveUrl = el.dataset.approveUrl || '';
                    this.reviewModal = true;
                },

                openEdit(el) {
                    this.editDriverId      = el.dataset.editDid || null;
                    this.editEmail         = el.dataset.editEmail || '';
                    this.editName          = el.dataset.editName || '';
                    this.editLicenseNumber = el.dataset.editLicenseNumber || '';
                    this.editLicenseCode   = el.dataset.editLicenseCode || '';
                    this.editExpiration    = el.dataset.editExpiration || '';
                    this.editContact       = el.dataset.editContact || '';
                    this.editDriverCode    = el.dataset.editDriverCode || '';
                    this.editStatus        = el.dataset.editStatus || '';
                    this.editUrl           = el.dataset.editUrl || '';
                    this.editModal = true;
                },

                submitEdit() {
                    var form = document.createElement('form');
                    form.method = 'POST';
                    form.action = this.editUrl;

                    var token = document.createElement('input');
                    token.type = 'hidden';
                    token.name = '_token';
                    token.value = document.querySelector('meta[name="csrf-token"]').content;
                    form.appendChild(token);

                    var method = document.createElement('input');
                    method.type = 'hidden';
                    method.name = '_method';
                    method.value = 'PUT';
                    form.appendChild(method);

                    var fields = {
                        name: this.editName,
                        license_number: this.editLicenseNumber,
                        license_code: this.editLicenseCode,
                        expiration_date: this.editExpiration,
                        contact_info: this.editContact,
                        driver_code: this.editDriverCode
                    };

                    for (var key in fields) {
                        var input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = key;
                        input.value = fields[key];
                        form.appendChild(input);
                    }

                    var isApproved = document.createElement('input');
                    isApproved.type = 'hidden';
                    isApproved.name = 'is_approved';
                    isApproved.value = this.editStatus === '1' ? '1' : '0';
                    form.appendChild(isApproved);

                    var isRejected = document.createElement('input');
                    isRejected.type = 'hidden';
                    isRejected.name = 'is_rejected';
                    isRejected.value = this.editStatus === '0' ? '1' : '0';
                    form.appendChild(isRejected);

                    document.body.appendChild(form);
                    form.submit();
                },

                rejectDriver() {
                    if (!this.reviewDriverId) return;

                    var url = '{{ route("admin.drivers.reject", "__ID__") }}';
                    url = url.replace('__ID__', this.reviewDriverId);

                    var form = document.createElement('form');
                    form.method = 'POST';
                    form.action = url;

                    var token = document.createElement('input');
                    token.type = 'hidden';
                    token.name = '_token';
                    token.value = document.querySelector('meta[name="csrf-token"]').content;
                    form.appendChild(token);

                    var method = document.createElement('input');
                    method.type = 'hidden';
                    method.name = '_method';
                    method.value = 'PUT';
                    form.appendChild(method);

                    document.body.appendChild(form);
                    form.submit();
                }
            };
        }
    </script>

</body>

</html>
