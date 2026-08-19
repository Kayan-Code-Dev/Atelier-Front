export type LocationSearchMatch = {
  pathname: string;
  search?: Record<string, string>;
};

export function matchesLocationSearch(
  pathname: string,
  search: string,
  m: LocationSearchMatch
): boolean {
  if (pathname !== m.pathname) return false;
  if (!m.search || Object.keys(m.search).length === 0) return true;
  const params = new URLSearchParams(
    search.startsWith("?") ? search.slice(1) : search
  );
  for (const [k, v] of Object.entries(m.search)) {
    if (params.get(k) !== v) return false;
  }
  return true;
}

import { SOLD_PROCESS_TYPE } from "@/lib/salesOrderConstants";

export const SOLD_ORDERS_LIST_LOCATION: LocationSearchMatch = {
  pathname: "/orders/list",
  search: { process_type: SOLD_PROCESS_TYPE },
};
