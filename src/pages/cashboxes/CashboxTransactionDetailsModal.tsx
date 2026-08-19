import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { TTransaction } from "@/api/v2/transactions/transactions.types";
import { formatDateTime } from "@/utils/formatDate";

type Props = {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  transaction: TTransaction | null;
};

const formatMoney = (value: number | string | null | undefined): string => {
  if (value === null || value === undefined) return "—";
  const num = typeof value === "number" ? value : Number(value);
  if (Number.isNaN(num)) return String(value);
  return num.toLocaleString("ar-EG", { minimumFractionDigits: 2 }) + " ج.م";
};

const getCategoryLabel = (category: TTransaction["category"]) => {
  switch (category) {
    case "payment":
      return "دفعة عميل";
    case "expense":
      return "مصروف";
    case "salary_expense":
      return "راتب / رواتب";
    case "receivable_payment":
      return "تحصيل مستحقات";
    case "reversal":
      return "عكس / استرداد";
    default:
      return category;
  }
};

const KNOWN_METADATA_KEYS = [
  "supplier_id",
  "order_number",
  "total_amount",
  "order_id",
  "payment_method",
  "period",
  "employee_id",
  "employee_code",
  "reason",
] as const;

export function CashboxTransactionDetailsModal({
  open,
  onOpenChange,
  transaction,
}: Props) {
  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-2xl" dir="rtl">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-3">
            <span>تفاصيل الحركة المالية</span>
            {transaction && (
              <span
                className={`inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-xs font-semibold ${
                  transaction.type === "reversal"
                    ? "border-amber-200 bg-amber-50 text-amber-800"
                    : transaction.type === "income"
                      ? "border-green-200 bg-green-50 text-green-700"
                      : "border-red-200 bg-red-50 text-red-600"
                }`}
              >
                <i
                  className={
                    transaction.type === "reversal"
                      ? "ri-arrow-go-back-line"
                      : transaction.type === "income"
                        ? "ri-arrow-down-line"
                        : "ri-arrow-up-line"
                  }
                />
                {transaction.type === "reversal"
                  ? "عكس / استرداد"
                  : transaction.type === "income"
                    ? "إيراد"
                    : "مصروف"}
              </span>
            )}
          </DialogTitle>
        </DialogHeader>

        {transaction ? (
          <div className="space-y-5 mt-2">
            
            <div
              className={`flex items-center justify-between rounded-xl border-2 p-5 ${
                transaction.type === "reversal"
                  ? "border-amber-200 bg-amber-50"
                  : transaction.type === "income"
                    ? "border-green-200 bg-green-50"
                    : "border-red-200 bg-red-50"
              }`}
            >
              <div className="flex items-center gap-3">
                <div
                  className={`flex h-12 w-12 items-center justify-center rounded-xl ${
                    transaction.type === "reversal"
                      ? "bg-amber-100 text-amber-700"
                      : transaction.type === "income"
                        ? "bg-green-100 text-green-600"
                        : "bg-red-100 text-red-500"
                  }`}
                >
                  <i
                    className={
                      transaction.type === "reversal"
                        ? "ri-arrow-go-back-line text-xl"
                        : transaction.type === "income"
                          ? "ri-arrow-down-circle-line text-xl"
                          : "ri-arrow-up-circle-line text-xl"
                    }
                  />
                </div>
                <div>
                  <p className="text-xs font-medium text-slate-500">المبلغ</p>
                  <p
                    className={`text-2xl font-black ${
                      transaction.type === "reversal"
                        ? "text-amber-900"
                        : transaction.type === "income"
                          ? "text-green-700"
                          : "text-red-600"
                    }`}
                  >
                    {transaction.type === "reversal"
                      ? ""
                      : transaction.type === "income"
                        ? "+"
                        : "-"}
                    {formatMoney(transaction.amount)}
                  </p>
                </div>
              </div>
              <div className="text-left">
                <p className="text-xs text-slate-500 mb-0.5">رقم الحركة</p>
                <p className="font-mono font-bold text-slate-700">
                  #{transaction.id}
                </p>
              </div>
            </div>

            
            <div className="modal-section">
              <p className="modal-section-title flex items-center gap-2">
                <i className="ri-information-line text-slate-500" />
                معلومات الحركة
              </p>
              <div className="space-y-0">
                <div className="modal-detail-row">
                  <span className="modal-detail-label">التاريخ</span>
                  <span className="modal-detail-value">
                    {formatDateTime(transaction.created_at)}
                  </span>
                </div>
                <div className="modal-detail-row">
                  <span className="modal-detail-label">الصندوق</span>
                  <span className="modal-detail-value">
                    {transaction.cashbox?.name ?? `#${transaction.cashbox_id}`}
                  </span>
                </div>
                <div className="modal-detail-row">
                  <span className="modal-detail-label">التصنيف</span>
                  <span className="modal-detail-value">
                    <span className="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-600">
                      {getCategoryLabel(transaction.category)}
                    </span>
                  </span>
                </div>
              </div>
            </div>

            
            <div className="modal-section">
              <p className="modal-section-title flex items-center gap-2">
                <i className="ri-wallet-3-line text-slate-500" />
                الأرصدة
              </p>
              <div className="space-y-0">
                <div className="modal-detail-row">
                  <span className="modal-detail-label">الرصيد قبل الحركة</span>
                  <span className="modal-detail-value font-mono text-amber-700">
                    {formatMoney(transaction.cashbox_balance_before)}
                  </span>
                </div>
                <div className="modal-detail-row">
                  <span className="modal-detail-label">الرصيد بعد الحركة</span>
                  <span className="modal-detail-value font-mono font-bold text-sky-700">
                    {formatMoney(
                      transaction.cashbox_balance_after != null
                        ? transaction.cashbox_balance_after
                        : transaction.balance_after,
                    )}
                  </span>
                </div>
              </div>
            </div>

            
            {(transaction.description?.trim() || transaction.reference_type) && (
              <div className="modal-section">
                <p className="modal-section-title flex items-center gap-2">
                  <i className="ri-file-text-line text-slate-500" />
                  الوصف
                </p>
                <p className="text-sm text-foreground whitespace-pre-wrap leading-relaxed">
                  {transaction.description?.trim() || "—"}
                </p>
                {transaction.reference_type && (
                  <p className="text-xs text-slate-500 mt-2">
                    المرجع: {transaction.reference_type}
                    {transaction.reference_id != null &&
                      ` #${transaction.reference_id}`}
                  </p>
                )}
              </div>
            )}

            
            {transaction.metadata &&
              Object.keys(transaction.metadata).length > 0 && (
                <div className="modal-section">
                  <p className="modal-section-title flex items-center gap-2">
                    <i className="ri-database-2-line text-slate-500" />
                    البيانات الإضافية
                  </p>
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    {"supplier_id" in transaction.metadata && (
                      <DetailItem
                        label="رقم المورد"
                        value={String(
                          (transaction.metadata as { supplier_id?: number })
                            .supplier_id
                        )}
                      />
                    )}
                    {"order_number" in transaction.metadata && (
                      <DetailItem
                        label="رقم طلب المورد"
                        value={
                          (transaction.metadata as { order_number?: string })
                            .order_number ?? "—"
                        }
                      />
                    )}
                    {"total_amount" in transaction.metadata && (
                      <DetailItem
                        label="إجمالي الطلب"
                        value={formatMoney(
                          (transaction.metadata as { total_amount?: string })
                            .total_amount
                        )}
                      />
                    )}
                    {"order_id" in transaction.metadata && (
                      <DetailItem
                        label="رقم الفاتورة"
                        value={String(
                          (transaction.metadata as { order_id?: number })
                            .order_id
                        )}
                      />
                    )}
                    {"payment_method" in transaction.metadata && (
                      <DetailItem
                        label="وسيلة الدفع"
                        value={
                          (transaction.metadata as {
                            payment_method?: string;
                          }).payment_method ?? "—"
                        }
                      />
                    )}
                    {"period" in transaction.metadata && (
                      <DetailItem
                        label="فترة الراتب"
                        value={
                          (transaction.metadata as { period?: string })
                            .period ?? "—"
                        }
                      />
                    )}
                    {"employee_id" in transaction.metadata && (
                      <DetailItem
                        label="رقم الموظف"
                        value={String(
                          (transaction.metadata as { employee_id?: number })
                            .employee_id
                        )}
                      />
                    )}
                    {"employee_code" in transaction.metadata && (
                      <DetailItem
                        label="كود الموظف"
                        value={
                          (transaction.metadata as {
                            employee_code?: string;
                          }).employee_code ?? "—"
                        }
                      />
                    )}
                    {"reason" in transaction.metadata && (
                      <DetailItem
                        label="السبب / الملاحظة"
                        value={
                          (transaction.metadata as { reason?: string }).reason ?? "—"
                        }
                      />
                    )}
                  </div>
                  {(() => {
                    const unknownKeys = Object.keys(transaction.metadata).filter(
                      (k) => !KNOWN_METADATA_KEYS.includes(k as (typeof KNOWN_METADATA_KEYS)[number])
                    );
                    if (unknownKeys.length === 0) return null;
                    const unknownEntries = Object.fromEntries(
                      unknownKeys.map((k) => [
                        k,
                        transaction.metadata![k as keyof typeof transaction.metadata],
                      ])
                    );
                    return (
                      <div className="mt-3">
                        <p className="text-xs text-slate-500 mb-2">حقول إضافية</p>
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                          {unknownKeys.map((k) => (
                            <DetailItem
                              key={k}
                              label={k}
                              value={
                                typeof unknownEntries[k] === "object"
                                  ? JSON.stringify(unknownEntries[k])
                                  : String(unknownEntries[k] ?? "—")
                              }
                            />
                          ))}
                        </div>
                      </div>
                    );
                  })()}
                </div>
              )}

            
            {transaction.creator && (
              <div className="modal-section">
                <p className="modal-section-title flex items-center gap-2">
                  <i className="ri-user-line text-slate-500" />
                  المستخدم
                </p>
                <div className="flex items-center gap-3 p-3 rounded-lg bg-slate-50 border border-slate-100">
                  <div className="w-10 h-10 flex items-center justify-center rounded-full bg-sky-100 text-sky-600">
                    <i className="ri-user-3-line text-lg" />
                  </div>
                  <div>
                    <p className="font-semibold text-slate-800">
                      {transaction.creator.name}
                    </p>
                    {transaction.creator.email && (
                      <p className="text-xs text-slate-500">
                        {transaction.creator.email}
                      </p>
                    )}
                  </div>
                </div>
              </div>
            )}

            {transaction.is_reversed && (
              <div className="rounded-lg border border-amber-200 bg-amber-50 px-4 py-2.5 flex items-center gap-2 text-amber-800 text-sm">
                <i className="ri-error-warning-line" />
                <span>تم عكس هذه الحركة</span>
              </div>
            )}
          </div>
        ) : (
          <div className="flex flex-col items-center justify-center py-12 text-slate-400">
            <i className="ri-inbox-line text-4xl mb-3" />
            <p className="text-sm font-medium">لا توجد بيانات لعرضها.</p>
          </div>
        )}
      </DialogContent>
    </Dialog>
  );
}

function DetailItem({ label, value }: { label: string; value: string }) {
  return (
    <div className="flex flex-col gap-0.5 p-2.5 rounded-lg bg-slate-50/50 border border-slate-100">
      <span className="text-xs text-slate-500">{label}</span>
      <span className="text-sm font-medium text-slate-700">{value}</span>
    </div>
  );
}
