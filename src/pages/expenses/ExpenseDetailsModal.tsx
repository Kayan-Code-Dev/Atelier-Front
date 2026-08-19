import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import {
  getExpenseCategoryDisplay,
  TExpense,
} from "@/api/v2/expenses/expenses.types";
import { formatDate } from "@/utils/formatDate";

const getStatusLabel = (status: TExpense["status"]) => {
  const map: Record<TExpense["status"], string> = {
    pending: "معلق",
    paid: "مدفوع",
    cancelled: "ملغي",
  };
  return map[status] || status;
};

const getStatusBadgeClass = (status: TExpense["status"]) => {
  const map: Record<TExpense["status"], string> = {
    pending: "bg-amber-50 text-amber-700 border border-amber-200",
    paid: "bg-green-50 text-green-700 border border-green-200",
    cancelled: "bg-red-50 text-red-600 border border-red-200",
  };
  return map[status] ?? "bg-slate-50 text-slate-600 border border-slate-200";
};

type Props = {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  expense: TExpense | null;
};

const formatMoney = (value: number | string | null | undefined) => {
  if (value === null || value === undefined) return "—";
  const num = typeof value === "number" ? value : Number(value);
  if (Number.isNaN(num)) return String(value);
  return num.toLocaleString("ar-EG", { minimumFractionDigits: 2 }) + " ج.م";
};

function DetailItem({ label, value }: { label: string; value: React.ReactNode }) {
  return (
    <div className="flex flex-col gap-0.5 p-2.5 rounded-lg bg-slate-50/50 border border-slate-100">
      <span className="text-xs text-slate-500">{label}</span>
      <span className="text-sm font-medium text-slate-700">{value}</span>
    </div>
  );
}

