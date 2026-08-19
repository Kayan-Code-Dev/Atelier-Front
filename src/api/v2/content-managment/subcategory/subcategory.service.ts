import {
  TCreateSubcategoryRequest,
  TCreateSubcategoryResponse,
  TSubcategory,
  TUpdateSubcategoryRequest,
} from "./subcategory.types";
import { api } from "@/api/api-contants";
import { populateError } from "@/api/api.utils";
import { TPaginationResponse } from "@/api/api-common.types";

export const createSubcategoryApi = async (req: TCreateSubcategoryRequest) => {
  try {
    const { data } = await api.post<TCreateSubcategoryResponse>(
      "/subcategories",
      req
    );
    return data;
  } catch (error: any) {
    populateError(error, "خطأ فى إنشاء قسم المنتجات الفرعي");
  }
};

export const getSubcategoriesApi = async (page: number, per_page: number, category_id?: number) => {
  try {
    const { data } = await api.get<TPaginationResponse<TSubcategory>>(
      "/subcategories",
      { params: { page, per_page, category_id } }
    );
    return data;
  } catch (error: any) {
    populateError(error, "خطأ فى جلب أقسام المنتجات الفرعية");
  }
};

export const getSubcategoryByIdApi = async (id: number) => {
  try {
    const { data } = await api.get<TSubcategory>(`/subcategories/${id}`);
    return data;
  } catch (error: any) {
    populateError(error, "خطأ فى جلب قسم المنتجات الفرعي");
  }
};

export const updateSubcategoryApi = async (
  id: number,
  req: TUpdateSubcategoryRequest
) => {
  try {
    const { data } = await api.put<TSubcategory>(`/subcategories/${id}`, req);
    return data;
  } catch (error: any) {
    populateError(error, "خطأ فى تحديث قسم المنتجات الفرعي");
  }
};

export const deleteSubcategoryApi = async (id: number) => {
  try {
    await api.delete(`/subcategories/${id}`);
    return true;
  } catch (error: any) {
    populateError(error, "خطأ فى حذف قسم المنتجات الفرعي");
  }
};


export const exportSubcategoriesToCSV = async (params?: Record<string, unknown>) => {
  try {
    const response = await api.get<Blob>(`/subcategories/export`, {
      params,
      responseType: "blob",
    });
    return { data: response.data, headers: response.headers };
  } catch (error) {
    populateError(error, "خطأ فى تصدير أقسام المنتجات الفرعية");
  }
};