import { TInventoryResponse } from "../inventory/inventory.types";
import { TAddressResponse } from "../address/address.types";

export type TCreateBranchRequest = {
  branch_code: string;
  name: string;
  address: {
    street: string;
    building: string;
    city_id: number;
    notes: string;
  };
  inventory_name: string;
  phone?: string;
  image?: File | null;
  vat_enabled?: boolean;
  vat_type?: "fixed" | "percentage" | null;
  vat_value?: number | null;
  currency_id?: number | null;
};

export type TBranchResponse = {
  branch_code: string;
  name: string;
  address_id: number;
  updated_at: string;
  created_at: string;
  id: number;
  inventory: TInventoryResponse;
  address: TAddressResponse;
  phone?: string | null;
  image?: string | null;
  image_url?: string | null;
  vat_enabled?: boolean | null;
  vat_type?: "fixed" | "percentage" | null;
  vat_value?: number | null;
  currency_name?: string | null;
  currency_code?: string | null;
  currency_symbol?: string | null;
  /** حالة الفرع من الخادم إن وُجدت (نشط / قيد الإنشاء / مغلق أو مكافئ إنجليزي) */
  status?: string | null;
};

export type TUpdateBranchRequest = Partial<TCreateBranchRequest>;


