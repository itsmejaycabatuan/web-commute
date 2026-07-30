<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SmartCommute | PUJ Drivers</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: { extend: { fontFamily: { sans: ['Inter', 'sans-serif'] } } }
        }
    </script>
    <style>
        body, html { margin: 0; padding: 0; height: 100%; font-family: 'Inter', sans-serif; overflow-x: hidden; background: #050505; }
        .glass-panel { background: #111111 !important; border: 1px solid #1e1e1e; box-shadow: 0 4px 24px rgba(0,0,0,0.6); }
        .glass-card { background: #161616; border: 1px solid #222222; box-shadow: 0 4px 24px rgba(0,0,0,0.5); }
        .sidebar-transition { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        .table-row { transition: all 0.2s ease; }
        .table-row:hover { background: #1a1a1a; }
        [x-cloak] { display: none !important; }

        ::-webkit-scrollbar { width: 3px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #333; border-radius: 10px; }
        .modal-scroll::-webkit-scrollbar { width: 3px; }
        .modal-scroll::-webkit-scrollbar-track { background: transparent; }
        .modal-scroll::-webkit-scrollbar-thumb { background: #333; border-radius: 10px; }

        input[type="date"]::-webkit-calendar-picker-indicator { filter: invert(0.4); cursor: pointer; }

        input[type="file"]::file-selector-button {
            background: #111; border: 1px solid #1e1e1e; color: #555;
            padding: 6px 14px; border-radius: 8px; font-size: 9px; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.1em; cursor: pointer;
            margin-right: 12px; transition: all 0.2s;
        }
        input[type="file"]::file-selector-button:hover { background: #1a1a1a; color: #888; }

        select.form-input {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='rgba(255,255,255,0.2)' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
            background-repeat: no-repeat; background-position: right 14px center; padding-right: 40px;
        }
        select.form-input option { background: #111; color: #fff; }
    </style>
</head>

@php
    $mappedDrivers = $drivers->map(function($d) {
        $hasLicenseInDb = ($d->has_license_in_db ?? false);
        $hasLicense = $d->license_image_path || $hasLicenseInDb;

        if ($d->license_image_path) {
            $licenseUrl = asset('storage/' . $d->license_image_path);
        } elseif ($hasLicenseInDb) {
            $licenseUrl = route('admin.drivers.license', $d, true);
        } else {
            $licenseUrl = '';
        }

        if ($d->is_approved === 1) { $status = 'approved'; }
        elseif ($d->is_rejected === 1) { $status = 'rejected'; }
        else { $status = 'pending'; }

        return [
            'id' => $d->id,
            'email' => $d->email,
            'name' => $d->name ?? '',
            'contact_info' => $d->contact_info ?? '',
            'license_number' => $d->license_number ?? '',
            'license_code' => $d->license_code ?? '',
            'expiration_date' => $d->expiration_date ? (is_string($d->expiration_date) ? substr($d->expiration_date, 0, 10) : $d->expiration_date->format('Y-m-d')) : '',
            'expiration_formatted' => $d->expiration_date ? (is_string($d->expiration_date) ? \Carbon\Carbon::parse($d->expiration_date)->format('M d, Y') : $d->expiration_date->format('M d, Y')) : '',
            'driver_code' => $d->driver_code ?? '',
            'is_approved' => $d->is_approved,
            'is_rejected' => $d->is_rejected,
            'status' => $status,
            'has_license' => $hasLicense,
            'license_url' => $licenseUrl,
            'created_at' => $d->created_at->format('M j, Y'),
            'approve_url' => route('admin.drivers.approve', $d->id),
            'reject_url' => route('admin.drivers.reject', $d->id),
            'update_url' => route('admin.drivers.update', $d->id),
            'delete_url' => route('admin.drivers.destroy', $d->id),
        ];
    })->values();
@endphp

<script type="text/json" id="drivers-data">
    @json($mappedDrivers)
</script>

<body class="antialiased text-white" x-data="driverReview()" @keydown.escape.window="closeAllModals()">

    @include('layout.sidebar')

    <main :class="open ? 'md:ml-72' : 'md:ml-20'" class="sidebar-transition pt-8 pr-4 sm:pr-8 pb-8 pl-4 sm:pl-8 min-h-screen mb-12">

        <!-- ── Mobile: Admin Identity Card ── -->
        <div class="lg:hidden mb-5">
            <div class="glass-card p-4 rounded-[1.25rem]">
                <div class="flex items-center gap-3.5">
                    <div class="w-11 h-11 bg-red-600 rounded-xl flex items-center justify-center shrink-0">
                        <span class="text-sm font-black text-white">{{ strtoupper(substr(explode('@', Auth::user()->email)[0], 0, 1)) }}</span>
                    </div>
                    <div class="min-w-0">
                        <h2 class="text-sm font-bold text-white truncate">System Administrator</h2>
                        <p class="text-[10px] text-[#555] truncate">{{ Auth::user()->email }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 pt-3.5 mt-3.5 border-t border-[#1e1e1e]">
                    <i class="fa-solid fa-shield-halved text-[8px] text-red-400"></i>
                    <span class="text-[10px] text-[#888] font-bold">Full Access</span>
                    <span class="text-[#333]">•</span>
                    <span class="font-mono text-[9px] text-[#444]">Admin</span>
                </div>
            </div>
        </div>

        <!-- ── Page Header (desktop) ── -->
        <div class="hidden lg:block mb-8">
            <div class="flex items-center gap-2 mb-1.5">
                <span class="text-[9px] font-bold uppercase tracking-[0.15em] text-[#444]">Manage</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">PUJ Drivers</h1>
            <p class="text-[11px] text-[#555] mt-1 flex items-center gap-2">
                <i class="fa-solid fa-id-badge text-[9px] text-amber-400"></i>
                <span class="text-[#888] font-bold">{{ $drivers instanceof \Illuminate\Pagination\LengthAwarePaginator ? $drivers->total() : count($drivers) }}</span> registered driver accounts
            </p>
        </div>

        @if (session('success'))
            <div class="mb-5 px-4 py-3 rounded-xl border border-emerald-500/15 bg-emerald-500/5 flex items-center gap-3">
                <div class="w-6 h-6 rounded-md bg-emerald-500/10 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-check text-[8px] text-emerald-400"></i>
                </div>
                <span class="text-[11px] text-emerald-400 font-medium">{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-5 px-4 py-3 rounded-xl border border-red-500/15 bg-red-500/5 flex items-center gap-3">
                <div class="w-6 h-6 rounded-md bg-red-500/10 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-xmark text-[8px] text-red-400"></i>
                </div>
                <span class="text-[11px] text-red-400 font-medium">{{ session('error') }}</span>
            </div>
        @endif

        <!-- ══════════ TABLE CARD ══════════ -->
        <div class="glass-card rounded-[1.25rem] sm:rounded-[1.5rem] overflow-hidden">

            <!-- ── Search, Filters & Add Button ── -->
            <div class="p-4 sm:p-5 border-b border-[#1e1e1e]">
                <div class="flex flex-col sm:flex-row gap-3 mb-3.5">
                    <div class="relative flex-1">
                        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-[10px] text-[#333]"></i>
                        <input x-model="search" type="text" placeholder="Search by email or name..."
                            class="w-full pl-10 pr-10 py-2.5 bg-[#111] border border-[#1e1e1e] rounded-xl text-[11px] text-white placeholder-[#333] focus:outline-none focus:border-[#333] transition">
                        <button x-show="search.length > 0" @click="search = ''"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-[#444] hover:text-white transition">
                            <i class="fa-solid fa-xmark text-[10px]"></i>
                        </button>
                    </div>
                    <button @click="addModal = true"
                        class="flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-[10px] font-bold uppercase tracking-widest text-white transition active:scale-[0.98] shrink-0">
                        <i class="fa-solid fa-plus text-[9px]"></i>
                        <span>Add Driver</span>
                    </button>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-1.5">
                        <button @click="filter = 'all'"
                            :class="filter === 'all' ? 'bg-white/10 text-white border-white/20' : 'bg-[#111] text-[#555] border-[#1e1e1e] hover:border-[#333] hover:text-[#888]'"
                            class="px-3 py-1.5 rounded-lg text-[8px] font-bold uppercase tracking-widest border transition">
                            All
                        </button>
                        <button @click="filter = 'pending'"
                            :class="filter === 'pending' ? 'bg-amber-500/10 text-amber-400 border-amber-500/20' : 'bg-[#111] text-[#555] border-[#1e1e1e] hover:border-[#333] hover:text-[#888]'"
                            class="px-3 py-1.5 rounded-lg text-[8px] font-bold uppercase tracking-widest border transition">
                            Pending
                        </button>
                        <button @click="filter = 'approved'"
                            :class="filter === 'approved' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 'bg-[#111] text-[#555] border-[#1e1e1e] hover:border-[#333] hover:text-[#888]'"
                            class="px-3 py-1.5 rounded-lg text-[8px] font-bold uppercase tracking-widest border transition">
                            Approved
                        </button>
                        <button @click="filter = 'rejected'"
                            :class="filter === 'rejected' ? 'bg-red-500/10 text-red-400 border-red-500/20' : 'bg-[#111] text-[#555] border-[#1e1e1e] hover:border-[#333] hover:text-[#888]'"
                            class="px-3 py-1.5 rounded-lg text-[8px] font-bold uppercase tracking-widest border transition">
                            Rejected
                        </button>
                    </div>
                    <span class="text-[8px] font-bold text-[#333] uppercase tracking-widest"
                        x-text="filteredDrivers.length + ' of ' + drivers.length"></span>
                </div>
            </div>

            <!-- ── Table ── -->
            <div class="overflow-x-auto -mx-2 px-2 pb-2">
                <table class="w-full text-left min-w-[700px]">
                    <thead>
                        <tr class="text-[8px] sm:text-[9px] uppercase tracking-[0.15em] text-[#444] border-b border-[#1e1e1e]">
                            <th class="px-4 sm:px-6 py-3 font-bold w-12">#</th>
                            <th class="px-4 sm:px-6 py-3 font-bold">Driver</th>
                            <th class="px-4 sm:px-6 py-3 font-bold">Status</th>
                            <th class="px-4 sm:px-6 py-3 font-bold">Registered</th>
                            <th class="px-4 sm:px-6 py-3 font-bold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#1a1a1a]">

                        <template x-for="(driver, index) in filteredDrivers" :key="driver.id">
                            <tr class="table-row">
                                <td class="px-4 sm:px-6 py-3">
                                    <span class="text-[10px] font-bold text-[#333]" x-text="String(index + 1).padStart(2, '0')"></span>
                                </td>
                                <td class="px-4 sm:px-6 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-[#111] border border-[#1e1e1e] flex items-center justify-center shrink-0">
                                            <i class="fa-solid fa-id-badge text-[9px] text-[#444]"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-[10px] sm:text-[11px] font-bold text-[#ccc] truncate max-w-[200px]" x-text="driver.email"></p>
                                            <p class="text-[7px] text-[#444] font-bold uppercase truncate max-w-[200px]" x-text="driver.name || 'No name set'"></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 sm:px-6 py-3">
                                    <template x-if="driver.status === 'approved'">
                                        <span class="text-[7px] sm:text-[8px] bg-emerald-500/10 text-emerald-400 border border-emerald-500/15 px-1.5 py-0.5 rounded-md font-bold uppercase">Approved</span>
                                    </template>
                                    <template x-if="driver.status === 'rejected'">
                                        <span class="text-[7px] sm:text-[8px] bg-red-500/10 text-red-400 border border-red-500/15 px-1.5 py-0.5 rounded-md font-bold uppercase">Rejected</span>
                                    </template>
                                    <template x-if="driver.status === 'pending'">
                                        <span class="text-[7px] sm:text-[8px] bg-amber-500/10 text-amber-400 border border-amber-500/15 px-1.5 py-0.5 rounded-md font-bold uppercase">Pending</span>
                                    </template>
                                </td>
                                <td class="px-4 sm:px-6 py-3">
                                    <span class="text-[10px] font-bold text-[#555]" x-text="driver.created_at"></span>
                                </td>
                                <td class="px-4 sm:px-6 py-3">
                                    <div class="flex items-center gap-1.5 justify-end">
                                        <!-- Review button: only for pending -->
                                        <template x-if="driver.status === 'pending'">
                                            <button @click="openReview(driver)"
                                                class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-[8px] font-bold uppercase tracking-widest bg-blue-600 hover:bg-blue-500 text-white transition">
                                                <i class="fa-solid fa-eye text-[7px]"></i>
                                                <span>Review</span>
                                            </button>
                                        </template>

                                        <!-- View button: only for approved/rejected -->
                                        <template x-if="driver.status !== 'pending'">
                                            <button @click="openView(driver)"
                                                class="w-8 h-8 rounded-lg bg-purple-500/5 border border-purple-500/10 hover:bg-purple-500/10 hover:border-purple-500/20 flex items-center justify-center transition group"
                                                title="View ID & Info">
                                                <i class="fa-solid fa-id-card text-[8px] text-purple-500/40 group-hover:text-purple-400 transition"></i>
                                            </button>
                                        </template>

                                        <!-- Edit -->
                                        <button @click="openEdit(driver)"
                                            class="w-8 h-8 rounded-lg bg-[#111] border border-[#1e1e1e] hover:bg-[#1a1a1a] hover:border-[#333] flex items-center justify-center transition group"
                                            title="Edit">
                                            <i class="fa-solid fa-pen text-[8px] text-[#444] group-hover:text-white transition"></i>
                                        </button>

                                        <!-- Delete -->
                                        <button @click="openDelete(driver)"
                                            class="w-8 h-8 rounded-lg bg-red-500/5 border border-red-500/10 hover:bg-red-500/10 hover:border-red-500/20 flex items-center justify-center transition group"
                                            title="Delete">
                                            <i class="fa-solid fa-trash text-[8px] text-red-500/40 group-hover:text-red-400 transition"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <!-- Empty: no drivers -->
                        <template x-if="drivers.length === 0">
                            <tr>
                                <td colspan="5" class="py-12 sm:py-16">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="w-12 h-12 rounded-xl bg-[#111] border border-[#1e1e1e] flex items-center justify-center mb-3">
                                            <i class="fa-solid fa-id-badge text-base text-[#222]"></i>
                                        </div>
                                        <p class="text-[11px] text-[#444] font-medium mb-4">No drivers registered yet</p>
                                        <button @click="addModal = true"
                                            class="flex items-center gap-2 px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-[9px] font-bold uppercase tracking-widest text-white transition">
                                            <i class="fa-solid fa-plus text-[8px]"></i>
                                            <span>Add First Driver</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <!-- Empty: no search results -->
                        <template x-if="drivers.length > 0 && filteredDrivers.length === 0">
                            <tr>
                                <td colspan="5" class="py-12 sm:py-16">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="w-12 h-12 rounded-xl bg-[#111] border border-[#1e1e1e] flex items-center justify-center mb-3">
                                            <i class="fa-solid fa-magnifying-glass text-base text-[#222]"></i>
                                        </div>
                                        <p class="text-[11px] text-[#444] font-medium">No drivers match your search</p>
                                        <button @click="search = ''; filter = 'all'"
                                            class="mt-3 text-[9px] font-bold uppercase tracking-widest text-blue-400 hover:text-white transition">
                                            Clear filters
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>

                    </tbody>
                </table>
            </div>
        </div>
    </main>


    <!-- ==================== ADD DRIVER MODAL ==================== -->
    <div x-show="addModal" x-cloak
        class="fixed inset-0 z-[250] flex items-center justify-center p-4 bg-black/80"
        @click.self="addModal = false"
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" style="display:none;">

        <div class="glass-panel w-full max-w-lg rounded-[2rem] overflow-hidden"
            @click.stop x-show="addModal"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">

            <div class="flex items-center justify-between px-6 sm:px-8 pt-6 sm:pt-7 pb-4">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-blue-500/10 border border-blue-500/15 flex items-center justify-center">
                        <i class="fa-solid fa-user-plus text-xs text-blue-400"></i>
                    </div>
                    <div>
                        <h3 class="text-sm sm:text-base font-bold text-white">Add Driver</h3>
                        <p class="text-[9px] text-[#555]">Create a new driver account</p>
                    </div>
                </div>
                <button @click="addModal = false"
                    class="w-8 h-8 rounded-lg bg-[#1a1a1a] border border-[#2a2a2a] flex items-center justify-center hover:bg-[#222] transition">
                    <i class="fa-solid fa-xmark text-[10px] text-[#555]"></i>
                </button>
            </div>

            <div class="px-6 sm:px-8 pb-6 sm:pb-8 modal-scroll overflow-y-auto" style="max-height: 75vh;">
                <form action="{{ route('admin.drivers.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <input type="hidden" name="_form_type" value="add">

                    <div class="flex items-center gap-3 !mb-4">
                        <div class="flex-1 h-px bg-[#1e1e1e]"></div>
                        <span class="text-[7px] font-bold uppercase tracking-[0.15em] text-[#333]">Account credentials</span>
                        <div class="flex-1 h-px bg-[#1e1e1e]"></div>
                    </div>

                    <div>
                        <label class="block mb-1.5 text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Email <span class="text-red-400">*</span></label>
                        <input type="email" name="email" value="{{ old('email') }}" required
                            class="w-full px-4 py-2.5 rounded-xl bg-[#111] border border-[#1e1e1e] text-[11px] text-white placeholder-[#333] focus:outline-none focus:border-[#333] transition"
                            placeholder="driver@email.com">
                        @error('email')
                            <p class="mt-1.5 text-[9px] text-red-400 flex items-center gap-1.5"><i class="fa-solid fa-circle-exclamation text-[7px]"></i> {{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-1.5 text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Password <span class="text-red-400">*</span></label>
                        <input type="password" name="password" required minlength="8"
                            class="w-full px-4 py-2.5 rounded-xl bg-[#111] border border-[#1e1e1e] text-[11px] text-white placeholder-[#333] focus:outline-none focus:border-[#333] transition"
                            placeholder="Minimum 8 characters">
                        @error('password')
                            <p class="mt-1.5 text-[9px] text-red-400 flex items-center gap-1.5"><i class="fa-solid fa-circle-exclamation text-[7px]"></i> {{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-1.5 text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Confirm Password <span class="text-red-400">*</span></label>
                        <input type="password" name="password_confirmation" required
                            class="w-full px-4 py-2.5 rounded-xl bg-[#111] border border-[#1e1e1e] text-[11px] text-white placeholder-[#333] focus:outline-none focus:border-[#333] transition"
                            placeholder="Re-enter password">
                    </div>

                    <div class="flex items-center gap-3 !my-4">
                        <div class="flex-1 h-px bg-[#1e1e1e]"></div>
                        <span class="text-[7px] font-bold uppercase tracking-[0.15em] text-[#333]">Driver details</span>
                        <div class="flex-1 h-px bg-[#1e1e1e]"></div>
                    </div>

                    <div>
                        <label class="block mb-1.5 text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Name <span class="text-red-400">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                            class="w-full px-4 py-2.5 rounded-xl bg-[#111] border border-[#1e1e1e] text-[11px] text-white placeholder-[#333] focus:outline-none focus:border-[#333] transition"
                            placeholder="Full name">
                        @error('name')
                            <p class="mt-1.5 text-[9px] text-red-400 flex items-center gap-1.5"><i class="fa-solid fa-circle-exclamation text-[7px]"></i> {{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block mb-1.5 text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">License Number <span class="text-red-400">*</span></label>
                            <input type="text" name="license_number" value="{{ old('license_number') }}" required
                                class="w-full px-4 py-2.5 rounded-xl bg-[#111] border border-[#1e1e1e] text-[11px] text-white placeholder-[#333] focus:outline-none focus:border-[#333] transition"
                                placeholder="S45-98-765432">
                            @error('license_number')
                                <p class="mt-1.5 text-[9px] text-red-400 flex items-center gap-1.5"><i class="fa-solid fa-circle-exclamation text-[7px]"></i> {{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-1.5 text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">License Code <span class="text-red-400">*</span></label>
                            <input type="text" name="license_code" value="{{ old('license_code') }}" required
                                class="w-full px-4 py-2.5 rounded-xl bg-[#111] border border-[#1e1e1e] text-[11px] text-white placeholder-[#333] focus:outline-none focus:border-[#333] transition"
                                placeholder="A, B, C">
                            @error('license_code')
                                <p class="mt-1.5 text-[9px] text-red-400 flex items-center gap-1.5"><i class="fa-solid fa-circle-exclamation text-[7px]"></i> {{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block mb-1.5 text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Expiration Date <span class="text-red-400">*</span></label>
                            <input type="date" name="expiration_date" value="{{ old('expiration_date') }}" required
                                class="w-full px-4 py-2.5 rounded-xl bg-[#111] border border-[#1e1e1e] text-[11px] text-white focus:outline-none focus:border-[#333] transition">
                            @error('expiration_date')
                                <p class="mt-1.5 text-[9px] text-red-400 flex items-center gap-1.5"><i class="fa-solid fa-circle-exclamation text-[7px]"></i> {{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-1.5 text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Driver Code <span class="text-red-400">*</span></label>
                            <input type="text" name="driver_code" value="{{ old('driver_code') }}" required
                                class="w-full px-4 py-2.5 rounded-xl bg-[#111] border border-[#1e1e1e] text-[11px] text-white placeholder-[#333] focus:outline-none focus:border-[#333] transition"
                                placeholder="Unique ID">
                            @error('driver_code')
                                <p class="mt-1.5 text-[9px] text-red-400 flex items-center gap-1.5"><i class="fa-solid fa-circle-exclamation text-[7px]"></i> {{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block mb-1.5 text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">License Image</label>
                        <input type="file" name="license_image" accept="image/jpg,image/jpeg,image/png"
                            class="w-full px-4 py-2.5 rounded-xl bg-[#111] border border-[#1e1e1e] text-[11px] text-[#444] focus:outline-none focus:border-[#333] transition">
                        <p class="mt-1.5 text-[8px] text-[#333]">JPG, JPEG, or PNG. Max 4MB.</p>
                        @error('license_image')
                            <p class="mt-1 text-[9px] text-red-400 flex items-center gap-1.5"><i class="fa-solid fa-circle-exclamation text-[7px]"></i> {{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex gap-2.5 pt-2">
                        <button type="button" @click="addModal = false"
                            class="flex-1 py-2.5 rounded-xl bg-[#1a1a1a] border border-[#2a2a2a] text-white text-[10px] font-bold uppercase tracking-widest hover:bg-[#222] transition">
                            Cancel
                        </button>
                        <button type="submit"
                            class="flex-1 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-[10px] font-bold uppercase tracking-widest transition active:scale-[0.98]">
                            Create Driver
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <!-- ==================== VIEW ID & INFO MODAL ==================== -->
    <div x-show="viewModal" x-cloak
        class="fixed inset-0 z-[250] flex items-center justify-center p-4 bg-black/80"
        @click.self="viewModal = false"
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" style="display:none;">

        <div class="glass-panel w-full max-w-4xl rounded-[2rem] overflow-hidden"
            @click.stop x-show="viewModal"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">

            <div class="flex items-center justify-between px-6 sm:px-8 pt-6 sm:pt-7 pb-4">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-purple-500/10 border border-purple-500/15 flex items-center justify-center">
                        <i class="fa-solid fa-id-card text-xs text-purple-400"></i>
                    </div>
                    <div>
                        <h3 class="text-sm sm:text-base font-bold text-white">Driver Info & ID</h3>
                        <p class="text-[9px] text-[#555]">
                            <span x-text="viewEmail"></span>
                            <span class="text-[#333]" x-text="' · ID #' + viewDriverId"></span>
                        </p>
                    </div>
                </div>
                <button @click="viewModal = false"
                    class="w-8 h-8 rounded-lg bg-[#1a1a1a] border border-[#2a2a2a] flex items-center justify-center hover:bg-[#222] transition">
                    <i class="fa-solid fa-xmark text-[10px] text-[#555]"></i>
                </button>
            </div>

            <div class="px-6 sm:px-8 pb-6 sm:pb-8 modal-scroll overflow-y-auto" style="max-height: 75vh;">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-6">

                    <!-- Left: License Image -->
                    <div class="md:col-span-2">
                        <p class="text-[8px] font-bold uppercase tracking-[0.15em] text-[#444] mb-3">License ID</p>
                        <template x-if="viewHasLicense && viewLicenseUrl">
                            <div class="md:sticky md:top-0">
                                <a :href="viewLicenseUrl" target="_blank" rel="noopener"
                                    class="block rounded-xl border border-[#1e1e1e] overflow-hidden bg-black/40 hover:border-[#333] transition">
                                    <img :src="viewLicenseUrl" alt="License ID" loading="lazy" class="w-full max-h-80 object-contain">
                                </a>
                                <a :href="viewLicenseUrl" target="_blank" rel="noopener"
                                    class="inline-flex items-center gap-1.5 mt-2.5 text-purple-400 hover:text-purple-300 text-[8px] font-bold uppercase tracking-widest transition">
                                    <i class="fa-solid fa-arrow-up-right-from-square text-[7px]"></i> Open full size
                                </a>
                            </div>
                        </template>
                        <template x-if="!viewHasLicense || !viewLicenseUrl">
                            <div class="flex flex-col items-center justify-center py-16 rounded-xl border border-dashed border-[#1e1e1e] bg-[#0a0a0a]">
                                <i class="fa-regular fa-image text-xl text-[#222] mb-2"></i>
                                <p class="text-[10px] text-[#333] font-medium">No license image on file</p>
                            </div>
                        </template>

                        <!-- Status badge under image -->
                        <div class="mt-4">
                            <template x-if="viewStatus === 'approved'">
                                <div class="flex items-center gap-2.5 p-3 rounded-xl bg-emerald-500/5 border border-emerald-500/10">
                                    <div class="w-7 h-7 rounded-lg bg-emerald-500/10 border border-emerald-500/15 flex items-center justify-center shrink-0">
                                        <i class="fa-solid fa-circle-check text-[9px] text-emerald-400"></i>
                                    </div>
                                    <div>
                                        <p class="text-[9px] font-bold text-emerald-400 uppercase">Approved</p>
                                        <p class="text-[7px] text-[#444]">Driver has been verified</p>
                                    </div>
                                </div>
                            </template>
                            <template x-if="viewStatus === 'rejected'">
                                <div class="flex items-center gap-2.5 p-3 rounded-xl bg-red-500/5 border border-red-500/10">
                                    <div class="w-7 h-7 rounded-lg bg-red-500/10 border border-red-500/15 flex items-center justify-center shrink-0">
                                        <i class="fa-solid fa-circle-xmark text-[9px] text-red-400"></i>
                                    </div>
                                    <div>
                                        <p class="text-[9px] font-bold text-red-400 uppercase">Rejected</p>
                                        <p class="text-[7px] text-[#444]">Driver has been denied access</p>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Right: Info Details -->
                    <div class="md:col-span-3">
                        <div class="flex items-center gap-3 mb-5">
                            <div class="flex-1 h-px bg-[#1e1e1e]"></div>
                            <span class="text-[7px] font-bold uppercase tracking-[0.15em] text-[#333]">Driver details</span>
                            <div class="flex-1 h-px bg-[#1e1e1e]"></div>
                        </div>

                        <div class="space-y-3">

                            <!-- Name -->
                            <div class="flex items-center justify-between p-3.5 rounded-xl bg-[#0a0a0a] border border-[#1e1e1e]">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-[#111] border border-[#1e1e1e] flex items-center justify-center shrink-0">
                                        <i class="fa-solid fa-user text-[9px] text-[#444]"></i>
                                    </div>
                                    <span class="text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Name</span>
                                </div>
                                <span class="text-[11px] font-bold text-[#ccc]" x-text="viewName || '—'"></span>
                            </div>

                            <!-- Email -->
                            <div class="flex items-center justify-between p-3.5 rounded-xl bg-[#0a0a0a] border border-[#1e1e1e]">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-[#111] border border-[#1e1e1e] flex items-center justify-center shrink-0">
                                        <i class="fa-solid fa-envelope text-[9px] text-[#444]"></i>
                                    </div>
                                    <span class="text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Email</span>
                                </div>
                                <span class="text-[11px] font-bold text-[#888] truncate max-w-[200px]" x-text="viewEmail"></span>
                            </div>

                            <!-- Contact -->
                            <div class="flex items-center justify-between p-3.5 rounded-xl bg-[#0a0a0a] border border-[#1e1e1e]">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-[#111] border border-[#1e1e1e] flex items-center justify-center shrink-0">
                                        <i class="fa-solid fa-phone text-[9px] text-[#444]"></i>
                                    </div>
                                    <span class="text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Contact</span>
                                </div>
                                <span class="text-[11px] font-bold text-[#888]" x-text="viewContact || '—'"></span>
                            </div>

                            <!-- License Number -->
                            <div class="flex items-center justify-between p-3.5 rounded-xl bg-[#0a0a0a] border border-[#1e1e1e]">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-blue-500/5 border border-blue-500/10 flex items-center justify-center shrink-0">
                                        <i class="fa-solid fa-hashtag text-[9px] text-blue-400/60"></i>
                                    </div>
                                    <span class="text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">License No.</span>
                                </div>
                                <span class="text-[11px] font-bold text-[#ccc] font-mono" x-text="viewLicenseNumber || '—'"></span>
                            </div>

                            <!-- License Code & Expiration -->
                            <div class="grid grid-cols-2 gap-3">
                                <div class="p-3.5 rounded-xl bg-[#0a0a0a] border border-[#1e1e1e]">
                                    <div class="flex items-center gap-2 mb-2">
                                        <div class="w-6 h-6 rounded-md bg-[#111] border border-[#1e1e1e] flex items-center justify-center shrink-0">
                                            <i class="fa-solid fa-barcode text-[8px] text-[#444]"></i>
                                        </div>
                                        <span class="text-[7px] font-bold uppercase tracking-[0.15em] text-[#444]">Code</span>
                                    </div>
                                    <p class="text-[12px] font-bold text-[#ccc]" x-text="viewLicenseCode || '—'"></p>
                                </div>
                                <div class="p-3.5 rounded-xl bg-[#0a0a0a] border border-[#1e1e1e]">
                                    <div class="flex items-center gap-2 mb-2">
                                        <div class="w-6 h-6 rounded-md bg-[#111] border border-[#1e1e1e] flex items-center justify-center shrink-0">
                                            <i class="fa-regular fa-calendar text-[8px] text-[#444]"></i>
                                        </div>
                                        <span class="text-[7px] font-bold uppercase tracking-[0.15em] text-[#444]">Expires</span>
                                    </div>
                                    <p class="text-[12px] font-bold" :class="isExpired ? 'text-red-400' : 'text-[#ccc]'" x-text="viewExpirationFormatted || '—'"></p>
                                    <template x-if="isExpired">
                                        <p class="text-[7px] text-red-400/60 font-bold uppercase mt-0.5">Expired</p>
                                    </template>
                                </div>
                            </div>

                            <!-- Driver Code -->
                            <div class="flex items-center justify-between p-3.5 rounded-xl bg-[#0a0a0a] border border-[#1e1e1e]">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-amber-500/5 border border-amber-500/10 flex items-center justify-center shrink-0">
                                        <i class="fa-solid fa-route text-[9px] text-amber-400/60"></i>
                                    </div>
                                    <span class="text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Driver Code</span>
                                </div>
                                <span class="text-[11px] font-bold text-[#ccc] font-mono" x-text="viewDriverCode || '—'"></span>
                            </div>

                            <!-- Registered Date -->
                            <div class="flex items-center justify-between p-3.5 rounded-xl bg-[#0a0a0a] border border-[#1e1e1e]">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-[#111] border border-[#1e1e1e] flex items-center justify-center shrink-0">
                                        <i class="fa-regular fa-clock text-[9px] text-[#444]"></i>
                                    </div>
                                    <span class="text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Registered</span>
                                </div>
                                <span class="text-[11px] font-bold text-[#555]" x-text="viewRegistered"></span>
                            </div>

                        </div>

                        <!-- Action buttons at bottom -->
                        <div class="flex gap-2.5 mt-5 pt-5 border-t border-[#1e1e1e]">
                            <button @click="viewModal = false; openEdit(viewDriverObj)"
                                class="flex-1 flex items-center justify-center gap-2 py-2.5 rounded-xl bg-[#111] border border-[#1e1e1e] hover:bg-[#1a1a1a] hover:border-[#333] text-[9px] font-bold uppercase tracking-widest text-[#888] hover:text-white transition">
                                <i class="fa-solid fa-pen text-[8px]"></i>
                                Edit
                            </button>
                            <button @click="viewModal = false; openDelete(viewDriverObj)"
                                class="flex-1 flex items-center justify-center gap-2 py-2.5 rounded-xl bg-red-500/5 border border-red-500/10 hover:bg-red-500/10 hover:border-red-500/20 text-[9px] font-bold uppercase tracking-widest text-red-400/60 hover:text-red-400 transition">
                                <i class="fa-solid fa-trash text-[8px]"></i>
                                Delete
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>


    <!-- ==================== REVIEW MODAL ==================== -->
    <div x-show="reviewModal" x-cloak
        class="fixed inset-0 z-[250] flex items-center justify-center p-4 bg-black/80"
        @click.self="reviewModal = false"
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" style="display:none;">

        <div class="glass-panel w-full max-w-4xl rounded-[2rem] overflow-hidden"
            @click.stop x-show="reviewModal"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">

            <div class="flex items-center justify-between px-6 sm:px-8 pt-6 sm:pt-7 pb-4">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-blue-500/10 border border-blue-500/15 flex items-center justify-center">
                        <i class="fa-solid fa-id-card text-xs text-blue-400"></i>
                    </div>
                    <div>
                        <h3 class="text-sm sm:text-base font-bold text-white">Driver Review</h3>
                        <p class="text-[9px] text-[#555]">
                            <span x-text="reviewEmail"></span>
                            <span class="text-[#333]" x-text="' · ' + reviewRegistered"></span>
                        </p>
                    </div>
                </div>
                <button @click="reviewModal = false"
                    class="w-8 h-8 rounded-lg bg-[#1a1a1a] border border-[#2a2a2a] flex items-center justify-center hover:bg-[#222] transition">
                    <i class="fa-solid fa-xmark text-[10px] text-[#555]"></i>
                </button>
            </div>

            <div class="mx-6 sm:mx-8 mb-5 p-3 rounded-xl bg-[#0a0a0a] border border-[#1e1e1e]">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-phone text-[8px] text-[#333]"></i>
                    <span class="text-[8px] font-bold uppercase tracking-[0.15em] text-[#333]">Contact</span>
                    <template x-if="reviewContact">
                        <span class="text-[11px] font-bold text-[#ccc]" x-text="reviewContact"></span>
                    </template>
                    <template x-if="!reviewContact">
                        <span class="text-[11px] text-[#333] italic">Not provided</span>
                    </template>
                </div>
            </div>

            <div class="px-6 sm:px-8 pb-6 sm:pb-8 modal-scroll overflow-y-auto" style="max-height: 70vh;">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-6">

                    <div class="md:col-span-2">
                        <p class="text-[8px] font-bold uppercase tracking-[0.15em] text-[#444] mb-3">Submitted ID</p>
                        <template x-if="reviewHasLicense">
                            <div class="md:sticky md:top-0">
                                <a :href="reviewLicenseUrl" target="_blank" rel="noopener"
                                    class="block rounded-xl border border-[#1e1e1e] overflow-hidden bg-black/40 hover:border-[#333] transition">
                                    <img :src="reviewLicenseUrl" alt="ID" loading="lazy" class="w-full max-h-72 object-contain">
                                </a>
                                <a :href="reviewLicenseUrl" target="_blank" rel="noopener"
                                    class="inline-flex items-center gap-1.5 mt-2 text-blue-400 hover:text-blue-300 text-[8px] font-bold uppercase tracking-widest transition">
                                    <i class="fa-solid fa-arrow-up-right-from-square text-[7px]"></i> Open full size
                                </a>
                            </div>
                        </template>
                        <template x-if="!reviewHasLicense">
                            <div class="flex flex-col items-center justify-center py-14 rounded-xl border border-dashed border-[#1e1e1e] bg-[#0a0a0a]">
                                <i class="fa-regular fa-image text-xl text-[#222] mb-2"></i>
                                <p class="text-[10px] text-[#333] font-medium">No ID image uploaded</p>
                            </div>
                        </template>
                    </div>

                    <div class="md:col-span-3">
                        <div class="flex items-center gap-3 mb-5">
                            <div class="flex-1 h-px bg-[#1e1e1e]"></div>
                            <span class="text-[7px] font-bold uppercase tracking-[0.15em] text-[#333]">Required to approve</span>
                            <div class="flex-1 h-px bg-[#1e1e1e]"></div>
                        </div>

                        <form :action="reviewApproveUrl" method="POST" class="space-y-3">
                            @csrf

                            <div>
                                <label class="block mb-1.5 text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Name <span class="text-red-400">*</span></label>
                                <input type="text" name="name" value="{{ old('name') }}" required
                                    class="w-full px-4 py-2.5 rounded-xl bg-[#111] border border-[#1e1e1e] text-[11px] text-white placeholder-[#333] focus:outline-none focus:border-[#333] transition">
                                @error('name')
                                    <p class="mt-1.5 text-[9px] text-red-400 flex items-center gap-1.5"><i class="fa-solid fa-circle-exclamation text-[7px]"></i> {{ $message }}</p>
                                @enderror
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block mb-1.5 text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">License Number <span class="text-red-400">*</span></label>
                                    <input type="text" name="license_number" value="{{ old('license_number') }}" required
                                        class="w-full px-4 py-2.5 rounded-xl bg-[#111] border border-[#1e1e1e] text-[11px] text-white placeholder-[#333] focus:outline-none focus:border-[#333] transition">
                                    @error('license_number')
                                        <p class="mt-1.5 text-[9px] text-red-400 flex items-center gap-1.5"><i class="fa-solid fa-circle-exclamation text-[7px]"></i> {{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="block mb-1.5 text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">License Code <span class="text-red-400">*</span></label>
                                    <input type="text" name="license_code" value="{{ old('license_code') }}" required
                                        class="w-full px-4 py-2.5 rounded-xl bg-[#111] border border-[#1e1e1e] text-[11px] text-white placeholder-[#333] focus:outline-none focus:border-[#333] transition">
                                    @error('license_code')
                                        <p class="mt-1.5 text-[9px] text-red-400 flex items-center gap-1.5"><i class="fa-solid fa-circle-exclamation text-[7px]"></i> {{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block mb-1.5 text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Expiration Date <span class="text-red-400">*</span></label>
                                    <input type="date" name="expiration_date" value="{{ old('expiration_date') }}" required
                                        class="w-full px-4 py-2.5 rounded-xl bg-[#111] border border-[#1e1e1e] text-[11px] text-white focus:outline-none focus:border-[#333] transition">
                                    @error('expiration_date')
                                        <p class="mt-1.5 text-[9px] text-red-400 flex items-center gap-1.5"><i class="fa-solid fa-circle-exclamation text-[7px]"></i> {{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="block mb-1.5 text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Driver Code <span class="text-red-400">*</span></label>
                                    <input type="text" name="driver_code" value="{{ old('driver_code') }}" required
                                        class="w-full px-4 py-2.5 rounded-xl bg-[#111] border border-[#1e1e1e] text-[11px] text-white placeholder-[#333] focus:outline-none focus:border-[#333] transition">
                                    @error('driver_code')
                                        <p class="mt-1.5 text-[9px] text-red-400 flex items-center gap-1.5"><i class="fa-solid fa-circle-exclamation text-[7px]"></i> {{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="flex gap-2.5 pt-3">
                                <button type="button" @click="reviewModal = false"
                                    class="flex-1 py-2.5 rounded-xl bg-[#1a1a1a] border border-[#2a2a2a] text-white text-[9px] font-bold uppercase tracking-widest hover:bg-[#222] transition">
                                    Cancel
                                </button>
                                <button type="button" @click="rejectDriver()"
                                    class="flex-1 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white text-[9px] font-bold uppercase tracking-widest transition active:scale-[0.98]">
                                    <i class="fa-solid fa-ban text-[8px] mr-1"></i> Reject
                                </button>
                                <button type="submit"
                                    class="flex-1 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-[9px] font-bold uppercase tracking-widest transition active:scale-[0.98]">
                                    <i class="fa-solid fa-check text-[8px] mr-1"></i> Approve
                                </button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>


    <!-- ==================== EDIT MODAL ==================== -->
    <div x-show="editModal" x-cloak
        class="fixed inset-0 z-[250] flex items-center justify-center p-4 bg-black/80"
        @click.self="editModal = false"
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" style="display:none;">

        <div class="glass-panel w-full max-w-lg rounded-[2rem] overflow-hidden"
            @click.stop x-show="editModal"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">

            <div class="flex items-center justify-between px-6 sm:px-8 pt-6 sm:pt-7 pb-4">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-amber-500/10 border border-amber-500/15 flex items-center justify-center">
                        <i class="fa-solid fa-pen-to-square text-xs text-amber-400"></i>
                    </div>
                    <div>
                        <h3 class="text-sm sm:text-base font-bold text-white">Edit Driver</h3>
                        <p class="text-[9px] text-[#555]" x-text="editEmail"></p>
                    </div>
                </div>
                <button @click="editModal = false"
                    class="w-8 h-8 rounded-lg bg-[#1a1a1a] border border-[#2a2a2a] flex items-center justify-center hover:bg-[#222] transition">
                    <i class="fa-solid fa-xmark text-[10px] text-[#555]"></i>
                </button>
            </div>

            <div class="px-6 sm:px-8 pb-6 sm:pb-8 modal-scroll overflow-y-auto" style="max-height: 75vh;">
                <form id="editForm" @submit.prevent="submitEdit()" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block mb-1.5 text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Approval Status</label>
                        <div class="relative">
                            <select x-model="editStatus"
                                class="form-input w-full px-4 py-2.5 rounded-xl bg-[#111] border text-[11px] text-white focus:outline-none transition"
                                :class="editStatus === '1' ? 'border-emerald-500/30' : 'border-red-500/30'">
                                <option value="1">Approved</option>
                                <option value="0">Rejected</option>
                            </select>
                            <div class="absolute right-10 top-1/2 -translate-y-1/2 pointer-events-none">
                                <template x-if="editStatus === '1'">
                                    <i class="fa-solid fa-circle-check text-emerald-400 text-[10px]"></i>
                                </template>
                                <template x-if="editStatus === '0'">
                                    <i class="fa-solid fa-circle-xmark text-red-400 text-[10px]"></i>
                                </template>
                            </div>
                        </div>
                        <p class="mt-1.5 text-[8px] text-[#333] flex items-center gap-1">
                            <i class="fa-solid fa-info-circle text-[7px]"></i>
                            Rejecting will disable the driver's ability to sign in.
                        </p>
                    </div>

                    <div class="flex items-center gap-3 !my-4">
                        <div class="flex-1 h-px bg-[#1e1e1e]"></div>
                        <span class="text-[7px] font-bold uppercase tracking-[0.15em] text-[#333]">Driver details</span>
                        <div class="flex-1 h-px bg-[#1e1e1e]"></div>
                    </div>

                    <div>
                        <label class="block mb-1.5 text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Name <span class="text-red-400">*</span></label>
                        <input type="text" name="name" x-model="editName" required
                            class="w-full px-4 py-2.5 rounded-xl bg-[#111] border border-[#1e1e1e] text-[11px] text-white placeholder-[#333] focus:outline-none focus:border-[#333] transition">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block mb-1.5 text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">License Number <span class="text-red-400">*</span></label>
                            <input type="text" name="license_number" x-model="editLicenseNumber" required
                                class="w-full px-4 py-2.5 rounded-xl bg-[#111] border border-[#1e1e1e] text-[11px] text-white placeholder-[#333] focus:outline-none focus:border-[#333] transition">
                        </div>
                        <div>
                            <label class="block mb-1.5 text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">License Code <span class="text-red-400">*</span></label>
                            <input type="text" name="license_code" x-model="editLicenseCode" required
                                class="w-full px-4 py-2.5 rounded-xl bg-[#111] border border-[#1e1e1e] text-[11px] text-white placeholder-[#333] focus:outline-none focus:border-[#333] transition">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block mb-1.5 text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Expiration Date <span class="text-red-400">*</span></label>
                            <input type="date" name="expiration_date" x-model="editExpiration" required
                                class="w-full px-4 py-2.5 rounded-xl bg-[#111] border border-[#1e1e1e] text-[11px] text-white focus:outline-none focus:border-[#333] transition">
                        </div>
                        <div>
                            <label class="block mb-1.5 text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Driver Code <span class="text-red-400">*</span></label>
                            <input type="text" name="driver_code" x-model="editDriverCode" required
                                class="w-full px-4 py-2.5 rounded-xl bg-[#111] border border-[#1e1e1e] text-[11px] text-white placeholder-[#333] focus:outline-none focus:border-[#333] transition">
                        </div>
                    </div>

                    <div>
                        <label class="block mb-1.5 text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Contact Information <span class="text-red-400">*</span></label>
                        <input type="text" name="contact_info" x-model="editContact" required
                            class="w-full px-4 py-2.5 rounded-xl bg-[#111] border border-[#1e1e1e] text-[11px] text-white placeholder-[#333] focus:outline-none focus:border-[#333] transition">
                    </div>

                    <div class="flex gap-2.5 pt-2">
                        <button type="button" @click="editModal = false"
                            class="flex-1 py-2.5 rounded-xl bg-[#1a1a1a] border border-[#2a2a2a] text-white text-[10px] font-bold uppercase tracking-widest hover:bg-[#222] transition">
                            Cancel
                        </button>
                        <button type="submit"
                            class="flex-1 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-500 text-white text-[10px] font-bold uppercase tracking-widest transition active:scale-[0.98]">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <!-- ==================== DELETE MODAL ==================== -->
    <div x-show="showDeleteModal" x-cloak
        class="fixed inset-0 z-[250] flex items-center justify-center p-4 bg-black/80"
        @click.self="showDeleteModal = false"
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" style="display:none;">

        <div class="glass-panel p-6 sm:p-8 rounded-[2rem] max-w-sm w-full">
            <div class="text-center">
                <div class="w-14 h-14 bg-red-500/10 rounded-2xl flex items-center justify-center mx-auto mb-5 border border-red-500/20">
                    <i class="fa-solid fa-trash text-red-400 text-lg"></i>
                </div>
                <h3 class="text-lg font-bold text-white mb-1.5">Delete Driver?</h3>
                <p class="text-[11px] text-[#555] mb-1">This action is permanent and cannot be undone.</p>
                <p class="text-[10px] text-[#333] mb-5">The driver's license file will also be removed.</p>

                <div class="inline-flex items-center gap-2.5 mb-7 px-3.5 py-2 rounded-xl bg-[#0a0a0a] border border-[#1e1e1e]">
                    <div class="w-6 h-6 rounded-md bg-[#111] border border-[#1e1e1e] flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-id-badge text-[7px] text-[#444]"></i>
                    </div>
                    <span class="text-[10px] font-bold text-[#888] truncate max-w-[220px]" x-text="deleteEmail"></span>
                </div>

                <div class="flex gap-2.5">
                    <button @click="showDeleteModal = false"
                        class="flex-1 py-3 rounded-xl bg-[#1a1a1a] border border-[#2a2a2a] text-white text-[10px] font-bold uppercase tracking-widest hover:bg-[#222] transition">
                        Cancel
                    </button>
                    <form method="POST" :action="deleteUrl" class="flex-1">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="w-full py-3 rounded-xl bg-red-600 text-white text-[10px] font-bold uppercase tracking-widest hover:bg-red-700 transition active:scale-[0.98]">
                            Delete
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <script>
        function driverReview() {
            return {
                open: false,
                search: '',
                filter: 'all',
                drivers: [],

                addModal: false,

                viewModal: false,
                viewDriverObj: null,
                viewDriverId: null,
                viewEmail: '',
                viewName: '',
                viewContact: '',
                viewLicenseNumber: '',
                viewLicenseCode: '',
                viewExpirationFormatted: '',
                viewDriverCode: '',
                viewRegistered: '',
                viewStatus: '',
                viewHasLicense: false,
                viewLicenseUrl: '',

                get isExpired() {
                    if (!this.viewExpirationFormatted || this.viewExpirationFormatted === '—') return false;
                    try {
                        return new Date(this.viewExpirationFormatted) < new Date();
                    } catch(e) { return false; }
                },

                reviewModal: false,
                reviewDriverId: null,
                reviewEmail: '',
                reviewContact: '',
                reviewRegistered: '',
                reviewLicenseUrl: '',
                reviewHasLicense: false,
                reviewApproveUrl: '',
                reviewRejectUrl: '',

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

                showDeleteModal: false,
                deleteDriverId: null,
                deleteEmail: '',
                deleteUrl: '',

                init() {
                    try {
                        this.drivers = JSON.parse(document.getElementById('drivers-data').textContent);
                    } catch(e) {
                        this.drivers = [];
                    }

                    @if($errors->any() && old('_form_type') === 'add')
                        this.addModal = true;
                    @endif

                    @php $autoReviewId = session('review_driver_id'); @endphp
                    @if($autoReviewId)
                        setTimeout(() => {
                            const d = this.drivers.find(d => d.id === {{ $autoReviewId }});
                            if (d) this.openReview(d);
                        }, 300);
                    @endif
                },

                get filteredDrivers() {
                    return this.drivers.filter(d => {
                        const q = this.search.toLowerCase();
                        const matchSearch = d.email.toLowerCase().includes(q) || (d.name && d.name.toLowerCase().includes(q));
                        if (this.filter === 'all') return matchSearch;
                        return matchSearch && d.status === this.filter;
                    });
                },

                closeAllModals() {
                    this.addModal = false;
                    this.viewModal = false;
                    this.reviewModal = false;
                    this.editModal = false;
                    this.showDeleteModal = false;
                },

                openView(driver) {
                    this.viewDriverObj = driver;
                    this.viewDriverId = driver.id;
                    this.viewEmail = driver.email;
                    this.viewName = driver.name;
                    this.viewContact = driver.contact_info;
                    this.viewLicenseNumber = driver.license_number;
                    this.viewLicenseCode = driver.license_code;
                    this.viewExpirationFormatted = driver.expiration_formatted || '';
                    this.viewDriverCode = driver.driver_code;
                    this.viewRegistered = driver.created_at;
                    this.viewStatus = driver.status;
                    this.viewHasLicense = driver.has_license;
                    this.viewLicenseUrl = driver.license_url;
                    this.viewModal = true;
                },

                openReview(driver) {
                    this.reviewDriverId = driver.id;
                    this.reviewEmail = driver.email;
                    this.reviewContact = driver.contact_info;
                    this.reviewRegistered = driver.created_at;
                    this.reviewLicenseUrl = driver.license_url;
                    this.reviewHasLicense = driver.has_license;
                    this.reviewApproveUrl = driver.approve_url;
                    this.reviewRejectUrl = driver.reject_url;
                    this.reviewModal = true;
                },

                openEdit(driver) {
                    this.editDriverId = driver.id;
                    this.editEmail = driver.email;
                    this.editName = driver.name;
                    this.editLicenseNumber = driver.license_number;
                    this.editLicenseCode = driver.license_code;
                    this.editExpiration = driver.expiration_date;
                    this.editContact = driver.contact_info;
                    this.editDriverCode = driver.driver_code;
                    this.editStatus = driver.is_approved === 1 ? '1' : '0';
                    this.editUrl = driver.update_url;
                    this.editModal = true;
                },

                openDelete(driver) {
                    this.deleteDriverId = driver.id;
                    this.deleteEmail = driver.email;
                    this.deleteUrl = driver.delete_url;
                    this.showDeleteModal = true;
                },

                submitEdit() {
                    var form = document.createElement('form');
                    form.method = 'POST';
                    form.action = this.editUrl;

                    var token = document.createElement('input');
                    token.type = 'hidden'; token.name = '_token';
                    token.value = document.querySelector('meta[name="csrf-token"]').content;
                    form.appendChild(token);

                    var method = document.createElement('input');
                    method.type = 'hidden'; method.name = '_method'; method.value = 'PUT';
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
                        input.type = 'hidden'; input.name = key; input.value = fields[key];
                        form.appendChild(input);
                    }

                    var isApproved = document.createElement('input');
                    isApproved.type = 'hidden'; isApproved.name = 'is_approved';
                    isApproved.value = this.editStatus === '1' ? '1' : '0';
                    form.appendChild(isApproved);

                    var isRejected = document.createElement('input');
                    isRejected.type = 'hidden'; isRejected.name = 'is_rejected';
                    isRejected.value = this.editStatus === '0' ? '1' : '0';
                    form.appendChild(isRejected);

                    document.body.appendChild(form);
                    form.submit();
                },

                rejectDriver() {
                    if (!this.reviewRejectUrl) return;

                    var form = document.createElement('form');
                    form.method = 'POST';
                    form.action = this.reviewRejectUrl;

                    var token = document.createElement('input');
                    token.type = 'hidden'; token.name = '_token';
                    token.value = document.querySelector('meta[name="csrf-token"]').content;
                    form.appendChild(token);

                    var method = document.createElement('input');
                    method.type = 'hidden'; method.name = '_method'; method.value = 'PUT';
                    form.appendChild(method);

                    document.body.appendChild(form);
                    form.submit();
                }
            };
        }
    </script>

</body>

</html>
