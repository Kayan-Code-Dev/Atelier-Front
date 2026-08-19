import type { TDashboardSales, TDashboardClients } from "@/api/v2/dashboard/dashboard.types";
import { useMemo } from "react";

type Props = {
  sales: TDashboardSales | undefined;
  clients: TDashboardClients | undefined;
  growthRates?: { revenue?: number; sales?: number; rental?: number; tailoring?: number };
};

export function DashboardGrowthSection({
  sales,
  clients,
  growthRates,
}: Props) {
  const fallbackRate = clients?.growth_rate ?? 0;
  const growthItems = useMemo(
    () => [
      {
        label: "إجمالي الإيرادات",
        rate: growthRates?.revenue ?? fallbackRate,
        color: "#10B981",
        icon: "ri-money-dollar-circle-line",
      },
      {
        label: "المبيعات المباشرة",
        rate: growthRates?.sales ?? fallbackRate,
        color: "#C2964A",
        icon: "ri-store-3-line",
      },
      {
        label: "قسم الإيجار",
        rate: growthRates?.rental ?? fallbackRate,
        color: "#7C3AED",
        icon: "ri-key-2-line",
      },
      {
        label: "قسم التفصيل",
        rate: growthRates?.tailoring ?? fallbackRate,
        color: "#0D9488",
        icon: "ri-scissors-cut-line",
      },
    ],
    [fallbackRate, growthRates]
  );

  const periodLabel =
    sales?.period?.from && sales?.period?.to
      ? `(${sales.period.from} – ${sales.period.to})`
      : null;

  return (
    <div
      className="rounded-2xl p-4 lg:p-5"
      style={{
        background: "linear-gradient(135deg, #0A1628 0%, #0F1C36 100%)",
        border: "1px solid rgba(255,255,255,0.06)",
      }}
    >
      <div className="flex items-center gap-2 mb-3.5">
        <div
          className="w-6 h-6 flex items-center justify-center rounded-lg"
          style={{ background: "rgba(16,185,129,0.15)" }}
        >
          <i className="ri-trending-up-line text-[13px]" style={{ color: "#10B981" }} />
        </div>
        <span className="text-white text-[12px] font-bold">مقارنة بالفترة السابقة</span>
        {periodLabel && (
          <span
            className="text-[11px] font-medium hidden sm:block"
            style={{ color: "rgba(255,255,255,0.40)" }}
          >
            {periodLabel}
          </span>
        )}
      </div>
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
        {growthItems.map((item) => (
          <div
            key={item.label}
            className="flex items-center gap-3 px-3 py-2.5 rounded-xl"
            style={{
              background: "rgba(255,255,255,0.04)",
              border: "1px solid rgba(255,255,255,0.06)",
            }}
          >
            <div
              className="w-9 h-9 rounded-xl flex items-center justify-center shrink-0"
              style={{ background: `${item.color}18` }}
            >
              <i
                className={`${item.icon} text-[15px]`}
                style={{ color: item.color }}
              />
            </div>
            <div className="min-w-0">
              <p
                className="text-[13px] font-black"
                style={{ color: item.color }}
              >
                +{Number(item.rate) === Math.floor(item.rate) ? item.rate : item.rate.toFixed(1)}%
              </p>
              <p
                className="text-[11px] font-medium truncate"
                style={{ color: "rgba(255,255,255,0.45)" }}
              >
                {item.label}
              </p>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
