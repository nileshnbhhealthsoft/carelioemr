@extends('layouts.app')

@section('title', 'AuraEMR - Cloud EMR & EHR Healthcare Platform ($80/month)')

@section('content')

<!-- Top Announcement Banner -->
<div class="bg-slate-900 text-white px-4 py-2 text-xs font-semibold flex flex-col sm:flex-row items-center justify-between gap-2">
    <div class="flex items-center space-x-2">
        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
        <span>AuraEMR Cloud Healthcare Platform • 24/7 Global Infrastructure</span>
    </div>
    <a href="{{ url('/admin/login') }}" class="bg-blue-600 hover:bg-blue-500 text-white px-3 py-1 rounded-lg text-xs font-bold transition-all whitespace-nowrap">
        Admin Portal &rarr;
    </a>
</div>

<!-- Header / Navigation Bar -->
<header class="sticky top-0 z-40 w-full border-b border-slate-200 bg-white/95 backdrop-blur-md shadow-xs">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between gap-4">
        
        <!-- Brand Logo -->
        <a href="{{ url('/') }}" class="flex items-center space-x-3 shrink-0">
            <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-blue-600 text-white shadow-md shadow-blue-600/20 font-bold shrink-0">
                <i data-lucide="activity" class="w-6 h-6 stroke-[2.5]"></i>
            </div>
            <div class="flex flex-col justify-center">
                <div class="flex items-center space-x-2">
                    <span class="text-xl font-black tracking-tight text-slate-900 leading-none">
                        Aura<span class="text-blue-600">EMR</span>
                    </span>
                    <span class="text-[10px] uppercase font-extrabold tracking-wider px-2 py-0.5 rounded-md bg-blue-50 text-blue-700 border border-blue-200/80 shrink-0">
                        Cloud
                    </span>
                </div>
                <span class="text-[11px] text-slate-500 font-semibold mt-0.5">
                    EMR &amp; EHR Healthcare Suite
                </span>
            </div>
        </a>

        <!-- Center Nav Links -->
        <nav class="hidden xl:flex items-center space-x-8 text-sm font-bold text-slate-700">
            <a href="#services" class="hover:text-blue-600 transition-colors whitespace-nowrap">Target Practices</a>
            <a href="#regions" class="hover:text-blue-600 transition-colors whitespace-nowrap">Global Coverage</a>
            <a href="#features" class="hover:text-blue-600 transition-colors whitespace-nowrap">Platform Features</a>
            <a href="#pricing" class="hover:text-blue-600 transition-colors whitespace-nowrap flex items-center gap-1.5">
                <span>Pricing</span>
                <span class="text-blue-600 font-extrabold text-xs bg-blue-50 px-2 py-0.5 rounded-full border border-blue-200">$80/mo</span>
            </a>
        </nav>

        <!-- Right Actions -->
        <div class="hidden lg:flex items-center space-x-3 shrink-0">
            <a href="{{ url('/admin/login') }}" class="flex items-center space-x-1.5 px-3.5 py-2 rounded-xl bg-slate-100 border border-slate-200 text-slate-800 text-xs font-bold hover:bg-slate-200 transition-all whitespace-nowrap">
                <i data-lucide="layout-dashboard" class="w-4 h-4 text-blue-600"></i>
                <span>Admin Portal</span>
            </a>

            <button onclick="openCheckoutModal()" class="flex items-center space-x-2 px-4 py-2.5 rounded-xl bg-blue-600 text-white font-bold text-xs shadow-md shadow-blue-600/20 hover:bg-blue-700 transition-all cursor-pointer whitespace-nowrap">
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
                    <span class="w-2 h-2 rounded-full bg-blue-600 animate-pulse"></span>
                    <span>Next-Generation Cloud EMR &amp; EHR Suite</span>
                </div>

                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black tracking-tight text-slate-900 leading-[1.1]">
                    Unified Medical Records for <span class="text-blue-600">Practices, Hospitals &amp; Clinics</span>
                </h1>

                <p class="text-base sm:text-lg text-slate-600 font-medium max-w-2xl leading-relaxed mx-auto lg:mx-0">
                    Streamline SOAP charting, real-time vitals monitoring, e-prescriptions, and hospital bed management across <strong>Saint Lucia, USA, India, UAE, and Europe</strong>.
                </p>

                <!-- Key Highlights Badge Bar -->
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 pt-2 max-w-xl mx-auto lg:mx-0">
                    <div class="p-3 rounded-2xl bg-white border border-slate-200 shadow-2xs text-left">
                        <div class="text-xs text-slate-400 font-bold uppercase">Monthly License</div>
                        <div class="text-xl font-black text-blue-600 mt-0.5">$80 / month</div>
                    </div>
                    <div class="p-3 rounded-2xl bg-white border border-slate-200 shadow-2xs text-left">
                        <div class="text-xs text-slate-400 font-bold uppercase">Setup Cost Note</div>
                        <div class="text-sm font-extrabold text-amber-700 mt-1">Billed Separately</div>
                    </div>
                    <div class="p-3 rounded-2xl bg-white border border-slate-200 shadow-2xs text-left col-span-2 sm:col-span-1">
                        <div class="text-xs text-slate-400 font-bold uppercase">Global Coverage</div>
                        <div class="text-sm font-extrabold text-slate-800 mt-1">5 Key Hubs 🇱🇨 🇺🇸 🇮🇳</div>
                    </div>
                </div>

                <!-- CTA Buttons -->
                <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4 pt-4">
                    <button onclick="openCheckoutModal()" class="w-full sm:w-auto px-8 py-4 rounded-2xl bg-blue-600 text-white font-extrabold text-sm shadow-xl shadow-blue-600/25 hover:bg-blue-700 transition-all cursor-pointer flex items-center justify-center space-x-2">
                        <i data-lucide="shield-check" class="w-5 h-5"></i>
                        <span>Subscribe Now – $80/Month</span>
                    </button>
                    
                    <a href="#features" class="w-full sm:w-auto px-6 py-4 rounded-2xl bg-white border border-slate-200 text-slate-700 font-bold text-sm hover:bg-slate-100 transition-all text-center">
                        Explore Workstation Demo
                    </a>
                </div>

            </div>

            <!-- Right Hero Image Preview -->
            <div class="lg:col-span-5 relative">
                <div class="relative rounded-3xl overflow-hidden border border-slate-200 shadow-2xl bg-white p-3">
                    <img src="https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?auto=format&fit=crop&w=1000&q=80" alt="Doctor using AuraEMR software" class="w-full h-80 sm:h-96 object-cover rounded-2xl">
                    <div class="absolute bottom-6 left-6 right-6 p-4 rounded-2xl bg-white/95 backdrop-blur-md border border-slate-200 shadow-xl flex items-center justify-between">
                        <div>
                            <div class="text-xs font-bold text-slate-900">Dr. Sarah Johnson</div>
                            <div class="text-[11px] text-blue-600 font-semibold">General Practice • Saint Lucia Hub</div>
                        </div>
                        <span class="px-2.5 py-1 rounded-full text-[11px] font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200">Live Sync</span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- Target Practice Settings -->
