import { TPaginationResponse } from "@/api/api-common.types";
import { api } from "@/api/api-contants";
import { populateError } from "@/api/api.utils";
import { TAdmin } from "./admins.types";

export const getAdmins = async (page: number) => {
  try {
    const { data } = await api.get<{ data: TPaginationResponse<TAdmin> }>(
      `/admins/admins`,
      { params: { page } }
    );
    data.data.total_pages = Math.ceil(data.data.total / 10);
    return data.data;
  } catch (error) {
    populateError(error, "خطأ فى جلب المشرفين");
  }
};

export const getDeletedAdmins = async (page: number) => {
  try {
    const { data } = await api.get<{ data: TPaginationResponse<TAdmin> }>(
      `/admins/admins/get_deleted_admins`,
      { params: { page } }
    );
    data.data.total_pages = Math.ceil(data.data.total / 10);
    return data.data;
  } catch (error) {
    populateError(error, "خطأ فى جلب المشرفين");
  }
};

export const createAdmin = async (data: FormData) => {
  try {
    await api.post(`/admins/admins`, data);
  } catch (error) {
    populateError(error, "خطأ فى إضافة المشرف");
  }
};

export const updateAdmin = async (id: string, data: FormData) => {
  try {
    await api.post(`/admins/admins/${id}`, data);
  } catch (error) {
    populateError(error, "خطأ فى تعديل المشرف");
  }
};

export const deleteAdmin = async (id: string) => {
  try {
    await api.delete(`/admins/admins/${id}`);
  } catch (error) {
    populateError(error, "خطأ فى حذف المشرف");
  }
};

export const forceDeleteAdmin = async (id: string) => {
  try {
    await api.delete(`/admins/admins/force_delete_admin/${id}`);
  } catch (error) {
    populateError(error, "خطأ فى حذف المشرف");
  }
};

export const restoreAdmin = async (id: string) => {
  try {
    const response = await api.get(`/admins/admins/restore_admin/${id}`);

    return response.data
  } catch (error) {
    populateError(error, "خطأ فى استعادة المشرف");
  }
};

export const getAdminRoles = async () => {
  try {
    const { data } = await api.get<{ data: { id: number; name: string }[] }>(
      `/admins/admins/get_role_admin`
    );
    return data.data;
  } catch (error) {
    populateError(error, "خطأ فى جلب الصلاحيات");
  }
};

export const blockAdmin = async (id: string) => {
  try {
    await api.post(`/admins/admins/block_admin/${id}`);
  } catch (error) {
    populateError(error, "خطأ في حظر المشرف");
  }
};

