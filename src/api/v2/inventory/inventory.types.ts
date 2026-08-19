export type TInventoryResponse = {
  id: number;
  name: string;
  deleted_at: string | null;
  created_at: string;
  updated_at: string;
  inventoriable_type: string;
  inventoriable_id: string;
};
