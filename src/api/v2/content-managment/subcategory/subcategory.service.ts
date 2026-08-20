import {
  TCreateSubcategoryRequest,
  TCreateSubcategoryResponse,
  TSubcategory,
  TUpdateSubcategoryRequest,
} from "./subcategory.types";
import { api } from "@/api/api-contants";
import { populateError } from "@/api/api.utils";
import { TPaginationResponse } from "@/api/api-common.types";

type DressCategoryRow = {
  id: number;
  name: string;
  description?: string | null;
  parent_id?: number | null;
  created_at?: string;
  updated_at?: string;
  deleted_at?: string | null;
  parent?: { id: number; name: string } | null;
};

function mapChild(row: DressCategoryRow): TSubcategory {
  return {
    id: row.id,
    name: row.name,
    description: row.description || "",
    category_id: Number(row.parent_id ?? row.parent?.id ?? 0),
    created_at: row.created_at || "",
    updated_at: row.updated_at || "",
    deleted_at: row.deleted_at || "",
    category: {
      id: Number(row.parent_id ?? row.parent?.id ?? 0),
      name: row.parent?.name || "",
    } as TSubcategory["category"],
  };
}

export const createSubcategoryApi = async (req: TCreateSubcategoryRequest) => {
  try {
    const { data } = await api.post<DressCategoryRow>("/dress-categories", {
      name: req.name,
      description: req.description,
      parent_id: req.category_id,
    });
    return mapChild(data) as unknown as TCreateSubcategoryResponse;
  } catch (error: any) {
    populateError(error, "خطأ فى إنشاء قسم المنتجات الفرعي");
  }
};

export const getSubcategoriesApi = async (
  page: number,
  per_page: number,
  category_id?: number,
) => {
  try {
    const { data } = await api.get<TPaginationResponse<DressCategoryRow>>(
      "/dress-categories",
      {
        params: {
          page,
          per_page,
          only_children: 1,
          parent_id: category_id || undefined,
        },
      },
    );
    return {
      ...data,
      data: (data?.data ?? []).map(mapChild),
    } as TPaginationResponse<TSubcategory>;
  } catch (error: any) {
    populateError(error, "خطأ فى جلب أقسام المنتجات الفرعية");
  }
};

export const getSubcategoryByIdApi = async (id: number) => {
  try {
    const { data } = await api.get<DressCategoryRow>(`/dress-categories/${id}`);
    return mapChild(data);
  } catch (error: any) {
    populateError(error, "خطأ فى جلب قسم المنتجات الفرعي");
  }
};

export const updateSubcategoryApi = async (
  id: number,
  req: TUpdateSubcategoryRequest,
) => {
  try {
    const { data } = await api.put<DressCategoryRow>(`/dress-categories/${id}`, {
      name: req.name,
      description: req.description,
      parent_id: req.category_id,
    });
    return mapChild(data);
  } catch (error: any) {
    populateError(error, "خطأ فى تحديث قسم المنتجات الفرعي");
  }
};

export const deleteSubcategoryApi = async (id: number) => {
  try {
    await api.delete(`/dress-categories/${id}`);
    return true;
  } catch (error: any) {
    populateError(error, "خطأ فى حذف قسم المنتجات الفرعي");
  }
};

export const exportSubcategoriesToCSV = async (
  params?: Record<string, unknown>,
) => {
  try {
    const response = await api.get<Blob>(`/dress-categories/export`, {
      params: { ...params, only_children: 1 },
      responseType: "blob",
    });
    return { data: response.data, headers: response.headers };
  } catch (error) {
    populateError(error, "خطأ فى تصدير أقسام المنتجات الفرعية");
  }
};
