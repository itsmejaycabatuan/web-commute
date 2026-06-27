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

        [x-cloak] {
            display: none !important;
        }

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

        .modal-scroll::-webkit-scrollbar {
            width: 4px;
        }
        .modal-scroll::-webkit-scrollbar-track {
            background: transparent;
        }
        .modal-scroll::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.1);
            border-radius: 999px;
        }

        input[type="date"]::-webkit-calendar-picker-indicator {
            filter: invert(0.6);
            cursor: pointer;
        }
    </style>
</head>

<body x-data="{
        open: true,
        reviewModal: false,
        reviewEmail: '',
        reviewStatus: '',
        reviewRegistered: '',
        reviewLicenseUrl: '',
        reviewHasLicense: false,
        reviewApproveUrl: '',
        reviewRejectUrl: '',
        openReview(el) {
            this.reviewEmail       = el.dataset.email || '';
            this.reviewStatus      = el.dataset.status || '';
            this.reviewRegistered  = el.dataset.registered || '';
            this.reviewLicenseUrl  = el.dataset.licenseUrl || '';
            this.reviewHasLicense  = el.dataset.hasLicense === '1';
            this.reviewApproveUrl  = el.dataset.approveUrl || '';
            this.reviewRejectUrl   = el.dataset.rejectUrl || '';
            this.reviewModal = true;
        }
    }" x-on:keydown.escape.window="reviewModal = false">

    @include('layout.sidebar')

    <main :class="open ? 'ml-72' : 'ml-20'" class="sidebar-transition p-8 md:p-12 min-h-screen">
        <div class="max-w-7xl">
            <header class="flex flex-col gap-4 mb-10 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h2 class="text-3xl font-black tracking-tight mb-2">PUJ drivers</h2>
                    <p class="text-gray-500 text-sm">Manage drivers and approvals. Click review to verify and fill in driver details.</p>
                </div>
                <a href="{{ route('admin.drivers.create') }}"
                    class="inline-flex justify-center items-center px-5 py-3 rounded-xl text-xs font-bold uppercase tracking-widest bg-blue-600 hover:bg-blue-500 text-white transition shrink-0">
                    <i class="mr-2 fa-solid fa-plus"></i> Add driver
                </a>
            </header>

            @if (session('success'))
                <div
                    class="mb-6 px-4 py-3 rounded-xl border border-emerald-500/30 bg-emerald-500/10 text-emerald-300 text-sm">
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
                                @endphp
                                <tr class="hover:bg-white/[0.02]">
                                    <td class="px-4 py-4 font-medium">{{ $driver->email }}</td>
                                    <td class="px-4 py-4">
                                        @if ($driver->driver_approval_status === 'pending')
                                            <span
                                                class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider bg-amber-500/15 text-amber-400 border border-amber-500/25">Pending</span>
                                        @elseif ($driver->driver_approval_status === 'approved')
                                            <span
                                                class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider bg-emerald-500/15 text-emerald-400 border border-emerald-500/25">Approved</span>
                                        @elseif ($driver->driver_approval_status === 'rejected')
                                            <span
                                                class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider bg-red-500/15 text-red-400 border border-red-500/25">Rejected</span>
                                        @else
                                            <span
                                                class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider bg-gray-500/15 text-gray-400 border border-gray-500/25">{{ $driver->driver_approval_status ? ucfirst($driver->driver_approval_status) : 'Unset' }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 text-gray-500 whitespace-nowrap">
                                        {{ $driver->created_at->format('M j, Y') }}
                                    </td>
                                    <td class="px-4 py-4 text-right">
                                        <div class="flex flex-wrap gap-1.5 justify-end items-center">
                                            @if ($driver->driver_approval_status !== 'approved')
                                                <button type="button"
                                                    data-did="{{ $driver->id }}"
                                                    data-email="{{ $driver->email }}"
                                                    data-status="{{ $driver->driver_approval_status }}"
                                                    data-registered="{{ $driver->created_at->format('M j, Y') }}"
                                                    data-license-url="{{ $licenseRoute }}"
                                                    data-has-license="{{ $hasLicense ? '1' : '0' }}"
                                                    data-approve-url="{{ route('admin.drivers.approve', $driver) }}"
                                                    data-reject-url="{{ route('admin.drivers.reject', $driver) }}"
                                                    x-on:click="openReview($event.currentTarget)"
                                                    class="inline-flex items-center px-2.5 py-1.5 rounded-lg text-[9px] font-bold uppercase tracking-wider bg-blue-600 hover:bg-blue-500 text-white transition">
                                                    <i class="mr-1.5 fa-solid fa-eye"></i> Review
                                                </button>
                                            @endif
                                            <a href="{{ route('admin.drivers.edit', $driver) }}"
                                                class="inline-flex items-center px-2.5 py-1.5 rounded-lg text-[9px] font-bold uppercase tracking-wider bg-white/10 hover:bg-white/15 text-white transition">
                                                Edit
                                            </a>
                                            <form action="{{ route('admin.drivers.destroy', $driver) }}" method="POST"
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
                                    <td colspan="4" class="px-6 py-12 text-center text-gray-500">No drivers registered yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Review driver modal -->
    <div x-show="reviewModal" x-cloak
        class="fixed inset-0 z-[250] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
        x-on:click.self="reviewModal = false"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0">

        <div class="glass w-full max-w-lg rounded-[2rem] border border-white/10 shadow-2xl overflow-hidden"
            x-on:click.stop
            x-show="reviewModal"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95">

            <!-- Header -->
            <div class="flex items-center justify-between px-8 pt-7 pb-4">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-blue-500/15 text-blue-400 border border-blue-500/25">
                        <i class="fa-solid fa-id-card text-sm"></i>
                    </div>
                    <h3 class="text-lg font-bold tracking-tight">Driver Review</h3>
                </div>
                <button type="button" x-on:click="reviewModal = false"
                    class="flex items-center justify-center w-8 h-8 rounded-lg bg-white/5 hover:bg-white/10 transition text-gray-400 hover:text-white">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>

            <!-- Scrollable body -->
            <div class="px-8 pb-8 modal-scroll overflow-y-auto" style="max-height: 75vh;">

                <!-- Status + email -->
                <div class="flex items-center gap-3 mb-6 flex-wrap">
                    <template x-if="reviewStatus === 'pending'">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider bg-amber-500/15 text-amber-400 border border-amber-500/25">Pending</span>
                    </template>
                    <template x-if="reviewStatus === 'rejected'">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider bg-red-500/15 text-red-400 border border-red-500/25">Rejected</span>
                    </template>
                    <span class="text-sm text-gray-400" x-text="reviewEmail"></span>
                    <span class="text-xs text-gray-600" x-text="'· ' + reviewRegistered"></span>
                </div>

                <!-- ID / License image -->
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

                <!-- Divider -->
                <div class="flex items-center gap-3 mb-6">
                    <div class="flex-1 h-px bg-white/10"></div>
                    <span class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Required to approve</span>
                    <div class="flex-1 h-px bg-white/10"></div>
                </div>

                <!-- Real form with server-side validation -->
                <form :action="reviewApproveUrl" method="POST" class="space-y-4 mb-8">
                    @csrf

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

                    <!-- Action buttons -->
                    <div class="flex gap-3 pt-2">
                        <button type="button" x-on:click="reviewModal = false"
                            class="flex-1 py-3 rounded-xl bg-white/5 hover:bg-white/10 transition font-bold text-xs uppercase tracking-widest text-gray-300">
                            Cancel
                        </button>
                        <button type="button" x-on:click="document.getElementById('rejectForm').submit()"
                            class="flex-1 py-3 rounded-xl bg-red-600/90 hover:bg-red-500 transition font-bold text-xs uppercase tracking-widest text-white">
                            <i class="mr-1.5 fa-solid fa-ban"></i> Reject
                        </button>
                        <button type="submit"
                            class="flex-1 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 transition font-bold text-xs uppercase tracking-widest text-white">
                            <i class="mr-1.5 fa-solid fa-check"></i> Approve
                        </button>
                    </div>
                </form>

                <!-- Hidden reject form -->
                <form id="rejectForm" :action="reviewRejectUrl" method="POST" class="hidden">
                    @csrf
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

</body>

</html>
