{{-- Luxurious RTL email shell for DressnMore --}}
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>{{ $subject ?? 'DressnMore' }}</title>
</head>
<body style="margin:0;padding:0;background:#0b1220;font-family:Tahoma,'Segoe UI',Arial,sans-serif;-webkit-text-size-adjust:100%;">
  <div style="display:none;max-height:0;overflow:hidden;opacity:0;">
    {{ $preheader ?? '' }}
  </div>
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:linear-gradient(180deg,#0b1220 0%,#111827 100%);padding:32px 12px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:600px;background:#ffffff;border-radius:20px;overflow:hidden;box-shadow:0 20px 50px rgba(0,0,0,0.35);">
          <tr>
            <td style="background:linear-gradient(135deg,#0f172a 0%,#1e3a8a 55%,#0369a1 100%);padding:28px 32px;">
              <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                <tr>
                  <td align="right" style="vertical-align:middle;">
                    <div style="font-size:22px;font-weight:800;color:#ffffff;letter-spacing:0.5px;">DressnMore</div>
                    <div style="font-size:12px;color:rgba(255,255,255,0.75);margin-top:4px;">نظام إدارة الأتيليه الاحترافي</div>
                  </td>
                  <td align="left" style="vertical-align:middle;width:56px;">
                    <div style="width:48px;height:48px;border-radius:14px;background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.25);text-align:center;line-height:48px;color:#67e8f9;font-size:22px;font-weight:700;">✂</div>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td style="height:4px;background:linear-gradient(90deg,#f59e0b,#22d3ee,#3b82f6);"></td>
          </tr>
          <tr>
            <td style="padding:36px 32px 28px;background:#ffffff;color:#0f172a;">
              @yield('content')
            </td>
          </tr>
          <tr>
            <td style="padding:22px 32px 28px;background:#f8fafc;border-top:1px solid #e2e8f0;">
              <p style="margin:0 0 8px;font-size:12px;color:#64748b;line-height:1.7;text-align:center;">
                هذه رسالة تلقائية من منصة <strong style="color:#1e3a8a;">DressnMore</strong>
              </p>
              <p style="margin:0;font-size:11px;color:#94a3b8;line-height:1.6;text-align:center;">
                للدعم: <a href="mailto:info@dressnmore.it.com" style="color:#0284c7;text-decoration:none;">info@dressnmore.it.com</a>
                &nbsp;|&nbsp;
                <a href="https://dressnmore.it.com" style="color:#0284c7;text-decoration:none;">dressnmore.it.com</a>
              </p>
            </td>
          </tr>
        </table>
        <p style="margin:18px 0 0;font-size:11px;color:#64748b;text-align:center;">© {{ date('Y') }} DressnMore. جميع الحقوق محفوظة.</p>
      </td>
    </tr>
  </table>
</body>
</html>
