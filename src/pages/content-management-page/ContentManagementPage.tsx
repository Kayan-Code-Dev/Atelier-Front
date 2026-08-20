import { useMemo } from "react";
import { Outlet, useLocation, useNavigate } from "react-router";
import { useMyPermissions } from "@/api/auth/auth.hooks";

type ContentTabId =
  | "company"
  | "profile"
  | "branches"
  | "financial"
  | "currencies"
  | "product-taxonomy"
  | "invoice-rules"
  | "notifications"
  | "users"
  | "system";

type TabDef = {
  id: ContentTabId;
  path: string;
  label: string;
  description: string;
  icon: string;
  color: string;
  permissions: string[];
};

type SettingsGroup = {
  id: string;
  label: string;
  tabs: TabDef[];
};

const SETTINGS_GROUPS: SettingsGroup[] = [
  {
    id: "business",
    label: "المنشأة",
    tabs: [
      {
        id: "company",
        path: "/content/company",
        label: "الشركة",
        description: "الاسم، التواصل، الضريبة والسجل",
        icon: "ri-building-2-line",
        color: "#0C1A3E",
        permissions: ["settings.view", "settings.manage", "dashboard.view"],
      },
      {
        id: "profile",
        path: "/content/profile",
        label: "الملف الشخصي",
        description: "حسابك، الصورة، وكلمة المرور",
        icon: "ri-user-3-line",
        color: "#3B82F6",
        permissions: [],
      },
      {
        id: "branches",
        path: "/content/branches",
        label: "الفروع",
        description: "الضريبة والعملة لكل فرع",
        icon: "ri-store-2-line",
        color: "#C2964A",
        permissions: ["branches.view"],
      },
    ],
  },
  {
    id: "ops",
    label: "التشغيل",
    tabs: [
      {
        id: "financial",
        path: "/content/financial",
        label: "المالية",
        description: "العملات، الخزن، القيود، المصروفات",
        icon: "ri-safe-2-line",
        color: "#0EA5E9",
        permissions: [
          "currencies.view",
          "cashboxes.view",
          "expenses.view",
          "accounting.view",
          "accounting.journal_entries.view",
        ],
      },
      {
        id: "currencies",
        path: "/content/currencies",
        label: "العملات",
        description: "إدارة قائمة العملات",
        icon: "ri-exchange-dollar-line",
        color: "#0C1A3E",
        permissions: ["currencies.view"],
      },
      {
        id: "product-taxonomy",
        path: "/content/product-taxonomy",
        label: "المخزون والأصناف",
        description: "أقسام المنتجات والأقسام الفرعية",
        icon: "ri-folder-3-line",
        color: "#10B981",
        permissions: ["categories.view", "subcategories.view"],
      },
      {
        id: "invoice-rules",
        path: "/content/invoice-rules",
        label: "الفواتير والمستندات",
        description: "قوالب الطباعة وشروط الفاتورة",
        icon: "ri-printer-line",
        color: "#6366F1",
        permissions: ["settings.view", "settings.manage", "dashboard.view"],
      },
    ],
  },
  {
    id: "people",
    label: "الأشخاص والنظام",
    tabs: [
      {
        id: "notifications",
        path: "/content/notifications",
        label: "الإشعارات",
        description: "اختصار لمركز الإشعارات",
        icon: "ri-notification-3-line",
        color: "#F59E0B",
        permissions: ["notifications.view", "notifications.manage"],
      },
      {
        id: "users",
        path: "/content/users",
        label: "المستخدمون والصلاحيات",
        description: "الموظفون وحسابك",
        icon: "ri-shield-user-line",
        color: "#8B5CF6",
        permissions: ["hr.employees.view", "dashboard.view"],
      },
      {
        id: "system",
        path: "/content/system",
        label: "النظام",
        description: "اللغة، المنطقة الزمنية، العملة الافتراضية",
        icon: "ri-settings-3-line",
        color: "#64748B",
        permissions: ["settings.view", "settings.manage", "dashboard.view"],
      },
    ],
  },
];

const ALL_TABS = SETTINGS_GROUPS.flatMap((g) => g.tabs);

function tabVisible(tab: TabDef, perms: string[] | undefined): boolean {
  if (!tab.permissions.length) return true;
  if (!perms?.length) return false;
  return tab.permissions.some((p) => perms.includes(p));
}

