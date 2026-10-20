{{-- Filter bar shared by the admin ledgers (fare transactions, wallet top-ups).

     Expects: $action (route url), $statuses (array), $methods (iterable),
     $placeholder (search hint), $accent ('blue' | 'emerald'). --}}
@php
    $accent ??= 'blue';
    $fieldClass =
        'w-full px-3.5 py-2.5 bg-gray-50 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] rounded-xl text-[11px] text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-[#333] focus:outline-none focus:border-gray-300 dark:focus:border-[#333] transition';
    $labelClass = 'block mb-1 text-[7px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]';
    $activeKeys = ['search', 'status', 'method', 'from_date', 'to_date', 'min_amount', 'max_amount'];
    $activeCount = collect($activeKeys)->filter(fn($key) => request()->filled($key))->count();
@endphp

<div class="glass-card rounded-[1.25rem] p-4 sm:p-5 mb-5">
    <form action="{{ $action }}" method="GET" class="space-y-3">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="sm:col-span-2">
                <label class="{{ $labelClass }}" for="f-search">Search</label>
                <div class="relative">
                    <i
                        class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-[10px] text-gray-400 dark:text-[#333]"></i>
                    <input id="f-search" type="text" name="search" value="{{ request('search') }}"
                        placeholder="{{ $placeholder }}" class="{{ $fieldClass }} pl-10">
                </div>
            </div>
            <div>
                <label class="{{ $labelClass }}" for="f-status">Payment status</label>
                <select id="f-status" name="status" class="{{ $fieldClass }}">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="{{ $labelClass }}" for="f-method">Payment method</label>
                <select id="f-method" name="method" class="{{ $fieldClass }}">
                    <option value="">All methods</option>
                    @foreach ($methods as $method)
                        <option value="{{ $method }}" @selected(request('method') === $method)>{{ $method }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="{{ $labelClass }}" for="f-from">From date</label>
                <input id="f-from" type="date" name="from_date" value="{{ request('from_date') }}"
                    class="{{ $fieldClass }}">
            </div>
            <div>
                <label class="{{ $labelClass }}" for="f-to">To date</label>
                <input id="f-to" type="date" name="to_date" value="{{ request('to_date') }}"
                    class="{{ $fieldClass }}">
            </div>
            <div>
                <label class="{{ $labelClass }}" for="f-min">Min amount (₱)</label>
                <input id="f-min" type="number" step="0.01" min="0" name="min_amount"
                    value="{{ request('min_amount') }}" placeholder="0.00" class="{{ $fieldClass }}">
            </div>
            <div>
                <label class="{{ $labelClass }}" for="f-max">Max amount (₱)</label>
                <input id="f-max" type="number" step="0.01" min="0" name="max_amount"
                    value="{{ request('max_amount') }}" placeholder="No limit" class="{{ $fieldClass }}">
            </div>
        </div>

        @if ($errors->any())
            <p class="text-[10px] font-semibold text-red-500 dark:text-red-400">{{ $errors->first() }}</p>
        @endif

        <div class="flex items-center justify-end gap-2.5">
            @if ($activeCount)
                <a href="{{ $action }}"
                    class="flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-gray-50 dark:bg-[#111] border border-gray-200 dark:border-[#1e1e1e] hover:border-gray-300 dark:hover:border-[#333] text-[9px] font-bold uppercase tracking-widest text-gray-500 dark:text-[#555] hover:text-gray-700 dark:hover:text-[#888] transition">
                    <i class="fa-solid fa-xmark text-[8px]"></i>
                    <span>Clear ({{ $activeCount }})</span>
                </a>
            @endif
            <button type="submit"
                class="flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-{{ $accent }}-600 hover:bg-{{ $accent }}-500 text-[9px] font-bold uppercase tracking-widest text-white transition active:scale-[0.98]">
                <i class="fa-solid fa-filter text-[8px]"></i>
                <span>Apply filters</span>
            </button>
        </div>
    </form>
</div>
