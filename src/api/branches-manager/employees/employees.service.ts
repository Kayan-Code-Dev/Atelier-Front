import { api } from "@/api/api-contants";
import { TListItem } from "./employees.types";
import { populateError } from "@/api/api.utils";

const MODULE_ENDPOINTS: Record<string, (id?: string | number) => string> = {
  "branches-list": () => "/branch_managers/employees/get_branches",
  "department-list": (id) =>
    `/branch_managers/employees/get_branches_department/${id}`,
  "job-list": (id) => `/branch_managers/employees/get_branches_job/${id}`,
  "roles-list": (id) => `/branch_managers/employees/get_role_branch/${id}`,
  "branch-employee-department-list": () =>
    `/branches/employees/get_my_branches_department`,
  "branch-employee-job-list": (id) =>
    `/branches/employees/get_my_branches_job/${id}`,
  "branch-employee-roles-list": () => `/branches/employees/get_my_role_branch`,
  "branch-job-department-list": () => `/branches/jobs/get_department`,
  "branch-sub-category-list": () =>
    `/branches/sub_categories/get_my_categories`,
  "order-categories-list": () => `/branches/orders/get_categories`,
  "order-sub-categories-list": (id) => `/branches/orders/sub-categories/${id}`,
  "employees-inventories-categories-list": () =>
    `/employees/inventories/ge_category`,
  "employees-inventories-sub-categories-list": (id) =>
    `/employees/inventories/get_sub_category_by_categories/${id}`,
  "employees-inventories-transfer-branches-list": () =>
    `/employees/inventories/get_branches`,
  "employees-order-categories-list": () => `/employees/orders/categories`,
  "employees-order-sub-categories-list": (id) =>
    `/employees/orders/sub-categories/${id}`,
};

const REQUIRES_ID = new Set([
  "department-list",
  "job-list",
  "roles-list",
  "branch-employee-job-list",
  "order-sub-categories-list",
  "employees-inventories-sub-categories-list",
  "employees-order-sub-categories-list",
]);

export { REQUIRES_ID };

export const getListByModule = async (
  switchKey: string,
  id: string | number | undefined,
) => {
  const buildEndpoint =
    MODULE_ENDPOINTS[switchKey] ?? MODULE_ENDPOINTS["branches-list"];
  const endpoint = buildEndpoint(id);
  try {
    const { data } = await api.get<{ data: TListItem[] }>(endpoint);
    return data.data;
  } catch (error) {
    populateError(error, "خطأ فى جلب البيانات");
  }
};
