export interface DeliveryInvoicesFiltersState {
  search: string;
  paymentStatus: string;
  deliveryStatus: string;
  employee: string;
  branch: string;
  eventDateFrom: string;
  eventDateTo: string;
}

interface Props {
  filters: DeliveryInvoicesFiltersState;
  onChange: (key: keyof DeliveryInvoicesFiltersState, value: string) => void;
  onReset: () => void;
  count: number;
  total: number;
  employeeOptions: string[];
  branchOptions: string[];
}

const paymentStatuses = ["الكل", "مدفوع", "مدفوع جزئياً", "غير مدفوع"];
const deliveryStatuses = [
  "الكل",
  "في الانتظار",
  "تم الاستلام",
  "تم التسليم",
  "تم الاسترجاع",
  "متأخر",
  "ملغي",
];

export default function DeliveryInvoicesFilters({
  filters,
  onChange,
  onReset,
  count,
  total,
  employeeOptions,
  branchOptions,
}: Props) {
  const employees = ["الكل", ...employeeOptions.filter((e) => e && e !== "-")];
  const branches = ["الكل", ...branchOptions.filter((b) => b && b !== "-")];

  const hasActive =
    Boolean(filters.search) ||
    (filters.paymentStatus && filters.paymentStatus !== "الكل") ||
    (filters.deliveryStatus && filters.deliveryStatus !== "الكل") ||
    (filters.employee && filters.employee !== "الكل") ||
    (filters.branch && filters.branch !== "الكل") ||
    Boolean(filters.eventDateFrom) ||
    Boolean(filters.eventDateTo);

  return (
    <div className="bg-white rounded-xl border border-blue-100 p-4 space-y-3">
      <div className="flex flex-wrap gap-3 items-end">
        <div className="flex-1 min-w-[200px]">
          <label className="text-xs text-gray-500 block mb-1.5">بحث</label>
          <div className="relative">
            <i className="ri-search-line absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm" />
            <input
              type="text"
              placeholder="اسم العميل، رقم الفاتورة، رقم قومي..."
              value={filters.search}
              onChange={(e) => onChange("search", e.target.value)}
              className="w-full pr-9 pl-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-blue-400 bg-gray-50"
            />
          </div>
        </div>

        <div className="min-w-[145px]">
          <label className="text-xs text-gray-500 block mb-1.5">حالة الدفع</label>
          <select
            value={filters.paymentStatus}
            onChange={(e) => onChange("paymentStatus", e.target.value)}
            className="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-blue-400 bg-gray-50"
          >
            {paymentStatuses.map((s) => (
              <option key={s} value={s}>
                {s}
              </option>
            ))}
          </select>
        </div>

        <div className="min-w-[155px]">
          <label className="text-xs text-gray-500 block mb-1.5">حالة التسليم</label>
          <select
            value={filters.deliveryStatus}
            onChange={(e) => onChange("deliveryStatus", e.target.value)}
            className="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-blue-400 bg-gray-50"
          >
            {deliveryStatuses.map((s) => (
              <option key={s} value={s}>
                {s}
              </option>
            ))}
          </select>
        </div>

        <div className="min-w-[140px]">
          <label className="text-xs text-gray-500 block mb-1.5">الموظف</label>
          <select
            value={filters.employee}
            onChange={(e) => onChange("employee", e.target.value)}
            className="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-blue-400 bg-gray-50"
          >
            {employees.map((e) => (
              <option key={e} value={e}>
                {e}
              </option>
            ))}
          </select>
        </div>

        <div className="min-w-[140px]">
          <label className="text-xs text-gray-500 block mb-1.5">الفرع</label>
          <select
            value={filters.branch}
            onChange={(e) => onChange("branch", e.target.value)}
            className="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-blue-400 bg-gray-50"
          >
            {branches.map((b) => (
              <option key={b} value={b}>
                {b}
              </option>
            ))}
          </select>
        </div>

        <div className="min-w-[140px]">
          <label className="text-xs text-gray-500 block mb-1.5">موعد الفرح من</label>
          <input
            type="date"
            value={filters.eventDateFrom}
            onChange={(e) => onChange("eventDateFrom", e.target.value)}
            className="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-blue-400 bg-gray-50"
          />
        </div>
        <div className="min-w-[140px]">
          <label className="text-xs text-gray-500 block mb-1.5">موعد الفرح إلى</label>
          <input
            type="date"
            value={filters.eventDateTo}
            onChange={(e) => onChange("eventDateTo", e.target.value)}
            className="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-blue-400 bg-gray-50"
          />
        </div>

        {hasActive ? (
          <div className="flex items-end">
            <button
              type="button"
              onClick={onReset}
              className="flex items-center gap-1.5 px-4 py-2 text-sm text-red-500 border border-red-200 rounded-lg hover:bg-red-50 cursor-pointer whitespace-nowrap"
            >
              <i className="ri-refresh-line" />
              مسح الفلاتر
            </button>
          </div>
        ) : null}
      </div>

      {hasActive ? (
        <div className="flex items-center gap-2 text-xs text-blue-600 bg-blue-50 px-3 py-2 rounded-lg">
          <i className="ri-filter-3-line" />
          <span>
            يتم عرض <strong>{count}</strong> فاتورة من أصل <strong>{total}</strong>
          </span>
        </div>
      ) : null}
    </div>
  );
}
