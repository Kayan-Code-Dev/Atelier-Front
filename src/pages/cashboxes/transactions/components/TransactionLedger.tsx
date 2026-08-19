import { TTransaction } from "@/api/v2/transactions/transactions.types";
import {
  isExpenseReference,
  isOrderPaymentReference,
  isTailoringOrderPaymentReference,
} from "@/api/v2/transactions/transactionReference";
import { getCategoryLabel } from "../hooks/useCashboxTransactionsPage";
import { formatDateTime } from "@/utils/formatDate";
import CustomPagination from "@/components/custom/CustomPagination";

const formatMoney = (value: number | string | null | undefined): string => {
  if (value === null || value === undefined) return "—";
  const num = typeof value === "number" ? value : Number(value);
  if (Number.isNaN(num)) return "—";
  return num.toLocaleString("ar-EG", { minimumFractionDigits: 2 });
};

type Props = {
  items: TTransaction[];
  isPending: boolean;
  isError: boolean;
  error: Error | null;
  openingBalance: number;
  totalIncome: number;
  totalExpense: number;
  totalReversalAbs?: number;
  closingBalance: number;
  total: number | undefined;
  totalPages: number | undefined;
  onResetFilters: () => void;
  onViewPayment: (id: number) => void;
  onViewExpense: (id: number) => void;
  onViewTailoringOrder?: (orderId: number) => void;
  onViewTransaction: (tx: TTransaction) => void;
  
  hidePaginationForPrint?: boolean;
  
  printRootId?: string;
};

