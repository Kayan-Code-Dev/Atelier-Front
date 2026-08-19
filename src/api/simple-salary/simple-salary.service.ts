import { api } from "@/api/api-contants";
import { populateError } from "@/api/api.utils";
import type {
  TSimpleSalarySummary,
  TCreateSimpleSalaryDeductionRequest,
  TCreateSimpleSalaryDeductionResponse,
  TCreateSimpleSalaryAdditionRequest,
  TCreateSimpleSalaryAdditionResponse,
  TGetSimpleSalaryDeductionsParams,
  TGetSimpleSalaryAdditionsParams,
  TSimpleSalaryPaginatedResponse,
  TSimpleSalaryDeduction,
  TSimpleSalaryAddition,
  TSimpleSalaryPayRequest,
  TSimpleSalaryPayResponse,
  TGetSimpleSalaryPaymentsParams,
  TSimpleSalaryPayment,
} from "./simple-salary.types";

/** baseURL already includes /api/v1 */
const BASE = "/simple-salary";

export type TGetSimpleSalarySummaryParams = {
  from_date?: string; // YYYY-MM-DD
  to_date?: string; // YYYY-MM-DD
};

export async function getSimpleSalarySummary(
  employeeId: number,
  period: string,
  params?: TGetSimpleSalarySummaryParams
): Promise<TSimpleSalarySummary | null> {
  try {
    const { data } = await api.get<TSimpleSalarySummary>(
      `${BASE}/employee/${employeeId}/period/${period}`,
      { params }
    );
    return data;
  } catch (error) {
    populateError(error, "خطأ في جلب ملخص الراتب");
    return null;
  }
}

export async function createSimpleSalaryDeduction(
  body: TCreateSimpleSalaryDeductionRequest
): Promise<TCreateSimpleSalaryDeductionResponse | null> {
  try {
    const { data } = await api.post<TCreateSimpleSalaryDeductionResponse>(
      `${BASE}/deductions`,
      body
    );
    return data;
  } catch (error) {
    populateError(error, "خطأ في إضافة الخصم");
    return null;
  }
}

export async function getSimpleSalaryDeductions(
  params?: TGetSimpleSalaryDeductionsParams
): Promise<TSimpleSalaryPaginatedResponse<TSimpleSalaryDeduction> | null> {
  try {
    const { data } = await api.get<
      TSimpleSalaryPaginatedResponse<TSimpleSalaryDeduction>
    >(`${BASE}/deductions`, { params });
    return data;
  } catch (error) {
    populateError(error, "خطأ في جلب الخصومات");
    return null;
  }
}

export async function createSimpleSalaryAddition(
  body: TCreateSimpleSalaryAdditionRequest
): Promise<TCreateSimpleSalaryAdditionResponse | null> {
  try {
    const { data } = await api.post<TCreateSimpleSalaryAdditionResponse>(
      `${BASE}/additions`,
      body
    );
    return data;
  } catch (error) {
    populateError(error, "خطأ في إضافة المكافأة");
    return null;
  }
}

export async function getSimpleSalaryAdditions(
  params?: TGetSimpleSalaryAdditionsParams
): Promise<TSimpleSalaryPaginatedResponse<TSimpleSalaryAddition> | null> {
  try {
    const { data } = await api.get<
      TSimpleSalaryPaginatedResponse<TSimpleSalaryAddition>
    >(`${BASE}/additions`, { params });
    return data;
  } catch (error) {
    populateError(error, "خطأ في جلب الإضافات");
    return null;
  }
}

export async function paySimpleSalary(
  body: TSimpleSalaryPayRequest
): Promise<TSimpleSalaryPayResponse | null> {
  try {
    const { data } = await api.post<TSimpleSalaryPayResponse>(
      `${BASE}/pay`,
      body
    );
    return data;
  } catch (error) {
    populateError(error, "خطأ في تسجيل الدفعة");
    return null;
  }
}

export async function getSimpleSalaryPayments(
  params?: TGetSimpleSalaryPaymentsParams
): Promise<TSimpleSalaryPaginatedResponse<TSimpleSalaryPayment> | null> {
  try {
    const { data } = await api.get<
      TSimpleSalaryPaginatedResponse<TSimpleSalaryPayment>
    >(`${BASE}/payments`, { params });
    return data;
  } catch (error) {
    populateError(error, "خطأ في جلب الدفعات");
    return null;
  }
}
