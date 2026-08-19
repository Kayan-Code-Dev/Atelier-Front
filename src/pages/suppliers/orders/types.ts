import type { TSupplierOrderResponse } from "@/api/v2/suppliers/suppliers.types";

export type SupplierOrderStatusAr =
  | "قيد الانتظار"
  | "مُوَّرد"
  | "مستلم"
  | "ملغي";

export interface SupplierOrderItemVM {
  name: string;
  quantity: number;
  unit: string;
  unitPrice: number;
  total: number;
}

/** View model for project-style suppliers/orders UI */
export interface SupplierOrderVM {
  id: number;
  orderRef: string;
  supplierId: number;
  supplierName: string;
  supplierCode: string;
  supplierPhone: string;
  items: SupplierOrderItemVM[];
  totalAmount: number;
  paidAmount: number;
  orderDate: string;
  expectedDate: string;
  receivedDate: string;
  status: SupplierOrderStatusAr;
  notes: string;
  branch: string;
  employee: string;
  _raw: TSupplierOrderResponse;
}
