<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Commute System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
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
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        @keyframes pulse-slow {
            0%, 100% { opacity: 0.4; }
            50% { opacity: 0.8; }
        }

        @keyframes slide-up {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slide-in-left {
            from { opacity: 0; transform: translateX(-40px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes slide-in-right {
            from { opacity: 0; transform: translateX(40px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes fade-in {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes scale-in {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }

        @keyframes marquee {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }

        .animate-float { animation: float 6s ease-in-out infinite; }
        .animate-pulse-slow { animation: pulse-slow 4s ease-in-out infinite; }
        .animate-marquee { animation: marquee 30s linear infinite; }

        .reveal {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .reveal.active {
            opacity: 1;
            transform: translateY(0);
        }

        .reveal-delay-1 { transition-delay: 0.1s; }
        .reveal-delay-2 { transition-delay: 0.2s; }
        .reveal-delay-3 { transition-delay: 0.3s; }
        .reveal-delay-4 { transition-delay: 0.4s; }

        .hero-load-1 { animation: slide-up 1s cubic-bezier(0.16, 1, 0.3, 1) 0.2s both; }
        .hero-load-2 { animation: slide-up 1s cubic-bezier(0.16, 1, 0.3, 1) 0.4s both; }
        .hero-load-3 { animation: slide-up 1s cubic-bezier(0.16, 1, 0.3, 1) 0.6s both; }
        .hero-load-4 { animation: fade-in 1s ease 0.8s both; }

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
    </style>
</head>

<body class="font-sans text-white bg-[#050505] overflow-x-hidden">

    <!-- Mobile Menu Overlay -->
    <div id="mobileOverlay" class="mobile-overlay fixed inset-0 bg-black/60 backdrop-blur-sm z-[90]" onclick="closeMobileMenu()"></div>

    <!-- Mobile Menu Drawer -->
    <div id="mobileMenu" class="mobile-menu fixed top-0 right-0 w-[85%] max-w-[380px] h-full bg-[#0a0a0a] border-l border-white/5 z-[100] flex flex-col">
        <div class="flex justify-between items-center p-6 border-b border-white/5">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
                    <i class="fa-solid fa-bus text-white text-xs"></i>
                </div>
                <span class="text-lg font-bold tracking-tight">SmartCommute</span>
            </div>
            <button onclick="closeMobileMenu()" class="w-10 h-10 rounded-full bg-white/5 flex items-center justify-center hover:bg-white/10 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <nav class="flex-1 p-6 space-y-2">
            @if (!Auth::user())
                <a href="{{ url('/register') }}" onclick="closeMobileMenu()" class="flex items-center gap-4 px-4 py-4 rounded-2xl hover:bg-white/5 transition group">
                    <div class="w-10 h-10 rounded-xl bg-blue-500/10 flex items-center justify-center group-hover:bg-blue-500/20 transition">
                        <i class="fa-solid fa-user-plus text-blue-400 text-sm"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-sm">Register</p>
                        <p class="text-[10px] text-gray-500">Create a new account</p>
                    </div>
                </a>
                <a href="{{ url('/login') }}" onclick="closeMobileMenu()" class="flex items-center gap-4 px-4 py-4 rounded-2xl hover:bg-white/5 transition group">
                    <div class="w-10 h-10 rounded-xl bg-purple-500/10 flex items-center justify-center group-hover:bg-purple-500/20 transition">
                        <i class="fa-solid fa-right-to-bracket text-purple-400 text-sm"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-sm">Log In</p>
                        <p class="text-[10px] text-gray-500">Access your account</p>
                    </div>
                </a>
                <a href="{{ route('guest.map') }}" onclick="closeMobileMenu()" class="flex items-center gap-4 px-4 py-4 rounded-2xl hover:bg-white/5 transition group">
                    <div class="w-10 h-10 rounded-xl bg-green-500/10 flex items-center justify-center group-hover:bg-green-500/20 transition">
                        <i class="fa-solid fa-map text-green-400 text-sm"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-sm">View Map</p>
                        <p class="text-[10px] text-gray-500">Explore live routes</p>
                    </div>
                </a>
            @endif

            @if (Auth::user())
                <a href="{{ route('map') }}" onclick="closeMobileMenu()" class="flex items-center gap-4 px-4 py-4 rounded-2xl hover:bg-white/5 transition group">
                    <div class="w-10 h-10 rounded-xl bg-blue-500/10 flex items-center justify-center group-hover:bg-blue-500/20 transition">
                        <i class="fa-solid fa-gauge-high text-blue-400 text-sm"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-sm">Map</p>
                        <p class="text-[10px] text-gray-500">Your commute hub</p>
                    </div>
                </a>
            @endif

            <div class="pt-4">
                <div class="line-glow w-full mb-4"></div>
                <a href="#features" onclick="closeMobileMenu()" class="block px-4 py-3 text-sm text-gray-400 hover:text-white transition">Features</a>
                <a href="#services" onclick="closeMobileMenu()" class="block px-4 py-3 text-sm text-gray-400 hover:text-white transition">Services</a>
                <a href="#feedback" onclick="closeMobileMenu()" class="block px-4 py-3 text-sm text-gray-400 hover:text-white transition">Feedback</a>
                <a href="#contacts" onclick="closeMobileMenu()" class="block px-4 py-3 text-sm text-gray-400 hover:text-white transition">Contact</a>
            </div>
        </nav>

        <div class="p-6 border-t border-white/5">
            <a href="{{ url('/register') }}" onclick="closeMobileMenu()" class="block w-full text-center bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3.5 rounded-2xl transition text-sm">
                Get Started Free
            </a>
        </div>
    </div>

    <!-- ==================== HERO SECTION ==================== -->
    <section class="relative min-h-screen w-full hero-bg flex flex-col justify-between p-5 sm:p-8 md:p-12 lg:p-16">

        <!-- Floating decorative orbs -->
        <div class="absolute top-20 right-10 w-72 h-72 bg-blue-500/10 rounded-full blur-[100px] animate-pulse-slow pointer-events-none"></div>
        <div class="absolute bottom-40 left-10 w-56 h-56 bg-purple-500/10 rounded-full blur-[80px] animate-pulse-slow pointer-events-none" style="animation-delay: 2s;"></div>

        <nav class="flex justify-between items-center w-full relative z-10">
            <div class="flex items-center gap-2.5 sm:gap-3">
                <div class="w-9 h-9 sm:w-10 sm:h-10 bg-blue-600 rounded-xl flex items-center justify-center shadow-lg shadow-blue-600/30">
                    <i class="fa-solid fa-bus text-white text-sm"></i>
                </div>
                <span class="text-lg sm:text-xl md:text-2xl font-bold tracking-tight">Smart<span class="text-blue-400">Commute</span></span>
            </div>

            <!-- Desktop Nav -->
            @if (!Auth::user())
                <div class="hidden md:flex items-center space-x-1 text-sm font-medium glass-card px-2 py-2 rounded-full">
                    <a href="{{ url('/register') }}" class="px-5 py-2 rounded-full hover:bg-white/10 transition text-gray-300 hover:text-white">Register</a>
                    <a href="{{ url('/login') }}" class="px-5 py-2 rounded-full hover:bg-white/10 transition text-gray-300 hover:text-white">Log in</a>
                    <div class="w-px h-5 bg-white/10 mx-1"></div>
                    <a href="{{ route('guest.map') }}" class="px-5 py-2 rounded-full hover:bg-white/10 transition text-gray-300 hover:text-white flex items-center gap-2">
                        <i class="fa-solid fa-map-location-dot text-xs text-blue-400"></i> View Map
                    </a>
                </div>
            @endif

            @if (Auth::user())
                @if (Auth::user()->roles->first()->name == 'admin' || Auth::user()->roles->first()->name == 'commuter' || Auth::user()->roles->first()->name == 'driver')
                        <div class="hidden md:flex items-center space-x-1 text-sm font-medium glass-card px-2 py-2 rounded-full">
                            <a href="{{ route('map') }}" class="px-5 py-2 rounded-full hover:bg-white/10 transition text-gray-300 hover:text-white flex items-center gap-2">
                                <i class="fa-solid fa-gauge-high text-xs text-blue-400"></i> Map
                            </a>
                        </div>
                @endif

                @if (Auth::user()->roles->first()->name == 'maintenance_manager' || Auth::user()->roles->first()->name == 'driver_manager')
                        <div class="hidden md:flex items-center space-x-1 text-sm font-medium glass-card px-2 py-2 rounded-full">
                            <a href="{{ route('dashboard') }}" class="px-5 py-2 rounded-full hover:bg-white/10 transition text-gray-300 hover:text-white flex items-center gap-2">
                                <i class="fa-solid fa-gauge-high text-xs text-blue-400"></i> Dashboard
                            </a>
                        </div>
                @endif
            @endif

            <!-- Hamburger -->
            <button id="hamburgerBtn" onclick="openMobileMenu()" class="hamburger md:hidden w-10 h-10 rounded-full bg-white/10 backdrop-blur-md border border-white/10 flex flex-col items-center justify-center gap-1.5">
                <span class="hamburger-line w-4 h-[1.5px] bg-white rounded-full"></span>
                <span class="hamburger-line w-4 h-[1.5px] bg-white rounded-full"></span>
                <span class="hamburger-line w-4 h-[1.5px] bg-white rounded-full"></span>
            </button>
        </nav>

        <!-- Hero Content -->
        <div class="max-w-5xl relative z-10 pt-8 md:pt-0">
            <h1 class="hero-load-2 text-4xl sm:text-5xl md:text-7xl lg:text-8xl font-extrabold leading-[0.95] sm:leading-[1] mb-6 sm:mb-8 tracking-tight">
                The Smartest Way <br class="hidden sm:block">
                To <span class="text-gradient">Optimize</span> Your <br class="hidden sm:block">
                Daily Commute
            </h1>

            <div class="hero-load-3 flex flex-wrap items-center gap-x-3 gap-y-2 text-base sm:text-lg md:text-xl">
                <span class="font-semibold text-white">Swift,</span>
                <span class="font-semibold text-blue-400">Safe,</span>
                <span class="text-gray-400">and</span>
                <span class="font-semibold text-green-400">Affordable</span>
            </div>

            <!-- Stats row (hidden on smallest screens) -->
            <div class="hero-load-4 hidden sm:flex items-center gap-8 mt-10 pt-8 border-t border-white/10">
                <div>
                    <p class="text-2xl md:text-3xl font-bold stat-counter text-white">12K<span class="text-blue-400">+</span></p>
                    <p class="text-[10px] uppercase tracking-widest text-gray-500 mt-1">Daily Commuters</p>
                </div>
                <div class="w-px h-10 bg-white/10"></div>
                <div>
                    <p class="text-2xl md:text-3xl font-bold stat-counter text-white">98<span class="text-green-400">%</span></p>
                    <p class="text-[10px] uppercase tracking-widest text-gray-500 mt-1">On-Time Rate</p>
                </div>
                <div class="w-px h-10 bg-white/10"></div>
                <div>
                    <p class="text-2xl md:text-3xl font-bold stat-counter text-white">45<span class="text-purple-400">+</span></p>
                    <p class="text-[10px] uppercase tracking-widest text-gray-500 mt-1">Active Routes</p>
                </div>
            </div>
        </div>

        <!-- Bottom CTA -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-6 relative z-10">
            <a href="{{ url('/register') }}"
                class="group flex items-center gap-4 glass-card rounded-full pl-2 pr-6 sm:pr-8 py-2 hover:bg-white hover:text-black transition-all duration-500 hover:shadow-2xl hover:shadow-white/10">
                <div class="bg-white text-black w-10 h-10 sm:w-12 sm:h-12 rounded-full flex items-center justify-center group-hover:bg-blue-600 group-hover:text-white transition-all duration-500">
                    <i class="fa-solid fa-arrow-right text-sm"></i>
                </div>
                <span class="font-semibold uppercase tracking-widest text-xs sm:text-sm">Get Started</span>
            </a>
        </div>

        <!-- Scroll indicator -->
        <div class="absolute bottom-6 left-1/2 -translate-x-1/2 hidden md:flex flex-col items-center gap-2 hero-load-4">
            <span class="text-[9px] uppercase tracking-[0.3em] text-gray-600">Scroll</span>
            <div class="w-5 h-8 rounded-full border border-white/20 flex justify-center pt-1.5">
                <div class="w-1 h-2 bg-white/40 rounded-full animate-bounce"></div>
            </div>
        </div>
    </section>

    <!-- ==================== MARQUEE STRIP ==================== -->
    <div class="bg-[#0a0a0a] border-y border-white/5 py-4 overflow-hidden">
        <div class="flex animate-marquee whitespace-nowrap">
            <span class="mx-8 text-xs uppercase tracking-[0.3em] text-gray-600 font-medium flex items-center gap-3"><i class="fa-solid fa-circle text-[3px] text-blue-500"></i> Real-Time GPS Tracking</span>
            <span class="mx-8 text-xs uppercase tracking-[0.3em] text-gray-600 font-medium flex items-center gap-3"><i class="fa-solid fa-circle text-[3px] text-purple-500"></i> Contactless Payments</span>
            <span class="mx-8 text-xs uppercase tracking-[0.3em] text-gray-600 font-medium flex items-center gap-3"><i class="fa-solid fa-circle text-[3px] text-green-500"></i> AI Route Optimization</span>
            <span class="mx-8 text-xs uppercase tracking-[0.3em] text-gray-600 font-medium flex items-center gap-3"><i class="fa-solid fa-circle text-[3px] text-orange-500"></i> Smart Scheduling</span>
            <span class="mx-8 text-xs uppercase tracking-[0.3em] text-gray-600 font-medium flex items-center gap-3"><i class="fa-solid fa-circle text-[3px] text-cyan-500"></i> Fleet Analytics</span>
            <span class="mx-8 text-xs uppercase tracking-[0.3em] text-gray-600 font-medium flex items-center gap-3"><i class="fa-solid fa-circle text-[3px] text-pink-500"></i> Passenger Insights</span>
            <span class="mx-8 text-xs uppercase tracking-[0.3em] text-gray-600 font-medium flex items-center gap-3"><i class="fa-solid fa-circle text-[3px] text-blue-500"></i> Real-Time GPS Tracking</span>
            <span class="mx-8 text-xs uppercase tracking-[0.3em] text-gray-600 font-medium flex items-center gap-3"><i class="fa-solid fa-circle text-[3px] text-purple-500"></i> Contactless Payments</span>
            <span class="mx-8 text-xs uppercase tracking-[0.3em] text-gray-600 font-medium flex items-center gap-3"><i class="fa-solid fa-circle text-[3px] text-green-500"></i> AI Route Optimization</span>
            <span class="mx-8 text-xs uppercase tracking-[0.3em] text-gray-600 font-medium flex items-center gap-3"><i class="fa-solid fa-circle text-[3px] text-orange-500"></i> Smart Scheduling</span>
            <span class="mx-8 text-xs uppercase tracking-[0.3em] text-gray-600 font-medium flex items-center gap-3"><i class="fa-solid fa-circle text-[3px] text-cyan-500"></i> Fleet Analytics</span>
            <span class="mx-8 text-xs uppercase tracking-[0.3em] text-gray-600 font-medium flex items-center gap-3"><i class="fa-solid fa-circle text-[3px] text-pink-500"></i> Passenger Insights</span>
        </div>
    </div>

    <!-- ==================== FEATURES SECTION ==================== -->
    <section id="features" class="py-16 sm:py-24 md:py-32 px-5 sm:px-8 bg-[#050505]">
        <div class="max-w-7xl mx-auto flex flex-col lg:flex-row items-center gap-12 lg:gap-20">

            <!-- Image Side -->
            <div class="lg:w-1/2 relative w-full reveal">
                <div class="absolute -inset-6 bg-gradient-to-br from-blue-600/20 to-purple-600/10 rounded-[3rem] blur-[60px]"></div>

                <div class="relative">
                    <div class="glass-card p-3 sm:p-4 rounded-[2rem] sm:rounded-[3rem] overflow-hidden">
                        <img src="https://img.freepik.com/free-vector/data-informational-infographic-statistic_24877-51525.jpg"
                            alt="System Analytics"
                            class="rounded-[1.5rem] sm:rounded-[2.5rem] w-full grayscale hover:grayscale-0 transition-all duration-700 hover:scale-[1.02]">
                    </div>

                    <!-- Floating stat card -->
                    <div class="absolute -bottom-4 -right-4 sm:bottom-6 sm:right-6 glass-card glow-blue p-4 sm:p-5 rounded-2xl animate-float">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-blue-500/20 rounded-xl flex items-center justify-center">
                                <i class="fa-solid fa-chart-line text-blue-400"></i>
                            </div>
                            <div>
                                <p class="text-[10px] text-gray-500 uppercase tracking-widest">Efficiency</p>
                                <p class="text-xl font-bold stat-counter">+34.8%</p>
                            </div>
                        </div>
                    </div>

                    <!-- Floating badge -->
                    <div class="absolute -top-3 -left-3 sm:top-6 sm:left-6 glass-card p-3 sm:p-4 rounded-2xl animate-float" style="animation-delay: 1.5s;">
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                            <span class="text-[10px] font-semibold uppercase tracking-wider">System Online</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Text Side -->
            <div class="lg:w-1/2 space-y-6 sm:space-y-8">
                <div class="space-y-4 reveal">
                    <div class="inline-flex items-center gap-2 bg-blue-500/10 px-3 py-1.5 rounded-full">
                        <span class="w-1.5 h-1.5 bg-blue-400 rounded-full"></span>
                        <span class="text-blue-400 text-[10px] font-bold uppercase tracking-[0.2em]">SmartCommute</span>
                    </div>
                    <h2 class="text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-extrabold leading-[1.05] tracking-tight">
                        Your Commute <br>
                        <span class="text-white/20 italic font-light">Made Smarter</span>
                    </h2>
                    <p class="text-gray-400 text-sm sm:text-base leading-relaxed max-w-xl">
                        We believe in the power of real-time data. Our analytics-driven approach allows us to make
                        informed decisions and optimize your commute for maximum efficiency.
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-5">
                    <div class="glass-card p-5 sm:p-6 rounded-2xl reveal reveal-delay-1 group cursor-default">
                        <div class="w-10 h-10 bg-blue-500/10 rounded-xl flex items-center justify-center mb-4 group-hover:bg-blue-600/20 group-hover:scale-110 transition-all duration-500">
                            <i class="fa-solid fa-chart-line text-blue-400"></i>
                        </div>
                        <h4 class="font-bold mb-2 text-sm sm:text-base">Data-Driven</h4>
                        <p class="text-[11px] sm:text-xs text-gray-500 leading-relaxed">Optimization based on thousands of daily commute patterns.</p>
                    </div>

                    <div class="glass-card p-5 sm:p-6 rounded-2xl reveal reveal-delay-2 group cursor-default">
                        <div class="w-10 h-10 bg-green-500/10 rounded-xl flex items-center justify-center mb-4 group-hover:bg-green-600/20 group-hover:scale-110 transition-all duration-500">
                            <i class="fa-solid fa-bolt text-green-400"></i>
                        </div>
                        <h4 class="font-bold mb-2 text-sm sm:text-base">Real-Time</h4>
                        <p class="text-[11px] sm:text-xs text-gray-500 leading-relaxed">Instant updates on bus locations and traffic delays.</p>
                    </div>

                    <div class="glass-card p-5 sm:p-6 rounded-2xl reveal reveal-delay-3 group cursor-default">
                        <div class="w-10 h-10 bg-purple-500/10 rounded-xl flex items-center justify-center mb-4 group-hover:bg-purple-600/20 group-hover:scale-110 transition-all duration-500">
                            <i class="fa-solid fa-shield-halved text-purple-400"></i>
                        </div>
                        <h4 class="font-bold mb-2 text-sm sm:text-base">Secure</h4>
                        <p class="text-[11px] sm:text-xs text-gray-500 leading-relaxed">End-to-end encrypted payments and private data handling.</p>
                    </div>

                    <div class="glass-card p-5 sm:p-6 rounded-2xl reveal reveal-delay-4 group cursor-default">
                        <div class="w-10 h-10 bg-orange-500/10 rounded-xl flex items-center justify-center mb-4 group-hover:bg-orange-600/20 group-hover:scale-110 transition-all duration-500">
                            <i class="fa-solid fa-leaf text-orange-400"></i>
                        </div>
                        <h4 class="font-bold mb-2 text-sm sm:text-base">Eco-Friendly</h4>
                        <p class="text-[11px] sm:text-xs text-gray-500 leading-relaxed">Reduced carbon footprint through optimized route planning.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ==================== SERVICES SECTION ==================== -->
    <section id="services" class="py-16 sm:py-24 md:py-32 px-5 sm:px-8 bg-[#050505] relative overflow-hidden">
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[400px] bg-blue-600/5 blur-[120px] rounded-full pointer-events-none"></div>

        <div class="max-w-7xl mx-auto relative z-10">
            <div class="text-center mb-12 sm:mb-16 space-y-4 reveal">
                <div class="inline-flex items-center gap-2 bg-blue-500/10 px-3 py-1.5 rounded-full">
                    <span class="w-1.5 h-1.5 bg-blue-400 rounded-full"></span>
                    <span class="text-blue-400 text-[10px] font-bold uppercase tracking-[0.2em]">SmartCommute System</span>
                </div>
                <h2 class="text-3xl sm:text-4xl md:text-5xl font-extrabold tracking-tight">
                    Comprehensive Commute <span class="text-white/20 italic font-light">Solutions</span>
                </h2>
                <p class="text-gray-500 max-w-2xl mx-auto text-xs sm:text-sm px-4">Everything you need to navigate your daily journey with precision and ease.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">

                <div class="service-card glass-card p-6 sm:p-8 rounded-[1.5rem] sm:rounded-[2rem] transition-all duration-500 group reveal reveal-delay-1">
                    <div class="w-12 h-12 sm:w-14 sm:h-14 bg-blue-500/10 rounded-2xl flex items-center justify-center mb-5 sm:mb-6 group-hover:scale-110 group-hover:bg-blue-500/20 transition-all duration-500">
                        <i class="fa-solid fa-location-crosshairs text-blue-400 text-lg sm:text-xl"></i>
                    </div>
                    <h4 class="text-base sm:text-lg font-bold mb-3">Live GPS Tracking</h4>
                    <p class="text-[11px] sm:text-xs text-gray-500 leading-relaxed mb-5 sm:mb-6">Track your bus in real-time with meter-perfect precision. Never miss a ride or wait in the rain again.</p>
                    <a href="#" class="text-[10px] font-bold uppercase tracking-widest text-blue-400 hover:text-white transition flex items-center gap-2 group/link">
                        Live Update <i class="fa-solid fa-arrow-right text-[8px] group-hover/link:translate-x-1 transition-transform"></i>
                    </a>
                </div>

                <div class="service-card glass-card p-6 sm:p-8 rounded-[1.5rem] sm:rounded-[2rem] transition-all duration-500 group reveal reveal-delay-2">
                    <div class="w-12 h-12 sm:w-14 sm:h-14 bg-purple-500/10 rounded-2xl flex items-center justify-center mb-5 sm:mb-6 group-hover:scale-110 group-hover:bg-purple-500/20 transition-all duration-500">
                        <i class="fa-solid fa-calendar-check text-purple-400 text-lg sm:text-xl"></i>
                    </div>
                    <h4 class="text-base sm:text-lg font-bold mb-3">Smart Scheduling</h4>
                    <p class="text-[11px] sm:text-xs text-gray-500 leading-relaxed mb-5 sm:mb-6">AI-driven route planning that learns your habits and suggests the fastest departure times automatically.</p>
                    <a href="#" class="text-[10px] font-bold uppercase tracking-widest text-purple-400 hover:text-white transition flex items-center gap-2 group/link">
                        Optimized <i class="fa-solid fa-arrow-right text-[8px] group-hover/link:translate-x-1 transition-transform"></i>
                    </a>
                </div>

                <div class="service-card glass-card p-6 sm:p-8 rounded-[1.5rem] sm:rounded-[2rem] transition-all duration-500 group reveal reveal-delay-3">
                    <div class="w-12 h-12 sm:w-14 sm:h-14 bg-green-500/10 rounded-2xl flex items-center justify-center mb-5 sm:mb-6 group-hover:scale-110 group-hover:bg-green-500/20 transition-all duration-500">
                        <i class="fa-solid fa-credit-card text-green-400 text-lg sm:text-xl"></i>
                    </div>
                    <h4 class="text-base sm:text-lg font-bold mb-3">Contactless Pay</h4>
                    <p class="text-[11px] sm:text-xs text-gray-500 leading-relaxed mb-5 sm:mb-6">Swift, secure, and cash-free. Manage your digital wallet and pay for rides with a single tap or scan.</p>
                    <a href="#" class="text-[10px] font-bold uppercase tracking-widest text-green-400 hover:text-white transition flex items-center gap-2 group/link">
                        Efficiency <i class="fa-solid fa-arrow-right text-[8px] group-hover/link:translate-x-1 transition-transform"></i>
                    </a>
                </div>

                <div class="service-card glass-card p-6 sm:p-8 rounded-[1.5rem] sm:rounded-[2rem] transition-all duration-500 group reveal reveal-delay-4">
                    <div class="w-12 h-12 sm:w-14 sm:h-14 bg-orange-500/10 rounded-2xl flex items-center justify-center mb-5 sm:mb-6 group-hover:scale-110 group-hover:bg-orange-500/20 transition-all duration-500">
                        <i class="fa-solid fa-route text-orange-400 text-lg sm:text-xl"></i>
                    </div>
                    <h4 class="text-base sm:text-lg font-bold mb-3">Route Analytics</h4>
                    <p class="text-[11px] sm:text-xs text-gray-500 leading-relaxed mb-5 sm:mb-6">Advanced heatmaps and traffic data help us optimize city-wide routes to reduce your travel time.</p>
                    <a href="#" class="text-[10px] font-bold uppercase tracking-widest text-orange-400 hover:text-white transition flex items-center gap-2 group/link">
                        High Accuracy <i class="fa-solid fa-arrow-right text-[8px] group-hover/link:translate-x-1 transition-transform"></i>
                    </a>
                </div>

            </div>
        </div>
    </section>

    <!-- ==================== FEEDBACK SECTION ==================== -->
    <section id="feedback" class="py-16 sm:py-24 md:py-32 px-5 sm:px-8 bg-[#050505] relative">
        <div class="absolute top-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-white/5 to-transparent"></div>

        <div class="max-w-7xl mx-auto relative z-10">
            <div class="text-center mb-12 sm:mb-16 space-y-4 reveal">
                <div class="inline-flex items-center gap-2 bg-blue-500/10 px-3 py-1.5 rounded-full">
                    <span class="w-1.5 h-1.5 bg-blue-400 rounded-full"></span>
                    <span class="text-blue-400 text-[10px] font-bold uppercase tracking-[0.2em]">Feedbacks</span>
                </div>
                <h2 class="text-3xl sm:text-4xl md:text-5xl font-extrabold tracking-tight">
                    What Our <span class="text-white/20 italic font-light">Users Say</span>
                </h2>
                <p class="text-gray-500 max-w-2xl mx-auto text-xs sm:text-sm px-4">See how SmartCommute is changing the daily journey for thousands of people.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5 sm:gap-6">

                <div class="testimonial-card glass-card p-7 sm:p-10 rounded-[2rem] flex flex-col items-center text-center relative group reveal reveal-delay-1">
                    <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-full bg-gradient-to-br from-blue-500/20 to-blue-600/5 border border-blue-500/20 flex items-center justify-center mb-5 group-hover:border-blue-500/50 group-hover:scale-105 transition-all duration-500">
                        <i class="fa-solid fa-user text-xl sm:text-2xl text-blue-400"></i>
                    </div>

                    <div class="flex space-x-0.5 mb-4 text-yellow-500 text-xs">
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                    </div>

                    <p class="text-gray-400 text-xs sm:text-sm italic leading-relaxed mb-7 sm:mb-8">
                        "The real-time tracking is a lifesaver. I used to wait 20 minutes at the stop, but now I time my walk perfectly. The digital wallet makes boarding so much faster!"
                    </p>

                    <div class="mt-auto">
                        <h5 class="font-bold text-white text-sm">Karl Campoy</h5>
                        <p class="text-[9px] sm:text-[10px] uppercase tracking-widest text-blue-400 font-bold mt-1">Daily Commuter</p>
                    </div>
                </div>

                <div class="testimonial-card glass-card p-7 sm:p-10 rounded-[2rem] flex flex-col items-center text-center relative group border-blue-500/15 glow-blue reveal reveal-delay-2">
                    <div class="absolute top-4 right-4 bg-blue-500/10 px-2.5 py-1 rounded-full">
                        <span class="text-[8px] font-bold uppercase tracking-wider text-blue-400">Featured</span>
                    </div>
                    <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-full bg-gradient-to-br from-purple-500/20 to-purple-600/5 border border-purple-500/20 flex items-center justify-center mb-5 group-hover:border-purple-500/50 group-hover:scale-105 transition-all duration-500">
                        <i class="fa-solid fa-user text-xl sm:text-2xl text-purple-400"></i>
                    </div>

                    <div class="flex space-x-0.5 mb-4 text-yellow-500 text-xs">
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                    </div>

                    <p class="text-gray-400 text-xs sm:text-sm italic leading-relaxed mb-7 sm:mb-8">
                        "Managing my routes and schedule through the app has reduced my stress significantly. The navigation is optimized for large buses, which is exactly what we needed."
                    </p>

                    <div class="mt-auto">
                        <h5 class="font-bold text-white text-sm">Daniel Padilla</h5>
                        <p class="text-[9px] sm:text-[10px] uppercase tracking-widest text-purple-400 font-bold mt-1">Transit Driver</p>
                    </div>
                </div>

                <div class="testimonial-card glass-card p-7 sm:p-10 rounded-[2rem] flex flex-col items-center text-center relative group reveal reveal-delay-3">
                    <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-full bg-gradient-to-br from-green-500/20 to-green-600/5 border border-green-500/20 flex items-center justify-center mb-5 group-hover:border-green-500/50 group-hover:scale-105 transition-all duration-500">
                        <i class="fa-solid fa-user text-xl sm:text-2xl text-green-400"></i>
                    </div>

                    <div class="flex space-x-0.5 mb-4 text-yellow-500 text-xs">
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star-half-stroke"></i>
                    </div>

                    <p class="text-gray-400 text-xs sm:text-sm italic leading-relaxed mb-7 sm:mb-8">
                        "From an administrative standpoint, the analytics dashboard provides insights we never had before. We've optimized fuel consumption by 15% across the fleet."
                    </p>

                    <div class="mt-auto">
                        <h5 class="font-bold text-white text-sm">Juswa Garcia</h5>
                        <p class="text-[9px] sm:text-[10px] uppercase tracking-widest text-green-400 font-bold mt-1">Fleet Manager</p>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ==================== CONTACT SECTION ==================== -->
    <section id="contacts" class="py-16 sm:py-24 md:py-32 px-5 sm:px-8 bg-[#050505] relative">
        <div class="absolute top-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-white/5 to-transparent"></div>
        <div class="absolute bottom-1/3 right-0 w-96 h-96 bg-blue-600/5 blur-[120px] rounded-full pointer-events-none"></div>

        <div class="max-w-6xl mx-auto relative z-10">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-10 md:gap-16 items-center">

                <div class="space-y-6 sm:space-y-8 reveal">
                    <div>
                        <div class="inline-flex items-center gap-2 bg-blue-500/10 px-3 py-1.5 rounded-full mb-6">
                            <span class="w-1.5 h-1.5 bg-blue-400 rounded-full"></span>
                            <span class="text-blue-400 text-[10px] font-bold uppercase tracking-[0.2em]">Contact Us</span>
                        </div>
                        <h2 class="text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-extrabold leading-[1.05] tracking-tight mb-4">
                            Get in <span class="text-gradient">Touch</span>
                        </h2>
                        <p class="text-gray-400 text-sm sm:text-base max-w-md leading-relaxed">
                            Have questions about our routes or pricing? Our team is here to help you optimize your commute.
                        </p>
                    </div>

                    <div class="space-y-5">
                        <div class="flex items-center gap-4 group">
                            <div class="w-12 h-12 bg-white/5 border border-white/10 rounded-2xl flex items-center justify-center text-lg group-hover:bg-blue-500/10 group-hover:border-blue-500/30 transition-all duration-300 shrink-0">
                                <i class="fa-solid fa-envelope text-blue-400"></i>
                            </div>
                            <div>
                                <p class="text-[10px] text-gray-500 uppercase tracking-widest mb-0.5">Email Us</p>
                                <p class="text-sm sm:text-base font-medium">support@smartcommute.com</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-4 group">
                            <div class="w-12 h-12 bg-white/5 border border-white/10 rounded-2xl flex items-center justify-center text-lg group-hover:bg-green-500/10 group-hover:border-green-500/30 transition-all duration-300 shrink-0">
                                <i class="fa-solid fa-phone text-green-400"></i>
                            </div>
                            <div>
                                <p class="text-[10px] text-gray-500 uppercase tracking-widest mb-0.5">Call Us</p>
                                <p class="text-sm sm:text-base font-medium">+1 (555) 000-1234</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-4 group">
                            <div class="w-12 h-12 bg-white/5 border border-white/10 rounded-2xl flex items-center justify-center text-lg group-hover:bg-purple-500/10 group-hover:border-purple-500/30 transition-all duration-300 shrink-0">
                                <i class="fa-solid fa-location-dot text-purple-400"></i>
                            </div>
                            <div>
                                <p class="text-[10px] text-gray-500 uppercase tracking-widest mb-0.5">Visit Us</p>
                                <p class="text-sm sm:text-base font-medium">123 Transit Ave, Metro City</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="glass-card p-6 sm:p-8 md:p-10 rounded-[1.5rem] sm:rounded-[2rem] reveal reveal-delay-2">
                    <form action="#" class="space-y-4 sm:space-y-5">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label class="text-[10px] uppercase tracking-widest text-gray-500 ml-1 font-medium">First Name</label>
                                <input type="text" placeholder="John"
                                    class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm transition-all duration-300">
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] uppercase tracking-widest text-gray-500 ml-1 font-medium">Last Name</label>
                                <input type="text" placeholder="Doe"
                                    class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm transition-all duration-300">
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="text-[10px] uppercase tracking-widest text-gray-500 ml-1 font-medium">Email</label>
                            <input type="email" placeholder="john@example.com"
                                class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm transition-all duration-300">
                        </div>

                        <div class="space-y-2">
                            <label class="text-[10px] uppercase tracking-widest text-gray-500 ml-1 font-medium">Message</label>
                            <textarea rows="4" placeholder="How can we help?"
                                class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm transition-all duration-300 resize-none"></textarea>
                        </div>

                        <button type="button" id="sendBtn"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3.5 sm:py-4 rounded-xl transition-all duration-300 transform active:scale-[0.98] uppercase tracking-widest text-[10px] sm:text-xs flex items-center justify-center gap-2">
                            <span id="sendText">Send Message</span>
                            <i class="fa-solid fa-paper-plane text-[10px]" id="sendIcon"></i>
                            <i class="fa-solid fa-check text-[10px] hidden" id="sendCheck"></i>
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </section>

    <!-- ==================== FOOTER ==================== -->
    <footer class="bg-[#030303] pt-16 sm:pt-24 pb-8 sm:pb-12 px-5 sm:px-8 border-t border-white/5">
        <div class="max-w-7xl mx-auto">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-10 sm:gap-12 mb-12 sm:mb-16">

                <div class="space-y-5 sm:space-y-6 sm:col-span-2 lg:col-span-1">
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
                            <i class="fa-solid fa-bus text-white text-xs"></i>
                        </div>
                        <span class="text-lg font-bold tracking-tight">Smart<span class="text-blue-400">Commute</span></span>
                    </div>
                    <p class="text-gray-500 text-xs sm:text-sm leading-relaxed max-w-xs">
                        Revolutionizing the way you move. Real-time tracking, smart scheduling, and secure payments all in one place.
                    </p>
                    <div class="flex space-x-3">
                        <a href="#" class="w-9 h-9 glass-card rounded-xl flex items-center justify-center hover:bg-blue-600 hover:border-blue-600 transition-all duration-300 group">
                            <i class="fa-brands fa-facebook-f text-xs text-gray-400 group-hover:text-white transition"></i>
                        </a>
                        <a href="#" class="w-9 h-9 glass-card rounded-xl flex items-center justify-center hover:bg-blue-600 hover:border-blue-600 transition-all duration-300 group">
                            <i class="fa-brands fa-twitter text-xs text-gray-400 group-hover:text-white transition"></i>
                        </a>
                        <a href="#" class="w-9 h-9 glass-card rounded-xl flex items-center justify-center hover:bg-blue-600 hover:border-blue-600 transition-all duration-300 group">
                            <i class="fa-brands fa-instagram text-xs text-gray-400 group-hover:text-white transition"></i>
                        </a>
                        <a href="#" class="w-9 h-9 glass-card rounded-xl flex items-center justify-center hover:bg-blue-600 hover:border-blue-600 transition-all duration-300 group">
                            <i class="fa-brands fa-github text-xs text-gray-400 group-hover:text-white transition"></i>
                        </a>
                    </div>
                </div>

                <div>
                    <h4 class="text-white font-bold mb-5 sm:mb-6 uppercase text-[10px] tracking-[0.2em]">Quick Links</h4>
                    <ul class="space-y-3 sm:space-y-4 text-xs sm:text-sm text-gray-500">
                        <li><a href="#services" class="hover:text-blue-400 transition flex items-center gap-2 group"><i class="fa-solid fa-chevron-right text-[6px] text-gray-700 group-hover:text-blue-400 transition"></i> Our Services</a></li>
                        <li><a href="#features" class="hover:text-blue-400 transition flex items-center gap-2 group"><i class="fa-solid fa-chevron-right text-[6px] text-gray-700 group-hover:text-blue-400 transition"></i> How it Works</a></li>
                        <li><a href="#feedback" class="hover:text-blue-400 transition flex items-center gap-2 group"><i class="fa-solid fa-chevron-right text-[6px] text-gray-700 group-hover:text-blue-400 transition"></i> User Reviews</a></li>
                        <li><a href="/tutorial" class="hover:text-blue-400 transition flex items-center gap-2 group"><i class="fa-solid fa-chevron-right text-[6px] text-gray-700 group-hover:text-blue-400 transition"></i> System Tutorial</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-white font-bold mb-5 sm:mb-6 uppercase text-[10px] tracking-[0.2em]">Support</h4>
                    <ul class="space-y-3 sm:space-y-4 text-xs sm:text-sm text-gray-500">
                        <li><a href="#" class="hover:text-blue-400 transition flex items-center gap-2 group"><i class="fa-solid fa-chevron-right text-[6px] text-gray-700 group-hover:text-blue-400 transition"></i> Help Center</a></li>
                        <li><a href="#" class="hover:text-blue-400 transition flex items-center gap-2 group"><i class="fa-solid fa-chevron-right text-[6px] text-gray-700 group-hover:text-blue-400 transition"></i> Terms of Service</a></li>
                        <li><a href="#" class="hover:text-blue-400 transition flex items-center gap-2 group"><i class="fa-solid fa-chevron-right text-[6px] text-gray-700 group-hover:text-blue-400 transition"></i> Privacy Policy</a></li>
                        <li><a href="#contacts" class="hover:text-blue-400 transition flex items-center gap-2 group"><i class="fa-solid fa-chevron-right text-[6px] text-gray-700 group-hover:text-blue-400 transition"></i> Contact Support</a></li>
                    </ul>
                </div>

                <div class="space-y-4 sm:space-y-6">
                    <h4 class="text-white font-bold uppercase text-[10px] tracking-[0.2em]">Stay Updated</h4>
                    <p class="text-gray-500 text-xs sm:text-sm">Get the latest route updates and news delivered to your inbox.</p>
                    <div class="relative">
                        <input type="email" placeholder="Your email"
                            class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-xs focus:outline-none transition-all duration-300 pr-12">
                        <button class="absolute right-1.5 top-1/2 -translate-y-1/2 bg-blue-600 text-white p-2.5 rounded-lg hover:bg-blue-700 transition-all duration-300 hover:scale-105">
                            <i class="fa-solid fa-paper-plane text-[10px]"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="line-glow w-full mb-8"></div>

            <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                <p class="text-[10px] text-gray-600 uppercase tracking-widest text-center sm:text-left">
                    &copy; 2026 SmartCommute. All rights reserved.
                </p>
                <div class="flex items-center gap-5 sm:gap-6">
                    <span class="text-[10px] text-gray-600 uppercase tracking-widest flex items-center">
                        <span class="w-1.5 h-1.5 bg-green-500 rounded-full mr-2 animate-pulse"></span> Servers Operational
                    </span>
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

        // Navbar background on scroll
        const nav = document.querySelector('nav');
        let lastScroll = 0;

        window.addEventListener('scroll', () => {
            const currentScroll = window.pageYOffset;

            if (currentScroll > 100) {
                nav.style.background = 'rgba(5, 5, 5, 0.8)';
                nav.style.backdropFilter = 'blur(20px)';
                nav.style.WebkitBackdropFilter = 'blur(20px)';
            } else {
                nav.style.background = 'transparent';
                nav.style.backdropFilter = 'none';
                nav.style.WebkitBackdropFilter = 'none';
            }

            lastScroll = currentScroll;
        });

        // Send button interaction
        const sendBtn = document.getElementById('sendBtn');
        if (sendBtn) {
            sendBtn.addEventListener('click', function () {
                const text = document.getElementById('sendText');
                const icon = document.getElementById('sendIcon');
                const check = document.getElementById('sendCheck');

                this.classList.remove('bg-blue-600', 'hover:bg-blue-700');
                this.classList.add('bg-green-600');

                text.textContent = 'Message Sent!';
                icon.classList.add('hidden');
                check.classList.remove('hidden');

                setTimeout(() => {
                    this.classList.add('bg-blue-600', 'hover:bg-blue-700');
                    this.classList.remove('bg-green-600');

                    text.textContent = 'Send Message';
                    icon.classList.remove('hidden');
                    check.classList.add('hidden');
                }, 2500);
            });
        }

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
    </script>
</body>

</html>
