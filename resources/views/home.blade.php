<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Commute System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        html {
            scroll-behavior: smooth;
        }

        .hero-bg {
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)),
                url("{{ asset('images/newbg.jpg') }}");
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;

        }
    </style>
</head>

<body class="antialiased font-sans text-white">

    <section class="relative h-screen w-full hero-bg flex flex-col justify-between p-8 md:p-12">

        <nav class="flex justify-between items-center w-full">
            <div class="flex items-center space-x-2">
                <div class="w-8 h-8 border-2 border-white rounded-full flex items-center justify-center">
                    <div class="w-4 h-4 bg-white rounded-full"></div>
                </div>
                <span class="text-2xl font-bold tracking-wider italic">SmartCommute</span>
            </div>

            @if (!Auth::user())
                <div
                    class="hidden md:flex items-center space-x-8 text-sm font-medium bg-white/10 backdrop-blur-md border border-white/20 px-8 py-3 rounded-full">
                    <a href="{{ url('/register') }}" class="hover:text-gray-300 transition">Register</a>
                    <a href="{{ url('/login') }}" class="hover:text-gray-300 transition">Log in</a>
                    <a href="#contacts" class="hover:text-gray-300 transition">Contacts</a>
                </div>
            @endif

            @if (Auth::user())
                <div
                    class="hidden md:flex items-center space-x-8 text-sm font-medium bg-white/10 backdrop-blur-md border border-white/20 px-8 py-3 rounded-full">
                    <a href="{{ route('commuter.dashboard') }}" class="hover:text-gray-300 transition">Dashboard</a>
                </div>
            @endif

            {{-- @if (!Auth::id())
            <div
                class="hidden md:flex items-center space-x-8 text-sm font-medium bg-white/10 backdrop-blur-md border border-white/20 px-8 py-3 rounded-full">
                <a href="{{ url('/register') }}" class="hover:text-gray-300 transition">Register</a>
                <a href="{{ url('/login') }}" class="hover:text-gray-300 transition">Log in</a>
                <a href="#contacts" class="hover:text-gray-300 transition">Contacts</a>
            </div>
            @endif --}}


            <div class="flex items-center space-x-6">
                <i class="fa-solid fa-magnifying-glass cursor-pointer"></i>
                <div class="flex items-center space-x-1 cursor-pointer">
                    <i class="fa-solid fa-globe"></i>
                    <i class="fa-solid fa-chevron-down text-xs"></i>
                </div>
                <i class="fa-solid fa-bars text-xl cursor-pointer"></i>
            </div>
        </nav>

        <div class="max-w-4xl">
            <h1 class="text-6xl md:text-8xl font-bold leading-tight mb-6">
                The Smartest Way <br> To Optimize Your Daily Commute
            </h1>

            <div class="flex items-center space-x-2 text-lg">
                <span class="opacity-80 bold ">Swift,</span>
                <span class="opacity-80 bold ">Safe</span>
                <span class="opacity-80">, And Affordable</span>
            </div>
        </div>

        <div class="flex flex-col md:flex-row justify-between items-end md:items-center space-y-6 md:space-y-0">

            <a href="/services"
                class="group flex items-center space-x-4 border border-white/50 rounded-full px-2 py-2 pr-6 hover:bg-white hover:text-black transition duration-300">
                <div class="bg-white text-black w-10 h-10 rounded-full flex items-center justify-center">
                    <i class="fa-solid fa-arrow-right"></i>
                </div>
                <span class="font-medium uppercase tracking-widest text-sm">Get Started</span>
            </a>
        </div>

    </section>
    <section id="features" class="py-24 px-8 bg-[#0a0a0a]">
        <div class="max-w-7xl mx-auto flex flex-col lg:flex-row items-center gap-16">

            <div class="lg:w-1/2 relative">
                <div class="absolute -inset-4 bg-blue-600/20 rounded-[3rem] blur-3xl opacity-30"></div>

                <div class="glass glass-inset p-4 rounded-[3rem] relative overflow-hidden">
                    <img src="https://img.freepik.com/free-vector/data-informational-infographic-statistic_24877-51525.jpg"
                        alt="System Analytics"
                        class="rounded-[2.5rem] shadow-2xl grayscale hover:grayscale-0 transition duration-700">
                </div>


            </div>

            <div class="lg:w-1/2 space-y-8">
                <div class="space-y-4">
                    <span class="text-blue-400 text-xs font-black uppercase tracking-[0.3em]">SmartCommute</span>
                    <h2 class="text-5xl md:text-6xl font-bold leading-tight">
                        Your Commute <br> <span class="text-white/40 italic">Made Smarter</span>
                    </h2>
                    <p class="text-gray-400 text-lg leading-relaxed max-w-xl">
                        We believe in the power of real-time data. Our analytics-driven approach allows us to make
                        informed decisions and optimize your commute for maximum efficiency.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="glass p-6 rounded-2xl border-white/5 hover:border-blue-500/30 transition group">
                        <div
                            class="w-10 h-10 bg-white/5 rounded-lg flex items-center justify-center mb-4 group-hover:bg-blue-600/20 transition">
                            <i class="fa-solid fa-chart-line text-blue-400"></i>
                        </div>
                        <h4 class="font-bold mb-2">Data-Driven</h4>
                        <p class="text-xs text-gray-500 leading-relaxed">Optimization based on thousands of daily
                            commute patterns.</p>
                    </div>

                    <div class="glass p-6 rounded-2xl border-white/5 hover:border-green-500/30 transition group">
                        <div
                            class="w-10 h-10 bg-white/5 rounded-lg flex items-center justify-center mb-4 group-hover:bg-green-600/20 transition">
                            <i class="fa-solid fa-bolt text-green-400"></i>
                        </div>
                        <h4 class="font-bold mb-2">Real-Time</h4>
                        <p class="text-xs text-gray-500 leading-relaxed">Instant updates on bus locations and traffic
                            delays.</p>
                    </div>
                </div>


            </div>
        </div>
    </section>
    <section id="services" class="py-24 px-8 bg-[#0a0a0a] relative overflow-hidden">
        <div
            class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[800px] h-[300px] bg-blue-600/10 blur-[120px] rounded-full">
        </div>

        <div class="max-w-7xl mx-auto relative z-10">
            <div class="text-center mb-16 space-y-4">
                <span class="text-blue-400 text-xs font-black uppercase tracking-[0.3em]">SmartCommute System</span>
                <h2 class="text-4xl md:text-5xl font-bold">Comprehensive Commute <span
                        class="text-white/40">Solutions</span></h2>
                <p class="text-gray-500 max-w-2xl mx-auto text-sm">Everything you need to navigate your daily journey
                    with precision and ease.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

                <div
                    class="glass glass-inset p-8 rounded-[2rem] hover:bg-white/5 transition duration-500 group border-white/5">
                    <div
                        class="w-14 h-14 bg-blue-500/10 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition duration-500">
                        <i class="fa-solid fa-location-crosshairs text-blue-400 text-xl"></i>
                    </div>
                    <h4 class="text-lg font-bold mb-3">Live GPS Tracking</h4>
                    <p class="text-xs text-gray-500 leading-relaxed mb-6">Track your bus in real-time with meter-perfect
                        precision. Never miss a ride or wait in the rain again.</p>
                    <a href="#"
                        class="text-[10px] font-black uppercase tracking-widest text-blue-400 hover:text-white transition">Live
                        Update Enable</a>
                </div>

                <div
                    class="glass glass-inset p-8 rounded-[2rem] hover:bg-white/5 transition duration-500 group border-white/5">
                    <div
                        class="w-14 h-14 bg-purple-500/10 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition duration-500">
                        <i class="fa-solid fa-calendar-check text-purple-400 text-xl"></i>
                    </div>
                    <h4 class="text-lg font-bold mb-3">Smart Scheduling</h4>
                    <p class="text-xs text-gray-500 leading-relaxed mb-6">AI-driven route planning that learns your
                        habits and suggests the fastest departure times automatically.</p>
                    <a href="#"
                        class="text-[10px] font-black uppercase tracking-widest text-purple-400 hover:text-white transition">Optimized
                        Speed</a>
                </div>

                <div
                    class="glass glass-inset p-8 rounded-[2rem] hover:bg-white/5 transition duration-500 group border-white/5">
                    <div
                        class="w-14 h-14 bg-green-500/10 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition duration-500">
                        <i class="fa-solid fa-credit-card text-green-400 text-xl"></i>
                    </div>
                    <h4 class="text-lg font-bold mb-3">Contactless Pay</h4>
                    <p class="text-xs text-gray-500 leading-relaxed mb-6">Swift, secure, and cash-free. Manage your
                        digital wallet and pay for rides with a single tap or scan.</p>
                    <a href="#"
                        class="text-[10px] font-black uppercase tracking-widest text-green-400 hover:text-white transition">More
                        Efficiency</a>
                </div>

                <div
                    class="glass glass-inset p-8 rounded-[2rem] hover:bg-white/5 transition duration-500 group border-white/5">
                    <div
                        class="w-14 h-14 bg-orange-500/10 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition duration-500">
                        <i class="fa-solid fa-route text-orange-400 text-xl"></i>
                    </div>
                    <h4 class="text-lg font-bold mb-3">Route Analytics</h4>
                    <p class="text-xs text-gray-500 leading-relaxed mb-6">Advanced heatmaps and traffic data help us
                        optimize city-wide routes to reduce your travel time.</p>
                    <a href="#"
                        class="text-[10px] font-black uppercase tracking-widest text-orange-400 hover:text-white transition">High
                        Efficiency</a>
                </div>

            </div>
        </div>
    </section>
    <section id="feedback" class="py-24 px-8 bg-[#0a0a0a] relative">
        <div class="max-w-7xl mx-auto relative z-10">

            <div class="text-center mb-16 space-y-4">
                <span class="text-blue-400 text-xs font-black uppercase tracking-[0.3em]">FEEDBACKS</span>
                <h2 class="text-4xl md:text-5xl font-bold">What Our <span class="text-white/40">Feedbacks</span></h2>
                <p class="text-gray-500 max-w-2xl mx-auto text-sm">See how SmartCommute is changing the daily journey
                    for thousands of people.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">

                <div
                    class="glass glass-inset p-10 rounded-[2.5rem] flex flex-col items-center text-center relative group">
                    <div
                        class="w-16 h-16 rounded-full border-2 border-blue-500/30 p-1 mb-6 group-hover:border-blue-500 transition duration-500 flex items-center justify-center">
                        <i class="fa-solid fa-user text-2xl text-blue-400"></i>
                    </div>

                    <div class="flex space-x-1 mb-4 text-yellow-500 text-xs">
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                    </div>

                    <p class="text-gray-400 text-sm italic leading-relaxed mb-8">
                        "The real-time tracking is a lifesaver. I used to wait 20 minutes at the stop, but now I time my
                        walk perfectly. The digital wallet makes boarding so much faster!"
                    </p>

                    <div>
                        <h5 class="font-bold text-white">Karl Campoy</h5>
                        <p class="text-[10px] uppercase tracking-widest text-blue-400 font-bold">Daily Commuter</p>
                    </div>
                </div>

                <div
                    class="glass glass-inset p-10 rounded-[2.5rem] flex flex-col items-center text-center relative group border-blue-500/20 shadow-2xl shadow-blue-500/5">
                    <div
                        class="w-16 h-16 rounded-full border-2 border-blue-500/30 p-1 mb-6 group-hover:border-blue-500 transition duration-500 flex items-center justify-center">
                        <i class="fa-solid fa-user text-2xl text-blue-400"></i>
                    </div>

                    <div class="flex space-x-1 mb-4 text-yellow-500 text-xs">
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                    </div>

                    <p class="text-gray-400 text-sm italic leading-relaxed mb-8">
                        "Managing my routes and schedule through the app has reduced my stress significantly. The
                        navigation is optimized for large buses, which is exactly what we needed."
                    </p>

                    <div>
                        <h5 class="font-bold text-white">Daniel Padilla</h5>
                        <p class="text-[10px] uppercase tracking-widest text-purple-400 font-bold">Transit Driver</p>
                    </div>
                </div>

                <div
                    class="glass glass-inset p-10 rounded-[2.5rem] flex flex-col items-center text-center relative group">
                    <div
                        class="w-16 h-16 rounded-full border-2 border-blue-500/30 p-1 mb-6 group-hover:border-blue-500 transition duration-500 flex items-center justify-center">
                        <i class="fa-solid fa-user text-2xl text-blue-400"></i>
                    </div>

                    <div class="flex space-x-1 mb-4 text-yellow-500 text-xs">
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star-half-stroke"></i>
                    </div>

                    <p class="text-gray-400 text-sm italic leading-relaxed mb-8">
                        "From an administrative standpoint, the analytics dashboard provides insights we never had
                        before. We've optimized fuel consumption by 15% across the fleet."
                    </p>

                    <div>
                        <h5 class="font-bold text-white">Juswa Garcia</h5>
                        <p class="text-[10px] uppercase tracking-widest text-green-400 font-bold">Daily Commuter</p>
                    </div>
                </div>

            </div>
        </div>
    </section>
    <section id="contacts" class="min-h-screen bg-[#0a0a0a] py-20 px-8 flex items-center justify-center">
        <div class="max-w-6xl w-full grid grid-cols-1 md:grid-cols-2 gap-12 items-center">

            <div class="space-y-8">
                <div>
                    <h2 class="text-4xl md:text-6xl font-bold mb-4">Get in Touch</h2>
                    <p class="text-gray-400 max-w-md">Have questions about our routes or pricing? Our team is here to
                        help you optimize your commute.</p>
                </div>

                <div class="space-y-6">
                    <div class="flex items-center space-x-4">
                        <div
                            class="w-12 h-12 bg-white/5 border border-white/10 rounded-full flex items-center justify-center text-xl">
                            <i class="fa-solid fa-envelope"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-widest">Email Us</p>
                            <p class="text-lg">support@smartcommute.com</p>
                        </div>
                    </div>

                    <div class="flex items-center space-x-4">
                        <div
                            class="w-12 h-12 bg-white/5 border border-white/10 rounded-full flex items-center justify-center text-xl">
                            <i class="fa-solid fa-phone"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-widest">Call Us</p>
                            <p class="text-lg">+1 (555) 000-1234</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white/5 backdrop-blur-xl border border-white/10 p-8 md:p-10 rounded-[2rem] shadow-2xl">
                <form action="#" class="space-y-5">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="text-[10px] uppercase tracking-widest opacity-50 ml-1">First Name</label>
                            <input type="text" placeholder=""
                                class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-white/20">
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] uppercase tracking-widest opacity-50 ml-1">Last Name</label>
                            <input type="text" placeholder=""
                                class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-white/20">
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] uppercase tracking-widest opacity-50 ml-1">Message</label>
                        <textarea rows="4" placeholder="How can we help?"
                            class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-white/20"></textarea>
                    </div>

                    <button
                        class="w-full bg-white text-black font-bold py-4 rounded-xl hover:bg-gray-200 transition transform active:scale-95 uppercase tracking-widest text-xs">
                        Send Message
                    </button>
                </form>
            </div>

        </div>
    </section>
    <footer class="bg-[#050505] pt-24 pb-12 px-8 border-t border-white/5">
        <div class="max-w-7xl mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 mb-16">

                <div class="space-y-6">
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
                            <i class="fa-solid fa-bus text-white text-sm"></i>
                        </div>
                        <span class="text-xl font-bold tracking-tighter">Smart<span
                                class="text-blue-500">Commute</span></span>
                    </div>
                    <p class="text-gray-500 text-sm leading-relaxed">
                        Revolutionizing the way you move. Real-time tracking, smart scheduling, and secure payments all
                        in one place.
                    </p>
                    <div class="flex space-x-4">
                        <a href="#"
                            class="w-9 h-9 glass rounded-full flex items-center justify-center hover:bg-blue-600 transition duration-300">
                            <i class="fa-brands fa-facebook-f text-xs"></i>
                        </a>
                        <a href="#"
                            class="w-9 h-9 glass rounded-full flex items-center justify-center hover:bg-blue-600 transition duration-300">
                            <i class="fa-brands fa-twitter text-xs"></i>
                        </a>
                        <a href="#"
                            class="w-9 h-9 glass rounded-full flex items-center justify-center hover:bg-blue-600 transition duration-300">
                            <i class="fa-brands fa-instagram text-xs"></i>
                        </a>
                    </div>
                </div>

                <div>
                    <h4 class="text-white font-bold mb-6 uppercase text-[10px] tracking-[0.2em]">Quick Links</h4>
                    <ul class="space-y-4 text-sm text-gray-500">
                        <li><a href="#services" class="hover:text-blue-400 transition">Our Services</a></li>
                        <li><a href="#features" class="hover:text-blue-400 transition">How it Works</a></li>
                        <li><a href="#feedback" class="hover:text-blue-400 transition">User Reviews</a></li>
                        <li><a href="/tutorial" class="hover:text-blue-400 transition">System Tutorial</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-white font-bold mb-6 uppercase text-[10px] tracking-[0.2em]">Support</h4>
                    <ul class="space-y-4 text-sm text-gray-500">
                        <li><a href="#" class="hover:text-blue-400 transition">Help Center</a></li>
                        <li><a href="#" class="hover:text-blue-400 transition">Terms of Service</a></li>
                        <li><a href="#" class="hover:text-blue-400 transition">Privacy Policy</a></li>
                        <li><a href="#" class="hover:text-blue-400 transition">Contact Support</a></li>
                    </ul>
                </div>

                <div class="space-y-6">
                    <h4 class="text-white font-bold uppercase text-[10px] tracking-[0.2em]">Stay Updated</h4>
                    <p class="text-gray-500 text-sm">Get the latest route updates and news.</p>
                    <div class="relative">
                        <input type="email" placeholder="Your email"
                            class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 transition">
                        <button
                            class="absolute right-2 top-1/2 -translate-y-1/2 bg-blue-600 text-white p-2 rounded-lg hover:bg-blue-700 transition">
                            <i class="fa-solid fa-paper-plane text-[10px]"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="pt-8 border-t border-white/5 flex flex-col md:flex-row justify-between items-center gap-4">
                <p class="text-[10px] text-gray-600 uppercase tracking-widest">
                    &copy; 2026 SmartCommute. All rights reserved.
                </p>
                <div class="flex items-center space-x-6">
                    <span class="text-[10px] text-gray-600 uppercase tracking-widest flex items-center">
                        <i class="fa-solid fa-circle text-[6px] text-green-500 mr-2 animate-pulse"></i> Servers
                        Operational
                    </span>
                </div>
            </div>
        </div>
    </footer>
</body>

</html>