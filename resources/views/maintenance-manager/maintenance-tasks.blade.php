<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

        .modal-backdrop {
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(8px);
        }

        .modal-enter {
            animation: modalIn 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        .modal-leave {
            animation: modalOut 0.2s ease-in forwards;
        }

        @keyframes modalIn {
            from { opacity: 0; transform: scale(0.95) translateY(10px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        @keyframes modalOut {
            from { opacity: 1; transform: scale(1) translateY(0); }
            to { opacity: 0; transform: scale(0.95) translateY(10px); }
        }

        input[type="number"]::-webkit-inner-spin-button,
        input[type="number"]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        input[type="number"] {
            -moz-appearance: textfield;
        }

        .input-field {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            transition: all 0.2s ease;
        }

        .input-field:focus {
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(59, 130, 246, 0.5);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            outline: none;
        }

        [x-cloak] { display: none !important; }
    </style>
</head>

<body x-data="{ open: true, showModal: false, animating: false, editMode: false, editId: null,
    openModal() {
        this.editMode = false;
        this.editId = null;
        this.showModal = true;
        this.animating = true;
        setTimeout(() => this.animating = false, 300);
    },
    openEditModal(id, name, miles, months) {
        this.editMode = true;
        this.editId = id;
        document.getElementById('field_task').value = name;
        document.getElementById('field_miles').value = miles || '';
        document.getElementById('field_months').value = months || '';
        this.showModal = true;
        this.animating = true;
        setTimeout(() => this.animating = false, 300);
    },
    closeModal() {
        this.animating = true;
        setTimeout(() => {
            this.showModal = false;
            this.animating = false;
            document.getElementById('field_task').value = '';
            document.getElementById('field_miles').value = '';
            document.getElementById('field_months').value = '';
        }, 200);
    }
}" @keydown.escape="if(showModal) closeModal()">

    @include('components.flash');

    @include('maintenance-manager.layout.sidebar');

    <main :class="open ? 'ml-72' : 'ml-20'" x-data="{ editing: false }"
        class="sidebar-transition p-8 md:p-12 min-h-screen">

            <div class="max-w-4xl mx-auto">

            <header class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
                <div>
                    <h2 class="text-3xl font-black tracking-tight mb-1">Maintenance <span class="text-blue-500">Tasks</span></h2>
                    <p class="text-white/40 text-sm">Standard service intervals and frequency reference.</p>
                </div>
                <button @click="openModal()" class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-sm font-semibold shadow-lg shadow-blue-900/40 transition-all self-start">
                    <i class="fa-solid fa-plus mr-2"></i> Add Task
                </button>
            </header>

            <div class="glass rounded-[2rem] border border-white/5 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left whitespace-nowrap">
                        <thead>
                            <tr class="border-b border-white/5 bg-white/[0.02]">
                                <th class="px-8 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-blue-500">Task Performed</th>
                                <th class="px-8 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-white/40 text-right">Miles Between Service</th>
                                <th class="px-8 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-white/40 text-right">Months Between Service</th>
                                <th class="px-6 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-white/40 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @forelse ($tasks as $task)
                                <tr class="hover:bg-white/[0.02] transition-colors group">
                                    <td class="px-8 py-4 text-sm text-white font-semibold">{{ $task->tasks_performed }}</td>
                                    <td class="px-8 py-4 text-sm font-mono text-right {{ $task->miles_between_service ? 'text-white/60' : 'text-white/20' }}">
                                        {{ $task->miles_between_service ? number_format($task->miles_between_service) : '—' }}
                                    </td>
                                    <td class="px-8 py-4 text-sm font-mono text-right {{ $task->months_between_service ? 'text-white/60' : 'text-white/20' }}">
                                        {{ $task->months_between_service ?? '—' }}
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-1 opacity-40 group-hover:opacity-100 transition-opacity">
                                            <button type="button"
                                                @click="openEditModal({{ $task->id }}, '{{ $task->tasks_performed }}', {{ $task->miles_between_service ?? 'null' }}, {{ $task->months_between_service ?? 'null' }})"
                                                class="w-8 h-8 rounded-lg flex items-center justify-center text-white/60 hover:text-blue-400 hover:bg-blue-500/10 transition-all"
                                                title="Edit">
                                                <i class="fa-solid fa-pen-to-square text-xs"></i>
                                            </button>
                                            <form method="POST" action="{{ route('maintenance-manager.maintenance-tasks.destroy', $task->id) }}" class="inline" onsubmit="return confirm('Delete this task?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="w-8 h-8 rounded-lg flex items-center justify-center text-white/60 hover:text-red-400 hover:bg-red-500/10 transition-all"
                                                    title="Delete">
                                                    <i class="fa-solid fa-trash-can text-xs"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-8 py-16 text-center text-white/20 text-sm">
                                        <i class="fa-solid fa-inbox text-2xl mb-3 block"></i>
                                        No tasks yet. Click "Add Task" to create one.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <template x-teleport="body">
        <div x-show="showModal" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4 modal-backdrop"
            @click.self="closeModal()"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0">

            <div :class="!animating ? 'modal-enter' : 'modal-leave'"
                class="w-full max-w-lg rounded-[1.5rem] border border-white/10 bg-[#111111] shadow-2xl shadow-black/60">

                <!-- Header -->
                <div class="flex items-center justify-between px-8 py-6 border-b border-white/5">
                    <div>
                        <h3 class="text-lg font-black tracking-tight" x-text="editMode ? 'Edit Maintenance Task' : 'New Maintenance Task'"></h3>
                        <p class="text-white/30 text-xs mt-0.5">Define service interval for this task.</p>
                    </div>
                    <button @click="closeModal()"
                        class="w-8 h-8 rounded-lg flex items-center justify-center text-white/30 hover:text-white hover:bg-white/10 transition-all">
                        <i class="fa-solid fa-xmark text-sm"></i>
                    </button>
                </div>

                <!-- Form -->
                <form x-bind:action="editMode ? '{{ route('maintenance-manager.maintenance-tasks.update', '') }}/' + editId : '{{ route('maintenance-manager.maintenance-tasks.store') }}'"
                    x-bind:method="editMode ? 'POST' : 'POST'"
                    class="p-8 space-y-5">
                    @csrf
                    @if(false) @method('PUT') @endif

                    <input type="hidden" name="_method" x-bind:value="editMode ? 'PUT' : ''">

                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-white/40 mb-2">Task Performed</label>
                        <input type="text" id="field_task"
                            class="input-field w-full px-4 py-3 rounded-xl text-sm text-white placeholder-white/20"
                            placeholder="e.g. Transmission Fluid Change"
                            name="tasks_performed">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-white/40 mb-2">Miles</label>
                            <div class="relative">
                                <input type="number" id="field_miles" min="0"
                                    class="input-field w-full px-4 py-3 rounded-xl text-sm text-white placeholder-white/20 pr-12"
                                    placeholder="5,000"
                                    name="miles_between_service">
                                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-white/20 text-xs font-semibold">mi</span>
                            </div>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-white/40 mb-2">Months</label>
                            <div class="relative">
                                <input type="number" id="field_months" min="0"
                                    class="input-field w-full px-4 py-3 rounded-xl text-sm text-white placeholder-white/20 pr-14"
                                    placeholder="6"
                                    name="months_between_service">
                                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-white/20 text-xs font-semibold">mos</span>
                            </div>
                        </div>
                    </div>

                    <p class="text-white/20 text-xs leading-relaxed">
                        <i class="fa-solid fa-circle-info mr-1 text-blue-500/60"></i>
                        At least one of <strong class="text-white/40">Miles</strong> or <strong class="text-white/40">Months</strong> must be provided. Leave a field empty if not applicable.
                    </p>

                    <!-- Footer -->
                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button type="button" @click="closeModal()"
                            class="px-5 py-2.5 rounded-xl text-white/50 hover:text-white hover:bg-white/5 text-sm font-semibold transition-all">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 active:scale-[0.97] text-white text-sm font-semibold shadow-lg shadow-blue-900/40 transition-all">
                            <i class="fa-solid fa-check mr-2"></i>
                            <span x-text="editMode ? 'Update Task' : 'Add Task'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</body>

</html>
