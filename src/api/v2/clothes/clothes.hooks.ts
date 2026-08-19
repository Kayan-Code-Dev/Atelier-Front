import {
  infiniteQueryOptions,
  mutationOptions,
  queryOptions,
  useQueryClient,
} from "@tanstack/react-query";
import {
  TGetClothesRequestParams,
  TUpdateClothesRequest,
} from "./clothes.types";
import {
  createClothes,
  deleteClothes,
  exportClothesToCSV,
  getClothes,
  getClothesAvialbelByDate,
  getClothesById,
  getClothethesUnavailableDaysRangesbyIds,
  getClothOrders,
  importClothes,
  updateClothes,
} from "./clothes.service";
import { TEntity } from "@/lib/types/entity.types";

export const CLOTHES_KEY = "CLOTHES";

export const useGetClothesQueryOptions = (params: TGetClothesRequestParams) => {
  return queryOptions({
    queryKey: [CLOTHES_KEY, params],
    queryFn: () => getClothes(params),
    staleTime: 1000 * 60 * 5,
  });
};

export const useGetClothByIdQueryOptions = (id: number) => {
  return queryOptions({
    queryKey: [CLOTHES_KEY, "detail", id],
    queryFn: () => getClothesById(id),
    enabled: !!id && id > 0,
    staleTime: 1000 * 60 * 5,
  });
};

export const useGetClothOrdersQueryOptions = (clothId: number) => {
  return queryOptions({
    queryKey: [CLOTHES_KEY, "orders", clothId],
    queryFn: () => getClothOrders(clothId),
    enabled: !!clothId && clothId > 0,
    staleTime: 1000 * 60 * 2,
  });
};

export const useCreateClothesMutationOptions = () => {
  const qClient = useQueryClient();
  return mutationOptions({
    mutationFn: createClothes,
    onSettled: () => {
      qClient.invalidateQueries({ queryKey: [CLOTHES_KEY] });
    },
  });
};

export const useUpdateClothesMutationOptions = () => {
  const qClient = useQueryClient();
  return mutationOptions({
    mutationFn: (data: { id: number; req: TUpdateClothesRequest }) =>
      updateClothes(data.id, data.req),
    onSettled: () => {
      qClient.invalidateQueries({ queryKey: [CLOTHES_KEY] });
    },
  });
};

export const useDeleteClothesMutationOptions = () => {
  const qClient = useQueryClient();
  return mutationOptions({
    mutationFn: deleteClothes,
    onSettled: () => {
      qClient.invalidateQueries({ queryKey: [CLOTHES_KEY] });
    },
  });
};

export const useGetInfiniteClothesQueryOptions = (
  params: TGetClothesRequestParams
) => {
  return infiniteQueryOptions({
    queryKey: [CLOTHES_KEY, "infinite", params],
    queryFn: ({ pageParam = 1 }) =>
      getClothes({ ...params, page: pageParam, per_page: params.per_page }),
    initialPageParam: 1,
    getNextPageParam: (lastPage) => {
      if (!lastPage) return undefined;
      if (lastPage.current_page < lastPage.total_pages) {
        return lastPage.current_page + 1;
      }
      return undefined;
    },
  });
};

export const useGetClothesAvialbelByDateQueryOptions = (
  date: string,
  entity_type: TEntity,
  entity_id: number
) => {
  return queryOptions({
    queryKey: [CLOTHES_KEY, "available-for-date", date, entity_type, entity_id],
    queryFn: () => getClothesAvialbelByDate(date, entity_type, entity_id),
    staleTime: 1000 * 60 * 5,
  });
};

export const useGetClothesUnavailableDaysRangesbyIdsQueryOptions = (
  ids: number[]
) => {
  return queryOptions({
    queryKey: [CLOTHES_KEY, "unavailable-days", ids],
    queryFn: () => getClothethesUnavailableDaysRangesbyIds(ids),
    staleTime: 1000 * 60 * 5,
  });
};

export const useExportClothesToCSVMutationOptions = () => {
  return mutationOptions({
    mutationFn: (params?: Parameters<typeof exportClothesToCSV>[0]) =>
      exportClothesToCSV(params),
  });
};

export const useImportClothesMutationOptions = () => {
  const qClient = useQueryClient();
  return mutationOptions({
    mutationFn: importClothes,
    onSettled: () => {
      qClient.invalidateQueries({ queryKey: [CLOTHES_KEY] });
    },
  });
};