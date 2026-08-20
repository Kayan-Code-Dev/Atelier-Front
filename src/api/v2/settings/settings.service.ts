import { api } from "@/api/api-contants";
import { populateError } from "@/api/api.utils";
import type { TAppSettings, TUpdateAppSettingsRequest } from "./settings.types";

type Envelope<T> = { data?: T } & T;

function unwrap<T>(payload: Envelope<T>): T {
  if (payload && typeof payload === "object" && "data" in payload && payload.data != null) {
    return payload.data as T;
  }
  return payload as T;
}

export async function getAppSettingsApi(): Promise<TAppSettings> {
  try {
    const { data } = await api.get<Envelope<TAppSettings>>("/settings/app");
    return unwrap(data);
  } catch (error: unknown) {
    populateError(error, "تعذر جلب إعدادات النظام");
    throw error;
  }
}

export async function updateAppSettingsApi(
  body: TUpdateAppSettingsRequest
): Promise<TAppSettings> {
  try {
    const { data } = await api.put<Envelope<TAppSettings>>("/settings/app", body);
    return unwrap(data);
  } catch (error: unknown) {
    populateError(error, "تعذر حفظ إعدادات النظام");
    throw error;
  }
}
