/**
 * Format phone for display: replace leading + with 00 (e.g. +201091422569 → 00201091422569).
 * Use fallback when phone is empty or invalid.
 */
export function formatPhone(
  phone: string | null | undefined,
  fallback: string = "-"
): string {
  if (phone == null || typeof phone !== "string") return fallback;
  const trimmed = phone.trim();
  if (!trimmed) return fallback;
  return trimmed.replace(/^\++/, "00");
}
