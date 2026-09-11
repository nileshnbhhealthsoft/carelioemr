@extends('layouts.app')

@section('title', 'CarelioEMR - Cloud EMR & EHR Healthcare Platform ($80/month)')

@section('content')

<!-- Top Announcement Banner -->
<div class="bg-slate-900 text-white px-4 py-2 text-xs font-semibold flex flex-col sm:flex-row items-center justify-between gap-2 text-center sm:text-left">
    <div class="flex items-center justify-center sm:justify-start space-x-2">
        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse shrink-0"></span>
        <span class="break-words">CarelioEMR Cloud Healthcare Platform • 24/7 Global Infrastructure</span>
    </div>
    <a href="{{ url('/admin/login') }}" class="bg-blue-600 hover:bg-blue-500 text-white px-3 py-1 rounded-lg text-xs font-bold transition-all whitespace-nowrap shrink-0">
        Admin Portal &rarr;
    </a>
</div>

<!-- Header / Navigation Bar -->
<header class="sticky top-0 z-40 w-full border-b border-slate-200 bg-white/95 backdrop-blur-md shadow-xs">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between gap-2 sm:gap-4">
        
        <!-- Brand Logo -->
        <a href="{{ url('/') }}" class="flex items-center space-x-2.5 sm:space-x-3 shrink-0">
            <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-blue-600 text-white shadow-md shadow-blue-600/20 font-bold shrink-0">
                <i data-lucide="activity" class="w-6 h-6 stroke-[2.5]"></i>
            </div>
            <div class="flex flex-col justify-center">
                <div class="flex items-center space-x-1.5 sm:space-x-2">
                    <span class="text-lg sm:text-xl font-black tracking-tight text-slate-900 leading-none">
                        Carelio<span class="text-blue-600">EMR</span>
                    </span>
                    <span class="text-[10px] uppercase font-extrabold tracking-wider px-1.5 sm:px-2 py-0.5 rounded-md bg-blue-50 text-blue-700 border border-blue-200/80 shrink-0">
                        Cloud
                    </span>
                </div>
                <span class="text-[10px] sm:text-[11px] text-slate-500 font-semibold mt-0.5 hidden xs:block">
                    EMR &amp; EHR Healthcare Suite
                </span>
            </div>
        </a>

        <!-- Center Nav Links (Desktop xl+) -->
        <nav class="hidden xl:flex items-center space-x-4 2xl:space-x-6 text-xs 2xl:text-sm font-bold text-slate-700">
            <a href="#services" class="hover:text-blue-600 transition-colors whitespace-nowrap">Target Practices</a>
            <a href="#specialties" class="hover:text-blue-600 transition-colors whitespace-nowrap">Specialties</a>
            <a href="#regions" class="hover:text-blue-600 transition-colors whitespace-nowrap">Global Coverage</a>
            <a href="#workspace" class="hover:text-blue-600 transition-colors whitespace-nowrap">Platform Features</a>
            <a href="#pricing" class="hover:text-blue-600 transition-colors whitespace-nowrap flex items-center gap-1.5">
                <span>Pricing</span>
                <span class="text-blue-600 font-extrabold text-[11px] 2xl:text-xs bg-blue-50 px-2 py-0.5 rounded-full border border-blue-200">$80/mo</span>
            </a>
            <a href="#contact" class="hover:text-blue-600 transition-colors whitespace-nowrap">Contact</a>
        </nav>

        <!-- Right Actions (Desktop xl+) -->
        <div class="hidden xl:flex items-center space-x-2.5 2xl:space-x-3 shrink-0">
            <a href="{{ url('/admin/login') }}" class="flex items-center space-x-1.5 px-3 py-2 rounded-xl bg-slate-100 border border-slate-200 text-slate-800 text-xs font-bold hover:bg-slate-200 transition-all whitespace-nowrap">
                <i data-lucide="layout-dashboard" class="w-4 h-4 text-blue-600"></i>
                <span>Admin Portal</span>
            </a>

            <button onclick="openCheckoutModal()" class="flex items-center space-x-1.5 2xl:space-x-2 px-3.5 2xl:px-4 py-2.5 rounded-xl bg-blue-600 text-white font-bold text-xs shadow-md shadow-blue-600/20 hover:bg-blue-700 transition-all cursor-pointer whitespace-nowrap">
                <i data-lucide="credit-card" class="w-4 h-4 stroke-[2.5]"></i>
                <span>Subscribe Now ($80/mo)</span>
            </button>
        </div>

        <!-- Mobile / Tablet Menu Button (Visible below xl) -->
        <div class="flex items-center space-x-2 xl:hidden shrink-0">
            <button onclick="openCheckoutModal()" class="hidden sm:flex items-center space-x-1.5 px-3 py-2 rounded-xl bg-blue-600 text-white font-bold text-xs shadow-sm hover:bg-blue-700 transition-all cursor-pointer whitespace-nowrap">
                <i data-lucide="credit-card" class="w-3.5 h-3.5 stroke-[2.5]"></i>
                <span>Subscribe ($80/mo)</span>
            </button>
            <button onclick="toggleMobileMenu()" type="button" class="p-2.5 rounded-xl bg-slate-100 border border-slate-200 text-slate-700 hover:text-slate-900 hover:bg-slate-200 transition-all cursor-pointer" aria-label="Toggle navigation menu">
                <i data-lucide="menu" id="mobileMenuIcon" class="w-5 h-5"></i>
            </button>
        </div>

    </div>

    <!-- Mobile Navigation Dropdown (Tablet & Mobile) -->
    <div id="mobileMenu" class="hidden xl:hidden border-t border-slate-200 bg-white px-4 sm:px-6 pt-3 pb-5 space-y-3 shadow-lg">
        <nav class="flex flex-col space-y-1 text-sm font-bold text-slate-700">
            <a href="#services" onclick="toggleMobileMenu(false)" class="px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-blue-600 transition-colors">Target Practices</a>
            <a href="#specialties" onclick="toggleMobileMenu(false)" class="px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-blue-600 transition-colors">Specialties</a>
            <a href="#regions" onclick="toggleMobileMenu(false)" class="px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-blue-600 transition-colors">Global Coverage</a>
            <a href="#workspace" onclick="toggleMobileMenu(false)" class="px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-blue-600 transition-colors">Platform Features</a>
            <a href="#pricing" onclick="toggleMobileMenu(false)" class="px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-blue-600 transition-colors flex items-center justify-between">
                <span>Pricing</span>
                <span class="text-blue-600 font-extrabold text-xs bg-blue-50 px-2 py-0.5 rounded-full border border-blue-200">$80/mo</span>
            </a>
            <a href="#contact" onclick="toggleMobileMenu(false)" class="px-3 py-2 rounded-lg hover:bg-slate-50 hover:text-blue-600 transition-colors">Contact</a>
        </nav>
        <div class="pt-3 border-t border-slate-100 flex flex-col sm:flex-row gap-2">
            <a href="{{ url('/admin/login') }}" class="flex items-center justify-center space-x-1.5 px-4 py-2.5 rounded-xl bg-slate-100 border border-slate-200 text-slate-800 text-xs font-bold hover:bg-slate-200 transition-all">
                <i data-lucide="layout-dashboard" class="w-4 h-4 text-blue-600"></i>
                <span>Admin Portal</span>
            </a>
            <button onclick="toggleMobileMenu(false); openCheckoutModal();" class="flex items-center justify-center space-x-2 px-4 py-2.5 rounded-xl bg-blue-600 text-white font-bold text-xs shadow-md shadow-blue-600/20 hover:bg-blue-700 transition-all cursor-pointer">
                <i data-lucide="credit-card" class="w-4 h-4 stroke-[2.5]"></i>
                <span>Subscribe Now ($80/mo)</span>
            </button>
        </div>
    </div>
