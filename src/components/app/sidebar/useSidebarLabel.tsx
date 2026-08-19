import { useMemo } from "react";
import { useAuthStore } from "@/zustand-stores/auth.store";
import { SidebarLabel } from "./constants";

/**
 * Returns permissions from login response only (so sidebar shows only what user has).
 * Supports both top-level permissions and nested data.permissions.
 */
export function useSidebarPermissions(): string[] {
  const loginData = useAuthStore((s) => s.loginData);
  if (!loginData) return [];
  const list =
    (loginData as { permissions?: string[] }).permissions ??
    (loginData as { data?: { permissions?: string[] } }).data?.permissions;
  return Array.isArray(list) ? list : [];
}

/**
 * Recursively filters the sidebar labels based on user permissions.
 *
 * An item is kept only if:
 * 1. The user has at least one of the item's `permissions` (array), OR
 * 2. The user has the item's `permission` (legacy single), OR
 * 3. Any of its descendants are visible (after filtering).
 *
 * Items with no permissions defined (undefined) are treated as public.
 * Items with empty permissions array [] are NOT shown (require at least one permission).
 */
function filterRecursive(
  labels: SidebarLabel[],
  userPermissions: Set<string>
): SidebarLabel[] {
  return labels.reduce((filteredList, item) => {
    let filteredSubItems: SidebarLabel[] | undefined = undefined;
    if (item.subItems && item.subItems.length > 0) {
      filteredSubItems = filterRecursive(item.subItems, userPermissions);
    }

    const hasVisibleChildren = filteredSubItems && filteredSubItems.length > 0;

    const hasAccessByPermissions =
      item.permissions && item.permissions.length > 0
        ? item.permissions.some((p) => userPermissions.has(p))
        : false;
    const hasAccessByPermission = item.permission
      ? userPermissions.has(item.permission)
      : false;
    // Public only when permission key is not set at all (undefined). Empty array = not public.
    const isPublic =
      item.permission == null &&
      (item.permissions == null || item.permissions === undefined);

    const keep =
      isPublic ||
      hasAccessByPermissions ||
      hasAccessByPermission ||
      hasVisibleChildren;

    if (keep) {
      filteredList.push({
        ...item,
        subItems: filteredSubItems,
      });
    }

    return filteredList;
  }, [] as SidebarLabel[]);
}

/**
 * Normalize permissions to a string array (handles API returning objects with .name etc).
 */
function normalizePermissions(value: unknown): string[] {
  if (value == null) return [];
  if (Array.isArray(value)) {
    return value
      .map((p) => (typeof p === "string" ? p : (p as { name?: string })?.name))
      .filter((p): p is string => typeof p === "string" && p.length > 0);
  }
  return [];
}

/**
 * Filters the sidebar by user permissions.
 * User must have at least one of each item's permissions to see it.
 */
export function filterSidebarByPermissions(
  allSidebarLabels: SidebarLabel[],
  userPermissions: string[] | unknown
): SidebarLabel[] {
  const list = normalizePermissions(userPermissions);
  const permissionsSet = new Set(list);
  return filterRecursive(allSidebarLabels, permissionsSet);
}

function useSidebarLabel(labels: SidebarLabel[], myPermissions: string[] | unknown) {
  const ret = useMemo(() => {
    return filterSidebarByPermissions(labels, myPermissions ?? []);
  }, [labels, myPermissions]);
  return ret;
}

export default useSidebarLabel;
