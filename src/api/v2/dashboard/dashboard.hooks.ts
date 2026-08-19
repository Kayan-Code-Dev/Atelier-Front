import { queryOptions } from "@tanstack/react-query";
import { getDashboardOverview } from "./dashboard.service";
import { TDashboardOverviewParams } from "./dashboard.types";

export const DASHBOARD_KEY = "DASHBOARD_KEY";

export const useGetDashboardOverviewQueryOptions = (
  params?: TDashboardOverviewParams
) => {
  return queryOptions({
    queryKey: [DASHBOARD_KEY, "overview", params],
    queryFn: () => getDashboardOverview(params),
    staleTime: 1000 * 60 * 5, // 5 minutes
  });
};