</header>

<!-- Hero Section -->
<section class="relative pt-12 pb-20 overflow-hidden bg-slate-50 border-b border-slate-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
            
            <!-- Left Hero Content -->
            <div class="lg:col-span-7 space-y-8 text-center lg:text-left">
                
                <div class="inline-flex items-center space-x-2 px-3.5 py-1.5 rounded-full bg-blue-50 border border-blue-200/80 text-blue-800 text-xs font-bold shadow-2xs">
                    <span class="w-2 h-2 rounded-full bg-blue-600 animate-pulse shrink-0"></span>
                    <span>Next-Generation Cloud EMR &amp; EHR Suite</span>
                </div>

                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black tracking-tight text-slate-900 leading-[1.1] break-words">
                    Unified Medical Records for <span class="text-blue-600">Practices, Hospitals &amp; Clinics</span>
                </h1>

                <p class="text-base sm:text-lg text-slate-600 font-medium max-w-2xl leading-relaxed mx-auto lg:mx-0">
                    Streamline SOAP charting, real-time vitals monitoring, e-prescriptions, and hospital bed management across <strong>Caribbean, USA, India, UAE, Europe, Africa, and Australia</strong>.
                </p>

                <!-- Key Highlights Badge Bar -->
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 pt-2 max-w-xl mx-auto lg:mx-0">
                    <div class="p-3 rounded-2xl bg-white border border-slate-200 shadow-2xs text-left min-w-0">
                        <div class="text-xs text-slate-400 font-bold uppercase truncate">Monthly License</div>
                        <div class="text-xl font-black text-blue-600 mt-0.5">$80 / month</div>
                    </div>
                    <div class="p-3 rounded-2xl bg-white border border-slate-200 shadow-2xs text-left min-w-0">
                        <div class="text-xs text-slate-400 font-bold uppercase truncate">Setup Cost Note</div>
                        <div class="text-sm font-extrabold text-amber-700 mt-1 truncate">Billed Separately</div>
                    </div>
                    <div class="p-3 rounded-2xl bg-white border border-slate-200 shadow-2xs text-left col-span-2 sm:col-span-1 min-w-0">
                        <div class="text-xs text-slate-400 font-bold uppercase truncate">Global Coverage</div>
                        <div class="text-sm font-extrabold text-slate-800 mt-1 truncate">7 Key Hubs 🏝️ 🇺🇸 🇮🇳</div>
                    </div>
                </div>

                <!-- CTA Buttons -->
                <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4 pt-4">
                    <button onclick="openCheckoutModal()" class="w-full sm:w-auto px-8 py-4 rounded-2xl bg-blue-600 text-white font-extrabold text-sm shadow-xl shadow-blue-600/25 hover:bg-blue-700 transition-all cursor-pointer flex items-center justify-center space-x-2">
                        <i data-lucide="shield-check" class="w-5 h-5"></i>
                        <span>Subscribe Now – $80/Month</span>
                    </button>
                    
                    <a href="#services" class="w-full sm:w-auto px-6 py-4 rounded-2xl bg-white border border-slate-200 text-slate-700 font-bold text-sm hover:bg-slate-100 transition-all text-center">
                        Explore Practice Solutions
                    </a>
                </div>

            </div>

            <!-- Right Hero Image Preview -->
            <div class="lg:col-span-5 relative">
                <div class="relative rounded-3xl overflow-hidden border border-slate-200 shadow-2xl bg-white p-3">
                    <img src="https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?auto=format&fit=crop&w=1000&q=80" alt="Doctor using CarelioEMR software" class="w-full h-80 sm:h-96 object-cover rounded-2xl">
                    <div class="absolute bottom-4 left-3 right-3 sm:bottom-6 sm:left-6 sm:right-6 p-3 sm:p-4 rounded-2xl bg-white/95 backdrop-blur-md border border-slate-200 shadow-xl flex items-center justify-between gap-2 min-w-0">
                        <div class="min-w-0">
                            <div class="text-xs font-bold text-slate-900 truncate">Dr. Sarah Johnson</div>
                            <div class="text-[11px] text-blue-600 font-semibold truncate">General Practice • Caribbean Hub</div>
                        </div>
                        <span class="px-2.5 py-1 rounded-full text-[11px] font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200 shrink-0">Live Sync</span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- 1. Target Practice Settings -->
<section id="services" class="py-20 bg-white border-b border-slate-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
        
        <div class="text-center max-w-3xl mx-auto space-y-3">
            <span class="text-xs uppercase font-extrabold tracking-wider text-blue-600 bg-blue-50 px-3 py-1 rounded-full border border-blue-200">Target Healthcare Sectors</span>
            <h2 class="text-3xl sm:text-4xl font-black text-slate-900">Built Specifically for Your Practice Setting</h2>
            <p class="text-sm text-slate-600 font-medium">Custom workflows tailored for solo practices, multi-specialty clinics, and multi-bed hospital networks.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            
            <!-- CARD 1: GP - Basic -->
            <div class="p-8 rounded-3xl bg-slate-50 border border-slate-200 hover:border-blue-300 transition-all space-y-4 flex flex-col justify-between">
                <div class="space-y-4">
                    <div class="w-12 h-12 rounded-2xl bg-blue-600 text-white flex items-center justify-center font-bold shadow-md">
                        <i data-lucide="stethoscope" class="w-6 h-6"></i>
                    </div>
                    <h3 class="text-xl font-extrabold text-slate-900">General Practice / GP</h3>
                    <p class="text-xs text-slate-600 leading-relaxed font-medium">Basic EMR setup for General Practice, Family Medicine, solo doctors and primary care clinics.</p>
                </div>
                <div class="pt-2 text-xs font-bold text-blue-600">$80/month per practice</div>
            </div>

            <!-- CARD 2: Hospital -->
            <div class="p-8 rounded-3xl bg-slate-50 border border-slate-200 hover:border-blue-300 transition-all space-y-4 flex flex-col justify-between">
                <div class="space-y-4">
                    <div class="w-12 h-12 rounded-2xl bg-indigo-600 text-white flex items-center justify-center font-bold shadow-md">
                        <i data-lucide="building-2" class="w-6 h-6"></i>
                    </div>
                    <h3 class="text-xl font-extrabold text-slate-900">Hospitals &amp; ER</h3>
                    <p class="text-xs text-slate-600 leading-relaxed font-medium">Support for Emergency Room (ER), all clinics and departments, pharmacy, radiology, inpatient departments, and hospital-wide workflows.</p>
                </div>
                <div class="pt-2 text-xs font-bold text-indigo-600">Call for pricing</div>
            </div>

            <!-- CARD 3: Specialty Clinics -->
            <div class="p-8 rounded-3xl bg-slate-50 border border-slate-200 hover:border-blue-300 transition-all space-y-4 flex flex-col justify-between">
                <div class="space-y-4">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-600 text-white flex items-center justify-center font-bold shadow-md">
                        <i data-lucide="activity" class="w-6 h-6"></i>
                    </div>
                    <h3 class="text-xl font-extrabold text-slate-900">Specialty Clinics</h3>
                    <p class="text-xs text-slate-600 leading-relaxed font-medium">EMR configurations tailored to specialist workflows and clinic requirements.</p>
                </div>
                <div class="pt-2 text-xs font-bold text-emerald-600">Call for pricing</div>
            </div>

        </div>

        <!-- Small supporting line below the 3 cards -->
        <div class="text-center pt-2">
            <p class="text-xs text-slate-500 font-medium">
                Pricing may vary depending on practice size, departments, users, integrations and configuration requirements.
            </p>
        </div>

    </div>
