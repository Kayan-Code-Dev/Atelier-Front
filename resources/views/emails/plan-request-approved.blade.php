{{-- Account activated after plan approval --}}
@extends('emails.layouts.luxury', [
  'subject' => 'تم تفعيل حسابك على DressnMore',
  'preheader' => 'حسابك جاهز — بيانات الدخول داخل الرسالة',
])

@section('content')
  <p style="margin:0 0 8px;font-size:13px;color:#059669;font-weight:700;">تم التفعيل بنجاح</p>
  <h1 style="margin:0 0 14px;font-size:24px;line-height:1.4;color:#0f172a;font-weight:800;">مرحباً {{ $name }}</h1>
  <p style="margin:0 0 20px;font-size:15px;line-height:1.8;color:#475569;">
    تمت الموافقة على طلب باقة <strong>{{ $planName }}</strong> وتفعيل حسابك على نظام DressnMore.
    يمكنك تسجيل الدخول الآن باستخدام البيانات التالية:
  </p>

  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;margin:0 0 22px;">
    <tr>
      <td style="padding:12px 16px;background:#f8fafc;border-bottom:1px solid #e2e8f0;width:36%;font-size:12px;color:#64748b;font-weight:700;">البريد الإلكتروني</td>
      <td style="padding:12px 16px;background:#ffffff;border-bottom:1px solid #e2e8f0;font-size:14px;color:#0f172a;font-weight:700;direction:ltr;text-align:right;">{{ $email }}</td>
    </tr>
    <tr>
      <td style="padding:12px 16px;background:#f8fafc;border-bottom:1px solid #e2e8f0;font-size:12px;color:#64748b;font-weight:700;">كلمة المرور</td>
      <td style="padding:12px 16px;background:#ffffff;border-bottom:1px solid #e2e8f0;font-size:14px;color:#0f172a;font-weight:700;direction:ltr;text-align:right;font-family:'Courier New',monospace;">{{ $password }}</td>
    </tr>
    <tr>
      <td style="padding:12px 16px;background:#f8fafc;font-size:12px;color:#64748b;font-weight:700;">رابط الدخول</td>
      <td style="padding:12px 16px;background:#ffffff;font-size:13px;direction:ltr;text-align:right;">
        <a href="{{ $loginUrl }}" style="color:#0284c7;text-decoration:none;font-weight:700;">{{ $loginUrl }}</a>
      </td>
    </tr>
  </table>

  <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 22px;">
    <tr>
      <td style="border-radius:12px;background:linear-gradient(135deg,#1e3a8a,#0284c7);">
        <a href="{{ $loginUrl }}" style="display:inline-block;padding:14px 28px;color:#ffffff;text-decoration:none;font-weight:800;font-size:14px;">تسجيل الدخول الآن</a>
      </td>
    </tr>
  </table>

  <p style="margin:0;font-size:13px;line-height:1.7;color:#b45309;background:#fffbeb;border:1px solid #fde68a;border-radius:12px;padding:12px 14px;">
    احفظ كلمة المرور في مكان آمن، ويُفضّل تغييرها من إعدادات الحساب بعد أول دخول.
  </p>
@endsection
