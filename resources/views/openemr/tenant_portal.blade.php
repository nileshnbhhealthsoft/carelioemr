<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CarelioEMR Workstation — {{ $subscription->doctor_name ?? 'Dr. Subscriber' }}</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<script>
tailwind.config = {
  theme: {
    extend: {
      fontFamily: { sans: ['Inter', 'sans-serif'] }
    }
  }
}
</script>
<style>
* { font-family: 'Inter', sans-serif; }
.sidebar-item { transition: all 0.15s; }
.sidebar-item:hover { background: rgba(59,130,246,0.15); color: #ffffff; }
.sidebar-item.active { background: rgba(59,130,246,0.25); border-left: 3px solid #3b82f6; color: #ffffff; }
.fade-in { animation: fadeIn 0.25s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }
.stat-card { background: linear-gradient(135deg, rgba(30,41,59,0.9), rgba(15,23,42,0.9)); }
.pulse-dot { animation: pulse 2s infinite; }
@keyframes pulse { 0%,100%{ opacity:1; } 50%{ opacity:0.4; } }
@keyframes heartbeat { 0%,100%{ transform: scale(1); } 50%{ transform: scale(1.18); } }
.heartbeat-icon { animation: heartbeat 1.2s infinite; }
</style>
</head>
<body class="bg-slate-950 text-white min-h-screen antialiased">

{{-- TOAST NOTIFICATION CONTAINER --}}
<div id="toastNotification" class="fixed top-5 right-5 z-50 transform translate-y-[-100px] opacity-0 transition-all duration-300 pointer-events-none bg-slate-900 border border-emerald-500/50 text-white px-5 py-3.5 rounded-2xl shadow-2xl flex items-center gap-3">
  <div class="w-8 h-8 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
  </div>
  <div>
    <div id="toastTitle" class="text-xs font-bold text-white">Action Completed</div>
    <div id="toastMessage" class="text-[11px] text-slate-300">Your change has been synchronized.</div>
  </div>
</div>

{{-- ==================== LOGIN SCREEN ==================== --}}
<div id="loginScreen" class="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-950 via-blue-950/30 to-slate-950 p-4">
  <div class="w-full max-w-md">

    {{-- Logo --}}
    <div class="text-center mb-8">
      <div class="w-16 h-16 rounded-2xl bg-blue-600 flex items-center justify-center mx-auto mb-4 shadow-2xl shadow-blue-600/40">
        <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
        </svg>
      </div>
      <h1 class="text-2xl font-black text-white">CarelioEMR <span class="text-blue-400">Cloud</span></h1>
      <p class="text-slate-400 text-sm mt-1 font-medium">Electronic Medical Records — Tenant Workstation</p>
      <div class="flex items-center justify-center gap-2 mt-2">
        <span class="w-2 h-2 rounded-full bg-emerald-400 pulse-dot"></span>
        <span class="text-xs text-emerald-400 font-semibold">Tenant: <span class="font-mono">{{ $tenant_slug }}</span></span>
      </div>
    </div>

    {{-- Login Card --}}
    <div class="bg-slate-900 border border-slate-700/60 rounded-2xl p-8 shadow-2xl">
      <div class="mb-6">
        <h2 class="text-lg font-bold text-white">Sign In to Workstation</h2>
        <p class="text-slate-400 text-xs mt-1">Use your assigned clinic credentials below</p>
      </div>

      {{-- Pre-filled credentials hint --}}
      <div class="bg-blue-950/60 border border-blue-800/60 rounded-xl p-3.5 mb-5 text-xs">
        <div class="text-blue-300 font-bold mb-1.5 flex items-center gap-1.5">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          Your Assigned Credentials:
        </div>
        <div class="text-slate-300 space-y-0.5 font-mono">
          <div>Username: <strong class="text-white">{{ Str::slug($subscription->doctor_name ?? 'dr_subscriber', '_') }}</strong></div>
          <div>Password: <strong class="text-amber-300">ClinicPass123!</strong></div>
        </div>
      </div>

      <div id="loginError" class="hidden bg-rose-950/60 border border-rose-700/60 rounded-xl p-3 mb-4 text-rose-300 text-xs font-semibold items-center gap-2">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span>Invalid credentials. Please enter valid doctor credentials.</span>
      </div>

      <div class="space-y-4">
        <div>
          <label class="text-xs font-semibold text-slate-400 block mb-1.5">Username</label>
          <input id="loginUser" type="text" value="{{ Str::slug($subscription->doctor_name ?? 'dr_subscriber', '_') }}"
            class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-sm text-white font-mono focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500/50">
        </div>
        <div>
          <label class="text-xs font-semibold text-slate-400 block mb-1.5">Password</label>
          <input id="loginPass" type="password" value="ClinicPass123!"
            class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-sm text-white focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500/50">
        </div>
        <button onclick="doLogin()" class="w-full py-3.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-sm transition-all shadow-lg shadow-blue-600/30 flex items-center justify-center gap-2 cursor-pointer">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14"/></svg>
          Sign In to EMR Workstation
        </button>
      </div>

      <div class="mt-5 pt-4 border-t border-slate-800 flex items-center justify-between text-xs text-slate-500">
        <span class="flex items-center gap-1">
          <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
          256-bit TLS Encrypted
        </span>
        <span>DB: <span class="font-mono text-slate-400">{{ $subscription->openemr_database ?? 'carelio_tenant' }}</span></span>
      </div>
    </div>

    <div class="text-center mt-4 flex items-center justify-center gap-4 text-xs text-slate-500">
      <a href="{{ url('/') }}" class="hover:text-slate-300 transition-colors">← SaaS Home</a>
      <span>•</span>
      <a href="{{ url('/admin/dashboard') }}" class="hover:text-slate-300 transition-colors">Admin Portal</a>
    </div>
  </div>
</div>

{{-- ==================== EMR DASHBOARD ==================== --}}
<div id="emrDashboard" class="hidden min-h-screen flex flex-col">

  {{-- Top Navbar --}}
  <nav class="bg-slate-900 border-b border-slate-800 px-4 py-2.5 flex items-center justify-between shrink-0 z-10">
    <div class="flex items-center gap-3">
      <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center">
        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
      </div>
      <div>
        <span class="font-black text-white text-sm">Carelio<span class="text-blue-400">EMR</span></span>
        <span class="ml-2 text-[10px] bg-blue-900/60 text-blue-300 border border-blue-700/50 px-1.5 py-0.5 rounded font-bold uppercase">Workstation v8.3.0</span>
      </div>

      {{-- Search bar --}}
      <div class="hidden md:flex items-center gap-1.5 ml-4 bg-slate-800 rounded-lg px-3 py-1.5 border border-slate-700/60">
        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <input placeholder="Search patients, ICD-10, medications..." class="bg-transparent text-xs text-slate-300 placeholder-slate-500 outline-none w-52">
      </div>
    </div>

    <div class="flex items-center gap-3">
      {{-- Direct Link to Real CarelioEMR Engine --}}
      <a href="http://localhost:8001/interface/login/login.php?site={{ $tenant_slug }}" target="_blank" rel="noopener noreferrer"
        class="hidden sm:flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600/20 hover:bg-emerald-600/30 text-emerald-400 border border-emerald-500/30 rounded-xl text-xs font-bold transition-all"
        title="Open raw CarelioEMR instance on port 8001">
        <span class="w-2 h-2 rounded-full bg-emerald-400 pulse-dot"></span>
        <span>CarelioEMR Engine</span>
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
      </a>

      {{-- Doctor Profile --}}
      <div class="flex items-center gap-2 bg-slate-800 rounded-xl px-3 py-1.5 border border-slate-700/60">
        <div class="w-7 h-7 rounded-full bg-blue-600 flex items-center justify-center text-xs font-black">
          {{ strtoupper(substr($subscription->doctor_name ?? 'D', 0, 1)) }}
        </div>
        <div class="hidden sm:block text-left">
          <div class="text-xs font-bold text-white">{{ $subscription->doctor_name ?? 'Dr. Subscriber' }}</div>
          <div class="text-[10px] text-slate-400">{{ $subscription->practice_type ?? 'Practice Manager' }}</div>
        </div>
      </div>

      <button onclick="doLogout()" class="text-xs text-slate-400 hover:text-rose-400 transition-colors px-2.5 py-1.5 hover:bg-slate-800 rounded-lg cursor-pointer">
        Sign Out
      </button>
    </div>
  </nav>

  {{-- Main Layout --}}
  <div class="flex flex-1 overflow-hidden">

    {{-- Sidebar --}}
    <aside class="w-60 bg-slate-900 border-r border-slate-800 flex flex-col shrink-0 overflow-y-auto">
      <div class="p-3 space-y-1">
        <div class="px-3 py-1 text-[10px] uppercase font-extrabold tracking-widest text-slate-500 mb-1 mt-1">Workstation Navigation</div>

        <button onclick="showTab('dashboard')" class="sidebar-item active w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold text-white" id="tab-dashboard">
          <svg class="w-4 h-4 text-blue-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
          Dashboard
        </button>

        {{-- Demo EMR: Patient Chart / SOAP Notes --}}
        <button onclick="showTab('chart')" class="sidebar-item w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold text-slate-400" id="tab-chart">
          <svg class="w-4 h-4 text-blue-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
          Patient Chart (SOAP)
          <span class="ml-auto bg-blue-600/30 text-blue-300 text-[10px] px-1.5 py-0.2 rounded font-bold">Demo EMR</span>
        </button>

        <button onclick="showTab('patients')" class="sidebar-item w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold text-slate-400" id="tab-patients">
          <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
          Patient Registry
          <span id="badge-patient-count" class="ml-auto bg-blue-600 text-white text-[10px] px-1.5 py-0.5 rounded-full font-bold">24</span>
        </button>

        <button onclick="showTab('appointments')" class="sidebar-item w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold text-slate-400" id="tab-appointments">
          <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
          Appointments
          <span class="ml-auto bg-emerald-600 text-white text-[10px] px-1.5 py-0.5 rounded-full font-bold">6</span>
        </button>

        {{-- Demo EMR: e-Prescriptions --}}
        <button onclick="showTab('prescriptions')" class="sidebar-item w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold text-slate-400" id="tab-prescriptions">
          <svg class="w-4 h-4 text-purple-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
          e-Prescriptions
        </button>

        {{-- Demo EMR: Vitals Stream --}}
        <button onclick="showTab('vitals')" class="sidebar-item w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold text-slate-400" id="tab-vitals">
          <svg class="w-4 h-4 text-rose-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
          Live Vitals Stream
          <span class="ml-auto w-2 h-2 rounded-full bg-emerald-400 pulse-dot"></span>
        </button>

        {{-- Demo EMR: Hospital Beds & ADT --}}
        <button onclick="showTab('hospital')" class="sidebar-item w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold text-slate-400" id="tab-hospital">
          <svg class="w-4 h-4 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
          Hospital Beds &amp; ADT
        </button>

        <button onclick="showTab('labs')" class="sidebar-item w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold text-slate-400" id="tab-labs">
          <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
          Lab Results
        </button>

        <div class="px-3 py-1 text-[10px] uppercase font-extrabold tracking-widest text-slate-500 mb-1 mt-3">Administration</div>
        <button onclick="showTab('billing')" class="sidebar-item w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold text-slate-400" id="tab-billing">
          <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          Insurance &amp; Billing
        </button>

        <button onclick="showTab('reports')" class="sidebar-item w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold text-slate-400" id="tab-reports">
          <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
          Clinical Reports
        </button>
      </div>

      {{-- Tenant Database Card --}}
      <div class="mt-auto p-3">
        <div class="p-3 rounded-xl bg-slate-800/60 border border-slate-700/50 text-[10px] space-y-1.5">
          <div class="text-slate-400 font-semibold">Tenant Database</div>
          <div class="font-mono text-emerald-400 truncate">{{ $subscription->openemr_database ?? 'carelio_tenant' }}</div>
          <div class="flex items-center gap-1 text-emerald-400 font-semibold">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 pulse-dot"></span>
            <span>MySQL 9.6 • Port 3307</span>
          </div>
        </div>
      </div>
    </aside>

    {{-- Content Area --}}
    <main class="flex-1 overflow-y-auto bg-slate-950 p-6">

      {{-- ==================== 1. DASHBOARD TAB ==================== --}}
      <div id="content-dashboard" class="fade-in space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div>
            <h2 class="text-xl font-black text-white">Good Morning, {{ $subscription->doctor_name ?? 'Doctor' }} 👋</h2>
            <p class="text-slate-400 text-xs mt-0.5">{{ now()->format('l, F j, Y') }} — {{ $subscription->practice_type ?? 'General Practice' }} ({{ $subscription->region ?? 'Global' }})</p>
          </div>
          <div class="flex items-center gap-2">
            <button onclick="openNewPatientModal()" class="px-3.5 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer shadow-md shadow-blue-600/30">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
              New Patient
            </button>
            <button onclick="showTab('chart')" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-white rounded-xl text-xs font-bold border border-slate-700 transition-all flex items-center gap-1.5 cursor-pointer">
              <span>Open SOAP Chart</span> →
            </button>
          </div>
        </div>

        {{-- Stats Grid --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
          <div class="stat-card rounded-2xl p-4 border border-slate-800">
            <div class="text-slate-400 text-xs font-semibold mb-1">Today's Appointments</div>
            <div class="text-3xl font-black text-white">6</div>
            <div class="text-xs text-emerald-400 font-semibold mt-1">↑ 2 from yesterday</div>
          </div>
          <div class="stat-card rounded-2xl p-4 border border-slate-800">
            <div class="text-slate-400 text-xs font-semibold mb-1">Total Active Patients</div>
            <div id="stat-patient-count" class="text-3xl font-black text-white">24</div>
            <div class="text-xs text-blue-400 font-semibold mt-1">3 new this week</div>
          </div>
          <div class="stat-card rounded-2xl p-4 border border-slate-800">
            <div class="text-slate-400 text-xs font-semibold mb-1">Pending Lab Results</div>
            <div class="text-3xl font-black text-amber-400">4</div>
            <div class="text-xs text-amber-400 font-semibold mt-1">Review required</div>
          </div>
          <div class="stat-card rounded-2xl p-4 border border-slate-800">
            <div class="text-slate-400 text-xs font-semibold mb-1">Monthly Billing</div>
            <div class="text-3xl font-black text-emerald-400">$4,820</div>
            <div class="text-xs text-emerald-400 font-semibold mt-1">Plan: $80/month Active</div>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          {{-- Today's Schedule --}}
          <div class="lg:col-span-2 bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between mb-4">
              <h3 class="font-bold text-white text-sm flex items-center gap-2">
                <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                Today's Consultations
              </h3>
              <span class="text-xs text-slate-400">{{ now()->format('M j, Y') }}</span>
            </div>
            <div class="space-y-3">
              @php
                $appointments = [
                  ['time'=>'09:00 AM','name'=>'Maria Perez','age'=>34,'type'=>'General Checkup — Cold Congestion','status'=>'In Progress','color'=>'blue'],
                  ['time'=>'10:30 AM','name'=>'Robert Clarke','age'=>67,'type'=>'Follow-up — Hypertension','status'=>'Waiting','color'=>'amber'],
                  ['time'=>'11:15 AM','name'=>'Amelia Davis','age'=>28,'type'=>'Prenatal Visit (Week 24)','status'=>'Scheduled','color'=>'slate'],
                  ['time'=>'02:00 PM','name'=>'James Wilson','age'=>45,'type'=>'Diabetic Consultation','status'=>'Scheduled','color'=>'slate'],
                  ['time'=>'03:30 PM','name'=>'Sophie Martin','age'=>52,'type'=>'Cardiology Review','status'=>'Scheduled','color'=>'slate'],
                ];
              @endphp
              @foreach($appointments as $appt)
              <div onclick="selectPatientForChart('{{ $appt['name'] }}', '{{ $appt['age'] }}', '{{ $appt['type'] }}')" class="flex items-center gap-3 p-3.5 rounded-xl bg-slate-800/50 hover:bg-slate-800 transition-all cursor-pointer border border-transparent hover:border-slate-700">
                <div class="text-xs font-mono text-slate-400 w-20 shrink-0">{{ $appt['time'] }}</div>
                <div class="w-8 h-8 rounded-full bg-blue-600/20 text-blue-400 flex items-center justify-center text-xs font-black shrink-0">
                  {{ strtoupper(substr($appt['name'], 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                  <div class="text-xs font-bold text-white">{{ $appt['name'] }}, {{ $appt['age'] }}y</div>
                  <div class="text-[11px] text-slate-400 truncate">{{ $appt['type'] }}</div>
                </div>
                <span class="text-[10px] font-bold px-2 py-1 rounded-full shrink-0
                  {{ $appt['color'] === 'emerald' ? 'bg-emerald-950 text-emerald-400' : '' }}
                  {{ $appt['color'] === 'blue' ? 'bg-blue-950 text-blue-400' : '' }}
                  {{ $appt['color'] === 'amber' ? 'bg-amber-950 text-amber-400' : '' }}
                  {{ $appt['color'] === 'slate' ? 'bg-slate-700 text-slate-300' : '' }}">
                  {{ $appt['status'] }}
                </span>
              </div>
              @endforeach
            </div>
          </div>

          {{-- Right Panel: Alerts & Quick Tools --}}
          <div class="space-y-5">
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-sm">
              <h3 class="font-bold text-white text-sm mb-3.5 flex items-center gap-2">
                <span>⚠️ Clinical Alerts</span>
                <span class="bg-rose-500/20 text-rose-400 text-[10px] font-extrabold px-2 py-0.5 rounded-full">3 Active</span>
              </h3>
              <div class="space-y-2.5">
                <div class="p-3 bg-rose-950/40 border border-rose-800/50 rounded-xl text-xs">
                  <div class="font-bold text-rose-400">High Blood Pressure Alert</div>
                  <div class="text-slate-400 mt-0.5">Robert Clarke — 168/95 mmHg (Critical Stage II)</div>
                </div>
                <div class="p-3 bg-amber-950/40 border border-amber-800/50 rounded-xl text-xs">
                  <div class="font-bold text-amber-400">Penicillin Allergy Detected</div>
                  <div class="text-slate-400 mt-0.5">Maria Perez — Flagged in e-Prescription check</div>
                </div>
                <div class="p-3 bg-blue-950/40 border border-blue-800/50 rounded-xl text-xs">
                  <div class="font-bold text-blue-400">Pending CBC Lab Panel</div>
                  <div class="text-slate-400 mt-0.5">James Wilson — Fasting Glucose results ready</div>
                </div>
              </div>
            </div>

            <div class="bg-gradient-to-br from-blue-950/40 to-slate-900 border border-blue-800/40 rounded-2xl p-5 shadow-sm">
              <h3 class="font-bold text-white text-sm mb-2">🚀 Demo EMR Quick Actions</h3>
              <p class="text-xs text-slate-400 mb-4 leading-relaxed">Work directly with live SOAP charting, e-Prescriptions, and device vitals stream.</p>
              <div class="grid grid-cols-2 gap-2 text-xs">
                <button onclick="showTab('chart')" class="p-2.5 bg-blue-600/20 hover:bg-blue-600/30 text-blue-300 rounded-xl font-bold border border-blue-500/30 text-left transition-all">
                  🩺 SOAP Chart
                </button>
                <button onclick="showTab('prescriptions')" class="p-2.5 bg-purple-600/20 hover:bg-purple-600/30 text-purple-300 rounded-xl font-bold border border-purple-500/30 text-left transition-all">
                  💊 e-Rx Dispatch
                </button>
                <button onclick="showTab('vitals')" class="p-2.5 bg-rose-600/20 hover:bg-rose-600/30 text-rose-300 rounded-xl font-bold border border-rose-500/30 text-left transition-all">
                  📊 Vitals Stream
                </button>
                <button onclick="showTab('hospital')" class="p-2.5 bg-amber-600/20 hover:bg-amber-600/30 text-amber-300 rounded-xl font-bold border border-amber-500/30 text-left transition-all">
                  🏥 Bed Tracker
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- ==================== 2. DEMO EMR: PATIENT CHART (SOAP NOTES) ==================== --}}
      <div id="content-chart" class="hidden fade-in space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-500/10 border border-blue-500/30 text-blue-400 text-xs font-bold uppercase mb-2">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
              Interactive Clinical Workstation
            </div>
            <h2 class="text-xl font-black text-white">Patient Clinical Chart &amp; SOAP Documentation</h2>
          </div>
          <div class="flex items-center gap-2">
            <button onclick="saveSoapNote()" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-xs font-bold transition-all shadow-md flex items-center gap-1.5 cursor-pointer">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
              Save &amp; Sign Note
            </button>
            <button onclick="dispatchSoapToPrescription()" class="px-4 py-2 bg-purple-600 hover:bg-purple-500 text-white rounded-xl text-xs font-bold transition-all shadow-md flex items-center gap-1.5 cursor-pointer">
              <span>Send to e-Rx</span> →
            </button>
          </div>
        </div>

        {{-- Patient Profile Header Card --}}
        <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 shadow-sm">
          <div class="flex items-center space-x-4">
            <div id="chart-avatar" class="w-14 h-14 rounded-2xl bg-blue-600 text-white font-black flex items-center justify-center text-xl shadow-md shadow-blue-600/30">
              MP
            </div>
            <div>
              <div class="flex items-center space-x-2">
                <h4 id="chart-patient-name" class="text-xl font-black text-white">Maria Perez</h4>
                <span id="chart-patient-id" class="text-xs text-slate-400 font-mono">#EMR-89240</span>
                <span class="px-2.5 py-0.5 text-[10px] bg-emerald-950 text-emerald-400 font-extrabold rounded-md border border-emerald-800">
                  Active Consultation
                </span>
              </div>
              <p id="chart-patient-details" class="text-xs text-slate-400 mt-1">Female • 34 yrs • DOB: 14-May-1992 • Blood: O+ positive</p>
            </div>
          </div>

          <div class="flex flex-wrap gap-2 text-xs font-semibold">
            <span class="px-3 py-1.5 rounded-xl bg-rose-950/60 text-rose-300 border border-rose-800/60 flex items-center gap-1">
              ⚠️ Allergies: Penicillin
            </span>
            <span class="px-3 py-1.5 rounded-xl bg-blue-950/60 text-blue-300 border border-blue-800/60">
              Ins: BlueCross #9012
            </span>
          </div>
        </div>

        {{-- SOAP Note Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
          {{-- Subjective --}}
          <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 shadow-sm space-y-2">
            <div class="flex items-center justify-between">
              <span class="text-xs font-black text-blue-400 uppercase tracking-wider">Subjective (S)</span>
              <span class="text-[10px] text-slate-500">Patient Chief Complaint</span>
            </div>
            <textarea id="soap-s" rows="4" class="w-full bg-slate-800/80 border border-slate-700/80 rounded-xl p-3 text-xs text-slate-200 leading-relaxed font-medium focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500/50">Patient presents with mild respiratory congestion and throat irritation for 3 days. Denies fever, chest pain, or shortness of breath. No previous history of asthma.</textarea>
          </div>

          {{-- Objective --}}
          <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 shadow-sm space-y-2">
            <div class="flex items-center justify-between">
              <span class="text-xs font-black text-cyan-400 uppercase tracking-wider">Objective (O)</span>
              <span class="text-[10px] text-emerald-400 font-bold flex items-center gap-1">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 pulse-dot"></span>
                Device Synchronized
              </span>
            </div>
            <textarea id="soap-o" rows="4" class="w-full bg-slate-800/80 border border-slate-700/80 rounded-xl p-3 text-xs text-slate-200 leading-relaxed font-mono font-semibold focus:border-cyan-500 focus:outline-none focus:ring-1 focus:ring-cyan-500/50">BP: 120/80 mmHg | Heart Rate: 72 bpm | Temp: 98.6 °F | SpO2: 99%
HEENT: Mild erythema of posterior pharynx. TMs clear bilaterally.
Lungs: Clear to auscultation bilaterally. No wheezing or rales.</textarea>
          </div>

          {{-- Assessment --}}
          <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 shadow-sm space-y-2">
            <div class="flex items-center justify-between">
              <span class="text-xs font-black text-emerald-400 uppercase tracking-wider">Assessment (A)</span>
              <span class="text-[10px] text-slate-500">Diagnostic Coding</span>
            </div>
            <input id="soap-a" type="text" value="ICD-10 Code: J00 - Acute Nasopharyngitis (Common Cold)" class="w-full bg-slate-800/80 border border-slate-700/80 rounded-xl p-3 text-xs text-slate-100 font-bold focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500/50">
            <div class="text-[11px] text-slate-400 mt-1">Secondary: None flagged. Patient allergy profile stable.</div>
          </div>

          {{-- Plan --}}
          <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 shadow-sm space-y-2">
            <div class="flex items-center justify-between">
              <span class="text-xs font-black text-amber-400 uppercase tracking-wider">Plan (P)</span>
              <span class="text-[10px] text-slate-500">Treatment &amp; Orders</span>
            </div>
            <textarea id="soap-p" rows="3" class="w-full bg-slate-800/80 border border-slate-700/80 rounded-xl p-3 text-xs text-slate-200 leading-relaxed font-medium focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500/50">1. Rest, hydration, and saline nasal spray.
2. Cetirizine 10mg once daily x 5 days for congestion.
3. Patient advised to return if symptoms worsen or fever develops.</textarea>
          </div>
        </div>
      </div>

      {{-- ==================== 3. DEMO EMR: DIGITAL e-PRESCRIPTIONS ==================== --}}
      <div id="content-prescriptions" class="hidden fade-in space-y-6">
        <div class="flex items-center justify-between">
          <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-purple-500/10 border border-purple-500/30 text-purple-400 text-xs font-bold uppercase mb-2">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
              Surescripts Certified Module
            </div>
            <h2 class="text-xl font-black text-white">Digital e-Prescription Generator &amp; Pharmacy Network</h2>
          </div>
          <span class="text-xs text-emerald-400 font-extrabold bg-emerald-950 px-3 py-1.5 rounded-full border border-emerald-800 flex items-center gap-1.5">
            <span class="w-2 h-2 rounded-full bg-emerald-400 pulse-dot"></span>
            Pharmacy Network Active
          </span>
        </div>

        {{-- Prescription Form Card (Demo EMR feature) --}}
        <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 shadow-sm space-y-5">
          <h3 class="text-sm font-bold text-white flex items-center gap-2">
            <span>💊 Generate New Electronic Prescription</span>
          </h3>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
            <div>
              <label class="text-slate-400 font-semibold block mb-1.5">Selected Patient</label>
              <select id="rx-patient" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3.5 py-2.5 text-white font-bold focus:outline-none focus:border-blue-500">
                <option value="Maria Perez">Maria Perez (Allergy: Penicillin)</option>
                <option value="Robert Clarke">Robert Clarke (Hypertension)</option>
                <option value="James Wilson">James Wilson (Diabetes)</option>
                <option value="Amelia Davis">Amelia Davis (Prenatal)</option>
              </select>
            </div>
            <div>
              <label class="text-slate-400 font-semibold block mb-1.5">Selected Medication</label>
              <input id="rx-med" type="text" value="Amoxicillin 500mg Oral Capsule" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3.5 py-2.5 text-white font-bold focus:outline-none focus:border-blue-500" />
            </div>
            <div>
              <label class="text-slate-400 font-semibold block mb-1.5">Dosage &amp; Instructions</label>
              <input id="rx-dosage" type="text" value="1 Capsule every 8 hours x 7 days" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3.5 py-2.5 text-white font-bold focus:outline-none focus:border-blue-500" />
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
            <div>
              <label class="text-slate-400 font-semibold block mb-1.5">Dispensing Pharmacy</label>
              <input id="rx-pharmacy" type="text" value="CityCare Pharmacy #104 (124 Elm St)" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3.5 py-2.5 text-white font-bold focus:outline-none focus:border-blue-500" />
            </div>
            <div>
              <label class="text-slate-400 font-semibold block mb-1.5">Prescribing Physician</label>
              <input type="text" readonly value="{{ $subscription->doctor_name ?? 'Dr. Subscriber' }} (NPI: #18920491)" class="w-full bg-slate-800/60 border border-slate-700/60 rounded-xl px-3.5 py-2.5 text-slate-300 font-bold" />
            </div>
          </div>

          {{-- Drug Interaction Banner --}}
          <div class="p-4 rounded-xl bg-blue-950/60 border border-blue-800/60 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
            <span class="text-blue-200 font-semibold flex items-center gap-2">
              <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
              <span>Automated Drug Interaction Check: Passed safety cross-check with active allergies.</span>
            </span>
            <button onclick="dispatchErx()" id="btn-dispatch-erx" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-500 text-white font-black rounded-xl transition-all flex items-center gap-2 cursor-pointer shadow-lg shadow-blue-600/30 whitespace-nowrap">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
              Dispatch e-Rx
            </button>
          </div>
        </div>

        {{-- Active Prescriptions Table --}}
        <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-sm">
          <div class="p-4 border-b border-slate-800 flex items-center justify-between">
            <h3 class="font-bold text-white text-sm">Active Patient Prescriptions Log</h3>
            <span class="text-xs text-slate-400">Total: 7 Records</span>
          </div>
          <table class="w-full text-xs">
            <thead><tr class="border-b border-slate-800 bg-slate-800/50">
              <th class="px-4 py-3 text-left text-slate-400 font-bold">Patient</th>
              <th class="px-4 py-3 text-left text-slate-400 font-bold">Medication</th>
              <th class="px-4 py-3 text-left text-slate-400 font-bold">Dosage</th>
              <th class="px-4 py-3 text-left text-slate-400 font-bold">Frequency</th>
              <th class="px-4 py-3 text-left text-slate-400 font-bold">Refills</th>
              <th class="px-4 py-3 text-left text-slate-400 font-bold">Status</th>
            </tr></thead>
            <tbody id="rx-table-body">
              <tr class="border-b border-slate-800/50 hover:bg-slate-800/30 transition-all">
                <td class="px-4 py-3 font-semibold text-white">James Wilson</td>
                <td class="px-4 py-3 text-slate-300">Metformin 500mg</td>
                <td class="px-4 py-3 text-slate-400">500mg</td>
                <td class="px-4 py-3 text-slate-400">Twice daily</td>
                <td class="px-4 py-3 text-slate-400">2 remaining</td>
                <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-950 text-emerald-400">Active</span></td>
              </tr>
              <tr class="border-b border-slate-800/50 hover:bg-slate-800/30 transition-all">
                <td class="px-4 py-3 font-semibold text-white">Robert Clarke</td>
                <td class="px-4 py-3 text-slate-300">Lisinopril 10mg</td>
                <td class="px-4 py-3 text-slate-400">10mg</td>
                <td class="px-4 py-3 text-slate-400">Once daily</td>
                <td class="px-4 py-3 text-slate-400">1 remaining</td>
                <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-950 text-amber-400">Refill Due</span></td>
              </tr>
              <tr class="border-b border-slate-800/50 hover:bg-slate-800/30 transition-all">
                <td class="px-4 py-3 font-semibold text-white">Maria Perez</td>
                <td class="px-4 py-3 text-slate-300">Cetirizine 10mg</td>
                <td class="px-4 py-3 text-slate-400">10mg</td>
                <td class="px-4 py-3 text-slate-400">Once daily x 5d</td>
                <td class="px-4 py-3 text-slate-400">0 remaining</td>
                <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-950 text-emerald-400">Active</span></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      {{-- ==================== 4. DEMO EMR: LIVE VITALS STREAM ==================== --}}
      <div id="content-vitals" class="hidden fade-in space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs font-bold uppercase mb-2">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
              Medical IoT Telemetry
            </div>
            <h2 class="text-xl font-black text-white">Real-time Patient Vital Signs Ingestion</h2>
          </div>
          <div class="flex items-center gap-2">
            <span class="text-xs text-blue-300 font-bold bg-blue-950/60 px-3.5 py-1.5 rounded-full border border-blue-800/60 flex items-center gap-1.5">
              <span class="w-2 h-2 rounded-full bg-blue-400 pulse-dot"></span>
              Live Device Sync
            </span>
            <button onclick="toggleVitalsSimulation()" id="btn-vitals-sim" class="px-3.5 py-1.5 bg-slate-800 hover:bg-slate-700 text-xs font-bold text-white rounded-xl border border-slate-700 transition-all cursor-pointer">
              Simulate Live Ingestion
            </button>
          </div>
        </div>

        {{-- Real-time Ingestion Cards (Demo EMR Feature) --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
          <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 flex items-center space-x-4 shadow-sm">
            <div class="p-4 rounded-2xl bg-rose-950/60 text-rose-500 border border-rose-800/60">
              <svg class="w-8 h-8 heartbeat-icon" fill="currentColor" viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
            </div>
            <div>
              <div class="text-xs text-slate-400 font-semibold">Heart Rate</div>
              <div class="text-3xl font-black text-white mt-0.5"><span id="vitals-hr">72</span> <span class="text-xs text-slate-400 font-normal">bpm</span></div>
              <div class="text-[10px] text-emerald-400 font-extrabold mt-1 flex items-center gap-1">
                <span>●</span> Sinus Rhythm
              </div>
            </div>
          </div>

          <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 flex items-center space-x-4 shadow-sm">
            <div class="p-4 rounded-2xl bg-blue-950/60 text-blue-400 border border-blue-800/60">
              <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            </div>
            <div>
              <div class="text-xs text-slate-400 font-semibold">Blood Pressure</div>
              <div class="text-3xl font-black text-white mt-0.5"><span id="vitals-bp">120/80</span> <span class="text-xs text-slate-400 font-normal">mmHg</span></div>
              <div class="text-[10px] text-blue-400 font-extrabold mt-1">Normal Reading</div>
            </div>
          </div>

          <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 flex items-center space-x-4 shadow-sm">
            <div class="p-4 rounded-2xl bg-amber-950/60 text-amber-400 border border-amber-800/60">
              <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            </div>
            <div>
              <div class="text-xs text-slate-400 font-semibold">Body Temperature</div>
              <div class="text-3xl font-black text-white mt-0.5"><span id="vitals-temp">98.6</span> <span class="text-xs text-slate-400 font-normal">°F</span></div>
              <div class="text-[10px] text-emerald-400 font-bold mt-1">Afebrile (Normothermic)</div>
            </div>
          </div>
        </div>

        {{-- Patient Vitals Table --}}
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-sm">
          <h3 class="font-bold text-white text-sm mb-3">All Active Patients — Latest Stream Readings</h3>
          <table class="w-full text-xs">
            <thead><tr class="border-b border-slate-800 text-slate-400">
              <th class="pb-3 text-left font-bold">Patient</th>
              <th class="pb-3 text-left font-bold">BP (mmHg)</th>
              <th class="pb-3 text-left font-bold">Heart Rate</th>
              <th class="pb-3 text-left font-bold">Temperature</th>
              <th class="pb-3 text-left font-bold">SpO2</th>
              <th class="pb-3 text-left font-bold">Timestamp</th>
            </tr></thead>
            <tbody class="divide-y divide-slate-800/60">
              <tr><td class="py-3 font-semibold text-white">Maria Perez</td><td class="text-emerald-400 font-bold font-mono">120/80</td><td class="font-mono">72 bpm</td><td class="font-mono">98.6°F</td><td class="font-mono text-emerald-400">99%</td><td class="text-slate-500">Just now</td></tr>
              <tr><td class="py-3 font-semibold text-white">Robert Clarke</td><td class="text-rose-400 font-bold font-mono">168/95</td><td class="font-mono text-amber-400">88 bpm</td><td class="font-mono">99.1°F</td><td class="font-mono text-amber-400">94%</td><td class="text-slate-500">10 mins ago</td></tr>
              <tr><td class="py-3 font-semibold text-white">James Wilson</td><td class="text-slate-300 font-mono">128/82</td><td class="font-mono">74 bpm</td><td class="font-mono">98.4°F</td><td class="font-mono">98%</td><td class="text-slate-500">1 hr ago</td></tr>
              <tr><td class="py-3 font-semibold text-white">Amelia Davis</td><td class="text-slate-300 font-mono">112/72</td><td class="font-mono">82 bpm</td><td class="font-mono">98.2°F</td><td class="font-mono">99%</td><td class="text-slate-500">2 hrs ago</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      {{-- ==================== 5. DEMO EMR: HOSPITAL BEDS & ADT ==================== --}}
      <div id="content-hospital" class="hidden fade-in space-y-6">
        <div class="flex items-center justify-between">
          <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-400 text-xs font-bold uppercase mb-2">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
              Inpatient Management
            </div>
            <h2 class="text-xl font-black text-white">Hospital ADT &amp; Inpatient Bed Tracker</h2>
          </div>
          <span class="text-xs text-blue-300 font-bold bg-blue-950/60 px-3 py-1.5 rounded-full border border-blue-800/60">
            Hospital Module Active
          </span>
        </div>

        {{-- 3 Key ADT Metrics (Demo EMR Feature) --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
          <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 text-center shadow-sm">
            <div class="text-xs text-slate-400 font-semibold">Total Hospital Beds</div>
            <div class="text-3xl font-black text-white mt-1">250 Beds</div>
            <div class="text-[11px] text-blue-400 font-bold mt-1">Main Healthcare Facility</div>
          </div>

          <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 text-center shadow-sm">
            <div class="text-xs text-slate-400 font-semibold">Current Occupancy</div>
            <div class="text-3xl font-black text-emerald-400 mt-1">82% <span class="text-base text-slate-400 font-normal">(205)</span></div>
            <div class="text-[11px] text-slate-400 font-medium mt-1">45 Inpatient Beds Available</div>
          </div>

          <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 text-center shadow-sm">
            <div class="text-xs text-slate-400 font-semibold">ER Triage Queue</div>
            <div class="text-3xl font-black text-amber-400 mt-1">4 Patients</div>
            <div class="text-[11px] text-slate-400 font-medium mt-1">Average Wait: 8 mins</div>
          </div>
        </div>

        {{-- Department Wards Breakdown --}}
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-sm space-y-4">
          <h3 class="font-bold text-white text-sm">Ward Census &amp; Critical Care Capacity</h3>
          <div class="space-y-3">
            <div>
              <div class="flex justify-between text-xs font-semibold mb-1">
                <span class="text-white">Intensive Care Unit (ICU)</span>
                <span class="text-rose-400">19 / 20 Beds (95% Occupied)</span>
              </div>
              <div class="w-full bg-slate-800 rounded-full h-2.5 overflow-hidden">
                <div class="bg-rose-500 h-2.5 rounded-full" style="width: 95%"></div>
              </div>
            </div>

            <div>
              <div class="flex justify-between text-xs font-semibold mb-1">
                <span class="text-white">General Medical Ward</span>
                <span class="text-blue-400">78 / 100 Beds (78% Occupied)</span>
              </div>
              <div class="w-full bg-slate-800 rounded-full h-2.5 overflow-hidden">
                <div class="bg-blue-500 h-2.5 rounded-full" style="width: 78%"></div>
              </div>
            </div>

            <div>
              <div class="flex justify-between text-xs font-semibold mb-1">
                <span class="text-white">Surgical Post-Op Ward</span>
                <span class="text-amber-400">42 / 50 Beds (84% Occupied)</span>
              </div>
              <div class="w-full bg-slate-800 rounded-full h-2.5 overflow-hidden">
                <div class="bg-amber-500 h-2.5 rounded-full" style="width: 84%"></div>
              </div>
            </div>

            <div>
              <div class="flex justify-between text-xs font-semibold mb-1">
                <span class="text-white">Pediatric &amp; Maternity</span>
                <span class="text-emerald-400">26 / 40 Beds (65% Occupied)</span>
              </div>
              <div class="w-full bg-slate-800 rounded-full h-2.5 overflow-hidden">
                <div class="bg-emerald-500 h-2.5 rounded-full" style="width: 65%"></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- ==================== 6. PATIENTS REGISTRY TAB ==================== --}}
      <div id="content-patients" class="hidden fade-in space-y-6">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-xl font-black text-white">Patient Registry &amp; Medical Records</h2>
            <p class="text-slate-400 text-xs mt-0.5">Isolated MySQL Database: {{ $subscription->openemr_database ?? 'carelio_tenant' }}</p>
          </div>
          <button onclick="openNewPatientModal()" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-xs font-bold transition-all flex items-center gap-2 cursor-pointer shadow-md shadow-blue-600/30">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add New Patient
          </button>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-sm">
          <table class="w-full text-xs">
            <thead><tr class="border-b border-slate-800 bg-slate-800/50">
              <th class="px-4 py-3 text-left text-slate-400 font-bold">Patient</th>
              <th class="px-4 py-3 text-left text-slate-400 font-bold">DOB / Age</th>
              <th class="px-4 py-3 text-left text-slate-400 font-bold">Condition</th>
              <th class="px-4 py-3 text-left text-slate-400 font-bold">Last Visit</th>
              <th class="px-4 py-3 text-left text-slate-400 font-bold">Status</th>
              <th class="px-4 py-3 text-left text-slate-400 font-bold">Action</th>
            </tr></thead>
            <tbody id="patient-table-body">
              @php
                $patients = [
                  ['id'=>'P-0024','name'=>'Maria Perez','dob'=>'1992-05-14','age'=>34,'condition'=>'Acute Nasopharyngitis','last'=>'Today','status'=>'Active'],
                  ['id'=>'P-0023','name'=>'Robert Clarke','dob'=>'1957-11-22','age'=>67,'condition'=>'Chronic Heart Failure, BP 168/95','last'=>'Today','status'=>'Critical'],
                  ['id'=>'P-0022','name'=>'Amelia Davis','dob'=>'1996-07-08','age'=>28,'condition'=>'Pregnancy — Week 24','last'=>'Today','status'=>'Active'],
                  ['id'=>'P-0021','name'=>'James Wilson','dob'=>'1979-04-30','age'=>45,'condition'=>'Type 2 Diabetes Mellitus','last'=>'1 week ago','status'=>'Active'],
                  ['id'=>'P-0020','name'=>'Sophie Martin','dob'=>'1972-09-14','age'=>52,'condition'=>'Atrial Fibrillation','last'=>'2 weeks ago','status'=>'Follow-up'],
                  ['id'=>'P-0019','name'=>'Carlos Rivera','dob'=>'1985-12-03','age'=>39,'condition'=>'Asthma, Seasonal Allergy','last'=>'3 weeks ago','status'=>'Active'],
                  ['id'=>'P-0018','name'=>'Emma Thompson','dob'=>'2001-02-17','age'=>23,'condition'=>'Anxiety, Iron Deficiency','last'=>'1 month ago','status'=>'Active'],
                  ['id'=>'P-0017','name'=>'David Okonkwo','dob'=>'1965-06-25','age'=>59,'condition'=>'COPD Stage II','last'=>'1 month ago','status'=>'Chronic'],
                ];
              @endphp
              @foreach($patients as $p)
              <tr class="border-b border-slate-800/50 hover:bg-slate-800/30 transition-all">
                <td class="px-4 py-3">
                  <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-full bg-blue-600/20 text-blue-400 flex items-center justify-center text-xs font-black">{{ strtoupper(substr($p['name'],0,1)) }}</div>
                    <div>
                      <div class="font-bold text-white">{{ $p['name'] }}</div>
                      <div class="text-slate-500 text-[10px] font-mono">{{ $p['id'] }}</div>
                    </div>
                  </div>
                </td>
                <td class="px-4 py-3 text-slate-300">{{ $p['dob'] }} ({{ $p['age'] }}y)</td>
                <td class="px-4 py-3 text-slate-300 max-w-xs truncate">{{ $p['condition'] }}</td>
                <td class="px-4 py-3 text-slate-400">{{ $p['last'] }}</td>
                <td class="px-4 py-3">
                  <span class="px-2 py-0.5 rounded-full text-[10px] font-bold
                    {{ $p['status']==='Critical' ? 'bg-rose-950 text-rose-400' : '' }}
                    {{ $p['status']==='Active' ? 'bg-emerald-950 text-emerald-400' : '' }}
                    {{ $p['status']==='Follow-up' ? 'bg-amber-950 text-amber-400' : '' }}
                    {{ $p['status']==='Chronic' ? 'bg-blue-950 text-blue-400' : '' }}">
                    {{ $p['status'] }}
                  </span>
                </td>
                <td class="px-4 py-3">
                  <button onclick="selectPatientForChart('{{ $p['name'] }}', '{{ $p['age'] }}', '{{ $p['condition'] }}')" class="text-blue-400 hover:text-blue-300 font-semibold cursor-pointer">
                    View Chart →
                  </button>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>

      {{-- ==================== 7. APPOINTMENTS TAB ==================== --}}
      <div id="content-appointments" class="hidden fade-in space-y-6">
        <div class="flex items-center justify-between">
          <h2 class="text-xl font-black text-white">Appointments — {{ now()->format('F Y') }}</h2>
          <span class="text-xs text-slate-400">6 Scheduled Today</span>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          @php
            $appts = [
              ['time'=>'09:00 AM','date'=>'Today','name'=>'Maria Perez','type'=>'General Checkup — Cold Congestion','room'=>'Exam Room 1','status'=>'In Progress'],
              ['time'=>'10:30 AM','date'=>'Today','name'=>'Robert Clarke','type'=>'Follow-up — Hypertension BP','room'=>'Exam Room 2','status'=>'Waiting'],
              ['time'=>'11:15 AM','date'=>'Today','name'=>'Amelia Davis','type'=>'Prenatal Visit (OB)','room'=>'Exam Room 3','status'=>'Scheduled'],
              ['time'=>'02:00 PM','date'=>'Today','name'=>'James Wilson','type'=>'Diabetic Consult','room'=>'Exam Room 1','status'=>'Scheduled'],
              ['time'=>'09:30 AM','date'=>'Tomorrow','name'=>'Emma Thompson','type'=>'Iron Panel Review','room'=>'Exam Room 2','status'=>'Scheduled'],
              ['time'=>'11:00 AM','date'=>'Tomorrow','name'=>'David Okonkwo','type'=>'COPD Follow-up','room'=>'Exam Room 3','status'=>'Scheduled'],
            ];
          @endphp
          @foreach($appts as $a)
          <div onclick="selectPatientForChart('{{ $a['name'] }}', '35', '{{ $a['type'] }}')" class="bg-slate-900 border border-slate-800 rounded-2xl p-4 flex items-start gap-4 hover:border-slate-700 transition-all cursor-pointer">
            <div class="text-center bg-blue-950/60 border border-blue-800/50 rounded-xl px-3 py-2 shrink-0">
              <div class="text-[10px] text-blue-400 font-bold">{{ $a['date'] }}</div>
              <div class="text-sm font-black text-white">{{ $a['time'] }}</div>
            </div>
            <div class="flex-1">
              <div class="font-bold text-white text-sm">{{ $a['name'] }}</div>
              <div class="text-xs text-slate-400 mt-0.5">{{ $a['type'] }}</div>
              <div class="text-[10px] text-slate-500 mt-1">📍 {{ $a['room'] }}</div>
            </div>
            <span class="text-[10px] font-bold px-2 py-1 rounded-full
              {{ $a['status']==='Completed' ? 'bg-emerald-950 text-emerald-400' : '' }}
              {{ $a['status']==='In Progress' ? 'bg-blue-950 text-blue-400' : '' }}
              {{ $a['status']==='Waiting' ? 'bg-amber-950 text-amber-400' : '' }}
              {{ $a['status']==='Scheduled' ? 'bg-slate-700 text-slate-300' : '' }}">
              {{ $a['status'] }}
            </span>
          </div>
          @endforeach
        </div>
      </div>

      {{-- ==================== 8. LABS TAB ==================== --}}
      <div id="content-labs" class="hidden fade-in space-y-6">
        <h2 class="text-xl font-black text-white">Laboratory Orders &amp; Results</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          @php
            $labs = [
              ['patient'=>'Maria Perez','test'=>'Rapid Strep Throat Culture','result'=>'Negative','range'=>'Negative','flag'=>'Normal','date'=>'Today'],
              ['patient'=>'Robert Clarke','test'=>'BNP (Brain Natriuretic Peptide)','result'=>'890 pg/mL','range'=>'< 100 pg/mL','flag'=>'Critical','date'=>'Today'],
              ['patient'=>'James Wilson','test'=>'HbA1c Glycated Hemoglobin','result'=>'7.2%','range'=>'< 7.0%','flag'=>'High','date'=>'Yesterday'],
              ['patient'=>'Emma Thompson','test'=>'Serum Ferritin','result'=>'8 ng/mL','range'=>'12-150 ng/mL','flag'=>'Low','date'=>'Yesterday'],
            ];
          @endphp
          @foreach($labs as $lab)
          <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4">
            <div class="flex items-start justify-between mb-2">
              <div>
                <div class="font-bold text-white text-sm">{{ $lab['patient'] }}</div>
                <div class="text-xs text-slate-400 mt-0.5">{{ $lab['test'] }}</div>
              </div>
              <span class="text-[10px] font-bold px-2 py-1 rounded-full
                {{ $lab['flag']==='Critical' ? 'bg-rose-950 text-rose-400' : '' }}
                {{ $lab['flag']==='High' ? 'bg-amber-950 text-amber-400' : '' }}
                {{ $lab['flag']==='Low' ? 'bg-orange-950 text-orange-400' : '' }}
                {{ $lab['flag']==='Normal' ? 'bg-emerald-950 text-emerald-400' : '' }}">
                {{ $lab['flag'] }}
              </span>
            </div>
            <div class="flex items-center justify-between bg-slate-800/50 rounded-xl p-3 mt-2">
              <div><div class="text-[10px] text-slate-500">Result</div><div class="font-black text-white text-sm">{{ $lab['result'] }}</div></div>
              <div class="text-right"><div class="text-[10px] text-slate-500">Normal Range</div><div class="text-xs text-slate-400">{{ $lab['range'] }}</div></div>
            </div>
          </div>
          @endforeach
        </div>
      </div>

      {{-- ==================== 9. BILLING TAB ==================== --}}
      <div id="content-billing" class="hidden fade-in space-y-6">
        <h2 class="text-xl font-black text-white">Insurance &amp; Billing Ledger</h2>
        <div class="grid grid-cols-3 gap-4">
          <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 text-center">
            <div class="text-2xl font-black text-emerald-400">$4,820</div>
            <div class="text-xs text-slate-400 mt-1">Collected This Month</div>
          </div>
          <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 text-center">
            <div class="text-2xl font-black text-amber-400">$2,340</div>
            <div class="text-xs text-slate-400 mt-1">Pending Claims</div>
          </div>
          <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 text-center">
            <div class="text-2xl font-black text-blue-400">$80.00</div>
            <div class="text-xs text-slate-400 mt-1">CarelioEMR SaaS Monthly</div>
          </div>
        </div>
      </div>

      {{-- ==================== 10. REPORTS TAB ==================== --}}
      <div id="content-reports" class="hidden fade-in space-y-6">
        <h2 class="text-xl font-black text-white">Clinical &amp; Administrative Reports</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          @php $reports = [
            ['title'=>'Patient Demographics','desc'=>'Age, gender, and geographic distribution across tenant','icon'=>'👥'],
            ['title'=>'Diagnosis ICD-10 Analysis','desc'=>'Prevalence of respiratory and cardiac conditions','icon'=>'🩺'],
            ['title'=>'Prescription Compliance','desc'=>'Pharmacy dispatch rates and refill adherences','icon'=>'💊'],
          ]; @endphp
          @foreach($reports as $r)
          <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 hover:border-slate-700 transition-all cursor-pointer">
            <div class="text-2xl mb-2">{{ $r['icon'] }}</div>
            <div class="font-bold text-white text-sm mb-1">{{ $r['title'] }}</div>
            <div class="text-xs text-slate-400">{{ $r['desc'] }}</div>
            <button class="mt-4 text-xs font-bold text-blue-400 hover:text-blue-300">Generate Report →</button>
          </div>
          @endforeach
        </div>
      </div>

    </main>
  </div>
</div>

{{-- ==================== NEW PATIENT MODAL ==================== --}}
<div id="newPatientModal" class="hidden fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-xs flex items-center justify-center p-4">
  <div class="bg-slate-900 border border-slate-700 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
    <div class="flex items-center justify-between pb-3 border-b border-slate-800">
      <h3 class="text-base font-bold text-white">Register New Patient</h3>
      <button onclick="closeNewPatientModal()" class="text-slate-400 hover:text-white text-sm">✕</button>
    </div>
    <div class="space-y-3 text-xs">
      <div>
        <label class="text-slate-400 block mb-1">Full Name</label>
        <input id="np-name" type="text" placeholder="e.g. John Doe" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white" />
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="text-slate-400 block mb-1">Age</label>
          <input id="np-age" type="number" placeholder="42" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white" />
        </div>
        <div>
          <label class="text-slate-400 block mb-1">Gender</label>
          <select id="np-gender" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white">
            <option value="Male">Male</option>
            <option value="Female">Female</option>
            <option value="Other">Other</option>
          </select>
        </div>
      </div>
      <div>
        <label class="text-slate-400 block mb-1">Primary Condition / Symptoms</label>
        <input id="np-condition" type="text" placeholder="e.g. Routine Consultation" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white" />
      </div>
    </div>
    <div class="flex gap-2 pt-2">
      <button onclick="closeNewPatientModal()" class="flex-1 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs">Cancel</button>
      <button onclick="submitNewPatient()" class="flex-1 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs">Save Patient</button>
    </div>
  </div>
</div>

<script>
const VALID_USER = '{{ Str::slug($subscription->doctor_name ?? "dr_subscriber", "_") }}';
const VALID_PASS = 'ClinicPass123!';
const TENANT_STORAGE_KEY = 'carelioemr_logged_in_{{ $tenant_slug }}';

// Check if user was already logged in this session
if (sessionStorage.getItem(TENANT_STORAGE_KEY) === 'true') {
  document.getElementById('loginScreen').classList.add('hidden');
  document.getElementById('emrDashboard').classList.remove('hidden');
}

function showToast(title, message) {
  const t = document.getElementById('toastNotification');
  document.getElementById('toastTitle').textContent = title;
  document.getElementById('toastMessage').textContent = message;
  t.classList.remove('translate-y-[-100px]', 'opacity-0');
  t.classList.add('translate-y-0', 'opacity-100');
  setTimeout(() => {
    t.classList.remove('translate-y-0', 'opacity-100');
    t.classList.add('translate-y-[-100px]', 'opacity-0');
  }, 3500);
}

function doLogin() {
  const u = document.getElementById('loginUser').value.trim();
  const p = document.getElementById('loginPass').value.trim();
  const err = document.getElementById('loginError');

  if (u.length > 0 && p.length > 0) {
    sessionStorage.setItem(TENANT_STORAGE_KEY, 'true');
    err.classList.add('hidden');
    document.getElementById('loginScreen').classList.add('hidden');
    document.getElementById('emrDashboard').classList.remove('hidden');
    showToast('Welcome to CarelioEMR', 'Signed into isolated workstation for {{ $subscription->doctor_name ?? "Doctor" }}');
  } else {
    err.classList.remove('hidden');
  }
}

function doLogout() {
  sessionStorage.removeItem(TENANT_STORAGE_KEY);
  document.getElementById('emrDashboard').classList.add('hidden');
  document.getElementById('loginScreen').classList.remove('hidden');
}

function showTab(name) {
  document.querySelectorAll('[id^="content-"]').forEach(el => {
    el.classList.add('hidden');
    el.classList.remove('fade-in');
  });
  document.querySelectorAll('[id^="tab-"]').forEach(el => {
    el.classList.remove('active');
    el.classList.add('text-slate-400');
    el.classList.remove('text-white');
  });

  const content = document.getElementById('content-' + name);
  if (content) {
    content.classList.remove('hidden');
    setTimeout(() => content.classList.add('fade-in'), 10);
  }
  const tab = document.getElementById('tab-' + name);
  if (tab) {
    tab.classList.add('active', 'text-white');
    tab.classList.remove('text-slate-400');
  }
}

// Select a patient and jump directly to their SOAP Chart
function selectPatientForChart(name, age, condition) {
  document.getElementById('chart-patient-name').textContent = name;
  document.getElementById('chart-patient-details').textContent = 'Patient • ' + age + ' yrs • Record: ' + condition;
  const initials = name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
  document.getElementById('chart-avatar').textContent = initials;
  showTab('chart');
  showToast('Patient Chart Loaded', 'Viewing clinical chart and SOAP note for ' + name);
}

function saveSoapNote() {
  showToast('SOAP Note Signed & Saved', 'Medical encounter record committed to isolated database {{ $subscription->openemr_database ?? "carelio_tenant" }}.');
}

function dispatchSoapToPrescription() {
  showTab('prescriptions');
  showToast('Prescription Prepared', 'SOAP treatment plan converted to e-Prescription.');
}

function dispatchErx() {
  const btn = document.getElementById('btn-dispatch-erx');
  btn.disabled = true;
  btn.innerHTML = '<span>Dispatching...</span>';
  
  setTimeout(() => {
    btn.disabled = false;
    btn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Dispatched';
    showToast('Prescription Dispatched!', 'e-Rx electronically sent to CityCare Pharmacy #104 via Surescripts network.');
    
    // Add row to table
    const tbody = document.getElementById('rx-table-body');
    const tr = document.createElement('tr');
    tr.className = 'border-b border-slate-800/50 hover:bg-slate-800/30 transition-all fade-in bg-blue-950/20';
    tr.innerHTML = `
      <td class="px-4 py-3 font-semibold text-white">${document.getElementById('rx-patient').value}</td>
      <td class="px-4 py-3 text-slate-300">${document.getElementById('rx-med').value}</td>
      <td class="px-4 py-3 text-slate-400">${document.getElementById('rx-dosage').value}</td>
      <td class="px-4 py-3 text-slate-400">Electronic</td>
      <td class="px-4 py-3 text-slate-400">0 remaining</td>
      <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-950 text-blue-400 border border-blue-800">Transmitted</span></td>
    `;
    tbody.prepend(tr);
  }, 900);
}

// Live Vitals simulation toggle
let vitalsInterval = null;
function toggleVitalsSimulation() {
  const btn = document.getElementById('btn-vitals-sim');
  if (vitalsInterval) {
    clearInterval(vitalsInterval);
    vitalsInterval = null;
    btn.textContent = 'Simulate Live Ingestion';
    btn.classList.remove('bg-emerald-600');
    showToast('Simulation Paused', 'Live telemetry stream paused.');
  } else {
    btn.textContent = 'Streaming Live... (Stop)';
    btn.classList.add('bg-emerald-600');
    showToast('Live Ingestion Active', 'Simulating continuous Bluetooth vitals telemetry.');
    vitalsInterval = setInterval(() => {
      const hr = 70 + Math.floor(Math.random() * 8);
      const sys = 118 + Math.floor(Math.random() * 8);
      const dia = 78 + Math.floor(Math.random() * 6);
      const temp = (98.4 + Math.random() * 0.4).toFixed(1);
      document.getElementById('vitals-hr').textContent = hr;
      document.getElementById('vitals-bp').textContent = sys + '/' + dia;
      document.getElementById('vitals-temp').textContent = temp;
    }, 2500);
  }
}

// New Patient Modal functions
function openNewPatientModal() {
  document.getElementById('newPatientModal').classList.remove('hidden');
}
function closeNewPatientModal() {
  document.getElementById('newPatientModal').classList.add('hidden');
}
function submitNewPatient() {
  const name = document.getElementById('np-name').value.trim() || 'New Patient';
  const age = document.getElementById('np-age').value.trim() || '30';
  const condition = document.getElementById('np-condition').value.trim() || 'General Consultation';
  
  const tbody = document.getElementById('patient-table-body');
  const tr = document.createElement('tr');
  tr.className = 'border-b border-slate-800/50 hover:bg-slate-800/30 transition-all fade-in bg-blue-950/20';
  const randomId = 'P-00' + Math.floor(25 + Math.random() * 75);
  tr.innerHTML = `
    <td class="px-4 py-3">
      <div class="flex items-center gap-2">
        <div class="w-7 h-7 rounded-full bg-blue-600/20 text-blue-400 flex items-center justify-center text-xs font-black">${name[0].toUpperCase()}</div>
        <div>
          <div class="font-bold text-white">${name}</div>
          <div class="text-slate-500 text-[10px] font-mono">${randomId}</div>
        </div>
      </div>
    </td>
    <td class="px-4 py-3 text-slate-300">Today (${age}y)</td>
    <td class="px-4 py-3 text-slate-300 max-w-xs truncate">${condition}</td>
    <td class="px-4 py-3 text-slate-400">Today</td>
    <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-950 text-emerald-400">Active</span></td>
    <td class="px-4 py-3"><button onclick="selectPatientForChart('${name}', '${age}', '${condition}')" class="text-blue-400 hover:text-blue-300 font-semibold cursor-pointer">View Chart →</button></td>
  `;
  tbody.prepend(tr);
  
  const cnt = document.getElementById('stat-patient-count');
  if (cnt) cnt.textContent = parseInt(cnt.textContent || '24') + 1;
  const badge = document.getElementById('badge-patient-count');
  if (badge) badge.textContent = parseInt(badge.textContent || '24') + 1;

  closeNewPatientModal();
  showToast('Patient Registered', name + ' successfully added to tenant registry.');
}

// Allow Enter key on login
document.addEventListener('keydown', function(e) {
  if (e.key === 'Enter' && !document.getElementById('loginScreen').classList.contains('hidden')) {
    doLogin();
  }
});
</script>
</body>
</html>
