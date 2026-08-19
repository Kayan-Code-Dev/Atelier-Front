interface Filters {
  search: string;
  supplier: string;
  status: string;
  branch: string;
  dateFrom: string;
  dateTo: string;
}

interface Props {
  filters: Filters;
  onChange: (key: string, value: string) => void;
  onReset: () => void;
  count: number;
  total: number;
  supplierOptions: { id: number; name: string }[];
  branchOptions: string[];
}

export default function SupOrderFilters({
  filters,
  onChange,
  onReset,
  count,
  total,
  supplierOptions,
  branchOptions,
}: Props) {
  const hasActive =
    Boolean(filters.search) ||
    filters.supplier !== "الكل" ||
    filters.status !== "الكل" ||
    filters.branch !== "الكل" ||
    Boolean(filters.dateFrom) ||
    Boolean(filters.dateTo);

  return (
    <div className="bg-white rounded-xl border border-blue-100 p-4 space-y-3">
      <div className="flex flex-wrap gap-3 items-end">
        <div className="flex-1 min-w-[200px]">
          <label className="text-xs text-gray-500 block mb-1.5">بحث</label>
          <div className="relative">
            <i className="ri-search-line absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm" />
            <input
              type="text"
              placeholder="رقم الطلبية، اسم المورد، المنتجات..."
              value={filters.search}
              onChange={(e) => onChange("search", e.target.value)}
              className="w-full pr-9 pl-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-blue-400 bg-gray-50"
            />
          </div>
        </div>
        <div className="min-w-[180px]">
          <label className="text-xs text-gray-500 block mb-1.5">المورد</label>
          <select
            value={filters.supplier}
            onChange={(e) => onChange("supplier", e.target.value)}
            className="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-blue-400 bg-gray-50"
          >
            <option value="الكل">الكل</option>
            {supplierOptions.map((s) => (
              <option key={s.id} value={s.name}>
                {s.name}
              </option>
            ))}
          </select>
        </div>
        <div className="min-w-[140px]">
          <label className="text-xs text-gray-500 block mb-1.5">الحالة</label>
          <select
            value={filters.status}
            onChange={(e) => onChange("status", e.target.value)}
            className="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-blue-400 bg-gray-50"
          >
            {["الكل", "قيد الانتظار", "مُوَّرد", "مستلم", "ملغي"].map((s) => (
              <option key={s} value={s}>
                {s}
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
            {branchOptions.map((b) => (
              <option key={b} value={b}>
                {b}
              </option>
            ))}
          </select>
        </div>
        <div className="min-w-[140px]">
          <label className="text-xs text-gray-500 block mb-1.5">من تاريخ</label>
          <input
            type="date"
            value={filters.dateFrom}
            onChange={(e) => onChange("dateFrom", e.target.value)}
            className="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-blue-400 bg-gray-50"
          />
        </div>
        <div className="min-w-[140px]">
          <label className="text-xs text-gray-500 block mb-1.5">إلى تاريخ</label>
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
              مسح
            </button>
          </div>
        )}
      </div>
      {hasActive && (
        <div className="flex items-center gap-2 text-xs text-blue-600 bg-blue-50 px-3 py-2 rounded-lg">
          <i className="ri-filter-3-line" />
          <span>
            يتم عرض <strong>{count}</strong> طلبية من أصل <strong>{total}</strong>
          </span>
        </div>
      )}
    </div>
  );
}
