{{--
    Colour-coding that stays readable in BOTH themes (shared by every themed page).

    The pages use Tailwind colour utilities directly (text-emerald-500, bg-red-500/10,
    border-l-blue-500, dark:text-[#444] …). Many of those shades were too faint for
    older eyes — e.g. amber-500 on white is 2.1:1 and #444 on the dark cards is 1.9:1.
    Rather than touching every view, this partial re-points those utilities at one
    palette per theme, chosen so text is at least 4.5:1 against the light and dark
    surfaces (and against its own tinted badge background).

      green/emerald = good / approved / paid      amber/yellow = pending / due soon
      red/rose      = error / rejected / overdue  blue         = information / primary
      purple        = totals / secondary

    To retune a colour, change the --cc-* variable. Base utilities for every hue
    (text-{hue}-400…700) are always covered; opacity and hover variants are listed
    for the ones the views use, so add a selector if you introduce a new one.
--}}
<style>
    :root {
        --cc-red: 185 28 28;
        --cc-red-fill: 185 28 28;
        --cc-red-fill-hover: 153 27 27;
        --cc-rose: 190 18 60;
        --cc-rose-fill: 190 18 60;
        --cc-rose-fill-hover: 159 18 57;
        --cc-pink: 190 24 93;
        --cc-pink-fill: 190 24 93;
        --cc-pink-fill-hover: 157 23 77;
        --cc-orange: 180 60 10;
        --cc-orange-fill: 180 60 10;
        --cc-orange-fill-hover: 154 52 18;
        --cc-amber: 163 82 0;
        --cc-amber-fill: 180 83 9;
        --cc-amber-fill-hover: 146 64 14;
        --cc-yellow: 138 90 0;
        --cc-yellow-fill: 161 98 7;
        --cc-yellow-fill-hover: 133 77 14;
        --cc-lime: 70 112 14;
        --cc-lime-fill: 70 112 14;
        --cc-lime-fill-hover: 63 98 18;
        --cc-green: 20 120 56;
        --cc-green-fill: 20 120 56;
        --cc-green-fill-hover: 22 101 52;
        --cc-emerald: 4 120 87;
        --cc-emerald-fill: 4 120 87;
        --cc-emerald-fill-hover: 6 95 70;
        --cc-teal: 15 118 110;
        --cc-teal-fill: 15 118 110;
        --cc-teal-fill-hover: 17 94 89;
        --cc-cyan: 14 116 144;
        --cc-cyan-fill: 14 116 144;
        --cc-cyan-fill-hover: 21 94 117;
        --cc-sky: 3 105 161;
        --cc-sky-fill: 3 105 161;
        --cc-sky-fill-hover: 7 89 133;
        --cc-blue: 67 56 202;
        --cc-blue-fill: 79 70 229;
        --cc-blue-fill-hover: 67 56 202;
        --cc-indigo: 79 70 229;
        --cc-indigo-fill: 67 56 202;
        --cc-indigo-fill-hover: 55 48 163;
        --cc-violet: 109 40 217;
        --cc-violet-fill: 109 40 217;
        --cc-violet-fill-hover: 91 33 182;
        --cc-purple: 126 34 206;
        --cc-purple-fill: 126 34 206;
        --cc-purple-fill-hover: 107 33 168;
        --cc-fuchsia: 162 28 175;
        --cc-fuchsia-fill: 162 28 175;
        --cc-fuchsia-fill-hover: 134 25 143;
    }

    :root.dark {
        --cc-red: 248 113 113;
        --cc-rose: 251 113 133;
        --cc-pink: 244 114 182;
        --cc-orange: 251 146 60;
        --cc-amber: 251 191 36;
        --cc-yellow: 250 204 21;
        --cc-lime: 163 230 53;
        --cc-green: 74 222 128;
        --cc-emerald: 52 211 153;
        --cc-teal: 45 212 191;
        --cc-cyan: 34 211 238;
        --cc-sky: 56 189 248;
        --cc-blue: 165 180 252;
        --cc-indigo: 129 140 248;
        --cc-violet: 167 139 250;
        --cc-purple: 192 132 252;
        --cc-fuchsia: 232 121 249;
    }

    /* ── Colour-coded text: one readable shade per hue, per theme ── */
    :root:root:root .text-red-400,
    :root:root:root .text-red-400\/60,
    :root:root:root .text-red-400\/70,
    :root:root:root .text-red-400\/80,
    :root:root:root .text-red-500,
    :root:root:root .text-red-500\/40,
    :root:root:root .text-red-500\/50,
    :root:root:root .text-red-500\/80,
    :root:root:root .text-red-600,
    :root:root:root .text-red-700,
    :root:root:root .hover\:text-red-300:hover,
    :root:root:root .hover\:text-red-400:hover,
    :root:root:root .hover\:text-red-600:hover,
    :root:root:root .group:hover .group-hover\:text-red-400,
    :root:root:root .group:hover .group-hover\:text-red-500 {
        color: rgb(var(--cc-red)) !important;
    }

    :root:root:root .text-rose-400,
    :root:root:root .text-rose-500,
    :root:root:root .text-rose-500\/60,
    :root:root:root .text-rose-600,
    :root:root:root .text-rose-700,
    :root:root:root .hover\:text-rose-400:hover,
    :root:root:root .hover\:text-rose-500:hover,
    :root:root:root .hover\:text-rose-600:hover {
        color: rgb(var(--cc-rose)) !important;
    }

    :root:root:root .text-pink-400,
    :root:root:root .text-pink-500,
    :root:root:root .text-pink-600,
    :root:root:root .text-pink-700 {
        color: rgb(var(--cc-pink)) !important;
    }

    :root:root:root .text-orange-400,
    :root:root:root .text-orange-500,
    :root:root:root .text-orange-500\/60,
    :root:root:root .text-orange-600,
    :root:root:root .text-orange-700,
    :root:root:root .group:hover .group-hover\:text-orange-500 {
        color: rgb(var(--cc-orange)) !important;
    }

    :root:root:root .text-amber-400,
    :root:root:root .text-amber-400\/60,
    :root:root:root .text-amber-500,
    :root:root:root .text-amber-500\/50,
    :root:root:root .text-amber-500\/60,
    :root:root:root .text-amber-600,
    :root:root:root .text-amber-700,
    :root:root:root .hover\:text-amber-500:hover,
    :root:root:root .hover\:text-amber-600:hover,
    :root:root:root .group:hover .group-hover\:text-amber-500 {
        color: rgb(var(--cc-amber)) !important;
    }

    :root:root:root .text-yellow-400,
    :root:root:root .text-yellow-500,
    :root:root:root .text-yellow-600,
    :root:root:root .text-yellow-700,
    :root:root:root .group:hover .group-hover\:text-yellow-400 {
        color: rgb(var(--cc-yellow)) !important;
    }

    :root:root:root .text-lime-400,
    :root:root:root .text-lime-500,
    :root:root:root .text-lime-600,
    :root:root:root .text-lime-700 {
        color: rgb(var(--cc-lime)) !important;
    }

    :root:root:root .text-green-400,
    :root:root:root .text-green-500,
    :root:root:root .text-green-500\/60,
    :root:root:root .text-green-600,
    :root:root:root .text-green-700 {
        color: rgb(var(--cc-green)) !important;
    }

    :root:root:root .text-emerald-400,
    :root:root:root .text-emerald-400\/60,
    :root:root:root .text-emerald-400\/70,
    :root:root:root .text-emerald-500,
    :root:root:root .text-emerald-500\/30,
    :root:root:root .text-emerald-500\/40,
    :root:root:root .text-emerald-500\/50,
    :root:root:root .text-emerald-500\/60,
    :root:root:root .text-emerald-600,
    :root:root:root .text-emerald-700,
    :root:root:root .hover\:text-emerald-500:hover,
    :root:root:root .hover\:text-emerald-600:hover,
    :root:root:root .hover\:text-emerald-800:hover,
    :root:root:root .group:hover .group-hover\:text-emerald-500 {
        color: rgb(var(--cc-emerald)) !important;
    }

    :root:root:root .text-teal-400,
    :root:root:root .text-teal-500,
    :root:root:root .text-teal-600,
    :root:root:root .text-teal-700 {
        color: rgb(var(--cc-teal)) !important;
    }

    :root:root:root .text-cyan-400,
    :root:root:root .text-cyan-500,
    :root:root:root .text-cyan-600,
    :root:root:root .text-cyan-700 {
        color: rgb(var(--cc-cyan)) !important;
    }

    :root:root:root .text-sky-400,
    :root:root:root .text-sky-500,
    :root:root:root .text-sky-600,
    :root:root:root .text-sky-700 {
        color: rgb(var(--cc-sky)) !important;
    }

    :root:root:root .text-blue-400,
    :root:root:root .text-blue-400\/60,
    :root:root:root .text-blue-500,
    :root:root:root .text-blue-500\/20,
    :root:root:root .text-blue-500\/30,
    :root:root:root .text-blue-500\/40,
    :root:root:root .text-blue-500\/50,
    :root:root:root .text-blue-500\/60,
    :root:root:root .text-blue-600,
    :root:root:root .text-blue-700,
    :root:root:root .hover\:text-blue-300:hover,
    :root:root:root .hover\:text-blue-400:hover,
    :root:root:root .hover\:text-blue-500:hover,
    :root:root:root .hover\:text-blue-600:hover,
    :root:root:root .hover\:text-blue-800:hover,
    :root:root:root .group:hover .group-hover\:text-blue-400,
    :root:root:root .group:hover .group-hover\:text-blue-500 {
        color: rgb(var(--cc-blue)) !important;
    }

    :root:root:root .text-indigo-400,
    :root:root:root .text-indigo-500,
    :root:root:root .text-indigo-600,
    :root:root:root .text-indigo-700 {
        color: rgb(var(--cc-indigo)) !important;
    }

    :root:root:root .text-violet-400,
    :root:root:root .text-violet-500,
    :root:root:root .text-violet-600,
    :root:root:root .text-violet-700 {
        color: rgb(var(--cc-violet)) !important;
    }

    :root:root:root .text-purple-400,
    :root:root:root .text-purple-400\/60,
    :root:root:root .text-purple-500,
    :root:root:root .text-purple-500\/40,
    :root:root:root .text-purple-600,
    :root:root:root .text-purple-700,
    :root:root:root .hover\:text-purple-400:hover,
    :root:root:root .hover\:text-purple-600:hover,
    :root:root:root .hover\:text-purple-800:hover,
    :root:root:root .group:hover .group-hover\:text-purple-500 {
        color: rgb(var(--cc-purple)) !important;
    }

    :root:root:root .text-fuchsia-400,
    :root:root:root .text-fuchsia-500,
    :root:root:root .text-fuchsia-600,
    :root:root:root .text-fuchsia-700 {
        color: rgb(var(--cc-fuchsia)) !important;
    }

    /* ── Colour-coded outlines: tinted borders are too faint to carry the code on their own ── */
    :root:root:root .border-red-400\/30,
    :root:root:root .border-red-500\/10,
    :root:root:root .border-red-500\/15,
    :root:root:root .border-red-500\/20,
    :root:root:root .border-red-500\/25,
    :root:root:root .border-red-500\/30,
    :root:root:root .border-red-500\/50 {
        border-color: rgb(var(--cc-red) / 0.5) !important;
    }

    :root:root:root .border-rose-500\/10,
    :root:root:root .border-rose-500\/15,
    :root:root:root .border-rose-500\/20,
    :root:root:root .border-rose-500\/25 {
        border-color: rgb(var(--cc-rose) / 0.5) !important;
    }

    :root:root:root .border-rose-400 {
        border-color: rgb(var(--cc-rose)) !important;
    }

    :root:root:root .border-orange-500\/15,
    :root:root:root .border-orange-500\/20 {
        border-color: rgb(var(--cc-orange) / 0.5) !important;
    }

    :root:root:root .border-amber-500\/10,
    :root:root:root .border-amber-500\/15,
    :root:root:root .border-amber-500\/20,
    :root:root:root .border-amber-500\/25,
    :root:root:root .border-amber-500\/30 {
        border-color: rgb(var(--cc-amber) / 0.5) !important;
    }

    :root:root:root .border-yellow-500\/15 {
        border-color: rgb(var(--cc-yellow) / 0.5) !important;
    }

    :root:root:root .border-green-500\/15,
    :root:root:root .border-green-500\/20 {
        border-color: rgb(var(--cc-green) / 0.5) !important;
    }

    :root:root:root .border-emerald-500\/10,
    :root:root:root .border-emerald-500\/15,
    :root:root:root .border-emerald-500\/20,
    :root:root:root .border-emerald-500\/25 {
        border-color: rgb(var(--cc-emerald) / 0.5) !important;
    }

    :root:root:root .border-teal-500\/15,
    :root:root:root .border-teal-500\/20 {
        border-color: rgb(var(--cc-teal) / 0.5) !important;
    }

    :root:root:root .border-blue-400\/30,
    :root:root:root .border-blue-500\/10,
    :root:root:root .border-blue-500\/15,
    :root:root:root .border-blue-500\/20,
    :root:root:root .border-blue-500\/25,
    :root:root:root .border-blue-500\/30,
    :root:root:root .border-blue-500\/50 {
        border-color: rgb(var(--cc-blue) / 0.5) !important;
    }

    :root:root:root .border-blue-500,
    :root:root:root .border-blue-600 {
        border-color: rgb(var(--cc-blue)) !important;
    }

    :root:root:root .border-purple-500\/10,
    :root:root:root .border-purple-500\/15,
    :root:root:root .border-purple-500\/20 {
        border-color: rgb(var(--cc-purple) / 0.5) !important;
    }

    /* Accent stripes on cards. `.dark .glass-card { border: … }` used to wipe these out in dark mode. */
    :root:root:root .border-l-red-500 {
        border-left-color: rgb(var(--cc-red)) !important;
        border-left-width: 4px !important;
    }

    :root:root:root .border-l-rose-500 {
        border-left-color: rgb(var(--cc-rose)) !important;
        border-left-width: 4px !important;
    }

    :root:root:root .border-l-orange-500 {
        border-left-color: rgb(var(--cc-orange)) !important;
        border-left-width: 4px !important;
    }

    :root:root:root .border-l-amber-500 {
        border-left-color: rgb(var(--cc-amber)) !important;
        border-left-width: 4px !important;
    }

    :root:root:root .border-l-emerald-500 {
        border-left-color: rgb(var(--cc-emerald)) !important;
        border-left-width: 4px !important;
    }

    :root:root:root .border-l-teal-500 {
        border-left-color: rgb(var(--cc-teal)) !important;
        border-left-width: 4px !important;
    }

    :root:root:root .border-l-cyan-500 {
        border-left-color: rgb(var(--cc-cyan)) !important;
        border-left-width: 4px !important;
    }

    :root:root:root .border-l-blue-500 {
        border-left-color: rgb(var(--cc-blue)) !important;
        border-left-width: 4px !important;
    }

    :root:root:root .border-l-purple-500 {
        border-left-color: rgb(var(--cc-purple)) !important;
        border-left-width: 4px !important;
    }

    /* ── Solid colour buttons and pills: white label needs a darker fill than the 500 shade ── */
    :root:root:root .text-white.bg-red-400,
    :root:root:root .text-white.bg-red-500,
    :root:root:root .text-white.bg-red-600 {
        background-color: rgb(var(--cc-red-fill)) !important;
    }

    :root:root:root .text-white.hover\:bg-red-500:hover,
    :root:root:root .text-white.hover\:bg-red-700:hover {
        background-color: rgb(var(--cc-red-fill-hover)) !important;
    }

    :root:root:root .text-white.bg-rose-400,
    :root:root:root .text-white.bg-rose-500,
    :root:root:root .text-white.bg-rose-600 {
        background-color: rgb(var(--cc-rose-fill)) !important;
    }

    :root:root:root .text-white.hover\:bg-rose-500:hover {
        background-color: rgb(var(--cc-rose-fill-hover)) !important;
    }

    :root:root:root .text-white.bg-pink-400,
    :root:root:root .text-white.bg-pink-500,
    :root:root:root .text-white.bg-pink-600 {
        background-color: rgb(var(--cc-pink-fill)) !important;
    }

    :root:root:root .text-white.bg-orange-400,
    :root:root:root .text-white.bg-orange-500,
    :root:root:root .text-white.bg-orange-600 {
        background-color: rgb(var(--cc-orange-fill)) !important;
    }

    :root:root:root .text-white.hover\:bg-orange-500:hover {
        background-color: rgb(var(--cc-orange-fill-hover)) !important;
    }

    :root:root:root .text-white.bg-amber-400,
    :root:root:root .text-white.bg-amber-500,
    :root:root:root .text-white.bg-amber-600 {
        background-color: rgb(var(--cc-amber-fill)) !important;
    }

    :root:root:root .text-white.hover\:bg-amber-500:hover,
    :root:root:root .text-white.hover\:bg-amber-600:hover {
        background-color: rgb(var(--cc-amber-fill-hover)) !important;
    }

    :root:root:root .text-white.bg-yellow-400,
    :root:root:root .text-white.bg-yellow-500,
    :root:root:root .text-white.bg-yellow-600 {
        background-color: rgb(var(--cc-yellow-fill)) !important;
    }

    :root:root:root .text-white.bg-lime-400,
    :root:root:root .text-white.bg-lime-500,
    :root:root:root .text-white.bg-lime-600 {
        background-color: rgb(var(--cc-lime-fill)) !important;
    }

    :root:root:root .text-white.bg-green-400,
    :root:root:root .text-white.bg-green-500,
    :root:root:root .text-white.bg-green-600 {
        background-color: rgb(var(--cc-green-fill)) !important;
    }

    :root:root:root .text-white.bg-emerald-400,
    :root:root:root .text-white.bg-emerald-500,
    :root:root:root .text-white.bg-emerald-600 {
        background-color: rgb(var(--cc-emerald-fill)) !important;
    }

    :root:root:root .text-white.hover\:bg-emerald-500:hover {
        background-color: rgb(var(--cc-emerald-fill-hover)) !important;
    }

    :root:root:root .text-white.bg-teal-400,
    :root:root:root .text-white.bg-teal-500,
    :root:root:root .text-white.bg-teal-600 {
        background-color: rgb(var(--cc-teal-fill)) !important;
    }

    :root:root:root .text-white.bg-cyan-400,
    :root:root:root .text-white.bg-cyan-500,
    :root:root:root .text-white.bg-cyan-600 {
        background-color: rgb(var(--cc-cyan-fill)) !important;
    }

    :root:root:root .text-white.bg-sky-400,
    :root:root:root .text-white.bg-sky-500,
    :root:root:root .text-white.bg-sky-600 {
        background-color: rgb(var(--cc-sky-fill)) !important;
    }

    :root:root:root .text-white.bg-blue-400,
    :root:root:root .text-white.bg-blue-500,
    :root:root:root .text-white.bg-blue-600 {
        background-color: rgb(var(--cc-blue-fill)) !important;
    }

    :root:root:root .text-white.hover\:bg-blue-400:hover,
    :root:root:root .text-white.hover\:bg-blue-500:hover,
    :root:root:root .text-white.hover\:bg-blue-600:hover,
    :root:root:root .text-white.hover\:bg-blue-700:hover {
        background-color: rgb(var(--cc-blue-fill-hover)) !important;
    }

    :root:root:root .text-white.bg-indigo-400,
    :root:root:root .text-white.bg-indigo-500,
    :root:root:root .text-white.bg-indigo-600 {
        background-color: rgb(var(--cc-indigo-fill)) !important;
    }

    :root:root:root .text-white.bg-violet-400,
    :root:root:root .text-white.bg-violet-500,
    :root:root:root .text-white.bg-violet-600 {
        background-color: rgb(var(--cc-violet-fill)) !important;
    }

    :root:root:root .text-white.bg-purple-400,
    :root:root:root .text-white.bg-purple-500,
    :root:root:root .text-white.bg-purple-600 {
        background-color: rgb(var(--cc-purple-fill)) !important;
    }

    :root:root:root .text-white.hover\:bg-purple-500:hover,
    :root:root:root .text-white.hover\:bg-purple-700:hover {
        background-color: rgb(var(--cc-purple-fill-hover)) !important;
    }

    :root:root:root .text-white.bg-fuchsia-400,
    :root:root:root .text-white.bg-fuchsia-500,
    :root:root:root .text-white.bg-fuchsia-600 {
        background-color: rgb(var(--cc-fuchsia-fill)) !important;
    }

    /* ── Grey text: every step of the grey scale stays readable (≥ 4.5:1) on its theme's surfaces ── */
    :root:root:root:not(.dark) .text-\[\#1a1a1a\],
    :root:root:root:not(.dark) .text-\[\#222\],
    :root:root:root:not(.dark) .text-\[\#282828\],
    :root:root:root:not(.dark) .text-gray-200,
    :root:root:root:not(.dark) .text-gray-300,
    :root:root:root:not(.dark) .text-slate-200,
    :root:root:root:not(.dark) .text-slate-300 {
        color: #667080 !important;
    }

    :root:root:root:not(.dark) .text-\[\#333\] {
        color: #626b7b !important;
    }

    :root:root:root:not(.dark) .text-\[\#444\],
    :root:root:root:not(.dark) .text-gray-400,
    :root:root:root:not(.dark) .text-slate-400 {
        color: #5d6676 !important;
    }

    :root:root:root:not(.dark) .text-\[\#555\],
    :root:root:root:not(.dark) .text-gray-500,
    :root:root:root:not(.dark) .text-slate-500 {
        color: #586172 !important;
    }

    :root:root:root:not(.dark) .text-\[\#666\],
    :root:root:root:not(.dark) .text-\[\#777\] {
        color: #525c6b !important;
    }

    :root:root:root:not(.dark) .text-\[\#888\] {
        color: #4b5563 !important;
    }

    :root:root:root:not(.dark) .text-\[\#aaa\],
    :root:root:root:not(.dark) .text-\[\#bbb\] {
        color: #475569 !important;
    }

    :root:root:root:not(.dark) .text-\[\#ccc\],
    :root:root:root:not(.dark) .text-\[\#ddd\] {
        color: #334155 !important;
    }

    :root:root:root.dark .dark\:text-\[\#1a1a1a\],
    :root:root:root.dark .dark\:text-\[\#222\],
    :root:root:root.dark .dark\:text-\[\#282828\],
    :root:root:root.dark .text-\[\#1a1a1a\],
    :root:root:root.dark .text-\[\#222\],
    :root:root:root.dark .text-\[\#282828\] {
        color: #8d96ba !important;
    }

    :root:root:root.dark .dark\:text-\[\#333\],
    :root:root:root.dark .dark\:text-gray-600,
    :root:root:root.dark .text-\[\#333\] {
        color: #959ec2 !important;
    }

    :root:root:root.dark .dark\:text-\[\#444\],
    :root:root:root.dark .dark\:text-gray-500,
    :root:root:root.dark .text-\[\#444\] {
        color: #9ea7ca !important;
    }

    :root:root:root.dark .dark\:text-\[\#555\],
    :root:root:root.dark .text-\[\#555\] {
        color: #a8b0d1 !important;
    }

    :root:root:root.dark .dark\:text-\[\#666\],
    :root:root:root.dark .text-\[\#666\] {
        color: #adb5d4 !important;
    }

    :root:root:root.dark .dark\:text-\[\#777\],
    :root:root:root.dark .text-\[\#777\] {
        color: #b5bcd8 !important;
    }

    :root:root:root.dark .dark\:text-\[\#888\],
    :root:root:root.dark .text-\[\#888\] {
        color: #bdc4dd !important;
    }

    /* Hover feedback for grey text (the rules above would otherwise pin the colour) */
    :root:root:root:not(.dark) .hover\:text-\[\#555\]:hover,
    :root:root:root:not(.dark) .hover\:text-\[\#ccc\]:hover,
    :root:root:root:not(.dark) .hover\:text-black:hover,
    :root:root:root:not(.dark) .hover\:text-gray-500:hover,
    :root:root:root:not(.dark) .hover\:text-gray-600:hover,
    :root:root:root:not(.dark) .hover\:text-gray-700:hover,
    :root:root:root:not(.dark) .hover\:text-gray-900:hover,
    :root:root:root:not(.dark) .hover\:text-slate-900:hover,
    :root:root:root:not(.dark) .group:hover .group-hover\:text-\[\#555\],
    :root:root:root:not(.dark) .group:hover .group-hover\:text-black,
    :root:root:root:not(.dark) .group:hover .group-hover\:text-gray-300,
    :root:root:root:not(.dark) .group:hover .group-hover\:text-gray-500,
    :root:root:root:not(.dark) .group:hover .group-hover\:text-gray-700,
    :root:root:root:not(.dark) .group:hover .group-hover\:text-gray-900 {
        color: #0f172a !important;
    }

    :root:root:root.dark .dark\:hover\:text-\[\#555\]:hover,
    :root:root:root.dark .dark\:hover\:text-\[\#888\]:hover,
    :root:root:root.dark .dark\:hover\:text-\[\#ccc\]:hover,
    :root:root:root.dark .dark\:hover\:text-gray-200:hover,
    :root:root:root.dark .dark\:hover\:text-white:hover,
    :root:root:root.dark .group:hover .dark\:group-hover\:text-\[\#777\],
    :root:root:root.dark .group:hover .dark\:group-hover\:text-\[\#888\],
    :root:root:root.dark .group:hover .dark\:group-hover\:text-white {
        color: #ffffff !important;
    }

    /* ── Form hints ── */
    :root:root:root:not(.dark) ::placeholder {
        color: #6b7280 !important;
        opacity: 1;
    }

    :root:root:root.dark ::placeholder {
        color: #8a8a8a !important;
        opacity: 1;
    }
</style>
