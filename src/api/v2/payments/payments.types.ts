export type TPaymentStatus = "pending" | "paid" | "canceled";
export type TPaymentType = "initial" | "fee" | "normal";

export type TClothPayment = {
  cloth_id: number;
  amount: number;
};

export type TCreatePaymentRequest = {
  order_id: number;
  cloth_payments: TClothPayment[];
  status: TPaymentStatus; // only use paid or canceled in the form
  payment_type: TPaymentType;
  payment_date: string;
  notes: string;
};

export type TPayment = {
  id: number;
  order_id: number;
  /** API returns amount as a string, but some places treat it as number, so allow both */
  amount: number | string;
  status: TPaymentStatus;
  payment_type: TPaymentType;
  payment_date: string;
  notes: string;
  created_by: number;
  created_at: string;
  updated_at: string;
  deleted_at: string | null;
  /** Cashbox snapshot fields (may be null for legacy payments) */
  cashbox_id?: number | null;
  /** Cashbox with branch (API returns branch here when order.branch is absent) */
  cashbox?: {
    id: number;
    name: string;
    branch_id?: number;
    branch?: { id: number; name: string; branch_code?: string } | null;
  } | null;
  transaction_id?: number | null;
  cashbox_balance_before?: number | null;
  cashbox_balance_after?: number | null;
  cashbox_daily_income_total?: number | null;
  cashbox_daily_expense_total?: number | null;
  cashbox_snapshot_meta?: {
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
  } | null;
  order: {
    id: number;
    client_id: number;
    inventory_id: number;
    /** Total price comes from API as a decimal string */
    total_price: number | string;
    status: string;
    tailoring_stage: string | null;
    tailoring_stage_changed_at: string | null;
    expected_completion_date: string | null;
    actual_completion_date: string | null;
    assigned_factory_id: number | null;
    sent_to_factory_date: string | null;
    received_from_factory_date: string | null;
    factory_notes: string | null;
    priority: string;
    /** Total paid and remaining amounts (decimal strings) */
    paid: string;
    remaining: string;
    delivery_date: string | null;
    days_of_rent: number;
    occasion_datetime: string | null;
    visit_datetime: string | null;
    order_notes: string | null;
    discount_type: string | null;
    discount_value: string;
    vat_enabled: boolean;
    vat_type: string | null;
    vat_value: string | null;
    deleted_at: string | null;
    created_at: string;
    updated_at: string;
    employee_id: number;
    branch?: { id: number; name: string } | null;
    client: {
      id: number;
      /** Full name field returned by API (preferred when present) */
      name?: string;
      /** Optional name parts for backward compatibility */
      first_name?: string;
      middle_name?: string;
      last_name?: string;
      date_of_birth: string | null;
      national_id: string;
      address_id?: number;
      source: string | null;
      breast_size?: string | null;
      waist_size?: string | null;
      sleeve_size?: string | null;
      hip_size?: string | null;
      shoulder_size?: string | null;
      length_size?: string | null;
      measurement_notes?: string | null;
      last_measurement_date?: string | null;
      deleted_at: string | null;
      created_at: string;
      updated_at: string;
    };
  };
  user: {
    id: number;
    name: string;
    email: string;
    email_verified_at: string;
    deleted_at: string | null;
    created_at: string;
    updated_at: string;
  };
};

/** Aligned with GET /api/v1/payments index filters */
export type TGetPaymentsParams = {
  page?: number;
  per_page?: number;
  status?: TPaymentStatus;
  payment_type?: TPaymentType;
  order_id?: number;
  client_id?: number;
  employee_id?: number;
  inventory_id?: number;
  branch_id?: number;
  cashbox_id?: number;
  date_from?: string;
  date_to?: string;
  amount_min?: number;
  amount_max?: number;
  amount_from?: number;
  amount_to?: number;
  created_by?: number;
  search?: string;
};

export type TPaymentsScope = "payments" | "tailoring" | "manual";

export type TTailoringPaymentListItem = {
  id: number;
  tailoring_order_id: number;
  amount: number | string;
  status: TPaymentStatus;
  payment_type: TPaymentType;
  payment_date: string;
  notes?: string | null;
  cashbox_id?: number | null;
  cashbox?: {
    id: number;
    name: string;
    branch_id?: number;
    branch?: { id: number; name: string } | null;
  } | null;
  tailoring_order?: {
    id: number;
    branch_id?: number;
    branch?: { id: number; name: string } | null;
    client_id?: number;
    client?: {
      id: number;
      name?: string;
      first_name?: string;
      middle_name?: string;
      last_name?: string;
    } | null;
    total_price?: number | string;
    paid?: number | string;
    remaining?: number | string;
    status?: string;
  } | null;
  user?: { id: number; name: string; email?: string } | null;
  created_at: string;
};

export type TManualCashboxPaymentListItem = {
  id: number;
  cashbox_id: number;
  transaction_id: number | null;
  amount: number | string;
  payment_method?: string | null;
  received_from?: string | null;
  description: string;
  notes?: string | null;
  paid_at: string;
  cashbox?: {
    id: number;
    name: string;
    branch_id?: number;
    branch?: { id: number; name: string } | null;
  } | null;
  creator?: { id: number; name: string; email?: string } | null;
  transaction?: {
    id: number;
    balance_after?: number | string | null;
  } | null;
  created_at?: string;
};
