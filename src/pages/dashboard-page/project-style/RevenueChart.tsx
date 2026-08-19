import { useState } from "react";
import { buildSalesByStatusData } from "../utils/dashboard.utils";
import type { TDashboardSales, TDashboardFinancial } from "@/api/v2/dashboard/dashboard.types";

const fmt = (n: number) =>
  n >= 1000 ? `${(n / 1000).toFixed(0)}K` : String(n);

type ViewType = "revenue" | "orders";

type Props = {
  sales: TDashboardSales | undefined;
  financial: TDashboardFinancial | undefined;
};

export default function RevenueChart({ sales, financial }: Props) {
  const [view, setView] = useState<ViewType>("revenue");
  const [hovered, setHovered] = useState<number | null>(null);

  const byStatusData = buildSalesByStatusData(sales?.by_status);
  const statusRevenues = byStatusData.map((d) => (d.إيرادات ?? 0) * 1000);
  const maxRevenue = Math.max(...statusRevenues, sales?.total_revenue ?? 0, 1);
  const maxOrders = Math.max(
    ...byStatusData.map((d) => d.طلبات),
    sales?.order_count ?? 0,
    1
  );

  const totalRevenue = sales?.total_revenue ?? 0;
  const totalExpenses = financial?.total_expenses ?? 0;
  const netProfit = financial?.profit ?? totalRevenue - totalExpenses;

  const BAR_H = 130;

  return (
    <div
      className="rounded-2xl p-5 border"
      style={{
        background: "white",
        borderColor: "var(--color-border)",
        boxShadow: "var(--shadow-card)",
      }}
    >
      <div className="flex items-start justify-between mb-5">
        <div>
          <h3 className="font-black text-base" style={{ color: "var(--color-text-primary)" }}>
            الأداء المالي
          </h3>
          <p className="text-xs mt-0.5" style={{ color: "var(--color-text-muted)" }}>
            المبيعات حسب الحالة
          </p>
        </div>
        <div
          className="flex items-center gap-1 rounded-lg p-1"
          style={{ background: "#F4F7FB", border: "1px solid var(--color-border)" }}
        >
          {(["revenue", "orders"] as ViewType[]).map((v) => (
            <button
              key={v}
              onClick={() => setView(v)}
              className={`px-3 py-1 text-xs font-bold rounded-md transition-all cursor-pointer whitespace-nowrap ${
                view === v ? "bg-white shadow-sm" : ""
              }`}
              style={{
                color: view === v ? "var(--color-text-primary)" : "var(--color-text-muted)",
                border: view === v ? "1px solid var(--color-border)" : "none",
              }}
            >
              {v === "revenue" ? "الإيرادات" : "الطلبات"}
            </button>
          ))}
        </div>
      </div>

      {byStatusData.length === 0 ? (
        <div className="py-12 text-center text-sm" style={{ color: "var(--color-text-muted)" }}>
          لا توجد بيانات مبيعات للفترة المحددة
        </div>
      ) : (
        <>
          <div className="flex items-end gap-2" style={{ height: BAR_H + 32 }}>
            {byStatusData.map((d, idx) => {
              const isHovered = hovered === idx;
              const rev = (d.إيرادات ?? 0) * 1000;
              const orders = d.طلبات ?? 0;
              const hRev = (rev / maxRevenue) * BAR_H;
              const hOrd = (orders / maxOrders) * BAR_H;
              return (
                <div
                  key={d.name}
                  className="flex-1 flex flex-col items-center gap-1 cursor-pointer group relative"
                  onMouseEnter={() => setHovered(idx)}
                  onMouseLeave={() => setHovered(null)}
                >
                  {isHovered && (
                    <div
                      className="absolute z-10 text-white text-xs rounded-xl px-3 py-2 shadow-lg pointer-events-none fade-in"
                      style={{ marginTop: "-80px", background: "#0C2A42" }}
                    >
                      <p className="font-bold mb-1">{d.name}</p>
                      <p style={{ color: "#7DD3FC" }}>إيرادات: {fmt(rev)}</p>
                      <p style={{ color: "#A5B4FC" }}>طلبات: {orders}</p>
                    </div>
                  )}
                  <div className="w-full flex items-end justify-center" style={{ height: BAR_H }}>
                    <div
                      className="w-full rounded-t-xl transition-all duration-500"
                      style={{
                        height: view === "revenue" ? hRev : hOrd,
                        background: isHovered
                          ? "linear-gradient(180deg, #0284C7, #0EA5E9)"
                          : "linear-gradient(180deg, #0EA5E9, #7DD3FC)",
                      }}
                    />
                  </div>
                  <div className="flex flex-col items-center">
                    <span className="text-[10px] font-medium" style={{ color: "var(--color-text-muted)" }}>
                      {d.name.slice(0, 4)}
                    </span>
                    <span className="text-[10px] font-bold" style={{ color: "var(--color-text-secondary)" }}>
                      {view === "revenue" ? fmt(rev) : orders}
                    </span>
                  </div>
                </div>
              );
            })}
          </div>

          <div
            className="mt-4 pt-4 grid grid-cols-3 gap-3 text-center"
            style={{ borderTop: "1px solid var(--color-border)" }}
          >
            <div>
              <p className="text-xs mb-0.5" style={{ color: "var(--color-text-muted)" }}>
                إجمالي الإيرادات
              </p>
              <p className="text-sm font-black" style={{ color: "#059669" }}>
                {fmt(totalRevenue)} ج.م
              </p>
            </div>
            <div>
              <p className="text-xs mb-0.5" style={{ color: "var(--color-text-muted)" }}>
                إجمالي المصاريف
              </p>
              <p className="text-sm font-black" style={{ color: "#EF4444" }}>
                {fmt(totalExpenses)} ج.م
              </p>
            </div>
            <div>
              <p className="text-xs mb-0.5" style={{ color: "var(--color-text-muted)" }}>
                صافي الربح
              </p>
              <p className="text-sm font-black" style={{ color: "#0EA5E9" }}>
                {fmt(netProfit)} ج.م
              </p>
            </div>
          </div>
        </>
      )}
    </div>
  );
}
