import { useMemo } from "react";
import type { TOrder } from "@/api/v2/orders/orders.types";
import { getSaleInvoiceStatusLabel } from "./soldInvoices.helpers";

type Props = {
  orders: TOrder[];
  totalInvoicesFromApi: number;
};

export default function SoldInvoicesStats({ orders, totalInvoicesFromApi }: Props) {
  const stats = useMemo(() => {
    const total = totalInvoicesFromApi;
    const completed = orders.filter((i) => getSaleInvoiceStatusLabel(i) === "مكتملة").length;
    const pending = orders.filter((i) => getSaleInvoiceStatusLabel(i) === "معلقة").length;
    const totalRevenue = orders
      .filter((i) => getSaleInvoiceStatusLabel(i) !== "ملغية")
      .reduce((s, i) => s + Number(i.total_price ?? 0), 0);
    const collected = orders
      .filter((i) => getSaleInvoiceStatusLabel(i) !== "ملغية")
      .reduce((s, i) => s + Number(i.paid ?? 0), 0);
    const remaining = orders
      .filter((i) => getSaleInvoiceStatusLabel(i) !== "ملغية")
      .reduce((s, i) => s + Number(i.remaining ?? 0), 0);

    return { total, completed, pending, totalRevenue, collected, remaining };
  }, [orders, totalInvoicesFromApi]);

  const cards = [
    {
      label: "إجمالي الفواتير",
      value: stats.total,
      sub: "فاتورة بيع",
      icon: "ri-file-list-3-line",
      color: "#0EA5E9",
      bg: "#F0F9FF",
    },
    {
      label: "مكتملة",
      value: stats.completed,
      sub: "تم التسليم",
      icon: "ri-checkbox-circle-line",
      color: "#10B981",
      bg: "#ECFDF5",
    },
    {
      label: "قيد التنفيذ",
      value: stats.pending,
      sub: "في انتظار الاستلام",
      icon: "ri-time-line",
      color: "#F59E0B",
      bg: "#FFFBEB",
    },
    {
      label: "إجمالي الإيرادات",
      value: stats.totalRevenue.toLocaleString("ar-SA") + " ﷼",
      sub: "شامل الضريبة",
      icon: "ri-money-dollar-circle-line",
      color: "#0284C7",
      bg: "#E0F2FE",
    },
    {
      label: "المحصّل",
      value: stats.collected.toLocaleString("ar-SA") + " ﷼",
      sub: "مجموع المدفوعات",
      icon: "ri-bank-card-line",
      color: "#0369A1",
      bg: "#BAE6FD",
    },
    {
      label: "المتبقي",
      value: stats.remaining.toLocaleString("ar-SA") + " ﷼",
      sub: "ذمم العملاء",
      icon: "ri-wallet-3-line",
      color: "#EF4444",
      bg: "#FEF2F2",
    },
  ];

  return (
    <div className="grid grid-cols-6 gap-4">
      {cards.map((card) => (
        <div
          key={card.label}
          className="rounded-xl p-4 flex flex-col gap-2"
          style={{ background: card.bg, border: `1px solid ${card.color}22` }}
        >
          <div className="flex items-center justify-between">
            <span className="text-xs font-medium" style={{ color: card.color }}>
              {card.label}
            </span>
            <div
              className="w-8 h-8 rounded-lg flex items-center justify-center"
              style={{ background: card.color + "18" }}
            >
              <i className={`${card.icon} text-base`} style={{ color: card.color }} />
            </div>
          </div>
          <div className="text-xl font-bold text-slate-800">{card.value}</div>
          <div className="text-xs text-slate-400">{card.sub}</div>
        </div>
      ))}
    </div>
  );
}
