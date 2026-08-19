import { useMemo, useState, useCallback } from "react";
import { format } from "date-fns";
import type { TDashboardOverviewParams } from "@/api/v2/dashboard/dashboard.types";
import { PERIOD_LABELS } from "../constants/dashboard.constants";

export type DashboardFiltersState = {
  filters: TDashboardOverviewParams;
  dateFrom: Date | undefined;
  dateTo: Date | undefined;
  showFilters: boolean;
};

export type ActiveFilters = {
  branchId?: number;
  departmentId?: number;
  dateFrom?: Date;
  dateTo?: Date;
};

export function useDashboardFilters() {
  const [showFilters, setShowFilters] = useState(false);
  const [filters, setFilters] = useState<TDashboardOverviewParams>({
    period: "month",
    growth_compare_period: "previous_month",
  });
  const [dateFrom, setDateFrom] = useState<Date | undefined>();
  const [dateTo, setDateTo] = useState<Date | undefined>();

  const queryParams = useMemo<TDashboardOverviewParams>(() => {
    const params: TDashboardOverviewParams = { ...filters };
    if (dateFrom) params.date_from = format(dateFrom, "yyyy-MM-dd");
    if (dateTo) params.date_to = format(dateTo, "yyyy-MM-dd");
    return params;
  }, [filters, dateFrom, dateTo]);

  const periodLabel = useMemo(() => {
    if (dateFrom && dateTo)
      return `${format(dateFrom, "yyyy-MM-dd")} → ${format(dateTo, "yyyy-MM-dd")}`;
    return PERIOD_LABELS[filters.period ?? "month"] ?? filters.period ?? "الشهر";
  }, [filters.period, dateFrom, dateTo]);

  const hasActiveFilters =
    !!filters.branch_id ||
    !!filters.department_id ||
    (!!dateFrom && !!dateTo);

  const activeFilters: ActiveFilters = useMemo(
    () => ({
      branchId: filters.branch_id,
      departmentId: filters.department_id,
      dateFrom,
      dateTo,
    }),
    [filters.branch_id, filters.department_id, dateFrom, dateTo]
  );

  const setFilter = useCallback((key: keyof TDashboardOverviewParams, value: unknown) => {
    setFilters((prev) => ({
      ...prev,
      [key]: value === "all" || value === "" ? undefined : value,
    }));
  }, []);

  const resetFilters = useCallback(() => {
    setFilters({ period: "month", growth_compare_period: "previous_month" });
    setDateFrom(undefined);
    setDateTo(undefined);
  }, []);

  const toggleShowFilters = useCallback(() => {
    setShowFilters((v) => !v);
  }, []);

  return {
    filters,
    dateFrom,
    dateTo,
    setDateFrom,
    setDateTo,
    showFilters,
    setShowFilters: toggleShowFilters,
    queryParams,
    periodLabel,
    hasActiveFilters,
    activeFilters,
    setFilter,
    resetFilters,
  };
}
