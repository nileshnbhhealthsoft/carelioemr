import React from 'react';
import { Activity, ArrowUp } from 'lucide-react';

export default function Footer({ onOpenCheckout }) {
  const scrollToTop = () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  return (
    <footer className="bg-white border-t border-slate-200 text-slate-600 text-xs relative">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-10">
          
          {/* Col 1: Brand */}
          <div className="lg:col-span-2 space-y-4">
            <div className="flex items-center space-x-3 cursor-pointer" onClick={scrollToTop}>
              <div className="flex items-center justify-center w-10 h-10 rounded-xl bg-blue-600 text-white font-bold shadow-md shadow-blue-600/20">
                <Activity className="w-6 h-6 stroke-[2.5]" />
              </div>
              <span className="text-2xl font-extrabold text-slate-900">Aura<span className="text-blue-600">EMR</span></span>
            </div>

            <p className="text-slate-500 text-xs leading-relaxed max-w-sm font-medium">
              Next-generation cloud-based Electronic Medical Records (EMR) and Electronic Health Records (EHR) subscription suite for General Practice, Hospitals, and Clinics.
            </p>

            <div className="p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 text-xs space-y-1">
              <div className="font-bold text-blue-700">$80 / Month Subscription</div>
              <div className="text-[11px] text-orange-700 font-semibold">* Setup cost will be an additional cost.</div>
            </div>
          </div>

          {/* Col 2: Target Practices */}
          <div className="space-y-3">
            <div className="text-xs uppercase font-extrabold tracking-wider text-slate-900">Target Practices</div>
            <ul className="space-y-2 font-medium">
              <li><a href="#services" className="hover:text-blue-600 transition-colors">General Practice</a></li>
              <li><a href="#services" className="hover:text-blue-600 transition-colors">Hospitals &amp; Inpatient Care</a></li>
              <li><a href="#services" className="hover:text-blue-600 transition-colors">Specialty Clinics</a></li>
              <li><a href="#services" className="hover:text-blue-600 transition-colors">Polyclinic Networks</a></li>
              <li><a href="#services" className="hover:text-blue-600 transition-colors">Telehealth Providers</a></li>
            </ul>
          </div>

          {/* Col 3: Coverage Regions */}
          <div className="space-y-3">
            <div className="text-xs uppercase font-extrabold tracking-wider text-slate-900">Coverage Regions</div>
            <ul className="space-y-2 font-medium">
              <li className="flex items-center gap-1.5"><span className="text-base">🇱🇨</span> <a href="#regions" className="hover:text-blue-600">Saint Lucia (OECS)</a></li>
              <li className="flex items-center gap-1.5"><span className="text-base">🇺🇸</span> <a href="#regions" className="hover:text-blue-600">USA (HIPAA)</a></li>
              <li className="flex items-center gap-1.5"><span className="text-base">🇮🇳</span> <a href="#regions" className="hover:text-blue-600">India (ABDM)</a></li>
              <li className="flex items-center gap-1.5"><span className="text-base">🇦🇪</span> <a href="#regions" className="hover:text-blue-600">UAE (DHA/NABIDH)</a></li>
              <li className="flex items-center gap-1.5"><span className="text-base">🇪🇺</span> <a href="#regions" className="hover:text-blue-600">Europe (GDPR)</a></li>
            </ul>
          </div>

          {/* Col 4: Platform & Legal */}
          <div className="space-y-3">
            <div className="text-xs uppercase font-extrabold tracking-wider text-slate-900">Platform &amp; Legal</div>
            <ul className="space-y-2 font-medium">
              <li><a href="#features" className="hover:text-blue-600">EMR Feature Modules</a></li>
              <li><a href="#pricing" className="hover:text-blue-600">Stripe Payment Integration</a></li>
              <li><span className="text-slate-400">HIPAA Compliance</span></li>
              <li><span className="text-slate-400">GDPR Privacy Policy</span></li>
              <li><span className="text-slate-400">System Uptime SLA (99.99%)</span></li>
            </ul>
          </div>

        </div>

        {/* Bottom Bar */}
        <div className="mt-12 pt-8 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs font-medium">
          <p className="text-slate-500 text-center sm:text-left">
            &copy; {new Date().getFullYear()} AuraEMR Platform Inc. All rights reserved. Cloud EMR &amp; EHR Subscription Services.
          </p>

          <div className="flex items-center space-x-4">
            <button
              onClick={onOpenCheckout}
              className="px-3.5 py-1.5 rounded-xl bg-blue-50 text-blue-700 border border-blue-200 hover:bg-blue-100 font-bold transition-all"
            >
              Subscribe ($80/mo)
            </button>
            <button
              onClick={scrollToTop}
              className="p-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 transition-colors"
              title="Scroll to top"
            >
              <ArrowUp className="w-4 h-4" />
            </button>
          </div>
        </div>

      </div>
    </footer>
  );
}
