import { useMemo } from "react";
import { useQuery } from "@tanstack/react-query";
import { useGetSupplierOrdersSnapshotQueryOptions } from "@/api/v2/suppliers/suppliers.hooks";
import { formatDate, toEnglishNumerals } from "@/utils/formatDate";
import {
  buildStatementFromOrders,
  formatAccountCurrency,
} from "./supplierAccountHelpers";

type Props = { supplierId: number };

const typeConfig: Record<string, { cls: string; icon: string }> = {
  طلبية: { cls: "bg-violet-50 text-violet-700", icon: "ri-shopping-cart-2-line" },
  دفع: { cls: "bg-emerald-50 text-emerald-700", icon: "ri-bank-card-line" },
  إرجاع: { cls: "bg-orange-50 text-orange-700", icon: "ri-arrow-go-back-line" },
};

export default function SupplierAccountStatementTab({ supplierId }: Props) {
  const { data, isPending, isError, error } = useQuery(
    useGetSupplierOrdersSnapshotQueryOptions(supplierId),
  );

  const statement = useMemo(
    () => buildStatementFromOrders(data?.data ?? []),
    [data?.data],
  );

  const lastBalance = statement[statement.length - 1]?.balance ?? 0;

  const totals = useMemo(() => {
    const purchases = statement
      .filter((s) => s.type === "طلبية")
      .reduce((a, s) => a + s.debit, 0);
    const payRet = statement
      .filter((s) => s.type === "دفع" || s.type === "إرجاع")
      .reduce((a, s) => a + s.credit, 0);
    return { purchases, payRet };
  }, [statement]);

  if (isError) {
    return (
      <div className="bg-white border border-gray-100 rounded-2xl p-6 text-center text-destructive text-sm">
        {error?.message ?? "تعذر تحميل البيانات."}
      </div>
    );
  }

  if (isPending) {
    return (
      <div className="bg-white border border-gray-100 rounded-2xl p-10 text-center text-sm text-slate-400">
        جاري التحميل...
      </div>
    );
  }

  return (
    <div className="space-y-4">
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div
          className={`rounded-xl p-4 ${lastBalance > 0 ? "bg-orange-50" : "bg-emerald-50"}`}
        >
          <p className="text-xs text-slate-500 mb-1">الرصيد الحالي (كشف)</p>
          <p
            className={`text-xl font-black ${lastBalance > 0 ? "text-orange-600" : "text-emerald-600"}`}
          >
            {lastBalance > 0
              ? formatAccountCurrency(lastBalance)
              : "مسدّد"}
          </p>
          <p className="text-[10px] text-slate-400 mt-0.5">
            {lastBalance > 0 ? "حسب حركات الطلبيات المعروضة" : "الحساب متوازن"}
          </p>
        </div>
        <div className="bg-violet-50 rounded-xl p-4">
          <p className="text-xs text-slate-500 mb-1">إجمالي مدين (طلبيات)</p>
          <p className="text-xl font-black text-violet-700 tabular-nums" dir="ltr">
            {formatAccountCurrency(totals.purchases)}
          </p>
          <p className="text-[10px] text-slate-400 mt-0.5">
            {statement.filter((s) => s.type === "طلبية").length} بند
          </p>
        </div>
        <div className="bg-emerald-50 rounded-xl p-4">
          <p className="text-xs text-slate-500 mb-1">إجمالي دائن (دفع + إرجاع)</p>
          <p className="text-xl font-black text-emerald-700 tabular-nums" dir="ltr">
            {formatAccountCurrency(totals.payRet)}
          </p>
          <p className="text-[10px] text-slate-400 mt-0.5">
            مجمّع من الطلبيات (حتى 500)
          </p>
        </div>
      </div>

      <div className="bg-white border border-gray-100 rounded-2xl overflow-hidden">
        <div className="px-5 py-3.5 border-b border-gray-100 bg-gray-50 flex items-center justify-between gap-2 flex-wrap">
          <p className="text-sm font-bold text-slate-700">كشف الحساب التفصيلي</p>
          <button
            type="button"
            onClick={() => window.print()}
            className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-800 text-white text-xs font-bold hover:bg-slate-900 cursor-pointer transition-colors whitespace-nowrap"
          >
            <i className="ri-printer-line" />
            طباعة الكشف
          </button>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead>
              <tr className="bg-gray-50 border-b border-gray-100">
                <th className="text-right px-5 py-3 text-xs font-semibold text-slate-500">
                  التاريخ
                </th>
                <th className="text-right px-4 py-3 text-xs font-semibold text-slate-500">
                  المرجع
                </th>
                <th className="text-right px-4 py-3 text-xs font-semibold text-slate-500">
                  البيان
                </th>
                <th className="text-center px-4 py-3 text-xs font-semibold text-slate-500">
                  النوع
                </th>
                <th className="text-center px-4 py-3 text-xs font-semibold text-orange-500">
                  مدين
                </th>
                <th className="text-center px-4 py-3 text-xs font-semibold text-emerald-600">
                  دائن
                </th>
                <th className="text-center px-4 py-3 text-xs font-semibold text-slate-700">
                  الرصيد
                </th>
              </tr>
            </thead>
            <tbody>
              {statement.length === 0 ? (
                <tr>
                  <td
                    colSpan={7}
                    className="px-5 py-12 text-center text-sm text-slate-400"
                  >
                    لا حركات لعرضها.
                  </td>
                </tr>
              ) : (
                statement.map((entry) => {
                  const cfg = typeConfig[entry.type];
                  return (
                    <tr
                      key={entry.id}
                      className="border-b border-gray-50 hover:bg-gray-50/50"
                    >
                      <td className="px-5 py-3.5 text-xs text-slate-500 font-medium" dir="ltr">
                        {entry.date
                          ? toEnglishNumerals(formatDate(entry.date))
                          : "—"}
                      </td>
                      <td className="px-4 py-3.5">
                        <span className="text-xs text-violet-600 font-medium" dir="ltr">
                          {toEnglishNumerals(entry.ref)}
                        </span>
                      </td>
                      <td className="px-4 py-3.5 max-w-60">
                        <p className="text-xs text-slate-700 line-clamp-2">
                          {entry.description}
                        </p>
                      </td>
                      <td className="px-4 py-3.5 text-center">
                        <span
                          className={`inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold whitespace-nowrap ${cfg.cls}`}
                        >
                          <i className={`${cfg.icon} text-xs`} />
                          {entry.type}
                        </span>
                      </td>
                      <td className="px-4 py-3.5 text-center text-xs tabular-nums text-slate-700" dir="ltr">
                        {entry.debit > 0
                          ? formatAccountCurrency(entry.debit)
                          : "—"}
                      </td>
                      <td className="px-4 py-3.5 text-center text-xs tabular-nums text-slate-700" dir="ltr">
                        {entry.credit > 0
                          ? formatAccountCurrency(entry.credit)
                          : "—"}
                      </td>
                      <td className="px-4 py-3.5 text-center text-xs font-bold tabular-nums text-slate-800" dir="ltr">
                        {formatAccountCurrency(entry.balance)}
                      </td>
                    </tr>
                  );
                })
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}