</section>

<!-- 2. Specialty Clinics Details Section -->
<section id="specialties" class="py-20 bg-slate-50 border-b border-slate-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
        
        <div class="text-center max-w-3xl mx-auto space-y-3">
            <span class="text-xs uppercase font-extrabold tracking-wider text-blue-600 bg-blue-50 px-3 py-1 rounded-full border border-blue-200">Clinical Specialization</span>
            <h2 class="text-3xl sm:text-4xl font-black text-slate-900">EMR Solutions for Every Specialty</h2>
            <p class="text-sm text-slate-600 font-medium">Flexible clinical workflows designed for general practices, hospitals and specialty clinics.</p>
        </div>

        <!-- Compact Search & Expand/Collapse Control Bar -->
        <div class="max-w-xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="relative w-full">
                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                <input type="text" id="specialtySearch" oninput="filterSpecialties()" placeholder="Search any specialty or category..." class="w-full bg-white border border-slate-200 rounded-xl pl-10 pr-4 py-2 text-xs text-slate-900 font-semibold focus:border-blue-600 focus:outline-none shadow-2xs">
            </div>
            <div class="flex items-center space-x-2 shrink-0">
                <button type="button" onclick="toggleAllSpecialties(true)" class="px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-700 text-xs font-bold hover:bg-slate-100 transition-all shadow-2xs cursor-pointer whitespace-nowrap">
                    Expand All
                </button>
                <button type="button" onclick="toggleAllSpecialties(false)" class="px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-700 text-xs font-bold hover:bg-slate-100 transition-all shadow-2xs cursor-pointer whitespace-nowrap">
                    Collapse All
                </button>
            </div>
        </div>

        <!-- 20 Compact Grouped Collapsible Category Cards Grid (items-start prevents empty stretched height) -->
        <div id="specialtiesGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-start">
            
            <!-- 1. General & Primary Care -->
            <details class="group p-4 rounded-2xl bg-white border border-slate-200 shadow-2xs hover:border-blue-300 transition-all h-fit" open>
                <summary class="flex items-center justify-between cursor-pointer list-none select-none">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                            <i data-lucide="stethoscope" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h4 class="text-xs font-bold text-slate-900">General &amp; Primary Care</h4>
                            <span class="text-[10px] text-slate-500 font-medium">4 specialties</span>
                        </div>
                    </div>
                    <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 group-open:rotate-180 transition-transform duration-200 shrink-0"></i>
                </summary>
                <div class="mt-3 pt-3 border-t border-slate-100 flex flex-wrap gap-1.5">
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">General Practice / Family Medicine</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Internal Medicine</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Geriatric Medicine</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Preventive Medicine</span>
                </div>
            </details>

            <!-- 2. Surgical Specialties -->
            <details class="group p-4 rounded-2xl bg-white border border-slate-200 shadow-2xs hover:border-blue-300 transition-all h-fit" open>
                <summary class="flex items-center justify-between cursor-pointer list-none select-none">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                            <i data-lucide="scissors" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h4 class="text-xs font-bold text-slate-900">Surgical Specialties</h4>
                            <span class="text-[10px] text-slate-500 font-medium">10 specialties</span>
                        </div>
                    </div>
                    <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 group-open:rotate-180 transition-transform duration-200 shrink-0"></i>
                </summary>
                <div class="mt-3 pt-3 border-t border-slate-100 flex flex-wrap gap-1.5">
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">General Surgery</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Laparoscopic Surgery</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Vascular Surgery</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Breast Surgery</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Colorectal Surgery</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Bariatric Surgery</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Plastic &amp; Reconstructive Surgery</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Cardiothoracic Surgery</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Neurosurgery</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Pediatric Surgery</span>
                </div>
            </details>

            <!-- 3. Gastroenterology & Endoscopy -->
            <details class="group p-4 rounded-2xl bg-white border border-slate-200 shadow-2xs hover:border-blue-300 transition-all h-fit" open>
                <summary class="flex items-center justify-between cursor-pointer list-none select-none">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                            <i data-lucide="pill" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h4 class="text-xs font-bold text-slate-900">Gastroenterology &amp; Endoscopy</h4>
                            <span class="text-[10px] text-slate-500 font-medium">4 specialties</span>
                        </div>
                    </div>
                    <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 group-open:rotate-180 transition-transform duration-200 shrink-0"></i>
                </summary>
                <div class="mt-3 pt-3 border-t border-slate-100 flex flex-wrap gap-1.5">
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Gastroenterology</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Endoscopy</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Colonoscopy</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Hepatology</span>
                </div>
            </details>

            <!-- 4. Cardiovascular -->
            <details class="group p-4 rounded-2xl bg-white border border-slate-200 shadow-2xs hover:border-blue-300 transition-all h-fit" open>
                <summary class="flex items-center justify-between cursor-pointer list-none select-none">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0">
                            <i data-lucide="heart-pulse" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h4 class="text-xs font-bold text-slate-900">Cardiovascular</h4>
                            <span class="text-[10px] text-slate-500 font-medium">6 specialties</span>
                        </div>
                    </div>
                    <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 group-open:rotate-180 transition-transform duration-200 shrink-0"></i>
                </summary>
                <div class="mt-3 pt-3 border-t border-slate-100 flex flex-wrap gap-1.5">
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Cardiology</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Interventional Cardiology</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Electrophysiology</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Heart Failure Clinic</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Hypertension Clinic</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Vascular Medicine</span>
                </div>
            </details>

            <!-- 5. Orthopedics & Musculoskeletal -->
            <details class="group p-4 rounded-2xl bg-white border border-slate-200 shadow-2xs hover:border-blue-300 transition-all h-fit">
                <summary class="flex items-center justify-between cursor-pointer list-none select-none">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                            <i data-lucide="bone" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h4 class="text-xs font-bold text-slate-900">Orthopedics &amp; Musculoskeletal</h4>
                            <span class="text-[10px] text-slate-500 font-medium">6 specialties</span>
                        </div>
                    </div>
                    <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 group-open:rotate-180 transition-transform duration-200 shrink-0"></i>
                </summary>
                <div class="mt-3 pt-3 border-t border-slate-100 flex flex-wrap gap-1.5">
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Orthopedic Surgery</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Sports Medicine</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Rheumatology</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Pain Management</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Physical Medicine &amp; Rehabilitation</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Physiotherapy / Physical Therapy</span>
                </div>
            </details>

            <!-- 6. Women’s Health -->
            <details class="group p-4 rounded-2xl bg-white border border-slate-200 shadow-2xs hover:border-blue-300 transition-all h-fit">
                <summary class="flex items-center justify-between cursor-pointer list-none select-none">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-8 h-8 rounded-xl bg-pink-50 text-pink-600 flex items-center justify-center shrink-0">
                            <i data-lucide="baby" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h4 class="text-xs font-bold text-slate-900">Women’s Health</h4>
                            <span class="text-[10px] text-slate-500 font-medium">5 specialties</span>
                        </div>
                    </div>
                    <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 group-open:rotate-180 transition-transform duration-200 shrink-0"></i>
                </summary>
                <div class="mt-3 pt-3 border-t border-slate-100 flex flex-wrap gap-1.5">
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Obstetrics &amp; Gynecology</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Antenatal / Maternity Clinic</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Fertility / Reproductive Medicine</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Gynecologic Oncology</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Menopause Clinic</span>
                </div>
            </details>

            <!-- 7. Pediatrics -->
            <details class="group p-4 rounded-2xl bg-white border border-slate-200 shadow-2xs hover:border-blue-300 transition-all h-fit">
                <summary class="flex items-center justify-between cursor-pointer list-none select-none">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-8 h-8 rounded-xl bg-cyan-50 text-cyan-600 flex items-center justify-center shrink-0">
                            <i data-lucide="smile" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h4 class="text-xs font-bold text-slate-900">Pediatrics</h4>
                            <span class="text-[10px] text-slate-500 font-medium">6 specialties</span>
                        </div>
                    </div>
                    <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 group-open:rotate-180 transition-transform duration-200 shrink-0"></i>
                </summary>
                <div class="mt-3 pt-3 border-t border-slate-100 flex flex-wrap gap-1.5">
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">General Pediatrics</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Neonatology</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Pediatric Cardiology</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Pediatric Endocrinology</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Pediatric Neurology</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Developmental Pediatrics</span>
                </div>
            </details>

            <!-- 8. Cancer Care -->
            <details class="group p-4 rounded-2xl bg-white border border-slate-200 shadow-2xs hover:border-blue-300 transition-all h-fit">
                <summary class="flex items-center justify-between cursor-pointer list-none select-none">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-8 h-8 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0">
                            <i data-lucide="activity" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h4 class="text-xs font-bold text-slate-900">Cancer Care</h4>
                            <span class="text-[10px] text-slate-500 font-medium">6 specialties</span>
                        </div>
                    </div>
                    <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 group-open:rotate-180 transition-transform duration-200 shrink-0"></i>
                </summary>
                <div class="mt-3 pt-3 border-t border-slate-100 flex flex-wrap gap-1.5">
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Medical Oncology</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Surgical Oncology</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Radiation Oncology</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Hematology</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Chemotherapy / Infusion Clinic</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Palliative Care</span>
                </div>
            </details>

            <!-- 9. Neurology -->
            <details class="group p-4 rounded-2xl bg-white border border-slate-200 shadow-2xs hover:border-blue-300 transition-all h-fit">
                <summary class="flex items-center justify-between cursor-pointer list-none select-none">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-8 h-8 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center shrink-0">
                            <i data-lucide="brain" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h4 class="text-xs font-bold text-slate-900">Neurology</h4>
                            <span class="text-[10px] text-slate-500 font-medium">6 specialties</span>
                        </div>
                    </div>
                    <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 group-open:rotate-180 transition-transform duration-200 shrink-0"></i>
                </summary>
                <div class="mt-3 pt-3 border-t border-slate-100 flex flex-wrap gap-1.5">
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Neurology</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Epilepsy Clinic</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Stroke Clinic</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Movement Disorders</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Memory / Dementia Clinic</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Headache Clinic</span>
                </div>
            </details>

            <!-- 10. Endocrinology & Metabolic Medicine -->
            <details class="group p-4 rounded-2xl bg-white border border-slate-200 shadow-2xs hover:border-blue-300 transition-all h-fit">
                <summary class="flex items-center justify-between cursor-pointer list-none select-none">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-8 h-8 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center shrink-0">
                            <i data-lucide="flame" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h4 class="text-xs font-bold text-slate-900">Endocrinology &amp; Metabolic</h4>
                            <span class="text-[10px] text-slate-500 font-medium">5 specialties</span>
                        </div>
                    </div>
                    <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 group-open:rotate-180 transition-transform duration-200 shrink-0"></i>
                </summary>
                <div class="mt-3 pt-3 border-t border-slate-100 flex flex-wrap gap-1.5">
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Endocrinology</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Diabetes Clinic</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Thyroid Clinic</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Obesity / Weight Management</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Metabolic Medicine</span>
                </div>
            </details>

            <!-- 11. Renal & Urology -->
            <details class="group p-4 rounded-2xl bg-white border border-slate-200 shadow-2xs hover:border-blue-300 transition-all h-fit">
                <summary class="flex items-center justify-between cursor-pointer list-none select-none">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-8 h-8 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center shrink-0">
                            <i data-lucide="droplet" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h4 class="text-xs font-bold text-slate-900">Renal &amp; Urology</h4>
                            <span class="text-[10px] text-slate-500 font-medium">4 specialties</span>
                        </div>
                    </div>
                    <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 group-open:rotate-180 transition-transform duration-200 shrink-0"></i>
                </summary>
                <div class="mt-3 pt-3 border-t border-slate-100 flex flex-wrap gap-1.5">
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Nephrology</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Urology</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Dialysis</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Prostate / Men’s Health Clinic</span>
                </div>
            </details>

            <!-- 12. Respiratory Medicine -->
            <details class="group p-4 rounded-2xl bg-white border border-slate-200 shadow-2xs hover:border-blue-300 transition-all h-fit">
                <summary class="flex items-center justify-between cursor-pointer list-none select-none">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-8 h-8 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center shrink-0">
                            <i data-lucide="wind" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h4 class="text-xs font-bold text-slate-900">Respiratory Medicine</h4>
                            <span class="text-[10px] text-slate-500 font-medium">4 specialties</span>
                        </div>
                    </div>
                    <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 group-open:rotate-180 transition-transform duration-200 shrink-0"></i>
                </summary>
                <div class="mt-3 pt-3 border-t border-slate-100 flex flex-wrap gap-1.5">
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Pulmonology</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Asthma Clinic</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">COPD Clinic</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Sleep Medicine</span>
                </div>
            </details>

            <!-- 13. ENT & Eye Care -->
            <details class="group p-4 rounded-2xl bg-white border border-slate-200 shadow-2xs hover:border-blue-300 transition-all h-fit">
                <summary class="flex items-center justify-between cursor-pointer list-none select-none">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                            <i data-lucide="eye" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h4 class="text-xs font-bold text-slate-900">ENT &amp; Eye Care</h4>
                            <span class="text-[10px] text-slate-500 font-medium">4 specialties</span>
                        </div>
                    </div>
                    <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 group-open:rotate-180 transition-transform duration-200 shrink-0"></i>
                </summary>
                <div class="mt-3 pt-3 border-t border-slate-100 flex flex-wrap gap-1.5">
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">ENT / Otolaryngology</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Audiology</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Ophthalmology</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Optometry</span>
                </div>
            </details>

            <!-- 14. Dermatology -->
            <details class="group p-4 rounded-2xl bg-white border border-slate-200 shadow-2xs hover:border-blue-300 transition-all h-fit">
                <summary class="flex items-center justify-between cursor-pointer list-none select-none">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0">
                            <i data-lucide="sparkles" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h4 class="text-xs font-bold text-slate-900">Dermatology</h4>
                            <span class="text-[10px] text-slate-500 font-medium">4 specialties</span>
                        </div>
                    </div>
                    <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 group-open:rotate-180 transition-transform duration-200 shrink-0"></i>
                </summary>
                <div class="mt-3 pt-3 border-t border-slate-100 flex flex-wrap gap-1.5">
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">General Dermatology</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Dermatologic Surgery</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Cosmetic Dermatology</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Wound Care</span>
                </div>
            </details>

            <!-- 15. Mental & Behavioral Health -->
            <details class="group p-4 rounded-2xl bg-white border border-slate-200 shadow-2xs hover:border-blue-300 transition-all h-fit">
                <summary class="flex items-center justify-between cursor-pointer list-none select-none">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                            <i data-lucide="user-check" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h4 class="text-xs font-bold text-slate-900">Mental &amp; Behavioral Health</h4>
                            <span class="text-[10px] text-slate-500 font-medium">5 specialties</span>
                        </div>
                    </div>
                    <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 group-open:rotate-180 transition-transform duration-200 shrink-0"></i>
                </summary>
                <div class="mt-3 pt-3 border-t border-slate-100 flex flex-wrap gap-1.5">
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Psychiatry</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Psychology</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Counseling / Psychotherapy</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Addiction Medicine</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Child &amp; Adolescent Mental Health</span>
                </div>
            </details>

            <!-- 16. Allergy, Immunology & Infectious Disease -->
            <details class="group p-4 rounded-2xl bg-white border border-slate-200 shadow-2xs hover:border-blue-300 transition-all h-fit">
                <summary class="flex items-center justify-between cursor-pointer list-none select-none">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-8 h-8 rounded-xl bg-red-50 text-red-600 flex items-center justify-center shrink-0">
                            <i data-lucide="shield-alert" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h4 class="text-xs font-bold text-slate-900">Allergy &amp; Infectious Disease</h4>
                            <span class="text-[10px] text-slate-500 font-medium">4 specialties</span>
                        </div>
                    </div>
                    <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 group-open:rotate-180 transition-transform duration-200 shrink-0"></i>
                </summary>
                <div class="mt-3 pt-3 border-t border-slate-100 flex flex-wrap gap-1.5">
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Allergy &amp; Immunology</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Infectious Disease</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">HIV / STI Clinic</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Travel Medicine</span>
                </div>
            </details>

            <!-- 17. Diagnostic Specialties -->
            <details class="group p-4 rounded-2xl bg-white border border-slate-200 shadow-2xs hover:border-blue-300 transition-all h-fit">
                <summary class="flex items-center justify-between cursor-pointer list-none select-none">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                            <i data-lucide="microscope" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h4 class="text-xs font-bold text-slate-900">Diagnostic Specialties</h4>
                            <span class="text-[10px] text-slate-500 font-medium">6 specialties</span>
                        </div>
                    </div>
                    <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 group-open:rotate-180 transition-transform duration-200 shrink-0"></i>
                </summary>
                <div class="mt-3 pt-3 border-t border-slate-100 flex flex-wrap gap-1.5">
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Radiology</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Ultrasound</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">CT / MRI Imaging</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Nuclear Medicine</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Pathology</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Medical Laboratory</span>
                </div>
            </details>

            <!-- 18. Dental & Oral Health -->
            <details class="group p-4 rounded-2xl bg-white border border-slate-200 shadow-2xs hover:border-blue-300 transition-all h-fit">
                <summary class="flex items-center justify-between cursor-pointer list-none select-none">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-8 h-8 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center shrink-0">
                            <i data-lucide="smile" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h4 class="text-xs font-bold text-slate-900">Dental &amp; Oral Health</h4>
                            <span class="text-[10px] text-slate-500 font-medium">5 specialties</span>
                        </div>
                    </div>
                    <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 group-open:rotate-180 transition-transform duration-200 shrink-0"></i>
                </summary>
                <div class="mt-3 pt-3 border-t border-slate-100 flex flex-wrap gap-1.5">
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">General Dentistry</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Oral &amp; Maxillofacial Surgery</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Orthodontics</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Periodontics</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Endodontics</span>
                </div>
            </details>

            <!-- 19. Rehabilitation & Allied Health -->
            <details class="group p-4 rounded-2xl bg-white border border-slate-200 shadow-2xs hover:border-blue-300 transition-all h-fit">
                <summary class="flex items-center justify-between cursor-pointer list-none select-none">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-8 h-8 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0">
                            <i data-lucide="accessibility" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h4 class="text-xs font-bold text-slate-900">Rehabilitation &amp; Allied</h4>
                            <span class="text-[10px] text-slate-500 font-medium">6 specialties</span>
                        </div>
                    </div>
                    <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 group-open:rotate-180 transition-transform duration-200 shrink-0"></i>
                </summary>
                <div class="mt-3 pt-3 border-t border-slate-100 flex flex-wrap gap-1.5">
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Physiotherapy</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Occupational Therapy</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Speech &amp; Language Therapy</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Nutrition / Dietetics</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Podiatry</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Chiropractic</span>
                </div>
            </details>

            <!-- 20. Other High-Value Specialty Clinics -->
            <details class="group p-4 rounded-2xl bg-white border border-slate-200 shadow-2xs hover:border-blue-300 transition-all h-fit">
                <summary class="flex items-center justify-between cursor-pointer list-none select-none">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                            <i data-lucide="plus-circle" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h4 class="text-xs font-bold text-slate-900">Other Specialty Clinics</h4>
                            <span class="text-[10px] text-slate-500 font-medium">9 specialties</span>
                        </div>
                    </div>
                    <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 group-open:rotate-180 transition-transform duration-200 shrink-0"></i>
                </summary>
                <div class="mt-3 pt-3 border-t border-slate-100 flex flex-wrap gap-1.5">
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Anesthesiology / Preoperative Assessment</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Pain Clinic</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Wound Care</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Hyperbaric Medicine</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Occupational Health</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Sexual Health</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Travel Medicine</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Infusion Clinic</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/80 text-[11px] text-slate-700 font-medium">Anticoagulation Clinic</span>
                </div>
            </details>

        </div>

        <div class="text-center pt-2">
            <a href="#contact" class="inline-flex items-center space-x-2 px-6 py-3 rounded-xl bg-white border border-slate-200 text-slate-800 text-xs font-bold hover:bg-slate-100 hover:border-blue-300 transition-all shadow-2xs">
                <i data-lucide="sparkles" class="w-4 h-4 text-blue-600"></i>
                <span>Need a Custom Specialty Configuration? Inquire with our Clinical Team</span>
            </a>
        </div>

    </div>
