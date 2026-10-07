<?php

$outDir = 'E:/xampp/htdocs/1page/manual_assets';
if (!is_dir($outDir)) {
    mkdir($outDir, 0777, true);
}

// 1. Temporary Password Reset Screen
$svgReset = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 900 580" width="100%" height="100%" font-family="system-ui, -apple-system, 'Segoe UI', Roboto, Helvetica, sans-serif">
  <defs>
    <linearGradient id="headerGrad" x1="0%" y1="0%" x2="100%" y2="0%">
      <stop offset="0%" stop-color="#0284c7" />
      <stop offset="1%" stop-color="#0369a1" />
    </linearGradient>
    <filter id="shadow" x="-5%" y="-5%" width="110%" height="110%">
      <feDropShadow dx="0" dy="8" stdDeviation="12" flood-color="#000" flood-opacity="0.08" />
    </filter>
  </defs>

  <!-- Background -->
  <rect width="900" height="580" fill="#f8fafc" rx="12" />

  <!-- Window Frame -->
  <rect x="30" y="25" width="840" height="530" rx="12" fill="#ffffff" stroke="#e2e8f0" stroke-width="2" filter="url(#shadow)" />

  <!-- Window Title Bar -->
  <path d="M 30,37 Q 30,25 42,25 L 858,25 Q 870,25 870,37 L 870,70 L 30,70 Z" fill="#f1f5f9" />
  <circle cx="55" cy="48" r="6" fill="#ef4444" />
  <circle cx="75" cy="48" r="6" fill="#f59e0b" />
  <circle cx="95" cy="48" r="6" fill="#10b981" />
  <text x="450" y="52" fill="#64748b" font-size="13" font-weight="600" text-anchor="middle">CarelioEMR Security Portal — Temporary Password Update</text>

  <!-- Logo and Header -->
  <g transform="translate(360, 90)">
    <circle cx="15" cy="15" r="14" fill="#0284c7" />
    <path d="M 11,15 L 14,19 L 20,11" stroke="#ffffff" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
    <text x="38" y="21" font-size="20" font-weight="800" fill="#0f172a" letter-spacing="-0.5">Carelio<tspan fill="#0284c7">EMR</tspan></text>
  </g>

  <text x="450" y="145" font-size="19" font-weight="700" fill="#0f172a" text-anchor="middle">Set Your Permanent Password</text>
  <text x="450" y="168" font-size="13" fill="#64748b" text-anchor="middle">For account security, you must update your initial administrator credentials.</text>

  <!-- Form Box -->
  <g transform="translate(180, 195)">
    <!-- Current Password -->
    <text x="0" y="15" font-size="13" font-weight="600" fill="#334155">Current Temporary Password</text>
    <rect x="0" y="25" width="540" height="42" rx="6" fill="#f8fafc" stroke="#cbd5e1" stroke-width="1.5" />
    <text x="16" y="51" font-size="15" fill="#94a3b8">••••••••••••••</text>

    <!-- New Password -->
    <text x="0" y="95" font-size="13" font-weight="600" fill="#334155">New Permanent Password</text>
    <rect x="0" y="105" width="540" height="42" rx="6" fill="#ffffff" stroke="#0284c7" stroke-width="2" />
    <text x="16" y="131" font-size="15" fill="#0f172a">SiteAdmin@Carelio2026!</text>

    <!-- Confirm Password -->
    <text x="0" y="175" font-size="13" font-weight="600" fill="#334155">Confirm New Password</text>
    <rect x="0" y="185" width="540" height="42" rx="6" fill="#ffffff" stroke="#10b981" stroke-width="1.5" />
    <text x="16" y="211" font-size="15" fill="#0f172a">SiteAdmin@Carelio2026!</text>
    <circle cx="515" cy="206" r="10" fill="#dcfce7" />
    <path d="M 511,206 L 514,209 L 519,203" stroke="#16a34a" stroke-width="2" fill="none" stroke-linecap="round" />

    <!-- Requirements Card -->
    <rect x="0" y="240" width="540" height="40" rx="6" fill="#f0fdf4" stroke="#bbf7d0" stroke-width="1" />
    <text x="15" y="264" font-size="12" fill="#15803d" font-weight="600">✓ Strong: 12+ characters, uppercase, number &amp; symbol verified</text>

    <!-- Submit Button -->
    <rect x="0" y="295" width="540" height="46" rx="6" fill="url(#headerGrad)" />
    <text x="270" y="324" font-size="15" font-weight="700" fill="#ffffff" text-anchor="middle">Save Password &amp; Proceed to Login →</text>
  </g>

  <!-- Auto Redirect Callout -->
  <g transform="translate(180, 500)">
    <rect x="0" y="0" width="540" height="32" rx="6" fill="#eff6ff" stroke="#bfdbfe" />
    <text x="270" y="20" font-size="12" font-weight="500" fill="#1d4ed8" text-anchor="middle">🔒 CSRF Protected — Automatically redirects to tenant login page upon update</text>
  </g>
</svg>
SVG;
file_put_contents($outDir . '/02_temporary_password_reset.svg', $svgReset);

