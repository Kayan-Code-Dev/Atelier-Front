import { TSupplierOrderResponse } from "@/api/v2/suppliers/suppliers.types";
import { toEnglishNumerals } from "@/utils/formatDate";

export function parseMoney(v: string | number | null | undefined): number {
  if (v == null || v === "") return 0;
  const n = typeof v === "number" ? v : parseFloat(String(v));
  return Number.isNaN(n) ? 0 : n;
}

export function formatAccountCurrency(value: number): string {
  return `${toEnglishNumerals(
    value.toLocaleString("en-US", { minimumFractionDigits: 2 }),
  )} ج.م`;
}

export function isCancelledOrder(o: TSupplierOrderResponse): boolean {
  const s = (o.status || "").toLowerCase();
  return s === "cancelled" || s.includes("ملغ");
}

export function isReturnedOrder(o: TSupplierOrderResponse): boolean {
  if (isCancelledOrder(o)) return false;
  const s = (o.status || "").toLowerCase();
  return (
    s.includes("return") ||
    s.includes("refund") ||
    s.includes("مرتجع") ||
    s.includes("returned")
  );
}

export function isDeliveredOrder(o: TSupplierOrderResponse): boolean {
  return (o.status || "").toLowerCase() === "delivered";
}

export function isActiveOrder(o: TSupplierOrderResponse): boolean {
  return !isCancelledOrder(o) && !isDeliveredOrder(o) && !isReturnedOrder(o);
}

export type SupplierStatementRow = {
  id: string;
  date: string;
  type: "طلبية" | "دفع" | "إرجاع";
  ref: string;
  description: string;
  debit: number;
  credit: number;
  balance: number;
};

/** Builds ledger lines from order list (snapshot, max 500 rows from API). */
export function buildStatementFromOrders(
  orders: TSupplierOrderResponse[],
): SupplierStatementRow[] {
  type Ev = {
    sort: number;
    id: string;
    date: string;
    type: SupplierStatementRow["type"];
    ref: string;
    description: string;
    debit: number;
    credit: number;
  };
  const events: Ev[] = [];

  for (const o of orders) {
    if (isCancelledOrder(o)) continue;
    const ts = new Date(o.order_date || o.created_at || 0).getTime();
    const total = parseMoney(o.total_amount);
    const paid = parseMoney(o.payment_amount);
    const ordRef = String(o.order_number || o.id);

    events.push({
      sort: ts,
      id: `p-${o.id}`,
      date: o.order_date || "",
      type: "طلبية",
      ref: ordRef,
      description: `طلبية شراء رقم ${ordRef}`,
      debit: total,
      credit: 0,
    });

    if (paid > 0) {
      events.push({
        sort: ts + 1,
        id: `pay-${o.id}`,
        date: o.order_date || "",
        type: "دفع",
        ref: ordRef,
        description: `دفعة مسجّلة على الطلبية ${ordRef}`,
        debit: 0,
        credit: paid,
      });
    }

    if (isReturnedOrder(o)) {
      events.push({
        sort: ts + 2,
        id: `r-${o.id}`,
        date: o.order_date || "",
        type: "إرجاع",
        ref: ordRef,
        description: `إرجاع طلبية ${ordRef}`,
        debit: 0,
        credit: total,
      });
    }
  }

  events.sort((a, b) => a.sort - b.sort || a.id.localeCompare(b.id));

  let balance = 0;
  return events.map((e) => {
    balance += e.debit - e.credit;
    return {
      id: e.id,
      date: e.date,
      type: e.type,
      ref: e.ref,
      description: e.description,
      debit: e.debit,
      credit: e.credit,
      balance,
    };
  });
}