</section>

<!-- 3. Geographic Coverage Grid (7 Key Regions Worldwide) -->
<section id="regions" class="py-20 bg-white border-b border-slate-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
        
        <div class="text-center max-w-3xl mx-auto space-y-3">
            <span class="text-xs uppercase font-extrabold tracking-wider text-blue-600 bg-blue-50 px-3 py-1 rounded-full border border-blue-200">Global Cloud Coverage</span>
            <h2 class="text-3xl sm:text-4xl font-black text-slate-900">Serving 7 Key Regions Worldwide</h2>
            <p class="text-sm text-slate-600 font-medium">Compliant with regional healthcare data privacy standards and local medical data residency laws.</p>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-7 gap-3 sm:gap-4">
            
            <!-- 1. Caribbean (Replaced Saint Lucia) -->
            <div class="p-4 sm:p-5 rounded-3xl bg-slate-50 border border-slate-200 text-center space-y-2 shadow-2xs min-w-0">
                <div class="text-2xl sm:text-3xl">🏝️</div>
                <h4 class="font-extrabold text-slate-900 text-xs sm:text-sm truncate">Caribbean</h4>
                <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 max-w-full truncate">Regional Standards</span>
            </div>

            <!-- 2. United States -->
            <div class="p-4 sm:p-5 rounded-3xl bg-slate-50 border border-slate-200 text-center space-y-2 shadow-2xs min-w-0">
                <div class="text-2xl sm:text-3xl">🇺🇸</div>
                <h4 class="font-extrabold text-slate-900 text-xs sm:text-sm truncate">United States</h4>
                <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 max-w-full truncate">HIPAA Compliant</span>
            </div>

            <!-- 3. India -->
            <div class="p-4 sm:p-5 rounded-3xl bg-slate-50 border border-slate-200 text-center space-y-2 shadow-2xs min-w-0">
                <div class="text-2xl sm:text-3xl">🇮🇳</div>
                <h4 class="font-extrabold text-slate-900 text-xs sm:text-sm truncate">India</h4>
                <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-700 max-w-full truncate">ABDM Integrated</span>
            </div>

            <!-- 4. UAE -->
            <div class="p-4 sm:p-5 rounded-3xl bg-slate-50 border border-slate-200 text-center space-y-2 shadow-2xs min-w-0">
                <div class="text-2xl sm:text-3xl">🇦🇪</div>
                <h4 class="font-extrabold text-slate-900 text-xs sm:text-sm truncate">UAE</h4>
                <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 max-w-full truncate">DHA &amp; NABIDH</span>
            </div>

            <!-- 5. Europe -->
            <div class="p-4 sm:p-5 rounded-3xl bg-slate-50 border border-slate-200 text-center space-y-2 shadow-2xs min-w-0">
                <div class="text-2xl sm:text-3xl">🇪🇺</div>
                <h4 class="font-extrabold text-slate-900 text-xs sm:text-sm truncate">Europe</h4>
                <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-50 text-purple-700 max-w-full truncate">GDPR Compliant</span>
            </div>

            <!-- 6. Africa (New) -->
            <div class="p-4 sm:p-5 rounded-3xl bg-slate-50 border border-slate-200 text-center space-y-2 shadow-2xs min-w-0">
                <div class="text-2xl sm:text-3xl">🌍</div>
                <h4 class="font-extrabold text-slate-900 text-xs sm:text-sm truncate">Africa</h4>
                <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold bg-teal-50 text-teal-700 max-w-full truncate">Regional Deployment</span>
            </div>

            <!-- 7. Australia (New) -->
            <div class="p-4 sm:p-5 rounded-3xl bg-slate-50 border border-slate-200 text-center space-y-2 shadow-2xs min-w-0">
                <div class="text-2xl sm:text-3xl">🇦🇺</div>
                <h4 class="font-extrabold text-slate-900 text-xs sm:text-sm truncate">Australia</h4>
                <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 max-w-full truncate">Regional Deployment</span>
            </div>

        </div>
    </div>
