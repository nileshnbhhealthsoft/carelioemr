import React from 'react';
import { Globe, ShieldCheck, Server, MapPin, CheckCircle2, Lock } from 'lucide-react';

const COVERAGE_REGIONS = [
  {
    code: 'LC',
    name: 'Saint Lucia',
    flag: '🇱🇨',
    regionTag: 'Caribbean & OECS Hub',
    compliance: 'OECS Medical Standards & Local Cloud Compliance',
    datacenter: 'AWS Caribbean Edge / Miami Direct Node',
    latency: '< 25ms',
    features: [
      'Tailored for Saint Lucia General Practices & Victoria Hospital networks',
      'Local EC Dollar ($XCD) & USD billing options',
      'Dedicated regional onboarding & Caribbean support team',
      'Offsite disaster recovery cloud storage'
    ]
  },
  {
    code: 'US',
    name: 'USA',
    flag: '🇺🇸',
    regionTag: 'HIPAA & HITECH Native',
    compliance: 'HIPAA Compliant, SOC 2 Type II & HITECH Act',
    datacenter: 'US-East (N. Virginia) & US-West (Oregon) Dual Zone',
    latency: '< 10ms',
    features: [
      'Full HIPAA Business Associate Agreement (BAA) included',
      'EPCS (Electronic Prescribing for Controlled Substances)',
      'Clearinghouse integrations (Change Healthcare, Availity)',
      'CMS Meaningful Use Stage 3 & MIPS Reporting'
    ]
  },
  {
    code: 'IN',
    name: 'India',
    flag: '🇮🇳',
    regionTag: 'ABDM M1, M2, M3 Integrated',
    compliance: 'Ayushman Bharat Digital Mission (ABDM) & DISHA',
    datacenter: 'AWS Mumbai & Hyderabad Data Centers',
    latency: '< 15ms',
    features: [
      'ABHA (Ayushman Bharat Health Account) linking & Creation',
      'NABH accredited hospital workflow templates',
      'GST invoice generation & local payment gateways',
      'Multi-lingual prescription generation (Hindi, English, Regional)'
    ]
  },
  {
    code: 'AE',
    name: 'UAE',
    flag: '🇦🇪',
    regionTag: 'DHA & NABIDH Ready',
    compliance: 'DHA Sheryan, NABIDH (Dubai) & Malaffi (Abu Dhabi)',
    datacenter: 'AWS Middle East (UAE / Bahrain Region)',
    latency: '< 12ms',
    features: [
      'Direct integration ready for DHA Sheryan e-Claims',
      'Malaffi & Nabidh Health Information Exchange sync',
      'Emirates ID card reader patient registration',
      'Bilingual Arabic / English patient charts & invoices'
    ]
  },
  {
    code: 'EU',
    name: 'Europe',
    flag: '🇪🇺',
    regionTag: 'GDPR & EU Data Sovereign',
    compliance: 'GDPR Article 28, EU Cloud Code of Conduct & CE Software',
    datacenter: 'AWS EU (Frankfurt & Ireland Data Hubs)',
    latency: '< 18ms',
    features: [
      'Strict EU In-Region Data Residency & Sovereignty Guarantee',
      'Right to be Forgotten & Data Export Automation',
      'Multi-currency support (€ EUR, £ GBP, CHF)',
      'Cross-border patient transfer protocol compliance'
    ]
  }
];

