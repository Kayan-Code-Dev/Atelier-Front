import { useNavigate } from "react-router";

type LinkCard = {
  title: string;
  description: string;
  path: string;
  icon: string;
  color: string;
};

function ModuleLinksTab({
  intro,
  links,
}: {
  intro: string;
  links: LinkCard[];
}) {
  const navigate = useNavigate();

  return (
    <div className="space-y-4 max-w-3xl">
      <p className="text-xs text-slate-500">{intro}</p>
      <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
        {links.map((link) => (
          <button
            key={link.path}
            type="button"
            onClick={() => navigate(link.path)}
            className="text-right rounded-2xl border border-slate-100 bg-white p-4 hover:border-slate-200 transition-colors"
          >
            <div className="flex items-start gap-3">
              <div
                className="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                style={{ background: `${link.color}15` }}
              >
                <i className={`${link.icon} text-base`} style={{ color: link.color }} />
              </div>
              <div className="min-w-0">
                <p className="text-sm font-black text-slate-800">{link.title}</p>
                <p className="text-[11px] text-slate-400 mt-1">{link.description}</p>
              </div>
              <i className="ri-arrow-left-s-line text-slate-300 mr-auto mt-1" />
            </div>
          </button>
        ))}
      </div>
    </div>
  );
}

export function FinancialSettingsTab() {
  return (
    <ModuleLinksTab
      intro="إدارة العملات والصناديق من الوحدات المخصّصة. العملة الافتراضية للمنشأة تُضبط من تبويب النظام."
      links={[
        {
          title: "العملات",
          description: "إضافة وتعديل العملات المعتمدة في النظام",
          path: "/content/currencies",
          icon: "ri-exchange-dollar-line",
          color: "#0C1A3E",
        },
        {
          title: "الخزن / الصناديق",
          description: "الصناديق والأرصدة والحركات النقدية",
          path: "/cashboxes",
          icon: "ri-safe-2-line",
          color: "#C2964A",
        },
        {
          title: "القيود اليومية",
          description: "دفتر القيود المحاسبية",
          path: "/treasury/entries",
          icon: "ri-book-2-line",
          color: "#6366F1",
        },
        {
          title: "المصروفات",
          description: "تسجيل ومتابعة المصروفات التشغيلية",
          path: "/expenses",
          icon: "ri-wallet-3-line",
          color: "#EF4444",
        },
      ]}
    />
  );
}

export function NotificationsSettingsTab() {
  return (
    <ModuleLinksTab
      intro="تفضيلات الإشعارات التشغيلية تُدار من وحدة الإشعارات."
      links={[
        {
          title: "مركز الإشعارات",
          description: "عرض وإدارة إشعارات النظام",
          path: "/notifications",
          icon: "ri-notification-3-line",
          color: "#0D6E5F",
        },
      ]}
    />
  );
}

export function UsersRolesSettingsTab() {
  return (
    <ModuleLinksTab
      intro="المستخدمون والصلاحيات الأساسية مرتبطة بموظفي الحساب. وحدة الأدوار التفصيلية تُفعَّل لاحقاً عند الحاجة."
      links={[
        {
          title: "الموظفون",
          description: "إدارة الموظفين وحساباتهم",
          path: "/employees",
          icon: "ri-team-line",
          color: "#3B82F6",
        },
        {
          title: "الملف الشخصي",
          description: "بيانات حسابك وكلمة المرور",
          path: "/content/profile",
          icon: "ri-user-3-line",
          color: "#10B981",
        },
      ]}
    />
  );
}
