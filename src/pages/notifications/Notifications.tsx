import { useState, useMemo } from "react";
import { useQuery, useMutation } from "@tanstack/react-query";
import {
  useGetNotificationsQueryOptions,
  useMarkNotificationAsReadMutationOptions,
  useMarkAllNotificationsAsReadMutationOptions,
  useDeleteNotificationMutationOptions,
} from "@/api/v2/notifications/notifications.hooks";
import {
  getNotificationCategory,
  categoryConfig,
  type NotificationCategory,
} from "./utils/notificationCategory";
import { NotificationStats } from "./components/NotificationStats";
import { NotificationItem } from "./components/NotificationItem";
import { toast } from "sonner";

type FilterTab = "all" | "unread" | NotificationCategory;
type PriorityFilter = "all" | "urgent" | "high" | "normal" | "low";

const PER_PAGE = 20;

const filterTabs: { id: FilterTab; label: string; icon: string }[] = [
  { id: "all", label: "الكل", icon: "ri-apps-2-line" },
  { id: "unread", label: "غير مقروءة", icon: "ri-mail-unread-line" },
  { id: "sales", label: "المبيعات", icon: "ri-store-3-line" },
  { id: "rental", label: "الإيجار", icon: "ri-key-2-line" },
  { id: "tailoring", label: "التفصيل", icon: "ri-scissors-cut-line" },
  { id: "treasury", label: "الخزنة", icon: "ri-safe-2-line" },
  { id: "delivery", label: "التسليمات", icon: "ri-truck-line" },
  { id: "employees", label: "الموظفون", icon: "ri-user-star-line" },
  { id: "suppliers", label: "الموردون", icon: "ri-building-2-line" },
  { id: "customers", label: "العملاء", icon: "ri-group-line" },
  { id: "inventory", label: "المخزون", icon: "ri-price-tag-3-line" },
  { id: "system", label: "النظام", icon: "ri-settings-3-line" },
];

