<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartCommute | App Tutorial</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #0f172a; color: #fff; }
        .glass { background: rgba(15, 23, 42, 0.8); backdrop-filter: blur(16px); border: 1px solid rgba(255,255,255,0.1); }
    </style>
</head>
<body class="antialiased min-h-screen">
    <div class="max-w-2xl mx-auto p-6 md:p-10">
        <div class="flex items-center justify-between mb-10">
            <a href="{{ route('commuter.dashboard') }}" class="text-blue-400 hover:text-blue-300 text-sm font-bold flex items-center gap-2">
                <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
        <div class="glass rounded-[2rem] p-8 md:p-10">
            <div class="w-14 h-14 bg-yellow-500/20 rounded-2xl flex items-center justify-center mb-6">
                <i class="fa-solid fa-wand-magic-sparkles text-yellow-500 text-xl"></i>
            </div>
            <h1 class="text-2xl font-bold mb-2">App Tutorial</h1>
            <p class="text-white/60 text-sm mb-8">New to SmartCommute? Here's how to get started.</p>
            <div class="space-y-6">
                <div class="flex gap-4">
                    <span class="w-8 h-8 rounded-full bg-blue-600/30 flex items-center justify-center text-blue-400 text-xs font-bold shrink-0">1</span>
                    <div>
                        <h3 class="font-bold text-sm mb-1">Enter Your Location</h3>
                        <p class="text-white/60 text-xs">Type your starting point or click the crosshairs icon to use your current GPS location.</p>
                    </div>
                </div>
                <div class="flex gap-4">
                    <span class="w-8 h-8 rounded-full bg-blue-600/30 flex items-center justify-center text-blue-400 text-xs font-bold shrink-0">2</span>
                    <div>
                        <h3 class="font-bold text-sm mb-1">Enter Destination</h3>
                        <p class="text-white/60 text-xs">Type where you want to go (e.g., "Ayala, Cebu" or "SM City").</p>
                    </div>
                </div>
                <div class="flex gap-4">
                    <span class="w-8 h-8 rounded-full bg-blue-600/30 flex items-center justify-center text-blue-400 text-xs font-bold shrink-0">3</span>
                    <div>
                        <h3 class="font-bold text-sm mb-1">Calculate Fare</h3>
                        <p class="text-white/60 text-xs">Click "Calculate Fare" to see the estimated cost and travel time. Fare is automatically deducted from your Digital Wallet.</p>
                    </div>
                </div>
                <div class="flex gap-4">
                    <span class="w-8 h-8 rounded-full bg-blue-600/30 flex items-center justify-center text-blue-400 text-xs font-bold shrink-0">4</span>
                    <div>
                        <h3 class="font-bold text-sm mb-1">View History</h3>
                        <p class="text-white/60 text-xs">Check "Recent Receipts" or "History" to see past trips and spending.</p>
                    </div>
                </div>
            </div>
            <a href="{{ route('commuter.dashboard') }}" class="mt-10 block w-full bg-blue-600 hover:bg-blue-500 py-3.5 rounded-2xl text-center text-sm font-bold uppercase tracking-widest transition">
                Go to Dashboard
            </a>
        </div>
    </div>
</body>
</html>
