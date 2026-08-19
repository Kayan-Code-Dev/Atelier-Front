import {
  mutationOptions,
  queryOptions,
  useQueryClient,
} from "@tanstack/react-query";
import {
  createBranchesRole,
  getBranchesRoles,
  showBranchesRole,
  toggleBranchesRolePermission,
} from "./branches.service";

export const BRANCHES_ROLES_KEY = "BRANCHES_ROLES";

export const useBranchesRolesListQueryOptions = () => {
  return queryOptions({
    queryKey: [BRANCHES_ROLES_KEY],
    queryFn: () => getBranchesRoles(),
    staleTime: 1000 * 60 * 5,
  });
};

export const useBranchesShowRoleQueryOptions = (id: number) => {
  return queryOptions({
    queryKey: [BRANCHES_ROLES_KEY, id],
    queryFn: () => showBranchesRole(id),
    enabled: !!id,
  });
};

export const useCreateBranchesRoleMutationOptions = () => {
  const qClient = useQueryClient();
  return mutationOptions({
    mutationFn: (data: { guard_name: string; name: string }) =>
      createBranchesRole(data.guard_name, data.name),
    onSettled: () => {
      qClient.invalidateQueries({
        queryKey: [BRANCHES_ROLES_KEY],
      });
    },
  });
};

export const useToggleBranchesRolePermissionMutationOptions = () => {
  const qClient = useQueryClient();
  return mutationOptions({
    mutationFn: (data: any) =>
      toggleBranchesRolePermission(data.role_id, data.permission_id),
    onMutate: async (data) => {
      qClient.cancelQueries({
        queryKey: [BRANCHES_ROLES_KEY, data.role_id],
      });
      const previousRoles = qClient.getQueryData([
        BRANCHES_ROLES_KEY,
        data.role_id,
      ]);
      qClient.setQueryData(
        [BRANCHES_ROLES_KEY, data.role_id],
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
        queryKey: [BRANCHES_ROLES_KEY],
      });
    },
  });
};
