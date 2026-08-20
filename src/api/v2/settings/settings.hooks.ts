import { mutationOptions, queryOptions } from "@tanstack/react-query";
import { getAppSettingsApi, updateAppSettingsApi } from "./settings.service";
import type { TUpdateAppSettingsRequest } from "./settings.types";

export const APP_SETTINGS_KEY = ["app-settings"] as const;

export function useAppSettingsQueryOptions() {
  return queryOptions({
    queryKey: APP_SETTINGS_KEY,
    queryFn: getAppSettingsApi,
    staleTime: 30_000,
  });
}

export function useUpdateAppSettingsMutationOptions() {
  return mutationOptions({
    mutationFn: (body: TUpdateAppSettingsRequest) => updateAppSettingsApi(body),
  });
}
