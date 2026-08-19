import { populateError } from "@/api/api.utils";
import { TRoleItem, TRolePermissionItem } from "../admins/roles/roles.types";
import { api } from "@/api/api-contants";

export const getBranchesRoles = async () => {
    try {
        const { data } = await api.get<{
            data: TRoleItem[];
        }>(`/branches/my_roles_branches`);
        return data.data;
    } catch (error) {
        populateError(error, "خطأ في جلب الصلاحيات");
    }
};

export const showBranchesRole = async (id: number) => {
    try {
        const { data } = await api.get<{
            permissions: TRolePermissionItem[];
            role: TRoleItem;
        }>(`/branches/role/${id}`);
        return data;
    } catch (error) {
        populateError(error, "خطأ في جلب الصلاحية");
    }
};

export const toggleBranchesRolePermission = async (
    role_id: number,
    permission_id: number
) => {
    try {
        await api.post(`/branches/togglePermission`, {
            role_id,
            permission_id,
        });
    } catch (error) {
        populateError(error, "خطأ اثناء تعديل اذن فى صلاحية");
    }
};

export const createBranchesRole = async (guard_name: string, name: string) => {
    try {
        await api.post(`/branches/create_roles_branch`, {
            guard_name,
            name,
        });
    } catch (error) {
        populateError(error, "خطأ فى انشاء صلاحية");
    }
};
