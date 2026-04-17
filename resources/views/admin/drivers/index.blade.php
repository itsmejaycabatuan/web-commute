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
    </style>
</head>

<body x-data="{
        open: true,
        rejectModal: false,
        rejectAction: '',
        rejectDriverEmail: '',
        openRejectModal(url, email) {
            this.rejectAction = url;
            this.rejectDriverEmail = email || '';
            this.rejectModal = true;
        },
        submitReject() {
            this.$refs.rejectPostForm.submit();
        }
    }" x-on:keydown.escape.window="rejectModal = false">

    @include('layout.sidebar')

    <main :class="open ? 'ml-72' : 'ml-20'" class="sidebar-transition p-8 md:p-12 min-h-screen">
        <div class="max-w-7xl">
            <header class="flex flex-col gap-4 mb-10 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h2 class="text-3xl font-black tracking-tight mb-2">PUJ drivers</h2>
                    <p class="text-gray-500 text-sm">Manage drivers and approvals. Open the license in a new tab or
                        download the file.</p>
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
                                <th class="px-4 py-4">License #</th>
                                <th class="px-4 py-4">Code</th>
                                <th class="px-4 py-4">Preview</th>
                                <th class="px-4 py-4">Status</th>
                                <th class="px-4 py-4">Registered</th>
                                <th class="px-4 py-4 text-right min-w-[280px]">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @forelse ($drivers as $driver)
                                @php
                                    $hasLicenseInDb = ($driver->has_license_in_db ?? false);
                                    $hasLicense = $driver->license_image_path || $hasLicenseInDb;
                                    if ($driver->license_image_path) {
                                        $licenseRoute = asset('storage/' . $driver->license_image_path);
                                        $licenseDownload = $licenseRoute;
                                    } elseif ($hasLicenseInDb) {
                                        $licenseRoute = route('admin.drivers.license', $driver, true);
                                        $licenseDownload = route('admin.drivers.license', ['user' => $driver, 'download' => 1], true);
                                    } else {
                                        $licenseRoute = '';
                                        $licenseDownload = '';
                                    }
                                @endphp
                                <tr class="hover:bg-white/[0.02]">
                                    <td class="px-4 py-4 font-medium">{{ $driver->email }}</td>
                                    <td class="px-4 py-4 text-gray-300">{{ $driver->license_number ?? '—' }}</td>
                                    <td class="px-4 py-4 text-gray-300">{{ $driver->license_code ?? '—' }}</td>
                                    <td class="px-4 py-4">
                                        @if ($driver->license_image_path || ($driver->has_license_in_db ?? false))
                                            <div class="flex flex-col gap-2 items-start max-w-[140px]">
                                                <a href="{{ $licenseRoute }}" target="_blank" rel="noopener"
                                                    class="block rounded-lg border border-white/10 overflow-hidden bg-black/40">
                                                    <img src="{{ $licenseRoute }}" alt="License preview" loading="lazy"
                                                        class="max-h-20 w-full object-contain object-center" width="120"
                                                        height="80">
                                                </a>
                                                <a href="{{ $licenseRoute }}" target="_blank" rel="noopener"
                                                    class="inline-flex items-center gap-2 text-blue-400 hover:text-blue-300 text-[10px] font-bold uppercase tracking-wider">
                                                    <i class="fa-regular fa-image"></i> View full size
                                                </a>
                                                {{-- <a href="{{ $licenseDownload }}" download
                                                    class="inline-flex items-center gap-2 text-gray-400 hover:text-white text-[10px] font-bold uppercase tracking-wider">
                                                    <i class="fa-solid fa-download"></i> Download
                                                </a> --}}
                                            </div>
                                        @else
                                            <span class="text-gray-500 text-xs">No file</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4">
                                        @if ($driver->driver_approval_status === 'pending')
                                            <span
                                                class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider bg-amber-500/15 text-amber-400 border border-amber-500/25">Pending</span>
                                        @elseif ($driver->driver_approval_status === 'approved')
                                            <span
                                                class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider bg-emerald-500/15 text-emerald-400 border border-emerald-500/25">Approved</span>
                                        @elseif ($driver->driver_approval_status === 'rejected')
                                            <span
                                                class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider bg-red-500/15 text-red-400 border border-red-500/25">Reject</span>
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
                                            <form action="{{ route('admin.drivers.approve', $driver) }}" method="POST"
                                                class="inline">
                                                @csrf

                                                @if ($driver->driver_approval_status !== 'approved')
                                                    <button type="submit"
                                                        class="px-2.5 py-1.5 rounded-lg text-[9px] font-bold uppercase tracking-wider bg-emerald-600 hover:bg-emerald-500 text-white transition disabled:opacity-35 disabled:cursor-not-allowed disabled:hover:bg-emerald-600">
                                                        Approve
                                                    </button>
                                                @endif

                                            </form>

                                            @if ($driver->driver_approval_status !== 'approved')
                                                <button type="button"
                                                    data-reject-url="{{ route('admin.drivers.reject', $driver) }}"
                                                    data-reject-email="{{ $driver->email }}"
                                                    x-on:click="openRejectModal($event.currentTarget.dataset.rejectUrl, $event.currentTarget.dataset.rejectEmail)"
                                                    class="px-2.5 py-1.5 rounded-lg text-[9px] font-bold uppercase tracking-wider bg-red-700/90 hover:bg-red-600 text-white transition disabled:opacity-35 disabled:cursor-not-allowed disabled:hover:bg-red-700/90">
                                                    Reject
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
                                    <td colspan="7" class="px-6 py-12 text-center text-gray-500">No drivers registered yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Reject driver confirmation (replaces browser confirm) -->
    <form x-ref="rejectPostForm" method="POST" x-bind:action="rejectAction" class="hidden">
        @csrf
    </form>

    <div x-show="rejectModal" x-cloak
        class="fixed inset-0 z-[250] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
        x-on:click.self="rejectModal = false">
        <div class="glass w-full max-w-md p-8 rounded-[2rem] border border-white/10 shadow-2xl text-center"
            x-on:click.stop>
            <div class="flex justify-center mb-4">
                <div
                    class="flex items-center justify-center w-14 h-14 rounded-2xl bg-red-500/15 text-red-400 border border-red-500/25">
                    <i class="fa-solid fa-ban text-2xl"></i>
                </div>
            </div>
            <h3 class="text-lg font-bold tracking-tight mb-2">Reject this driver?</h3>
            <p class="text-sm text-gray-400 mb-1">Account</p>
            <p class="text-sm font-semibold text-white mb-4 break-all" x-text="rejectDriverEmail"></p>
            <p class="text-xs text-gray-500 leading-relaxed mb-8">They will not be able to log in until you approve them
                again from this list.</p>
            <div class="flex gap-3">
                <button type="button" x-on:click="rejectModal = false"
                    class="flex-1 py-3 rounded-xl bg-white/5 hover:bg-white/10 transition font-bold text-xs uppercase tracking-widest text-gray-300">
                    Cancel
                </button>
                <button type="button" x-on:click="submitReject(); rejectModal = false"
                    class="flex-1 py-3 rounded-xl bg-red-600 hover:bg-red-500 transition font-bold text-xs uppercase tracking-widest text-white">
                    Confirm reject
                </button>
            </div>
        </div>
    </div>

</body>

</html>