import React from 'react';
import { FileText, Pill, Video, CreditCard, TestTube, Lock, Zap, CheckCircle } from 'lucide-react';

const FEATURES = [
  {
    icon: FileText,
    title: 'Intelligent Patient Charting',
    color: 'text-blue-600',
    bg: 'bg-blue-50',
    border: 'border-blue-200',
    description: 'Complete electronic medical record management with custom SOAP templates, voice-to-text charting, and medical history timelines.'
  },
  {
    icon: Pill,
    title: 'e-Prescribing & Allergy Alerts',
    color: 'text-cyan-600',
    bg: 'bg-cyan-50',
    border: 'border-cyan-200',
    description: 'Instant e-Prescription dispatch to partner pharmacies with automated drug interaction checks, dosage calculators, and refills.'
  },
  {
    icon: Video,
    title: 'HD Telehealth Consultations',
    color: 'text-emerald-600',
    bg: 'bg-emerald-50',
    border: 'border-emerald-200',
    description: 'Encrypted 1-click video calls directly inside patient charts. Screen share diagnostic reports and issue digital prescriptions.'
  },
  {
    icon: CreditCard,
    title: 'Automated Billing & Claims',
    color: 'text-orange-600',
    bg: 'bg-orange-50',
    border: 'border-orange-200',
    description: 'Built-in medical billing engine with automated ICD-10/11 coding, insurance claim filing, copay receipting, and Stripe payments.'
  },
  {
    icon: TestTube,
    title: 'Lab & Diagnostic Result Sync',
    color: 'text-indigo-600',
    bg: 'bg-indigo-50',
    border: 'border-indigo-200',
    description: 'Bi-directional integration with clinical laboratories and imaging centers. Real-time notifications when pathology or X-Ray results land.'
  },
  {
    icon: Lock,
    title: 'HIPAA & GDPR Encryption',
    color: 'text-rose-600',
    bg: 'bg-rose-50',
    border: 'border-rose-200',
    description: 'AES-256 bit zero-trust encryption at rest and in transit, comprehensive audit logging, and granular role-based permissions.'
  }
];

export default function FeaturesSection() {
  return (
    <section id="features" className="py-20 bg-slate-50 border-t border-slate-200 relative">
      
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {/* Header */}
        <div className="text-center max-w-3xl mx-auto">
          <div className="inline-flex items-center space-x-2 px-3.5 py-1 rounded-full bg-white border border-slate-200 text-blue-700 text-xs font-bold uppercase tracking-wider mb-4 shadow-xs">
            <Zap className="w-3.5 h-3.5 text-blue-600" />
            <span>Platform Modules</span>
          </div>
          <h2 className="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
            Everything Your Medical Practice Needs in One Workspace
          </h2>
          <p className="mt-4 text-slate-600 text-base sm:text-lg">
            Engineered to eliminate administrative burdens so doctors and clinical staff can focus on delivering high-quality patient care.
          </p>
        </div>

        {/* Feature Grid */}
        <div className="mt-14 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
          {FEATURES.map((f, idx) => {
            const Icon = f.icon;
            return (
              <div
                key={idx}
                className="white-card white-card-hover p-6 sm:p-8 rounded-3xl flex flex-col justify-between relative group"
              >
                <div>
                  <div className={`w-12 h-12 rounded-2xl ${f.bg} border ${f.border} flex items-center justify-center mb-6`}>
                    <Icon className={`w-6 h-6 ${f.color}`} />
                  </div>
                  <h3 className="text-xl font-bold text-slate-900 group-hover:text-blue-600 transition-colors">
                    {f.title}
                  </h3>
                  <p className="mt-3 text-slate-600 text-xs sm:text-sm leading-relaxed">
                    {f.description}
                  </p>
                </div>

                <div className="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500 font-semibold">
                  <span className="flex items-center gap-1.5 text-slate-700">
                    <CheckCircle className="w-3.5 h-3.5 text-emerald-600" /> Included in $80/mo
                  </span>
                  <span className="text-blue-600 font-bold group-hover:translate-x-1 transition-transform">
                    Learn &rarr;
                  </span>
                </div>
              </div>
            );
          })}
        </div>

      </div>
    </section>
  );
}
