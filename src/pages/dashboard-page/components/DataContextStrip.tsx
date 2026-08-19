import { Clock, X } from "lucide-react";
import { Button } from "@/components/ui/button";
import { format } from "date-fns";
import type { ActiveFilters } from "../hooks/useDashboardFilters";

type DataContextStripProps = {
  periodFrom?: string;
  periodTo?: string;
  generatedAt?: string;
  activeFilters: ActiveFilters;
  onResetFilters: () => void;
};

export function DataContextStrip({
  periodFrom,
  periodTo,
  generatedAt,
  activeFilters,
  onResetFilters,
}: DataContextStripProps) {
  const hasActiveFilters =
    !!activeFilters.branchId ||
    !!activeFilters.departmentId ||
    (!!activeFilters.dateFrom && !!activeFilters.dateTo);

  return (
    <div className="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-xl border bg-card px-4 py-3 shadow-sm">
      <div className="flex flex-wrap items-center gap-4 text-sm text-muted-foreground">
        {periodFrom != null && periodTo != null && (
          <span className="flex items-center gap-1.5">
            <span className="font-medium text-foreground">الفترة:</span>
            <span className="tabular-nums">
              {periodFrom} → {periodTo}
            </span>
          </span>
        )}
        {generatedAt && (
          <span className="flex items-center gap-1.5">
            <Clock className="h-3.5 w-3.5" />
            <span className="font-medium text-foreground">آخر تحديث:</span>
            <span className="tabular-nums">
              {format(new Date(generatedAt), "yyyy-MM-dd HH:mm")}
            </span>
          </span>
        )}
      </div>
      {hasActiveFilters && (
        <div className="flex flex-wrap items-center gap-2">
          <span className="text-xs text-muted-foreground">الفلاتر النشطة:</span>
          {activeFilters.dateFrom != null && activeFilters.dateTo != null && (
            <span className="inline-flex items-center rounded-lg bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary">
              فترة مخصصة
            </span>
          )}
          {activeFilters.branchId != null && (
            <span className="inline-flex items-center rounded-lg bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary">
              فرع #{activeFilters.branchId}
            </span>
          )}
          {activeFilters.departmentId != null && (
            <span className="inline-flex items-center rounded-lg bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary">
              قسم #{activeFilters.departmentId}
            </span>
          )}
          <Button
            variant="ghost"
            size="sm"
            onClick={onResetFilters}
            className="h-7 text-xs text-muted-foreground hover:text-foreground"
          >
            <X className="ml-1 h-3 w-3" />
            مسح الفلاتر
          </Button>
        </div>
      )}
    </div>
  );
}
