<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Commute System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    <style>
        html {
            scroll-behavior: smooth;
        }

        * {
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .hero-bg {
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.7) 0%, rgba(0, 20, 60, 0.6) 50%, rgba(0, 0, 0, 0.8) 100%),
                url("{{ asset('images/newbg.jpg') }}");
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .glass {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }

        .glass-inset {
            background: rgba(255, 255, 255, 0.04);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.06);
        }

        .glass-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.05) 0%, rgba(255, 255, 255, 0.02) 100%);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .glass-card:hover {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.08) 0%, rgba(255, 255, 255, 0.03) 100%);
            border-color: rgba(255, 255, 255, 0.15);
        }

        .text-gradient {
            background: linear-gradient(135deg, #3b82f6, #8b5cf6, #06b6d4);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .text-gradient-warm {
            background: linear-gradient(135deg, #f59e0b, #ef4444, #ec4899);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .glow-blue {
            box-shadow: 0 0 40px rgba(59, 130, 246, 0.15), 0 0 80px rgba(59, 130, 246, 0.05);
        }

        .glow-blue-strong {
            box-shadow: 0 0 60px rgba(59, 130, 246, 0.25), 0 0 120px rgba(59, 130, 246, 0.1);
        }

        .line-glow {
            background: linear-gradient(90deg, transparent, #3b82f6, transparent);
            height: 1px;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        @keyframes pulse-slow {

            0%,
            100% {
                opacity: 0.4;
            }

            50% {
                opacity: 0.8;
            }
        }

        @keyframes slide-up {
            from {
                opacity: 0;
                transform: translateY(40px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fade-in {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes marquee {
            0% {
                transform: translateX(0);
            }

            100% {
                transform: translateX(-50%);
            }
        }

        .animate-float {
            animation: float 6s ease-in-out infinite;
        }

        .animate-pulse-slow {
            animation: pulse-slow 4s ease-in-out infinite;
        }

        .animate-marquee {
            animation: marquee 30s linear infinite;
        }

        .reveal {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .reveal.active {
            opacity: 1;
            transform: translateY(0);
        }

        .reveal-delay-1 {
            transition-delay: 0.1s;
        }

        .reveal-delay-2 {
            transition-delay: 0.2s;
        }

        .reveal-delay-3 {
            transition-delay: 0.3s;
        }

        .reveal-delay-4 {
            transition-delay: 0.4s;
        }

        .hero-load-1 {
            animation: slide-up 1s cubic-bezier(0.16, 1, 0.3, 1) 0.2s both;
        }

        .hero-load-2 {
            animation: slide-up 1s cubic-bezier(0.16, 1, 0.3, 1) 0.4s both;
        }

        .hero-load-3 {
            animation: slide-up 1s cubic-bezier(0.16, 1, 0.3, 1) 0.6s both;
        }

        .hero-load-4 {
            animation: fade-in 1s ease 0.8s both;
        }

        .stat-counter {
            font-variant-numeric: tabular-nums;
        }

        .mobile-menu {
            transform: translateX(100%);
            transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .mobile-menu.open {
            transform: translateX(0);
        }

        .mobile-overlay {
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }

        .mobile-overlay.open {
            opacity: 1;
            pointer-events: auto;
        }

        .hamburger-line {
            transition: all 0.3s ease;
        }

        .hamburger.active .hamburger-line:nth-child(1) {
            transform: rotate(45deg) translate(5px, 5px);
        }

        .hamburger.active .hamburger-line:nth-child(2) {
            opacity: 0;
        }

        .hamburger.active .hamburger-line:nth-child(3) {
            transform: rotate(-45deg) translate(5px, -5px);
        }

        input::placeholder,
        textarea::placeholder {
            color: rgba(255, 255, 255, 0.2);
        }

        input:focus,
        textarea:focus {
            border-color: rgba(59, 130, 246, 0.5) !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .service-card {
            position: relative;
            overflow: hidden;
        }

        .service-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(59, 130, 246, 0.5), transparent);
            opacity: 0;
            transition: opacity 0.5s ease;
        }

        .service-card:hover::before {
            opacity: 1;
        }

        .testimonial-card {
            position: relative;
        }

        .testimonial-card::after {
            content: '"';
            position: absolute;
            top: 20px;
            right: 30px;
            font-size: 120px;
            font-weight: 900;
            line-height: 1;
            color: rgba(59, 130, 246, 0.05);
            pointer-events: none;
        }

        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: #0a0a0a;
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        /* Toast notification */
        .toast {
            position: fixed;
            bottom: 1.5rem;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            opacity: 0;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            z-index: 200;
            pointer-events: none;
        }

        .toast.show {
            transform: translateX(-50%) translateY(0);
            opacity: 1;
            pointer-events: auto;
        }
    </style>
</head>

<body class="font-sans text-white bg-[#050505] overflow-x-hidden">

    <!-- Toast Notification -->
    <div id="toast" class="toast">
        <div class="glass-card glow-blue px-5 py-3 rounded-2xl flex items-center gap-3 border border-blue-500/20">
            <div class="w-8 h-8 bg-green-500/20 rounded-full flex items-center justify-center shrink-0">
                <i class="fa-solid fa-check text-green-400 text-xs"></i>
            </div>
            <span id="toastMsg" class="text-sm font-medium">Message sent successfully!</span>
        </div>
    </div>

    <!-- Mobile Menu Overlay -->
    <div id="mobileOverlay" class="mobile-overlay fixed inset-0 bg-black/60 backdrop-blur-sm z-[90]"
        onclick="closeMobileMenu()"></div>

    <!-- Mobile Menu Drawer -->
    <div id="mobileMenu"
        class="mobile-menu fixed top-0 right-0 w-[85%] max-w-[380px] h-full bg-[#0a0a0a] border-l border-white/5 z-[100] flex flex-col">
        <div class="flex justify-between items-center p-5 sm:p-6 border-b border-white/5">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
                    <i class="fa-solid fa-bus text-white text-xs"></i>
                </div>
                <span class="text-lg font-bold tracking-tight">SmartCommute</span>
            </div>
            <button onclick="closeMobileMenu()"
                class="w-10 h-10 rounded-full bg-white/5 flex items-center justify-center hover:bg-white/10 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <nav class="flex-1 p-5 sm:p-6 space-y-2 overflow-y-auto">
            @if (!Auth::user())
                <a href="{{ url('/register') }}" onclick="closeMobileMenu()"
                    class="flex items-center gap-4 px-4 py-4 rounded-2xl hover:bg-white/5 transition group">
                    <div
                        class="w-10 h-10 rounded-xl bg-blue-500/10 flex items-center justify-center group-hover:bg-blue-500/20 transition">
                        <i class="fa-solid fa-user-plus text-blue-400 text-sm"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-sm">Register</p>
                        <p class="text-[10px] text-gray-500">Create a new account</p>
                    </div>
                </a>
                <a href="{{ url('/login') }}" onclick="closeMobileMenu()"
                    class="flex items-center gap-4 px-4 py-4 rounded-2xl hover:bg-white/5 transition group">
                    <div
                        class="w-10 h-10 rounded-xl bg-purple-500/10 flex items-center justify-center group-hover:bg-purple-500/20 transition">
                        <i class="fa-solid fa-right-to-bracket text-purple-400 text-sm"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-sm">Log In</p>
                        <p class="text-[10px] text-gray-500">Access your account</p>
                    </div>
                </a>
                <a href="{{ route('map.guest') }}" onclick="closeMobileMenu()"
                    class="flex items-center gap-4 px-4 py-4 rounded-2xl hover:bg-white/5 transition group">
                    <div
                        class="w-10 h-10 rounded-xl bg-green-500/10 flex items-center justify-center group-hover:bg-green-500/20 transition">
                        <i class="fa-solid fa-map text-green-400 text-sm"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-sm">View Map</p>
                        <p class="text-[10px] text-gray-500">Explore live routes</p>
                    </div>
                </a>
            @endif

            @if (Auth::user())
                <a href="{{ route('map') }}" onclick="closeMobileMenu()"
                    class="flex items-center gap-4 px-4 py-4 rounded-2xl hover:bg-white/5 transition group">
                    <div
                        class="w-10 h-10 rounded-xl bg-blue-500/10 flex items-center justify-center group-hover:bg-blue-500/20 transition">
                        <i class="fa-solid fa-gauge-high text-blue-400 text-sm"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-sm">Map</p>
                        <i clas="fa-gauge-high text-blue-400 text-xs sm:text-sm"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-sm">Map</p>
                        <p class="text-[10px] text-gray-500">Your commute hub</p>
                    </div>
                </a>
            @endif
            <div class="pt-3 sm:pt-4">
                <div class="line-glow w-full mb-3 sm:mb-4"></div>
                <a href="#features" onclick="closeMobileMenu()"
                    class="block px-3 sm:px-4 py-2.5 sm:py-3 text-sm text-gray-400 hover:text-white transition">Features</a>
                <a href="#services" onclick="closeMobileMenu()"
                    class="block px-3 sm:px-4 py-2.5 sm:py-3 text-sm text-gray-400 hover:text-white transition">Services</a>
                <a href="#feedback" onclick="closeMobileMenu()"
                    class="block px-3 sm:px-4 py-2.5 sm:py-3 text-sm text-gray-400 hover:text-white transition">Feedback</a>
                <a href="#contacts" onclick="closeMobileMenu()"
                    class="block px-3 sm:px-4 py-2.5 sm:py-3 text-sm text-gray-400 hover:text-white transition">Contact</a>
            </div>
        </nav>

        <div class="p-4 sm:p-6 border-t border-white/5">
            <a href="{{ url('/register') }}" onclick="closeMobileMenu()"
                class="block w-full text-center bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 sm:py-3.5 rounded-2xl transition text-sm">
                Get Started Free
            </a>
        </div>
    </div>

    <!-- ==================== HERO SECTION ==================== -->
    <section
        class="relative min-h-[100svh] w-full hero-bg flex flex-col justify-between p-3 sm:p-5 md:p-8 lg:p-12 xl:p-16">

        <!-- Floating decorative orbs — scaled for mobile -->
        <div
            class="absolute top-16 right-4 sm:top-20 sm:right-10 w-40 h-40 sm:w-72 sm:h-72 bg-blue-500/10 rounded-full blur-[60px] sm:blur-[100px] animate-pulse-slow pointer-events-none">
        </div>
        <div class="absolute bottom-32 left-4 sm:bottom-40 sm:left-10 w-32 h-32 sm:w-56 sm:h-56 bg-purple-500/10 rounded-full blur-[50px] sm:blur-[80px] animate-pulse-slow pointer-events-none"
            style="animation-delay: 2s;"></div>

        <nav class="flex justify-between items-center w-full relative z-10">
            <div class="flex items-center gap-2 sm:gap-2.5 md:gap-3">
                <div
                    class="w-8 h-8 sm:w-9 sm:h-9 md:w-10 md:h-10 bg-blue-600 rounded-lg sm:rounded-xl flex items-center justify-center shadow-lg shadow-blue-600/30">
                    <i class="fa-solid fa-bus text-white text-xs sm:text-sm"></i>
                </div>
                <span class="text-base sm:text-lg md:text-xl lg:text-2xl font-bold tracking-tight">Smart<span
                        class="text-blue-400">Commute</span></span>
            </div>

            <!-- Desktop Nav -->
            @if (!Auth::user())
                <div
                    class="hidden md:flex items-center space-x-1 text-sm font-medium glass-card px-2 py-2 rounded-full">
                    <a href="{{ url('/register') }}"
                        class="px-5 py-2 rounded-full hover:bg-white/10 transition text-gray-300 hover:text-white">Register</a>
                    <a href="{{ url('/login') }}"
                        class="px-5 py-2 rounded-full hover:bg-white/10 transition text-gray-300 hover:text-white">Log
                        in</a>
                    <div class="w-px h-5 bg-white/10 mx-1"></div>
                    <a href="{{ route('map.guest') }}"
                        class="px-5 py-2 rounded-full hover:bg-white/10 transition text-gray-300 hover:text-white flex items-center gap-2">
                        <i class="fa-solid fa-map-location-dot text-xs text-blue-400"></i> View Map
                    </a>
                </div>
            @endif

            @if (Auth::user())
                @if (Auth::user()->roles->first()->name == 'admin' ||
                        Auth::user()->roles->first()->name == 'commuter' ||
                        Auth::user()->roles->first()->name == 'driver')
                    <div
                        class="hidden md:flex items-center space-x-1 text-sm font-medium glass-card px-2 py-2 rounded-full">
                        <a href="{{ route('map') }}"
                            class="px-5 py-2 rounded-full hover:bg-white/10 transition text-gray-300 hover:text-white flex items-center gap-2">
                            <i class="fa-solid fa-gauge-high text-xs text-blue-400"></i> Map
                        </a>
                    </div>
                @endif

                @if (Auth::user()->roles->first()->name == 'maintenance_manager' ||
                        Auth::user()->roles->first()->name == 'driver_manager')
                    <div
                        class="hidden md:flex items-center space-x-1 text-sm font-medium glass-card px-2 py-2 rounded-full">
                        <a href="{{ route('dashboard') }}"
                            class="px-5 py-2 rounded-full hover:bg-white/10 transition text-gray-300 hover:text-white flex items-center gap-2">
                            <i class="fa-solid fa-gauge-high text-xs text-blue-400"></i> Dashboard
                        </a>
                    </div>
                @endif
            @endif

            <!-- Hamburger -->
            <button id="hamburgerBtn" onclick="openMobileMenu()"
                class="hamburger md:hidden w-9 h-9 sm:w-10 sm:h-10 rounded-full bg-white/10 backdrop-blur-md border border-white/10 flex flex-col items-center justify-center gap-1.5">
                <span class="hamburger-line w-3.5 h-[1.5px] bg-white rounded-full"></span>
                <span class="hamburger-line w-3.5 h-[1.5px] bg-white rounded-full"></span>
                <span class="hamburger-line w-3.5 h-[1.5px] bg-white rounded-full"></span>
            </button>
        </nav>

        <!-- Hero Content -->
        <div class="max-w-5xl relative z-10 pt-4 sm:pt-8 md:pt-0">
            <h1
                class="hero-load-2 text-[1.75rem] sm:text-4xl md:text-5xl lg:text-7xl xl:text-8xl font-extrabold leading-[0.95] sm:leading-[1] mb-4 sm:mb-6 md:mb-8 tracking-tight">
                The Smartest Way <br class="hidden xs:block">
                To <span class="text-gradient">Optimize</span> Your <br class="hidden sm:block">
                Daily Commute
            </h1>

            <div
                class="hero-load-3 flex flex-wrap items-center gap-x-2 sm:gap-x-3 gap-y-1.5 sm:gap-y-2 text-sm sm:text-base md:text-lg lg:text-xl">
                <span class="font-semibold text-white">Swift,</span>
                <span class="font-semibold text-blue-400">Safe,</span>
                <span class="text-gray-400">and</span>
                <span class="font-semibold text-green-400">Affordable</span>
            </div>

            <!-- Stats row — visible on all screens, compact on mobile -->
            <div
                class="hero-load-4 flex items-center gap-4 sm:gap-6 md:gap-8 mt-6 sm:mt-8 md:mt-10 pt-4 sm:pt-6 md:pt-8 border-t border-white/10">
                <div>
                    <p class="text-lg sm:text-2xl md:text-3xl font-bold stat-counter text-white">12K<span
                            class="text-blue-400">+</span></p>
                    <p class="text-[8px] sm:text-[10px] uppercase tracking-widest text-gray-500 mt-0.5 sm:mt-1">Daily
                        Commuters</p>
                </div>
                <div class="w-px h-6 sm:h-8 md:h-10 bg-white/10"></div>
                <div>
                    <p class="text-lg sm:text-2xl md:text-3xl font-bold stat-counter text-white">98<span
                            class="text-green-400">%</span></p>
                    <p class="text-[8px] sm:text-[10px] uppercase tracking-widest text-gray-500 mt-0.5 sm:mt-1">On-Time
                        Rate</p>
                </div>
                <div class="w-px h-6 sm:h-8 md:h-10 bg-white/10"></div>
                <div>
                    <p class="text-lg sm:text-2xl md:text-3xl font-bold stat-counter text-white">45<span
                            class="text-purple-400">+</span></p>
                    <p class="text-[8px] sm:text-[10px] uppercase tracking-widest text-gray-500 mt-0.5 sm:mt-1">Active
                        Routes</p>
                </div>
            </div>
        </div>

        <!-- Bottom CTA -->
        <div
            class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4 sm:gap-6 relative z-10 mt-6 sm:mt-0">
            <a href="{{ url('/register') }}"
                class="group flex items-center gap-3 sm:gap-4 glass-card rounded-full pl-1.5 sm:pl-2 pr-4 sm:pr-6 md:pr-8 py-1.5 sm:py-2 hover:bg-white hover:text-black transition-all duration-500 hover:shadow-2xl hover:shadow-white/10">
                <div
                    class="bg-white text-black w-9 h-9 sm:w-10 sm:h-10 md:w-12 md:h-12 rounded-full flex items-center justify-center group-hover:bg-blue-600 group-hover:text-white transition-all duration-500">
                    <i class="fa-solid fa-arrow-right text-xs sm:text-sm"></i>
                </div>
                <span class="font-semibold uppercase tracking-widest text-[10px] sm:text-xs md:text-sm">Get
                    Started</span>
            </a>
        </div>

        <!-- Scroll indicator -->
        <div
            class="absolute bottom-4 sm:bottom-6 left-1/2 -translate-x-1/2 hidden md:flex flex-col items-center gap-2 hero-load-4">
            <span class="text-[9px] uppercase tracking-[0.3em] text-gray-600">Scroll</span>
            <div class="w-5 h-8 rounded-full border border-white/20 flex justify-center pt-1.5">
                <div class="w-1 h-2 bg-white/40 rounded-full animate-bounce"></div>
            </div>
        </div>
    </section>

    <!-- ==================== MARQUEE STRIP ==================== -->
    <div class="bg-[#0a0a0a] border-y border-white/5 py-3 sm:py-4 overflow-hidden">
        <div class="flex animate-marquee whitespace-nowrap">
            <span
                class="mx-4 sm:mx-8 text-[9px] sm:text-xs uppercase tracking-[0.2em] sm:tracking-[0.3em] text-gray-600 font-medium flex items-center gap-2 sm:gap-3"><i
                    class="fa-solid fa-circle text-[2px] sm:text-[3px] text-blue-500"></i> Real-Time GPS
                Tracking</span>
            <span
                class="mx-4 sm:mx-8 text-[9px] sm:text-xs uppercase tracking-[0.2em] sm:tracking-[0.3em] text-gray-600 font-medium flex items-center gap-2 sm:gap-3"><i
                    class="fa-solid fa-circle text-[2px] sm:text-[3px] text-purple-500"></i> Contactless
                Payments</span>
            <span
                class="mx-4 sm:mx-8 text-[9px] sm:text-xs uppercase tracking-[0.2em] sm:tracking-[0.3em] text-gray-600 font-medium flex items-center gap-2 sm:gap-3"><i
                    class="fa-solid fa-circle text-[2px] sm:text-[3px] text-cyan-500"></i> Fleet Analytics</span>
            <span
                class="mx-4 sm:mx-8 text-[9px] sm:text-xs uppercase tracking-[0.2em] sm:tracking-[0.3em] text-gray-600 font-medium flex items-center gap-2 sm:gap-3"><i
                    class="fa-solid fa-circle text-[2px] sm:text-[3px] text-pink-500"></i> Passenger Insights</span>
            <span
                class="mx-4 sm:mx-8 text-[9px] sm:text-xs uppercase tracking-[0.2em] sm:tracking-[0.3em] text-gray-600 font-medium flex items-center gap-2 sm:gap-3"><i
                    class="fa-solid fa-circle text-[2px] sm:text-[3px] text-blue-500"></i> Real-Time GPS
                Tracking</span>
            <span
                class="mx-4 sm:mx-8 text-[9px] sm:text-xs uppercase tracking-[0.2em] sm:tracking-[0.3em] text-gray-600 font-medium flex items-center gap-2 sm:gap-3"><i
                    class="fa-solid fa-circle text-[2px] sm:text-[3px] text-purple-500"></i> Contactless
                Payments</span>
            <span
                class="mx-4 sm:mx-8 text-[9px] sm:text-xs uppercase tracking-[0.2em] sm:tracking-[0.3em] text-gray-600 font-medium flex items-center gap-2 sm:gap-3"><i
                    class="fa-solid fa-circle text-[2px] sm:text-[3px] text-cyan-500"></i> Fleet Analytics</span>
            <!-- Duplicate for seamless loop -->
            <span
                class="mx-4 sm:mx-8 text-[9px] sm:text-xs uppercase tracking-[0.2em] sm:tracking-[0.3em] text-gray-600 font-medium flex items-center gap-2 sm:gap-3"><i
                    class="fa-solid fa-circle text-[2px] sm:text-[3px] text-blue-500"></i> Real-Time GPS
                Tracking</span>
            <span
                class="mx-4 sm:mx-8 text-[9px] sm:text-xs uppercase tracking-[0.2em] sm:tracking-[0.3em] text-gray-600 font-medium flex items-center gap-2 sm:gap-3"><i
                    class="fa-solid fa-circle text-[2px] sm:text-[3px] text-purple-500"></i> Contactless
                Payments</span>
            <span
                class="mx-4 sm:mx-8 text-[9px] sm:text-xs uppercase tracking-[0.2em] sm:tracking-[0.3em] text-gray-600 font-medium flex items-center gap-2 sm:gap-3"><i
                    class="fa-solid fa-circle text-[2px] sm:text-[3px] text-cyan-500"></i> Fleet Analytics</span>
            <span
                class="mx-4 sm:mx-8 text-[9px] sm:text-xs uppercase tracking-[0.2em] sm:tracking-[0.3em] text-gray-600 font-medium flex items-center gap-2 sm:gap-3"><i
                    class="fa-solid fa-circle text-[2px] sm:text-[3px] text-pink-500"></i> Passenger Insights</span>
        </div>
    </div>

    <!-- ==================== FEATURES SECTION ==================== -->
    <section id="features" class="py-10 sm:py-16 md:py-24 lg:py-32 px-3 sm:px-5 md:px-8 bg-[#050505]">
        <div class="max-w-7xl mx-auto flex flex-col lg:flex-row items-center gap-8 sm:gap-10 md:gap-12 lg:gap-20">

            <!-- Image Side -->
            <div class="lg:w-1/2 relative w-full reveal">
                <div
                    class="absolute -inset-3 sm:-inset-6 bg-gradient-to-br from-blue-600/20 to-purple-600/10 rounded-2xl sm:rounded-[3rem] blur-[40px] sm:blur-[60px]">
                </div>

                <div class="relative">
                    <div
                        class="glass-card p-2 sm:p-3 md:p-4 rounded-xl sm:rounded-[2rem] md:rounded-[3rem] overflow-hidden">
                        <img src="https://img.freepik.com/free-vector/data-informational-infographic-statistic_24877-51525.jpg"
                            alt="System Analytics"
                            class="rounded-lg sm:rounded-[1.5rem] md:rounded-[2.5rem] w-full grayscale hover:grayscale-0 transition-all duration-700 hover:scale-[1.02]">
                    </div>
                </div>
            </div>

            <!-- Text Side -->
            <div class="lg:w-1/2 space-y-5 sm:space-y-6 md:space-y-8">
                <div class="space-y-3 sm:space-y-4 reveal">
                    <div
                        class="inline-flex items-center gap-1.5 sm:gap-2 bg-blue-500/10 px-2.5 sm:px-3 py-1 sm:py-1.5 rounded-full">
                        <span class="w-1 h-1 sm:w-1.5 sm:h-1.5 bg-blue-400 rounded-full"></span>
                        <span
                            class="text-blue-400 text-[9px] sm:text-[10px] font-bold uppercase tracking-[0.15em] sm:tracking-[0.2em]">SmartCommute</span>
                    </div>
                    <h2
                        class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl xl:text-6xl font-extrabold leading-[1.05] sm:leading-[1.05] tracking-tight">
                        Your Commute <br>
                        <span class="text-white/20 italic font-light">Made Smarter</span>
                    </h2>
                    <p class="text-gray-400 text-xs sm:text-sm md:text-base leading-relaxed max-w-xl">
                        We believe in the power of real-time data. Our analytics-driven approach allows us to make
                        informed decisions and optimize your commute for maximum efficiency.
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-3 sm:gap-4 md:gap-5">
                    <div
                        class="glass-card p-3 sm:p-4 md:p-5 lg:p-6 rounded-xl sm:rounded-2xl reveal reveal-delay-1 group cursor-default">
                        <div
                            class="w-8 h-8 sm:w-9 sm:h-9 md:w-10 md:h-10 bg-blue-500/10 rounded-lg sm:rounded-xl flex items-center justify-center mb-2 sm:mb-3 md:mb-4 group-hover:bg-blue-600/20 group-hover:scale-110 transition-all duration-500">
                            <i class="fa-solid fa-chart-line text-blue-400 text-xs sm:text-sm"></i>
                        </div>
                        <h4 class="font-bold mb-1 sm:mb-2 text-xs sm:text-sm md:text-base">Data-Driven</h4>
                        <p class="text-[10px] sm:text-[11px] md:text-xs text-gray-500 leading-relaxed">Optimization
                            based on thousands of daily commute patterns.</p>
                    </div>

                    <div
                        class="glass-card p-3 sm:p-4 md:p-5 lg:p-6 rounded-xl sm:rounded-2xl reveal reveal-delay-2 group cursor-default">
                        <div
                            class="w-8 h-8 sm:w-9 sm:h-9 md:w-10 md:h-10 bg-green-500/10 rounded-lg sm:rounded-xl flex items-center justify-center mb-2 sm:mb-3 md:mb-4 group-hover:bg-green-600/20 group-hover:scale-110 transition-all duration-500">
                            <i class="fa-solid fa-bolt text-green-400 text-xs sm:text-sm"></i>
                        </div>
                        <h4 class="font-bold mb-1 sm:mb-2 text-xs sm:text-sm md:text-base">Real-Time</h4>
                        <p class="text-[10px] sm:text-[11px] md:text-xs text-gray-500 leading-relaxed">Instant updates
                            on PUJ locations and traffic delays.</p>
                    </div>

                    <div
                        class="glass-card p-3 sm:p-4 md:p-5 lg:p-6 rounded-xl sm:rounded-2xl reveal reveal-delay-3 group cursor-default">
                        <div
                            class="w-8 h-8 sm:w-9 sm:h-9 md:w-10 md:h-10 bg-purple-500/10 rounded-lg sm:rounded-xl flex items-center justify-center mb-2 sm:mb-3 md:mb-4 group-hover:bg-purple-600/20 group-hover:scale-110 transition-all duration-500">
                            <i class="fa-solid fa-shield text-purple-400 text-xs sm:text-sm"></i>
                        </div>
                        <h4 class="font-bold mb-1 sm:mb-2 text-xs sm:text-sm md:text-base">Secure</h4>
                        <p class="text-[10px] sm:text-[11px] md:text-xs text-gray-500 leading-relaxed">End-to-end
                            encrypted payments and private data handling.</p>
                    </div>

                    <div
                        class="glass-card p-3 sm:p-4 md:p-5 lg:p-6 rounded-xl sm:rounded-2xl reveal reveal-delay-4 group cursor-default">
                        <div
                            class="w-8 h-8 sm:w-9 sm:h-9 md:w-10 md:h-10 bg-orange-500/10 rounded-lg sm:rounded-xl flex items-center justify-center mb-2 sm:mb-3 md:mb-4 group-hover:bg-orange-600/20 group-hover:scale-110 transition-all duration-500">
                            <i class="fa-solid fa-leaf text-orange-400 text-xs sm:text-sm"></i>
                        </div>
                        <h4 class="font-bold mb-1 sm:mb-2 text-xs sm:text-sm md:text-base">Eco-Friendly</h4>
                        <p class="text-[10px] sm:text-[11px] md:text-xs text-gray-500 leading-relaxed">Reduced carbon
                            footprint through optimized route planning.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ==================== SERVICES SECTION ==================== -->
    <section id="services"
        class="py-10 sm:py-16 md:py-24 lg:py-32 px-3 sm:px-5 md:px-8 bg-[#050505] relative overflow-hidden">
        <div
            class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[300px] h-[200px] sm:w-[600px] sm:h-[400px] bg-blue-600/5 blur-[80px] sm:blur-[120px] rounded-full pointer-events-none">
        </div>

        <div class="max-w-7xl mx-auto relative z-10">
            <div class="text-center mb-8 sm:mb-12 md:mb-16 space-y-3 sm:space-y-4 reveal">
                <div
                    class="inline-flex items-center gap-1.5 sm:gap-2 bg-blue-500/10 px-2.5 sm:px-3 py-1 sm:py-1.5 rounded-full">
                    <span class="w-1 h-1 sm:w-1.5 sm:h-1.5 bg-blue-400 rounded-full"></span>
                    <span
                        class="text-blue-400 text-[9px] sm:text-[10px] font-bold uppercase tracking-[0.15em] sm:tracking-[0.2em]">SmartCommute
                        System</span>
                </div>
                <h2 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-extrabold tracking-tight">
                    Comprehensive Commute <span class="text-white/20 italic font-light">Solutions</span>
                </h2>
                <p class="text-gray-500 max-w-2xl mx-auto text-[11px] sm:text-xs md:text-sm px-2 sm:px-4">Everything
                    you need to navigate your daily journey with precision and ease.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 md:gap-5">

                <!-- Card 1: Live GPS Tracking -->
                <div
                    class="service-card glass-card p-4 sm:p-5 md:p-6 lg:p-8 rounded-xl sm:rounded-[1.5rem] md:rounded-[2rem] transition-all duration-500 group reveal reveal-delay-1">
                    <div
                        class="w-10 h-10 sm:w-12 sm:h-12 md:w-14 md:h-14 bg-blue-500/10 rounded-xl sm:rounded-2xl flex items-center justify-center mb-3 sm:mb-4 md:mb-5 lg:mb-6 group-hover:scale-110 group-hover:bg-blue-500/20 transition-all duration-500">
                        <i class="fa-solid fa-location-crosshairs text-blue-400 text-sm sm:text-lg md:text-xl"></i>
                    </div>
                    <h4 class="text-sm sm:text-base md:text-lg font-bold mb-2 sm:mb-3">Live GPS Tracking</h4>
                    <p
                        class="text-[10px] sm:text-[11px] md:text-xs text-gray-500 leading-relaxed mb-3 sm:mb-4 md:mb-5 lg:mb-6">
                        Track your PUJ in real-time with meter-perfect precision. Never miss a ride or wait in the rain
                        again.</p>
                    <a
                        class="text-[9px] sm:text-[10px] font-bold uppercase tracking-widest text-blue-400 hover:text-white transition flex items-center gap-1.5 sm:gap-2 group/link">
                        Live Update <i
                            class="fa-solid fa-arrow-right text-[7px] sm:text-[8px] group-hover/link:translate-x-1 transition-transform"></i>
                    </a>
                </div>

                <!-- Card 2: Time Keeping -->
                <div
                    class="service-card glass-card p-4 sm:p-5 md:p-6 lg:p-8 rounded-xl sm:rounded-[1.5rem] md:rounded-[2rem] transition-all duration-500 group reveal reveal-delay-2">
                    <div
                        class="w-10 h-10 sm:w-12 sm:h-12 md:w-14 md:h-14 bg-purple-500/10 rounded-xl sm:rounded-2xl flex items-center justify-center mb-3 sm:mb-4 md:mb-5 lg:mb-6 group-hover:scale-110 group-hover:bg-purple-500/20 transition-all duration-500">
                        <i class="fa-solid fa-clock text-purple-400 text-sm sm:text-lg md:text-xl"></i>
                    </div>
                    <h4 class="text-sm sm:text-base md:text-lg font-bold mb-2 sm:mb-3">Time Keeping</h4>
                    <p
                        class="text-[10px] sm:text-[11px] md:text-xs text-gray-500 leading-relaxed mb-3 sm:mb-4 md:mb-5 lg:mb-6">
                        Automated logs for driver shifts and attendance ensuring compliance and safety.</p>
                    <a
                        class="text-[9px] sm:text-[10px] font-bold uppercase tracking-widest text-purple-400 hover:text-white transition flex items-center gap-1.5 sm:gap-2 group/link">
                        Compliance <i
                            class="fa-solid fa-arrow-right text-[7px] sm:text-[8px] group-hover/link:translate-x-1 transition-transform"></i>
                    </a>
                </div>

                <!-- Card 3: Contactless Pay -->
                <div
                    class="service-card glass-card p-4 sm:p-5 md:p-6 lg:p-8 rounded-xl sm:rounded-[1.5rem] md:rounded-[2rem] transition-all duration-500 group reveal reveal-delay-3">
                    <div
                        class="w-10 h-10 sm:w-12 sm:h-12 md:w-14 md:h-14 bg-green-500/10 rounded-xl sm:rounded-2xl flex items-center justify-center mb-3 sm:mb-4 md:mb-5 lg:mb-6 group-hover:scale-110 group-hover:bg-green-500/20 transition-all duration-500">
                        <i class="fa-solid fa-credit-card text-green-400 text-sm sm:text-lg md:text-xl"></i>
                    </div>
                    <h4 class="text-sm sm:text-base md:text-lg font-bold mb-2 sm:mb-3">Contactless Pay</h4>
                    <p
                        class="text-[10px] sm:text-[11px] md:text-xs text-gray-500 leading-relaxed mb-3 sm:mb-4 md:mb-5 lg:mb-6">
                        Swift, secure, and cash-free. Manage your digital wallet and pay for rides with a single tap or
                        scan.</p>
                    <a
                        class="text-[9px] sm:text-[10px] font-bold uppercase tracking-widest text-green-400 hover:text-white transition flex items-center gap-1.5 sm:gap-2 group/link">
                        Efficiency <i
                            class="fa-solid fa-arrow-right text-[7px] sm:text-[8px] group-hover/link:translate-x-1 transition-transform"></i>
                    </a>
                </div>

                <!-- Card 4: PUJ Maintenance Records -->
                <div
                    class="service-card glass-card p-4 sm:p-5 md:p-6 lg:p-8 rounded-xl sm:rounded-[1.5rem] md:rounded-[2rem] transition-all duration-500 group reveal reveal-delay-4">
                    <div
                        class="w-10 h-10 sm:w-12 sm:h-12 md:w-14 md:h-14 bg-orange-500/10 rounded-xl sm:rounded-2xl flex items-center justify-center mb-3 sm:mb-4 md:mb-5 lg:mb-6 group-hover:scale-110 group-hover:bg-orange-500/20 transition-all duration-500">
                        <i class="fa-solid fa-wrench text-orange-400 text-sm sm:text-lg md:text-xl"></i>
                    </div>
                    <h4 class="text-sm sm:text-base md:text-lg font-bold mb-2 sm:mb-3">PUJ Maintenance Records</h4>
                    <p
                        class="text-[10px] sm:text-[11px] md:text-xs text-gray-500 leading-relaxed mb-3 sm:mb-4 md:mb-5 lg:mb-6">
                        Digital logs for repairs, inspections, and service history to keep the fleet in top condition.
                    </p>
                    <a
                        class="text-[9px] sm:text-[10px] font-bold uppercase tracking-widest text-orange-400 hover:text-white transition flex items-center gap-1.5 sm:gap-2 group/link">
                        Fleet Health <i
                            class="fa-solid fa-arrow-right text-[7px] sm:text-[8px] group-hover/link:translate-x-1 transition-transform"></i>
                    </a>
                </div>

            </div>
        </div>
    </section>

    <!-- ==================== FEEDBACK SECTION ==================== -->
    <section id="feedback" class="py-10 sm:py-16 md:py-24 lg:py-32 px-3 sm:px-5 md:px-8 bg-[#050505] relative">
        <div class="absolute top-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-white/5 to-transparent">
        </div>

        <div class="max-w-7xl mx-auto relative z-10">
            <div class="text-center mb-8 sm:mb-12 md:mb-16 space-y-3 sm:space-y-4 reveal">
                <div
                    class="inline-flex items-center gap-1.5 sm:gap-2 bg-blue-500/10 px-2.5 sm:px-3 py-1 sm:py-1.5 rounded-full">
                    <span class="w-1 h-1 sm:w-1.5 sm:h-1.5 bg-blue-400 rounded-full"></span>
                    <span
                        class="text-blue-400 text-[9px] sm:text-[10px] font-bold uppercase tracking-[0.15em] sm:tracking-[0.2em]">Feedbacks</span>
                </div>
                <h2 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-extrabold tracking-tight">
                    What Our <span class="text-white/20 italic font-light">Users Say</span>
                </h2>
                <p class="text-gray-500 max-w-2xl mx-auto text-[11px] sm:text-xs md:text-sm px-2 sm:px-4">See how
                    SmartCommute is changing the daily journey for thousands of people.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-5 md:gap-6">

                <div
                    class="testimonial-card glass-card p-5 sm:p-6 md:p-7 lg:p-10 rounded-xl sm:rounded-2xl md:rounded-[2rem] flex flex-col items-center text-center relative group reveal reveal-delay-1">
                    <div
                        class="w-11 h-11 sm:w-12 sm:h-12 md:w-14 md:h-14 lg:w-16 lg:h-16 rounded-full bg-gradient-to-br from-blue-500/20 to-blue-600/5 border border-blue-500/20 flex items-center justify-center mb-3 sm:mb-4 md:mb-5 group-hover:border-blue-500/50 group-hover:scale-105 transition-all duration-500">
                        <i class="fa-solid fa-user text-base sm:text-lg md:text-xl lg:text-2xl text-blue-400"></i>
                    </div>

                    <div class="flex space-x-0.5 mb-3 sm:mb-4 text-yellow-500 text-[10px] sm:text-xs">
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                    </div>

                    <p
                        class="text-gray-400 text-[11px] sm:text-xs md:text-sm italic leading-relaxed mb-4 sm:mb-5 md:mb-7 lg:mb-8">
                        "The real-time tracking is a lifesaver. I used to wait 20 minutes at the stop, but now I time my
                        walk perfectly. The digital wallet makes boarding so much faster!"
                    </p>

                    <div class="mt-auto">
                        <h5 class="font-bold text-white text-xs sm:text-sm">Karl Campoy</h5>
                        <p
                            class="text-[8px] sm:text-[9px] md:text-[10px] uppercase tracking-widest text-blue-400 font-bold mt-0.5 sm:mt-1">
                            Daily Commuter</p>
                    </div>
                </div>

                <div
                    class="testimonial-card glass-card p-5 sm:p-6 md:p-7 lg:p-10 rounded-xl sm:rounded-2xl md:rounded-[2rem] flex flex-col items-center text-center relative group border-blue-500/15 glow-blue reveal reveal-delay-2">
                    <div
                        class="absolute top-3 right-3 sm:top-4 sm:right-4 bg-blue-500/10 px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-full">
                        <span
                            class="text-[7px] sm:text-[8px] font-bold uppercase tracking-wider text-blue-400">Featured</span>
                    </div>
                    <div
                        class="w-11 h-11 sm:w-12 sm:h-12 md:w-14 md:h-14 lg:w-16 lg:h-16 rounded-full bg-gradient-to-br from-purple-500/20 to-purple-600/5 border border-purple-500/20 flex items-center justify-center mb-3 sm:mb-4 md:mb-5 group-hover:border-purple-500/50 group-hover:scale-105 transition-all duration-500">
                        <i class="fa-solid fa-user text-base sm:text-lg md:text-xl lg:text-2xl text-purple-400"></i>
                    </div>

                    <div class="flex space-x-0.5 mb-3 sm:mb-4 text-yellow-500 text-[10px] sm:text-xs">
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                    </div>

                    <p
                        class="text-gray-400 text-[11px] sm:text-xs md:text-sm italic leading-relaxed mb-4 sm:mb-5 md:mb-7 lg:mb-8">
                        "Managing my routes and schedule through the app has reduced my stress significantly. The
                        navigation is optimized for PUJ, which is exactly what we needed."
                    </p>

                    <div class="mt-auto">
                        <h5 class="font-bold text-white text-xs sm:text-sm">Daniel Padilla</h5>
                        <p
                            class="text-[8px] sm:text-[9px] md:text-[10px] uppercase tracking-widest text-purple-400 font-bold mt-0.5 sm:mt-1">
                            Transit Driver</p>
                    </div>
                </div>

                <div
                    class="testimonial-card glass-card p-5 sm:p-6 md:p-7 lg:p-10 rounded-xl sm:rounded-2xl md:rounded-[2rem] flex flex-col items-center text-center relative group reveal reveal-delay-3">
                    <div
                        class="w-11 h-11 sm:w-12 sm:h-12 md:w-14 md:h-14 lg:w-16 lg:h-16 rounded-full bg-gradient-to-br from-green-500/20 to-green-600/5 border border-green-500/20 flex items-center justify-center mb-3 sm:mb-4 md:mb-5 group-hover:border-green-500/50 group-hover:scale-105 transition-all duration-500">
                        <i class="fa-solid fa-user text-base sm:text-lg md:text-xl lg:text-2xl text-green-400"></i>
                    </div>

                    <div class="flex space-x-0.5 mb-3 sm:mb-4 text-yellow-500 text-[10px] sm:text-xs">
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star-half-stroke"></i>
                    </div>

                    <p
                        class="text-gray-400 text-[11px] sm:text-xs md:text-sm italic leading-relaxed mb-4 sm:mb-5 md:mb-7 lg:mb-8">
                        "From an administrative standpoint, the analytics dashboard provides insights we never had
                        before. We've optimized fuel consumption by 15% across the fleet."
                    </p>

                    <div class="mt-auto">
                        <h5 class="font-bold text-white text-xs sm:text-sm">Juswa Garcia</h5>
                        <p
                            class="text-[8px] sm:text-[9px] md:text-[10px] uppercase tracking-widest text-green-400 font-bold mt-0.5 sm:mt-1">
                            Fleet Manager</p>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ==================== CONTACT SECTION ==================== -->
    <section id="contacts" class="py-10 sm:py-16 md:py-24 lg:py-32 px-3 sm:px-5 md:px-8 bg-[#050505] relative">
        <div class="absolute top-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-white/5 to-transparent">
        </div>
        <div
            class="absolute bottom-1/3 right-0 w-48 h-48 sm:w-96 sm:h-96 bg-blue-600/5 blur-[80px] sm:blur-[120px] rounded-full pointer-events-none">
        </div>

        <div class="max-w-6xl mx-auto relative z-10">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 sm:gap-10 md:gap-16 items-center">

                <div class="space-y-5 sm:space-y-6 md:space-y-8 reveal">
                    <div>
                        <div
                            class="inline-flex items-center gap-1.5 sm:gap-2 bg-blue-500/10 px-2.5 sm:px-3 py-1 sm:py-1.5 rounded-full mb-4 sm:mb-6">
                            <span class="w-1 h-1 sm:w-1.5 sm:h-1.5 bg-blue-400 rounded-full"></span>
                            <span
                                class="text-blue-400 text-[9px] sm:text-[10px] font-bold uppercase tracking-[0.15em] sm:tracking-[0.2em]">Contact
                                Us</span>
                        </div>
                        <h2
                            class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl xl:text-6xl font-extrabold leading-[1.05] tracking-tight mb-3 sm:mb-4">
                            Get in <span class="text-gradient">Touch</span>
                        </h2>
                        <p class="text-gray-400 text-xs sm:text-sm md:text-base max-w-md leading-relaxed">
                            Have questions about our routes or pricing? Our team is here to help you optimize your
                            commute.
                        </p>
                    </div>

                    <div class="space-y-3 sm:space-y-4 md:space-y-5">
                        <div class="flex items-center gap-3 sm:gap-4 group">
                            <div
                                class="w-10 h-10 sm:w-11 sm:h-11 md:w-12 md:h-12 bg-white/5 border border-white/10 rounded-xl sm:rounded-2xl flex items-center justify-center text-sm sm:text-base md:text-lg group-hover:bg-blue-500/10 group-hover:border-blue-500/30 transition-all duration-300 shrink-0">
                                <i class="fa-solid fa-envelope text-blue-400"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[9px] sm:text-[10px] text-gray-500 uppercase tracking-widest mb-0.5">
                                    Email Us</p>
                                <p class="text-xs sm:text-sm md:text-base font-medium truncate">
                                    support@smartcommute.com</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 sm:gap-4 group">
                            <div
                                class="w-10 h-10 sm:w-11 sm:h-11 md:w-12 md:h-12 bg-white/5 border border-white/10 rounded-xl sm:rounded-2xl flex items-center justify-center text-sm sm:text-base md:text-lg group-hover:bg-green-500/10 group-hover:border-green-500/30 transition-all duration-300 shrink-0">
                                <i class="fa-solid fa-phone text-green-400"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[9px] sm:text-[10px] text-gray-500 uppercase tracking-widest mb-0.5">
                                    Call Us</p>
                                <p class="text-xs sm:text-sm md:text-base font-medium">+1 (555) 000-1234</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 sm:gap-4 group">
                            <div
                                class="w-10 h-10 sm:w-11 sm:h-11 md:w-12 md:h-12 bg-white/5 border border-white/10 rounded-xl sm:rounded-2xl flex items-center justify-center text-sm sm:text-base md:text-lg group-hover:bg-purple-500/10 group-hover:border-purple-500/30 transition-all duration-300 shrink-0">
                                <i class="fa-solid fa-location-dot text-purple-400"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[9px] sm:text-[10px] text-gray-500 uppercase tracking-widest mb-0.5">
                                    Visit Us</p>
                                <p class="text-xs sm:text-sm md:text-base font-medium">123 Transit Ave, Metro City</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="glass-card p-4 sm:p-5 md:p-6 lg:p-8 md:p-10 rounded-xl sm:rounded-[1.5rem] md:rounded-[2rem] reveal reveal-delay-2">
                    <form action="#" class="space-y-3 sm:space-y-4 md:space-y-5">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                            <div class="space-y-1.5 sm:space-y-2">
                                <label
                                    class="text-[9px] sm:text-[10px] uppercase tracking-widest text-gray-500 ml-1 font-medium">First
                                    Name</label>
                                <input type="text" placeholder="John"
                                    class="w-full bg-white/5 border border-white/10 rounded-lg sm:rounded-xl px-3 sm:px-4 py-2.5 sm:py-3 text-xs sm:text-sm transition-all duration-300">
                            </div>
                            <div class="space-y-1.5 sm:space-y-2">
                                <label
                                    class="text-[9px] sm:text-[10px] uppercase tracking-widest text-gray-500 ml-1 font-medium">Last
                                    Name</label>
                                <input type="text" placeholder="Doe"
                                    class="w-full bg-white/5 border border-white/10 rounded-lg sm:rounded-xl px-3 sm:px-4 py-2.5 sm:py-3 text-xs sm:text-sm transition-all duration-300">
                            </div>
                        </div>

                        <div class="space-y-1.5 sm:space-y-2">
                            <label
                                class="text-[9px] sm:text-[10px] uppercase tracking-widest text-gray-500 ml-1 font-medium">Email</label>
                            <input type="email" placeholder="john@example.com"
                                class="w-full bg-white/5 border border-white/10 rounded-lg sm:rounded-xl px-3 sm:px-4 py-2.5 sm:py-3 text-xs sm:text-sm transition-all duration-300">
                        </div>

                        <div class="space-y-1.5 sm:space-y-2">
                            <label
                                class="text-[9px] sm:text-[10px] uppercase tracking-widest text-gray-500 ml-1 font-medium">Message</label>
                            <textarea rows="4" placeholder="How can we help?"
                                class="w-full bg-white/5 border border-white/10 rounded-lg sm:rounded-xl px-3 sm:px-4 py-2.5 sm:py-3 text-xs sm:text-sm transition-all duration-300 resize-none"></textarea>
                        </div>

                        <button type="button" id="sendBtn"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 sm:py-3.5 md:py-4 rounded-lg sm:rounded-xl transition-all duration-300 transform active:scale-[0.98] uppercase tracking-widest text-[9px] sm:text-[10px] md:text-xs flex items-center justify-center gap-2">
                            <span id="sendText">Send Message</span>
                            <i class="fa-solid fa-paper-plane text-[8px] sm:text-[10px]"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- ==================== FOOTER ==================== -->
    <footer class="bg-[#050505] border-t border-white/5 py-8 sm:py-12 md:py-16 px-3 sm:px-5 md:px-8">
        <div class="max-w-7xl mx-auto">
            <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-4 gap-6 sm:gap-8 md:gap-12 mb-8 sm:mb-12">
                <!-- Brand -->
                <div class="col-span-2 sm:col-span-2 md:col-span-1">
                    <div class="flex items-center gap-2 mb-3 sm:mb-4">
                        <div class="w-7 h-7 sm:w-8 sm:h-8 bg-blue-600 rounded-lg flex items-center justify-center">
                            <i class="fa-solid fa-bus text-white text-[10px] sm:text-xs"></i>
                        </div>
                        <span class="text-sm sm:text-base md:text-lg font-bold tracking-tight">Smart<span
                                class="text-blue-400">Commute</span></span>
                    </div>
                    <p class="text-[10px] sm:text-xs text-gray-500 leading-relaxed max-w-xs">Optimizing daily commutes
                        with real-time data, contactless payments, and intelligent route planning.</p>
                </div>

                <!-- Links -->
                <div>
                    <h6
                        class="text-[9px] sm:text-[10px] uppercase tracking-widest text-gray-400 font-bold mb-3 sm:mb-4">
                        Product</h6>
                    <ul class="space-y-1.5 sm:space-y-2">
                        <li><a href="#features"
                                class="text-[11px] sm:text-xs text-gray-500 hover:text-white transition">Features</a>
                        </li>
                        <li><a href="#services"
                                class="text-[11px] sm:text-xs text-gray-500 hover:text-white transition">Services</a>
                        </li>
                        <li><a href="#"
                                class="text-[11px] sm:text-xs text-gray-500 hover:text-white transition">Pricing</a>
                        </li>
                    </ul>
                </div>

                <div>
                    <h6
                        class="text-[9px] sm:text-[10px] uppercase tracking-widest text-gray-400 font-bold mb-3 sm:mb-4">
                        Company</h6>
                    <ul class="space-y-1.5 sm:space-y-2">
                        <li><a href="#"
                                class="text-[11px] sm:text-xs text-gray-500 hover:text-white transition">About</a></li>
                        <li><a href="#contacts"
                                class="text-[11px] sm:text-xs text-gray-500 hover:text-white transition">Contact</a>
                        </li>
                        <li><a href="#"
                                class="text-[11px] sm:text-xs text-gray-500 hover:text-white transition">Careers</a>
                        </li>
                    </ul>
                </div>

                <div class="col-span-2 sm:col-span-1">
                    <h6
                        class="text-[9px] sm:text-[10px] uppercase tracking-widest text-gray-400 font-bold mb-3 sm:mb-4">
                        Legal</h6>
                    <ul class="space-y-1.5 sm:space-y-2">
                        <li><a href="#"
                                class="text-[11px] sm:text-xs text-gray-500 hover:text-white transition">Privacy</a>
                        </li>
                        <li><a href="#"
                                class="text-[11px] sm:text-xs text-gray-500 hover:text-white transition">Terms</a></li>
                    </ul>
                </div>
            </div>

            <div
                class="border-t border-white/5 pt-6 sm:pt-8 flex flex-col sm:flex-row justify-between items-center gap-3 sm:gap-4">
                <p class="text-[9px] sm:text-[10px] text-gray-600">&copy; 2025 SmartCommute. All rights reserved.</p>
                <div class="flex items-center gap-3 sm:gap-4">
                    <a href="#"
                        class="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-white/5 flex items-center justify-center hover:bg-white/10 transition">
                        <i class="fa-brands fa-facebook-f text-[10px] sm:text-xs text-gray-400"></i>
                    </a>
                    <a href="#"
                        class="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-white/5 flex items-center justify-center hover:bg-white/10 transition">
                        <i class="fa-brands fa-twitter text-[10px] sm:text-xs text-gray-400"></i>
                    </a>
                    <a href="#"
                        class="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-white/5 flex items-center justify-center hover:bg-white/10 transition">
                        <i class="fa-brands fa-github text-[10px] sm:text-xs text-gray-400"></i>
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <!-- ==================== SCRIPTS ==================== -->
    <script>
        // Mobile Menu
        function openMobileMenu() {
            document.getElementById('mobileMenu').classList.add('open');
            document.getElementById('mobileOverlay').classList.add('open');
            document.getElementById('hamburgerBtn').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeMobileMenu() {
            document.getElementById('mobileMenu').classList.remove('open');
            document.getElementById('mobileOverlay').classList.remove('open');
            document.getElementById('hamburgerBtn').classList.remove('active');
            document.body.style.overflow = '';
        }

        // Scroll Reveal
        const revealElements = document.querySelectorAll('.reveal');

        const revealObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('active');
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        });

        revealElements.forEach(el => revealObserver.observe(el));

        // Send button interaction
        const sendBtn = document.getElementById('sendBtn');
        const sendText = document.getElementById('sendText');

        if (sendBtn) {
            sendBtn.addEventListener('click', function() {
                sendText.textContent = 'Sending...';
                sendBtn.disabled = true;
                sendBtn.classList.add('opacity-70');

                setTimeout(() => {
                    sendText.textContent = 'Message Sent!';
                    sendBtn.classList.remove('opacity-70');
                    sendBtn.classList.add('bg-green-600');
                    sendBtn.classList.remove('bg-blue-600', 'hover:bg-blue-700');

                    setTimeout(() => {
                        sendText.textContent = 'Send Message';
                        sendBtn.disabled = false;
                        sendBtn.classList.remove('bg-green-600');
                        sendBtn.classList.add('bg-blue-600', 'hover:bg-blue-700');
                    }, 2500);
                }, 1500);
            });
        }

        // Close mobile menu on resize to desktop
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 768) {
                closeMobileMenu();
            }
        });
    </script>
</body>

</html>
