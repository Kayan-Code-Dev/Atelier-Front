import {
  infiniteQueryOptions,
  mutationOptions,
  queryOptions,
  useQueryClient,
} from "@tanstack/react-query";
import {
  createCityApi,
  deleteCityApi,
  exportCitiesToCSV,
  getCitiesApi,
  getCitiesByCountryModule,
  getCityByIdApi,
  updateCityApi,
} from "./city.service";
import { TUpdateCityRequest } from "./city.types";

export const CITIES_KEY = "CITIES";

export const useCitiesQueryOptions = (page: number, per_page: number) => {
  return queryOptions({
    queryKey: [CITIES_KEY, page, per_page],
    queryFn: () => getCitiesApi(page, per_page),
  });
};

export const useCreateCityMutationOptions = () => {
  const qClient = useQueryClient();
  return mutationOptions({
    mutationFn: createCityApi,
    onSettled: () => {
      qClient.invalidateQueries({ queryKey: [CITIES_KEY] });
    },
  });
};

export const useCityByIdQueryOptions = (id: number) => {
  return queryOptions({
    queryKey: [CITIES_KEY, id],
    queryFn: () => getCityByIdApi(id),
  });
};

export const useUpdateCityMutationOptions = () => {
  const qClient = useQueryClient();
  return mutationOptions({
    mutationFn: (data: { id: number; req: TUpdateCityRequest }) =>
      updateCityApi(data.id, data.req),
    onSettled: () => {
      qClient.invalidateQueries({ queryKey: [CITIES_KEY] });
    },
  });
};

export const useDeleteCityMutationOptions = () => {
  const qClient = useQueryClient();
  return mutationOptions({
    mutationFn: (id: number) => deleteCityApi(id),
    onSettled: () => {
      qClient.invalidateQueries({ queryKey: [CITIES_KEY] });
    },
  });
};

export const useInfiniteCitiesQueryOptions = (per_page: number) => {
  return infiniteQueryOptions({
    queryKey: [CITIES_KEY, "infinite"],
    queryFn: ({ pageParam = 1 }) => getCitiesApi(pageParam, per_page),
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

export const useExportCitiesToCSVMutationOptions = () => {
  return mutationOptions({
    mutationFn: (params?: Parameters<typeof exportCitiesToCSV>[0]) =>
      exportCitiesToCSV(params),
  });
};

export const useCitiesByCountryModuleQueryOptions = (
  countryId: number,
  switchKey?: string,
) =>
  queryOptions({
    queryKey: [CITIES_KEY, "by-country-module", countryId, switchKey],
    queryFn: () => getCitiesByCountryModule(countryId, switchKey),
    enabled: !!countryId,
    staleTime: 1000 * 60 * 5,
  });