import type { TOrder } from "@/api/v2/orders/orders.types";
import { formatDate, toEnglishNumerals } from "@/utils/formatDate";
import { formatPhone } from "@/utils/formatPhone";
import {
  getOrderCurrencyInfo,
  getItemListDisplay,
} from "@/api/v2/orders/order.utils";
import { OrderEmployeeName } from "@/components/custom/OrderEmployeeName";
import {
  getRentalUiPayment,
  getRentalUiStatus,
  paymentStatusColors,
  rentalStatusColors,
} from "../rentalUi";


export interface RentalTableProps {
  invoices: TOrder[];
  isPending: boolean;
  isDelivering: boolean;
  onPrint: (order: TOrder) => void;
  onPrintClientCopy: (order: TOrder) => void;
  onPrintAck: (order: TOrder) => void;
  onEdit: (order: TOrder) => void;
  onView: (order: TOrder) => void;
  onCustody: (order: TOrder) => void;
  onPayment: (order: TOrder) => void;
  onDeliver: (order: TOrder) => void;
  onCancel: (order: TOrder) => void;
  onDelete: (order: TOrder) => void;
  canEdit: (o: TOrder) => boolean;
  canPrint: (o: TOrder) => boolean;
  canAddPayment: (o: TOrder) => boolean;
  canCustody: (o: TOrder) => boolean;
  canDeliver: (o: TOrder) => boolean;
  canCancel: (o: TOrder) => boolean;
  canDelete: (o: TOrder) => boolean;
}

