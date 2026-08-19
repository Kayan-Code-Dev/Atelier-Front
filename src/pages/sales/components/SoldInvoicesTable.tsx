import { useMemo, useState } from "react";
import { useNavigate } from "react-router";
import type { TOrder } from "@/api/v2/orders/orders.types";
import {
  formatSaleInvoiceDate,
  getSaleInvoiceStatusLabel,
  getSalePaymentLabel,
  salePaymentColors,
  saleStatusColors,
} from "./soldInvoices.helpers";
import { soldOrderDetailPath } from "@/pages/sales/salesOrderPaths";

type SortKey = "id" | "client" | "date" | "total";

interface Props {
  orders: TOrder[];
  isPending: boolean;
  totalInvoicesFromApi: number;
  onPrint: (order: TOrder) => void;
}

export default function SoldInvoicesTable({
  orders,
  isPending,
  totalInvoicesFromApi,
  onPrint,
}: Props) {
  const navigate = useNavigate();
  const [sortField, setSortField] = useState<SortKey>("date");
  const [sortDir, setSortDir] = useState<"asc" | "desc">("desc");

  const sorted = useMemo(() => {
    const list = [...orders];
    list.sort((a, b) => {
      let va = "";
      let vb = "";
      switch (sortField) {
        case "id":
          va = String(a.id);
          vb = String(b.id);
          break;
        case "client":
          va = a.client?.name ?? "";
          vb = b.client?.name ?? "";
          break;
        case "date":
          va = a.created_at ?? "";
          vb = b.created_at ?? "";
          break;
        case "total":
          va = String(a.total_price ?? 0);
          vb = String(b.total_price ?? 0);
          break;
        default:
          break;
      }
      return sortDir === "asc" ? va.localeCompare(vb, "ar") : vb.localeCompare(va, "ar");
    });
    return list;
  }, [orders, sortField, sortDir]);

  const handleSort = (field: SortKey) => {
    if (sortField === field) setSortDir((d) => (d === "asc" ? "desc" : "asc"));
    else {
      setSortField(field);
      setSortDir("desc");
    }
  };

  const SortIcon = ({ field }: { field: SortKey }) => (
    <i
      className={`ri-arrow-${sortField === field ? (sortDir === "asc" ? "up" : "down") : "up-down"}-line text-xs mr-1 ${sortField === field ? "text-indigo-500" : "text-slate-300"}`}
    />
  );

  const branchName = (o: TOrder) =>
    o.branch?.name ?? o.inventory?.inventoriable?.name ?? "—";

  const clientName = (o: TOrder) => o.client?.name?.trim() || "—";
  const clientPhone = (o: TOrder) => o.client?.phones?.[0]?.phone ?? "—";

  return (
    <div className="bg-white rounded-xl overflow-hidden border border-slate-100">
      <div
        className="grid px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wide bg-slate-50 border-b border-slate-100"
        style={{
          gridTemplateColumns: "80px 1fr 130px 140px 130px 130px 120px 100px",
        }}
      >
        <button
          type="button"
          className="text-right flex items-center whitespace-nowrap cursor-pointer"
          onClick={() => handleSort("id")}
        >
          <SortIcon field="id" /> رقم الفاتورة
        </button>
        <button
          type="button"
          className="text-right flex items-center whitespace-nowrap cursor-pointer"
          onClick={() => handleSort("client")}
        >
          العميلة
        </button>
        <button
          type="button"
          className="text-right flex items-center whitespace-nowrap cursor-pointer"
          onClick={() => handleSort("date")}
        >
          <SortIcon field="date" /> تاريخ الفاتورة
        </button>
        <span className="text-right">الفرع</span>
        <button
          type="button"
          className="text-right flex items-center whitespace-nowrap cursor-pointer"
          onClick={() => handleSort("total")}
        >
          <SortIcon field="total" /> الإجمالي
        </button>
        <span className="text-right">حالة الدفع</span>
        <span className="text-right">حالة الفاتورة</span>
        <span className="text-right">إجراء</span>
      </div>

      {isPending ? (
        <div className="py-16 text-center text-slate-400">جاري التحميل...</div>
      ) : sorted.length === 0 ? (
        <div className="py-16 text-center text-slate-400">
          <i className="ri-search-line text-3xl mb-2 block" />
          لا توجد فواتير مطابقة
        </div>
      ) : (
        sorted.map((inv) => {
          const total = Number(inv.total_price ?? 0);
          const collected = Number(inv.paid ?? 0);
          const pct = total > 0 ? Math.round((collected / total) * 100) : 0;
          const payLabel = getSalePaymentLabel(inv);
          const stLabel = getSaleInvoiceStatusLabel(inv);
          return (
            <div
              key={inv.id}
              role="button"
              tabIndex={0}
              className="grid px-4 py-3 border-b border-slate-50 hover:bg-slate-50 transition-colors cursor-pointer items-center"
              style={{
                gridTemplateColumns: "80px 1fr 130px 140px 130px 130px 120px 100px",
              }}
              onClick={() => navigate(soldOrderDetailPath(inv.id))}
              onKeyDown={(e) => {
                if (e.key === "Enter" || e.key === " ") {
                  e.preventDefault();
                  navigate(soldOrderDetailPath(inv.id));
                }
              }}
            >
              <div className="font-bold text-sm text-indigo-600">#{inv.id}</div>

              <div>
                <div className="font-semibold text-sm text-slate-800">{clientName(inv)}</div>
                <div className="text-xs text-slate-400">{clientPhone(inv)}</div>
              </div>

              <div className="text-sm text-slate-600">{formatSaleInvoiceDate(inv.created_at)}</div>

              <div className="text-sm text-slate-600 truncate" title={branchName(inv)}>
                {branchName(inv)}
              </div>

              <div>
                <div className="font-bold text-sm text-slate-800">
                  {total.toLocaleString("ar-SA")} ﷼
                </div>
                <div className="w-full bg-slate-100 rounded-full h-1 mt-1">
                  <div
                    className="h-1 rounded-full transition-all"
                    style={{
                      width: `${Math.min(100, pct)}%`,
                      background: pct === 100 ? "#10B981" : "#F59E0B",
                    }}
                  />
                </div>
                <div className="text-xs text-slate-400 mt-0.5">{pct}% محصّل</div>
              </div>

              <div>
                <span
                  className={`text-xs px-2 py-1 rounded-full font-medium whitespace-nowrap ${salePaymentColors[payLabel] ?? "bg-slate-50 text-slate-600 border border-slate-200"}`}
                >
                  {payLabel}
                </span>
              </div>

              <div>
                <span
                  className={`text-xs px-2 py-1 rounded-full font-medium whitespace-nowrap ${saleStatusColors[stLabel] ?? ""}`}
                >
                  {stLabel}
                </span>
              </div>

              <div
                className="flex items-center gap-1.5"
                onClick={(e) => e.stopPropagation()}
              >
                <button
                  type="button"
                  className="w-7 h-7 rounded-lg flex items-center justify-center cursor-pointer text-slate-500 hover:bg-indigo-50 hover:text-indigo-600 transition-colors"
                  title="عرض التفاصيل"
                  onClick={() => navigate(soldOrderDetailPath(inv.id))}
                >
                  <i className="ri-eye-line text-sm" />
                </button>
                <button
                  type="button"
                  className="w-7 h-7 rounded-lg flex items-center justify-center cursor-pointer text-slate-500 hover:bg-emerald-50 hover:text-emerald-600 transition-colors"
                  title="طباعة"
                  onClick={() => onPrint(inv)}
                >
                  <i className="ri-printer-line text-sm" />
                </button>
              </div>
            </div>
          );
        })
      )}

      <div className="px-4 py-2.5 flex items-center justify-between bg-slate-50 border-t border-slate-100">
        <span className="text-xs text-slate-400">
          عرض {sorted.length} من {totalInvoicesFromApi} فاتورة
        </span>
        <span className="text-xs font-semibold text-slate-600">
          إجمالي المعروض:{" "}
          {sorted
            .filter((i) => getSaleInvoiceStatusLabel(i) !== "ملغية")
            .reduce((s, i) => s + Number(i.total_price ?? 0), 0)
            .toLocaleString("ar-SA")}{" "}
          ﷼
        </span>
      </div>
    </div>
  );
}
