<!DOCTYPE html>
<html>
<head>
    <meta charset=utf-8>
    <meta name=viewport content=width=device-width, initial-scale=1.0>
    <title>CarelioEMR Registration Received</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f8fafc; color: #0f172a; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 30px auto; background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        .header { background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%); padding: 30px 40px; text-align: center; color: #ffffff; }
        .header h1 { margin: 0; font-size: 24px; font-weight: 900; letter-spacing: -0.5px; }
        .header p { margin: 5px 0 0 0; font-size: 13px; opacity: 0.9; }
        .content { padding: 40px; }
        .greeting { font-size: 18px; font-weight: 700; color: #0f172a; margin-bottom: 16px; }
        .message-card { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 24px; font-size: 15px; line-height: 1.6; color: #1e293b; }
        .summary-card { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 24px; }
        .footer { background-color: #f1f5f9; padding: 20px 40px; text-align: center; font-size: 11px; color: #94a3b8; }
    </style>
</head>
<body>
    <div class=container>
        
        <div class=header>
            <h1>CarelioEMR Cloud Platform</h1>
            <p>Registration Acknowledgement</p>
        </div>

        <div class="content">
            <div class="greeting">Dear {{ $subscription->doctor_name }},</div>

            <div class="message-card">
                Thank you for registering with us. Your setup will be ready within 24 hours, and we will notify you via email once your setup is ready.
            </div>

            <div class="summary-card">
                <div style="font-size:11px; font-weight:800; text-transform:uppercase; color:#2563eb; letter-spacing:0.5px; margin-bottom:12px;">Registration Overview</div>
                <table width="100%" cellpadding="6" cellspacing="0" style="font-size:13px;">
                    <tr>
                        <td style="color:#64748b; font-weight:500;">Subscriber:</td>
                        <td style="font-weight:700; color:#0f172a; text-align:right;">{{ $subscription->doctor_name }}</td>
                    </tr>
                    <tr>
                        <td style="color:#64748b; font-weight:500;">Email Address:</td>
                        <td style="font-weight:600; color:#0f172a; text-align:right;">{{ $subscription->email }}</td>
                    </tr>
                    <tr>
                        <td style="color:#64748b; font-weight:500;">Practice Type:</td>
                        <td style="font-weight:700; color:#2563eb; text-align:right;">{{ $subscription->practice_type }}</td>
                    </tr>
                    <tr>
                        <td style="color:#64748b; font-weight:500;">Deployment Region:</td>
                        <td style="font-weight:700; color:#0f172a; text-align:right;">{{ $subscription->region }} Region Node</td>
                    </tr>
                </table>
            </div>
        </div>

        <div class=footer>
            &copy; 2026 CarelioEMR Cloud Healthcare Suite. All rights reserved.<br>
            256-bit TLS Encrypted • ISO 27001 &amp; HIPAA Compliant Infrastructure
        </div>

    </div>
</body>
</html>