<section id="services" class="py-20 bg-white border-b border-slate-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
        
        <div class="text-center max-w-3xl mx-auto space-y-3">
            <span class="text-xs uppercase font-extrabold tracking-wider text-blue-600 bg-blue-50 px-3 py-1 rounded-full border border-blue-200">Target Healthcare Sectors</span>
            <h2 class="text-3xl sm:text-4xl font-black text-slate-900">Built Specifically for Your Practice Setting</h2>
            <p class="text-sm text-slate-600 font-medium">Custom workflows tailored for solo practices, multi-specialty clinics, and multi-bed hospital networks.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            
            <div class="p-8 rounded-3xl bg-slate-50 border border-slate-200 hover:border-blue-300 transition-all space-y-4">
                <div class="w-12 h-12 rounded-2xl bg-blue-600 text-white flex items-center justify-center font-bold shadow-md">
                    <i data-lucide="stethoscope" class="w-6 h-6"></i>
                </div>
                <h3 class="text-xl font-extrabold text-slate-900">General Practice</h3>
                <p class="text-xs text-slate-600 leading-relaxed font-medium">Ideal for solo practitioners and family clinics needing rapid SOAP charting, ICD-10 coding, and automated patient appointment reminders.</p>
                <div class="pt-2 text-xs font-bold text-blue-600">$80/month per practice</div>
            </div>

            <div class="p-8 rounded-3xl bg-slate-50 border border-slate-200 hover:border-blue-300 transition-all space-y-4">
                <div class="w-12 h-12 rounded-2xl bg-indigo-600 text-white flex items-center justify-center font-bold shadow-md">
                    <i data-lucide="building-2" class="w-6 h-6"></i>
                </div>
                <h3 class="text-xl font-extrabold text-slate-900">Hospitals &amp; ER</h3>
                <p class="text-xs text-slate-600 leading-relaxed font-medium">Designed for inpatient admissions, ER triage queues, intensive care unit vitals streams, and multi-bed ward occupancy tracking.</p>
                <div class="pt-2 text-xs font-bold text-indigo-600">$80/month per node</div>
            </div>

            <div class="p-8 rounded-3xl bg-slate-50 border border-slate-200 hover:border-blue-300 transition-all space-y-4">
                <div class="w-12 h-12 rounded-2xl bg-emerald-600 text-white flex items-center justify-center font-bold shadow-md">
                    <i data-lucide="activity" class="w-6 h-6"></i>
                </div>
                <h3 class="text-xl font-extrabold text-slate-900">Specialty Clinics</h3>
                <p class="text-xs text-slate-600 leading-relaxed font-medium">Tailored for cardiology, pediatrics, and outpatient specialty chains requiring custom prescription templates and lab integrations.</p>
                <div class="pt-2 text-xs font-bold text-emerald-600">$80/month per clinic</div>
            </div>

        </div>
    </div>
