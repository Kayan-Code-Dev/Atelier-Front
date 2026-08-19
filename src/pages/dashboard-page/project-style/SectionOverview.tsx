import { useNavigate } from "react-router";
import type { TDashboardFinancial } from "@/api/v2/dashboard/dashboard.types";

const fmt = (n: number) =>
  new Intl.NumberFormat("ar-EG", { maximumFractionDigits: 0 }).format(n);

type Props = {
  financial: TDashboardFinancial | undefined;
};

const BRANCH_COLORS = ["#2563EB", "#059669", "#D97706", "#7C3AED", "#DC2626"];

export default function SectionOverview({ financial }: Props) {
  const navigate = useNavigate();

  const totalIncome = financial?.total_income ?? 0;
  const totalExpenses = financial?.total_expenses ?? 0;
  const balance = (financial?.cashbox_balances ?? []).reduce((s, cb) => s + (cb.balance ?? 0), 0);
  const netMovement = totalIncome - totalExpenses;

  const cashboxes = financial?.cashbox_balances ?? [];
  const maxBalance = Math.max(...cashboxes.map((c) => c.balance ?? 0), 1);

  return (
    <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
      <div
        className="rounded-2xl p-5 border"
        style={{ background: "white", borderColor: "var(--color-border)", boxShadow: "var(--shadow-card)" }}
      >
        <div className="flex items-center justify-between mb-4">
          <div className="flex items-center gap-2">
            <div className="w-8 h-8 flex items-center justify-center rounded-xl text-white" style={{ background: "#0C2A42" }}>
              <i className="ri-safe-line text-sm" />
            </div>
            <h3 className="font-black text-sm" style={{ color: "var(--color-text-primary)" }}>
              الخزنة
            </h3>
          </div>
          <button
            onClick={() => navigate("/cashboxes")}
            className="text-xs font-bold cursor-pointer transition-colors"
            style={{ color: "#0284C7" }}
          >
            كشف المعاملات ←
          </button>
        </div>

        <div
          className="rounded-xl p-4 mb-4 text-white text-center"
          style={{ background: "linear-gradient(135deg, #0369A1, #0EA5E9)" }}
        >
          <p className="text-xs mb-1" style={{ color: "rgba(255,255,255,0.70)" }}>
            إجمالي أرصدة الصناديق
          </p>
          <p className="text-3xl font-black">{fmt(balance)}</p>
          <p className="text-xs mt-1" style={{ color: "rgba(255,255,255,0.60)" }}>
            جنيه مصري
          </p>
        </div>

        <div className="grid grid-cols-3 gap-3">
          {[
            { label: "إجمالي الإيرادات", value: totalIncome, color: "text-emerald-600", bg: "bg-emerald-50", icon: "ri-arrow-down-line" },
            { label: "إجمالي المصاريف", value: totalExpenses, color: "text-red-500", bg: "bg-red-50", icon: "ri-arrow-up-line" },
            { label: "صافي الحركة", value: netMovement, color: "text-sky-600", bg: "bg-sky-50", icon: "ri-scales-line" },
          ].map((item) => (
            <div key={item.label} className={`${item.bg} rounded-xl p-3 text-center`}>
              <div className="flex items-center justify-center mb-1">
                <i className={`${item.icon} text-sm ${item.color}`} />
              </div>
              <p className={`text-sm font-black ${item.color}`}>{fmt(item.value)}</p>
              <p className="text-[10px] mt-0.5" style={{ color: "var(--color-text-muted)" }}>
                {item.label}
              </p>
            </div>
          ))}
        </div>
      </div>

      <div
        className="rounded-2xl p-5 border"
        style={{ background: "white", borderColor: "var(--color-border)", boxShadow: "var(--shadow-card)" }}
      >
        <div className="flex items-center justify-between mb-4">
          <div className="flex items-center gap-2">
            <div className="w-8 h-8 flex items-center justify-center rounded-xl bg-amber-100 text-amber-600">
              <i className="ri-bank-line text-sm" />
            </div>
            <h3 className="font-black text-sm" style={{ color: "var(--color-text-primary)" }}>
              أرصدة الصناديق
            </h3>
          </div>
          <button
            onClick={() => navigate("/cashboxes")}
            className="text-xs font-bold cursor-pointer transition-colors"
            style={{ color: "#D97706" }}
          >
            إدارة الصناديق ←
          </button>
        </div>

        {cashboxes.length === 0 ? (
          <div className="py-8 text-center text-sm" style={{ color: "var(--color-text-muted)" }}>
            لا توجد صناديق
          </div>
        ) : (
          <div className="space-y-3">
            {cashboxes.map((cb, idx) => {
              const pct = maxBalance > 0 ? Math.round(((cb.balance ?? 0) / maxBalance) * 100) : 0;
              const color = BRANCH_COLORS[idx % BRANCH_COLORS.length];
              return (
                <div key={cb.cashbox_id}>
                  <div className="flex items-center justify-between mb-1.5">
                    <div className="flex items-center gap-2 min-w-0">
                      <span
                        className="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-black text-white shrink-0"
                        style={{ background: color }}
                      >
                        {idx + 1}
                      </span>
                      <span className="text-xs font-semibold truncate" style={{ color: "var(--color-text-secondary)" }}>
                        {cb.name}
                      </span>
                    </div>
                    <span className="text-[11px] font-black shrink-0" style={{ color: "var(--color-text-secondary)" }}>
                      {fmt(cb.balance ?? 0)} ج.م
                    </span>
                  </div>
                  <div className="w-full h-1.5 bg-gray-100 rounded-full overflow-hidden">
                    <div
                      className="h-full rounded-full transition-all duration-700"
                      style={{ width: `${pct}%`, background: color }}
                    />
                  </div>
                </div>
              );
            })}
          </div>
        )}

        <div
          className="mt-4 pt-3 flex items-center justify-between"
          style={{ borderTop: "1px solid var(--color-border)" }}
        >
          <span className="text-xs font-semibold" style={{ color: "var(--color-text-muted)" }}>
            إجمالي الأرصدة
          </span>
          <span className="text-sm font-black" style={{ color: "var(--color-text-primary)" }}>
            {fmt(balance)} ج.م
          </span>
        </div>
      </div>
    </div>
  );
}
