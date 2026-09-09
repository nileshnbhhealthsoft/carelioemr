import React from 'react';
import { Check, CreditCard, ShieldCheck, HelpCircle, AlertCircle, ArrowRight, Sparkles, Building, Info, Lock, Zap } from 'lucide-react';

export default function PricingSection({ onOpenCheckout, selectedRegion }) {
  return (
    <section id="pricing" className="py-24 bg-light-mesh border-t border-slate-200 relative">
      
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        
        {/* Header */}
        <div className="text-center max-w-3xl mx-auto">
          <div className="inline-flex items-center space-x-2 px-4 py-1.5 rounded-full bg-white border border-blue-200 text-blue-700 text-xs font-bold uppercase tracking-wider mb-4 shadow-xs">
            <Zap className="w-4 h-4 text-blue-600" />
            <span>Transparent Subscription Model</span>
          </div>
          <h2 className="text-3xl sm:text-5xl font-extrabold text-slate-900 tracking-tight leading-tight">
            Straightforward Cloud Pricing
          </h2>
          <p className="mt-4 text-slate-600 text-base sm:text-lg">
            Complete EMR &amp; EHR software suite for General Practice, Hospitals, and Clinics for one predictable monthly rate.
          </p>
        </div>

        {/* Pricing Card */}
        <div className="mt-14 max-w-4xl mx-auto">
          
          <div className="white-card white-card-hover border-2 border-blue-500 rounded-3xl p-8 sm:p-14 shadow-2xl relative overflow-hidden">
            
            {/* Top Badge */}
            <div className="absolute top-0 right-0">
              <div className="bg-gradient-to-l from-blue-600 to-cyan-600 text-white font-black text-xs uppercase tracking-wider px-6 py-2 rounded-bl-2xl shadow-md">
                Stripe Test Mode Integrated
              </div>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-12 gap-10 items-center">
              
              {/* Left Column: Price & Setup Note */}
              <div className="lg:col-span-6 space-y-6">
                
                <div>
                  <span className="px-3.5 py-1 rounded-full text-xs font-extrabold bg-blue-50 text-blue-700 border border-blue-200">
                    Full Platform Access
                  </span>
                  
                  {/* Big Price Tag */}
                  <div className="mt-4 flex items-baseline space-x-3">
                    <span className="text-5xl sm:text-7xl font-black text-slate-900">$80</span>
                    <div className="text-slate-500 font-bold">
                      <span className="text-xl text-slate-900 block">/ month</span>
                      <span className="text-xs text-slate-500 font-normal">billed monthly per practice</span>
                    </div>
                  </div>

                  {/* PROMINENT MANDATORY SETUP COST ALERT */}
                  <div className="mt-6 p-4 rounded-2xl bg-orange-50 border border-orange-200 text-orange-950 text-xs sm:text-sm font-medium space-y-1.5 shadow-xs">
                    <div className="flex items-center space-x-2 text-orange-700 font-black text-sm">
                      <AlertCircle className="w-5 h-5 shrink-0 text-orange-600" />
                      <span>Setup cost will be an additional cost.</span>
                    </div>
                    <p className="text-orange-900/90 text-xs leading-relaxed pl-7 font-medium">
                      Initial setup is billed separately based on clinic size, legacy database migration, custom HL7/FHIR interface setup, and staff onboarding.
                    </p>
                  </div>
                </div>

                {/* Regional Availability Summary */}
                <div className="p-4 rounded-2xl bg-slate-50 border border-slate-200 text-xs text-slate-700 flex items-center justify-between shadow-xs">
                  <span className="font-bold text-slate-500">Available Regions:</span>
                  <span className="font-extrabold text-blue-700 flex items-center gap-1.5">
                    <span>🇱🇨 🇺🇸 🇮🇳 🇦🇪 🇪🇺</span>
                  </span>
                </div>

                {/* Direct Stripe Subscribe Button */}
                <div className="space-y-3">
                  <button
                    onClick={onOpenCheckout}
                    className="w-full py-4 rounded-2xl bg-blue-600 text-white font-black text-base shadow-lg shadow-blue-600/25 hover:bg-blue-700 hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center justify-center space-x-2 cursor-pointer"
                  >
                    <CreditCard className="w-5 h-5 stroke-[2.5]" />
                    <span>Subscribe Now – $80 / Month</span>
                  </button>
                  <div className="text-center text-[11px] text-slate-500 flex items-center justify-center gap-1.5 font-medium">
                    <Lock className="w-3.5 h-3.5 text-blue-600" />
                    <span>Stripe API Test Checkout (Connected to your key)</span>
                  </div>
                </div>

              </div>

              {/* Right Column: Included Features */}
              <div className="lg:col-span-6 bg-slate-50 rounded-2xl p-6 border border-slate-200 space-y-4 shadow-sm">
                
                <div className="text-xs uppercase font-extrabold tracking-wider text-blue-700 border-b border-slate-200 pb-3">
                  Included in your $80/mo Plan:
                </div>

                <div className="space-y-3">
                  {[
                    'Full EMR & EHR Patient Charting Modules',
                    'Digital e-Prescriptions with Drug Safety Alerts',
                    'Integrated HD Telehealth Video Consultations',
                    'Medical Billing Engine & Claims Generation',
                    'Regional Compliance (HIPAA, GDPR, ABDM, DHA, OECS)',
                    'Unlimited Patient Record Storage & Cloud Backups',
                    'Multi-Device Support (Desktop, Tablet, Mobile)',
                    '24/7 Clinical & Technical Priority Support'
                  ].map((feat, idx) => (
                    <div key={idx} className="flex items-start space-x-2.5 text-xs text-slate-800 font-semibold">
                      <Check className="w-4 h-4 text-emerald-600 mt-0.5 shrink-0" />
                      <span>{feat}</span>
                    </div>
                  ))}
                </div>

                <div className="pt-3 border-t border-slate-200 text-[11px] text-slate-500 flex items-center justify-between font-medium">
                  <span>Billing Interval: Monthly</span>
                  <span className="text-blue-700 font-bold">Cancel Anytime</span>
                </div>

              </div>

            </div>

          </div>

          {/* Setup Cost FAQ Box */}
          <div className="mt-8 p-6 rounded-2xl bg-white border border-slate-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-xs">
            <div className="flex items-start space-x-3">
              <Info className="w-5 h-5 text-blue-600 shrink-0 mt-0.5" />
              <div>
                <h4 className="text-sm font-bold text-slate-900">How is Setup Cost calculated?</h4>
                <p className="text-xs text-slate-500 mt-0.5 font-medium">
                  Setup costs are customized based on practice size, existing data migration complexity, and staff training requirements.
                </p>
              </div>
            </div>
            <a
              href="#faq"
              className="text-xs font-bold text-blue-700 hover:text-blue-800 border border-blue-200 px-4 py-2 rounded-xl whitespace-nowrap bg-blue-50"
            >
              Read Setup Cost FAQ
            </a>
          </div>

        </div>

      </div>
    </section>
  );
}
