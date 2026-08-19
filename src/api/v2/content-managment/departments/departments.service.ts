import { TPaginationResponse } from "@/api/api-common.types";
import {
  TCreateDepartmentRequest,
  TDepartment,
  TGetDepartmentsParams,
  TUpdateDepartmentRequest,
} from "./departments.types";
import { api } from "@/api/api-contants";
import { populateError } from "@/api/api.utils";

export const getDepartments = async (params: TGetDepartmentsParams) => {
  try {
    const { data } = await api.get<TPaginationResponse<TDepartment>>(
      `/departments`,
      { params }
    );
    return data;
  } catch (error) {
    populateError(error, "خطأ فى جلب الاقسام");
  }
};

export const createDepartment = async (data: TCreateDepartmentRequest) => {
  try {
    await api.post<TDepartment>(`/departments`, data);
  } catch (error) {
    populateError(error, "خطأ فى اضافة قسم");
  }
};

export const updateDepartment = async (
  id: number,
  data: TUpdateDepartmentRequest
) => {
  try {
    await api.put<TDepartment>(`/departments/${id}`, data);
  } catch (error) {
    populateError(error, "خطأ فى تعديل قسم");
  }
};


export const deleteDepartment = async (id: number) => {
  try {
    await api.delete<TDepartment>(`/departments/${id}`);
  } catch (error) {
    populateError(error, "خطأ فى حذف قسم");
  }
};

