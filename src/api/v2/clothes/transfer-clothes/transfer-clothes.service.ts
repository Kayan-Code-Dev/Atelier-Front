import { TPaginationResponse } from "@/api/api-common.types";
import { api } from "@/api/api-contants";
import { populateError } from "@/api/api.utils";
import {
  CreateTransferClothesRequest,
  TGetTransferClothesQuery,
  TTransferClothesItem,
  TUpdateTransferClothesRequest,
} from "./transfer-clothes.types";

export const createTransferClothes = async (
  data: CreateTransferClothesRequest
) => {
  try {
    await api.post(`/transfers`, data);
  } catch (error) {
    populateError(error, "خطأ فى ارسال الطلب ");
  }
};

export const getTransferClothes = async (query: TGetTransferClothesQuery) => {
  try {
    const { data } = await api.get<TPaginationResponse<TTransferClothesItem>>(
      `/transfers`,
      {
        params: {
          page: query.page,
          per_page: query.per_page,
          status: query.status,
        },
      }
    );
    return data;
  } catch (error) {
    populateError(error, "خطأ فى جلب الطلبات ");
  }
};

export const updateTransferClothes = async (
  id: number,
  data: TUpdateTransferClothesRequest
) => {
  try {
    await api.put(`/transfers/${id}`, data);
  } catch (error) {
    populateError(error, "خطأ فى تحديث الطلب ");
  }
};

export const deleteTransferClothes = async (id: number) => {
  try {
    await api.delete(`/transfers/${id}`);
  } catch (error) {
    populateError(error, "خطأ فى حذف الطلب ");
  }
};

export const approveTransferClothes = async (id: number) => {
  try {
    await api.post(`/transfers/${id}/approve`);
  } catch (error) {
    populateError(error, "خطأ فى قبول الطلب ");
  }
};

export const rejectTransferClothes = async (id: number) => {
  try {
    await api.post(`/transfers/${id}/reject`);
  } catch (error) {
    populateError(error, "خطأ فى رفض الطلب ");
  }
};

export const getTransferClotheById = async (id: number) => {
  try {
    const { data } = await api.get<TTransferClothesItem>(`/transfers/${id}`);
    return data;
  } catch (error) {
    populateError(error, "خطأ فى جلب الطلب ");
  }
};

export const approvePartialTransferClothes = async (
  id: number,
  item_ids: number[]
) => {
  try {
    await api.post(`/transfers/${id}/approve-items`, { item_ids });
  } catch (error) {
    populateError(error, "خطأ فى قبول الطلب ");
  }
};

export const rejectPartialTransferClothes = async (
  id: number,
  item_ids: number[]
) => {
  try {
    await api.post(`/transfers/${id}/reject-items`, { item_ids });
  } catch (error) {
    populateError(error, "خطأ فى رفض الطلب ");
  }
};

export const exportTransferClothesToCSV = async (params?: Record<string, unknown>) => {
  try {
    const response = await api.get<Blob>(`/transfers/export`, {
      params,
      responseType: "blob",
    });
    return { data: response.data, headers: response.headers };
  } catch (error) {
    populateError(error, "خطأ فى تصدير طلبات التحويل ");
  }
};