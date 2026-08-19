import type { SupplierOrderVM, SupplierOrderStatusAr } from "../types";

interface Props {
  entries: SupplierOrderVM[];
  onStatusChange: (order: SupplierOrderVM, status: SupplierOrderStatusAr) => void;
  statusChangeDisabled?: (order: SupplierOrderVM) => boolean;
  onPayment: (order: SupplierOrderVM) => void;
  onEdit: (order: SupplierOrderVM) => void;
  onView: (order: SupplierOrderVM) => void;
  onReceive?: (order: SupplierOrderVM) => void;
  onDelete?: (order: SupplierOrderVM) => void;
  canAddPayment?: (order: SupplierOrderVM) => boolean;
}

const fmt = (n: number) =>
  new Intl.NumberFormat("ar-EG", { minimumFractionDigits: 2 }).format(n);

const statusColors: Record<string, string> = {
  "قيد الانتظار": "bg-amber-100 text-amber-700",
  "مُوَّرد": "bg-blue-100 text-blue-700",
  مستلم: "bg-green-100 text-green-700",
  ملغي: "bg-red-100 text-red-500",
};

const STATUS_OPTIONS: SupplierOrderStatusAr[] = [
  "قيد الانتظار",
  "مُوَّرد",
  "مستلم",
  "ملغي",
];

function todayYmd(): string {
  return new Date().toISOString().slice(0, 10);
}

