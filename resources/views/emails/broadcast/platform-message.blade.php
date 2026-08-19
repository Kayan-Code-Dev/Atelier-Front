@extends('emails.layouts.luxury', [
  'subject' => $title,
  'preheader' => \Illuminate\Support\Str::limit(strip_tags($message), 90),
])

@section('content')
  <p style="margin:0 0 8px;font-size:13px;color:#0284c7;font-weight:700;">إشعار من إدارة DressnMore</p>
  @if(!empty($tenantName))
    <p style="margin:0 0 10px;font-size:13px;color:#64748b;">إلى أتيليه: <strong style="color:#0f172a;">{{ $tenantName }}</strong></p>
  @endif
  <h1 style="margin:0 0 16px;font-size:22px;line-height:1.45;color:#0f172a;font-weight:800;">{{ $title }}</h1>

  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 8px;">
    <tr>
      <td style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:14px;padding:18px 20px;">
        <div style="font-size:15px;line-height:1.9;color:#334155;white-space:pre-wrap;">{{ $message }}</div>
      </td>
    </tr>
  </table>

  <p style="margin:20px 0 0;font-size:13px;line-height:1.7;color:#64748b;">
    يمكنك أيضاً متابعة الإشعارات من داخل لوحة التحكم الخاصة بك.
  </p>
@endsection
