import type { TOrder } from "@/api/v2/orders/orders.types";
import { getOrderCurrencyInfo } from "@/api/v2/orders/order.utils";
import type { DeliveryInvoiceProject, ProjectDeliveryStatus } from "./deliveryInvoiceProject.types";

export interface DeliveryInvoiceTableRow {
  invoice: DeliveryInvoiceProject;
  order: TOrder;
}

interface Props {
  rows: DeliveryInvoiceTableRow[];
  onStatusChange: (order: TOrder, status: ProjectDeliveryStatus) => void;
  onNavigateOrder: (id: number) => void;
  onCancelClick: (order: TOrder) => void;
  onDeleteClick: (order: TOrder) => void;
  onPaymentClick: (order: TOrder) => void;
  onCustodyClick: (order: TOrder) => void;
  onEdit: (order: TOrder) => void;
  onDeliver: (order: TOrder) => void;
  isDelivering: boolean;
}

const fmt = (n: number) =>
  new Intl.NumberFormat("ar-EG", { minimumFractionDigits: 2 }).format(n);

const paymentColors: Record<string, string> = {
  مدفوع: "bg-green-500 text-white",
  "مدفوع جزئياً": "bg-amber-400 text-white",
  "غير مدفوع": "bg-red-100 text-red-600",
};

const deliveryColors: Record<string, string> = {
  "في الانتظار": "bg-blue-100 text-blue-600",
  "تم الاستلام": "bg-amber-100 text-amber-600",
  "تم التسليم": "bg-green-100 text-green-600",
  "تم الاسترجاع": "bg-purple-100 text-purple-600",
  متأخر: "bg-red-100 text-red-500",
  ملغي: "bg-gray-100 text-gray-500",
};

const itemTypeColors: Record<string, string> = {
  إيجار: "bg-blue-50 text-blue-600",
  بيع: "bg-green-50 text-green-600",
  تفصيل: "bg-purple-50 text-purple-600",
};

const statusSelectOptions: ProjectDeliveryStatus[] = [
  "في الانتظار",
  "تم الاستلام",
  "تم التسليم",
  "تم الاسترجاع",
  "متأخر",
  "ملغي",
];

function formatDateDisplay(ymd: string) {
  if (!ymd) return "—";
  return ymd.replace(/-/g, "/");
}

