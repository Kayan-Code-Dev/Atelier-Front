export type TCashbox = {
  id: number;
  name: string;
  branch_id: number;
  initial_balance: number;
  current_balance: number;
  description: string;
  is_active: boolean;
  created_at: string;
  updated_at: string;
  deleted_at: string | null;
  branch: {
    id: number;
    branch_code: string;
    name: string;
  };
  today_income: number;
  today_expense: number;
  today_summary?: {
    expense: number;
    income: number;
    net_change: number;
  };
};

/** Aligned with GET /api/v1/cashboxes index filters */
export type TCashboxesParams = {
  per_page?: number;
  page?: number;
  is_active?: boolean;
  branch_id?: number;
  initial_balance_min?: number;
  initial_balance_max?: number;
  current_balance_min?: number;
  current_balance_max?: number;
  search?: string;
};

export type TCreateCashboxRequest = {
  name: string;
  branch_id?: number;
  initial_balance?: number;
  description?: string;
  is_active?: boolean;
};

export type TUpdateCashboxRequest = Partial<{
  name: string;
  description: string;
  is_active: boolean;
}>;

// by id and date
export type TCachboxDailySummary = {
  date: string;
  cashbox_id: number;
  cashbox_name: string;
  opening_balance: number;
  total_income: number;
  total_expense: number;
  net_change: number;
  closing_balance: number;
  transaction_count: number;
  reversal_count: number;
};

export type TCachboxRecalculateResponse = {
  message: string;
  previous_balance: number;
  calculated_balance: number;
  difference: number;
};

export type TManualCashboxPaymentRequest = {
  amount: number;
  description: string;
  payment_method?: string;
  received_from?: string;
  notes?: string;
};

export type TManualCashboxPaymentResponse = {
  message: string;
  manual_payment: {
    id: number;
    cashbox_id: number;
    transaction_id: number;
    amount: string;
    payment_method: string;
    received_from: string | null;
    description: string;
    notes: string | null;
    paid_at: string;
  };
  transaction: {
    id: number;
    cashbox_id: number;
    type: "income";
    amount: string;
    balance_after: string;
    category: "payment";
    description: string;
    reference_type: string | null;
    reference_id: number | null;
    metadata: {
      manual_payment?: boolean;
      payment_method?: string;
      received_from?: string;
      notes?: string;
    };
  };
  cashbox: {
    id: number;
    name: string;
    current_balance: string;
  };
};

export type TManualCashboxPaymentsListParams = {
  per_page?: number;
  payment_method?: string;
  received_from?: string;
  start_date?: string;
  end_date?: string;
};

// ── Cashbox Closure ──

export type TClosePeriodRequest = {
  notes?: string;
};

export type TCashboxClosureDetails = {
  income_by_category?: Record<string, { count: number; total: number }>;
  expense_by_category?: Record<string, { count: number; total: number }>;
  reversals?: { count: number; total: number };
  order_payments?: { count: number; total: number };
  tailoring_payments?: { count: number; total: number };
  manual_payments?: { count: number; total: number };
  business_expenses?: {
    count: number;
    total: number;
    by_category?: Record<string, { count: number; total: number }>;
  };
  salary_payments?: { count: number; total: number };
};

export type TCashboxClosure = {
  id: number;
  cashbox_id: number;
  from_date: string;
  to_date: string;
  opening_balance: string | number;
  total_income: string | number;
  total_expense: string | number;
  net_change: string | number;
  closing_balance: string | number;
  transaction_count: number;
  reversal_count: number;
  new_initial_balance: string | number;
  closed_by: number;
  closed_at: string;
  details: TCashboxClosureDetails;
  notes: string | null;
  created_at?: string;
  updated_at?: string;
  closer?: {
    id: number;
    name: string;
    avatar_url?: string | null;
    logo_url?: string | null;
  };
};

export type TClosePeriodResponse = {
  message: string;
  closure: TCashboxClosure;
};

export type TClosuresListResponse = {
  cashbox: {
    id: number;
    name: string;
    current_balance: string | number;
  };
  closures: {
    data: TCashboxClosure[];
    current_page: number;
    total: number;
    per_page: number;
    total_pages?: number;
  };
};

export type TClosuresListParams = {
  per_page?: number;
  page?: number;
};

/** GET /cashbox-closures/{closure_id}/archived-data */
export type TClosureArchivedDataType =
  | "transactions"
  | "payments"
  | "expenses"
  | "manual_payments"
  | "tailoring_payments"
  | "salary_payments"
  | "payments_expenses";

export type TClosureArchivedDataParams = {
  type: TClosureArchivedDataType;
  page?: number;
  /** default 15, max 100 per API */
  per_page?: number;
};

/** Closure snapshot on archived-data response (may omit some list-only fields) */
export type TClosureArchivedSnapshot = Pick<
  TCashboxClosure,
  | "id"
  | "cashbox_id"
  | "from_date"
  | "to_date"
  | "opening_balance"
  | "closing_balance"
  | "total_income"
  | "total_expense"
  | "net_change"
  | "transaction_count"
  | "reversal_count"
  | "new_initial_balance"
  | "closed_at"
  | "notes"
  | "details"
> & {
  closer?: TCashboxClosure["closer"];
};

export type TClosureArchivedDataResponse = {
  closure: TClosureArchivedSnapshot;
  cashbox: {
    id: number;
    name: string;
    branch_id?: number;
  };
  type: string;
  data: Record<string, unknown>[];
  current_page: number;
  total: number;
  total_pages: number;
  per_page: number;
};
