import { api } from "@/api/api-contants";
import { populateError } from "@/api/api.utils";
import {
  TCreateCurrencyRequest,
  TCreateCurrencyResponse,
  TCurrency,
} from "./currency.types";
import { TPaginationResponse } from "@/api/api-common.types";

export const createCurrencyApi = async (req: TCreateCurrencyRequest) => {
  try {
    const { data } = await api.post<{ data: TCreateCurrencyResponse }>(
      "/currencies",
      req
    );
    return data;
  } catch (error: any) {
    populateError(error, "خطأ فى إنشاء العملة");
  }
};

export const getCurrenciesApi = async (page: number, per_page: number) => {
  try {
    const { data } = await api.get<TPaginationResponse<TCurrency>>(
      "/currencies",
      { params: { page, per_page } }
    );
    return data;
  } catch (error: any) {
    populateError(error, "خطأ فى جلب العملات");
  }
};

export const getCurrencyByIdApi = async (id: number) => {
  try {
    const { data } = await api.get<{ data: TCurrency }>(`/currencies/${id}`);
    return data.data;
  } catch (error: any) {
    populateError(error, "خطأ فى جلب العملة");
  }
};

export const updateCurrencyApi = async (
  id: number,
  req: TCreateCurrencyRequest
) => {
  try {
    const { data } = await api.put<{ data: TCurrency }>(
      `/currencies/${id}`,
      req
    );
    return data.data;
  } catch (error: any) {
    populateError(error, "خطأ فى تحديث العملة");
  }
};

export const deleteCurrencyApi = async (id: number) => {
  try {
    await api.delete(`/currencies/${id}`);
    return true;
  } catch (error: any) {
    populateError(error, "خطأ فى حذف العملة");
  }
};

export const exportCurrenciesToCSV = async (params?: Record<string, unknown>) => {
  try {
    const response = await api.get<Blob>(`/currencies/export`, {
      params,
      responseType: "blob",
    });
    return { data: response.data, headers: response.headers };
  } catch (error) {
    populateError(error, "خطأ فى تصدير العملات");
  }
};

