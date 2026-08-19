import type { DeliveryInvoiceTypeAr, DeliverySearchStatus } from "./deliverySearch.types";

export const deliveryStatusConfig: Record<
  DeliverySearchStatus,
  {
    label: string;
    color: string;
    bg: string;
    border: string;
    icon: string;
    dot: string;
  }
> = {
  "ينتظر التسليم": {
    label: "ينتظر التسليم",
    color: "text-amber-700",
    bg: "bg-amber-50",
    border: "border-amber-200",
    icon: "ri-time-line",
    dot: "bg-amber-500",
  },
  "تأخر التسليم": {
    label: "تأخر التسليم",
    color: "text-red-700",
    bg: "bg-red-50",
    border: "border-red-200",
    icon: "ri-alarm-warning-line",
    dot: "bg-red-500",
  },
  "جاهز للاستلام": {
    label: "جاهز للاستلام",
    color: "text-emerald-700",
    bg: "bg-emerald-50",
    border: "border-emerald-200",
    icon: "ri-gift-line",
    dot: "bg-emerald-500",
  },
  "تم التسليم": {
    label: "تم التسليم",
    color: "text-sky-700",
    bg: "bg-sky-50",
    border: "border-sky-200",
    icon: "ri-check-double-line",
    dot: "bg-sky-500",
  },
  "ينتظر الإرجاع": {
    label: "ينتظر الإرجاع",
    color: "text-violet-700",
    bg: "bg-violet-50",
    border: "border-violet-200",
    icon: "ri-arrow-go-back-line",
    dot: "bg-violet-500",
  },
  "تأخر الإرجاع": {
    label: "تأخر الإرجاع",
    color: "text-orange-700",
    bg: "bg-orange-50",
    border: "border-orange-200",
    icon: "ri-error-warning-line",
    dot: "bg-orange-500",
  },
  "تم الإرجاع": {
    label: "تم الإرجاع",
    color: "text-slate-600",
    bg: "bg-slate-50",
    border: "border-slate-200",
    icon: "ri-checkbox-circle-line",
    dot: "bg-slate-400",
  },
  "قيد التنفيذ": {
    label: "قيد التنفيذ",
    color: "text-indigo-700",
    bg: "bg-indigo-50",
    border: "border-indigo-200",
    icon: "ri-tools-line",
    dot: "bg-indigo-500",
  },
  ملغي: {
    label: "ملغي",
    color: "text-gray-500",
    bg: "bg-gray-100",
    border: "border-gray-200",
    icon: "ri-close-circle-line",
    dot: "bg-gray-400",
  },
};

export const invoiceTypeConfig: Record<
  DeliveryInvoiceTypeAr,
  { color: string; bg: string; border: string; icon: string }
> = {
  إيجار: {
    color: "text-rose-700",
    bg: "bg-rose-50",
    border: "border-rose-200",
    icon: "ri-key-2-line",
  },
  بيع: {
    color: "text-emerald-700",
    bg: "bg-emerald-50",
    border: "border-emerald-200",
    icon: "ri-shopping-bag-3-line",
  },
  تفصيل: {
    color: "text-violet-700",
    bg: "bg-violet-50",
    border: "border-violet-200",
    icon: "ri-scissors-cut-line",
  },
};

export const paymentStatusConfig: Record<
  string,
  { color: string; bg: string; border: string }
> = {
  "مدفوع بالكامل": {
    color: "text-emerald-700",
    bg: "bg-emerald-50",
    border: "border-emerald-200",
  },
  "مدفوع جزئياً": {
    color: "text-amber-700",
    bg: "bg-amber-50",
    border: "border-amber-200",
  },
  "غير مدفوع": {
    color: "text-red-700",
    bg: "bg-red-50",
    border: "border-red-200",
  },
};

export const borderByStatus: Record<DeliverySearchStatus, string> = {
  "ينتظر التسليم": "border-amber-400",
  "تأخر التسليم": "border-red-500",
  "جاهز للاستلام": "border-emerald-500",
  "تم التسليم": "border-sky-400",
  "ينتظر الإرجاع": "border-violet-400",
  "تأخر الإرجاع": "border-orange-500",
  "تم الإرجاع": "border-slate-300",
  "قيد التنفيذ": "border-indigo-400",
  ملغي: "border-gray-300",
};