export default function Notifications() {
  const [page, setPage] = useState(1);
  const [activeTab, setActiveTab] = useState<FilterTab>("all");
  const [priorityFilter, setPriorityFilter] = useState<PriorityFilter>("all");
  const [search, setSearch] = useState("");

  const unreadOnly = activeTab === "unread";
  const categoryFilter =
    activeTab !== "all" && activeTab !== "unread" ? activeTab : null;

  const { data, isPending, isError } = useQuery(
    useGetNotificationsQueryOptions({
      page,
      per_page: PER_PAGE,
      unread_only: unreadOnly,
    })
  );

  const markAsReadMutation = useMutation(
    useMarkNotificationAsReadMutationOptions()
  );
  const markAllAsReadMutation = useMutation(
    useMarkAllNotificationsAsReadMutationOptions()
  );
  const deleteMutation = useMutation(useDeleteNotificationMutationOptions());

  const notifications = data?.data ?? [];
  const meta = data?.meta ?? { total: 0, read: 0, unread: 0 };
  const unreadCount = meta.unread;
  const totalPages = data?.total_pages ?? 1;

  const filtered = useMemo(() => {
    let list = [...notifications];
    if (categoryFilter) {
      list = list.filter((n) => getNotificationCategory(n) === categoryFilter);
    }
    if (priorityFilter !== "all") {
      list = list.filter(
        (n) => (n.priority ?? "normal") === priorityFilter
      );
    }
    if (search.trim()) {
      const q = search.toLowerCase();
      list = list.filter(
        (n) =>
          (n.title ?? "").toLowerCase().includes(q) ||
          (n.message ?? "").toLowerCase().includes(q)
      );
    }
    return list;
  }, [notifications, categoryFilter, priorityFilter, search]);

  const handleMarkRead = (id: number) => {
    markAsReadMutation.mutate(id, {
      onSuccess: () => toast.success("تم تحديد الإشعار كمقروء"),
    });
  };

  const handleDelete = (id: number) => {
    deleteMutation.mutate(id, {
      onSuccess: () => toast.success("تم حذف الإشعار"),
    });
  };

  const handleMarkAllRead = () => {
    markAllAsReadMutation.mutate(undefined, {
      onSuccess: () => toast.success("تم تحديد جميع الإشعارات كمقروءة"),
    });
  };

  const getTabCount = (tab: FilterTab) => {
    if (tab === "all") return unreadCount;
    if (tab === "unread") return unreadCount;
    return notifications.filter(
      (n) => getNotificationCategory(n) === tab && !n.read_at
    ).length;
  };

  return (
    <div className="min-h-screen" style={{ background: "#F8FAFC" }}>
      
      <div
        className="sticky top-0 z-10 px-6 py-4"
        style={{ background: "#FFFFFF", borderBottom: "1px solid #EEF2F8" }}
      >
        <div className="flex items-center justify-between gap-4">
          <div className="flex items-center gap-3">
            <div
              className="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
              style={{ background: "linear-gradient(135deg, #EF4444, #DC2626)" }}
            >
              <i className="ri-notification-3-line text-white text-base" />
            </div>
            <div>
              <h1 className="text-lg font-black text-slate-800">مركز الإشعارات</h1>
              <p className="text-xs text-slate-400 mt-0.5">
                {unreadCount > 0
                  ? `${unreadCount} إشعار غير مقروء`
                  : "جميع الإشعارات مقروءة"}
              </p>
            </div>
          </div>
          <div className="flex items-center gap-2">
            {unreadCount > 0 && (
              <button
                onClick={handleMarkAllRead}
                disabled={markAllAsReadMutation.isPending}
                className="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold cursor-pointer transition-all duration-150 whitespace-nowrap disabled:opacity-60"
                style={{
                  background: "#F0FDF4",
                  color: "#22C55E",
                  border: "1px solid #BBF7D0",
                }}
                onMouseEnter={(e) => {
                  if (!markAllAsReadMutation.isPending)
                    (e.currentTarget as HTMLElement).style.background = "#DCFCE7";
                }}
                onMouseLeave={(e) => {
                  (e.currentTarget as HTMLElement).style.background = "#F0FDF4";
                }}
              >
                <i className="ri-check-double-line" />
                تعيين الكل كمقروء
              </button>
            )}
          </div>
        </div>
      </div>

      <div className="px-6 py-5 space-y-5">
        {/* Stats */}
        <NotificationStats notifications={notifications} meta={meta} />

        {/* Search + Priority filter */}
        <div className="flex items-center gap-3">
          <div className="relative flex-1">
            <i className="ri-search-line absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm" />
            <input
              type="text"
              placeholder="البحث في الإشعارات..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="w-full pr-9 pl-9 py-2.5 rounded-xl text-sm text-slate-700 outline-none transition-all duration-150"
              style={{
                background: "#FFFFFF",
                border: "1px solid #E2E8F0",
              }}
              onFocus={(e) => {
                (e.target as HTMLElement).style.borderColor = "#C2964A";
              }}
              onBlur={(e) => {
                (e.target as HTMLElement).style.borderColor = "#E2E8F0";
              }}
            />
            {search && (
              <button
                onClick={() => setSearch("")}
                className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 flex items-center justify-center cursor-pointer"
                style={{ color: "#94A3B8" }}
              >
                <i className="ri-close-line text-sm" />
              </button>
            )}
          </div>

          <div
            className="flex items-center gap-1 p-1 rounded-xl"
            style={{ background: "#FFFFFF", border: "1px solid #E2E8F0" }}
          >
            {(["all", "urgent", "high", "normal", "low"] as PriorityFilter[]).map(
              (p) => {
                const labels: Record<PriorityFilter, string> = {
                  all: "الأولوية",
                  urgent: "عاجل",
                  high: "مهم",
                  normal: "متوسط",
                  low: "عادي",
                };
                const colors: Record<PriorityFilter, string> = {
                  all: "#64748B",
                  urgent: "#EF4444",
                  high: "#EF4444",
                  normal: "#F59E0B",
                  low: "#64748B",
                };
                return (
                  <button
                    key={p}
                    onClick={() => setPriorityFilter(p)}
                    className="px-3 py-1.5 rounded-lg text-xs font-semibold cursor-pointer transition-all duration-150 whitespace-nowrap"
                    style={
                      priorityFilter === p
                        ? { background: colors[p], color: "#FFFFFF" }
                        : { color: "#64748B", background: "transparent" }
                    }
                  >
                    {labels[p]}
                  </button>
                );
              }
            )}
          </div>
        </div>

        {/* Category Tabs */}
        <div
          className="rounded-xl p-1 flex items-center gap-1 overflow-x-auto"
          style={{
            background: "#FFFFFF",
            border: "1px solid #E2E8F0",
            scrollbarWidth: "none",
          }}
        >
          <style>{`[data-notif-tabs]::-webkit-scrollbar { display: none; }`}</style>
          <div data-notif-tabs className="flex items-center gap-1 overflow-x-auto">
            {filterTabs.map((tab) => {
              const count = getTabCount(tab.id);
              const isActive = activeTab === tab.id;
              const cfg =
                tab.id !== "all" && tab.id !== "unread"
                  ? categoryConfig[tab.id as NotificationCategory]
                  : null;
              return (
                <button
                  key={tab.id}
                  onClick={() => {
                    setActiveTab(tab.id);
                    setPage(1);
                  }}
                  className="flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold cursor-pointer transition-all duration-150 whitespace-nowrap shrink-0"
                  style={
                    isActive
                      ? {
                          background: cfg ? `${cfg.color}18` : "#0C1A3E",
                          color: cfg ? cfg.color : "#FFFFFF",
                        }
                      : { color: "#64748B" }
                  }
                >
                  <i className={tab.icon} />
                  {tab.label}
                  {count > 0 && (
                    <span
                      className="text-[10px] px-1.5 py-0.5 rounded-full font-black"
                      style={{
                        background: isActive
                          ? (cfg ? cfg.color : "#FFFFFF")
                          : "#EF4444",
                        color: isActive
                          ? (cfg ? "#FFFFFF" : "#0C1A3E")
                          : "#FFFFFF",
                      }}
                    >
                      {count}
                    </span>
                  )}
                </button>
              );
            })}
          </div>
        </div>

        {/* Notifications list */}
        <div className="space-y-2">
          {isPending ? (
            <div className="flex flex-col items-center justify-center py-16 rounded-xl bg-white border border-slate-100">
              <div className="animate-spin w-10 h-10 rounded-full border-2 border-slate-200 border-t-slate-600 mb-4" />
              <p className="text-slate-500 font-semibold text-sm">جاري التحميل...</p>
            </div>
          ) : isError ? (
            <div className="flex flex-col items-center justify-center py-16 rounded-xl bg-white border border-slate-100">
              <i className="ri-error-warning-line text-3xl text-slate-300 mb-4" />
              <p className="text-slate-500 font-semibold text-sm">
                حدث خطأ في جلب الإشعارات
              </p>
            </div>
          ) : filtered.length === 0 ? (
            <div className="flex flex-col items-center justify-center py-16 rounded-xl bg-white border border-slate-100">
              <div
                className="w-16 h-16 rounded-2xl flex items-center justify-center mb-4"
                style={{ background: "#F1F5F9" }}
              >
                <i
                  className="ri-notification-off-line text-3xl"
                  style={{ color: "#94A3B8" }}
                />
              </div>
              <p className="text-slate-500 font-semibold text-sm">لا توجد إشعارات</p>
              <p className="text-slate-400 text-xs mt-1">
                {search
                  ? "لا توجد نتائج مطابقة للبحث"
                  : "صندوقك نظيف تماماً!"}
              </p>
            </div>
          ) : (
            <>
              {priorityFilter === "all" && !categoryFilter ? (
                <>
                  {(["urgent", "high", "normal", "low"] as const).map((priority) => {
                    const group = filtered.filter(
                      (n) => (n.priority ?? "normal") === priority
                    );
                    if (group.length === 0) return null;
                    const labels = {
                      urgent: "تنبيهات عاجلة",
                      high: "تنبيهات مهمة",
                      normal: "تنبيهات متوسطة",
                      low: "تنبيهات عادية",
                    };
                    const icons = {
                      urgent: "ri-alarm-warning-line",
                      high: "ri-alarm-warning-line",
                      normal: "ri-information-line",
                      low: "ri-notification-2-line",
                    };
                    const colors = {
                      urgent: "#EF4444",
                      high: "#EF4444",
                      normal: "#F59E0B",
                      low: "#64748B",
                    };
                    return (
                      <div key={priority} className="space-y-2">
                        <div className="flex items-center gap-2 px-1">
                          <i
                            className={`${icons[priority]} text-sm`}
                            style={{ color: colors[priority] }}
                          />
                          <span
                            className="text-xs font-bold"
                            style={{ color: colors[priority] }}
                          >
                            {labels[priority]}
                          </span>
                          <span
                            className="text-[10px] px-2 py-0.5 rounded-full font-bold"
                            style={{
                              background: `${colors[priority]}18`,
                              color: colors[priority],
                            }}
                          >
                            {group.length}
                          </span>
                        </div>
                        {group.map((n) => (
                          <NotificationItem
                            key={n.id}
                            notification={n}
                            onMarkRead={handleMarkRead}
                            onDelete={handleDelete}
                          />
                        ))}
                      </div>
                    );
                  })}
                </>
              ) : (
                filtered.map((n) => (
                  <NotificationItem
                    key={n.id}
                    notification={n}
                    onMarkRead={handleMarkRead}
                    onDelete={handleDelete}
                  />
                ))
              )}
            </>
          )}
        </div>

        {/* Pagination */}
        {data && data.total > 0 && (
          <div className="flex items-center justify-between gap-4 py-3">
            <p className="text-sm text-slate-500">
              عرض {data.data.length} من {data.total} إشعار
            </p>
            <div className="flex items-center gap-2">
              <button
                onClick={() => setPage((p) => Math.min(totalPages, p + 1))}
                disabled={page >= totalPages || isPending}
                className="px-3 py-1.5 rounded-lg text-sm font-semibold disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                style={{
                  background: page < totalPages ? "#F1F5F9" : "#E2E8F0",
                  color: "#64748B",
                }}
              >
                التالي
              </button>
              <span className="text-sm font-medium text-slate-600">
                صفحة {page} من {totalPages}
              </span>
              <button
                onClick={() => setPage((p) => Math.max(1, p - 1))}
                disabled={page <= 1 || isPending}
                className="px-3 py-1.5 rounded-lg text-sm font-semibold disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                style={{
                  background: page > 1 ? "#F1F5F9" : "#E2E8F0",
                  color: "#64748B",
                }}
              >
                السابق
              </button>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
