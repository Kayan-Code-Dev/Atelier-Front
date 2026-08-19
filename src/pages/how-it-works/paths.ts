import { SOLD_PROCESS_TYPE } from "@/lib/salesOrderConstants";

/** مسارات dressnmore المستخدمة في صفحة «كيف يعمل النظام» */
export const HOW_IT_WORKS_PATHS = {
  dashboard: "/dashboard",
  rentalList: "/orders/list",
  rentalCreate: "/orders/rental/create",
  salesList: `/orders/list?process_type=${SOLD_PROCESS_TYPE}`,  salesCreate: "/sales/create",
  tailoring: "/tailoring/orders",
  deliverySearch: "/orders/search-deliveries-returns",
  payments: "/payments",
  cashboxTransactions: "/cashboxes/transactions",
  employees: "/employees",
  suppliers: "/suppliers",
  branch: "/branch",
} as const;
