<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SmartCommute | Dashboard</title>
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

        .modal-overlay {
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(8px);
        }

        .modal-panel {
            background: #0a0a0a;
            border: 1px solid rgba(255, 255, 255, 0.06);
        }

        .input-field {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            color: #fff;
            transition: all 0.2s ease;
        }

        .input-field:focus {
            outline: none;
            border-color: rgba(59, 130, 246, 0.5);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            background: rgba(255, 255, 255, 0.06);
        }

        .input-field::placeholder {
            color: rgba(255, 255, 255, 0.2);
        }

        .input-field.input-error {
            border-color: rgba(239, 68, 68, 0.5) !important;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.08) !important;
        }

        .btn-primary {
            background: #2563eb;
            box-shadow: 0 4px 20px rgba(37, 99, 235, 0.3);
            transition: all 0.2s ease;
        }

        .btn-primary:hover {
            background: #3b82f6;
            box-shadow: 0 6px 30px rgba(37, 99, 235, 0.4);
            transform: translateY(-1px);
        }

        .btn-danger {
            background: #dc2626;
            box-shadow: 0 4px 20px rgba(220, 38, 38, 0.3);
            transition: all 0.2s ease;
        }

        .btn-danger:hover {
            background: #ef4444;
            box-shadow: 0 6px 30px rgba(220, 38, 38, 0.4);
            transform: translateY(-1px);
        }

        .btn-ghost {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.08);
            transition: all 0.2s ease;
        }

        .btn-ghost:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.15);
        }

        .action-btn {
            opacity: 0;
            transition: all 0.2s ease;
        }

        tr:hover .action-btn {
            opacity: 1;
        }

        /* Toast */
        .toast-enter {
            animation: toastIn 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        .toast-leave {
            animation: toastOut 0.3s ease forwards;
        }

        @keyframes toastIn {
            from { opacity: 0; transform: translateY(20px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        @keyframes toastOut {
            from { opacity: 1; transform: translateY(0) scale(1); }
            to { opacity: 0; transform: translateY(-10px) scale(0.95); }
        }

        /* Modal animation */
        .modal-enter {
            animation: modalIn 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        .modal-leave {
            animation: modalOut 0.25s ease forwards;
        }

        @keyframes modalIn {
            from { opacity: 0; transform: scale(0.95) translateY(10px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        @keyframes modalOut {
            from { opacity: 1; transform: scale(1) translateY(0); }
            to { opacity: 0; transform: scale(0.95) translateY(10px); }
        }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.2); }

        /* Toggle switch */
        .toggle-track {
            width: 44px;
            height: 24px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.1);
            transition: background 0.2s ease;
            cursor: pointer;
            position: relative;
        }

        .toggle-track.active {
            background: #dc2626;
        }

        .toggle-knob {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: white;
            position: absolute;
            top: 3px;
            left: 3px;
            transition: transform 0.2s ease;
        }

        .toggle-track.active .toggle-knob {
            transform: translateX(20px);
        }

        /* Bulk row error shake */
        .row-error {
            animation: rowShake 0.4s ease;
        }

        @keyframes rowShake {
            0%, 100% { transform: translateX(0); }
            20% { transform: translateX(-4px); }
            40% { transform: translateX(4px); }
            60% { transform: translateX(-3px); }
            80% { transform: translateX(3px); }
        }

        /* Progress bar */
        .progress-fill {
            transition: width 0.3s ease;
        }

        /* Bulk row hover */
        .bulk-row {
            transition: background 0.15s ease;
        }
        .bulk-row:hover {
            background: rgba(255, 255, 255, 0.02);
        }
    </style>
</head>

<body x-data="violationManager()">

    @include('driver-manager.layout.sidebar');

    @include('components.flash');

    <main :class="open ? 'ml-72' : 'ml-20'" x-data="{ editing: false }"
        class="sidebar-transition p-8 md:p-12 min-h-screen">

                <div class="max-w-[1400px] mx-auto">

            <!-- Header -->
            <header class="mb-8 flex flex-col md:flex-row md:items-end justify-between gap-6">
                <div>
                    <h2 class="text-3xl font-black tracking-tight mb-2">Violation <span class="text-blue-500">Codes</span></h2>
                    <p class="text-white/40 text-sm">Reference guide for traffic violation fines and penalties.</p>
                </div>
                <div class="flex items-center gap-3">
                    <button @click="openBulkModal()"
                        class="px-5 py-2.5 rounded-xl btn-ghost text-white/50 text-sm font-semibold inline-flex items-center gap-2 hover:text-emerald-400 hover:border-emerald-500/25 hover:bg-emerald-500/5">
                        <i class="fa-solid fa-layer-group text-xs"></i> Bulk Add
                    </button>
                    <button @click="openAddModal()"
                        class="px-5 py-2.5 rounded-xl btn-primary text-white text-sm font-semibold inline-flex items-center gap-2">
                        <i class="fa-solid fa-plus text-xs"></i> Add Code
                    </button>
                </div>
            </header>

            <!-- Table -->
            <div class="glass rounded-[2rem] overflow-hidden border border-white/5">
                <div class="overflow-x-auto">
                    <table class="w-full text-left whitespace-nowrap">
                        <thead>
                            <tr class="border-b border-white/5 bg-white/[0.03]">
                                <th class="px-6 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-blue-500 sticky left-0 z-10" style="background: #080808;">Code</th>
                                <th class="px-6 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-blue-500">Violation Name</th>
                                <th class="px-6 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-blue-500 text-right w-32">1st Offense</th>
                                <th class="px-6 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-blue-500 text-right w-32">2nd Offense</th>
                                <th class="px-6 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-blue-500 text-right w-32">3rd Offense</th>
                                <th class="px-6 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-red-500 text-right w-32">4th +</th>
                                <th class="px-6 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-white/20 text-center w-24">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <template x-for="(item, index) in violations" :key="item.id">
                                <tr class="hover:bg-white/[0.02] transition-colors group">
                                    <td class="px-6 py-4 sticky left-0 z-10 transition-colors" style="background: #050505;"
                                        :style="`background: #050505;`">
                                        <span class="inline-block px-2.5 py-1 rounded-md bg-blue-500/10 text-blue-400 border border-blue-500/20 font-mono text-sm font-bold"
                                            x-text="item.code"></span>
                                    </td>
                                    <td class="px-6 py-4 font-semibold text-white" x-text="item.violation_name"></td>
                                    <td class="px-6 py-4 text-right font-mono text-white/60" x-text="'₱ ' + Number(item.first_offense).toLocaleString()"></td>
                                    <td class="px-6 py-4 text-right font-mono text-white/60" x-text="'₱ ' + Number(item.second_offense).toLocaleString()"></td>
                                    <td class="px-6 py-4 text-right font-mono text-white/60" x-text="'₱ ' + Number(item.third_offense).toLocaleString()"></td>
                                    <td class="px-6 py-4 text-right">
                                        <template x-if="item.is_revoked">
                                            <span class="inline-block font-mono text-red-400 font-bold text-xs px-2.5 py-1 rounded-md border border-red-500/20 bg-red-500/5">
                                                Revocation
                                            </span>
                                        </template>
                                        <template x-if="!item.is_revoked">
                                            <span class="font-mono text-white/80 font-bold" x-text="'₱ ' + Number(item.fourth_offense).toLocaleString()"></span>
                                        </template>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex items-center justify-center gap-1">
                                            <button @click="openEditModal(item)"
                                                class="action-btn w-8 h-8 rounded-lg flex items-center justify-center text-white/40 hover:text-blue-400 hover:bg-blue-500/10 transition-all"
                                                title="Edit">
                                                <i class="fa-solid fa-pen-to-square text-xs"></i>
                                            </button>
                                            <button @click="openDeleteModal(item)"
                                                class="action-btn w-8 h-8 rounded-lg flex items-center justify-center text-white/40 hover:text-red-400 hover:bg-red-500/10 transition-all"
                                                title="Delete">
                                                <i class="fa-solid fa-trash-can text-xs"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>

                            <!-- Empty State -->
                            <template x-if="violations.length === 0">
                                <tr>
                                    <td colspan="7" class="px-6 py-20 text-center">
                                        <div class="flex flex-col items-center gap-4">
                                            <div class="w-16 h-16 rounded-2xl bg-white/[0.03] border border-white/5 flex items-center justify-center">
                                                <i class="fa-solid fa-list-check text-2xl text-white/10"></i>
                                            </div>
                                            <div>
                                                <p class="text-white/30 font-semibold mb-1">No violation codes yet</p>
                                                <p class="text-white/15 text-sm">Click "Add Code" to create your first entry.</p>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Footer count -->
            <div class="mt-4 flex items-center justify-between px-2">
                <p class="text-white/20 text-xs font-medium">
                    Showing <span x-text="violations.length" class="text-white/40"></span> violation codes
                </p>
            </div>
        </div>
    </main>

<!-- ==================== ADD / EDIT MODAL ==================== -->
    <div x-show="showModal" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4 modal-overlay"
        @click.self="closeModal()"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0">

        <div class="modal-panel rounded-2xl w-full max-w-lg shadow-2xl shadow-black/50"
            :class="modalAnimating ? (modalClosing ? 'modal-leave' : 'modal-enter') : ''">

            <!-- Modal Header -->
            <div class="flex items-center justify-between px-7 py-5 border-b border-white/5">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center"
                        :class="isEditing ? 'bg-amber-500/10' : 'bg-blue-500/10'">
                        <i :class="isEditing ? 'fa-solid fa-pen-to-square text-amber-400' : 'fa-solid fa-plus text-blue-400'"
                            class="text-sm"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-white text-base" x-text="isEditing ? 'Edit Violation Code' : 'New Violation Code'"></h3>
                        <p class="text-white/30 text-xs" x-text="isEditing ? 'Update the violation details below.' : 'Fill in the violation details below.'"></p>
                    </div>
                </div>
                <button @click="closeModal()"
                    class="w-8 h-8 rounded-lg flex items-center justify-center text-white/30 hover:text-white/70 hover:bg-white/5 transition-all">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Modal Body — Form (DIRECT SUBMISSION) -->
                <form action="{{ route('violation-codes.store') }}" method="POST" id="violationForm">
                    @csrf

                <!-- For EDIT mode: Add PUT method override -->
                    <template x-if="isEditing">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <!-- Hidden field for edit mode -->
                    <input type="hidden" name="id" x-model="form.id">

                    <div class="px-7 py-6 space-y-5">

                        <!-- Row: Code + Name -->
                        <div class="grid grid-cols-[120px_1fr] gap-4">
                            <div>
                                <label class="block text-[10px] font-bold uppercase tracking-[0.15em] text-white/30 mb-2">Code</label>
                                <input type="text"
                                    name="code"
                                    x-model="form.code"
                                    maxlength="10"
                                    class="input-field w-full px-3.5 py-2.5 rounded-xl text-sm font-mono font-bold"
                                    placeholder="UV06"
                                    >
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold uppercase tracking-[0.15em] text-white/30 mb-2">Violation Name</label>
                                <input type="text"
                                    name="name"
                                    x-model="form.name"
                                    class="input-field w-full px-3.5 py-2.5 rounded-xl text-sm font-semibold"
                                    placeholder="e.g. Beating the Red Light"
                                    >
                            </div>
                        </div>

                        <!-- Fines -->
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-[0.15em] text-white/30 mb-3">Fine Amounts (₱)</label>
                            <div class="grid grid-cols-3 gap-3">
                                <div>
                                    <span class="block text-[10px] font-semibold text-white/20 mb-1.5">1st Offense</span>
                                    <input type="number"
                                        name="first"
                                        x-model="form.first"
                                        min="0"
                                        step="100"
                                        class="input-field w-full px-3.5 py-2.5 rounded-xl text-sm font-mono text-right"
                                        placeholder="1,000"
                                        >
                                </div>
                                <div>
                                    <span class="block text-[10px] font-semibold text-white/20 mb-1.5">2nd Offense</span>
                                    <input type="number"
                                        name="second"
                                        x-model="form.second"
                                        min="0"
                                        step="100"
                                        class="input-field w-full px-3.5 py-2.5 rounded-xl text-sm font-mono text-right"
                                        placeholder="2,000"
                                        >
                                </div>
                                <div>
                                    <span class="block text-[10px] font-semibold text-white/20 mb-1.5">3rd Offense</span>
                                    <input type="number"
                                        name="third"
                                        x-model="form.third"
                                        min="0"
                                        step="100"
                                        class="input-field w-full px-3.3 py-2.5 rounded-xl text-sm font-mono text-right"
                                        placeholder="3,000"
                                        >
                                </div>
                            </div>
                        </div>

                        <!-- 4th+ Offense -->
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <label class="block text-[10px] font-bold uppercase tracking-[0.15em] text-white/30">4th+ Offense Penalty</label>
                                <div class="flex items-center gap-2.5">
                                    <span class="text-[11px] font-semibold" :class="form.is_revocation ? 'text-red-400' : 'text-white/20'">License Revocation</span>
                                    <div class="toggle-track" :class="{ 'active': form.is_revocation }" @click="form.is_revocation = !form.is_revocation">
                                        <div class="toggle-knob"></div>
                                    </div>
                                </div>
                            </div>
                            <div x-show="!form.is_revocation" x-transition>
                                <input type="number"
                                    name="fourth_plus"
                                    x-model="form.fourth_plus"
                                    min="0"
                                    step="100"
                                    class="input-field w-full px-3.5 py-2.5 rounded-xl text-sm font-mono text-right"
                                    placeholder="10,000">
                            </div>

                            <!-- Hidden field to track revocation status -->
                            <input type="hidden" name="is_revocation" :value="form.is_revocation ? '1' : '0'">

                            <div x-show="form.is_revocation" x-transition
                                class="flex items-center gap-3 px-4 py-3 rounded-xl border border-red-500/15 bg-red-500/5">
                                <i class="fa-solid fa-triangle-exclamation text-red-400 text-sm"></i>
                                <span class="text-red-400/80 text-sm font-medium">Driver's license will be revoked on the 4th offense.</span>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="flex items-center justify-end gap-3 px-7 py-5 border-t border-white/5 bg-white/[0.01] rounded-b-2xl">
                        <button type="button"
                                @click="closeModal()"
                                class="btn-ghost px-5 py-2.5 rounded-xl text-sm font-semibold text-white/60 hover:text-white/90">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-6 py-2.5 rounded-xl text-sm font-bold text-white inline-flex items-center gap-2 transition-all shadow-lg"
                            :class="isEditing
                                ? 'bg-amber-600 hover:bg-amber-500 shadow-amber-900/30'
                                : 'btn-primary'">

                        <!-- Dynamic Icon -->
                        <i :class="isEditing ? 'fa-solid fa-check' : 'fa-solid fa-plus'" class="text-xs"></i>

                        <!-- Dynamic Text -->
                        <span x-text="isEditing ? 'Update Code' : 'Create Code'"></span>
                    </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<!-- ==================== DELETE CONFIRMATION MODAL ==================== -->
<div x-show="showDeleteModal" x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4 modal-overlay"
    @click.self="closeDeleteModal()"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0">

    <div class="modal-panel rounded-2xl w-full max-w-md shadow-2xl shadow-black/50"
        :class="deleteModalAnimating ? (deleteModalClosing ? 'modal-leave' : 'modal-enter') : ''">

        <!-- Modal Header -->
        <div class="flex items-center justify-between px-7 py-5 border-b border-white/5">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center bg-red-500/10">
                    <i class="fa-solid fa-trash-can text-red-400 text-sm"></i>
                </div>
                <div>
                    <h3 class="font-bold text-white text-base">Delete Violation Code</h3>
                    <p class="text-white/30 text-xs">This action cannot be undone.</p>
                </div>
            </div>
            <button @click="closeDeleteModal()"
                class="w-8 h-8 rounded-lg flex items-center justify-center text-white/30 hover:text-white/70 hover:bg-white/5 transition-all">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Modal Body -->
        <div class="px-7 py-6">
            <div class="flex items-start gap-4 p-4 rounded-xl border border-red-500/15 bg-red-500/5">
                <div class="w-10 h-10 rounded-full bg-red-500/10 flex items-center justify-center flex-shrink-0 mt-0.5">
                    <i class="fa-solid fa-triangle-exclamation text-red-400"></i>
                </div>
                <div>
                    <p class="text-white/80 text-sm font-medium mb-1">
                        Are you sure you want to delete this violation code?
                    </p>
                    <div class="mt-3 px-3 py-2 rounded-lg bg-black/30 border border-white/5">
                        <p class="text-white/40 text-xs uppercase tracking-wider mb-1">Code</p>
                        <p class="font-mono font-bold text-blue-400" x-text="deleteTarget?.code"></p>
                        <p class="text-white/40 text-xs uppercase tracking-wider mb-1 mt-2">Violation Name</p>
                        <p class="font-semibold text-white" x-text="deleteTarget?.violation_name"></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="flex items-center justify-end gap-3 px-7 py-5 border-t border-white/5 bg-white/[0.01] rounded-b-2xl">
            <button type="button"
                @click="closeDeleteModal()"
                class="btn-ghost px-5 py-2.5 rounded-xl text-sm font-semibold text-white/60 hover:text-white/90">
                Cancel
            </button>
            <form action="{{ route('violation-codes.destroy', ':id') }}"
                method="POST"
                id="deleteForm"
                style="display: inline;">
                @csrf
                @method('DELETE')
                <button type="submit"
                    class="px-6 py-2.5 rounded-xl text-sm font-bold text-white inline-flex items-center gap-2 transition-all shadow-lg bg-red-600 hover:bg-red-500 shadow-red-900/30">
                    <i class="fa-solid fa-trash-can text-xs"></i>
                    Delete Permanently
                </button>
            </form>
        </div>
    </div>
</div>

<!-- ==================== BULK ADD MODAL ==================== -->
<div x-show="showBulkModal" x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4 modal-overlay"
    @click.self="closeBulkModal()"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0">

    <div class="modal-panel rounded-2xl w-full max-w-5xl shadow-2xl shadow-black/50 flex flex-col"
        :class="bulkModalAnimating ? (bulkModalClosing ? 'modal-leave' : 'modal-enter') : ''"
        style="max-height: 90vh;">

        <!-- Modal Header -->
        <div class="flex items-center justify-between px-7 py-5 border-b border-white/5 flex-shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center bg-emerald-500/10">
                    <i class="fa-solid fa-layer-group text-emerald-400 text-sm"></i>
                </div>
                <div>
                    <h3 class="font-bold text-white text-base">Bulk Add Violation Codes</h3>
                    <p class="text-white/30 text-xs">Add multiple violation codes at once. Only rows with both Code and Name will be created.</p>
                </div>
            </div>
            <button @click="closeBulkModal()"
                class="w-8 h-8 rounded-lg flex items-center justify-center text-white/30 hover:text-white/70 hover:bg-white/5 transition-all">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Column Headers -->
        <div class="px-7 pt-5 pb-2 flex-shrink-0">
            <div class="grid gap-1.5 items-center text-[9px] font-black uppercase tracking-[0.2em] text-white/20"
                 style="grid-template-columns: 28px 100px 1fr 85px 85px 85px 90px 44px 32px;">
                <span class="text-center">#</span>
                <span>Code</span>
                <span>Violation Name</span>
                <span class="text-right">1st</span>
                <span class="text-right">2nd</span>
                <span class="text-right">3rd</span>
                <span class="text-right">4th+</span>
                <span class="text-center">Rev</span>
                <span></span>
            </div>
        </div>

        <!-- Scrollable Rows Area -->
        <div id="bulkRowsContainer" class="overflow-y-auto flex-1 px-7 pb-2">
            <div class="space-y-1">
                <template x-for="(row, idx) in bulkRows" :key="idx">
                    <div class="bulk-row grid gap-1.5 items-center rounded-lg px-1.5 py-1.5"
                         :class="row._error ? 'bg-red-500/5 row-error' : ''"
                         style="grid-template-columns: 28px 100px 1fr 85px 85px 85px 90px 44px 32px;">

                        <!-- Row number -->
                        <span class="text-white/15 text-[11px] font-mono text-center select-none" x-text="idx + 1"></span>

                        <!-- Code -->
                        <input type="text"
                            x-model="row.code"
                            maxlength="10"
                            class="input-field w-full px-2.5 py-2 rounded-lg text-xs font-mono font-bold"
                            :class="row._error && !row.code.trim() ? 'input-error' : ''"
                            placeholder="UV06"
                            @input="row._error = false">

                        <!-- Name -->
                        <input type="text"
                            x-model="row.name"
                            class="input-field w-full px-2.5 py-2 rounded-lg text-xs font-semibold"
                            :class="row._error && !row.name.trim() ? 'input-error' : ''"
                            placeholder="e.g. Beating the Red Light"
                            @input="row._error = false">

                        <!-- 1st Offense -->
                        <input type="number"
                            x-model="row.first"
                            min="0"
                            step="100"
                            class="input-field w-full px-2.5 py-2 rounded-lg text-xs font-mono text-right"
                            placeholder="0">

                        <!-- 2nd Offense -->
                        <input type="number"
                            x-model="row.second"
                            min="0"
                            step="100"
                            class="input-field w-full px-2.5 py-2 rounded-lg text-xs font-mono text-right"
                            placeholder="0">

                        <!-- 3rd Offense -->
                        <input type="number"
                            x-model="row.third"
                            min="0"
                            step="100"
                            class="input-field w-full px-2.5 py-2 rounded-lg text-xs font-mono text-right"
                            placeholder="0">

                        <!-- 4th+ Offense -->
                        <div class="relative">
                            <input type="number"
                                x-show="!row.is_revocation"
                                x-model="row.fourth_plus"
                                min="0"
                                step="100"
                                class="input-field w-full px-2.5 py-2 rounded-lg text-xs font-mono text-right"
                                placeholder="0"
                                x-transition>
                            <div x-show="row.is_revocation"
                                class="flex items-center justify-center h-[34px] rounded-lg border border-red-500/15 bg-red-500/5"
                                x-transition>
                                <span class="text-red-400 text-[9px] font-bold uppercase tracking-wider">Revoked</span>
                            </div>
                        </div>

                        <!-- Revocation Toggle Badge -->
                        <div class="flex justify-center">
                            <button type="button"
                                @click="row.is_revocation = !row.is_revocation"
                                class="text-[9px] font-black px-2 py-1 rounded-md transition-all tracking-wider select-none"
                                :class="row.is_revocation
                                    ? 'bg-red-500/15 text-red-400 border border-red-500/30'
                                    : 'bg-white/[0.03] text-white/15 border border-white/[0.06] hover:border-white/15 hover:text-white/30'">
                                REV
                            </button>
                        </div>

                        <!-- Delete Row -->
                        <div class="flex justify-center">
                            <button type="button"
                                @click="removeBulkRow(idx)"
                                :class="bulkRows.length <= 1 ? 'opacity-20 pointer-events-none' : 'opacity-0 group-hover:opacity-100'"
                                class="w-7 h-7 rounded-md flex items-center justify-center text-white/30 hover:text-red-400 hover:bg-red-500/10 transition-all"
                                :title="bulkRows.length <= 1 ? 'Cannot remove last row' : 'Remove row'">
                                <i class="fa-solid fa-xmark text-[10px]"></i>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Bulk Modal Footer -->
        <div class="flex-shrink-0 border-t border-white/5 bg-white/[0.01] rounded-b-2xl">
            <!-- Progress bar (visible during submission) -->
            <div x-show="bulkSubmitting" x-transition class="h-1 bg-white/5">
                <div class="h-full bg-emerald-500 progress-fill"
                     :style="'width: ' + (bulkTotalCount > 0 ? (bulkSubmittedCount / bulkTotalCount * 100) : 0) + '%'"></div>
            </div>

            <div class="flex items-center justify-between px-7 py-4">
                <!-- Left side: row actions + count -->
                <div class="flex items-center gap-4">
                    <button type="button"
                        @click="addBulkRow()"
                        :disabled="bulkSubmitting"
                        class="px-3.5 py-2 rounded-lg text-xs font-semibold text-emerald-400/70 hover:text-emerald-400 hover:bg-emerald-500/10 border border-emerald-500/15 hover:border-emerald-500/30 transition-all inline-flex items-center gap-1.5 disabled:opacity-30 disabled:pointer-events-none">
                        <i class="fa-solid fa-plus text-[9px]"></i> Add Row
                    </button>
                    <button type="button"
                        @click="clearBulkRows()"
                        :disabled="bulkSubmitting"
                        class="px-3 py-2 rounded-lg text-xs font-semibold text-white/20 hover:text-white/40 hover:bg-white/5 transition-all disabled:opacity-30 disabled:pointer-events-none">
                        Clear
                    </button>
                    <div class="h-4 w-px bg-white/10"></div>
                    <p class="text-white/20 text-xs">
                        <span x-text="bulkFilledCount" class="font-bold" :class="bulkFilledCount > 0 ? 'text-emerald-400/70' : ''"></span>
                        <span> of </span>
                        <span x-text="bulkRows.length" class="font-bold text-white/40"></span>
                        <span> rows ready</span>
                    </p>
                </div>

                <!-- Right side: cancel + submit -->
                <div class="flex items-center gap-3">
                    <button type="button"
                        @click="closeBulkModal()"
                        :disabled="bulkSubmitting"
                        class="btn-ghost px-5 py-2.5 rounded-xl text-sm font-semibold text-white/60 hover:text-white/90 disabled:opacity-30 disabled:pointer-events-none">
                        Cancel
                    </button>
                    <button type="button"
                        @click="submitBulk()"
                        :disabled="bulkSubmitting || bulkFilledCount === 0"
                        class="px-6 py-2.5 rounded-xl text-sm font-bold text-white inline-flex items-center gap-2 transition-all shadow-lg bg-emerald-600 hover:bg-emerald-500 shadow-emerald-900/30 disabled:opacity-30 disabled:pointer-events-none disabled:transform-none">

                        <!-- Submitting state -->
                        <template x-if="bulkSubmitting">
                            <span class="inline-flex items-center gap-2">
                                <i class="fa-solid fa-spinner fa-spin text-xs"></i>
                                <span x-text="'Adding ' + bulkSubmittedCount + ' of ' + bulkTotalCount + '...'"></span>
                            </span>
                        </template>

                        <!-- Idle state -->
                        <template x-if="!bulkSubmitting">
                            <span class="inline-flex items-center gap-2">
                                <i class="fa-solid fa-layer-group text-xs"></i>
                                <span x-text="'Create ' + bulkFilledCount + ' Code' + (bulkFilledCount !== 1 ? 's' : '')"></span>
                            </span>
                        </template>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

    <script>
        function violationManager() {
            return {
                open: true,

                violations: @json($violation_codes ?? []),

                // Single Add/Edit Modal
                showModal: false,
                showDeleteModal: false,
                isEditing: false,
                modalAnimating: false,
                modalClosing: false,
                deleteModalAnimating: false,
                deleteModalClosing: false,

                // Bulk Add Modal
                showBulkModal: false,
                bulkModalAnimating: false,
                bulkModalClosing: false,
                bulkRows: [],
                bulkSubmitting: false,
                bulkSubmittedCount: 0,
                bulkTotalCount: 0,

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

                // ── Computed: how many bulk rows are fill-ready ──
                get bulkFilledCount() {
                    return this.bulkRows.filter(r => r.code.trim() && r.name.trim()).length;
                },

                // ── Single Add/Edit ──
                openAddModal() {
                    this.isEditing = false;
                    this.form = {
                        id: null,
                        code: '',
                        name: '',
                        first: '0',
                        second: '0',
                        third: '0',
                        fourth_plus: '0',
                        is_revocation: false
                    };
                    const form = document.getElementById('violationForm');
                    form.action = '{{ route('violation-codes.store') }}';
                    this._openModal('showModal', 'modalAnimating', 'modalClosing');
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
                    const form = document.getElementById('violationForm');
                    form.action = '{{ route("violation-codes.update", ":id") }}'.replace(':id', item.id);
                    this._openModal('showModal', 'modalAnimating', 'modalClosing');
                },

                closeModal() {
                    this._closeModal('showModal', 'modalAnimating', 'modalClosing');
                },

                // ── Delete ──
                openDeleteModal(item) {
                    this.deleteTarget = { ...item };
                    const deleteForm = document.getElementById('deleteForm');
                    if (deleteForm) {
                        deleteForm.action = '{{ route("violation-codes.destroy", ":id") }}'.replace(':id', item.id);
                    }
                    this._openModal('showDeleteModal', 'deleteModalAnimating', 'deleteModalClosing');
                },

                closeDeleteModal() {
                    this._closeModal('showDeleteModal', 'deleteModalAnimating', 'deleteModalClosing');
                },

                // ── Bulk Add ──
                _newBulkRow() {
                    return {
                        code: '',
                        name: '',
                        first: '0',
                        second: '0',
                        third: '0',
                        fourth_plus: '0',
                        is_revocation: false,
                        _error: false
                    };
                },

                openBulkModal() {
                    this.bulkRows = Array.from({ length: 3 }, () => this._newBulkRow());
                    this.bulkSubmitting = false;
                    this.bulkSubmittedCount = 0;
                    this.bulkTotalCount = 0;
                    this._openModal('showBulkModal', 'bulkModalAnimating', 'bulkModalClosing');
                },

                closeBulkModal() {
                    if (this.bulkSubmitting) return;
                    this._closeModal('showBulkModal', 'bulkModalAnimating', 'bulkModalClosing');
                },

                addBulkRow() {
                    this.bulkRows.push(this._newBulkRow());
                    this.$nextTick(() => {
                        const container = document.getElementById('bulkRowsContainer');
                        if (container) {
                            container.scrollTo({ top: container.scrollHeight, behavior: 'smooth' });
                        }
                    });
                },

                removeBulkRow(index) {
                    if (this.bulkRows.length > 1) {
                        this.bulkRows.splice(index, 1);
                    }
                },

                clearBulkRows() {
                    this.bulkRows = [this._newBulkRow()];
                },

                async submitBulk() {
                    // Validate: mark rows missing code or name
                    let hasError = false;
                    this.bulkRows.forEach(row => {
                        const isEmpty = !row.code.trim() || !row.name.trim();
                        row._error = isEmpty;
                        if (isEmpty && (row.code.trim() || row.name.trim())) {
                            // Partially filled row — that's an error
                            hasError = true;
                        }
                    });

                    // Only submit fully filled rows; skip completely empty ones
                    const validRows = this.bulkRows.filter(r => r.code.trim() && r.name.trim());
                    if (validRows.length === 0) return;

                    if (hasError) {
                        // Shake the error rows but still submit the valid ones
                        // (or you could block — here we proceed with valid rows)
                    }

                    this.bulkSubmitting = true;
                    this.bulkSubmittedCount = 0;
                    this.bulkTotalCount = validRows.length;

                    const token = document.querySelector('meta[name="csrf-token"]')?.content;
                    if (!token) {
                        this.bulkSubmitting = false;
                        this._showToast('CSRF token not found. Please refresh the page.', 'error');
                        return;
                    }

                    for (const row of validRows) {
                        const formData = new FormData();
                        formData.append('_token', token);
                        formData.append('code', row.code.trim());
                        formData.append('name', row.name.trim());
                        formData.append('first', row.first || '0');
                        formData.append('second', row.second || '0');
                        formData.append('third', row.third || '0');
                        formData.append('fourth_plus', row.is_revocation ? '0' : (row.fourth_plus || '0'));
                        formData.append('is_revocation', row.is_revocation ? '1' : '0');

                        try {
                            const response = await fetch('{{ route("violation-codes.store") }}', {
                                method: 'POST',
                                body: formData,
                                headers: { 'X-Requested-With': 'XMLHttpRequest' }
                            });

                            if (!response.ok) {
                                const text = await response.text().catch(() => '');
                                throw new Error(text || 'Server error');
                            }

                            this.bulkSubmittedCount++;
                        } catch (e) {
                            this.bulkSubmitting = false;
                            this._showToast('Failed to add "' + row.code + ' — ' + row.name + '". Stopped at ' + this.bulkSubmittedCount + ' of ' + this.bulkTotalCount + '.', 'error');
                            return;
                        }
                    }

                    // All succeeded — reload to see new data
                    window.location.reload();
                },

                // ── Toast (standalone, doesn't depend on flash component) ──
                _showToast(message, type = 'success') {
                    const toast = document.createElement('div');
                    toast.className = 'fixed bottom-6 right-6 z-[100] px-5 py-3.5 rounded-xl text-sm font-semibold shadow-2xl toast-enter flex items-center gap-3 max-w-md ' + (
                        type === 'error'
                            ? 'bg-red-600/95 text-white border border-red-500/30'
                            : 'bg-emerald-600/95 text-white border border-emerald-500/30'
                    );
                    toast.innerHTML = '<i class="fa-solid ' + (type === 'error' ? 'fa-circle-exclamation' : 'fa-circle-check') + ' text-white/80"></i><span>' + message + '</span>';
                    document.body.appendChild(toast);

                    setTimeout(() => {
                        toast.classList.remove('toast-enter');
                        toast.classList.add('toast-leave');
                        setTimeout(() => toast.remove(), 300);
                    }, 4000);
                },

                // ── Modal Animation Helpers ──
                _openModal(showProp, animProp, closeProp) {
                    this[closeProp] = false;
                    this[showProp] = true;
                    this.$nextTick(() => { this[animProp] = true; });
                    document.body.style.overflow = 'hidden';
                },

                _closeModal(showProp, animProp, closeProp) {
                    this[closeProp] = true;
                    setTimeout(() => {
                        this[showProp] = false;
                        this[animProp] = false;
                        this[closeProp] = false;
                        document.body.style.overflow = '';
                    }, 250);
                },

            };
        }
    </script>
</body>

</html>
