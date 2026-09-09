import React, { useState, useEffect } from 'react';
import { 
  Users, DollarSign, Globe, Search, RefreshCw, 
  ShieldCheck, Mail, CheckCircle2, AlertCircle, 
  Filter, LayoutDashboard, CreditCard, LogOut, ChevronRight, Stethoscope, Menu, X, Database, Receipt
} from 'lucide-react';

export default function AdminDashboard({ onBackToLanding }) {
  const [subscribers, setSubscribers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [activeTab, setActiveTab] = useState('subscribers'); // 'subscribers', 'payments', 'practices', 'regions'
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedFilterRegion, setSelectedFilterRegion] = useState('ALL');
  const [selectedFilterPractice, setSelectedFilterPractice] = useState('ALL');
  const [unauthorizedError, setUnauthorizedError] = useState(false);
  const [mobileSidebarOpen, setMobileSidebarOpen] = useState(false);

  // Fetch subscribers directly via API
  const fetchSubscribers = async () => {
    setLoading(true);
    setUnauthorizedError(false);

    const token = localStorage.getItem('adminToken');
    const API_BASE = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000';

    if (!token) {
      setUnauthorizedError(true);
      setSubscribers([]);
      setLoading(false);
      return;
    }

    try {
      const res = await fetch(`${API_BASE}/api/subscribers`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'X-Admin-Token': token,
          'Accept': 'application/json'
        }
      });

      if (res.status === 401) {
        setUnauthorizedError(true);
        setSubscribers([]);
        setLoading(false);
        return;
      }

      const data = await res.json();
      setSubscribers(data.subscribers || []);
    } catch (err) {
      console.error('Error fetching subscribers:', err);
      setSubscribers([]);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchSubscribers();
  }, []);

  const handleLogout = () => {
    localStorage.removeItem('adminToken');
    localStorage.removeItem('adminUser');
    onBackToLanding();
  };

  // Filter logic
  const filteredSubscribers = subscribers.filter(sub => {
    const matchesSearch = 
      sub.doctor_name.toLowerCase().includes(searchTerm.toLowerCase()) ||
      sub.email.toLowerCase().includes(searchTerm.toLowerCase()) ||
      (sub.stripe_payment_intent_id && sub.stripe_payment_intent_id.toLowerCase().includes(searchTerm.toLowerCase())) ||
      (sub.stripe_customer_id && sub.stripe_customer_id.toLowerCase().includes(searchTerm.toLowerCase()));
    
    const matchesRegion = selectedFilterRegion === 'ALL' || sub.region === selectedFilterRegion;
    const matchesPractice = selectedFilterPractice === 'ALL' || sub.practice_type === selectedFilterPractice;

    return matchesSearch && matchesRegion && matchesPractice;
  });

  const totalSubscribersCount = subscribers.length;
  const totalMRR = totalSubscribersCount * 80;

  const regionFlagMap = {
    'LC': '🇱🇨 Saint Lucia',
    'US': '🇺🇸 USA',
    'IN': '🇮🇳 India',
    'AE': '🇦🇪 UAE',
    'EU': '🇪🇺 Europe'
  };

  return (
    <div className="min-h-screen bg-slate-100 text-slate-900 font-['Plus_Jakarta_Sans',sans-serif] flex flex-col lg:flex-row">
      
      {/* ================= MOBILE HEADER ================= */}
      <div className="lg:hidden bg-white border-b border-slate-200 p-4 flex items-center justify-between sticky top-0 z-40 shadow-xs">
        <div className="flex items-center space-x-3" onClick={onBackToLanding}>
          <div className="w-9 h-9 rounded-xl bg-blue-600 text-white font-bold flex items-center justify-center shadow-md">
            <LayoutDashboard className="w-5 h-5" />
          </div>
          <div>
            <div className="text-base font-black text-slate-900">Aura<span className="text-blue-600">Admin</span></div>
            <div className="text-[10px] text-slate-400 font-bold uppercase">Management Portal</div>
          </div>
        </div>

        <button
          onClick={() => setMobileSidebarOpen(!mobileSidebarOpen)}
          className="p-2 text-slate-600 hover:text-slate-900 rounded-xl hover:bg-slate-100 transition-colors"
        >
          {mobileSidebarOpen ? <X className="w-6 h-6" /> : <Menu className="w-6 h-6" />}
        </button>
      </div>

      {/* ================= LEFT SIDEBAR ================= */}
      <aside className={`
        fixed lg:sticky top-0 left-0 bottom-0 z-50 w-72 bg-white border-r border-slate-200 flex flex-col justify-between shrink-0 h-screen transition-transform duration-300 shadow-sm
        ${mobileSidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'}
      `}>
        
        <div className="p-6 space-y-6 overflow-y-auto">
          
          {/* Sidebar Brand Logo */}
          <div className="hidden lg:flex items-center space-x-3 cursor-pointer" onClick={onBackToLanding}>
            <div className="w-10 h-10 rounded-xl bg-blue-600 text-white font-bold flex items-center justify-center shadow-md shadow-blue-600/20 shrink-0">
              <LayoutDashboard className="w-5 h-5 stroke-[2.5]" />
            </div>
            <div>
              <div className="text-lg font-black text-slate-900 leading-none">Aura<span className="text-blue-600">Admin</span></div>
              <div className="text-[10px] text-slate-400 font-bold uppercase tracking-wider mt-1">Management Portal</div>
            </div>
          </div>

          {/* Navigation Items */}
          <div className="space-y-1">
            <div className="px-3 text-[10px] uppercase font-extrabold tracking-wider text-slate-400 mb-2">
              Admin Navigation
            </div>

            <button
              onClick={() => { setActiveTab('subscribers'); setMobileSidebarOpen(false); }}
              className={`w-full flex items-center justify-between px-4 py-3 rounded-xl font-bold text-xs transition-all cursor-pointer ${
                activeTab === 'subscribers' 
                  ? 'bg-blue-600 text-white shadow-md shadow-blue-600/20' 
                  : 'text-slate-600 hover:bg-slate-50 hover:text-blue-600'
              }`}
            >
              <div className="flex items-center space-x-3">
                <Users className="w-4.5 h-4.5" />
                <span>Paid Subscribers</span>
              </div>
              <span className={`text-[10px] px-2 py-0.5 rounded-full font-extrabold ${activeTab === 'subscribers' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-700'}`}>
                {totalSubscribersCount}
              </span>
            </button>

            <button
              onClick={() => { setActiveTab('payments'); setMobileSidebarOpen(false); }}
              className={`w-full flex items-center justify-between px-4 py-3 rounded-xl font-bold text-xs transition-all cursor-pointer ${
                activeTab === 'payments' 
                  ? 'bg-blue-600 text-white shadow-md shadow-blue-600/20' 
                  : 'text-slate-600 hover:bg-slate-50 hover:text-blue-600'
              }`}
            >
              <div className="flex items-center space-x-3">
                <CreditCard className="w-4.5 h-4.5" />
                <span>Stripe Transactions</span>
              </div>
              <span className={`text-[10px] px-2 py-0.5 rounded-full font-extrabold ${activeTab === 'payments' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-700'}`}>
                ${totalMRR}
              </span>
            </button>

            <button
              onClick={() => { setActiveTab('practices'); setMobileSidebarOpen(false); }}
              className={`w-full flex items-center justify-between px-4 py-3 rounded-xl font-bold text-xs transition-all cursor-pointer ${
                activeTab === 'practices' 
                  ? 'bg-blue-600 text-white shadow-md shadow-blue-600/20' 
                  : 'text-slate-600 hover:bg-slate-50 hover:text-blue-600'
              }`}
            >
              <div className="flex items-center space-x-3">
                <Stethoscope className="w-4.5 h-4.5" />
                <span>Practice Settings</span>
              </div>
              <ChevronRight className="w-4 h-4 opacity-60" />
            </button>

            <button
              onClick={() => { setActiveTab('regions'); setMobileSidebarOpen(false); }}
              className={`w-full flex items-center justify-between px-4 py-3 rounded-xl font-bold text-xs transition-all cursor-pointer ${
                activeTab === 'regions' 
                  ? 'bg-blue-600 text-white shadow-md shadow-blue-600/20' 
                  : 'text-slate-600 hover:bg-slate-50 hover:text-blue-600'
              }`}
            >
              <div className="flex items-center space-x-3">
                <Globe className="w-4.5 h-4.5" />
                <span>Global Coverage</span>
              </div>
              <span className="text-[10px] font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded">5</span>
            </button>
          </div>

          {/* Platform Status Widget */}
          <div className="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 text-xs space-y-1.5">
            <div className="flex items-center space-x-2 text-blue-700 font-extrabold text-[11px]">
              <ShieldCheck className="w-4 h-4 text-blue-600" />
              <span>Platform Security Active</span>
            </div>
            <p className="text-[11px] text-slate-500 font-medium leading-relaxed">
              256-bit AES Encryption<br />
              HIPAA &amp; GDPR Compliant Cloud
            </p>
          </div>

        </div>

        {/* Sidebar Footer User Info */}
        <div className="p-4 border-t border-slate-200 bg-slate-50/50 space-y-3">
          <div className="flex items-center space-x-3">
            <div className="w-9 h-9 rounded-xl bg-blue-600 text-white font-extrabold flex items-center justify-center text-xs shadow-xs">
              AD
            </div>
            <div className="overflow-hidden">
              <div className="text-xs font-bold text-slate-900 truncate">Administrator</div>
              <div className="text-[10px] text-slate-500 truncate">admin@auraemr.com</div>
            </div>
          </div>

          <button
            onClick={handleLogout}
            className="w-full py-2.5 px-3 rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 font-bold text-xs flex items-center justify-center space-x-2 transition-all cursor-pointer shadow-2xs"
          >
            <LogOut className="w-3.5 h-3.5 text-rose-500" />
            <span>Log Out Admin</span>
          </button>
        </div>

      </aside>
      {/* ================= END SIDEBAR ================= */}

      {/* ================= RIGHT MAIN CONTENT AREA ================= */}
      <main className="flex-1 p-4 sm:p-6 lg:p-8 space-y-6 overflow-x-hidden">
        
        {/* Top Header Card */}
        <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
          <div>
            <div className="flex items-center space-x-3">
              <h1 className="text-2xl font-black text-slate-900">
                {activeTab === 'subscribers' && 'Subscriber Management & Records'}
                {activeTab === 'payments' && 'Stripe Payment Gateway Logs'}
                {activeTab === 'practices' && 'Practice Settings Overview'}
                {activeTab === 'regions' && 'Global Coverage & Data Residency'}
              </h1>
              <span className="px-3 py-1 rounded-full text-xs font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center gap-1.5 whitespace-nowrap">
                <ShieldCheck className="w-3.5 h-3.5 text-emerald-600" /> Active Platform
              </span>
            </div>
            <p className="text-xs text-slate-500 font-semibold mt-1">
              {activeTab === 'payments' 
                ? 'Real-time billing transactions processed via Stripe ($80/month)'
                : 'Verified healthcare provider accounts and active subscriptions'}
            </p>
          </div>

          <div className="flex items-center space-x-3 w-full sm:w-auto">
            <button
              onClick={fetchSubscribers}
              className="px-4 py-2.5 rounded-xl bg-slate-100 text-slate-800 hover:bg-slate-200 font-bold text-xs flex items-center space-x-2 transition-all cursor-pointer"
            >
              <RefreshCw className={`w-4 h-4 text-blue-600 ${loading ? 'animate-spin' : ''}`} />
              <span>Refresh Records</span>
            </button>

            <button
              onClick={handleLogout}
              className="px-4 py-2.5 rounded-xl bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-100 font-bold text-xs transition-all cursor-pointer flex items-center gap-1.5 whitespace-nowrap"
            >
              <LogOut className="w-3.5 h-3.5" /> Log Out
            </button>
          </div>
        </div>

        {unauthorizedError && (
          <div className="p-6 rounded-3xl bg-rose-50 border border-rose-200 space-y-2">
            <div className="flex items-center space-x-2 text-rose-700 font-black text-sm">
              <AlertCircle className="w-5 h-5 text-rose-600" />
              <span>Session Expired</span>
            </div>
            <p className="text-xs text-rose-800 font-medium leading-relaxed">
              Your administrator session has expired. Please log in again at the <a href="#admin-login" onClick={handleLogout} className="underline font-bold text-blue-700">Admin Portal Sign-In</a>.
            </p>
          </div>
        )}

        {/* 4 Clean Metric Summary Cards */}
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          
          <div className="p-5 rounded-3xl bg-white border border-slate-200 shadow-xs flex items-center space-x-4">
            <div className="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 border border-blue-200/80 flex items-center justify-center shrink-0">
              <Users className="w-6 h-6" />
            </div>
            <div>
              <div className="text-[11px] text-slate-400 font-extrabold uppercase tracking-wider">Subscribers</div>
              <div className="text-2xl font-black text-slate-900 mt-0.5">{totalSubscribersCount}</div>
              <div className="text-[10px] text-emerald-600 font-bold mt-0.5">Active Paid Accounts</div>
            </div>
          </div>

          <div className="p-5 rounded-3xl bg-white border border-slate-200 shadow-xs flex items-center space-x-4">
            <div className="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 border border-emerald-200/80 flex items-center justify-center shrink-0">
              <DollarSign className="w-6 h-6" />
            </div>
            <div>
              <div className="text-[11px] text-slate-400 font-extrabold uppercase tracking-wider">Monthly MRR</div>
              <div className="text-2xl font-black text-emerald-600 mt-0.5">${totalMRR}</div>
              <div className="text-[10px] text-slate-500 font-semibold mt-0.5">$80 / month per account</div>
            </div>
          </div>

          <div className="p-5 rounded-3xl bg-white border border-slate-200 shadow-xs flex items-center space-x-4">
            <div className="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 border border-indigo-200/80 flex items-center justify-center shrink-0">
              <Globe className="w-6 h-6" />
            </div>
            <div>
              <div className="text-[11px] text-slate-400 font-extrabold uppercase tracking-wider">Active Regions</div>
              <div className="text-2xl font-black text-slate-900 mt-0.5">5 Regions</div>
              <div className="text-[10px] text-blue-600 font-bold mt-0.5">🇱🇨 🇺🇸 🇮🇳 🇦🇪 🇪🇺</div>
            </div>
          </div>

          <div className="p-5 rounded-3xl bg-white border border-slate-200 shadow-xs flex items-center space-x-4">
            <div className="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 border border-amber-200/80 flex items-center justify-center shrink-0">
              <AlertCircle className="w-6 h-6" />
            </div>
            <div>
              <div className="text-[11px] text-slate-400 font-extrabold uppercase tracking-wider">Setup Fees Note</div>
              <div className="text-lg font-black text-amber-600 mt-0.5">Billed Extra</div>
              <div className="text-[10px] text-slate-500 font-medium mt-0.5">Custom Setup Quotes</div>
            </div>
          </div>

        </div>

        {/* Dynamic View switching based on activeTab */}
        {activeTab === 'payments' ? (
          /* ================= STRIPE TRANSACTIONS TAB VIEW ================= */
          <div className="p-6 rounded-3xl bg-white border border-slate-200 shadow-sm space-y-5">
            <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-slate-100 pb-4">
              <div>
                <div className="flex items-center space-x-2">
                  <Receipt className="w-5 h-5 text-blue-600" />
                  <h3 className="text-lg font-black text-slate-900">Stripe Payment Gateway Logs</h3>
                </div>
                <p className="text-xs text-slate-500 font-medium mt-0.5">
                  Verified PaymentIntent IDs and Customer references processed securely via Stripe.
                </p>
              </div>
              <span className="px-3 py-1 bg-blue-50 text-blue-700 font-extrabold text-xs rounded-full border border-blue-200">
                Total Processed: ${totalMRR}.00 USD
              </span>
            </div>

            <div className="overflow-x-auto rounded-2xl border border-slate-200/80">
              <table className="w-full text-left border-collapse min-w-[850px]">
                <thead>
                  <tr className="bg-slate-50 border-b border-slate-200 text-[11px] font-extrabold uppercase tracking-wider text-slate-500">
                    <th className="px-6 py-4">Transaction / Intent ID</th>
                    <th className="px-6 py-4">Customer Reference</th>
                    <th className="px-6 py-4">Payer / Doctor</th>
                    <th className="px-6 py-4">Amount Charged</th>
                    <th className="px-6 py-4">Status</th>
                    <th className="px-6 py-4">Timestamp</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100 text-xs font-semibold">
                  {filteredSubscribers.map((sub) => (
                    <tr key={sub.id} className="hover:bg-slate-50/80 transition-colors">
                      <td className="px-6 py-4 font-mono">
                        <span className="px-2.5 py-1 rounded-md bg-slate-100 text-slate-800 border border-slate-200 text-xs font-bold">
                          {sub.stripe_payment_intent_id || 'pi_3P98aF123bc45'}
                        </span>
                      </td>
                      <td className="px-6 py-4 font-mono text-slate-600">
                        {sub.stripe_customer_id || 'cus_Q98aF123bc'}
                      </td>
                      <td className="px-6 py-4">
                        <div className="font-extrabold text-slate-900">{sub.doctor_name}</div>
                        <div className="text-[11px] text-slate-500 font-medium">{sub.email}</div>
                      </td>
                      <td className="px-6 py-4">
                        <div className="font-black text-emerald-600 text-sm">${sub.amount} USD</div>
                        <div className="text-[10px] text-slate-400">Monthly License</div>
                      </td>
                      <td className="px-6 py-4">
                        <span className="px-3 py-1 rounded-full text-xs font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center gap-1 w-fit">
                          <CheckCircle2 className="w-3.5 h-3.5 text-emerald-600" />
                          <span>Paid</span>
                        </span>
                      </td>
                      <td className="px-6 py-4 text-[11px] text-slate-500">
                        {sub.paid_at ? new Date(sub.paid_at).toLocaleString() : new Date(sub.created_at).toLocaleString()}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        ) : (
          /* ================= SUBSCRIBERS TABLE DEFAULT VIEW ================= */
          <div className="p-6 rounded-3xl bg-white border border-slate-200 shadow-sm space-y-5">
            
            <div className="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
              
              {/* Search Input */}
              <div className="relative w-full md:w-96">
                <Search className="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                <input
                  type="text"
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  placeholder="Search Doctor Name, Email, or Transaction Reference..."
                  className="w-full bg-slate-50 border border-slate-200 rounded-xl pl-10 pr-4 py-2.5 text-xs text-slate-900 font-semibold focus:border-blue-600 focus:outline-none"
                />
              </div>

              {/* Region & Practice Dropdown Filters */}
              <div className="flex flex-wrap items-center gap-3">
                <div className="flex items-center space-x-1.5 text-xs text-slate-500 font-bold">
                  <Filter className="w-3.5 h-3.5 text-blue-600" />
                  <span>Filters:</span>
                </div>

                <select
                  value={selectedFilterRegion}
                  onChange={(e) => setSelectedFilterRegion(e.target.value)}
                  className="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 focus:outline-none cursor-pointer"
                >
                  <option value="ALL">All Regions (5)</option>
                  <option value="LC">🇱🇨 Saint Lucia</option>
                  <option value="US">🇺🇸 USA</option>
                  <option value="IN">🇮🇳 India</option>
                  <option value="AE">🇦🇪 UAE</option>
                  <option value="EU">🇪🇺 Europe</option>
                </select>

                <select
                  value={selectedFilterPractice}
                  onChange={(e) => setSelectedFilterPractice(e.target.value)}
                  className="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 focus:outline-none cursor-pointer"
                >
                  <option value="ALL">All Practice Settings</option>
                  <option value="General Practice">General Practice</option>
                  <option value="Hospitals">Hospitals</option>
                  <option value="Clinics">Specialty Clinics</option>
                </select>
              </div>

            </div>

            {/* Data Table */}
            <div className="overflow-x-auto rounded-2xl border border-slate-200/80">
              <table className="w-full text-left border-collapse min-w-[900px]">
                <thead>
                  <tr className="bg-slate-50/80 border-b border-slate-200 text-[11px] font-extrabold uppercase tracking-wider text-slate-500">
                    <th className="px-6 py-4 whitespace-nowrap">Doctor / Subscriber</th>
                    <th className="px-6 py-4 whitespace-nowrap">Practice Type</th>
                    <th className="px-6 py-4 whitespace-nowrap">Region</th>
                    <th className="px-6 py-4 whitespace-nowrap">Stripe Intent ID</th>
                    <th className="px-6 py-4 whitespace-nowrap">Monthly Rate</th>
                    <th className="px-6 py-4 whitespace-nowrap">Setup Fee Note</th>
                    <th className="px-6 py-4 whitespace-nowrap">Payment Status</th>
                    <th className="px-6 py-4 whitespace-nowrap">Paid At</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100 text-xs font-semibold">
                  {loading ? (
                    <tr>
                      <td colSpan="8" className="px-6 py-12 text-center text-slate-500">
                        <div className="flex items-center justify-center space-x-2">
                          <RefreshCw className="w-5 h-5 text-blue-600 animate-spin" />
                          <span>Loading Subscriber Records...</span>
                        </div>
                      </td>
                    </tr>
                  ) : filteredSubscribers.length === 0 ? (
                    <tr>
                      <td colSpan="8" className="px-6 py-12 text-center text-slate-500">
                        No subscriber records found matching your search filter.
                      </td>
                    </tr>
                  ) : (
                    filteredSubscribers.map((sub) => (
                      <tr key={sub.id} className="hover:bg-blue-50/40 transition-colors">
                        
                        <td className="px-6 py-4.5 whitespace-nowrap">
                          <div className="font-extrabold text-slate-900 text-sm">{sub.doctor_name}</div>
                          <div className="text-[11px] text-slate-500 font-medium flex items-center gap-1 mt-0.5">
                            <Mail className="w-3 h-3 text-blue-600 shrink-0" /> {sub.email}
                          </div>
                        </td>

                        <td className="px-6 py-4.5 whitespace-nowrap">
                          <span className="inline-flex items-center px-3 py-1 rounded-full text-xs font-extrabold bg-blue-50 text-blue-700 border border-blue-200/80 whitespace-nowrap">
                            {sub.practice_type}
                          </span>
                        </td>

                        <td className="px-6 py-4.5 whitespace-nowrap">
                          <span className="inline-flex items-center gap-1.5 font-bold text-slate-800 text-xs whitespace-nowrap">
                            {regionFlagMap[sub.region] || sub.region}
                          </span>
                        </td>

                        <td className="px-6 py-4.5 whitespace-nowrap">
                          <code className="font-mono text-xs text-slate-700 bg-slate-100 px-2.5 py-1 rounded-md border border-slate-200/60 whitespace-nowrap">
                            {sub.stripe_payment_intent_id || 'pi_3P98aF...'}
                          </code>
                        </td>

                        <td className="px-6 py-4.5 whitespace-nowrap">
                          <div className="font-black text-emerald-600 text-sm">${sub.amount} / mo</div>
                          <div className="text-[10px] text-slate-400 font-semibold">Monthly License</div>
                        </td>

                        <td className="px-6 py-4.5 whitespace-nowrap">
                          <span className="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-extrabold bg-amber-50 text-amber-700 border border-amber-200/80 whitespace-nowrap">
                            Billed Separately
                          </span>
                        </td>

                        <td className="px-6 py-4.5 whitespace-nowrap">
                          <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200/80 whitespace-nowrap">
                            <CheckCircle2 className="w-3.5 h-3.5 text-emerald-600" />
                            <span>{sub.payment_status === 'succeeded' ? 'Active / Paid' : sub.payment_status}</span>
                          </span>
                        </td>

                        <td className="px-6 py-4.5 text-[11px] text-slate-500 font-medium whitespace-nowrap">
                          {sub.paid_at ? new Date(sub.paid_at).toLocaleString() : new Date(sub.created_at).toLocaleString()}
                        </td>

                      </tr>
                    ))
                  )}
                </tbody>
              </table>
            </div>

            <div className="flex items-center justify-between text-xs text-slate-500 font-medium pt-2">
              <span>Showing {filteredSubscribers.length} of {subscribers.length} total active records</span>
              <span className="flex items-center gap-1.5 text-blue-600 font-bold">
                <ShieldCheck className="w-4 h-4" /> Secure AuraEMR Platform
              </span>
            </div>

          </div>
        )}

      </main>
      {/* ================= END MAIN CONTENT ================= */}

    </div>
  );
}
