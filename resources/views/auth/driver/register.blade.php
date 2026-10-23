<x-auth.shell title="Driver Registration - SmartCommute" :wide="true" heading="Drive with SmartCommute."
    blurb="Apply online with your licence details. Once approved and assigned a vehicle, clock in from the map and keep your shifts on record.">

    <x-slot:head>
        <style>
            .file-drop-zone {
                background: var(--sc-surface-2);
                border: 2px dashed var(--sc-line-strong);
                border-radius: 18px;
                transition: border-color .25s, background-color .25s;
                cursor: pointer;
            }

            .file-drop-zone:hover {
                border-color: var(--sc-brand-hi);
                background: var(--sc-brand-soft);
            }

            .file-drop-zone.has-file {
                border-color: rgba(16, 185, 129, .6);
                background: rgba(16, 185, 129, .08);
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
    </x-slot:head>

    <span class="sc-chip" style="background: rgba(245,158,11,.16); color: #b45309"><i
            class="fa-solid fa-id-card"></i> Driver</span>
    <h1 class="au-title mt-4">Driver registration</h1>
    <p class="au-sub">Become a PUJ operator. An administrator reviews every application before you can sign in.</p>

    <form action="{{ route('driver.register') }}" method="POST" enctype="multipart/form-data"
        class="mt-8 grid gap-5 sm:grid-cols-2">
        @csrf

        <!-- Name -->
        <div>
            <label for="name" class="sc-label">Name</label>
            <div class="au-icon-field">
                <i class="fa-solid fa-user lead"></i>
                <input type="text" id="name" name="name" placeholder="Full name" value="{{ old('name') }}"
                    autocomplete="name" class="sc-field">
            </div>
            @error('name')
                <div class="au-err"><i class="fa-solid fa-circle-exclamation"></i><span>{{ $message }}</span></div>
            @enderror
        </div>

        <!-- Email -->
        <div>
            <label for="email" class="sc-label">Email</label>
            <div class="au-icon-field">
                <i class="fa-solid fa-envelope lead"></i>
                <input type="email" id="email" name="email" placeholder="you@example.com"
                    value="{{ old('email') }}" autocomplete="email" class="sc-field">
            </div>
            @error('email')
                <div class="au-err"><i class="fa-solid fa-circle-exclamation"></i><span>{{ $message }}</span></div>
            @enderror
        </div>

        <!-- Contact -->
        <div class="sm:col-span-2">
            <label for="contact-info" class="sc-label">Contact number</label>
            <div class="flex">
                <span class="grid place-items-center px-4 text-[15px] font-bold"
                    style="border: 1px solid var(--sc-line-strong); border-right: 0; border-radius: 14px 0 0 14px; background: var(--sc-surface-2); color: var(--sc-ink-2)">+63</span>
                <div class="au-icon-field flex-1">
                    <i class="fa-solid fa-phone lead"></i>
                    <input type="tel" name="contact_info" id="contact-info" placeholder="9XX XXX XXXX" maxlength="12"
                        value="{{ old('contact_info') }}" oninput="formatPhoneNumber(this)" class="sc-field"
                        style="border-radius: 0 14px 14px 0">
                </div>
            </div>
            <p class="mt-2 text-[13px]" style="color: var(--sc-mute)">Philippine mobile number (e.g., 917 123 4567)
            </p>
            @error('contact_info')
                <div class="au-err"><i class="fa-solid fa-circle-exclamation"></i><span>{{ $message }}</span></div>
            @enderror
        </div>

        <!-- Password -->
        <div>
            <label for="password" class="sc-label">Password</label>
            <div class="au-icon-field">
                <i class="fa-solid fa-lock lead"></i>
                <input type="password" name="password" id="password" placeholder="••••••••"
                    autocomplete="new-password" class="sc-field" style="padding-right: 52px">
                <button type="button" onclick="togglePassword('password', 'eye-icon-1')" class="au-eye"
                    aria-label="Show or hide password"><i id="eye-icon-1" class="fa-solid fa-eye"></i></button>
            </div>
            @error('password')
                <div class="au-err"><i class="fa-solid fa-circle-exclamation"></i><span>{{ $message }}</span></div>
            @enderror
        </div>

        <!-- Confirm Password -->
        <div>
            <label for="confirm-password" class="sc-label">Confirm password</label>
            <div class="au-icon-field">
                <i class="fa-solid fa-lock lead"></i>
                <input type="password" name="confirm-password" id="confirm-password" placeholder="••••••••"
                    autocomplete="new-password" class="sc-field" style="padding-right: 52px">
                <button type="button" onclick="togglePassword('confirm-password', 'eye-icon-2')" class="au-eye"
                    aria-label="Show or hide password"><i id="eye-icon-2" class="fa-solid fa-eye"></i></button>
            </div>
            @error('confirm-password')
                <div class="au-err"><i class="fa-solid fa-circle-exclamation"></i><span>{{ $message }}</span></div>
            @enderror
        </div>

        <!-- License Upload -->
        <div class="sm:col-span-2">
            <label class="sc-label">License ID</label>
            <div class="file-drop-zone p-6 text-center" id="fileDropZone"
                onclick="document.getElementById('license_file').click()">
                <input type="file" name="license_image" id="license_file" accept="image/jpeg,image/png,image/jpg"
                    class="hidden" onchange="handleFileSelect(this)">
                <div id="filePlaceholder">
                    <div class="grid place-items-center w-12 h-12 mx-auto mb-3 rounded-2xl"
                        style="background: var(--sc-brand-soft); color: var(--sc-brand-text)"><i
                            class="fa-solid fa-cloud-arrow-up text-lg"></i></div>
                    <p class="text-[15px] font-bold" style="color: var(--sc-ink)">Click to upload</p>
                    <p class="text-[13px] mt-0.5" style="color: var(--sc-mute)">JPG or PNG — max 2MB</p>
                </div>
                <div id="fileSelected" class="hidden">
                    <div class="grid place-items-center w-12 h-12 mx-auto mb-3 rounded-2xl"
                        style="background: rgba(16,185,129,.16); color: #10b981"><i
                            class="fa-solid fa-file-image text-lg"></i></div>
                    <p class="text-[15px] font-bold" style="color: var(--sc-ink)" id="fileName"></p>
                    <p class="text-[13px] mt-0.5" style="color: var(--sc-mute)">Click to change</p>
                </div>
            </div>
            @error('license_image')
                <div class="au-err"><i class="fa-solid fa-circle-exclamation"></i><span>{{ $message }}</span></div>
            @enderror
        </div>

        <!-- Terms -->
        <div class="sm:col-span-2">
            <label class="flex items-start gap-3 cursor-pointer text-[14.5px] leading-snug"
                style="color: var(--sc-ink-2)">
                <input type="checkbox" id="terms" name="terms" value="1"
                    class="mt-0.5 w-4 h-4 rounded cursor-pointer" {{ old('terms') ? 'checked' : '' }}>
                <span>I agree to the <a href="#" class="au-link">Terms of Service</a> and <a href="#"
                        class="au-link">Privacy Policy</a>.</span>
            </label>
            @error('terms')
                <div class="au-err"><i class="fa-solid fa-circle-exclamation"></i><span>{{ $message }}</span></div>
            @enderror
        </div>

        <button type="submit" class="sc-btn sm:col-span-2" style="height: 54px; font-size: 15.5px">Submit
            application</button>
    </form>

    <div class="au-divider flex flex-wrap items-center justify-center gap-x-6 gap-y-2">
        <span>Already have an account? <a href="{{ url('/login') }}" class="au-link">Log in</a></span>
        <a href="{{ route('register') }}" class="au-link">Register as a commuter</a>
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
    </script>
</x-auth.shell>
