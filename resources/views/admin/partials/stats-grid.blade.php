{{-- Admin dashboard: full stat cards --}}
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 mb-10">
    <div class="glass rounded-2xl border border-white/10 p-6 flex items-start gap-4">
        <span class="text-2xl shrink-0" aria-hidden="true">👤</span>
        <div>
            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1">Total users</p>
            <p class="text-3xl font-black tracking-tight text-white">{{ number_format($stats['total_users']) }}</p>
            <p class="text-[10px] text-gray-600 mt-1">All accounts in the system</p>
        </div>
    </div>
    <div class="glass rounded-2xl border border-cyan-500/20 p-6 flex items-start gap-4">
        <span class="text-2xl shrink-0" aria-hidden="true">🧑‍🤝‍🧑</span>
        <div>
            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1">Total commuters</p>
            <p class="text-3xl font-black tracking-tight text-cyan-400">{{ number_format($stats['total_commuters']) }}</p>
            <p class="text-[10px] text-gray-600 mt-1">PUJ passenger accounts</p>
        </div>
    </div>
    <div class="glass rounded-2xl border border-white/10 p-6 flex items-start gap-4">
        <span class="text-2xl shrink-0" aria-hidden="true">🚗</span>
        <div>
            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1">Total drivers</p>
            <p class="text-3xl font-black tracking-tight text-blue-400">{{ number_format($stats['total_drivers']) }}</p>
            <p class="text-[10px] text-gray-600 mt-1">PUJ operator accounts</p>
        </div>
    </div>
    <div class="glass rounded-2xl border border-amber-500/30 ring-1 ring-amber-500/20 p-6 flex items-start gap-4 bg-amber-500/[0.03]">
        <span class="text-2xl shrink-0" aria-hidden="true">⏳</span>
        <div>
            <p class="text-[10px] font-bold uppercase tracking-widest text-amber-400/90 mb-1">Pending drivers</p>
            <p class="text-3xl font-black tracking-tight text-amber-400">{{ number_format($stats['pending_drivers']) }}</p>
            <p class="text-[10px] text-amber-400/50 mt-1 font-semibold">Needs your review</p>
        </div>
    </div>
    <div class="glass rounded-2xl border border-emerald-500/20 p-6 flex items-start gap-4">
        <span class="text-2xl shrink-0" aria-hidden="true">✅</span>
        <div>
            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1">Approved drivers</p>
            <p class="text-3xl font-black tracking-tight text-emerald-400">{{ number_format($stats['approved_drivers']) }}</p>
            <p class="text-[10px] text-gray-600 mt-1">Can sign in</p>
        </div>
    </div>
    <div class="glass rounded-2xl border border-red-500/20 p-6 flex items-start gap-4">
        <span class="text-2xl shrink-0" aria-hidden="true">❌</span>
        <div>
            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1">Rejected drivers</p>
            <p class="text-3xl font-black tracking-tight text-red-400/90">{{ number_format($stats['rejected_drivers']) }}</p>
            <p class="text-[10px] text-gray-600 mt-1">Denied registrations</p>
        </div>
    </div>
    <div class="glass rounded-2xl border border-white/10 p-6 flex items-start gap-4">
        <span class="text-2xl shrink-0" aria-hidden="true">📄</span>
        <div>
            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1">Driver applications</p>
            <p class="text-3xl font-black tracking-tight text-white">{{ number_format($stats['total_applications']) }}</p>
            <p class="text-[10px] text-gray-600 mt-1">All-time signup records</p>
        </div>
    </div>
</div>
