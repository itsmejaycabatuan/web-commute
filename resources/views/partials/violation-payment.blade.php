{{-- Payment state of one violation's fine, with the Driver Manager's mark-paid action.
     Expects $v (the mapped violation: paymentStatus, paymentUrl, paidAt). --}}
@php
    $isPaid = ($v['paymentStatus'] ?? 'unpaid') === 'paid';
@endphp
<div class="flex gap-2 {{ ($stack ?? false) ? 'flex-col items-end' : 'items-center flex-wrap '.($align ?? 'justify-end') }}">
    <span
        class="inline-flex items-center gap-1 text-[7px] sm:text-[8px] px-2 py-0.5 rounded-md font-bold uppercase border {{ $isPaid ? 'bg-emerald-500/10 text-emerald-500 dark:text-emerald-400 border-emerald-500/15' : 'bg-red-500/10 text-red-500 dark:text-red-400 border-red-500/15' }}"
        @if ($isPaid && ! empty($v['paidAt'])) title="Paid {{ $v['paidAt'] }}" @endif>
        <i class="fa-solid {{ $isPaid ? 'fa-circle-check' : 'fa-clock' }} text-[6px]"></i>
        {{ $isPaid ? 'Paid' : 'Unpaid' }}
    </span>

    @if (! empty($v['paymentUrl']))
        <form method="POST" action="{{ $v['paymentUrl'] }}" data-ajax class="m-0">
            @csrf
            @method('PATCH')
            <input type="hidden" name="payment_status" value="{{ $isPaid ? 'unpaid' : 'paid' }}">
            <button type="submit"
                class="px-2.5 py-1 rounded-lg border text-[7px] sm:text-[8px] font-bold uppercase tracking-widest transition active:scale-[0.97] whitespace-nowrap {{ $isPaid ? 'bg-gray-100 dark:bg-[#111] border-gray-200 dark:border-[#1e1e1e] text-gray-500 dark:text-[#555] hover:text-gray-900 dark:hover:text-[#ccc] hover:border-gray-300 dark:hover:border-[#333]' : 'bg-emerald-600 border-emerald-600 text-white hover:bg-emerald-500 hover:border-emerald-500' }}">
                {{ $isPaid ? 'Mark Unpaid' : 'Mark Paid' }}
            </button>
        </form>
    @endif
</div>
