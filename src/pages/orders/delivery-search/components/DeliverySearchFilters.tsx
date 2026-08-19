import type { ReactNode } from "react";
import type {
  DeliveryInvoiceTypeAr,
  DeliverySearchStatus,
  DeliverySearchUiFilters,
} from "../deliverySearch.types";

export type { DeliverySearchUiFilters };

interface DeliverySearchFiltersProps {
  filters: DeliverySearchUiFilters;
  onChange: (f: DeliverySearchUiFilters) => void;
  branches: string[];
  resultCount: number;
  totalCount: number;
  advancedSection?: ReactNode;
}

const invoiceTypes: (DeliveryInvoiceTypeAr | "الكل")[] = [
  "الكل",
  "إيجار",
  "بيع",
  "تفصيل",
];

const deliveryStatuses: (DeliverySearchStatus | "الكل")[] = [
  "الكل",
  "ينتظر التسليم",
  "تأخر التسليم",
  "جاهز للاستلام",
  "تم التسليم",
  "ينتظر الإرجاع",
  "تأخر الإرجاع",
  "تم الإرجاع",
  "قيد التنفيذ",
  "ملغي",
];

const invoiceTypeColors: Record<string, string> = {
  الكل: "bg-slate-100 text-slate-700 border-slate-200",
  إيجار: "bg-rose-50 text-rose-700 border-rose-200",
  بيع: "bg-emerald-50 text-emerald-700 border-emerald-200",
  تفصيل: "bg-violet-50 text-violet-700 border-violet-200",
};

const deliveryStatusColors: Record<
  string,
  { idle: string; active: string; dot: string }
> = {
  الكل: {
    idle: "bg-slate-100 text-slate-600 border-slate-200",
    active: "bg-slate-700 text-white border-slate-700",
    dot: "bg-slate-400",
  },
  "ينتظر التسليم": {
    idle: "bg-amber-50 text-amber-700 border-amber-200",
    active: "bg-amber-500 text-white border-amber-500",
    dot: "bg-amber-500",
  },
  "تأخر التسليم": {
    idle: "bg-red-50 text-red-700 border-red-200",
    active: "bg-red-600 text-white border-red-600",
    dot: "bg-red-500",
  },
  "جاهز للاستلام": {
    idle: "bg-emerald-50 text-emerald-700 border-emerald-200",
    active: "bg-emerald-600 text-white border-emerald-600",
    dot: "bg-emerald-500",
  },
  "تم التسليم": {
    idle: "bg-sky-50 text-sky-700 border-sky-200",
    active: "bg-sky-600 text-white border-sky-600",
    dot: "bg-sky-500",
  },
  "ينتظر الإرجاع": {
    idle: "bg-violet-50 text-violet-700 border-violet-200",
    active: "bg-violet-600 text-white border-violet-600",
    dot: "bg-violet-500",
  },
  "تأخر الإرجاع": {
    idle: "bg-orange-50 text-orange-700 border-orange-200",
    active: "bg-orange-600 text-white border-orange-600",
    dot: "bg-orange-500",
  },
  "تم الإرجاع": {
    idle: "bg-slate-50 text-slate-600 border-slate-200",
    active: "bg-slate-600 text-white border-slate-600",
    dot: "bg-slate-400",
  },
  "قيد التنفيذ": {
    idle: "bg-indigo-50 text-indigo-700 border-indigo-200",
    active: "bg-indigo-600 text-white border-indigo-600",
    dot: "bg-indigo-500",
  },
  ملغي: {
    idle: "bg-gray-100 text-gray-500 border-gray-200",
    active: "bg-gray-600 text-white border-gray-600",
    dot: "bg-gray-400",
  },
};

