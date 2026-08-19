import { api } from "@/api/api-contants";
import { populateError } from "@/api/api.utils";

export type TCountryByModule = { id: number; name: string; code: string };

const MODULE_ENDPOINTS: Record<string, string> = {
  admin: "/admins/admins/get_countries",
  "branches-managers": "/admins/branch-managers/get_countries",
  "branches-employees": "/branch_managers/employees/get_countries",
  "branch-employee": "/branches/employees/get_countries",
};

export const getCountriesByModule = async (switchKey: string) => {
  const endpoint =
    MODULE_ENDPOINTS[switchKey] ?? MODULE_ENDPOINTS["admin"];
  try {
    const { data } = await api.get<{ data: TCountryByModule[] }>(endpoint);
    return data.data;
  } catch (error) {
    populateError(error, "خطأ فى جلب الدول");
  }
};
