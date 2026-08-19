import { UseFormReturn } from "react-hook-form";
import { TBranchResponse } from "@/api/v2/branches/branches.types";
import {
  PAYMENT_STATUSES,
  PAYMENT_TYPES,
  type FilterFormValues,
} from "../hooks/usePaymentsPage";
import { TCashbox } from "@/api/v2/cashboxes/cashboxes.types";

type Props = {
  form: UseFormReturn<FilterFormValues>;
  branches: TBranchResponse[];
  cashboxes: TCashbox[];
  itemsCount: number;
  totalCount: number;
  isExporting: boolean;
  isExportingPDF: boolean;
  onResetFilters: () => void;
  onExport: () => void;
  onExportPDF: () => void;
};

export function PaymentFilters({
  form,
  branches,
  cashboxes,
  itemsCount,
  totalCount,
  isExporting,
  isExportingPDF,
  onResetFilters,
  onExport,
  onExportPDF,
}: Props) {
  return (
    <div className="sys-card p-4 space-y-3">
      <form onSubmit={(e) => e.preventDefault()} className="space-y-3">
        <div className="flex flex-wrap items-center gap-3">
          <select {...form.register("scope")} className="sys-select min-w-[170px]">
            <option value="payments">المدفوعات</option>
            <option value="tailoring">مدفوعات التفصيل</option>
            <option value="manual">المدفوعات الأخرى</option>
          </select>
          <div className="relative min-w-56 flex-1">
            <i className="ri-search-line absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm" />
            <input
              type="text"
              placeholder="ابحث بالرقم، العميل، رقم الفاتورة..."
              {...form.register("search")}
              className="sys-input pr-9"
            />
          </div>
          <select
            {...form.register("branch_id")}
            className="sys-select min-w-[140px]"
          >
            <option value="">الفرع (الكل)</option>
            {branches.map((b) => (
              <option key={b.id} value={b.id}>
                {b.name}
              </option>
            ))}
          </select>
          <select {...form.register("status")} className="sys-select min-w-[120px]">
            {PAYMENT_STATUSES.map((s) => (
              <option key={s.value} value={s.value}>
                {s.label}
              </option>
            ))}
          </select>
          <select
            {...form.register("payment_type")}
            className="sys-select min-w-[120px]"
          >
            {PAYMENT_TYPES.map((t) => (
              <option key={t.value} value={t.value}>
                {t.label}
              </option>
            ))}
          </select>
          <select
            {...form.register("cashbox_id")}
            className="sys-select min-w-[170px]"
          >
            <option value="">الصندوق (الكل)</option>
            {cashboxes.map((c) => (
              <option key={c.id} value={c.id}>
                {c.name}
              </option>
            ))}
          </select>
        </div>
        <div className="flex flex-wrap items-center gap-3">
          <div className="flex items-center gap-2">
            <span className="text-xs text-slate-500 whitespace-nowrap">من تاريخ:</span>
            <input
              type="date"
              {...form.register("date_from")}
              className="sys-input w-36 text-sm"
            />
          </div>
          <div className="flex items-center gap-2">
            <span className="text-xs text-slate-500 whitespace-nowrap">إلى تاريخ:</span>
            <input
              type="date"
              {...form.register("date_to")}
              className="sys-input w-36 text-sm"
            />
          </div>
          <button
            type="button"
            onClick={onResetFilters}
            className="flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold cursor-pointer whitespace-nowrap text-slate-600 transition-colors hover:bg-slate-200"
            style={{ background: "#F1F5F9", border: "1px solid #E2E8F0" }}
          >
            <i className="ri-refresh-line" /> إعادة ضبط
          </button>
          <div className="flex-1" />
          <span className="text-xs text-slate-500">
            يعرض <span className="font-bold text-slate-700">{itemsCount}</span> من{" "}
            {totalCount} سجل
          </span>
          <button
            type="button"
            onClick={onExport}
            disabled={isExporting}
            className="flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold cursor-pointer whitespace-nowrap disabled:opacity-60"
            style={{
              background: "#F0FDF4",
              color: "#065F46",
              border: "1px solid #D1FAE5",
            }}
          >
            <i className="ri-file-excel-2-line text-sm" />{" "}
            {isExporting ? "جاري التصدير..." : "تصدير Excel"}
          </button>
          <button
            type="button"
            onClick={onExportPDF}
            disabled={isExportingPDF}
            className="flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold cursor-pointer whitespace-nowrap disabled:opacity-60"
            style={{
              background: "#FEF2F2",
              color: "#991B1B",
              border: "1px solid #FECACA",
            }}
          >
            <i className="ri-file-pdf-line text-sm" />{" "}
            {isExportingPDF ? "جاري التصدير..." : "تصدير PDF"}
          </button>
        </div>
      </form>
    </div>
  );
}
