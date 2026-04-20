@if(session('success'))
    <div id="flash-message" class="fixed top-24 right-6 z-50 animate-slide-in">
        <div
            class="bg-gradient-to-r from-green-500 to-emerald-600 text-white px-6 py-4 rounded-2xl shadow-2xl shadow-green-500/20 flex items-center gap-3 min-w-[300px]">
            <i class="fa-solid fa-check-circle text-xl"></i>
            <div class="flex-1">
                <p class="font-bold text-sm">Success!</p>
                <p class="text-xs opacity-90">{{ session('success') }}</p>
            </div>
            <button onclick="this.parentElement.parentElement.remove()" class="hover:opacity-70">
                <i class="fa-solid fa-times"></i>
            </button>
        </div>
    </div>
@endif

@if(session('error'))
    <div id="flash-message" class="fixed top-24 right-6 z-50 animate-slide-in">
        <div
            class="bg-gradient-to-r from-red-500 to-rose-600 text-white px-6 py-4 rounded-2xl shadow-2xl shadow-red-500/20 flex items-center gap-3 min-w-[300px]">
            <i class="fa-solid fa-circle-exclamation text-xl"></i>
            <div class="flex-1">
                <p class="font-bold text-sm">Error!</p>
                <p class="text-xs opacity-90">{{ session('error') }}</p>
            </div>
            <button onclick="this.parentElement.parentElement.remove()" class="hover:opacity-70">
                <i class="fa-solid fa-times"></i>
            </button>
        </div>
    </div>
@endif

@if(session('warning'))
    <div id="flash-message" class="fixed top-24 right-6 z-50 animate-slide-in">
        <div
            class="bg-gradient-to-r from-yellow-500 to-amber-600 text-white px-6 py-4 rounded-2xl shadow-2xl shadow-yellow-500/20 flex items-center gap-3 min-w-[300px]">
            <i class="fa-solid fa-triangle-exclamation text-xl"></i>
            <div class="flex-1">
                <p class="font-bold text-sm">Warning!</p>
                <p class="text-xs opacity-90">{{ session('warning') }}</p>
            </div>
            <button onclick="this.parentElement.parentElement.remove()" class="hover:opacity-70">
                <i class="fa-solid fa-times"></i>
            </button>
        </div>
    </div>
@endif

@if($errors->any())
    <div id="flash-message" class="fixed top-24 right-6 z-50 animate-slide-in">
        <div
            class="bg-gradient-to-r from-red-500 to-rose-600 text-white px-6 py-4 rounded-2xl shadow-2xl shadow-red-500/20 flex items-start gap-3 min-w-[300px]">
            <i class="fa-solid fa-circle-exclamation text-xl mt-0.5"></i>
            <div class="flex-1">
                <p class="font-bold text-sm">Error </p>
                <ul class="text-xs opacity-90 mt-1 list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            <button onclick="this.parentElement.parentElement.remove()" class="hover:opacity-70">
                <i class="fa-solid fa-times"></i>
            </button>
        </div>
    </div>
@endif

<style>
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }

        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    .animate-slide-in {
        animation: slideIn 0.3s ease-out;
    }
</style>

<script>
    // Auto-hide flash messages after 5 seconds
    setTimeout(function () {
        const flashMessages = document.querySelectorAll('#flash-message');
        flashMessages.forEach(function (message) {
            message.style.transition = 'opacity 0.5s ease-out';
            message.style.opacity = '0';
            setTimeout(function () {
                message.remove();
            }, 500);
        });
    }, 5000);
</script>