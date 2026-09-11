@extends('layouts.app')

@section('title', 'Admin Dashboard - CarelioEMR Multi-Tenant')

@section('content')

<div class="min-h-screen bg-slate-100 flex flex-col lg:flex-row">
    
    <!-- LEFT SIDEBAR -->
    <aside class="w-72 bg-white border-r border-slate-200 flex flex-col justify-between shrink-0 h-screen sticky top-0 shadow-sm hidden lg:flex">
        
        <div class="p-6 space-y-6 overflow-y-auto">
            
            <a href="{{ url('/') }}" class="flex items-center space-x-3 cursor-pointer">
                <div class="w-10 h-10 rounded-xl bg-blue-600 text-white font-bold flex items-center justify-center shadow-md shadow-blue-600/20 shrink-0">
                    <i data-lucide="activity" class="w-5 h-5 stroke-[2.5]"></i>
                </div>
                <div>
                    <div class="text-lg font-black text-slate-900 leading-none tracking-tight">Carelio<span class="text-blue-600">Admin</span></div>
                    <div class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mt-1">Multi-Tenant CarelioEMR</div>
                </div>
            </a>

            <div class="space-y-1">
                <div class="px-3 text-[10px] uppercase font-extrabold tracking-wider text-slate-400 mb-2">
                    Admin Navigation
                </div>

                <a href="{{ url('/admin/dashboard?tab=subscribers') }}" class="w-full flex items-center justify-between px-4 py-3 rounded-xl font-bold text-xs transition-all {{ request('tab', 'subscribers') == 'subscribers' ? 'bg-blue-600 text-white shadow-md' : 'text-slate-600 hover:bg-slate-50' }}">
                    <div class="flex items-center space-x-3">
                        <i data-lucide="users" class="w-4.5 h-4.5"></i>
                        <span>Tenant Sites</span>
                    </div>
                    <span class="text-[10px] px-2 py-0.5 rounded-full font-extrabold {{ request('tab', 'subscribers') == 'subscribers' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-700' }}">
                        {{ $subscribers->count() }}
                    </span>
                </a>

                <a href="{{ url('/admin/dashboard?tab=payments') }}" class="w-full flex items-center justify-between px-4 py-3 rounded-xl font-bold text-xs transition-all {{ request('tab') == 'payments' ? 'bg-blue-600 text-white shadow-md' : 'text-slate-600 hover:bg-slate-50' }}">
                    <div class="flex items-center space-x-3">
                        <i data-lucide="credit-card" class="w-4.5 h-4.5"></i>
                        <span>Stripe Transactions</span>
                    </div>
                    <span class="text-[10px] px-2 py-0.5 rounded-full font-extrabold {{ request('tab') == 'payments' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-700' }}">
                        ${{ $subscribers->count() * 80 }}
                    </span>
                </a>
            </div>

            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 text-xs space-y-1.5">
                <div class="flex items-center space-x-2 text-blue-700 font-extrabold text-[11px]">
                    <i data-lucide="shield-check" class="w-4 h-4 text-blue-600"></i>
                    <span>CarelioEMR Multi-Tenancy</span>
                </div>
                <p class="text-[11px] text-slate-500 font-medium leading-relaxed">
                    Zero Core Modifications<br>
                    Isolated Tenant DB &amp; Restricted Role
                </p>
            </div>

        </div>

        <div class="p-4 border-t border-slate-200 bg-slate-50/50 space-y-3">
            <div class="flex items-center space-x-3">
                <div class="w-9 h-9 rounded-xl bg-blue-600 text-white font-extrabold flex items-center justify-center text-xs shadow-xs">
                    CA
                </div>
                <div class="overflow-hidden">
                    <div class="text-xs font-bold text-slate-900 truncate">{{ session('admin_user.name', 'CarelioEMR Super Admin') }}</div>
                    <div class="text-[10px] text-slate-500 truncate">{{ session('admin_user.email', 'admin@carelioemr.com') }}</div>
                </div>
            </div>

            <a href="{{ url('/admin/logout') }}" class="w-full py-2.5 px-3 rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 font-bold text-xs flex items-center justify-center space-x-2 transition-all cursor-pointer shadow-2xs">
                <i data-lucide="log-out" class="w-3.5 h-3.5 text-rose-500"></i>
                <span>Log Out Admin</span>
            </a>
        </div>

    </aside>

    <!-- RIGHT MAIN CONTENT -->
    <main class="flex-1 p-4 sm:p-6 lg:p-8 space-y-6 overflow-x-hidden">
        
        @if(session('success'))
            <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold flex items-center justify-between shadow-xs">
                <div class="flex items-center space-x-2">
                    <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                    <span>{{ session('success') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 text-sm font-black">&times;</button>
            </div>
        @endif

        @if(session('error'))
            <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-bold flex items-center justify-between shadow-xs">
                <div class="flex items-center space-x-2">
                    <i data-lucide="alert-triangle" class="w-4 h-4 text-rose-600 shrink-0"></i>
                    <span>{{ session('error') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700 text-sm font-black">&times;</button>
            </div>
        @endif
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
            <div>
                <div class="flex items-center space-x-3">
                    <h1 class="text-2xl font-black text-slate-900">
                        CarelioEMR Multi-Tenant Sites &amp; Provisioning Log
                    </h1>
                    <span class="px-3 py-1 rounded-full text-xs font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center gap-1.5">
                        <i data-lucide="shield-check" class="w-3.5 h-3.5 text-emerald-600"></i> Auto-Provisioner Active
                    </span>
                </div>
                <p class="text-xs text-slate-500 font-semibold mt-1">
                    Isolated databases, site folders, and restricted Clinic Admin roles created automatically upon payment.
                </p>
            </div>

            <div class="flex items-center space-x-3">
                <a href="{{ url('/admin/dashboard') }}" class="px-4 py-2.5 rounded-xl bg-slate-100 text-slate-800 font-bold text-xs flex items-center space-x-2">
                    <i data-lucide="refresh-cw" class="w-4 h-4 text-blue-600"></i>
                    <span>Refresh Records</span>
                </a>
                <a href="{{ url('/admin/logout') }}" class="px-4 py-2.5 rounded-xl bg-rose-50 text-rose-700 border border-rose-200 font-bold text-xs flex items-center gap-1.5">
                    <i data-lucide="log-out" class="w-3.5 h-3.5"></i> Log Out
                </a>
            </div>
        </div>

        <!-- Metric Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="p-5 rounded-3xl bg-white border border-slate-200 shadow-xs flex items-center space-x-4">
                <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 border border-blue-200/80 flex items-center justify-center shrink-0">
                    <i data-lucide="building-2" class="w-6 h-6"></i>
                </div>
                <div>
                    <div class="text-[11px] text-slate-400 font-extrabold uppercase tracking-wider">Tenant Sites</div>
                    <div class="text-2xl font-black text-slate-900 mt-0.5">{{ $subscribers->count() }}</div>
                    <div class="text-[10px] text-emerald-600 font-bold mt-0.5">Isolated CarelioEMR Sites</div>
                </div>
            </div>

            <div class="p-5 rounded-3xl bg-white border border-slate-200 shadow-xs flex items-center space-x-4">
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 border border-emerald-200/80 flex items-center justify-center shrink-0">
                    <i data-lucide="dollar-sign" class="w-6 h-6"></i>
                </div>
                <div>
                    <div class="text-[11px] text-slate-400 font-extrabold uppercase tracking-wider">Monthly MRR</div>
                    <div class="text-2xl font-black text-emerald-600 mt-0.5">${{ $subscribers->count() * 80 }}</div>
                    <div class="text-[10px] text-slate-500 font-semibold mt-0.5">$80 / month per tenant</div>
                </div>
            </div>

            <div class="p-5 rounded-3xl bg-white border border-slate-200 shadow-xs flex items-center space-x-4">
                <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 border border-indigo-200/80 flex items-center justify-center shrink-0">
                    <i data-lucide="database" class="w-6 h-6"></i>
                </div>
                <div>
                    <div class="text-[11px] text-slate-400 font-extrabold uppercase tracking-wider">Tenant DB Isolation</div>
                    <div class="text-lg font-black text-slate-900 mt-0.5">100% Isolated</div>
                    <div class="text-[10px] text-blue-600 font-bold mt-0.5">Separate DB per Tenant</div>
                </div>
            </div>

            <div class="p-5 rounded-3xl bg-white border border-slate-200 shadow-xs flex items-center space-x-4">
                <div class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-600 border border-purple-200/80 flex items-center justify-center shrink-0">
                    <i data-lucide="lock" class="w-6 h-6"></i>
                </div>
                <div>
                    <div class="text-[11px] text-slate-400 font-extrabold uppercase tracking-wider">Role Access</div>
                    <div class="text-lg font-black text-purple-700 mt-0.5">Restricted Admin</div>
                    <div class="text-[10px] text-slate-500 font-medium mt-0.5">No Super-Admin Privilege</div>
                </div>
            </div>
        </div>

        <!-- Data Table Container -->
        <div class="p-6 rounded-3xl bg-white border border-slate-200 shadow-sm space-y-5">
            <div class="overflow-x-auto rounded-2xl border border-slate-200/80">
                <table class="w-full text-left border-collapse min-w-[950px]">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-extrabold uppercase tracking-wider text-slate-500">
                            <th class="px-6 py-4 whitespace-nowrap">Doctor / Subscriber</th>
                            <th class="px-6 py-4 whitespace-nowrap">CarelioEMR Site URL</th>
                            <th class="px-6 py-4 whitespace-nowrap">Tenant DB</th>
                            <th class="px-6 py-4 whitespace-nowrap">Provision Status</th>
                            <th class="px-6 py-4 whitespace-nowrap">Review Status</th>
                            <th class="px-6 py-4 whitespace-nowrap">Review Action</th>
                            <th class="px-6 py-4 whitespace-nowrap">Stripe Reference</th>
                            <th class="px-6 py-4 whitespace-nowrap">Paid At</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs font-semibold">
                        @forelse($subscribers as $sub)
                            <tr class="hover:bg-blue-50/40 transition-colors">
                                <td class="px-6 py-4.5 whitespace-nowrap">
                                    <div class="font-extrabold text-slate-900 text-sm">{{ $sub->doctor_name }}</div>
                                    <div class="text-[11px] text-slate-500 font-medium flex items-center gap-1 mt-0.5">
                                        <i data-lucide="mail" class="w-3 h-3 text-blue-600 shrink-0"></i> {{ $sub->email }}
                                    </div>
                                </td>

                                <td class="px-6 py-4.5 whitespace-nowrap">
                                    @if($sub->openemr_site_url)
                                        <a href="{{ $sub->openemr_site_url }}" target="_blank" class="inline-flex items-center gap-1 px-3 py-1 rounded-lg bg-blue-50 text-blue-700 border border-blue-200 font-bold hover:bg-blue-100 text-xs">
                                            <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                                            <span>{{ $sub->tenant_slug }}</span>
                                        </a>
                                    @else
                                        <span class="text-slate-400 font-mono">Pending...</span>
                                    @endif
                                </td>

                                <td class="px-6 py-4.5 whitespace-nowrap">
                                    <code class="font-mono text-[11px] text-slate-700 bg-slate-100 px-2.5 py-1 rounded-md border border-slate-200/60">
                                        {{ $sub->openemr_database ?? 'carelio_pending' }}
                                    </code>
                                </td>

                                <td class="px-6 py-4.5 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-extrabold {{ $sub->provision_status == 'completed' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($sub->provision_status == 'failed' ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-amber-50 text-amber-700 border border-amber-200') }}">
                                        <i data-lucide="{{ $sub->provision_status == 'completed' ? 'check-circle-2' : 'refresh-cw' }}" class="w-3.5 h-3.5"></i>
                                        <span>{{ ucfirst($sub->provision_status ?? 'completed') }}</span>
                                    </span>
                                </td>

                                <td class="px-6 py-4.5 whitespace-nowrap">
                                    @if($sub->review_status == 'approved')
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200" title="Approved by {{ $sub->reviewed_by }} at {{ $sub->reviewed_at }}">
                                            <i data-lucide="check-check" class="w-3.5 h-3.5 text-emerald-600"></i>
                                            <span>Approved</span>
                                        </span>
                                    @elseif($sub->review_status == 'rejected')
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-extrabold bg-rose-50 text-rose-700 border border-rose-200" title="{{ $sub->rejection_reason ?? 'No reason provided' }} (by {{ $sub->reviewed_by }})">
                                            <i data-lucide="x-circle" class="w-3.5 h-3.5 text-rose-600"></i>
                                            <span>Rejected</span>
                                        </span>
                                    @elseif($sub->review_status == 'pending_review')
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-extrabold bg-amber-50 text-amber-700 border border-amber-200">
                                            <i data-lucide="clock" class="w-3.5 h-3.5 text-amber-600"></i>
                                            <span>Pending Review</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-500">
                                            <i data-lucide="minus" class="w-3.5 h-3.5"></i>
                                            <span>Awaiting</span>
                                        </span>
                                    @endif
                                </td>

                                <td class="px-6 py-4.5 whitespace-nowrap">
                                    @if($sub->provision_status == 'completed' && $sub->review_status == 'pending_review')
                                        <div class="flex items-center space-x-2">
                                            <form action="{{ url('/admin/subscribers/' . $sub->id . '/approve') }}" method="POST" class="inline" onsubmit="return confirm('Approve tenant for {{ addslashes($sub->doctor_name) }}?');">
                                                @csrf
                                                <button type="submit" class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs transition-all flex items-center gap-1 cursor-pointer">
                                                    <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                                    <span>Approve</span>
                                                </button>
                                            </form>

                                            <button type="button" onclick="openRejectModal({{ $sub->id }}, '{{ addslashes($sub->doctor_name) }}')" class="px-3 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-bold text-xs transition-all flex items-center gap-1 cursor-pointer">
                                                <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                                <span>Reject</span>
                                            </button>
                                        </div>
                                    @elseif($sub->review_status == 'approved')
                                        <div class="text-[11px] text-emerald-700 font-semibold flex items-center gap-1">
                                            <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                            <span>Approved ({{ $sub->reviewed_by ?? 'Admin' }})</span>
                                        </div>
                                    @elseif($sub->review_status == 'rejected')
                                        <div class="text-[11px] text-rose-600 font-semibold flex items-center gap-1">
                                            <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                            <span>Rejected{{ $sub->rejection_reason ? ': ' . \Illuminate\Support\Str::limit($sub->rejection_reason, 20) : '' }}</span>
                                        </div>
                                    @else
                                        <span class="text-[11px] text-slate-400 font-medium">Provisioning Required</span>
                                    @endif
                                </td>

                                <td class="px-6 py-4.5 whitespace-nowrap">
                                    <code class="font-mono text-xs text-slate-700 bg-slate-100 px-2.5 py-1 rounded-md border border-slate-200/60">
                                        {{ $sub->stripe_payment_intent_id ?? 'pi_3P98aF...' }}
                                    </code>
                                </td>

                                <td class="px-6 py-4.5 text-[11px] text-slate-500 font-medium whitespace-nowrap">
                                    {{ $sub->paid_at ? \Carbon\Carbon::parse($sub->paid_at)->toDayDateTimeString() : \Carbon\Carbon::parse($sub->created_at)->toDayDateTimeString() }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colSpan="8" class="px-6 py-12 text-center text-slate-500 font-medium">
                                    No tenant database records found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </main>

</div>

<!-- Reject Modal -->
<div id="rejectModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-sm">
    <div class="relative w-full max-w-md bg-white border border-slate-200 rounded-3xl shadow-2xl p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div class="flex items-center space-x-2 text-rose-600 font-black text-sm">
                <i data-lucide="alert-triangle" class="w-5 h-5"></i>
                <span>Reject Tenant Application</span>
            </div>
            <button onclick="closeRejectModal()" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-500 font-bold text-sm cursor-pointer">&times;</button>
        </div>

        <p class="text-xs text-slate-600 font-medium">
            Are you sure you want to reject the tenant setup for <strong id="rejectDoctorName" class="text-slate-900"></strong>? The provisioned database and site files will remain completely intact for audit.
        </p>

        <form id="rejectForm" method="POST" action="">
            @csrf
            <div class="space-y-1.5 mb-4">
                <label class="text-xs font-bold text-slate-700 block">Rejection Reason (Optional):</label>
                <textarea name="rejection_reason" rows="3" placeholder="e.g. Incomplete practitioner documentation, license review needed..." class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs text-slate-900 font-medium focus:border-rose-500 focus:outline-none"></textarea>
            </div>

            <div class="flex items-center justify-end space-x-2">
                <button type="button" onclick="closeRejectModal()" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs cursor-pointer">Cancel</button>
                <button type="submit" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs cursor-pointer shadow-sm">Confirm Rejection</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openRejectModal(subId, doctorName) {
        document.getElementById('rejectForm').action = '{{ url('/admin/subscribers') }}/' + subId + '/reject';
        document.getElementById('rejectDoctorName').innerText = doctorName;
        document.getElementById('rejectModal').classList.remove('hidden');
        if (window.lucide) lucide.createIcons();
    }
    function closeRejectModal() {
        document.getElementById('rejectModal').classList.add('hidden');
    }
</script>

@endsection
