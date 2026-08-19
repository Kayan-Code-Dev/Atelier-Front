import {
  infiniteQueryOptions,
  mutationOptions,
  queryOptions,
  useQueryClient,
} from "@tanstack/react-query";
import {
  createBranch,
  deleteBranch,
  exportBranchesToCSV,
  getBranch,
  getBranches,
  updateBranch,
  type TGetBranchesParams,
} from "./branches.service";
import { TCreateBranchRequest, TUpdateBranchRequest } from "./branches.types";

export const BRANCHES_KEY = "branches";

export const useGetBranchesQueryOptions = (
  page: number,
  per_page: number,
  params?: TGetBranchesParams
) => {
  return queryOptions({
    queryKey: [BRANCHES_KEY, page, per_page, params],
    queryFn: () => getBranches(page, per_page, params),
    staleTime: 1000 * 60 * 5,
  });
};

export const useCreateBranchMutationOptions = () => {
  const queryClient = useQueryClient();
  return mutationOptions({
    mutationFn: (data: TCreateBranchRequest) => createBranch(data),
    onSettled: () => {
      queryClient.invalidateQueries({ queryKey: [BRANCHES_KEY] });
    },
  });
};

export const useUpdateBranchMutationOptions = () => {
  const queryClient = useQueryClient();
  return mutationOptions({
    mutationFn: (payload: { id: number; data: TUpdateBranchRequest }) =>
      updateBranch(payload.id, payload.data),
    onSettled: () => {
      queryClient.invalidateQueries({ queryKey: [BRANCHES_KEY] });
    },
  });
};

export const useDeleteBranchMutationOptions = () => {
  const queryClient = useQueryClient();
  return mutationOptions({
    mutationFn: (id: number) => deleteBranch(id),
    onSettled: () => {
      queryClient.invalidateQueries({ queryKey: [BRANCHES_KEY] });
    },
  });
};

export const useGetBranchQueryOptions = (id: number) => {
  return queryOptions({
    queryKey: [BRANCHES_KEY, id],
    queryFn: () => getBranch(id),
    staleTime: 1000 * 60 * 5,
  });
};

export const useGetInfiniteBranchesQueryOptions = (
  per_page: number,
  params?: TGetBranchesParams
) => {
  return infiniteQueryOptions({
    queryKey: [BRANCHES_KEY, "infinite", params],
    queryFn: ({ pageParam = 1 }) => getBranches(pageParam, per_page, params),
    initialPageParam: 1,
    staleTime: 1000 * 60 * 5,
    getNextPageParam: (lastPage) => {
      if (!lastPage) return undefined;
      if (lastPage.current_page < lastPage.total_pages) {
        return lastPage.current_page + 1;
      }
    },
  });
};

export const useExportBranchesToCSVQueryOptions = () => {
  return mutationOptions({
    mutationFn: (params?: Parameters<typeof exportBranchesToCSV>[0]) =>
      exportBranchesToCSV(params),
  });
};