// 2. Caribbean Demographics UI Mockup
$svgDemographics = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 920 620" width="100%" height="100%" font-family="system-ui, -apple-system, 'Segoe UI', Roboto, Helvetica, sans-serif">
  <defs>
    <filter id="demoShadow" x="-5%" y="-5%" width="110%" height="110%">
      <feDropShadow dx="0" dy="6" stdDeviation="10" flood-color="#000" flood-opacity="0.07" />
    </filter>
  </defs>

  <rect width="920" height="620" fill="#f8fafc" rx="12" />
  
  <!-- Window Container -->
  <rect x="25" y="20" width="870" height="575" rx="10" fill="#ffffff" stroke="#e2e8f0" stroke-width="1.5" filter="url(#demoShadow)" />
  
  <!-- Window Header -->
  <path d="M 25,30 Q 25,20 35,20 L 885,20 Q 895,20 895,30 L 895,62 L 25,62 Z" fill="#0f172a" />
  <circle cx="50" cy="41" r="5" fill="#ef4444" />
  <circle cx="68" cy="41" r="5" fill="#f59e0b" />
  <circle cx="86" cy="41" r="5" fill="#10b981" />
  <text x="115" y="46" fill="#f8fafc" font-size="14" font-weight="600">CarelioEMR — Patient Registration &amp; Demographics</text>
  <rect x="740" y="32" width="130" height="22" rx="4" fill="#1e293b" />
  <text x="805" y="47" fill="#94a3b8" font-size="11" text-anchor="middle">Saint Lucia Regional</text>

  <!-- Section Header -->
  <g transform="translate(55, 90)">
    <rect x="0" y="0" width="810" height="42" rx="6" fill="#f0fdf4" stroke="#bbf7d0" />
    <circle cx="24" cy="21" r="10" fill="#22c55e" />
    <path d="M 19,21 L 23,25 L 29,17" stroke="#ffffff" stroke-width="2" fill="none" stroke-linecap="round" />
    <text x="45" y="26" font-size="14" font-weight="700" fill="#166534">Caribbean Geographic Demographics Engine (Active)</text>
    <text x="455" y="26" font-size="12" fill="#15803d">29 Caribbean Nations • 10 Saint Lucia Districts • 367 Communities</text>
  </g>

  <!-- Form Layout -->
  <g transform="translate(55, 155)">
    <!-- Row 1: Patient Basic Info -->
    <text x="0" y="15" font-size="12" font-weight="600" fill="#64748b">FIRST NAME</text>
    <rect x="0" y="25" width="250" height="38" rx="6" fill="#ffffff" stroke="#cbd5e1" />
    <text x="12" y="49" font-size="14" fill="#1e293b">Marcus</text>

    <text x="280" y="15" font-size="12" font-weight="600" fill="#64748b">LAST NAME</text>
    <rect x="280" y="25" width="250" height="38" rx="6" fill="#ffffff" stroke="#cbd5e1" />
    <text x="292" y="49" font-size="14" fill="#1e293b">Alexander</text>

    <text x="560" y="15" font-size="12" font-weight="600" fill="#64748b">DATE OF BIRTH</text>
    <rect x="560" y="25" width="250" height="38" rx="6" fill="#ffffff" stroke="#cbd5e1" />
    <text x="572" y="49" font-size="14" fill="#1e293b">1988-04-14</text>
  </g>

  <!-- Cascading Dropdowns Highlight Box -->
  <g transform="translate(55, 240)">
    <rect x="0" y="0" width="810" height="230" rx="8" fill="#f8fafc" stroke="#38bdf8" stroke-width="2" stroke-dasharray="6,4" />
    <rect x="20" y="-12" width="270" height="24" rx="4" fill="#0284c7" />
    <text x="155" y="4" font-size="11" font-weight="700" fill="#ffffff" text-anchor="middle">⚡ DYNAMIC CASCADING SELECTION</text>

    <!-- Step 1: Country -->
    <g transform="translate(30, 30)">
      <text x="0" y="15" font-size="13" font-weight="700" fill="#0369a1">1. Country (Selects Nation)</text>
      <rect x="0" y="25" width="225" height="42" rx="6" fill="#ffffff" stroke="#0284c7" stroke-width="2" />
      <text x="14" y="52" font-size="14" font-weight="600" fill="#0f172a">Saint Lucia (LC)</text>
      <path d="M 195,47 L 202,54 L 209,47" fill="none" stroke="#0284c7" stroke-width="2" />
    </g>

    <!-- Arrow 1 -->
    <path d="M 275,80 L 305,80" stroke="#0284c7" stroke-width="2.5" marker-end="url(#arrow)" />

    <!-- Step 2: District -->
    <g transform="translate(325, 30)">
      <text x="0" y="15" font-size="13" font-weight="700" fill="#0369a1">2. Administrative District</text>
      <rect x="0" y="25" width="215" height="42" rx="6" fill="#ffffff" stroke="#0284c7" stroke-width="2" />
      <text x="14" y="52" font-size="14" font-weight="600" fill="#0f172a">Castries</text>
      <path d="M 185,47 L 192,54 L 199,47" fill="none" stroke="#0284c7" stroke-width="2" />
      <text x="0" y="85" font-size="11" fill="#64748b">Populates 10 LC Districts</text>
    </g>

    <!-- Arrow 2 -->
    <path d="M 560,80 L 590,80" stroke="#0284c7" stroke-width="2.5" />

    <!-- Step 3: Community -->
    <g transform="translate(605, 30)">
      <text x="0" y="15" font-size="13" font-weight="700" fill="#0369a1">3. Local Community</text>
      <rect x="0" y="25" width="180" height="42" rx="6" fill="#ffffff" stroke="#0284c7" stroke-width="2" />
      <text x="14" y="52" font-size="14" font-weight="600" fill="#0f172a">Babonneau</text>
      <path d="M 152,47 L 159,54 L 166,47" fill="none" stroke="#0284c7" stroke-width="2" />
      <text x="0" y="85" font-size="11" fill="#64748b">Filtered to Castries (367 Total)</text>
    </g>

    <!-- Street Address & Postal -->
    <g transform="translate(30, 130)">
      <text x="0" y="15" font-size="12" font-weight="600" fill="#475569">STREET ADDRESS</text>
      <rect x="0" y="25" width="485" height="38" rx="6" fill="#ffffff" stroke="#cbd5e1" />
      <text x="14" y="49" font-size="13" fill="#1e293b">Hill Top Road, Building 4A</text>

      <text x="510" y="15" font-size="12" font-weight="600" fill="#475569">POSTAL CODE</text>
      <rect x="510" y="25" width="245" height="38" rx="6" fill="#ffffff" stroke="#cbd5e1" />
      <text x="524" y="49" font-size="13" fill="#1e293b">LC01 101</text>
    </g>
  </g>

  <!-- Footer Actions -->
  <g transform="translate(55, 495)">
    <rect x="0" y="0" width="140" height="44" rx="6" fill="#0284c7" />
    <text x="70" y="27" font-size="14" font-weight="700" fill="#ffffff" text-anchor="middle">Save Patient</text>

    <rect x="155" y="0" width="100" height="44" rx="6" fill="#f1f5f9" stroke="#cbd5e1" />
    <text x="205" y="27" font-size="14" font-weight="600" fill="#475569" text-anchor="middle">Cancel</text>

    <text x="810" y="27" font-size="12" fill="#64748b" text-anchor="end">Database stored in OpenEMR list_options schema</text>
  </g>