export default function SupOrderTable({
  entries,
  onStatusChange,
  statusChangeDisabled,
  onPayment,
  onEdit,
  onView,
  onReceive,
  onDelete,
  canAddPayment,
}: Props) {
  const today = todayYmd();

  if (entries.length === 0) {
    return (
      <div className="bg-white rounded-xl border border-blue-100 p-16 text-center">
        <div className="w-16 h-16 flex items-center justify-center mx-auto rounded-full bg-blue-50 text-blue-200 mb-4">
          <i className="ri-inbox-line text-3xl" />
        </div>
        <p className="text-gray-400 text-sm">لا توجد طلبيات تطابق الفلاتر</p>
      </div>
    );
  }

  return (
    <div className="bg-white rounded-xl border border-blue-100 overflow-hidden">
      <div
        className="grid bg-blue-900 text-white text-xs font-medium"
        style={{
          gridTemplateColumns: "50px 1.2fr 1.6fr 1fr 240px 120px 100px",
        }}
      >
        <div className="px-4 py-3 text-center">#</div>
        <div className="px-4 py-3">بيانات الطلبية</div>
        <div className="px-4 py-3">المنتجات المطلوبة</div>
        <div className="px-4 py-3 text-center">المبالغ</div>
        <div className="px-4 py-3 text-center">التواريخ</div>
        <div className="px-4 py-3 text-center">الحالة</div>
        <div className="px-4 py-3 text-center">إجراءات</div>
      </div>

      <div className="divide-y divide-gray-50">
        {entries.map((order) => {
          const remaining = order.totalAmount - order.paidAmount;
          const isOverdue =
            !order.receivedDate &&
            order.expectedDate < today &&
            order.status !== "ملغي";
          const statusLocked = statusChangeDisabled?.(order) ?? false;
          const showPay = canAddPayment?.(order) ?? remaining > 0;

          return (
            <div
              key={order.id}
              className={`grid hover:bg-blue-50/20 transition-colors group ${isOverdue ? "bg-amber-50/30 border-r-2 border-amber-400" : ""}`}
              style={{
                gridTemplateColumns: "50px 1.2fr 1.6fr 1fr 240px 120px 100px",
              }}
            >
              <div className="px-4 py-4 text-center">
                <span className="font-bold text-blue-600 text-sm">
                  #{order.id}
                </span>
                {isOverdue && (
                  <div className="mt-1">
                    <span className="text-xs bg-amber-100 text-amber-600 px-1 py-0.5 rounded-full">
                      متأخر
                    </span>
                  </div>
                )}
              </div>

              <div className="px-4 py-4 space-y-1.5">
                <p className="font-mono text-sm font-bold text-blue-600">
                  {order.orderRef}
                </p>
                <p className="text-xs font-semibold text-gray-700">
                  <span className="text-gray-400 ml-1">المورد:</span>
                  {order.supplierName}
                </p>
                <p className="text-xs text-gray-500">
                  <span className="text-gray-400 ml-1">الكود:</span>
                  <span className="font-mono text-blue-500">
                    {order.supplierCode}
                  </span>
                </p>
                <p className="text-xs text-gray-400">
                  <span className="ml-1">الفرع:</span>
                  {order.branch}
                </p>
                <p className="text-xs text-gray-400">
                  <span className="ml-1">الموظف:</span>
                  {order.employee}
                </p>
              </div>

              <div className="px-4 py-4 border-x border-gray-100">
                <div className="space-y-2">
                  {order.items.map((item, i) => (
                    <div key={i} className="text-xs">
                      <div className="flex items-start justify-between gap-2">
                        <span className="text-gray-700 font-medium flex-1">
                          {item.name}
                        </span>
                        <span className="text-blue-600 font-semibold whitespace-nowrap">
                          {fmt(item.total)} ج.م
                        </span>
                      </div>
                      <div className="flex items-center gap-2 text-gray-400 mt-0.5">
                        <span>
                          {item.quantity} {item.unit}
                        </span>
                        <span>×</span>
                        <span>
                          {fmt(item.unitPrice)} ج.م/{item.unit}
                        </span>
                      </div>
                    </div>
                  ))}
                </div>
                {order.notes && (
                  <p className="text-xs text-amber-500 mt-2 italic border-t border-gray-100 pt-2">
                    <i className="ri-sticky-note-line ml-1" />
                    {order.notes}
                  </p>
                )}
              </div>

              <div className="px-4 py-4 border-l border-gray-100 flex flex-col justify-center gap-1.5">
                <div className="flex items-center justify-between text-xs">
                  <span className="text-gray-400">الإجمالي:</span>
                  <span className="font-bold text-gray-800">
                    {fmt(order.totalAmount)} ج.م
                  </span>
                </div>
                <div className="flex items-center justify-between text-xs">
                  <span className="text-gray-400">المدفوع:</span>
                  <span className="font-semibold text-green-600">
                    {fmt(order.paidAmount)} ج.م
                  </span>
                </div>
                <div className="flex items-center justify-between text-xs border-t border-gray-100 pt-1">
                  <span className="text-gray-400">المتبقي:</span>
                  <span
                    className={`font-bold ${remaining > 0 ? "text-red-500" : "text-gray-400"}`}
                  >
                    {fmt(remaining)} ج.م
                  </span>
                </div>
                {order.totalAmount > 0 && (
                  <div className="mt-1">
                    <div className="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                      <div
                        className="h-full bg-green-400 rounded-full"
                        style={{
                          width: `${Math.min(100, (order.paidAmount / order.totalAmount) * 100)}%`,
                        }}
                      />
                    </div>
                    <p className="text-xs text-gray-400 mt-0.5 text-left">
                      {Math.round((order.paidAmount / order.totalAmount) * 100)}%
                    </p>
                  </div>
                )}
              </div>

              <div className="px-4 py-4 border-l border-gray-100 flex flex-col justify-center gap-1.5">
                <div className="flex items-center justify-between text-xs">
                  <span className="text-gray-400">تاريخ الطلب:</span>
                  <span className="text-gray-600 font-mono text-xs">
                    {order.orderDate.replace(/-/g, "/")}
                  </span>
                </div>
                <div className="flex items-center justify-between text-xs">
                  <span className="text-gray-400">التسليم المتوقع:</span>
                  <span
                    className={`font-mono text-xs ${isOverdue ? "text-amber-600 font-bold" : "text-blue-600"}`}
                  >
                    {order.expectedDate.replace(/-/g, "/")}
                  </span>
                </div>
                {order.receivedDate && (
                  <div className="flex items-center justify-between text-xs">
                    <span className="text-gray-400">تاريخ الاستلام:</span>
                    <span className="text-green-600 font-mono text-xs">
                      {order.receivedDate.replace(/-/g, "/")}
                    </span>
                  </div>
                )}
              </div>

              <div className="px-4 py-4 border-l border-gray-100 flex items-center justify-center">
                {statusLocked ? (
                  <span
                    className={`text-xs px-2 py-1.5 rounded-lg font-semibold text-center ${statusColors[order.status]}`}
                    title="لا تتوفر قطع في القائمة — استخدم تعديل الطلبية لتحديث الحالة"
                  >
                    {order.status}
                  </span>
                ) : (
                  <select
                    value={order.status}
                    onChange={(e) =>
                      onStatusChange(
                        order,
                        e.target.value as SupplierOrderStatusAr,
                      )
                    }
                    className={`text-xs px-2 py-1.5 rounded-lg border-0 cursor-pointer font-semibold focus:outline-none text-center ${statusColors[order.status]}`}
                  >
                    {STATUS_OPTIONS.map((s) => (
                      <option key={s} value={s}>
                        {s}
                      </option>
                    ))}
                  </select>
                )}
              </div>

              <div className="px-4 py-4 border-l border-gray-100 flex flex-col items-center justify-center gap-2">
                {onReceive && !order.receivedDate && (
                  <button
                    type="button"
                    data-onboarding-target={
                      entries.find((row) => !row.receivedDate)?.id === order.id
                        ? "purchase_order_received"
                        : undefined
                    }
                    title="استلام الطلبية"
                    onClick={() => onReceive(order)}
                    className="w-8 h-8 flex items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-100 cursor-pointer"
                  >
                    <i className="ri-inbox-unarchive-line text-sm" />
                  </button>
                )}
                {showPay && (
                  <button
                    type="button"
                    title="تسجيل دفعة"
                    onClick={() => onPayment(order)}
                    className="w-8 h-8 flex items-center justify-center rounded-lg bg-green-50 text-green-600 hover:bg-green-100 cursor-pointer"
                  >
                    <i className="ri-bank-card-line text-sm" />
                  </button>
                )}
                <button
                  type="button"
                  title="تعديل"
                  onClick={() => onEdit(order)}
                  className="w-8 h-8 flex items-center justify-center rounded-lg bg-amber-50 text-amber-500 hover:bg-amber-100 cursor-pointer"
                >
                  <i className="ri-edit-line text-sm" />
                </button>
                <button
                  type="button"
                  title="عرض حساب المورد"
                  onClick={() => onView(order)}
                  className="w-8 h-8 flex items-center justify-center rounded-lg bg-blue-50 text-blue-500 hover:bg-blue-100 cursor-pointer"
                >
                  <i className="ri-eye-line text-sm" />
                </button>
                {onDelete && (
                  <button
                    type="button"
                    title="حذف"
                    onClick={() => onDelete(order)}
                    className="w-8 h-8 flex items-center justify-center rounded-lg bg-red-50 text-red-400 hover:bg-red-100 cursor-pointer"
                  >
                    <i className="ri-delete-bin-line text-sm" />
                  </button>
                )}
              </div>
            </div>
          );
        })}
      </div>

      <div className="bg-blue-900 text-white px-5 py-3 flex items-center justify-between text-xs flex-wrap gap-2">
        <span className="text-blue-200">{entries.length} طلبية</span>
        <div className="flex flex-wrap gap-4 sm:gap-6">
          <span className="text-blue-200">
            الإجمالي:{" "}
            <strong className="text-white">
              {fmt(entries.reduce((s, o) => s + o.totalAmount, 0))} ج.م
            </strong>
          </span>
          <span className="text-green-300">
            المدفوع:{" "}
            <strong>
              {fmt(entries.reduce((s, o) => s + o.paidAmount, 0))} ج.م
            </strong>
          </span>
          <span className="text-red-300">
            المتبقي:{" "}
            <strong>
              {fmt(
                entries.reduce(
                  (s, o) => s + (o.totalAmount - o.paidAmount),
                  0,
                ),
              )}{" "}
              ج.م
            </strong>
          </span>
        </div>
      </div>
    </div>
  );
}