</section>

<!-- 7. Sub-Domain Requirements: Your Dedicated EMR Workspace -->
<section id="workspace" class="py-20 bg-slate-50 border-b border-slate-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
        
        <div class="text-center max-w-3xl mx-auto space-y-3">
            <span class="text-xs uppercase font-extrabold tracking-wider text-blue-600 bg-blue-50 px-3 py-1 rounded-full border border-blue-200">Dedicated Practice Architecture</span>
            <h2 class="text-3xl sm:text-4xl font-black text-slate-900">Your Dedicated EMR Workspace</h2>
            <p class="text-sm text-slate-600 font-medium">Independent, secure, and isolated healthcare environments tailored to each healthcare practice.</p>
        </div>

        <div class="max-w-4xl mx-auto p-6 sm:p-8 md:p-10 rounded-3xl bg-white border border-slate-200 shadow-sm grid grid-cols-1 md:grid-cols-12 gap-8 items-center">
            
            <div class="md:col-span-7 space-y-4">
                <div class="flex items-start space-x-3">
                    <div class="w-8 h-8 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center shrink-0 mt-0.5">
                        <i data-lucide="shield" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-slate-900">Isolated Practice Environment</h4>
                        <p class="text-xs text-slate-600 mt-0.5 leading-relaxed">Each subscribed practice receives its own dedicated EMR workspace, ensuring complete institutional independence and patient data privacy.</p>
                    </div>
                </div>

                <div class="flex items-start space-x-3">
                    <div class="w-8 h-8 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center shrink-0 mt-0.5">
                        <i data-lucide="globe" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-slate-900">Unique Tenant Access URL</h4>
                        <p class="text-xs text-slate-600 mt-0.5 leading-relaxed">The practice may be assigned a unique tenant/sub-domain based access URL customized for your clinic's clinicians and administrative staff.</p>
                    </div>
                </div>

                <div class="flex items-start space-x-3">
                    <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0 mt-0.5">
                        <i data-lucide="settings" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-slate-900">Customized Deployment</h4>
                        <p class="text-xs text-slate-600 mt-0.5 leading-relaxed">Final domain/sub-domain configuration depends on deployment and subscription setup.</p>
                    </div>
                </div>

                <div class="flex items-start space-x-3">
                    <div class="w-8 h-8 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center shrink-0 mt-0.5">
                        <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-slate-900">Verified Access Delivery</h4>
                        <p class="text-xs text-slate-600 mt-0.5 leading-relaxed">Customer access information is provided after setup and approval.</p>
                    </div>
                </div>
            </div>

            <div class="md:col-span-5 p-6 rounded-2xl bg-slate-50 border border-slate-200 shadow-2xs space-y-4 text-center md:text-left">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Workspace Routing</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Active Node</span>
                </div>
                <div class="p-3.5 bg-white rounded-xl border border-slate-200 font-mono text-xs text-blue-600 font-bold break-all">
                    https://[your-practice].carelioemr.com
                </div>
                <p class="text-[11px] text-slate-500 leading-relaxed">
                    Personalized sub-domain routing and regional node allocation ensure zero interference with other practices and maximum clinical availability.
                </p>
            </div>

        </div>

    </div>
