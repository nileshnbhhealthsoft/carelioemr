@extends('layouts.app')

@section('title', 'Admin Portal Login - CarelioEMR')

@section('content')

<div class="min-h-screen bg-slate-50 flex items-center justify-center p-4">
    
    <div class="w-full max-w-md bg-white border border-slate-200 rounded-3xl p-8 shadow-xl space-y-6 relative overflow-hidden">
        
        <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-blue-600 via-cyan-500 to-emerald-500"></div>

        <div class="text-center space-y-3">
            <div class="w-12 h-12 rounded-2xl bg-blue-600 text-white font-bold flex items-center justify-center mx-auto shadow-md shadow-blue-600/20">
                <i data-lucide="activity" class="w-7 h-7 stroke-[2.5]"></i>
            </div>
            <div>
                <h2 class="text-2xl font-black text-slate-900">CarelioEMR Admin Portal</h2>
                <p class="text-xs text-slate-500 font-semibold mt-0.5">
                    Authorized Management &amp; Subscriber Console
                </p>
            </div>
        </div>

        @if(session('error'))
            <div class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs flex items-center gap-2 font-semibold">
                <i data-lucide="alert-circle" class="w-4 h-4 text-rose-600 shrink-0"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <form action="{{ url('/admin/login') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="text-xs font-bold text-slate-700 block mb-1.5">Admin Email</label>
                <div class="relative">
                    <i data-lucide="mail" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                    <input type="email" name="email" required placeholder="admin@carelioemr.com" class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-10 pr-4 py-2.5 text-xs text-slate-900 font-semibold focus:border-blue-600 focus:outline-none">
                </div>
            </div>

            <div>
                <label class="text-xs font-bold text-slate-700 block mb-1.5">Password</label>
                <div class="relative">
                    <i data-lucide="lock" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                    <input type="password" name="password" required placeholder="••••••••" class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-10 pr-4 py-2.5 text-xs text-slate-900 font-semibold focus:border-blue-600 focus:outline-none">
                </div>
            </div>

            <button type="submit" class="w-full py-3.5 rounded-xl bg-blue-600 text-white font-black text-sm shadow-md shadow-blue-600/25 hover:bg-blue-700 transition-all flex items-center justify-center space-x-2 cursor-pointer">
                <i data-lucide="key" class="w-4 h-4"></i>
                <span>Sign In to Admin Portal</span>
            </button>
        </form>

        <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
            <a href="{{ url('/') }}" class="text-slate-500 hover:text-slate-900 font-semibold flex items-center gap-1 cursor-pointer">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i> Back to Landing Page
            </a>

            <span class="text-slate-400 font-semibold text-[11px]">
                Secure TLS 1.3
            </span>
        </div>

    </div>

</div>

@endsection
