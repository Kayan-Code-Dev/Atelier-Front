import { queryOptions } from "@tanstack/react-query";
import { getCountriesByModule } from "./country.service";

export const COUNTRIES_KEY = "COUNTRIES";

export const useCountriesByModuleQueryOptions = (switchKey: string) =>
  queryOptions({
    queryKey: [COUNTRIES_KEY, "by-module", switchKey],
    queryFn: () => getCountriesByModule(switchKey),
    staleTime: 1000 * 60 * 5,
  });
