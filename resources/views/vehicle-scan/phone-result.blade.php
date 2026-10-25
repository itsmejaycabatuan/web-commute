<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>SmartCommute | Scan registration papers</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
</head>

<body class="bg-slate-50 dark:bg-[#0b0b0b] text-slate-900 dark:text-white min-h-screen">
    <main class="mx-auto max-w-md px-4 py-10 text-center">
        <div class="rounded-2xl border border-slate-200 dark:border-[#222] bg-white dark:bg-[#111] p-8">
            @if ($status === 'ready')
                <i class="fa-solid fa-circle-check text-4xl text-emerald-500"></i>
            @else
                <i class="fa-solid fa-triangle-exclamation text-4xl text-amber-500"></i>
            @endif
            <p class="mt-3 font-bold">{{ $message }}</p>
            @if ($status !== 'ready' && $status !== 'expired')
                <p class="mt-3"><a class="text-blue-600 font-bold" href="javascript:history.back()">Try again</a></p>
            @endif
        </div>
    </main>
</body>

</html>
