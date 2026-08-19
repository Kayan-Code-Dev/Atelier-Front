import type { TOrder } from "@/api/v2/orders/orders.types";

export type DeliveryInvoiceTypeAr = "إيجار" | "بيع" | "تفصيل";

export type DeliverySearchStatus =
  | "ينتظر التسليم"
  | "تأخر التسليم"
  | "جاهز للاستلام"
  | "تم التسليم"
  | "ينتظر الإرجاع"
  | "تأخر الإرجاع"
  | "تم الإرجاع"
  | "قيد التنفيذ"
  | "ملغي";

export type DeliveryPaymentStatusAr =
  | "مدفوع بالكامل"
  | "مدفوع جزئياً"
  | "غير مدفوع";

/** View-model for delivery-search UI (aligned with project delivery-search mock shape). */
export type DeliverySearchRow = {
  order: TOrder;
  id: string;
  invoiceNumber: string;
  invoiceType: DeliveryInvoiceTypeAr;
  invoiceDate: string;
  customerName: string;
  customerPhone: string;
  branchName: string;
  deliveryDate: string;
  returnDate: string;
  deliveryStatus: DeliverySearchStatus;
  paymentStatus: DeliveryPaymentStatusAr;
  totalAmount: number;
  remaining: number;
  currencySymbol: string;
};

export interface DeliverySearchUiFilters {
  search: string;
  invoiceType: DeliveryInvoiceTypeAr | "الكل";
  deliveryStatus: DeliverySearchStatus | "الكل";
  branch: string;
  dateFrom: string;
  dateTo: string;
}
