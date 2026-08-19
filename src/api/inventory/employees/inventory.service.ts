import { TPaginationResponse } from "@/api/api-common.types";
import { api } from "@/api/api-contants";
import { populateError } from "@/api/api.utils";
import {
  TEmployeeInvantoryItem,
  TInventoryTransfersItem,
} from "../inventory.types";

export const getEmployeesInventory = async (page: number) => {
  try {
    const { data } = await api.get<{
      data: TPaginationResponse<TEmployeeInvantoryItem>;
    }>(`/employees/inventories`, { params: { page } });
    data.data.total_pages = Math.ceil(data.data.total / 10);
    return data.data;
  } catch (error) {
    populateError(error, "خطأ فى جلب مخزون الموظف");
  }
};

export type TCreateEmployeesInventoryRequest = {
  name: string;
  category_id: string;
  subCategories_id: string;
  price: number;
  type: string;
  notes: string;
  quantity: number;
};

export const createEmployeesInventory = async (
  data: TCreateEmployeesInventoryRequest
) => {
  try {
    await api.post(`/employees/inventories`, data);
  } catch (error) {
    populateError(error, "خطأ فى ارسال الطلب ");
  }
};

export type TUpdateEmployeesInventoryRequest = {
  code: string;
} & Partial<TCreateEmployeesInventoryRequest>;

export const updateEmployeesInventory = async (
  data: TUpdateEmployeesInventoryRequest,
  id: number
) => {
  try {
    await api.put(`/employees/inventories/${id}`, data);
  } catch (error) {
    populateError(error, "خطأ فى ارسال الطلب ");
  }
};

export const deleteEmployeesInventory = async (id: number) => {
  try {
    await api.delete(`/employees/inventories/${id}`);
  } catch (error) {
    populateError(error, "خطأ فى ارسال الطلب ");
  }
};

export type TCreateEmployeeInventoryTransferRequest = {
  to_branch_id: string;
  quantity: number;
  category_id: string;
  subCategories_id: string;
  notes?: string;
};

export const createEmployeeInventoryTransfer = async (
  data: TCreateEmployeeInventoryTransferRequest
) => {
  try {
    await api.post(`/employees/inventory_transfers`, data);
  } catch (error) {
    populateError(error, "خطأ فى ارسال الطلب ");
  }
};

export const getEmployeeInventoryTransfers = async (page: number) => {
  try {
    const { data } = await api.get<{
      data: TPaginationResponse<TInventoryTransfersItem>;
    }>(`/employees/inventory_transfers`, { params: { page } });
    data.data.total_pages = Math.ceil(data.data.total / 10);
    return data.data;
  } catch (error) {
    populateError(error, "خطأ فى جلب مخزون الموظف");
  }
};

export const approveEmployeeInventoryTransfer = async (id: string) => {
  try {
    await api.post(`/employees/inventory_transfers/${id}/approve`);
  } catch (error) {
    populateError(error, "خطأ فى ارسال الطلب ");
  }
};

export const rejectEmployeeInventoryTransfer = async (id: string) => {
  try {
    await api.post(`/employees/inventory_transfers/${id}/reject`);
  } catch (error) {
    populateError(error, "خطأ فى ارسال الطلب ");
  }
};