export function TransactionLedger({
  items,
  isPending,
  isError,
  error,
  openingBalance,
  totalIncome,
  totalExpense,
  totalReversalAbs = 0,
  closingBalance,
  total,
  totalPages,
  onResetFilters,
  onViewPayment,
  onViewExpense,
  onViewTailoringOrder,
  onViewTransaction,
  hidePaginationForPrint,
  printRootId = "pdf-print-ledger",
}: Props) {
  return (
    <div
      id={printRootId}
      className={`sys-card ${hidePaginationForPrint ? "" : "overflow-hidden"}`}
    >
      <div className="px-5 py-3 border-b border-slate-200/80 flex items-center justify-between">
        <h3 className="font-semibold text-slate-700 text-sm flex items-center gap-2">
          <i className="ri-book-open-line text-blue-500" />
          دفتر الأستاذ — كشف المعاملات
        </h3>
        <span className="text-xs text-slate-400">{items.length} حركة</span>
      </div>

      <div className={hidePaginationForPrint ? "" : "overflow-x-auto"}>
        <table className={`sys-table ${hidePaginationForPrint ? "w-full" : "min-w-[900px]"}`}>
          <thead>
            <tr>
              <th>#</th>
              <th>التاريخ</th>
              <th>الصندوق</th>
              <th>نوع الحركة</th>
              <th>التصنيف</th>
              <th>دائن (دخل)</th>
              <th>مدين (خروج)</th>
              <th>الرصيد بعد</th>
              <th>المستخدم</th>
              <th>التفاصيل</th>
            </tr>
          </thead>
          <tbody>
            {isPending ? (
              <tr>
                <td colSpan={10} className="py-10 text-center text-slate-500">
                  جاري التحميل...
                </td>
              </tr>
            ) : isError ? (
              <tr>
                <td colSpan={10} className="py-10 text-center text-red-500">
                  حدث خطأ. {error?.message}
                </td>
              </tr>
            ) : items.length > 0 ? (
              <>
                <tr className="bg-amber-50">
                  <td colSpan={5} className="px-4 py-2.5 text-amber-700 font-semibold text-xs">
                    <i className="ri-flag-line ml-1" />
                    رصيد أول المدة
                  </td>
                  <td className="px-4 py-2.5 text-center" />
                  <td className="px-4 py-2.5 text-center" />
                  <td className="px-4 py-2.5 text-center font-bold text-amber-700">
                    {formatMoney(openingBalance)} ج.م
                  </td>
                  <td colSpan={2} />
                </tr>
                {items.map((tx) => {
                  const isIncome = tx.type === "income";
                  const isReversal = tx.type === "reversal";
                  const amount =
                    typeof tx.amount === "number" ? tx.amount : Number(tx.amount) || 0;
                  const balanceAfterRaw =
                    tx.cashbox_balance_after != null
                      ? tx.cashbox_balance_after
                      : tx.balance_after;
                  const balanceAfter =
                    typeof balanceAfterRaw === "number"
                      ? balanceAfterRaw
                      : Number(balanceAfterRaw) || 0;
                  const ref = tx.reference_type;
                  const refId = tx.reference_id;
                  const tailoringOrderId =
                    isTailoringOrderPaymentReference(ref) && tx.metadata
                      ? (tx.metadata as { tailoring_order_id?: number }).tailoring_order_id
                      : undefined;

                  const openRowDetails = () => {
                    if (isOrderPaymentReference(ref) && refId != null) {
                      onViewPayment(refId);
                      return;
                    }
                    if (
                      isTailoringOrderPaymentReference(ref) &&
                      typeof tailoringOrderId === "number" &&
                      onViewTailoringOrder
                    ) {
                      onViewTailoringOrder(tailoringOrderId);
                      return;
                    }
                    if (isExpenseReference(ref) && refId != null) {
                      onViewExpense(refId);
                      return;
                    }
                    onViewTransaction(tx);
                  };

                  const detailsTitle =
                    isOrderPaymentReference(ref) && refId != null
                      ? "تفاصيل دفعة الطلب"
                      : isTailoringOrderPaymentReference(ref) &&
                          typeof tailoringOrderId === "number" &&
                          onViewTailoringOrder
                        ? "أمر التفصيل"
                        : isExpenseReference(ref) && refId != null
                          ? "تفاصيل المصروف"
                          : "تفاصيل الحركة";

                  return (
                    <tr key={tx.id}>
                      <td>
                        <span className="font-mono text-xs font-bold text-slate-600">
                          #{tx.id}
                        </span>
                      </td>
                      <td className="text-xs text-slate-600 whitespace-nowrap">
                        {formatDateTime(tx.created_at)}
                      </td>
                      <td className="text-xs text-slate-600">
                        {tx.cashbox?.name ?? `#${tx.cashbox_id}`}
                      </td>
                      <td>
                        {isReversal ? (
                          <span className="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-1 text-xs font-semibold text-amber-800">
                            <i className="ri-arrow-go-back-line" />
                            عكس / استرداد
                          </span>
                        ) : (
                          <span
                            className={`inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs font-semibold ${
                              isIncome ? "bg-green-50 text-green-700" : "bg-red-50 text-red-600"
                            }`}
                          >
                            <i className={isIncome ? "ri-arrow-down-line" : "ri-arrow-up-line"} />
                            {isIncome ? "إيراد" : "مصروف"}
                          </span>
                        )}
                      </td>
                      <td>
                        <span className="rounded-full bg-blue-50 px-2 py-0.5 text-xs text-blue-600">
                          {getCategoryLabel(tx.category)}
                        </span>
                      </td>
                      <td className="text-center">
                        {isReversal ? (
                          amount >= 0 ? (
                            <span className="text-sm font-semibold text-emerald-600">
                              {formatMoney(amount)} ج.م
                            </span>
                          ) : (
                            <span className="text-slate-300 text-xs">—</span>
                          )
                        ) : isIncome ? (
                          <span className="text-sm font-semibold text-green-600">
                            {formatMoney(amount)} ج.م
                          </span>
                        ) : (
                          <span className="text-slate-300 text-xs">—</span>
                        )}
                      </td>
                      <td className="text-center">
                        {isReversal ? (
                          amount < 0 ? (
                            <span className="text-sm font-semibold text-red-600">
                              {formatMoney(Math.abs(amount))} ج.م
                            </span>
                          ) : (
                            <span className="text-slate-300 text-xs">—</span>
                          )
                        ) : !isIncome ? (
                          <span className="text-sm font-semibold text-red-600">
                            {formatMoney(amount)} ج.م
                          </span>
                        ) : (
                          <span className="text-slate-300 text-xs">—</span>
                        )}
                      </td>
                      <td className="text-center text-sm font-bold">
                        <span
                          className={balanceAfter >= 0 ? "text-blue-700" : "text-red-600"}
                        >
                          {formatMoney(balanceAfter)} ج.م
                        </span>
                      </td>
                      <td className="text-xs text-slate-500">
                        {tx.creator?.name ?? "—"}
                      </td>
                      <td className="text-center">
                        <button
                          type="button"
                          className="inline-flex h-8 w-8 cursor-pointer items-center justify-center rounded-lg border border-blue-200/90 bg-blue-50/90 text-blue-700 transition-colors hover:border-blue-300 hover:bg-blue-100"
                          title={detailsTitle}
                          aria-label={detailsTitle}
                          onClick={openRowDetails}
                        >
                          <i className="ri-eye-line text-base leading-none" />
                        </button>
                      </td>
                    </tr>
                  );
                })}
                <tr className="bg-slate-800 text-sm font-bold text-white">
                  <td colSpan={5} className="px-4 py-3 text-right">
                    <span className="ml-2 text-xs font-normal text-slate-300">
                      المجموع ({items.length} حركة)
                      {totalReversalAbs > 0 && (
                        <span className="mr-2 text-amber-200">
                          · عكس: {formatMoney(totalReversalAbs)} ج.م
                        </span>
                      )}
                    </span>
                  </td>
                  <td className="px-4 py-3 text-center text-green-300">
                    {formatMoney(totalIncome)} ج.م
                  </td>
                  <td className="px-4 py-3 text-center text-red-300">
                    {formatMoney(totalExpense)} ج.م
                  </td>
                  <td className="px-4 py-3 text-center">
                    <span
                      className={closingBalance >= 0 ? "text-green-300" : "text-red-300"}
                    >
                      {formatMoney(closingBalance)} ج.م
                    </span>
                  </td>
                  <td colSpan={2} className="px-4 py-3 text-center text-xs text-slate-300">
                    رصيد آخر المدة
                  </td>
                </tr>
              </>
            ) : (
              <tr>
                <td colSpan={10} className="py-14 text-center">
                  <div className="text-slate-400">
                    <i className="ri-inbox-line text-4xl mb-2 block" />
                    <p className="text-sm font-medium">لا توجد معاملات لعرضها</p>
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

      {!hidePaginationForPrint && (
        <div
          className="px-5 py-3 flex items-center justify-between flex-wrap gap-3"
          style={{ background: "#F8FAFC", borderTop: "1px solid #E2E8F0" }}
        >
          <span className="text-sm text-slate-500">
            إجمالي المعاملات: <span className="font-semibold text-slate-700">{total ?? 0}</span>
          </span>
          <CustomPagination
            totalElementsLabel="إجمالي الحركات"
            totalElements={total}
            totalPages={totalPages}
            isLoading={isPending}
          />
        </div>
      )}
    </div>
  );
}
