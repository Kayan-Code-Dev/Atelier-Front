import { TPaginationResponse } from "@/api/api-common.types";
import {
  TCreateJobTitleRequest,
  TGetJobTitlesParams,
  TJobTitle,
  TJobTitleLevel,
  TUpdateJobTitleRequest,
} from "./job-titles.types";
import { api } from "@/api/api-contants";
import { populateError } from "@/api/api.utils";
import { TRole } from "../roles/roles.types";

export const getJobTitles = async (params: TGetJobTitlesParams) => {
  try {
    const { data } = await api.get<TPaginationResponse<TJobTitle>>(
      "/job-titles",
      {
        params,
      }
    );
    return data;
  } catch (error) {
    populateError(error, "خطأ فى جلب قائمة المسميات الوظيفية");
  }
};

export const createJobTitle = async (data: TCreateJobTitleRequest) => {
  try {
    const { data: response } = await api.post<TJobTitle>("/job-titles", data);
    return response;
  } catch (error) {
    populateError(error, "خطأ فى إنشاء المسمية الوظيفية");
  }
};

export const updateJobTitle = async (
  id: number,
  data: TUpdateJobTitleRequest
) => {
  try {
    const { data: response } = await api.put<TJobTitle>(
      `/job-titles/${id}`,
      data
    );
    return response;
  } catch (error) {
    populateError(error, "خطأ فى تحديث المسمية الوظيفية");
  }
};

export const deleteJobTitle = async (id: number) => {
  try {
    await api.delete(`/job-titles/${id}`);
  } catch (error) {
    populateError(error, "خطأ فى حذف المسمية الوظيفية");
  }
};

export const getJobTitle = async (id: number) => {
  try {
    const { data: response } = await api.get<TJobTitle>(`/job-titles/${id}`);
    return response;
  } catch (error) {
    populateError(error, "خطأ فى جلب المسمية الوظيفية");
  }
};

export const getJobTitleLevels = async () => {
  try {
    const { data: response } = await api.get<{ levels: TJobTitleLevel[] }>(
      "/job-titles/levels"
    );
    return response.levels;
  } catch (error) {
    populateError(error, "خطأ فى جلب قائمة المستويات الوظيفية");
  }
};

export const getJobTitleRoles = async (id: number) => {
  try {
    const { data: response } = await api.get<{ roles: TRole[] }>(
      `/job-titles/${id}/roles`
    );
    return response.roles;
  } catch (error) {
    populateError(error, "خطأ فى جلب قائمة الصلاحيات الوظيفية");
  }
};

export const syncJobTitleRoles = async (id: number, role_ids: number[]) => {
  try {
    await api.put(`/job-titles/${id}/roles/sync`, { role_ids });
  } catch (error) {
    populateError(error, "خطأ فى تحديث قائمة الصلاحيات الوظيفية");
  }
};