export default function DeliveryInvoicesTable({
  rows,
  onStatusChange,
  onNavigateOrder,
  onCancelClick,
  onDeleteClick,
  onPaymentClick,
  onCustodyClick,
  onEdit,
  onDeliver,
  isDelivering,
}: Props) {
  const today = new Date().toISOString().slice(0, 10);

  if (rows.length === 0) {
    return (
      <div className="bg-white rounded-xl border border-blue-100 p-16 text-center">
        <div className="w-16 h-16 flex items-center justify-center mx-auto rounded-full bg-blue-50 text-blue-200 mb-4">
          <i className="ri-inbox-line text-3xl" />
        </div>
        <p className="text-gray-400 text-sm">لا توجد فواتير تطابق الفلاتر</p>
      </div>
    );
  }

  return (
    <div className="bg-white rounded-xl border border-blue-100 overflow-hidden">
      <div
        className="grid bg-blue-900 text-white text-xs font-medium"
        style={{ gridTemplateColumns: "50px 1fr 260px 1fr 120px" }}
      >
        <div className="px-4 py-3 text-center">#</div>
        <div className="px-4 py-3">بيانات العميل</div>
        <div className="px-4 py-3 text-center">التواريخ / الإجراءات</div>
        <div className="px-4 py-3 text-center">الأصناف / المبلغ / الحالة</div>
        <div className="px-4 py-3 text-center">الموظف</div>
      </div>

      <div className="divide-y divide-gray-100">
        {rows.map(({ invoice: inv, order }) => {
          const sym = getOrderCurrencyInfo(order).currency_symbol;
          const isToday = inv.dates.eventDate === today;
          const isPast =
            Boolean(inv.dates.eventDate) &&
            inv.dates.eventDate < today &&
            inv.deliveryStatus === "في الانتظار";

          return (
            <div
              key={inv.id}
              className={`grid hover:bg-blue-50/20 transition-colors group ${isToday ? "bg-rose-50/30 border-r-2 border-rose-400" : ""} ${isPast ? "bg-amber-50/20" : ""}`}
              style={{ gridTemplateColumns: "50px 1fr 260px 1fr 120px" }}
            >
              <div className="px-3 py-4 text-center">
                <button
                  type="button"
                  onClick={() => onNavigateOrder(inv.id)}
                  className="font-bold text-blue-600 text-sm hover:underline"
                >
                  #{inv.id}
                </button>
                {isToday ? (
                  <div className="mt-1">
                    <span className="text-xs bg-rose-100 text-rose-500 px-1.5 py-0.5 rounded-full animate-pulse">
                      اليوم
                    </span>
                  </div>
                ) : null}
              </div>

              <div className="px-4 py-4 space-y-1.5">
                <p className="font-semibold text-gray-800 text-sm">
                  <span className="text-gray-400 text-xs ml-1">الاسم:</span>
                  {inv.customer.name}
                </p>
                <p className="text-xs text-gray-500">
                  <span className="text-gray-400 ml-1">الرقم القومي:</span>
                  <span className="font-mono">{inv.customer.nationalId}</span>
                </p>
                <p className="text-xs text-gray-500">
                  <span className="text-gray-400 ml-1">الهاتف:</span>
                  <span dir="ltr" className="inline-block">
                    {inv.customer.phone}
                  </span>
                </p>
                <p className="text-xs text-gray-500">
                  <span className="text-gray-400 ml-1">هاتف الواتساب:</span>
                  <span dir="ltr" className="inline-block">
                    {inv.customer.whatsapp}
                  </span>
                </p>
                <p className="text-xs text-gray-400 leading-relaxed">
                  <span className="text-gray-400 ml-1">العنوان:</span>
                  {inv.customer.address}
                </p>
              </div>

              <div className="px-3 py-4 border-x border-gray-100">
                <div className="space-y-1.5 mb-3">
                  <div className="flex items-center justify-between text-xs">
                    <span className="text-gray-400">تاريخ الفاتورة:</span>
                    <span className="text-gray-600 font-mono text-xs">
                      {formatDateDisplay(inv.dates.invoiceDate)}
                    </span>
                  </div>
                  <div className="flex items-center justify-between text-xs">
                    <span className="text-gray-400">استلام:</span>
                    <span className="text-amber-600 font-mono text-xs">
                      {formatDateDisplay(inv.dates.pickupDate)}
                    </span>
                  </div>
                  <div className="flex items-center justify-between text-xs">
                    <span className="text-gray-400">الفرح:</span>
                    <span
                      className={`font-mono text-xs font-semibold ${isToday ? "text-rose-600 font-bold" : "text-blue-600"}`}
                    >
                      {formatDateDisplay(inv.dates.eventDate)}
                    </span>
                  </div>
                  <div className="flex items-center justify-between text-xs">
                    <span className="text-gray-400">استرجاع:</span>
                    <span className="text-purple-600 font-mono text-xs">
                      {formatDateDisplay(inv.dates.returnDate)}
                    </span>
                  </div>
                </div>

                <div className="flex flex-wrap gap-1.5 justify-center">
                  <button
                    type="button"
                    title="إلغاء"
                    onClick={() => onCancelClick(order)}
                    className="w-7 h-7 flex items-center justify-center rounded-md bg-red-50 text-red-400 hover:bg-red-100 cursor-pointer transition-colors"
                  >
                    <i className="ri-forbid-line text-sm" />
                  </button>
                  <button
                    type="button"
                    title="ضمان"
                    onClick={() => onCustodyClick(order)}
                    className="w-7 h-7 flex items-center justify-center rounded-md bg-blue-50 text-blue-500 hover:bg-blue-100 cursor-pointer transition-colors"
                  >
                    <i className="ri-shield-check-line text-sm" />
                  </button>
                  <button
                    type="button"
                    title="تسجيل دفعة"
                    onClick={() => onPaymentClick(order)}
                    className="w-7 h-7 flex items-center justify-center rounded-md bg-green-50 text-green-500 hover:bg-green-100 cursor-pointer transition-colors"
                  >
                    <i className="ri-bank-card-line text-sm" />
                  </button>
                  <button
                    type="button"
                    title="تسليم"
                    onClick={() => onDeliver(order)}
                    disabled={isDelivering}
                    className="w-7 h-7 flex items-center justify-center rounded-md bg-emerald-50 text-emerald-600 hover:bg-emerald-100 cursor-pointer transition-colors disabled:opacity-50"
                  >
                    <i className="ri-checkbox-circle-line text-sm" />
                  </button>
                  <button
                    type="button"
                    title="تعديل"
                    onClick={() => onEdit(order)}
                    className="w-7 h-7 flex items-center justify-center rounded-md bg-amber-50 text-amber-500 hover:bg-amber-100 cursor-pointer transition-colors"
                  >
                    <i className="ri-edit-line text-sm" />
                  </button>
                  <button
                    type="button"
                    title="عرض"
                    onClick={() => onNavigateOrder(inv.id)}
                    className="w-7 h-7 flex items-center justify-center rounded-md bg-gray-100 text-gray-500 hover:bg-gray-200 cursor-pointer transition-colors"
                  >
                    <i className="ri-eye-line text-sm" />
                  </button>
                  <div className="relative w-full mt-1">
                    <select
                      value={inv.deliveryStatus}
                      onChange={(e) =>
                        onStatusChange(order, e.target.value as ProjectDeliveryStatus)
                      }
                      className={`w-full text-xs px-2 py-1 rounded-md border-0 cursor-pointer font-medium focus:outline-none ${deliveryColors[inv.deliveryStatus] ?? "bg-gray-100 text-gray-600"}`}
                    >
                      {statusSelectOptions.map((s) => (
                        <option key={s} value={s}>
                          {s}
                        </option>
                      ))}
                    </select>
                  </div>
                  <button
                    type="button"
                    title="حذف"
                    onClick={() => onDeleteClick(order)}
                    className="w-7 h-7 flex items-center justify-center rounded-md bg-red-50 text-red-400 hover:bg-red-100 cursor-pointer transition-colors"
                  >
                    <i className="ri-delete-bin-line text-sm" />
                  </button>
                </div>
              </div>

              <div className="px-4 py-4 border-l border-gray-100">
                <div className="mb-3">
                  <p className="text-xs text-gray-400 mb-1.5">الأصناف:</p>
                  <div className="space-y-1">
                    {inv.items.map((item, i) => (
                      <div key={i} className="flex items-center gap-1.5">
                        <span
                          className={`text-xs px-1.5 py-0.5 rounded ${itemTypeColors[item.type] ?? "bg-gray-50 text-gray-600"}`}
                        >
                          {item.type}
                        </span>
                        <span className="text-xs text-gray-700">{item.name}</span>
                      </div>
                    ))}
                  </div>
                </div>

                <div className="space-y-1 border-t border-gray-100 pt-2">
                  <div className="flex items-center justify-between text-xs">
                    <span className="text-gray-400">السعر:</span>
                    <span className="text-gray-700 font-medium">
                      {fmt(inv.pricing.total)} {sym}
                    </span>
                  </div>
                  <div className="flex items-center justify-between text-xs">
                    <span className="text-gray-400">المدفوع:</span>
                    <span className="text-green-600 font-semibold">
                      {fmt(inv.pricing.paid)} {sym}
                    </span>
                  </div>
                  <div className="flex items-center justify-between text-xs">
                    <span className="text-gray-400">المتبقي:</span>
                    <span
                      className={`font-semibold ${inv.pricing.remaining > 0 ? "text-red-500" : "text-gray-400"}`}
                    >
                      {fmt(inv.pricing.remaining)} {sym}
                    </span>
                  </div>
                </div>

                <div className="mt-2.5">
                  <span
                    className={`text-xs font-semibold px-3 py-1 rounded-full ${paymentColors[inv.paymentStatus] ?? "bg-gray-100 text-gray-600"}`}
                  >
                    {inv.paymentStatus}
                    {inv.items[0]?.type === "إيجار" ? (
                      <span className="mr-1 opacity-75">(إيجار)</span>
                    ) : null}
                  </span>
                </div>

                {inv.notes ? (
                  <p className="text-xs text-gray-400 mt-2 italic truncate" title={inv.notes}>
                    {inv.notes}
                  </p>
                ) : null}
              </div>

              <div className="px-3 py-4 text-center">
                <div className="flex flex-col items-center gap-2">
                  <div className="w-9 h-9 flex items-center justify-center rounded-full bg-blue-100 text-blue-600 text-sm font-bold">
                    {inv.employee && inv.employee !== "-" ? inv.employee.charAt(0) : "؟"}
                  </div>
                  <span className="text-xs text-gray-600 font-medium leading-tight text-center">
                    {inv.employee}
                  </span>
                  <span className="text-xs text-gray-400">{inv.branch}</span>
                </div>
              </div>
            </div>
          );
        })}
      </div>

      <div className="bg-blue-900 text-white px-5 py-3 flex items-center justify-between text-sm">
        <span className="text-blue-200 text-xs">{rows.length} فاتورة</span>
        <div className="flex items-center gap-6 text-xs">
          <span className="text-blue-200">
            الإجمالي:{" "}
            <strong className="text-white">
              {fmt(rows.reduce((s, r) => s + r.invoice.pricing.total, 0))} ج.م
            </strong>
          </span>
          <span className="text-green-300">
            محصّل:{" "}
            <strong>{fmt(rows.reduce((s, r) => s + r.invoice.pricing.paid, 0))} ج.م</strong>
          </span>
          <span className="text-amber-300">
            متبقي:{" "}
            <strong>{fmt(rows.reduce((s, r) => s + r.invoice.pricing.remaining, 0))} ج.م</strong>
          </span>
        </div>
      </div>
    </div>
  );
}