export function ExpenseDetailsModal({
  open,
  onOpenChange,
  expense,
}: Props) {
  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-2xl" dir="rtl">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-3">
            <span>تفاصيل المصروف</span>
            {expense && (
              <span
                className={`inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold ${getStatusBadgeClass(
                  expense.status
                )}`}
              >
                <i className="ri-file-list-3-line" />
                {getStatusLabel(expense.status)}
              </span>
            )}
          </DialogTitle>
        </DialogHeader>

        {expense ? (
          <div className="space-y-5 mt-2">
            
            <div className="rounded-xl border-2 p-5 flex items-center justify-between bg-red-50 border-red-200">
              <div className="flex items-center gap-3">
                <div className="w-12 h-12 flex items-center justify-center rounded-xl bg-red-100 text-red-600">
                  <i className="ri-arrow-up-circle-line text-xl" />
                </div>
                <div>
                  <p className="text-xs text-slate-500 font-medium">المبلغ</p>
                  <p className="text-2xl font-black text-red-600">
                    -{formatMoney(expense.amount)}
                  </p>
                </div>
              </div>
              <div className="text-left">
                <p className="text-xs text-slate-500 mb-0.5">رقم المصروف</p>
                <p className="font-mono font-bold text-slate-700">#{expense.id}</p>
              </div>
            </div>

            
            <div className="modal-section">
              <p className="modal-section-title flex items-center gap-2">
                <i className="ri-information-line text-slate-500" />
                معلومات المصروف
              </p>
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <DetailItem label="تاريخ المصروف" value={formatDate(expense.expense_date)} />
                <DetailItem label="الفرع" value={expense.branch?.name ?? "—"} />
                <DetailItem label="الصندوق" value={expense.cashbox?.name ?? "—"} />
                <DetailItem label="المورد" value={expense.vendor ?? "—"} />
                <DetailItem label="رقم المرجع" value={expense.reference_number ?? "—"} />
                <DetailItem
                  label="الفئة"
                  value={
                    <span className="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700">
                      {getExpenseCategoryDisplay(expense.category, expense.subcategory)}
                    </span>
                  }
                />
                <DetailItem label="تاريخ الإنشاء" value={formatDate(expense.created_at)} />
                <DetailItem label="تاريخ آخر تحديث" value={formatDate(expense.updated_at)} />
                {expense.paid_at && (
                  <DetailItem label="تاريخ الدفع الفعلي" value={formatDate(expense.paid_at)} />
                )}
                {expense.transaction_id != null && (
                  <DetailItem label="رقم حركة الخزنة" value={`#${expense.transaction_id}`} />
                )}
              </div>
            </div>

            
            {(expense.description?.trim() || expense.notes?.trim()) && (
              <div className="modal-section">
                <p className="modal-section-title flex items-center gap-2">
                  <i className="ri-file-text-line text-slate-500" />
                  {expense.description?.trim() ? "الوصف" : "الملاحظات"}
                </p>
                <p className="text-sm text-slate-700 whitespace-pre-wrap leading-relaxed">
                  {expense.description?.trim() || expense.notes?.trim() || "—"}
                </p>
                {expense.description?.trim() && expense.notes?.trim() && (
                  <div className="mt-3 pt-3 border-t border-slate-200">
                    <p className="text-xs text-slate-500 mb-1.5">الملاحظات</p>
                    <p className="text-sm text-slate-700 whitespace-pre-wrap">
                      {expense.notes}
                    </p>
                  </div>
                )}
              </div>
            )}

            
            {(expense.creator || expense.approver) && (
              <div className="modal-section">
                <p className="modal-section-title flex items-center gap-2">
                  <i className="ri-user-line text-slate-500" />
                  المستخدمون
                </p>
                <div className="space-y-3">
                  {expense.creator && (
                    <div className="flex items-center gap-3 p-3 rounded-lg bg-slate-50 border border-slate-100">
                      <div className="w-10 h-10 flex items-center justify-center rounded-full bg-sky-100 text-sky-600">
                        <i className="ri-user-3-line text-lg" />
                      </div>
                      <div>
                        <p className="text-xs text-slate-500">أنشئ بواسطة</p>
                        <p className="font-semibold text-slate-800">{expense.creator.name}</p>
                        {expense.creator.email && (
                          <p className="text-xs text-slate-500">{expense.creator.email}</p>
                        )}
                      </div>
                    </div>
                  )}
                  {expense.approver && (
                    <div className="flex items-center gap-3 p-3 rounded-lg bg-green-50/50 border border-green-100">
                      <div className="w-10 h-10 flex items-center justify-center rounded-full bg-green-100 text-green-600">
                        <i className="ri-user-check-line text-lg" />
                      </div>
                      <div>
                        <p className="text-xs text-slate-500">تمت الموافقة بواسطة</p>
                        <p className="font-semibold text-slate-800">{expense.approver.name}</p>
                        {expense.approver.email && (
                          <p className="text-xs text-slate-500">{expense.approver.email}</p>
                        )}
                      </div>
                    </div>
                  )}
                </div>
              </div>
            )}

            
            {(expense.cashbox_balance_before != null ||
              expense.cashbox_balance_after != null ||
              expense.cashbox_snapshot_meta) && (
              <div className="modal-section">
                <p className="modal-section-title flex items-center gap-2">
                  <i className="ri-wallet-3-line text-slate-500" />
                  معلومات الخزنة
                </p>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <DetailItem label="الصندوق" value={expense.cashbox?.name ?? "—"} />
                  {expense.cashbox_balance_before != null && (
                    <DetailItem
                      label="الرصيد قبل المصروف"
                      value={formatMoney(expense.cashbox_balance_before)}
                    />
                  )}
                  {expense.cashbox_balance_after != null && (
                    <DetailItem
                      label="الرصيد بعد المصروف"
                      value={formatMoney(expense.cashbox_balance_after)}
                    />
                  )}
                  {expense.cashbox_daily_income_total != null && (
                    <DetailItem
                      label="إجمالي إيرادات اليوم"
                      value={formatMoney(expense.cashbox_daily_income_total)}
                    />
                  )}
                  {expense.cashbox_daily_expense_total != null && (
                    <DetailItem
                      label="إجمالي مصروفات اليوم"
                      value={formatMoney(expense.cashbox_daily_expense_total)}
                    />
                  )}
                  {expense.cashbox_snapshot_meta && (
                    <>
                      <DetailItem
                        label="رصيد بداية اليوم"
                        value={formatMoney(
                          expense.cashbox_snapshot_meta.opening_balance
                        )}
                      />
                      <DetailItem
                        label="رصيد نهاية اليوم"
                        value={formatMoney(
                          expense.cashbox_snapshot_meta.closing_balance
                        )}
                      />
                      <DetailItem
                        label="صافي التغير"
                        value={formatMoney(
                          expense.cashbox_snapshot_meta.net_change
                        )}
                      />
                    </>
                  )}
                </div>
              </div>
            )}

            {expense.status === "paid" &&
              expense.cashbox_balance_before == null &&
              expense.cashbox_balance_after == null &&
              !expense.cashbox_snapshot_meta && (
                <p className="rounded-lg border border-slate-100 bg-slate-50 px-3 py-2 text-xs text-slate-500">
                  لا تتوفر لقطة خزنة لهذا المصروف المدفوع (سجل قديم أو دفع خارج التدفق الحالي).
                </p>
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
