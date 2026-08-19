import { fmtNumber } from "@/utils/formatDate";
import type { ReturnInvoiceProject } from "./returnInvoiceProject.types";

interface Props {
  entries: ReturnInvoiceProject[];
  allEntries: ReturnInvoiceProject[];
  isOverduePage?: boolean;
}

export default function ReturnInvoicesStats({
  entries,
  allEntries,
  isOverduePage,
}: Props) {
  const lateEntries = allEntries.filter(
    (e) => e.deliveryStatus === "متأخر",
  );
  const returned = allEntries.filter(
    (e) => e.deliveryStatus === "تم الاسترجاع",
  );
  const waiting = allEntries.filter(
    (e) => e.deliveryStatus === "في الانتظار",
  );
  const totalPenalties = allEntries.reduce(
    (s, e) => s + e.penalty.totalPenalty,
    0,
  );
  const paidPenalties = allEntries
    .filter((e) => e.deliveryStatus === "تم الاسترجاع")
    .reduce((s, e) => s + e.penalty.totalPenalty, 0);
  const unpaidPenalties = allEntries
    .filter((e) => e.deliveryStatus === "متأخر")
    .reduce((s, e) => s + e.penalty.totalPenalty, 0);

  const maxLateDays =
    lateEntries.length > 0
      ? Math.max(...lateEntries.map((e) => e.penalty.delayDays))
      : 0;

  return (
    <div className="space-y-4">
      {/* Main Stats */}
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div className="bg-white rounded-xl border border-blue-100 p-4">
          <div className="flex items-center justify-between mb-3">
            <div className="w-10 h-10 flex items-center justify-center rounded-lg bg-blue-50 text-blue-600">
              <i className="ri-refresh-line text-xl" />
            </div>
            <span className="text-xs bg-blue-50 text-blue-600 px-2 py-0.5 rounded-full">
              {entries.length} مُصفّى
            </span>
          </div>
          <p className="text-2xl font-bold text-gray-800">
            {allEntries.length}
          </p>
          <p className="text-sm text-gray-500 mt-1">
            {isOverduePage ? "إجمالي المتأخرات" : "إجمالي الإرجاعات"}
          </p>
        </div>

        <div
          className={`rounded-xl border p-4 ${lateEntries.length > 0 ? "bg-red-50 border-red-200" : "bg-white border-blue-100"}`}
        >
          <div className="flex items-center justify-between mb-3">
            <div
              className={`w-10 h-10 flex items-center justify-center rounded-lg ${lateEntries.length > 0 ? "bg-red-100 text-red-500" : "bg-gray-100 text-gray-400"}`}
            >
              <i className="ri-alarm-warning-line text-xl" />
            </div>
            {lateEntries.length > 0 && (
              <span className="text-xs bg-red-100 text-red-500 px-2 py-0.5 rounded-full animate-pulse">
                تنبيه
              </span>
            )}
          </div>
          <p
            className={`text-2xl font-bold ${lateEntries.length > 0 ? "text-red-600" : "text-gray-800"}`}
          >
            {lateEntries.length}
          </p>
          <p className="text-sm text-gray-500 mt-1">إرجاعات متأخرة</p>
          {maxLateDays > 0 && (
            <p className="text-xs text-red-400 mt-1">
              أكثر تأخر: {maxLateDays} أيام
            </p>
          )}
        </div>

        <div className="bg-white rounded-xl border border-blue-100 p-4">
          <div className="flex items-center justify-between mb-3">
            <div className="w-10 h-10 flex items-center justify-center rounded-lg bg-green-50 text-green-600">
              <i className="ri-check-double-line text-xl" />
            </div>
          </div>
          <p className="text-2xl font-bold text-gray-800">{returned.length}</p>
          <p className="text-sm text-gray-500 mt-1">تم الاسترجاع</p>
          <p className="text-xs text-gray-400 mt-1">
            {waiting.length} في الانتظار
          </p>
        </div>

        <div
          className={`rounded-xl border p-4 ${unpaidPenalties > 0 ? "bg-orange-50 border-orange-200" : "bg-white border-blue-100"}`}
        >
          <div className="flex items-center justify-between mb-3">
            <div
              className={`w-10 h-10 flex items-center justify-center rounded-lg ${unpaidPenalties > 0 ? "bg-orange-100 text-orange-500" : "bg-gray-100 text-gray-400"}`}
            >
              <i className="ri-money-dollar-circle-line text-xl" />
            </div>
          </div>
          <p
            className={`text-2xl font-bold ${unpaidPenalties > 0 ? "text-orange-600" : "text-gray-800"}`}
          >
            {fmtNumber(totalPenalties)}
          </p>
          <p className="text-sm text-gray-500 mt-1">إجمالي الغرامات (ج.م)</p>
          {unpaidPenalties > 0 && (
            <p className="text-xs text-orange-500 mt-1">
              مستحق: {fmtNumber(unpaidPenalties)} ج.م
            </p>
          )}
        </div>
      </div>

      {/* Penalty Summary & Status Distribution */}
      <div className="grid grid-cols-3 gap-4">
        <div className="bg-orange-50 rounded-xl border border-orange-100 p-4 flex items-center gap-4">
          <div className="w-10 h-10 flex items-center justify-center rounded-lg bg-orange-100 text-orange-600 flex-shrink-0">
            <i className="ri-error-warning-line text-xl" />
          </div>
          <div>
            <p className="text-xs text-orange-500">غرامات مستحقة (لم تُدفع)</p>
            <p className="text-lg font-bold text-orange-700">
              {fmtNumber(unpaidPenalties)} <span className="text-xs">ج.م</span>
            </p>
          </div>
        </div>
        <div className="bg-green-50 rounded-xl border border-green-100 p-4 flex items-center gap-4">
          <div className="w-10 h-10 flex items-center justify-center rounded-lg bg-green-100 text-green-600 flex-shrink-0">
            <i className="ri-shield-check-line text-xl" />
          </div>
          <div>
            <p className="text-xs text-green-600">غرامات محصّلة</p>
            <p className="text-lg font-bold text-green-700">
              {fmtNumber(paidPenalties)} <span className="text-xs">ج.م</span>
            </p>
          </div>
        </div>
        <div className="bg-white rounded-xl border border-blue-100 p-4">
          <p className="text-xs text-gray-500 mb-2">توزيع حالات الإرجاع</p>
          <div className="flex gap-2 flex-wrap">
            {[
              {
                label: "في الانتظار",
                color: "bg-blue-100 text-blue-600",
                count: waiting.length,
              },
              {
                label: "تم الاسترجاع",
                color: "bg-green-100 text-green-600",
                count: returned.length,
              },
              {
                label: "متأخر",
                color: "bg-red-100 text-red-500",
                count: lateEntries.length,
              },
            ].map((s) => (
              <div
                key={s.label}
                className={`flex items-center gap-1.5 px-2 py-1 rounded-lg ${s.color} text-xs`}
              >
                <span>{s.label}</span>
                <span className="font-bold bg-white/60 rounded-full w-4 h-4 flex items-center justify-center">
                  {s.count}
                </span>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
}
