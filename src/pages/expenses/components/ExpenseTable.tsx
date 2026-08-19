import { TExpense } from "@/api/v2/expenses/expenses.types";
import CustomPagination from "@/components/custom/CustomPagination";
import { formatDate } from "@/utils/formatDate";
import { statusConfig, getStatusLabel } from "../hooks/useExpensesPage";
import { getExpenseCategoryDisplay } from "@/api/v2/expenses/expenses.types";

type Props = {
  items: TExpense[];
  isPending: boolean;
  isError: boolean;
  error: Error | null;
  totalAmount: number;
  paidAmount: number;
  paidCount: number;
  pendingCount: number;
  cancelledCount: number;
  total: number | undefined;
  totalPages: number | undefined;
  onResetFilters: () => void;
  onViewDetails: (e: TExpense) => void;
  onUpdate: (e: TExpense) => void;
  onDelete: (e: TExpense) => void;
  onCancel: (e: TExpense) => void;
  onPay: (e: TExpense) => void;
};

const categoryColors: Record<string, { color: string; bg: string }> = {
  operating: { color: "#7C3AED", bg: "#EDE9FE" },
  salaries_wages: { color: "#065F46", bg: "#D1FAE5" },
  materials_raw: { color: "#0369A1", bg: "#E0F2FE" },
  marketing: { color: "#9D174D", bg: "#FCE7F3" },
  daily_operating: { color: "#1D4ED8", bg: "#DBEAFE" },
  financial: { color: "#92400E", bg: "#FEF3C7" },
  other: { color: "#374151", bg: "#F3F4F6" },
};

function getCategoryStyle(categoryId: string) {
  return categoryColors[categoryId] || { color: "#374151", bg: "#F3F4F6" };
}

