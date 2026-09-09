import React, { useState } from 'react';
import { Stethoscope, Hospital, Building2, Check, ArrowRight, Activity, Users, HeartPulse } from 'lucide-react';

const AUDIENCE_DATA = [
  {
    id: 'general-practice',
    title: 'General Practice',
    subtitle: 'Solo Physicians & Polyclinics',
    icon: Stethoscope,
    badgeColor: 'bg-blue-50 text-blue-700 border-blue-200',
    textColor: 'text-blue-600',
    description: 'Rapid intake forms, streamlined consultation SOAP notes, e-Prescribing with allergy alerts, and integrated patient portal billing.',
    keyFeatures: [
      'Rapid SOAP Charting with custom templates',
      'One-click e-Prescriptions with drug interaction checks',
      'Integrated appointment scheduling & SMS reminders',
      'Direct insurance copay & patient billing',
      'Patient portal with lab result distribution',
      'Offline-capable chart caching for uninterrupted visits'
    ],
    stats: [
      { label: 'Avg. Consultation Charting', value: '< 2 mins' },
      { label: 'Prescription Accuracy', value: '99.9%' },
      { label: 'Patient Retention', value: '+40%' }
    ]
  },
  {
    id: 'hospitals',
    title: 'Hospitals',
    subtitle: 'Inpatient & ER Department Networks',
    icon: Hospital,
    badgeColor: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    textColor: 'text-emerald-600',
    description: 'Enterprise inpatient EHR with real-time bed management, emergency triage queues, PACS radiology sync, and HL7/FHIR multi-system interoperability.',
    keyFeatures: [
      'Inpatient ADT (Admission, Discharge, Transfer) workflow',
      'Emergency Department triage & bed tracking',
      'ICU & Ward nursing order entry & MAR logs',
      'PACS / Radiology DICOM viewer integration',
      'Multi-department clinical handover logs',
      'Role-based security for doctors, nurses, and admins'
    ],
    stats: [
      { label: 'Bed Occupancy Optimization', value: '35% Faster' },
      { label: 'HL7 Interoperability', value: '100% Compliant' },
      { label: 'EHR Uptime SLA', value: '99.99%' }
    ]
  },
  {
    id: 'clinics',
    title: 'Specialty Clinics',
    subtitle: 'Outpatient & Surgical Care Centers',
    icon: Building2,
    badgeColor: 'bg-indigo-50 text-indigo-700 border-indigo-200',
    textColor: 'text-indigo-600',
    description: 'Tailored clinical forms for Cardiology, Dental, Dermatology, Orthopedics, Pediatrics, and multi-location outpatient clinic chains.',
    keyFeatures: [
      'Specialty charting schemas & body maps',
      'Surgical procedure logs & consent sign-offs',
      'Multi-location surgical inventory tracking',
      'Telehealth HD video consultations',
      'Automated claim submission clearinghouse',
      'Centralized dashboard for multi-branch chains'
    ],
    stats: [
      { label: 'Claim First-Pass Rate', value: '98.2%' },
      { label: 'Patient No-Show Rate', value: '-65%' },
      { label: 'Multi-Branch Sync', value: 'Real-time' }
    ]
  }
];

