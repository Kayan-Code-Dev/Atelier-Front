import { populateError } from "@/api/api.utils";
import {
  TCreateEmployeeCustody,
  TEmployeeCustody,
  TEmployeeCustodyConditionOnAssignment,
  TEmployeeCustodyType,
  TGetEmployeeCustodiesParams,
  TUpdateEmployeeCustody,
} from "./employee-custodies.types";
import { api } from "@/api/api-contants";
import { TPaginationResponse } from "@/api/api-common.types";

export const createEmployeeCustody = async (data: TCreateEmployeeCustody) => {
  try {
    const { data: responseData } = await api.post<TEmployeeCustody>(
      "/employees/custodies",
      data
    );
    return responseData;
  } catch (error) {
    populateError(error, "خطأ فى إنشاء الضمان");
  }
};

export const getAllEmployeeCustodies = async (
  params: TGetEmployeeCustodiesParams
) => {
  try {
    const { data: responseData } = await api.get<
      TPaginationResponse<TEmployeeCustody>
    >("/employees/custodies", { params });
    return responseData;
  } catch (error) {
    populateError(error, "خطأ فى جلب الضمانات");
  }
};

export const getEmployeeCustodyById = async (id: number) => {
  try {
    const { data: responseData } = await api.get<TEmployeeCustody>(
      `/employees/custodies/${id}`
    );
    return responseData;
  } catch (error) {
    populateError(error, "خطأ فى جلب الضمان");
  }
};

export const updateEmployeeCustody = async (
  id: number,
  data: TUpdateEmployeeCustody
) => {
  try {
    const { data: responseData } = await api.put<TEmployeeCustody>(
      `/employees/custodies/${id}`,
      data
    );
    return responseData;
  } catch (error) {
    populateError(error, "خطأ فى تحديث الضمان");
  }
};

export const deleteEmployeeCustody = async (id: number) => {
  try {
    await api.delete(`/employees/custodies/${id}`);
  } catch (error) {
    populateError(error, "خطأ فى حذف الضمان");
  }
};

export const markEmployeeCustodyAsReturned = async (
  id: number,
  data: {
    condition_on_return: TEmployeeCustodyConditionOnAssignment;
    return_notes: string;
  }
) => {
  try {
    await api.post(`/employees/custodies/${id}/return`, data);
  } catch (error) {
    populateError(error, "خطأ فى تحديث حالة الضمان");
  }
};

export const markEmployeeCustodyAsLost = async (id: number, notes: string) => {
  try {
    await api.post(`/employees/custodies/${id}/mark-lost`, { notes });
  } catch (error) {
    populateError(error, "خطأ فى تحديث حالة الضمان");
  }
};

export const markEmployeeCustodyAsDamaged = async (
  id: number,
  notes: string
) => {
  try {
    await api.post(`/employees/custodies/${id}/mark-damaged`, { notes });
  } catch (error) {
    populateError(error, "خطأ فى تحديث حالة الضمان");
  }
};

export const getEmployeeCustodyTypes = async () => {
  try {
    const { data: responseData } = await api.get<{
      types: TEmployeeCustodyType[];
    }>("/employees/custodies/types");
    return responseData.types;
  } catch {
    // Atelier tenants may not expose custody type catalog yet.
    return [] as TEmployeeCustodyType[];
  }
};

