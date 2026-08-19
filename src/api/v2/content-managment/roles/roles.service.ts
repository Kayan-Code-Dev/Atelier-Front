import { api } from "@/api/api-contants";
import { TCreateRoleRequest, TRole, TUpdateRoleRequest } from "./roles.types";
import { TPaginationResponse } from "@/api/api-common.types";
import { populateError } from "@/api/api.utils";

export const getRolesList = async (page: number, per_page: number) => {
  try {
    const { data } = await api.get<TPaginationResponse<TRole>>("/roles", {
      params: {
        page,
        per_page,
      },
    });
    return data;
  } catch (error) {
    populateError(error, "خطأ فى جلب قائمة الصلاحيات");
  }
};

export const createRole = async (request: TCreateRoleRequest) => {
  try {
    const { data } = await api.post<TRole>("/roles", request);
    return data;
  } catch (error) {
    populateError(error, "خطأ فى إنشاء الصلاحية");
  }
};

export const updateRole = async (id: number, request: TUpdateRoleRequest) => {
  try {
    const { data } = await api.put<TRole>(`/roles/${id}`, request);
    return data;
  } catch (error) {
    populateError(error, "خطأ فى تحديث الصلاحية");
  }
};

export const deleteRole = async (id: number) => {
  try {
    await api.delete(`/roles/${id}`);
  } catch (error) {
    populateError(error, "خطأ فى حذف الصلاحية");
  }
};

export const getRole = async (id: number) => {
  try {
    const { data } = await api.get<TRole>(`/roles/${id}`);
    return data;
  } catch (error) {
    populateError(error, "خطأ فى جلب الصلاحية");
  }
};

export const exportRolesToCSV = async (params?: Record<string, unknown>) => {
  try {
    const response = await api.get<Blob>(`/roles/export`, {
      params,
      responseType: "blob",
    });
    return { data: response.data, headers: response.headers };
  } catch (error) {
    populateError(error, "خطأ فى تصدير الصلاحيات");
  }
};