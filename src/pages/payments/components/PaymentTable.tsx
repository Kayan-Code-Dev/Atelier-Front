import type { UseMutationResult } from "@tanstack/react-query";
import { TPayment, TPaymentsScope } from "@/api/v2/payments/payments.types";
import CustomPagination from "@/components/custom/CustomPagination";
import { formatDate } from "@/utils/formatDate";
import {
  statusConfig,
  paymentTypeIcons,
  getClientName,
  getStatusLabel,
  getPaymentTypeLabel,
} from "../hooks/usePaymentsPage";
import type { TPaymentListRow } from "../hooks/usePaymentsPage";

function orderBranchId(order: TPaymentListRow["order"]): number | undefined {
  if (!order) return undefined;
  if (order.branch?.id != null) return order.branch.id;
  const o = order as { branch_id?: number };
  if (typeof o.branch_id === "number") return o.branch_id;
  return undefined;
}


function isPendingStatus(status: string | undefined): boolean {
  return String(status ?? "").trim().toLowerCase() === "pending";
}

type Props = {
  items: TPaymentListRow[];
  scope: TPaymentsScope;
  isPending: boolean;
  isError: boolean;
  error: Error | null;
  totalAmount: number;
  completedCount: number;
  pendingCount: number;
  cancelledCount: number;
  total: number | undefined;
  totalPages: number | undefined;
  showNotes: string | null;
  setShowNotes: (id: string | null) => void;
  onViewDetails: (p: TPaymentListRow) => void;
  onMarkAsPaid: (id: number) => void;
  onMarkAsCanceled: (id: number) => void;
  onResetFilters: () => void;
  markAsPaidMutation: UseMutationResult<TPayment | undefined, Error, number>;
  markAsCanceledMutation: UseMutationResult<TPayment | undefined, Error, number>;
};

