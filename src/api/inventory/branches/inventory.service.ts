import { TPaginationResponse } from "@/api/api-common.types";
import { api } from "@/api/api-contants";
import { populateError } from "@/api/api.utils";
import {
  TBranchInventoryItem,
  TInventoryTransfersItem,
} from "../inventory.types";
import { TListItem } from "@/api/branches-manager/employees/employees.types";

export const getBranchesInventory = async (page: number) => {
  try {
    const { data } = await api.get<{
      data: TPaginationResponse<TBranchInventoryItem>;
    }>(`/branches/inventories`, { params: { page } });
    data.data.total_pages = Math.ceil(data.data.total / 10);
    return data.data;
  } catch (error) {
    populateError(error, "خطأ فى جلب مخزون الفرع");
  }
};

export const getBranchesInventoryCategories = async () => {
  try {
    const { data } = await api.get<{
      data: TListItem[];
    }>(`/branches/inventories/ge_category`);
    return data.data;
  } catch (error) {
    populateError(error, "خطأ فى جلب ال categories ");
  }
};

export const getBranchesInventorySubCategoriesByCategory = async (
  categoryId: number
) => {
  try {
    const { data } = await api.get<{
      data: TListItem[];
    }>(`/branches/inventories/get_sub_category_by_categories/${categoryId}`);
    return data.data;
  } catch (error) {
    populateError(error, "خطأ فى جلب ال sub categories ");
  }
};

export type TCreateBranchesInventoryRequest = {
  name: string;
  category_id: number;
  subCategories_id: number;
  price: number;
  type: string;
  notes: string;
  quantity: number;
};

export const createBranchesInventory = async (
  data: TCreateBranchesInventoryRequest
) => {
  try {
    await api.post(`/branches/inventories`, data);
  } catch (error) {
    populateError(error, "خطأ فى ارسال الطلب ");
  }
};

export type TUpdateBranchesInventoryRequest = {
  code: string;
} & Partial<TCreateBranchesInventoryRequest>;

export const updateBranchesInventory = async (
  data: TUpdateBranchesInventoryRequest,
  id: number
) => {
  try {
    await api.put(`/branches/inventories/${id}`, data);
  } catch (error) {
    populateError(error, "خطأ فى ارسال الطلب ");
  }
};

export const deleteBranchesInventory = async (id: number) => {
  try {
    await api.delete(`/branches/inventories/${id}`);
  } catch (error) {
    populateError(error, "خطأ فى ارسال الطلب ");
  }
};

export type TCreateBranchInventoryTransferRequest = {
  to_branch_id: number;
  quantity: number;
  category_id: number;
  subCategories_id: number;
  notes?: string;
};

export const createBranchInventoryTransfer = async (
  data: TCreateBranchInventoryTransferRequest
) => {
  try {
    await api.post(`/branches/inventory_transfers`, data);
  } catch (error) {
    populateError(error, "خطأ فى ارسال الطلب ");
  }
};

export const getBranchInventoryTransfers = async (page: number) => {
  try {
    const { data } = await api.get<{
      data: TPaginationResponse<TInventoryTransfersItem>;
    }>(`/branches/inventory_transfers`, { params: { page } });
    data.data.total_pages = Math.ceil(data.data.total / 10);
    return data.data;
  } catch (error) {
    populateError(error, "خطأ فى جلب مخزون الفرع");
  }
};

export const getBranchesInventoryTransferBranches = async () => {
  try {
    const { data } = await api.get<{ data: TListItem[] }>(
      `branches/inventories/get_branches`
    );
    return data.data;
  } catch (error) {
    populateError(error, "خطأ فى جلب الفروع ");
  }
};

export const approveBranchInventoryTransfer = async (id: string) => {
  try {
    await api.post(`/branches/inventory_transfers/${id}/approve`);
  } catch (error) {
    populateError(error, "خطأ فى ارسال الطلب ");
  }
};

export const rejectBranchInventoryTransfer = async (id: string) => {
  try {
    await api.post(`/branches/inventory_transfers/${id}/reject`);
  } catch (error) {
    populateError(error, "خطأ فى ارسال الطلب ");
  }
};
