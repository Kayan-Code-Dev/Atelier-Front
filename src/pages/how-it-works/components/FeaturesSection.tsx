interface Feature {
  icon: string;
  title: string;
  description: string;
  color: string;
  colorSoft: string;
}

const features: Feature[] = [
  {
    icon: "ri-search-2-fill",
    title: "بحث موحّد للتسليمات",
    description:
      "ابحث في كل الفواتير دفعةً واحدة بدل التنقل بين الأقسام — إيجار وبيع وتفصيل في شاشة واحدة.",
    color: "#0284C7",
    colorSoft: "#F0F9FF",
  },
  {
    icon: "ri-notification-3-fill",
    title: "تنبيهات ذكية فورية",
    description:
      "النظام يراقب التواريخ تلقائياً ويُنبّهك قبل موعد الإرجاع وعند وصول طلبيات الموردين.",
    color: "#D97706",
    colorSoft: "#FFFBEB",
  },
  {
    icon: "ri-bar-chart-box-fill",
    title: "تقارير ورسوم بيانية",
    description:
      "تقارير الإيرادات والمصروفات والأداء برسوم بيانية تفاعلية — قرّر بناءً على أرقام حقيقية.",
    color: "#059669",
    colorSoft: "#ECFDF5",
  },
  {
    icon: "ri-layout-masonry-fill",
    title: "لوحة كانبان للتفصيل",
    description:
      "تتبّع أوامر الخياطة بصرياً عبر مراحل الإنتاج — سحب وإفلات بسيط لتحديث الحالة.",
    color: "#7C3AED",
    colorSoft: "#F5F3FF",
  },
  {
    icon: "ri-shield-user-fill",
    title: "صلاحيات متعددة المستويات",
    description:
      "كل موظف يرى فقط ما يحتاجه — مدير كامل الصلاحيات، كاشير محدود، مشرف إنتاج.",
    color: "#4F46E5",
    colorSoft: "#EEF2FF",
  },
  {
    icon: "ri-map-pin-2-fill",
    title: "دعم متعدد الفروع",
    description:
      "أدر فروعك المتعددة من مكان واحد مع تقارير منفصلة لكل فرع وتحويل المنتجات بينها.",
    color: "#9333EA",
    colorSoft: "#FAF5FF",
  },
  {
    icon: "ri-printer-fill",
    title: "طباعة الفواتير والتقارير",
    description:
      "طباعة فواتير الإيجار والبيع والتفصيل مباشرةً من النظام بتنسيق احترافي.",
    color: "#0891B2",
    colorSoft: "#ECFEFF",
  },
  {
    icon: "ri-safe-2-fill",
    title: "خزنة يومية دقيقة",
    description:
      "إغلاق يومي للخزنة مع قيود محاسبية تلقائية وكشف معاملات مفصّل لكل فرع.",
    color: "#0F766E",
    colorSoft: "#F0FDFA",
  },
];

export default function FeaturesSection() {
  return (
    <div>
      <div style={{ marginBottom: 24 }}>
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
            المزايا الرئيسية
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
          ما يميّز النظام عن سواه ويجعله الاختيار المثالي لإدارة عملك
        </p>
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-[14px]">
        {features.map((f, i) => (
          <div
            key={i}
            style={{
              background: "#FFFFFF",
              borderRadius: 16,
              padding: "18px 16px",
              border: "1px solid #F1F5F9",
              transition: "transform 0.2s, border-color 0.2s",
            }}
            onMouseEnter={(e) => {
              (e.currentTarget as HTMLElement).style.transform =
                "translateY(-2px)";
              (e.currentTarget as HTMLElement).style.borderColor = `${f.color}40`;
            }}
            onMouseLeave={(e) => {
              (e.currentTarget as HTMLElement).style.transform = "translateY(0)";
              (e.currentTarget as HTMLElement).style.borderColor = "#F1F5F9";
            }}
          >
            <div
              style={{
                width: 42,
                height: 42,
                borderRadius: 12,
                background: f.colorSoft,
                display: "flex",
                alignItems: "center",
                justifyContent: "center",
                marginBottom: 12,
                border: `1px solid ${f.color}20`,
              }}
            >
              <i className={f.icon} style={{ fontSize: 19, color: f.color }} />
            </div>
            <h4
              style={{
                fontSize: 13.5,
                fontWeight: 800,
                color: "#1E293B",
                margin: "0 0 6px",
              }}
            >
              {f.title}
            </h4>
            <p
              style={{
                fontSize: 12,
                color: "#64748B",
                lineHeight: 1.65,
                margin: 0,
              }}
            >
              {f.description}
            </p>
          </div>
        ))}
      </div>
    </div>
  );
}
