@extends('emails.layouts.luxury', [
  'subject' => 'رمز التحقق لطلب باقة DressnMore',
  'preheader' => 'رمز التحقق الخاص بك: '.$otp,
])

@section('content')
  <p style="margin:0 0 8px;font-size:13px;color:#0284c7;font-weight:700;letter-spacing:0.3px;">التحقق من البريد</p>
  <h1 style="margin:0 0 14px;font-size:24px;line-height:1.4;color:#0f172a;font-weight:800;">مرحباً {{ $greeting }}</h1>
  <p style="margin:0 0 22px;font-size:15px;line-height:1.8;color:#475569;">
    لإكمال طلب الباقة على DressnMore، استخدم رمز التحقق التالي. الرمز صالح لمدة <strong>10 دقائق</strong> فقط.
  </p>

  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px;">
    <tr>
      <td align="center" style="background:linear-gradient(135deg,#eff6ff,#ecfeff);border:1px solid #bae6fd;border-radius:16px;padding:22px 16px;">
        <div style="font-size:12px;color:#0369a1;margin-bottom:10px;font-weight:600;">رمز التحقق</div>
        <div style="font-size:34px;letter-spacing:10px;font-weight:800;color:#0c4a6e;font-family:'Courier New',monospace;direction:ltr;">{{ $otp }}</div>
      </td>
    </tr>
  </table>

  <p style="margin:0;font-size:13px;line-height:1.7;color:#64748b;">
    إذا لم تطلب هذا الرمز، يمكنك تجاهل الرسالة بأمان. لا تشارك الرمز مع أي شخص.
  </p>
@endsection
