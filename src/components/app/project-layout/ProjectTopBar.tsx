import { useState } from "react";
import { useLocation, useNavigate } from "react-router";
import { formatDistanceToNow } from "date-fns";
import { ar } from "date-fns/locale";
import { getRouteInfo } from "./routeTitles";
import { useMutation, useQuery } from "@tanstack/react-query";
import {
  useGetNotificationsQueryOptions,
  useGetUnreadCountQueryOptions,
  useMarkAllNotificationsAsReadMutationOptions,
} from "@/api/v2/notifications/notifications.hooks";
import { useHasPermission } from "@/api/auth/auth.hooks";
import { normalizeNotificationActionUrl } from "@/utils/notificationActionUrl";
import type { Notification } from "@/api/v2/notifications/notifications.types";

const notifStyles: Record<string, { bg: string; color: string; icon: string }> = {
  warning: { bg: "#FEF7E0", color: "#C2820A", icon: "ri-error-warning-line" },
  info: { bg: "#E8F2EF", color: "#0D6E5F", icon: "ri-information-line" },
  success: { bg: "#E8F8F1", color: "#0A6640", icon: "ri-checkbox-circle-line" },
  danger: { bg: "#FEE8E8", color: "#9B1A1A", icon: "ri-alert-line" },
};

function getNotifStyle(n: Notification) {
  const t = (n.type || "").toLowerCase();
  if (["warning", "danger", "info", "success"].includes(t)) return notifStyles[t];
  if (["error", "critical"].includes(t)) return notifStyles.danger;
  if (n.priority === "urgent" || n.priority === "high") return notifStyles.warning;
  return notifStyles.info;
}
const quickActions = [
  { icon: "ri-file-add-line", label: "فاتورة بيع", path: "/sales/create", color: "#0D6E5F" },
  { icon: "ri-key-2-line", label: "فاتورة إيجار", path: "/orders/rental/create", color: "#143048" },
  { icon: "ri-scissors-cut-line", label: "أمر تفصيل", path: "/tailoring/choose-client", color: "#3D6B8A" },
  { icon: "ri-user-add-line", label: "عميل جديد", path: "/clients", color: "#0A6640" },
  { icon: "ri-exchange-line", label: "قيد مالي", path: "/cashboxes", color: "#C2783A" },
];

interface ProjectTopBarProps {
  sidebarWidth: number;
  onMobileMenuToggle?: () => void;
}

