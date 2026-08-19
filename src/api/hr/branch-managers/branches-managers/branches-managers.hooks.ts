import { TPaginationResponse } from "@/api/api-common.types";
import { TBranchesManager } from "./branches-managers.types";
import { mutationOptions, queryOptions, useQueryClient } from "@tanstack/react-query";
import {
  blockBranchesManager,
  createBranchesManager,
  deleteBranchesManager,
  forceDeleteBranchesManager,
  getBranchesManagers,
  getBranchesManagersRoles,
  getDeletedBranchesManagers,
  restoreBranchesManager,
  updateBranchesManager,
} from "./branches-managers.service";

export const BRANCHES_MANAGERS_KEY = "branches-managers";
export const DELETED_BRANCHES_MANAGERS_KEY = "deleted-branches-managers";

export const useGetBranchesManagersQueryOptions = (page: number) =>
  queryOptions<TPaginationResponse<TBranchesManager> | undefined, Error>({
    queryKey: [BRANCHES_MANAGERS_KEY, page],
    queryFn: () => getBranchesManagers(page),
  });

export const useCreateBranchesManagerMutationOptions = () => {
  const qClient = useQueryClient();
  return mutationOptions({
    mutationFn: createBranchesManager,
    onSuccess: () => {
      qClient.invalidateQueries({ queryKey: [BRANCHES_MANAGERS_KEY] });
    },
  });
};

export const useUpdateBranchesManagerMutationOptions = () => {
  const qClient = useQueryClient();
  return mutationOptions({
    mutationFn: ({ id, data }: { id: string; data: FormData }) =>
      updateBranchesManager(id, data),
    onSuccess: () => {
      qClient.invalidateQueries({ queryKey: [BRANCHES_MANAGERS_KEY] });
    },
  });
};

export const useDeleteBranchesManagerMutationOptions = () => {
  const qClient = useQueryClient();
  return mutationOptions({
    mutationFn: deleteBranchesManager,
    onSuccess: () => {
      qClient.invalidateQueries({ queryKey: [BRANCHES_MANAGERS_KEY] });
      qClient.invalidateQueries({ queryKey: [DELETED_BRANCHES_MANAGERS_KEY] });
    },
  });
};

export const useGetBranchesManagersRolesQueryOptions = () =>
  queryOptions({
    queryKey: ["branches-managers-roles"],
    queryFn: getBranchesManagersRoles,
    staleTime: 24 * 60 * 60 * 1000,
  });

export const useGetDeletedBranchesManagersQueryOptions = (page: number) =>
  queryOptions<TPaginationResponse<TBranchesManager> | undefined, Error>({
    queryKey: [DELETED_BRANCHES_MANAGERS_KEY, page],
    queryFn: () => getDeletedBranchesManagers(page),
  });

export const useForceDeleteBranchesManagerMutationOptions = () => {
  const qClient = useQueryClient();
  return mutationOptions({
    mutationFn: forceDeleteBranchesManager,
    onSuccess: () => {
      qClient.invalidateQueries({ queryKey: [BRANCHES_MANAGERS_KEY] });
      qClient.invalidateQueries({ queryKey: [DELETED_BRANCHES_MANAGERS_KEY] });
    },
  });
};

export const useRestoreBranchesManagerMutationOptions = () => {
  const qClient = useQueryClient();
  return mutationOptions({
    mutationFn: restoreBranchesManager,
    onSuccess: () => {
      qClient.invalidateQueries({ queryKey: [BRANCHES_MANAGERS_KEY] });
      qClient.invalidateQueries({ queryKey: [DELETED_BRANCHES_MANAGERS_KEY] });
    },
  });
};

export const useBlockBranchesManagerMutationOptions = () => {
  const qClient = useQueryClient();
  return mutationOptions({
    mutationFn: blockBranchesManager,
    onSuccess: () => {
      qClient.invalidateQueries({ queryKey: [BRANCHES_MANAGERS_KEY] });
    },
  });
};
