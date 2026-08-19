import type { SupplierOrderVM } from "../types";

interface Props {
  entries: SupplierOrderVM[];
  all: SupplierOrderVM[];
}

const fmt = (n: number) =>
  new Intl.NumberFormat("ar-EG", { minimumFractionDigits: 2 }).format(n);

export default function SupOrderStats({ entries, all }: Props) {
  const received = all.filter((o) => o.status === "مستلم");
  const pending = all.filter((o) => o.status === "قيد الانتظار");
  const ordered = all.filter((o) => o.status === "مُوَّرد");
  const cancelled = all.filter((o) => o.status === "ملغي");
  const totalAmount = all.reduce((s, o) => s + o.totalAmount, 0);
  const totalPaid = all.reduce((s, o) => s + o.paidAmount, 0);
  const totalRemaining = totalAmount - totalPaid;

  return (
    <div className="space-y-4">
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div className="bg-white rounded-xl border border-blue-100 p-4">
          <div className="flex items-center justify-between mb-3">
            <div className="w-10 h-10 flex items-center justify-center rounded-lg bg-blue-50 text-blue-600">
              <i className="ri-shopping-cart-2-line text-xl" />
            </div>
            <span className="text-xs bg-blue-50 text-blue-600 px-2 py-0.5 rounded-full">
              {entries.length} مُصفّى
            </span>
          </div>
          <p className="text-2xl font-bold text-gray-800">{all.length}</p>
          <p className="text-sm text-gray-500 mt-1">إجمالي الطلبيات</p>
        </div>
        <div
          className={`rounded-xl border p-4 ${pending.length > 0 ? "bg-amber-50 border-amber-200" : "bg-white border-blue-100"}`}
        >
          <div className="flex items-center justify-between mb-3">
            <div
              className={`w-10 h-10 flex items-center justify-center rounded-lg ${pending.length > 0 ? "bg-amber-100 text-amber-600" : "bg-gray-100 text-gray-400"}`}
            >
              <i className="ri-time-line text-xl" />
            </div>
            {pending.length > 0 && (
              <span className="text-xs bg-amber-100 text-amber-600 px-2 py-0.5 rounded-full animate-pulse">
                قيد الانتظار
              </span>
            )}
          </div>
          <p
            className={`text-2xl font-bold ${pending.length > 0 ? "text-amber-600" : "text-gray-800"}`}
          >
            {pending.length}
          </p>
          <p className="text-sm text-gray-500 mt-1">قيد الانتظار</p>
          <p className="text-xs text-gray-400 mt-0.5">
            {ordered.length} مُوَّرد / {received.length} مستلم
          </p>
        </div>
        <div className="bg-white rounded-xl border border-blue-100 p-4">
          <div className="flex items-center justify-between mb-3">
            <div className="w-10 h-10 flex items-center justify-center rounded-lg bg-green-50 text-green-600">
              <i className="ri-money-dollar-circle-line text-xl" />
            </div>
          </div>
          <p className="text-2xl font-bold text-gray-800">{fmt(totalAmount)}</p>
          <p className="text-sm text-gray-500 mt-1">إجمالي قيمة الطلبيات (ج.م)</p>
        </div>
        <div
          className={`rounded-xl border p-4 ${totalRemaining > 0 ? "bg-red-50 border-red-100" : "bg-white border-blue-100"}`}
        >
          <div className="flex items-center justify-between mb-3">
            <div
              className={`w-10 h-10 flex items-center justify-center rounded-lg ${totalRemaining > 0 ? "bg-red-100 text-red-500" : "bg-green-50 text-green-500"}`}
            >
              <i className="ri-alarm-warning-line text-xl" />
            </div>
          </div>
          <p
            className={`text-2xl font-bold ${totalRemaining > 0 ? "text-red-600" : "text-green-700"}`}
          >
            {fmt(totalRemaining)}
          </p>
          <p className="text-sm text-gray-500 mt-1">مستحقات غير مدفوعة (ج.م)</p>
          <p className="text-xs text-green-600 mt-0.5">مدفوع: {fmt(totalPaid)} ج.م</p>
        </div>
      </div>

      <div className="bg-white rounded-xl border border-blue-100 p-4 flex flex-wrap gap-3">
        {[
          {
            label: "قيد الانتظار",
            count: pending.length,
            color: "bg-amber-50 text-amber-600",
            icon: "ri-time-line",
          },
          {
            label: "مُوَّرد",
            count: ordered.length,
            color: "bg-blue-50 text-blue-600",
            icon: "ri-truck-line",
          },
          {
            label: "مستلم",
            count: received.length,
            color: "bg-green-50 text-green-600",
            icon: "ri-check-double-line",
          },
          {
            label: "ملغي",
            count: cancelled.length,
            color: "bg-red-50 text-red-500",
            icon: "ri-close-circle-line",
          },
        ].map((s) => (
          <div
            key={s.label}
            className={`flex items-center gap-2 px-4 py-2 rounded-lg ${s.color} text-sm`}
          >
            <i className={s.icon} />
            <span>{s.label}</span>
            <span className="bg-white/70 rounded-full w-5 h-5 flex items-center justify-center text-xs font-bold">
              {s.count}
            </span>
          </div>
        ))}
        <div className="mr-auto flex items-center gap-2 text-xs text-gray-500">
          <span>إجمالي الوحدات المطلوبة:</span>
          <strong className="text-blue-700">
            {all.reduce(
              (s, o) => s + o.items.reduce((a, i) => a + i.quantity, 0),
              0,
            )}
          </strong>
          <span>قطعة/متر</span>
        </div>
      </div>
    </div>
  );
}
