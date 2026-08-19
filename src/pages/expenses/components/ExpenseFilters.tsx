import { UseFormReturn } from "react-hook-form";
import { BranchesSelect } from "@/components/custom/BranchesSelect";
import {
  STATUS_OPTIONS,
  type FilterFormValues,
} from "../hooks/useExpensesPage";
import type { TExpenseCategoryWithSubs } from "@/api/v2/expenses/expenses.types";

type Props = {
  form: UseFormReturn<FilterFormValues>;
  itemsCount: number;
  totalCount: number;
  isExporting: boolean;
  isExportingPDF: boolean;
  onResetFilters: () => void;
  onExport: () => void;
  onExportPDF: () => void;
  onOpenCreate: () => void;
  categoryOptions: { value: string; label: string }[];
  categoriesWithSubs: TExpenseCategoryWithSubs[];
};

export function ExpenseFilters({
  form,
  itemsCount,
  totalCount,
  isExporting,
  isExportingPDF,
  onResetFilters,
  onExport,
  onExportPDF,
  onOpenCreate,
  categoryOptions,
  categoriesWithSubs,
}: Props) {
  const category = form.watch("category");
  const selectedCategory = categoriesWithSubs.find((c) => c.id === category);

  return (
    <div className="sys-card p-4 space-y-3">
      <form onSubmit={(e) => e.preventDefault()} className="space-y-3">
        <div className="flex flex-wrap items-center gap-3">
          <div className="relative min-w-52 flex-1">
            <i className="ri-search-line absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm" />
            <input
              type="text"
              placeholder="ابحث بالرقم، الفئة، الملاحظات..."
              {...form.register("search")}
              className="sys-input pr-9"
            />
          </div>
          <BranchesSelect
            value={form.watch("branch_id") || ""}
            onChange={(v) => form.setValue("branch_id", v || undefined)}
            placeholder="الفرع"
            className="min-w-[140px]"
          />
          <select
            {...form.register("status")}
            className="sys-select min-w-[120px]"
          >
            {STATUS_OPTIONS.map((s) => (
              <option key={s.value} value={s.value}>
                {s.label}
              </option>
            ))}
          </select>
          <select
            {...form.register("category")}
            className="sys-select min-w-[140px]"
            onChange={(e) => {
              form.setValue("category", e.target.value || undefined);
              form.setValue("subcategory", undefined);
            }}
          >
            {categoryOptions.map((c) => (
              <option key={c.value} value={c.value}>
                {c.label}
              </option>
            ))}
          </select>
        </div>
        <div className="flex flex-wrap items-center gap-3">
          <div className="flex items-center gap-2">
            <span className="text-xs text-slate-500 whitespace-nowrap">من تاريخ:</span>
            <input
              type="date"
              {...form.register("start_date")}
              className="sys-input w-36 text-sm"
            />
          </div>
          <div className="flex items-center gap-2">
            <span className="text-xs text-slate-500 whitespace-nowrap">إلى تاريخ:</span>
            <input
              type="date"
              {...form.register("end_date")}
              className="sys-input w-36 text-sm"
            />
          </div>
          {selectedCategory && selectedCategory.subcategories.length > 0 && (
            <select
              {...form.register("subcategory")}
              className="sys-select min-w-[140px]"
            >
              <option value="">الكل</option>
              {selectedCategory.subcategories.map((sub) => (
                <option key={sub.id} value={sub.id}>
                  {sub.name}
                </option>
              ))}
            </select>
          )}
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
              background: "#FEE2E2",
              color: "#DC2626",
              border: "1px solid #FECACA",
            }}
          >
            <i className="ri-file-pdf-line text-sm" />{" "}
            {isExportingPDF ? "جاري التصدير..." : "تصدير PDF"}
          </button>
          <button
            type="button"
            onClick={onOpenCreate}
            className="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-bold cursor-pointer whitespace-nowrap blue-btn"
          >
            <i className="ri-add-line" /> مصروف جديد
          </button>
        </div>
      </form>
    </div>
  );
}