export default function GeographicCoverage({ selectedRegion, setSelectedRegion }) {
  const activeRegion = COVERAGE_REGIONS.find(r => r.code === selectedRegion) || COVERAGE_REGIONS[0];

  return (
    <section id="regions" className="py-20 bg-white border-t border-slate-200 relative">
      
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        
        {/* Header */}
        <div className="text-center max-w-3xl mx-auto">
          <div className="inline-flex items-center space-x-2 px-3.5 py-1 rounded-full bg-blue-50 border border-blue-200 text-blue-700 text-xs font-bold uppercase tracking-wider mb-4 shadow-xs">
            <Globe className="w-3.5 h-3.5 text-blue-600" />
            <span>Global Reach &amp; Data Sovereignty</span>
          </div>
          <h2 className="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
            Active Service Across 5 Key Regions
          </h2>
          <p className="mt-4 text-slate-600 text-base sm:text-lg">
            AuraEMR ensures local data sovereignty, ultra-low latency cloud servers, and strict regulatory compliance in Saint Lucia, the USA, India, UAE, and Europe.
          </p>
        </div>

        {/* Region Selector Pills */}
        <div className="mt-10 flex flex-wrap justify-center gap-3">
          {COVERAGE_REGIONS.map((r) => {
            const isSelected = selectedRegion === r.code;
            return (
              <button
                key={r.code}
                onClick={() => setSelectedRegion(r.code)}
                className={`flex items-center space-x-2.5 px-6 py-3.5 rounded-2xl font-bold text-sm transition-all duration-300 cursor-pointer ${
                  isSelected
                    ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/25 scale-[1.03]'
                    : 'bg-slate-50 border border-slate-200 text-slate-700 hover:text-blue-600 hover:border-blue-200 shadow-xs'
                }`}
              >
                <span className="text-xl">{r.flag}</span>
                <span>{r.name}</span>
                {isSelected && <CheckCircle2 className="w-4 h-4 text-white" />}
              </button>
            );
          })}
        </div>

        {/* Selected Region Card */}
        <div className="mt-8 bg-slate-50 border border-slate-200 rounded-3xl p-6 sm:p-10 shadow-lg relative overflow-hidden">
          
          <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
            
            {/* Left: Info */}
            <div className="lg:col-span-7 space-y-6">
              
              <div className="flex items-center space-x-4">
                <span className="text-5xl">{activeRegion.flag}</span>
                <div>
                  <div className="flex items-center space-x-2">
                    <h3 className="text-2xl sm:text-3xl font-extrabold text-slate-900">{activeRegion.name}</h3>
                    <span className="px-3 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-700 border border-blue-200">
                      {activeRegion.regionTag}
                    </span>
                  </div>
                  <p className="text-xs text-slate-600 mt-1 flex items-center gap-1.5 font-medium">
                    <Server className="w-4 h-4 text-blue-600" />
                    <span>Data Center: <strong className="text-slate-900">{activeRegion.datacenter}</strong></span>
                  </p>
                </div>
              </div>

              {/* Compliance Highlight */}
              <div className="p-4 rounded-2xl bg-white border border-slate-200 flex items-start space-x-3.5 shadow-xs">
                <ShieldCheck className="w-6 h-6 text-emerald-600 shrink-0 mt-0.5" />
                <div>
                  <div className="text-xs font-bold uppercase tracking-wider text-blue-700">Regional Regulatory Compliance</div>
                  <div className="text-sm font-extrabold text-slate-900 mt-0.5">{activeRegion.compliance}</div>
                  <div className="text-xs text-slate-600 mt-1">
                    Complies with local healthcare privacy and data protection frameworks.
                  </div>
                </div>
              </div>

              {/* Features */}
              <div>
                <div className="text-xs font-extrabold uppercase text-slate-400 tracking-wider mb-3">Regional Feature Highlights</div>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  {activeRegion.features.map((feat, idx) => (
                    <div key={idx} className="p-3 rounded-xl bg-white border border-slate-200 flex items-start space-x-2.5">
                      <CheckCircle2 className="w-4 h-4 text-blue-600 mt-0.5 shrink-0" />
                      <span className="text-xs text-slate-800 font-semibold">{feat}</span>
                    </div>
                  ))}
                </div>
              </div>

            </div>

            {/* Right: Infrastructure Card */}
            <div className="lg:col-span-5">
              <div className="p-6 rounded-2xl bg-white border border-slate-200 space-y-5 shadow-sm">
                
                <div className="text-sm font-bold text-slate-900 flex items-center justify-between border-b border-slate-100 pb-3">
                  <span className="flex items-center gap-2">
                    <MapPin className="w-4 h-4 text-blue-600" /> Infrastructure Node
                  </span>
                  <span className="text-xs text-emerald-600 font-extrabold flex items-center gap-1 bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200">
                    <span className="w-2 h-2 rounded-full bg-emerald-600 animate-ping" /> Operational
                  </span>
                </div>

                <div className="space-y-3">
                  <div className="flex justify-between items-center text-xs p-3 rounded-xl bg-slate-50 font-semibold">
                    <span className="text-slate-600">Network Latency</span>
                    <span className="text-blue-700 font-extrabold">{activeRegion.latency}</span>
                  </div>

                  <div className="flex justify-between items-center text-xs p-3 rounded-xl bg-slate-50 font-semibold">
                    <span className="text-slate-600">Subscription Rate</span>
                    <span className="text-slate-900 font-extrabold">$80 / month</span>
                  </div>

                  <div className="flex justify-between items-center text-xs p-3 rounded-xl bg-slate-50 font-semibold">
                    <span className="text-slate-600">Setup Support</span>
                    <span className="text-orange-600 font-bold">Custom Onboarding</span>
                  </div>
                </div>

                <div className="pt-2 text-[11px] text-slate-500 text-center flex items-center justify-center gap-1.5 font-medium">
                  <Lock className="w-3.5 h-3.5 text-blue-600" />
                  <span>Local data residency enforced for {activeRegion.name}</span>
                </div>

              </div>
            </div>

          </div>

        </div>

        {/* Quick Country Grid */}
        <div className="mt-8 grid grid-cols-2 sm:grid-cols-5 gap-3">
          {COVERAGE_REGIONS.map((r) => (
            <div
              key={r.code}
              onClick={() => setSelectedRegion(r.code)}
              className="p-3.5 rounded-2xl bg-white border border-slate-200 text-center hover:border-blue-400 cursor-pointer transition-all shadow-xs"
            >
              <div className="text-3xl mb-1">{r.flag}</div>
              <div className="text-xs font-bold text-slate-900">{r.name}</div>
              <div className="text-[10px] text-slate-500 font-medium mt-0.5">Active in {r.code}</div>
            </div>
          ))}
        </div>

      </div>
    </section>
  );
}
