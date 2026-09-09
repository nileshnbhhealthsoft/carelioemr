import React, { useState } from 'react';
import { Activity, Globe, ShieldCheck, ChevronDown, CreditCard, Menu, X, LayoutDashboard } from 'lucide-react';

const REGIONS = [
  { code: 'LC', name: 'Saint Lucia', flag: '🇱🇨' },
  { code: 'US', name: 'USA', flag: '🇺🇸' },
  { code: 'IN', name: 'India', flag: '🇮🇳' },
  { code: 'AE', name: 'UAE', flag: '🇦🇪' },
  { code: 'EU', name: 'Europe', flag: '🇪🇺' },
];

export default function Navbar({ onOpenCheckout, selectedRegion, setSelectedRegion, onOpenAdmin }) {
  const [regionOpen, setRegionOpen] = useState(false);
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

  const activeRegionObj = REGIONS.find(r => r.code === selectedRegion) || REGIONS[0];

  return (
    <header className="sticky top-0 z-50 w-full border-b border-slate-200 bg-white/95 backdrop-blur-md shadow-xs">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between gap-4">
        
        {/* Left: Brand Logo */}
        <div 
          className="flex items-center space-x-3 cursor-pointer shrink-0" 
          onClick={() => window.scrollTo({ top: 0, behavior: 'smooth' })}
        >
          <div className="flex items-center justify-center w-10 h-10 rounded-xl bg-blue-600 text-white shadow-md shadow-blue-600/20 font-bold shrink-0">
            <Activity className="w-6 h-6 stroke-[2.5]" />
          </div>
          <div className="flex flex-col justify-center">
            <div className="flex items-center space-x-2">
              <span className="text-xl font-black tracking-tight text-slate-900 leading-none">
                Aura<span className="text-blue-600">EMR</span>
              </span>
              <span className="text-[10px] uppercase font-extrabold tracking-wider px-2 py-0.5 rounded-md bg-blue-50 text-blue-700 border border-blue-200/80 shrink-0">
                Cloud
              </span>
            </div>
            <span className="text-[11px] text-slate-500 font-semibold mt-0.5">
              EMR &amp; EHR Healthcare Suite
            </span>
          </div>
        </div>

        {/* Center: Navigation Links */}
        <nav className="hidden xl:flex items-center space-x-8 text-sm font-bold text-slate-700">
          <a href="#services" className="hover:text-blue-600 transition-colors whitespace-nowrap">
            Target Practices
          </a>
          <a href="#regions" className="hover:text-blue-600 transition-colors whitespace-nowrap">
            Global Coverage
          </a>
          <a href="#features" className="hover:text-blue-600 transition-colors whitespace-nowrap">
            Platform Features
          </a>
          <a href="#pricing" className="hover:text-blue-600 transition-colors whitespace-nowrap flex items-center gap-1.5">
            <span>Pricing</span>
            <span className="text-blue-600 font-extrabold text-xs bg-blue-50 px-2 py-0.5 rounded-full border border-blue-200">
              $80/mo
            </span>
          </a>
        </nav>

        {/* Right Actions: Admin Portal Button, Region Dropdown & Subscribe Button */}
        <div className="hidden lg:flex items-center space-x-3 shrink-0">
          
          {/* Admin Portal Button */}
          <button
            onClick={onOpenAdmin}
            className="flex items-center space-x-1.5 px-3.5 py-2 rounded-xl bg-slate-100 border border-slate-200 text-slate-800 text-xs font-bold hover:bg-slate-200 transition-all cursor-pointer whitespace-nowrap"
            title="Access Admin Portal"
          >
            <LayoutDashboard className="w-4 h-4 text-blue-600" />
            <span>Admin Portal</span>
          </button>

          {/* Region Switcher Dropdown */}
          <div className="relative">
            <button
              onClick={() => setRegionOpen(!regionOpen)}
              className="flex items-center space-x-2 px-3.5 py-2 rounded-xl bg-slate-100 border border-slate-200 text-slate-800 text-xs font-bold hover:bg-slate-200/70 transition-all cursor-pointer whitespace-nowrap"
            >
              <Globe className="w-4 h-4 text-blue-600 shrink-0" />
              <span>{activeRegionObj.flag} {activeRegionObj.name}</span>
              <ChevronDown className="w-3.5 h-3.5 text-slate-500 shrink-0" />
            </button>

            {regionOpen && (
              <div className="absolute right-0 mt-2 w-52 rounded-2xl bg-white border border-slate-200 shadow-2xl py-2 z-50 animate-in fade-in zoom-in-95">
                <div className="px-3.5 py-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-400 border-b border-slate-100 mb-1">
                  Select Coverage Region
                </div>
                {REGIONS.map((r) => (
                  <button
                    key={r.code}
                    onClick={() => {
                      setSelectedRegion(r.code);
                      setRegionOpen(false);
                    }}
                    className={`w-full text-left px-3.5 py-2 text-xs font-semibold flex items-center justify-between hover:bg-blue-50 transition-colors cursor-pointer ${
                      selectedRegion === r.code ? 'text-blue-600 font-bold bg-blue-50' : 'text-slate-700'
                    }`}
                  >
                    <span className="flex items-center space-x-2">
                      <span className="text-base">{r.flag}</span>
                      <span>{r.name}</span>
                    </span>
                    {selectedRegion === r.code && <ShieldCheck className="w-4 h-4 text-blue-600" />}
                  </button>
                ))}
              </div>
            )}
          </div>

          {/* Subscribe CTA Button */}
          <button
            onClick={onOpenCheckout}
            className="flex items-center space-x-2 px-4 py-2.5 rounded-xl bg-blue-600 text-white font-bold text-xs shadow-md shadow-blue-600/20 hover:bg-blue-700 hover:scale-[1.02] active:scale-[0.98] transition-all cursor-pointer whitespace-nowrap shrink-0"
          >
            <CreditCard className="w-4 h-4 stroke-[2.5]" />
            <span>Subscribe Now ($80/mo)</span>
          </button>
        </div>

        {/* Mobile / Tablet Controls */}
        <div className="flex lg:hidden items-center space-x-2 shrink-0">
          <button
            onClick={onOpenAdmin}
            className="px-2.5 py-1.5 text-xs font-bold bg-slate-100 text-slate-800 rounded-xl border border-slate-200 flex items-center gap-1"
          >
            <LayoutDashboard className="w-3.5 h-3.5 text-blue-600" /> Admin
          </button>
          <button
            onClick={onOpenCheckout}
            className="px-3 py-1.5 text-xs font-bold bg-blue-600 text-white rounded-xl shadow-xs"
          >
            $80/mo
          </button>
          <button
            onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
            className="p-2 text-slate-700 hover:text-slate-900 rounded-xl hover:bg-slate-100 transition-colors"
          >
            {mobileMenuOpen ? <X className="w-6 h-6" /> : <Menu className="w-6 h-6" />}
          </button>
        </div>

      </div>

      {/* Mobile Drawer Menu */}
      {mobileMenuOpen && (
        <div className="lg:hidden bg-white border-b border-slate-200 px-4 py-5 space-y-4 shadow-xl">
          <button
            onClick={() => {
              setMobileMenuOpen(false);
              onOpenAdmin();
            }}
            className="w-full text-left py-2 text-sm font-extrabold text-blue-600 flex items-center gap-2"
          >
            <LayoutDashboard className="w-4 h-4" /> Open Admin Portal
          </button>
          <a
            href="#services"
            onClick={() => setMobileMenuOpen(false)}
            className="block text-sm font-bold text-slate-800 hover:text-blue-600"
          >
            Target Practices (General Practice, Hospitals, Clinics)
          </a>
          <a
            href="#regions"
            onClick={() => setMobileMenuOpen(false)}
            className="block text-sm font-bold text-slate-800 hover:text-blue-600"
          >
            Global Coverage (Saint Lucia, USA, India, UAE, Europe)
          </a>
          <a
            href="#features"
            onClick={() => setMobileMenuOpen(false)}
            className="block text-sm font-bold text-slate-800 hover:text-blue-600"
          >
            Platform Features
          </a>
          <a
            href="#pricing"
            onClick={() => setMobileMenuOpen(false)}
            className="block text-sm font-bold text-slate-800 hover:text-blue-600"
          >
            Pricing ($80/month + Setup Fee Note)
          </a>

          <button
            onClick={() => {
              setMobileMenuOpen(false);
              onOpenCheckout();
            }}
            className="w-full py-3.5 bg-blue-600 text-white font-black text-sm rounded-xl text-center shadow-md hover:bg-blue-700 transition-colors"
          >
            Subscribe Now – $80 / Month
          </button>
        </div>
      )}
    </header>
  );
}
