import { useNavigate } from "react-router";
import HeroSection from "./components/HeroSection";
import ModulesSection from "./components/ModulesSection";
import WorkflowSection from "./components/WorkflowSection";
import FeaturesSection from "./components/FeaturesSection";
import { HOW_IT_WORKS_PATHS } from "./paths";

const quickLinks = [
  {
    label: "قسم الإيجار",
    path: HOW_IT_WORKS_PATHS.rentalList,
    icon: "ri-key-2-fill",
    color: "#059669",
  },
  {
    label: "قسم البيع",
    path: HOW_IT_WORKS_PATHS.salesCreate,
    icon: "ri-store-3-fill",
    color: "#E11D48",
  },
  {
    label: "قسم التفصيل",
    path: HOW_IT_WORKS_PATHS.tailoring,
    icon: "ri-scissors-cut-fill",
    color: "#7C3AED",
  },
  {
    label: "بحث التسليمات",
    path: HOW_IT_WORKS_PATHS.deliverySearch,
    icon: "ri-search-2-fill",
    color: "#0284C7",
  },
  {
    label: "إدارة الحسابات",
    path: HOW_IT_WORKS_PATHS.payments,
    icon: "ri-money-dollar-circle-fill",
    color: "#D97706",
  },
  {
    label: "الخزنة",
    path: HOW_IT_WORKS_PATHS.cashboxTransactions,
    icon: "ri-safe-2-fill",
    color: "#0891B2",
  },
  {
    label: "الموظفون",
    path: HOW_IT_WORKS_PATHS.employees,
    icon: "ri-user-star-fill",
    color: "#4F46E5",
  },
  {
    label: "الموردون",
    path: HOW_IT_WORKS_PATHS.suppliers,
    icon: "ri-building-2-fill",
    color: "#0F766E",
  },
  {
    label: "الفروع",
    path: HOW_IT_WORKS_PATHS.branch,
    icon: "ri-map-pin-2-fill",
    color: "#9333EA",
  },
];

export default function HowItWorksPage() {
  const navigate = useNavigate();

  return (
    <div
      dir="rtl"
      className="min-h-0 pb-4"
      style={{
        display: "flex",
        flexDirection: "column",
        gap: 36,
      }}
    >
      <HeroSection />

      <div
        style={{
          background: "#FFFFFF",
          borderRadius: 16,
          padding: "18px 24px",
          border: "1px solid #F1F5F9",
        }}
      >
        <div
          style={{
            display: "flex",
            alignItems: "center",
            gap: 6,
            marginBottom: 14,
          }}
        >
          <i
            className="ri-flashlight-fill"
            style={{ fontSize: 15, color: "#C2964A" }}
          />
          <span style={{ fontSize: 13, fontWeight: 700, color: "#475569" }}>
            الوصول السريع للأقسام
          </span>
        </div>
        <div style={{ display: "flex", flexWrap: "wrap", gap: 10 }}>
          {quickLinks.map((l) => (
            <button
              key={l.path}
              type="button"
              onClick={() => navigate(l.path)}
              style={{
                display: "flex",
                alignItems: "center",
                gap: 8,
                padding: "8px 14px",
                borderRadius: 10,
                cursor: "pointer",
                background: `${l.color}10`,
                border: `1px solid ${l.color}25`,
                color: l.color,
                fontSize: 12.5,
                fontWeight: 700,
                transition: "all 0.15s",
                whiteSpace: "nowrap",
              }}
              onMouseEnter={(e) => {
                (e.currentTarget as HTMLElement).style.background =
                  `${l.color}20`;
                (e.currentTarget as HTMLElement).style.borderColor =
                  `${l.color}50`;
              }}
              onMouseLeave={(e) => {
                (e.currentTarget as HTMLElement).style.background =
                  `${l.color}10`;
                (e.currentTarget as HTMLElement).style.borderColor =
                  `${l.color}25`;
              }}
            >
              <i className={l.icon} style={{ fontSize: 15 }} />
              {l.label}
            </button>
          ))}
        </div>
      </div>

      <ModulesSection />
      <WorkflowSection />
      <FeaturesSection />

      <div
        style={{
          background:
            "linear-gradient(135deg, #0C1A3E 0%, #0D1B45 60%, #091437 100%)",
          borderRadius: 20,
          padding: "32px 28px",
          display: "flex",
          alignItems: "center",
          justifyContent: "space-between",
          position: "relative",
          overflow: "hidden",
          flexWrap: "wrap",
          gap: 20,
        }}
      >
        <div
          style={{
            position: "absolute",
            top: -50,
            left: -50,
            width: 250,
            height: 250,
            borderRadius: "50%",
            background:
              "radial-gradient(circle, rgba(194,150,74,0.15) 0%, transparent 70%)",
            pointerEvents: "none",
          }}
        />
        <div style={{ position: "relative", minWidth: 0 }}>
          <h3
            style={{
              fontSize: "clamp(18px, 3vw, 26px)",
              fontWeight: 900,
              color: "#FFFFFF",
              margin: "0 0 10px",
            }}
          >
            جاهز تبدأ استخدام النظام؟
          </h3>
          <p
            style={{
              fontSize: 14,
              color: "rgba(255,255,255,0.55)",
              margin: 0,
            }}
          >
            انتقل للوحة التحكم الرئيسية أو أنشئ أول فاتورة إيجار
          </p>
        </div>
        <div
          style={{
            display: "flex",
            gap: 12,
            flexShrink: 0,
            flexWrap: "wrap",
            position: "relative",
          }}
        >
          <button
            type="button"
            onClick={() => navigate(HOW_IT_WORKS_PATHS.dashboard)}
            style={{
              background: "linear-gradient(135deg, #C2964A, #E8BF7A)",
              color: "#0C1A3E",
              border: "none",
              borderRadius: 12,
              padding: "12px 28px",
              fontSize: 14,
              fontWeight: 800,
              cursor: "pointer",
              display: "flex",
              alignItems: "center",
              gap: 8,
              whiteSpace: "nowrap",
              boxShadow: "0 4px 20px rgba(194,150,74,0.4)",
            }}
          >
            <i className="ri-dashboard-3-fill" />
            لوحة التحكم
          </button>
          <button
            type="button"
            onClick={() => navigate(HOW_IT_WORKS_PATHS.rentalCreate)}
            style={{
              background: "rgba(255,255,255,0.10)",
              color: "white",
              border: "1px solid rgba(255,255,255,0.2)",
              borderRadius: 12,
              padding: "12px 24px",
              fontSize: 14,
              fontWeight: 700,
              cursor: "pointer",
              display: "flex",
              alignItems: "center",
              gap: 8,
              whiteSpace: "nowrap",
            }}
          >
            <i className="ri-key-2-line" />
            ابدأ بالإيجار
          </button>
        </div>
      </div>

      <div style={{ height: 8 }} />
    </div>
  );
}
