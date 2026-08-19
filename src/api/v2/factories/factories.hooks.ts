import {
  mutationOptions,
  queryOptions,
  useQueryClient,
  infiniteQueryOptions,
} from "@tanstack/react-query";
import {
  createFactory,
  getFactories,
  getFactory,
  updateFactory,
  deleteFactory,
  exportFactoriesToCSV,
} from "./factories.service";
import { TUpdateFactoryRequest } from "./factories.types";

export const FACTORIES_KEY = "factories";

export const useCreateFactoryMutationOptions = () => {
  const queryClient = useQueryClient();
  return mutationOptions({
    mutationFn: createFactory,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: [FACTORIES_KEY] });
    },
  });
};

export const useUpdateFactoryMutationOptions = () => {
  const queryClient = useQueryClient();
  return mutationOptions({
    mutationFn: (payload: { id: number; data: TUpdateFactoryRequest }) =>
      updateFactory(payload.id, payload.data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: [FACTORIES_KEY] });
    },
  });
};

export const useGetFactoriesQueryOptions = (page: number, per_page: number) => {
  return queryOptions({
    queryKey: [FACTORIES_KEY, page, per_page],
    queryFn: () => getFactories(page, per_page),
    staleTime: 1000 * 60 * 5,
  });
};

export const useGetFactoryQueryOptions = (id: number) => {
  return queryOptions({
    queryKey: [FACTORIES_KEY, id],
    queryFn: () => getFactory(id),
    staleTime: 1000 * 60 * 5,
  });
};

export const useDeleteFactoryMutationOptions = () => {
  const queryClient = useQueryClient();
  return mutationOptions({
    mutationFn: deleteFactory,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: [FACTORIES_KEY] });
    },
  });
};

export const useGetInfiniteFactoriesQueryOptions = (
  per_page: number
) => {
  return infiniteQueryOptions({
    queryKey: [FACTORIES_KEY, "infinite"],
    queryFn: ({ pageParam = 1 }) => getFactories(pageParam, per_page),
    initialPageParam: 1,
    getNextPageParam: (lastPage) => {
      if (!lastPage) return undefined;
      if (lastPage.current_page < lastPage.total_pages) {
        return lastPage.current_page + 1;
      }
      return undefined;
    },
    staleTime: 1000 * 60 * 5,
  });
};

export const useExportFactoriesToCSVMutationOptions = () => {
  return mutationOptions({
    mutationFn: (params?: Parameters<typeof exportFactoriesToCSV>[0]) =>
      exportFactoriesToCSV(params),
  });
};