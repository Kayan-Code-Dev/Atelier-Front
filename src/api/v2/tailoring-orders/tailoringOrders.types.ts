import type { TPaginationResponse } from "@/api/api-common.types";

export type TTailoringWorkflowSlug =
  | "fabric_received"
  | "cutting_prep"
  | "sewing"
  | "finishing_embroidery"
  | "ready_for_delivery"
  | "delivered";

export type TTailoringWorkflowStatusesResponse = {
  statuses: string[];
  labels_ar: Record<string, string>;
};

export type TTailoringOrderPayment = {
  id?: number;
  amount: number;
  payment_date?: string | null;
  notes?: string | null;
  status?: string;
  payment_type?: string;
};

export type TTailoringFabricResource = {
  garment_type?: string;
  fabric_type?: string;
  color?: string | null;
  quantity?: string | number | null;
  includes_embroidery?: boolean;
  supplier_id?: number | null;
  notes?: string | null;
  supplier?: { id?: number; name?: string } | null;
};

export type TTailoringOrderResource = {
  id: number;
  branch_id: number;
  branch?: { id?: number; name?: string } | null;
  employee_id?: number | null;
  employee?: {
    id?: number;
    name?: string;
    user?: { name?: string };
  } | null;
  client_id: number;
  client?: {
    id?: number;
    name?: string;
    phones?: { phone: string; type?: string }[];
    national_id?: string;
    address?: { address?: string; city?: { name?: string } };
  } | null;
  status: string;
  total_price: number;
  paid: number;
  remaining: number;
  occasion_datetime: string;
  delivery_date: string;
  delivered_at?: string | null;
  measurements?: Record<string, unknown> | null;
  fabric?: TTailoringFabricResource | null;
  payments?: TTailoringOrderPayment[];
  created_at: string;
  updated_at: string;
  deleted_at?: string | null;
};

export type TGetTailoringOrdersApiParams = {
  page?: number;
  per_page?: number;
  id?: number;
  ids?: string;
  status?: string;
  client_id?: number;
  employee_id?: number;
  branch_id?: number;
  search?: string;
  occasion_from?: string;
  occasion_to?: string;
  delivery_from?: string;
  delivery_to?: string;
  delivered_from?: string;
  delivered_to?: string;
  created_from?: string;
  created_to?: string;
  min_total_price?: number;
  max_total_price?: number;
  min_paid?: number;
  max_paid?: number;
  min_remaining?: number;
  max_remaining?: number;
  unpaid_only?: boolean;
  supplier_id?: number;
  garment_type?: string;
  fabric_type?: string;
  includes_embroidery?: boolean;
  sort_by?: string;
  sort_dir?: "asc" | "desc";
};

export type TTailoringOrdersListResponse = TPaginationResponse<TTailoringOrderResource> & {
  per_page?: number;
};

export type TCreateTailoringOrderFabricPayload = {
  garment_type: string;
  fabric_type: string;
  color?: string | null;
  quantity?: string | number | null;
  supplier_id?: number | null;
  includes_embroidery?: boolean;
  notes?: string | null;
};

export type TCreateTailoringOrderClientPayload = {
  name: string;
  national_id?: string | null;
  address: { city_id: number; address: string };
  phones: { phone: string; type?: string }[];
};

export type TCreateTailoringOrderPayload = {
  existing_client: boolean;
  client_id?: number;
  client?: TCreateTailoringOrderClientPayload;
  branch_id: number;
  employee_id?: number | null;
  total_price: number;
  initial_paid?: number;
  occasion_datetime: string;
  delivery_date: string;
  delivered_at?: string | null;
  fabric: TCreateTailoringOrderFabricPayload;
  measurements?: Record<string, string>;
};

export type TPatchTailoringOrderMeasurementsPayload = {
  measurements: Record<string, string>;
};

export type TPatchTailoringOrderStatusPayload = {
  status: string;
};

export type TAddTailoringOrderPaymentPayload = {
  amount: number;
  payment_date?: string | null;
  notes?: string | null;
  status?: "pending" | "paid" | "canceled";
  payment_type?: "initial" | "fee" | "normal";
};
