import React, { useState } from 'react';
import { Activity, Lock, Mail, ArrowLeft, ShieldCheck, AlertCircle, Key } from 'lucide-react';

export default function AdminLogin({ onLoginSuccess, onBackToLanding }) {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const handleLogin = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError('');

    const API_BASE = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000';

    try {
      // API Authentication
      const res = await fetch(`${API_BASE}/api/admin/login`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ email, password })
      });

      const data = await res.json();

      if (res.ok && data.success && data.token) {
        localStorage.setItem('adminToken', data.token);
        localStorage.setItem('adminUser', JSON.stringify(data.user));
        setLoading(false);
        onLoginSuccess();
      } else {
        setLoading(false);
        setError(data.message || 'Invalid administrator credentials');
      }
    } catch (err) {
      setLoading(false);
      setError('Unable to reach authentication server. Please check your network connection.');
    }
  };

  return (
    <div className="min-h-screen bg-slate-50 text-slate-900 font-['Plus_Jakarta_Sans',sans-serif] flex items-center justify-center p-4">
      
      <div className="w-full max-w-md bg-white border border-slate-200 rounded-3xl p-8 shadow-xl space-y-6 relative overflow-hidden">
        
        {/* Top Accent Line */}
        <div className="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-blue-600 via-cyan-500 to-emerald-500" />

        {/* Header */}
        <div className="text-center space-y-3">
          <div className="w-12 h-12 rounded-2xl bg-blue-600 text-white font-bold flex items-center justify-center mx-auto shadow-md shadow-blue-600/20">
            <Activity className="w-7 h-7 stroke-[2.5]" />
          </div>
          <div>
            <h2 className="text-2xl font-black text-slate-900">AuraEMR Admin Portal</h2>
            <p className="text-xs text-slate-500 font-semibold mt-0.5">
              Authorized Management &amp; Subscriber Console
            </p>
          </div>
        </div>

        {/* Admin Login Credentials Box */}
        <div className="p-3.5 rounded-2xl bg-blue-50 border border-blue-200 text-xs space-y-1">
          <div className="flex items-center space-x-1.5 text-blue-700 font-bold">
            <ShieldCheck className="w-4 h-4 text-blue-600" />
            <span>Administrator Credentials:</span>
          </div>
          <div className="text-slate-700 text-[11px] font-mono pl-5">
            Email: <strong className="text-slate-900">admin@auraemr.com</strong><br />
            Password: <strong className="text-slate-900">admin123</strong>
          </div>
        </div>

        {error && (
          <div className="p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs flex items-center gap-2 font-semibold">
            <AlertCircle className="w-4 h-4 text-rose-600 shrink-0" />
            <span>{error}</span>
          </div>
        )}

        {/* Login Form */}
        <form onSubmit={handleLogin} className="space-y-4">
          <div>
            <label className="text-xs font-bold text-slate-700 block mb-1.5">Admin Email</label>
            <div className="relative">
              <Mail className="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
              <input
                type="email"
                required
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                className="w-full bg-slate-50 border border-slate-200 rounded-xl pl-10 pr-4 py-2.5 text-xs text-slate-900 font-semibold focus:border-blue-600 focus:outline-none"
              />
            </div>
          </div>

          <div>
            <label className="text-xs font-bold text-slate-700 block mb-1.5">Password</label>
            <div className="relative">
              <Lock className="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
              <input
                type="password"
                required
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                className="w-full bg-slate-50 border border-slate-200 rounded-xl pl-10 pr-4 py-2.5 text-xs text-slate-900 font-semibold focus:border-blue-600 focus:outline-none"
              />
            </div>
          </div>

          <button
            type="submit"
            disabled={loading}
            className="w-full py-3.5 rounded-xl bg-blue-600 text-white font-black text-sm shadow-md shadow-blue-600/25 hover:bg-blue-700 transition-all flex items-center justify-center space-x-2 cursor-pointer disabled:opacity-50"
          >
            {loading ? (
              <span>Authenticating...</span>
            ) : (
              <>
                <Key className="w-4 h-4" />
                <span>Sign In to Admin Portal</span>
              </>
            )}
          </button>
        </form>

        <div className="pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
          <button
            onClick={onBackToLanding}
            className="text-slate-500 hover:text-slate-900 font-semibold flex items-center gap-1 cursor-pointer"
          >
            <ArrowLeft className="w-3.5 h-3.5" /> Back to Landing Page
          </button>

          <span className="text-slate-400 font-semibold text-[11px]">
            Secure TLS 1.3
          </span>
        </div>

      </div>

    </div>
  );
}
