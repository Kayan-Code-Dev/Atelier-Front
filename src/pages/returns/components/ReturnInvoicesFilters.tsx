export interface ReturnInvoicesFiltersState {
  search: string;
  client: string;
  employee: string;
  dateFrom: string;
  dateTo: string;
}

interface Props {
  filters: ReturnInvoicesFiltersState;
  onChange: (key: keyof ReturnInvoicesFiltersState, value: string) => void;
  onReset: () => void;
  count: number;
  total: number;
  employeeOptions: string[];
  clientOptions: string[];
}

export default function ReturnInvoicesFilters({
  filters,
  onChange,
  onReset,
  count,
  total,
  employeeOptions,
  clientOptions,
}: Props) {
  const hasActive =
    filters.search ||
    (filters.client && filters.client !== "الكل") ||
    (filters.employee && filters.employee !== "الكل") ||
    filters.dateFrom ||
    filters.dateTo;

  return (
    <div className="bg-white rounded-xl border border-blue-100 p-4 space-y-3">
      <div className="flex flex-wrap gap-3 items-end">
        <div className="flex-1 min-w-[200px]">
          <label className="text-xs text-gray-500 block mb-1.5">بحث</label>
          <div className="relative">
            <i className="ri-search-line absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm" />
            <input
              type="text"
              placeholder="اسم العميل، رقم الفاتورة، رقم قومي، هاتف..."
              value={filters.search}
              onChange={(e) => onChange("search", e.target.value)}
              className="w-full pr-9 pl-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-blue-400 bg-gray-50"
            />
          </div>
        </div>

        <div className="min-w-[160px]">
          <label className="text-xs text-gray-500 block mb-1.5">العميل</label>
          <select
            value={filters.client}
            onChange={(e) => onChange("client", e.target.value)}
            className="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-blue-400 bg-gray-50"
          >
            {["الكل", ...clientOptions].map((c) => (
              <option key={c}>{c}</option>
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
            {["الكل", ...employeeOptions].map((e) => (
              <option key={e}>{e}</option>
            ))}
          </select>
        </div>

        <div className="min-w-[140px]">
          <label className="text-xs text-gray-500 block mb-1.5">
            موعد الاسترجاع من
          </label>
          <input
            type="date"
            value={filters.dateFrom}
            onChange={(e) => onChange("dateFrom", e.target.value)}
            className="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-blue-400 bg-gray-50"
          />
        </div>
        <div className="min-w-[140px]">
          <label className="text-xs text-gray-500 block mb-1.5">
            موعد الاسترجاع إلى
          </label>
          <input
            type="date"
            value={filters.dateTo}
            onChange={(e) => onChange("dateTo", e.target.value)}
            className="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-blue-400 bg-gray-50"
          />
        </div>

        {hasActive && (
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
        )}
      </div>

      {hasActive && (
        <div className="flex items-center gap-2 text-xs text-blue-600 bg-blue-50 px-3 py-2 rounded-lg">
          <i className="ri-filter-3-line" />
          <span>
            يتم عرض <strong>{count}</strong> إرجاع من أصل{" "}
            <strong>{total}</strong>
          </span>
        </div>
      )}
    </div>
  );
}
