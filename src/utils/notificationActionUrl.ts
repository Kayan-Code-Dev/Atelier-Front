/**
 * Metadata that may come with a notification (e.g. supplier_id for supplier orders).
 */
export type NotificationMetadata = Record<string, unknown> | null | undefined;

/**
 * Normalizes notification action_url from backend to frontend routes.
 * Backend may send paths like /supplier-orders/4 which don't exist in the frontend.
 * When metadata.supplier_id is present for a supplier-order notification, the URL
 * becomes /suppliers/orders?supplier_id=X so the user lands on that supplier's orders.
 */
export function normalizeNotificationActionUrl(
  url: string | null | undefined,
  metadata?: NotificationMetadata
): string {
  if (!url || typeof url !== "string") {
    // Build URL from metadata for known types (e.g. SupplierOrder with supplier_id)
    const supplierId = metadata && typeof metadata.supplier_id !== "undefined" && metadata.supplier_id != null
      ? String(metadata.supplier_id)
      : null;
    if (supplierId) return `/suppliers/orders?supplier_id=${encodeURIComponent(supplierId)}`;
    return "/";
  }
  try {
    // Use pathname only (strip origin if full URL)
    let path = url;
    if (url.startsWith("http://") || url.startsWith("https://")) {
      const u = new URL(url);
      path = u.pathname;
    } else if (url.startsWith("//")) {
      path = new URL(`https:${url}`).pathname;
    }
    path = path.replace(/\/+$/, "") || "/";

    // Map backend paths to frontend routes
    const matchSupplierOrder = path.match(/^\/supplier-orders(?:\/(\d+))?\/?$/);
    if (matchSupplierOrder) {
      const supplierId = metadata && typeof metadata.supplier_id !== "undefined" && metadata.supplier_id != null
        ? String(metadata.supplier_id)
        : null;
      if (supplierId) return `/suppliers/orders?supplier_id=${encodeURIComponent(supplierId)}`;
      return "/suppliers/orders";
    }

    return path || "/";
  } catch {
    return "/";
  }
}
