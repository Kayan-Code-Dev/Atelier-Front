import { useNavigate } from "react-router";
import { HOW_IT_WORKS_PATHS } from "../paths";

interface Module {
  id: string;
  icon: string;
  title: string;
  subtitle: string;
  description: string;
  features: string[];
  color: string;
  colorSoft: string;
  colorFrom: string;
  colorTo: string;
  path: string;
  image: string;
}

const modules: Module[] = [
  {
    id: "rental",
    icon: "ri-key-2-fill",
    title: "قسم الإيجار",
    subtitle: "إدارة إيجار الأزياء والملابس",
    description: "إنشاء فواتير الإيجار بالكامل مع تتبع التسليم والإرجاع.",
    features: [
      "فاتورة إيجار متكاملة",
      "تواريخ التسليم والإرجاع",
      "تتبع حالة كل طلب",
      "طباعة الفاتورة والعقد",
    ],
    color: "#059669",
    colorSoft: "#ECFDF5",
    colorFrom: "#10B981",
    colorTo: "#047857",
    path: HOW_IT_WORKS_PATHS.rentalList,
    image: "/images/how-it-works/mod-rental.jpg",
  },
  {
    id: "sales",
    icon: "ri-store-3-fill",
    title: "قسم البيع",
    subtitle: "فواتير البيع والمواعيد",
    description: "بيع مباشر للعملاء مع جدول مواعيد منظّم.",
    features: [
      "فواتير بيع فورية",
      "جدول المواعيد",
      "تتبع المبيعات",
      "تقارير المبيعات اليومية",
    ],
    color: "#E11D48",
    colorSoft: "#FFF1F2",
    colorFrom: "#F43F5E",
    colorTo: "#BE123C",
    path: HOW_IT_WORKS_PATHS.salesCreate,
    image: "/images/how-it-works/mod-sales.jpg",
  },
  {
    id: "tailoring",
    icon: "ri-scissors-cut-fill",
    title: "قسم التفصيل",
    subtitle: "أوامر الخياطة ومتابعة الإنتاج",
    description: "لوحة كانبان لمتابعة كل مرحلة من مراحل التفصيل.",
    features: [
      "أوامر تفصيل مفصّلة",
      "مراحل الإنتاج بالكانبان",
      "المقاسات والملاحظات",
      "تاريخ التسليم المتوقع",
    ],
    color: "#7C3AED",
    colorSoft: "#F5F3FF",
    colorFrom: "#8B5CF6",
    colorTo: "#6D28D9",
    path: HOW_IT_WORKS_PATHS.tailoring,
    image: "/images/how-it-works/mod-tailor.jpg",
  },
  {
    id: "delivery",
    icon: "ri-truck-fill",
    title: "بحث التسليمات",
    subtitle: "تتبع التسليم والإرجاع",
    description:
      "عرض شامل لحالة كل فاتورة — ينتظر التسليم أو تأخر أو تم.",
    features: [
      "بحث موحّد لكل الفواتير",
      "حالات تسليم وإرجاع",
      "تنبيهات التأخير",
      "إجراءات تأكيد سريعة",
    ],
    color: "#0284C7",
    colorSoft: "#F0F9FF",
    colorFrom: "#0EA5E9",
    colorTo: "#0369A1",
    path: HOW_IT_WORKS_PATHS.deliverySearch,
    image:
      "/images/how-it-works/mod-delivery.jpg",
  },
  {
    id: "accounting",
    icon: "ri-money-dollar-circle-fill",
    title: "إدارة الحسابات",
    subtitle: "المدفوعات والمصروفات",
    description: "سجلات مالية متكاملة مع تقارير.",
    features: [
      "قائمة المدفوعات",
      "تتبع المصروفات",
      "كشوف مالية",
      "تقارير",
    ],
    color: "#D97706",
    colorSoft: "#FFFBEB",
    colorFrom: "#F59E0B",
    colorTo: "#B45309",
    path: HOW_IT_WORKS_PATHS.payments,
    image:
      "/images/how-it-works/mod-accounting.jpg",
  },
  {
    id: "treasury",
    icon: "ri-safe-2-fill",
    title: "الخزنة",
    subtitle: "المعاملات والقيود المحاسبية",
    description: "كشف يومي للخزنة مع قيود محاسبية دقيقة.",
    features: [
      "كشف المعاملات اليومي",
      "القيود المحاسبية",
      "إغلاق يومي للخزنة",
      "تقرير الفرع المالي",
    ],
    color: "#0891B2",
    colorSoft: "#ECFEFF",
    colorFrom: "#06B6D4",
    colorTo: "#0E7490",
    path: HOW_IT_WORKS_PATHS.cashboxTransactions,
    image:
      "/images/how-it-works/mod-treasury.jpg",
  },
  {
    id: "employees",
    icon: "ri-user-star-fill",
    title: "الموظفون",
    subtitle: "الصلاحيات والرواتب والضمانات",
    description: "إدارة فريق العمل بالكامل مع كشوفات الرواتب.",
    features: [
      "صلاحيات متعددة المستويات",
      "كشوفات الرواتب",
      "ضمانات الموظفين",
      "جداول العمل",
    ],
    color: "#4F46E5",
    colorSoft: "#EEF2FF",
    colorFrom: "#6366F1",
    colorTo: "#4338CA",
    path: HOW_IT_WORKS_PATHS.employees,
    image:
      "/images/how-it-works/mod-employees.jpg",
  },
  {
    id: "suppliers",
    icon: "ri-building-2-fill",
    title: "الموردون",
    subtitle: "طلبيات وحسابات الموردين",
    description: "تتبع طلبيات الموردين وكشوفات الحساب.",
    features: [
      "قائمة الموردين",
      "طلبيات الشراء",
      "حسابات الموردين",
      "سجل المدفوعات",
    ],
    color: "#0F766E",
    colorSoft: "#F0FDFA",
    colorFrom: "#14B8A6",
    colorTo: "#0F766E",
    path: HOW_IT_WORKS_PATHS.suppliers,
    image:
      "/images/how-it-works/mod-suppliers.jpg",
  },
  {
    id: "branches",
    icon: "ri-map-pin-2-fill",
    title: "الفروع",
    subtitle: "إدارة متعددة الفروع",
    description: "تابع أداء كل فرع وأدر موارده بشكل مستقل.",
    features: [
      "إضافة فروع متعددة",
      "مسؤول لكل فرع",
      "تقارير الفرع المنفصلة",
      "نقل المنتجات بين الفروع",
    ],
    color: "#9333EA",
    colorSoft: "#FAF5FF",
    colorFrom: "#A855F7",
    colorTo: "#7E22CE",
    path: HOW_IT_WORKS_PATHS.branch,
    image:
      "/images/how-it-works/mod-branches.jpg",
  },
];

