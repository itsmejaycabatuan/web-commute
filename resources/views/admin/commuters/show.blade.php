<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SmartCommute | Commuter Details</title>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    @include('partials.head-scripts')

    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="antialiased text-gray-900 dark:text-white" x-data>
    <x-layout.sidebar />

    <main class="pt-8 pr-4 sm:pr-8 pb-8 pl-4 sm:pl-8 md:pl-[110px] min-h-screen">
        <div class="max-w-4xl mx-auto">

            <a href="{{ route('commuters.index') }}"
                class="inline-flex items-center gap-2 mb-6 text-[10px] font-bold uppercase tracking-widest text-gray-500 dark:text-[#555] hover:text-gray-900 dark:hover:text-white transition">
                <i class="fa-solid fa-arrow-left text-[9px]"></i>
                Back to Commuters
            </a>

            <div class="flex flex-col sm:flex-row sm:items-center gap-4 mb-8">
                <div
                    class="w-14 h-14 rounded-2xl bg-blue-600 flex items-center justify-center shrink-0 shadow-lg shadow-blue-600/20">
                    <span class="text-lg font-black text-white">
                        {{ strtoupper(substr(explode('@', $user->email)[0], 0, 1)) }}
                    </span>
                </div>
                <div class="min-w-0">
                    <h1 class="text-xl sm:text-2xl font-black tracking-tight text-gray-900 dark:text-white truncate">
                        {{ $user->email }}
                    </h1>
                    <div class="flex items-center gap-2 mt-1.5 flex-wrap">
                        @if ($user->isSuspended())
                            <span
                                class="text-[8px] font-bold uppercase tracking-widest bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/15 px-2 py-0.5 rounded-md">
                                Suspended
                            </span>
                        @else
                            <span
                                class="text-[8px] font-bold uppercase tracking-widest bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/15 px-2 py-0.5 rounded-md">
                                Active
                            </span>
                        @endif
                        <span
                            class="text-[8px] font-bold uppercase tracking-widest bg-gray-100 dark:bg-[#111] text-gray-500 dark:text-[#555] border border-gray-200 dark:border-[#1e1e1e] px-2 py-0.5 rounded-md">
                            {{ $user->hasVerifiedEmail() ? 'Email verified' : 'Email pending' }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                <div class="glass-card p-5 rounded-[1.25rem]">
                    <p class="text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">
                        Wallet Balance</p>
                    <p class="mt-2 text-2xl font-black font-mono text-emerald-500 dark:text-emerald-400">
                        ₱{{ number_format($balance, 2) }}
                    </p>
                </div>
                <div class="glass-card p-5 rounded-[1.25rem]">
                    <p class="text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">
                        Fares Paid</p>
                    <p class="mt-2 text-2xl font-black font-mono text-gray-900 dark:text-white">
                        {{ (int) $user->fares_paid }}
                    </p>
                </div>
                <div class="glass-card p-5 rounded-[1.25rem]">
                    <p class="text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">
                        Total Spent</p>
                    <p class="mt-2 text-2xl font-black font-mono text-gray-900 dark:text-white">
                        ₱{{ number_format($totalFareSpent, 2) }}
                    </p>
                </div>
            </div>

            <div class="glass-card rounded-[1.25rem] sm:rounded-[1.5rem] overflow-hidden">
                <div class="p-5 border-b border-gray-200 dark:border-[#1e1e1e]">
                    <h2 class="text-sm font-bold text-gray-900 dark:text-white">Account Details</h2>
                    <p class="text-[10px] text-gray-500 dark:text-[#555] mt-0.5">Read-only record</p>
                </div>
                <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-5">
                    <div>
                        <p class="text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">
                            Email</p>
                        <p class="text-[11px] font-bold text-gray-700 dark:text-[#ccc] mt-1 break-all">
                            {{ $user->email }}</p>
                    </div>
                    <div>
                        <p class="text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">
                            Registered</p>
                        <p class="text-[11px] font-bold text-gray-700 dark:text-[#ccc] mt-1">
                            {{ $user->created_at->format('M j, Y g:i A') }}</p>
                    </div>
                    <div>
                        <p class="text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">
                            Email Verified At</p>
                        <p class="text-[11px] font-bold text-gray-700 dark:text-[#ccc] mt-1">
                            {{ $user->email_verified_at?->format('M j, Y g:i A') ?? 'Not verified' }}</p>
                    </div>
                    <div>
                        <p class="text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">
                            Suspended At</p>
                        <p class="text-[11px] font-bold text-gray-700 dark:text-[#ccc] mt-1">
                            {{ $user->suspended_at?->format('M j, Y g:i A') ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">
                            Last Fare</p>
                        <p class="text-[11px] font-bold text-gray-700 dark:text-[#ccc] mt-1">
                            {{ $lastFare?->paid_at?->format('M j, Y g:i A') ?? 'No fares yet' }}
                            @if ($lastFare)
                                <span class="text-gray-400 dark:text-[#444] font-mono">
                                    · {{ $lastFare->transaction_id }}
                                </span>
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-[8px] font-bold uppercase tracking-[0.15em] text-gray-400 dark:text-[#444]">
                            Top-ups</p>
                        <p class="text-[11px] font-bold text-gray-700 dark:text-[#ccc] mt-1">
                            {{ $user->topupHistories()->count() }}</p>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>

</html>