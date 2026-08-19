import { useState } from "react";
import type { CSSProperties } from "react";
import { useNavigate } from "react-router";
import { HOW_IT_WORKS_PATHS } from "../paths";

interface WorkflowStep {
  number: string;
  icon: string;
  title: string;
  description: string;
  detail: string;
  tip?: string;
}

interface Workflow {
  id: string;
  label: string;
  icon: string;
  color: string;
  colorSoft: string;
  colorFrom: string;
  colorTo: string;
  title: string;
  description: string;
  path: string;
  steps: WorkflowStep[];
}

const workflows: Workflow[] = [
  {
    id: "rental",
    label: "الإيجار",
    icon: "ri-key-2-fill",
    color: "#059669",
    colorSoft: "#ECFDF5",
    colorFrom: "#10B981",
    colorTo: "#047857",
    title: "دورة الإيجار الكاملة",
    description: "من استقبال العميل وحتى إرجاع القطعة — كل خطوة موثّقة",
    path: HOW_IT_WORKS_PATHS.rentalList,
    steps: [
      {
        number: "١",
        icon: "ri-user-add-line",
        title: "استقبال العميل",
        description: "أضف بيانات العميل أو اختر من القائمة",
        detail:
          "اذهب لقسم الإيجار → اضغط «فاتورة جديدة» → أدخل اسم العميل أو رقم الجوال للبحث السريع.",
        tip: "يمكن حفظ العميل تلقائياً عند إنشاء الفاتورة",
      },
      {
        number: "٢",
        icon: "ri-add-circle-line",
        title: "إنشاء الفاتورة",
        description: "حدد المنتجات وتواريخ الإيجار والأسعار",
        detail:
          "أضف القطع المؤجَّرة مع السعر، حدد تاريخ التسليم وتاريخ الإرجاع، وأضف أي ملاحظات خاصة.",
      },
      {
        number: "٣",
        icon: "ri-money-dollar-box-line",
        title: "تسجيل الدفعة",
        description: "سجّل المبلغ المدفوع مقدماً والمتبقي",
        detail:
          "يمكن تقسيم الدفع — دفعة مقدمة عند التسليم والباقي عند الإرجاع. كل دفعة تُسجّل تلقائياً في الحسابات.",
        tip: "الرصيد المتبقي يظهر دائماً في تفاصيل الفاتورة",
      },
      {
        number: "٤",
        icon: "ri-truck-line",
        title: "تأكيد التسليم",
        description: "سجّل التسليم عند استلام العميل للقطعة",
        detail:
          "من «بحث التسليمات» ابحث عن الفاتورة → اضغط «تأكيد التسليم» → تتغير الحالة فوراً.",
      },
      {
        number: "٥",
        icon: "ri-arrow-go-back-line",
        title: "تأكيد الإرجاع",
        description: "عند عودة القطعة سجّل الإرجاع بنقرة",
        detail:
          "من «بحث التسليمات» → اضغط «تأكيد الإرجاع» → النظام يحسب التأخير إن وجد ويُنهي دورة الإيجار.",
        tip: "الفواتير المتأخرة تظهر تلقائياً في تنبيهات الإرجاع المتأخر",
      },
    ],
  },
  {
    id: "sales",
    label: "البيع",
    icon: "ri-store-3-fill",
    color: "#E11D48",
    colorSoft: "#FFF1F2",
    colorFrom: "#F43F5E",
    colorTo: "#BE123C",
    title: "دورة البيع المباشر",
    description: "من طلب العميل وحتى إتمام عملية البيع",
    path: HOW_IT_WORKS_PATHS.salesCreate,
    steps: [
      {
        number: "١",
        icon: "ri-calendar-schedule-line",
        title: "حجز الموعد (اختياري)",
        description: "استقبل الطلب المسبق في جدول المواعيد",
        detail:
          "اذهب لـ «جدول المواعيد» أو قسم البيع → أضف موعداً جديداً بتاريخ ووقت المجيء.",
      },
      {
        number: "٢",
        icon: "ri-file-add-line",
        title: "إنشاء فاتورة البيع",
        description: "أضف المنتجات والأسعار وبيانات العميل",
        detail:
          "قسم البيع → «إنشاء فاتورة بيع» → أضف المنتجات والكميات والخصومات إن وجدت.",
      },
      {
        number: "٣",
        icon: "ri-printer-line",
        title: "الطباعة والتسليم",
        description: "اطبع الفاتورة وسلّم المنتج للعميل",
        detail:
          "من تفاصيل الفاتورة اضغط «طباعة» → سلّم المنتج وسجّل الدفع الكامل.",
        tip: "يمكن إرسال الفاتورة رقمياً",
      },
    ],
  },
  {
    id: "tailoring",
    label: "التفصيل",
    icon: "ri-scissors-cut-fill",
    color: "#7C3AED",
    colorSoft: "#F5F3FF",
    colorFrom: "#8B5CF6",
    colorTo: "#6D28D9",
    title: "دورة التفصيل والخياطة",
    description: "من أخذ المقاسات وحتى تسليم الملبس الجاهز",
    path: HOW_IT_WORKS_PATHS.tailoring,
    steps: [
      {
        number: "١",
        icon: "ri-ruler-2-line",
        title: "أخذ المقاسات",
        description: "سجّل مقاسات العميل التفصيلية",
        detail:
          "التفصيل → «أمر تفصيل جديد» → أدخل كل المقاسات مع الوصف الكامل للقطعة المطلوبة.",
      },
      {
        number: "٢",
        icon: "ri-layout-masonry-line",
        title: "متابعة الإنتاج بالكانبان",
        description: "تتبّع كل مرحلة من مراحل التفصيل",
        detail:
          "لوحة الكانبان تعرض الأوامر في مراحل الإنتاج. حرّك البطاقات بين المراحل لتحديث الحالة.",
        tip: "كل تحريك للبطاقة يُسجَّل مع الوقت",
      },
      {
        number: "٣",
        icon: "ri-check-double-line",
        title: "تسليم الملبس الجاهز",
        description: "عند الاكتمال سلّم القطعة وسجّل الدفع",
        detail:
          "عند جاهزية الأمر → من بحث التسليمات أو تفاصيل الطلب → تأكيد الاستلام عند الحاجة.",
      },
    ],
  },
];

