export type SalaryStatsGridProps = {
  monthLabel: string;
  totalNet: number;
  totalBonuses: number;
  totalDeductions: number;
  totalOvertime: number;
  paidCount: number;
  unpaidCount: number;
};

function fmt(n: number) {
  return n.toLocaleString("ar-SA", {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  });
}

export function SalaryStatsGrid({
  monthLabel,
  totalNet,
  totalBonuses,
  totalDeductions,
  totalOvertime,
  paidCount,
  unpaidCount,
}: SalaryStatsGridProps) {
  return (
    <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
      <div className="bg-white rounded-xl p-5 border border-gray-100">
        <div className="flex items-center gap-3 mb-3">
          <div className="w-10 h-10 flex items-center justify-center rounded-lg bg-emerald-50">
            <i className="ri-money-dollar-circle-line text-emerald-600 text-xl" />
          </div>
          <span className="text-sm text-gray-500 font-medium">إجمالي الرواتب الصافية</span>
        </div>
        <p className="text-2xl font-bold text-gray-800">
          {fmt(totalNet)} <span className="text-sm font-normal text-gray-400">ج.م</span>
        </p>
        <p className="text-xs text-gray-400 mt-1">{monthLabel}</p>
      </div>

      <div className="bg-white rounded-xl p-5 border border-gray-100">
        <div className="flex items-center gap-3 mb-3">
          <div className="w-10 h-10 flex items-center justify-center rounded-lg bg-amber-50">
            <i className="ri-award-line text-amber-500 text-xl" />
          </div>
          <span className="text-sm text-gray-500 font-medium">إجمالي الحوافز والمكافآت</span>
        </div>
        <p className="text-2xl font-bold text-gray-800">
          {fmt(totalBonuses)} <span className="text-sm font-normal text-gray-400">ج.م</span>
        </p>
        <p className="text-xs text-emerald-600 mt-1">
          <i className="ri-add-line" /> مضافة للرواتب
        </p>
      </div>

      <div className="bg-white rounded-xl p-5 border border-gray-100">
        <div className="flex items-center gap-3 mb-3">
          <div className="w-10 h-10 flex items-center justify-center rounded-lg bg-red-50">
            <i className="ri-subtract-line text-red-500 text-xl" />
          </div>
          <span className="text-sm text-gray-500 font-medium">إجمالي الخصومات</span>
        </div>
        <p className="text-2xl font-bold text-gray-800">
          {fmt(totalDeductions)} <span className="text-sm font-normal text-gray-400">ج.م</span>
        </p>
        <p className="text-xs text-red-400 mt-1">
          <i className="ri-subtract-line" /> مخصومة من الرواتب
        </p>
      </div>

      <div className="bg-white rounded-xl p-5 border border-gray-100">
        <div className="flex items-center gap-3 mb-3">
          <div className="w-10 h-10 flex items-center justify-center rounded-lg bg-sky-50">
            <i className="ri-time-line text-sky-600 text-xl" />
          </div>
          <span className="text-sm text-gray-500 font-medium">العمل الإضافي</span>
        </div>
        <p className="text-2xl font-bold text-gray-800">
          {fmt(totalOvertime)} <span className="text-sm font-normal text-gray-400">ج.م</span>
        </p>
        <div className="flex flex-wrap items-center gap-3 mt-1">
          <span className="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-600 font-medium">
            <span className="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block" /> {paidCount}{" "}
            مدفوع
          </span>
          <span className="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full bg-orange-50 text-orange-600 font-medium">
            <span className="w-1.5 h-1.5 rounded-full bg-orange-500 inline-block" /> {unpaidCount}{" "}
            معلق
          </span>
        </div>
      </div>
    </div>
  );
}
