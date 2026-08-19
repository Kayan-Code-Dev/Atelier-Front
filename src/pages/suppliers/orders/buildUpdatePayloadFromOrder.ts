import type {
  TSupplierOrderResponse,
  TUpdateSupplierOrderRequest,
} from "@/api/v2/suppliers/suppliers.types";
import { resolveClothId } from "@/api/v2/suppliers/suppliers.types";

function parseMoney(v: string | number | null | undefined): number {
  if (v == null || v === "") return 0;
  const n = typeof v === "number" ? v : parseFloat(String(v));
  return Number.isNaN(n) ? 0 : n;
}

/**
 * Builds PUT payload from list-row order. Returns null if `clothes` are missing
 * (status cannot be updated safely without opening the edit form).
 */
export function buildUpdatePayloadFromOrder(
  o: TSupplierOrderResponse,
  newStatusApi: string,
): TUpdateSupplierOrderRequest | null {
  const clothesRaw = o.clothes ?? [];
  if (clothesRaw.length === 0) return null;

  const clothes = clothesRaw.map((c) => {
    const price =
      typeof c.price === "number" ? c.price : parseMoney(c.price);
    const payment =
      typeof c.payment === "number" ? c.payment : parseMoney(c.payment);
    return {
      cloth_id: resolveClothId(c),
      price,
      payment,
      remaining: Math.max(0, price - payment),
      notes: c.notes?.trim() || null,
      category_id: c.category_id ?? undefined,
      subcategory_ids:
        c.subcategory_ids?.length && c.subcategory_ids.length > 0
          ? c.subcategory_ids
          : o.subcategory_id != null
            ? [o.subcategory_id]
            : undefined,
    };
  });

  const total = parseMoney(o.total_amount);
  const paid = parseMoney(o.payment_amount);

  return {
    supplier_id: o.supplier_id,
    category_id: o.category_id ?? clothes[0].category_id ?? 0,
    subcategory_id: o.subcategory_id ?? 0,
    branch_id: o.branch_id ?? 0,
    order_number: o.order_number,
    type: o.type ?? undefined,
    order_date: (o.order_date || "").slice(0, 10),
    status: newStatusApi,
    total_amount: total,
    payment_amount: paid,
    remaining_payment: Math.max(0, total - paid),
    notes: o.notes?.trim() || null,
    clothes,
  };
}