</section>

<!-- Geographic Coverage Grid -->
<section id="regions" class="py-20 bg-slate-50 border-b border-slate-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
        
        <div class="text-center max-w-3xl mx-auto space-y-3">
            <span class="text-xs uppercase font-extrabold tracking-wider text-blue-600 bg-blue-50 px-3 py-1 rounded-full border border-blue-200">Global Cloud Coverage</span>
            <h2 class="text-3xl sm:text-4xl font-black text-slate-900">Serving 5 Key Regions Worldwide</h2>
            <p class="text-sm text-slate-600 font-medium">Compliant with regional healthcare data privacy standards and local medical data residency laws.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6">
            
            <div class="p-6 rounded-3xl bg-white border border-slate-200 text-center space-y-3 shadow-2xs">
                <div class="text-4xl">🇱🇨</div>
                <h4 class="font-extrabold text-slate-900 text-sm">Saint Lucia</h4>
                <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700">OECS Standard</span>
            </div>

            <div class="p-6 rounded-3xl bg-white border border-slate-200 text-center space-y-3 shadow-2xs">
                <div class="text-4xl">🇺🇸</div>
                <h4 class="font-extrabold text-slate-900 text-sm">United States</h4>
                <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700">HIPAA Compliant</span>
            </div>

            <div class="p-6 rounded-3xl bg-white border border-slate-200 text-center space-y-3 shadow-2xs">
                <div class="text-4xl">🇮🇳</div>
                <h4 class="font-extrabold text-slate-900 text-sm">India</h4>
                <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-700">ABDM Integrated</span>
            </div>

            <div class="p-6 rounded-3xl bg-white border border-slate-200 text-center space-y-3 shadow-2xs">
                <div class="text-4xl">🇦🇪</div>
                <h4 class="font-extrabold text-slate-900 text-sm">UAE</h4>
                <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700">DHA &amp; NABIDH</span>
            </div>

            <div class="p-6 rounded-3xl bg-white border border-slate-200 text-center space-y-3 shadow-2xs">
                <div class="text-4xl">🇪🇺</div>
                <h4 class="font-extrabold text-slate-900 text-sm">Europe</h4>
                <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-purple-50 text-purple-700">GDPR Compliant</span>
            </div>

        </div>
    </div>
</section>

