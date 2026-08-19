<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: "DejaVu Sans", dejavusans, Arial, sans-serif;
            font-size: 11px;
            color: #111827;
            direction: rtl;
            unicode-bidi: embed;
            text-align: right;
        }
        .header {
            border-bottom: 2px solid #1a3a6d;
            padding-bottom: 10px;
            margin-bottom: 12px;
        }
        .brand {
            color: #1a3a6d;
            font-size: 12px;
            font-weight: bold;
            margin: 0 0 4px;
        }
        h1 {
            font-size: 18px;
            margin: 0;
            color: #0f172a;
        }
        .meta {
            margin: 8px 0 14px;
            color: #475569;
            font-size: 10px;
        }
        .meta-item {
            display: inline-block;
            margin-left: 14px;
            margin-bottom: 4px;
        }
        .meta-label {
            color: #64748b;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        th, td {
            border: 1px solid #cbd5e1;
            padding: 7px 8px;
            text-align: right;
            vertical-align: middle;
            word-wrap: break-word;
        }
        th {
            background-color: #1a3a6d;
            color: #ffffff;
            font-weight: bold;
            font-size: 10px;
        }
        tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .footer {
            margin-top: 12px;
            font-size: 9px;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
        }
        .empty {
            text-align: center;
            color: #64748b;
            padding: 18px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">DressnMore</div>
        <h1>{{ $title }}</h1>
    </div>

    <div class="meta">
        <span class="meta-item"><span class="meta-label">تاريخ التصدير:</span> {{ $generatedAt ?? now()->format('Y-m-d H:i') }}</span>
        <span class="meta-item"><span class="meta-label">عدد الصفوف:</span> {{ $rowCount ?? count($rows) }}</span>
        @foreach(($meta ?? []) as $label => $value)
            <span class="meta-item">
                <span class="meta-label">{{ $label }}:</span>
                {{ is_scalar($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE) }}
            </span>
        @endforeach
    </div>

    <table>
        <thead>
            <tr>
                @foreach($headers as $header)
                    <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    @foreach($row as $cell)
                        <td>{{ is_scalar($cell) || $cell === null ? ($cell ?? '') : json_encode($cell, JSON_UNESCAPED_UNICODE) }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td class="empty" colspan="{{ max(count($headers), 1) }}">لا توجد بيانات للتصدير</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        تم إنشاء هذا الملف تلقائياً من نظام DressnMore — العرض من اليمين إلى اليسار.
    </div>
</body>
</html>