</svg>
SVG;
file_put_contents($outDir . '/03_caribbean_demographics.svg', $svgDemographics);

// 3. Subscription Dashboard - ACTIVE
$svgSubActive = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 960 600" width="100%" height="100%" font-family="system-ui, -apple-system, 'Segoe UI', Roboto, Helvetica, sans-serif">
  <defs>
    <filter id="dashShadow" x="-5%" y="-5%" width="110%" height="110%">
      <feDropShadow dx="0" dy="6" stdDeviation="12" flood-color="#000" flood-opacity="0.06" />
    </filter>
  </defs>

  <rect width="960" height="600" fill="#f8fafc" rx="12" />

  <!-- Container -->
  <rect x="30" y="20" width="900" height="560" rx="10" fill="#ffffff" stroke="#e2e8f0" stroke-width="1.5" filter="url(#dashShadow)" />

  <!-- Top Navigation Bar -->
  <rect x="30" y="20" width="900" height="64" rx="10" fill="#ffffff" />
  <line x1="30" y1="84" x2="930" y2="84" stroke="#e2e8f0" stroke-width="1.5" />
  
  <text x="60" y="52" font-size="19" font-weight="800" fill="#0f172a">Carelio<tspan fill="#0284c7">EMR</tspan> Subscription Management</text>
  
  <!-- Status Badge ACTIVE -->
  <rect x="740" y="38" width="150" height="32" rx="16" fill="#dcfce7" stroke="#86efac" stroke-width="1.5" />
  <circle cx="758" cy="54" r="5" fill="#16a34a" />
  <text x="815" y="60" font-size="13" font-weight="800" fill="#166534" text-anchor="middle">STATUS: ACTIVE</text>

  <!-- Banner ACTIVE -->
  <g transform="translate(60, 105)">
    <rect x="0" y="0" width="840" height="55" rx="8" fill="#f0fdf4" stroke="#86efac" stroke-width="1.5" />
    <rect x="0" y="0" width="6" height="55" rx="3" fill="#22c55e" />
    <circle cx="32" cy="28" r="12" fill="#22c55e" />
    <path d="M 26,28 L 30,32 L 38,23" stroke="#ffffff" stroke-width="2.5" fill="none" stroke-linecap="round" />
    <text x="56" y="26" font-size="14" font-weight="700" fill="#15803d">Active Clinic Subscription</text>
    <text x="56" y="44" font-size="12" fill="#166534">Tenant is in regular standing. All medical modules, encounter charts, and billing are fully accessible.</text>
  </g>

  <!-- Metric Cards -->
  <g transform="translate(60, 180)">
    <!-- Card 1 -->
    <rect x="0" y="0" width="195" height="85" rx="8" fill="#f8fafc" stroke="#e2e8f0" />
    <text x="18" y="26" font-size="11" font-weight="700" fill="#64748b">TENANT SITE</text>
    <text x="18" y="55" font-size="14" font-weight="700" fill="#0f172a">dr-sarah-johnson-1</text>
    <text x="18" y="72" font-size="10" fill="#94a3b8">Multi-tenant Isolation</text>

    <!-- Card 2 -->
    <rect x="215" y="0" width="195" height="85" rx="8" fill="#f8fafc" stroke="#e2e8f0" />
    <text x="233" y="26" font-size="11" font-weight="700" fill="#64748b">EXPIRATION DATE</text>
    <text x="233" y="55" font-size="16" font-weight="800" fill="#0284c7">2026-11-30</text>
    <text x="233" y="72" font-size="10" fill="#16a34a">Valid for 55 days</text>

    <!-- Card 3 -->
    <rect x="430" y="0" width="195" height="85" rx="8" fill="#f8fafc" stroke="#e2e8f0" />
    <text x="448" y="26" font-size="11" font-weight="700" fill="#64748b">GRACE PERIOD WINDOW</text>
    <text x="448" y="55" font-size="15" font-weight="700" fill="#0f172a">2 Days (48h)</text>
    <text x="448" y="72" font-size="10" fill="#94a3b8">Applies Post-Expiration</text>

    <!-- Card 4 -->
    <rect x="645" y="0" width="195" height="85" rx="8" fill="#f8fafc" stroke="#e2e8f0" />
    <text x="663" y="26" font-size="11" font-weight="700" fill="#64748b">DATA INTEGRITY</text>
    <text x="663" y="55" font-size="15" font-weight="800" fill="#16a34a">100% Safe</text>
    <text x="663" y="72" font-size="10" fill="#15803d">Zero Deletion Policy</text>
  </g>

  <!-- Site Admin Action Panel -->
  <g transform="translate(60, 285)">
    <rect x="0" y="0" width="840" height="135" rx="8" fill="#ffffff" stroke="#cbd5e1" stroke-width="1.5" />
    <text x="24" y="28" font-size="13" font-weight="800" fill="#0f172a">SITE ADMINISTRATOR SUBSCRIPTION CONTROLS</text>

    <!-- Action 1: Quick Renew -->
    <g transform="translate(24, 45)">
      <text x="0" y="15" font-size="12" font-weight="600" fill="#475569">Quick Renewal</text>
      <rect x="0" y="24" width="140" height="38" rx="6" fill="#0284c7" />
      <text x="70" y="48" font-size="13" font-weight="700" fill="#ffffff" text-anchor="middle">+ 1 Month ($199)</text>

      <rect x="155" y="24" width="140" height="38" rx="6" fill="#0369a1" />
      <text x="225" y="48" font-size="13" font-weight="700" fill="#ffffff" text-anchor="middle">+ 1 Year ($1,999)</text>
    </g>

    <!-- Action 2: Extend Specific Date -->
    <g transform="translate(360, 45)">
      <text x="0" y="15" font-size="12" font-weight="600" fill="#475569">Custom Expiration Date Override</text>
      <rect x="0" y="24" width="220" height="38" rx="6" fill="#ffffff" stroke="#cbd5e1" />
      <text x="14" y="48" font-size="13" fill="#334155">2026-12-31</text>
      
      <rect x="235" y="24" width="130" height="38" rx="6" fill="#0f172a" />
      <text x="300" y="48" font-size="13" font-weight="700" fill="#ffffff" text-anchor="middle">Extend Date</text>
    </g>
  </g>

  <!-- Interactive Test Simulator Strip -->
  <g transform="translate(60, 440)">
    <rect x="0" y="0" width="840" height="110" rx="8" fill="#f1f5f9" stroke="#94a3b8" stroke-dasharray="4,4" />
    <text x="24" y="26" font-size="12" font-weight="800" fill="#475569">🧪 INTERACTIVE CLIENT TEST SIMULATOR (1-Click Lifecycle Transitions)</text>
    <text x="24" y="45" font-size="11" fill="#64748b">Click below to instantly simulate any subscription phase for client demonstrations:</text>

    <g transform="translate(24, 58)">
      <rect x="0" y="0" width="150" height="34" rx="6" fill="#22c55e" />
      <text x="75" y="22" font-size="12" font-weight="700" fill="#ffffff" text-anchor="middle">Simulate ACTIVE</text>

      <rect x="165" y="0" width="180" height="34" rx="6" fill="#eab308" />
      <text x="255" y="22" font-size="12" font-weight="700" fill="#ffffff" text-anchor="middle">Simulate EXPIRING (2d)</text>

      <rect x="360" y="0" width="190" height="34" rx="6" fill="#f97316" />
      <text x="455" y="22" font-size="12" font-weight="700" fill="#ffffff" text-anchor="middle">Simulate GRACE PERIOD</text>

      <rect x="565" y="0" width="170" height="34" rx="6" fill="#ef4444" />
      <text x="650" y="22" font-size="12" font-weight="700" fill="#ffffff" text-anchor="middle">Simulate DEACTIVATE</text>
    </g>
  </g>
