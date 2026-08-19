import { api } from "@/api/api-contants";
import { populateError } from "@/api/api.utils";
import { TRoleItem, TRolePermissionItem } from "./roles.types";
import { TLoginGuard } from "@/api/auth/auth.types";

export const getRolesList = async () => {
  try {
    const { data } = await api.get<{ data: TRoleItem[] }>("/admins/roles");
    return data.data;
  } catch (error) {
    populateError(error, "خطأ اثناء تحميل فى التصريحات");
  }
};

export const deleteRole = async (id: number) => {
  try {
    await api.delete(`/admins/roles/${id}`);
  } catch (error) {
    populateError(error, "خطأ اثناء مسح الصلاحية");
  }
};

export const editRole = async (
  id: number,
  {
    guard_name,
    name,
  }: {
    guard_name: TLoginGuard;
    name: string;
  }
) => {
  try {
    await api.put(`admins/roles/${id}`, {
      guard_name,
      name,
    });
  } catch (error) {
    populateError(error, "خطأ فى تعديل الصلاحية");
  }
};

export const showRole = async (id: number) => {
  try {
    const { data } = await api.get<{
      permissions: TRolePermissionItem[];
      role: TRoleItem;
    }>(`/admins/roles/${id}`);
    return data;
  } catch (error) {
    populateError(error, "خطأ اثناء عرض الصلاحية");
  }
};

export const toggleRolePermission = async (
  role_id: number,
  permission_id: number
) => {
  try {
    await api.post(`/admins/permissions/role`, {
      role_id,
      permission_id,
    });
  } catch (error) {
    populateError(error, "خطأ اثناء تعديل اذن فى صلاحية");
  }
};

export const createRole = async (guard_name: TLoginGuard, name: string) => {
  try {
    await api.post(`/admins/roles`, {
      guard_name,
      name,
    });
  } catch (error) {
    populateError(error, "خطأ فى انشاء صلاحية");
  }
};
