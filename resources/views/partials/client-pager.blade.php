{{-- 10-per-page pager for lists that are filtered in the browser (Alpine).

     The enclosing Alpine component must define:
       page, perPage (10), and a list expression passed here as $list
     and iterate its own `pageOf(list)` / `pageCount(list)` helpers - see
     window.clientPager() in this partial, which components spread into their data. --}}
@once
    <script>
        /* Spread into an Alpine component: { ...clientPager(), ... }.
           pageOf(list) returns the current page's slice, pageCount(list) the page total. */
        window.clientPager = function () {
            return {
                page: 1,
                perPage: 10,
                pageOf(list) {
                    const last = this.pageCount(list);
                    if (this.page > last) this.page = last;
                    return list.slice((this.page - 1) * this.perPage, this.page * this.perPage);
                },
                pageCount(list) {
                    return Math.max(1, Math.ceil(list.length / this.perPage));
                },
                pageNumbers(list) {
                    const last = this.pageCount(list);
                    const nums = [];
                    for (let i = 1; i <= last; i++) {
                        if (i === 1 || i === last || Math.abs(i - this.page) <= 1) nums.push(i);
                        else if (nums[nums.length - 1] !== '…') nums.push('…');
                    }
                    return nums;
                },
                goToPage(n, list) {
                    if (n >= 1 && n <= this.pageCount(list)) this.page = n;
                }
            };
        };
    </script>
@endonce

<div x-show="{{ $list }}.length > perPage" x-cloak
    class="px-4 sm:px-6 py-4 border-t border-gray-200 dark:border-[#1e1e1e] flex flex-col sm:flex-row items-center justify-between gap-3">
    <p class="text-[10px] font-bold text-gray-400 dark:text-[#555]"
        x-text="'Showing ' + ((page - 1) * perPage + 1) + '–' + Math.min(page * perPage, {{ $list }}.length) + ' of ' + {{ $list }}.length"></p>
    <nav class="flex flex-wrap justify-center gap-1.5" aria-label="Pagination">
        <button type="button" @click="goToPage(page - 1, {{ $list }})" :disabled="page === 1"
            class="w-8 h-8 rounded-lg border border-slate-200 dark:border-white/10 flex items-center justify-center text-xs text-slate-400 dark:text-white/60 hover:bg-slate-100 dark:hover:bg-white/5 transition disabled:opacity-30 disabled:cursor-not-allowed"
            aria-label="Previous page"><i class="fa-solid fa-chevron-left"></i></button>
        <template x-for="(n, i) in pageNumbers({{ $list }})" :key="i">
            <span>
                <template x-if="n === '…'"><span
                        class="w-8 h-8 flex items-center justify-center text-xs text-slate-400 dark:text-white/60">…</span></template>
                <template x-if="n !== '…'">
                    <button type="button" @click="goToPage(n, {{ $list }})"
                        class="w-8 h-8 rounded-lg flex items-center justify-center text-xs transition"
                        :class="n === page ? 'bg-blue-600 font-bold text-white shadow-lg shadow-blue-600/20' :
                            'border border-slate-200 dark:border-white/10 text-slate-500 dark:text-white/60 hover:bg-slate-100 dark:hover:bg-white/5'"
                        x-text="n"></button>
                </template>
            </span>
        </template>
        <button type="button" @click="goToPage(page + 1, {{ $list }})" :disabled="page === pageCount({{ $list }})"
            class="w-8 h-8 rounded-lg border border-slate-200 dark:border-white/10 flex items-center justify-center text-xs text-slate-400 dark:text-white/60 hover:bg-slate-100 dark:hover:bg-white/5 transition disabled:opacity-30 disabled:cursor-not-allowed"
            aria-label="Next page"><i class="fa-solid fa-chevron-right"></i></button>
    </nav>
</div>
