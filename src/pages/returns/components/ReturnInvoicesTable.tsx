import type { TOrder } from "@/api/v2/orders/orders.types";
import { fmtCurrency } from "@/utils/formatDate";
import type {
  ReturnInvoiceTableRow,
  ReturnDeliveryStatus,
} from "./returnInvoiceProject.types";

interface Props {
  rows: ReturnInvoiceTableRow[];
  onNavigateOrder: (id: number) => void;
  onViewOrder: (order: TOrder) => void;
  onReturnOrder: (order: TOrder) => void;
  canReturn: (order: TOrder) => boolean;
  onStatusChange?: (id: number, status: ReturnDeliveryStatus) => void;
  lateOnly?: boolean;
}

const paymentColors: Record<string, string> = {
  مدفوع: "bg-green-500 text-white",
  "مدفوع جزئياً": "bg-amber-400 text-white",
  "غير مدفوع": "bg-red-100 text-red-600",
};

const returnStatusColors: Record<string, string> = {
  "في الانتظار": "bg-blue-100 text-blue-600",
  "تم الاسترجاع": "bg-green-100 text-green-600",
  متأخر: "bg-red-100 text-red-500",
  مرفوض: "bg-gray-100 text-gray-500",
};

const returnTypeColors: Record<string, string> = {
  فوري: "bg-green-50 text-green-600 border-green-200",
  متأخر: "bg-red-50 text-red-500 border-red-200",
  مجدول: "bg-blue-50 text-blue-600 border-blue-200",
};

const returnTypeIcons: Record<string, string> = {
  فوري: "ri-flashlight-line",
  متأخر: "ri-alarm-warning-line",
  مجدول: "ri-calendar-check-line",
};

const conditionColors: Record<string, string> = {
  ممتازة: "text-green-600",
  جيدة: "text-blue-600",
  مقبولة: "text-amber-500",
  تالفة: "text-red-500",
};

const itemTypeColors: Record<string, string> = {
  إيجار: "bg-blue-50 text-blue-600",
  بيع: "bg-green-50 text-green-600",
  تفصيل: "bg-purple-50 text-purple-600",
};

const allStatuses: ReturnDeliveryStatus[] = [
  "في الانتظار",
  "تم الاسترجاع",
  "متأخر",
  "مرفوض",
];