export function PaymentTable({
  items,
  scope,
  isPending,
  isError,
  error,
  totalAmount,
  completedCount,
  pendingCount,
  cancelledCount,
  total,
  totalPages,
  showNotes,
  setShowNotes,
  onViewDetails,
  onMarkAsPaid,
  onMarkAsCanceled,
  onResetFilters,
  markAsPaidMutation,
  markAsCanceledMutation,
}: Props) {
  const isManualScope = scope === "manual";
  return (
    <div className="sys-card overflow-hidden">
      <div className="overflow-x-auto">
        <table className="sys-table">
          <thead>
            <tr>
              <th className="whitespace-nowrap">رقم</th>
              {isManualScope ? (
                <>
                  <th>الصندوق</th>
                  <th>طريقة الدفع</th>
                  <th>مستلم من</th>
                  <th>الوصف</th>
                  <th>رقم الحركة</th>
                </>
              ) : (
                <>
                  <th>العميل</th>
                  <th>الفرع</th>
                </>
              )}
              <th>المبلغ</th>
              <th>الحالة</th>
              {!isManualScope && <th>نوع الدفعة</th>}
              <th>تاريخ الدفع</th>
              <th>تاريخ الإنشاء</th>
              <th>ملاحظات</th>
              {!isManualScope && <th>الإجراءات</th>}
            </tr>
          </thead>
          <tbody>
            {isPending ? (
              <tr>
                <td colSpan={isManualScope ? 11 : 10} className="py-10 text-center text-slate-500">
                  جاري التحميل...
                </td>
              </tr>
            ) : isError ? (
              <tr>
                <td colSpan={isManualScope ? 11 : 10} className="py-10 text-center text-red-500">
                  حدث خطأ أثناء تحميل البيانات. {error?.message}
                </td>
              </tr>
            ) : items.length > 0 ? (
              items.map((p) => {
                const sc = statusConfig[p.status];
                const amount =
                  typeof p.amount === "number" ? p.amount : Number(p.amount) || 0;
                const rawClientName = getClientName(
                  p.order?.client as TPayment["order"]["client"]
                );
                const clientName =
                  rawClientName !== "—"
                    ? rawClientName
                    : p.order?.client_id
                      ? `عميل #${p.order.client_id}`
                      : "—";
                const brId = orderBranchId(p.order);
                const branchName =
                  p.cashbox?.branch?.name ??
                  p.order?.branch?.name ??
                  p.cashbox?.name ??
                  (brId != null ? `فرع #${brId}` : "—");
                return (
                  <tr key={p.id}>
                    <td>
                      <div>
                        <span className="font-mono text-xs font-bold text-blue-700 bg-blue-50 px-2 py-0.5 rounded block mb-0.5">
                          {p.id}
                        </span>
                        <span className="text-xs text-slate-400">
                          #{p.order_id ?? "—"}
                        </span>
                      </div>
                    </td>
                    {isManualScope ? (
                      <>
                        <td>
                          <span className="text-xs text-slate-700 whitespace-nowrap">
                            {p.cashbox?.name ?? "—"}
                          </span>
                        </td>
                        <td>
                          <span className="text-xs text-slate-600 whitespace-nowrap">
                            {p.payment_method || "cash"}
                          </span>
                        </td>
                        <td>
                          <span className="text-xs text-slate-600 whitespace-nowrap">
                            {p.received_from || "—"}
                          </span>
                        </td>
                        <td>
                          <span className="text-xs text-slate-700 max-w-40 truncate block" title={p.description || "—"}>
                            {p.description || "—"}
                          </span>
                        </td>
                        <td>
                          <span className="text-xs font-mono text-slate-600 whitespace-nowrap">
                            {p.transaction_id ? `#${p.transaction_id}` : "—"}
                          </span>
                        </td>
                      </>
                    ) : (
                      <>
                        <td>
                          <div className="flex items-center gap-2">
                            <div
                              className="w-7 h-7 rounded-full shrink-0 flex items-center justify-center text-xs font-bold text-white"
                              style={{
                                background:
                                  "linear-gradient(135deg, #2563EB, #60A5FA)",
                              }}
                            >
                              {clientName.charAt(0)}
                            </div>
                            <span className="text-sm font-medium text-slate-700 whitespace-nowrap">
                              {clientName}
                            </span>
                          </div>
                        </td>
                        <td>
                          <span className="text-xs text-slate-600 whitespace-nowrap">
                            {branchName}
                          </span>
                        </td>
                      </>
                    )}
                    <td>
                      <span className="font-bold text-slate-800 text-sm">
                        {amount.toLocaleString("ar-EG")}
                      </span>
                      <span className="text-xs text-slate-400 mr-1">ج.م</span>
                    </td>
                    <td>
                      <span
                        className="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-full whitespace-nowrap"
                        style={{ color: sc.color, background: sc.bg }}
                      >
                        <i className={`${sc.icon} text-xs`} />
                        {getStatusLabel(p.status)}
                      </span>
                    </td>
                    {!isManualScope && (
                      <td>
                        <div className="flex items-center gap-1.5 whitespace-nowrap">
                          <i
                            className={`${paymentTypeIcons[p.payment_type]} text-base text-slate-400`}
                          />
                          <span className="text-xs text-slate-600">
                            {getPaymentTypeLabel(p.payment_type)}
                          </span>
                        </div>
                      </td>
                    )}
                    <td>
                      <span className="text-xs text-slate-600 whitespace-nowrap">
                        {formatDate(p.payment_date)}
                      </span>
                    </td>
                    <td>
                      <span className="text-xs text-slate-400 whitespace-nowrap">
                        {formatDate(p.created_at)}
                      </span>
                    </td>
                    <td>
                      {p.notes ? (
                        <div className="relative">
                          <button
                            type="button"
                            onClick={() =>
                              setShowNotes(
                                showNotes === String(p.id) ? null : String(p.id)
                              )
                            }
                            className="flex items-center gap-1 text-xs text-blue-600 cursor-pointer hover:underline max-w-28 truncate"
                          >
                            <i className="ri-sticky-note-line shrink-0" />
                            <span className="truncate">{p.notes}</span>
                          </button>
                          {showNotes === String(p.id) && (
                            <div
                              className="absolute left-0 top-6 z-20 w-56 p-3 rounded-xl text-xs text-slate-700 leading-relaxed slide-down"
                              style={{
                                background: "white",
                                border: "1px solid #E2E8F0",
                                boxShadow: "0 4px 12px rgba(0,0,0,0.08)",
                              }}
                            >
                              {p.notes}
                            </div>
                          )}
                        </div>
                      ) : (
                        <span className="text-xs text-slate-300">—</span>
                      )}
                    </td>
                    {!isManualScope && (
                      <td>
                        <div className="flex items-center gap-1 whitespace-nowrap">
                          {p.source === "payments" && (
                            <button
                              type="button"
                              className="w-7 h-7 rounded-lg flex items-center justify-center cursor-pointer hover:bg-blue-50 transition-colors"
                              title="عرض التفاصيل"
                              onClick={() => onViewDetails(p)}
                            >
                              <i className="ri-eye-line text-sm text-blue-600" />
                            </button>
                          )}
                          {isPendingStatus(p.status) && p.source === "payments" && (
                            <button
                              type="button"
                              className="w-7 h-7 rounded-lg flex items-center justify-center cursor-pointer hover:bg-green-50 transition-colors"
                              title="تحديد كمدفوع"
                              onClick={() => onMarkAsPaid(p.id)}
                              disabled={
                                markAsPaidMutation.isPending ||
                                markAsCanceledMutation.isPending
                              }
                            >
                              <i className="ri-checkbox-circle-line text-sm text-green-600" />
                            </button>
                          )}
                          {isPendingStatus(p.status) && p.source === "payments" && (
                            <button
                              type="button"
                              className="w-7 h-7 rounded-lg flex items-center justify-center cursor-pointer hover:bg-red-50 transition-colors"
                              title="إلغاء"
                              onClick={() => onMarkAsCanceled(p.id)}
                              disabled={
                                markAsPaidMutation.isPending ||
                                markAsCanceledMutation.isPending
                              }
                            >
                              <i className="ri-close-circle-line text-sm text-red-400" />
                            </button>
                          )}
                        </div>
                      </td>
                    )}
                  </tr>
                );
              })
            ) : (
              <tr>
                <td colSpan={isManualScope ? 11 : 10} className="py-14 text-center">
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
            <span className="font-bold text-green-700">{completedCount}</span>{" "}
            مدفوع
          </span>
          <span>
            <span className="font-bold text-yellow-700">{pendingCount}</span>{" "}
            معلق
          </span>
          <span>
            <span className="font-bold text-red-700">{cancelledCount}</span> ملغي
          </span>
          <span className="text-sm font-bold text-slate-700">
            الإجمالي:{" "}
            <span className="text-blue-700">
              {totalAmount.toLocaleString("ar-EG")} ج.م
            </span>
          </span>
        </div>
        <CustomPagination
          totalElementsLabel="إجمالي المدفوعات"
          totalElements={total}
          totalPages={totalPages}
          isLoading={isPending}
        />
      </div>
    </div>
  );
}
