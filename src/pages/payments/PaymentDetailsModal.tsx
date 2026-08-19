import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { TPayment } from "@/api/v2/payments/payments.types";
import { formatDate } from "@/utils/formatDate";

type Props = {
  payment: TPayment;
  open: boolean;
  onOpenChange: (open: boolean) => void;
};

const getStatusLabel = (status: string) => {
  const statusMap: Record<string, string> = {
    pending: "معلق",
    paid: "مدفوع",
    canceled: "ملغي",
  };
  return statusMap[status] || status;
};

const getStatusBadgeClass = (status: string) => {
  const classMap: Record<string, string> = {
    pending: "bg-amber-50 text-amber-700 border border-amber-200",
    paid: "bg-green-50 text-green-700 border border-green-200",
    canceled: "bg-red-50 text-red-600 border border-red-200",
  };
  return classMap[status] || "bg-slate-50 text-slate-600 border border-slate-200";
};

const getPaymentTypeLabel = (type: string) => {
  const typeMap: Record<string, string> = {
    initial: "مبدئي",
    fee: "رسوم",
    normal: "عادي",
  };
  return typeMap[type] || type;
};

const formatMoney = (value: number | string | null | undefined) => {
  if (value === null || value === undefined) return "—";
  const num = typeof value === "number" ? value : Number(value);
  if (Number.isNaN(num)) return String(value);
  return num.toLocaleString("ar-EG", { minimumFractionDigits: 2 }) + " ج.م";
};

const getClientName = (
  client:
    | { name?: string; first_name?: string; middle_name?: string; last_name?: string }
    | null
    | undefined
) => {
  if (!client) return "—";
  if (typeof client.name === "string" && client.name.trim()) return client.name.trim();
  const parts = [client.first_name, client.middle_name, client.last_name].filter(Boolean) as string[];
  return parts.length ? parts.join(" ").trim() : "—";
};

function DetailItem({ label, value }: { label: string; value: React.ReactNode }) {
  return (
    <div className="flex flex-col gap-0.5 p-2.5 rounded-lg bg-slate-50/50 border border-slate-100">
      <span className="text-xs text-slate-500">{label}</span>
      <span className="text-sm font-medium text-slate-700">{value}</span>
    </div>
  );
}

