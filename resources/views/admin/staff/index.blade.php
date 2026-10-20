<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SmartCommute | Manage Staff</title>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>

    @include('partials.head-scripts')

    <style>
        .sidebar-transition {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }

        .table-row {
            transition: all 0.2s ease !important;
        }

        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

@php
    $inputClass =
        'w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] text-[11px] text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-[#333] focus:outline-none focus:border-gray-300 dark:focus:border-[#333] transition';
    $labelClass = 'block mb-1.5 text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]';
    $ghostBtn =
        'flex-1 py-3 rounded-xl bg-gray-100 dark:bg-[#1a1a1a] border border-gray-200 dark:border-[#2a2a2a] text-gray-900 dark:text-white text-[10px] font-bold uppercase tracking-widest hover:bg-gray-200 dark:hover:bg-[#222] transition';
    $rows = $staff->map(
        fn($u) => [
            'id' => $u->id,
            'email' => $u->email,
            'role' => $u->roles->first()?->name,
            'role_label' => $roles[$u->roles->first()?->name] ?? '—',
            'is_suspended' => (bool) $u->is_suspended,
            'created_at' => $u->created_at?->format('M j, Y'),
            'is_self' => $u->id === Auth::id(),
        ],
    );
@endphp

<script type="text/json" id="staff-data">
    @json($rows->keyBy('id'))
</script>

