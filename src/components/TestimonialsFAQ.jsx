import React, { useState } from 'react';
import { Star, ChevronDown, HelpCircle, ShieldCheck, Quote } from 'lucide-react';

const TESTIMONIALS = [
  {
    name: 'Dr. Marcus Etienne',
    role: 'Medical Director',
    clinic: 'Victoria General Polyclinic, Saint Lucia 🇱🇨',
    text: 'AuraEMR completely modernized our consultation charting. Deployment in Saint Lucia was fast, and having local OECS compliance out of the box gave our clinical board total confidence.',
    rating: 5
  },
  {
    name: 'Dr. Elena Rostova',
    role: 'Chief of Health Informatics',
    clinic: 'St. Jude Community Hospital, USA 🇺🇸',
    text: 'Transitioning to an $80/month subscription model saved our hospital system thousands in legacy licensing fees. The HIPAA compliance and e-Prescribing integration are flawless.',
    rating: 5
  },
  {
    name: 'Dr. Rajesh Nair',
    role: 'Senior Physician & Founder',
    clinic: 'Nair Specialty Clinics, India 🇮🇳',
    text: 'The ABDM health account integration and instant digital Rx generation reduced our patient waiting time by over 40%. Fantastic cloud performance.',
    rating: 5
  },
  {
    name: 'Dr. Fatima Al-Mansoori',
    role: 'Outpatient Services Lead',
    clinic: 'Emirates Healthcare Network, UAE 🇦🇪',
    text: 'NABIDH and DHA e-claims compatibility made our billing smooth. The setup team configured our custom clinic templates during initial onboarding.',
    rating: 5
  }
];

const FAQS = [
  {
    q: 'What is included in the $80 / month subscription fee?',
    a: 'The $80 / month subscription provides full access to all cloud EMR and EHR modules per practice node. This includes patient charting (SOAP notes), e-Prescribing, HD Telehealth consultations, billing and clearinghouse claims generation, lab result sync, unlimited patient record storage, automatic updates, and 24/7 priority support.'
  },
  {
    q: 'Why is setup cost listed as an additional cost?',
    a: 'Setup cost will be an additional cost because each healthcare practice has unique requirements. Setup services include legacy data extraction & migration from existing EMR systems, custom HL7/FHIR hospital interface configuration, specialized clinical template building, and hands-on staff training.'
  },
  {
    q: 'Which countries and regions are supported for cloud hosting?',
    a: 'AuraEMR is deployed in 5 key geographic regions: Saint Lucia (Caribbean & OECS hub), the USA (HIPAA native), India (ABDM & DISHA), UAE (DHA & NABIDH), and Europe (GDPR & EU Data Sovereignty). Each region operates on local ultra-low latency data centers.'
  },
  {
    q: 'Is AuraEMR suitable for General Practice, Hospitals, and Clinics alike?',
    a: 'Yes! AuraEMR features role-based configuration modules for solo General Practice clinics, multi-specialty outpatient Clinics, and large inpatient Hospital networks with triage and ADT bed management.'
  },
  {
    q: 'How does the Stripe Payment Checkout work on this page?',
    a: 'The landing page includes an integrated Stripe Payment Gateway connected to your API keys. You can test the subscription process using standard test cards (e.g. 4242 4242 4242 4242) to experience the checkout flow and receive instant subscription confirmations.'
  }
];

export default function TestimonialsFAQ() {
  const [openFaq, setOpenFaq] = useState(null);

  return (
    <section id="faq" className="py-20 bg-slate-50 border-t border-slate-200 relative">
      
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {/* Header */}
        <div className="text-center max-w-3xl mx-auto mb-14">
          <div className="inline-flex items-center space-x-2 px-3.5 py-1 rounded-full bg-white border border-slate-200 text-blue-700 text-xs font-bold uppercase tracking-wider mb-4 shadow-xs">
            <Quote className="w-3.5 h-3.5 text-blue-600" />
            <span>Clinical Reviews</span>
          </div>
          <h2 className="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
            Trusted by Doctors &amp; Hospital Leaders Worldwide
          </h2>
          <p className="mt-4 text-slate-600 text-base">
            See how healthcare providers in Saint Lucia, USA, India, UAE, and Europe transform care delivery with AuraEMR.
          </p>
        </div>

        {/* Testimonials Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-20">
          {TESTIMONIALS.map((t, idx) => (
            <div
              key={idx}
              className="p-6 sm:p-8 rounded-3xl bg-white border border-slate-200 space-y-4 shadow-sm flex flex-col justify-between"
            >
              <div className="space-y-3">
                <div className="flex space-x-1">
                  {[...Array(t.rating)].map((_, i) => (
                    <Star key={i} className="w-4 h-4 fill-amber-400 text-amber-400" />
                  ))}
                </div>
                <p className="text-slate-700 text-xs sm:text-sm leading-relaxed italic font-medium">
                  "{t.text}"
                </p>
              </div>

              <div className="pt-4 border-t border-slate-100 flex items-center justify-between">
                <div>
                  <div className="text-sm font-bold text-slate-900">{t.name}</div>
                  <div className="text-xs text-blue-600 font-semibold">{t.role}</div>
                  <div className="text-[11px] text-slate-500 mt-0.5 font-medium">{t.clinic}</div>
                </div>
                <ShieldCheck className="w-6 h-6 text-emerald-600" />
              </div>
            </div>
          ))}
        </div>

        {/* FAQ Header */}
        <div className="text-center max-w-3xl mx-auto mb-10">
          <div className="inline-flex items-center space-x-2 px-3.5 py-1 rounded-full bg-white border border-slate-200 text-blue-700 text-xs font-bold uppercase tracking-wider mb-4 shadow-xs">
            <HelpCircle className="w-3.5 h-3.5 text-blue-600" />
            <span>Got Questions?</span>
          </div>
          <h3 className="text-2xl sm:text-3xl font-extrabold text-slate-900">
            Frequently Asked Questions
          </h3>
        </div>

        {/* FAQ Accordion */}
        <div className="max-w-3xl mx-auto space-y-4">
          {FAQS.map((faq, idx) => {
            const isOpen = openFaq === idx;
            return (
              <div
                key={idx}
                className="rounded-2xl bg-white border border-slate-200 overflow-hidden shadow-xs transition-colors"
              >
                <button
                  onClick={() => setOpenFaq(isOpen ? null : idx)}
                  className="w-full p-5 text-left flex items-center justify-between space-x-4 cursor-pointer hover:bg-slate-50"
                >
                  <span className="text-sm sm:text-base font-bold text-slate-900">{faq.q}</span>
                  <ChevronDown
                    className={`w-5 h-5 text-blue-600 transition-transform duration-300 ${
                      isOpen ? 'rotate-180' : ''
                    }`}
                  />
                </button>

                {isOpen && (
                  <div className="px-5 pb-5 pt-1 border-t border-slate-100 text-xs sm:text-sm text-slate-600 leading-relaxed font-medium animate-in fade-in">
                    {faq.a}
                  </div>
                )}
              </div>
            );
          })}
        </div>

      </div>
    </section>
  );
}
