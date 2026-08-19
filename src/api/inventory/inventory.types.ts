export type TInventoryItem = {
  id: number;
  name: string;
  category_name: string;
  sub_category_name: string;
  quantity: string;
  updated_at: string;
  status: "منخفضة" | "كافية" | "مرتفع";
  branch_name: string;
};

export type TBranchInventoryItem = TInventoryItem & {
  code: string;
  category_id: number;
  sub_category_id: number;
  price: string;
  type: TInventoryItemType;
};

export type TInventoryItemType = "raw" | "product";

export type TInventoryTransfersItem = {
  uuid: string;
  product_name: string;
  quantity: string;
  from_branch_name: string;
  to_branch_name: string;
  transfer_date: string;
  arrival_date: string;
  status: "قيد الانتظار" | "تم القبول" | "تم الرفض" | "تم الوصول";
};

export type TEmployeeInvantoryItem = {
  id: number;
  code: string;
  name: string;
  price: string;
  type: TInventoryItemType;
  category_id: string;
  category_name: string;
  sub_category_name: string;
  sub_category_id: string;
  quantity: string;
  updated_at: string;
  status: "منخفضة" | "كافية" | "مرتفع";
}