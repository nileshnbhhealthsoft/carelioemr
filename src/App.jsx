import React, { useState, useEffect } from 'react';
import Navbar from './components/Navbar';
import HeroSection from './components/HeroSection';
import TargetAudience from './components/TargetAudience';
import GeographicCoverage from './components/GeographicCoverage';
import FeaturesSection from './components/FeaturesSection';
import InteractiveDashboardPreview from './components/InteractiveDashboardPreview';
import PricingSection from './components/PricingSection';
import TestimonialsFAQ from './components/TestimonialsFAQ';
import Footer from './components/Footer';
import StripeCheckoutModal from './components/StripeCheckoutModal';
import AdminDashboard from './components/AdminDashboard';
import AdminLogin from './components/AdminLogin';

export default function App() {
  const [isCheckoutOpen, setIsCheckoutOpen] = useState(false);
  const [selectedRegion, setSelectedRegion] = useState('LC'); // Default Saint Lucia
  const [viewMode, setViewMode] = useState('landing'); // 'landing', 'admin-login', 'admin-dashboard'
  const [isAdminAuthenticated, setIsAdminAuthenticated] = useState(false);

  // Check URL Hash on load (#admin or #admin-login)
  useEffect(() => {
    const handleHashChange = () => {
      const hash = window.location.hash.toLowerCase();
      if (hash === '#admin' || hash === '#admin-login' || hash === '#admin-dashboard') {
        if (isAdminAuthenticated) {
          setViewMode('admin-dashboard');
        } else {
          setViewMode('admin-login');
        }
      } else {
        setViewMode('landing');
      }
    };

    handleHashChange();
    window.addEventListener('hashchange', handleHashChange);
    return () => window.removeEventListener('hashchange', handleHashChange);
  }, [isAdminAuthenticated]);

  const handleOpenAdmin = () => {
    window.location.hash = 'admin-login';
    if (isAdminAuthenticated) {
      setViewMode('admin-dashboard');
    } else {
      setViewMode('admin-login');
    }
  };

  const handleAdminLoginSuccess = () => {
    setIsAdminAuthenticated(true);
    setViewMode('admin-dashboard');
    window.location.hash = 'admin-dashboard';
  };

  const handleBackToLanding = () => {
    setViewMode('landing');
    window.location.hash = '';
  };

  if (viewMode === 'admin-login') {
    return (
      <AdminLogin
        onLoginSuccess={handleAdminLoginSuccess}
        onBackToLanding={handleBackToLanding}
      />
    );
  }

  if (viewMode === 'admin-dashboard') {
    return (
      <AdminDashboard
        onBackToLanding={handleBackToLanding}
      />
    );
  }

  return (
    <div className="min-h-screen bg-slate-50 text-slate-900 font-['Plus_Jakarta_Sans',sans-serif] selection:bg-blue-600 selection:text-white">
      
      {/* Top Professional Banner */}
      <div className="bg-slate-900 text-white px-4 py-2 text-xs font-semibold flex flex-col sm:flex-row items-center justify-between gap-2">
        <div className="flex items-center space-x-2">
          <span className="w-2 h-2 rounded-full bg-emerald-400 animate-pulse" />
          <span>AuraEMR Cloud Healthcare Platform • 24/7 Global Infrastructure</span>
        </div>
        <button
          onClick={handleOpenAdmin}
          className="bg-blue-600 hover:bg-blue-500 text-white px-3 py-1 rounded-lg text-xs font-bold transition-all cursor-pointer whitespace-nowrap"
        >
          Admin Portal &rarr;
        </button>
      </div>

      {/* Navigation Bar */}
      <Navbar
        onOpenCheckout={() => setIsCheckoutOpen(true)}
        selectedRegion={selectedRegion}
        setSelectedRegion={setSelectedRegion}
        onOpenAdmin={handleOpenAdmin}
      />

      {/* Main Hero Section */}
      <HeroSection
        onOpenCheckout={() => setIsCheckoutOpen(true)}
        selectedRegion={selectedRegion}
      />

      {/* Target Audience */}
      <TargetAudience
        onOpenCheckout={() => setIsCheckoutOpen(true)}
      />

      {/* Geographic Coverage */}
      <GeographicCoverage
        selectedRegion={selectedRegion}
        setSelectedRegion={setSelectedRegion}
      />

      {/* Core Platform Features */}
      <FeaturesSection />

      {/* Interactive EMR Workstation Preview */}
      <InteractiveDashboardPreview />

      {/* Pricing & Subscription ($80/month + Setup note) */}
      <PricingSection
        onOpenCheckout={() => setIsCheckoutOpen(true)}
        selectedRegion={selectedRegion}
      />

      {/* Social Proof & FAQ */}
      <TestimonialsFAQ />

      {/* Footer */}
      <Footer
        onOpenCheckout={() => setIsCheckoutOpen(true)}
      />

      {/* Stripe Payment Checkout Modal */}
      <StripeCheckoutModal
        isOpen={isCheckoutOpen}
        onClose={() => setIsCheckoutOpen(false)}
        selectedRegion={selectedRegion}
      />

    </div>
  );
}
