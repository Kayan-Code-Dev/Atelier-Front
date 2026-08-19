import type { TOrder } from "@/api/v2/orders/orders.types";
import { formatDate } from "@/utils/formatDate";
import { getOrderCurrencyInfo } from "@/api/v2/orders/order.utils";
import { formatPhone } from "@/utils/formatPhone";
import type {
  DeliveryInvoiceTypeAr,
  DeliveryPaymentStatusAr,
  DeliverySearchRow,
  DeliverySearchStatus,
} from "./deliverySearch.types";

function parseDay(d: string | null | undefined): Date | null {
  if (!d) return null;
  const x = new Date(d);
  return Number.isNaN(x.getTime()) ? null : x;
}

function startOfDay(d: Date): Date {
  return new Date(d.getFullYear(), d.getMonth(), d.getDate());
}

export function mapOrderTypeToInvoiceType(o: TOrder): DeliveryInvoiceTypeAr {
  if (o.order_type === "rent") return "إيجار";
  if (o.order_type === "tailoring") return "تفصيل";
  return "بيع";
}

export function deriveDeliveryStatus(order: TOrder): DeliverySearchStatus {
  if (order.status === "canceled") return "ملغي";

  const today = startOfDay(new Date());

  if (order.order_type === "rent") {
    if (order.status === "finished") return "تم الإرجاع";
    if (order.status === "delivered") {
      const visit = parseDay(order.visit_datetime);
      if (visit && startOfDay(visit) < today) return "تأخر الإرجاع";
      return "ينتظر الإرجاع";
    }
    const del = parseDay(order.delivery_date ?? null);
    if (del && startOfDay(del) < today) return "تأخر التسليم";
    return "ينتظر التسليم";
  }

  if (order.order_type === "buy" || order.order_type === "mixed") {
    if (order.status === "delivered" || order.status === "finished") {
      return "تم التسليم";
    }
    const del = parseDay(order.delivery_date ?? null);
    if (del && startOfDay(del) < today) return "تأخر التسليم";
    return "ينتظر التسليم";
  }

  if (order.order_type === "tailoring") {
    if (order.status === "delivered" || order.status === "finished") {
      return "تم التسليم";
    }
    if (order.actual_completion_date) {
      return "جاهز للاستلام";
    }
    const exp = parseDay(order.expected_completion_date ?? null);
    if (exp && startOfDay(exp) < today) return "تأخر التسليم";
    return "قيد التنفيذ";
  }

  if (order.status === "delivered") return "تم التسليم";
  if (order.status === "finished") return "تم الإرجاع";
  return "قيد التنفيذ";
}

function paymentLabel(order: TOrder): DeliveryPaymentStatusAr {
  if (order.status === "canceled") return "غير مدفوع";
  const rem = Number(order.remaining);
  const total = Number(order.total_price);
  if (!Number.isFinite(rem) || !Number.isFinite(total)) return "غير مدفوع";
  if (rem <= 0) return "مدفوع بالكامل";
  if (rem >= total) return "غير مدفوع";
  return "مدفوع جزئياً";
}

export function mapOrderToDeliveryRow(order: TOrder): DeliverySearchRow {
  const { currency_symbol } = getOrderCurrencyInfo(order);
  const phones = order.client?.phones;
  const phone =
    phones && phones.length > 0
      ? formatPhone(phones[0]?.phone, "") || "—"
      : "—";

  const returnDate =
    order.order_type === "rent" && order.visit_datetime
      ? formatDate(order.visit_datetime)
      : "";

  return {
    order,
    id: String(order.id),
    invoiceNumber: `#${order.id}`,
    invoiceType: mapOrderTypeToInvoiceType(order),
    invoiceDate: formatDate(order.created_at),
    customerName: order.client?.name?.trim() || "—",
    customerPhone: phone,
    branchName: order.branch?.name?.trim() || "—",
    deliveryDate: order.delivery_date
      ? formatDate(order.delivery_date)
      : "—",
    returnDate,
    deliveryStatus: deriveDeliveryStatus(order),
    paymentStatus: paymentLabel(order),
    totalAmount: Number(order.total_price) || 0,
    remaining: Number(order.remaining) || 0,
    currencySymbol: currency_symbol || "ج.م",
  };
}
