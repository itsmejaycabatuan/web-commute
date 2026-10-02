{{-- Status badge for a payment that is not settled yet. --}}
@php
    $styles = [
        'pending' => ['bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20', 'Pending', 'fa-clock'],
        'failed' => ['bg-red-500/10 text-red-600 dark:text-red-400 border-red-500/20', 'Failed', 'fa-circle-xmark'],
        'cancelled' => ['bg-slate-500/10 text-slate-500 dark:text-[#888] border-slate-500/20', 'Cancelled', 'fa-ban'],
    ][$payment->status] ?? [
        'bg-slate-500/10 text-slate-500 dark:text-[#888] border-slate-500/20',
        ucfirst((string) $payment->status),
        'fa-circle-question',
    ];
@endphp
<span
    class="inline-flex items-center gap-1 text-[9px] font-bold uppercase tracking-wider px-2 py-1 rounded-lg border {{ $styles[0] }} whitespace-nowrap">
    <i class="fa-solid {{ $styles[2] }} text-[8px]"></i> {{ $styles[1] }}
</span>