export default function RentalTable({
  invoices,
  isPending,
  isDelivering,
  onPrint,
  onPrintClientCopy,
  onPrintAck,
  onEdit,
  onView,
  onCustody,
  onPayment,
  onDeliver,
  onCancel,
  onDelete,
  canEdit,
  canPrint,
  canAddPayment,
  canCustody,
  canDeliver,
  canCancel,
  canDelete,
}: RentalTableProps) {
  return (
    <div className="bg-white rounded-lg border border-slate-200 overflow-hidden">
      <table className="w-full text-sm" dir="rtl">
        <thead>
          <tr className="bg-slate-50 border-b border-slate-200">
            <th className="text-right py-3 px-4 font-semibold text-slate-600 w-8">#</th>
            <th className="text-right py-3 px-4 font-semibold text-slate-600">بيانات العميل</th>
            <th className="text-right py-3 px-4 font-semibold text-slate-600">التواريخ / الإجراءات</th>
            <th className="text-right py-3 px-4 font-semibold text-slate-600">الأصناف / المبالغ / الحالة</th>
            <th className="text-right py-3 px-4 font-semibold text-slate-600">الموظف</th>
          </tr>
        </thead>
        <tbody>
          {isPending ? (
            <tr>
              <td colSpan={5} className="py-16 text-center text-slate-400">
                جاري التحميل...
              </td>
            </tr>
          ) : (
            invoices.map((inv) => {
              const { currency_symbol } = getOrderCurrencyInfo(inv);
              const totalPrice = Number(inv.total_price ?? 0);
              const paidNum = Number(inv.paid ?? 0);
              const remNum = Number(inv.remaining ?? 0);
              const discountVal = Number(inv.discount_value ?? 0);
              const uiPay = getRentalUiPayment(inv);
              const uiStat = getRentalUiStatus(inv);
              const itemsText =
                inv.items && inv.items.length > 0
                  ? inv.items
                      .map((item) => getItemListDisplay(item as Record<string, unknown>))
                      .join("، ")
                  : "-";

              return (
                <tr
                  key={inv.id}
                  className="border-b border-slate-100 hover:bg-slate-50/50 transition-colors align-top"
                >
                  <td className="py-3 px-4">
                    <button
                      type="button"
                      onClick={() => onView(inv)}
                      className="font-mono text-slate-500 text-xs hover:text-slate-800 underline-offset-2 hover:underline"
                    >
                      <span dir="ltr" className="tabular-nums">
                        #{toEnglishNumerals(inv.id)}
                      </span>
                    </button>
                  </td>

                  <td className="py-3 px-4 min-w-[220px]">
                    <div className="space-y-1">
                      <div className="font-semibold text-slate-800 text-sm">
                        {inv.client?.name ?? "-"}
                      </div>
                      <div className="text-xs text-slate-500">
                        <span className="text-slate-400">الرقم القومي:</span>{" "}
                        <span className="font-mono" dir="ltr">
                          {toEnglishNumerals(inv.client?.national_id) || "-"}
                        </span>
                      </div>
                      <div className="text-xs text-slate-500">
                        <span className="text-slate-400">الهاتف:</span>{" "}
                        <span dir="ltr">
                          {inv.client?.phones && inv.client.phones.length > 0
                            ? formatPhone(inv.client.phones[0]?.phone, "-")
                            : "-"}
                        </span>
                      </div>
                      <div className="text-xs text-slate-500">
                        <span className="text-slate-400">هاتف الواتساب:</span>{" "}
                        <span dir="ltr">
                          {inv.client?.phones && inv.client.phones.length > 1
                            ? formatPhone(inv.client.phones[1]?.phone, "-")
                            : "-"}
                        </span>
                      </div>
                      <div className="text-xs text-slate-500">
                        <span className="text-slate-400">العنوان:</span>{" "}
                        {inv.client?.address
                          ? `${inv.client.address.country_name ?? ""}${
                              inv.client.address.city_name
                                ? ` - ${inv.client.address.city_name}`
                                : ""
                            }${
                              inv.client.address.street
                                ? ` - ${inv.client.address.street}`
                                : ""
                            }${
                              inv.client.address.building
                                ? ` - ${inv.client.address.building}`
                                : ""
                            }`
                          : "-"}
                      </div>
                    </div>
                  </td>

                  <td className="py-3 px-4 min-w-[200px]">
                    <div className="space-y-1 mb-3">
                      <div className="text-xs text-slate-500">
                        <span className="text-slate-400">تاريخ الفاتورة:</span>{" "}
                        <span className="font-medium text-slate-700" dir="ltr">
                          {toEnglishNumerals(formatDate(inv.created_at)) || "-"}
                        </span>
                      </div>
                      <div className="text-xs text-slate-500">
                        <span className="text-slate-400">استلام:</span>{" "}
                        <span className="font-medium text-slate-700" dir="ltr">
                          {inv.delivery_date
                            ? toEnglishNumerals(formatDate(inv.delivery_date))
                            : "-"}
                        </span>
                      </div>
                      <div className="text-xs text-slate-500">
                        <span className="text-slate-400">الفرح:</span>{" "}
                        <span className="font-medium text-slate-700" dir="ltr">
                          {inv.occasion_datetime
                            ? toEnglishNumerals(formatDate(inv.occasion_datetime))
                            : "-"}
                        </span>
                      </div>
                      <div className="text-xs text-slate-500">
                        <span className="text-slate-400">استرجاع:</span>{" "}
                        <span className="font-medium text-slate-700" dir="ltr">
                          {inv.visit_datetime
                            ? toEnglishNumerals(formatDate(inv.visit_datetime))
                            : "-"}
                        </span>
                      </div>
                    </div>
                    <div className="flex flex-wrap gap-1">
                      {canPrint(inv) && (
                        <>
                          <button
                            type="button"
                            onClick={() => onPrint(inv)}
                            className="w-7 h-7 flex items-center justify-center rounded border border-slate-200 text-slate-500 hover:bg-slate-100 hover:text-slate-700 transition-colors cursor-pointer"
                            title="طباعة"
                          >
                            <i className="ri-printer-line text-sm" />
                          </button>
                          <button
                            type="button"
                            onClick={() => onPrintClientCopy(inv)}
                            className="w-7 h-7 flex items-center justify-center rounded border border-slate-200 text-slate-500 hover:bg-slate-100 hover:text-slate-700 transition-colors cursor-pointer"
                            title="نسخة PDF"
                          >
                            <i className="ri-file-pdf-line text-sm" />
                          </button>
                        </>
                      )}
                      {canEdit(inv) && (
                        <button
                          type="button"
                          onClick={() => onEdit(inv)}
                          className="w-7 h-7 flex items-center justify-center rounded border border-slate-200 text-slate-500 hover:bg-sky-50 hover:text-sky-600 transition-colors cursor-pointer"
                          title="تعديل"
                        >
                          <i className="ri-edit-line text-sm" />
                        </button>
                      )}
                      <button
                        type="button"
                        onClick={() => onView(inv)}
                        className="w-7 h-7 flex items-center justify-center rounded border border-slate-200 text-slate-500 hover:bg-slate-100 hover:text-slate-700 transition-colors cursor-pointer"
                        title="عرض التفاصيل"
                      >
                        <i className="ri-eye-line text-sm" />
                      </button>
                      {canCustody(inv) && (
                        <button
                          type="button"
                          onClick={() => onCustody(inv)}
                          className="w-7 h-7 flex items-center justify-center rounded border border-slate-200 text-slate-500 hover:bg-indigo-50 hover:text-indigo-600 transition-colors cursor-pointer"
                          title="تعهد"
                        >
                          <i className="ri-shield-check-line text-sm" />
                        </button>
                      )}
                      {canAddPayment(inv) && (
                        <button
                          type="button"
                          onClick={() => onPayment(inv)}
                          className="w-7 h-7 flex items-center justify-center rounded border border-slate-200 text-slate-500 hover:bg-emerald-50 hover:text-emerald-600 transition-colors cursor-pointer"
                          title="تسديد"
                        >
                          <i className="ri-bank-card-line text-sm" />
                        </button>
                      )}
                      {canDeliver(inv) && (
                        <button
                          type="button"
                          onClick={() => onDeliver(inv)}
                          disabled={isDelivering}
                          className="w-7 h-7 flex items-center justify-center rounded border border-slate-200 text-slate-500 hover:bg-teal-50 hover:text-teal-600 transition-colors cursor-pointer disabled:opacity-50"
                          title="تأكيد الإرجاع"
                        >
                          <i className="ri-checkbox-circle-line text-sm" />
                        </button>
                      )}
                      {canPrint(inv) && (
                        <button
                          type="button"
                          onClick={() => onPrintAck(inv)}
                          className="w-7 h-7 flex items-center justify-center rounded border border-slate-200 text-slate-500 hover:bg-slate-100 hover:text-slate-700 transition-colors cursor-pointer"
                          title="إيصال"
                        >
                          <i className="ri-receipt-line text-sm" />
                        </button>
                      )}
                      {canCancel(inv) && (
                        <button
                          type="button"
                          onClick={() => onCancel(inv)}
                          className="w-7 h-7 flex items-center justify-center rounded border border-red-100 text-red-400 hover:bg-red-50 hover:text-red-600 transition-colors cursor-pointer"
                          title="إلغاء"
                        >
                          <i className="ri-close-circle-line text-sm" />
                        </button>
                      )}
                      {canDelete(inv) && (
                        <button
                          type="button"
                          onClick={() => onDelete(inv)}
                          className="w-7 h-7 flex items-center justify-center rounded border border-slate-200 text-slate-500 hover:bg-red-50 hover:text-red-600 transition-colors cursor-pointer"
                          title="حذف"
                        >
                          <i className="ri-delete-bin-line text-sm" />
                        </button>
                      )}
                    </div>
                  </td>

                  <td className="py-3 px-4 min-w-[200px]">
                    <div className="space-y-1">
                      <div className="text-xs text-slate-500">
                        <span className="text-slate-400">الأصناف:</span>{" "}
                        <span className="text-slate-700">({itemsText})</span>
                      </div>
                      <div className="text-xs">
                        <span className="text-slate-400">السعر</span>{" "}
                        <span className="text-slate-500">(شامل الضريبة):</span>{" "}
                        <span className="font-medium text-slate-700" dir="ltr">
                          {totalPrice.toLocaleString("ar-EG")} {currency_symbol}
                        </span>
                      </div>
                      <div className="text-xs text-emerald-700">
                        المدفوع:{" "}
                        <span className="font-semibold" dir="ltr">
                          {paidNum.toLocaleString("ar-EG")} {currency_symbol}
                        </span>
                      </div>
                      <div className="text-xs text-rose-600">
                        المتبقي:{" "}
                        <span className="font-semibold" dir="ltr">
                          {remNum.toLocaleString("ar-EG")} {currency_symbol}
                        </span>
                      </div>
                      <div className="flex items-center gap-2 mt-2 flex-wrap">
                        <span
                          className={`text-xs px-2 py-0.5 rounded-full font-medium ${paymentStatusColors[uiPay]}`}
                        >
                          {uiPay} (إيجار)
                        </span>
                        <span
                          className={`text-xs px-2 py-0.5 rounded-full font-medium ${rentalStatusColors[uiStat]}`}
                        >
                          {uiStat}
                        </span>
                      </div>
                      {discountVal > 0 && (
                        <div className="text-xs text-amber-600">
                          خصم: {discountVal.toLocaleString("ar-EG")} {currency_symbol}
                          {inv.discount_type ? (
                            <span className="text-slate-400 mr-1">({inv.discount_type})</span>
                          ) : null}
                        </div>
                      )}
                    </div>
                  </td>

                  <td className="py-3 px-4">
                    <OrderEmployeeName
                      order={inv}
                      className="text-sm text-slate-700 font-medium whitespace-nowrap"
                    />
                    <div className="text-xs text-slate-400 mt-0.5">
                      {inv.branch?.name ?? "—"}
                    </div>
                  </td>
                </tr>
              );
            })
          )}

          {!isPending && invoices.length === 0 && (
            <tr>
              <td colSpan={5} className="py-16 text-center text-slate-400">
                <i className="ri-file-search-line text-4xl mb-2 block" />
                لا توجد فواتير مطابقة للبحث
              </td>
            </tr>
          )}
        </tbody>
      </table>
    </div>
  );
}
