<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CarelioEMR Internal Admin Credentials</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; background-color: #f8fafc; color: #0f172a; margin: 0; padding: 0; }
        .container { max-width: 620px; margin: 28px auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; }
        .header { background: #0f172a; color: #ffffff; padding: 24px 32px; }
        .header h1 { margin: 0; font-size: 20px; }
        .header p { margin: 6px 0 0; color: #cbd5e1; font-size: 13px; }
        .content { padding: 28px 32px; }
        .notice { background: #fff7ed; border: 1px solid #fed7aa; color: #9a3412; padding: 14px 16px; border-radius: 8px; font-size: 13px; font-weight: 700; margin-bottom: 20px; }
        .table-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; margin-bottom: 18px; }
        .table-title { font-size: 11px; font-weight: 800; text-transform: uppercase; color: #475569; margin-bottom: 10px; letter-spacing: 0.4px; }
        td { font-size: 13px; padding: 7px 4px; vertical-align: top; }
        .label { color: #64748b; font-weight: 600; }
        .value { color: #0f172a; font-weight: 700; text-align: right; }
        .mono { font-family: Consolas, Monaco, monospace; }
        .secret { background: #e0f2fe; color: #075985; padding: 3px 7px; border-radius: 4px; letter-spacing: 0.4px; }
        .footer { background: #f1f5f9; padding: 16px 32px; font-size: 11px; color: #64748b; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Internal Administrator Created</h1>
            <p>CarelioEMR tenant provisioning credentials</p>
        </div>

        <div class="content">
            <div class="notice">
                Internal use only. Do not forward these credentials to the subscribed customer or site users.
            </div>

            <div class="table-card">
                <div class="table-title">Tenant</div>
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td class="label">Subscriber / Doctor:</td>
                        <td class="value">{{ $subscription->doctor_name }}</td>
                    </tr>
                    <tr>
                        <td class="label">Customer Email:</td>
                        <td class="value">{{ $subscription->email }}</td>
                    </tr>
                    <tr>
                        <td class="label">Tenant Slug:</td>
                        <td class="value mono">{{ $subscription->tenant_slug }}</td>
                    </tr>
                    <tr>
                        <td class="label">Site URL:</td>
                        <td class="value mono"><a href="{{ $siteUrl }}">{{ $siteUrl }}</a></td>
                    </tr>
                </table>
            </div>

            <div class="table-card">
                <div class="table-title">Internal Admin Login</div>
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td class="label">Name:</td>
                        <td class="value">{{ $credentials['full_name'] ?? 'Carelio Administrator' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Username:</td>
                        <td class="value mono">{{ $credentials['username'] }}</td>
                    </tr>
                    <tr>
                        <td class="label">Temporary Password:</td>
                        <td class="value mono"><span class="secret">{{ $credentials['password'] }}</span></td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="footer">
            This message is generated only for configured internal CarelioEMR operators.
        </div>
    </div>
</body>
</html>
