@extends('emails.layouts.luxury', [
  'subject' => 'طلب باقة جديد #'.$requestId.' — DressnMore',
  'preheader' => 'طلب باقة جديد بانتظار مراجعتك',
])

@section('content')
  <p style="margin:0 0 8px;font-size:13px;color:#d97706;font-weight:700;">تنبيه للإدارة</p>
  <h1 style="margin:0 0 14px;font-size:22px;line-height:1.45;color:#0f172a;font-weight:800;">طلب باقة جديد بانتظار المراجعة</h1>
  <p style="margin:0 0 22px;font-size:14px;line-height:1.8;color:#475569;">
    تم استلام طلب اشتراك جديد مع إثبات دفع. راجع التفاصيل أدناه من لوحة الإدارة.
  </p>

  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;margin:0 0 22px;">
    @foreach([
      'رقم الطلب' => '#'.$requestId,
      'الاسم' => $name,
      'البريد' => $email,
      'الهاتف' => $phone,
      'الباقة' => $planTitle,
      'بوابة الدفع' => $gatewayName,
      'مرجع التحويل' => $reference,
    ] as $label => $value)
      <tr>
        <td style="padding:12px 16px;background:#f8fafc;border-bottom:1px solid #e2e8f0;width:38%;font-size:12px;color:#64748b;font-weight:700;">{{ $label }}</td>
        <td style="padding:12px 16px;background:#ffffff;border-bottom:1px solid #e2e8f0;font-size:13px;color:#0f172a;font-weight:600;direction:ltr;text-align:right;">{{ $value }}</td>
      </tr>
    @endforeach
  </table>

  <p style="margin:0;font-size:13px;line-height:1.7;color:#64748b;">
    افتح صفحة <strong>طلبات الباقات</strong> في لوحة الأدمن للموافقة أو الرفض.
  </p>
@endsection