</svg>
SVG;
file_put_contents($outDir . '/04_subscription_dashboard_active.svg', $svgSubActive);

// 4. Subscription Dashboard - EXPIRING_SOON
$svgSubExpiring = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 960 480" width="100%" height="100%" font-family="system-ui, -apple-system, 'Segoe UI', Roboto, Helvetica, sans-serif">
  <defs>
    <filter id="expShadow" x="-5%" y="-5%" width="110%" height="110%">
      <feDropShadow dx="0" dy="6" stdDeviation="12" flood-color="#000" flood-opacity="0.06" />
    </filter>
  </defs>

  <rect width="960" height="480" fill="#f8fafc" rx="12" />
  <rect x="30" y="20" width="900" height="440" rx="10" fill="#ffffff" stroke="#e2e8f0" stroke-width="1.5" filter="url(#expShadow)" />

  <!-- Header -->
  <text x="60" y="58" font-size="19" font-weight="800" fill="#0f172a">Carelio<tspan fill="#0284c7">EMR</tspan> Subscription Management</text>
  
  <!-- Status Badge EXPIRING_SOON -->
  <rect x="690" y="36" width="210" height="32" rx="16" fill="#fef9c3" stroke="#fef08a" stroke-width="1.5" />
  <circle cx="708" cy="52" r="5" fill="#ca8a04" />
  <text x="800" y="58" font-size="12" font-weight="800" fill="#854d0e" text-anchor="middle">STATUS: EXPIRING SOON</text>

  <!-- Banner EXPIRING_SOON -->
  <g transform="translate(60, 95)">
    <rect x="0" y="0" width="840" height="85" rx="8" fill="#fefce8" stroke="#fde047" stroke-width="1.5" />
    <rect x="0" y="0" width="6" height="85" rx="3" fill="#eab308" />
    <circle cx="36" cy="42" r="16" fill="#eab308" />
    <text x="36" y="48" font-size="18" font-weight="900" fill="#ffffff" text-anchor="middle">!</text>
    <text x="68" y="35" font-size="15" font-weight="800" fill="#a16207">Subscription Renewal Reminder — Expires in ≤ 2 Days</text>
    <text x="68" y="56" font-size="13" fill="#854d0e">Your clinic subscription will end on 2026-10-08 (2 days remaining). An automated email reminder has been</text>
    <text x="68" y="74" font-size="13" fill="#854d0e">sent to your billing contact. Please renew today to prevent entry into grace period.</text>
  </g>

  <!-- Metrics -->
  <g transform="translate(60, 205)">
    <rect x="0" y="0" width="260" height="80" rx="8" fill="#fefce8" stroke="#fef08a" />
    <text x="20" y="26" font-size="11" font-weight="700" fill="#854d0e">DAYS REMAINING</text>
    <text x="20" y="56" font-size="22" font-weight="900" fill="#ca8a04">2 Days (48 Hours)</text>

    <rect x="290" y="0" width="260" height="80" rx="8" fill="#f8fafc" stroke="#e2e8f0" />
    <text x="20" y="26" font-size="11" font-weight="700" fill="#64748b" transform="translate(290,0)">EXPIRATION DATE</text>
    <text x="20" y="56" font-size="20" font-weight="800" fill="#0f172a" transform="translate(290,0)">October 08, 2026</text>

    <rect x="580" y="0" width="260" height="80" rx="8" fill="#f8fafc" stroke="#e2e8f0" />
    <text x="20" y="26" font-size="11" font-weight="700" fill="#64748b" transform="translate(580,0)">CLINIC ACCESS</text>
    <text x="20" y="56" font-size="18" font-weight="800" fill="#16a34a" transform="translate(580,0)">Full Unlocked Access</text>
  </g>

  <!-- Action Bar -->
  <g transform="translate(60, 315)">
    <rect x="0" y="0" width="840" height="90" rx="8" fill="#ffffff" stroke="#cbd5e1" />
    <text x="24" y="32" font-size="14" font-weight="700" fill="#0f172a">Site Administrator Renewal Options</text>
    
    <g transform="translate(24, 45)">
      <rect x="0" y="0" width="180" height="36" rx="6" fill="#0284c7" />
      <text x="90" y="23" font-size="13" font-weight="700" fill="#ffffff" text-anchor="middle">Renew for 1 Month</text>

      <rect x="195" y="0" width="180" height="36" rx="6" fill="#0369a1" />
      <text x="285" y="23" font-size="13" font-weight="700" fill="#ffffff" text-anchor="middle">Renew for 1 Year</text>

      <rect x="390" y="0" width="160" height="36" rx="6" fill="#f1f5f9" stroke="#cbd5e1" />
      <text x="470" y="23" font-size="13" font-weight="600" fill="#475569" text-anchor="middle">Custom Extension</text>
    </g>
  </g>
