import { TPaginationResponse } from "../../api-common.types";
import { api } from "../../api-contants";
import { populateError } from "../../api.utils";
import { TListItem } from "../../branches-manager/employees/employees.types";
import { TInventoryItem, TInventoryTransfersItem } from "../inventory.types";

export const getInventoryItems = async (page: number) => {
  try {
    const { data } = await api.get<{
      data: TPaginationResponse<TInventoryItem>;
    }>(`/branch_managers/inventories`, {
      params: {
        page,
      },
    });
    data.data.total_pages = Math.ceil(data.data.total / 10);
    return data.data;
  } catch (error) {
    populateError(error, "خطأ فى جلب المخزن ");
  }
};

export const getInventoryTransfers = async (page: number) => {
  try {
    const { data } = await api.get<{
      data: TPaginationResponse<TInventoryTransfersItem>;
    }>(`/branch_managers/inventory_transfers`, {
      params: {
        page,
      },
    });
    data.data.total_pages = Math.ceil(data.data.total / 10);
    return data.data;
  } catch (error) {
    populateError(error, "خطأ فى جلب المخزن ");
  }
};

export const getInventoryBranches = async () => {
  try {
    const { data } = await api.get<{
      data: TListItem[];
    }>(`/branch_managers/inventory_transfers/get_branches`);
    return data.data;
  } catch (error) {
    populateError(error, "خطأ فى جلب الفروع ");
  }
};

export const getInventoryCategoriesByBranch = async (branchId: number) => {
  try {
    const { data } = await api.get<{
      data: TListItem[];
    }>(`/branch_managers/inventory_transfers/ge_category/${branchId}`);
    return data.data;
  } catch (error) {
    populateError(error, "خطأ فى جلب ال categories ");
  }
};

export const getInventorySubCategoriesByCategory = async (categoryId: number) => {
  try {
    const { data } = await api.get<{
      data: TListItem[];
    }>(
      `/branch_managers/inventory_transfers/get_sub_category_by_categories/${categoryId}`
    );
    return data.data;
  } catch (error) {
    populateError(error, "خطأ فى جلب ال sub categories ");
  }
};

export type TCreateTransferRequest = {
  from_branch_id: string;
  to_branch_id: string;
  quantity: string;
  category_id: string;
  subCategories_id: string;
  notes?: string;
};

export const createInventoryTransfer = async (data: any) => {
  try {
    await api.post(`/branch_managers/inventory_transfers`, data);
  } catch (error) {
    populateError(error, "خطأ فى ارسال الطلب ");
  }
};

export const approveInventoryTransfer = async (uuid: string) => {
  try {
    await api.post(`/branch_managers/inventory_transfers/${uuid}/approve`);
  } catch (error) {
    populateError(error, "خطأ فى قبول الطلب ");
  }
};

export const rejectInventoryTransfer = async (uuid: string) => {
  try {
    await api.post(`/branch_managers/inventory_transfers/${uuid}/reject`);
  } catch (error) {
    populateError(error, "خطأ فى رفض الطلب ");
  }
};