export default function WorkflowSection() {
  const [activeTab, setActiveTab] = useState("rental");
  const [expandedStep, setExpandedStep] = useState<number | null>(null);
  const navigate = useNavigate();

  const wf = workflows.find((w) => w.id === activeTab)!;

  return (
    <div>
      <div
        style={{
          marginBottom: 24,
          display: "flex",
          alignItems: "flex-end",
          justifyContent: "space-between",
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
              مراحل العمل
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
            خطوات تفصيلية لكل دورة عمل رئيسية في النظام
          </p>
        </div>
      </div>

      <div
        className="flex flex-wrap gap-2.5"
        style={{ marginBottom: 24 }}
      >
        {workflows.map((w) => (
          <button
            key={w.id}
            type="button"
            onClick={() => {
              setActiveTab(w.id);
              setExpandedStep(null);
            }}
            style={{
              display: "flex",
              alignItems: "center",
              gap: 8,
              padding: "10px 20px",
              borderRadius: 12,
              cursor: "pointer",
              fontWeight: 700,
              fontSize: 13,
              transition: "all 0.2s",
              ...(activeTab === w.id
                ? {
                    background: `linear-gradient(135deg, ${w.colorFrom}, ${w.colorTo})`,
                    color: "#FFFFFF",
                    boxShadow: `0 4px 16px ${w.colorFrom}40`,
                    border: "none",
                  }
                : {
                    background: "#F8FAFC",
                    color: "#64748B",
                    boxShadow: "none",
                    border: "1px solid #E2E8F0",
                  }),
            } as CSSProperties}
          >
            <i className={w.icon} style={{ fontSize: 16 }} />
            {w.label}
          </button>
        ))}
      </div>

      <div
        style={{
          background: "#FFFFFF",
          borderRadius: 20,
          border: "1px solid #F1F5F9",
          overflow: "hidden",
        }}
      >
        <div
          style={{
            background: `linear-gradient(135deg, ${wf.colorFrom}15, ${wf.colorTo}08)`,
            borderBottom: `1px solid ${wf.colorFrom}20`,
            padding: "20px 28px",
            display: "flex",
            alignItems: "center",
            justifyContent: "space-between",
            flexWrap: "wrap",
            gap: 12,
          }}
        >
          <div style={{ display: "flex", alignItems: "center", gap: 14 }}>
            <div
              style={{
                width: 48,
                height: 48,
                borderRadius: 14,
                background: `linear-gradient(135deg, ${wf.colorFrom}, ${wf.colorTo})`,
                display: "flex",
                alignItems: "center",
                justifyContent: "center",
                boxShadow: `0 4px 16px ${wf.colorFrom}40`,
              }}
            >
              <i className={wf.icon} style={{ fontSize: 22, color: "#FFFFFF" }} />
            </div>
            <div>
              <h3
                style={{
                  fontSize: 17,
                  fontWeight: 800,
                  color: "#0F172A",
                  margin: "0 0 3px",
                }}
              >
                {wf.title}
              </h3>
              <p style={{ fontSize: 13, color: "#64748B", margin: 0 }}>
                {wf.description}
              </p>
            </div>
          </div>
          <button
            type="button"
            onClick={() => navigate(wf.path)}
            style={{
              background: `linear-gradient(135deg, ${wf.colorFrom}, ${wf.colorTo})`,
              color: "white",
              border: "none",
              borderRadius: 10,
              padding: "9px 18px",
              fontSize: 12.5,
              fontWeight: 700,
              cursor: "pointer",
              display: "flex",
              alignItems: "center",
              gap: 7,
              whiteSpace: "nowrap",
            }}
          >
            افتح القسم
            <i className="ri-arrow-left-line" />
          </button>
        </div>

        <div style={{ padding: "8px 0" }}>
          {wf.steps.map((step, i) => {
            const isExpanded = expandedStep === i;
            const isLast = i === wf.steps.length - 1;
            return (
              <div key={i}>
                <div
                  role="button"
                  tabIndex={0}
                  onKeyDown={(e) => {
                    if (e.key === "Enter" || e.key === " ")
                      setExpandedStep(isExpanded ? null : i);
                  }}
                  onClick={() => setExpandedStep(isExpanded ? null : i)}
                  style={{
                    display: "flex",
                    alignItems: "flex-start",
                    gap: 0,
                    padding: "0 28px",
                    cursor: "pointer",
                    transition: "background 0.15s",
                  }}
                  onMouseEnter={(e) => {
                    (e.currentTarget as HTMLElement).style.background = "#FAFAFA";
                  }}
                  onMouseLeave={(e) => {
                    (e.currentTarget as HTMLElement).style.background = "transparent";
                  }}
                >
                  <div
                    style={{
                      display: "flex",
                      flexDirection: "column",
                      alignItems: "center",
                      marginInlineEnd: 18,
                      paddingTop: 16,
                    }}
                  >
                    <div
                      style={{
                        width: 36,
                        height: 36,
                        borderRadius: "50%",
                        flexShrink: 0,
                        background: isExpanded
                          ? `linear-gradient(135deg, ${wf.colorFrom}, ${wf.colorTo})`
                          : wf.colorSoft,
                        border: `2px solid ${isExpanded ? wf.colorFrom : `${wf.colorFrom}40`}`,
                        display: "flex",
                        alignItems: "center",
                        justifyContent: "center",
                        transition: "all 0.2s",
                      }}
                    >
                      <i
                        className={step.icon}
                        style={{
                          fontSize: 16,
                          color: isExpanded ? "#FFFFFF" : wf.color,
                        }}
                      />
                    </div>
                    {!isLast ? (
                      <div
                        style={{
                          width: 2,
                          flex: 1,
                          minHeight: 20,
                          background: `${wf.colorFrom}25`,
                          margin: "4px 0",
                        }}
                      />
                    ) : null}
                  </div>

                  <div style={{ flex: 1, padding: "16px 0 14px", minWidth: 0 }}>
                    <div
                      style={{
                        display: "flex",
                        alignItems: "center",
                        gap: 10,
                        marginBottom: 3,
                        flexWrap: "wrap",
                      }}
                    >
                      <span
                        style={{
                          fontSize: 10.5,
                          fontWeight: 800,
                          color: wf.color,
                          background: wf.colorSoft,
                          borderRadius: 99,
                          padding: "2px 9px",
                        }}
                      >
                        الخطوة {step.number}
                      </span>
                      <span
                        style={{
                          fontSize: 14.5,
                          fontWeight: 800,
                          color: "#1E293B",
                        }}
                      >
                        {step.title}
                      </span>
                    </div>
                    <p
                      style={{
                        fontSize: 13,
                        color: "#64748B",
                        margin: 0,
                        lineHeight: 1.5,
                      }}
                    >
                      {step.description}
                    </p>

                    {isExpanded ? (
                      <div
                        style={{
                          marginTop: 12,
                          padding: "12px 14px",
                          background: `${wf.colorFrom}08`,
                          borderRadius: 10,
                          borderInlineEnd: `3px solid ${wf.colorFrom}`,
                        }}
                      >
                        <p
                          style={{
                            fontSize: 13,
                            color: "#334155",
                            margin: "0 0 8px",
                            lineHeight: 1.65,
                          }}
                        >
                          {step.detail}
                        </p>
                        {step.tip ? (
                          <div
                            style={{
                              display: "flex",
                              alignItems: "flex-start",
                              gap: 7,
                            }}
                          >
                            <i
                              className="ri-lightbulb-line"
                              style={{
                                fontSize: 14,
                                color: "#F59E0B",
                                marginTop: 1,
                              }}
                            />
                            <span
                              style={{
                                fontSize: 12,
                                color: "#92400E",
                                fontWeight: 600,
                              }}
                            >
                              {step.tip}
                            </span>
                          </div>
                        ) : null}
                      </div>
                    ) : null}
                  </div>

                  <div style={{ padding: "18px 0 0 0", flexShrink: 0 }}>
                    <i
                      className={`ri-${isExpanded ? "arrow-up-s" : "arrow-down-s"}-line`}
                      style={{ fontSize: 18, color: "#CBD5E1" }}
                    />
                  </div>
                </div>
              </div>
            );
          })}
        </div>
      </div>
    </div>
  );
}
