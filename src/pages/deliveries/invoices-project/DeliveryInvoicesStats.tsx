import type { DeliveryInvoiceProject } from "./deliveryInvoiceProject.types";

interface Props {
  entries: DeliveryInvoiceProject[];
  allEntries: DeliveryInvoiceProject[];
}

export default function DeliveryInvoicesStats({ entries, allEntries }: Props) {
  const today = new Date().toISOString().slice(0, 10);

  const todayEvents = allEntries.filter((e) => e.dates.eventDate === today).length;
  const upcoming = allEntries.filter(
    (e) => e.dates.eventDate > today && e.deliveryStatus === "في الانتظار",
  ).length;
  const late = allEntries.filter((e) => e.deliveryStatus === "متأخر").length;
  const totalRevenue = allEntries.reduce((s, e) => s + e.pricing.total, 0);
  const totalPaid = allEntries.reduce((s, e) => s + e.pricing.paid, 0);
  const totalRemaining = allEntries.reduce((s, e) => s + e.pricing.remaining, 0);

  const fmt = (n: number) => new Intl.NumberFormat("ar-EG").format(n);

  return (
    <div className="space-y-4">
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div className="bg-white rounded-xl border border-blue-100 p-4">
          <div className="flex items-center justify-between mb-3">
            <div className="w-10 h-10 flex items-center justify-center rounded-lg bg-blue-50 text-blue-600">
              <i className="ri-file-list-3-line text-xl" />
            </div>
            <span className="text-xs bg-blue-50 text-blue-600 px-2 py-0.5 rounded-full">
              {entries.length} مُصفّى
            </span>
          </div>
          <p className="text-2xl font-bold text-gray-800">{allEntries.length}</p>
          <p className="text-sm text-gray-500 mt-1">إجمالي الفواتير</p>
        </div>

        <div
          className={`rounded-xl border p-4 ${todayEvents > 0 ? "bg-rose-50 border-rose-200" : "bg-white border-blue-100"}`}
        >
          <div className="flex items-center justify-between mb-3">
            <div
              className={`w-10 h-10 flex items-center justify-center rounded-lg ${todayEvents > 0 ? "bg-rose-100 text-rose-600" : "bg-gray-100 text-gray-400"}`}
            >
              <i className="ri-cake-line text-xl" />
            </div>
            {todayEvents > 0 ? (
              <span className="text-xs bg-rose-100 text-rose-600 px-2 py-0.5 rounded-full animate-pulse">
                اليوم!
              </span>
            ) : null}
          </div>
          <p
            className={`text-2xl font-bold ${todayEvents > 0 ? "text-rose-600" : "text-gray-800"}`}
          >
            {todayEvents}
          </p>
          <p className="text-sm text-gray-500 mt-1">أفراح اليوم</p>
        </div>

        <div className="bg-white rounded-xl border border-blue-100 p-4">
          <div className="flex items-center justify-between mb-3">
            <div className="w-10 h-10 flex items-center justify-center rounded-lg bg-amber-50 text-amber-500">
              <i className="ri-time-line text-xl" />
            </div>
            <span className="text-xs bg-amber-50 text-amber-600 px-2 py-0.5 rounded-full">قادم</span>
          </div>
          <p className="text-2xl font-bold text-gray-800">{upcoming}</p>
          <p className="text-sm text-gray-500 mt-1">بانتظار التسليم</p>
        </div>

        <div
          className={`rounded-xl border p-4 ${late > 0 ? "bg-red-50 border-red-200" : "bg-white border-blue-100"}`}
        >
          <div className="flex items-center justify-between mb-3">
            <div
              className={`w-10 h-10 flex items-center justify-center rounded-lg ${late > 0 ? "bg-red-100 text-red-500" : "bg-gray-100 text-gray-400"}`}
            >
              <i className="ri-alarm-warning-line text-xl" />
            </div>
          </div>
          <p className={`text-2xl font-bold ${late > 0 ? "text-red-600" : "text-gray-800"}`}>{late}</p>
          <p className="text-sm text-gray-500 mt-1">متأخرة الاسترجاع</p>
        </div>
      </div>

      <div className="grid grid-cols-3 gap-4">
        <div className="bg-white rounded-xl border border-blue-100 p-4 flex items-center gap-4">
          <div className="w-10 h-10 flex items-center justify-center rounded-lg bg-blue-50 text-blue-600 flex-shrink-0">
            <i className="ri-money-dollar-circle-line text-xl" />
          </div>
          <div>
            <p className="text-xs text-gray-400">إجمالي الإيرادات</p>
            <p className="text-lg font-bold text-gray-800">
              {fmt(totalRevenue)} <span className="text-xs text-gray-400">ج.م</span>
            </p>
          </div>
        </div>
        <div className="bg-green-50 rounded-xl border border-green-100 p-4 flex items-center gap-4">
          <div className="w-10 h-10 flex items-center justify-center rounded-lg bg-green-100 text-green-600 flex-shrink-0">
            <i className="ri-checkbox-circle-line text-xl" />
          </div>
          <div>
            <p className="text-xs text-green-600">إجمالي المحصّل</p>
            <p className="text-lg font-bold text-green-700">
              {fmt(totalPaid)} <span className="text-xs text-green-500">ج.م</span>
            </p>
          </div>
        </div>
        <div className="bg-amber-50 rounded-xl border border-amber-100 p-4 flex items-center gap-4">
          <div className="w-10 h-10 flex items-center justify-center rounded-lg bg-amber-100 text-amber-600 flex-shrink-0">
            <i className="ri-hourglass-line text-xl" />
          </div>
          <div>
            <p className="text-xs text-amber-600">إجمالي المتبقي</p>
            <p className="text-lg font-bold text-amber-700">
              {fmt(totalRemaining)} <span className="text-xs text-amber-500">ج.م</span>
            </p>
          </div>
        </div>
      </div>

      <div className="bg-white rounded-xl border border-blue-100 p-4">
        <p className="text-xs text-gray-500 mb-3 font-medium">توزيع حالات التسليم</p>
        <div className="flex gap-2 flex-wrap">
          {(
            [
              {
                label: "في الانتظار",
                icon: "ri-time-line",
                color: "bg-sky-100 text-sky-600",
                count: allEntries.filter((e) => e.deliveryStatus === "في الانتظار").length,
              },
              {
                label: "تم الاستلام",
                icon: "ri-hand-coin-line",
                color: "bg-amber-100 text-amber-600",
                count: allEntries.filter((e) => e.deliveryStatus === "تم الاستلام").length,
              },
              {
                label: "تم التسليم",
                icon: "ri-check-double-line",
                color: "bg-green-100 text-green-600",
                count: allEntries.filter((e) => e.deliveryStatus === "تم التسليم").length,
              },
              {
                label: "تم الاسترجاع",
                icon: "ri-arrow-go-back-line",
                color: "bg-cyan-100 text-cyan-600",
                count: allEntries.filter((e) => e.deliveryStatus === "تم الاسترجاع").length,
              },
              {
                label: "متأخر",
                icon: "ri-alarm-warning-line",
                color: "bg-red-100 text-red-500",
                count: allEntries.filter((e) => e.deliveryStatus === "متأخر").length,
              },
            ] as const
          ).map((s) => (
            <div key={s.label} className={`flex items-center gap-2 px-3 py-1.5 rounded-lg ${s.color} text-sm`}>
              <i className={s.icon} />
              <span>{s.label}</span>
              <span className="font-bold bg-white/50 rounded-full w-5 h-5 flex items-center justify-center text-xs">
                {s.count}
              </span>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}
