import {
  TClothesUnavailableDaysRangesResponse,
  TClothesAvailableForDateResponse,
  TClothResponse,
  TClothOrdersResponse,
  TCreateClothesRequest,
  TGetClothesRequestParams,
  TUpdateClothesRequest,
} from "./clothes.types";
import { TPaginationResponse } from "@/api/api-common.types";
import { api } from "@/api/api-contants";
import { populateError } from "@/api/api.utils";
import { TEntity } from "@/lib/types/entity.types";

export const getClothes = async (params: TGetClothesRequestParams) => {
  try {
    const { data } = await api.get<TPaginationResponse<TClothResponse>>(
      "/clothes",
      { params }
    );
    return data;
  } catch (error: any) {
    populateError(error, "خطأ فى جلب المنتجات");
  }
};

export const createClothes = async (data: TCreateClothesRequest) => {
  try {
    const { data: responseData } = await api.post<TClothResponse>(
      "/clothes",
      data
    );
    return responseData;
  } catch (error: any) {
    populateError(error, "خطأ فى إنشاء المنتجات");
  }
};

export const updateClothes = async (
  id: number,
  data: TUpdateClothesRequest
) => {
  try {
    const { data: responseData } = await api.put<TClothResponse>(
      `/clothes/${id}`,
      data
    );
    return responseData;
  } catch (error: any) {
    populateError(error, "خطأ فى تحديث المنتج");
  }
};

export const deleteClothes = async (id: number) => {
  try {
    const { data: responseData } = await api.delete<TClothResponse>(
      `/clothes/${id}`
    );
    return responseData;
  } catch (error: any) {
    populateError(error, "خطأ فى حذف المنتج");
  }
};

export const getClothesById = async (id: number) => {
  try {
    const { data: responseData } = await api.get<TClothResponse>(
      `/clothes/${id}`
    );
    return responseData;
  } catch (error: any) {
    populateError(error, "خطأ فى جلب المنتجات");
  }
};


export const getClothOrders = async (clothId: number) => {
  try {
    const { data: responseData } = await api.get<
      TClothOrdersResponse | { data: TClothOrdersResponse }
    >(`/clothes/${clothId}/orders`);
    const result =
      responseData && "data" in responseData && responseData.data
        ? responseData.data
        : (responseData as TClothOrdersResponse);
    return (
      result ?? {
        cloth_code: "",
        cloth_id: clothId,
        orders: [],
        total: 0,
      }
    );
  } catch (error: any) {
    populateError(error, "خطأ فى جلب تأجيرات واستخدامات المنتج");
  }
  return { cloth_code: "", cloth_id: clothId, orders: [], total: 0 } as TClothOrdersResponse;
};

export const getClothesAvialbelByDate = async (
  date: string,
  entity_type: TEntity,
  entity_id: number
) => {
  try {
    const { data: responseData } =
      await api.get<TClothesAvailableForDateResponse>(
        `/clothes/available-for-date`,
        { params: { delivery_date: date, entity_type, entity_id } }
      );
    return responseData;
  } catch (error: any) {
    populateError(error, "خطأ فى جلب المنتجات المتاحة للتاريخ");
  }
};

export const getClothethesUnavailableDaysRangesbyIds = async (
  ids: number[]
) => {
  try {
    const { data: responseData } =
      await api.get<TClothesUnavailableDaysRangesResponse>(
        `/clothes/unavailable-days`,
        { params: { cloth_ids: ids } }
      );
    return responseData;
  } catch (error: any) {
    populateError(error, "خطأ فى جلب المنتجات غير المتاحة للتاريخ");
  }
};


export const exportClothesToCSV = async (params?: Record<string, unknown>) => {
  try {
    const response = await api.get<Blob>(`/clothes/export`, {
      params,
      responseType: "blob",
    });
    return { data: response.data, headers: response.headers };
  } catch (error) {
    populateError(error, "خطأ فى تصدير المنتجات");
  }
};

export const importClothes = async (file: File) => {
  try {
    const formData = new FormData();
    formData.append("file", file);

    const { data } = await api.post<unknown>(`/clothes/import`, formData);
    return data;
  } catch (error: any) {
    populateError(error, "خطأ فى استيراد المنتجات");
  }
};