import { useMemo } from "react";
import type { TClothResponse } from "@/api/v2/clothes/clothes.types";
import { clothPrice, STATUS_VISUAL } from "./clothesViewUtils";

type Props = {
  items: TClothResponse[];
  /** إجمالي النتائج من الـ API (مع الفلاتر) */
  totalFiltered?: number;
};

export function ClothesProductsStats({ items, totalFiltered }: Props) {
  const stats = useMemo(() => {
    const ready = items.filter((p) => p.status === "ready_for_rent").length;
    const repair = items.filter((p) => p.status === "repairing").length;
    const damaged = items.filter((p) =>
      ["damaged", "burned", "scratched"].includes(p.status)
    ).length;
    const dead = items.filter((p) => p.status === "die").length;
    const totalValue = items.reduce((sum, p) => sum + clothPrice(p), 0);
    const displayTotal = totalFiltered ?? items.length;
    const readyPct =
      items.length > 0 ? Math.round((ready / items.length) * 100) : 0;

    return [
      {
        label: "إجمالي المنتجات",
        value: displayTotal,
        sub: "حسب الفلاتر الحالية",
        icon: "ri-stack-line",
        accent: "#1E3A7B",
        light: "#EEF2FF",
        trend: null as number | null,
      },
      {
        label: STATUS_VISUAL.ready_for_rent.label,
        value: ready,
        sub: `${readyPct}% من عرض الصفحة`,
        icon: "ri-checkbox-circle-line",
        accent: "#065F46",
        light: "#D1FAE5",
        trend: readyPct,
      },
      {
        label: STATUS_VISUAL.repairing.label,
        value: repair,
        sub: "بانتظار الورشة",
        icon: "ri-tools-line",
        accent: "#92400E",
        light: "#FEF3C7",
        trend: null,
      },
      {
        label: "تالف / ميت",
        value: damaged + dead,
        sub: `${damaged} تالف · ${dead} ميت`,
        icon: "ri-error-warning-line",
        accent: "#991B1B",
        light: "#FEE2E2",
        trend: null,
      },
      {
        label: "إجمالي القيمة",
        value: totalValue > 0 ? totalValue.toLocaleString("ar-EG") : "—",
        sub: "صفحة حالية (ج.م)",
        icon: "ri-money-dollar-circle-line",
        accent: "#0E7490",
        light: "#CFFAFE",
        trend: null,
      },
    ];
  }, [items, totalFiltered]);

  return (
    <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-4">
      {stats.map((s) => (
        <div
          key={s.label}
          className="rounded-2xl p-4 flex flex-col gap-3 transition-all cursor-default"
          style={{ background: "white", border: "1px solid #F1F5F9" }}
        >
          <div className="flex items-start justify-between gap-2">
            <div
              className="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
              style={{ background: s.light }}
            >
              <i className={`${s.icon} text-lg`} style={{ color: s.accent }} />
            </div>
            {s.trend !== null && (
              <span
                className="text-[11px] font-bold px-2 py-0.5 rounded-full shrink-0"
                style={{ background: "#D1FAE5", color: "#065F46" }}
              >
                {s.trend}%
              </span>
            )}
          </div>
          <div>
            <p className="text-2xl font-black text-slate-800 leading-none">
              {s.value}
            </p>
            <p className="text-[11px] text-slate-400 mt-1 font-medium">{s.label}</p>
            <p className="text-[10px] text-slate-300 mt-0.5">{s.sub}</p>
          </div>
        </div>
      ))}
    </div>
  );
}
