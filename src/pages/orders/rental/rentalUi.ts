
import type { TOrder } from "@/api/v2/orders/orders.types";

export type RentalUiStatus = "نشط" | "مرتجع" | "متأخر" | "ملغي";
export type RentalUiPayment = "مدفوع بالكامل" | "مدفوع جزئياً" | "غير مدفوع";

export function getRentalUiStatus(order: TOrder): RentalUiStatus {
  if (order.status === "canceled") return "ملغي";
  if (order.is_overdue) return "متأخر";
  if (order.status === "finished" || order.is_returned) return "مرتجع";
  return "نشط";
}

export function getRentalUiPayment(order: TOrder): RentalUiPayment {
  if (order.status === "partially_paid") return "مدفوع جزئياً";
  if (order.status === "paid") return "مدفوع بالكامل";
  const rem = Number(order.remaining ?? 0);
  if (order.status === "finished" && rem <= 0) return "مدفوع بالكامل";
  const paid = Number(order.paid ?? 0);
  const total = Number(order.total_price ?? 0);
  if (paid > 0 && rem > 0) return "مدفوع جزئياً";
  if (total > 0 && rem <= 0 && paid > 0) return "مدفوع بالكامل";
  return "غير مدفوع";
}


export const rentalStatusColors: Record<RentalUiStatus, string> = {
  نشط: "bg-emerald-50 text-emerald-700 border border-emerald-200",
  مرتجع: "bg-slate-50 text-slate-600 border border-slate-200",
  متأخر: "bg-red-50 text-red-700 border border-red-200",
  ملغي: "bg-gray-50 text-gray-600 border border-gray-200",
};


export const paymentStatusColors: Record<RentalUiPayment, string> = {
  "مدفوع بالكامل": "bg-emerald-50 text-emerald-700 border border-emerald-200",
  "مدفوع جزئياً": "bg-amber-50 text-amber-700 border border-amber-200",
  "غير مدفوع": "bg-red-50 text-red-700 border border-red-200",
};