export function DeliverySearchFilters({
  filters,
  onChange,
  branches,
  resultCount,
  totalCount,
  advancedSection,
}: DeliverySearchFiltersProps) {
  const hasActiveFilters =
    filters.search !== "" ||
    filters.invoiceType !== "الكل" ||
    filters.deliveryStatus !== "الكل" ||
    filters.branch !== "الكل" ||
    filters.dateFrom !== "" ||
    filters.dateTo !== "";

  const clearAll = () =>
    onChange({
      search: "",
      invoiceType: "الكل",
      deliveryStatus: "الكل",
      branch: "الكل",
      dateFrom: "",
      dateTo: "",
    });

  return (
    <div className="space-y-4">
      <div className="bg-white rounded-xl border border-slate-200 p-4 space-y-4">
        <div className="flex flex-col lg:flex-row lg:items-center gap-3">
          <div className="relative flex-1 min-w-0">
            <span className="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 flex items-center justify-center pointer-events-none">
              <i className="ri-search-line text-slate-400 text-sm" />
            </span>
            <input
              type="text"
              placeholder="بحث باسم العميل أو رقم الفاتورة..."
              value={filters.search}
              onChange={(e) => onChange({ ...filters, search: e.target.value })}
              className="w-full pr-9 pl-9 py-2 text-sm rounded-lg border border-slate-200 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-300 text-right"
            />
            {filters.search ? (
              <button
                type="button"
                onClick={() => onChange({ ...filters, search: "" })}
                className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 flex items-center justify-center cursor-pointer"
              >
                <i className="ri-close-line text-slate-400 text-sm hover:text-slate-600" />
              </button>
            ) : null}
          </div>
          <select
            value={filters.branch}
            onChange={(e) => onChange({ ...filters, branch: e.target.value })}
            className="px-3 py-2 text-sm rounded-lg border border-slate-200 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-300 cursor-pointer min-w-[140px] w-full lg:w-auto"
          >
            <option value="الكل">كل الفروع</option>
            {branches.map((b) => (
              <option key={b} value={b}>
                {b}
              </option>
            ))}
          </select>
          <input
            type="date"
            value={filters.dateFrom}
            onChange={(e) => onChange({ ...filters, dateFrom: e.target.value })}
            className="px-3 py-2 text-sm rounded-lg border border-slate-200 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-300 cursor-pointer w-full lg:w-auto"
            title="من تاريخ"
          />
          <input
            type="date"
            value={filters.dateTo}
            onChange={(e) => onChange({ ...filters, dateTo: e.target.value })}
            className="px-3 py-2 text-sm rounded-lg border border-slate-200 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-300 cursor-pointer w-full lg:w-auto"
            title="إلى تاريخ"
          />
          {hasActiveFilters ? (
            <button
              type="button"
              onClick={clearAll}
              className="flex items-center justify-center gap-1.5 px-3 py-2 text-sm rounded-lg border border-red-200 text-red-600 bg-red-50 hover:bg-red-100 cursor-pointer whitespace-nowrap transition-colors"
            >
              <i className="ri-filter-off-line text-sm" />
              مسح الكل
            </button>
          ) : null}
        </div>

        <div className="flex items-center gap-2 flex-wrap">
          <span className="text-xs font-semibold text-slate-500 ml-1">نوع الفاتورة:</span>
          {invoiceTypes.map((type) => {
            const isActive = filters.invoiceType === type;
            const colors = invoiceTypeColors[type];
            return (
              <button
                key={type}
                type="button"
                onClick={() => onChange({ ...filters, invoiceType: type })}
                className={[
                  "px-3 py-1.5 text-xs font-semibold rounded-full border cursor-pointer transition-all duration-150 whitespace-nowrap",
                  isActive ? `${colors} ring-2 ring-offset-1 ring-slate-400` : `${colors} opacity-70 hover:opacity-100`,
                ].join(" ")}
              >
                {type}
              </button>
            );
          })}
        </div>

        <div className="flex items-center gap-2 flex-wrap">
          <span className="text-xs font-semibold text-slate-500 ml-1">حالة التسليم:</span>
          {deliveryStatuses.map((status) => {
            const isActive = filters.deliveryStatus === status;
            const cfg = deliveryStatusColors[status];
            return (
              <button
                key={status}
                type="button"
                onClick={() => onChange({ ...filters, deliveryStatus: status })}
                className={[
                  "flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-full border cursor-pointer transition-all duration-150 whitespace-nowrap",
                  isActive ? cfg.active : cfg.idle,
                ].join(" ")}
              >
                {status !== "الكل" ? (
                  <span
                    className={`w-1.5 h-1.5 rounded-full shrink-0 ${isActive ? "bg-white" : cfg.dot}`}
                  />
                ) : null}
                {status}
              </button>
            );
          })}
        </div>

        <div className="flex items-center justify-between pt-1 border-t border-slate-100 flex-wrap gap-2">
          <p className="text-xs text-slate-500">
            يُعرض <span className="font-black text-slate-700">{resultCount}</span> من{" "}
            <span className="font-black text-slate-700">{totalCount}</span> فاتورة
          </p>
          {hasActiveFilters ? (
            <span className="flex items-center gap-1 text-xs font-semibold text-amber-600 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded-full">
              <span className="w-1.5 h-1.5 rounded-full bg-amber-500" />
              فلتر نشط
            </span>
          ) : null}
        </div>
      </div>

      {advancedSection}
    </div>
  );
}
