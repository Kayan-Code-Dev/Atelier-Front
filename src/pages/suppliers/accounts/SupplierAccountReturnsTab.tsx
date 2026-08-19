import { useQuery } from "@tanstack/react-query";
import { useGetSupplierOrdersSnapshotQueryOptions } from "@/api/v2/suppliers/suppliers.hooks";
import { formatDate, toEnglishNumerals } from "@/utils/formatDate";
import {
  isReturnedOrder,
  parseMoney,
  formatAccountCurrency,
} from "./supplierAccountHelpers";

type Props = { supplierId: number };

export default function SupplierAccountReturnsTab({ supplierId }: Props) {
  const { data, isPending, isError, error } = useQuery(
    useGetSupplierOrdersSnapshotQueryOptions(supplierId),
  );

  const rows = data?.data?.filter((o) => isReturnedOrder(o)) ?? [];

  if (isError) {
    return (
      <div className="bg-white border border-gray-100 rounded-2xl p-6 text-center text-destructive text-sm">
        {error?.message ?? "تعذر تحميل البيانات."}
      </div>
    );
  }

  return (
    <div className="bg-white border border-gray-100 rounded-2xl overflow-hidden">
      <div className="px-5 py-3.5 border-b border-gray-100 bg-gray-50">
        <p className="text-sm font-bold text-slate-700">طلبيات مُرجَعة</p>
        <p className="text-xs text-slate-400 mt-0.5">
          حسب حالة الطلبية في النظام (حتى 500 طلبية).
        </p>
      </div>
      {isPending ? (
        <div className="p-10 text-center text-sm text-slate-400">جاري التحميل...</div>
      ) : (
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead>
              <tr className="bg-gray-50 border-b border-gray-100">
                <th className="text-right px-5 py-3 text-xs font-semibold text-slate-500">
                  الطلبية
                </th>
                <th className="text-right px-4 py-3 text-xs font-semibold text-slate-500">
                  التاريخ
                </th>
                <th className="text-center px-4 py-3 text-xs font-semibold text-slate-500">
                  الحالة
                </th>
                <th className="text-center px-4 py-3 text-xs font-semibold text-orange-600">
                  قيمة الطلبية
                </th>
              </tr>
            </thead>
            <tbody>
              {rows.length === 0 ? (
                <tr>
                  <td
                    colSpan={4}
                    className="px-5 py-12 text-center text-sm text-slate-400"
                  >
                    لا توجد إرجاعات مسجّلة لهذا المورد.
                  </td>
                </tr>
              ) : (
                rows.map((o) => (
                  <tr
                    key={o.id}
                    className="border-b border-gray-50 hover:bg-orange-50/30"
                  >
                    <td className="px-5 py-3.5">
                      <span className="text-xs font-semibold text-orange-600" dir="ltr">
                        {toEnglishNumerals(o.order_number || o.id)}
                      </span>
                    </td>
                    <td className="px-4 py-3.5 text-xs text-slate-500" dir="ltr">
                      {o.order_date
                        ? toEnglishNumerals(formatDate(o.order_date))
                        : "—"}
                    </td>
                    <td className="px-4 py-3.5 text-center text-xs text-slate-700">
                      {o.status || "—"}
                    </td>
                    <td className="px-4 py-3.5 text-center text-xs font-bold tabular-nums" dir="ltr">
                      {formatAccountCurrency(parseMoney(o.total_amount))}
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
