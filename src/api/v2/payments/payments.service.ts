import {
  TCreatePaymentRequest,
  TGetPaymentsParams,
  TManualCashboxPaymentListItem,
  TPayment,
  TTailoringPaymentListItem,
} from "./payments.types";
import { api } from "@/api/api-contants";
import { TPaginationResponse } from "@/api/api-common.types";
import { populateError } from "@/api/api.utils";

export const getPayments = async (params: TGetPaymentsParams) => {
  try {
    const { data } = await api.get<TPaginationResponse<TPayment>>("/payments", {
      params,
    });
    return data;
  } catch (error) {
    populateError(error, "خطأ فى جلب المدفوعات");
  }
};

export const getTailoringPayments = async (
  params: {
    per_page?: number;
    page?: number;
    tailoring_order_id?: number;
    status?: TPayment["status"];
    payment_type?: TPayment["payment_type"];
    cashbox_id?: number;
    start_date?: string;
    end_date?: string;
  }
) => {
  try {
    const { data } = await api.get<TPaginationResponse<TTailoringPaymentListItem>>(
      "/tailoring-orders/payments",
      { params }
    );
    return data;
  } catch (error) {
    populateError(error, "خطأ فى جلب مدفوعات التفصيل");
  }
};

export const getManualCashboxPayments = async (
  cashboxId: number,
  params?: {
    per_page?: number;
    page?: number;
    payment_method?: string;
    received_from?: string;
    start_date?: string;
    end_date?: string;
  }
) => {
  try {
    const { data } = await api.get<{
      cashbox: { id: number; name: string; current_balance: string };
      payments: TPaginationResponse<TManualCashboxPaymentListItem>;
    }>(`/cashboxes/${cashboxId}/payments/manual`, { params });
    return data;
  } catch (error) {
    populateError(error, "خطأ فى جلب المدفوعات الأخرى");
  }
};

export const getAllManualCashboxPayments = async (params?: {
  per_page?: number;
  page?: number;
  payment_method?: string;
  received_from?: string;
  start_date?: string;
  end_date?: string;
  branch_id?: number;
  cashbox_id?: number;
}) => {
  try {
    const { data } = await api.get<TPaginationResponse<TManualCashboxPaymentListItem>>(
      "/cashboxes/payments/manual",
      { params }
    );
    return data;
  } catch (error) {
    populateError(error, "خطأ فى جلب المدفوعات الأخرى");
  }
};

export const getPaymentById = async (id: number) => {
  try {
    const { data } = await api.get<TPayment>(`/payments/${id}`);
    return data;
  } catch (error) {
    populateError(error, "خطأ فى جلب المدفوعة");
  }
};

export const createPayment = async (payment: TCreatePaymentRequest) => {
  try {
    const { data } = await api.post<TPayment>("/payments", payment);
    return data;
  } catch (error) {
    populateError(error, "خطأ فى إنشاء المدفوعة");
  }
};

export const markPaymentAsPaid = async (id: number) => {
  try {
    const { data } = await api.post<TPayment>(`/payments/${id}/pay`);
    return data;
  } catch (error) {
    populateError(error, "خطأ فى تحديث حالة المدفوعة");
  }
};

export const markPaymentAsCanceled = async (id: number) => {
  try {
    const { data } = await api.post<TPayment>(`/payments/${id}/cancel`);
    return data;
  } catch (error) {
    populateError(error, "خطأ فى تحديث حالة المدفوعة");
  }
};


/** Export payments to Excel; same query params as payments index. Returns blob + headers for filename from Content-Disposition. */
export const exportPaymentsToCSV = async (params?: TGetPaymentsParams) => {
  try {
    const response = await api.get<Blob>(`/payments/export`, {
      params,
      responseType: "blob",
    });
    return { data: response.data, headers: response.headers };
  } catch (error) {
    populateError(error, "خطأ فى تصدير المدفوعات");
  }
};