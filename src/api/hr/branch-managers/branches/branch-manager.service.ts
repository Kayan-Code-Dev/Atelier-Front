import { TPaginationResponse } from "@/api/api-common.types";
import { api } from "@/api/api-contants";
import { populateError } from "@/api/api.utils";
import { TBranchManager } from "./branch-manager.types";

export const getBranchManagers = async (page: number) => {
  try {
    const { data } = await api.get<{
      data: TPaginationResponse<TBranchManager>;
    }>(`/admins/branches`, { params: { page } });
    data.data.total_pages = Math.ceil(data.data.total / 10);
    return data.data;
  } catch (error) {
    populateError(error, "خطأ في جلب بيانات مدراء الفرع");
  }
};

export const getBranchesManagersIds = async () => {
  try {
    const { data } = await api.get<{ data: { id: number; name: string }[] }>(
      `/admins/branches/branch_Manger`
    );
    return data.data;
  } catch (error) {
    populateError(error, "خطأ في جلب بيانات مدراء الفروع");
  }
};

export const createBranchManager = async (data: FormData) => {
  try {
    await api.post(`/admins/branches`, data);
  } catch (error) {
    populateError(error, "خطأ في إنشاء مدير فرع جديد");
  }
};

export const updateBranchManager = async (id: string, data: FormData) => {
  try {
    await api.post(`/admins/branches/${id}`, data);
  } catch (error) {
    populateError(error, "خطأ في تحديث مدير فرع");
  }
};

export const deleteBranchManager = async (id: string) => {
  try {
    await api.delete(`/admins/branches/${id}`);
  } catch (error) {
    populateError(error, "خطأ فى مسح مدير فرع");
  }
};

export const getAllDeletedBranchManagers = async (page: number) => {
  try {
    const { data } = await api.get<{
      data: TPaginationResponse<TBranchManager>;
    }>(`/admins/branches/get_deleted_branches`, { params: { page } });
    data.data.total_pages = Math.ceil(data.data.total / 10);
    return data.data;
  } catch (error) {
    populateError(error, "خطأ في جلب بيانات مدراء الفرع المحذوفين");
  }
};


export const restoreBranchManager = async (id: string) => {
  try {
    await api.get(`/admins/branches/restore_branches/${id}`);
    } catch (error) {
    populateError(error, "خطأ في استعادة مدير فرع");
  }
};

export const deleteBranchManagerPermanently = async (id: string) => {
  try {
    await api.delete(`/admins/branches/force_delete_branches/${id}`);
  } catch (error) {
    populateError(error, "خطأ في مسح مدير فرع نهائي");
  }
};

export const blockBranchManager = async (id: string) => {
  try {
    await api.post(`/admins/branches/block_branches/${id}`);
  } catch (error) {
    populateError(error, "خطأ في حظر مدير فرع");
  }
};