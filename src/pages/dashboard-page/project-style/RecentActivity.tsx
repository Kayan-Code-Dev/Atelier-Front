import { useNavigate } from "react-router";
import type { TOrder } from "@/api/v2/orders/orders.types";

const fmt = (n: number) =>
  new Intl.NumberFormat("ar-EG", { maximumFractionDigits: 0 }).format(n);

const typeConfig: Record<string, { icon: string; color: string; bg: string; label: string }> = {
  buy: { icon: "ri-shopping-bag-3-line", color: "#0EA5E9", bg: "#F0F9FF", label: "بيع" },
  rent: { icon: "ri-key-2-line", color: "#0369A1", bg: "#E0F2FE", label: "إيجار" },
  tailoring: { icon: "ri-scissors-cut-line", color: "#0891B2", bg: "#ECFEFF", label: "تفصيل" },
  mixed: { icon: "ri-file-list-3-line", color: "#7C3AED", bg: "#F5F0FE", label: "مختلط" },
};

function getStatusColor(status: string): string {
  if (status === "paid" || status === "delivered" || status === "finished")
    return "bg-emerald-50 text-emerald-700";
  if (status === "partially_paid") return "bg-amber-50 text-amber-700";
  if (status === "canceled") return "bg-red-50 text-red-700";
  return "bg-slate-50 text-slate-600";
}

function getStatusLabel(status: string): string {
  const labels: Record<string, string> = {
    created: "تم الإنشاء",
    paid: "مدفوع",
    partially_paid: "مدفوع جزئياً",
    finished: "منتهي",
    canceled: "ملغي",
    delivered: "تم التسليم",
  };
  return labels[status] ?? status;
}

type Props = {
  orders: TOrder[];
};

export default function RecentActivity({ orders }: Props) {
  const navigate = useNavigate();

  const activities = orders.slice(0, 8);

  const getDetailPath = (order: TOrder) => `/orders/${order.id}`;

  const getTypeConfig = (orderType: string) =>
    typeConfig[orderType] ?? typeConfig.buy;

  return (
    <div
      className="rounded-2xl overflow-hidden border"
      style={{ background: "white", borderColor: "var(--color-border)", boxShadow: "var(--shadow-card)" }}
    >
      <div
        className="flex items-center justify-between px-5 py-4"
        style={{ borderBottom: "1px solid var(--color-border)" }}
      >
        <div className="flex items-center gap-2">
          <div
            className="w-7 h-7 flex items-center justify-center rounded-lg"
            style={{ background: "#F4F7FB", color: "var(--color-text-muted)" }}
          >
            <i className="ri-pulse-line text-sm" />
          </div>
          <h3 className="font-black text-sm" style={{ color: "var(--color-text-primary)" }}>
            آخر الطلبات
          </h3>
        </div>
        <div className="flex items-center gap-1.5">
          {(["buy", "rent", "tailoring"] as const).map((t) => {
            const cfg = typeConfig[t];
            return (
              <span
                key={t}
                className="text-[10px] font-bold px-2 py-0.5 rounded-full"
                style={{ background: cfg.bg, color: cfg.color }}
              >
                {cfg.label}
              </span>
            );
          })}
        </div>
      </div>

      {activities.length === 0 ? (
        <div className="py-12 text-center text-sm" style={{ color: "var(--color-text-muted)" }}>
          لا توجد طلبات حديثة
        </div>
      ) : (
        <div>
          {activities.map((item, idx) => {
            const cfg = getTypeConfig(item.order_type);
            return (
              <div
                key={item.id}
                onClick={() => navigate(getDetailPath(item))}
                className="flex items-center gap-3 px-5 py-3 cursor-pointer transition-colors"
                style={{
                  borderBottom:
                    idx < activities.length - 1 ? "1px solid var(--color-border)" : "none",
                }}
                onMouseEnter={(e) => {
                  (e.currentTarget as HTMLElement).style.background = "#F8FAFC";
                }}
                onMouseLeave={(e) => {
                  (e.currentTarget as HTMLElement).style.background = "transparent";
                }}
              >
                <span
                  className="text-xs font-bold w-4 shrink-0 text-center"
                  style={{ color: "var(--color-text-muted)" }}
                >
                  {idx + 1}
                </span>

                <div
                  className="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 text-sm"
                  style={{ background: cfg.bg, color: cfg.color }}
                >
                  <i className={cfg.icon} />
                </div>

                <div className="flex-1 min-w-0">
                  <p
                    className="text-sm font-bold truncate"
                    style={{ color: "var(--color-text-primary)" }}
                  >
                    {item.client?.name ?? `طلب #${item.id}`}
                  </p>
                  <p
                    className="text-xs truncate"
                    style={{ color: "var(--color-text-muted)" }}
                  >
                    #{item.id} · {item.branch?.name ?? "-"}
                  </p>
                </div>

                <span
                  className={`text-[11px] font-bold px-2 py-0.5 rounded-full whitespace-nowrap hidden sm:inline ${getStatusColor(item.status)}`}
                >
                  {getStatusLabel(item.status)}
                </span>

                <p className="text-sm font-black shrink-0" style={{ color: "var(--color-text-secondary)" }}>
                  {fmt(parseFloat(item.total_price) || 0)}
                  <span className="text-xs font-normal mr-0.5" style={{ color: "var(--color-text-muted)" }}>
                    ج.م
                  </span>
                </p>
              </div>
            );
          })}
        </div>
      )}

      <div
        className="px-5 py-3 grid grid-cols-3 gap-2 text-center text-xs"
        style={{ borderTop: "1px solid var(--color-border)" }}
      >
        <button
          onClick={() => navigate("/sales/invoices")}
          className="py-1.5 rounded-lg font-semibold cursor-pointer transition-colors"
          style={{ color: "var(--color-text-muted)" }}
          onMouseEnter={(e) => {
            (e.currentTarget as HTMLElement).style.background = "#F4F7FB";
          }}
          onMouseLeave={(e) => {
            (e.currentTarget as HTMLElement).style.background = "transparent";
          }}
        >
          فواتير البيع
        </button>
        <button
          onClick={() => navigate("/orders/list")}
          className="py-1.5 rounded-lg font-semibold cursor-pointer transition-colors"
          style={{ color: "var(--color-text-muted)" }}
          onMouseEnter={(e) => {
            (e.currentTarget as HTMLElement).style.background = "#F4F7FB";
          }}
          onMouseLeave={(e) => {
            (e.currentTarget as HTMLElement).style.background = "transparent";
          }}
        >
          كل الطلبات
        </button>
        <button
          onClick={() => navigate("/tailoring/orders")}
          className="py-1.5 rounded-lg font-semibold cursor-pointer transition-colors"
          style={{ color: "var(--color-text-muted)" }}
          onMouseEnter={(e) => {
            (e.currentTarget as HTMLElement).style.background = "#F4F7FB";
          }}
          onMouseLeave={(e) => {
            (e.currentTarget as HTMLElement).style.background = "transparent";
          }}
        >
          أوامر التفصيل
        </button>
      </div>
    </div>
  );
}
