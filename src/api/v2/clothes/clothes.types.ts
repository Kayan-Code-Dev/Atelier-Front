import { TEntity } from "@/lib/types/entity.types";

export type TCreateClothesRequest = {
  code: string;
  measurements?: string;
  status: TClothesStatus;
  entity_type: TEntity;
  entity_id: number;
  notes?: string;
  description?: string;
  breast_size?: string;
  waist_size?: string;
  sleeve_size?: string;
  cloth_type_id?: number;
  category_id?: number | null;
  subcategory_ids?: number[] | null;
};

export type TClothResponse = {
  code: string;
  measurements?: string;
  status: TClothesStatus;
  notes?: string;
  entity_type: TEntity;
  entity_id: number;
  entity_name?: string;
  updated_at: string;
  created_at: string;
  id: number;
  cloth_type_id?: number;
  cloth_type_name?: string;
  description?: string;
  breast_size?: string;
  waist_size?: string;
  sleeve_size?: string;
  category_id?: number | null;
  category_name?: string | null;
  subcategory_ids?: number[] | null;
  subcategory_names?: string[] | null;
};

export type TUpdateClothesRequest = {
  code: string;
  status: TClothesStatus;
  entity_type: TEntity;
  entity_id: number;
  notes?: string;
  breast_size?: string;
  waist_size?: string;
  sleeve_size?: string;
  measurements?: string;
  cloth_type_id?: number;
  category_id?: number | null;
  subcategory_ids?: number[] | null;
};

export type TClothesStatus =
  | "damaged"
  | "burned"
  | "scratched"
  | "ready_for_rent"
  | "rented"
  | "die"
  | "repairing";

export type TGetClothesRequestParams = {
  page?: number;
  per_page?: number;
  
  id?: string;
  
  branch_id?: number;
  
  inventory_id?: number;
  
  breast_size?: string;
  
  waist_size?: string;
  
  sleeve_size?: string;
  
  created_from?: string;
  
  created_to?: string;
  
  search?: string;
  entity_type?: TEntity;
  entity_id?: number;
  name?: string;
  code?: string;
  category_id?: number;
  subcat_id?: number[];
  status?: TClothesStatus;
  
  measurements?: string;
  
  delivery_date?: string;
  
  days_of_rent?: number;
  
  occasion_datetime?: string;
  
  visit_datetime?: string;
};

export type TClothesAvailableForDateResponse = {
  delivery_date: string;
  days_of_rent: number;
  entity_type: TEntity;
  entity_id: number;
  available_clothes: {
    id: number;
    code: string;
    name?: string;
    description: string;
    status: TClothesStatus;
    cloth_type?: {
      id: number;
      name: string;
    };
  }[];
  total_available: number;
};

export type TClothesUnavailableDaysRangesResponse = {
  results: {
    cloth_id: number;
    unavailable_ranges: {
      start: string;
      end: string;
    }[];
  }[];
};


export type TClothOrderClient = {
  id: number;
  name?: string | null;
  first_name?: string | null;
  last_name?: string | null;
  date_of_birth?: string | null;
  national_id?: string | null;
  phones?: { phone: string; type?: string }[];
};

export type TClothOrderPivot = {
  type: string;
  price: string;
  quantity: number;
  status: string;
  returnable: boolean;
};

export type TClothOrderItem = {
  id: number;
  client: TClothOrderClient;
  created_at: string;
  days_of_rent: number | null;
  delivery_date: string | null;
  occasion_datetime: string | null;
  paid: string;
  pivot: TClothOrderPivot;
  remaining: string;
  return_date: string | null;
  status: string;
  total_price: string;
  visit_datetime: string | null;
};

export type TClothOrdersMeta = {
  buy_count: number;
  rent_count: number;
  tailoring_count: number;
  total: number;
  payment?: {
    orders_fully_paid: number;
    orders_with_balance: number;
    payments_count: number;
    total_amount: number;
    total_paid: number;
    total_remaining: number;
  };
};

export type TClothOrdersResponse = {
  cloth_code: string;
  cloth_id: number;
  orders: TClothOrderItem[];
  total: number;
  meta?: TClothOrdersMeta;
};
