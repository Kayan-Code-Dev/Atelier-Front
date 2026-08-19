/**
 * Types for Simple Salary API
 * Base: GET/POST /api/v1/simple-salary/*
 */

/** Period format YYYY-MM (month 01–12) */
export type TPeriod = string;

export type TSimpleSalaryEmployee = {
  id: number;
  name: string;
  employee_code: string;
};

export type TSimpleSalaryDeduction = {
  id: number;
  employee_id: number;
  period: TPeriod;
  amount: number;
  reason: string;
  date: string;
  notes: string | null;
  created_at?: string;
  updated_at?: string;
  employee?: { id: number; user?: { name: string } };
};

export type TSimpleSalaryAddition = {
  id: number;
  employee_id: number;
  period: TPeriod;
  amount: number;
  reason: string;
  date: string;
  notes: string | null;
  created_at?: string;
  updated_at?: string;
  employee?: { id: number; user?: { name: string } };
};

export type TSimpleSalaryPayment = {
  id: number;
  employee_id: number;
  period: TPeriod;
  amount: number;
  paid_at: string;
  cashbox_id: number;
  transaction_id?: number;
  payment_method: "cash" | "bank_transfer" | "check";
  payment_reference: string | null;
  notes: string | null;
  employee?: { id: number; user?: { name: string } };
  cashbox?: { id: number; name: string };
  transaction?: { id: number; amount: number; [k: string]: unknown };
};

/** GET /simple-salary/employee/{id}/period/{period} */
export type TSimpleSalarySummary = {
  employee: TSimpleSalaryEmployee;
  period: TPeriod;
  salary: number;
  deductions: TSimpleSalaryDeduction[];
  total_deductions: number;
  additions?: TSimpleSalaryAddition[];
  total_additions?: number;
  net_to_pay: number;
  total_paid: number;
  remaining_to_pay: number;
  payments: TSimpleSalaryPayment[];
  fully_paid: boolean;
  /** Present when from_date/to_date query params were used */
  from_date?: string;
  to_date?: string;
};

/** POST /simple-salary/deductions body */
export type TCreateSimpleSalaryDeductionRequest = {
  employee_id: number;
  period: TPeriod;
  amount: number;
  reason: string;
  date: string; // YYYY-MM-DD
  notes?: string;
};

/** 201 response after creating deduction */
export type TCreateSimpleSalaryDeductionResponse = {
  message: string;
  deduction: TSimpleSalaryDeduction;
};

/** POST /simple-salary/additions body */
export type TCreateSimpleSalaryAdditionRequest = {
  employee_id: number;
  period: TPeriod;
  amount: number;
  reason: string;
  date: string; // YYYY-MM-DD
  notes?: string;
};

/** 201 response after creating addition */
export type TCreateSimpleSalaryAdditionResponse = {
  message: string;
  addition: TSimpleSalaryAddition;
};

/** GET /simple-salary/additions query */
export type TGetSimpleSalaryAdditionsParams = {
  employee_id?: number;
  period?: TPeriod;
  per_page?: number;
  page?: number;
};

/** GET /simple-salary/deductions query */
export type TGetSimpleSalaryDeductionsParams = {
  employee_id?: number;
  period?: TPeriod;
  per_page?: number;
  page?: number;
};

/** Paginated response (deductions / payments) */
export type TSimpleSalaryPaginatedResponse<T> = {
  data: T[];
  current_page: number;
  total: number;
  total_pages: number;
  per_page: number;
};

/** POST /simple-salary/pay body */
export type TSimpleSalaryPayRequest = {
  employee_id: number;
  period: TPeriod;
  payment_method: "cash" | "bank_transfer" | "check";
  amount?: number;
  payment_reference?: string;
  notes?: string;
};

/** 201 response after pay */
export type TSimpleSalaryPayResponse = {
  message: string;
  payment: TSimpleSalaryPayment;
};

/** GET /simple-salary/payments query */
export type TGetSimpleSalaryPaymentsParams = {
  employee_id?: number;
  period?: TPeriod;
  per_page?: number;
  page?: number;
};

export const PAYMENT_METHODS = ["cash", "bank_transfer", "check"] as const;
export type TPaymentMethod = (typeof PAYMENT_METHODS)[number];

export const PAYMENT_METHOD_LABELS: Record<TPaymentMethod, string> = {
  cash: "نقدي",
  bank_transfer: "تحويل بنكي",
  check: "شيك",
};
