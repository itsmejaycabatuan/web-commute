<div>
    <!-- Be present above all else. - Naval Ravikant -->
    <div id="settings-modal" class="fixed inset-0 z-[200] hidden items-center justify-center p-4">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeSettingsModal()"></div>

        <!-- Modal Content -->
        <div class="modal-animate relative w-full max-w-xs p-6 rounded-2xl shadow-2xl
                    bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10">

            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-sm font-bold tracking-wide uppercase text-gray-900 dark:text-white">Settings</h3>
                <button onclick="closeSettingsModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-white transition">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <!-- Theme Toggle Section -->
            <div>
                <label class="block mb-3 text-xs font-semibold tracking-widest uppercase text-gray-500 dark:text-gray-400">Appearance</label>
                <div class="grid grid-cols-2 gap-3">

                    <!-- Light Mode Button -->
                    <button onclick="setTheme('light')" id="btn-light" class="theme-btn group flex flex-col items-center gap-2 p-4 rounded-xl border-2 transition-all duration-200 border-gray-200 dark:border-white/10 hover:border-blue-500 dark:hover:border-blue-500">
                        <i class="fa-solid fa-sun text-xl text-amber-500"></i>
                        <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">Light</span>
                    </button>

                    <!-- Dark Mode Button -->
                    <button onclick="setTheme('dark')" id="btn-dark" class="theme-btn group flex flex-col items-center gap-2 p-4 rounded-xl border-2 transition-all duration-200 border-gray-200 dark:border-white/10 hover:border-blue-500 dark:hover:border-blue-500">
                        <i class="fa-solid fa-moon text-xl text-blue-500"></i>
                        <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">Dark</span>
                    </button>

                </div>
            </div>
        </div>
    </div>

    <script>
        // Open/Close Modal
        function openSettingsModal() {
            const modal = document.getElementById('settings-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            updateSettingsUI(); // Update active states when opened
        }

        function closeSettingsModal() {
            const modal = document.getElementById('settings-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        // Set Theme Logic
        function setTheme(theme) {
            if (theme === 'dark') {
                document.documentElement.classList.add('dark');
                localStorage.setItem('color-theme', 'dark');
            } else {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('color-theme', 'light');
            }
            updateSettingsUI();
        }

        // Update which button looks "active"
        function updateSettingsUI() {
            const isDark = document.documentElement.classList.contains('dark');
            const btnLight = document.getElementById('btn-light');
            const btnDark = document.getElementById('btn-dark');

            // Reset both
            btnLight.classList.remove('border-blue-600', 'bg-blue-50', 'dark:bg-blue-900/20');
            btnDark.classList.remove('border-blue-600', 'bg-blue-50', 'dark:bg-blue-900/20');

            // Activate correct one
            if (isDark) {
                btnDark.classList.add('border-blue-600', 'dark:bg-blue-900/20');
            } else {
                btnLight.classList.add('border-blue-600', 'bg-blue-50');
            }
        }

        // Close modal on Escape key press
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeSettingsModal();
        });
    </script>
</div>
