import { api } from "@/api/api-contants";
import {
  TCreateWorkshopRequest,
  TUpdateWorkshopRequest,
  TworkshopCloth,
  TWorkshopClothHistory,
  TWorkshopClothStatus,
  TWorkshopResponse,
  TWorkshopTransfer,
} from "./workshop.types";
import { populateError } from "@/api/api.utils";
import { TPaginationResponse } from "@/api/api-common.types";

export const createWorkshop = async (data: TCreateWorkshopRequest) => {
  try {
    const { data: response } = await api.post<TWorkshopResponse>(
      "/workshops",
      data
    );
    return response;
  } catch (error) {
    populateError(error, "خطأ فى إنشاء الورشة");
  }
};

export const updateWorkshop = async (
  id: number,
  data: TUpdateWorkshopRequest
) => {
  try {
    const { data: response } = await api.put<TWorkshopResponse>(
      `/workshops/${id}`,
      data
    );
    return response;
  } catch (error) {
    populateError(error, "خطأ فى تحديث الورشة");
  }
};

export const getWorkshops = async (page: number, per_page: number, status?: TWorkshopClothStatus) => {
  try {
    const { data: response } = await api.get<
      TPaginationResponse<TWorkshopResponse>
    >(`/workshops`, { params: { page, per_page, status } });
    return response;
  } catch (error) {
    populateError(error, "خطأ فى جلب الورش");
  }
};

export const getWorkshop = async (id: number) => {
  try {
    const { data: response } = await api.get<TWorkshopResponse>(
      `/workshops/${id}`
    );
    return response;
  } catch (error) {
    populateError(error, "خطأ فى جلب الورشة");
  }
};

export const deleteWorkshop = async (id: number) => {
  try {
    const { data: response } = await api.delete<TWorkshopResponse>(
      `/workshops/${id}`
    );
    return response;
  } catch (error) {
    populateError(error, "خطأ فى حذف الورشة");
  }
};

// Workshop Cloths management

export const getWorkshopCloths = async (workshop_id: number, page: number, per_page: number, status?: TWorkshopClothStatus) => {
  try {
    const { data: response } = await api.get<
      TPaginationResponse<TworkshopCloth>
    >(`/workshops/${workshop_id}/clothes`, { params: { page, per_page, status } });
    return response;
  } catch (error) {
    populateError(error, "خطأ فى جلب المنتجات");
  }
};

export const getWorkshopTransfers = async (workshop_id: number, page: number, per_page: number) => {
  try {
    const { data: response } = await api.get<
      TPaginationResponse<TWorkshopTransfer>
    >(`/workshops/${workshop_id}/pending-transfers`, { params: { page, per_page } });
    return response;
  } catch (error) {
    populateError(error, "خطأ فى جلب الطلبات المعلقة");
  }
};

export const approveWorkshopTransfer = async (
  workshop_id: number,
  transfer_id: number,
  items: number[]
) => {
  try {
    await api.post(`/workshops/${workshop_id}/approve-transfer/${transfer_id}`, {
      items,
    });
  } catch (error) {
    populateError(error, "خطأ فى قبول الطلب");
  }
};

export const updateWorkshopClothStatus = async (
  workshop_id: number,
  cloth_id: number,
  status: TWorkshopClothStatus,
  notes: string
) => {
  try {
    await api.post(`/workshops/${workshop_id}/update-cloth-status`, {
      status,
      notes,
      cloth_id,
    });
  } catch (error) {
    populateError(error, "خطأ فى تحديث حالة المنتج");
  }
};

export const returnWorkshopCloth = async (
  workshop_id: number,
  cloth_id: number,
  notes: string
) => {
  try {
    await api.post(`/workshops/${workshop_id}/return-cloth`, {
      cloth_id,
      notes,
    });
  } catch (error) {
    populateError(error, "خطأ فى إعادة المنتج");
  }
};

export const getWorkshopClothHistory = async (
  workshop_id: number,
  cloth_id: number
) => {
  try {
    const { data: response } = await api.get<TWorkshopClothHistory>(
      `/workshops/${workshop_id}/cloth-history/${cloth_id}`
    );
    return response;
  } catch (error) {
    populateError(error, "خطأ فى جلب تاريخ المنتج");
  }
};


export const exportWorkshopsToCSV = async (params?: Record<string, unknown>) => {
  try {
    const response = await api.get<Blob>(`/workshops/export`, {
      params,
      responseType: "blob",
    });
    return { data: response.data, headers: response.headers };
  } catch (error) {
    populateError(error, "خطأ فى تصدير الورش");
  }
};