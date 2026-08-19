import {
  TCreateFactoryRequest,
  TFactoryResponse,
  TUpdateFactoryRequest,
} from "./factories.types";
import { api } from "@/api/api-contants";
import { populateError } from "@/api/api.utils";
import { TPaginationResponse } from "@/api/api-common.types";

export const createFactory = async (data: TCreateFactoryRequest) => {
  try {
    const { data: response } = await api.post<TFactoryResponse>(
      "/factories",
      data
    );
    return response;
  } catch (error) {
    populateError(error, "خطأ فى إنشاء المصنع");
  }
};

export const updateFactory = async (
  id: number,
  data: TUpdateFactoryRequest
) => {
  try {
    const { data: response } = await api.put<TFactoryResponse>(
      `/factories/${id}`,
      data
    );
    return response;
  } catch (error) {
    populateError(error, "خطأ فى تحديث المصنع");
  }
};

export const getFactories = async (page: number, per_page: number) => {
  try {
    const { data: response } = await api.get<
      TPaginationResponse<TFactoryResponse>
    >(`/factories`, { params: { page, per_page } });
    return response;
  } catch (error) {
    populateError(error, "خطأ فى جلب المصانع");
  }
};

export const getFactory = async (id: number) => {
  try {
    const { data: response } = await api.get<TFactoryResponse>(
      `/factories/${id}`
    );
    return response;
  } catch (error) {
    populateError(error, "خطأ فى جلب المصنع");
  }
};

export const deleteFactory = async (id: number) => {
  try {
    const { data: response } = await api.delete<TFactoryResponse>(
      `/factories/${id}`
    );
    return response;
  } catch (error) {
    populateError(error, "خطأ فى حذف المصنع");
  }
};


export const exportFactoriesToCSV = async (params?: Record<string, unknown>) => {
  try {
    const response = await api.get<Blob>(`/factories/export`, {
      params,
      responseType: "blob",
    });
    return { data: response.data, headers: response.headers };
  } catch (error) {
    populateError(error, "خطأ فى تصدير المصانع");
  }
};