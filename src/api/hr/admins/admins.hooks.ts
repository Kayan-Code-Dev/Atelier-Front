import { mutationOptions, queryOptions, useQueryClient } from "@tanstack/react-query";
import {
  blockAdmin,
  createAdmin,
  deleteAdmin,
  forceDeleteAdmin,
  getAdminRoles,
  getAdmins,
  getDeletedAdmins,
  restoreAdmin,
  updateAdmin,
} from "./admins.service";
import { TAdmin } from "./admins.types";
import { TPaginationResponse } from "@/api/api-common.types";

export const ADMINS_KEY = "admins";
export const DELETED_ADMINS_KEY = "deleted-admins";

export const useGetAdminsQueryOptions = (page: number) =>
  queryOptions<TPaginationResponse<TAdmin> | undefined, Error>({
    queryKey: [ADMINS_KEY, page],
    queryFn: () => getAdmins(page),
  });

export const useGetDeletedAdminsQueryOptions = (page: number) =>
  queryOptions<TPaginationResponse<TAdmin> | undefined, Error>({
    queryKey: [DELETED_ADMINS_KEY, page],
    queryFn: () => getDeletedAdmins(page),
  });

export const useCreateAdminMutationOptions = () => {
  const qClient = useQueryClient();
  return mutationOptions({
    mutationFn: createAdmin,
    onSuccess: () => {
      qClient.invalidateQueries({ queryKey: [ADMINS_KEY] });
    },
  });
};

export const useUpdateAdminMutationOptions = () => {
  const qClient = useQueryClient();
  return mutationOptions({
    mutationFn: ({ id, data }: { id: string; data: FormData }) =>
      updateAdmin(id, data),
    onSuccess: () => {
      qClient.invalidateQueries({ queryKey: [ADMINS_KEY] });
    },
  });
};

export const useDeleteAdminMutationOptions = () => {
  const qClient = useQueryClient();
  return mutationOptions({
    mutationFn: deleteAdmin,
    onSuccess: () => {
      qClient.invalidateQueries({ queryKey: [ADMINS_KEY] });
      qClient.invalidateQueries({ queryKey: [DELETED_ADMINS_KEY] });
    },
  });
};

export const useForceDeleteAdminMutationOptions = () => {
  const qClient = useQueryClient();
  return mutationOptions({
    mutationFn: forceDeleteAdmin,
    onSuccess: () => {
      qClient.invalidateQueries({ queryKey: [ADMINS_KEY] });
      qClient.invalidateQueries({ queryKey: [DELETED_ADMINS_KEY] });
    },
  });
};

export const useRestoreAdminMutationOptions = () => {
  const qClient = useQueryClient();
  return mutationOptions({
    mutationFn: restoreAdmin,
    onSuccess: () => {
      qClient.invalidateQueries({ queryKey: [ADMINS_KEY] });
      qClient.invalidateQueries({ queryKey: [DELETED_ADMINS_KEY] });
    },
  });
};

export const useGetAdminRolesQueryOptions = () =>
  queryOptions({
    queryKey: ["admin-roles"],
    queryFn: getAdminRoles,
    staleTime: 24 * 60 * 60 * 1000,
  });

export const useBlockAdminMutationOptions = () => {
  const qClient = useQueryClient();
  return mutationOptions({
    mutationFn: blockAdmin,
    onSuccess: () => {
      qClient.invalidateQueries({ queryKey: [ADMINS_KEY] });
    },
  });
};
