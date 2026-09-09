import React from 'react';
import { ShieldCheck, CheckCircle2, ArrowRight, Sparkles, Building2, Stethoscope, Hospital, CreditCard, Lock, Globe, AlertCircle, Heart, Activity } from 'lucide-react';

export default function HeroSection({ onOpenCheckout, selectedRegion }) {
  return (
    <section className="relative overflow-hidden pt-12 pb-20 lg:pt-16 lg:pb-28 bg-light-mesh">
      
      {/* Background Dot Pattern */}
      <div className="absolute inset-0 bg-dot-pattern opacity-60 pointer-events-none" />

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        
        {/* Top Announcement Badge */}
        <div className="flex justify-center mb-6">
          <div className="inline-flex items-center space-x-2.5 px-4 py-1.5 rounded-full bg-white border border-blue-200 text-blue-700 text-xs font-bold shadow-md">
            <span className="w-2 h-2 rounded-full bg-blue-600 animate-ping" />
            <span>Cloud EMR &amp; EHR Healthcare Suite</span>
            <span className="text-slate-300">•</span>
            <span className="text-slate-900 font-extrabold">$80 / month</span>
            <span className="text-slate-300">•</span>
            <span className="text-orange-600 font-bold">* Setup cost extra</span>
          </div>
        </div>

        {/* Hero Headline */}
        <div className="text-center max-w-4xl mx-auto">
          <h1 className="text-4xl sm:text-6xl lg:text-7xl font-extrabold tracking-tight text-slate-900 leading-[1.1]">
            Modern Cloud EMR &amp; EHR for <br className="hidden sm:inline" />
            <span className="text-gradient-blue">
              General Practice, Hospitals &amp; Clinics
            </span>
          </h1>

          <p className="mt-6 text-base sm:text-xl text-slate-600 max-w-3xl mx-auto leading-relaxed font-normal">
            Streamline patient charting, issue digital prescriptions, and automate medical billing with a compliant cloud platform built for clinical performance.
          </p>
        </div>

        {/* Global Regional Coverage Badges */}
        <div className="mt-8 flex flex-wrap justify-center items-center gap-2 sm:gap-3 text-xs sm:text-sm">
          <span className="flex items-center text-slate-700 font-bold mr-1">
            <Globe className="w-4 h-4 text-blue-600 mr-1.5" /> Service Availability:
          </span>
          {[
            { flag: '🇱🇨', name: 'Saint Lucia' },
            { flag: '🇺🇸', name: 'USA' },
            { flag: '🇮🇳', name: 'India' },
            { flag: '🇦🇪', name: 'UAE' },
            { flag: '🇪🇺', name: 'Europe' },
          ].map((reg, idx) => (
            <span
              key={idx}
              className="px-3 py-1.5 bg-white border border-slate-200 rounded-full text-slate-800 text-xs font-semibold shadow-xs flex items-center gap-1.5 hover:border-blue-300 transition-all cursor-default"
            >
              <span className="text-base">{reg.flag}</span>
              <span>{reg.name}</span>
            </span>
          ))}
        </div>

        {/* Pricing Card & Action Button Bar */}
        <div className="mt-10 max-w-3xl mx-auto white-card white-card-hover rounded-3xl p-6 sm:p-8 shadow-xl relative overflow-hidden">
          
          <div className="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-blue-600 via-cyan-500 to-emerald-500" />
          
          <div className="flex flex-col sm:flex-row items-center justify-between gap-6">
            
            <div className="text-center sm:text-left">
              <div className="text-xs uppercase tracking-widest font-extrabold text-blue-600">Fixed Monthly Plan</div>
              <div className="mt-1 flex items-baseline justify-center sm:justify-start space-x-2">
                <span className="text-4xl sm:text-5xl font-black text-slate-900">$80</span>
                <span className="text-base text-slate-500 font-bold">/ month</span>
              </div>
              <p className="mt-2 text-xs text-orange-700 font-bold flex items-center justify-center sm:justify-start gap-1.5 bg-orange-50 px-3 py-1 rounded-lg border border-orange-200">
                <AlertCircle className="w-4 h-4 text-orange-600 shrink-0" />
                <span>Setup cost will be an additional cost.</span>
              </p>
            </div>

            <div className="w-full sm:w-auto flex flex-col gap-3">
              <button
                onClick={onOpenCheckout}
                className="w-full sm:w-auto px-8 py-4 rounded-2xl bg-blue-600 text-white font-black text-sm shadow-lg shadow-blue-600/25 hover:bg-blue-700 hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center justify-center space-x-2 cursor-pointer"
              >
                <CreditCard className="w-5 h-5 stroke-[2.5]" />
                <span>Subscribe Now – $80 / mo</span>
                <ArrowRight className="w-4 h-4 stroke-[2.5]" />
              </button>

              <a
                href="#pricing"
                className="text-center text-xs font-semibold text-slate-500 hover:text-blue-600 transition-colors"
              >
                View setup cost details &amp; Stripe checkout
              </a>
            </div>

          </div>

          <div className="mt-6 pt-4 border-t border-slate-100 flex flex-wrap justify-between items-center text-xs text-slate-600 gap-3">
            <span className="flex items-center gap-1.5 font-semibold text-slate-700">
              <ShieldCheck className="w-4 h-4 text-emerald-600" /> HIPAA, GDPR &amp; OECS Compliant
            </span>
            <span className="flex items-center gap-1.5 font-semibold text-slate-700">
              <Lock className="w-4 h-4 text-blue-600" /> Stripe API Test Mode Integrated
            </span>
            <span className="flex items-center gap-1.5 font-semibold text-slate-700">
              <CheckCircle2 className="w-4 h-4 text-emerald-600" /> 24/7 Clinical &amp; IT Support
            </span>
          </div>

        </div>

        {/* Visual Hero Showcase */}
        <div className="mt-16 relative max-w-6xl mx-auto">
          
          <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
            
            {/* Left: Dual Doctor Portrait */}
            <div className="lg:col-span-5 relative group">
              <div className="relative rounded-3xl overflow-hidden border border-slate-200 bg-white shadow-xl">
                <img
                  src="/dual_doctors.jpg"
                  alt="Medical Practitioners using AuraEMR tablet"
                  className="w-full h-[400px] object-cover object-top hover:scale-105 transition-transform duration-700"
                />
                <div className="absolute inset-0 bg-gradient-to-t from-slate-900/80 via-transparent to-transparent"></div>

                {/* Floating Patient Vitals Badge */}
                <div className="absolute top-4 right-4 p-3 rounded-2xl bg-white/95 border border-slate-200 shadow-lg flex items-center space-x-3 backdrop-blur-md">
                  <div className="p-2 rounded-xl bg-rose-50 text-rose-600 border border-rose-200">
                    <Heart className="w-5 h-5 animate-pulse" />
                  </div>
                  <div>
                    <div className="text-[10px] text-slate-500 uppercase font-extrabold">Patient Vital Stream</div>
                    <div className="text-xs font-black text-slate-900">HR: 72 bpm • SpO2: 99%</div>
                  </div>
                </div>

                <div className="absolute bottom-4 left-4 right-4 p-4 rounded-2xl bg-white/95 backdrop-blur-md border border-slate-200 shadow-md">
                  <div className="flex items-center justify-between">
                    <div>
                      <div className="text-xs font-extrabold text-blue-600 uppercase">Multi-Provider EHR</div>
                      <div className="text-sm font-bold text-slate-900">Dual Doctor Charting &amp; Handover</div>
                    </div>
                    <span className="px-2.5 py-1 text-[10px] font-bold bg-blue-50 text-blue-700 rounded-full border border-blue-200">
                      Live Cloud
                    </span>
                  </div>
                </div>
              </div>
            </div>

            {/* Right: EMR Cloud Dashboard Visual Showcase */}
            <div className="lg:col-span-7 relative group">
              <div className="relative rounded-3xl overflow-hidden border border-slate-200 bg-white shadow-xl">
                
                {/* Desktop Window Header */}
                <div className="bg-slate-100 px-5 py-3 border-b border-slate-200 flex items-center justify-between">
                  <div className="flex items-center space-x-2">
                    <span className="w-3 h-3 rounded-full bg-rose-500 inline-block" />
                    <span className="w-3 h-3 rounded-full bg-amber-500 inline-block" />
                    <span className="w-3 h-3 rounded-full bg-emerald-500 inline-block" />
                    <span className="text-xs font-bold text-slate-700 ml-2">AuraEMR Workstation • Desktop Dashboard</span>
                  </div>
                  <span className="text-[11px] text-blue-700 font-bold bg-blue-50 px-2.5 py-0.5 rounded border border-blue-200">
                    256-bit SSL Encrypted
                  </span>
                </div>

                <img
                  src="/emr_dashboard.jpg"
                  alt="AuraEMR Cloud Medical Software Interface"
                  className="w-full h-[360px] sm:h-[400px] object-cover hover:scale-102 transition-transform duration-700"
                />

                <div className="p-4 bg-white border-t border-slate-200 flex flex-wrap items-center justify-between gap-2 text-xs">
                  <span className="text-slate-700 font-semibold flex items-center gap-1.5">
                    <Activity className="w-4 h-4 text-blue-600" /> Integrated Charting, e-Rx &amp; Medical Billing
                  </span>
                  <a
                    href="#demo"
                    className="text-blue-600 font-bold hover:text-blue-700 flex items-center gap-1"
                  >
                    <span>Try Interactive Demo</span> &rarr;
                  </a>
                </div>

              </div>
            </div>

          </div>

          {/* Quick Target Badges Row */}
          <div className="mt-8 grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div className="p-4 rounded-2xl bg-white border border-slate-200 shadow-xs flex items-center space-x-4">
              <div className="p-3 rounded-xl bg-blue-50 text-blue-600 border border-blue-200">
                <Stethoscope className="w-6 h-6" />
              </div>
              <div>
                <div className="text-sm font-bold text-slate-900">General Practice</div>
                <div className="text-xs text-slate-500">Solo &amp; Polyclinics</div>
              </div>
            </div>

            <div className="p-4 rounded-2xl bg-white border border-slate-200 shadow-xs flex items-center space-x-4">
              <div className="p-3 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-200">
                <Hospital className="w-6 h-6" />
              </div>
              <div>
                <div className="text-sm font-bold text-slate-900">Hospitals</div>
                <div className="text-xs text-slate-500">Inpatient &amp; ER Triage</div>
              </div>
            </div>

            <div className="p-4 rounded-2xl bg-white border border-slate-200 shadow-xs flex items-center space-x-4">
              <div className="p-3 rounded-xl bg-indigo-50 text-indigo-600 border border-indigo-200">
                <Building2 className="w-6 h-6" />
              </div>
              <div>
                <div className="text-sm font-bold text-slate-900">Specialty Clinics</div>
                <div className="text-xs text-slate-500">Outpatient Chains</div>
              </div>
            </div>
          </div>

        </div>

      </div>
    </section>
  );
}
