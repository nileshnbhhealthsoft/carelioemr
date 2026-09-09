<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CarelioEMR Subscription Confirmation</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f8fafc; color: #0f172a; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 30px auto; background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        .header { background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%); padding: 30px 40px; text-align: center; color: #ffffff; }
        .header h1 { margin: 0; font-size: 24px; font-weight: 900; letter-spacing: -0.5px; }
        .header p { margin: 5px 0 0 0; font-size: 13px; opacity: 0.9; }
        .content { padding: 40px; }
        .greeting { font-size: 18px; font-weight: 700; color: #0f172a; margin-bottom: 12px; }
        .text { font-size: 14px; line-height: 1.6; color: #475569; margin-bottom: 24px; }
        .receipt-card { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 24px; }
        .alert-box { background-color: #fffbeb; border: 1px solid #fef3c7; border-radius: 10px; padding: 14px; font-size: 12px; color: #92400e; font-weight: 600; margin-bottom: 24px; }
        .cta-btn { display: block; width: 100%; text-align: center; background-color: #2563eb; color: #ffffff !important; padding: 14px 0; border-radius: 10px; font-weight: 800; font-size: 14px; text-decoration: none; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25); }
        .footer { background-color: #f1f5f9; padding: 20px 40px; text-align: center; font-size: 11px; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="container">
        
        <div class="header">
            <h1>CarelioEMR Cloud Platform</h1>
            <p>Official Subscription Confirmation &amp; Receipt</p>
        </div>

        <div class="content">
            <div class="greeting">Welcome aboard, {{ $subscription->doctor_name }}!</div>
            <div class="text">
                Thank you for subscribing to <strong>CarelioEMR Cloud Healthcare Suite</strong>. Your monthly subscription has been successfully activated and your dedicated CarelioEMR tenant workstation is provisioned.
            </div>

            <div class="receipt-card">
                <div style="font-size:11px; font-weight:800; text-transform:uppercase; color:#2563eb; letter-spacing:0.5px; margin-bottom:12px;">Subscription Details</div>
                
                <table width="100%" cellpadding="6" cellspacing="0" style="font-size:13px;">
                    <tr>
                        <td style="color:#64748b; font-weight:500;">Subscriber / Doctor:</td>
                        <td style="font-weight:700; color:#0f172a; text-align:right;">{{ $subscription->doctor_name }}</td>
                    </tr>
                    <tr>
                        <td style="color:#64748b; font-weight:500;">Email Address:</td>
                        <td style="font-weight:600; color:#0f172a; text-align:right;">{{ $subscription->email }}</td>
                    </tr>
                    <tr>
                        <td style="color:#64748b; font-weight:500;">Healthcare Practice Type:</td>
                        <td style="font-weight:700; color:#2563eb; text-align:right;">{{ $subscription->practice_type }}</td>
                    </tr>
                    <tr>
                        <td style="color:#64748b; font-weight:500;">Deployment Region:</td>
                        <td style="font-weight:700; color:#0f172a; text-align:right;">{{ $subscription->region }} Region Node</td>
                    </tr>
                    <tr>
                        <td style="color:#64748b; font-weight:500;">Monthly Plan:</td>
                        <td style="font-weight:900; color:#059669; text-align:right;">$80.00 / month</td>
                    </tr>
                    <tr>
                        <td style="color:#64748b; font-weight:500;">CarelioEMR Dedicated Site URL:</td>
                        <td style="font-family:monospace; font-size:11px; color:#2563eb; text-align:right; font-weight:700;">{{ $subscription->openemr_site_url ?? url('/tenant/' . ($subscription->tenant_slug ?? '')) }}</td>
                    </tr>
                    <tr>
                        <td style="color:#64748b; font-weight:500;">Stripe Intent Reference:</td>
                        <td style="font-family:monospace; font-size:11px; color:#475569; text-align:right;">{{ $subscription->stripe_payment_intent_id ?? 'pi_3P98aF123bc45' }}</td>
                    </tr>
                </table>
            </div>

            <div class="alert-box">
                ⚠️ <strong>Important Note on Setup Costs:</strong><br>
                Please note that initial data migration, HL7/FHIR integration, and custom staff onboarding setup costs will be billed separately based on your practice size.
            </div>

            <!-- DYNAMIC TENANT WORKSTATION SITE LINK ON PORT 8000 -->
            <a href="{{ $subscription->openemr_site_url ?? url('/tenant/' . ($subscription->tenant_slug ?? '')) }}" class="cta-btn">Access Your Dedicated Workstation &rarr;</a>
        </div>

        <div class="footer">
            &copy; 2026 CarelioEMR Cloud Healthcare Suite. All rights reserved.<br>
            256-bit TLS Encrypted • ISO 27001 &amp; HIPAA Compliant Infrastructure
        </div>

    </div>
</body>
</html>