export default function ReturnInvoicesTable({
  rows,
  onNavigateOrder,
  onViewOrder,
  onReturnOrder,
  canReturn,
  onStatusChange,
  lateOnly,
}: Props) {
  const totalPenalties = rows.reduce(
    (s, r) => s + r.invoice.penalty.totalPenalty,
    0,
  );

  if (rows.length === 0) {
    return (
      <div className="bg-white rounded-xl border border-blue-100 p-16 text-center">
        <div className="w-16 h-16 flex items-center justify-center mx-auto rounded-full bg-green-50 text-green-200 mb-4">
          <i className="ri-checkbox-circle-line text-3xl" />
        </div>
        <p className="text-gray-400 text-sm">
          {lateOnly
            ? "لا توجد إرجاعات متأخرة حالياً"
            : "لا توجد إرجاعات تطابق الفلاتر"}
        </p>
      </div>
    );
  }

  return (
    <div className="bg-white rounded-xl border border-blue-100 overflow-hidden">
      {/* Header */}
      <div
        className="grid bg-blue-900 text-white text-xs font-medium"
        style={{
          gridTemplateColumns: "50px 1fr 270px 1fr 220px 110px",
        }}
      >
        <div className="px-4 py-3 text-center">#</div>
        <div className="px-4 py-3">بيانات العميل</div>
        <div className="px-4 py-3 text-center">التواريخ / الإجراءات</div>
        <div className="px-4 py-3 text-center">الأصناف / المبلغ / الحالة</div>
        <div className="px-4 py-3 text-center">الإرجاع والغرامة</div>
        <div className="px-4 py-3 text-center">الموظف</div>
      </div>

      {/* Rows */}
      <div className="divide-y divide-gray-100">
        {rows.map(({ invoice: inv, order }) => {
          const isLate = inv.deliveryStatus === "متأخر";
          const isReturned = inv.deliveryStatus === "تم الاسترجاع";
          const hasLateReturn =
            inv.returnType === "متأخر" &&
            inv.deliveryStatus === "تم الاسترجاع";
          const hasPenalty = inv.penalty.totalPenalty > 0;

          return (
            <div
              key={inv.id}
              className={`grid hover:bg-blue-50/20 transition-colors group ${isLate ? "bg-red-50/20 border-r-2 border-red-400" : hasLateReturn ? "bg-orange-50/20 border-r-2 border-orange-300" : ""}`}
              style={{
                gridTemplateColumns: "50px 1fr 270px 1fr 220px 110px",
              }}
            >
              {/* # */}
              <div className="px-3 py-4 text-center">
                <button
                  type="button"
                  onClick={() => onNavigateOrder(inv.id)}
                  className="font-bold text-blue-600 text-sm hover:underline"
                >
                  #{inv.id}
                </button>
                {isLate && (
                  <div className="mt-1">
                    <span className="text-xs bg-red-100 text-red-500 px-1.5 py-0.5 rounded-full">
                      متأخر
                    </span>
                  </div>
                )}
                {hasLateReturn && (
                  <div className="mt-1">
                    <span className="text-xs bg-orange-100 text-orange-500 px-1.5 py-0.5 rounded-full">
                      عاد متأخراً
                    </span>
                  </div>
                )}
              </div>

              {/* Customer */}
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
                  <span className="text-gray-400 ml-1">واتساب:</span>
                  <span dir="ltr" className="inline-block">
                    {inv.customer.whatsapp}
                  </span>
                </p>
                <p className="text-xs text-gray-400 leading-relaxed">
                  <span className="ml-1">العنوان:</span>
                  {inv.customer.address}
                </p>
              </div>

              {/* Dates + Actions */}
              <div className="px-3 py-4 border-x border-gray-100">
                <div className="space-y-1.5 mb-3">
                  <div className="flex items-center justify-between text-xs">
                    <span className="text-gray-400">تاريخ الفاتورة:</span>
                    <span className="font-mono text-gray-600 text-xs">
                      {inv.dates.invoiceDate.replace(/-/g, "/")}
                    </span>
                  </div>
                  <div className="flex items-center justify-between text-xs">
                    <span className="text-gray-400">موعد الفرح:</span>
                    <span className="font-mono text-blue-600 text-xs">
                      {inv.dates.eventDate
                        ? inv.dates.eventDate.replace(/-/g, "/")
                        : "—"}
                    </span>
                  </div>
                  <div className="flex items-center justify-between text-xs">
                    <span className="text-gray-400">موعد الاسترجاع:</span>
                    <span
                      className={`font-mono text-xs font-semibold ${isLate ? "text-red-600" : "text-purple-600"}`}
                    >
                      {inv.dates.returnDate
                        ? inv.dates.returnDate.replace(/-/g, "/")
                        : "—"}
                    </span>
                  </div>
                  {inv.dates.actualReturnDate && (
                    <div className="flex items-center justify-between text-xs">
                      <span className="text-gray-400">
                        الاسترجاع الفعلي:
                      </span>
                      <span
                        className={`font-mono text-xs font-semibold ${inv.penalty.delayDays > 0 ? "text-orange-500" : "text-green-600"}`}
                      >
                        {inv.dates.actualReturnDate.replace(/-/g, "/")}
                      </span>
                    </div>
                  )}
                </div>

                {/* Actions */}
                <div className="flex flex-wrap gap-1.5 justify-center">
                  {canReturn(order) && (
                    <button
                      type="button"
                      title="تأكيد الاسترجاع"
                      onClick={() => onReturnOrder(order)}
                      className="w-7 h-7 flex items-center justify-center rounded-md bg-green-50 text-green-600 hover:bg-green-100 cursor-pointer"
                    >
                      <i className="ri-checkbox-circle-line text-sm" />
                    </button>
                  )}
                  <button
                    type="button"
                    title="عرض"
                    onClick={() => onViewOrder(order)}
                    className="w-7 h-7 flex items-center justify-center rounded-md bg-gray-100 text-gray-500 hover:bg-gray-200 cursor-pointer"
                  >
                    <i className="ri-eye-line text-sm" />
                  </button>
                  <button
                    type="button"
                    title="فتح صفحة الطلب"
                    onClick={() => onNavigateOrder(inv.id)}
                    className="w-7 h-7 flex items-center justify-center rounded-md bg-blue-50 text-blue-500 hover:bg-blue-100 cursor-pointer"
                  >
                    <i className="ri-external-link-line text-sm" />
                  </button>
                  <div className="w-full mt-1">
                    {onStatusChange ? (
                      <select
                        value={inv.deliveryStatus}
                        onChange={(e) =>
                          onStatusChange(
                            inv.id,
                            e.target.value as ReturnDeliveryStatus,
                          )
                        }
                        className={`w-full text-xs px-2 py-1 rounded-md border-0 cursor-pointer font-medium focus:outline-none ${returnStatusColors[inv.deliveryStatus] ?? "bg-gray-100 text-gray-600"}`}
                      >
                        {allStatuses.map((s) => (
                          <option key={s} value={s}>
                            {s}
                          </option>
                        ))}
                      </select>
                    ) : (
                      <span
                        className={`w-full block text-center text-xs px-2 py-1 rounded-md font-medium ${returnStatusColors[inv.deliveryStatus] ?? "bg-gray-100 text-gray-600"}`}
                      >
                        {inv.deliveryStatus}
                      </span>
                    )}
                  </div>
                </div>
              </div>

              {/* Items / Amount */}
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
                        <span className="text-xs text-gray-700">
                          {item.name}
                        </span>
                      </div>
                    ))}
                  </div>
                </div>
                <div className="space-y-1 border-t border-gray-100 pt-2">
                  <div className="flex items-center justify-between text-xs">
                    <span className="text-gray-400">السعر:</span>
                    <span className="text-gray-700 font-medium">
                      {fmtCurrency(inv.pricing.total)} ج.م
                    </span>
                  </div>
                  <div className="flex items-center justify-between text-xs">
                    <span className="text-gray-400">المدفوع:</span>
                    <span className="text-green-600 font-semibold">
                      {fmtCurrency(inv.pricing.paid)} ج.م
                    </span>
                  </div>
                  {inv.pricing.remaining > 0 && (
                    <div className="flex items-center justify-between text-xs">
                      <span className="text-gray-400">المتبقي:</span>
                      <span className="text-red-500 font-semibold">
                        {fmtCurrency(inv.pricing.remaining)} ج.م
                      </span>
                    </div>
                  )}
                </div>
                <div className="mt-2">
                  <span
                    className={`text-xs font-semibold px-3 py-1 rounded-full ${paymentColors[inv.paymentStatus] ?? "bg-gray-100 text-gray-600"}`}
                  >
                    {inv.paymentStatus}
                  </span>
                </div>
                {inv.notes && (
                  <p className="text-xs text-gray-400 mt-2 italic truncate">
                    {inv.notes}
                  </p>
                )}
              </div>

              {/* Return & Penalty Column */}
              <div className="px-4 py-4 border-l border-gray-100">
                {/* Return Type */}
                <div className="flex items-center gap-2 mb-3">
                  <span
                    className={`text-xs font-bold px-2.5 py-1 rounded-full border ${returnTypeColors[inv.returnType] ?? "bg-gray-50 text-gray-600 border-gray-200"}`}
                  >
                    <i
                      className={`ml-1 ${returnTypeIcons[inv.returnType] ?? ""}`}
                    />
                    {inv.returnType}
                  </span>
                </div>

                {/* Late Days */}
                {(isLate || inv.penalty.delayDays > 0) && (
                  <div
                    className={`rounded-lg px-3 py-2 mb-3 ${isLate ? "bg-red-50 border border-red-100" : "bg-orange-50 border border-orange-100"}`}
                  >
                    <div className="flex items-center justify-between text-xs mb-1">
                      <span
                        className={
                          isLate ? "text-red-500" : "text-orange-500"
                        }
                      >
                        أيام التأخير:
                      </span>
                      <span
                        className={`font-bold text-base ${isLate ? "text-red-600" : "text-orange-600"}`}
                      >
                        {inv.penalty.delayDays}
                      </span>
                    </div>
                    <div className="flex items-center justify-between text-xs">
                      <span
                        className={
                          isLate ? "text-red-400" : "text-orange-400"
                        }
                      >
                        غرامة/يوم:
                      </span>
                      <span
                        className={`font-medium ${isLate ? "text-red-500" : "text-orange-500"}`}
                      >
                        {fmtCurrency(inv.penalty.penaltyPerDay)} ج.م
                      </span>
                    </div>
                  </div>
                )}

                {/* Penalty Amount */}
                {hasPenalty ? (
                  <div
                    className={`rounded-lg px-3 py-2 mb-2 ${isLate ? "bg-red-50" : "bg-orange-50"}`}
                  >
                    <p
                      className={`text-xs mb-0.5 ${isLate ? "text-red-400" : "text-orange-400"}`}
                    >
                      إجمالي الغرامة
                    </p>
                    <p
                      className={`text-lg font-bold ${isLate ? "text-red-600" : "text-orange-600"}`}
                    >
                      {fmtCurrency(inv.penalty.totalPenalty)}{" "}
                      <span className="text-xs font-normal">ج.م</span>
                    </p>
                    {isReturned && (
                      <span className="text-xs bg-green-100 text-green-600 px-2 py-0.5 rounded-full mt-1 inline-block">
                        <i className="ri-check-line ml-1" />
                        محصّلة
                      </span>
                    )}
                  </div>
                ) : (
                  <div className="rounded-lg px-3 py-2 mb-2 bg-green-50 border border-green-100">
                    <p className="text-xs text-green-500">لا توجد غرامة</p>
                    <p className="text-sm font-bold text-green-600">
                      غرامة/يوم: {fmtCurrency(inv.penalty.penaltyPerDay)} ج.م
                    </p>
                  </div>
                )}

                {/* Condition */}
                {inv.dates.actualReturnDate &&
                  inv.penalty.productCondition && (
                    <div className="text-xs flex items-center gap-1.5">
                      <i className="ri-t-shirt-2-line text-gray-400" />
                      <span className="text-gray-400">حالة المنتج:</span>
                      <span
                        className={`font-semibold ${conditionColors[inv.penalty.productCondition] ?? "text-gray-600"}`}
                      >
                        {inv.penalty.productCondition}
                      </span>
                    </div>
                  )}

                {inv.penalty.reason && (
                  <p className="text-xs text-gray-400 mt-1.5 italic">
                    {inv.penalty.reason}
                  </p>
                )}
              </div>

              {/* Employee */}
              <div className="px-3 py-4 text-center">
                <div className="flex flex-col items-center gap-2">
                  <div className="w-9 h-9 flex items-center justify-center rounded-full bg-blue-100 text-blue-600 font-bold text-sm">
                    {inv.employee && inv.employee !== "-"
                      ? inv.employee.charAt(0)
                      : "؟"}
                  </div>
                  <span className="text-xs text-gray-600 font-medium leading-tight">
                    {inv.employee}
                  </span>
                  <span className="text-xs text-gray-400">{inv.branch}</span>
                </div>
              </div>
            </div>
          );
        })}
      </div>

      {/* Footer */}
      <div className="bg-blue-900 text-white px-5 py-3 flex items-center justify-between text-sm">
        <span className="text-blue-200 text-xs">{rows.length} إرجاع</span>
        <div className="flex items-center gap-6 text-xs">
          <span className="text-blue-200">
            إجمالي الإيرادات:{" "}
            <strong className="text-white">
              {fmtCurrency(rows.reduce((s, r) => s + r.invoice.pricing.total, 0))} ج.م
            </strong>
          </span>
          {totalPenalties > 0 && (
            <span className="text-orange-300">
              إجمالي الغرامات:{" "}
              <strong>{fmtCurrency(totalPenalties)} ج.م</strong>
            </span>
          )}
        </div>
      </div>
    </div>
  );
}
