import { TOrder } from "../orders/orders.types";

export type TCreateCustodyRequest = {
  type: TCustodyType;
  description: string;
  value?: number; // only for money type
  photos?: File[]; // only for physical_item type or document type , max 2 photos
  notes: string;
};

export type TCustodyType = "money" | "physical_item" | "document";

export type TCustodyStatus = "pending" | "returned" | "lost";

export type TOrderCustody = {
  id: number;
  order_id: number;
  type: TCustodyType;
  description: string;
  value?: number; // only for money type
  photos?: {photo_url : string, id : number}[]; // only for physical_item type or document type , max 2 photos
  notes: string;
  status: TCustodyStatus;
  returned_at: string | null;
  created_at: string;
  updated_at: string;
  deleted_at: string | null;
  order: TOrder;
  returns: any[];
};

export type TGetAllCustodiesParams = {
  order_id: number;
  client_id: number;
  page: number;
  per_page: number;
};

export type TCustodyAction = "returned_to_user" | "forfeit";

export type TReturnCustodyRequest = {
  custody_action: TCustodyAction;
  notes: string;
  reason_of_kept?: string;
  acknowledgement_receipt_photos: File[];
};
