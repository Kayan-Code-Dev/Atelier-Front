import { TLoginGuard } from "@/api/auth/auth.types";

export const ROLES = {
  "admin-api": {
    label: "المشرف العام",
    value: "admin-api",
  },
  "branchManager-api": {
    label: "مدير الفروع",
    value: "branchManager-api",
  },
  "employee-api": {
    label: "موظف عادي",
    value: "employee-api",
  },
  "branch-api": {
    label: "مدير فرع",
    value: "branch-api",
  },
};

export type TRoleItem = {
  id: number;
  name: string;
  guard_name: TLoginGuard;
  permissions_count: number | string;
  created_at: Date;
};

export type TRolePermissionItem = {
  id: number;
  name: string;
  guard_name: TLoginGuard;
  granted: boolean;
};