export function PaymentDetailsModal({ payment, open, onOpenChange }: Props) {
  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-2xl" dir="rtl">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-3">
            <span>تفاصيل الدفعة</span>
            <span
              className={`inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold ${getStatusBadgeClass(
                payment.status
              )}`}
            >
              <i className="ri-bank-card-line" />
              {getStatusLabel(payment.status)}
            </span>
          </DialogTitle>
        </DialogHeader>

        <div className="space-y-5 mt-2">
          
          <div className="rounded-xl border-2 p-5 flex items-center justify-between bg-sky-50 border-sky-200">
            <div className="flex items-center gap-3">
              <div className="w-12 h-12 flex items-center justify-center rounded-xl bg-sky-100 text-sky-600">
                <i className="ri-money-dollar-circle-line text-xl" />
              </div>
              <div>
                <p className="text-xs text-slate-500 font-medium">المبلغ</p>
                <p className="text-2xl font-black text-sky-700">
                  {formatMoney(payment.amount)}
                </p>
              </div>
            </div>
            <div className="text-left">
              <p className="text-xs text-slate-500 mb-0.5">رقم الدفعة</p>
              <p className="font-mono font-bold text-slate-700">#{payment.id}</p>
            </div>
          </div>

          
          <div className="modal-section">
            <p className="modal-section-title flex items-center gap-2">
              <i className="ri-information-line text-slate-500" />
              معلومات الدفعة
            </p>
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <DetailItem label="رقم الطلب" value={payment.order_id} />
              <DetailItem label="نوع الدفعة" value={getPaymentTypeLabel(payment.payment_type)} />
              <DetailItem label="تاريخ الدفع" value={formatDate(payment.payment_date)} />
              {payment.transaction_id != null && (
                <DetailItem label="رقم حركة الخزنة" value={`#${payment.transaction_id}`} />
              )}
            </div>
          </div>

          
          {payment.order?.client && (
            <div className="modal-section">
              <p className="modal-section-title flex items-center gap-2">
                <i className="ri-user-line text-slate-500" />
                معلومات العميل
              </p>
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <DetailItem
                  label="اسم العميل"
                  value={getClientName(payment.order.client)}
                />
                <DetailItem
                  label="الرقم القومي"
                  value={payment.order.client.national_id ?? "—"}
                />
                <DetailItem
                  label="مصدر العميل"
                  value={payment.order.client.source ?? "—"}
                />
              </div>
            </div>
          )}

          
          {payment.order && (
            <div className="modal-section">
              <p className="modal-section-title flex items-center gap-2">
                <i className="ri-shopping-cart-line text-slate-500" />
                معلومات الطلب
              </p>
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <DetailItem label="رقم الطلب" value={payment.order.id} />
                <DetailItem label="إجمالي الطلب" value={formatMoney(payment.order.total_price)} />
                <DetailItem label="حالة الطلب" value={payment.order.status} />
                <DetailItem label="إجمالي المدفوع" value={formatMoney(payment.order.paid)} />
                <DetailItem label="المتبقي" value={formatMoney(payment.order.remaining)} />
                <DetailItem
                  label="موعد الاستلام"
                  value={payment.order.delivery_date ? formatDate(payment.order.delivery_date) : "—"}
                />
                <DetailItem
                  label="تاريخ المناسبة"
                  value={
                    payment.order.occasion_datetime
                      ? formatDate(payment.order.occasion_datetime)
                      : "—"
                  }
                />
                <DetailItem
                  label="موعد الاسترجاع"
                  value={
                    payment.order.visit_datetime
                      ? formatDate(payment.order.visit_datetime)
                      : "—"
                  }
                />
              </div>
              {payment.order.order_notes && (
                <div className="mt-3">
                  <p className="text-xs text-slate-500 mb-1.5">ملاحظات الطلب</p>
                  <p className="text-sm text-slate-700 whitespace-pre-wrap rounded-lg bg-slate-50 p-3 border border-slate-100">
                    {payment.order.order_notes}
                  </p>
                </div>
              )}
            </div>
          )}

          
          {payment.user && (
            <div className="modal-section">
              <p className="modal-section-title flex items-center gap-2">
                <i className="ri-user-settings-line text-slate-500" />
                المستخدم
              </p>
              <div className="flex items-center gap-3 p-3 rounded-lg bg-slate-50 border border-slate-100">
                <div className="w-10 h-10 flex items-center justify-center rounded-full bg-sky-100 text-sky-600">
                  <i className="ri-user-3-line text-lg" />
                </div>
                <div>
                  <p className="font-semibold text-slate-800">{payment.user.name}</p>
                  {payment.user.email && (
                    <p className="text-xs text-slate-500">{payment.user.email}</p>
                  )}
                </div>
              </div>
            </div>
          )}

          
          {(payment.cashbox_id != null ||
            payment.cashbox_balance_before != null ||
            payment.cashbox_balance_after != null ||
            payment.cashbox_snapshot_meta) && (
            <div className="modal-section">
              <p className="modal-section-title flex items-center gap-2">
                <i className="ri-wallet-3-line text-slate-500" />
                معلومات الخزنة
              </p>
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <DetailItem
                  label="الصندوق"
                  value={
                    payment.cashbox_snapshot_meta?.cashbox_name ??
                    (payment.cashbox_id != null ? `#${payment.cashbox_id}` : "—")
                  }
                />
                {payment.cashbox_balance_before != null && (
                  <DetailItem
                    label="الرصيد قبل الدفعة"
                    value={formatMoney(payment.cashbox_balance_before)}
                  />
                )}
                {payment.cashbox_balance_after != null && (
                  <DetailItem
                    label="الرصيد بعد الدفعة"
                    value={formatMoney(payment.cashbox_balance_after)}
                  />
                )}
                {payment.cashbox_daily_income_total != null && (
                  <DetailItem
                    label="إجمالي إيرادات اليوم"
                    value={formatMoney(payment.cashbox_daily_income_total)}
                  />
                )}
                {payment.cashbox_daily_expense_total != null && (
                  <DetailItem
                    label="إجمالي مصروفات اليوم"
                    value={formatMoney(payment.cashbox_daily_expense_total)}
                  />
                )}
                {payment.cashbox_snapshot_meta && (
                  <>
                    <DetailItem
                      label="رصيد بداية اليوم"
                      value={formatMoney(payment.cashbox_snapshot_meta.opening_balance)}
                    />
                    <DetailItem
                      label="رصيد نهاية اليوم"
                      value={formatMoney(payment.cashbox_snapshot_meta.closing_balance)}
                    />
                    <DetailItem
                      label="صافي التغير"
                      value={formatMoney(payment.cashbox_snapshot_meta.net_change)}
                    />
                  </>
                )}
              </div>
            </div>
          )}

          {payment.cashbox_id == null &&
            payment.cashbox_balance_before == null &&
            payment.cashbox_balance_after == null &&
            !payment.cashbox_snapshot_meta && (
              <p className="rounded-lg border border-slate-100 bg-slate-50 px-3 py-2 text-xs text-slate-500">
                لا تتوفر لقطة خزنة لهذه الدفعة (قد تكون من سجلات قديمة أو لم تُسجَّل في الصندوق بعد).
              </p>
            )}

          
          {payment.notes && (
            <div className="modal-section">
              <p className="modal-section-title flex items-center gap-2">
                <i className="ri-file-text-line text-slate-500" />
                الملاحظات
              </p>
              <p className="text-sm text-slate-700 whitespace-pre-wrap">
                {payment.notes}
              </p>
            </div>
          )}

          
          <div className="modal-section">
            <p className="modal-section-title flex items-center gap-2">
              <i className="ri-time-line text-slate-500" />
              التواريخ
            </p>
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <DetailItem label="تاريخ الإنشاء" value={formatDate(payment.created_at)} />
              <DetailItem label="تاريخ التحديث" value={formatDate(payment.updated_at)} />
            </div>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  );
}