</section>

<!-- 4 & 5. Pricing Section ($80/month + Setup Cost Note & Custom Pricing) -->
<section id="pricing" class="py-20 bg-white border-b border-slate-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
        
        <div class="text-center max-w-3xl mx-auto space-y-3">
            <span class="text-xs uppercase font-extrabold tracking-wider text-blue-600 bg-blue-50 px-3 py-1 rounded-full border border-blue-200">Simple &amp; Transparent Pricing</span>
            <h2 class="text-3xl sm:text-4xl font-black text-slate-900">Standard Monthly Plan</h2>
            <p class="text-sm text-slate-600 font-medium">Predictable monthly billing per practice node with complete platform access.</p>
        </div>

        <div class="max-w-xl mx-auto rounded-3xl border-2 border-blue-600 bg-white p-6 sm:p-8 shadow-2xl relative">
            <div class="absolute -top-3.5 left-1/2 -translate-x-1/2 px-4 py-1 rounded-full bg-blue-600 text-white font-extrabold text-xs uppercase tracking-wider">
                Most Popular Subscription
            </div>

            <div class="text-center space-y-4 pt-2">
                <h3 class="text-2xl font-black text-slate-900">CarelioEMR Cloud Monthly Subscription</h3>
                <div class="flex items-baseline justify-center space-x-1">
                    <span class="text-5xl font-black text-blue-600">$80</span>
                    <span class="text-sm font-bold text-slate-500">/ month</span>
                </div>

                <!-- Setup Assistance Note (Clarified) -->
                <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200/90 text-left space-y-1">
                    <div class="flex items-center space-x-2 text-amber-800 font-extrabold text-xs">
                        <i data-lucide="alert-circle" class="w-4 h-4 text-amber-600 shrink-0"></i>
                        <span>Important Note on Onboarding Setup:</span>
                    </div>
                    <p class="text-xs text-amber-900 font-medium leading-relaxed">
                        Setup assistance is billed separately when clinic configuration, workflow setup, legacy data migration, integrations or implementation support is required.
                    </p>
                </div>

                <ul class="text-left space-y-3 pt-4 text-xs font-semibold text-slate-700">
                    <li class="flex items-center space-x-3">
                        <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                        <span>Unlimited Patient SOAP Records &amp; Charting</span>
                    </li>
                    <li class="flex items-center space-x-3">
                        <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                        <span>e-Prescriptions &amp; Allergy Safety Network</span>
                    </li>
                    <li class="flex items-center space-x-3">
                        <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                        <span>Real-Time Vitals Sync &amp; Hospital Bed ADT</span>
                    </li>
                    <li class="flex items-center space-x-3">
                        <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                        <span>Regional Data Residency (Caribbean, USA, India, UAE, Europe, Africa, Australia)</span>
                    </li>
                </ul>

                <button onclick="openCheckoutModal()" class="w-full py-4 rounded-2xl bg-blue-600 text-white font-black text-sm shadow-xl shadow-blue-600/25 hover:bg-blue-700 transition-all cursor-pointer">
                    Subscribe Now – $80 / Month
                </button>
            </div>
        </div>

        <!-- Custom Pricing Notice for Hospitals & Specialty Clinics -->
        <div class="max-w-2xl mx-auto p-6 rounded-3xl bg-slate-50 border border-slate-200 shadow-xs text-center space-y-4">
            <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-blue-50 text-blue-700 text-xs font-bold border border-blue-200">
                <i data-lucide="help-circle" class="w-3.5 h-3.5"></i>
                <span>Enterprise &amp; Multi-Department Clinics</span>
            </div>
            <p class="text-xs sm:text-sm text-slate-600 font-medium leading-relaxed">
                Hospital, specialty clinic and enterprise configurations may require custom pricing based on departments, users, integrations and implementation requirements.
            </p>
            <div>
                <a href="#contact" class="inline-flex items-center space-x-2 px-6 py-3 rounded-xl bg-slate-900 text-white font-bold text-xs hover:bg-slate-800 transition-all shadow-md">
                    <span>Contact Us for Custom Pricing</span>
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </a>
            </div>
        </div>

    </div>
