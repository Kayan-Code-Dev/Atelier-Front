import { useMemo } from "react";
import type { TOrder } from "@/api/v2/orders/orders.types";
import { getRentalUiStatus } from "../rentalUi";


interface RentalStatsProps {
  invoices: TOrder[];
  
  currencySymbol?: string;
}

export default function RentalStats({
  invoices,
  currencySymbol = "ج.م",
}: RentalStatsProps) {
  const stats = useMemo(() => {
    const total = invoices.length;
    const active = invoices.filter((i) => getRentalUiStatus(i) === "نشط").length;
    const late = invoices.filter((i) => getRentalUiStatus(i) === "متأخر").length;
    const returned = invoices.filter((i) => getRentalUiStatus(i) === "مرتجع").length;
    const totalRevenue = invoices.reduce((s, i) => s + Number(i.total_price ?? 0), 0);
    const totalCollected = invoices.reduce((s, i) => s + Number(i.paid ?? 0), 0);
    const totalRemaining = invoices.reduce((s, i) => s + Number(i.remaining ?? 0), 0);
    return { total, active, late, returned, totalRevenue, totalCollected, totalRemaining };
  }, [invoices]);

  const cards = [
    {
      label: "إجمالي الفواتير",
      value: stats.total,
      sub: `${stats.active} نشطة`,
      icon: "ri-file-text-line",
      color: "text-slate-700",
      bg: "bg-slate-50",
      border: "border-slate-200",
    },
    {
      label: "فواتير نشطة",
      value: stats.active,
      sub: `${stats.returned} مرتجعة`,
      icon: "ri-checkbox-circle-line",
      color: "text-emerald-700",
      bg: "bg-emerald-50",
      border: "border-emerald-200",
    },
    {
      label: "متأخرات الإرجاع",
      value: stats.late,
      sub: "تحتاج متابعة",
      icon: "ri-alarm-warning-line",
      color: "text-red-700",
      bg: "bg-red-50",
      border: "border-red-200",
    },
    {
      label: "إجمالي الإيرادات",
      value: `${stats.totalRevenue.toLocaleString("ar-EG")} ${currencySymbol}`,
      sub: `محصّل: ${stats.totalCollected.toLocaleString("ar-EG")} ${currencySymbol}`,
      icon: "ri-money-dollar-circle-line",
      color: "text-amber-700",
      bg: "bg-amber-50",
      border: "border-amber-200",
    },
    {
      label: "المتبقي للتحصيل",
      value: `${stats.totalRemaining.toLocaleString("ar-EG")} ${currencySymbol}`,
      sub: "من جميع الفواتير",
      icon: "ri-wallet-3-line",
      color: "text-rose-700",
      bg: "bg-rose-50",
      border: "border-rose-200",
    },
  ];

  return (
    <div className="grid grid-cols-5 gap-4 mb-6">
      {cards.map((c) => (
        <div key={c.label} className={`rounded-lg border ${c.border} ${c.bg} p-4`}>
          <div className="flex items-center justify-between mb-2">
            <span className="text-xs text-slate-500 font-medium">{c.label}</span>
            <div className={`w-8 h-8 flex items-center justify-center rounded-md ${c.bg}`}>
              <i className={`${c.icon} text-lg ${c.color}`} />
            </div>
          </div>
          <div className={`text-xl font-bold ${c.color} mb-1`}>{c.value}</div>
          <div className="text-xs text-slate-400">{c.sub}</div>
        </div>
      ))}
    </div>
  );
}
