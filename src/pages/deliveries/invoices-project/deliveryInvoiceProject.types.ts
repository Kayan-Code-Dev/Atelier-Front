/** نفس شكل mock المشروع: project/src/mocks/deliveries.ts */
export type ProjectPaymentStatus = "مدفوع" | "مدفوع جزئياً" | "غير مدفوع";

export type ProjectDeliveryStatus =
  | "في الانتظار"
  | "تم الاستلام"
  | "تم التسليم"
  | "تم الاسترجاع"
  | "متأخر"
  | "ملغي";

export interface DeliveryItemProject {
  name: string;
  type: "إيجار" | "بيع" | "تفصيل";
}

export interface DeliveryInvoiceProject {
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
  };
  items: DeliveryItemProject[];
  pricing: {
    total: number;
    paid: number;
    remaining: number;
  };
  paymentStatus: ProjectPaymentStatus;
  deliveryStatus: ProjectDeliveryStatus;
  employee: string;
  branch: string;
  notes: string;
}

export interface DeliveryInvoiceRow {
  invoice: DeliveryInvoiceProject;
}
