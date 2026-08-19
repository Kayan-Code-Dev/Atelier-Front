import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from "@/components/ui/dialog";
import { Skeleton } from "@/components/ui/skeleton";
import { Badge } from "@/components/ui/badge";
import {
  TRoleItem,
  TRolePermissionItem,
} from "@/api/permissions-roles/admins/roles/roles.types";
import { useState } from "react";
import { cn } from "@/lib/utils";
import { Loader2 } from "lucide-react";
import { useBranchesManagersShowRoleQueryOptions, useToggleBranchesManagersRolePermissionMutationOptions } from "@/api/permissions-roles/branches-managers/branches-managers.hooks";
import { useMutation, useQuery } from "@tanstack/react-query";

type Props = {
  role: TRoleItem | null;
  open: boolean;
  onOpenChange: (open: boolean) => void;
};

export function ViewBranchesManagersPermissionsModal({ role, open, onOpenChange }: Props) {
  const { data, isPending } = useQuery(useBranchesManagersShowRoleQueryOptions(role?.id!));
  const permissions = data ? data.permissions : [];
  const [togglingId, setTogglingId] = useState<number | null>(null);
  const { mutate: togglePermission } = useMutation(useToggleBranchesManagersRolePermissionMutationOptions());

  const handleToggle = (permissionId: number) => {
    if (!role || togglingId) return; // Prevent clicks while one is processing

    setTogglingId(permissionId);

    togglePermission(
      { role_id: role.id, permission_id: permissionId },
      {
        onSettled: () => {
          // Re-query will happen via onSettled in the hook
          setTogglingId(null); // Clear the loading state
        },
      }
    );
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle className="text-center">
            اذونات الدور: {role?.name}
          </DialogTitle>
          <DialogDescription className="text-center ">
            قائمة بجميع الاذونات الممنوحة لهذا الدور.
          </DialogDescription>
        </DialogHeader>
        <div className="py-4">
          {isPending ? (
            <div className="flex flex-wrap gap-2">
              <Skeleton className="h-6 w-24 rounded-full" />
              <Skeleton className="h-6 w-32 rounded-full" />
              <Skeleton className="h-6 w-28 rounded-full" />
            </div>
          ) : permissions && permissions.length > 0 ? (
            <div className="flex flex-wrap gap-2">
              {permissions.map((perm: TRolePermissionItem) => {
                const isToggling = togglingId === perm.id;

                return (
                  <Badge
                    key={perm.id}
                    variant="secondary" // Base style (normal/gray)
                    onClick={() => handleToggle(perm.id)}
                    className={cn(
                      "cursor-pointer p-2 transition-all",
                      // If granted, make it green
                      perm.granted &&
                        "bg-green-600 text-white hover:bg-green-600/80",
                      // If any are toggling, dim the ones that are NOT
                      togglingId &&
                        !isToggling &&
                        "opacity-50 pointer-events-none"
                    )}
                  >
                    {isToggling ? (
                      <Loader2 className="h-4 w-4 animate-spin" />
                    ) : (
                      perm.name
                    )}
                  </Badge>
                );
              })}
            </div>
          ) : (
            <p className="text-center text-muted-foreground">
              لا توجد اذونات مرتبطة بهذا الدور.
            </p>
          )}
        </div>
      </DialogContent>
    </Dialog>
  );
}