export default function TargetAudience({ onOpenCheckout }) {
  const [activeTab, setActiveTab] = useState('general-practice');

  const currentAudience = AUDIENCE_DATA.find(a => a.id === activeTab) || AUDIENCE_DATA[0];

  return (
    <section id="services" className="py-20 bg-slate-100/60 border-t border-slate-200 relative">
      
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {/* Section Header */}
        <div className="text-center max-w-3xl mx-auto">
          <div className="inline-flex items-center space-x-2 px-3.5 py-1 rounded-full bg-white border border-slate-200 text-blue-700 text-xs font-bold uppercase tracking-wider mb-4 shadow-xs">
            <Users className="w-3.5 h-3.5 text-blue-600" />
            <span>Target Audience &amp; Healthcare Settings</span>
          </div>
          <h2 className="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
            Tailored Cloud EMR/EHR Solutions for Every Healthcare Sector
          </h2>
          <p className="mt-4 text-slate-600 text-base sm:text-lg">
            Whether running a solo general practice, a multi-facility hospital, or a specialized clinic, AuraEMR scales seamlessly to your clinical workflow.
          </p>
        </div>

        {/* Tab Switcher */}
        <div className="mt-10 flex items-center justify-start sm:justify-center gap-2.5 overflow-x-auto pb-2 sm:pb-0 scrollbar-none px-1">
          {AUDIENCE_DATA.map((item) => {
            const Icon = item.icon;
            const isActive = activeTab === item.id;
            return (
              <button
                key={item.id}
                onClick={() => setActiveTab(item.id)}
                className={`flex items-center space-x-2.5 px-5 py-3 rounded-2xl font-bold text-xs sm:text-sm whitespace-nowrap transition-all duration-300 cursor-pointer shrink-0 ${
                  isActive
                    ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/25 scale-[1.02]'
                    : 'bg-white border border-slate-200 text-slate-700 hover:text-blue-600 hover:border-blue-200 shadow-xs'
                }`}
              >
                <Icon className={`w-4 h-4 sm:w-5 sm:h-5 ${isActive ? 'text-white' : 'text-slate-500'}`} />
                <span>{item.title}</span>
              </button>
            );
          })}
        </div>

        {/* Audience Detail Card */}
        <div className="mt-8 bg-white border border-slate-200 rounded-3xl p-6 sm:p-10 shadow-xl relative overflow-hidden">
          
          <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
            
            {/* Left: Description & Features */}
            <div className="lg:col-span-7 space-y-6">
              
              <div>
                <div className="flex items-center space-x-3 mb-2">
                  <span className={`px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider border ${currentAudience.badgeColor}`}>
                    {currentAudience.subtitle}
                  </span>
                </div>
                <h3 className="text-2xl sm:text-3xl font-extrabold text-slate-900">
                  Built for {currentAudience.title}
                </h3>
                <p className="mt-3 text-slate-600 text-sm sm:text-base leading-relaxed">
                  {currentAudience.description}
                </p>
              </div>

              {/* Feature Checklist */}
              <div>
                <div className="text-xs uppercase font-extrabold text-slate-400 tracking-wider mb-3">Key Specialized Capabilities</div>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  {currentAudience.keyFeatures.map((feat, idx) => (
                    <div key={idx} className="flex items-start space-x-2.5 p-3 rounded-xl bg-slate-50 border border-slate-200">
                      <Check className={`w-4 h-4 mt-0.5 shrink-0 ${currentAudience.textColor}`} />
                      <span className="text-xs text-slate-800 font-semibold">{feat}</span>
                    </div>
                  ))}
                </div>
              </div>

              {/* Action Button */}
              <div className="pt-2 flex items-center space-x-4">
                <button
                  onClick={onOpenCheckout}
                  className="px-6 py-3 rounded-xl bg-blue-600 text-white font-bold text-xs hover:bg-blue-700 shadow-md shadow-blue-600/20 transition-all flex items-center space-x-2 cursor-pointer"
                >
                  <span>Subscribe for {currentAudience.title} ($80/mo)</span>
                  <ArrowRight className="w-4 h-4" />
                </button>
                <span className="text-xs text-slate-500 font-medium">* Additional setup cost applies</span>
              </div>

            </div>

            {/* Right: Metrics */}
            <div className="lg:col-span-5">
              <div className="p-6 rounded-2xl bg-slate-50 border border-slate-200 space-y-6">
                
                <div className="flex items-center justify-between border-b border-slate-200 pb-4">
                  <div className="flex items-center space-x-3">
                    <div className="p-2.5 rounded-xl bg-blue-100 text-blue-700">
                      <Activity className="w-6 h-6" />
                    </div>
                    <div>
                      <div className="text-sm font-bold text-slate-900">Efficiency Impact</div>
                      <div className="text-xs text-slate-500">{currentAudience.title} Metrics</div>
                    </div>
                  </div>
                  <span className="text-xs font-bold text-blue-700 bg-blue-100 px-3 py-1 rounded-full border border-blue-200">
                    Active Module
                  </span>
                </div>

                <div className="space-y-3">
                  {currentAudience.stats.map((stat, idx) => (
                    <div key={idx} className="p-4 rounded-xl bg-white border border-slate-200 flex items-center justify-between shadow-xs">
                      <span className="text-xs text-slate-600 font-medium">{stat.label}</span>
                      <span className={`text-lg font-black ${currentAudience.textColor}`}>{stat.value}</span>
                    </div>
                  ))}
                </div>

                <div className="p-3.5 rounded-xl bg-white border border-slate-200 text-[11px] text-slate-600 flex items-center space-x-2">
                  <HeartPulse className="w-4 h-4 text-rose-500 shrink-0" />
                  <span>Supports HL7 v2, FHIR Release 4, and ICD-10 clinical coding standards.</span>
                </div>

              </div>
            </div>

          </div>

        </div>

      </div>
    </section>
  );
}
