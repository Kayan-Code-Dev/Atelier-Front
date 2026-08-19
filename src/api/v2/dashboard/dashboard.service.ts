import { api } from "@/api/api-contants";
import { populateError } from "@/api/api.utils";
import { TDashboardOverviewParams, TDashboardOverviewResponse } from "./dashboard.types";

export const getDashboardOverview = async (
  params?: TDashboardOverviewParams
): Promise<TDashboardOverviewResponse | undefined> => {
  try {
    const response = await api.get<{ data?: TDashboardOverviewResponse } | TDashboardOverviewResponse>(
      "/dashboard/overview",
      { params }
    );
    // Handle both response structures: { data: {...} } or direct object
    return (response.data as any)?.data || response.data;
  } catch (error) {
    populateError(error, "خطأ فى جلب بيانات لوحة التحكم");
  }
};