</svg>
SVG;
file_put_contents($outDir . '/05_subscription_dashboard_expiring.svg', $svgSubExpiring);

// 5. Subscription Dashboard - EXPIRED_GRACE_PERIOD
$svgSubGrace = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 960 480" width="100%" height="100%" font-family="system-ui, -apple-system, 'Segoe UI', Roboto, Helvetica, sans-serif">
  <defs>
    <filter id="graceShadow" x="-5%" y="-5%" width="110%" height="110%">
      <feDropShadow dx="0" dy="6" stdDeviation="12" flood-color="#000" flood-opacity="0.06" />
    </filter>
  </defs>

  <rect width="960" height="480" fill="#f8fafc" rx="12" />
  <rect x="30" y="20" width="900" height="440" rx="10" fill="#ffffff" stroke="#e2e8f0" stroke-width="1.5" filter="url(#graceShadow)" />

  <!-- Header -->
  <text x="60" y="58" font-size="19" font-weight="800" fill="#0f172a">Carelio<tspan fill="#0284c7">EMR</tspan> Subscription Management</text>
  
  <!-- Status Badge EXPIRED_GRACE_PERIOD -->
  <rect x="650" y="36" width="250" height="32" rx="16" fill="#ffedd5" stroke="#fed7aa" stroke-width="1.5" />
  <circle cx="668" cy="52" r="5" fill="#ea580c" />
  <text x="778" y="58" font-size="12" font-weight="800" fill="#9a3412" text-anchor="middle">STATUS: EXPIRED / GRACE PERIOD</text>

  <!-- Banner EXPIRED_GRACE_PERIOD -->
  <g transform="translate(60, 95)">
    <rect x="0" y="0" width="840" height="90" rx="8" fill="#fff7ed" stroke="#fdba74" stroke-width="1.5" />
    <rect x="0" y="0" width="6" height="90" rx="3" fill="#f97316" />
    <circle cx="36" cy="45" r="16" fill="#f97316" />
    <text x="36" y="52" font-size="18" font-weight="900" fill="#ffffff" text-anchor="middle">⏱</text>
    <text x="68" y="34" font-size="15" font-weight="800" fill="#c2410c">Subscription Expired — 2-Day Grace Period In Effect</text>
    <text x="68" y="55" font-size="13" fill="#9a3412">The regular subscription expired on 2026-10-06. Clinical and patient care continues uninterrupted during</text>
    <text x="68" y="73" font-size="13" fill="#9a3412">this 2-day grace window. Deactivation will occur on 2026-10-08 23:59:59 if renewal is not submitted.</text>
  </g>

  <!-- Metrics -->
  <g transform="translate(60, 210)">
    <rect x="0" y="0" width="260" height="80" rx="8" fill="#fff7ed" stroke="#fed7aa" />
    <text x="20" y="26" font-size="11" font-weight="700" fill="#9a3412">GRACE TIME REMAINING</text>
    <text x="20" y="56" font-size="20" font-weight="900" fill="#ea580c">1 Day, 14 Hours</text>

    <rect x="290" y="0" width="260" height="80" rx="8" fill="#f8fafc" stroke="#e2e8f0" />
    <text x="20" y="26" font-size="11" font-weight="700" fill="#64748b" transform="translate(290,0)">DEACTIVATION DEADLINE</text>
    <text x="20" y="56" font-size="20" font-weight="800" fill="#dc2626" transform="translate(290,0)">October 08, 2026</text>

    <rect x="580" y="0" width="260" height="80" rx="8" fill="#f0fdf4" stroke="#bbf7d0" />
    <text x="20" y="26" font-size="11" font-weight="700" fill="#166534" transform="translate(580,0)">PATIENT RECORDS ACCESS</text>
    <text x="20" y="56" font-size="18" font-weight="800" fill="#16a34a" transform="translate(580,0)">Continuous Active</text>
  </g>

  <!-- Action Bar -->
  <g transform="translate(60, 315)">
    <rect x="0" y="0" width="840" height="90" rx="8" fill="#ffffff" stroke="#cbd5e1" />
    <text x="24" y="32" font-size="14" font-weight="700" fill="#0f172a">Immediate Renewal Action Required</text>
    
    <g transform="translate(24, 45)">
      <rect x="0" y="0" width="230" height="38" rx="6" fill="#ea580c" />
      <text x="115" y="24" font-size="13" font-weight="800" fill="#ffffff" text-anchor="middle">Renew Now (+30 Days) →</text>

      <rect x="245" y="0" width="180" height="38" rx="6" fill="#0f172a" />
      <text x="335" y="24" font-size="13" font-weight="700" fill="#ffffff" text-anchor="middle">Override / Extend Date</text>
    </g>
  </g>