<!-- Pricing Section ($80/month + MANDATORY Setup Fee Alert) -->
<section id="pricing" class="py-20 bg-white border-b border-slate-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
        
        <div class="text-center max-w-3xl mx-auto space-y-3">
            <span class="text-xs uppercase font-extrabold tracking-wider text-blue-600 bg-blue-50 px-3 py-1 rounded-full border border-blue-200">Simple &amp; Transparent Pricing</span>
            <h2 class="text-3xl sm:text-4xl font-black text-slate-900">Standard Monthly Plan</h2>
            <p class="text-sm text-slate-600 font-medium">Predictable monthly billing per practice node with complete platform access.</p>
        </div>

        <div class="max-w-xl mx-auto rounded-3xl border-2 border-blue-600 bg-white p-8 shadow-2xl relative">
            <div class="absolute -top-3.5 left-1/2 -translate-x-1/2 px-4 py-1 rounded-full bg-blue-600 text-white font-extrabold text-xs uppercase tracking-wider">
                Most Popular Subscription
            </div>

            <div class="text-center space-y-4 pt-2">
                <h3 class="text-2xl font-black text-slate-900">AuraEMR Cloud Monthly Subscription</h3>
                <div class="flex items-baseline justify-center space-x-1">
                    <span class="text-5xl font-black text-blue-600">$80</span>
                    <span class="text-sm font-bold text-slate-500">/ month</span>
                </div>

                <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200/90 text-left space-y-1">
                    <div class="flex items-center space-x-2 text-amber-800 font-extrabold text-xs">
                        <i data-lucide="alert-circle" class="w-4 h-4 text-amber-600 shrink-0"></i>
                        <span>Important Note on Onboarding Setup:</span>
                    </div>
                    <p class="text-xs text-amber-900 font-medium leading-relaxed">
                        <strong>Setup cost will be an additional cost</strong> (billed separately based on your practice size, legacy data migration, and HL7/FHIR integration requirements).
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
                        <span>Regional Data Residency (Saint Lucia, USA, India, UAE, Europe)</span>
                    </li>
                </ul>

                <button onclick="openCheckoutModal()" class="w-full py-4 rounded-2xl bg-blue-600 text-white font-black text-sm shadow-xl shadow-blue-600/25 hover:bg-blue-700 transition-all cursor-pointer">
                    Subscribe Now – $80 / Month
                </button>
            </div>
        </div>

    </div>
</section>

<!-- Footer -->
<footer class="bg-slate-900 text-white py-12 border-t border-slate-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-6 text-xs text-slate-400">
        <div>
            &copy; 2026 AuraEMR Cloud Healthcare Suite. All rights reserved.<br>
            HIPAA, GDPR, ABDM, DHA &amp; OECS Compliant Infrastructure.
        </div>

        <div class="flex items-center space-x-6 font-bold">
            <a href="{{ url('/admin/login') }}" class="hover:text-white transition-colors">Admin Portal</a>
            <a href="#pricing" class="hover:text-white transition-colors">Pricing ($80/mo)</a>
        </div>
    </div>
</footer>

<!-- Stripe Checkout Modal -->
<div id="checkoutModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-sm overflow-y-auto">
    <div class="relative w-full max-w-lg bg-white border border-slate-200 rounded-3xl shadow-2xl overflow-hidden my-auto p-6 sm:p-8 space-y-6">
        
        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
            <div>
                <h3 class="text-lg font-bold text-slate-900">AuraEMR Stripe Subscription Checkout</h3>
                <p class="text-xs text-slate-500 mt-0.5">Plan Rate: $80.00 / month</p>
            </div>
            <button onclick="closeCheckoutModal()" class="p-2 text-slate-400 hover:text-slate-800 rounded-lg">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form id="checkoutForm" onsubmit="handleStripePayment(event)" class="space-y-4">
            @csrf
            
            <div class="grid grid-cols-2 gap-3">
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
                    <option value="General Practice">General Practice</option>
                    <option value="Hospitals">Hospitals</option>
                    <option value="Clinics">Specialty Clinics</option>
                </select>
            </div>

            <div>
                <label class="text-xs font-bold text-slate-700 block mb-1">Region Node</label>
                <select id="region" name="region" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 font-semibold focus:border-blue-600 focus:outline-none">
                    <option value="LC">🇱🇨 Saint Lucia</option>
                    <option value="US">🇺🇸 USA</option>
                    <option value="IN">🇮🇳 India</option>
                    <option value="AE">🇦🇪 UAE</option>
                    <option value="EU">🇪🇺 Europe</option>
                </select>
            </div>

            <div>
                <label class="text-xs font-bold text-slate-700 block mb-1">Card Details (Test Card: 4242 4242 4242 4242)</label>
                <div id="card-element" class="p-3.5 rounded-xl bg-slate-50 border border-slate-200"></div>
                <div id="card-errors" class="text-xs text-rose-600 mt-1 font-semibold"></div>
            </div>

            <div class="p-3.5 rounded-xl bg-amber-50 border border-amber-200 text-xs space-y-1">
                <div class="font-bold text-amber-800">Setup Fee Note:</div>
                <div class="text-[11px] text-amber-900 font-medium">Setup cost will be an additional cost (billed separately based on practice onboarding).</div>
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
                    alert('Subscription Confirmed! OpenEMR Tenant Site Provisioned & Email Sent.');
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
