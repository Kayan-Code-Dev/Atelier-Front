import { populateError } from "@/api/api.utils";
import {
  TCategory,
  TCreateCategoryRequest,
  TCreateCategoryResponse,
  TUpdateCategoryRequest,
} from "./category.type";
import { api } from "@/api/api-contants";
import { TPaginationResponse } from "@/api/api-common.types";

export const createCategoryApi = async (req: TCreateCategoryRequest) => {
  try {
    const { data } = await api.post<TCreateCategoryResponse>(
      "/categories",
      req
    );
    return data;
  } catch (error: any) {
    populateError(error, "خطأ فى إنشاء قسم المنتجات");
  }
};

export const getCategoriesApi = async (page: number, per_page: number) => {
  try {
    const { data } = await api.get<TPaginationResponse<TCategory>>(
      "/categories",
      { params: { page, per_page } }
    );
    return data;
  } catch (error: any) {
    populateError(error, "خطأ فى جلب أقسام المنتجات");
  }
};

export const getCategoryByIdApi = async (id: number) => {
  try {
    const { data } = await api.get<TCategory>(`/categories/${id}`);
    return data;
  } catch (error: any) {
    populateError(error, "خطأ فى جلب قسم المنتجات");
  }
};

export const updateCategoryApi = async (
  id: number,
  req: TUpdateCategoryRequest
) => {
  try {
    const { data } = await api.put<TCategory>(
      `/categories/${id}`,
      req
    );
    return data;
  } catch (error: any) {
    populateError(error, "خطأ فى تحديث قسم المنتجات");
  }
};

export const deleteCategoryApi = async (id: number) => {
  try {
    await api.delete(`/categories/${id}`);
    return true;
  } catch (error: any) {
    populateError(error, "خطأ فى حذف قسم المنتجات");
  }
};

export const exportCategoriesToCSV = async (params?: Record<string, unknown>) => {
  try {
    const response = await api.get<Blob>(`/categories/export`, {
      params,
      responseType: "blob",
    });
    return { data: response.data, headers: response.headers };
  } catch (error) {
    populateError(error, "خطأ فى تصدير أقسام المنتجات");
  }
};