<body class="antialiased text-gray-900 dark:text-white" x-data>
    <div x-data="{
        staff: JSON.parse(document.getElementById('staff-data').textContent),
        modal: null, // 'add' | 'edit' | 'suspend' | 'delete'
        selected: null,
        showPassword: false,
        form: { role: 'driver_manager' },
        open(modal, id = null) {
            this.selected = id ? { ...this.staff[id] } : null;
            this.form = { role: this.selected ? this.selected.role : 'driver_manager' };
            this.showPassword = false;
            this.modal = modal;
        },
        close() { this.modal = null; }
    }" @keydown.escape.window="close()">
        <x-layout.sidebar />

        <main :class="$store.sidebar.open ? 'md:ml-72' : 'md:ml-20'"
            class="sidebar-transition pt-8 pr-4 sm:pr-8 pb-8 pl-4 sm:pl-8 min-h-screen mb-12">

            <!-- ── Page Header ── -->
            <div class="mb-6 lg:mb-8">
                <div class="flex items-center gap-2 mb-1.5">
                    <span
                        class="text-[9px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Manage</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-gray-900 dark:text-white">Staff
                    <span class="text-blue-500 dark:text-blue-400">Accounts</span></h1>
                <p class="text-[11px] text-gray-500 dark:text-[#555] mt-1 flex items-center gap-2">
                    <i class="fa-solid fa-user-gear text-[9px] text-cyan-500 dark:text-cyan-400"></i>
                    <span class="text-gray-700 dark:text-[#888] font-bold">{{ $totals['all'] }}</span>
                    driver manager and maintenance manager accounts
                    @if ($totals['suspended'] > 0)
                        <span class="text-gray-300 dark:text-[#333]">•</span>
                        <span class="text-red-500 dark:text-red-400 font-bold">{{ $totals['suspended'] }} suspended</span>
                    @endif
                </p>
            </div>

            <!-- ══════════ TABLE CARD ══════════ -->
            <div class="glass-card rounded-[1.25rem] sm:rounded-[1.5rem] overflow-hidden">

                <div class="p-4 sm:p-5 border-b border-gray-200 dark:border-[#1e1e1e]">
                    <form action="{{ route('staff.index') }}" method="GET"
                        class="flex flex-col lg:flex-row gap-3">
                        <div class="relative flex-1">
                            <i
                                class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-[10px] text-gray-400 dark:text-[#333]"></i>
                            <input type="text" name="search" value="{{ $filters['search'] }}"
                                placeholder="Search by email..."
                                class="w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] rounded-xl text-[11px] text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-[#333] focus:outline-none focus:border-gray-300 dark:focus:border-[#333] transition">
                        </div>
                        <select name="role" onchange="this.form.submit()"
                            class="px-4 py-2.5 bg-gray-50 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] rounded-xl text-[11px] text-gray-900 dark:text-white focus:outline-none">
                            <option value="">All roles</option>
                            @foreach ($roles as $value => $label)
                                <option value="{{ $value }}" @selected($filters['role'] === $value)>{{ $label }}
                                </option>
                            @endforeach
                        </select>
                        <select name="status" onchange="this.form.submit()"
                            class="px-4 py-2.5 bg-gray-50 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] rounded-xl text-[11px] text-gray-900 dark:text-white focus:outline-none">
                            <option value="">Any status</option>
                            <option value="active" @selected($filters['status'] === 'active')>Active</option>
                            <option value="suspended" @selected($filters['status'] === 'suspended')>Suspended</option>
                        </select>
                        <button type="submit"
                            class="flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-gray-900 dark:bg-white/10 text-[10px] font-bold uppercase tracking-widest text-white transition active:scale-[0.98] shrink-0">
                            <i class="fa-solid fa-filter text-[9px]"></i><span>Filter</span>
                        </button>
                        @if ($filters['search'] || $filters['role'] || $filters['status'])
                            <a href="{{ route('staff.index') }}"
                                class="flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-gray-50 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] text-[10px] font-bold uppercase tracking-widest text-gray-500 dark:text-[#555] shrink-0">
                                <i class="fa-solid fa-xmark text-[9px]"></i><span>Clear</span>
                            </a>
                        @endif
                        <button type="button" @click="open('add')"
                            class="flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-[10px] font-bold uppercase tracking-widest text-white transition active:scale-[0.98] shrink-0">
                            <i class="fa-solid fa-plus text-[9px]"></i><span>Add Staff</span>
                        </button>
                    </form>
                </div>

                <div class="overflow-x-auto -mx-2 px-2 pb-2">
                    <table class="w-full text-left min-w-[640px]">
                        <thead>
                            <tr
                                class="text-[8px] sm:text-[9px] uppercase tracking-[0.15em] text-gray-400 dark:text-[#444] border-b border-gray-200 dark:border-[#1e1e1e]">
                                <th class="px-4 sm:px-6 py-3 font-bold">Staff</th>
                                <th class="px-4 sm:px-6 py-3 font-bold">Role</th>
                                <th class="px-4 sm:px-6 py-3 font-bold">Status</th>
                                <th class="px-4 sm:px-6 py-3 font-bold">Created</th>
                                <th class="px-4 sm:px-6 py-3 font-bold text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-[#1a1a1a]">
                            @forelse ($rows as $row)
                                <tr class="table-row">
                                    <td class="px-4 sm:px-6 py-3">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] flex items-center justify-center shrink-0">
                                                <span
                                                    class="text-[9px] font-black text-gray-500 dark:text-[#555]">{{ strtoupper(substr($row['email'], 0, 1)) }}</span>
                                            </div>
                                            <span
                                                class="text-[10px] sm:text-[11px] font-bold text-gray-700 dark:text-[#ccc] truncate max-w-[240px]">{{ $row['email'] }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 sm:px-6 py-3">
                                        <span
                                            class="text-[7px] sm:text-[8px] border px-1.5 py-0.5 rounded-md font-bold uppercase {{ $row['role'] === 'driver_manager' ? 'bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 border-cyan-500/15' : 'bg-orange-500/10 text-orange-600 dark:text-orange-400 border-orange-500/15' }}">{{ $row['role_label'] }}</span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-3">
                                        @if ($row['is_suspended'])
                                            <span
                                                class="text-[7px] sm:text-[8px] bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/15 px-1.5 py-0.5 rounded-md font-bold uppercase">Suspended</span>
                                        @else
                                            <span
                                                class="text-[7px] sm:text-[8px] bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/15 px-1.5 py-0.5 rounded-md font-bold uppercase">Active</span>
                                        @endif
                                    </td>
                                    <td class="px-4 sm:px-6 py-3">
                                        <span
                                            class="text-[10px] font-bold text-gray-500 dark:text-[#555]">{{ $row['created_at'] }}</span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-3">
                                        <div class="flex items-center gap-1.5 justify-end">
                                            <button type="button" @click="open('edit', {{ $row['id'] }})" title="Edit"
                                                class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] hover:bg-gray-200 dark:hover:bg-[#1a1a1a] flex items-center justify-center transition group">
                                                <i
                                                    class="fa-solid fa-pen text-[8px] text-gray-400 dark:text-[#444] group-hover:text-gray-900 dark:group-hover:text-white transition"></i>
                                            </button>
                                            <button type="button" @click="open('suspend', {{ $row['id'] }})"
                                                title="{{ $row['is_suspended'] ? 'Reactivate' : 'Suspend' }}"
                                                class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] hover:bg-gray-200 dark:hover:bg-[#1a1a1a] flex items-center justify-center transition group">
                                                <i
                                                    class="fa-solid {{ $row['is_suspended'] ? 'fa-play text-emerald-500/60 group-hover:text-emerald-500' : 'fa-ban text-amber-500/60 group-hover:text-amber-500' }} text-[8px] transition"></i>
                                            </button>
                                            <button type="button" @click="open('delete', {{ $row['id'] }})"
                                                title="Delete"
                                                class="w-8 h-8 rounded-lg bg-red-500/5 border border-red-500/10 hover:bg-red-500/10 hover:border-red-500/20 flex items-center justify-center transition group">
                                                <i
                                                    class="fa-solid fa-trash text-[8px] text-red-500/40 group-hover:text-red-500 dark:group-hover:text-red-400 transition"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-12 sm:py-16">
                                        <div class="flex flex-col items-center justify-center">
                                            <div
                                                class="w-12 h-12 rounded-xl bg-gray-100 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] flex items-center justify-center mb-3">
                                                <i
                                                    class="fa-solid fa-user-gear text-base text-gray-300 dark:text-[#222]"></i>
                                            </div>
                                            <p class="text-[11px] text-gray-400 dark:text-[#444] font-medium">
                                                {{ $filters['search'] || $filters['role'] || $filters['status'] ? 'No staff accounts match your filters' : 'No staff accounts yet' }}
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($staff->hasPages())
                    <div class="px-4 sm:px-6 py-4 border-t border-gray-200 dark:border-[#1e1e1e]">
                        {{ $staff->links('vendor.pagination.custom') }}
                    </div>
                @endif
            </div>
        </main>

        <!-- ══════════ ADD / EDIT MODAL ══════════ -->
        <div x-show="modal === 'add' || modal === 'edit'" x-cloak
            class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 dark:bg-black/80"
            style="display:none;">
            <div @click.away="close()"
                class="glass-panel p-6 sm:p-8 rounded-[2rem] max-w-md w-full max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-base sm:text-lg font-bold text-gray-900 dark:text-white"
                            x-text="modal === 'add' ? 'Add Staff Account' : 'Edit Staff Account'"></h3>
                        <p class="text-[10px] text-gray-500 dark:text-[#555] mt-0.5"
                            x-text="modal === 'add' ? 'Create a driver manager or maintenance manager' : selected?.email">
                        </p>
                    </div>
                    <button type="button" @click="close()"
                        class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-[#1a1a1a] border border-gray-200 dark:border-[#2a2a2a] flex items-center justify-center hover:bg-gray-200 dark:hover:bg-[#222] transition">
                        <i class="fa-solid fa-xmark text-[10px] text-gray-500 dark:text-[#555]"></i>
                    </button>
                </div>

                <form method="POST"
                    :action="modal === 'add' ? '{{ route('staff.store') }}' : '{{ url('/staff') }}/' + (selected?.id || '')"
                    class="space-y-4" data-ajax novalidate>
                    @csrf
                    <template x-if="modal === 'edit'"><input type="hidden" name="_method" value="PUT"></template>

                    <div>
                        <label class="{{ $labelClass }}">Email</label>
                        <input type="email" name="email" :value="modal === 'edit' ? selected?.email : ''"
                            class="{{ $inputClass }}" placeholder="name@company.com" autocomplete="off" data-rules="required|email|max:255">
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Role</label>
                        <select name="role" x-model="form.role" class="{{ $inputClass }}">
                            @foreach ($roles as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="{{ $labelClass }}"
                            x-text="modal === 'add' ? 'Password' : 'New password (optional)'"></label>
                        <div class="relative">
                            <input :type="showPassword ? 'text' : 'password'" name="password"
                                class="{{ $inputClass }}"
                                :placeholder="modal === 'add' ? 'Minimum 8 characters' : 'Leave blank to keep current'"
                                autocomplete="new-password" x-bind:data-rules="modal === 'add' ? 'required|min:8' : 'min:8'">
                            <button type="button" @click="showPassword = !showPassword"
                                class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 dark:text-[#444] hover:text-gray-900 dark:hover:text-white transition">
                                <i :class="showPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"
                                    class="text-[10px]"></i>
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Confirm password</label>
                        <input :type="showPassword ? 'text' : 'password'" name="password_confirmation"
                            class="{{ $inputClass }}" placeholder="Re-enter password" autocomplete="new-password" :data-rules="modal === 'add' ? 'required|same:password' : 'same:password'">
                    </div>

                    <div class="flex gap-2.5 pt-1">
                        <button type="button" @click="close()" class="{{ $ghostBtn }}">Cancel</button>
                        <button type="submit"
                            class="flex-1 py-3 rounded-xl bg-blue-600 hover:bg-blue-500 text-[10px] font-bold uppercase tracking-widest text-white transition active:scale-[0.98]"
                            x-text="modal === 'add' ? 'Create Account' : 'Save Changes'"></button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ══════════ SUSPEND MODAL ══════════ -->
        <div x-show="modal === 'suspend'" x-cloak
            class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 dark:bg-black/80"
            style="display:none;">
            <div @click.away="close()" class="glass-panel p-6 sm:p-8 rounded-[2rem] max-w-sm w-full text-center">
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-5 border"
                    :class="selected?.is_suspended ? 'bg-emerald-500/10 border-emerald-500/20' :
                        'bg-amber-500/10 border-amber-500/20'">
                    <i class="fa-solid text-lg"
                        :class="selected?.is_suspended ? 'fa-play text-emerald-500' : 'fa-ban text-amber-500'"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1.5"
                    x-text="selected?.is_suspended ? 'Reactivate account?' : 'Suspend account?'"></h3>
                <p class="text-[11px] text-gray-500 dark:text-[#555] mb-1"
                    x-text="selected?.is_suspended ? 'They will be able to sign in again.' : 'They are signed out immediately and cannot sign in until reactivated.'">
                </p>
                <p class="text-[10px] font-bold text-gray-700 dark:text-[#888] mb-6" x-text="selected?.email"></p>
                <div class="flex gap-2.5">
                    <button type="button" @click="close()" class="{{ $ghostBtn }}">Cancel</button>
                    <form method="POST" :action="'{{ url('/staff') }}/' + (selected?.id || '') + '/suspension'"
                        class="flex-1" data-ajax novalidate>
                        @csrf
                        @method('PATCH')
                        <button type="submit"
                            class="w-full py-3 rounded-xl text-white text-[10px] font-bold uppercase tracking-widest transition active:scale-[0.98]"
                            :class="selected?.is_suspended ? 'bg-emerald-600 hover:bg-emerald-500' :
                                'bg-amber-600 hover:bg-amber-500'">Confirm</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- ══════════ DELETE MODAL ══════════ -->
        <div x-show="modal === 'delete'" x-cloak
            class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 dark:bg-black/80"
            style="display:none;">
            <div @click.away="close()" class="glass-panel p-6 sm:p-8 rounded-[2rem] max-w-sm w-full text-center">
                <div
                    class="w-14 h-14 bg-red-500/10 rounded-2xl flex items-center justify-center mx-auto mb-5 border border-red-500/20">
                    <i class="fa-solid fa-trash text-red-500 dark:text-red-400 text-lg"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1.5">Delete staff account?</h3>
                <p class="text-[11px] text-gray-500 dark:text-[#555] mb-1">This action is permanent and cannot be
                    undone.</p>
                <p class="text-[10px] font-bold text-gray-700 dark:text-[#888] mb-6" x-text="selected?.email"></p>
                <div class="flex gap-2.5">
                    <button type="button" @click="close()" class="{{ $ghostBtn }}">Cancel</button>
                    <form method="POST" :action="'{{ url('/staff') }}/' + (selected?.id || '')" class="flex-1"
                        data-ajax novalidate>
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="w-full py-3 rounded-xl bg-red-600 text-white text-[10px] font-bold uppercase tracking-widest hover:bg-red-700 transition active:scale-[0.98]">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
