import {
  mutationOptions,
  queryOptions,
  useQueryClient,
  infiniteQueryOptions,
} from "@tanstack/react-query";
import {
  createSubcategoryApi,
  deleteSubcategoryApi,
  exportSubcategoriesToCSV,
  getSubcategoriesApi,
  getSubcategoryByIdApi,
  updateSubcategoryApi,
} from "./subcategory.service";
import { TUpdateSubcategoryRequest } from "./subcategory.types";

export const SUBCATEGORIES_KEY = "SUBCATEGORIES";

export const useSubcategoriesQueryOptions = (
  page: number,
  per_page: number,
  category_id?: number
) => {
  return queryOptions({
    queryKey: [SUBCATEGORIES_KEY, page, per_page, category_id],
    queryFn: () => getSubcategoriesApi(page, per_page, category_id),
  });
};

export const useCreateSubcategoryMutationOptions = () => {
  const qClient = useQueryClient();
  return mutationOptions({
    mutationFn: createSubcategoryApi,
    onSettled: () => {
      qClient.invalidateQueries({ queryKey: [SUBCATEGORIES_KEY] });
    },
  });
};

export const useSubcategoryByIdQueryOptions = (id: number) => {
  return queryOptions({
    queryKey: [SUBCATEGORIES_KEY, id],
    queryFn: () => getSubcategoryByIdApi(id),
  });
};

export const useUpdateSubcategoryMutationOptions = () => {
  const qClient = useQueryClient();
  return mutationOptions({
    mutationFn: (data: { id: number; req: TUpdateSubcategoryRequest }) =>
      updateSubcategoryApi(data.id, data.req),
    onSettled: () => {
      qClient.invalidateQueries({ queryKey: [SUBCATEGORIES_KEY] });
    },
  });
};

export const useDeleteSubcategoryMutationOptions = () => {
  const qClient = useQueryClient();
  return mutationOptions({
    mutationFn: (id: number) => deleteSubcategoryApi(id),
    onSettled: () => {
      qClient.invalidateQueries({ queryKey: [SUBCATEGORIES_KEY] });
    },
  });
};

export const useGetInifiniteSubcategoriesQueryOptions = (
  category_id?: number,
  per_page: number = 10
) => {
  return infiniteQueryOptions({
    queryKey: [SUBCATEGORIES_KEY, "infinite", category_id, per_page],
    queryFn: ({ pageParam = 1 }) =>
      getSubcategoriesApi(pageParam, per_page, category_id),
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

export const useExportSubcategoriesToCSVMutationOptions = () => {
  return mutationOptions({
    mutationFn: (params?: Parameters<typeof exportSubcategoriesToCSV>[0]) =>
      exportSubcategoriesToCSV(params),
  });
};