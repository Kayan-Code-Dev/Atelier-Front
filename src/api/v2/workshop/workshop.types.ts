import { TAddressResponse } from "../address/address.types";
import { TClothResponse } from "../clothes/clothes.types";
import { TTransferClothesItem } from "../clothes/transfer-clothes/transfer-clothes.types";
import { TInventoryResponse } from "../inventory/inventory.types";

export type TCreateWorkshopRequest = {
  workshop_code: string;
  name: string;
  address: {
    street: string;
    building: string;
    city_id: number;
    notes: string;
  };
  inventory_name: string;
};

export type TWorkshopResponse = {
  id: number;
  workshop_code: string;
  name: string;
  address_id: string;
  deleted_at: string | null;
  created_at: string;
  updated_at: string;
  inventory: TInventoryResponse;
  address: TAddressResponse;
  /** من الخادم إن وُجد (نشط / مغلق / قيد الإنشاء) */
  status?: string | null;
};

export type TUpdateWorkshopRequest = Partial<TCreateWorkshopRequest>;

export type TWorkshopClothStatus =
  | "processing"
  | "received"
  | "ready_for_delivery";

export type TworkshopCloth = {
  id: number;
  code: string;
  name: string;
  workshop_status: TWorkshopClothStatus;
  workshop_notes: string;
  received_at: string;
};

export type TWorkshopTransfer = TTransferClothesItem;

export type TWorkshopClothHistory = {
  cloth: TClothResponse;
  current_status: TWorkshopClothStatus;
  is_in_workshop: boolean;
  history: TWorkshopClothHistoryItem[];
};

export type TWorkshopClothHistoryAction =
  | "received"
  | "status_changed"
  | "returned";

export type TWorkshopClothHistoryItem = {
  id: number;
  workshop_id: number;
  cloth_id: number;
  transfer_id: number | null;
  action: TWorkshopClothHistoryAction;
  cloth_status: TWorkshopClothStatus;
  notes: string;
  received_at: string | null;
  returned_at: string | null;
  user_id: number;
  created_at: string;
  updated_at: string;
  transfer: any;
  user: {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    deleted_at: string | null;
    created_at: string;
    updated_at: string;
  };
};
