@extends('emails.layouts.luxury', [
  'subject' => 'وصلنا طلبك — DressnMore',
  'preheader' => 'تم استلام طلب التوظيف رقم '.$applicationNumber,
])

@section('content')
  <p style="margin:0 0 8px;font-size:13px;color:#0284c7;font-weight:700;">فريق التوظيف</p>
  <h1 style="margin:0 0 14px;font-size:22px;line-height:1.45;color:#0f172a;font-weight:800;">وصلنا طلبك</h1>
  <p style="margin:0 0 22px;font-size:14px;line-height:1.8;color:#475569;">
    مرحباً {{ $name }}،<br>
    شكراً لاهتمامك بالانضمام إلى فريق DressnMore.
    سنراجع طلبك، وإذا كان هناك توافق سنتواصل معك.
  </p>

  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;margin:0 0 22px;">
    <tr>
      <td style="padding:12px 16px;background:#f8fafc;border-bottom:1px solid #e2e8f0;width:38%;font-size:12px;color:#64748b;font-weight:700;">رقم الطلب</td>
      <td style="padding:12px 16px;background:#ffffff;border-bottom:1px solid #e2e8f0;font-size:13px;color:#0f172a;font-weight:700;direction:ltr;text-align:right;">{{ $applicationNumber }}</td>
    </tr>
    <tr>
      <td style="padding:12px 16px;background:#f8fafc;font-size:12px;color:#64748b;font-weight:700;">الدور</td>
      <td style="padding:12px 16px;background:#ffffff;font-size:13px;color:#0f172a;font-weight:600;">{{ $jobTitle ?: 'طلب عام' }}</td>
    </tr>
  </table>

  <p style="margin:0;font-size:13px;line-height:1.7;color:#64748b;">
    هذه رسالة تأكيد فقط. لا تحتاج إلى الرد عليها.
  </p>
@endsection
