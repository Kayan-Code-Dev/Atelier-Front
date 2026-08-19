import { useMemo, useState } from "react";
import { TSupplierResponse } from "@/api/v2/suppliers/suppliers.types";
import { parseMoney, formatAccountCurrency } from "./supplierAccountHelpers";

type Props = {
  suppliers: TSupplierResponse[] | undefined;
  selectedId: number | null;
  onSelect: (id: number) => void;
  isLoading: boolean;
};

export default function SupplierAccountsSidebar({
  suppliers,
  selectedId,
  onSelect,
  isLoading,
}: Props) {
  const [search, setSearch] = useState("");

  const filtered = useMemo(() => {
    const list = suppliers ?? [];
    const q = search.trim().toLowerCase();
    if (!q) return list;
    return list.filter((s) => {
      const name = (s.name || "").toLowerCase();
      const code = (s.code || "").toLowerCase();
      const phone = (s.phone || "").toLowerCase();
      return name.includes(q) || code.includes(q) || phone.includes(q);
    });
  }, [suppliers, search]);

  const footerTotals = useMemo(() => {
    const list = suppliers ?? [];
    let purchases = 0;
    let due = 0;
    for (const s of list) {
      purchases += parseMoney(s.total_order_amount ?? s.total_purchases);
      due += parseMoney(s.total_remaining ?? s.remaining);
    }
    return { purchases, due };
  }, [suppliers]);

  return (
    <div className="flex flex-col h-full bg-white border border-gray-100 rounded-2xl overflow-hidden shadow-sm">
      <div className="p-4 border-b border-gray-100">
        <p className="text-sm font-bold text-slate-800 mb-3">قائمة الموردين</p>
        <div className="relative mb-3">
          <i className="ri-search-line absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm" />
          <input
            type="text"
            placeholder="بحث بالاسم أو الكود..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="w-full pr-9 pl-3 py-2 rounded-lg border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-amber-200"
          />
        </div>
      </div>

      <div className="flex-1 overflow-y-auto min-h-[200px]">
        {isLoading && (
          <div className="p-6 text-center text-xs text-slate-400">جاري التحميل...</div>
        )}
        {!isLoading &&
          filtered.map((s) => {
            const remaining = parseMoney(s.total_remaining ?? s.remaining);
            const isSelected = selectedId === s.id;
            const ordersCount =
              s.orders_count ?? s.purchases_count ?? 0;
            return (
              <button
                key={s.id}
                type="button"
                onClick={() => onSelect(s.id)}
                className={`w-full text-right px-4 py-3.5 border-b border-gray-50 cursor-pointer transition-all ${
                  isSelected
                    ? "bg-amber-50"
                    : "hover:bg-gray-50"
                }`}
                style={
                  isSelected
                    ? { borderRight: "3px solid #F59E0B" }
                    : undefined
                }
              >
                <div className="flex items-start justify-between gap-2">
                  <div className="flex-1 min-w-0">
                    <p
                      className={`text-sm font-bold truncate ${
                        isSelected ? "text-amber-700" : "text-slate-800"
                      }`}
                    >
                      {s.name}
                    </p>
                    <div className="flex items-center gap-2 mt-0.5">
                      <span className="text-[10px] text-slate-400">
                        {s.code || "—"}
                      </span>
                    </div>
                  </div>
                  <div className="text-left flex-shrink-0">
                    <p
                      className={`text-xs font-bold ${
                        remaining > 0 ? "text-orange-500" : "text-emerald-600"
                      }`}
                    >
                      {remaining > 0
                        ? formatAccountCurrency(remaining)
                        : "مسدّد"}
                    </p>
                    <p className="text-[10px] text-slate-400 mt-0.5">
                      {ordersCount} طلبية
                    </p>
                  </div>
                </div>
              </button>
            );
          })}

        {!isLoading && filtered.length === 0 && (
          <div className="text-center py-10">
            <i className="ri-search-line text-gray-300 text-2xl" />
            <p className="text-xs text-gray-400 mt-2">لا نتائج</p>
          </div>
        )}
      </div>

      <div className="p-3 border-t border-gray-100 bg-gray-50">
        <div className="grid grid-cols-2 gap-2 text-center">
          <div>
            <p className="text-[10px] text-slate-400">إجمالي المشتريات</p>
            <p className="text-xs font-bold text-slate-700">
              {formatAccountCurrency(footerTotals.purchases)}
            </p>
          </div>
          <div>
            <p className="text-[10px] text-slate-400">إجمالي المستحق</p>
            <p className="text-xs font-bold text-orange-600">
              {formatAccountCurrency(Math.max(0, footerTotals.due))}
            </p>
          </div>
        </div>
      </div>
    </div>
  );
}
