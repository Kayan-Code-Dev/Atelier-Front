import type {
  TDashboardSales,
  TDashboardClients,
  TDashboardPayments,
  TDashboardInventory,
  TDashboardFinancial,
} from "@/api/v2/dashboard/dashboard.types";

const fmt = (n: number | null | undefined) =>
  n != null ? new Intl.NumberFormat("ar-EG", { maximumFractionDigits: 0 }).format(n) : "0";

type Props = {
  sales: TDashboardSales | undefined;
  clients: TDashboardClients | undefined;
  payments: TDashboardPayments | undefined;
  inventory: TDashboardInventory | undefined;
  financial: TDashboardFinancial | undefined;
};

const cards = (
  s: TDashboardSales | undefined,
  c: TDashboardClients | undefined,
  p: TDashboardPayments | undefined,
  inv: TDashboardInventory | undefined,
  fin: TDashboardFinancial | undefined
) => [
  {
    label: "إجمالي الإيرادات",
    value: s?.total_revenue ?? 0,
    suffix: "ج.م",
    rate: c?.growth_rate ?? 0,
    icon: "ri-money-dollar-circle-line",
    accent: "#10B981",
    accentBg: "rgba(16,185,129,0.10)",
    gradient: "linear-gradient(135deg, #10B981, #059669)",
    showRate: true,
    rateIsPct: false,
  },
  {
    label: "إجمالي الطلبات",
    value: s?.order_count ?? 0,
    suffix: "طلب",
    rate: 0,
    icon: "ri-file-list-3-line",
    accent: "#0284C7",
    accentBg: "rgba(2,132,199,0.10)",
    gradient: "linear-gradient(135deg, #0369A1, #0EA5E9)",
    showRate: false,
    rateIsPct: false,
  },
  {
    label: "إجمالي المدفوعات",
    value: p?.total_payments ?? 0,
    suffix: "ج.م",
    rate: 0,
    icon: "ri-bank-card-line",
    accent: "#C2964A",
    accentBg: "rgba(194,150,74,0.10)",
    gradient: "linear-gradient(135deg, #B8862A, #E8BF7A)",
    showRate: false,
    rateIsPct: false,
  },
  {
    label: "العملاء النشطون",
    value: c?.active_clients ?? c?.total_clients ?? 0,
    suffix: "عميل",
    rate: c?.growth_rate ?? 0,
    icon: "ri-user-add-line",
    accent: "#0D9488",
    accentBg: "rgba(13,148,136,0.10)",
    gradient: "linear-gradient(135deg, #0D9488, #14B8A6)",
    showRate: true,
    rateIsPct: false,
  },
  {
    label: "المنتجات المتاحة",
    value: inv?.available ?? 0,
    suffix: "قطعة",
    rate: inv?.utilization_rate ?? 0,
    icon: "ri-price-tag-3-line",
    accent: "#0891B2",
    accentBg: "rgba(8,145,178,0.10)",
    gradient: "linear-gradient(135deg, #0891B2, #06B6D4)",
    showRate: true,
    rateIsPct: true,
  },
  {
    label: "صافي الربح",
    value: fin?.profit ?? 0,
    suffix: "ج.م",
    rate: fin?.profit_margin ?? 0,
    icon: "ri-line-chart-line",
    accent: "#7C3AED",
    accentBg: "rgba(124,58,237,0.10)",
    gradient: "linear-gradient(135deg, #7C3AED, #8B5CF6)",
    showRate: true,
    rateIsPct: true,
  },
];

export default function DashboardStats({ sales, clients, payments, inventory, financial }: Props) {
  const items = cards(sales, clients, payments, inventory, financial);

  return (
    <div className="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3 lg:gap-4">
      {items.map((card) => (
        <div
          key={card.label}
          className="kpi-card"
          style={{ "--after-gradient": card.gradient } as React.CSSProperties}
        >
          <div
            className="absolute bottom-0 right-0 left-0 h-[3px] rounded-b-[14px]"
            style={{ background: card.gradient, opacity: 0.8 }}
          />
          <div className="flex items-center justify-between mb-3">
            <div
              className="w-9 h-9 flex items-center justify-center rounded-xl shrink-0"
              style={{ background: card.gradient, boxShadow: `0 3px 10px ${card.accent}30` }}
            >
              <i className={`${card.icon} text-white text-[15px]`} />
            </div>
            {card.showRate && (card.rate > 0 || card.rateIsPct) && (
              <div
                className="flex items-center gap-0.5 text-[10.5px] font-bold px-2 py-0.5 rounded-full"
                style={{ background: card.accentBg, color: card.accent }}
              >
                {!card.rateIsPct && <i className="ri-arrow-up-line text-[10px]" />}
                <span>{card.rateIsPct ? `${card.rate}%` : `+${card.rate}%`}</span>
              </div>
            )}
          </div>
          <p
            className="text-[19px] font-black leading-none tracking-tight"
            style={{ color: "var(--color-text-primary)" }}
          >
            {fmt(card.value)}
          </p>
          {card.suffix && (
            <span className="text-[10px] font-semibold ml-1" style={{ color: "var(--color-text-muted)" }}>
              {card.suffix}
            </span>
          )}
          <p
            className="text-[11.5px] font-semibold mt-1.5 leading-snug"
            style={{ color: "var(--color-text-secondary)" }}
          >
            {card.label}
          </p>
        </div>
      ))}
    </div>
  );
}
