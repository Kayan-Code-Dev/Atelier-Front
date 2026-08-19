import { TLoginGuard } from "@/api/auth/auth.types";

export type TPermissionItem = {
  id: string;
  name: string;
  guard_name: TLoginGuard;
  created_at: string;
};
