{{-- Demo / trial account expired --}}
@extends('emails.layouts.luxury', [
  'subject' => $title ?? 'انتهى تفعيل حسابك التجريبي',
  'preheader' => 'انتهت فترة التجربة — تواصل معنا للتجديد',
])

@section('content')
  <p style="margin:0 0 8px;font-size:13px;color:#b45309;font-weight:700;">انتهاء التفعيل</p>
  <h1 style="margin:0 0 14px;font-size:24px;line-height:1.4;color:#0f172a;font-weight:800;">
    {{ $tenantName }}
  </h1>
  <p style="margin:0 0 20px;font-size:15px;line-height:1.8;color:#475569;">
    {{ $body }}
  </p>
  <p style="margin:0;font-size:13px;line-height:1.7;color:#0f172a;background:#fff7ed;border:1px solid #fed7aa;border-radius:12px;padding:12px 14px;">
    للتجديد أو التحويل إلى اشتراك كامل، يرجى التواصل مع فريق DressnMore عبر البريد
    <a href="mailto:info@dressnmore.it.com" style="color:#0284c7;font-weight:700;">info@dressnmore.it.com</a>
  </p>
@endsection
