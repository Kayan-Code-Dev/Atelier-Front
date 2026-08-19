import {
  TCreateExpenseRequest,
  TExpense,
  TExpenseCategoriesApiResponse,
  TGetExpensesParams,
  TGetExpenseSummaryParams,
  TGetExpenseSummaryResponse,
  TUpdateExpenseRequest,
} from "./expenses.types";
import { api } from "@/api/api-contants";
import { populateError } from "@/api/api.utils";
import { TPaginationResponse } from "@/api/api-common.types";

export const getExpenseCategories = async () => {
  try {
    const { data } = await api.get<TExpenseCategoriesApiResponse>("/expenses/categories");
    return data;
  } catch (error) {
    populateError(error, "خطأ فى جلب تصنيفات المصروفات");
  }
};

export const getExpenses = async (params: TGetExpensesParams) => {
  try {
    const { data } = await api.get<TPaginationResponse<TExpense>>("/expenses", {
      params,
    });
    return data;
  } catch (error) {
    populateError(error, "خطأ فى جلب المصروفات");
  }
};

export const getExpenseById = async (id: number) => {
  try {
    const { data } = await api.get<TExpense>(`/expenses/${id}`);
    return data;
  } catch (error) {
    populateError(error, "خطأ فى جلب المصروف");
  }
};

export const createExpense = async (data: TCreateExpenseRequest) => {
  try {
    const { data: responseData } = await api.post<TExpense>("/expenses", data);
    return responseData;
  } catch (error) {
    populateError(error, "خطأ فى إنشاء المصروف");
  }
};

export const updateExpense = async (
  id: number,
  data: TUpdateExpenseRequest
) => {
  try {
    const { data: responseData } = await api.put<TExpense>(
      `/expenses/${id}`,
      data
    );
    return responseData;
  } catch (error) {
    populateError(error, "خطأ فى تحديث المصروف");
  }
};

// only for pending expenses
export const deleteExpense = async (id: number) => {
  try {
    await api.delete(`/expenses/${id}`);
  } catch (error) {
    populateError(error, "خطأ فى حذف المصروف");
  }
};

export const getExpenseSummary = async (params: TGetExpenseSummaryParams) => {
  try {
    const { data: responseData } = await api.get<TGetExpenseSummaryResponse>(
      `/expenses/summary`,
      { params }
    );
    return responseData;
  } catch (error) {
    populateError(error, "خطأ فى جلب ملخص المصروفات");
  }
};

export const cancelExpense = async (id: number) => {
  try {
    const { data: responseData } = await api.post<TExpense>(
      `/expenses/${id}/cancel`
    );
    return responseData;
  } catch (error) {
    populateError(error, "خطأ فى إلغاء المصروف");
  }
};

export const payExpense = async (id: number) => {
  try {
    const { data: responseData } = await api.post<TExpense>(
      `/expenses/${id}/pay`
    );
    return responseData;
  } catch (error) {
    populateError(error, "خطأ فى تحديث حالة المصروف");
  }
};

/** Export expenses to Excel; same query params as expenses index. */
export const exportExpensesToExcel = async (params?: TGetExpensesParams) => {
  try {
    const response = await api.get<Blob>("/expenses/export", {
      params,
      responseType: "blob",
    });
    return { data: response.data, headers: response.headers };
  } catch (error) {
    populateError(error, "خطأ فى تصدير المصروفات");
  }
};
