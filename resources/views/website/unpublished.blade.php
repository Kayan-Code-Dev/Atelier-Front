<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>الموقع قيد التجهيز</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@500;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0; min-height: 100vh; display: grid; place-items: center;
            font-family: Cairo, sans-serif; color: #0f172a;
            background: linear-gradient(160deg, #0C1A3E 0%, #1E3A7B 55%, #C2964A 100%);
        }
        .card {
            width: min(480px, calc(100% - 2rem));
            background: #fff; border-radius: 18px; padding: 2rem;
            box-shadow: 0 24px 60px rgba(0,0,0,.25); text-align: center;
        }
        h1 { margin: 0 0 .75rem; font-size: 1.5rem; }
        p { margin: 0; opacity: .75; line-height: 1.7; }
        .badge {
            display: inline-block; margin-bottom: 1rem; padding: .35rem .75rem;
            border-radius: 999px; background: #fff7ed; color: #9a3412; font-size: .85rem; font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="badge">{{ ($status ?? 'draft') === 'maintenance' ? 'صيانة' : 'غير منشور بعد' }}</div>
        <h1>{{ $site_name ?: 'الموقع قيد التجهيز' }}</h1>
        <p>سيتم تفعيل الموقع للعامة بعد نشره من لوحة التحكم.</p>
    </div>
</body>
</html>
