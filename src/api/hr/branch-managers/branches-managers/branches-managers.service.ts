import { TPaginationResponse } from "@/api/api-common.types";
import { api } from "@/api/api-contants";
import { populateError } from "@/api/api.utils";
import { TBranchesManager } from "./branches-managers.types";

export const getBranchesManagers = async (page: number) => {
    try {
        const { data } = await api.get<{ data: TPaginationResponse<TBranchesManager> }>(
            `/admins/branch-managers`,
            { params: { page } }
        );
        data.data.total_pages = Math.ceil(data.data.total / 10);
        return data.data;
    } catch (error) {
        populateError(error, "خطأ فى جلب مدراء الفروع");
    }
};

export const createBranchesManager = async (data: FormData) => {
    try {
        await api.post(`/admins/branch-managers`, data);
    } catch (error) {
        populateError(error, "خطأ فى إضافة مدير الفروع");
    }
};

export const updateBranchesManager = async (id: string, data: FormData) => {
    try {
        await api.post(`/admins/branch-managers/${id}`, data);
    } catch (error) {
        populateError(error, "خطأ فى تعديل مدير الفروع");
    }
};

export const deleteBranchesManager = async (id: string) => {
    try {
        await api.delete(`/admins/branch-managers/${id}`);
    } catch (error) {
        populateError(error, "خطأ فى حذف مدير الفروع");
    }
};

export const getDeletedBranchesManagers = async (page: number) => {
    try {
        const { data } = await api.get<{ data: TPaginationResponse<TBranchesManager> }>(
            `/admins/branch-managers/get_deleted_branchManagers`,
            { params: { page } }
        );
        data.data.total_pages = Math.ceil(data.data.total / 10);
        return data.data;
    } catch (error) {
        populateError(error, "خطأ فى جلب مدراء الفروع");
    }
};

export const forceDeleteBranchesManager = async (id: string) => {
    try {
        await api.delete(`/admins/branch-managers/force_delete_branchManager/${id}`);
    } catch (error) {
        populateError(error, "خطأ فى حذف مدير الفروع");
    }
};

export const restoreBranchesManager = async (id: string) => {
    try {
        const response = await api.get(`/admins/branch-managers/restore_branchManager/${id}`);

        return response.data
    } catch (error) {
        populateError(error, "خطأ فى استعادة مدير الفروع");
    }
};

export const getBranchesManagersRoles = async () => {
    try {
        const { data } = await api.get<{ data: { id: number; name: string; guard_name: string }[] }>(
            `/admins/branch-managers/get_role_branch_manager`
        );
        return data.data;
    } catch (error) {
        populateError(error, "خطأ فى جلب الصلاحيات");
    }
};

export const blockBranchesManager = async (id: string) => {
    try {
        await api.post(`/admins/branch-managers/block_branchManager/${id}`);
    } catch (error) {
        populateError(error, "خطأ في حظر مدير الفروع");
    }
};