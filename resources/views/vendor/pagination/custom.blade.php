@if ($paginator->hasPages())
    <nav class="flex justify-center space-x-1.5 sm:space-x-2">
        {{-- Previous Page Link --}}
        @if ($paginator->onFirstPage())
            <span
                class="w-8 h-8 rounded-lg border border-slate-200 dark:border-white/5 flex items-center justify-center text-xs text-slate-300 dark:text-white opacity-30 cursor-not-allowed">
                <i class="fa-solid fa-chevron-left"></i>
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}"
                class="w-8 h-8 rounded-lg border border-slate-200 dark:border-white/10 flex items-center justify-center text-slate-400 dark:text-white/60 hover:bg-slate-100 dark:hover:bg-white/5 transition text-xs opacity-70 hover:opacity-100">
                <i class="fa-solid fa-chevron-left"></i>
            </a>
        @endif

        {{-- Pagination Elements --}}
        @foreach ($elements as $element)
            {{-- "Three Dots" Separator --}}
            @if (is_string($element))
                <span
                    class="w-8 h-8 flex items-center justify-center text-xs text-slate-400 dark:text-white/30">{{ $element }}</span>
            @endif

            {{-- Array Of Links --}}
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span
                            class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center text-xs font-bold text-white shadow-lg shadow-blue-600/20">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}"
                            class="w-8 h-8 rounded-lg border border-slate-200 dark:border-white/10 flex items-center justify-center text-slate-500 dark:text-white/60 hover:bg-slate-100 dark:hover:bg-white/5 transition text-xs hover:text-slate-900 dark:hover:text-white">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next Page Link --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}"
                class="w-8 h-8 rounded-lg border border-slate-200 dark:border-white/10 flex items-center justify-center text-slate-400 dark:text-white/60 hover:bg-slate-100 dark:hover:bg-white/5 transition text-xs opacity-70 hover:opacity-100">
                <i class="fa-solid fa-chevron-right"></i>
            </a>
        @else
            <span
                class="w-8 h-8 rounded-lg border border-slate-200 dark:border-white/5 flex items-center justify-center text-xs text-slate-300 dark:text-white opacity-30 cursor-not-allowed">
                <i class="fa-solid fa-chevron-right"></i>
            </span>
        @endif
    </nav>
@endif