</svg>
SVG;
file_put_contents($outDir . '/06_subscription_dashboard_grace.svg', $svgSubGrace);

// 6. Subscription Dashboard - DEACTIVATED
$svgSubDeact = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 960 520" width="100%" height="100%" font-family="system-ui, -apple-system, 'Segoe UI', Roboto, Helvetica, sans-serif">
  <defs>
    <filter id="deactShadow" x="-5%" y="-5%" width="110%" height="110%">
      <feDropShadow dx="0" dy="6" stdDeviation="12" flood-color="#000" flood-opacity="0.06" />
    </filter>
  </defs>

  <rect width="960" height="520" fill="#f8fafc" rx="12" />
  <rect x="30" y="20" width="900" height="480" rx="10" fill="#ffffff" stroke="#e2e8f0" stroke-width="1.5" filter="url(#deactShadow)" />

  <!-- Header -->
  <text x="60" y="58" font-size="19" font-weight="800" fill="#0f172a">Carelio<tspan fill="#0284c7">EMR</tspan> Subscription Management</text>
  
  <!-- Status Badge DEACTIVATED -->
  <rect x="700" y="36" width="200" height="32" rx="16" fill="#fee2e2" stroke="#fecaca" stroke-width="1.5" />
  <circle cx="718" cy="52" r="5" fill="#dc2626" />
  <text x="803" y="58" font-size="12" font-weight="800" fill="#991b1b" text-anchor="middle">STATUS: DEACTIVATED</text>

  <!-- Banner DEACTIVATED -->
  <g transform="translate(60, 95)">
    <rect x="0" y="0" width="840" height="95" rx="8" fill="#fef2f2" stroke="#fca5a5" stroke-width="1.5" />
    <rect x="0" y="0" width="6" height="95" rx="3" fill="#ef4444" />
    <circle cx="36" cy="48" r="16" fill="#ef4444" />
    <path d="M 28,40 L 44,56 M 44,40 L 28,56" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round" />
    <text x="68" y="34" font-size="15" font-weight="800" fill="#b91c1c">Tenant Deactivated — Safe Suspension Enforced</text>
    <text x="68" y="55" font-size="13" fill="#991b1b">The 2-day grace period has passed without renewal. Staff and clinician logins are locked server-side.</text>
    <text x="68" y="74" font-size="13" font-weight="700" fill="#15803d">🔒 100% Data Preservation Guarantee: Zero patient records, notes, encounters, or billing rows have been deleted.</text>
  </g>

  <!-- Metrics -->
  <g transform="translate(60, 215)">
    <rect x="0" y="0" width="260" height="80" rx="8" fill="#fef2f2" stroke="#fecaca" />
    <text x="20" y="26" font-size="11" font-weight="700" fill="#991b1b">ACCOUNT ACCESS</text>
    <text x="20" y="56" font-size="20" font-weight="900" fill="#dc2626">Locked (Staff)</text>

    <rect x="290" y="0" width="260" height="80" rx="8" fill="#f0fdf4" stroke="#bbf7d0" />
    <text x="20" y="26" font-size="11" font-weight="700" fill="#166534" transform="translate(290,0)">CLINICAL DATA STATUS</text>
    <text x="20" y="56" font-size="20" font-weight="800" fill="#16a34a" transform="translate(290,0)">100% Intact &amp; Safe</text>

    <rect x="580" y="0" width="260" height="80" rx="8" fill="#f8fafc" stroke="#e2e8f0" />
    <text x="20" y="26" font-size="11" font-weight="700" fill="#64748b" transform="translate(580,0)">REACTIVATION TIME</text>
    <text x="20" y="56" font-size="18" font-weight="800" fill="#0284c7" transform="translate(580,0)">Instant (1 Click)</text>
  </g>

  <!-- Action Bar -->
  <g transform="translate(60, 320)">
    <rect x="0" y="0" width="840" height="150" rx="8" fill="#ffffff" stroke="#cbd5e1" />
    <text x="24" y="32" font-size="14" font-weight="800" fill="#0f172a">Site Administrator Instant Reactivation</text>
    <text x="24" y="52" font-size="12" fill="#64748b">Only Site Administrators have permission to reactivate. One click will restore ACTIVE status and immediately unlock clinician logins.</text>

    <g transform="translate(24, 75)">
      <rect x="0" y="0" width="260" height="46" rx="6" fill="#16a34a" />
      <text x="130" y="28" font-size="14" font-weight="800" fill="#ffffff" text-anchor="middle">⚡ Reactivate Tenant (+30 Days)</text>

      <rect x="280" y="0" width="200" height="46" rx="6" fill="#0284c7" />
      <text x="380" y="28" font-size="14" font-weight="700" fill="#ffffff" text-anchor="middle">Annual Reactivation (+1 Yr)</text>
    </g>
  </g>
