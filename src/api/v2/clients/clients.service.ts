import { TPaginationResponse } from "@/api/api-common.types";
import { api } from "@/api/api-contants";
import { populateError } from "@/api/api.utils";
import {
  TCreateClientRequest,
  TClientResponse,
  TUpdateClientRequest,
} from "./clients.types";

/** Optional filters for GET /api/v1/clients (index) */
export type TGetClientsParams = {
  search?: string;
  id?: number | string;
  address_id?: number;
  source?: string;
  date_of_birth_from?: string;
  date_of_birth_to?: string;
};

export const getClients = async (
  page: number,
  per_page: number,
  params?: TGetClientsParams
) => {
  try {
    const query: Record<string, string | number | undefined> = {
      page,
      per_page,
      ...(params?.search != null && params.search !== ""
        ? { search: params.search }
        : {}),
      ...(params?.id != null ? { id: params.id } : {}),
      ...(params?.address_id != null ? { address_id: params.address_id } : {}),
      ...(params?.source != null && params.source !== ""
        ? { source: params.source }
        : {}),
      ...(params?.date_of_birth_from
        ? { date_of_birth_from: params.date_of_birth_from }
        : {}),
      ...(params?.date_of_birth_to
        ? { date_of_birth_to: params.date_of_birth_to }
        : {}),
    };
    const { data } = await api.get<TPaginationResponse<TClientResponse>>(
      "/clients",
      { params: query }
    );
    return data;
  } catch (error) {
    populateError(error, "خطأ فى جلب العملاء");
  }
};

export const createClient = async (data: TCreateClientRequest) => {
  try {
    const { data: response } = await api.post<TClientResponse>(
      "/clients",
      data
    );
    return response;
  } catch (error) {
    populateError(error, "خطأ فى إنشاء العميل");
  }
};

export const updateClient = async (id: number, data: TUpdateClientRequest) => {
  try {
    const { data: response } = await api.put<TClientResponse>(
      `/clients/${id}`,
      data
    );
    return response;
  } catch (error) {
    populateError(error, "خطأ فى تحديث العميل");
  }
};

export const deleteClient = async (id: number) => {
  try {
    await api.delete<TClientResponse>(`/clients/${id}`);
  } catch (error) {
    populateError(error, "خطأ فى حذف العميل");
  }
};

export const getClient = async (id: number) => {
  try {
    const { data: response } = await api.get<TClientResponse>(`/clients/${id}`);
    return response;
  } catch (error) {
    populateError(error, "خطأ فى جلب العميل");
  }
};

export const exportClientsToCSV = async (params?: Record<string, unknown>) => {
  try {
    const response = await api.get<Blob>(`/clients/export`, {
      params,
      responseType: "blob",
    });
    return { data: response.data, headers: response.headers };
  } catch (error) {
    populateError(error, "خطأ فى تصدير العملاء");
  }
};
