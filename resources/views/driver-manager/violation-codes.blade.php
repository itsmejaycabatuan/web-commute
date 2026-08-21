<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SmartCommute | Violation Codes</title>
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

        input[type="number"]::-webkit-inner-spin-button,
        input[type="number"]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
    </style>
</head>

<body class="antialiased text-white" x-data="{ open: false }">

    <x-layout.sidebar :menu-items="$sidebarMenu" />

    <main :class="open ? 'md:ml-[270px]' : 'md:ml-[76px]'" class="sidebar-transition pt-8 pr-4 sm:pr-8 pb-8 pl-4 sm:pl-8 min-h-screen mb-12"
          x-data="violationManager()">

        <!-- ── Mobile: Identity Card ── -->
        <div class="lg:hidden mb-5">
            <div class="glass-card p-4 rounded-[1.25rem]">
                <div class="flex items-center gap-3.5">
                    <div class="w-11 h-11 bg-blue-600 rounded-xl flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-gauge-high text-white text-sm"></i>
                    </div>
                    <div class="min-w-0">
                        <h2 class="text-sm font-bold text-white truncate">Driver Manager</h2>
                        <p class="text-[10px] text-[#555] truncate">{{ Auth::user()->email }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 pt-3.5 mt-3.5 border-t border-[#1e1e1e]">
                    <i class="fa-solid fa-id-badge text-[8px] text-blue-400"></i>
                    <span class="text-[10px] text-[#888] font-bold">Driver Oversight</span>
                    <span class="text-[#333]">•</span>
                    <span class="font-mono text-[9px] text-[#444]">Manager</span>
                </div>
            </div>
        </div>

        <!-- ── Page Header (desktop) ── -->
        <div class="hidden lg:block mb-8">
            <div class="flex items-center gap-2 mb-1.5">
                <span class="text-[9px] font-bold uppercase tracking-[0.15em] text-[#444]">Manage</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">Violation Codes</h1>
            <p class="text-[11px] text-[#555] mt-1 flex items-center gap-2">
                <i class="fa-solid fa-road text-[9px] text-amber-400"></i>
                <span class="text-[#888] font-bold">{{ count($violation_codes ?? []) }}</span> violation codes defined
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

            <div class="p-4 sm:p-5 border-b border-[#1e1e1e]">
                <div class="flex items-center justify-between">
                    <span class="text-[8px] font-bold text-[#333] uppercase tracking-widest">
                        {{ count($violation_codes ?? []) }} codes
                    </span>
                    <div class="flex items-center gap-2">
                        <button @click="openBulkModal()"
                            class="flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-[#111] hover:bg-[#1a1a1a] border border-[#1e1e1e] hover:border-[#333] text-[#888] hover:text-white text-[10px] font-bold uppercase tracking-widest transition active:scale-[0.98]">
                            <i class="fa-solid fa-layer-group text-[9px] text-emerald-400"></i>
                            <span>Bulk Add</span>
                        </button>
                        <button @click="openAddModal()"
                            class="flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-[10px] font-bold uppercase tracking-widest text-white transition active:scale-[0.98]">
                            <i class="fa-solid fa-plus text-[9px]"></i>
                            <span>Add Code</span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left min-w-[750px]">
                    <thead>
                        <tr class="text-[8px] sm:text-[9px] uppercase tracking-[0.15em] text-[#444] border-b border-[#1e1e1e]">
                            <th class="px-4 sm:px-6 py-3 font-bold w-24">Code</th>
                            <th class="px-4 sm:px-6 py-3 font-bold">Violation Name</th>
                            <th class="px-4 sm:px-6 py-3 font-bold text-right w-28">1st Offense</th>
                            <th class="px-4 sm:px-6 py-3 font-bold text-right w-28">2nd Offense</th>
                            <th class="px-4 sm:px-6 py-3 font-bold text-right w-28">3rd Offense</th>
                            <th class="px-4 sm:px-6 py-3 font-bold text-right w-28">4th+</th>
                            <th class="px-4 sm:px-6 py-3 font-bold text-center w-20">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#1a1a1a]">
                        <template x-for="(item, index) in violations" :key="item.id">
                            <tr class="table-row">
                                <td class="px-4 sm:px-6 py-3">
                                    <span class="text-[9px] sm:text-[10px] font-bold uppercase font-mono px-2 py-0.5 rounded-md bg-blue-500/10 text-blue-400 border border-blue-500/15"
                                          x-text="item.code"></span>
                                </td>
                                <td class="px-4 sm:px-6 py-3">
                                    <span class="text-[10px] sm:text-[11px] font-bold text-[#ccc]" x-text="item.violation_name"></span>
                                </td>
                                <td class="px-4 sm:px-6 py-3 text-right">
                                    <span class="text-[10px] font-bold text-[#555] font-mono" x-text="'₱' + Number(item.first_offense).toLocaleString()"></span>
                                </td>
                                <td class="px-4 sm:px-6 py-3 text-right">
                                    <span class="text-[10px] font-bold text-[#555] font-mono" x-text="'₱' + Number(item.second_offense).toLocaleString()"></span>
                                </td>
                                <td class="px-4 sm:px-6 py-3 text-right">
                                    <span class="text-[10px] font-bold text-[#555] font-mono" x-text="'₱' + Number(item.third_offense).toLocaleString()"></span>
                                </td>
                                <td class="px-4 sm:px-6 py-3 text-right">
                                    <template x-if="item.is_revoked">
                                        <span class="text-[7px] sm:text-[8px] font-bold uppercase px-1.5 py-0.5 rounded-md bg-red-500/10 text-red-400 border border-red-500/15">Revocation</span>
                                    </template>
                                    <template x-if="!item.is_revoked">
                                        <span class="text-[10px] font-bold text-[#888] font-mono" x-text="'₱' + Number(item.fourth_offense).toLocaleString()"></span>
                                    </template>
                                </td>
                                <td class="px-4 sm:px-6 py-3">
                                    <div class="flex items-center gap-1.5 justify-center">
                                        <button @click="openEditModal(item)"
                                            class="w-8 h-8 rounded-lg bg-[#111] border border-[#1e1e1e] hover:bg-[#1a1a1a] hover:border-[#333] flex items-center justify-center transition group"
                                            title="Edit">
                                            <i class="fa-solid fa-pen text-[8px] text-[#444] group-hover:text-blue-400 transition"></i>
                                        </button>
                                        <button @click="openDeleteModal(item)"
                                            class="w-8 h-8 rounded-lg bg-red-500/5 border border-red-500/10 hover:bg-red-500/10 hover:border-red-500/20 flex items-center justify-center transition group"
                                            title="Delete">
                                            <i class="fa-solid fa-trash text-[8px] text-red-500/40 group-hover:text-red-400 transition"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <template x-if="violations.length === 0">
                            <tr>
                                <td colspan="7" class="py-12 sm:py-16">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="w-12 h-12 rounded-xl bg-[#111] border border-[#1e1e1e] flex items-center justify-center mb-3">
                                            <i class="fa-solid fa-list-check text-base text-[#222]"></i>
                                        </div>
                                        <p class="text-[11px] text-[#444] font-medium mb-4">No violation codes yet</p>
                                        <button @click="openAddModal()"
                                            class="flex items-center gap-2 px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-[9px] font-bold uppercase tracking-widest text-white transition">
                                            <i class="fa-solid fa-plus text-[8px]"></i>
                                            <span>Add First Code</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>


        <!-- ══════════ ADD / EDIT MODAL ══════════ -->
        <div x-show="showModal" x-cloak
            class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/80"
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" style="display:none;">

            <div @click.away="closeModal()"
                class="glass-panel p-6 sm:p-8 rounded-[2rem] max-w-lg w-full max-h-[90vh] overflow-y-auto">

                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                             :class="isEditing ? 'bg-amber-500/10 border border-amber-500/15' : 'bg-blue-500/10 border border-blue-500/15'">
                            <i :class="isEditing ? 'fa-solid fa-pen text-amber-400' : 'fa-solid fa-plus text-blue-400'" class="text-[10px]"></i>
                        </div>
                        <div>
                            <h3 class="text-base sm:text-lg font-bold text-white" x-text="isEditing ? 'Edit Violation Code' : 'New Violation Code'"></h3>
                            <p class="text-[10px] text-[#555] mt-0.5" x-text="isEditing ? 'Update the violation details below' : 'Fill in the violation details below'"></p>
                        </div>
                    </div>
                    <button @click="closeModal()"
                        class="w-8 h-8 rounded-lg bg-[#1a1a1a] border border-[#2a2a2a] flex items-center justify-center hover:bg-[#222] transition">
                        <i class="fa-solid fa-xmark text-[10px] text-[#555]"></i>
                    </button>
                </div>

                <form method="POST" id="violationForm" class="space-y-4">
                    @csrf
                    <template x-if="isEditing">
                        @method('PUT')
                    </template>
                    <input type="hidden" name="id" :value="form.id">

                    <div class="grid grid-cols-1 sm:grid-cols-[120px_1fr] gap-4">
                        <div>
                            <label class="block mb-1.5 text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Code</label>
                            <input type="text" name="code" x-model="form.code" maxlength="10"
                                class="w-full px-4 py-3 rounded-xl bg-[#111] border border-[#1e1e1e] text-[11px] text-white font-mono font-bold focus:outline-none focus:border-[#333] transition"
                                placeholder="UV06">
                            @error('code')
                                <p class="mt-1.5 text-[9px] text-red-400 flex items-center gap-1.5">
                                    <i class="fa-solid fa-circle-exclamation text-[7px]"></i> {{ $message }}
                                </p>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-1.5 text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Violation Name</label>
                            <input type="text" name="name" x-model="form.name"
                                class="w-full px-4 py-3 rounded-xl bg-[#111] border border-[#1e1e1e] text-[11px] text-white focus:outline-none focus:border-[#333] transition"
                                placeholder="e.g. Beating the Red Light">
                            @error('name')
                                <p class="mt-1.5 text-[9px] text-red-400 flex items-center gap-1.5">
                                    <i class="fa-solid fa-circle-exclamation text-[7px]"></i> {{ $message }}
                                </p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block mb-2 text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">Fine Amounts (₱)</label>
                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <span class="block text-[8px] font-bold text-[#333] mb-1.5">1st Offense</span>
                                <input type="number" name="first" x-model="form.first" min="0" step="100"
                                    class="w-full px-4 py-3 rounded-xl bg-[#111] border border-[#1e1e1e] text-[11px] text-white font-mono text-right focus:outline-none focus:border-[#333] transition"
                                    placeholder="1,000">
                            </div>
                            <div>
                                <span class="block text-[8px] font-bold text-[#333] mb-1.5">2nd Offense</span>
                                <input type="number" name="second" x-model="form.second" min="0" step="100"
                                    class="w-full px-4 py-3 rounded-xl bg-[#111] border border-[#1e1e1e] text-[11px] text-white font-mono text-right focus:outline-none focus:border-[#333] transition"
                                    placeholder="2,000">
                            </div>
                            <div>
                                <span class="block text-[8px] font-bold text-[#333] mb-1.5">3rd Offense</span>
                                <input type="number" name="third" x-model="form.third" min="0" step="100"
                                    class="w-full px-4 py-3 rounded-xl bg-[#111] border border-[#1e1e1e] text-[11px] text-white font-mono text-right focus:outline-none focus:border-[#333] transition"
                                    placeholder="3,000">
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-[8px] font-bold uppercase tracking-[0.15em] text-[#444]">4th+ Offense Penalty</label>
                            <div class="flex items-center gap-2.5">
                                <span class="text-[9px] font-bold" :class="form.is_revocation ? 'text-red-400' : 'text-[#333]'">License Revocation</span>
                                <div class="relative flex items-center justify-center w-9 h-5 rounded-full cursor-pointer transition"
                                     :class="form.is_revocation ? 'bg-red-600' : 'bg-[#222]'"
                                     @click="form.is_revocation = !form.is_revocation">
                                    <div class="absolute left-0.5 w-4 h-4 rounded-full transition"
                                         :class="form.is_revocation ? 'translate-x-4 bg-white' : 'translate-x-0 bg-[#555]'"></div>
                                </div>
                            </div>
                        </div>
                        <div x-show="!form.is_revocation" x-transition>
                            <input type="number" name="fourth_plus" x-model="form.fourth_plus" min="0" step="100"
                                class="w-full px-4 py-3 rounded-xl bg-[#111] border border-[#1e1e1e] text-[11px] text-white font-mono text-right focus:outline-none focus:border-[#333] transition"
                                placeholder="10,000">
                        </div>
                        <input type="hidden" name="is_revocation" :value="form.is_revocation ? '1' : '0'">
                        <div x-show="form.is_revocation" x-transition
                            class="flex items-center gap-3 p-3.5 rounded-xl bg-red-500/5 border border-red-500/15">
                            <i class="fa-solid fa-triangle-exclamation text-red-400 text-[10px]"></i>
                            <span class="text-[10px] text-red-400/80 font-medium">Driver's license will be revoked on the 4th offense</span>
                        </div>
                    </div>

                    <div class="flex gap-2.5 pt-1">
                        <button type="button" @click="closeModal()"
                            class="flex-1 py-3 rounded-xl bg-[#1a1a1a] border border-[#2a2a2a] text-white text-[10px] font-bold uppercase tracking-widest hover:bg-[#222] transition">
                            Cancel
                        </button>
                        <button type="submit"
                            class="flex-1 py-3 rounded-xl text-white text-[10px] font-bold uppercase tracking-widest transition active:scale-[0.98]"
                            :class="isEditing ? 'bg-amber-600 hover:bg-amber-500 shadow-lg shadow-amber-600/10' : 'bg-blue-600 hover:bg-blue-500 shadow-lg shadow-blue-600/10'">
                            <i :class="isEditing ? 'fa-solid fa-check' : 'fa-solid fa-plus'" class="fa-solid mr-1.5 text-[8px]"></i>
                            <span x-text="isEditing ? 'Update Code' : 'Create Code'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>


        <!-- ══════════ DELETE MODAL ══════════ -->
        <div x-show="showDeleteModal" x-cloak
            class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/80"
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" style="display:none;">

            <div @click.away="closeDeleteModal()"
                class="glass-panel p-6 sm:p-8 rounded-[2rem] max-w-sm w-full">

                <div class="text-center">
                    <div class="w-14 h-14 bg-red-500/10 rounded-2xl flex items-center justify-center mx-auto mb-5 border border-red-500/20">
                        <i class="fa-solid fa-trash text-red-400 text-lg"></i>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-1.5">Delete Code?</h3>
                    <p class="text-xs text-[#555] mb-5">This action is permanent and cannot be undone.</p>

                    <div class="inline-flex items-start gap-3 p-4 rounded-xl bg-[#0a0a0a] border border-[#1e1e1e] mb-7 text-left">
                        <div class="flex flex-col gap-0.5">
                            <span class="text-[7px] font-bold uppercase text-[#333]">Code</span>
                            <span class="text-[11px] font-bold text-blue-400 font-mono" x-text="deleteTarget?.code"></span>
                        </div>
                        <div class="flex flex-col gap-0.5">
                            <span class="text-[7px] font-bold uppercase text-[#333]">Violation</span>
                            <span class="text-[11px] font-bold text-[#ccc]" x-text="deleteTarget?.violation_name"></span>
                        </div>
                    </div>

                    <div class="flex gap-2.5">
                        <button @click="closeDeleteModal()"
                            class="flex-1 py-3 rounded-xl bg-[#1a1a1a] border border-[#2a2a2a] text-white text-[10px] font-bold uppercase tracking-widest hover:bg-[#222] transition">
                            Cancel
                        </button>
                        <form method="POST" id="deleteForm" class="flex-1">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="w-full py-3 rounded-xl bg-red-600 text-white text-[10px] font-bold uppercase tracking-widest hover:bg-red-700 transition active:scale-[0.98] shadow-lg shadow-red-600/10">
                                Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>


        <!-- ══════════ BULK ADD MODAL ══════════ -->
        <div x-show="showBulkModal" x-cloak
            class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/80"
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" style="display:none;">

            <div @click.away="closeBulkModal()"
                class="glass-panel rounded-[2rem] max-w-5xl w-full overflow-hidden flex flex-col"
                style="max-height: 90vh;">

                <div class="p-6 sm:p-8 pb-4 shrink-0">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/15 flex items-center justify-center">
                                <i class="fa-solid fa-layer-group text-[10px] text-emerald-400"></i>
                            </div>
                            <div>
                                <h3 class="text-base sm:text-lg font-bold text-white">Bulk Add Violation Codes</h3>
                                <p class="text-[10px] text-[#555] mt-0.5">Add multiple codes at once. Only rows with Code and Name will be created.</p>
                            </div>
                        </div>
                        <button @click="closeBulkModal()"
                            class="w-8 h-8 rounded-lg bg-[#1a1a1a] border border-[#2a2a2a] flex items-center justify-center hover:bg-[#222] transition">
                            <i class="fa-solid fa-xmark text-[10px] text-[#555]"></i>
                        </button>
                    </div>
                </div>

                <div class="px-6 sm:px-8 pb-2 shrink-0">
                    <div class="grid gap-1.5 items-center text-[7px] font-bold uppercase tracking-[0.15em] text-[#333]"
                         style="grid-template-columns: 28px 90px 1fr 75px 75px 75px 80px 40px 32px;">
                        <span class="text-center">#</span>
                        <span>Code</span>
                        <span>Name</span>
                        <span class="text-right">1st</span>
                        <span class="text-right">2nd</span>
                        <span class="text-right">3rd</span>
                        <span class="text-right">4th+</span>
                        <span class="text-center">Rev</span>
                        <span></span>
                    </div>
                </div>

                <div id="bulkRowsContainer" class="overflow-y-auto flex-1 px-6 sm:px-8 pb-2">
                    <div class="space-y-1">
                        <template x-for="(row, idx) in bulkRows" :key="idx">
                            <div class="grid gap-1.5 items-center rounded-lg px-1.5 py-1.5 bg-[#111] border border-[#1e1e1e] hover:bg-[#151515] transition"
                                 :class="row._error ? '!border-red-500/30 !bg-red-500/5' : ''"
                                 style="grid-template-columns: 28px 90px 1fr 75px 75px 75px 80px 40px 32px;">
                                <span class="text-[#222] text-[10px] font-mono text-center select-none" x-text="idx + 1"></span>

                                <input type="text" x-model="row.code" maxlength="10"
                                    class="w-full px-2.5 py-2 rounded-lg bg-[#0a0a0a] border border-[#1e1e1e] text-[9px] font-mono font-bold text-white focus:outline-none focus:border-[#333] transition"
                                    :class="row._error && !row.code.trim() ? '!border-red-500/50 !bg-red-500/5' : ''"
                                    placeholder="UV06" @input="row._error = false">

                                <input type="text" x-model="row.name"
                                    class="w-full px-2.5 py-2 rounded-lg bg-[#0a0a0a] border border-[#1e1e1e] text-[9px] font-semibold text-white focus:outline-none focus:border-[#333] transition"
                                    :class="row._error && !row.name.trim() ? '!border-red-500/50 !bg-red-500/5' : ''"
                                    placeholder="Violation name" @input="row._error = false">

                                <input type="number" x-model="row.first" min="0" step="100"
                                    class="w-full px-2.5 py-2 rounded-lg bg-[#0a0a0a] border border-[#1e1e1e] text-[9px] font-mono text-right text-[#888] focus:outline-none focus:border-[#333] transition"
                                    placeholder="0">

                                <input type="number" x-model="row.second" min="0" step="100"
                                    class="w-full px-2.5 py-2 rounded-lg bg-[#0a0a0a] border border-[#1e1e1e] text-[9px] font-mono text-right text-[#888] focus:outline-none focus:border-[#333] transition"
                                    placeholder="0">

                                <input type="number" x-model="row.third" min="0" step="100"
                                    class="w-full px-2.5 py-2 rounded-lg bg-[#0a0a0a] border border-[#1e1e1e] text-[9px] font-mono text-right text-[#888] focus:outline-none focus:border-[#333] transition"
                                    placeholder="0">

                                <div class="relative">
                                    <input type="number" x-show="!row.is_revocation" x-model="row.fourth_plus" min="0" step="100" x-transition
                                        class="w-full px-2.5 py-2 rounded-lg bg-[#0a0a0a] border border-[#1e1e1e] text-[9px] font-mono text-right text-[#888] focus:outline-none focus:border-[#333] transition"
                                        placeholder="0">
                                    <div x-show="row.is_revocation" x-transition
                                        class="flex items-center justify-center h-[30px] rounded-lg border border-red-500/15 bg-red-500/5">
                                        <span class="text-[8px] font-bold uppercase tracking-wider text-red-400">Rev</span>
                                    </div>
                                </div>

                                <div class="flex justify-center">
                                    <button type="button" @click="row.is_revocation = !row.is_revocation"
                                        class="text-[8px] font-black px-2 py-1 rounded-md transition-all tracking-wider select-none"
                                        :class="row.is_revocation ? 'bg-red-500/15 text-red-400 border border-red-500/25' : 'bg-[#1a1a1a] text-[#333] border border-[#222] hover:border-[#333] hover:text-[#555]'">
                                        REV
                                    </button>
                                </div>

                                <div class="flex justify-center">
                                    <button type="button" @click="removeBulkRow(idx)"
                                        :class="bulkRows.length <= 1 ? 'opacity-20 pointer-events-none' : ''"
                                        class="w-6 h-6 rounded-md flex items-center justify-center text-[#333] hover:text-red-400 hover:bg-red-500/10 transition-all">
                                        <i class="fa-solid fa-xmark text-[8px]"></i>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="flex-shrink-0 px-6 sm:px-8 py-4 border-t border-[#1e1e1e]">
                    <div x-show="bulkSubmitting" x-transition class="h-0.5 bg-[#1e1e1e] mb-4 rounded-full overflow-hidden">
                        <div class="h-full bg-emerald-500 transition-all duration-300"
                             :style="'width: ' + (bulkTotalCount > 0 ? (bulkSubmittedCount / bulkTotalCount * 100) : 0) + '%'"></div>
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <button type="button" @click="addBulkRow()" :disabled="bulkSubmitting"
                                class="flex items-center gap-1.5 px-3 py-2 rounded-lg bg-emerald-500/10 hover:bg-emerald-500/20 border border-emerald-500/15 text-emerald-400 text-[9px] font-bold uppercase tracking-wider transition disabled:opacity-30 disabled:pointer-events-none">
                                <i class="fa-solid fa-plus text-[7px]"></i> Add
                            </button>
                            <button type="button" @click="clearBulkRows()" :disabled="bulkSubmitting"
                                class="text-[9px] font-bold text-[#333] hover:text-[#555] transition disabled:opacity-30 disabled:pointer-events-none">
                                Clear
                            </button>
                            <div class="h-4 w-px bg-[#1e1e1e]"></div>
                            <span class="text-[8px] text-[#333]">
                                <span x-text="bulkFilledCount" class="font-bold" :class="bulkFilledCount > 0 ? 'text-emerald-400/60' : ''"></span>
                                <span> / </span>
                                <span x-text="bulkRows.length" class="font-bold text-[#222]"></span> ready
                            </span>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <button type="button" @click="closeBulkModal()" :disabled="bulkSubmitting"
                                class="px-5 py-2.5 rounded-xl bg-[#1a1a1a] border border-[#2a2a2a] text-white text-[10px] font-bold uppercase tracking-widest hover:bg-[#222] transition disabled:opacity-30 disabled:pointer-events-none">
                                Cancel
                            </button>
                            <button type="button" @click="submitBulk()"
                                :disabled="bulkSubmitting || bulkFilledCount === 0"
                                class="px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-[10px] font-bold uppercase tracking-widest transition active:scale-[0.98] shadow-lg shadow-emerald-600/10 disabled:opacity-30 disabled:pointer-events-none">
                                <template x-if="!bulkSubmitting">
                                    <span class="inline-flex items-center gap-2"><i class="fa-solid fa-layer-group text-[8px]"></i> Create <span x-text="bulkFilledCount"></span></span>
                                </template>
                                <template x-if="bulkSubmitting">
                                    <span class="inline-flex items-center gap-2"><i class="fa-solid fa-spinner fa-spin text-[9px]"></i> <span x-text="bulkSubmittedCount + ' / ' + bulkTotalCount"></span></span>
                                </template>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </main>

    <script>
        function violationManager() {
            return {
                violations: @json($violation_codes ?? []),

                showModal: false,
                showDeleteModal: false,
                showBulkModal: false,
                isEditing: false,

                form: {
                    id: null,
                    code: '',
                    name: '',
                    first: '',
                    second: '',
                    third: '',
                    fourth_plus: '',
                    is_revocation: false
                },

                deleteTarget: null,

                bulkRows: [],
                bulkSubmitting: false,
                bulkSubmittedCount: 0,
                bulkTotalCount: 0,

                get bulkFilledCount() {
                    return this.bulkRows.filter(r => r.code.trim() && r.name.trim()).length;
                },

                _newBulkRow() {
                    return { code: '', name: '', first: '0', second: '0', third: '0', fourth_plus: '0', is_revocation: false, _error: false };
                },

                openAddModal() {
                    this.isEditing = false;
                    this.form = { id: null, code: '', name: '', first: '0', second: '0', third: '0', fourth_plus: '0', is_revocation: false };
                    document.getElementById('violationForm').action = '{{ route('violation-codes.store') }}';
                    this.showModal = true;
                    document.body.style.overflow = 'hidden';
                },

                openEditModal(item) {
                    this.isEditing = true;
                    this.form = {
                        id: item.id,
                        code: item.code,
                        name: item.violation_name,
                        first: item.first_offense,
                        second: item.second_offense,
                        third: item.third_offense,
                        fourth_plus: item.fourth_offense || '',
                        is_revocation: !!item.is_revoked
                    };
                    document.getElementById('violationForm').action = '{{ route('violation-codes.update', ':id') }}'.replace(':id', item.id);
                    this.showModal = true;
                    document.body.style.overflow = 'hidden';
                },

                closeModal() {
                    this.showModal = false;
                    document.body.style.overflow = '';
                },

                openDeleteModal(item) {
                    this.deleteTarget = { ...item };
                    document.getElementById('deleteForm').action = '{{ route('violation-codes.destroy', ':id') }}'.replace(':id', item.id);
                    this.showDeleteModal = true;
                    document.body.style.overflow = 'hidden';
                },

                closeDeleteModal() {
                    this.showDeleteModal = false;
                    document.body.style.overflow = '';
                },

                openBulkModal() {
                    this.bulkRows = Array.from({ length: 3 }, () => this._newBulkRow());
                    this.bulkSubmitting = false;
                    this.bulkSubmittedCount = 0;
                    this.bulkTotalCount = 0;
                    this.showBulkModal = true;
                    document.body.style = 'overflow: hidden';
                },

                closeBulkModal() {
                    if (this.bulkSubmitting) return;
                    this.showBulkModal = false;
                    document.body.style.overflow = '';
                },

                addBulkRow() {
                    this.bulkRows.push(this._newBulkRow());
                    this.$nextTick(() => {
                        const c = document.getElementById('bulkRowsContainer');
                        if (c) c.scrollTo({ top: c.scrollHeight, behavior: 'smooth' });
                    });
                },

                removeBulkRow(index) {
                    if (this.bulkRows.length > 1) this.bulkRows.splice(index, 1);
                },

                clearBulkRows() {
                    this.bulkRows = [this._newBulkRow()];
                },

                async submitBulk() {
                    this.bulkRows.forEach(r => { r._error = !r.code.trim() || !r.name.trim(); });
                    const validRows = this.bulkRows.filter(r => r.code.trim() && r.name.trim());
                    if (validRows.length === 0) return;

                    this.bulkSubmitting = true;
                    this.bulkSubmittedCount = 0;
                    this.bulkTotalCount = validRows.length;

                    const token = document.querySelector('meta[name="csrf-token"]')?.content;
                    if (!token) { this.bulkSubmitting = false; return; }

                    for (const row of validRows) {
                        const fd = new FormData();
                        fd.append('_token', token);
                        fd.append('code', row.code.trim());
                        fd.append('name', row.name.trim());
                        fd.append('first', row.first || '0');
                        fd.append('second', row.second || '0');
                        fd.append('third', row.third || '0');
                        fd.append('fourth_plus', row.is_revocation ? '0' : (row.fourth_plus || '0'));
                        fd.append('is_revocation', row.is_revocation ? '1' : '0');

                        try {
                            const res = await fetch('{{ route('violation-codes.store') }}', { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                            if (!res.ok) { const t = await res.text().catch(() => ''); throw new Error(t || 'Server error'); }
                            this.bulkSubmittedCount++;
                        } catch (e) {
                            this.bulkSubmitting = false;
                            alert('Failed at "' + row.code + ' — ' + row.name + '". Stopped at ' + this.bulkSubmittedCount + ' of ' + this.bulkTotalCount + '.');
                            return;
                        }
                    }

                    window.location.reload();
                }
            };
        }
    </script>

</body>
</html>
