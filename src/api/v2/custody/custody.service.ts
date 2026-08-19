import { populateError } from "@/api/api.utils";
import {
  TCreateCustodyRequest,
  TGetAllCustodiesParams,
  TOrderCustody,
  TReturnCustodyRequest,
} from "./custody.types";
import { TPaginationResponse } from "@/api/api-common.types";
import { api } from "@/api/api-contants";

export const createCustody = async (
  order_id: number,
  data: TCreateCustodyRequest
) => {
  try {
    const formData = new FormData();
    formData.append("order_id", order_id.toString());
    formData.append("type", data.type);
    formData.append("description", data.description);
    formData.append("notes", data.notes);
    formData.append("value", data.value?.toString() || "");
    data.photos?.forEach((photo, index) => {
      formData.append(`photos[${index}]`, photo);
    });
    const { data: responseData } = await api.post<TOrderCustody>(
      `/orders/${order_id}/custody`,
      formData
    );
    return responseData;
  } catch (error: any) {
    populateError(error, "خطأ فى إنشاء الضمان");
  }
};

export const getAllCustodies = async (params: TGetAllCustodiesParams) => {
  try {
    const { data: responseData } = await api.get<
      TPaginationResponse<TOrderCustody>
    >("/custody", { params });
    return responseData;
  } catch (error: any) {
    populateError(error, "خطأ فى جلب الضمانات");
  }
};

export const returnCustody = async (
  custody_id: number,
  data: TReturnCustodyRequest
) => {
  try {
    const formData = new FormData();
    formData.append("custody_action", data.custody_action);
    formData.append("notes", data.notes);
    if (data.reason_of_kept) {
      formData.append("reason_of_kept", data.reason_of_kept);
    }
    data.acknowledgement_receipt_photos.forEach((photo, index) => {
      formData.append(`acknowledgement_receipt_photos[${index}]`, photo);
    });
    
    const { data: responseData } = await api.post<TOrderCustody>(
      `/custody/${custody_id}/return`,
      formData
    );
    return responseData;
  } catch (error: any) {
    populateError(error, "خطأ فى إعادة الضمان");
  }
};


export const getCustodyDetails = async (custody_id: number) => {
  try {
    const { data: responseData } = await api.get<TOrderCustody>(
      `/custody/${custody_id}`
    );
    return responseData;
  } catch (error: any) {
    populateError(error, "خطأ فى جلب تفاصيل الضمان");
  }
};