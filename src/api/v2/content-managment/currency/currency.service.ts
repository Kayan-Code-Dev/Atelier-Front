import { api } from "@/api/api-contants";
import { populateError } from "@/api/api.utils";
import {
  TCreateCurrencyRequest,
  TCreateCurrencyResponse,
  TCurrency,
} from "./currency.types";
import { TPaginationResponse } from "@/api/api-common.types";

type AppSettingsCurrency = {
  currency?: string;
  currency_symbol?: string;
  currency_label?: string;
};

function settingsToCurrency(settings: AppSettingsCurrency): TCurrency {
  const code = String(settings.currency || "EGP");
  return {
    id: 1,
    name: settings.currency_label || code,
    code,
    symbol: settings.currency_symbol || code,
    created_at: "",
    updated_at: "",
    deleted_at: null,
  };
}

export const createCurrencyApi = async (_req: TCreateCurrencyRequest) => {
  populateError(
    new Error("إدارة العملات تتم من إعدادات النظام"),
    "إدارة العملات تتم من إعدادات النظام",
  );
  return undefined as unknown as { data: TCreateCurrencyResponse };
};

export const getCurrenciesApi = async (page: number, per_page: number) => {
  try {
    const { data } = await api.get<AppSettingsCurrency>("/settings/app");
    const row = settingsToCurrency(data ?? {});
    return {
      data: [row],
      current_page: page || 1,
      per_page: per_page || 10,
      total: 1,
      total_pages: 1,
    } satisfies TPaginationResponse<TCurrency>;
  } catch (error: any) {
    populateError(error, "خطأ فى جلب العملات");
  }
};

export const getCurrencyByIdApi = async (_id: number) => {
  try {
    const { data } = await api.get<AppSettingsCurrency>("/settings/app");
    return settingsToCurrency(data ?? {});
  } catch (error: any) {
    populateError(error, "خطأ فى جلب العملة");
  }
};

export const updateCurrencyApi = async (
  _id: number,
  req: TCreateCurrencyRequest,
) => {
  try {
    const payload = {
      currency: (req as { code?: string }).code || (req as { name?: string }).name,
    };
    const { data } = await api.put<AppSettingsCurrency>("/settings/app", payload);
    return settingsToCurrency(data ?? {});
  } catch (error: any) {
    populateError(error, "خطأ فى تحديث العملة");
  }
};

export const deleteCurrencyApi = async (_id: number) => {
  populateError(
    new Error("لا يمكن حذف عملة النظام"),
    "لا يمكن حذف عملة النظام",
  );
  return false;
};

export const exportCurrenciesToCSV = async (_params?: Record<string, unknown>) => {
  populateError(
    new Error("تصدير العملات غير متاح"),
    "تصدير العملات غير متاح",
  );
  return undefined as
    | { data: Blob; headers: unknown }
    | undefined;
};