export default function ProjectTopBar({ sidebarWidth, onMobileMenuToggle }: ProjectTopBarProps) {
  const location = useLocation();
  const navigate = useNavigate();
  const [showNotif, setShowNotif] = useState(false);
  const [showActions, setShowActions] = useState(false);
  const [searchQuery, setSearchQuery] = useState("");

  const { hasPermission: canViewNotifications } = useHasPermission(["notifications.view", "notifications.manage"]);
  const { data: apiNotifications } = useQuery({
    ...useGetNotificationsQueryOptions({ page: 1, per_page: 10, unread_only: false }),
    enabled: canViewNotifications,
  });
  const { data: unreadCountData } = useQuery({
    ...useGetUnreadCountQueryOptions(),
    enabled: canViewNotifications,
  });
  const markAllAsReadMutation = useMutation(useMarkAllNotificationsAsReadMutationOptions());

  const notificationsList: Notification[] = apiNotifications?.data ?? [];
  const unread = unreadCountData?.unread_count ?? 0;
  const route = getRouteInfo(location.pathname, location.search);

  return (
    <header
      className="fixed top-0 left-0 z-30 flex items-center justify-between no-print transition-all duration-300"
      style={{
        height: "var(--topbar-height)",
        right: `${sidebarWidth}px`,
        background: "rgba(255,255,255,0.92)",
        borderBottom: "1px solid var(--color-border)",
        backdropFilter: "blur(12px)",
        paddingLeft: "16px",
        paddingRight: "16px",
        gap: "12px",
      }}
    >
      <div className="flex items-center gap-2.5 min-w-0 flex-shrink-0">
        <button
          onClick={onMobileMenuToggle}
          className="lg:hidden w-8 h-8 rounded-md flex items-center justify-center cursor-pointer flex-shrink-0 transition-colors"
          style={{ background: "var(--chalk)", color: "var(--ink-mid)", border: "1px solid var(--color-border)" }}
        >
          <i className="ri-menu-2-line text-base" />
        </button>

        <div
          className="hidden sm:flex w-8 h-8 rounded-md items-center justify-center flex-shrink-0"
          style={{
            background: "linear-gradient(145deg, #0b1f33, #143048)",
            boxShadow: "0 2px 6px rgba(11,31,51,0.25)",
          }}
        >
          <i className={`${route.icon} text-white text-[13px]`} />
        </div>

        <div className="min-w-0 flex-shrink">
          {route.parent ? (
            <p
              className="hidden sm:flex items-center text-[11px] font-600 leading-none mb-0.5 gap-1"
              style={{ color: "var(--color-text-muted)" }}
            >
              <span>{route.parent}</span>
              <i className="ri-arrow-left-s-line text-slate-300" />
              <span style={{ color: "var(--color-text-secondary)" }}>{route.title}</span>
            </p>
          ) : (
            <p className="hidden sm:block text-[11px] leading-none mb-0.5" style={{ color: "var(--color-text-muted)" }}>
              الرئيسية
              <i className="ri-arrow-left-s-line mx-0.5 text-slate-300 text-[11px]" />
              {route.title}
            </p>
          )}
          <h2
            className="font-bold text-[15px] leading-tight truncate font-display"
            style={{ color: "var(--color-text-primary)" }}
          >
            {route.title}
          </h2>
        </div>
      </div>

      <div className="hidden md:flex flex-1 max-w-[280px] mx-auto">
        <div className="relative w-full">
          <i className="ri-search-line absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none" />
          <input
            type="text"
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            placeholder="بحث سريع..."
            className="w-full pr-9 pl-4 py-2 text-[13px] rounded-md outline-none transition-all"
            style={{
              background: "var(--chalk)",
              border: "1.5px solid var(--color-border)",
              fontFamily: "var(--font-ui)",
              color: "var(--color-text-primary)",
            }}
            onFocus={(e) => {
              e.currentTarget.style.borderColor = "var(--emerald)";
              e.currentTarget.style.boxShadow = "0 0 0 3px rgba(13,110,95,0.14)";
              e.currentTarget.style.background = "white";
            }}
            onBlur={(e) => {
              e.currentTarget.style.borderColor = "var(--color-border)";
              e.currentTarget.style.boxShadow = "none";
              e.currentTarget.style.background = "var(--chalk)";
            }}
          />
          {searchQuery && (
            <button
              onClick={() => setSearchQuery("")}
              className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 cursor-pointer"
            >
              <i className="ri-close-line text-sm" />
            </button>
          )}
        </div>
      </div>

      <div className="flex items-center gap-1.5 flex-shrink-0">
        <div
          className="hidden xl:flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-semibold whitespace-nowrap"
          style={{ background: "var(--chalk)", color: "var(--color-text-muted)", border: "1px solid var(--color-border)" }}
        >
          <i className="ri-calendar-line" style={{ color: "var(--color-accent)" }} />
          {new Date().toLocaleDateString("ar-EG", { weekday: "short", month: "short", day: "numeric" })}
        </div>

        {canViewNotifications && (
          <div className="relative">
            <button
              onClick={() => {
                setShowNotif(!showNotif);
                setShowActions(false);
              }}
              className="w-8 h-8 rounded-md flex items-center justify-center cursor-pointer transition-all relative"
              style={{
                background: showNotif ? "var(--emerald-muted)" : "var(--chalk)",
                border: `1.5px solid ${showNotif ? "rgba(13,110,95,0.4)" : "var(--color-border)"}`,
                color: showNotif ? "var(--emerald)" : "#4A5568",
              }}
            >
              <i className="ri-notification-3-line text-[15px]" />
              {unread > 0 && (
                <span
                  className="absolute -top-1 -right-1 w-4 h-4 rounded-full text-[9px] font-black flex items-center justify-center text-white"
                  style={{ background: "#DC2626" }}
                >
                  {unread > 99 ? "99+" : unread}
                </span>
              )}
            </button>

            {showNotif && (
              <div
                className="absolute left-0 top-11 w-80 rounded-lg overflow-hidden slide-down z-50"
                style={{
                  background: "white",
                  border: "1px solid var(--color-border)",
                  boxShadow: "var(--shadow-dropdown)",
                }}
              >
                <div className="flex items-center justify-between px-4 py-3 border-b border-slate-100">
                  <div className="flex items-center gap-2">
                    <div className="w-6 h-6 flex items-center justify-center rounded-md" style={{ background: "var(--emerald-muted)" }}>
                      <i className="ri-notification-3-line text-xs" style={{ color: "var(--emerald)" }} />
                    </div>
                    <h3 className="font-bold text-sm" style={{ color: "var(--color-text-primary)" }}>
                      الإشعارات
                    </h3>
                  </div>
                  <div className="flex items-center gap-2">
                    <span className="badge-base badge-red">{unread} جديد</span>
                    <button
                      className="text-xs cursor-pointer"
                      style={{ color: "var(--color-text-muted)" }}
                      onClick={() => markAllAsReadMutation.mutate()}
                    >
                      تحديد الكل
                    </button>
                  </div>
                </div>

                <div className="max-h-72 overflow-y-auto">
                  {notificationsList.length === 0 ? (
                    <div className="py-8 text-center text-sm" style={{ color: "var(--color-text-muted)" }}>
                      لا توجد إشعارات
                    </div>
                  ) : (
                    notificationsList.map((n, idx) => {
                      const ns = getNotifStyle(n);
                      const isRead = !!n.read_at;
                      return (
                        <div
                          key={n.id}
                          className="flex items-start gap-3 px-4 py-3 cursor-pointer transition-colors"
                          style={{
                            background: isRead ? "transparent" : "rgba(13,110,95,0.04)",
                            borderBottom: idx < notificationsList.length - 1 ? "1px solid #F1F4F7" : "none",
                          }}
                          onMouseEnter={(e) => {
                            (e.currentTarget as HTMLElement).style.background = "var(--emerald-muted)";
                          }}
                          onMouseLeave={(e) => {
                            (e.currentTarget as HTMLElement).style.background = isRead ? "transparent" : "rgba(13,110,95,0.04)";
                          }}
                          onClick={() => {
                            if (n.action_url || n.metadata?.supplier_id != null) {
                              navigate(normalizeNotificationActionUrl(n.action_url ?? undefined, n.metadata));
                              setShowNotif(false);
                            }
                          }}
                        >
                          <div
                            className="w-7 h-7 rounded-md flex-shrink-0 flex items-center justify-center mt-0.5"
                            style={{ background: ns.bg }}
                          >
                            <i className={`${ns.icon} text-sm`} style={{ color: ns.color }} />
                          </div>
                          <div className="flex-1 min-w-0">
                            <p
                              className="text-xs leading-relaxed"
                              style={{
                                color: isRead ? "#64748B" : "var(--color-text-primary)",
                                fontWeight: isRead ? "500" : "700",
                              }}
                            >
                              {n.title || n.message}
                            </p>
                            <p className="text-[11px] mt-0.5" style={{ color: "var(--color-text-muted)" }}>
                              {n.created_at
                                ? formatDistanceToNow(new Date(n.created_at), { addSuffix: true, locale: ar })
                                : ""}
                            </p>
                          </div>
                          {!isRead && (
                            <span
                              className="w-1.5 h-1.5 rounded-full flex-shrink-0 mt-1.5"
                              style={{ background: "var(--emerald)" }}
                            />
                          )}
                        </div>
                      );
                    })
                  )}
                </div>

                <div className="px-4 py-2.5 border-t border-slate-100">
                  <button
                    onClick={() => {
                      navigate("/notifications");
                      setShowNotif(false);
                    }}
                    className="w-full text-xs font-bold cursor-pointer transition-colors py-1.5 rounded-md"
                    style={{ color: "var(--emerald)" }}
                    onMouseEnter={(e) => {
                      (e.currentTarget as HTMLElement).style.background = "var(--emerald-muted)";
                    }}
                    onMouseLeave={(e) => {
                      (e.currentTarget as HTMLElement).style.background = "transparent";
                    }}
                  >
                    عرض كل الإشعارات ←
                  </button>
                </div>
              </div>
            )}
          </div>
        )}

        <div className="relative">
          <button
            onClick={() => {
              setShowActions(!showActions);
              setShowNotif(false);
            }}
            className="flex items-center gap-1.5 px-3 py-1.5 rounded-md cursor-pointer text-[13px] font-bold transition-all whitespace-nowrap blue-btn"
          >
            <i className="ri-add-line text-[15px]" />
            <span className="hidden sm:inline">إضافة</span>
          </button>

          {showActions && (
            <div
              className="absolute left-0 top-11 w-52 rounded-lg overflow-hidden slide-down z-50"
              style={{
                background: "white",
                border: "1px solid var(--color-border)",
                boxShadow: "var(--shadow-dropdown)",
              }}
            >
              <div className="px-4 py-3 border-b border-slate-100">
                <p className="text-[11px] font-bold uppercase tracking-wider" style={{ color: "var(--color-text-muted)" }}>
                  إضافة سريعة
                </p>
              </div>
              {quickActions.map((action) => (
                <button
                  key={action.path}
                  onClick={() => {
                    navigate(action.path);
                    setShowActions(false);
                  }}
                  className="flex items-center gap-3 px-4 py-2.5 w-full cursor-pointer text-right transition-colors"
                  onMouseEnter={(e) => {
                    (e.currentTarget as HTMLElement).style.background = "var(--chalk)";
                  }}
                  onMouseLeave={(e) => {
                    (e.currentTarget as HTMLElement).style.background = "transparent";
                  }}
                >
                  <div
                    className="w-7 h-7 rounded-md flex items-center justify-center flex-shrink-0"
                    style={{ background: `${action.color}15` }}
                  >
                    <i className={`${action.icon} text-sm`} style={{ color: action.color }} />
                  </div>
                  <span className="text-[13px] font-semibold" style={{ color: "var(--color-text-primary)" }}>
                    {action.label}
                  </span>
                </button>
              ))}
            </div>
          )}
        </div>
      </div>

      {(showNotif || showActions) && (
        <div className="fixed inset-0 z-40" onClick={() => { setShowNotif(false); setShowActions(false); }} />
      )}
    </header>
  );
}