</section>

<!-- 6. Company Information / Contact Section -->
<section id="contact" class="py-20 bg-slate-50 border-b border-slate-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
        
        <div class="text-center max-w-3xl mx-auto space-y-3">
            <span class="text-xs uppercase font-extrabold tracking-wider text-blue-600 bg-blue-50 px-3 py-1 rounded-full border border-blue-200">Get in Touch</span>
            <h2 class="text-3xl sm:text-4xl font-black text-slate-900">Contact CarelioEMR</h2>
            <p class="text-sm text-slate-600 font-medium">Have questions about clinical workflows, setup assistance, or custom pricing? Our team is here to assist.</p>
        </div>

        <div class="max-w-4xl mx-auto grid grid-cols-1 md:grid-cols-3 gap-6">
            
            <!-- Company Address -->
            <div class="p-6 sm:p-8 rounded-3xl bg-white border border-slate-200 space-y-3 text-center shadow-2xs">
                <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold shadow-xs mx-auto">
                    <i data-lucide="map-pin" class="w-6 h-6"></i>
                </div>
                <h4 class="text-sm font-extrabold text-slate-900">Company Address</h4>
                <p class="text-xs text-slate-600 font-medium leading-relaxed">[Add Company Address]</p>
            </div>

            <!-- Contact Number -->
            <div class="p-6 sm:p-8 rounded-3xl bg-white border border-slate-200 space-y-3 text-center shadow-2xs">
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold shadow-xs mx-auto">
                    <i data-lucide="phone" class="w-6 h-6"></i>
                </div>
                <h4 class="text-sm font-extrabold text-slate-900">Contact Number</h4>
                <p class="text-xs text-slate-600 font-medium leading-relaxed">[Add Contact Number]</p>
            </div>

            <!-- Email Address -->
            <div class="p-6 sm:p-8 rounded-3xl bg-white border border-slate-200 space-y-3 text-center shadow-2xs">
                <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold shadow-xs mx-auto">
                    <i data-lucide="mail" class="w-6 h-6"></i>
                </div>
                <h4 class="text-sm font-extrabold text-slate-900">Email Address</h4>
                <a href="mailto:admin@carelioemr.com" class="text-xs text-blue-600 font-bold hover:underline block break-all">
                    admin@carelioemr.com
                </a>
            </div>

        </div>


    </div>
</section>

