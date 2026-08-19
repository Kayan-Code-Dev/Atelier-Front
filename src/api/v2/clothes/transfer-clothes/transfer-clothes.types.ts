import { TEntity } from "@/lib/types/entity.types";

export type CreateTransferClothesRequest = {
  from_entity_type: TEntity;
  from_entity_id: number;
  to_entity_type: TEntity;
  to_entity_id: number;
  cloth_ids: number[];
  transfer_date: string;
  notes?: string;
};

export type TTransferClothesStatus = "pending" | "approved" | "rejected" | "partially_pending" | "partially_approved";

export type TTransferClothesItem = {
  id: number;
  from_entity_type: TEntity;
  from_entity_id: number;
  to_entity_type: TEntity;
  to_entity_id: number;
  transfer_date: string;
  notes: string;
  status: TTransferClothesStatus;
  created_at: string;
  updated_at: string;
  from_entity_name: string;
  to_entity_name: string;
  items: {
    cloth_id: number;
    cloth_code: string;
    cloth_name: string;
    status: TTransferClothesStatus;
    id : number
  }[];
};

export type TUpdateTransferClothesRequest = {
  cloth_ids: number[];
  transfer_date: string;
  notes: string;
};

export type TGetTransferClothesQuery = {
  page?: number;
  per_page?: number;
  status?: TTransferClothesStatus;
};
