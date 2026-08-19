import { api } from "@/api/api-contants";
import { populateError } from "@/api/api.utils";
import { TRoleItem, TRolePermissionItem } from "../admins/roles/roles.types";

export const getBranchesManagersRoles = async () => {
  try {
    const { data } = await api.get<{
      data: TRoleItem[];
    }>(`/branch_managers/my_roles_branch_managers`);
    return data.data;
  } catch (error) {
    populateError(error, "خطأ في جلب الصلاحيات");
  }
};

export const showBranchesManagersRole = async (id: number) => {
  try {
    const { data } = await api.get<{
      permissions: TRolePermissionItem[];
      role: TRoleItem;
    }>(`branch_managers/role/${id}`);
    return data;
  } catch (error) {
    populateError(error, "خطأ في جلب الصلاحية");
  }
};

export const toggleBranchesManagersRolePermission = async (
  role_id: number,
  permission_id: number
) => {
  try {
    await api.post(`/branch_managers/togglePermission`, {
      role_id,
      permission_id,
    });
  } catch (error) {
    populateError(error, "خطأ اثناء تعديل اذن فى صلاحية");
  }
};
