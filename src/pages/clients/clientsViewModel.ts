import type { TClientResponse } from "@/api/v2/clients/clients.types";
import { formatDate } from "@/utils/formatDate";

export type ClientUiStatus = "VIP" | "نشط" | "جديد" | "غير نشط";

export function getClientDisplayName(c: TClientResponse): string {
  const n = c.name?.trim();
  if (n) return n;
  const composed = [c.first_name, c.middle_name, c.last_name]
    .filter(Boolean)
    .join(" ")
    .trim();
  return composed || "—";
}

export function getClientCity(c: TClientResponse): string {
  return c.address?.city?.name ?? "—";
}

export function getPrimaryPhone(c: TClientResponse): string {
  return c.phones?.[0]?.phone ?? "—";
}

function isNewClient(createdAt: string): boolean {
  const d = new Date(createdAt);
  if (Number.isNaN(d.getTime())) return false;
  const diff =
    (Date.now() - d.getTime()) / (1000 * 60 * 60 * 24);
  return diff <= 30;
}

/** Heuristic labels for UI (no VIP/segment field on API — referral ≈ VIP for display). */
export function deriveClientStatus(c: TClientResponse): ClientUiStatus {
  if (c.source === "referral") return "VIP";
  if (isNewClient(c.created_at)) return "جديد";
  return "نشط";
}

export function clientCode(id: number): string {
  return `CLT-${String(id).padStart(4, "0")}`;
}

export function joinDateLabel(c: TClientResponse): string {
  return c.created_at ? formatDate(c.created_at) : "—";
}
