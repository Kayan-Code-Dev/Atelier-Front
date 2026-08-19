/** Number of orders to fetch per API call for returns pages */
export const FETCH_PER_PAGE = 500;

/** API filter: delivered orders for returns — always pass delayed=false */
export const RETURNS_FILTER = {
  status: "delivered" as const,
  delayed: false as const,
} as const;

/** API filter for overdue returns — only delayed orders */
export const OVERDUE_RETURNS_FILTER = {
  delayed: true as const,
} as const;
