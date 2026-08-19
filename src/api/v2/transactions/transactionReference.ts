import type { TTransaction } from "./transactions.types";

export function isOrderPaymentReference(referenceType: string | null | undefined): boolean {
  if (!referenceType) return false;
  if (referenceType.includes("TailoringOrderPayment")) return false;
  if (referenceType.includes("ReceivablePayment")) return false;
  return /\\Payment$|\/Payment$/.test(referenceType);
}

export function isTailoringOrderPaymentReference(
  referenceType: string | null | undefined,
): boolean {
  return referenceType?.includes("TailoringOrderPayment") ?? false;
}

export function isExpenseReference(referenceType: string | null | undefined): boolean {
  return referenceType?.includes("Expense") ?? false;
}

export function getTailoringOrderIdFromTransaction(tx: TTransaction): number | null {
  const m = tx.metadata as { tailoring_order_id?: number } | null;
  const id = m?.tailoring_order_id;
  return typeof id === "number" && !Number.isNaN(id) ? id : null;
}
