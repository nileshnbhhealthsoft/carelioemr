import React, { useState } from 'react';
import { Activity, User, Calendar, Pill, FileText, CheckCircle2, Clock, Plus, Search, ShieldCheck, Heart, Thermometer, Droplet, Send, BedDouble, AlertTriangle, ChevronRight, Stethoscope } from 'lucide-react';

export default function InteractiveDashboardPreview() {
  const [activeScreen, setActiveScreen] = useState('chart');

  return (
    <section id="demo" className="py-24 bg-white border-t border-slate-200 relative overflow-hidden">
      
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        
        {/* Header */}
        <div className="text-center max-w-3xl mx-auto mb-14">
          <div className="inline-flex items-center space-x-2 px-4 py-1.5 rounded-full bg-blue-50 border border-blue-200 text-blue-700 text-xs font-bold uppercase tracking-wider mb-4 shadow-xs">
            <Activity className="w-4 h-4 text-blue-600" />
            <span>Interactive Software Workstation</span>
          </div>
          <h2 className="text-3xl sm:text-5xl font-extrabold text-slate-900 tracking-tight leading-tight">
            Experience AuraEMR in Action
          </h2>
          <p className="mt-4 text-slate-600 text-base sm:text-lg">
            Test live clinical workflows below. See how General Practice, Hospitals, and Clinics manage patient charting, e-Rx, and bed tracking in real time.
          </p>
        </div>

        {/* Dashboard Frame Container */}
        <div className="white-card rounded-3xl overflow-hidden shadow-xl border border-slate-200">
          
          {/* Top Navigation Header */}
          <div className="bg-slate-100 px-6 py-4 border-b border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div className="flex items-center space-x-3">
              <div className="flex space-x-1.5">
                <span className="w-3 h-3 rounded-full bg-rose-500 inline-block" />
                <span className="w-3 h-3 rounded-full bg-amber-500 inline-block" />
                <span className="w-3 h-3 rounded-full bg-emerald-500 inline-block" />
              </div>
              <span className="text-xs text-slate-800 font-bold border-l border-slate-300 pl-3 flex items-center gap-1.5">
                <ShieldCheck className="w-4 h-4 text-blue-600" />
                <span>AuraEMR Workstation • Dr. Sarah Johnson (General Practice)</span>
              </span>
            </div>

            {/* Workstation Tab Switcher */}
            <div className="flex items-center gap-1.5 bg-white p-1.5 rounded-2xl border border-slate-200 shadow-xs overflow-x-auto max-w-full">
              <button
                onClick={() => setActiveScreen('chart')}
                className={`px-3 py-1.5 text-[11px] sm:text-xs font-extrabold rounded-xl transition-all cursor-pointer whitespace-nowrap shrink-0 ${
                  activeScreen === 'chart' ? 'bg-blue-600 text-white shadow-md' : 'text-slate-600 hover:text-blue-600'
                }`}
              >
                🩺 Patient Chart
              </button>
              <button
                onClick={() => setActiveScreen('rx')}
                className={`px-3 py-1.5 text-[11px] sm:text-xs font-extrabold rounded-xl transition-all cursor-pointer whitespace-nowrap shrink-0 ${
                  activeScreen === 'rx' ? 'bg-blue-600 text-white shadow-md' : 'text-slate-600 hover:text-blue-600'
                }`}
              >
                💊 e-Prescription
              </button>
              <button
                onClick={() => setActiveScreen('vitals')}
                className={`px-3 py-1.5 text-[11px] sm:text-xs font-extrabold rounded-xl transition-all cursor-pointer whitespace-nowrap shrink-0 ${
                  activeScreen === 'vitals' ? 'bg-blue-600 text-white shadow-md' : 'text-slate-600 hover:text-blue-600'
                }`}
              >
                📊 Vitals Stream
              </button>
              <button
                onClick={() => setActiveScreen('hospital')}
                className={`px-3 py-1.5 text-[11px] sm:text-xs font-extrabold rounded-xl transition-all cursor-pointer whitespace-nowrap shrink-0 ${
                  activeScreen === 'hospital' ? 'bg-blue-600 text-white shadow-md' : 'text-slate-600 hover:text-blue-600'
                }`}
              >
                🏥 Hospital Beds
              </button>
            </div>
          </div>

          {/* Screen Content */}
          <div className="p-6 sm:p-10 min-h-[460px] bg-slate-50">
            
            {activeScreen === 'chart' && (
              <div className="space-y-6 animate-in fade-in duration-300">
                
                {/* Patient Profile Card */}
                <div className="p-5 rounded-2xl bg-white border border-slate-200 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 shadow-sm">
                  <div className="flex items-center space-x-4">
                    <div className="w-14 h-14 rounded-2xl bg-blue-600 text-white font-black flex items-center justify-center text-xl shadow-md shadow-blue-600/20">
                      MP
                    </div>
                    <div>
                      <div className="flex items-center space-x-2">
                        <h4 className="text-xl font-black text-slate-900">Maria Perez</h4>
                        <span className="text-xs text-slate-500 font-mono">#EMR-89240</span>
                        <span className="px-2.5 py-0.5 text-[10px] bg-emerald-50 text-emerald-700 font-extrabold rounded-md border border-emerald-200">
                          Active Consultation
                        </span>
                      </div>
                      <p className="text-xs text-slate-500 mt-1">Female • 34 yrs • DOB: 14-May-1992 • Blood: O+ positive</p>
                    </div>
                  </div>

                  <div className="flex space-x-2 text-xs font-semibold">
                    <span className="px-3 py-1.5 rounded-xl bg-slate-100 text-slate-800 border border-slate-200">
                      Allergies: Penicillin
                    </span>
                    <span className="px-3 py-1.5 rounded-xl bg-blue-50 text-blue-700 border border-blue-200">
                      Ins: BlueCross #9012
                    </span>
                  </div>
                </div>

                {/* SOAP Note Grid */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  
                  <div className="space-y-4">
                    <div className="p-5 rounded-2xl bg-white border border-slate-200 shadow-xs">
                      <div className="text-xs font-black text-blue-600 uppercase tracking-wider mb-2">Subjective (S)</div>
                      <p className="text-xs text-slate-700 leading-relaxed font-medium">
                        "Patient presents with mild respiratory congestion and throat irritation for 3 days. Denies fever, chest pain, or shortness of breath."
                      </p>
                    </div>

                    <div className="p-5 rounded-2xl bg-white border border-slate-200 shadow-xs">
                      <div className="text-xs font-black text-cyan-600 uppercase tracking-wider mb-2">Objective (O)</div>
                      <p className="text-xs text-slate-800 leading-relaxed font-mono font-semibold">
                        BP: 120/80 mmHg | Heart Rate: 72 bpm | Temp: 98.6 °F | SpO2: 99%
                      </p>
                    </div>
                  </div>

                  <div className="space-y-4">
                    <div className="p-5 rounded-2xl bg-white border border-slate-200 shadow-xs">
                      <div className="text-xs font-black text-emerald-600 uppercase tracking-wider mb-2">Assessment (A)</div>
                      <div className="text-xs font-bold text-slate-900">ICD-10 Code: J00 - Acute Nasopharyngitis (Common Cold)</div>
                    </div>

                    <div className="p-5 rounded-2xl bg-white border border-slate-200 shadow-xs">
                      <div className="text-xs font-black text-amber-600 uppercase tracking-wider mb-2">Plan (P)</div>
                      <p className="text-xs text-slate-700 leading-relaxed font-medium">
                        1. Rest and fluid hydration<br />
                        2. Cetirizine 10mg once daily x 5 days<br />
                        3. Follow-up in 7 days if symptoms persist.
                      </p>
                    </div>
                  </div>

                </div>

              </div>
            )}

            {activeScreen === 'rx' && (
              <div className="space-y-6 animate-in fade-in duration-300">
                <div className="flex items-center justify-between">
                  <h4 className="text-lg font-black text-slate-900 flex items-center gap-2">
                    <Pill className="w-5 h-5 text-blue-600" /> Digital e-Prescription Generator
                  </h4>
                  <span className="text-xs text-emerald-700 font-extrabold bg-emerald-50 px-3 py-1 rounded-full border border-emerald-200">
                    Pharmacy Network Active
                  </span>
                </div>

                <div className="p-6 rounded-2xl bg-white border border-slate-200 shadow-sm space-y-4">
                  <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                    <div>
                      <label className="text-slate-500 font-semibold block mb-1.5">Selected Medication</label>
                      <input type="text" readOnly value="Amoxicillin 500mg Oral Capsule" className="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-slate-900 font-bold" />
                    </div>
                    <div>
                      <label className="text-slate-500 font-semibold block mb-1.5">Dosage &amp; Instructions</label>
                      <input type="text" readOnly value="1 Capsule every 8 hours x 7 days" className="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-slate-900 font-bold" />
                    </div>
                    <div>
                      <label className="text-slate-500 font-semibold block mb-1.5">Dispensing Pharmacy</label>
                      <input type="text" readOnly value="CityCare Pharmacy #104" className="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-slate-900 font-bold" />
                    </div>
                  </div>

                  <div className="p-4 rounded-xl bg-blue-50 border border-blue-200 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
                    <span className="text-blue-800 font-semibold flex items-center gap-2">
                      <CheckCircle2 className="w-4 h-4 text-blue-600 shrink-0" />
                      <span>Drug Interaction Check passed: No contraindications with Penicillin allergy log.</span>
                    </span>
                    <button className="px-5 py-2 bg-blue-600 text-white font-black rounded-xl hover:bg-blue-700 transition-colors flex items-center gap-1.5 cursor-pointer whitespace-nowrap shadow-xs">
                      <Send className="w-4 h-4" /> Dispatch e-Rx
                    </button>
                  </div>
                </div>
              </div>
            )}

            {activeScreen === 'vitals' && (
              <div className="space-y-6 animate-in fade-in duration-300">
                <div className="flex items-center justify-between">
                  <h4 className="text-lg font-black text-slate-900 flex items-center gap-2">
                    <Activity className="w-5 h-5 text-blue-600" /> Real-time Patient Vital Signs Ingestion
                  </h4>
                  <span className="text-xs text-blue-700 font-bold bg-blue-50 px-3 py-1 rounded-full border border-blue-200 flex items-center gap-1.5">
                    <span className="w-2 h-2 rounded-full bg-blue-600 animate-ping" /> Live Device Sync
                  </span>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-3 gap-6">
                  
                  <div className="p-6 rounded-2xl bg-white border border-slate-200 flex items-center space-x-4 shadow-sm">
                    <div className="p-3.5 rounded-2xl bg-rose-50 text-rose-600 border border-rose-200">
                      <Heart className="w-7 h-7 animate-pulse" />
                    </div>
                    <div>
                      <div className="text-xs text-slate-500 font-semibold">Heart Rate</div>
                      <div className="text-3xl font-black text-slate-900">72 <span className="text-xs text-slate-500 font-normal">bpm</span></div>
                      <div className="text-[10px] text-emerald-600 font-extrabold mt-1">Sinus Rhythm</div>
                    </div>
                  </div>

                  <div className="p-6 rounded-2xl bg-white border border-slate-200 flex items-center space-x-4 shadow-sm">
                    <div className="p-3.5 rounded-2xl bg-blue-50 text-blue-600 border border-blue-200">
                      <Activity className="w-7 h-7" />
                    </div>
                    <div>
                      <div className="text-xs text-slate-500 font-semibold">Blood Pressure</div>
                      <div className="text-3xl font-black text-slate-900">120/80 <span className="text-xs text-slate-500 font-normal">mmHg</span></div>
                      <div className="text-[10px] text-blue-600 font-extrabold mt-1">Normal Reading</div>
                    </div>
                  </div>

                  <div className="p-6 rounded-2xl bg-white border border-slate-200 flex items-center space-x-4 shadow-sm">
                    <div className="p-3.5 rounded-2xl bg-amber-50 text-amber-600 border border-amber-200">
                      <Thermometer className="w-7 h-7" />
                    </div>
                    <div>
                      <div className="text-xs text-slate-500 font-semibold">Temperature</div>
                      <div className="text-3xl font-black text-slate-900">98.6 <span className="text-xs text-slate-500 font-normal">°F</span></div>
                      <div className="text-[10px] text-slate-500 font-bold mt-1">Afebrile</div>
                    </div>
                  </div>

                </div>
              </div>
            )}

            {activeScreen === 'hospital' && (
              <div className="space-y-6 animate-in fade-in duration-300">
                <div className="flex items-center justify-between">
                  <h4 className="text-lg font-black text-slate-900 flex items-center gap-2">
                    <BedDouble className="w-5 h-5 text-blue-600" /> Hospital ADT &amp; Inpatient Bed Tracker
                  </h4>
                  <span className="text-xs text-blue-700 font-extrabold bg-blue-50 px-3 py-1 rounded-full border border-blue-200">
                    Hospital Module Active
                  </span>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                  <div className="p-4 rounded-xl bg-white border border-slate-200 text-center shadow-xs">
                    <div className="text-xs text-slate-500 font-semibold">Total Hospital Beds</div>
                    <div className="text-2xl font-black text-slate-900 mt-1">250 Beds</div>
                    <div className="text-[10px] text-blue-600 font-bold mt-1">Main Facility</div>
                  </div>

                  <div className="p-4 rounded-xl bg-white border border-slate-200 text-center shadow-xs">
                    <div className="text-xs text-slate-500 font-semibold">Current Occupancy</div>
                    <div className="text-2xl font-black text-emerald-600 mt-1">82% (205 Occupied)</div>
                    <div className="text-[10px] text-slate-500 font-medium mt-1">45 Beds Available</div>
                  </div>

                  <div className="p-4 rounded-xl bg-white border border-slate-200 text-center shadow-xs">
                    <div className="text-xs text-slate-500 font-semibold">ER Triage Queue</div>
                    <div className="text-2xl font-black text-amber-600 mt-1">4 Patients</div>
                    <div className="text-[10px] text-slate-500 font-medium mt-1">Avg Wait: 8 mins</div>
                  </div>
                </div>
              </div>
            )}

          </div>

        </div>

      </div>
    </section>
  );
}
