<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartCommute | Manage PUJ Commuters</title>
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
    </style>
</head>

@php
    $mappedCommuters = $commuters->map(fn($c) => [
        'id' => $c->id,
        'email' => $c->email,
        'email_verified_at' => $c->email_verified_at,
        'created_at' => $c->created_at->format('M j, Y g:i A'),
    ])->values();
@endphp

<script type="text/json" id="commuters-data">
    @json($mappedCommuters)
</script>

<body class="antialiased text-white" x-data="{
    open: false,
    search: '',
    filter: 'all',
    showAddModal: false,
    showEditModal: false,
    showDeleteModal: false,
    selectedUser: null,
    commuters: [],

    init() {
        try {
            this.commuters = JSON.parse(document.getElementById('commuters-data').textContent);
        } catch(e) {
            this.commuters = [];
        }

        @if($errors->any() && old('_form_type') === 'create')
            this.showAddModal = true;
        @endif
        @if($errors->any() && old('_form_type') === 'edit')
            this.selectedUser = {
                id: {{ old('_edit_id', 0) }},
                email: '{{ old('email', '') }}',
                email_verified_at: {{ old('mark_verified') ? '"yes"' : 'null' }},
                created_at: ''
            };
            this.showEditModal = true;
        @endif
    },

    get filteredCommuters() {
        return this.commuters.filter(c => {
            const matchSearch = c.email.toLowerCase().includes(this.search.toLowerCase());
            if (this.filter === 'all') return matchSearch;
            if (this.filter === 'verified') return matchSearch && c.email_verified_at;
            if (this.filter === 'pending') return matchSearch && !c.email_verified_at;
            return matchSearch;
        });
    },

    openEdit(user) {
        this.selectedUser = { ...user };
        this.showEditModal = true;
    },

    openDelete(user) {
        this.selectedUser = { ...user };
        this.showDeleteModal = true;
    }
}">

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
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">PUJ Commuters</h1>
            <p class="text-[11px] text-[#555] mt-1 flex items-center gap-2">
                <i class="fa-solid fa-users text-[9px] text-purple-400"></i>
                <span class="text-[#888] font-bold">{{ $commuters instanceof \Illuminate\Pagination\LengthAwarePaginator ? $commuters->total() : count($commuters) }}</span> registered commuter accounts
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
                        <input x-model="search" type="text" placeholder="Search by email..."
                            class="w-full pl-10 pr-10 py-2.5 bg-[#111] border border-[#1e1e1e] rounded-xl text-[11px] text-white placeholder-[#333] focus:outline-none focus:border-[#333] transition">
                        <button x-show="search.length > 0" @click="search = ''"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-[#444] hover:text-white transition">
                            <i class="fa-solid fa-xmark text-[10px]"></i>
                        </button>
                    </div>
                    <button @click="showAddModal = true"
                        class="flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-[10px] font-bold uppercase tracking-widest text-white transition active:scale-[0.98] shrink-0">
                        <i class="fa-solid fa-plus text-[9px]"></i>
                        <span>Add Commuter</span>
                    </button>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-1.5">
                        <button @click="filter = 'all'"
                            :class="filter === 'all' ? 'bg-white/10 text-white border-white/20' : 'bg-[#111] text-[#555] border-[#1e1e1e] hover:border-[#333] hover:text-[#888]'"
                            class="px-3 py-1.5 rounded-lg text-[8px] font-bold uppercase tracking-widest border transition">
                            All
                        </button>
                        <button @click="filter = 'verified'"
                            :class="filter === 'verified' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 'bg-[#111] text-[#555] border-[#1e1e1e] hover:border-[#333] hover:text-[#888]'"
                            class="px-3 py-1.5 rounded-lg text-[8px] font-bold uppercase tracking-widest border transition">
                            Verified
                        </button>
                        <button @click="filter = 'pending'"
                            :class="filter === 'pending' ? 'bg-amber-500/10 text-amber-400 border-amber-500/20' : 'bg-[#111] text-[#555] border-[#1e1e1e] hover:border-[#333] hover:text-[#888]'"
                            class="px-3 py-1.5 rounded-lg text-[8px] font-bold uppercase tracking-widest border transition">
                            Pending
                        </button>
                    </div>
                    <span class="text-[8px] font-bold text-[#333] uppercase tracking-widest"
                        x-text="filteredCommuters.length + ' of ' + commuters.length"></span>
                </div>
            </div>

            <!-- ── Table ── -->
            <div class="overflow-x-auto -mx-2 px-2 pb-2">
                <table class="w-full text-left min-w-[600px]">
                    <thead>
                        <tr class="text-[8px] sm:text-[9px] uppercase tracking-[0.15em] text-[#444] border-b border-[#1e1e1e]">
                            <th class="px-4 sm:px-6 py-3 font-bold w-12">#</th>
                            <th class="px-4 sm:px-6 py-3 font-bold">Commuter</th>
                            <th class="px-4 sm:px-6 py-3 font-bold">Status</th>
                            <th class="px-4 sm:px-6 py-3 font-bold">Registered</th>
                            <th class="px-4 sm:px-6 py-3 font-bold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#1a1a1a]">

                        <template x-for="(commuter, index) in filteredCommuters" :key="commuter.id">
                            <tr class="table-row">
                                <td class="px-4 sm:px-6 py-3">
                                    <span class="text-[10px] font-bold text-[#333]" x-text="String(index + 1).padStart(2, '0')"></span>
                                </td>
                                <td class="px-4 sm:px-6 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-[#111] border border-[#1e1e1e] flex items-center justify-center shrink-0">
                                            <span class="text-[9px] font-black text-[#555]" x-text="commuter.email.charAt(0).toUpperCase()"></span>
                                        </div>
                                        <span class="text-[10px] sm:text-[11px] font-bold text-[#ccc] truncate max-w-[220px]" x-text="commuter.email"></span>
                                    </div>
                                </td>
                                <td class="px-4 sm:px-6 py-3">
                                    <template x-if="commuter.email_verified_at">
                                        <span class="text-[7px] sm:text-[8px] bg-emerald-500/10 text-emerald-400 border border-emerald-500/15 px-1.5 py-0.5 rounded-md font-bold uppercase">Verified</span>
                                    </template>
                                    <template x-if="!commuter.email_verified_at">
                                        <span class="text-[7px] sm:text-[8px] bg-amber-500/10 text-amber-400 border border-amber-500/15 px-1.5 py-0.5 rounded-md font-bold uppercase">Pending</span>
                                    </template>
                                </td>
                                <td class="px-4 sm:px-6 py-3">
                                    <span class="text-[10px] font-bold text-[#555]" x-text="commuter.created_at"></span>
                                </td>
                                <td class="px-4 sm:px-6 py-3">
                                    <div class="flex items-center gap-1.5 justify-end">
                                        <button @click="openEdit(commuter)"
                                            class="w-8 h-8 rounded-lg bg-[#111] border border-[#1e1e1e] hover:bg-[#1a1a1a] hover:border-[#333] flex items-center justify-center transition group"
                                            title="Edit">
                                            <i class="fa-solid fa-pen text-[8px] text-[#444] group-hover:text-white transition"></i>
                                        </button>
                                        <button @click="openDelete(commuter)"
                                            class="w-8 h-8 rounded-lg bg-red-500/5 border border-red-500/10 hover:bg-red-500/10 hover:border-red-500/20 flex items-center justify-center transition group"
                                            title="Delete">
                                            <i class="fa-solid fa-trash text-[8px] text-red-500/40 group-hover:text-red-400 transition"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <!-- Empty: no commuters at all -->
                        <template x-if="commuters.length === 0">
                            <tr>
                                <td colspan="5" class="py-12 sm:py-16">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="w-12 h-12 rounded-xl bg-[#111] border border-[#1e1e1e] flex items-center justify-center mb-3">
                                            <i class="fa-solid fa-users text-base text-[#222]"></i>
                                        </div>
                                        <p class="text-[11px] text-[#444] font-medium mb-4">No commuter accounts yet</p>
                                        <button @click="showAddModal = true"
                                            class="flex items-center gap-2 px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-[9px] font-bold uppercase tracking-widest text-white transition">
                                            <i class="fa-solid fa-plus text-[8px]"></i>
                                            <span>Add First Commuter</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <!-- Empty: no search results -->
                        <template x-if="commuters.length > 0 && filteredCommuters.length === 0">
                            <tr>
                                <td colspan="5" class="py-12 sm:py-16">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="w-12 h-12 rounded-xl bg-[#111] border border-[#1e1e1e] flex items-center justify-center mb-3">
                                            <i class="fa-solid fa-magnifying-glass text-base text-[#222]"></i>
                                        </div>
                                        <p class="text-[11px] text-[#444] font-medium">No commuters match your search</p>
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


    <!-- ══════════ ADD COMMUTER MODAL ══════════ -->
    <div x-show="showAddModal" x-cloak
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/80"
        x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" style="display:none;">

        <div @click.away="showAddModal = false"
            class="glass-panel p-6 sm:p-8 rounded-[2rem] max-w-md w-full max-h-[90vh] overflow-y-auto">

            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-base sm:text-lg font-bold text-white">Add Commuter</h3>
                    <p class="text-[10px] text-[#555] mt-0.5">Create a new PUJ passenger account</p>
                </div>
                <button @click="showAddModal = false"
                    class="w-8 h-8 rounded-lg bg-[#1a1a1a] border border-[#2a2a2a] flex items-center justify-center hover:bg-[#222] transition">
                    <i class="fa-solid fa-xmark text-[10px] text-[#555]"></i>
                </button>
            </div>

            <form action="{{ route('admin.commuters.store') }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="_form_type" value="create">

                <div>
                    <label class="block mb-1.5 text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Email</label>
                    <input type="email" name="email" value="{{ old('email', '') }}" required
                        class="w-full px-4 py-3 rounded-xl bg-[#111] border border-[#1e1e1e] text-[11px] text-white placeholder-[#333] focus:outline-none focus:border-[#333] transition">
                    @error('email')
                        <p class="mt-1.5 text-[9px] text-red-400 flex items-center gap-1.5">
                            <i class="fa-solid fa-circle-exclamation text-[7px]"></i> {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label class="block mb-1.5 text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Password</label>
                    <input type="password" name="password" required
                        class="w-full px-4 py-3 rounded-xl bg-[#111] border border-[#1e1e1e] text-[11px] text-white placeholder-[#333] focus:outline-none focus:border-[#333] transition"
                        placeholder="Minimum 8 characters">
                    @error('password')
                        <p class="mt-1.5 text-[9px] text-red-400 flex items-center gap-1.5">
                            <i class="fa-solid fa-circle-exclamation text-[7px]"></i> {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label class="block mb-1.5 text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Confirm Password</label>
                    <input type="password" name="password_confirmation" required
                        class="w-full px-4 py-3 rounded-xl bg-[#111] border border-[#1e1e1e] text-[11px] text-white placeholder-[#333] focus:outline-none focus:border-[#333] transition"
                        placeholder="Re-enter password">
                </div>

                <div class="pt-1 border-t border-[#1e1e1e]">
                    <label class="flex items-center gap-3 cursor-pointer group py-1">
                        <div class="relative flex items-center justify-center">
                            <input type="checkbox" name="mark_verified" value="1"
                                class="peer sr-only" {{ old('mark_verified') ? 'checked' : '' }}>
                            <div class="w-9 h-5 rounded-full bg-[#222] border border-[#2a2a2a] peer-checked:bg-blue-600 peer-checked:border-blue-500 transition"></div>
                            <div class="absolute left-0.5 w-4 h-4 bg-[#555] rounded-full peer-checked:translate-x-4 peer-checked:bg-white transition"></div>
                        </div>
                        <span class="text-[10px] font-bold text-[#555] group-hover:text-[#888] transition">Mark email as verified</span>
                    </label>
                </div>

                <button type="submit"
                    class="w-full py-3 rounded-xl bg-blue-600 hover:bg-blue-500 text-[10px] font-bold uppercase tracking-widest text-white transition active:scale-[0.98]">
                    Create Commuter
                </button>
            </form>
        </div>
    </div>


    <!-- ══════════ EDIT COMMUTER MODAL ══════════ -->
    <div x-show="showEditModal" x-cloak
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/80"
        x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" style="display:none;">

        <div @click.away="showEditModal = false"
            class="glass-panel p-6 sm:p-8 rounded-[2rem] max-w-md w-full max-h-[90vh] overflow-y-auto">

            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-base sm:text-lg font-bold text-white">Edit Commuter</h3>
                    <p class="text-[10px] text-[#555] mt-0.5 truncate max-w-[260px]" x-text="selectedUser?.email"></p>
                </div>
                <button @click="showEditModal = false"
                    class="w-8 h-8 rounded-lg bg-[#1a1a1a] border border-[#2a2a2a] flex items-center justify-center hover:bg-[#222] transition">
                    <i class="fa-solid fa-xmark text-[10px] text-[#555]"></i>
                </button>
            </div>

            <form method="POST" :action="'/admin/commuters/' + (selectedUser?.id || '')" class="space-y-4">
                @csrf
                @method('PUT')
                <input type="hidden" name="_form_type" value="edit">
                <input type="hidden" name="_edit_id" :value="selectedUser?.id">

                <div>
                    <label class="block mb-1.5 text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Email</label>
                    <input type="email" name="email" :value="selectedUser?.email" required
                        class="w-full px-4 py-3 rounded-xl bg-[#111] border border-[#1e1e1e] text-[11px] text-white placeholder-[#333] focus:outline-none focus:border-[#333] transition">
                    @error('email')
                        <p class="mt-1.5 text-[9px] text-red-400 flex items-center gap-1.5">
                            <i class="fa-solid fa-circle-exclamation text-[7px]"></i> {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label class="block mb-1.5 text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">New Password</label>
                    <input type="password" name="password"
                        class="w-full px-4 py-3 rounded-xl bg-[#111] border border-[#1e1e1e] text-[11px] text-white placeholder-[#333] focus:outline-none focus:border-[#333] transition"
                        placeholder="Leave blank to keep current">
                    @error('password')
                        <p class="mt-1.5 text-[9px] text-red-400 flex items-center gap-1.5">
                            <i class="fa-solid fa-circle-exclamation text-[7px]"></i> {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label class="block mb-1.5 text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Confirm New Password</label>
                    <input type="password" name="password_confirmation"
                        class="w-full px-4 py-3 rounded-xl bg-[#111] border border-[#1e1e1e] text-[11px] text-white placeholder-[#333] focus:outline-none focus:border-[#333] transition"
                        placeholder="Re-enter new password">
                </div>

                <div class="pt-1 border-t border-[#1e1e1e]">
                    <label class="flex items-center gap-3 cursor-pointer group py-1">
                        <div class="relative flex items-center justify-center">
                            <input type="checkbox" name="mark_verified" value="1"
                                class="peer sr-only"
                                :checked="selectedUser?.email_verified_at ? true : false">
                            <div class="w-9 h-5 rounded-full bg-[#222] border border-[#2a2a2a] peer-checked:bg-blue-600 peer-checked:border-blue-500 transition"></div>
                            <div class="absolute left-0.5 w-4 h-4 bg-[#555] rounded-full peer-checked:translate-x-4 peer-checked:bg-white transition"></div>
                        </div>
                        <span class="text-[10px] font-bold text-[#555] group-hover:text-[#888] transition">Email verified</span>
                    </label>
                </div>

                <div class="flex gap-2.5 pt-1">
                    <button type="button" @click="showEditModal = false"
                        class="flex-1 py-3 rounded-xl bg-[#1a1a1a] border border-[#2a2a2a] text-white text-[10px] font-bold uppercase tracking-widest hover:bg-[#222] transition">
                        Cancel
                    </button>
                    <button type="submit"
                        class="flex-1 py-3 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-[10px] font-bold uppercase tracking-widest transition active:scale-[0.98]">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>


    <!-- ══════════ DELETE COMMUTER MODAL ══════════ -->
    <div x-show="showDeleteModal" x-cloak
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/80"
        x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" style="display:none;">

        <div @click.away="showDeleteModal = false"
            class="glass-panel p-6 sm:p-8 rounded-[2rem] max-w-sm w-full">

            <div class="text-center">
                <div class="w-14 h-14 bg-red-500/10 rounded-2xl flex items-center justify-center mx-auto mb-5 border border-red-500/20">
                    <i class="fa-solid fa-trash text-red-400 text-lg"></i>
                </div>
                <h3 class="text-lg font-bold text-white mb-1.5">Delete Commuter?</h3>
                <p class="text-[11px] text-[#555] mb-1">This action is permanent and cannot be undone.</p>
                <div class="inline-flex items-center gap-2 mt-2.5 mb-7 px-3.5 py-2 rounded-xl bg-[#0a0a0a] border border-[#1e1e1e]">
                    <div class="w-6 h-6 rounded-md bg-[#111] border border-[#1e1e1e] flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-user text-[7px] text-[#444]"></i>
                    </div>
                    <span class="text-[10px] font-bold text-[#888] truncate max-w-[220px]" x-text="selectedUser?.email"></span>
                </div>

                <div class="flex gap-2.5">
                    <button @click="showDeleteModal = false"
                        class="flex-1 py-3 rounded-xl bg-[#1a1a1a] border border-[#2a2a2a] text-white text-[10px] font-bold uppercase tracking-widest hover:bg-[#222] transition">
                        Cancel
                    </button>
                    <form method="POST" :action="'/admin/commuters/' + (selectedUser?.id || '')" class="flex-1">
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

</body>

</html>