export default function ModulesSection() {
  const navigate = useNavigate();

  return (
    <div>
      <div
        style={{
          marginBottom: 28,
          display: "flex",
          alignItems: "flex-end",
          justifyContent: "space-between",
          flexWrap: "wrap",
          gap: 12,
        }}
      >
        <div>
          <div
            style={{
              display: "flex",
              alignItems: "center",
              gap: 10,
              marginBottom: 8,
            }}
          >
            <div
              style={{
                width: 4,
                height: 28,
                borderRadius: 99,
                background: "linear-gradient(180deg, #C2964A, #E8BF7A)",
              }}
            />
            <h2
              style={{
                fontSize: 24,
                fontWeight: 900,
                color: "#0F172A",
                margin: 0,
              }}
            >
              أقسام النظام
            </h2>
          </div>
          <p
            style={{
              fontSize: 14,
              color: "#64748B",
              margin: 0,
              paddingRight: 14,
            }}
          >
            تعرّف على كل قسم واكتشف كيف يساعدك في إدارة عملك
          </p>
        </div>
        <span
          style={{
            fontSize: 12,
            fontWeight: 700,
            color: "#64748B",
            background: "#F1F5F9",
            borderRadius: 99,
            padding: "5px 14px",
            border: "1px solid #E2E8F0",
          }}
        >
          {modules.length} أقسام
        </span>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-[18px]">
        {modules.map((mod) => (
          <div
            key={mod.id}
            role="button"
            tabIndex={0}
            onKeyDown={(e) => {
              if (e.key === "Enter" || e.key === " ") {
                e.preventDefault();
                navigate(mod.path);
              }
            }}
            style={{
              background: "#FFFFFF",
              borderRadius: 18,
              border: "1px solid #F1F5F9",
              overflow: "hidden",
              transition: "transform 0.2s, box-shadow 0.2s",
              cursor: "pointer",
            }}
            onMouseEnter={(e) => {
              (e.currentTarget as HTMLElement).style.transform =
                "translateY(-3px)";
              (e.currentTarget as HTMLElement).style.boxShadow =
                `0 12px 40px -8px ${mod.colorFrom}30`;
              (e.currentTarget as HTMLElement).style.borderColor =
                `${mod.colorFrom}30`;
            }}
            onMouseLeave={(e) => {
              (e.currentTarget as HTMLElement).style.transform = "translateY(0)";
              (e.currentTarget as HTMLElement).style.boxShadow = "none";
              (e.currentTarget as HTMLElement).style.borderColor = "#F1F5F9";
            }}
            onClick={() => navigate(mod.path)}
          >
            <div style={{ height: 150, overflow: "hidden", position: "relative" }}>
              <img
                src={mod.image}
                alt={mod.title}
                style={{
                  width: "100%",
                  height: "100%",
                  objectFit: "cover",
                  objectPosition: "top",
                }}
              />
              <div
                style={{
                  position: "absolute",
                  inset: 0,
                  background: `linear-gradient(180deg, transparent 40%, ${mod.colorTo}cc 100%)`,
                }}
              />
              <div
                style={{
                  position: "absolute",
                  bottom: 12,
                  right: 12,
                  display: "flex",
                  alignItems: "center",
                  gap: 7,
                }}
              >
                <div
                  style={{
                    width: 34,
                    height: 34,
                    borderRadius: 10,
                    background: "rgba(255,255,255,0.95)",
                    display: "flex",
                    alignItems: "center",
                    justifyContent: "center",
                  }}
                >
                  <i
                    className={mod.icon}
                    style={{ fontSize: 17, color: mod.color }}
                  />
                </div>
              </div>
            </div>

            <div style={{ padding: "14px 16px 16px" }}>
              <div style={{ marginBottom: 6 }}>
                <h3
                  style={{
                    fontSize: 15,
                    fontWeight: 800,
                    color: "#0F172A",
                    margin: "0 0 2px",
                  }}
                >
                  {mod.title}
                </h3>
                <span
                  style={{ fontSize: 11.5, color: mod.color, fontWeight: 600 }}
                >
                  {mod.subtitle}
                </span>
              </div>

              <p
                style={{
                  fontSize: 12.5,
                  color: "#64748B",
                  lineHeight: 1.6,
                  margin: "0 0 12px",
                }}
              >
                {mod.description}
              </p>

              <div
                style={{
                  display: "flex",
                  flexWrap: "wrap",
                  gap: 5,
                  marginBottom: 14,
                }}
              >
                {mod.features.map((f, i) => (
                  <span
                    key={i}
                    style={{
                      fontSize: 11,
                      fontWeight: 600,
                      color: mod.color,
                      background: mod.colorSoft,
                      borderRadius: 99,
                      padding: "3px 10px",
                    }}
                  >
                    {f}
                  </span>
                ))}
              </div>

              <button
                type="button"
                style={{
                  width: "100%",
                  background: `linear-gradient(135deg, ${mod.colorFrom}, ${mod.colorTo})`,
                  color: "white",
                  border: "none",
                  borderRadius: 10,
                  padding: "9px 0",
                  fontSize: 12.5,
                  fontWeight: 700,
                  cursor: "pointer",
                  display: "flex",
                  alignItems: "center",
                  justifyContent: "center",
                  gap: 7,
                }}
                onClick={(e) => {
                  e.stopPropagation();
                  navigate(mod.path);
                }}
              >
                <span>انتقل للقسم</span>
                <i className="ri-arrow-left-line" style={{ fontSize: 14 }} />
              </button>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