</svg>
SVG;
file_put_contents($outDir . '/07_subscription_dashboard_deactivated.svg', $svgSubDeact);

// 7. Lifecycle State Machine Architecture Flowchart
$svgFlow = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 960 480" width="100%" height="100%" font-family="system-ui, -apple-system, 'Segoe UI', Roboto, Helvetica, sans-serif">
  <defs>
    <linearGradient id="blueGrad" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#0284c7" />
      <stop offset="1%" stop-color="#0369a1" />
    </linearGradient>
    <linearGradient id="amberGrad" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#f59e0b" />
      <stop offset="1%" stop-color="#d97706" />
    </linearGradient>
    <linearGradient id="orangeGrad" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#f97316" />
      <stop offset="1%" stop-color="#ea580c" />
    </linearGradient>
    <linearGradient id="redGrad" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#ef4444" />
      <stop offset="1%" stop-color="#dc2626" />
    </linearGradient>
    <marker id="arrow" viewBox="0 0 10 10" refX="5" refY="5" markerWidth="6" markerHeight="6" orient="auto-start-reverse">
      <path d="M 0 1 L 10 5 L 0 9 z" fill="#64748b" />
    </marker>
    <marker id="arrowGreen" viewBox="0 0 10 10" refX="5" refY="5" markerWidth="6" markerHeight="6" orient="auto-start-reverse">
      <path d="M 0 1 L 10 5 L 0 9 z" fill="#16a34a" />
    </marker>
  </defs>

  <rect width="960" height="480" fill="#f8fafc" rx="12" />
  <text x="480" y="45" font-size="20" font-weight="800" fill="#0f172a" text-anchor="middle">CarelioEMR Subscription Lifecycle Architecture</text>
  <text x="480" y="70" font-size="13" fill="#64748b" text-anchor="middle">Automated 2-Day Reminders • 2-Day Grace Period • Safe Deactivation with Zero Data Loss</text>

  <!-- Node 1: ACTIVE -->
  <g transform="translate(60, 140)">
    <rect x="0" y="0" width="170" height="110" rx="10" fill="url(#blueGrad)" />
    <text x="85" y="35" font-size="16" font-weight="800" fill="#ffffff" text-anchor="middle">ACTIVE</text>
    <text x="85" y="58" font-size="11" fill="#e0f2fe" text-anchor="middle">Standard Operations</text>
    <text x="85" y="76" font-size="11" fill="#bae6fd" text-anchor="middle">Full Access</text>
    <rect x="15" y="85" width="140" height="16" rx="4" fill="#0369a1" />
    <text x="85" y="97" font-size="9" fill="#ffffff" font-weight="600" text-anchor="middle">Days Left &gt; 2</text>
  </g>

  <!-- Arrow 1 -> 2 -->
  <path d="M 230,195 L 285,195" stroke="#64748b" stroke-width="2.5" marker-end="url(#arrow)" />
  <text x="257" y="185" font-size="10" font-weight="700" fill="#64748b" text-anchor="middle">≤ 2 Days</text>

  <!-- Node 2: EXPIRING SOON -->
  <g transform="translate(290, 140)">
    <rect x="0" y="0" width="170" height="110" rx="10" fill="url(#amberGrad)" />
    <text x="85" y="35" font-size="15" font-weight="800" fill="#ffffff" text-anchor="middle">EXPIRING SOON</text>
    <text x="85" y="58" font-size="11" fill="#fef9c3" text-anchor="middle">Remaining: ≤ 48 Hours</text>
    <text x="85" y="76" font-size="11" fill="#fef08a" text-anchor="middle">Daily Cron Reminder</text>
    <rect x="15" y="85" width="140" height="16" rx="4" fill="#b45309" />
    <text x="85" y="97" font-size="9" fill="#ffffff" font-weight="600" text-anchor="middle">Email Sent to Admin</text>
  </g>

  <!-- Arrow 2 -> 3 -->
  <path d="M 460,195 L 515,195" stroke="#64748b" stroke-width="2.5" marker-end="url(#arrow)" />
  <text x="487" y="185" font-size="10" font-weight="700" fill="#64748b" text-anchor="middle">Expires</text>

  <!-- Node 3: EXPIRED / GRACE PERIOD -->
  <g transform="translate(520, 140)">
    <rect x="0" y="0" width="170" height="110" rx="10" fill="url(#orangeGrad)" />
    <text x="85" y="32" font-size="14" font-weight="800" fill="#ffffff" text-anchor="middle">EXPIRED /</text>
    <text x="85" y="48" font-size="14" font-weight="800" fill="#ffffff" text-anchor="middle">GRACE PERIOD</text>
    <text x="85" y="70" font-size="11" fill="#ffedd5" text-anchor="middle">2 Days Window</text>
    <rect x="15" y="85" width="140" height="16" rx="4" fill="#c2410c" />
    <text x="85" y="97" font-size="9" fill="#ffffff" font-weight="600" text-anchor="middle">100% Uninterrupted</text>
  </g>

  <!-- Arrow 3 -> 4 -->
  <path d="M 690,195 L 745,195" stroke="#64748b" stroke-width="2.5" marker-end="url(#arrow)" />
  <text x="717" y="185" font-size="10" font-weight="700" fill="#64748b" text-anchor="middle">+2 Days Passed</text>

  <!-- Node 4: DEACTIVATED -->
  <g transform="translate(750, 140)">
    <rect x="0" y="0" width="165" height="110" rx="10" fill="url(#redGrad)" />
    <text x="82" y="35" font-size="15" font-weight="800" fill="#ffffff" text-anchor="middle">DEACTIVATED</text>
    <text x="82" y="58" font-size="11" fill="#fee2e2" text-anchor="middle">Clinicians Blocked</text>
    <text x="82" y="76" font-size="11" fill="#fecaca" text-anchor="middle">Safe Server-Side Lock</text>
    <rect x="12" y="85" width="140" height="16" rx="4" fill="#991b1b" />
    <text x="82" y="97" font-size="9" fill="#ffffff" font-weight="600" text-anchor="middle">Zero Data Deletion</text>
  </g>

  <!-- Reactivation Return Arc -->
  <path d="M 832,250 C 832,380 145,380 145,255" stroke="#16a34a" stroke-width="3" stroke-dasharray="6,4" fill="none" marker-end="url(#arrowGreen)" />
  
  <g transform="translate(380, 360)">
    <rect x="0" y="0" width="220" height="40" rx="6" fill="#f0fdf4" stroke="#16a34a" stroke-width="2" />
    <text x="110" y="24" font-size="12" font-weight="800" fill="#15803d" text-anchor="middle">⚡ Site Admin Renews / Reactivates</text>
  </g>

  <!-- Footer Guarantees -->
  <g transform="translate(60, 425)">
    <rect x="0" y="0" width="840" height="34" rx="6" fill="#f1f5f9" stroke="#e2e8f0" />
    <text x="420" y="22" font-size="12" font-weight="600" fill="#475569" text-anchor="middle">🔒 Core Rule: Deactivation NEVER deletes patient, encounter, billing, or clinical database records.</text>
  </g>
