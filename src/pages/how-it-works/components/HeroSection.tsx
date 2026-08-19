import { useNavigate } from "react-router";

export default function HeroSection() {
  const navigate = useNavigate();

  const stats = [
    { value: "٩", label: "أقسام رئيسية", icon: "ri-layout-grid-fill" },
    { value: "+٥٠", label: "ميزة متكاملة", icon: "ri-star-fill" },
    { value: "٣", label: "أنواع فواتير", icon: "ri-file-list-3-fill" },
    { value: "٢٤/٧", label: "متابعة فورية", icon: "ri-time-fill" },
  ];

  return (
    <div
      style={{
        background: "linear-gradient(135deg, #0C1A3E 0%, #0D1B45 50%, #091437 100%)",
        borderRadius: 24,
        overflow: "hidden",
        position: "relative",
      }}
    >
      <div
        style={{
          position: "absolute",
          inset: 0,
          pointerEvents: "none",
          overflow: "hidden",
        }}
      >
        <div
          style={{
            position: "absolute",
            top: -60,
            right: -60,
            width: 320,
            height: 320,
            borderRadius: "50%",
            background:
              "radial-gradient(circle, rgba(194,150,74,0.12) 0%, transparent 70%)",
          }}
        />
        <div
          style={{
            position: "absolute",
            bottom: -80,
            left: -40,
            width: 400,
            height: 400,
            borderRadius: "50%",
            background:
              "radial-gradient(circle, rgba(37,99,235,0.12) 0%, transparent 70%)",
          }}
        />
        <svg
          style={{
            position: "absolute",
            inset: 0,
            width: "100%",
            height: "100%",
            opacity: 0.04,
          }}
        >
          <defs>
            <pattern
              id="hiw-dots"
              x="0"
              y="0"
              width="24"
              height="24"
              patternUnits="userSpaceOnUse"
            >
              <circle cx="2" cy="2" r="1.5" fill="white" />
            </pattern>
          </defs>
          <rect width="100%" height="100%" fill="url(#hiw-dots)" />
        </svg>
      </div>

      <div style={{ padding: "48px 48px 0", position: "relative" }}>
        <div
          style={{
            display: "inline-flex",
            alignItems: "center",
            gap: 8,
            marginBottom: 20,
          }}
        >
          <div
            style={{
              background:
                "linear-gradient(135deg, rgba(194,150,74,0.25), rgba(232,191,122,0.15))",
              border: "1px solid rgba(194,150,74,0.35)",
              borderRadius: 99,
              padding: "5px 14px",
              display: "flex",
              alignItems: "center",
              gap: 7,
            }}
          >
            <div
              style={{
                width: 7,
                height: 7,
                borderRadius: "50%",
                background: "#C2964A",
                boxShadow: "0 0 6px rgba(194,150,74,0.8)",
              }}
            />
            <span
              style={{
                fontSize: 12,
                fontWeight: 700,
                color: "#E8BF7A",
                letterSpacing: "0.04em",
              }}
            >
              دليل الاستخدام الشامل
            </span>
          </div>
        </div>

        <div
          className="flex flex-col lg:flex-row gap-10 lg:gap-12 items-start"
          style={{ alignItems: "flex-start" }}
        >
          <div style={{ flex: 1, minWidth: 0 }}>
            <h1
              style={{
                fontSize: "clamp(26px, 4vw, 38px)",
                fontWeight: 900,
                color: "#FFFFFF",
                lineHeight: 1.25,
                margin: "0 0 16px",
              }}
            >
              كيف يعمل{" "}
              <span
                style={{
                  background:
                    "linear-gradient(90deg, #C2964A, #E8BF7A, #C2964A)",
                  WebkitBackgroundClip: "text",
                  WebkitTextFillColor: "transparent",
                }}
              >
                النظام؟
              </span>
            </h1>
            <p
              style={{
                fontSize: 16,
                color: "rgba(255,255,255,0.60)",
                lineHeight: 1.75,
                margin: "0 0 32px",
                maxWidth: 520,
              }}
            >
              منصة إدارة متكاملة تغطي إيجار الأزياء، المبيعات، التفصيل، والمحاسبة —
              كل شيء في مكان واحد لإدارة أعمالك باحترافية وكفاءة عالية.
            </p>

            <div style={{ display: "flex", gap: 12, flexWrap: "wrap" }}>
              <button
                type="button"
                onClick={() => navigate("/dashboard")}
                style={{
                  background: "linear-gradient(135deg, #C2964A, #E8BF7A)",
                  color: "#0C1A3E",
                  border: "none",
                  borderRadius: 12,
                  padding: "12px 24px",
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
                <i className="ri-play-circle-fill" style={{ fontSize: 18 }} />
                ابدأ الاستخدام
              </button>
              <button
                type="button"
                onClick={() => navigate("/orders/rental/create")}
                style={{
                  background: "rgba(255,255,255,0.08)",
                  color: "white",
                  border: "1px solid rgba(255,255,255,0.15)",
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
                <i className="ri-key-2-line" style={{ fontSize: 16 }} />
                فاتورة إيجار جديدة
              </button>
            </div>
          </div>

          <div
            className="hidden md:block w-full max-w-[380px] lg:max-w-none lg:w-[380px] shrink-0 self-end"
            style={{
              height: 240,
              borderRadius: "16px 16px 0 0",
              overflow: "hidden",
              boxShadow: "0 -8px 40px rgba(0,0,0,0.3)",
            }}
          >
            <img
              src="/images/how-it-works/hero.jpg"
              alt="واجهة النظام"
              style={{
                width: "100%",
                height: "100%",
                objectFit: "cover",
                objectPosition: "top",
              }}
            />
          </div>
        </div>
      </div>

      <div
        className="flex flex-wrap"
        style={{
          padding: "0 24px 0",
          borderTop: "1px solid rgba(255,255,255,0.07)",
          marginTop: 32,
        }}
      >
        {stats.map((s, i) => (
          <div
            key={i}
            className="min-w-[140px] flex-1"
            style={{
              padding: "18px 12px",
              display: "flex",
              alignItems: "center",
              gap: 12,
              borderRight:
                i > 0 ? "1px solid rgba(255,255,255,0.07)" : "none",
            }}
          >
            <div
              style={{
                width: 38,
                height: 38,
                borderRadius: 10,
                background: "rgba(194,150,74,0.15)",
                border: "1px solid rgba(194,150,74,0.2)",
                display: "flex",
                alignItems: "center",
                justifyContent: "center",
                flexShrink: 0,
              }}
            >
              <i className={s.icon} style={{ fontSize: 17, color: "#C2964A" }} />
            </div>
            <div>
              <div
                style={{
                  fontSize: 22,
                  fontWeight: 900,
                  color: "#FFFFFF",
                  lineHeight: 1,
                }}
              >
                {s.value}
              </div>
              <div
                style={{
                  fontSize: 11.5,
                  color: "rgba(255,255,255,0.45)",
                  marginTop: 2,
                  fontWeight: 600,
                }}
              >
                {s.label}
              </div>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
