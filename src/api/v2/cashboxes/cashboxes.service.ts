import {
  TCachboxDailySummary,
  TCachboxRecalculateResponse,
  TCashboxesParams,
  TCashbox,
  TManualCashboxPaymentRequest,
  TManualCashboxPaymentResponse,
  TUpdateCashboxRequest,
  TCreateCashboxRequest,
  TClosePeriodRequest,
  TClosePeriodResponse,
  TClosuresListResponse,
  TClosuresListParams,
  TClosureArchivedDataParams,
  TClosureArchivedDataResponse,
} from "./cashboxes.types";
import { api } from "@/api/api-contants";
import { populateError } from "@/api/api.utils";
import { TPaginationResponse } from "@/api/api-common.types";
import { TBranchResponse } from "../branches/branches.types";

export const getCashboxes = async (params: TCashboxesParams) => {
  try {
    const { data } = await api.get<TPaginationResponse<TCashbox>>(
      "/cashboxes",
      { params }
    );
    return data;
  } catch (error) {
    populateError(error, "خطأ فى جلب الصناديق");
  }
};

export const getCashbox = async (id: number) => {
  try {
    const { data } = await api.get<TCashbox>(`/cashboxes/${id}`);
    return data;
  } catch (error) {
    populateError(error, "خطأ فى جلب الصندوق");
  }
};

export const createCashbox = async (payload: TCreateCashboxRequest) => {
  try {
    const { data } = await api.post<TCashbox>("/cashboxes", payload);
    return data;
  } catch (error) {
    populateError(error, "خطأ فى إنشاء الصندوق");
    throw error;
  }
};

export const updateCashbox = async (
  id: number,
  data: TUpdateCashboxRequest
) => {
  try {
    await api.put<TCashbox>(`/cashboxes/${id}`, data);
  } catch (error) {
    populateError(error, "خطأ فى تحديث الصندوق");
  }
};

export const getCashboxDailySummary = async (id: number, date: string) => {
  try {
    const { data } = await api.get<TCachboxDailySummary>(
      `/cashboxes/${id}/daily-summary`,
      {
        params: {
          date,
        },
      }
    );
    return data;
  } catch (error) {
    populateError(error, "خطأ فى جلب الملخص اليومي للصندوق");
  }
};

export const recalculateCashbox = async (id: number) => {
  try {
    const { data } = await api.post<TCachboxRecalculateResponse>(
      `/cashboxes/${id}/recalculate`
    );
    return data;
  } catch (error) {
    populateError(error, "خطأ فى إعادة حساب الصندوق");
  }
};

export const getCashboxByBranchId = async (branchId: number) => {
  try {
    const { data } = await api.get<{
      cashbox: TCashbox;
      branch: TBranchResponse;
      today_summary: {
        income: number;
        expense: number;
        net_change: number;
      };
    }>(`/branches/${branchId}/cashbox`);
    return data;
  } catch (error) {
    populateError(error, "خطأ فى جلب الصناديق بالفرع");
  }
};

export const createManualCashboxPayment = async (
  cashboxId: number,
  payload: TManualCashboxPaymentRequest
) => {
  try {
    const { data } = await api.post<TManualCashboxPaymentResponse>(
      `/cashboxes/${cashboxId}/payments/manual`,
      payload
    );
    return data;
  } catch (error) {
    populateError(error, "خطأ فى تسجيل دفعة يدوية للصندوق");
  }
};

// ── Cashbox Closure ──

export const closeCashboxPeriod = async (
  id: number,
  payload?: TClosePeriodRequest
) => {
  try {
    const { data } = await api.post<TClosePeriodResponse>(
      `/cashboxes/${id}/close-period`,
      payload ?? {}
    );
    return data;
  } catch (error) {
    populateError(error, "خطأ فى إقفال الفترة");
  }
};

export const getCashboxClosures = async (
  id: number,
  params?: TClosuresListParams
) => {
  try {
    const { data } = await api.get<TClosuresListResponse>(
      `/cashboxes/${id}/closures`,
      { params }
    );
    return data;
  } catch (error) {
    populateError(error, "خطأ فى جلب سجل الإقفالات");
  }
};

export const getClosureArchivedData = async (
  closureId: number,
  params: TClosureArchivedDataParams
) => {
  try {
    const { data } = await api.get<TClosureArchivedDataResponse>(
      `/cashbox-closures/${closureId}/archived-data`,
      { params }
    );
    return data;
  } catch (error) {
    populateError(error, "خطأ فى جلب البيانات المؤرشفة للإقفال");
  }
};

/** Export cashboxes to Excel; same query params as cashboxes index. */
export const exportCashboxesToExcel = async (params?: TCashboxesParams) => {
  try {
    const response = await api.get<Blob>("/cashboxes/export", {
      params,
      responseType: "blob",
    });
    return { data: response.data, headers: response.headers };
  } catch (error) {
    populateError(error, "خطأ فى تصدير الصناديق");
  }
};
