import { useNavigate } from "react-router";
import RevenueChart from "../project-style/RevenueChart";
import { ORDER_TYPE_CONFIG } from "../constants/dashboard.constants";
import type {
  TDashboardSales,
  TDashboardFinancial,
} from "@/api/v2/dashboard/dashboard.types";
import type { TOrder } from "@/api/v2/orders/orders.types";

type Props = {
  sales: TDashboardSales | undefined;
  financial: TDashboardFinancial | undefined;
  recentOrders: TOrder[];
};

export function DashboardChartsSection({ sales, financial, recentOrders }: Props) {
  const navigate = useNavigate();

  return (
    <div className="grid grid-cols-1 xl:grid-cols-3 gap-4">
      <div className="xl:col-span-2">
        <RevenueChart sales={sales} financial={financial} />
      </div>

      <div
        className="rounded-2xl p-5 flex flex-col"
        style={{
          background: "white",
          border: "1px solid var(--color-border)",
          boxShadow: "var(--shadow-card)",
        }}
      >
        <div className="flex items-center justify-between mb-4">
          <div className="flex items-center gap-2">
            <div
              className="w-7 h-7 flex items-center justify-center rounded-xl"
              style={{ background: "rgba(194,150,74,0.12)" }}
            >
              <i
                className="ri-file-list-3-line text-[13px]"
                style={{ color: "var(--color-accent)" }}
              />
            </div>
            <h3 className="font-black text-[13px]" style={{ color: "var(--color-text-primary)" }}>
              أحدث الطلبات
            </h3>
          </div>
          <button
            onClick={() => navigate("/orders/list")}
            className="text-[12px] font-bold cursor-pointer transition-colors"
            style={{ color: "var(--color-accent)" }}
          >
            الكل ←
          </button>
        </div>

        <div className="space-y-2 flex-1">
          {recentOrders.map((order) => {
            const cfg = ORDER_TYPE_CONFIG[order.order_type] ?? ORDER_TYPE_CONFIG.buy;
            return (
              <div
                key={order.id}
                onClick={() => navigate(`/orders/${order.id}`)}
                className="flex items-center gap-3 p-3 rounded-xl cursor-pointer transition-opacity"
                style={{
                  background: cfg.bg,
                  border: `1px solid ${cfg.color}18`,
                }}
                onMouseEnter={(e) => {
                  (e.currentTarget as HTMLElement).style.opacity = "0.82";
                }}
                onMouseLeave={(e) => {
                  (e.currentTarget as HTMLElement).style.opacity = "1";
                }}
              >
                <div
                  className="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 text-white"
                  style={{ background: cfg.color }}
                >
                  <i className={`${cfg.icon} text-sm`} />
                </div>
                <div className="flex-1 min-w-0">
                  <p
                    className="text-[12px] font-bold truncate"
                    style={{ color: "var(--color-text-primary)" }}
                  >
                    {order.client?.name ?? `طلب #${order.id}`}
                  </p>
                  <p
                    className="text-[11px] font-medium"
                    style={{ color: "var(--color-text-muted)" }}
                  >
                    #{order.id} · {order.branch?.name ?? "-"}
                  </p>
                </div>
                <span
                  className="text-[10px] font-bold px-2 py-0.5 rounded-full shrink-0"
                  style={{ background: `${cfg.color}18`, color: cfg.color }}
                >
                  {parseFloat(order.total_price || "0").toLocaleString("ar-EG")} ج.م
                </span>
              </div>
            );
          })}
        </div>

        <button
          onClick={() => navigate("/orders/rental/create")}
          className="mt-3 w-full py-2 text-[12px] font-bold rounded-xl cursor-pointer transition-colors"
          style={{
            border: "1px dashed var(--color-border)",
            color: "var(--color-text-muted)",
          }}
          onMouseEnter={(e) => {
            (e.currentTarget as HTMLElement).style.background = "#F4F7FB";
            (e.currentTarget as HTMLElement).style.color = "var(--color-text-secondary)";
          }}
          onMouseLeave={(e) => {
            (e.currentTarget as HTMLElement).style.background = "transparent";
            (e.currentTarget as HTMLElement).style.color = "var(--color-text-muted)";
          }}
        >
          + طلب جديد
        </button>
      </div>
    </div>
  );
}