function ContentManagementPage() {
  const location = useLocation();
  const navigate = useNavigate();
  const { data: perms, isSuccess } = useMyPermissions();

  const visibleGroups = useMemo(() => {
    return SETTINGS_GROUPS.map((group) => ({
      ...group,
      tabs: group.tabs.filter((t) =>
        !isSuccess || !perms ? true : tabVisible(t, perms)
      ),
    })).filter((g) => g.tabs.length > 0);
  }, [isSuccess, perms]);

  const visibleTabs = useMemo(
    () => visibleGroups.flatMap((g) => g.tabs),
    [visibleGroups]
  );

  const sortedByPathLen = [...ALL_TABS].sort(
    (a, b) => b.path.length - a.path.length
  );
  const current =
    sortedByPathLen.find((t) => location.pathname.startsWith(t.path)) ??
    visibleTabs[0] ??
    ALL_TABS[0];

  return (
    <div className="min-h-screen" style={{ background: "#F8FAFC" }} dir="rtl">
      <div
        className="px-6 py-5"
        style={{ background: "#FFFFFF", borderBottom: "1px solid #EEF2F8" }}
      >
        <div className="flex items-center gap-3">
          <div
            className="w-10 h-10 rounded-xl flex items-center justify-center"
            style={{
              background: "linear-gradient(135deg, #0C1A3E, #1E3A7B)",
            }}
          >
            <i className="ri-settings-3-line text-white text-base" />
          </div>
          <div>
            <h1 className="text-lg font-black text-slate-800">الإعدادات</h1>
            <p className="text-xs text-slate-400 mt-0.5">
              إعدادات المنشأة والتشغيل والمستندات — بدون اشتراك أو كوتة
            </p>
          </div>
        </div>
      </div>

      <div className="flex gap-0 min-h-[calc(100vh-73px)]">
        <aside
          className="w-72 flex-shrink-0 py-5 px-4 space-y-4 hidden md:block"
          style={{
            background: "#FFFFFF",
            borderLeft: "1px solid #EEF2F8",
            minHeight: "calc(100vh - 73px)",
          }}
        >
          {visibleGroups.length === 0 ? (
            <p className="text-xs text-slate-400 px-2">
              لا توجد أقسام متاحة لصلاحياتك.
            </p>
          ) : (
            visibleGroups.map((group) => (
              <div key={group.id} className="space-y-1.5">
                <p className="text-[10px] font-black text-slate-400 px-2 tracking-widest uppercase">
                  {group.label}
                </p>
                {group.tabs.map((tab) => {
                  const isActive = location.pathname.startsWith(tab.path);
                  return (
                    <button
                      key={tab.id}
                      type="button"
                      onClick={() => navigate(tab.path)}
                      className="w-full flex items-center gap-3 px-3 py-3 rounded-xl cursor-pointer transition-all duration-150 text-right relative"
                      style={
                        isActive
                          ? {
                              background: `${tab.color}10`,
                              border: `1.5px solid ${tab.color}30`,
                            }
                          : {
                              background: "transparent",
                              border: "1.5px solid transparent",
                            }
                      }
                    >
                      {isActive ? (
                        <span
                          className="absolute right-0 top-2 bottom-2 w-0.5 rounded-full"
                          style={{ background: tab.color }}
                        />
                      ) : null}
                      <div
                        className="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0"
                        style={{
                          background: isActive ? `${tab.color}18` : "#F1F5F9",
                        }}
                      >
                        <i
                          className={`${tab.icon} text-sm`}
                          style={{ color: isActive ? tab.color : "#94A3B8" }}
                        />
                      </div>
                      <div className="flex-1 min-w-0 text-right">
                        <p
                          className="text-sm font-bold truncate"
                          style={{ color: isActive ? "#1E293B" : "#475569" }}
                        >
                          {tab.label}
                        </p>
                        <p className="text-[11px] text-slate-400 truncate mt-0.5">
                          {tab.description}
                        </p>
                      </div>
                    </button>
                  );
                })}
              </div>
            ))
          )}
        </aside>

        <main className="flex-1 p-4 md:p-6 overflow-y-auto min-w-0">
          <div className="flex md:hidden gap-2 overflow-x-auto pb-3 mb-4 -mx-1 px-1">
            {visibleTabs.map((tab) => {
              const isActive = location.pathname.startsWith(tab.path);
              return (
                <button
                  key={tab.id}
                  type="button"
                  onClick={() => navigate(tab.path)}
                  className="shrink-0 px-3 py-2 rounded-xl text-xs font-bold whitespace-nowrap"
                  style={
                    isActive
                      ? {
                          background: `${tab.color}18`,
                          color: tab.color,
                          border: `1px solid ${tab.color}40`,
                        }
                      : {
                          background: "#fff",
                          color: "#64748B",
                          border: "1px solid #EEF2F8",
                        }
                  }
                >
                  {tab.label}
                </button>
              );
            })}
          </div>

          <div className="flex items-center gap-2 mb-5 text-xs text-slate-400">
            <i className="ri-settings-3-line" />
            <span>الإعدادات</span>
            <i className="ri-arrow-left-s-line" />
            <span className="font-bold" style={{ color: current.color }}>
              {current.label}
            </span>
          </div>

          <div className="flex items-center gap-3 mb-6">
            <div
              className="w-11 h-11 rounded-xl flex items-center justify-center"
              style={{ background: `${current.color}15` }}
            >
              <i
                className={`${current.icon} text-lg`}
                style={{ color: current.color }}
              />
            </div>
            <div>
              <h2 className="text-lg font-black text-slate-800">
                {current.label}
              </h2>
              <p className="text-xs text-slate-400 mt-0.5">
                {current.description}
              </p>
            </div>
          </div>

          <Outlet />
        </main>
      </div>
    </div>
  );
}

export default ContentManagementPage;
