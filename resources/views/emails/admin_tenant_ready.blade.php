<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CarelioEMR Tenant Ready for Review</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f8fafc; color: #0f172a; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 30px auto; background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        .header { background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); padding: 30px 40px; text-align: center; color: #ffffff; border-bottom: 3px solid #2563eb; }
        .header h1 { margin: 0; font-size: 22px; font-weight: 900; letter-spacing: -0.5px; }
        .header p { margin: 6px 0 0 0; font-size: 13px; color: #94a3b8; }
        .content { padding: 36px 40px; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 9999px; font-size: 11px; font-weight: 800; background-color: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; margin-bottom: 16px; }
        .status-box { background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 18px; margin-bottom: 24px; font-size: 14px; font-weight: 700; color: #15803d; line-height: 1.5; }
        .table-card { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 24px; }
        .table-title { font-size: 11px; font-weight: 800; text-transform: uppercase; color: #475569; letter-spacing: 0.5px; margin-bottom: 12px; }
        .cta-btn { display: block; width: 100%; text-align: center; background-color: #2563eb; color: #ffffff !important; padding: 14px 0; border-radius: 10px; font-weight: 800; font-size: 14px; text-decoration: none; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25); }
        .footer { background-color: #f1f5f9; padding: 20px 40px; text-align: center; font-size: 11px; color: #94a3b8; }
    </style>
</head>
<body>

    <div class="container">
        
        <div class="header">
            <h1>CarelioEMR Internal Operations</h1>
            <p>Admin Notification &bull; Tenant Provisioning Complete</p>
        </div>


        <div class="content">
            <span class="badge">&#x2705; Provisioning Complete</span>


            <div class="status-box">
                Tenant provisioning completed successfully and is ready for admin review.
            </div>

            <div class="table-card">
                <div class="table-title">Tenant Audit Summary</div>
                <table width="100%" cellpadding="6" cellspacing="0" style="font-size:13px;">
                    <tr>
                        <td style="color:#64748b; font-weight:500;">Subscriber / Doctor:</td>
                        <td style="font-weight:700; color:#0f172a; text-align:right;">{{ $subscription->doctor_name }}</td>
                    </tr>
                    <tr>
                        <td style="color:#64748b; font-weight:500;">Customer Email:</td>
                        <td style="font-weight:600; color:#0f172a; text-align:right;">{{ $subscription->email }}</td>
                    </tr>
                    <tr>
                        <td style="color:#64748b; font-weight:500;">Practice Type:</td>
                        <td style="font-weight:700; color:#2563eb; text-align:right;">{{ $subscription->practice_type }}</td>
                    </tr>
                    <tr>
                        <td style="color:#64748b; font-weight:500;">Region:</td>
                        <td style="font-weight:700; color:#0f172a; text-align:right;">{{ $subscription->region }} Region Node</td>
                    </tr>
                    <tr>
                        <td style="color:#64748b; font-weight:500;">Tenant Slug:</td>
                        <td style="font-family:monospace; font-weight:700; color:#0f172a; text-align:right;">{{ $subscription->tenant_slug }}</td>
                    </tr>
                    <tr>
                        <td style="color:#64748b; font-weight:500;">Tenant Site URL:</td>
                        <td style="font-family:monospace; font-size:11px; color:#2563eb; text-align:right; font-weight:700;">{{ $subscription->getCanonicalSiteUrl() }}</td>
                    </tr>
                    <tr>
                        <td style="color:#64748b; font-weight:500;">Provisioning Status:</td>
                        <td style="font-weight:800; color:#059669; text-align:right;">{{ strtoupper($subscription->provision_status) }} (Health Checks Passed)</td>
                    </tr>
                </table>
            </div>

            <a href="{{ url('/admin/dashboard') }}" class="cta-btn">Open Admin Dashboard for Review &rarr;</a>
        </div>


        <div class="footer">
            CarelioEMR Automated Multi-Tenant Provisioner &bull; Internal Administrator Dispatch
        </div>

    </div>
</body>
</html>