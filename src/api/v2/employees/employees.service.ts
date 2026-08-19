import { api } from "@/api/api-contants";
import {
  TCreateEmployeeRequest,
  TEmployee,
  TEmployeeAssignmentsResponse,
  TGetEmployeesParams,
  TTerminateEmployeeRequest,
  TUpdateEmployeeRequest,
} from "./employees.types";
import { populateError } from "@/api/api.utils";
import { TPaginationResponse } from "@/api/api-common.types";

/** Export employees to Excel; same query params as employees index. */
export const exportEmployeesToExcel = async (params?: TGetEmployeesParams) => {
  try {
    const response = await api.get<Blob>("/employees/export", {
      params,
      responseType: "blob",
    });
    return { data: response.data, headers: response.headers };
  } catch (error) {
    populateError(error, "خطأ فى تصدير الموظفين");
  }
};

export const createEmployee = async (data: TCreateEmployeeRequest) => {
  try {
    await api.post("/employees", data);
  } catch (error) {
    populateError(error, "خطأ فى إضافة الموظف");
  }
};

export const getEmployees = async (params: TGetEmployeesParams) => {
  try {
    const response = await api.get<TPaginationResponse<TEmployee>>(
      "/employees",
      { params }
    );
    return response.data;
  } catch (error) {
    populateError(error, "خطأ فى إحضار الموظفين");
  }
};

export const getEmployee = async (id: number) => {
  try {
    const response = await api.get<TEmployee>(`/employees/${id}`);
    return response.data;
  } catch (error) {
    populateError(error, "خطأ فى إحضار الموظف");
  }
};

export const updateEmployee = async (
  id: number,
  data: TUpdateEmployeeRequest
) => {
  try {
    await api.put(`/employees/${id}`, data);
  } catch (error) {
    populateError(error, "خطأ فى تعديل الموظف");
  }
};

export const getEmployeeAssignments = async (id: number) => {
  try {
    const response = await api.get<{
      assignments: TEmployeeAssignmentsResponse[];
    }>(`/employees/${id}/entities`);
    return response.data.assignments;
  } catch (error) {
    populateError(error, "خطأ فى إحضار التخصيصات الموظف");
  }
};

export const terminateEmployee = async (
  id: number,
  data: TTerminateEmployeeRequest
) => {
  try {
    await api.post(`/employees/${id}/terminate`, data);
  } catch (error) {
    populateError(error, "خطأ فى إنهاء الموظف");
  }
};