export function ExpenseTable({
  items,
  isPending,
  isError,
  error,
  totalAmount,
  paidAmount,
  paidCount,
  pendingCount,
  cancelledCount,
  total,
  totalPages,
  onResetFilters,
  onViewDetails,
  onUpdate,
  onDelete,
  onCancel,
  onPay,
}: Props) {
  return (
    <div className="sys-card overflow-hidden">
      <div className="overflow-x-auto">
        <table className="sys-table">
          <thead>
            <tr>
              <th className="whitespace-nowrap">#</th>
              <th>الفرع</th>
              <th>الصندوق</th>
              <th>الفئة</th>
              <th>المورد</th>
              <th>المبلغ</th>
              <th>التاريخ</th>
              <th>الحالة</th>
              <th>بواسطة</th>
              <th>ملاحظات</th>
              <th>إجراءات</th>
            </tr>
          </thead>
          <tbody>
            {isPending ? (
              <tr>
                <td colSpan={11} className="py-10 text-center text-slate-500">
                  جاري التحميل...
                </td>
              </tr>
            ) : isError ? (
              <tr>
                <td colSpan={11} className="py-10 text-center text-red-500">
                  حدث خطأ أثناء تحميل البيانات. {error?.message}
                </td>
              </tr>
            ) : items.length > 0 ? (
              items.map((e, idx) => {
                const sc = statusConfig[e.status];
                const catStyle = getCategoryStyle(e.category);
                const amount =
                  typeof e.amount === "number"
                    ? e.amount
                    : Number(e.amount) || 0;
                return (
                  <tr key={e.id}>
                    <td>
                      <div>
                        <span className="font-mono text-xs font-bold text-red-700 bg-red-50 px-2 py-0.5 rounded block whitespace-nowrap">
                          {e.id}
                        </span>
                        <span className="text-xs text-slate-400 mt-0.5 block">
                          # {idx + 1}
                        </span>
                      </div>
                    </td>
                    <td>
                      <div className="flex items-center gap-1.5 whitespace-nowrap">
                        <i className="ri-map-pin-2-line text-xs text-slate-400" />
                        <span className="text-xs text-slate-600">
                          {e.branch?.name ?? "—"}
                        </span>
                      </div>
                    </td>
                    <td>
                      <div className="flex items-center gap-1.5 whitespace-nowrap">
                        <i className="ri-safe-2-line text-xs text-slate-400" />
                        <span className="text-xs text-slate-600">
                          {e.cashbox?.name ?? "—"}
                        </span>
                      </div>
                    </td>
                    <td>
                      <span
                        className="text-xs font-semibold px-2.5 py-1 rounded-full whitespace-nowrap"
                        style={{
                          color: catStyle.color,
                          background: catStyle.bg,
                        }}
                      >
                        {getExpenseCategoryDisplay(e.category, e.subcategory)}
                      </span>
                    </td>
                    <td>
                      <span className="text-sm text-slate-700 whitespace-nowrap">
                        {e.vendor || "—"}
                      </span>
                    </td>
                    <td>
                      <span className="font-bold text-slate-800 text-sm">
                        {amount.toLocaleString("ar-EG")}
                      </span>
                      <span className="text-xs text-slate-400 mr-1">ج.م</span>
                    </td>
                    <td>
                      <span className="text-xs text-slate-600 whitespace-nowrap">
                        {formatDate(e.expense_date)}
                      </span>
                    </td>
                    <td>
                      <span
                        className="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-full whitespace-nowrap"
                        style={{ color: sc.color, background: sc.bg }}
                      >
                        <i className={`${sc.icon} text-xs`} />
                        {getStatusLabel(e.status)}
                      </span>
                    </td>
                    <td>
                      <span className="text-xs text-slate-500 whitespace-nowrap">
                        {e.creator?.name ?? "—"}
                      </span>
                    </td>
                    <td>
                      {e.notes ? (
                        <span
                          className="text-xs text-slate-500 max-w-32 truncate block"
                          title={e.notes}
                        >
                          {e.notes}
                        </span>
                      ) : (
                        <span className="text-xs text-slate-300">—</span>
                      )}
                    </td>
                    <td>
                      <div className="flex items-center gap-1 whitespace-nowrap">
                        <button
                          type="button"
                          className="w-7 h-7 rounded-lg flex items-center justify-center cursor-pointer hover:bg-blue-50 transition-colors"
                          title="عرض"
                          onClick={() => onViewDetails(e)}
                        >
                          <i className="ri-eye-line text-sm text-blue-600" />
                        </button>
                        {e.status === "pending" && (
                          <>
                            <button
                              type="button"
                              className="w-7 h-7 rounded-lg flex items-center justify-center cursor-pointer hover:bg-amber-50 transition-colors"
                              title="تعديل"
                              onClick={() => onUpdate(e)}
                            >
                              <i className="ri-edit-line text-sm text-amber-500" />
                            </button>
                            <button
                              type="button"
                              className="w-7 h-7 rounded-lg flex items-center justify-center cursor-pointer hover:bg-red-50 transition-colors"
                              title="حذف"
                              onClick={() => onDelete(e)}
                            >
                              <i className="ri-delete-bin-line text-sm text-red-400" />
                            </button>
                            <button
                              type="button"
                              className="w-7 h-7 rounded-lg flex items-center justify-center cursor-pointer hover:bg-green-50 transition-colors"
                              title="دفع"
                              onClick={() => onPay(e)}
                            >
                              <i className="ri-money-dollar-circle-line text-sm text-green-600" />
                            </button>
                            <button
                              type="button"
                              className="w-7 h-7 rounded-lg flex items-center justify-center cursor-pointer hover:bg-red-50 transition-colors"
                              title="إلغاء"
                              onClick={() => onCancel(e)}
                            >
                              <i className="ri-close-circle-line text-sm text-red-400" />
                            </button>
                          </>
                        )}
                      </div>
                    </td>
                  </tr>
                );
              })
            ) : (
              <tr>
                <td colSpan={11} className="py-14 text-center">
                  <div className="text-slate-400">
                    <i className="ri-file-search-line text-4xl mb-2 block" />
                    <p className="text-sm font-medium">
                      لا توجد نتائج تطابق الفلاتر المحددة
                    </p>
                    <button
                      type="button"
                      onClick={onResetFilters}
                      className="mt-3 text-xs text-blue-600 cursor-pointer hover:underline"
                    >
                      إعادة ضبط الفلاتر
                    </button>
                  </div>
                </td>
              </tr>
            )}
          </tbody>
        </table>
      </div>

      <div
        className="px-5 py-3 flex items-center justify-between flex-wrap gap-3"
        style={{ background: "#F8FAFC", borderTop: "1px solid #E2E8F0" }}
      >
        <div className="flex items-center gap-6 text-xs text-slate-500">
          <span>
            <span className="font-bold text-green-700">{paidCount}</span> مدفوع
          </span>
          <span>
            <span className="font-bold text-yellow-700">{pendingCount}</span> معلق
          </span>
          <span>
            <span className="font-bold text-red-700">{cancelledCount}</span> ملغي
          </span>
          <span className="text-sm font-bold text-slate-700">
            الإجمالي:{" "}
            <span className="text-red-700">
              {totalAmount.toLocaleString("ar-EG")} ج.م
            </span>
            <span className="mx-3 text-slate-300">|</span>
            <span className="text-green-700">
              تم صرف: {paidAmount.toLocaleString("ar-EG")} ج.م
            </span>
          </span>
        </div>
        <CustomPagination
          totalElementsLabel="إجمالي المصروفات"
          totalElements={total}
          totalPages={totalPages}
          isLoading={isPending}
        />
      </div>
    </div>
  );
}
