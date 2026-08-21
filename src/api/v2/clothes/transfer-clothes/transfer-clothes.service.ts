import { TPaginationResponse } from "@/api/api-common.types";
import { api } from "@/api/api-contants";
import { populateError } from "@/api/api.utils";
import {
  CreateTransferClothesRequest,
  TGetTransferClothesQuery,
  TTransferClothesItem,
  TUpdateTransferClothesRequest,
} from "./transfer-clothes.types";

const emptyTransfersPage = (
  query: TGetTransferClothesQuery = {},
): TPaginationResponse<TTransferClothesItem> => ({
  data: [],
  current_page: query.page ?? 1,
  per_page: query.per_page ?? 15,
  total: 0,
  total_pages: 1,
});

/**
 * Atelier transfers a dress immediately to another branch:
 * POST /dresses/{id}/transfer { to_branch_id, notes }
 * There is no pending request/approve collection at /transfers.
 */
export const createTransferClothes = async (
  data: CreateTransferClothesRequest,
) => {
  if (data.to_entity_type !== "branch") {
    throw new Error("نقل المنتجات متاح بين الفروع فقط");
  }
  if (!data.cloth_ids?.length) {
    throw new Error("اختر منتجاً للنقل");
  }
  try {
    for (const clothId of data.cloth_ids) {
      await api.post(`/dresses/${clothId}/transfer`, {
        to_branch_id: data.to_entity_id,
        notes: data.notes ?? null,
      });
    }
  } catch (error) {
    populateError(error, "خطأ فى نقل المنتج");
  }
};

export const getTransferClothes = async (query: TGetTransferClothesQuery) => {
  // No collection endpoint in Atelier tenant API. Keep the page empty instead of 404.
  return emptyTransfersPage(query);
};

export const updateTransferClothes = async (
  _id: number,
  _data: TUpdateTransferClothesRequest,
) => {
  throw new Error("النقل يتم فوراً ولا يحتاج تعديلاً لاحقاً");
};

export const deleteTransferClothes = async (_id: number) => {
  throw new Error("النقل يتم فوراً ولا يمكن حذفه كطلب معلّق");
};

export const approveTransferClothes = async (_id: number) => {
  throw new Error("النقل يتم فوراً ولا يحتاج موافقة");
};

export const rejectTransferClothes = async (_id: number) => {
  throw new Error("النقل يتم فوراً ولا يحتاج رفضاً");
};

export const getTransferClotheById = async (
  _id: number,
): Promise<TTransferClothesItem | undefined> => {
  return undefined;
};

export const approvePartialTransferClothes = async (
  _id: number,
  _item_ids: number[],
) => {
  throw new Error("النقل يتم فوراً ولا يحتاج موافقة جزئية");
};

export const rejectPartialTransferClothes = async (
  _id: number,
  _item_ids: number[],
) => {
  throw new Error("النقل يتم فوراً ولا يحتاج رفضاً جزئياً");
};

export const exportTransferClothesToCSV = async (
  _params?: Record<string, unknown>,
): Promise<{ data: Blob; headers: unknown } | undefined> => {
  throw new Error("تصدير سجل النقل غير متاح حالياً");
};
