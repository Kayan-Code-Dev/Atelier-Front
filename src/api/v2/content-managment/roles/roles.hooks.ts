import {
  infiniteQueryOptions,
  mutationOptions,
  queryOptions,
  useQueryClient,
} from "@tanstack/react-query";
import {
  createRole,
  deleteRole,
  exportRolesToCSV,
  getRole,
  getRolesList,
  updateRole,
} from "./roles.service";
import { TCreateRoleRequest, TRole, TUpdateRoleRequest } from "./roles.types";
import { TPaginationResponse } from "@/api/api-common.types";

export const ROLES_KEY = "ROLES";

export const useGetRolesQueryOptions = (page: number, per_page: number) => {
  return queryOptions({
    queryKey: [ROLES_KEY, page, per_page],
    queryFn: () => getRolesList(page, per_page),
    staleTime: 1000 * 60 * 5,
  });
};

export const useGetRoleQueryOptions = (id: number) => {
  return queryOptions({
    queryKey: [ROLES_KEY, id],
    queryFn: () => getRole(id),
    staleTime: 1000 * 60 * 5,
  });
};

export const useCreateRoleMutationOptions = () => {
  const qClient = useQueryClient();
  return mutationOptions({
    mutationFn: (data: TCreateRoleRequest) => createRole(data),
    onSettled: () => {
      qClient.invalidateQueries({ queryKey: [ROLES_KEY] });
    },
  });
};

export const useUpdateRoleMutationOptions = () => {
  const qClient = useQueryClient();
  return mutationOptions({
    mutationFn: ({ id, data }: { id: number; data: TUpdateRoleRequest }) =>
      updateRole(id, data),
    onSettled: () => {
      qClient.invalidateQueries({ queryKey: [ROLES_KEY] });
    },
  });
};

export const useDeleteRoleMutationOptions = () => {
  const qClient = useQueryClient();
  return mutationOptions({
    mutationFn: (id: number) => deleteRole(id),
    onSettled: () => {
      qClient.invalidateQueries({ queryKey: [ROLES_KEY] });
    },
  });
};

// infinite query options, and use with shadcn compobox to get roles and search by name (multi select)
export const useGetRolesInfiniteQueryOptions = (per_page: number) => {
  return infiniteQueryOptions({
    queryKey: [ROLES_KEY, "infinite"],
    queryFn: ({ pageParam = 1 }) => getRolesList(pageParam, per_page),
    initialPageParam: 1,
    getNextPageParam: (lastPage: TPaginationResponse<TRole> | undefined) => {
      if (!lastPage) return undefined;
      if (lastPage.current_page < lastPage.total_pages) {
        return lastPage.current_page + 1;
      }
      return undefined;
    },
    staleTime: 1000 * 60 * 5,
  });
};

export const useExportRolesToCSVMutationOptions = () => {
  return mutationOptions({
    mutationFn: (params?: Parameters<typeof exportRolesToCSV>[0]) =>
      exportRolesToCSV(params),
  });
};