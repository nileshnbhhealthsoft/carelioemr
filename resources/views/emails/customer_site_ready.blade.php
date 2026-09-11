<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CarelioEMR Setup Ready</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f8fafc; color: #0f172a; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 30px auto; background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        .header { background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%); padding: 30px 40px; text-align: center; color: #ffffff; }
        .header h1 { margin: 0; font-size: 24px; font-weight: 900; letter-spacing: -0.5px; }
        .header p { margin: 5px 0 0 0; font-size: 13px; opacity: 0.9; }
        .content { padding: 40px; }
        .greeting { font-size: 18px; font-weight: 700; color: #0f172a; margin-bottom: 16px; }
        .status-box { background-color: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 12px; padding: 20px; margin-bottom: 24px; font-size: 15px; line-height: 1.6; color: #065666; font-weight: 700; }
        .table-card { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 24px; }
        .table-title { font-size: 11px; font-weight: 800; text-transform: uppercase; color: #2563eb; letter-spacing: 0.5px; margin-bottom: 12px; }
        .cta-btn { display: block; width: 100%; text-align: center; background-color: #2563eb; color: #ffffff !important; padding: 14px 0; border-radius: 10px; font-weight: 800; font-size: 14px; text-decoration: none; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25); margin-top: 12px; }
        .notice { font-size: 12px; color: #64748b; line-height: 1.5; margin-top: 18px; text-align: center; }
        .footer { background-color: #f1f5f9; padding: 20px 40px; text-align: center; font-size: 11px; color: #94a3b8; }
    </style>
</head>
<body>

    <div class="container">
        
        <div class="header">
            <h1>CarelioEMR Cloud Platform</h1>
            <p>Official Welcome &bull; Dedicated Workstation Activated</p>
        </div>

        <div class="content">
            @php
                $targetSiteUrl = $siteUrl ?? $subscription->getCanonicalSiteUrl();
            @endphp
            <div class="greeting">Dear {{ $subscription->doctor_name }},</div>

            <div class="status-box">
                Your CarelioEMR setup is ready.
            </div>

            <div class="table-card">
                <div class="table-title">Your Dedicated Website &amp; Access Details</div>
                <table width="100%" cellpadding="6" cellspacing="0" style="font-size:13px;">
                    <tr>
                        <td style="color:#64748b; font-weight:500;">Subscriber / Doctor:</td>
                        <td style="font-weight:700; color:#0f172a; text-align:right;">{{ $subscription->doctor_name }}</td>
                    </tr>
                    <tr>
                        <td style="color:#64748b; font-weight:500;">Practice Type:</td>
                        <td style="font-weight:700; color:#2563eb; text-align:right;">{{ $subscription->practice_type }}</td>
                    </tr>
                    <tr>
                        <td style="color:#64748b; font-weight:500;">Deployment Region:</td>
                        <td style="font-weight:700; color:#0f172a; text-align:right;">{{ $subscription->region }} Region Node</td>
                    </tr>
                    <tr>
                        <td style="color:#64748b; font-weight:500;">Dedicated Site URL:</td>
                        <td style="font-family:monospace; font-size:11px; color:#2563eb; text-align:right; font-weight:700;">
                            <a href="{{ $targetSiteUrl }}" style="color:#2563eb; text-decoration:none;">
                                {{ $targetSiteUrl }}
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td style="color:#64748b; font-weight:500;">Clinician Username:</td>
                        <td style="font-family:monospace; font-size:12px; color:#0f172a; text-align:right; font-weight:700;">
                            {{ \Illuminate\Support\Str::slug($subscription->doctor_name, '_') ?: ('doctor_' . $subscription->id) }}
                        </td>
                    </tr>
                    <tr>
                        <td style="color:#64748b; font-weight:500; vertical-align:top;">Password:</td>
                        <td style="font-size:12px; color:#475569; text-align:right; font-weight:500;">
                            Use your initial secure onboarding password.
                        </td>
                    </tr>
                </table>
            </div>

            <a href="{{ $targetSiteUrl }}" class="cta-btn">
                Log In to Your CarelioEMR Workstation &rarr;
            </a>

            <p class="notice">
                For clinical assistance or onboarding questions, email our clinical support desk at <strong>admin@carelioemr.com</strong>.
            </p>
        </div>

        <div class="footer">
            &copy; 2026 CarelioEMR Cloud Healthcare Suite. All rights reserved.<br>
            HIPAA &amp; Regional Standards Compliant Dedicated Clinical Infrastructure
        </div>

    </div>
</body>
</html>