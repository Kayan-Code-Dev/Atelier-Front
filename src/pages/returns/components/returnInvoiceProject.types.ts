export type ReturnPaymentStatus = "مدفوع" | "مدفوع جزئياً" | "غير مدفوع";

export type ReturnDeliveryStatus =
  | "في الانتظار"
  | "تم الاسترجاع"
  | "متأخر"
  | "مرفوض";

export type ReturnType = "فوري" | "مجدول" | "متأخر";

export type PenaltyStatus = "محصّلة" | "غير محصّلة" | "لا توجد";

export interface ReturnItemProject {
  name: string;
  type: "إيجار" | "بيع" | "تفصيل";
}

export interface ReturnPenaltyInfo {
  penaltyPerDay: number;
  delayDays: number;
  totalPenalty: number;
  status: PenaltyStatus;
  productCondition: string;
  reason: string;
}

export interface ReturnInvoiceProject {
  id: number;
  invoiceRef: string;
  customer: {
    name: string;
    nationalId: string;
    phone: string;
    whatsapp: string;
    address: string;
  };
  dates: {
    invoiceDate: string;
    pickupDate: string;
    eventDate: string;
    returnDate: string;
    actualReturnDate: string;
  };
  items: ReturnItemProject[];
  pricing: {
    total: number;
    paid: number;
    remaining: number;
  };
  paymentStatus: ReturnPaymentStatus;
  deliveryStatus: ReturnDeliveryStatus;
  returnType: ReturnType;
  penalty: ReturnPenaltyInfo;
  employee: string;
  branch: string;
  notes: string;
  isOverdue: boolean;
}

/** A table row combining the mapped invoice with the raw TOrder */
export interface ReturnInvoiceTableRow {
  invoice: ReturnInvoiceProject;
  order: import("@/api/v2/orders/orders.types").TOrder;
}