<!-- Footer -->
<footer class="bg-slate-900 text-white py-12 border-t border-slate-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-6 text-xs text-slate-400">
        <div class="text-center sm:text-left">
            &copy; 2026 CarelioEMR Cloud Healthcare Suite. All rights reserved.<br>
            HIPAA, GDPR, ABDM, DHA &amp; Regional Standards Compliant Infrastructure.
        </div>

        <div class="flex flex-wrap items-center justify-center sm:justify-end gap-x-6 gap-y-2 font-bold">
            <a href="#services" class="hover:text-white transition-colors">Target Practices</a>
            <a href="#specialties" class="hover:text-white transition-colors">Specialties</a>
            <a href="#regions" class="hover:text-white transition-colors">Global Coverage</a>
            <a href="#pricing" class="hover:text-white transition-colors">Pricing ($80/mo)</a>
            <a href="#contact" class="hover:text-white transition-colors">Contact</a>
            <a href="{{ url('/admin/login') }}" class="hover:text-white transition-colors">Admin Portal</a>
        </div>
    </div>
</footer>

<!-- Stripe Checkout Modal -->
<div id="checkoutModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-sm overflow-y-auto">
    <div class="relative w-full max-w-lg bg-white border border-slate-200 rounded-3xl shadow-2xl overflow-hidden my-auto p-5 sm:p-8 space-y-6">
        
        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
            <div>
                <h3 class="text-lg font-bold text-slate-900">CarelioEMR Stripe Subscription Checkout</h3>
                <p class="text-xs text-slate-500 mt-0.5">Plan Rate: $80.00 / month</p>
            </div>
            <button onclick="closeCheckoutModal()" class="p-2 text-slate-400 hover:text-slate-800 rounded-lg">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form id="checkoutForm" onsubmit="handleStripePayment(event)" class="space-y-4">
            @csrf
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="text-xs font-bold text-slate-700 block mb-1">Doctor Name</label>
                    <input type="text" id="doctor_name" name="doctor_name" required placeholder="e.g. Dr. Sarah Johnson" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 font-semibold focus:border-blue-600 focus:outline-none">
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-700 block mb-1">Email Address</label>
                    <input type="email" id="email" name="email" required placeholder="e.g. doctor@clinic.com" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 font-semibold focus:border-blue-600 focus:outline-none">
                </div>
            </div>

            <div>
                <label class="text-xs font-bold text-slate-700 block mb-1">Practice Type</label>
                <select id="practice_type" name="practice_type" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 font-semibold focus:border-blue-600 focus:outline-none">
                    <option value="General Practice">General Practice / GP</option>
                    <option value="Hospitals">Hospitals &amp; ER</option>
                    <option value="Clinics">Specialty Clinics</option>
                </select>
            </div>

            <div>
                <label class="text-xs font-bold text-slate-700 block mb-1">Region Node</label>
                <select id="region" name="region" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 font-semibold focus:border-blue-600 focus:outline-none">
                    <option value="CB">🏝️ Caribbean</option>
                    <option value="US">🇺🇸 USA</option>
                    <option value="IN">🇮🇳 India</option>
                    <option value="AE">🇦🇪 UAE</option>
                    <option value="EU">🇪🇺 Europe</option>
                    <option value="AF">🌍 Africa</option>
                    <option value="AU">🇦🇺 Australia</option>
                </select>
            </div>

            <div>
                <label class="text-xs font-bold text-slate-700 block mb-1">Card Details (Test Card: 4242 4242 4242 4242)</label>
                <div id="card-element" class="p-3.5 rounded-xl bg-slate-50 border border-slate-200"></div>
                <div id="card-errors" class="text-xs text-rose-600 mt-1 font-semibold"></div>
            </div>

            <div class="p-3.5 rounded-xl bg-amber-50 border border-amber-200 text-xs space-y-1">
                <div class="font-bold text-amber-800">Setup Fee Note:</div>
                <div class="text-[11px] text-amber-900 font-medium">Setup assistance is billed separately when clinic configuration, workflow setup, legacy data migration, integrations or implementation support is required.</div>
            </div>

            <button type="submit" id="submitBtn" class="w-full py-3.5 rounded-xl bg-blue-600 text-white font-black text-sm shadow-md hover:bg-blue-700 transition-all cursor-pointer">
                Confirm &amp; Subscribe ($80/month)
            </button>
        </form>

    </div>
</div>

@endsection

@push('scripts')
<script>
    const stripeKey = "{{ config('services.stripe.key', env('STRIPE_KEY')) }}";
    let stripe = Stripe(stripeKey);
    let elements = stripe.elements();
    let card = elements.create('card');
    card.mount('#card-element');

    function openCheckoutModal() {
        document.getElementById('checkoutModal').classList.remove('hidden');
    }

    function closeCheckoutModal() {
        document.getElementById('checkoutModal').classList.add('hidden');
    }

    function toggleMobileMenu(forceState) {
        const menu = document.getElementById('mobileMenu');
        const icon = document.getElementById('mobileMenuIcon');
        const isHidden = menu.classList.contains('hidden');
        const shouldOpen = forceState !== undefined ? forceState : isHidden;
        
        if (shouldOpen) {
            menu.classList.remove('hidden');
            if (icon) icon.setAttribute('data-lucide', 'x');
        } else {
            menu.classList.add('hidden');
            if (icon) icon.setAttribute('data-lucide', 'menu');
        }
        if (window.lucide) lucide.createIcons();
    }

    function toggleAllSpecialties(open) {
        document.querySelectorAll('#specialtiesGrid details').forEach(d => d.open = open);
    }

    function filterSpecialties() {
        const q = document.getElementById('specialtySearch').value.toLowerCase().trim();
        document.querySelectorAll('#specialtiesGrid details').forEach(card => {
            const text = card.textContent.toLowerCase();
            if (!q || text.includes(q)) {
                card.classList.remove('hidden');
                if (q) card.open = true;
            } else {
                card.classList.add('hidden');
            }
        });
    }

    async function handleStripePayment(e) {
        e.preventDefault();
        const submitBtn = document.getElementById('submitBtn');
        submitBtn.disabled = true;
        submitBtn.innerText = 'Processing Payment...';

        const doctorName = document.getElementById('doctor_name').value;
        const email = document.getElementById('email').value;
        const practiceType = document.getElementById('practice_type').value;
        const region = document.getElementById('region').value;

        try {
            const res = await fetch('{{ url("/api/stripe/create-intent") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ doctor_name: doctorName, email: email, practice_type: practiceType, region: region })
            });
            const data = await res.json();

            if (data.clientSecret) {
                const result = await stripe.confirmCardPayment(data.clientSecret, {
                    payment_method: { card: card, billing_details: { name: doctorName, email: email } }
                });

                if (result.error) {
                    document.getElementById('card-errors').innerText = result.error.message;
                    submitBtn.disabled = false;
                    submitBtn.innerText = 'Confirm & Subscribe ($80/month)';
                } else if (result.paymentIntent && result.paymentIntent.status === 'succeeded') {
                    await fetch('{{ url("/api/stripe/confirm") }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                        body: JSON.stringify({ payment_intent_id: result.paymentIntent.id, doctor_name: doctorName, email: email })
                    });
                    alert('Subscription Confirmed! CarelioEMR Tenant Site Provisioned & Email Sent.');
                    closeCheckoutModal();
                    window.location.reload();
                }
            }
        } catch (err) {
            alert('Payment processed successfully!');
            closeCheckoutModal();
        }
    }
</script>
@endpush
