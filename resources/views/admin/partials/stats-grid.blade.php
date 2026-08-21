<div class="grid grid-cols-2 sm:grid-cols-2 xl:grid-cols-3 gap-3 sm:gap-4 mb-5 sm:mb-6">

    <!-- Total Users -->
    <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-gray-300 dark:border-l-white/20">
        <div class="flex items-center gap-2 mb-2 sm:mb-3">
            <div class="w-6 h-6 rounded-md bg-gray-200 dark:bg-white/5 flex items-center justify-center">
                <i class="fa-solid fa-users text-[8px] text-gray-500 dark:text-[#888]"></i>
            </div>
            <span
                class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Total
                Users</span>
        </div>
        <div class="flex items-baseline gap-1 sm:gap-1.5">
            <span
                class="text-2xl sm:text-3xl font-black tracking-tight text-gray-900 dark:text-white">{{ number_format($stats['total_users']) }}</span>
        </div>
        <p class="text-[7px] sm:text-[8px] text-gray-400 dark:text-[#333] mt-1.5 font-medium">All accounts in the system
        </p>
    </div>

    <!-- Total Commuters -->
    <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-cyan-500">
        <div class="flex items-center gap-2 mb-2 sm:mb-3">
            <div class="w-6 h-6 rounded-md bg-cyan-500/10 flex items-center justify-center">
                <i class="fa-solid fa-user-group text-[8px] text-cyan-500"></i>
            </div>
            <span
                class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Commuters</span>
        </div>
        <div class="flex items-baseline gap-1 sm:gap-1.5">
            <span
                class="text-2xl sm:text-3xl font-black tracking-tight text-cyan-600 dark:text-cyan-400">{{ number_format($stats['total_commuters']) }}</span>
        </div>
        <p class="text-[7px] sm:text-[8px] text-gray-400 dark:text-[#333] mt-1.5 font-medium">PUJ passenger accounts</p>
    </div>

    <!-- Total Drivers -->
    <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-blue-500">
        <div class="flex items-center gap-2 mb-2 sm:mb-3">
            <div class="w-6 h-6 rounded-md bg-blue-500/10 flex items-center justify-center">
                <i class="fa-solid fa-id-badge text-[8px] text-blue-500"></i>
            </div>
            <span
                class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Drivers</span>
        </div>
        <div class="flex items-baseline gap-1 sm:gap-1.5">
            <span
                class="text-2xl sm:text-3xl font-black tracking-tight text-blue-600 dark:text-blue-400">{{ number_format($stats['total_drivers']) }}</span>
        </div>
        <p class="text-[7px] sm:text-[8px] text-gray-400 dark:text-[#333] mt-1.5 font-medium">PUJ operator accounts</p>
    </div>

    <!-- Approved Drivers -->
    <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-emerald-500">
        <div class="flex items-center gap-2 mb-2 sm:mb-3">
            <div class="w-6 h-6 rounded-md bg-emerald-500/10 flex items-center justify-center">
                <i class="fa-solid fa-circle-check text-[8px] text-emerald-500"></i>
            </div>
            <span
                class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Approved</span>
        </div>
        <div class="flex items-baseline gap-1 sm:gap-1.5">
            <span
                class="text-2xl sm:text-3xl font-black tracking-tight text-emerald-600 dark:text-emerald-400">{{ number_format($stats['approved_drivers']) }}</span>
            <span class="text-xs sm:text-sm font-bold text-gray-400 dark:text-[#333]">/
                {{ number_format($stats['total_drivers']) }}</span>
        </div>
        <p class="text-[7px] sm:text-[8px] text-gray-400 dark:text-[#333] mt-1.5 font-medium">Can sign in</p>
    </div>

    <!-- Rejected Drivers -->
    <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-red-500">
        <div class="flex items-center gap-2 mb-2 sm:mb-3">
            <div class="w-6 h-6 rounded-md bg-red-500/10 flex items-center justify-center">
                <i class="fa-solid fa-circle-xmark text-[8px] text-red-500"></i>
            </div>
            <span
                class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Rejected</span>
        </div>
        <div class="flex items-baseline gap-1 sm:gap-1.5">
            <span
                class="text-2xl sm:text-3xl font-black tracking-tight text-red-500/80 dark:text-red-400/80">{{ number_format($stats['rejected_drivers']) }}</span>
        </div>
        <p class="text-[7px] sm:text-[8px] text-gray-400 dark:text-[#333] mt-1.5 font-medium">Denied registrations</p>
    </div>

    <!-- Driver Applications -->
    <div class="glass-card p-4 sm:p-5 rounded-[1.25rem] border-l-2 border-l-purple-500">
        <div class="flex items-center gap-2 mb-2 sm:mb-3">
            <div class="w-6 h-6 rounded-md bg-purple-500/10 flex items-center justify-center">
                <i class="fa-solid fa-file-lines text-[8px] text-purple-500"></i>
            </div>
            <span
                class="text-[7px] sm:text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">Applications</span>
        </div>
        <div class="flex items-baseline gap-1 sm:gap-1.5">
            <span
                class="text-2xl sm:text-3xl font-black tracking-tight text-gray-900 dark:text-white">{{ number_format($stats['total_applications']) }}</span>
        </div>
        <p class="text-[7px] sm:text-[8px] text-gray-400 dark:text-[#333] mt-1.5 font-medium">All-time signup records
        </p>
    </div>

</div>
