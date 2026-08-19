import type { TOrder } from "@/api/v2/orders/orders.types";
import {
  getRentalUiPayment,
  paymentStatusColors,
} from "@/pages/orders/rental/rentalUi";

export const salePaymentColors = paymentStatusColors as Record<string, string>;

export type SaleInvoiceUiStatus = "مكتملة" | "معلقة" | "ملغية";

export function getSaleInvoiceStatusLabel(o: TOrder): SaleInvoiceUiStatus {
  if (o.status === "canceled") return "ملغية";
  if (o.status === "delivered" || o.status === "finished" || o.status === "paid") {
    return "مكتملة";
  }
  return "معلقة";
}

export function getSalePaymentLabel(o: TOrder): string {
  return getRentalUiPayment(o);
}

function saleInvoiceMatchesSearch(order: TOrder, search: string): boolean {
  const q = search.trim();
  if (!q) return true;
  const name = order.client?.name ?? "";
  const nationalId = String(order.client?.national_id ?? "");
  const phones = (order.client?.phones ?? [])
    .map((p) => String(p?.phone ?? ""))
    .join(" ");
  const invoiceNumber = String(order.id);
  const branchName =
    order.branch?.name ?? order.inventory?.inventoriable?.name ?? "";
  return (
    name.includes(q) ||
    phones.includes(q) ||
    nationalId.includes(q) ||
    invoiceNumber.includes(q) ||
    branchName.includes(q)
  );
}

export type SoldListFilterParams = {
  search: string;
  statusFilter: string;
  paymentFilter: string;
};

export function filterSoldInvoices(
  orders: TOrder[],
  { search, statusFilter, paymentFilter }: SoldListFilterParams
): TOrder[] {
  return orders.filter((inv) => {
    const matchSearch = saleInvoiceMatchesSearch(inv, search);
    const st = getSaleInvoiceStatusLabel(inv);
    const matchStatus = !statusFilter || st === statusFilter;
    const pay = getSalePaymentLabel(inv);
    const matchPayment = !paymentFilter || pay === paymentFilter;
    return matchSearch && matchStatus && matchPayment;
  });
}

export const saleStatusColors: Record<string, string> = {
  مكتملة: "bg-emerald-50 text-emerald-700 border border-emerald-200",
  معلقة: "bg-amber-50 text-amber-700 border border-amber-200",
  ملغية: "bg-red-50 text-red-700 border border-red-200",
};

export function formatSaleInvoiceDate(iso: string | undefined): string {
  if (!iso) return "—";
  try {
    const d = new Date(iso);
    if (isNaN(d.getTime())) return iso;
    return d.toLocaleDateString("ar-SA", {
      year: "numeric",
      month: "2-digit",
      day: "2-digit",
    });
  } catch {
    return iso;
  }
}
