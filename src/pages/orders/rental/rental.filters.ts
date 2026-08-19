
import type { TOrder } from "@/api/v2/orders/orders.types";
import { getRentalUiPayment, getRentalUiStatus } from "./rentalUi";

function rentalInvoiceMatchesSearch(order: TOrder, search: string): boolean {
  const q = search.trim();
  if (!q) return true;
  const name = order.client?.name ?? "";
  const nationalId = String(order.client?.national_id ?? "");
  const phones = (order.client?.phones ?? [])
    .map((p) => String(p?.phone ?? ""))
    .join(" ");
  const invoiceNumber = String(order.id);
  const employeeName =
    order.employee_name ??
    order.employee?.user?.name ??
    order.employee?.user?.email ??
    "";
  return (
    name.includes(q) ||
    phones.includes(q) ||
    nationalId.includes(q) ||
    invoiceNumber.includes(q) ||
    employeeName.includes(q)
  );
}

export type RentalListFilterParams = {
  search: string;
  statusFilter: string;
  paymentFilter: string;
};


export function filterRentalInvoices(
  orders: TOrder[],
  { search, statusFilter, paymentFilter }: RentalListFilterParams
): TOrder[] {
  return orders.filter((inv) => {
    const matchSearch = rentalInvoiceMatchesSearch(inv, search);
    const matchStatus = statusFilter === "الكل" || getRentalUiStatus(inv) === statusFilter;
    const matchPayment =
      paymentFilter === "الكل" || getRentalUiPayment(inv) === paymentFilter;
    return matchSearch && matchStatus && matchPayment;
  });
}
