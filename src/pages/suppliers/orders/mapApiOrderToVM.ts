import {
  resolveClothId,
  type TSupplierOrderResponse,
} from "@/api/v2/suppliers/suppliers.types";
import type {
  SupplierOrderItemVM,
  SupplierOrderStatusAr,
  SupplierOrderVM,
} from "./types";

function parseMoney(v: string | number | null | undefined): number {
  if (v == null || v === "") return 0;
  const n = typeof v === "number" ? v : parseFloat(String(v));
  return Number.isNaN(n) ? 0 : n;
}

/** Map API order status to Arabic labels used in project mock */
export function apiStatusToDisplay(status: string): SupplierOrderStatusAr {
  const x = (status || "").toLowerCase();
  if (x.includes("cancel") || x.includes("ملغ")) return "ملغي";
  if (x.includes("deliver") || x.includes("تسليم")) return "مستلم";
  if (x.includes("confirm")) return "مُوَّرد";
  if (x.includes("pending") || x.includes("انتظار")) return "قيد الانتظار";
  if (x.includes("complete") || x.includes("مكتمل")) return "مستلم";
  return "قيد الانتظار";
}

export function displayStatusToApi(s: SupplierOrderStatusAr): string {
  switch (s) {
    case "ملغي":
      return "cancelled";
    case "مستلم":
      return "delivered";
    case "مُوَّرد":
      return "confirmed";
    case "قيد الانتظار":
    default:
      return "pending";
  }
}

function addDaysYmd(ymd: string, days: number): string {
  try {
    const d = new Date(ymd.includes("T") ? ymd : `${ymd}T12:00:00`);
    if (Number.isNaN(d.getTime())) return ymd.slice(0, 10);
    d.setDate(d.getDate() + days);
    return d.toISOString().slice(0, 10);
  } catch {
    return ymd.slice(0, 10);
  }
}

function ymdFromIso(s?: string | null): string {
  if (!s) return "";
  return s.slice(0, 10);
}

export function mapApiOrderToVM(o: TSupplierOrderResponse): SupplierOrderVM {
  const totalAmount = parseMoney(o.total_amount);
  const paidAmount = parseMoney(o.payment_amount);
  const orderDate = ymdFromIso(o.order_date) || ymdFromIso(o.created_at);
  const displaySt = apiStatusToDisplay(o.status);
  const items: SupplierOrderItemVM[] =
    o.clothes && o.clothes.length > 0
      ? o.clothes.map((c) => {
          const price = typeof c.price === "number" ? c.price : parseMoney(c.price);
          return {
            name: (c.name || c.code || `قطعة #${resolveClothId(c)}`).trim(),
            quantity: 1,
            unit: "قطعة",
            unitPrice: price,
            total: price,
          };
        })
      : [
          {
            name:
              [o.category?.name, o.subcategory?.name].filter(Boolean).join(" — ") ||
              "طلبية",
            quantity: 1,
            unit: "طلبية",
            unitPrice: totalAmount,
            total: totalAmount,
          },
        ];

  const receivedDate =
    ymdFromIso(o.received_at) ||
    (o.inventory_received || displaySt === "مستلم"
      ? ymdFromIso(o.updated_at) || orderDate
      : "");

  return {
    id: o.id,
    orderRef: o.order_number || String(o.id),
    supplierId: o.supplier_id,
    supplierName: o.supplier?.name ?? "—",
    supplierCode: o.supplier?.code ?? "—",
    supplierPhone: o.supplier?.phone ?? "",
    items,
    totalAmount,
    paidAmount,
    orderDate,
    expectedDate: addDaysYmd(orderDate || ymdFromIso(o.created_at), 14),
    receivedDate,
    status: displaySt,
    notes: (o.notes || "").trim(),
    branch: o.branch?.name ?? "—",
    employee: "—",
    _raw: o,
  };
}
