import type { TOrder, TOrderType } from "@/api/v2/orders/orders.types";
import { getItemListDisplay } from "@/api/v2/orders/order.utils";
import type {
  DeliveryInvoiceProject,
  ProjectDeliveryStatus,
  ProjectPaymentStatus,
} from "./deliveryInvoiceProject.types";

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

function mapPaymentStatus(order: TOrder): ProjectPaymentStatus {
  const rem = Number.parseFloat(String(order.remaining ?? "0")) || 0;
  const paid = Number.parseFloat(String(order.paid ?? "0")) || 0;
  if (rem <= 0 && paid > 0) return "مدفوع";
  if (paid > 0 && rem > 0) return "مدفوع جزئياً";
  return "غير مدفوع";
}

function mapDeliveryStatus(order: TOrder): ProjectDeliveryStatus {
  if (order.status === "canceled") return "ملغي";
  if (order.status === "finished") return "تم الاسترجاع";
  if (order.status === "delivered") return "تم التسليم";

  const eventYmd = toYmd(order.occasion_datetime);
  const todayYmd = new Date().toISOString().slice(0, 10);

  if (
    order.status === "created" ||
    order.status === "paid" ||
    order.status === "partially_paid"
  ) {
    if (eventYmd && eventYmd < todayYmd) return "متأخر";
    return "في الانتظار";
  }

  return "في الانتظار";
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

/** صف واحد للواجهة المطابقة للمشروع + الطلب الأصلي للـ API */
export function mapOrderToDeliveryInvoiceProject(order: TOrder): DeliveryInvoiceProject {
  const phones = order.client?.phones ?? [];
  const phone0 = phones[0]?.phone ?? "-";
  const phone1 = phones[1]?.phone ?? phone0;

  const items: DeliveryInvoiceProject["items"] =
    order.items?.length > 0
      ? order.items.map((it) => ({
          name: getItemListDisplay(it as Record<string, unknown>),
          type: orderTypeToAr(order.order_type),
        }))
      : [{ name: "-", type: orderTypeToAr(order.order_type) }];

  const total = Number.parseFloat(String(order.total_price ?? "0")) || 0;
  const paid = Number.parseFloat(String(order.paid ?? "0")) || 0;
  const remaining = Number.parseFloat(String(order.remaining ?? "0")) || 0;

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
    },
    items,
    pricing: { total, paid, remaining },
    paymentStatus: mapPaymentStatus(order),
    deliveryStatus: mapDeliveryStatus(order),
    employee:
      order.employee_name?.trim() ||
      order.employee?.user?.name?.trim() ||
      "-",
    branch: order.branch?.name?.trim() || "-",
    notes: order.order_notes?.trim() ?? "",
  };
}
