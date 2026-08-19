
export const RENTAL_STATUS_FILTER_OPTIONS = [
  "الكل",
  "نشط",
  "مرتجع",
  "متأخر",
  "ملغي",
] as const;

export const RENTAL_PAYMENT_FILTER_OPTIONS = [
  "الكل",
  "مدفوع بالكامل",
  "مدفوع جزئياً",
  "غير مدفوع",
] as const;

export type RentalStatusFilterOption = (typeof RENTAL_STATUS_FILTER_OPTIONS)[number];
export type RentalPaymentFilterOption = (typeof RENTAL_PAYMENT_FILTER_OPTIONS)[number];
