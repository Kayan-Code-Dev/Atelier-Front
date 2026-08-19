/**
 * Route titles for TopBar — derived from sidebarLabels (single source of truth)
 */
import { matchesLocationSearch, SOLD_ORDERS_LIST_LOCATION } from "@/lib/matchLocationSearch";
import { SOLD_PROCESS_TYPE } from "@/lib/salesOrderConstants";
import { sidebarLabels, marketSidebarLabels } from "@/components/app/sidebar/constants";
import type { SidebarLabel } from "@/components/app/sidebar/constants";

export type RouteInfo = { title: string; icon: string; parent?: string };

function collectRoutes(
  items: SidebarLabel[],
  parentLabel?: string
): Record<string, RouteInfo> {
  const out: Record<string, RouteInfo> = {};
  for (const item of items) {
    if (item.path) {
      out[item.path] = {
        title: item.label,
        icon: item.riIcon ?? "ri-file-line",
        parent: parentLabel,
      };
    }
    if (item.subItems?.length) {
      const nextParent = item.path ? item.label : parentLabel;
      Object.assign(out, collectRoutes(item.subItems, nextParent));
    }
  }
  return out;
}

export const routeTitles = {
  ...collectRoutes(sidebarLabels),
  ...collectRoutes(marketSidebarLabels, "Marketplace"),
};

const soldOrdersListInfo: RouteInfo = routeTitles["/sales/invoices"] ?? {
  title: "فواتير البيع",
  icon: "ri-file-list-3-line",
  parent: "قسم البيع",
};

export function getRouteInfo(pathname: string, search = ""): RouteInfo {
  if (matchesLocationSearch(pathname, search, SOLD_ORDERS_LIST_LOCATION)) {
    return soldOrdersListInfo;
  }

  if (/^\/orders\/\d+$/.test(pathname)) {
    const params = new URLSearchParams(
      search.startsWith("?") ? search.slice(1) : search
    );
    if (params.get("process_type") === SOLD_PROCESS_TYPE) {
      return {
        title: "تفاصيل فاتورة البيع",
        icon: "ri-file-list-3-line",
        parent: "قسم البيع",
      };
    }
  }

  if (pathname === "/market/products/new") {
    return { title: "إضافة منتج", icon: "ri-add-line", parent: "Marketplace" };
  }
  if (/^\/market\/products\/[^/]+\/edit$/.test(pathname)) {
    return { title: "تعديل المنتج", icon: "ri-pencil-line", parent: "Marketplace" };
  }
  if (/^\/market\/products\/[^/]+$/.test(pathname)) {
    return { title: "تفاصيل المنتج", icon: "ri-price-tag-3-line", parent: "Marketplace" };
  }
  if (/^\/market\/orders\/[^/]+$/.test(pathname)) {
    return { title: "تفاصيل الطلب", icon: "ri-shopping-bag-3-line", parent: "Marketplace" };
  }
  if (/^\/market\/customers\/[^/]+$/.test(pathname)) {
    return { title: "ملف عميل السوق", icon: "ri-user-star-line", parent: "Marketplace" };
  }
  if (/^\/market\/messages\/[^/]+$/.test(pathname)) {
    return { title: "محادثة", icon: "ri-chat-3-line", parent: "Marketplace" };
  }
  if (/^\/market\/bookings\/[^/]+$/.test(pathname)) {
    return { title: "حجز بروفة", icon: "ri-calendar-check-line", parent: "Marketplace" };
  }

  if (routeTitles[pathname]) return routeTitles[pathname];
  const sorted = Object.entries(routeTitles).sort(([a], [b]) => b.length - a.length);
  for (const [key, val] of sorted) {
    if (pathname.startsWith(key + "/")) return val;
  }
  return { title: "الصفحة", icon: "ri-file-line" };
}
