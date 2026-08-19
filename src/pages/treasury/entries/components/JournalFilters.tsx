interface Filters {
  search: string;
  type: string;
  status: string;
  branch: string;
  dateFrom: string;
  dateTo: string;
}

interface Props {
  filters: Filters;
  onFilterChange: (key: string, value: string) => void;
  onReset: () => void;
  count: number;
  total: number;
}

const branches = ["الكل", "الفرع الرئيسي", "الفرع الثاني", "الفرع الثالث", "الورشة", "المصنع"];

export default function JournalFilters({ filters, onFilterChange, onReset, count, total }: Props) {
  const hasActive = filters.search || filters.type || filters.status || (filters.branch && filters.branch !== "الكل") || filters.dateFrom || filters.dateTo;

  return (
    <div className="bg-white rounded-xl border border-blue-100 p-4 space-y-3">
      <div className="flex flex-wrap gap-3 items-end">
        <div className="flex-1 min-w-[200px]">
          <label className="text-xs text-gray-500 block mb-1.5">بحث</label>
          <div className="relative">
            <i className="ri-search-line absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm" />
            <input
              type="text"
              placeholder="رقم القيد، البيان، الحساب..."
              value={filters.search}
              onChange={(e) => onFilterChange("search", e.target.value)}
              className="w-full pr-9 pl-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-blue-400 bg-gray-50"
            />
          </div>
        </div>
        <div className="min-w-[130px]">
          <label className="text-xs text-gray-500 block mb-1.5">نوع القيد</label>
          <select value={filters.type} onChange={(e) => onFilterChange("type", e.target.value)} className="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-blue-400 bg-gray-50">
            <option value="">الكل</option>
            <option value="عادي">عادي</option>
            <option value="افتتاحي">افتتاحي</option>
            <option value="تسوية">تسوية</option>
          </select>
        </div>
        <div className="min-w-[130px]">
          <label className="text-xs text-gray-500 block mb-1.5">الحالة</label>
          <select value={filters.status} onChange={(e) => onFilterChange("status", e.target.value)} className="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-blue-400 bg-gray-50">
            <option value="">الكل</option>
            <option value="معتمد">معتمد</option>
            <option value="مسودة">مسودة</option>
            <option value="ملغي">ملغي</option>
          </select>
        </div>
        <div className="min-w-[150px]">
          <label className="text-xs text-gray-500 block mb-1.5">الفرع</label>
          <select value={filters.branch} onChange={(e) => onFilterChange("branch", e.target.value)} className="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-blue-400 bg-gray-50">
            {branches.map((b) => <option key={b}>{b}</option>)}
          </select>
        </div>
        <div className="min-w-[140px]">
          <label className="text-xs text-gray-500 block mb-1.5">من تاريخ</label>
          <input type="date" value={filters.dateFrom} onChange={(e) => onFilterChange("dateFrom", e.target.value)} className="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-blue-400 bg-gray-50" />
        </div>
        <div className="min-w-[140px]">
          <label className="text-xs text-gray-500 block mb-1.5">إلى تاريخ</label>
          <input type="date" value={filters.dateTo} onChange={(e) => onFilterChange("dateTo", e.target.value)} className="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-blue-400 bg-gray-50" />
        </div>
        {hasActive && (
          <div className="flex items-end">
            <button onClick={onReset} className="flex items-center gap-1.5 px-4 py-2 text-sm text-red-500 border border-red-200 rounded-lg hover:bg-red-50 transition-colors cursor-pointer whitespace-nowrap">
              <i className="ri-refresh-line" />
              مسح الكل
            </button>
          </div>
        )}
      </div>
      {hasActive && (
        <div className="flex items-center gap-2 text-xs text-blue-600 bg-blue-50 px-3 py-2 rounded-lg">
          <i className="ri-filter-3-line" />
          <span>يتم عرض <strong>{count}</strong> قيد من أصل <strong>{total}</strong></span>
        </div>
      )}
    </div>
  );
}
