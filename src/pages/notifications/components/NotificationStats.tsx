import type { Notification } from "@/api/v2/notifications/notifications.types";
import {
  getNotificationCategory,
  categoryConfig,
  type NotificationCategory,
} from "../utils/notificationCategory";

interface NotificationStatsProps {
  notifications: Notification[];
  meta: { total: number; read: number; unread: number };
}

export function NotificationStats({ notifications, meta }: NotificationStatsProps) {
  const total = meta.total;
  const unread = meta.unread;
  const high = notifications.filter(
    (n) => (n.priority === "high" || n.priority === "urgent") && !n.read_at
  ).length;

  const categoryCounts = notifications.reduce<
    Record<NotificationCategory, { total: number; unread: number }>
  >((acc, n) => {
    const cat = getNotificationCategory(n);
    if (!acc[cat]) acc[cat] = { total: 0, unread: 0 };
    acc[cat].total += 1;
    if (!n.read_at) acc[cat].unread += 1;
    return acc;
  }, {} as Record<NotificationCategory, { total: number; unread: number }>);

  const topCategories = (
    Object.entries(categoryCounts) as [NotificationCategory, { total: number; unread: number }][]
  )
    .sort((a, b) => b[1].unread - a[1].unread)
    .slice(0, 4);

  return (
    <div className="space-y-4">
      <div className="grid grid-cols-3 gap-4">
        <div
          className="rounded-xl p-4 flex items-center gap-3"
          style={{ background: "linear-gradient(135deg, #0C1A3E 0%, #1E3A7B 100%)" }}
        >
          <div className="w-10 h-10 flex items-center justify-center rounded-xl bg-white/10 shrink-0">
            <i className="ri-notification-3-line text-white text-lg" />
          </div>
          <div>
            <p className="text-white/60 text-xs">إجمالي الإشعارات</p>
            <p className="text-white font-black text-2xl leading-tight">{total}</p>
          </div>
        </div>

        <div
          className="rounded-xl p-4 flex items-center gap-3"
          style={{ background: "linear-gradient(135deg, #EF4444 0%, #DC2626 100%)" }}
        >
          <div className="w-10 h-10 flex items-center justify-center rounded-xl bg-white/20 shrink-0">
            <i className="ri-mail-unread-line text-white text-lg" />
          </div>
          <div>
            <p className="text-white/80 text-xs">غير مقروءة</p>
            <p className="text-white font-black text-2xl leading-tight">{unread}</p>
          </div>
        </div>

        <div
          className="rounded-xl p-4 flex items-center gap-3"
          style={{ background: "linear-gradient(135deg, #F59E0B 0%, #D97706 100%)" }}
        >
          <div className="w-10 h-10 flex items-center justify-center rounded-xl bg-white/20 shrink-0">
            <i className="ri-alert-line text-white text-lg" />
          </div>
          <div>
            <p className="text-white/80 text-xs">تنبيهات عاجلة</p>
            <p className="text-white font-black text-2xl leading-tight">{high}</p>
          </div>
        </div>
      </div>

      {topCategories.length > 0 && (
        <div className="grid grid-cols-4 gap-3">
          {topCategories.map(([cat, counts]) => {
            const cfg = categoryConfig[cat];
            return (
              <div
                key={cat}
                className="rounded-xl p-3 flex items-center gap-3 cursor-default transition-all duration-150 hover:shadow-sm"
                style={{ background: cfg.bg, border: `1px solid ${cfg.color}22` }}
              >
                <div
                  className="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
                  style={{ background: `${cfg.color}22` }}
                >
                  <i className={`${cfg.icon} text-sm`} style={{ color: cfg.color }} />
                </div>
                <div className="min-w-0">
                  <p className="text-xs font-bold text-slate-700 truncate">{cfg.label}</p>
                  <p className="text-xs text-slate-500 mt-0.5">
                    <span className="font-black" style={{ color: cfg.color }}>
                      {counts.unread}
                    </span>
                    <span className="text-slate-400"> / {counts.total}</span>
                  </p>
                </div>
              </div>
            );
          })}
        </div>
      )}
    </div>
  );
}
