import {
  mutationOptions,
  queryOptions,
  useQueryClient,
  infiniteQueryOptions,
} from "@tanstack/react-query";
import {
  createCategoryApi,
  deleteCategoryApi,
  exportCategoriesToCSV,
  getCategoriesApi,
  getCategoryByIdApi,
  updateCategoryApi,
} from "./category.service";
import { TUpdateCategoryRequest } from "./category.type";

export const CATEGORIES_KEY = "CATEGORIES";

export const useCategoriesQueryOptions = (page: number, per_page: number) => {
  return queryOptions({
    queryKey: [CATEGORIES_KEY, page, per_page],
    queryFn: () => getCategoriesApi(page, per_page),
  });
};

export const useCreateCategoryMutationOptions = () => {
  const qClient = useQueryClient();
  return mutationOptions({
    mutationFn: createCategoryApi,
    onSettled: () => {
      qClient.invalidateQueries({ queryKey: [CATEGORIES_KEY] });
    },
  });
};

export const useCategoryByIdQueryOptions = (id: number) => {
  return queryOptions({
    queryKey: [CATEGORIES_KEY, id],
    queryFn: () => getCategoryByIdApi(id),
  });
};

export const useUpdateCategoryMutationOptions = () => {
  const qClient = useQueryClient();
  return mutationOptions({
    mutationFn: (data: { id: number; req: TUpdateCategoryRequest }) =>
      updateCategoryApi(data.id, data.req),
    onSettled: () => {
      qClient.invalidateQueries({ queryKey: [CATEGORIES_KEY] });
    },
  });
};

export const useDeleteCategoryMutationOptions = () => {
  const qClient = useQueryClient();
  return mutationOptions({
    mutationFn: (id: number) => deleteCategoryApi(id),
    onSettled: () => {
      qClient.invalidateQueries({ queryKey: [CATEGORIES_KEY] });
    },
  });
};

export const useInfiniteCategoriesQueryOptions = (per_page: number) => {
  return infiniteQueryOptions({
    queryKey: [CATEGORIES_KEY, "infinite"],
    queryFn: ({ pageParam = 1 }) => getCategoriesApi(pageParam, per_page),
    initialPageParam: 1,
    staleTime: 1000 * 60 * 5,
    getNextPageParam: (lastPage) => {
      if (!lastPage) return undefined;
      if (lastPage.current_page < lastPage.total_pages) {
        return lastPage.current_page + 1;
      }
      return undefined;
    },
  });
};

export const useExportCategoriesToCSVMutationOptions = () => {
  return mutationOptions({
    mutationFn: (params?: Parameters<typeof exportCategoriesToCSV>[0]) =>
      exportCategoriesToCSV(params),
  });
};