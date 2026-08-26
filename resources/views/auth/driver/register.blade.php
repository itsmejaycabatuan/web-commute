<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Registration - SmartCommute</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif']
                    }
                }
            }
        }
    </script>
    <style>
        /* ── Scrollbar Track ── */
        ::-webkit-scrollbar {
            width: 5px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.08);
            border-radius: 999px;
            transition: background 0.3s ease;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.18);
        }

        /* ── Active / dragging state ── */
        ::-webkit-scrollbar-thumb:active {
            background: rgba(255, 255, 255, 0.25);
        }

        /* ── Firefox ── */
        * {
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, 0.08) transparent;
        }

        *::-moz-scrollbar-track {
            background: transparent;
        }

        *::-moz-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.08);
            border-radius: 999px;
            border: none;
        }

        *::-moz-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.18);
        }

        /* ── Card-specific: slightly brighter thumb for the glass card ── */
        .glass-card::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.12);
        }

        .glass-card::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.22);
        }

        .glass-card {
            scrollbar-color: rgba(255, 255, 255, 0.12) transparent;
        }

        /* ── Corner rounding for the card scrollbar area ── */
        .glass-card {
            scrollbar-gutter: stable;
        }

        .register-bg {
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.75) 0%, rgba(0, 15, 50, 0.7) 50%, rgba(0, 0, 0, 0.85) 100%),
                url("{{ asset('images/newbg.jpg') }}");
            background-size: cover;
            background-position: center;
        }

        .glass-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.07) 0%, rgba(255, 255, 255, 0.03) 100%);
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .input-field {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.08);
            transition: all 0.3s ease;
        }

        .input-field:focus {
            border-color: rgba(59, 130, 246, 0.5);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            background: rgba(255, 255, 255, 0.07);
        }

        .input-field::placeholder {
            color: rgba(255, 255, 255, 0.2);
        }

        @keyframes card-enter {
            from {
                opacity: 0;
                transform: translateY(30px) scale(0.96);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .card-animate {
            animation: card-enter 0.7s cubic-bezier(0.16, 1, 0.3, 1) 0.15s both;
        }

        .btn-primary {
            background: white;
            color: black;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: #e5e7eb;
            transform: translateY(-1px);
            box-shadow: 0 8px 30px rgba(255, 255, 255, 0.15);
        }

        .btn-primary:active {
            transform: scale(0.98) translateY(0);
        }

        @keyframes flash-in {
            from {
                opacity: 0;
                transform: translate(-50%, -20px);
            }

            to {
                opacity: 1;
                transform: translate(-50%, 0);
            }
        }

        .flash-animate {
            animation: flash-in 0.5s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        .error-inline {
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.2);
            border-radius: 0.75rem;
            padding: 0.5rem 0.75rem;
        }

        /* File input styling */
        .file-drop-zone {
            background: rgba(255, 255, 255, 0.03);
            border: 2px dashed rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .file-drop-zone:hover {
            border-color: rgba(59, 130, 246, 0.4);
            background: rgba(59, 130, 246, 0.05);
        }

        .file-drop-zone.has-file {
            border-color: rgba(34, 197, 94, 0.4);
            background: rgba(34, 197, 94, 0.05);
        }

        input[type="tel"]::-webkit-inner-spin-button,
        input[type="tel"]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        input[type="tel"] {
            -moz-appearance: textfield;
        }
    </style>
</head>

<body
    class="flex relative justify-center items-center p-4 sm:p-6 min-h-screen register-bg font-sans text-white overflow-x-hidden">

    <!-- Decorative orbs -->
    <div class="absolute top-1/4 right-1/4 w-72 h-72 bg-amber-500/8 rounded-full blur-[100px] pointer-events-none">
    </div>
    <div class="absolute bottom-1/3 left-1/3 w-56 h-56 bg-purple-500/10 rounded-full blur-[80px] pointer-events-none">
    </div>

    <!-- Back button -->
    <a href="{{ route('register') }}"
        class="flex absolute top-5 left-5 sm:top-8 sm:left-8 items-center gap-2.5 transition group z-10">
        <div
            class="flex justify-center items-center w-10 h-10 rounded-xl border border-white/10 bg-white/5 backdrop-blur-md group-hover:bg-white/10 group-hover:border-white/20 transition-all duration-300">
            <i class="text-sm fa-solid fa-arrow-left text-white/60 group-hover:text-white transition"></i>
        </div>
        <span
            class="hidden sm:inline text-[10px] font-bold tracking-widest uppercase text-white/50 group-hover:text-white/80 transition">Back
            to Registration</span>
    </a>

    <!-- Card -->
    <div
        class="card-animate glass-card p-7 sm:p-8 w-full max-w-[420px] rounded-[2rem] shadow-2xl shadow-black/30 max-h-[93vh] overflow-y-auto">

        <div class="mb-7 text-center">
            <div class="flex flex-col items-center justify-center mb-4">
                <div
                    class="flex items-center justify-center w-12 h-12 bg-amber-600 rounded-xl shadow-lg shadow-amber-600/30 mb-3">
                    <i class="fa-solid fa-id-card text-white text-lg"></i>
                </div>
                <span class="text-xl font-bold tracking-tight text-white">
                    Smart<span class="text-blue-400">Commute</span>
                </span>
            </div>
            <div
                class="inline-flex items-center gap-1.5 bg-amber-500/10 border border-amber-500/20 px-3 py-1 rounded-full mb-3">
                <i class="fa-solid fa-steering-wheel text-amber-400 text-[9px]"></i>
                <span class="text-[10px] font-bold uppercase tracking-widest text-amber-400">Driver</span>
            </div>
            <h2 class="text-2xl font-extrabold tracking-tight">Driver Registration</h2>
            <p class="mt-1.5 text-xs text-gray-400">Become a PUJ Operator</p>
        </div>

        <form action="{{ route('driver.register') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf

            <!-- Email -->
            <div>
                <label
                    class="block mb-1.5 ml-1 font-semibold tracking-widest uppercase text-[10px] text-gray-400">Email</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-3.5 flex items-center pointer-events-none">
                        <i class="fa-solid fa-envelope text-xs text-white/20"></i>
                    </div>
                    <input type="email" name="email" placeholder="you@example.com" value="{{ old('email') }}"
                        class="input-field py-3 pl-10 pr-4 w-full text-sm rounded-xl focus:outline-none">
                </div>
                @error('email')
                    <div class="error-inline mt-2 flex items-center gap-2">
                        <i class="fa-solid fa-circle-exclamation text-amber-400 text-[10px]"></i>
                        <span class="text-amber-300 text-[11px]">{{ $message }}</span>
                    </div>
                @enderror
            </div>

            <!-- Contact -->
            <div>
                <label
                    class="block mb-1.5 ml-1 font-semibold tracking-widest uppercase text-[10px] text-gray-400">Contact
                    Number</label>
                <div class="flex">
                    <div class="flex items-center gap-1.5 px-3.5 rounded-l-xl border border-r-0 input-field"
                        style="border-right: none;">
                        <span class="text-xs font-bold text-gray-400">+63</span>
                    </div>
                    <div class="relative flex-1">
                        <div class="absolute inset-y-0 left-3.5 flex items-center pointer-events-none">
                            <i class="fa-solid fa-phone text-xs text-white/20"></i>
                        </div>
                        <input type="tel" name="contact_info" id="contact-info" placeholder="9XX XXX XXXX"
                            maxlength="12" value="{{ old('contact_info') }}" oninput="formatPhoneNumber(this)"
                            class="input-field py-3 pl-10 pr-4 w-full text-sm rounded-r-xl focus:outline-none placeholder:text-white/20">
                    </div>
                </div>
                <p class="mt-1.5 ml-1 text-[10px] text-gray-600">Philippine mobile number (e.g., 917 123 4567)</p>
                @error('contact_info')
                    <div class="error-inline mt-2 flex items-center gap-2">
                        <i class="fa-solid fa-circle-exclamation text-amber-400 text-[10px]"></i>
                        <span class="text-amber-300 text-[11px]">{{ $message }}</span>
                    </div>
                @enderror
            </div>

            <!-- Password -->
            <div>
                <label
                    class="block mb-1.5 ml-1 font-semibold tracking-widest uppercase text-[10px] text-gray-400">Password</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-3.5 flex items-center pointer-events-none">
                        <i class="fa-solid fa-lock text-xs text-white/20"></i>
                    </div>
                    <input type="password" name="password" id="password" placeholder="••••••••"
                        class="input-field py-3 pl-10 pr-10 w-full text-sm rounded-xl focus:outline-none">
                    <button type="button" onclick="togglePassword('password', 'eye-icon-1')"
                        class="absolute inset-y-0 right-3.5 flex items-center text-white/30 hover:text-white/70 transition">
                        <i id="eye-icon-1" class="fa-solid fa-eye text-xs"></i>
                    </button>
                </div>
                @error('password')
                    <div class="error-inline mt-2 flex items-center gap-2">
                        <i class="fa-solid fa-circle-exclamation text-amber-400 text-[10px]"></i>
                        <span class="text-amber-300 text-[11px]">{{ $message }}</span>
                    </div>
                @enderror
            </div>

            <!-- Confirm Password -->
            <div>
                <label
                    class="block mb-1.5 ml-1 font-semibold tracking-widest uppercase text-[10px] text-gray-400">Confirm
                    Password</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-3.5 flex items-center pointer-events-none">
                        <i class="fa-solid fa-lock text-xs text-white/20"></i>
                    </div>
                    <input type="password" name="confirm-password" id="confirm-password" placeholder="••••••••"
                        class="input-field py-3 pl-10 pr-10 w-full text-sm rounded-xl focus:outline-none">
                    <button type="button" onclick="togglePassword('confirm-password', 'eye-icon-2')"
                        class="absolute inset-y-0 right-3.5 flex items-center text-white/30 hover:text-white/70 transition">
                        <i id="eye-icon-2" class="fa-solid fa-eye text-xs"></i>
                    </button>
                </div>
                @error('confirm-password')
                    <div class="error-inline mt-2 flex items-center gap-2">
                        <i class="fa-solid fa-circle-exclamation text-amber-400 text-[10px]"></i>
                        <span class="text-amber-300 text-[11px]">{{ $message }}</span>
                    </div>
                @enderror
            </div>

            <!-- License Upload -->
            <div>
                <label
                    class="block mb-1.5 ml-1 font-semibold tracking-widest uppercase text-[10px] text-gray-400">License
                    ID</label>
                <div class="file-drop-zone p-5 text-center" id="fileDropZone"
                    onclick="document.getElementById('license_file').click()">
                    <input type="file" name="license_image" id="license_file"
                        accept="image/jpeg,image/png,image/jpg" class="hidden" onchange="handleFileSelect(this)">
                    <div id="filePlaceholder">
                        <div class="flex justify-center mb-2">
                            <div class="w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center">
                                <i class="fa-solid fa-cloud-arrow-up text-white/30"></i>
                            </div>
                        </div>
                        <p class="text-xs text-gray-400 font-medium">Click to upload</p>
                        <p class="text-[10px] text-gray-600 mt-0.5">JPG, PNG — Max 2MB</p>
                    </div>
                    <div id="fileSelected" class="hidden">
                        <div class="flex justify-center mb-2">
                            <div class="w-10 h-10 rounded-xl bg-green-500/10 flex items-center justify-center">
                                <i class="fa-solid fa-file-image text-green-400"></i>
                            </div>
                        </div>
                        <p class="text-xs text-green-300 font-medium" id="fileName"></p>
                        <p class="text-[10px] text-gray-500 mt-0.5">Click to change</p>
                    </div>
                </div>
                @error('license_image')
                    <div class="error-inline mt-2 flex items-center gap-2">
                        <i class="fa-solid fa-circle-exclamation text-amber-400 text-[10px]"></i>
                        <span class="text-amber-300 text-[11px]">{{ $message }}</span>
                    </div>
                @enderror
            </div>

            <!-- Terms -->
            <label class="flex items-start gap-2.5 cursor-pointer group pt-1">
                <input type="checkbox" id="terms" name="terms" value="1"
                    class="mt-0.5 w-3.5 h-3.5 rounded cursor-pointer bg-white/5 border-white/20 text-blue-600 focus:ring-blue-500/30 focus:ring-offset-0"
                    {{ old('terms') ? 'checked' : '' }}>
                <span class="leading-tight text-[11px] text-gray-400 group-hover:text-gray-300 transition">I agree to
                    the <a href="#" class="text-blue-400 hover:underline">Terms of Service</a> and <a
                        href="#" class="text-blue-400 hover:underline">Privacy Policy</a>.</span>
            </label>
            @error('terms')
                <div class="error-inline flex items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation text-amber-400 text-[10px]"></i>
                    <span class="text-amber-300 text-[11px]">{{ $message }}</span>
                </div>
            @enderror

            <button type="submit"
                class="btn-primary py-3.5 mt-2 w-full text-xs font-bold tracking-widest uppercase rounded-xl">
                Submit Application
            </button>
        </form>

        <div class="pt-6 mt-6 text-center border-t border-white/5">
            <p class="text-xs text-gray-500">
                Already have an account?
                <a href="{{ url('/login') }}" class="font-semibold text-white hover:text-blue-400 transition">Log
                    in</a>
            </p>
        </div>
    </div>

    <script>
        function togglePassword(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = "password";
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        function formatPhoneNumber(input) {
            let digits = input.value.replace(/\D/g, '');
            if (digits.startsWith('0')) digits = digits.substring(1);
            if (digits.length > 10) digits = digits.substring(0, 10);
            if (digits.length > 6) {
                input.value = digits.slice(0, 3) + ' ' + digits.slice(3, 6) + ' ' + digits.slice(6, 10);
            } else if (digits.length > 3) {
                input.value = digits.slice(0, 3) + ' ' + digits.slice(3);
            } else {
                input.value = digits;
            }
        }

        function handleFileSelect(input) {
            const zone = document.getElementById('fileDropZone');
            const placeholder = document.getElementById('filePlaceholder');
            const selected = document.getElementById('fileSelected');
            const nameEl = document.getElementById('fileName');

            if (input.files && input.files[0]) {
                nameEl.textContent = input.files[0].name;
                placeholder.classList.add('hidden');
                selected.classList.remove('hidden');
                zone.classList.add('has-file');
            } else {
                placeholder.classList.remove('hidden');
                selected.classList.add('hidden');
                zone.classList.remove('has-file');
            }
        }

        // Auto-dismiss flash
        const flash = document.getElementById('login-flash-success');
        if (flash) {
            setTimeout(() => {
                flash.style.transition = 'all 0.4s ease';
                flash.style.opacity = '0';
                flash.style.transform = 'translate(-50%, -20px)';
                setTimeout(() => flash.remove(), 400);
            }, 3500);
        }
    </script>
</body>

</html>
