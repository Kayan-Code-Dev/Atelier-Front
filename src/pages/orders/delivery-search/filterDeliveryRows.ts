import type { DeliverySearchRow, DeliverySearchUiFilters } from "./deliverySearch.types";

export function applyDeliveryUiFilters(
  rows: DeliverySearchRow[],
  f: DeliverySearchUiFilters
): DeliverySearchRow[] {
  return rows.filter((r) => {
    const q = f.search.trim().toLowerCase();
    if (q) {
      const idMatch = String(r.order.id).includes(f.search.trim());
      if (
        !r.customerName.toLowerCase().includes(q) &&
        !r.invoiceNumber.toLowerCase().includes(q) &&
        !r.customerPhone.replace(/\s/g, "").includes(f.search.replace(/\s/g, "")) &&
        !idMatch
      ) {
        return false;
      }
    }
    if (f.invoiceType !== "الكل" && r.invoiceType !== f.invoiceType) {
      return false;
    }
    if (f.deliveryStatus !== "الكل" && r.deliveryStatus !== f.deliveryStatus) {
      return false;
    }
    if (f.branch !== "الكل" && r.branchName !== f.branch) {
      return false;
    }
    if (f.dateFrom) {
      const from = new Date(f.dateFrom);
      from.setHours(0, 0, 0, 0);
      const inv = new Date(r.order.created_at);
      if (inv < from) return false;
    }
    if (f.dateTo) {
      const to = new Date(f.dateTo);
      to.setHours(23, 59, 59, 999);
      const inv = new Date(r.order.created_at);
      if (inv > to) return false;
    }
    return true;
  });
}
