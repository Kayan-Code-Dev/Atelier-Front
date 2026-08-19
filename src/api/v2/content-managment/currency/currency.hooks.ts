import {
  infiniteQueryOptions,
  mutationOptions,
  queryOptions,
  useQueryClient,
} from "@tanstack/react-query";
import {
  createCurrencyApi,
  deleteCurrencyApi,
  exportCurrenciesToCSV,
  getCurrenciesApi,
  getCurrencyByIdApi,
  updateCurrencyApi,
} from "./currency.service";
import { TCreateCurrencyRequest } from "./currency.types";

export const CURRENCIES_KEY = "CURRENCIES";

export const useCurrenciesQueryOptions = (page: number, per_page: number) => {
  return queryOptions({
    queryKey: [CURRENCIES_KEY, page, per_page],
    queryFn: () => getCurrenciesApi(page, per_page),
    staleTime: 1000 * 60 * 5,
  });
};

export const useCreateCurrencyMutationOptions = () => {
  const qClient = useQueryClient();
  return mutationOptions({
    mutationFn: createCurrencyApi,
    onSuccess: () => {
      qClient.invalidateQueries({ queryKey: [CURRENCIES_KEY] });
    },
  });
};

export const useCurrencyByIdQueryOptions = (id: number) => {
  return queryOptions({
    queryKey: [CURRENCIES_KEY, id],
    queryFn: () => getCurrencyByIdApi(id),
  });
};

export const useUpdateCurrencyMutationOptions = () => {
  const qClient = useQueryClient();
  return mutationOptions({
    mutationFn: (data: { id: number; req: TCreateCurrencyRequest }) =>
      updateCurrencyApi(data.id, data.req),
    onSettled: () => {
      qClient.invalidateQueries({ queryKey: [CURRENCIES_KEY] });
    },
  });
};

export const useDeleteCurrencyMutationOptions = () => {
  const qClient = useQueryClient();
  return mutationOptions({
    mutationFn: (id: number) => deleteCurrencyApi(id),
    onSettled: () => {
      qClient.invalidateQueries({ queryKey: [CURRENCIES_KEY] });
    },
  });
};

export const useInfiniteCurrenciesQueryOptions = (per_page: number) => {
  return infiniteQueryOptions({
    queryKey: [CURRENCIES_KEY, "infinite"],
    queryFn: ({ pageParam = 1 }) => getCurrenciesApi(pageParam, per_page),
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

export const useExportCurrenciesToCSVMutationOptions = () => {
  return mutationOptions({
    mutationFn: (params?: Parameters<typeof exportCurrenciesToCSV>[0]) =>
      exportCurrenciesToCSV(params),
  });
};

