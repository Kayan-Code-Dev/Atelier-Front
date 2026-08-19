import { TAddressResponse } from "../address/address.types";
import { TInventoryResponse } from "../inventory/inventory.types";

export type TCreateFactoryRequest = {
  factory_code: string;
  name: string;
  address: {
    street: string;
    building: string;
    city_id: number;
    notes: string;
  };
  inventory_name: string;
  status?: string;
};

export type TFactoryResponse = {
  id: number;
  factory_code: string;
  name: string;
  deleted_at: string | null;
  created_at: string;
  updated_at: string;
  address: TAddressResponse;
  inventory: TInventoryResponse;
  /** من الخادم إن وُجد (نشط / مغلق / قيد الإنشاء) */
  status?: string | null;
};


export type TUpdateFactoryRequest = Partial<TCreateFactoryRequest>;