</svg>
SVG;
file_put_contents($outDir . '/08_subscription_lifecycle_flow.svg', $svgFlow);

// 8. Regular Clinician Deactivated Lockout Screen Mockup
$svgLockout = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 900 520" width="100%" height="100%" font-family="system-ui, -apple-system, 'Segoe UI', Roboto, Helvetica, sans-serif">
  <defs>
    <filter id="lockShadow" x="-5%" y="-5%" width="110%" height="110%">
      <feDropShadow dx="0" dy="8" stdDeviation="14" flood-color="#000" flood-opacity="0.08" />
    </filter>
  </defs>

  <rect width="900" height="520" fill="#f8fafc" rx="12" />

  <rect x="40" y="30" width="820" height="460" rx="12" fill="#ffffff" stroke="#e2e8f0" stroke-width="1.5" filter="url(#lockShadow)" />

  <!-- Shield Icon -->
  <g transform="translate(450, 110)">
    <circle cx="0" cy="0" r="42" fill="#fee2e2" />
    <circle cx="0" cy="0" r="32" fill="#fecaca" />
    <path d="M 0,-18 L 16,-10 L 16,6 C 16,16 0,22 0,22 C 0,22 -16,16 -16,6 L -16,-10 Z" fill="#ef4444" />
    <rect x="-4" y="-2" width="8" height="10" rx="1" fill="#ffffff" />
    <circle cx="0" cy="-4" r="3" fill="#ffffff" />
  </g>

  <!-- Text -->
  <text x="450" y="195" font-size="22" font-weight="800" fill="#0f172a" text-anchor="middle">Clinic Account Subscription Inactive</text>
  <text x="450" y="225" font-size="14" fill="#64748b" text-anchor="middle">Access to this CarelioEMR tenant is temporarily suspended pending subscription renewal.</text>

  <!-- Data Safety Assurance Card -->
  <g transform="translate(150, 255)">
    <rect x="0" y="0" width="600" height="85" rx="8" fill="#f0fdf4" stroke="#86efac" stroke-width="1.5" />
    <circle cx="36" cy="42" r="14" fill="#22c55e" />
    <path d="M 30,42 L 34,46 L 42,38" stroke="#ffffff" stroke-width="2.5" fill="none" stroke-linecap="round" />
    <text x="65" y="34" font-size="14" font-weight="800" fill="#15803d">100% Data Preservation Guarantee</text>
    <text x="65" y="55" font-size="12" fill="#166534">All patient charts, clinical notes, appointments, and billing records are completely safe and untouched.</text>
    <text x="65" y="71" font-size="12" fill="#166534">Your site will be instantly restored with all data available upon renewal.</text>
  </g>

  <!-- Resolution Box -->
  <g transform="translate(150, 360)">
    <rect x="0" y="0" width="600" height="65" rx="8" fill="#f8fafc" stroke="#cbd5e1" />
    <text x="25" y="28" font-size="13" font-weight="700" fill="#334155">How to restore access:</text>
    <text x="25" y="48" font-size="12" fill="#64748b">Please notify your clinic's Site Administrator or contact <tspan fill="#0284c7" font-weight="600">billing@carelioemr.com</tspan></text>
    
    <rect x="450" y="16" width="130" height="34" rx="6" fill="#0f172a" />
    <text x="515" y="38" font-size="12" font-weight="700" fill="#ffffff" text-anchor="middle">Back to Login</text>
  </g>
</svg>
SVG;
file_put_contents($outDir . '/09_deactivated_lock_screen.svg', $svgLockout);

echo "All SVGs generated successfully in " . $outDir . "\n";
