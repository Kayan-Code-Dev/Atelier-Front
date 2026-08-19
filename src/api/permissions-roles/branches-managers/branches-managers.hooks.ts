import {
  getBranchesManagersRoles,
  showBranchesManagersRole,
  toggleBranchesManagersRolePermission,
} from "./branches-managers.service";
import {
  mutationOptions,
  queryOptions,
  useQueryClient,
} from "@tanstack/react-query";

export const BRANCHES_MANAGERS_ROLES_KEY = "BRANCHES_MANAGERS_ROLES";

export const useBranchesManagersRolesListQueryOptions = () => {
  return queryOptions({
    queryKey: [BRANCHES_MANAGERS_ROLES_KEY],
    queryFn: () => getBranchesManagersRoles(),
    staleTime: 1000 * 60 * 5,
  });
};

export const useBranchesManagersShowRoleQueryOptions = (id: number) => {
  return queryOptions({
    queryKey: [BRANCHES_MANAGERS_ROLES_KEY, id],
    queryFn: () => showBranchesManagersRole(id),
    enabled: !!id,
  });
};

export const useToggleBranchesManagersRolePermissionMutationOptions = () => {
  const qClient = useQueryClient();
  return mutationOptions({
    mutationFn: (data: any) =>
      toggleBranchesManagersRolePermission(data.role_id, data.permission_id),
    onMutate: async (data) => {
      qClient.cancelQueries({
        queryKey: [BRANCHES_MANAGERS_ROLES_KEY, data.role_id],
      });
      const previousRoles = qClient.getQueryData([
        BRANCHES_MANAGERS_ROLES_KEY,
        data.role_id,
      ]);
      qClient.setQueryData(
        [BRANCHES_MANAGERS_ROLES_KEY, data.role_id],
        (oldData: any) => {
          return {
            ...oldData,
            permissions: oldData.permissions.map((item: any) => {
              if (item.id === data.permission_id) {
                return {
                  ...item,
                  granted: !item.granted,
                };
              }
              return item;
            }),
          };
        }
      );
      return { previousRoles };
    },
    onSettled: () => {
      qClient.invalidateQueries({
        queryKey: [BRANCHES_MANAGERS_ROLES_KEY],
      });
    },
  });
};
