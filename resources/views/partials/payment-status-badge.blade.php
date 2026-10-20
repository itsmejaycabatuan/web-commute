{{-- Pill for a payment / top-up lifecycle status. Expects $status. --}}
@php
    $tone = match (strtolower((string) $status)) {
        'paid', 'completed' => 'emerald',
        'pending' => 'amber',
        'failed' => 'red',
        default => 'gray',
    };
@endphp
<span
    class="inline-block text-[7px] sm:text-[8px] bg-{{ $tone }}-500/10 text-{{ $tone }}-600 dark:text-{{ $tone }}-400 border border-{{ $tone }}-500/15 px-2 py-0.5 rounded-md font-bold uppercase">{{ $status ?: 'unknown' }}</span>
