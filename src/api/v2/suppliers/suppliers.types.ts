import { TPaginationResponse } from "@/api/api-common.types";

// ---------------------------------------------------------------------------
// Supplier
// ---------------------------------------------------------------------------

export type TCreateSupplierMinimalRequest = {
  name: string;
  code: string;
  phone?: string;
  address?: string;
};

export type TCreateSupplierClothItem = {
  code: string;
  name?: string;
  cloth_type_id?: number;
  category_id?: number;
  subcategory_id?: number;
  entity_type: "branch" | "factory" | "workshop";
  entity_id: number;
  price: number;
};

export type TCreateSupplierRequest = {
  name: string;
  code: string;
  phone?: string;
  address?: string;
  category_id: number;
  subcategory_id: number;
  branch_id: number;
  order_date: string;
  total_amount: number;
  payment_amount: number;
  remaining_payment: number;
  notes?: string;
  clothes: TCreateSupplierClothItem[];
};

export type TUpdateSupplierRequest = Partial<
  Pick<TCreateSupplierRequest, "name" | "code" | "phone" | "address">
>;

export type TSupplierResponse = {
  id: number;
  name: string;
  code: string;
  created_at: string;
  updated_at: string;
  phone?: string | null;
  address?: string | null;
  orders_count?: number | null;
  refund_orders_count?: number | null;
  total_order_amount?: string | number | null;
  total_payment?: string | number | null;
  total_refund?: string | number | null;
  total_remaining?: string | number | null;
  purchases_count?: number | null;
  returns_count?: number | null;
  net_purchases_count?: number | null;
  total_purchases?: string | number | null;
  total_returns?: string | number | null;
  net_balance?: string | number | null;
  paid?: string | number | null;
  remaining?: string | number | null;
};

export type TSuppliersListResponse = TPaginationResponse<TSupplierResponse>;

// ---------------------------------------------------------------------------
// Supplier Order — Cloth Item (API response may use `id` or `cloth_id`)
// ---------------------------------------------------------------------------

export type TSupplierOrderClothItem = {
  id?: number;
  cloth_id?: number;
  name?: string | null;
  code?: string | null;
  price: number;
  payment: number;
  remaining: number;
  notes?: string | null;
  category_id?: number | null;
  subcategory_ids?: number[] | null;
  /** May be present on API detail/list responses */
  breast_size?: string | null;
  waist_size?: string | null;
  sleeve_size?: string | null;
};

/** Resolves the cloth identifier regardless of which field the API returns. */
export function resolveClothId(cloth: TSupplierOrderClothItem): number {
  return cloth.cloth_id ?? cloth.id ?? 0;
}

// ---------------------------------------------------------------------------
// Supplier Order (list / detail response)
// ---------------------------------------------------------------------------

export type TSupplierOrderResponse = {
  id: number;
  supplier_id: number;
  supplier: {
    id: number;
    name: string;
    code: string;
    phone?: string;
    address?: string;
  } | null;
  category_id: number | null;
  category: { id: number; name: string } | null;
  subcategory_id: number | null;
  subcategory: { id: number; name: string } | null;
  branch_id: number | null;
  branch: { id: number; name: string } | null;
  order_number: string;
  type: string | null;
  model_id: number | null;
  order_date: string;
  status: string;
  total_amount: string;
  payment_amount: string;
  remaining_payment: string;
  notes: string | null;
  created_at: string;
  updated_at: string;
  received_at?: string | null;
  inventory_received?: boolean;
  clothes?: TSupplierOrderClothItem[];
};

export type TSupplierOrderDetailResponse = TSupplierOrderResponse;

export type TSupplierOrdersListResponse =
  TPaginationResponse<TSupplierOrderResponse>;

// ---------------------------------------------------------------------------
// Create Supplier Order
// ---------------------------------------------------------------------------

export type TCreateSupplierOrderClothItem = {
  code: string;
  breast_size?: string;
  waist_size?: string;
  sleeve_size?: string;
  notes?: string | null;
  status?: string;
  category_id?: number | null;
  subcategory_ids?: number[] | null;
  entity_type: "branch" | "factory" | "workshop";
  entity_id: number;
  price: number;
  payment?: number;
  remaining?: number;
};

export type TCreateSupplierOrderRequest = {
  supplier_id: number;
  category_id: number;
  subcategory_id: number;
  branch_id: number;
  order_number?: string;
  type?: string;
  order_date: string;
  total_amount: number;
  payment_amount: number;
  remaining_payment: number;
  notes?: string | null;
  clothes: TCreateSupplierOrderClothItem[];
};

// ---------------------------------------------------------------------------
// Update Supplier Order
// ---------------------------------------------------------------------------

export type TUpdateSupplierOrderClothItem = {
  cloth_id: number;
  price: number;
  payment: number;
  remaining: number;
  notes?: string | null;
  category_id?: number | null;
  subcategory_ids?: number[] | null;
};

export type TUpdateSupplierOrderRequest = {
  supplier_id: number;
  category_id: number;
  subcategory_id: number;
  branch_id: number;
  order_number: string;
  type?: string;
  order_date: string;
  status?: string;
  total_amount: number;
  payment_amount: number;
  remaining_payment: number;
  notes?: string | null;
  clothes: TUpdateSupplierOrderClothItem[];
};

// ---------------------------------------------------------------------------
// Add Payment
// ---------------------------------------------------------------------------

export type TAddPaymentToSupplierOrderRequest = {
  clothes: { cloth_id: number; amount: number }[];
};
