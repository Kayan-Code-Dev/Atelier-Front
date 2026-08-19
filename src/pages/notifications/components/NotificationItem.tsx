import { useNavigate } from "react-router";
import type { Notification } from "@/api/v2/notifications/notifications.types";
import {
  getNotificationCategory,
  categoryConfig,
  getNotificationDisplay,
} from "../utils/notificationCategory";
import { normalizeNotificationActionUrl } from "@/utils/notificationActionUrl";
import { formatDistanceToNow } from "date-fns";
import { ar } from "date-fns/locale";

const priorityBadge: Record<string, { label: string; color: string; bg: string }> = {
  urgent: { label: "عاجل", color: "#EF4444", bg: "#FEE2E2" },
  high: { label: "عاجل", color: "#EF4444", bg: "#FEE2E2" },
  normal: { label: "متوسط", color: "#F59E0B", bg: "#FEF3C7" },
  low: { label: "عادي", color: "#64748B", bg: "#F1F5F9" },
};

interface NotificationItemProps {
  notification: Notification;
  onMarkRead: (id: number) => void;
  onDelete: (id: number) => void;
}

export function NotificationItem({
  notification,
  onMarkRead,
  onDelete,
}: NotificationItemProps) {
  const navigate = useNavigate();
  const isRead = !!notification.read_at;
  const cfg = categoryConfig[getNotificationCategory(notification)];
  const { icon, color } = getNotificationDisplay(notification);
  const pBadge = priorityBadge[notification.priority ?? "normal"] ?? priorityBadge.normal;

  const timeAgo = formatDistanceToNow(new Date(notification.created_at), {
    addSuffix: true,
    locale: ar,
  });

  const handleAction = () => {
    const url = normalizeNotificationActionUrl(notification.action_url, notification.metadata);
    if (url && url !== "/") navigate(url);
  };

  const title =
    notification.title?.trim() ||
    (notification.reference_type?.includes("SupplierOrder") ||
    (notification.type ?? "").toLowerCase().includes("supplier")
      ? "أمر توريد جديد"
      : "إشعار");

  return (
    <div
      className={[
        "group relative rounded-xl p-4 transition-all duration-150 cursor-default",
        isRead ? "bg-white border border-slate-100" : "border border-slate-200",
      ].join(" ")}
      style={
        isRead ? undefined : { background: `${color}08`, borderColor: `${color}30` }
      }
    >
      {!isRead && (
        <span
          className="absolute right-4 top-4 w-2 h-2 rounded-full shrink-0"
          style={{ background: color }}
        />
      )}

      <div className="flex items-start gap-3">
        <div
          className="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 mt-0.5"
          style={{ background: `${color}18` }}
        >
          <i className={`${icon} text-base`} style={{ color }} />
        </div>

        <div className="flex-1 min-w-0">
          <div className="flex items-center gap-2 flex-wrap mb-1">
            <span className="text-sm font-bold text-slate-800">{title}</span>
            <span
              className="text-[10px] px-2 py-0.5 rounded-full font-bold"
              style={{ color: pBadge.color, background: pBadge.bg }}
            >
              {pBadge.label}
            </span>
            <span
              className="text-[10px] px-2 py-0.5 rounded-full font-medium"
              style={{ color: cfg.color, background: cfg.bg }}
            >
              <i className={`${cfg.icon} ml-1`} />
              {cfg.label}
            </span>
          </div>
          <p className="text-xs text-slate-500 leading-relaxed">{notification.message}</p>
          <div className="flex items-center gap-3 mt-2">
            <span className="text-[11px] text-slate-400 flex items-center gap-1">
              <i className="ri-time-line" />
              {timeAgo}
            </span>
            {(notification.action_url || notification.metadata?.supplier_id != null) && (
              <span
                onClick={handleAction}
                className="text-[11px] font-semibold cursor-pointer transition-opacity hover:opacity-70 flex items-center gap-1"
                style={{ color }}
              >
                عرض
                <i className="ri-arrow-left-line text-[10px]" />
              </span>
            )}
          </div>
        </div>

        <div className="flex items-center gap-1 shrink-0 opacity-0 group-hover:opacity-100 transition-opacity duration-150">
          {!isRead && (
            <button
              onClick={() => onMarkRead(notification.id)}
              className="w-7 h-7 rounded-lg flex items-center justify-center cursor-pointer transition-all duration-150"
              style={{ color: "#64748B", background: "#F1F5F9" }}
              onMouseEnter={(e) => {
                (e.currentTarget as HTMLElement).style.background = "#DCFCE7";
                (e.currentTarget as HTMLElement).style.color = "#22C55E";
              }}
              onMouseLeave={(e) => {
                (e.currentTarget as HTMLElement).style.background = "#F1F5F9";
                (e.currentTarget as HTMLElement).style.color = "#64748B";
              }}
              title="تعيين كمقروء"
            >
              <i className="ri-check-line text-sm" />
            </button>
          )}
          <button
            onClick={() => onDelete(notification.id)}
            className="w-7 h-7 rounded-lg flex items-center justify-center cursor-pointer transition-all duration-150"
            style={{ color: "#64748B", background: "#F1F5F9" }}
            onMouseEnter={(e) => {
              (e.currentTarget as HTMLElement).style.background = "#FEE2E2";
              (e.currentTarget as HTMLElement).style.color = "#EF4444";
            }}
            onMouseLeave={(e) => {
              (e.currentTarget as HTMLElement).style.background = "#F1F5F9";
              (e.currentTarget as HTMLElement).style.color = "#64748B";
            }}
            title="حذف الإشعار"
          >
            <i className="ri-delete-bin-line text-sm" />
          </button>
        </div>
      </div>
    </div>
  );
}
