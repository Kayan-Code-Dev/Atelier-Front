import { api } from "@/api/api-contants";
import { TPaginationResponse } from "@/api/api-common.types";
import { populateError } from "@/api/api.utils";
import { TTransaction, TTransactionsParams } from "./transactions.types";

/** GET /api/v1/transactions/payments-expenses */
export const getTransactionsPaymentsExpenses = async (
  params: TTransactionsParams
) => {
  try {
    const { data } = await api.get<TPaginationResponse<TTransaction>>(
      "/transactions/payments-expenses",
      { params }
    );
    return data;
  } catch (error) {
    populateError(error, "خطأ فى جلب كشف معاملات الخزنة");
  }
};


export const exportTransactionsToCSV = async (params?: TTransactionsParams) => {
  try {
    const response = await api.get<Blob>("/transactions/payments-expenses/export", {
      params,
      responseType: "blob",
    });
    return { data: response.data, headers: response.headers };
  } catch (error) {
    populateError(error, "خطأ فى تصدير كشف المعاملات");
  }
};

