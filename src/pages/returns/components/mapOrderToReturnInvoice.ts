import type { TOrder, TOrderType } from "@/api/v2/orders/orders.types";
import { getItemListDisplay } from "@/api/v2/orders/order.utils";
import type {
  ReturnInvoiceProject,
  ReturnDeliveryStatus,
  ReturnPaymentStatus,
  ReturnType,
  ReturnPenaltyInfo,
  PenaltyStatus,
} from "./returnInvoiceProject.types";

function toYmd(iso: string | null | undefined): string {
  if (!iso) return "";
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return "";
  return d.toISOString().slice(0, 10);
}

function orderTypeToAr(t: TOrderType): "إيجار" | "بيع" | "تفصيل" {
  if (t === "rent") return "إيجار";
  if (t === "buy") return "بيع";
  return "تفصيل";
}

function mapPaymentStatus(order: TOrder): ReturnPaymentStatus {
  const rem = Number.parseFloat(String(order.remaining ?? "0")) || 0;
  const paid = Number.parseFloat(String(order.paid ?? "0")) || 0;
  if (rem <= 0 && paid > 0) return "مدفوع";
  if (paid > 0 && rem > 0) return "مدفوع جزئياً";
  return "غير مدفوع";
}

function mapDeliveryStatus(order: TOrder): ReturnDeliveryStatus {
  if (order.status === "canceled") return "مرفوض";
  if (order.status === "finished") return "تم الاسترجاع";
  if (order.is_overdue) return "متأخر";
  return "في الانتظار";
}

function daysBetween(a: string, b: string): number {
  const da = new Date(a);
  const db = new Date(b);
  if (Number.isNaN(da.getTime()) || Number.isNaN(db.getTime())) return 0;
  return Math.max(0, Math.ceil((db.getTime() - da.getTime()) / 86_400_000));
}

function computeReturnType(order: TOrder): ReturnType {
  const returnDateStr = order.visit_datetime;
  if (!returnDateStr) return "مجدول";

  const returnDate = new Date(returnDateStr);
  if (Number.isNaN(returnDate.getTime())) return "مجدول";

  const now = new Date();
  now.setHours(0, 0, 0, 0);
  returnDate.setHours(0, 0, 0, 0);

  if (order.status === "finished") {
    const actualDate = order.updated_at ? new Date(order.updated_at) : now;
    actualDate.setHours(0, 0, 0, 0);
    if (actualDate <= returnDate) return "فوري";
    return "متأخر";
  }

  if (returnDate < now) return "متأخر";
  return "مجدول";
}

function computePenalty(order: TOrder): ReturnPenaltyInfo {
  const total = Number.parseFloat(String(order.total_price ?? "0")) || 0;
  const penaltyRate = Math.max(50, Math.round(total * 0.04));

  const returnDateStr = order.visit_datetime;
  if (!returnDateStr) {
    return {
      penaltyPerDay: penaltyRate,
      delayDays: 0,
      totalPenalty: 0,
      status: "لا توجد",
      productCondition: "",
      reason: "",
    };
  }

  const returnDate = new Date(returnDateStr);
  if (Number.isNaN(returnDate.getTime())) {
    return {
      penaltyPerDay: penaltyRate,
      delayDays: 0,
      totalPenalty: 0,
      status: "لا توجد",
      productCondition: "",
      reason: "",
    };
  }

  let referenceDate: Date;
  let penaltyStatus: PenaltyStatus;

  if (order.status === "finished") {
    referenceDate = order.updated_at ? new Date(order.updated_at) : new Date();
    penaltyStatus = "محصّلة";
  } else {
    referenceDate = new Date();
    penaltyStatus = "غير محصّلة";
  }

  const delayDays = daysBetween(returnDateStr, referenceDate.toISOString());

  if (delayDays <= 0) {
    return {
      penaltyPerDay: penaltyRate,
      delayDays: 0,
      totalPenalty: 0,
      status: "لا توجد",
      productCondition: "",
      reason: "",
    };
  }

  return {
    penaltyPerDay: penaltyRate,
    delayDays,
    totalPenalty: penaltyRate * delayDays,
    status: penaltyStatus,
    productCondition: "",
    reason: order.order_notes?.trim() ?? "",
  };
}

function formatClientAddress(order: TOrder): string {
  const a = order.client?.address;
  if (!a) return "-";
  return [
    a.country_name ?? "",
    a.city_name ? ` - ${a.city_name}` : "",
    a.street ? ` - ${a.street}` : "",
    a.building ? ` - ${a.building}` : "",
  ].join("");
}

export function mapOrderToReturnInvoice(order: TOrder): ReturnInvoiceProject {
  const phones = order.client?.phones ?? [];
  const phone0 = phones[0]?.phone ?? "-";
  const phone1 = phones[1]?.phone ?? phone0;

  const items: ReturnInvoiceProject["items"] =
    order.items?.length > 0
      ? order.items.map((it) => ({
          name: getItemListDisplay(it as Record<string, unknown>),
          type: orderTypeToAr(order.order_type),
        }))
      : [{ name: "-", type: orderTypeToAr(order.order_type) }];

  const total = Number.parseFloat(String(order.total_price ?? "0")) || 0;
  const paid = Number.parseFloat(String(order.paid ?? "0")) || 0;
  const remaining = Number.parseFloat(String(order.remaining ?? "0")) || 0;

  const actualReturnDate =
    order.status === "finished" ? toYmd(order.updated_at) : "";

  return {
    id: order.id,
    invoiceRef: String(order.id),
    customer: {
      name: order.client?.name ?? "-",
      nationalId: order.client?.national_id ?? "-",
      phone: phone0,
      whatsapp: phone1,
      address: formatClientAddress(order),
    },
    dates: {
      invoiceDate: toYmd(order.created_at),
      pickupDate: toYmd(order.delivery_date),
      eventDate: toYmd(order.occasion_datetime),
      returnDate: toYmd(order.visit_datetime),
      actualReturnDate,
    },
    items,
    pricing: { total, paid, remaining },
    paymentStatus: mapPaymentStatus(order),
    deliveryStatus: mapDeliveryStatus(order),
    returnType: computeReturnType(order),
    penalty: computePenalty(order),
    employee:
      order.employee_name?.trim() ||
      order.employee?.user?.name?.trim() ||
      "-",
    branch: order.branch?.name?.trim() || "-",
    notes: order.order_notes?.trim() ?? "",
    isOverdue: !!order.is_overdue,
  };
}
